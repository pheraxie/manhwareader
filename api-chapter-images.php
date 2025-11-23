<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

// Retourne la liste des images pour un chapitre stocké sous forme de dossier
// Paramètres acceptés : path=chapitres/nomManhwa/numeroChapitre  OU manhwa_id & chapter_number

function safe_join_chapitres($relative) {
    $base = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'chapitres');
    if (!$base) return false;
    // Nettoyer le chemin
    $relative = str_replace(['..\\','../','\\'], ['','','/'], $relative);
    $relative = ltrim($relative, '/');
    $full = realpath($base . DIRECTORY_SEPARATOR . $relative);
    if (!$full) return false;
    // S'assurer que le chemin est bien dans le dossier chapitres
    if (strpos($full, $base) !== 0) return false;
    return $full;
}

$allowed = ['jpg','jpeg','png','gif','webp','bmp','svg'];

$path = $_GET['path'] ?? null;
$manhwa_id = $_GET['manhwa_id'] ?? null;
$chapter_number = isset($_GET['chapter_number']) ? (int)$_GET['chapter_number'] : null;

try {
    if (!$path && $manhwa_id && $chapter_number !== null) {
        // Chercher dans la base le champ chapter_pages
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT chapter_pages FROM chapters WHERE manhwa_id = ? AND chapter_number = ? LIMIT 1");
        $stmt->bind_param('si', $manhwa_id, $chapter_number);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $path = $row['chapter_pages'];
        }
        $stmt->close();
        if (isset($conn)) $conn->close();
    }

    if (!$path) {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }

    // Normaliser
    $path = str_replace('\\', '/', $path);
    if (strpos($path, 'chapitres/') !== false) {
        $path = substr($path, strpos($path, 'chapitres/') + strlen('chapitres/'));
    }

    // Remove leading ./ or /\
    $path = preg_replace('#^\.?/+#', '', $path);

    $full = safe_join_chapitres($path);
    if (!$full || !is_dir($full)) {
        echo json_encode(['success' => false, 'message' => 'Dossier introuvable', 'path' => $path]);
        exit;
    }

    $files = scandir($full);
    $images = [];
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $images[] = $f;
    }

    // Trier numériquement si possible: extraire premier nombre dans le nom sinon lexicographique
    usort($images, function($a, $b) {
        preg_match('/(\d+)/', $a, $ma);
        preg_match('/(\d+)/', $b, $mb);
        if (isset($ma[1]) && isset($mb[1])) {
            $na = (int)$ma[1];
            $nb = (int)$mb[1];
            if ($na != $nb) return $na - $nb;
        }
        // Fallback lexical
        return strcasecmp($a, $b);
    });

    // Construire les URLs relatives pour le client
    $relDir = 'chapitres/' . trim($path, '/');
    $out = [];
    foreach ($images as $img) {
        $out[] = [
            'name' => $img,
            'url' => './' . $relDir . '/' . $img
        ];
    }

    echo json_encode(['success' => true, 'images' => $out]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur', 'error' => $e->getMessage()]);
    exit;
}

?>
