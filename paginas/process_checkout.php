<?php
session_start();
require_once '../templates/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tienda.php");
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

// 1. Validar Sesión de Caja
$sessionId = $cashRegisterManager->hasOpenSession($userId);
if (!$userId || !$sessionId) {
    die("Error: No tienes una caja abierta.");
}

// 2. Obtener datos del carrito y formulario
$cartItems = $cartManager->getCart($userId);
if (empty($cartItems)) die("El carrito está vacío.");

$totals = $cartManager->calculateTotal($cartItems);
$totalOrderAmount = $totals['total_usd'];
$customerName = $_POST['customer_name'] ?? 'Cliente General';
$address = $_POST['shipping_address'] ?? 'Tienda';
$rawPayments = $_POST['payments'] ?? [];

// 3. Estructurar Pagos
$processedPayments = [];
$totalPaidCheck = 0;
$rate = $config->get('exchange_rate');

foreach ($rawPayments as $methodId => $amount) {
    if ($amount > 0) {
        // Obtener moneda del método (Consultamos rápido para asegurar)
        // Nota: Idealmente transactionManager debería tener un método para esto,
        // pero aquí lo hacemos simple asumiendo que el ID coincide con lo que enviamos.
        // En producción, valida que $methodId exista.

        // Determinar moneda basándonos en el TransactionManager o una consulta rápida
        // (Aquí asumimos que viene del frontend bien, pero validamos moneda en TransactionManager)

        // Hack rápido: Recuperar info del método desde DB
        $stmt = $db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
        $stmt->execute([$methodId]);
        $currency = $stmt->fetchColumn();

        $processedPayments[] = [
            'method_id' => $methodId,
            'amount' => $amount,
            'currency' => $currency,
            'rate' => ($currency == 'VES') ? $rate : 1
        ];
    }
}

try {
    $db->beginTransaction();

    // 4. Crear la Orden (OrderManager)
    // Modificamos createOrder para aceptar estado 'paid' directo si es venta en tienda
    $orderItems = [];
    foreach ($cartItems as $item) {
        $orderItems[] = [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price_usd']
        ];
    }

    // Nota: createOrder devuelve el ID
    $orderId = $orderManager->createOrder($userId, $orderItems, $address); // Se crea como pending

    // Actualizar estado a PAID inmediatamente
    $orderManager->updateOrderStatus($orderId, 'paid');

    // 5. Registrar Transacciones de Dinero (TransactionManager)
    $paymentSuccess = $transactionManager->processOrderPayments(
        $orderId,
        $processedPayments,
        $totalOrderAmount,
        $userId,
        $sessionId
    );

    if (!$paymentSuccess) {
        throw new Exception("Error al procesar los pagos contables.");
    }

    // 6. Vaciar Carrito y Restar Stock
    // (El OrderManager ya debería restar stock, si no, hay que asegurarse)
    // En tu versión actual OrderManager NO resta stock, lo hacía carrito.php
    // Vamos a restar stock aquí manualmente para asegurar.
    foreach ($cartItems as $item) {
        $prod = $productManager->getProductById($item['product_id']);
        $newStock = $prod['stock'] - $item['quantity'];
        $productManager->updateProductStock($item['product_id'], $newStock);
    }

    $cartManager->emptyCart($userId);

    $db->commit();

    // Redirigir a éxito
    header("Location: tienda.php?success_order=" . $orderId);

} catch (Exception $e) {
    $db->rollBack();
    die("Error crítico procesando la venta: " . $e->getMessage());
}
?>
