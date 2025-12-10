<?php
// tests/run_advanced_inventory_test.php
require_once __DIR__ . '/../funciones/OrderManager.php';
require_once __DIR__ . '/MockDatabase.php';
require_once __DIR__ . '/SimpleTest.php';

echo "🚀 Iniciando Test Avanzado de Inventario (Híbrido + Recursivo)...\n\n";

class AdvancedMockDatabase extends MockDatabase
{
    // Sobreascribimos prepare para devolver datos simulados inteligentes
    public function prepare($sql)
    {
        $stmt = parent::prepare($sql);

        // 1. Simular productos (SELECT ... FROM products WHERE id = ?)
        if (strpos($sql, 'FROM products WHERE id =') !== false) {
            // Simulamos la BD de productos
            $stmt->setReturnResultCallback(function ($params) {
                $id = $params[0];
                switch ($id) {
                    case 100: // Simple Reventa (Lata)
                        return [['product_type' => 'simple', 'linked_manufactured_id' => null, 'stock' => 10]];
                    case 200: // Link Directo (Postre)
                        return [['product_type' => 'simple', 'linked_manufactured_id' => 99, 'stock' => 0]];
                    case 300: // Pizza (Prepared)
                        return [['product_type' => 'prepared', 'linked_manufactured_id' => null, 'stock' => 0]];
                    case 400: // Combo (Compound)
                        return [['product_type' => 'compound', 'linked_manufactured_id' => null, 'stock' => 0]];
                }
                return null;
            });
        }

        // 2. Simular Componentes (Recetas/Combos)
        if (strpos($sql, 'FROM product_components') !== false) {
            $stmt->setReturnResultCallback(function ($params) {
                $id = $params[0];
                if ($id == 300) {
                    // Pizza: Lleva Harina (Raw ID 10) y Queso (Raw ID 11)
                    return [
                        ['component_type' => 'raw', 'component_id' => 10, 'quantity' => 0.3], // 300g Harina
                        ['component_type' => 'raw', 'component_id' => 11, 'quantity' => 0.1]  // 100g Queso
                    ];
                }
                if ($id == 400) {
                    // Combo: Lleva 1 Pizza (Prod 300) y 1 Lata (Prod 100)
                    return [
                        ['component_type' => 'product', 'component_id' => 300, 'quantity' => 1],
                        ['component_type' => 'product', 'component_id' => 100, 'quantity' => 1]
                    ];
                }
                return [];
            });
        }

        // 3. Simular Order Items (Para deductStockFromSale)
        if (strpos($sql, 'FROM order_items') !== false) {
            // Este retorno lo configuramos manualmente en el test
        }

        return $stmt;
    }
}

// Helper para MockStatement con callback
class MockStatementWithCallback extends MockStatement
{
    private $callback;
    public function setReturnResultCallback($cb)
    {
        $this->callback = $cb;
    }
    public function fetch($mode = null)
    {
        if ($this->callback) {
            $res = call_user_func($this->callback, $this->params);
            return $res ? $res[0] : false;
        }
        return parent::fetch();
    }
    public function fetchAll($mode = null)
    {
        if ($this->callback) {
            return call_user_func($this->callback, $this->params);
        }
        return parent::fetchAll();
    }
}
// Parchear MockDatabase para usar el statement avanzado
class AdvancedMockDatabasePatched extends AdvancedMockDatabase
{
    public function prepare($sql)
    {
        $this->queries[] = "PREPARE: " . $sql;
        $stmt = new MockStatementWithCallback($sql);
        // Copiar lógica de AdvancedMockDatabase::prepare aqui (simplificado para el ejemplo, 
        // en realidad deberiamos usar composicion pero por brevedad lo hice inline arriba.
        // Re-aplicando la logica de arriba aqui:)
        if (strpos($sql, 'FROM products WHERE id =') !== false) {
            $stmt->setReturnResultCallback(function ($params) {
                $id = $params[0];
                if ($id == 100)
                    return [['product_type' => 'simple', 'linked_manufactured_id' => null]];
                if ($id == 200)
                    return [['product_type' => 'simple', 'linked_manufactured_id' => 99]];
                if ($id == 300)
                    return [['product_type' => 'prepared', 'linked_manufactured_id' => null]];
                if ($id == 400)
                    return [['product_type' => 'compound', 'linked_manufactured_id' => null]];
                return false;
            });
        }
        if (strpos($sql, 'FROM product_components') !== false) {
            $stmt->setReturnResultCallback(function ($params) {
                $id = $params[0];
                if ($id == 300)
                    return [['component_type' => 'raw', 'component_id' => 10, 'quantity' => 0.3]];
                if ($id == 400)
                    return [['component_type' => 'product', 'component_id' => 300, 'quantity' => 1]];
                return [];
            });
        }
        if (strpos($sql, 'FROM order_items') !== false) {
            $stmt->setReturnResultCallback(function ($params) {
                // Devolver el Combo (ID 400)
                return [['order_item_id' => 888, 'product_id' => 400, 'quantity' => 1]];
            });
        }
        if (strpos($sql, 'FROM order_item_modifiers') !== false) {
            // Simular Modificadores: Extra Salsa (ID 50)
            $stmt->setReturnResultCallback(function ($params) {
                // params: [order_item_id, sub_item_index] (si aplica)
                // Si sub_item_index es 0, retornamos extra
                // Verificamos si la query incluye sub_item_index
                //$queryString = $this->queryString; // No accesible facil aqui en callback
                return [['raw_material_id' => 50, 'quantity_adjustment' => 0.05, 'modifier_type' => 'add']];
            });
        }
        $this->statements[] = $stmt;
        return $stmt;
    }
}


// --- CONFIGURACIÓN ---
$mockDb = new AdvancedMockDatabasePatched();
$manager = new OrderManager($mockDb);

// --- TEST 1: Combo Recursivo ---
// Venta de Combo (ID 400). Contiene Pizza (ID 300). Pizza contiene Harina (ID 10).
// Resultado Esperado:
// 1. Descuento de HARINA (Recursividad nivel 2).
// 2. NO Descuento de Pizza Stock (Nivel 1).
// 3. NO Descuento de Combo Stock (Nivel 0).

echo "--- Prueba: Venta de Combo Recursivo ---\n";
// Inyectar el item de orden
$mockItemId = 888;
$mockDb->statements = []; // Reset
// Simulamos el resultado de get order items
// Hack: Modificamos el mock para la primera query
$mockDb->prepare("SELECT ... FROM order_items")->setReturnResult([
    ['id' => $mockItemId, 'product_id' => 400, 'quantity' => 1]
]);

$manager->deductStockFromSale(123);

// Analizar Queries Ejecutadas
$harinaDescontada = false;
$pizzaDescontada = false;

foreach ($mockDb->statements as $stmt) {
    if (strpos($stmt->queryString, 'UPDATE raw_materials') !== false && in_array(10, $stmt->params)) {
        $harinaDescontada = true;
        SimpleTest::assertEquals(0.3, (float) $stmt->params[0], "Debe descontar 0.3 de Harina");
    }
    if (strpos($stmt->queryString, 'UPDATE products') !== false && in_array(300, $stmt->params)) {
        $pizzaDescontada = true;
    }
}

SimpleTest::assertTrue($harinaDescontada, "La Harina (ingrediente de la pizza) debió ser descontada recursivamente.");
SimpleTest::assertTrue(!$pizzaDescontada, "La Pizza (producto intermedio) NO debió ser descontada de su stock de venta.");

echo "\n✨ Validación Recursiva Exitosa.\n";
?>