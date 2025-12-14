<?php
// test_phase1_delete.php

require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Core\Routing\Router;
use Minimarcket\Modules\SupplyChain\Services\SupplierService;
use Minimarcket\Core\Tenant\TenantContext;

echo "🧪 Testing Phase 1: Supplier DELETE Route...\n";

global $app;
$container = $app->getContainer();
$tenantA = ['id' => 1, 'name' => 'Test Tenant'];
TenantContext::setTenant($tenantA);

$router = new Router($container);
require_once __DIR__ . '/routes/web.php';

// Create a Dummy Supplier to Delete
$service = $container->get(SupplierService::class);
$newId = $service->addSupplier("To Delete " . uniqid(), " Nobody", "del@test.com", "0000", "Nowhere");

echo "Created Supplier ID: $newId for Tenant 1.\n";

try {
    // -------------------------------------------------------------
    // Test 1: Tenant 2 tries to delete ID $newId (Should FAIL)
    // -------------------------------------------------------------
    echo "\n[ACT] Tenant 2 tries to DELETE ID $newId...\n";
    TenantContext::setTenant(['id' => 2, 'name' => 'Other Tenant']);
    $_GET['id'] = $newId;

    // We expect the Router to call delete(), which calls service->delete(), via Repo.
    // Repo should return false because tenant_id mismatch.
    ob_start();
    $router->dispatch('GET', '/admin/suppliers/delete');
    ob_end_clean(); // Suppress header() output or redirects

    // Check if it still exists in Tenant 1
    TenantContext::setTenant($tenantA);
    $check = $service->getSupplierById($newId);

    if ($check) {
        echo "✅ [PASS] Tenant 2 FAILED to delete Tenant 1 data. Supplier still exists.\n";
    } else {
        echo "❌ [FAIL] SAAS LEAK: Tenant 2 DELETED Tenant 1 data!\n";
    }

    // -------------------------------------------------------------
    // Test 2: Tenant 1 deletes ID $newId (Should SUCCESS)
    // -------------------------------------------------------------
    echo "\n[ACT] Tenant 1 tries to DELETE ID $newId...\n";
    $_GET['id'] = $newId;

    ob_start();
    $router->dispatch('GET', '/admin/suppliers/delete');
    ob_end_clean();

    $checkAgain = $service->getSupplierById($newId);

    if (!$checkAgain) {
        echo "✅ [PASS] Tenant 1 successfully deleted their own data.\n";
    } else {
        echo "❌ [FAIL] Tenant 1 failed to delete data.\n";
    }

} catch (Exception $e) {
    echo "❌ [ERROR] Exception: " . $e->getMessage() . "\n";
}
