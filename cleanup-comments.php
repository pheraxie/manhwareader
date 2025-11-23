<?php
error_reporting(0);
header('Content-Type: application/json');
require_once 'config.php';
$conn = getDBConnection();

// This script deletes comments that reference non-existing manhwas or chapters.
// It returns the number of removed comments and list of removed IDs.

$deleted = [];

// 1) Delete comments where manhwa_id does not exist in manhwas table
$sql1 = "SELECT c.id FROM comments c LEFT JOIN manhwas m ON c.manhwa_id = m.manhwa_id WHERE m.manhwa_id IS NULL";
$res1 = $conn->query($sql1);
if ($res1) {
    while ($row = $res1->fetch_assoc()) {
        $id = $conn->real_escape_string($row['id']);
        $d = $conn->query("DELETE FROM comments WHERE id='".$id."'");
        if ($d) $deleted[] = $id;
    }
}

// 2) Delete comments where chapter_number is set but the chapter does not exist
$sql2 = "SELECT c.id, c.manhwa_id, c.chapter_number FROM comments c WHERE c.chapter_number IS NOT NULL";
$res2 = $conn->query($sql2);
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $manhwa_id = $conn->real_escape_string($row['manhwa_id']);
        $chapter_number = (int)$row['chapter_number'];
        $check = $conn->query("SELECT 1 FROM chapters WHERE manhwa_id='".$manhwa_id."' AND chapter_number=".$chapter_number." LIMIT 1");
        if (!$check || $check->num_rows === 0) {
            $id = $conn->real_escape_string($row['id']);
            $d = $conn->query("DELETE FROM comments WHERE id='".$id."'");
            if ($d) $deleted[] = $id;
        }
    }
}

echo json_encode(['success' => true, 'deleted_count' => count($deleted), 'deleted_ids' => $deleted]);
exit;
?>