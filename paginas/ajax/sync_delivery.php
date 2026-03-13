<?php
session_start();
require_once '../../templates/autoload.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

$tier = $_POST['delivery_tier'] ?? 'A';

try {
    $result = $cartManager->syncDeliveryItem($userId, $tier, $config);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
