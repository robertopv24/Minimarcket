<?php
require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Modules\Inventory\Services\RawMaterialService;
use Minimarcket\Modules\Manufacturing\Services\ProductionService;
use Minimarcket\Core\Database\ConnectionManager;

echo "Testing Manufacturing Module...\n";
global $app;
$container = $app->getContainer();

try {
    $rawService = $container->get(RawMaterialService::class);
    $prodService = $container->get(ProductionService::class);

    // 1. Crear Insumo de Prueba (Harina)
    echo "Creating Raw Material...\n";
    $rawName = "Harina Test " . time();
    $rawId = $rawService->createMaterial($rawName, 'kg', 1.50, 10, 1, 'ingredient');
    echo "Raw Material Created: $rawName (ID: $rawId)\n";

    // 2. Agregar Stock al Insumo
    echo "Adding Stock to Raw Material...\n";
    $rawService->addStock($rawId, 100, 1.50); // 100kg @ $1.50
    $raw = $rawService->getMaterialById($rawId);
    echo "Initial Raw Stock: {$raw['stock_quantity']} {$raw['unit']}\n";

    // 3. Crear Producto Manufacturado (Pan)
    echo "Creating Manufactured Product...\n";
    $prodName = "Pan Test " . time();
    $prodService->createManufacturedProduct($prodName, 'und');
    // Buscar el ID recién creado (ya que create no retorna ID explícito en la implementación actual, retorna bool)
    // Deberíamos mejorar create para retornar ID, pero por ahora buscaremos.
    $prods = $prodService->searchManufacturedProducts($prodName);
    $prodId = $prods[0]['id'];
    echo "Manufactured Product Created: $prodName (ID: $prodId)\n";

    // 4. Definir Receta (1 Pan requiere 0.5kg Harina)
    echo "Defining Recipe...\n";
    $prodService->addIngredientToRecipe($prodId, $rawId, 0.5);
    echo "Recipe Added: 0.5kg of Harina per Pan\n";

    // 5. Registrar Producción (Producir 10 Panes)
    echo "Registering Production (Qty: 10)...\n";
    // Costo esperado: 10 * 0.5kg * $1.50 = $7.50 Total. Unit Cost: $0.75.
    $userId = 1; // Admin
    $result = $prodService->registerProduction($prodId, 10, $userId);

    if ($result === true) {
        echo "[PASS] Production Registered Successfully.\n";
    } else {
        echo "[FAIL] Production Error: $result\n";
        exit;
    }

    // 6. Verificar Resultados

    // 6.1 Stock Insumo (Debe ser 100 - 5 = 95)
    $newRaw = $rawService->getMaterialById($rawId);
    echo "New Raw Stock: {$newRaw['stock_quantity']}\n";
    if (abs($newRaw['stock_quantity'] - 95) < 0.001) {
        echo "[PASS] Raw Material Stock Correct.\n";
    } else {
        echo "[FAIL] Raw Material Stock Incorrect. Expected 95.\n";
    }

    // 6.2 Stock y Costo Producto (Stock 10, Costo $0.75)
    $prods = $prodService->searchManufacturedProducts($prodName);
    $newProd = $prods[0];
    echo "New Product Stock: {$newProd['stock']}\n";
    echo "New Product Avg Cost: {$newProd['unit_cost_average']}\n";

    if (abs($newProd['stock'] - 10) < 0.001) {
        echo "[PASS] Manufactured Stock Correct.\n";
    } else {
        echo "[FAIL] Manufactured Stock Incorrect. Expected 10.\n";
    }

    if (abs($newProd['unit_cost_average'] - 0.75) < 0.001) {
        echo "[PASS] Avg Cost Correct.\n";
    } else {
        echo "[FAIL] Avg Cost Incorrect. Expected 0.75.\n";
    }

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
