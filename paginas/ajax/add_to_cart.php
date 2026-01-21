<?php
require_once '../../templates/autoload.php';
session_start();
header('Content-Type: application/json');

// 1. Validar Sesión
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar acciones.']);
    exit;
}

// 2. Validar Caja Abierta
try {
    $hasOpenSession = $cashRegisterManager->hasOpenSession($userId);
    if (!$hasOpenSession) {
        // Permitir agregar al admin sin caja para pruebas? No, regla estricta según tienda.php
        echo json_encode(['success' => false, 'message' => '⚠️ ERROR: Debes abrir una caja (Turno) antes de vender.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error verificando caja: ' . $e->getMessage()]);
    exit;
}

// 3. Procesar Petición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;

    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'ID de producto no válido.']);
        exit;
    }

    // Por ahora, la adición rápida no lleva modificadores complejos (se editan en carrito)
    $modifiers = [];

    try {
        $result = $cartManager->addToCart($userId, $productId, $quantity, $modifiers);

        if ($result === true) {
            echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito.']);
        } else {
            // Error de negocio (ej: Stock insuficiente)
            echo json_encode(['success' => false, 'message' => $result]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
