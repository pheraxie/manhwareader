<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$conn = getDBConnection();

// Vérifier combien de chapitres il y a au total
$result = $conn->query("SELECT COUNT(*) as count FROM chapters");
$row = $result->fetch_assoc();
$total = $row['count'];

// Vérifier les manhwas
$result2 = $conn->query("SELECT COUNT(*) as count FROM manhwas");
$row2 = $result2->fetch_assoc();
$totalManhwas = $row2['count'];

// Chercher passion
$result3 = $conn->query("SELECT * FROM manhwas WHERE manhwa_title LIKE '%passion%'");
$passion = [];
if ($result3) {
    while ($r = $result3->fetch_assoc()) {
        $passion[] = $r;
    }
}

// Chercher les chapitres qui n'ont pas de manhwa_title
$result4 = $conn->query("SELECT * FROM chapters LIMIT 5");
$chapters = [];
if ($result4) {
    while ($r = $result4->fetch_assoc()) {
        $chapters[] = $r;
    }
}

echo json_encode([
    'total_chapters' => $total,
    'total_manhwas' => $totalManhwas,
    'passion_data' => $passion,
    'sample_chapters' => $chapters
], JSON_PRETTY_PRINT);

$conn->close();
?>
