<?php
require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Modules\SupplyChain\Services\SupplierService;
use Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService;
use Minimarcket\Modules\SupplyChain\Services\PurchaseReceiptService;
use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Modules\Inventory\Services\RawMaterialService;

echo "Testing Supply Chain Module...\n";
global $app;
$container = $app->getContainer();

try {
    $supplierService = $container->get(SupplierService::class);
    $poService = $container->get(PurchaseOrderService::class);
    $receiptService = $container->get(PurchaseReceiptService::class);
    $productService = $container->get(ProductService::class);
    $rawService = $container->get(RawMaterialService::class);

    // 1. Crear Proveedor
    echo "Creating Supplier...\n";
    $supplierId = $supplierService->addSupplier("Proveedor Test " . time(), "Contacto Test", "test@provider.com", "555-1234", "Calle Falsa 123");
    if (!$supplierId)
        throw new Exception("Error creating supplier");
    echo "Supplier Created: ID $supplierId\n";

    // 2. Crear Orden de Compra (Con Producto y Materia Prima)
    // Producto existente (Pan Test del paso anterior o buscamos uno)
    $prods = $productService->getAllProducts();
    if (empty($prods)) {
        // Create dummy product
        $productService->createProduct("Prod Supply Test", "Test Desc", 10.0, 10, 30);
        $prods = $productService->getAllProducts();
    }
    $prodId = $prods[0]['id'];
    $initialProdStock = (float) $prods[0]['stock'];

    // Materia Prima
    $raws = $rawService->getAllMaterials();
    if (empty($raws)) {
        $rawService->createMaterial("Mat Supply Test", "kg", 5.0, 10, 0);
        $raws = $rawService->getAllMaterials();
    }
    $rawId = $raws[0]['id'];
    $initialRawStock = (float) $raws[0]['stock_quantity'];

    echo "Creating Purchase Order...\n";
    $items = [
        [
            'item_type' => 'product',
            'item_id' => $prodId,
            'quantity' => 20,
            'unit_price' => 5.0 // Costo. Precio venta será 5 * 1.3 = 6.5
        ],
        [
            'item_type' => 'raw_material',
            'item_id' => $rawId,
            'quantity' => 10,
            'unit_price' => 4.0
        ]
    ];

    $poId = $poService->createPurchaseOrder($supplierId, date('Y-m-d'), date('Y-m-d', strtotime('+3 days')), $items, 36.5); // Rate 36.5
    if (!$poId)
        throw new Exception("Error creating PO");
    echo "PO Created: ID $poId\n";

    // 3. Verificar Stock no ha cambiado aun
    $prodCheck = $productService->getProductById($prodId);
    echo "Stock Check (Pre-Receipt): Prod={$prodCheck['stock']} (Initial: $initialProdStock)\n";
    if (abs($prodCheck['stock'] - $initialProdStock) > 0.001) {
        echo "[WARN] Stock changed before receipt! (Legacy Logic Fix might be needed if this fails standard expectation)\n";
    }

    // 4. Recibir Orden
    echo "Receiving Order...\n";
    $receiptId = $receiptService->createPurchaseReceipt($poId, date('Y-m-d'));
    if (!$receiptId)
        throw new Exception("Error receiving PO");
    echo "Receipt Created: ID $receiptId\n";

    // 5. Verificar Stock y Precios Post-Receipt
    $prodPost = $productService->getProductById($prodId);
    $rawPost = $rawService->getMaterialById($rawId);

    // Prod: +20
    echo "Prod Stock Post: {$prodPost['stock']}\n";
    if (abs($prodPost['stock'] - ($initialProdStock + 20)) < 0.001)
        echo "[PASS] Product Stock Updated\n";
    else
        echo "[FAIL] Product Stock Error\n";

    // Raw: +10
    echo "Raw Stock Post: {$rawPost['stock_quantity']}\n";
    if (abs($rawPost['stock_quantity'] - ($initialRawStock + 10)) < 0.001)
        echo "[PASS] Raw Stock Updated\n";
    else
        echo "[FAIL] Raw Stock Error\n";

    // Validar Status PO
    $po = $poService->getPurchaseOrderById($poId);
    if ($po['status'] === 'received')
        echo "[PASS] PO Status Updated\n";
    else
        echo "[FAIL] PO Status Error: {$po['status']}\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
