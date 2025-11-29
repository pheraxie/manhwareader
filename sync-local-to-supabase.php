<?php
// sync-local-to-supabase.php
// Synchronise les données locales MySQL vers Supabase (manhwas, chapters, comments)
require_once 'config-env.php';
if (!defined('USE_LOCALHOST') || !USE_LOCALHOST) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Script réservé au mode local']);
    exit;
}
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$supabaseUrl = SUPABASE_URL;
$supabaseKey = SUPABASE_KEY;

function supa_request($method, $path, $body = null) {
    global $supabaseUrl, $supabaseKey;
    $url = rtrim($supabaseUrl, '/') . '/rest/v1/' . ltrim($path, '/');
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $supabaseKey,
        'apikey: ' . $supabaseKey,
        'Prefer: return=representation'
    ];

    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true
        ]
    ];
    if ($body !== null) $opts['http']['content'] = json_encode($body);

    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    $code = null;
    if (isset($http_response_header) && preg_match('#HTTP/\d\.\d\s+(\d+)#', $http_response_header[0], $m)) $code = intval($m[1]);
    return ['status' => $code, 'body' => $res, 'headers' => $http_response_header ?? []];
}

$conn = getDBConnection();

$report = ['manhwas_inserted' => 0, 'chapters_inserted' => 0, 'comments_inserted' => 0, 'errors' => []];

// --- Step 0: Ensure local DB contains chapters that exist as folders for mapped manhwas ---
// Load manhwa-folder mappings saved by api-manhwa-folders.php
$mapFile = __DIR__ . DIRECTORY_SEPARATOR . 'manhwa-folders.json';
$mappings = [];
if (file_exists($mapFile)) {
    $raw = file_get_contents($mapFile);
    $mappings = json_decode($raw, true) ?: [];
}

// Helper to scan folder for numeric chapter subfolders
function scan_folder_for_numbers($baseDir, $folder) {
    $out = [];
    $path = realpath($baseDir . DIRECTORY_SEPARATOR . $folder);
    if ($path === false || strpos($path, realpath($baseDir)) !== 0 || !is_dir($path)) return $out;
    $items = scandir($path);
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        $full = $path . DIRECTORY_SEPARATOR . $it;
        if (is_dir($full)) {
            if (preg_match('/(\d+)/', $it, $m)) $out[] = (int)$m[1];
        }
    }
    sort($out, SORT_NUMERIC);
    return array_values(array_unique($out));
}

// Base chapters directory
$chapBase = __DIR__ . DIRECTORY_SEPARATOR . 'chapitres';
foreach ($mappings as $mid => $folder) {
    $nums = scan_folder_for_numbers($chapBase, $folder);
    foreach ($nums as $num) {
        // Check local DB if chapter exists
        $stmt = $conn->prepare("SELECT id FROM chapters WHERE manhwa_id = ? AND chapter_number = ? LIMIT 1");
        $stmt->bind_param('si', $mid, $num);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = $res && $res->num_rows > 0;
        $stmt->close();
        if (!$exists) {
            // Insert minimal row locally
            $id = 'local_' . time() . '_' . mt_rand();
            $manhwa_id = $conn->real_escape_string($mid);
            $chapter_number = (int)$num;
            $date_added = date('Y-m-d H:i:s');
            $sql = "INSERT INTO chapters (id, manhwa_id, chapter_number, date_added) VALUES ('{$id}', '{$manhwa_id}', {$chapter_number}, '{$date_added}')";
            @$conn->query($sql);
        }
    }
}

// Load local manhwas
$res = $conn->query("SELECT * FROM manhwas ORDER BY date_added DESC");
$localManhwas = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $localManhwas[] = $row;
}

// Load local chapters
$res = $conn->query("SELECT * FROM chapters ORDER BY date_added DESC");
$localChapters = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $localChapters[] = $row;
}

// Load local comments
$res = $conn->query("SELECT * FROM comments ORDER BY date DESC");
$localComments = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $localComments[] = $row;
}

// Get existing keys from Supabase
$m = supa_request('GET', 'manhwas?select=manhwa_id');
$existingManhwas = [];
if ($m['status'] === 200 && $m['body']) {
    $arr = json_decode($m['body'], true);
    foreach ($arr as $r) if (isset($r['manhwa_id'])) $existingManhwas[$r['manhwa_id']] = true;
}

$c = supa_request('GET', 'chapters?select=id,manhwa_id,chapter_number');
$existingChapters = [];
$existingChapterIds = [];
if ($c['status'] === 200 && $c['body']) {
    $arr = json_decode($c['body'], true);
    foreach ($arr as $r) {
        if (isset($r['manhwa_id']) && isset($r['chapter_number'])) $existingChapters[$r['manhwa_id'].'|'.$r['chapter_number']] = true;
        if (isset($r['id'])) $existingChapterIds[$r['id']] = true;
    }
}

$co = supa_request('GET', 'comments?select=id');
$existingComments = [];
if ($co['status'] === 200 && $co['body']) {
    $arr = json_decode($co['body'], true);
    foreach ($arr as $r) if (isset($r['id'])) $existingComments[$r['id']] = true;
}

// Prepare local id sets for deletion checks
$localManhwaIds = [];
foreach ($localManhwas as $lm) if (isset($lm['id'])) $localManhwaIds[$lm['id']] = true;
$localChapterIds = [];
foreach ($localChapters as $lc) if (isset($lc['id'])) $localChapterIds[$lc['id']] = true;
$localCommentIds = [];
foreach ($localComments as $lco) if (isset($lco['id'])) $localCommentIds[$lco['id']] = true;

// --- Deletions: remove remote rows that were created locally but deleted locally (id starts with local_)
// Delete remote manhwas
$remoteFullManhwas = supa_request('GET', 'manhwas?select=id,manhwa_id');
if ($remoteFullManhwas['status'] === 200 && $remoteFullManhwas['body']) {
    $arr = json_decode($remoteFullManhwas['body'], true);
    foreach ($arr as $r) {
        if (!isset($r['id'])) continue;
        $rid = $r['id'];
        if (strpos($rid, 'local_') === 0 && !isset($localManhwaIds[$rid])) {
            supa_request('DELETE', "manhwas?id=eq.{$rid}");
        }
    }
}

// Delete remote chapters
$remoteFullChapters = supa_request('GET', 'chapters?select=id,manhwa_id,chapter_number');
if ($remoteFullChapters['status'] === 200 && $remoteFullChapters['body']) {
    $arr = json_decode($remoteFullChapters['body'], true);
    foreach ($arr as $r) {
        if (!isset($r['id'])) continue;
        $rid = $r['id'];
        if (strpos($rid, 'local_') === 0 && !isset($localChapterIds[$rid])) {
            supa_request('DELETE', "chapters?id=eq.{$rid}");
        }
    }
}

// Delete remote comments
$remoteFullComments = supa_request('GET', 'comments?select=id');
if ($remoteFullComments['status'] === 200 && $remoteFullComments['body']) {
    $arr = json_decode($remoteFullComments['body'], true);
    foreach ($arr as $r) {
        if (!isset($r['id'])) continue;
        $rid = $r['id'];
        if (strpos($rid, 'local_') === 0 && !isset($localCommentIds[$rid])) {
            supa_request('DELETE', "comments?id=eq.{$rid}");
        }
    }
}

$toInsertManhwas = [];
foreach ($localManhwas as $m) {
    if (!isset($existingManhwas[$m['manhwa_id']])) {
        $toInsertManhwas[] = [
            'id' => isset($m['id']) && $m['id'] ? $m['id'] : 'local_'.time().'_'.mt_rand(),
            'manhwa_id' => $m['manhwa_id'],
            'manhwa_title' => $m['manhwa_title'],
            'manhwa_cover' => $m['manhwa_cover'],
            'manhwa_description' => $m['manhwa_description'],
            'manhwa_season' => $m['manhwa_season'],
            'read_count' => (int)($m['read_count'] ?? 0),
            'order_index' => (int)($m['order_index'] ?? 0),
            'last_read_at' => $m['last_read_at'] ?? null,
            'date_added' => $m['date_added'] ?? null
        ];
    }
}
if (count($toInsertManhwas) > 0) {
    $r = supa_request('POST', 'manhwas', $toInsertManhwas);
    if ($r['status'] >= 200 && $r['status'] < 300) $report['manhwas_inserted'] = count($toInsertManhwas);
    else $report['errors'][] = ['manhwas' => $r];
}

// Update existing manhwas on Supabase with local values (simple overwrite)
foreach ($localManhwas as $m) {
    if (isset($existingManhwas[$m['manhwa_id']])) {
        $payload = [
            'manhwa_title' => $m['manhwa_title'] ?? null,
            'manhwa_cover' => $m['manhwa_cover'] ?? null,
            'manhwa_description' => $m['manhwa_description'] ?? null,
            'manhwa_season' => $m['manhwa_season'] ?? null,
            'read_count' => (int)($m['read_count'] ?? 0),
            'order_index' => (int)($m['order_index'] ?? 0),
            'last_read_at' => $m['last_read_at'] ?? null
        ];
        $r = supa_request('PATCH', "manhwas?manhwa_id=eq.{$m['manhwa_id']}", $payload);
        if (!($r['status'] >= 200 && $r['status'] < 300)) $report['errors'][] = ['manhwas_update' => $r];
    }
}

$toInsertChapters = [];
foreach ($localChapters as $ch) {
    $key = $ch['manhwa_id'].'|'.$ch['chapter_number'];
    if (!isset($existingChapters[$key])) {
        // Skip if chapter id already exists remotely or manhwa|chapter exists
        $remoteKey = $ch['manhwa_id'].'|'.$ch['chapter_number'];
        if (isset($existingChapterIds[$ch['id']]) || isset($existingChapters[$remoteKey])) continue;
        $toInsertChapters[] = [
            'id' => isset($ch['id']) ? (string)$ch['id'] : null,
            'manhwa_id' => $ch['manhwa_id'],
            'chapter_number' => (int)$ch['chapter_number'],
            'chapter_title' => $ch['chapter_title'],
            'chapter_description' => $ch['chapter_description'],
            'chapter_season' => $ch['chapter_season'],
            'last_read_at' => $ch['last_read_at'] ?? null,
            'chapter_pages' => $ch['chapter_pages'] ?? null,
            'chapter_cover' => $ch['chapter_cover'] ?? null,
            'is_favorite' => isset($ch['is_favorite']) ? (int)($ch['is_favorite'] ? 1 : 0) : 0,
            'date_added' => $ch['date_added'] ?? null
        ];
    }
}
if (count($toInsertChapters) > 0) {
    $r = supa_request('POST', 'chapters', $toInsertChapters);
    if ($r['status'] >= 200 && $r['status'] < 300) $report['chapters_inserted'] = count($toInsertChapters);
    else $report['errors'][] = ['chapters' => $r];
}

// Update existing chapters on Supabase with local values
foreach ($localChapters as $ch) {
    $key = $ch['manhwa_id'].'|'.$ch['chapter_number'];
    if (isset($existingChapters[$key]) || isset($existingChapterIds[$ch['id']])) {
        $payload = [
            'chapter_title' => $ch['chapter_title'] ?? null,
            'chapter_description' => $ch['chapter_description'] ?? null,
            'chapter_season' => $ch['chapter_season'] ?? null,
            'last_read_at' => $ch['last_read_at'] ?? null,
            'chapter_pages' => $ch['chapter_pages'] ?? null,
            'chapter_cover' => $ch['chapter_cover'] ?? null,
            'is_favorite' => isset($ch['is_favorite']) ? (int)($ch['is_favorite'] ? 1 : 0) : 0
        ];
        // Prefer update by id when available
        if (isset($ch['id']) && isset($existingChapterIds[$ch['id']])) {
            $r = supa_request('PATCH', "chapters?id=eq.{$ch['id']}", $payload);
        } else {
            $r = supa_request('PATCH', "chapters?manhwa_id=eq.{$ch['manhwa_id']}&chapter_number=eq.{$ch['chapter_number']}", $payload);
        }
        if (!($r['status'] >= 200 && $r['status'] < 300)) $report['errors'][] = ['chapters_update' => $r];
    }
}

$toInsertComments = [];
foreach ($localComments as $cm) {
    if (!isset($existingComments[$cm['id']])) {
        $toInsertComments[] = [
            'id' => $cm['id'],
            'manhwa_id' => $cm['manhwa_id'],
            'chapter_number' => isset($cm['chapter_number']) ? (int)$cm['chapter_number'] : null,
            'author' => $cm['author'] ?? null,
            'comment_text' => $cm['text'] ?? null,
            'images' => $cm['images'] ?? null,
            'date' => $cm['date'] ?? null
        ];
    }
}
if (count($toInsertComments) > 0) {
    $r = supa_request('POST', 'comments', $toInsertComments);
    if ($r['status'] >= 200 && $r['status'] < 300) $report['comments_inserted'] = count($toInsertComments);
    else $report['errors'][] = ['comments' => $r];
}

// Update existing comments
foreach ($localComments as $cm) {
    if (isset($existingComments[$cm['id']])) {
        $payload = [
            'author' => $cm['author'] ?? null,
            'comment_text' => $cm['text'] ?? null,
            'images' => $cm['images'] ?? null,
            'date' => $cm['date'] ?? null
        ];
        $r = supa_request('PATCH', "comments?id=eq.{$cm['id']}", $payload);
        if (!($r['status'] >= 200 && $r['status'] < 300)) $report['errors'][] = ['comments_update' => $r];
    }
}

echo json_encode(['success' => true, 'report' => $report]);

$conn->close();

?>
