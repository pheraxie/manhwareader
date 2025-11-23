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

if (!$data || !isset($data['action']) || !isset($data['item'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action ou item manquant']);
    exit;
}

$action = $data['action'];
$item = $data['item'];
$conn = getDBConnection();

try {
    if ($action === 'add' || $action === 'update') {
        // Déterminer la table
        if (isset($item['manhwa_title'])) {
            $table = 'manhwas';
            $id = $item['__backendId'] ?? 'local_' . time() . '_' . mt_rand();
            $manhwa_id = $item['manhwa_id'] ?? 'manhwa_' . time();
            $title = $conn->real_escape_string($item['manhwa_title']);
            $cover = $conn->real_escape_string($item['manhwa_cover'] ?? '');
            $description = $conn->real_escape_string($item['manhwa_description'] ?? '');
            $season = $conn->real_escape_string($item['manhwa_season'] ?? '');
            $date_added = $item['date_added'] ?? date('Y-m-d H:i:s');

            $sql = "INSERT INTO manhwas (id, manhwa_id, manhwa_title, manhwa_cover, manhwa_description, manhwa_season, date_added)
                    VALUES ('$id', '$manhwa_id', '$title', '$cover', '$description', '$season', '$date_added')
                    ON DUPLICATE KEY UPDATE
                    manhwa_title='$title', manhwa_cover='$cover', manhwa_description='$description', manhwa_season='$season'";
            $conn->query($sql);
        } elseif (isset($item['chapter_number'])) {
            $table = 'chapters';
            $id = $item['__backendId'] ?? 'local_' . time() . '_' . mt_rand();
            $manhwa_id = $conn->real_escape_string($item['manhwa_id']);
            $chapter_number = (int)$item['chapter_number'];
            $title = $conn->real_escape_string($item['chapter_title'] ?? '');
            $description = $conn->real_escape_string($item['chapter_description'] ?? '');
            $season = $conn->real_escape_string($item['chapter_season'] ?? '');
            $pages = $conn->real_escape_string($item['chapter_pages'] ?? '');
            $cover = $conn->real_escape_string($item['chapter_cover'] ?? '');
            $is_favorite = isset($item['is_favorite']) && $item['is_favorite'] ? 1 : 0;
            $date_added = $item['date_added'] ?? date('Y-m-d H:i:s');

            $sql = "INSERT INTO chapters (id, manhwa_id, chapter_number, chapter_title, chapter_description, chapter_season, chapter_pages, chapter_cover, is_favorite, date_added)
                    VALUES ('$id', '$manhwa_id', $chapter_number, '$title', '$description', '$season', '$pages', '$cover', $is_favorite, '$date_added')
                    ON DUPLICATE KEY UPDATE
                    chapter_title='$title', chapter_description='$description', chapter_season='$season', chapter_pages='$pages', chapter_cover='$cover', is_favorite=$is_favorite";
            $conn->query($sql);
        } elseif (isset($item['type']) && $item['type'] === 'tracking') {
            $table = 'tracking';
            $id = $item['id'] ?? 'tracking_' . time() . '_' . mt_rand();
            $title = $conn->real_escape_string($item['title'] ?? '');
            $chapter = (int)($item['chapter'] ?? 0);
            $status = $conn->real_escape_string($item['status'] ?? 'en-cours');
            $notes = $conn->real_escape_string($item['notes'] ?? '');
            $season = $conn->real_escape_string($item['season'] ?? '');
            $date_added = $item['date_added'] ?? date('Y-m-d H:i:s');
            $date_updated = $item['date_updated'] ?? $date_added;

            $sql = "INSERT INTO tracking (id, title, chapter, status, notes, season, date_added, date_updated)
                    VALUES ('$id', '$title', $chapter, '$status', '$notes', '$season', '$date_added', '$date_updated')
                    ON DUPLICATE KEY UPDATE
                    title='$title', chapter=$chapter, status='$status', notes='$notes', season='$season', date_updated='$date_updated'";
            $conn->query($sql);
        }
    } elseif ($action === 'delete') {
        if (!isset($item['type']) || !isset($item['id'])) {
            throw new Exception("Item type ou id manquant");
        }
        $type = $item['type'];
        $id = $item['id'];

        if ($type === 'tracking') {
            // Récupérer l'élément avant suppression pour le mettre dans trash
            $res = $conn->query("SELECT * FROM tracking WHERE id='$id'");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $original_data = $conn->real_escape_string(json_encode($row));
                $deleted_at = date('Y-m-d H:i:s');
                $conn->query("INSERT INTO trash (id, trash_type, original_data, deleted_at)
                              VALUES ('trash_$id', 'tracking', '$original_data', '$deleted_at')");
                // Supprimer le suivi
                $conn->query("DELETE FROM tracking WHERE id='$id'");
            }
        } elseif ($type === 'trash') {
            // Suppression définitive
            $conn->query("DELETE FROM trash WHERE id='$id'");
        }
    } elseif ($action === 'restore') {
        // Restaurer un élément depuis trash
        if (!isset($item['id']) || !isset($item['trash_type'])) {
            throw new Exception("Item id ou trash_type manquant pour restauration");
        }
        $res = $conn->query("SELECT * FROM trash WHERE id='".$item['id']."'");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $dataToRestore = json_decode($row['original_data'], true);

            if ($item['trash_type'] === 'tracking') {
                $idRestore = $dataToRestore['id'] ?? 'tracking_' . time() . '_' . mt_rand();
                $title = $conn->real_escape_string($dataToRestore['title'] ?? '');
                $chapter = (int)($dataToRestore['chapter'] ?? 0);
                $status = $conn->real_escape_string($dataToRestore['status'] ?? 'en-cours');
                $notes = $conn->real_escape_string($dataToRestore['notes'] ?? '');
                $season = $conn->real_escape_string($dataToRestore['season'] ?? '');
                $date_added = $dataToRestore['date_added'] ?? date('Y-m-d H:i:s');
                $date_updated = $dataToRestore['date_updated'] ?? $date_added;

                $sql = "INSERT INTO tracking (id, title, chapter, status, notes, season, date_added, date_updated)
                        VALUES ('$idRestore', '$title', $chapter, '$status', '$notes', '$season', '$date_added', '$date_updated')
                        ON DUPLICATE KEY UPDATE title='$title', chapter=$chapter, status='$status', notes='$notes', season='$season', date_updated='$date_updated'";
                $conn->query($sql);
            }
            // Supprimer de trash après restauration
            $conn->query("DELETE FROM trash WHERE id='".$item['id']."'");
        }
    }

    echo json_encode(['success' => true, 'message' => 'Action effectuée avec succès']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
