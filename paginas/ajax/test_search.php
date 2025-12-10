<?php
// Test simple para verificar que el endpoint funciona
require_once '../../templates/autoload.php';

// Validar sesion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Debug info
$debug = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'user_id_set' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'query' => $_GET['q'] ?? 'no query',
];

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'error' => 'Unauthorized',
        'debug' => $debug
    ]);
    exit;
}

$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([
        'message' => 'Query too short',
        'debug' => $debug
    ]);
    exit;
}

try {
    $results = $creditManager->searchClients($query);
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results),
        'debug' => $debug
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => $debug
    ]);
}
