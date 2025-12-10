<?php
// Autoload manual
require_once __DIR__ . '/../funciones/OrderManager.php';
require_once __DIR__ . '/MockDatabase.php';
require_once __DIR__ . '/SimpleTest.php';

// Mock externo para Database::getConnection si fuera necesario, pero ya inyectamos el mock.

echo "🚀 Iniciando Tests de OrderManager...\n\n";

// --- TEST 1: Crear Orden ---
echo "--- TEST 1: Creación de Orden ---\n";

$mockDb = new MockDatabase();
$orderManager = new OrderManager($mockDb);

$userId = 10;
$items = [
    [
        'product_id' => 50,
        'quantity' => 2,
        'price' => 10.00, // Precio base
        'unit_price_final' => 10.00,
        'consumption_type' => 'dine_in'
    ],
    [
        'product_id' => 51,
        'quantity' => 1,
        'price' => 5.00,
        'unit_price_final' => 5.00,
        'consumption_type' => 'takeaway',
        'id' => 999 // ID de cart_item para copiar modificadores
    ]
];
$shippingAddress = "Calle Falsa 123";

try {
    $orderId = $orderManager->createOrder($userId, $items, $shippingAddress);

    SimpleTest::assertTrue($orderId >= 1, "Order ID debería ser válido");
    SimpleTest::assertTrue($mockDb->inTransaction === false, "La transacción debería estar cerrada (commit)");

    // Verificar Queries
    // Query 1: INSERT orders
    // Esperamos total = (2*10) + (1*5) = 25.00

    $foundInsert = false;
    foreach ($mockDb->statements as $stmt) {
        if (strpos($stmt->queryString, 'INSERT INTO orders') !== false) {
            $foundInsert = true;
            $params = $stmt->params; // [user_id, total, address, method]
            SimpleTest::assertEquals(25.0, (float) $params[1], "El total de la orden debe ser 25.00");
            SimpleTest::assertEquals("Calle Falsa 123", $params[2], "La dirección debe coincidir");
        }
    }
    SimpleTest::assertTrue($foundInsert, "Se debió ejecutar INSERT INTO orders");

} catch (Exception $e) {
    echo "❌ EXCEPCIÓN NO ESPERADA: " . $e->getMessage() . "\n";
    exit(1);
}

// --- TEST 2: Transacción Fallida (Rollback) ---
echo "\n--- TEST 2: Rollback en Error ---\n";

$mockDb2 = new MockDatabase();
// Hack: Corromper el mock para que lance error en prepare
class BrokenMockDatabase extends MockDatabase
{
    public function prepare($sql)
    {
        if (strpos($sql, 'INSERT INTO orders') !== false) {
            throw new PDOException("Error simulado de base de datos");
        }
        return parent::prepare($sql);
    }
}

$brokenDb = new BrokenMockDatabase();
$orderManager2 = new OrderManager($brokenDb);

try {
    $orderManager2->createOrder($userId, $items, $shippingAddress);
    echo "❌ DEBIÓ FALLAR: No se lanzó la excepción esperada.\n";
} catch (PDOException $e) {
    echo "✅ Excepción capturada correctamente.\n";
    // Verificar que intentó hacer rollback
    $hasRollback = false;
    foreach ($brokenDb->queries as $q) {
        if ($q === 'ROLLBACK')
            $hasRollback = true;
    }
    SimpleTest::assertTrue($hasRollback, "Se debió ejecutar ROLLBACK tras el error");
}

echo "\n✨ Todos los tests completados.\n";
