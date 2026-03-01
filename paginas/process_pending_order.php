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
    if ($type === 'delivery' && ($deliveryTier === 'B' || $deliveryTier === 'C')) {
        $base = floatval($config->get('delivery_base_cost', 0));
        $fee = ($deliveryTier === 'C') ? ($base * 2) : $base;

        if ($fee > 0) {
            // Buscamos o asumimos un ID de producto para "Delivery" (podemos usar uno genérico o crearlo al vuelo si no existe)
            // Por simplicidad en este sistema POS, a veces se usan IDs fijos para servicios. 
            // Buscaremos uno llamado 'Delivery' o usaremos un placeholder.
            // 1. Asegurar que existe una categoría 'DOMICILIO' que no va a ninguna estación KDS
            $stmtCat = $db->prepare("SELECT id FROM categories WHERE name = 'DOMICILIO' LIMIT 1");
            $stmtCat->execute();
            $catId = $stmtCat->fetchColumn();

            if (!$catId) {
                $db->prepare("INSERT INTO categories (name, kitchen_station, icon, description) VALUES ('DOMICILIO', 'none', 'fa-truck', 'Gastos de envío')")->execute();
                $catId = $db->lastInsertId();
            } else {
                // Asegurar que la categoría existente no tenga estación
                $db->prepare("UPDATE categories SET kitchen_station = 'none' WHERE id = ?")->execute([$catId]);
            }

            // 2. Buscar el producto de servicio
            $stmtD = $db->prepare("SELECT id FROM products WHERE name = 'Servicio Delivery' LIMIT 1");
            $stmtD->execute();
            $dId = $stmtD->fetchColumn();

            if (!$dId) {
                // Crear el producto si no existe
                $db->prepare("INSERT INTO products (name, description, price_usd, price_ves, product_type, category_id, stock, is_visible, kitchen_station, created_at) 
                             VALUES ('Servicio Delivery', 'Servicio de entrega a domicilio', 0, 0, 'simple', ?, 9999, 0, '', NOW())")->execute([$catId]);
                $dId = $db->lastInsertId();
            } else {
                // FORZAR: Actualizar categoría y quitar estación propia del producto para asegurar que no salga en KDS
                $db->prepare("UPDATE products SET category_id = ?, kitchen_station = '' WHERE id = ?")->execute([$catId, $dId]);
            }

            $cartItems[] = [
                'product_id' => $dId,
                'quantity' => 1,
                'price' => $fee,
                'unit_price_final' => $fee,
                'consumption_type' => 'delivery',
                'product_type' => 'simple',
                'client_id' => $cartItems[0]['client_id'] ?? null,
                'employee_id' => $cartItems[0]['employee_id'] ?? null
            ];
        }
    }

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