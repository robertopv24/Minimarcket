<?php
require_once __DIR__ . '/../templates/autoload.php';
require_once __DIR__ . '/SimpleTest.php';

echo "=== TEST DE INTEGRACIÓN: CAJA REGISTRADORA ===\n";

try {
    // 1. Preparar Usuario de Prueba
    $uniqueId = uniqid();
    $email = "cash_test_$uniqueId@example.com";
    $userManager->createUser("Cashier $uniqueId", $email, "pass", "555", "ID-$uniqueId", "Addr", "admin"); // Admin to ensure permissions
    $user = $userManager->getUserByEmail($email);
    $userId = $user['id'];

    echo "1. Cajero creado ID: $userId\n";

    // 2. Abrir Caja
    echo "2. Abriendo caja...\n";
    $openRes = $cashRegisterManager->openRegister($userId, 100.00, 500.00); // 100 USD, 500 VES
    SimpleTest::assertTrue($openRes['status'], "La caja debió abrirse correctamente.");

    // Verificar estado
    $status = $cashRegisterManager->getStatus($userId);
    SimpleTest::assertEquals('open', $status['status'], "El estado de caja debe ser 'open'.");
    SimpleTest::assertEquals('100.00', $status['opening_balance_usd'], "Saldo inicial USD correcto.");
    echo "   ✅ Caja abierta con saldo inicial confirmado.\n";

    // 3. Simular Movimientos (Esto requeriría TransactionManager, por ahora verificamos cierre simple)
    // En una prueba real, haríamos ventas aquí. Para unit test, cerramos directamente.

    // 4. Cerrar Caja
    echo "3. Cerrando caja...\n";
    // Asumimos cierre con lo mismo + una ganancia simulada
    $closeRes = $cashRegisterManager->closeRegister($userId, 150.00, 600.00);
    SimpleTest::assertTrue($closeRes['status'], "La caja debió cerrarse correctamente.");

    // Verificar histórico
    $stmt = $db->prepare("SELECT * FROM cash_sessions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    SimpleTest::assertEquals('closed', $session['status'], "La sesión quedó cerrada en BD.");
    SimpleTest::assertEquals('150.00', $session['closing_balance_usd'], "Monto de cierre USD guardado.");
    echo "   ✅ Cierre verificado en base de datos. ID Sesión: " . $session['id'] . "\n";

    // Limpieza
    $db->exec("DELETE FROM cash_sessions WHERE user_id = $userId");
    $userManager->deleteUser($userId);
    echo "\nLimpieza completada.\n";

} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
    exit(1);
}
?>