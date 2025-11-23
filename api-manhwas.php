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
        // Récupérer tous les manhwas
        $sql = "SELECT * FROM manhwas ORDER BY date_added DESC";
        $result = $conn->query($sql);
        
        $manhwas = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $manhwas[] = [
                    'manhwa_id' => $row['manhwa_id'],
                    'manhwa_title' => $row['manhwa_title'],
                    'manhwa_cover' => $row['manhwa_cover'],
                    'manhwa_description' => $row['manhwa_description'],
                    'manhwa_season' => $row['manhwa_season'],
                    'read_count' => isset($row['read_count']) ? (int)$row['read_count'] : 0,
                    'order_index' => isset($row['order_index']) ? (int)$row['order_index'] : 0,
                    'last_read_at' => $row['last_read_at'] ?? null,
                    'date_added' => $row['date_added'],
                    '__backendId' => $row['id']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $manhwas]);
        break;
        
    case 'POST':
        // Créer un nouveau manhwa
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if ($raw && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Payload JSON invalide']);
            break;
        }
        
        $id = 'local_' . time() . '_' . mt_rand();
        $manhwa_id = $data['manhwa_id'] ?? 'manhwa_' . time();
        $title = $conn->real_escape_string($data['manhwa_title'] ?? '');
        $cover = $conn->real_escape_string($data['manhwa_cover'] ?? '');
        $description = $conn->real_escape_string($data['manhwa_description'] ?? '');
        $season = $conn->real_escape_string($data['manhwa_season'] ?? '');
        $order_index = isset($data['order_index']) ? (int)$data['order_index'] : 0;
        $read_count = isset($data['read_count']) ? (int)$data['read_count'] : 0;
        $date_added = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO manhwas (id, manhwa_id, manhwa_title, manhwa_cover, manhwa_description, manhwa_season, date_added) 
                VALUES ('$id', '$manhwa_id', '$title', '$cover', '$description', '$season', '$date_added')";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'PUT':
        // Mettre à jour un manhwa
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if ($raw && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Payload JSON invalide']);
            break;
        }
        $id = $conn->real_escape_string($data['__backendId'] ?? '');
        
        $title = $conn->real_escape_string($data['manhwa_title'] ?? '');
        $cover = $conn->real_escape_string($data['manhwa_cover'] ?? '');
        $description = $conn->real_escape_string($data['manhwa_description'] ?? '');
        $season = $conn->real_escape_string($data['manhwa_season'] ?? '');
            // Récupérer les champs additionnels (défaut 0)
            $order_index = isset($data['order_index']) ? (int)$data['order_index'] : 0;
            $read_count = isset($data['read_count']) ? (int)$data['read_count'] : 0;
        
            $sql = "UPDATE manhwas SET 
                manhwa_title = '$title',
                manhwa_cover = '$cover',
                manhwa_description = '$description',
                manhwa_season = '$season',
                order_index = $order_index,
                read_count = $read_count
                WHERE id = '$id'";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'DELETE':
        // Supprimer un manhwa
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $conn->real_escape_string($data['__backendId'] ?? '');
        
        $sql = "DELETE FROM manhwas WHERE id = '$id'";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
}

$conn->close();

