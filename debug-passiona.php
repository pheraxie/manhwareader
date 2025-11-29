<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$conn = getDBConnection();

// Chercher les chapitres pour Passiona
$result = $conn->query("SELECT id, manhwa_id, chapter_number, chapter_pages, chapter_cover FROM chapters WHERE manhwa_id='manhwa_1763894842685' LIMIT 5");

$chapters = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $chapters[] = $row;
    }
}

echo json_encode(['chapters' => $chapters], JSON_PRETTY_PRINT);
$conn->close();
?>
