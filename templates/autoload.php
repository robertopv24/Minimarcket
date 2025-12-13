<?php
/**
 * Autoload de clases del proyecto
 *
 * Este archivo carga automáticamente todas las clases ubicadas en `/funciones/`,
 * evitando múltiples inclusiones manuales en cada archivo.
 */

// Activar la visualización de errores (solo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 2. CORE MODULAR SYSTEM (NEW) ---
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Initialize Application (this handles Container, Env, Config, and Service Registration)
use Minimarcket\Application\Application;

$basePath = realpath(__DIR__ . '/..');
$app = new Application($basePath);
$container = $app->getContainer(); // Keep for compatibility if needed locally

// Register Legacy Database Wrapper if needed by new system (optional)
// $container->set('db', function() { return \Database::getConnection(); });

// --- 3. LEGACY SYSTEM COMPATIBILITY ---
require_once __DIR__ . '/../funciones/conexion.php';
require_once __DIR__ . '/../funciones/Config.php';

// Initialize Session Manager (Centralized Session Handling)
// This ensures that the session is started with consistent configuration across the app.
try {
    $sessionManager = $app->getContainer()->get(\Minimarcket\Core\Session\SessionManager::class);
    $sessionManager->start();
} catch (Exception $e) {
    // Fail silently or log if session cannot start (e.g. CLI)
    // In CLI test scripts, session_start might fail or behave differently.
}

// Crear instancias de las clases principales para su uso global
$config = new GlobalConfig();  // Configuración global
// We use the legacy Database class for now to ensure 100% compatibility with existing Type Hints
$db = Database::getConnection(); // Conexión a la base de datos (Legacy class)


require_once __DIR__ . '/../funciones/ProductManager.php';

$productManager = new ProductManager($db);  // Manejo de productos

require_once __DIR__ . '/../funciones/CartManager.php';
require_once __DIR__ . '/../funciones/Menus.php';
require_once __DIR__ . '/../funciones/OrderManager.php';
require_once __DIR__ . '/../funciones/UserManager.php';
require_once __DIR__ . '/../funciones/CashRegisterManager.php';
require_once __DIR__ . '/../funciones/TransactionManager.php';
require_once __DIR__ . '/../funciones/SupplierManager.php';
require_once __DIR__ . '/../funciones/PurchaseOrderManager.php';
require_once __DIR__ . '/../funciones/PurchaseReceiptManager.php';
require_once __DIR__ . '/../funciones/VaultManager.php';
require_once __DIR__ . '/../funciones/RawMaterialManager.php';
require_once __DIR__ . '/../funciones/ProductionManager.php';
require_once __DIR__ . '/../funciones/PayrollManager.php'; // NEW
require_once __DIR__ . '/../funciones/CreditManager.php'; // NEW
require_once __DIR__ . '/../funciones/RateLimiter.php'; // Rate limiting for security
require_once __DIR__ . '/../funciones/Csrf.php'; // CSRF Protection
require_once __DIR__ . '/../funciones/ExchangeRate.php';
require_once __DIR__ . '/../funciones/EmailController.php';
require_once __DIR__ . '/../funciones/UploadHelper.php';
require_once __DIR__ . '/../funciones/PrinterHelper.php';

// Instancias de los controladores
$cartManager = new CartManager($db);  // Manejo del carrito de compras
$rateLimiter = new RateLimiter(); // Uses defaults: 5 attempts, 300s window, 900s block
$menus = new Menus();  // Manejo de menús (sin parámetros)
$orderManager = new OrderManager($db);  // Manejo de órdenes
$supplierManager = new SupplierManager($db);  // Manejo de Proveedores
$purchaseOrderManager = new PurchaseOrderManager($db);  // Manejo de Órdenes de Compra
$purchaseReceiptManager = new PurchaseReceiptManager($db, $productManager);  // Manejo de Recepciones de Mercancía
$userManager = new UserManager($db);  // Manejo de usuarios
$cashRegisterManager = new CashRegisterManager($db);
$transactionManager = new TransactionManager($db);
$vaultManager = new VaultManager($db);
$rawMaterialManager = new RawMaterialManager($db);
$productionManager = new ProductionManager($db);
$payrollManager = new PayrollManager($db); // NEW
$creditManager = new CreditManager($db); // NEW

/**
 * Ahora en cualquier archivo que incluya `autoload.php` se podrán usar estas instancias, por ejemplo:
 *
 * ```php
 * require_once 'autoload.php';
 *
 * echo "Nombre del sitio: " . $config->get('site_name');
 * $usuario = $userManager->getUserById(1);
 * print_r($usuario);
 * ```
 */
