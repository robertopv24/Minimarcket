<?php
require_once __DIR__ . '/../templates/autoload.php';
require_once __DIR__ . '/SimpleTest.php';

echo "=== TEST DE INTEGRACIÓN CRÉDITOS ===\n";

try {
    // 1. Setup: Crear Cliente Test
    $uniqueId = uniqid();
    $db->prepare("INSERT INTO clients (name, document_id, credit_limit, current_debt, address, phone) VALUES (?, ?, 100.00, 0.00, 'Test Addr', '555')")
        ->execute(["Cliente Test $uniqueId", "DOC-$uniqueId"]);
    $clientId = $db->lastInsertId();

    echo "1. Cliente creado ID: $clientId (Límite $100)\n";

    // 1.5. Crear Usuario Mock para la Orden (FK constraint)
    $tempUserEmail = "credit_test_" . $uniqueId . "@example.com";
    $userManager->createUser("User $uniqueId", $tempUserEmail, "password", "555", "V$uniqueId", "Addr", "user");
    $user = $userManager->getUserByEmail($tempUserEmail);
    $userId = $user['id'];

    // 2. Crear Orden Mock
    $db->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address) VALUES (?, 50.00, 'delivered', 'Test Address')")
        ->execute([$userId]);
    $orderId = $db->lastInsertId();
    echo "2. Orden Mock creada ID: $orderId ($50)\n";

    // 3. Registrar Deuda
    $res = $creditManager->registerDebt($orderId, 50.00, $clientId, null, null, "Test Crédito");
    echo "3. Registro de deuda: $res\n";

    $client = $creditManager->getClientById($clientId);
    SimpleTest::assertEquals('50.000000', $client['current_debt'], "Deuda del cliente actualizada a 50");

    // 4. Intentar exceder límite
    // Límite 100, deuda 50. Intentar 60 (Total 110) -> Debe fallar
    $resFail = $creditManager->registerDebt($orderId, 60.00, $clientId, null, null, "Test Fail");
    echo "4. Intento exceder límite: $resFail\n";

    if (strpos($resFail, 'Error') !== false) {
        echo "   ✅ Bloqueo de límite correcto.\n";
    } else {
        echo "   ❌ ERROR: Se permitió exceder el límite.\n";
    }

    // 5. Pagar Deuda
    $creditManager->payDebt($client['id'], 25.00, 'cash'); // ID no es AR ID, payDebt toma AR ID?
    // Verificamos firma: public function payDebt($arId, $amount, ...)
    // Necesitamos el AR ID.
    $debts = $creditManager->getPendingClientDebts($clientId);
    $arId = $debts[0]['id'];

    $creditManager->payDebt($arId, 25.00, 'cash');

    $clientRefreshed = $creditManager->getClientById($clientId);
    echo "5. Deuda tras abono de $25: " . $clientRefreshed['current_debt'] . "\n";
    SimpleTest::assertEquals('25.000000', $clientRefreshed['current_debt'], "Deuda restante correcta");

    // Limpieza
    $db->exec("DELETE FROM accounts_receivable WHERE client_id = $clientId");
    $db->exec("DELETE FROM clients WHERE id = $clientId");
    $db->exec("DELETE FROM orders WHERE id = $orderId");
    if (isset($userId)) {
        $db->exec("DELETE FROM users WHERE id = $userId");
    }
    echo "\nLimpieza completada.\n";

} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}
