<?php
// Prevent any output before JSON
ob_start();

require_once '../../templates/autoload.php';

// Validar sesion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any previous output
ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = $_GET['q'] ?? '';

try {
    $results = $userManager->searchUsers($query);
    // Filtramos datos sensibles
    $safeResults = array_map(function ($u) {
        return [
            'id' => $u['id'],
            'name' => $u['name'],
            'document_id' => $u['document_id'] ?? '',
            'job_role' => $u['job_role'] ?? ''
        ];
    }, $results);

    echo json_encode($safeResults);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
