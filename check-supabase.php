<?php
require_once 'config-env.php';
header('Content-Type: application/json; charset=utf-8');

$supabaseUrl = SUPABASE_URL;
$supabaseKey = SUPABASE_KEY;

function supa_request($method, $path, $body = null) {
    global $supabaseUrl, $supabaseKey;
    $url = rtrim($supabaseUrl, '/') . '/rest/v1/' . ltrim($path, '/');
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $supabaseKey,
        'apikey: ' . $supabaseKey
    ];

    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true
        ]
    ];
    if ($body !== null) $opts['http']['content'] = json_encode($body);

    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    $code = null;
    if (isset($http_response_header) && preg_match('#HTTP/\d\.\d\s+(\d+)#', $http_response_header[0], $m)) {
        $code = intval($m[1]);
    }
    return ['status' => $code, 'body' => $res];
}

// Chercher les chapitres pour Passiona dans Supabase
$result = supa_request('GET', 'chapters?manhwa_id=eq.manhwa_1763894842685&select=*&limit=5');
$data = json_decode($result['body'], true);

echo json_encode([
    'status' => $result['status'],
    'chapters_in_supabase' => $data ?: [],
    'message' => 'Chapitres de Passiona dans Supabase'
], JSON_PRETTY_PRINT);
?>
