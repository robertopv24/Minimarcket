<?php

use Minimarcket\Core\Container;
use Minimarcket\Core\Routing\Router;
use Minimarcket\Core\Tenant\TenantService;
use Minimarcket\Core\Tenant\TenantContext;

// 1. Cargar Autoload y Bootstrapping básico
require_once __DIR__ . '/../src/Application/Application.php'; // Asumiendo que Application.php hace el require del autoload

// Si Application.php no carga el autoload, lo cargamos aquí manualmente para asegurar
if (!class_exists('Minimarcket\Core\Container')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 2. Inicializar la Aplicación (Contenedor, Env, DB)
global $app;
// $app debería estar definido en Application.php o lo instanciamos aquí si Application.php es una clase
// Revisando Application.php anterior: parece ser un script que hace setup.

// Vamos a requerir el index.php original? NO. 
// Vamos a usar el bootstrap de 'templates/autoload.php' que parece ser el standard actual.
require_once __DIR__ . '/../templates/autoload.php';

// 3. Obtener Contenedor e Instancias Core
$container = $app->getContainer();
$router = new Router($container);

// 4. Identificación de Tenant (SaaS Middleware)
try {
    $tenantService = $container->get(TenantService::class);
    $tenant = $tenantService->identifyTenant();
    TenantContext::setTenant($tenant);
} catch (Exception $e) {
    die("Error Critical SaaS: " . $e->getMessage());
}

// 5. Cargar Rutas
require_once __DIR__ . '/../routes/web.php';

// 6. Despachar Solicitud
try {
    $uri = $_SERVER['REQUEST_URI'];
    $method = $_SERVER['REQUEST_METHOD'];

    // Hack temporal para desarrollo en subcarpetas (si existe)
    // Si la URL es /Minimarcket/public/test, queremos /test
    $scriptName = dirname($_SERVER['SCRIPT_NAME']); // /Minimarcket/public
    if (strpos($uri, $scriptName) === 0) {
        $uri = substr($uri, strlen($scriptName));
    }

    $router->dispatch($method, $uri);

} catch (Exception $e) {
    echo "Error " . $e->getCode() . ": " . $e->getMessage();
}
