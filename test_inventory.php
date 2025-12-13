<?php
require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Modules\Inventory\Services\RawMaterialService;

echo "Testing Inventory Module...\n";
global $app;
$container = $app->getContainer();

try {
    // 1. Validar ProductService
    echo "1. Testing ProductService...\n";
    $productService = $container->get(ProductService::class);
    $products = $productService->getAllProducts();
    echo "[PASS] ProductService instantiated. Found " . count($products) . " products.\n";

    // 2. Validar RawMaterialService
    echo "2. Testing RawMaterialService...\n";
    $rawMaterialService = $container->get(RawMaterialService::class);
    $materials = $rawMaterialService->getAllMaterials();
    echo "[PASS] RawMaterialService instantiated. Found " . count($materials) . " raw materials.\n";

    // 3. Validar ProductService::getTotalProduct
    echo "3. Testing ProductService::getTotalProduct...\n";
    // We reuse the $productService instance from step 1
    $count = $productService->getTotalProduct();
    echo "[PASS] ProductService::getTotalProduct working. Total products: $count\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

echo "Inventory Tests Passed.\n";
exit(0);
