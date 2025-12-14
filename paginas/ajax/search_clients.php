<?php
// Prevent any output before JSON
ob_start();

require_once '../../templates/autoload.php';

// Validar sesion
if (session_status() === PHP_SESSION_NONE) {
// session_start();
}

// Clear any previous output
ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\CreditService;

$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $container = Container::getInstance();
    $creditService = $container->get(CreditService::class);
    $results = $creditService->searchClients($query);
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
