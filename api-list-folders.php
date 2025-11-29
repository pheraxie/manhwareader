<?php
// Retourne la liste des dossiers dans le répertoire `chapitres/`
header('Content-Type: application/json; charset=utf-8');
$base = __DIR__ . DIRECTORY_SEPARATOR . 'chapitres';
$out = [];
if (!is_dir($base)) {
    echo json_encode(['success' => false, 'message' => 'Dossier chapitres introuvable']);
    exit;
}

$items = scandir($base);
foreach ($items as $it) {
    if ($it === '.' || $it === '..') continue;
    if (is_dir($base . DIRECTORY_SEPARATOR . $it)) $out[] = $it;
}

echo json_encode(['success' => true, 'folders' => array_values($out)]);
?>
