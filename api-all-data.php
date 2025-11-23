<?php
require_once 'config.php';

// Récupérer TOUTES les données (manhwas, chapitres, tracking, trash)
$conn = getDBConnection();

$allData = [];

// Optionnel: filtrer les données par utilisateur (pour les suivis/commentaires)
// Ne pas filtrer par user_id par défaut (mono-utilisateur). Le param user_id est optionnel et ignoré ici.
$user_id = null;

// Récupérer les manhwas
$sql = "SELECT * FROM manhwas ORDER BY date_added DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allData[] = [
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

// Récupérer les chapitres
$sql = "SELECT * FROM chapters ORDER BY date_added DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allData[] = [
            'manhwa_id' => $row['manhwa_id'],
            'chapter_number' => (int)$row['chapter_number'],
            'chapter_title' => $row['chapter_title'],
            'chapter_description' => $row['chapter_description'],
            'chapter_season' => $row['chapter_season'],
                'last_read_at' => $row['last_read_at'] ?? null,
            'chapter_pages' => $row['chapter_pages'],
            'chapter_cover' => $row['chapter_cover'],
            'is_favorite' => (bool)$row['is_favorite'],
            'date_added' => $row['date_added'],
            '__backendId' => $row['id']
        ];
    }
}

$sql = "SELECT * FROM tracking";
if ($user_id) {
    $sql .= " WHERE user_id='".$user_id."'";
}
$sql .= " ORDER BY date_added DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allData[] = [
            'type' => 'tracking',
            'id' => $row['id'],
            'title' => $row['title'],
            'chapter' => (int)$row['chapter'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'season' => $row['season'],
            'date_added' => $row['date_added'],
            'date_updated' => $row['date_updated']
        ];
    }
}

// Récupérer la corbeille
$sql = "SELECT * FROM trash ORDER BY deleted_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allData[] = [
            'type' => 'trash',
            'id' => $row['id'],
            'trash_type' => $row['trash_type'],
            'original_data' => json_decode($row['original_data'], true),
            'deleted_at' => $row['deleted_at']
        ];
    }
}

echo json_encode(['success' => true, 'data' => $allData]);

$conn->close();

