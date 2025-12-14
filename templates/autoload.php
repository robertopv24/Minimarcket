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
// Crear instancias de las clases principales para su uso global
// FIX: Use ConfigService directly (SaaS/Container aware) instead of deprecated GlobalConfig proxy
use Minimarcket\Core\Config\ConfigService;
$config = $app->getContainer()->get(ConfigService::class);
// $config->setGlobals(); // ConfigService loads automatically on get()? Check implementation.
// ConfigService::get() calls load(). setGlobals() calls load().
// Legacy behavior was $config->setGlobals() in Config.php. We should replicate that if needed.
$config->setGlobals();

// We use the legacy Database class for now to ensure 100% compatibility with existing Type Hints
$db = Database::getConnection(); // Conexión a la base de datos (Legacy class)


require_once __DIR__ . '/../funciones/ProductManager.php';

// $productManager = new ProductManager($db); // MOVIDO: Instanciado via Container más abajo

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
// Instancias de los servicios modernos (Reemplazando Managers Legacy)
// Mantenemos los nombres de variables legacy ($productManager) para compatibilidad, 
// pero ahora contienen las instancias de los Servicios modernos (que actúan como proxies o reemplazos directos).

use Minimarcket\Modules\Sales\Services\CartService;
use Minimarcket\Core\View\MenuService;
use Minimarcket\Modules\Sales\Services\OrderService;
use Minimarcket\Modules\SupplyChain\Services\SupplierService;
use Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService;
use Minimarcket\Modules\SupplyChain\Services\PurchaseReceiptService;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Modules\Finance\Services\CashRegisterService;
use Minimarcket\Modules\Finance\Services\TransactionService;
use Minimarcket\Modules\Finance\Services\VaultService;
use Minimarcket\Modules\Inventory\Services\RawMaterialService;
use Minimarcket\Modules\Manufacturing\Services\ProductionService;
use Minimarcket\Modules\HR\Services\PayrollService;
use Minimarcket\Modules\Sales\Services\CreditService;
use Minimarcket\Modules\Inventory\Services\ProductService;

$productManager = $container->get(ProductService::class);
$cartManager = $container->get(CartService::class);
$rateLimiter = new RateLimiter(); // Helper, no service replacement yet
$menus = $container->get(MenuService::class);
$orderManager = $container->get(OrderService::class);
$supplierManager = $container->get(SupplierService::class);
$purchaseOrderManager = $container->get(PurchaseOrderService::class);
$purchaseReceiptManager = $container->get(PurchaseReceiptService::class);
$userManager = $container->get(UserService::class);
$cashRegisterManager = $container->get(CashRegisterService::class);
$transactionManager = $container->get(TransactionService::class);
$vaultManager = $container->get(VaultService::class);
$rawMaterialManager = $container->get(RawMaterialService::class);
$productionManager = $container->get(ProductionService::class);
$payrollManager = $container->get(PayrollService::class);
$creditManager = $container->get(CreditService::class);

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
