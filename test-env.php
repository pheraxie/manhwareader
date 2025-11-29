<?php
// Test si le site production charge bien depuis Supabase
header('Content-Type: application/json; charset=utf-8');

// Vérifier que le script est appelé avec les bonnes variables d'environnement
echo json_encode([
    'hostname' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    'is_localhost' => (function() {
        $h = $_SERVER['HTTP_HOST'] ?? '';
        return $h === 'localhost' || $h === '127.0.0.1' || $h === '';
    })(),
    'script_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'php_self' => $_SERVER['PHP_SELF'] ?? 'unknown',
    'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'unknown'
], JSON_PRETTY_PRINT);
?>
