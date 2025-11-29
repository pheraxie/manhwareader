<?php
// Retourne la liste des chapitres présents dans un dossier manhwa
header('Content-Type: application/json; charset=utf-8');
$base = __DIR__ . DIRECTORY_SEPARATOR . 'chapitres';
$folder = isset($_GET['folder']) ? $_GET['folder'] : null;
if (!$folder) {
    echo json_encode(['success' => false, 'message' => 'Paramètre folder requis']);
    exit;
}

$path = realpath($base . DIRECTORY_SEPARATOR . $folder);
if ($path === false || strpos($path, realpath($base)) !== 0 || !is_dir($path)) {
    echo json_encode(['success' => false, 'message' => 'Dossier introuvable']);
    exit;
}

$items = scandir($path);
$chapters = [];
foreach ($items as $it) {
    if ($it === '.' || $it === '..') continue;
    // garder uniquement les dossiers/chiffres
    if (is_dir($path . DIRECTORY_SEPARATOR . $it)) {
        // extraire nombre s'il y a des lettres
        if (preg_match('/(\d+)/', $it, $m)) {
            $chapters[] = (int)$m[1];
        }
    }
}

sort($chapters, SORT_NUMERIC);
echo json_encode(['success' => true, 'chapters' => array_values(array_unique($chapters))]);
?>
