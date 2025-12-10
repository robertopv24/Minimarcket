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

$cartItems = $cartManager->getCart($userId);
if (empty($cartItems)) {
    die("Error: El carrito está vacío. <a href='tienda.php'>Volver</a>");
}

// 2. Preparar Datos
$totals = $cartManager->calculateTotal($cartItems);
$totalOrderAmount = $totals['total_usd'];
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

        $processedPayments[] = [
            'method_id' => $methodId,
            'amount' => $amount,
            'currency' => $currency
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

        // 2. Crear Orden
        $orderId = $orderManager->createOrder($userId, $cartItems, $address);
        if (!$orderId)
            throw new Exception("Error al crear la orden.");

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
            $orderManager->updateOrderStatus($orderId, 'delivered'); // Cerrada
            // TODO: Podríamos agregar columna 'payment_type' en orders.

        } elseif ($creditType === 'client_credit') {
            if (!$clientId)
                throw new Exception("Falta ID de Cliente para Crédito.");
            // Registrar Deuda (sin iniciar nueva transacción)
            $res = $creditManager->registerDebt($orderId, $totalOrderAmount, $clientId, null, null, $notes, false);
            if (strpos($res, 'Error') !== false)
                throw new Exception($res); // Retorna string error si límite excedido

            $orderManager->updateOrderStatus($orderId, 'delivered');

        } elseif ($creditType === 'employee_credit') {
            if (!$empId)
                throw new Exception("Falta ID de Empleado para Crédito.");

            $userManager->getUserById($empId); // Validar existencia
            // Registrar Deuda a Empleado (sin iniciar nueva transacción)
            $creditManager->registerDebt($orderId, $totalOrderAmount, null, $empId, null, $notes, false);

            $orderManager->updateOrderStatus($orderId, 'delivered');
        } else {
            throw new Exception("Tipo de operación inválida.");
        }

        // 4. Inventario (Igual para todos)
        $orderManager->deductStockFromSale($orderId);
        $cartManager->emptyCart($userId);

        $db->commit();
        header("Location: ticket.php?id=" . $orderId . "&print=true");
        exit;
    }

    // --- FLUJO NORMAL (PAGO INMEDIATO CONTADO) ---
    // A. CREAR LA ORDEN
    $orderId = $orderManager->createOrder($userId, $cartItems, $address);
    if (!$orderId)
        throw new Exception("Error al crear la orden.");

    $orderManager->updateOrderStatus($orderId, 'paid');

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

    // D. INVENTARIO Y LIMPIEZA
    $orderManager->deductStockFromSale($orderId);
    $cartManager->emptyCart($userId);

    $db->commit();
    // --- FIN TRANSACCIÓN ---

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