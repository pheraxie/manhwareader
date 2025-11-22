<?php
header('Content-Type: application/json');

function scanDirectory($dir, $basePath = '') {
    $images = [];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!is_dir($dir)) {
        return $images;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $fullPath = $dir . '/' . $file;
        $relativePath = ($basePath ? $basePath . '/' : '') . $file;
        
        if (is_dir($fullPath)) {
            // Récursif pour les sous-dossiers
            $images = array_merge($images, scanDirectory($fullPath, $relativePath));
        } else {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExtensions)) {
                $images[] = [
                    'name' => $file,
                    'path' => './gallery/' . $relativePath,
                    'size' => filesize($fullPath),
                    'modified' => filemtime($fullPath)
                ];
            }
        }
    }
    
    return $images;
}

$galleryDir = __DIR__ . '/gallery';
$images = scanDirectory($galleryDir);

// Trier par date de modification (plus récent en premier)
usort($images, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

echo json_encode(['images' => $images], JSON_PRETTY_PRINT);
?>

