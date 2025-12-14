<?php
// test_credit_debts.php

require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\Sales\Services\CreditService;
use Minimarcket\Core\Tenant\TenantContext;

echo "🧪 Testing CreditService::getPendingEmployeeDebts...\n";

global $app;
$container = $app->getContainer();
TenantContext::setTenant(['id' => 1, 'name' => 'Test Tenant']);

/** @var CreditService $creditService */
$creditService = $container->get(CreditService::class);

try {
    // Test with ID 1 (Admin/Default User)
    $userId = 1;
    echo "\n[ACT] Fetching debts for User ID $userId...\n";

    $debts = $creditService->getPendingEmployeeDebts($userId);

    echo "✅ Method call successful.\n";
    echo "Count: " . count($debts) . "\n";

    if (is_array($debts)) {
        echo "✅ Return type is array.\n";
    } else {
        echo "❌ Return type is NOT array.\n";
    }

} catch (Error $e) {
    echo "❌ [FATAL] Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ [ERROR] Exception: " . $e->getMessage() . "\n";
}
