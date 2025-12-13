<?php
require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Modules\Sales\Services\OrderService;
use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Modules\Sales\Services\CartService;
use Minimarcket\Core\Database\ConnectionManager;

echo "Testing Sales Module...\n";
global $app;
$container = $app->getContainer();

try {
    $orderService = $container->get(OrderService::class);
    $productService = $container->get(ProductService::class);
    $cartService = $container->get(CartService::class);

    // 1. Obtener un producto de prueba
    $products = $productService->getAvailableProducts();
    if (empty($products)) {
        die("No available products to test sales.\n");
    }
    $testProduct = $products[0];
    $initialStock = $testProduct['stock'];
    $productId = $testProduct['id'];

    echo "Test Product: {$testProduct['name']} (ID: $productId). Initial Stock: $initialStock\n";

    // 2. Obtener Usuario Valido
    $pdo = $container->get(ConnectionManager::class)->getConnection();
    $stmtUser = $pdo->query("SELECT id FROM users LIMIT 1");
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("No users found in database to test sales.\n");
    }
    $userId = $user['id'];
    echo "Test User ID: $userId\n";

    // 3. Crear Orden
    echo "Creating Order...\n";
    $items = [
        [
            'product_id' => $productId,
            'quantity' => 1,
            'modifiers' => ['note' => 'Test Order'],
            'consumption_type' => 'dine_in'
        ]
    ];

    $orderId = $orderService->createOrder($userId, $items, 'Test Address', 'Pickup');
    echo "Order Created! ID: $orderId\n";

    // 4. Descontar Stock
    echo "Deducting Stock...\n";
    $orderService->deductStockFromSale($orderId);

    // 5. Verificar Stock
    $updatedProduct = $productService->getProductById($productId);
    $newStock = $updatedProduct['stock'];
    echo "New Stock: $newStock\n";

    if (abs($initialStock - $newStock - 1) < 0.001) {
        echo "[PASS] Stock deducted correctly.\n";
    } else {
        echo "[FAIL] Stock mismatch. Expected " . ($initialStock - 1) . ", got $newStock\n";
    }

    // ----------------------------------------------------
    // TEST CART SERVICE
    // ----------------------------------------------------
    echo "\nTesting CartService...\n";

    // 1. Vaciar carrito previo
    $cartService->emptyCart($userId);

    // 2. Agregar item
    echo "Adding to cart...\n";
    $cartService->addToCart($userId, $productId, 2, [], 'takeaway');

    // 3. Verificar carrito
    $cart = $cartService->getCart($userId);
    echo "Cart Items: " . count($cart) . "\n";

    if (count($cart) === 1 && $cart[0]['quantity'] == 2) {
        echo "[PASS] Item added to cart correctly.\n";
    } else {
        echo "[FAIL] Cart Item count or quantity mismatch.\n";
        print_r($cart);
    }

    // ----------------------------------------------------
    // TEST ORDER SERVICE SEARCH
    // ----------------------------------------------------
    echo "\nTesting OrderService Search (formerly Proxy)...\n";
    // $orderManager = new OrderManager(); // REMOVED
    $orders = $orderService->getOrdersBySearchAndFilter('', 'pending');
    echo "Found " . count($orders) . " pending orders via Service.\n";

    // Verificar que encontramos la orden que acabamos de crear
    $found = false;
    foreach ($orders as $o) {
        if ($o['id'] == $orderId)
            $found = true;
    }

    if ($found) {
        echo "[PASS] OrderService found the new order.\n";
    } else {
        echo "[WARN] OrderService did not find the new order (maybe filter issue?).\n";
    }

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
