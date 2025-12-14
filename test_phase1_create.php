<?php
// test_phase1_create.php

require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Core\Routing\Router;
use Minimarcket\Core\Tenant\TenantContext;

echo "🧪 Testing Phase 1: Supplier CREATE Route...\n";

global $app;
$container = $app->getContainer();
TenantContext::setTenant(['id' => 1, 'name' => 'Test Tenant']);

$router = new Router($container);
require_once __DIR__ . '/routes/web.php';

try {
    echo "\n[ACT] Dispatching GET /admin/suppliers/create...\n";

    ob_start();
    $router->dispatch('GET', '/admin/suppliers/create');
    $output = ob_get_clean();

    // Analyze Output
    echo "[RES] Output Length: " . strlen($output) . " bytes\n";

    if (strpos($output, 'Registrar Nuevo Proveedor') !== false) {
        echo "✅ [PASS] Form title 'Registrar Nuevo Proveedor' found.\n";
    } else {
        echo "❌ [FAIL] Form title not found.\n";
        echo "Preview: " . substr($output, 0, 500) . "...\n";
    }

    if (strpos($output, 'name="csrf_token"') !== false) {
        echo "✅ [PASS] CSRF Token field found.\n";
    } else {
        echo "⚠️ [WARN] CSRF Token field not found (check if Csrf class exists).\n";
    }

} catch (Exception $e) {
    echo "❌ [ERROR] Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
