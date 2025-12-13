<?php
require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Core\Tenant\TenantService;
use Minimarcket\Core\Tenant\TenantContext;

echo "Testing SaaS Boot...\n";

try {
    global $app;

    // 1. Get Service
    $tenantService = $app->getContainer()->get(TenantService::class);
    echo "TenantService got.\n";

    // 2. Identify Tenant
    $tenant = $tenantService->identifyTenant();
    echo "Identified Tenant: " . ($tenant['name'] ?? 'NULL') . " (ID: " . ($tenant['id'] ?? 'NULL') . ")\n";

    // 3. Set Context
    TenantContext::setTenant($tenant);
    echo "Context Set. Current ID: " . TenantContext::getTenantId() . "\n";

} catch (Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
