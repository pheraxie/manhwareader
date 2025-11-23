<?php
require_once 'config.php';

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
                    'date_added' => $row['date_added'],
                    '__backendId' => $row['id']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $manhwas]);
        break;
        
    case 'POST':
        // Créer un nouveau manhwa
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = 'local_' . time() . '_' . mt_rand();
        $manhwa_id = $data['manhwa_id'] ?? 'manhwa_' . time();
        $title = $conn->real_escape_string($data['manhwa_title'] ?? '');
        $cover = $conn->real_escape_string($data['manhwa_cover'] ?? '');
        $description = $conn->real_escape_string($data['manhwa_description'] ?? '');
        $season = $conn->real_escape_string($data['manhwa_season'] ?? '');
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
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $conn->real_escape_string($data['__backendId'] ?? '');
        
        $title = $conn->real_escape_string($data['manhwa_title'] ?? '');
        $cover = $conn->real_escape_string($data['manhwa_cover'] ?? '');
        $description = $conn->real_escape_string($data['manhwa_description'] ?? '');
        $season = $conn->real_escape_string($data['manhwa_season'] ?? '');
        
        $sql = "UPDATE manhwas SET 
                manhwa_title = '$title',
                manhwa_cover = '$cover',
                manhwa_description = '$description',
                manhwa_season = '$season'
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

