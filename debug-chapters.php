<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$conn = getDBConnection();
$result = $conn->query("SELECT id, manhwa_id, chapter_number, chapter_pages, chapter_cover FROM chapters WHERE manhwa_id='passion' LIMIT 5");

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode(['success' => true, 'chapters' => $data], JSON_PRETTY_PRINT);
$conn->close();
?>
