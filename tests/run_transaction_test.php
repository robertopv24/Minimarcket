<?php
require_once __DIR__ . '/../templates/autoload.php';
require_once __DIR__ . '/SimpleTest.php';

echo "=== TEST DE INTEGRACIÓN: TRANSACCIONES FINANCIERAS ===\n";

try {
    // 1. Registrar Transacción Manual
    $amount = 123.45;
    $desc = "Transacción Test Unitario";
    $type = "income";

    echo "1. Registrando ingreso manual ($amount)...\n";
    // registerTransaction($type, $amount, $description, $userId, $referenceType=null, $referenceId=null)
    // Usamos usuario admin (ID 1 asumiendo existe, o creamos uno pero para simplicidad usamos 1)

    $txId = $transactionManager->registerTransaction($type, $amount, $desc, 1, 'manual', 0);

    SimpleTest::assertTrue(is_numeric($txId), "Se debió retornar un ID de transacción.");

    // 2. Verificar en BD
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$txId]);
    $tx = $stmt->fetch(PDO::FETCH_ASSOC);

    SimpleTest::assertEquals($amount, (float) $tx['amount'], "Monto correcto.");
    SimpleTest::assertEquals($type, $tx['type'], "Tipo correcto.");
    echo "   ✅ Transacción verificada ID: $txId\n";

    // 3. Obtener Balance
    // No hay método directo simple de obtener balance global en este manager sin filtros,
    // pero podemos probar getTransactionsByDate
    $today = date('Y-m-d');
    $txs = $transactionManager->getTransactionsByDate($today, $today);

    $found = false;
    foreach ($txs as $t) {
        if ($t['id'] == $txId) {
            $found = true;
            break;
        }
    }
    SimpleTest::assertTrue($found, "La transacción aparece en el reporte del día.");
    echo "   ✅ Reporte validado.\n";

    // Limpieza
    $db->exec("DELETE FROM transactions WHERE id = $txId");
    echo "\nLimpieza completada.\n";

} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
    exit(1);
}
?>