<?php
session_start();
require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\CashRegisterService;
use Minimarcket\Modules\Sales\Services\CartService;
use Minimarcket\Modules\Sales\Services\OrderService;
use Minimarcket\Modules\Finance\Services\TransactionService;
use Minimarcket\Modules\Finance\Services\CreditService;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Core\Config\ConfigService;
use Minimarcket\Core\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tienda.php");
    exit;
}

$container = Container::getInstance();
$cashRegisterService = $container->get(CashRegisterService::class);
$cartService = $container->get(CartService::class);
$orderService = $container->get(OrderService::class);
$transactionService = $container->get(TransactionService::class);
$creditService = $container->get(CreditService::class);
$userService = $container->get(UserService::class);
$configService = $container->get(ConfigService::class);
$db = Database::getConnection();

$userId = $_SESSION['user_id'] ?? null;

// 1. Validaciones Iniciales
$sessionId = $cashRegisterService->hasOpenSession($userId);
if (!$userId || !$sessionId) {
    die("Error: No tienes una caja abierta. <a href='apertura_caja.php'>Abrir Caja</a>");
}

$cartItems = $cartService->getCart($userId);
if (empty($cartItems)) {
    die("Error: El carrito está vacío. <a href='tienda.php'>Volver</a>");
}

// 2. Preparar Datos
$totals = $cartService->calculateTotal($cartItems);
$totalOrderAmount = $totals['total_usd'];
$customerName = $_POST['customer_name'] ?? 'Cliente General';
$address = $_POST['shipping_address'] ?? 'Tienda';
$rate = $configService->get('exchange_rate');

// 3. Estructurar Array de Pagos
$rawPayments = $_POST['payments'] ?? [];
$processedPayments = [];

// Get Payment Methods to lookup currency (cached in Service logic ideally, but here we query or use service)
$allMethods = $transactionService->getPaymentMethods();
$methodsMap = [];
foreach ($allMethods as $m) {
    $methodsMap[$m['id']] = $m['currency'];
}

foreach ($rawPayments as $methodId => $amount) {
    if ($amount > 0) {
        if (!isset($methodsMap[$methodId]))
            continue;

        $processedPayments[] = [
            'method_id' => $methodId,
            'amount' => $amount,
            'currency' => $methodsMap[$methodId]
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
        if (!$userService->validateAnyAdminPassword($adminPass)) {
            throw new Exception("⛔ Contraseña de Administrador Incorrecta.");
        }

        // 2. Crear Orden
        $orderId = $orderService->createOrder($userId, $cartItems, $address);
        if (!$orderId)
            throw new Exception("Error al crear la orden.");

        // 3. Procesar según Tipo
        $creditType = $_POST['credit_type'] ?? ''; // client_credit, employee_credit, benefit
        $clientId = !empty($_POST['credit_client_id']) ? $_POST['credit_client_id'] : null;
        $empId = !empty($_POST['credit_employee_id']) ? $_POST['credit_employee_id'] : null;

        $notes = "Autorizado por Admin. Ref: " . date('Y-m-d H:i');

        if ($creditType === 'benefit') {
            // BENEFICIO: Gasto de la empresa (No deuda)
            $orderService->updateOrderStatus($orderId, 'delivered');

        } elseif ($creditType === 'client_credit') {
            if (!$clientId)
                throw new Exception("Falta ID de Cliente para Crédito.");

            // Registrar Deuda (sin iniciar nueva transacción, pasamos false)
            $res = $creditService->registerDebt($orderId, $totalOrderAmount, $clientId, null, null, $notes, false);
            if (is_string($res) && strpos($res, 'Error') !== false)
                throw new Exception($res);

            $orderService->updateOrderStatus($orderId, 'delivered');

        } elseif ($creditType === 'employee_credit') {
            if (!$empId)
                throw new Exception("Falta ID de Empleado para Crédito.");

            $userService->getUserById($empId); // Validar existencia
            // Registrar Deuda a Empleado (userId arg index = 4)
            // Function: registerDebt($orderId, $amount, $clientId = null, $userId = null, ...)
            $creditService->registerDebt($orderId, $totalOrderAmount, null, $empId, null, $notes, false);

            $orderService->updateOrderStatus($orderId, 'delivered');

        } else {
            throw new Exception("Tipo de operación inválida.");
        }

        // 4. Inventario (Igual para todos)
        $orderService->deductStockFromSale($orderId);
        $cartService->emptyCart($userId);

        $db->commit();
        header("Location: ticket.php?id=" . $orderId . "&print=true");
        exit;
    }

    // --- FLUJO NORMAL (PAGO INMEDIATO CONTADO) ---
    // A. CREAR LA ORDEN
    $orderId = $orderService->createOrder($userId, $cartItems, $address);
    if (!$orderId)
        throw new Exception("Error al crear la orden.");

    $orderService->updateOrderStatus($orderId, 'paid');

    // B. REGISTRAR PAGOS (INGRESOS)
    $transactionService->processOrderPayments($orderId, $processedPayments, $userId, $sessionId);

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

    // 2. Si hay vuelto y se seleccionó método
    if ($changeDue > 0.01 && !empty($_POST['change_method_id'])) {
        $changeMethodId = $_POST['change_method_id'];

        // Obtener moneda del método de vuelto
        if (isset($methodsMap[$changeMethodId])) {
            $changeCurrency = $methodsMap[$changeMethodId];

            // Calcular monto nominal (Ej: $1 vuelto en Bs = 60 Bs)
            $changeAmountNominal = ($changeCurrency == 'VES') ? ($changeDue * $rate) : $changeDue;

            $transactionService->registerOrderChange(
                $orderId,
                $changeAmountNominal,
                $changeCurrency,
                $changeMethodId,
                $userId,
                $sessionId
            );
        }
    }

    // D. INVENTARIO Y LIMPIEZA
    $orderService->deductStockFromSale($orderId);
    $cartService->emptyCart($userId);

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