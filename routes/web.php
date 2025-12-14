<?php

use Minimarcket\Core\Routing\Router;

/** @var Router $router */

$router->get('/', function () {
    echo "<h1>Minimarcket SaaS</h1><p>Bienvenido al nuevo Router Modular.</p>";
});

// Rutas de prueba para validar el sistema
$router->get('/test', function () {
    return "Test Route OK";
});

// --- SUPPLY CHAIN MODULE ---
$router->get('/admin/suppliers', [\Minimarcket\Modules\SupplyChain\Controllers\SupplierController::class, 'index']);
$router->get('/admin/suppliers/create', [\Minimarcket\Modules\SupplyChain\Controllers\SupplierController::class, 'create']);
$router->post('/admin/suppliers/store', [\Minimarcket\Modules\SupplyChain\Controllers\SupplierController::class, 'store']);
$router->get('/admin/suppliers/edit', [\Minimarcket\Modules\SupplyChain\Controllers\SupplierController::class, 'edit']);
$router->post('/admin/suppliers/update', [\Minimarcket\Modules\SupplyChain\Controllers\SupplierController::class, 'update']);
$router->get('/admin/suppliers/delete', [\Minimarcket\Modules\SupplyChain\Controllers\SupplierController::class, 'delete']);
