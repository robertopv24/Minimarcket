<?php
session_start();
require_once '../templates/autoload.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$tier = $_POST['delivery_tier'] ?? 'A';

try {
    // Sincronizar ítem de delivery en la tabla cart
    $cartManager->syncDeliveryItem($userId, $tier, $config);
    
    // Recalcular total para devolver al frontend
    $cartItems = $cartManager->getCart($userId);
    $totals = $cartManager->calculateTotal($cartItems);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Delivery sincronizado',
        'totals' => $totals
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
