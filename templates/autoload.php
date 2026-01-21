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

// --- 1. CARGADOR DE VARIABLES DE ENTORNO (.ENV) ---
// Buscamos el archivo .env en la raíz (un nivel arriba de templates)
$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0)
            continue;

        // Separar clave=valor
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Guardar en $_ENV y $_SERVER
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

require_once __DIR__ . '/../funciones/conexion.php';
require_once __DIR__ . '/../funciones/Config.php';
require_once __DIR__ . '/../funciones/SessionHelper.php';


// Crear instancias de las clases principales para su uso global
$config = new GlobalConfig();  // Configuración global
$db = Database::getConnection(); // Conexión a la base de datos


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
