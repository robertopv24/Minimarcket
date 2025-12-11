<?php
require_once __DIR__ . '/../templates/autoload.php';

echo "=== TEST MANUAL: CONVERSIÓN DE DIVISAS ===\n";

// CONFIG
$vesMethodId = 2; // Pago Movil VES
$usdAmount = 10.00; // Queremos pagar $10 USD
$exchangeRate = $config->get('exchange_rate');

echo "Tasa de Cambio Actual: $exchangeRate\n";
echo "Monto a Pagar (Input): $$usdAmount USD\n";
echo "Método de Pago ID: $vesMethodId (VES)\n\n";

// 1. Crear Cliente y Deuda Mock
$uniqueId = uniqid();
$db->prepare("INSERT INTO clients (name, document_id, credit_limit, current_debt, address, phone) VALUES (?, ?, 100.00, 0.00, 'Test Addr', '555')")
    ->execute(["Client Test $uniqueId", "DOC-$uniqueId"]);
$clientId = $db->lastInsertId();

// Crear Orden Mock
$db->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address) VALUES (21, 100.00, 'delivered', 'Test Address')")
    ->execute();
$orderId = $db->lastInsertId();

// Registrar Deuda de $100
$creditManager->registerDebt($orderId, 100.00, $clientId, null, null, "Test Init");
echo "1. Deuda Inicial creada: $100.00 USD\n";

// 2. Ejecutar Pago
// NOTA: Al llamar a payDebt con $10 y metodo VES, 
// Esperamos que en caja entren (10 * Tasa) Bolívares.
// Pero con el BUG actual, el sistema hará (10 / Tasa) Bolívares.

$debts = $creditManager->getPendingClientDebts($clientId);
$arId = $debts[0]['id'];

// Simular sesión de caja abierta (usamos user 1 admin)
$sessionId = $cashRegisterManager->hasOpenSession(1);
if (!$sessionId) {
    // Abrir caja temporalmente
    $cashRegisterManager->openRegister(1, 0, 0);
    $sessionId = $cashRegisterManager->hasOpenSession(1);
    echo "   (Caja abierta temporalmente ID $sessionId)\n";
}

echo "2. Ejecutando payDebt($arId, $usdAmount, 'cash', ..., $vesMethodId)...\n";
$creditManager->payDebt($arId, $usdAmount, 'cash', null, $vesMethodId, $sessionId);

// 3. Verificar Transacción
$stmt = $db->prepare("SELECT amount, currency, amount_usd_ref FROM transactions WHERE reference_id = ? AND reference_type = 'debt_payment' ORDER BY id DESC LIMIT 1");
$stmt->execute([$arId]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n--- RESULTDOS ---\n";
echo "Monto en Transacción (Nominal): " . $tx['amount'] . " " . $tx['currency'] . "\n";
echo "Monto USD Ref: " . $tx['amount_usd_ref'] . "\n";

$expectedNominal = $usdAmount * $exchangeRate;

if ($tx['currency'] == 'VES') {
    if (abs($tx['amount'] - $expectedNominal) < 0.01) {
        echo "✅ ÉXITO: El monto nominal es correcto ($expectedNominal VES).\n";
    } else {
        echo "❌ FALLO: El monto nominal es INCORRECTO. Esperado: $expectedNominal VES. Obtenido: {$tx['amount']} VES.\n";
    }
}

// Limpieza
// $db->exec("DELETE FROM payments WHERE order_id = $orderId");
// $db->exec("DELETE FROM transactions WHERE reference_id = $arId"); // Dejar para analisis manual si se quiere
?>