<?php
// test_phase1_routes.php

require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Core\Routing\Router;
use Minimarcket\Core\Tenant\TenantContext;

echo "🧪 Testing Phase 1: Supplier Controller Route...\n";

global $app;
$container = $app->getContainer();

// 1. Mock Tenant Context (SaaS)
TenantContext::setTenant(['id' => 1, 'name' => 'Test Tenant']);

// 2. Setup Router
$router = new Router($container);
require_once __DIR__ . '/routes/web.php';

// 3. Dispatch Route
try {
    echo "\n[ACT] Dispatching GET /admin/suppliers...\n";

    // Capture Output
    ob_start();
    $router->dispatch('GET', '/admin/suppliers');
    $output = ob_get_clean();

    // 4. Analyze Output
    echo "[RES] Output Length: " . strlen($output) . " bytes\n";

    if (strpos($output, 'Gestión de Proveedores') !== false) {
        echo "✅ [PASS] View contains title 'Gestión de Proveedores'.\n";
    } else {
        echo "❌ [FAIL] View title not found.\n";
        echo "Preview: " . substr($output, 0, 500) . "...\n";
    }

    if (strpos($output, 'Test Tenant') !== false) {
        // Optional: check if tenant name appears if header displays it.
    }

    // Check for a known supplier if DB has data
    // $output ... 

} catch (Exception $e) {
    echo "❌ [ERROR] Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
