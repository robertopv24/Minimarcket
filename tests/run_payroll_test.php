<?php
require_once __DIR__ . '/../templates/autoload.php';
require_once __DIR__ . '/SimpleTest.php';

echo "=== TEST DE INTEGRACIÓN NÓMINA ===\n";

try {
    // 1. Crear Usuario de Prueba (Documento único)
    $uniqueId = uniqid();
    $userEmail = "test_payroll_" . $uniqueId . "@example.com";
    $res = $userManager->createUser("Empleado Test", $userEmail, "123456", "555555", "V" . $uniqueId, "Calle Test", "user");
    $newUser = $userManager->getUserByEmail($userEmail);

    echo "1. Usuario creado ID: " . $newUser['id'] . "\n";

    // 2. Configurar Salario (Update Manual Simulado)
    $stmt = $db->prepare("UPDATE users SET job_role = 'kitchen', salary_amount = 50.00, salary_frequency = 'weekly' WHERE id = ?");
    $stmt->execute([$newUser['id']]);
    echo "2. Salario configurado ($50 weekly, Cocina)\n";

    // 3. Verificar Status Nómina
    $status = $payrollManager->getPayrollStatus('kitchen');
    $found = false;
    foreach ($status as $s) {
        if ($s['id'] == $newUser['id']) {
            $found = true;
            SimpleTest::assertEquals('50.000000', $s['salary_amount'], "Monto salario correcto");
        }
    }
    SimpleTest::assertTrue($found, "Empleado aparece en lista de nómina");

    // 4. Pagar Nómina
    $paymentMethodId = 1; // Asumimos ID 1 existe (USD Cash usualmente)
    $payId = $payrollManager->registerPayment($newUser['id'], 50.00, date('Y-m-d'), null, null, $paymentMethodId, "Pago Test", 1); // Admin ID 1

    echo "3. Pago registrado ID: $payId\n";
    SimpleTest::assertTrue(is_numeric($payId), "Pago exitoso");

    // 5. Verificar Transaccion
    $stmtT = $db->prepare("SELECT * FROM transactions WHERE reference_type = 'adjustment' AND reference_id = ?");
    $stmtT->execute([$payId]);
    $trans = $stmtT->fetch(PDO::FETCH_ASSOC);

    if ($trans) {
        echo "   ✅ Transacción financiera creada: ID " . $trans['id'] . " (Monto: " . $trans['amount'] . ")\n";
    } else {
        echo "   ❌ ERROR: No se creó transacción financiera.\n";
    }

    // Limpieza
    echo "\nLimpiando datos de prueba...\n";
    $db->exec("DELETE FROM users WHERE id = " . $newUser['id']);

} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage() . "\n";
}
