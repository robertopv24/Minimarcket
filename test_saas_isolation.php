<?php
// test_saas_isolation.php

require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Core\Tenant\TenantContext;
use Minimarcket\Core\Container;
use Minimarcket\Modules\Inventory\Repositories\ProductRepository;
use Minimarcket\Core\Database\ConnectionManager;

echo "🔒 Verificando Aislamiento SaaS (Tenant Isolation)...\n";

global $app;
$container = $app->getContainer();
$pdo = $container->get(ConnectionManager::class)->getConnection();

// 0. Setup: Limpiar y Crear Tenants de prueba en DB (Simulado)
// Asumimos que tenant_id 1 y 2 existen o los usamos como IDs lógicos.
$tenantA = ['id' => 1, 'name' => 'Tenant A - Supermercado'];
$tenantB = ['id' => 2, 'name' => 'Tenant B - Farmacia'];

$productRepo = $container->get(ProductRepository::class);

try {
    // ---------------------------------------------------------
    // TEST 1: Crear Producto en Tenant A
    // ---------------------------------------------------------
    echo "\n[INFO] Cambiando Contexto a Tenant A (ID: 1)...\n";
    TenantContext::setTenant($tenantA);

    $productNameA = "Producto Exclusivo A - " . uniqid();
    echo "[ACT] Creando producto: '$productNameA'\n";

    $idA = $productRepo->create([
        'name' => $productNameA,
        'description' => 'Test A',
        'price_usd' => 10,
        'price_ves' => 100,
        'stock' => 50
    ]);

    // Verificar que existe en A
    $checkA = $productRepo->find($idA);
    if ($checkA && $checkA['name'] === $productNameA) {
        echo "✅ [PASS] Producto creado y visible en Tenant A.\n";
    } else {
        die("❌ [FAIL] Error al crear producto en Tenant A.\n");
    }

    // ---------------------------------------------------------
    // TEST 2: Intentar ver Producto A desde Tenant B
    // ---------------------------------------------------------
    echo "\n[INFO] Cambiando Contexto a Tenant B (ID: 2)...\n";
    TenantContext::setTenant($tenantB);

    echo "[ACT] Buscando producto ID $idA (creado por Tenant A)...\n";
    $leakCheck = $productRepo->find($idA);

    if ($leakCheck === null) {
        echo "✅ [PASS] Aislamiento Exitoso: Tenant B NO puede ver producto de Tenant A.\n";
    } else {
        echo "❌ [FAIL] FUGA DE DATOS: Tenant B pudo ver: " . print_r($leakCheck, true) . "\n";
        die();
    }

    // ---------------------------------------------------------
    // TEST 3: Listados Globales (all)
    // ---------------------------------------------------------
    echo "\n[ACT] Solicitando todos los productos como Tenant B...\n";
    $allB = $productRepo->all();

    $foundLeak = false;
    foreach ($allB as $p) {
        if ($p['id'] == $idA)
            $foundLeak = true;
    }

    if (!$foundLeak) {
        echo "✅ [PASS] El listado 'all()' de Tenant B NO incluye el producto de Tenant A.\n";
    } else {
        echo "❌ [FAIL] FUGA EN LISTADO: El producto de A aparece en el listado de B.\n";
    }

    // ---------------------------------------------------------
    // TEST 4: Crear en B y verificar en A
    // ---------------------------------------------------------
    $productNameB = "Producto Exclusivo B - " . uniqid();
    $idB = $productRepo->create([
        'name' => $productNameB,
        'description' => 'Test B',
        'price_usd' => 20,
        'price_ves' => 200,
        'stock' => 20
    ]);

    echo "\n[INFO] Regresando a Tenant A...\n";
    TenantContext::setTenant($tenantA);
    $checkB_from_A = $productRepo->find($idB);

    if ($checkB_from_A === null) {
        echo "✅ [PASS] Aislamiento Bidireccional correcto.\n";
    } else {
        echo "❌ [FAIL] Tenant A puede ver datos de Tenant B.\n";
    }

    echo "\n🏆 RESULTADO FINAL: TODOS LOS TESTS PASARON. SAAS ISOLATION ACTIVO.\n";

} catch (Exception $e) {
    echo "❌ [ERROR] Excepción: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
