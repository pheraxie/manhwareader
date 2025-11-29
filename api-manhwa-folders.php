<?php
// Simple mapping manhwa_id -> folder saved in JSON file (local only)
header('Content-Type: application/json; charset=utf-8');
$mapFile = __DIR__ . DIRECTORY_SEPARATOR . 'manhwa-folders.json';
$method = $_SERVER['REQUEST_METHOD'];
$data = [];
if (file_exists($mapFile)) {
    $raw = file_get_contents($mapFile);
    $data = json_decode($raw, true) ?: [];
}

if ($method === 'GET') {
    echo json_encode(['success' => true, 'map' => $data]);
    exit;
}

// POST: { manhwa_id:..., folder:... }
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!$body || !isset($body['manhwa_id']) || !isset($body['folder'])) {
        echo json_encode(['success' => false, 'message' => 'manhwa_id et folder requis']);
        exit;
    }
    $mid = $body['manhwa_id'];
    $folder = $body['folder'];
    $data[$mid] = $folder;
    file_put_contents($mapFile, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Méthode non supportée']);
?>
