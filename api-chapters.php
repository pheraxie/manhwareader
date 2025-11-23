<?php
require_once 'config.php';

// Ensure we always return JSON and capture fatal errors
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
ob_start();
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        while (ob_get_level()) ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $err['message']]);
    }
});

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

switch ($method) {
    case 'GET':
        // Récupérer tous les chapitres (optionnel: filtrer par manhwa_id)
        $manhwa_id = $_GET['manhwa_id'] ?? null;
        
        if ($manhwa_id) {
            $manhwa_id = $conn->real_escape_string($manhwa_id);
            $sql = "SELECT * FROM chapters WHERE manhwa_id = '$manhwa_id' ORDER BY chapter_number DESC";
        } else {
            $sql = "SELECT * FROM chapters ORDER BY date_added DESC";
        }
        
        $result = $conn->query($sql);
        
        $chapters = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $chapters[] = [
                    'manhwa_id' => $row['manhwa_id'],
                    'chapter_number' => (int)$row['chapter_number'],
                    'chapter_title' => $row['chapter_title'],
                    'chapter_description' => $row['chapter_description'],
                    'chapter_season' => $row['chapter_season'],
                    'chapter_pages' => $row['chapter_pages'],
                    'chapter_cover' => $row['chapter_cover'],
                    'is_favorite' => (bool)$row['is_favorite'],
                    'date_added' => $row['date_added'],
                    '__backendId' => $row['id']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $chapters]);
        break;
        
    case 'POST':
        // Créer un nouveau chapitre
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if ($raw && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Payload JSON invalide']);
            break;
        }
        
        $id = 'local_' . time() . '_' . mt_rand();
        $manhwa_id = $conn->real_escape_string($data['manhwa_id'] ?? '');
            $chapter_number = (int)($data['chapter_number'] ?? 0);
        $title = $conn->real_escape_string($data['chapter_title'] ?? '');
        $description = $conn->real_escape_string($data['chapter_description'] ?? '');
        $season = $conn->real_escape_string($data['chapter_season'] ?? '');
        $pages = $conn->real_escape_string($data['chapter_pages'] ?? '');
        $cover = $conn->real_escape_string($data['chapter_cover'] ?? '');
        $is_favorite = isset($data['is_favorite']) && $data['is_favorite'] ? 1 : 0;
        $date_added = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO chapters (id, manhwa_id, chapter_number, chapter_title, chapter_description, chapter_season, chapter_pages, chapter_cover, is_favorite, date_added) 
                VALUES ('$id', '$manhwa_id', $chapter_number, '$title', '$description', '$season', '$pages', '$cover', $is_favorite, '$date_added')";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'PUT':
        // Mettre à jour un chapitre
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if ($raw && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Payload JSON invalide']);
            break;
        }
        $id = $conn->real_escape_string($data['__backendId'] ?? '');
        
            $chapter_number = (int)($data['chapter_number'] ?? 0);
        $title = $conn->real_escape_string($data['chapter_title'] ?? '');
        $description = $conn->real_escape_string($data['chapter_description'] ?? '');
        $season = $conn->real_escape_string($data['chapter_season'] ?? '');
        $pages = $conn->real_escape_string($data['chapter_pages'] ?? '');
        $cover = $conn->real_escape_string($data['chapter_cover'] ?? '');
        $is_favorite = isset($data['is_favorite']) && $data['is_favorite'] ? 1 : 0;
        
        $sql = "UPDATE chapters SET 
            chapter_number = $chapter_number,
            chapter_title = '$title',
            chapter_description = '$description',
            chapter_season = '$season',
            chapter_pages = '$pages',
            chapter_cover = '$cover',
            is_favorite = $is_favorite
            WHERE id = '$id'";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'DELETE':
        // Supprimer un chapitre
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $conn->real_escape_string($data['__backendId'] ?? '');
        
        $sql = "DELETE FROM chapters WHERE id = '$id'";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
}

$conn->close();

