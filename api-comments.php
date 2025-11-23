<?php
error_reporting(0);
header('Content-Type: application/json');
require_once 'config.php';
$conn = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $manhwa_id = isset($_GET['manhwa_id']) ? $conn->real_escape_string($_GET['manhwa_id']) : null;
    $chapter = isset($_GET['chapter_number']) ? (int)$_GET['chapter_number'] : null;

    $sql = "SELECT * FROM comments";
    $conds = [];
    if ($manhwa_id) $conds[] = "manhwa_id='".$manhwa_id."'";
    if ($chapter !== null) $conds[] = "chapter_number=".$chapter;
    if (count($conds) > 0) $sql .= ' WHERE '.implode(' AND ', $conds);
    $sql .= ' ORDER BY date DESC';

    $res = $conn->query($sql);
    $out = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
    }
    echo json_encode(['success'=>true,'data'=>$out]);
    exit;
}

// POST = create
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['manhwa_id']) || !isset($data['text'])) {
        echo json_encode(['success'=>false,'message'=>'Données invalides']);
        exit;
    }
    $id = isset($data['id']) ? $conn->real_escape_string($data['id']) : 'comment_'.time().'_'.mt_rand();
    $manhwa_id = $conn->real_escape_string($data['manhwa_id']);
    $chapter_number = isset($data['chapter_number']) ? (int)$data['chapter_number'] : null;
    $author = isset($data['author']) ? $conn->real_escape_string($data['author']) : 'Anonyme';
    $text = $conn->real_escape_string($data['text']);
    $images = isset($data['images']) ? $conn->real_escape_string(json_encode($data['images'])) : null;
    $date = isset($data['date']) ? $conn->real_escape_string($data['date']) : date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO comments (id, manhwa_id, chapter_number, author, text, images, date) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('ssissss', $id, $manhwa_id, $chapter_number, $author, $text, $images, $date);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) echo json_encode(['success'=>true,'id'=>$id]);
    else echo json_encode(['success'=>false,'message'=>'Erreur insertion']);
    exit;
}

// DELETE
if ($method === 'DELETE') {
    // Support id via query string or request body; also support bulk delete by manhwa_id and optional chapter_number
    $id = null;
    if (isset($_GET['id'])) {
        $id = $conn->real_escape_string($_GET['id']);
    } else {
        // Try parsing body (some clients send query-like body)
        parse_str(file_get_contents('php://input'), $input);
        if (isset($input['id'])) $id = $conn->real_escape_string($input['id']);
    }

    // Bulk delete by manhwa_id (+ chapter_number)
    if (!$id && isset($_GET['manhwa_id'])) {
        $manhwa_id = $conn->real_escape_string($_GET['manhwa_id']);
        if (isset($_GET['chapter_number']) && $_GET['chapter_number'] !== '') {
            $chapter = (int)$_GET['chapter_number'];
            $res = $conn->query("DELETE FROM comments WHERE manhwa_id='".$manhwa_id."' AND chapter_number=".$chapter);
        } else {
            $res = $conn->query("DELETE FROM comments WHERE manhwa_id='".$manhwa_id."'");
        }
        if ($res) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false,'message'=>'Erreur suppression commentaires']);
        exit;
    }

    if ($id) {
        $res = $conn->query("DELETE FROM comments WHERE id='".$id."'");
        if ($res) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false,'message'=>'Erreur suppression']);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'id ou manhwa_id requis']);
    exit;
}

// PUT (update)
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['id'])) {
        echo json_encode(['success'=>false,'message'=>'Données invalides']);
        exit;
    }
    $id = $conn->real_escape_string($data['id']);
    $text = isset($data['text']) ? $conn->real_escape_string($data['text']) : null;
    $images = isset($data['images']) ? $conn->real_escape_string(json_encode($data['images'])) : null;

    $fields = [];
    if ($text !== null) $fields[] = "text='".$text."'";
    if ($images !== null) $fields[] = "images='".$images."'";
    if (count($fields) === 0) {
        echo json_encode(['success'=>false,'message'=>'Rien à mettre à jour']);
        exit;
    }
    $sql = "UPDATE comments SET ".implode(',', $fields)." WHERE id='".$id."'";
    $res = $conn->query($sql);
    if ($res) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'message'=>'Erreur update']);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Méthode non supportée']);

?>
