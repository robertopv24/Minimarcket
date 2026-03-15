<?php
session_start();
require_once '../templates/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$type = $_POST['type'] ?? 'dine_in';
$deliveryTier = $_POST['delivery_tier'] ?? 'A'; // A, B, C

try {
    $cartItems = $cartManager->getCart($userId);
    if (empty($cartItems)) {
        throw new Exception("El carrito está vacío.");
    }

    // Validar sesión de caja
    $sessionId = $cashRegisterManager->hasOpenSession($userId);
    if (!$sessionId) {
        throw new Exception("Debes abrir una caja (Turno) antes de realizar pedidos.");
    }

    // Preparar dirección (Nombre del cliente o dirección de envío)
    $customerName = $_SESSION['pos_client_name'] ?? 'Cliente General';
    // Limpiar prefijos técnicos como DELIVERY (X): para la base de datos
    $address = preg_replace('/DELIVERY \([A-Z]\): /i', '', $customerName);

    // --- CARGO POR DELIVERY ---
    // Ya no inyectamos ítems virtuales. El cargo 'Servicio Delivery' debe estar 
    // en $cartItems si el usuario lo seleccionó en carrito.php.
    // Solo nos aseguramos de que el tipo de consumo y etiquetas sean correctos.

    // FORZAR: Si es delivery, todos los items se marcan como "Para Llevar" técnicamente
    // para que se dispare la lógica de empaques y etiquetas en KDS.
    // FORZAR: Si es delivery, todos los items se marcan con el tipo 'delivery'
    // para que se dispare la lógica de etiquetas verdes en KDS.
    if ($type === 'delivery') {
        foreach ($cartItems as &$item) {
            $item['is_takeaway'] = 1; // Mantenemos para compatibilidad con lógica de empaques si existe
            $item['consumption_type'] = 'delivery';

            // Si tiene modificadores agrupados, asegurar que cada sub-item también sea para delivery
            if (isset($item['modifiers_grouped'])) {
                foreach ($item['modifiers_grouped'] as &$group) {
                    $group['is_takeaway'] = 1;
                    $group['consumption_type'] = 'delivery';
                }
                unset($group);
            }
        }
        unset($item);
    }

    // --- INYECCIÓN DE CLIENTE/EMPLEADO A LA ORDEN ---
    // Asegurar que los ítems del carrito lleven el ID del cliente o empleado seleccionado en la sesión
    // Esto es crucial para que OrderManager::createOrder() asocie la orden al cliente en la base de datos
    $sessionClientId  = $_SESSION['pos_client_id']  ?? null;
    $sessionEmployeeId = $_SESSION['pos_employee_id'] ?? null;
    foreach ($cartItems as &$item) {
        if (empty($item['client_id']))   $item['client_id']   = $sessionClientId;
        if (empty($item['employee_id'])) $item['employee_id'] = $sessionEmployeeId;
    }
    unset($item);

    // Iniciar Transacción
    $db->beginTransaction();

    // 1. Determinar si es una EDICIÓN o una NUEVA ORDEN
    $orderId = null;
    $editingOrderId = $_SESSION['pos_editing_order_id'] ?? null;

    if ($editingOrderId) {
        // --- MODO EDICIÓN ---
        $orderId = $editingOrderId;
        
        // Reemplazar los ítems de la orden existente con los del carrito actual
        // replaceOrderItems ya maneja la eliminación de items viejos y la inserción de nuevos,
        // restaurando y descontando inventario según sea necesario.
        $orderManager->replaceOrderItems($orderId, $cartItems);
        
        // Actualizar el estado a 'preparing' para que vuelva a salir en cocina si es necesario,
        // y asegurar que el tipo de consumo, dirección y delivery_tier se actualizan por si el usuario los cambió en el carrito.
        $stmtUpdate = $db->prepare("UPDATE orders SET status = 'preparing', consumption_type = ?, delivery_tier = ?, shipping_address = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$type, $deliveryTier, $address, $orderId]);
        
        $message = 'Pedido modificado y reenviado a preparación con éxito.';
        
        // Limpiar la sesión de edición
        unset($_SESSION['pos_editing_order_id']);

    } else {
        // --- MODO NUEVA ORDEN O CONSOLIDAR (Dine-in) ---
        if ($type === 'dine_in') {
            $stmtExisting = $db->prepare("SELECT id FROM orders WHERE user_id = ? AND shipping_address = ? AND status IN ('preparing', 'ready') AND consumption_type = 'dine_in' ORDER BY created_at DESC LIMIT 1");
            $stmtExisting->execute([$userId, $address]);
            $orderId = $stmtExisting->fetchColumn();
        }

        if ($orderId) {
            // CONSOLIDAR: Añadir a orden existente
            $orderManager->addItemsToOrder($orderId, $cartItems);
            
            // Re-evaluar stock de la orden completa después de añadir
            $orderManager->deductStockFromSale($orderId);
            
            $message = 'Pedido añadido a la cuenta existente.';
        } else {
            // NUEVA: Crear la Orden con estado 'preparing'
            $orderId = $orderManager->createOrder($userId, $cartItems, $address, null, $deliveryTier);
            if (!$orderId) {
                throw new Exception("Falló la creación de la orden.");
            }

            // Actualizar estado a 'preparing' (para que salga en KDS) y setear el tipo de consumo
            $stmt = $db->prepare("UPDATE orders SET status = 'preparing', consumption_type = ? WHERE id = ?");
            $stmt->execute([$type, $orderId]);
            
            // Descontar Stock (Ya que el pedido se va a preparar)
            $orderManager->deductStockFromSale($orderId);
            
            $message = 'Pedido enviado a preparación con éxito.';
        }
    }

    // 3. Vaciar Carrito
    $cartManager->emptyCart($userId);

    // Limpiar cliente de la sesión
    unset($_SESSION['pos_client_id']);
    unset($_SESSION['pos_client_name']);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'order_id' => $orderId
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>