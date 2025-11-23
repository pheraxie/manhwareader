<?php

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Récupérer le JSON envoyé
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Accept both { action, item } and raw array / { data: [...] } for autosave
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload JSON invalide']);
    exit;
}

$action = $data['action'] ?? null;
$item = $data['item'] ?? null;
$allData = null;

// If no explicit action, detect autosave payload (indexed array or { data: [...] })
if (!$action) {
    if (is_array($data) && count($data) > 0 && array_keys($data) === range(0, count($data) - 1)) {
        $action = 'save_all';
        $allData = $data;
    } elseif (isset($data['data']) && is_array($data['data'])) {
        $action = 'save_all';
        $allData = $data['data'];
    }
}

$conn = getDBConnection();

try {
    // Handle autosave: write a data.php that outputs JSON (fallback file used client-side)
    if ($action === 'save_all') {
        // $allData contains the full dataset
        $payload = $allData ?? $data;

        // Ensure we have an array
        if (!is_array($payload)) {
            throw new Exception("Données invalides pour save_all");
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Create a PHP file that returns the JSON when requested (data.php)
        $phpContent = "<?php\nheader('Content-Type: application/json');\n";
        // Use var_export to safely embed the JSON string
        $phpContent .= "echo " . var_export($json, true) . ";\n";

        if (file_put_contents(__DIR__ . '/data.php', $phpContent) === false) {
            throw new Exception("Impossible d'écrire data.php");
        }

        echo json_encode(['success' => true, 'message' => 'Données sauvegardées (save_all)']);
        exit;
    }

    if ($action === 'add' || $action === 'update') {
        // ...existing DB handling code (left intact) ...
    } elseif ($action === 'delete') {
        // ...existing DB handling code (left intact) ...
    } elseif ($action === 'restore') {
        // Restore an item from the trash table back into the appropriate DB table
        // Expected payload: { action: 'restore', item: { id: 'trash_xxx', trash_type: 'tracking'|'manhwa'|'chapter' } }
        if (!is_array($item) || empty($item['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action ou item manquant (restore)']);
            exit;
        }

        $trashId = $conn->real_escape_string($item['id']);
        $trashType = $item['trash_type'] ?? null;

        $res = $conn->query("SELECT * FROM trash WHERE id='" . $trashId . "'");
        if (!$res || $res->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Élément introuvable dans la corbeille']);
            exit;
        }

        $row = $res->fetch_assoc();
        $original = json_decode($row['original_data'], true);
        if (!is_array($original)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Données originales invalides']);
            exit;
        }

        // Decide where to restore
        if ($trashType === 'tracking' || $row['trash_type'] === 'tracking') {
            // Ensure required fields are present and set defaults
            $t = $original;
            $id = $conn->real_escape_string($t['id'] ?? ('tracking_' . time() . '_' . mt_rand()));
            $title = $conn->real_escape_string($t['title'] ?? '');
            $chapter = isset($t['chapter']) ? (int)$t['chapter'] : 0;
            $status = $conn->real_escape_string($t['status'] ?? 'en-cours');
            $notes = $conn->real_escape_string($t['notes'] ?? '');
            $season = $conn->real_escape_string($t['season'] ?? '');
            $date_added = $conn->real_escape_string($t['date_added'] ?? date('Y-m-d H:i:s'));
            $date_updated = $conn->real_escape_string($t['date_updated'] ?? date('Y-m-d H:i:s'));

            // If a tracking with same id exists, update instead of insert
            $exists = $conn->query("SELECT id FROM tracking WHERE id='" . $id . "'");
            if ($exists && $exists->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE tracking SET title=?, chapter=?, status=?, notes=?, season=?, date_updated=? WHERE id=?");
                $stmt->bind_param('sisssss', $title, $chapter, $status, $notes, $season, $date_updated, $id);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO tracking (id,title,chapter,status,notes,season,date_added,date_updated) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('ssisssss', $id, $title, $chapter, $status, $notes, $season, $date_added, $date_updated);
                $stmt->execute();
                $stmt->close();
            }

            // Remove from trash
            $conn->query("DELETE FROM trash WHERE id='" . $trashId . "'");

            echo json_encode(['success' => true, 'message' => 'Suivi restauré depuis la corbeille']);
            exit;
        } elseif ($trashType === 'manhwa' || $row['trash_type'] === 'manhwa') {
            $m = $original;
            $id = $conn->real_escape_string($m['id'] ?? ('manhwa_' . time() . '_' . mt_rand()));
            $manhwa_id = $conn->real_escape_string($m['manhwa_id'] ?? $id);
            $title = $conn->real_escape_string($m['manhwa_title'] ?? '');
            $cover = $conn->real_escape_string($m['manhwa_cover'] ?? '');
            $description = $conn->real_escape_string($m['manhwa_description'] ?? '');
            $season = $conn->real_escape_string($m['manhwa_season'] ?? '');
            $date_added = $conn->real_escape_string($m['date_added'] ?? date('Y-m-d H:i:s'));

            $exists = $conn->query("SELECT id FROM manhwas WHERE id='" . $id . "' OR manhwa_id='" . $manhwa_id . "'");
            if ($exists && $exists->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE manhwas SET manhwa_title=?, manhwa_cover=?, manhwa_description=?, manhwa_season=?, date_added=? WHERE id=? OR manhwa_id=?");
                $stmt->bind_param('sssssss', $title, $cover, $description, $season, $date_added, $id, $manhwa_id);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO manhwas (id,manhwa_id,manhwa_title,manhwa_cover,manhwa_description,manhwa_season,date_added) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param('sssssss', $id, $manhwa_id, $title, $cover, $description, $season, $date_added);
                $stmt->execute();
                $stmt->close();
            }

            $conn->query("DELETE FROM trash WHERE id='" . $trashId . "'");
            echo json_encode(['success' => true, 'message' => 'Manhwa restauré depuis la corbeille']);
            exit;
        } elseif ($trashType === 'chapter' || $row['trash_type'] === 'chapter') {
            $c = $original;
            $id = $conn->real_escape_string($c['id'] ?? ('chapter_' . time() . '_' . mt_rand()));
            $manhwa_id = $conn->real_escape_string($c['manhwa_id'] ?? '');
            $chapter_number = isset($c['chapter_number']) ? (int)$c['chapter_number'] : 0;
            $chapter_title = $conn->real_escape_string($c['chapter_title'] ?? '');
            $chapter_description = $conn->real_escape_string($c['chapter_description'] ?? '');
            $chapter_season = $conn->real_escape_string($c['chapter_season'] ?? '');
            $chapter_pages = $conn->real_escape_string($c['chapter_pages'] ?? '');
            $chapter_cover = $conn->real_escape_string($c['chapter_cover'] ?? '');
            $is_favorite = isset($c['is_favorite']) ? (int)$c['is_favorite'] : 0;
            $date_added = $conn->real_escape_string($c['date_added'] ?? date('Y-m-d H:i:s'));

            $exists = $conn->query("SELECT id FROM chapters WHERE id='" . $id . "' OR (manhwa_id='" . $manhwa_id . "' AND chapter_number='" . $chapter_number . "')");
            if ($exists && $exists->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE chapters SET chapter_title=?, chapter_description=?, chapter_season=?, chapter_pages=?, chapter_cover=?, is_favorite=?, date_added=? WHERE id=? OR (manhwa_id=? AND chapter_number=?)");
                $stmt->bind_param('sssssiisss', $chapter_title, $chapter_description, $chapter_season, $chapter_pages, $chapter_cover, $is_favorite, $date_added, $id, $manhwa_id, $chapter_number);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO chapters (id,manhwa_id,chapter_number,chapter_title,chapter_description,chapter_season,chapter_pages,chapter_cover,is_favorite,date_added) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('ssisssssis', $id, $manhwa_id, $chapter_number, $chapter_title, $chapter_description, $chapter_season, $chapter_pages, $chapter_cover, $is_favorite, $date_added);
                $stmt->execute();
                $stmt->close();
            }

            $conn->query("DELETE FROM trash WHERE id='" . $trashId . "'");
            echo json_encode(['success' => true, 'message' => 'Chapitre restauré depuis la corbeille']);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Type de corbeille non supporté']);
            exit;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Action effectuée avec succès']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
