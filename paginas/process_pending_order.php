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
            }
        }
    }

    // Iniciar Transacción
    $db->beginTransaction();

    // 1. Buscar si ya existe una orden ABIERTA para esta mesa/cliente (Solo Dine-in)
    $orderId = null;
    if ($type === 'dine_in') {
        $stmtExisting = $db->prepare("SELECT id FROM orders WHERE user_id = ? AND shipping_address = ? AND status IN ('preparing', 'ready') AND consumption_type = 'dine_in' ORDER BY created_at DESC LIMIT 1");
        $stmtExisting->execute([$userId, $address]);
        $orderId = $stmtExisting->fetchColumn();
    }

    if ($orderId) {
        // CONSOLIDAR: Añadir a orden existente
        $orderManager->addItemsToOrder($orderId, $cartItems);
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
        $message = 'Pedido enviado a preparación con éxito.';
    }

    // 2. Descontar Stock (Ya que el pedido se va a preparar)
    $orderManager->deductStockFromSale($orderId);

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