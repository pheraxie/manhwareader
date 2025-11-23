<?php
require_once 'config.php';
$conn = getDBConnection();

// Récupérer l'action et l'ID
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Aucune action spécifiée']);
    exit;
}

switch ($action) {

    // Ajouter ou modifier un suivi
    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            echo json_encode(['success'=>false,'message'=>'Données invalides']);
            exit;
        }

        // Vérifier si l'ID existe
        $res = $conn->query("SELECT * FROM tracking WHERE id='".$conn->real_escape_string($data['id'])."'");
        if ($res->num_rows > 0) {
            // Modifier
            $tracking = $data;
            $stmt = $conn->prepare("UPDATE tracking SET title=?, chapter=?, status=?, notes=?, season=?, date_updated=NOW() WHERE id=?");
            $stmt->bind_param("sissss",
                $tracking['title'],
                $tracking['chapter'],
                $tracking['status'],
                $tracking['notes'],
                $tracking['season'],
                $tracking['id']
            );
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success'=>true,'message'=>'Suivi mis à jour']);
        } else {
            // Ajouter
            $tracking = $data;
            $stmt = $conn->prepare("INSERT INTO tracking (id,title,chapter,status,notes,season,date_added,date_updated) VALUES (?,?,?,?,?,?,NOW(),NOW())");
            $stmt->bind_param("ssisss",
                $tracking['id'],
                $tracking['title'],
                $tracking['chapter'],
                $tracking['status'],
                $tracking['notes'],
                $tracking['season']
            );
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success'=>true,'message'=>'Suivi ajouté']);
        }
    break;

    // Supprimer un suivi (mettre dans la corbeille)
    case 'delete':
        $res = $conn->query("SELECT * FROM tracking WHERE id='".$conn->real_escape_string($id)."'");
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $trashId = 'trash_'.time().'_'.mt_rand();
            $conn->query("INSERT INTO trash (id, trash_type, original_data, deleted_at) VALUES ('$trashId','tracking','".$conn->real_escape_string(json_encode($row))."',NOW())");
            $conn->query("DELETE FROM tracking WHERE id='".$conn->real_escape_string($id)."'");
            echo json_encode(['success'=>true,'message'=>'Suivi supprimé et mis dans la corbeille']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Suivi introuvable']);
        }
    break;

    // Restaurer depuis la corbeille
    case 'restore':
        $res = $conn->query("SELECT * FROM trash WHERE id='".$conn->real_escape_string($id)."' AND trash_type='tracking'");
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $tracking = json_decode($row['original_data'], true);
            $stmt = $conn->prepare("INSERT INTO tracking (id,title,chapter,status,notes,season,date_added,date_updated) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssisssss",
                $tracking['id'],
                $tracking['title'],
                $tracking['chapter'],
                $tracking['status'],
                $tracking['notes'],
                $tracking['season'],
                $tracking['date_added'],
                $tracking['date_updated']
            );
            $stmt->execute();
            $stmt->close();
            $conn->query("DELETE FROM trash WHERE id='".$conn->real_escape_string($id)."'");
            echo json_encode(['success'=>true,'message'=>'Suivi restauré depuis la corbeille']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Élément introuvable dans la corbeille']);
        }
    break;

    // Supprimer définitivement
    case 'delete_permanent':
        $conn->query("DELETE FROM trash WHERE id='".$conn->real_escape_string($id)."'");
        echo json_encode(['success'=>true,'message'=>'Élément supprimé définitivement']);
    break;

    // Vider la corbeille
    case 'empty_trash':
        $conn->query("DELETE FROM trash WHERE trash_type='tracking'");
        echo json_encode(['success'=>true,'message'=>'Corbeille vidée']);
    break;

    default:
        echo json_encode(['success'=>false,'message'=>'Action inconnue']);
}

$conn->close();
?>
