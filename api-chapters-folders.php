<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

// Liste les dossiers sous chapitres/<manhwa> et leurs sous-dossiers (chapters)
try {
    $base = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'chapitres');
    if (!$base || !is_dir($base)) {
        echo json_encode(['success'=>false,'message'=>'Dossier chapitres introuvable']);
        exit;
    }

    $manhwas = [];
    $dirs = scandir($base);
    foreach ($dirs as $d) {
        if ($d === '.' || $d === '..') continue;
        $full = $base . DIRECTORY_SEPARATOR . $d;
        if (!is_dir($full)) continue;

        // Lister les sous-dossiers (chapitres)
        $sub = scandir($full);
        $chapters = [];
        foreach ($sub as $s) {
            if ($s === '.' || $s === '..') continue;
            $fulls = $full . DIRECTORY_SEPARATOR . $s;
            if (is_dir($fulls)) {
                $chapters[] = $s;
            }
        }

        // Trier numériquement si possible
        usort($chapters, function($a, $b){
            preg_match('/(\d+)/', $a, $ma);
            preg_match('/(\d+)/', $b, $mb);
            if (isset($ma[1]) && isset($mb[1])) return (int)$ma[1] - (int)$mb[1];
            return strcasecmp($a, $b);
        });

        $manhwas[] = [
            'name' => $d,
            'chapters' => $chapters
        ];
    }

    echo json_encode(['success'=>true,'data'=>$manhwas]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur','error'=>$e->getMessage()]);
    exit;
}

?>
