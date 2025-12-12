# Minimarcket Architecture Documentation

## Overview
This project has been refactored from a legacy PHP application into a **Modular Monolith** architecture. It uses **Domain-Driven Design (DDD)** principles, **Dependency Injection (DI)**, and a **Proxy Pattern** for backward compatibility.

## Directory Structure

```
/Minimarcket
  /src              # New Source Code (PSR-4 Namespace: Minimarcket\)
    /Core           # Framework logic (Database, Container, Config, Security)
    /Modules        # Business Domains
      /Inventory
      /Sales
      /Finance
      /User
      /SupplyChain
      /HR
      /Manufacturing
  /funciones        # Legacy Compatibility Layer (Proxies)
  /templates        # Shared Views & Autoload
  /admin            # Admin Controllers/Views
  /paginas          # Frontend Controllers/Views
```

## Key Components

### 1. The Container (`src/Core/Container.php`)
A simple Dependency Injection Container. It acts as a Service Locator.
- **Usage**:
  ```php
  use Minimarcket\Core\Container;
  use Minimarcket\Modules\Inventory\Services\ProductService;

  $container = Container::getInstance();
  $productService = $container->get(ProductService::class);
  ```

### 2. Services (`src/Modules/*/Services/*`)
Business logic resides here. Services should:
- Be stateless.
- Accept dependencies via constructor (Auto-wired by Container).
- Return data (Arrays or DTOs), not HTML.

### 3. Proxies (`funciones/*.php`)
Legacy files that used to contain logic now wrap the new Services.
- **Example**: `ProductManager` delegates to `ProductService`.
- **Status**: marked as `@deprecated`. Do not modify. To change logic, modify the Service.

## Adding a New Module

1. Create directory: `src/Modules/NewDomain`.
2. Create Subdirectories: `Services`, `Models`.
3. Create Service: `src/Modules/NewDomain/Services/MyService.php`.
   - Namespace: `Minimarcket\Modules\NewDomain\Services`.
4. (Optional) Create Legacy Proxy in `funciones/` if existing code expects it.

## Database Access
Use `Minimarcket\Core\Database::getConnection()` which returns a singleton `PDO` instance.
Services receive `$db` in their constructor automatically.

## Security
- **CSRF**: Use `Minimarcket\Core\Security\CsrfToken` (or `Csrf` proxy).
- **Config**: Use `Minimarcket\Core\Config\ConfigService` (or `GlobalConfig` proxy).

## Migration Guide
When writing new Controllers (`admin/*.php`):
**DO NOT** use `global $productManager`.
**DO** use:
```php
require_once __DIR__ . '/../templates/autoload.php';
use Minimarcket\Core\Container;
use Minimarcket\Modules\Inventory\Services\ProductService;

$service = Container::getInstance()->get(ProductService::class);
$products = $service->getAllProducts();
```
