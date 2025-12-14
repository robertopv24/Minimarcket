<?php
// test_dashboard_metrics.php

require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\Sales\Services\OrderService;
use Minimarcket\Core\Tenant\TenantContext;

echo "🧪 Testing Dashboard Metrics (OrderService)...\n";

global $app;
$container = $app->getContainer();
TenantContext::setTenant(['id' => 1, 'name' => 'Test Tenant']);

/** @var OrderService $orderService */
$orderService = $container->get(OrderService::class);

try {
    echo "\n[ACT] Testing Reporting Methods...\n";

    $dia = $orderService->getTotalVentasDia();
    echo "✅ Total Ventas Día: $" . number_format($dia, 2) . "\n";

    $sem = $orderService->getTotalVentasSemana();
    echo "✅ Total Ventas Semana: $" . number_format($sem, 2) . "\n";

    $mes = $orderService->getTotalVentasMes();
    echo "✅ Total Ventas Mes: $" . number_format($mes, 2) . "\n";

    $anio = $orderService->getTotalVentasAnio();
    echo "✅ Total Ventas Año: $" . number_format($anio, 2) . "\n";

    $pending = $orderService->countOrdersByStatus('pending');
    echo "✅ Pending Orders: $pending\n";

    $recent = $orderService->getUltimosPedidos(3);
    echo "✅ Recent Orders (Count: " . count($recent) . ")\n";
    if (count($recent) > 0) {
        print_r($recent[0]);
    }

} catch (Exception $e) {
    echo "❌ [ERROR] Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} catch (Error $e) {
    echo "❌ [FATAL] Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
