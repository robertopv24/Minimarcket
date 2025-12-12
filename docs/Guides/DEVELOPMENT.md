# Development Guide

## Modern Architecture

Minimarcket uses a modular architecture with Dependency Injection.

### 1. The Container
Do not instantiate classes with `new Class()`. Use the container:

```php
use Minimarcket\Core\Container;
use Minimarcket\Modules\Sales\Services\OrderService;

$container = Container::getInstance();
$orderService = $container->get(OrderService::class);
```

### 2. Creating a New Service
1.  Define the class in `src/Modules/[Domain]/Services`.
2.  Namespace it: `namespace Minimarcket\Modules\[Domain]\Services;`.
3.  Inject dependencies via Constructor (Type Hint classes).
4.  The Container will automatically resolve these dependencies (Auto-wiring).

### 3. Database Access
In your service:

```php
use Minimarcket\Core\Database;
use PDO;

class MyService {
    private $db;
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }
}
```

### 4. Helpers
Use static helpers for utility functions:
*   `Minimarcket\Core\Helpers\UploadHelper`: For file uploads.
*   `Minimarcket\Core\Helpers\PrinterHelper`: For thermal printing.

### 5. Frontend Integration
Frontend files (`.php` files in root or `admin/`) should require `templates/autoload.php` at the very top. This initializes the autoloading and container environment.

## Legacy Code & Proxies
*   **Do not modify** files in `funciones/`. These are generated Proxies for backward compatibility.
*   If you need to change logic in `ProductManager`, change `src/Modules/Inventory/Services/ProductService.php` instead.
