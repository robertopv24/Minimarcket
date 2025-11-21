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


require_once '../funciones/conexion.php';
require_once '../funciones/Config.php';


// Crear instancias de las clases principales para su uso global
$config = new GlobalConfig();  // Configuración global
$db = Database::getConnection(); // Conexión a la base de datos


require_once '../funciones/ProductManager.php';

$productManager = new ProductManager($db);  // Manejo de productos

require_once '../funciones/CartManager.php';
require_once '../funciones/Menus.php';
require_once '../funciones/OrderManager.php';
require_once '../funciones/UserManager.php';
require_once '../funciones/SupplierManager.php';
require_once '../funciones/PurchaseOrderManager.php';
require_once '../funciones/PurchaseReceiptManager.php';


// Instancias de los controladores
$cartManager = new CartManager($db);  // Manejo del carrito de compras
$menus = new Menus($db);  // Manejo de menús
$orderManager = new OrderManager($db);  // Manejo de órdenes
$supplierManager = new SupplierManager($db);  // Manejo de Proveedores
$purchaseOrderManager = new PurchaseOrderManager($db);  // Manejo de Órdenes de Compra
$purchaseReceiptManager = new PurchaseReceiptManager($db, $productManager);  // Manejo de Recepciones de Mercancía
$userManager = new UserManager($db);  // Manejo de usuarios

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
