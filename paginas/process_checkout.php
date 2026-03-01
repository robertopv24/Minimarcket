<?php
session_start();
require_once '../templates/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tienda.php");
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

// 1. Validaciones Iniciales
$sessionId = $cashRegisterManager->hasOpenSession($userId);
if (!$userId || !$sessionId) {
    die("Error: No tienes una caja abierta. <a href='apertura_caja.php'>Abrir Caja</a>");
}

$orderId = $_POST['order_id'] ?: null;
$cartItems = [];

if ($orderId) {
    // Si es una orden existente, cargamos sus ítems
    $cartItems = $orderManager->getOrderItems($orderId);
} else {
    // Si no, usamos el carrito
    $cartItems = $cartManager->getCart($userId);
}

if (empty($cartItems)) {
    die("Error: El carrito o la orden están vacíos. <a href='tienda.php'>Volver</a>");
}

// NUEVO: Manejo de Delivery para Venta Directa (desde Carrito)
$deliveryTier = $_POST['delivery_tier'] ?? null;
$consumptionType = $_POST['consumption_type'] ?? 'dine_in';

if (!$orderId && $consumptionType === 'delivery') {
    // 1. Aplicar Etiquetas de Delivery a los ítems
    foreach ($cartItems as &$item) {
        $item['is_takeaway'] = 1;
        $item['consumption_type'] = 'delivery';
    }

    // 2. Calcular y Añadir Cargo por Servicio si corresponde
    if ($deliveryTier === 'B' || $deliveryTier === 'C') {
        $base = floatval($config->get('delivery_base_cost', 0));
        $fee = ($deliveryTier === 'C') ? ($base * 2) : $base;

        if ($fee > 0) {
            // Asegurar Categoría 'DOMICILIO'
            $stmtCat = $db->prepare("SELECT id FROM categories WHERE name = 'DOMICILIO' LIMIT 1");
            $stmtCat->execute();
            $catId = $stmtCat->fetchColumn();
            if (!$catId) {
                $db->prepare("INSERT INTO categories (name, kitchen_station, icon, description) VALUES ('DOMICILIO', 'none', 'fa-truck', 'Gastos de envío')")->execute();
                $catId = $db->lastInsertId();
            }

            // Buscar/Crear Producto 'Servicio Delivery'
            $stmtD = $db->prepare("SELECT id FROM products WHERE name = 'Servicio Delivery' LIMIT 1");
            $stmtD->execute();
            $dId = $stmtD->fetchColumn();
            if (!$dId) {
                $db->prepare("INSERT INTO products (name, description, price_usd, price_ves, product_type, category_id, stock, is_visible, kitchen_station, created_at) 
                             VALUES ('Servicio Delivery', 'Servicio de entrega a domicilio', 0, 0, 'simple', ?, 9999, 0, '', NOW())")->execute([$catId]);
                $dId = $db->lastInsertId();
            }

            $cartItems[] = [
                'product_id' => $dId,
                'quantity' => 1,
                'price' => $fee,
                'unit_price_final' => $fee,
                'consumption_type' => 'delivery',
                'name' => 'Servicio Delivery', // Para calculateTotal si no re-leemos
                'product_type' => 'simple',
                'total_price' => $fee
            ];
        }
    }
}

// 2. Preparar Datos
if ($orderId) {
    $orderData = $orderManager->getOrderById($orderId);
    $totalOrderAmount = $orderData['total_price'];
} else {
    $totals = $cartManager->calculateTotal($cartItems);
    $totalOrderAmount = $totals['total_usd'];
}
$customerName = $_POST['customer_name'] ?? 'Cliente General';
$address = $_POST['shipping_address'] ?? 'Tienda';
$rate = $config->get('exchange_rate');

// 3. Estructurar Array de Pagos
$rawPayments = $_POST['payments'] ?? [];
$processedPayments = [];

foreach ($rawPayments as $methodId => $amount) {
    if ($amount > 0) {
        $stmt = $db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
        $stmt->execute([$methodId]);
        $currency = $stmt->fetchColumn();

        // CAPTURAR DETALLES ADICIONALES (Referencia y Remitente)
        $details = $_POST['payment_details'][$methodId] ?? [];
        $paymentRef = $details['reference'] ?? null;
        $senderName = $details['sender'] ?? null;

        $processedPayments[] = [
            'method_id' => $methodId,
            'amount' => $amount,
            'currency' => $currency,
            'payment_reference' => $paymentRef,
            'sender_name' => $senderName
        ];
    }
}

try {
    // --- INICIO TRANSACCIÓN MAESTRA ---
    $db->beginTransaction();

    // A. VALIDAR CRÉDITO/BENEFICIO
    $isCredit = isset($_POST['is_credit']) && $_POST['is_credit'] === '1';

    if ($isCredit) {
        // 1. Validar Autorización
        $adminPass = $_POST['admin_password'] ?? '';
        if (!$userManager->validateAnyAdminPassword($adminPass)) {
            throw new Exception("⛔ Contraseña de Administrador Incorrecta.");
        }

        // 2. Crear o Obtener Orden
        if (!$orderId) {
            // Enriquecer cartItems con nombres de productos si faltan y asinar IDs de crédito
            foreach ($cartItems as &$item) {
                if (empty($item['name'])) {
                    $pData = $productManager->getProductById($item['product_id']);
                    $item['name'] = $pData['name'] ?? 'Producto';
                    $item['short_code'] = $pData['short_code'] ?? '';
                }
                $item['client_id'] = $clientId;
                $item['employee_id'] = $empId;
            }
            unset($item);

            $orderId = $orderManager->createOrder($userId, $cartItems, $address);
            if (!$orderId)
                throw new Exception("Error al crear la orden.");
            // Inventario solo se descuenta si la orden es NUEVA
            $orderManager->deductStockFromSale($orderId);
        }

        // 3. Procesar según Tipo
        $creditType = $_POST['credit_type'] ?? ''; // client_credit, employee_credit, benefit
        $clientId = $_POST['credit_client_id'] ?: null;
        $empId = $_POST['credit_employee_id'] ?: null;

        $notes = "Autorizado por Admin. Ref: " . date('Y-m-d H:i');

        if ($creditType === 'benefit') {
            // BENEFICIO: Gasto de la empresa (No deuda)
            // Marcamos orden como 'delivered' pero agregamos una nota interna de que fue beneficio.
            // Opcional: Registrar transacción 'expense' ficticia para cuadrar inventario vs gasto?
            // Por ahora, solo descontamos stock y marcamos pagado sin flujo de caja.
            $orderManager->updateOrderStatus($orderId, 'paid'); // Enviar a KDS/Despacho
            // TODO: Podríamos agregar columna 'payment_type' en orders.

        } elseif ($creditType === 'client_credit') {
            if (!$clientId)
                throw new Exception("Falta ID de Cliente para Crédito.");
            // Registrar Deuda (sin iniciar nueva transacción)
            $res = $creditManager->registerDebt($orderId, $totalOrderAmount, $clientId, null, null, $notes, false);
            if (strpos($res, 'Error') !== false)
                throw new Exception($res); // Retorna string error si límite excedido

            $orderManager->updateOrderStatus($orderId, 'paid');
        } elseif ($creditType === 'employee_credit') {
            if (!$empId)
                throw new Exception("Falta ID de Empleado para Crédito.");

            $userManager->getUserById($empId); // Validar existencia
            // Registrar Deuda a Empleado (sin iniciar nueva transacción)
            $creditManager->registerDebt($orderId, $totalOrderAmount, null, $empId, null, $notes, false);

            $orderManager->updateOrderStatus($orderId, 'paid');
        } else {
            throw new Exception("Tipo de operación inválida.");
        }

        // 4. Limpieza (Solo si venía de carrito)
        if (!$_POST['order_id']) {
            $cartManager->emptyCart($userId);
        }

        // Limpiar cliente de la sesión tras completar la venta
        unset($_SESSION['pos_client_id']);
        unset($_SESSION['pos_client_name']);

        $db->commit();
        header("Location: ticket.php?id=" . $orderId . "&print=true");
        exit;
    }

    // A. OBTENER O CREAR LA ORDEN
    if (!$orderId) {
        $orderId = $orderManager->createOrder($userId, $cartItems, $address, null, $_POST['delivery_tier'] ?? null, $customerName);
        if (!$orderId)
            throw new Exception("Error al crear la orden.");
        $orderManager->updateOrderStatus($orderId, 'preparing');
        // Solo descontamos inventario si la orden es NUEVA
        $orderManager->deductStockFromSale($orderId);
    } else {
        // SEGURIDAD: Si la orden ya está pagada/entregada, no registramos pagos de nuevo
        $existingOrder = $orderManager->getOrderById($orderId);
        if ($existingOrder && $existingOrder['status'] === 'delivered') {
            $db->commit();
            header("Location: ticket.php?id=" . $orderId . "&print=true");
            exit;
        }
        // Si ya existía (estaba en preparando/lista), la marcamos como pagada/entregada ahora
        $orderManager->updateOrderStatus($orderId, 'delivered');
    }

    // B. REGISTRAR PAGOS (INGRESOS)
    // El Manager ya no calcula vueltos, solo registra lo que entró.
    $transactionManager->processOrderPayments($orderId, $processedPayments, $userId, $sessionId);

    // C. CALCULAR Y REGISTRAR VUELTO (MANUAL)
    // 1. Calcular cuánto pagó realmente en USD
    $realPaidUsd = 0;
    foreach ($processedPayments as $p) {
        if ($p['currency'] == 'VES')
            $realPaidUsd += ($p['amount'] / $rate);
        else
            $realPaidUsd += $p['amount'];
    }

    $changeDue = $realPaidUsd - $totalOrderAmount;

    // 2. Si hay vuelto y se seleccionó método, llamar al Manager
    if ($changeDue > 0.01 && !empty($_POST['change_method_id'])) {
        $changeMethodId = $_POST['change_method_id'];

        // Obtener moneda del método de vuelto
        $stmtM = $db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
        $stmtM->execute([$changeMethodId]);
        $changeCurrency = $stmtM->fetchColumn();

        // Calcular monto nominal (Ej: $1 vuelto en Bs = 60 Bs)
        $changeAmountNominal = ($changeCurrency == 'VES') ? ($changeDue * $rate) : $changeDue;

        // LLAMADA LIMPIA AL MANAGER
        $transactionManager->registerOrderChange(
            $orderId,
            $changeAmountNominal,
            $changeCurrency,
            $changeMethodId,
            $userId,
            $sessionId
        );
    }

    // D. LIMPIEZA
    if (!$_POST['order_id']) {
        $cartManager->emptyCart($userId);
    }

    // Limpiar cliente de la sesión tras completar la venta
    unset($_SESSION['pos_client_id']);
    unset($_SESSION['pos_client_name']);

    $db->commit();
    header("Location: ticket.php?id=" . $orderId . "&print=true");
    exit;

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();

    echo "<div style='padding:20px; background:#f8d7da; color:#721c24; margin:20px; border:1px solid #f5c6cb;'>";
    echo "<h3>🚫 Error al Procesar</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<a href='checkout.php' style='font-weight:bold;'>Volver</a>";
    echo "</div>";
    exit;
}
?>