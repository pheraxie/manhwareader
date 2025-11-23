<?php
// Configuration de la base de données
// MODIFIEZ CES VALEURS SELON VOTRE CONFIGURATION XAMPP

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Par défaut, XAMPP n'a pas de mot de passe
define('DB_NAME', 'manhwareader');

// Connexion à la base de données
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Erreur de connexion: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Headers CORS pour développement local
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

