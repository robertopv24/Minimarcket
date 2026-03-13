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
        // La modificación carga los ítems en el carrito para editar.
        // La orden ORIGINAL se cancela solo al confirmar el checkout (en process_checkout.php)
        // para no perder el historial de delivery y transacciones previas.
        
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) $db->beginTransaction();
        
        // Vaciar carrito actual para evitar mezclas
        $cartManager->emptyCart($userId);
        
        // Cargar ítems de la orden al carrito (sin cancelar la orden aún)
        if ($cartManager->loadOrderIntoCart($userId, $orderId)) {
            // Guardar referencia a la orden original en sesión
            $_SESSION['pos_editing_order_id'] = $orderId;
            
            if (!$inTransaction) $db->commit();
            echo json_encode(['success' => true, 'message' => 'Orden cargada en el carrito para modificación.']);
        } else {
            if (!$inTransaction) $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al cargar los datos en el carrito.']);
        }
    } elseif ($action === 'deliver') {
        // Finalizar Entrega (Cambio de estado)
        $stmt = $db->prepare("UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$orderId])) {
            $orderManager->logStatusMilestone($orderId, 'system', 'delivered');
            echo json_encode(['success' => true, 'message' => 'Orden marcada como entregada.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la orden.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de sistema: ' . $e->getMessage()]);
}
