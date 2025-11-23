<?php
require_once 'config.php';

$conn = getDBConnection();
$allData = [];

// Manhwas
$result = $conn->query("SELECT * FROM manhwas ORDER BY date_added DESC");
while ($row = $result->fetch_assoc()) {
    $allData[] = [
        'manhwa_id' => $row['manhwa_id'],
        'manhwa_title' => $row['manhwa_title'],
        'manhwa_cover' => $row['manhwa_cover'],
        'manhwa_description' => $row['manhwa_description'],
        'manhwa_season' => $row['manhwa_season'],
        'date_added' => $row['date_added'],
        '__backendId' => $row['id']
    ];
}

// Chapitres
$result = $conn->query("SELECT * FROM chapters ORDER BY date_added DESC");
while ($row = $result->fetch_assoc()) {
    $allData[] = [
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

// Tracking
$result = $conn->query("SELECT * FROM tracking ORDER BY date_added DESC");
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

// Trash
$result = $conn->query("SELECT * FROM trash ORDER BY deleted_at DESC");
while ($row = $result->fetch_assoc()) {
    $allData[] = [
        'type' => 'trash',
        'id' => $row['id'],
        'trash_type' => $row['trash_type'],
        'original_data' => json_decode($row['original_data'], true),
        'deleted_at' => $row['deleted_at']
    ];
}

echo json_encode(['success' => true, 'data' => $allData]);
$conn->close();
?>
