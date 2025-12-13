<?php

namespace Minimarcket\Application;

use Minimarcket\Core\Container;
use Minimarcket\Core\Config\ConfigLoader;
use Minimarcket\Core\Config\GlobalConfig;
use Minimarcket\Core\Database\ConnectionManager;
use Minimarcket\Core\Database\QueryBuilder;

/**
 * Class Application
 * 
 * Punto de entrada principal de la aplicación.
 * Inicializa el contenedor, carga configuración, y registra servicios core.
 */
class Application
{
    protected Container $container;
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->container = new Container();
        Container::setInstance($this->container);

        $this->loadEnvironment();
        $this->loadConfiguration();
        $this->registerCoreServices();
    }

    /**
     * Carga las variables de entorno desde .env
     */
    protected function loadEnvironment(): void
    {
        $envPath = $this->basePath . '/.env';
        ConfigLoader::loadEnv($envPath);
    }

    /**
     * Carga la configuración desde archivos PHP o desde ENV
     */
    protected function loadConfiguration(): void
    {
        // Configuración de base de datos desde ENV
        GlobalConfig::load([
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'database' => $_ENV['DB_NAME'] ?? 'minimarket',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'charset' => 'utf8mb4'
            ],
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'Minimarcket',
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => $_ENV['APP_DEBUG'] ?? false
            ]
        ]);
    }

    /**
     * Registra los servicios core en el contenedor
     */
    protected function registerCoreServices(): void
    {
        // Registrar ConnectionManager como singleton con config
        $this->container->set(ConnectionManager::class, function ($container) {
            return new ConnectionManager(GlobalConfig::get('db'));
        });

        // Registrar QueryBuilder (se instanciará con ConnectionManager inyectado)
        $this->container->set(QueryBuilder::class, function ($container) {
            return new QueryBuilder($container->get(ConnectionManager::class));
        });

        // Registrar ConfigService (Core)
        $this->container->set(\Minimarcket\Core\Config\ConfigService::class, function ($container) {
            return new \Minimarcket\Core\Config\ConfigService(
                $container->get(ConnectionManager::class)->getConnection()
            );
        });

        // Registrar MenuService (View)
        $this->container->set(\Minimarcket\Core\View\MenuService::class, function ($container) {
            return new \Minimarcket\Core\View\MenuService(
                $container->get(ConnectionManager::class)->getConnection()
            );
        });

        // Registrar TenantService
        $this->container->set(\Minimarcket\Core\Tenant\TenantService::class, function ($container) {
            return new \Minimarcket\Core\Tenant\TenantService(
                $container->get(ConnectionManager::class)
            );
        });

        // Registrar módulos
        $this->registerModuleServices();
    }

    /**
     * Registra los servicios de módulos de negocio
     */
    protected function registerModuleServices(): void
    {
        // User Module
        $this->container->set(\Minimarcket\Modules\User\Repositories\UserRepository::class, function ($container) {
            return new \Minimarcket\Modules\User\Repositories\UserRepository(
                $container->get(ConnectionManager::class)
            );
        });



        // Inventory Module
        $this->container->set(\Minimarcket\Modules\Inventory\Repositories\ProductRepository::class, function ($container) {
            return new \Minimarcket\Modules\Inventory\Repositories\ProductRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Inventory\Repositories\RawMaterialRepository::class, function ($container) {
            return new \Minimarcket\Modules\Inventory\Repositories\RawMaterialRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Inventory\Services\ProductService::class, function ($container) {
            return new \Minimarcket\Modules\Inventory\Services\ProductService(
                $container->get(\Minimarcket\Modules\Inventory\Repositories\ProductRepository::class),
                $container->get(\Minimarcket\Modules\Inventory\Repositories\RawMaterialRepository::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Inventory\Services\RawMaterialService::class, function ($container) {
            return new \Minimarcket\Modules\Inventory\Services\RawMaterialService(
                $container->get(\Minimarcket\Modules\Inventory\Repositories\RawMaterialRepository::class)
            );
        });

        // Sales Module
        $this->container->set(\Minimarcket\Modules\Sales\Repositories\OrderRepository::class, function ($container) {
            return new \Minimarcket\Modules\Sales\Repositories\OrderRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Sales\Repositories\CartRepository::class, function ($container) {
            return new \Minimarcket\Modules\Sales\Repositories\CartRepository(
                $container->get(ConnectionManager::class)
            );
        });

        // Core Session Services
        $this->container->set(\Minimarcket\Core\Session\SessionManager::class, function ($container) {
            return new \Minimarcket\Core\Session\SessionManager();
        });

        // User Module
        $this->container->set(\Minimarcket\Modules\User\Repositories\UserRepository::class, function ($container) {
            return new \Minimarcket\Modules\User\Repositories\UserRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\User\Services\UserService::class, function ($container) {
            return new \Minimarcket\Modules\User\Services\UserService(
                $container->get(\Minimarcket\Modules\User\Repositories\UserRepository::class),
                $container->get(\Minimarcket\Core\Session\SessionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Sales\Repositories\CreditRepository::class, function ($container) {
            return new \Minimarcket\Modules\Sales\Repositories\CreditRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Sales\Services\OrderService::class, function ($container) {
            return new \Minimarcket\Modules\Sales\Services\OrderService(
                $container->get(\Minimarcket\Modules\Sales\Repositories\OrderRepository::class),
                $container->get(\Minimarcket\Modules\Inventory\Services\ProductService::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Sales\Services\CartService::class, function ($container) {
            return new \Minimarcket\Modules\Sales\Services\CartService(
                $container->get(\Minimarcket\Modules\Sales\Repositories\CartRepository::class)
            );
        });


        // Finance Module
        $this->container->set(\Minimarcket\Modules\Finance\Repositories\TransactionRepository::class, function ($container) {
            return new \Minimarcket\Modules\Finance\Repositories\TransactionRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Finance\Repositories\VaultRepository::class, function ($container) {
            return new \Minimarcket\Modules\Finance\Repositories\VaultRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Finance\Repositories\CashRegisterRepository::class, function ($container) {
            return new \Minimarcket\Modules\Finance\Repositories\CashRegisterRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Finance\Services\TransactionService::class, function ($container) {
            return new \Minimarcket\Modules\Finance\Services\TransactionService(
                $container->get(\Minimarcket\Modules\Finance\Repositories\TransactionRepository::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Finance\Services\VaultService::class, function ($container) {
            return new \Minimarcket\Modules\Finance\Services\VaultService(
                $container->get(\Minimarcket\Modules\Finance\Repositories\VaultRepository::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Finance\Services\CashRegisterService::class, function ($container) {
            return new \Minimarcket\Modules\Finance\Services\CashRegisterService(
                $container->get(\Minimarcket\Modules\Finance\Repositories\CashRegisterRepository::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Finance\Services\CreditService::class, function ($container) {
            // CreditService uses direct DB connection for now as per its constructor check
            return new \Minimarcket\Modules\Finance\Services\CreditService(
                $container->get(ConnectionManager::class)->getConnection()
            );
        });

        $this->container->set(\Minimarcket\Modules\Finance\Services\ExchangeRateService::class, function ($container) {
            return new \Minimarcket\Modules\Finance\Services\ExchangeRateService(
                $container->get(ConnectionManager::class)->getConnection()
            );
        });

        // SupplyChain Module
        $this->container->set(\Minimarcket\Modules\SupplyChain\Repositories\SupplierRepository::class, function ($container) {
            return new \Minimarcket\Modules\SupplyChain\Repositories\SupplierRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\SupplyChain\Repositories\PurchaseOrderRepository::class, function ($container) {
            return new \Minimarcket\Modules\SupplyChain\Repositories\PurchaseOrderRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\SupplyChain\Repositories\PurchaseReceiptRepository::class, function ($container) {
            return new \Minimarcket\Modules\SupplyChain\Repositories\PurchaseReceiptRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\SupplyChain\Services\SupplierService::class, function ($container) {
            return new \Minimarcket\Modules\SupplyChain\Services\SupplierService(
                $container->get(\Minimarcket\Modules\SupplyChain\Repositories\SupplierRepository::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService::class, function ($container) {
            return new \Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService(
                $container->get(\Minimarcket\Modules\SupplyChain\Repositories\PurchaseOrderRepository::class),
                $container->get(\Minimarcket\Modules\Inventory\Services\RawMaterialService::class),
                $container->get(\Minimarcket\Modules\Inventory\Services\ProductService::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\SupplyChain\Services\PurchaseReceiptService::class, function ($container) {
            return new \Minimarcket\Modules\SupplyChain\Services\PurchaseReceiptService(
                $container->get(\Minimarcket\Modules\SupplyChain\Repositories\PurchaseReceiptRepository::class),
                $container->get(\Minimarcket\Modules\SupplyChain\Repositories\PurchaseOrderRepository::class),
                $container->get(\Minimarcket\Modules\Inventory\Services\ProductService::class),
                $container->get(\Minimarcket\Core\Config\ConfigService::class),
                $container->get(\Minimarcket\Modules\Inventory\Services\RawMaterialService::class)
            );
        });

        // Manufacturing Module
        $this->container->set(\Minimarcket\Modules\Manufacturing\Repositories\ProductionRepository::class, function ($container) {
            return new \Minimarcket\Modules\Manufacturing\Repositories\ProductionRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\Manufacturing\Services\ProductionService::class, function ($container) {
            return new \Minimarcket\Modules\Manufacturing\Services\ProductionService(
                $container->get(\Minimarcket\Modules\Manufacturing\Repositories\ProductionRepository::class),
                $container->get(\Minimarcket\Modules\Inventory\Services\RawMaterialService::class)
            );
        });

        // HR Module
        $this->container->set(\Minimarcket\Modules\HR\Repositories\EmployeeRepository::class, function ($container) {
            return new \Minimarcket\Modules\HR\Repositories\EmployeeRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\HR\Repositories\PayrollRepository::class, function ($container) {
            return new \Minimarcket\Modules\HR\Repositories\PayrollRepository(
                $container->get(ConnectionManager::class)
            );
        });

        $this->container->set(\Minimarcket\Modules\HR\Services\PayrollService::class, function ($container) {
            return new \Minimarcket\Modules\HR\Services\PayrollService(
                $container->get(\Minimarcket\Modules\HR\Repositories\PayrollRepository::class),
                $container->get(\Minimarcket\Modules\HR\Repositories\EmployeeRepository::class),
                $container->get(\Minimarcket\Modules\Finance\Services\CreditService::class),
                $container->get(\Minimarcket\Modules\Finance\Services\VaultService::class)
            );
        });

        // Core Helper Services
        $this->container->set(\Minimarcket\Core\Helpers\PrinterHelper::class, function ($container) {
            return new \Minimarcket\Core\Helpers\PrinterHelper();
        });

        $this->container->set(\Minimarcket\Core\Services\EmailService::class, function ($container) {
            return new \Minimarcket\Core\Services\EmailService();
        });

        $this->container->set(\Minimarcket\Core\Security\CsrfToken::class, function ($container) {
            return new \Minimarcket\Core\Security\CsrfToken();
        });
    }

    /**
     * Obtiene el contenedor de dependencias
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Obtiene la ruta base de la aplicación
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
