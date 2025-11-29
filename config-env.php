<?php
// config-env.php — Détecte l'environnement (localhost vs en ligne) et configure les connexions

// Détecter si c'est localhost/développement
function isLocalhost() {
    $hostname = $_SERVER['HTTP_HOST'];
    return $hostname === 'localhost' || 
           $hostname === '127.0.0.1' || 
           strpos($hostname, 'localhost:') === 0 ||
           strpos($hostname, '127.0.0.1:') === 0 ||
           strpos($hostname, '192.168.') === 0 ||
           strpos($hostname, '10.') === 0 ||
           strpos($hostname, '172.') === 0;
}

// Configuration Supabase
define('SUPABASE_URL', 'https://carykkmxuvhmxrjawphq.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_tI_9bp91r0pXKHmEARX6FA_XS4_IBoE');
define('SUPABASE_PROJECT', 'carykkmxuvhmxrjawphq');

// Déterminer la source de données
define('USE_LOCALHOST', isLocalhost());

// Message de debug (à enlever en production)
error_log('[config-env] Environment: ' . (USE_LOCALHOST ? 'LOCALHOST (MySQL)' : 'PRODUCTION (Supabase)'));

// Importer la config appropriée
if (USE_LOCALHOST) {
    // Mode local : utiliser MySQL
    require_once 'config.php';
} else {
    // Mode en ligne : initialiser Supabase (via PHP client si nécessaire)
    // Pour l'instant, les appels Supabase seront faits via le client JS
}

// Fonction helper pour obtenir la connexion appropriée
function getConnection() {
    if (USE_LOCALHOST) {
        return getDBConnection();
    } else {
        // En production, retourner null et utiliser directement l'API Supabase côté JS
        return null;
    }
}

// Fonction helper pour retourner une réponse JSON standard
function jsonResponse($success, $data = null, $message = null) {
    $response = ['success' => $success];
    if ($data !== null) $response['data'] = $data;
    if ($message !== null) $response['message'] = $message;
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

?>
