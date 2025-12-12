<?php
/**
 * Modern Page Example
 * This page demonstrates how to use the new Architecture (Services + Container) 
 * instead of legacy globals.
 */
require_once __DIR__ . '/../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Core\Config\ConfigService;
use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Modules\Sales\Services\OrderService;
use Minimarcket\Modules\HR\Services\PayrollService;
use Minimarcket\Modules\Manufacturing\Services\ProductionService;

// 1. Get Container
$container = Container::getInstance();

// 2. Resolve Services
$config = $container->get(ConfigService::class);
$productService = $container->get(ProductService::class);
$orderService = $container->get(OrderService::class);
$payrollService = $container->get(PayrollService::class);
$productionService = $container->get(ProductionService::class);

// 3. Use Services
$siteName = $config->get('site_name', 'Minimarcket');
$totalProducts = count($productService->getAllProducts());
// For OrderService, we don't have a simple count method exposed yet in Service interface (it was in Manager?), 
// let's assume we want something else. OrderService::getRecentOrders() is not implemented yet? 
// Let's use getOrderById for demo or just skip if method missing.
// Actually OrderService has `getOrderDetails($id)`.
// We'll just show Product count.

// HR
$employees = $payrollService->getPayrollStatus();

// Manufacturing
$recipes = $productionService->getAllManufactured();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Modern Architecture Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4">System Health & Architecture Demo</h1>
        <div class="alert alert-success">
            <strong>Architecture:</strong> Modular Monolith (Services + DI)<br>
            <strong>Site Name:</strong> <?= htmlspecialchars($siteName) ?>
        </div>

        <div class="row">
            <!-- Inventory Card -->
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Inventory Module</h5>
                        <p class="card-text display-6"><?= $totalProducts ?></p>
                        <p class="text-muted">Active Products</p>
                    </div>
                </div>
            </div>

            <!-- HR Card -->
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">HR Module</h5>
                        <p class="card-text display-6"><?= count($employees) ?></p>
                        <p class="text-muted">Employees on Payroll</p>
                    </div>
                </div>
            </div>

            <!-- Manufacturing Card -->
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Manufacturing Module</h5>
                        <p class="card-text display-6"><?= count($recipes) ?></p>
                        <p class="text-muted">Manufactured Items</p>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        <h3>Developer Note</h3>
        <p>This page uses <code>Minimarcket\Core\Container</code> to inject services directly, bypassing the legacy
            <code>funciones/</code> proxies. This is the recommended way for all future development.</p>
        <pre class="bg-dark text-white p-3 rounded">
$container = Container::getInstance();
$service = $container->get(ProductService::class);
$data = $service->getAllProducts();
        </pre>
    </div>
</body>

</html>