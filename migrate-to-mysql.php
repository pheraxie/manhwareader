<?php
// Script de migration : transfère les données de data.json vers MySQL
// Exécutez ce script UNE SEULE FOIS après avoir créé la base de données

require_once 'config.php';

echo "🔄 Migration des données de data.json vers MySQL...\n\n";

// Charger data.json
if (!file_exists('data.json')) {
    die("❌ Fichier data.json non trouvé!\n");
}

$jsonData = json_decode(file_get_contents('data.json'), true);

if (!is_array($jsonData)) {
    die("❌ data.json n'est pas un tableau valide!\n");
}

$conn = getDBConnection();

$manhwasCount = 0;
$chaptersCount = 0;
$trackingCount = 0;
$trashCount = 0;

foreach ($jsonData as $item) {
    // Détecter le type d'élément
    if (isset($item['type'])) {
        if ($item['type'] === 'tracking') {
            // Insérer dans tracking
            $id = $item['id'] ?? 'tracking_' . time() . '_' . mt_rand();
            $title = $conn->real_escape_string($item['title'] ?? '');
            $chapter = (int)($item['chapter'] ?? 0);
            $status = $conn->real_escape_string($item['status'] ?? 'en-cours');
            $notes = $conn->real_escape_string($item['notes'] ?? '');
            $season = $conn->real_escape_string($item['season'] ?? '');
            $date_added = $item['date_added'] ?? date('Y-m-d H:i:s');
            $date_updated = $item['date_updated'] ?? $date_added;
            
            $sql = "INSERT IGNORE INTO tracking (id, title, chapter, status, notes, season, date_added, date_updated) 
                    VALUES ('$id', '$title', $chapter, '$status', '$notes', '$season', '$date_added', '$date_updated')";
            
            if ($conn->query($sql)) {
                $trackingCount++;
            }
        } elseif ($item['type'] === 'trash') {
            // Insérer dans trash
            $id = $item['id'] ?? 'trash_' . time() . '_' . mt_rand();
            $trash_type = $conn->real_escape_string($item['trash_type'] ?? '');
            $original_data = $conn->real_escape_string(json_encode($item['original_data'] ?? []));
            $deleted_at = $item['deleted_at'] ?? date('Y-m-d H:i:s');
            
            $sql = "INSERT IGNORE INTO trash (id, trash_type, original_data, deleted_at) 
                    VALUES ('$id', '$trash_type', '$original_data', '$deleted_at')";
            
            if ($conn->query($sql)) {
                $trashCount++;
            }
        }
    } elseif (isset($item['manhwa_title']) && !isset($item['chapter_number'])) {
        // C'est un manhwa
        $id = $item['__backendId'] ?? 'local_' . time() . '_' . mt_rand();
        $manhwa_id = $item['manhwa_id'] ?? 'manhwa_' . time();
        $title = $conn->real_escape_string($item['manhwa_title'] ?? '');
        $cover = $conn->real_escape_string($item['manhwa_cover'] ?? '');
        $description = $conn->real_escape_string($item['manhwa_description'] ?? '');
        $season = $conn->real_escape_string($item['manhwa_season'] ?? '');
        $date_added = $item['date_added'] ?? date('Y-m-d H:i:s');
        
        $sql = "INSERT IGNORE INTO manhwas (id, manhwa_id, manhwa_title, manhwa_cover, manhwa_description, manhwa_season, date_added) 
                VALUES ('$id', '$manhwa_id', '$title', '$cover', '$description', '$season', '$date_added')";
        
        if ($conn->query($sql)) {
            $manhwasCount++;
        }
    } elseif (isset($item['chapter_number'])) {
        // C'est un chapitre
        $id = $item['__backendId'] ?? 'local_' . time() . '_' . mt_rand();
        $manhwa_id = $conn->real_escape_string($item['manhwa_id'] ?? '');
        $chapter_number = (int)($item['chapter_number'] ?? 0);
        $title = $conn->real_escape_string($item['chapter_title'] ?? '');
        $description = $conn->real_escape_string($item['chapter_description'] ?? '');
        $season = $conn->real_escape_string($item['chapter_season'] ?? '');
        $pages = $conn->real_escape_string($item['chapter_pages'] ?? '');
        $cover = $conn->real_escape_string($item['chapter_cover'] ?? '');
        $is_favorite = isset($item['is_favorite']) && $item['is_favorite'] ? 1 : 0;
        $date_added = $item['date_added'] ?? date('Y-m-d H:i:s');
        
        $sql = "INSERT IGNORE INTO chapters (id, manhwa_id, chapter_number, chapter_title, chapter_description, chapter_season, chapter_pages, chapter_cover, is_favorite, date_added) 
                VALUES ('$id', '$manhwa_id', $chapter_number, '$title', '$description', '$season', '$pages', '$cover', $is_favorite, '$date_added')";
        
        if ($conn->query($sql)) {
            $chaptersCount++;
        }
    }
}

echo "✅ Migration terminée!\n";
echo "📚 Manhwas: $manhwasCount\n";
echo "📖 Chapitres: $chaptersCount\n";
echo "📋 Suivis: $trackingCount\n";
echo "🗑️ Corbeille: $trashCount\n";
echo "\n💾 Toutes les données sont maintenant dans MySQL!\n";

$conn->close();

