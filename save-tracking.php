<?php
// Save-tracking endpoint hardened: always responds JSON and reports errors
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

// Enable exceptions for mysqli so we can catch them
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = getDBConnection();

    // Récupérer l'action et l'ID
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? '';

    if (!$action) {
        echo json_encode(['success' => false, 'message' => 'Aucune action spécifiée']);
        exit;
    }

    switch ($action) {
        case 'save':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['id'])) {
                echo json_encode(['success'=>false,'message'=>'Données invalides']);
                exit;
            }

            // Supporter user_id (GET ou dans le payload)
            $user_id = $_GET['user_id'] ?? ($data['user_id'] ?? null);

            // Normaliser champs
            $tracking = $data;
            $order_index = isset($tracking['order_index']) ? (int)$tracking['order_index'] : 0;

            // Vérifier si l'ID existe
            $res = $conn->query("SELECT * FROM tracking WHERE id='".$conn->real_escape_string($tracking['id'])."'");
            if ($res && $res->num_rows > 0) {
                // Modifier
                if ($user_id) {
                    $stmt = $conn->prepare("UPDATE tracking SET title=?, chapter=?, status=?, notes=?, season=?, order_index=?, user_id=?, date_updated=NOW() WHERE id=?");
                    $stmt->bind_param("sisssiss",
                        $tracking['title'],
                        $tracking['chapter'],
                        $tracking['status'],
                        $tracking['notes'],
                        $tracking['season'],
                        $order_index,
                        $user_id,
                        $tracking['id']
                    );
                } else {
                    $stmt = $conn->prepare("UPDATE tracking SET title=?, chapter=?, status=?, notes=?, season=?, order_index=?, date_updated=NOW() WHERE id=?");
                    $stmt->bind_param("sisssis",
                        $tracking['title'],
                        $tracking['chapter'],
                        $tracking['status'],
                        $tracking['notes'],
                        $tracking['season'],
                        $order_index,
                        $tracking['id']
                    );
                }

                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) echo json_encode(['success'=>true,'message'=>'Suivi mis à jour']);
                else echo json_encode(['success'=>false,'message'=>'Erreur mise à jour']);
                exit;
            } else {
                // Ajouter
                if ($user_id) {
                    $stmt = $conn->prepare("INSERT INTO tracking (id,title,chapter,status,notes,season,order_index,user_id,date_added,date_updated) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())");
                    $stmt->bind_param("ssisssis",
                        $tracking['id'],
                        $tracking['title'],
                        $tracking['chapter'],
                        $tracking['status'],
                        $tracking['notes'],
                        $tracking['season'],
                        $order_index,
                        $user_id
                    );
                } else {
                    $stmt = $conn->prepare("INSERT INTO tracking (id,title,chapter,status,notes,season,order_index,date_added,date_updated) VALUES (?,?,?,?,?,?,?,NOW(),NOW())");
                    $stmt->bind_param("ssisssi",
                        $tracking['id'],
                        $tracking['title'],
                        $tracking['chapter'],
                        $tracking['status'],
                        $tracking['notes'],
                        $tracking['season'],
                        $order_index
                    );
                }

                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) echo json_encode(['success'=>true,'message'=>'Suivi ajouté']);
                else echo json_encode(['success'=>false,'message'=>'Erreur insertion']);
                exit;
            }
        break;

        case 'delete':
            $res = $conn->query("SELECT * FROM tracking WHERE id='".$conn->real_escape_string($id)."'");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $trashId = 'trash_'.time().'_'.mt_rand();
                $conn->query("INSERT INTO trash (id, trash_type, original_data, deleted_at) VALUES ('".$conn->real_escape_string($trashId)."','tracking','".$conn->real_escape_string(json_encode($row))."',NOW())");
                $conn->query("DELETE FROM tracking WHERE id='".$conn->real_escape_string($id)."'");
                echo json_encode(['success'=>true,'message'=>'Suivi supprimé et mis dans la corbeille']);
            } else {
                echo json_encode(['success'=>false,'message'=>'Suivi introuvable']);
            }
            exit;
        break;

        case 'restore':
            $res = $conn->query("SELECT * FROM trash WHERE id='".$conn->real_escape_string($id)."' AND trash_type='tracking'");
            if ($res && $res->num_rows > 0) {
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
            exit;
        break;

        case 'delete_permanent':
            $conn->query("DELETE FROM trash WHERE id='".$conn->real_escape_string($id)."'");
            echo json_encode(['success'=>true,'message'=>'Élément supprimé définitivement']);
            exit;
        break;

        case 'empty_trash':
            $conn->query("DELETE FROM trash WHERE trash_type='tracking'");
            echo json_encode(['success'=>true,'message'=>'Corbeille vidée']);
            exit;
        break;

        default:
            echo json_encode(['success'=>false,'message'=>'Action inconnue']);
            exit;
    }

} catch (Exception $e) {
    // Toujours renvoyer JSON en cas d'exception
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur', 'error' => $e->getMessage()]);
    exit;
}

?>
