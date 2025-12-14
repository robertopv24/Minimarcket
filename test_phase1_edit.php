<?php
// test_phase1_edit.php

require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Core\Routing\Router;
use Minimarcket\Core\Tenant\TenantContext;

echo "🧪 Testing Phase 1: Supplier EDIT Route...\n";

global $app;
$container = $app->getContainer();
TenantContext::setTenant(['id' => 1, 'name' => 'Test Tenant']);

$router = new Router($container);
require_once __DIR__ . '/routes/web.php';

try {
    // Necesitamos un ID real. Asumimos ID 1 (creado en tests anteriores o existente)
    // Para el test, simulamos que el parametro GET id = 1
    $_GET['id'] = 1;

    echo "\n[ACT] Dispatching GET /admin/suppliers/edit?id=1...\n";

    ob_start();
    $router->dispatch('GET', '/admin/suppliers/edit');
    $output = ob_get_clean();

    // Analyze Output
    echo "[RES] Output Length: " . strlen($output) . " bytes\n";

    if (strpos($output, 'Editar Proveedor') !== false) {
        echo "✅ [PASS] Form title 'Editar Proveedor' found.\n";
    } else {
        echo "❌ [FAIL] Form title not found (Maybe ID 1 does not exist or SaaS block?).\n";
        echo "Preview: " . substr($output, 0, 500) . "...\n";
    }

    // Test SaaS Block: Tenant 2 request ID 1
    TenantContext::setTenant(['id' => 2, 'name' => 'Other Tenant']);
    echo "\n[ACT] Dispatching GET /admin/suppliers/edit?id=1 AS TENANT 2 (Should Fail)...\n";

    ob_start();
    try {
        $router->dispatch('GET', '/admin/suppliers/edit');
    } catch (\Throwable $t) {
        // Controller might die() or throw.
    }
    $output2 = ob_get_clean();

    if (strpos($output2, 'Proveedor no encontrado') !== false || strpos($output2, 'acceso denegado') !== false) {
        echo "✅ [PASS] Access Denied for Tenant 2 accessing Tenant 1 data.\n";
    } else {
        echo "❌ [FAIL] Tenant 2 could access Tenant 1 data (or unexpected error).\n";
        echo "Output: " . substr($output2, 0, 100) . "\n";
    }

} catch (Exception $e) {
    echo "❌ [ERROR] Exception: " . $e->getMessage() . "\n";
}
