<?php
session_start();
require_once '../templates/autoload.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';
$orderId = intval($_POST['order_id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de orden inválido']);
    exit;
}

try {
    if ($action === 'cancel') {
        if (!$orderManager->isOrderCancellable($orderId)) {
            echo json_encode(['success' => false, 'message' => 'Esta orden ya no puede ser cancelada.']);
            exit;
        }

        if ($orderManager->cancelOrder($orderId)) {
            echo json_encode(['success' => true, 'message' => 'Orden cancelada con éxito y stock restaurado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cancelar la orden.']);
        }
    } elseif ($action === 'modify') {
        if (!$orderManager->isOrderModifiable($orderId)) {
            echo json_encode(['success' => false, 'message' => 'El tiempo límite para modificar esta orden ha expirado.']);
            exit;
        }

        // La modificación implica cancelar la actual y cargar en carrito
        $db->beginTransaction();
        
        // Vaciar carrito actual primero para evitar mezclas
        $cartManager->emptyCart($userId);
        
        // Cargar orden en carrito
        if ($cartManager->loadOrderIntoCart($userId, $orderId)) {
            // Cancelar la orden original
            if ($orderManager->cancelOrder($orderId)) {
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Orden cargada en el carrito para modificación.']);
            } else {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error al procesar la cancelación para modificación.']);
            }
        } else {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al cargar los datos en el carrito.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de sistema: ' . $e->getMessage()]);
}
