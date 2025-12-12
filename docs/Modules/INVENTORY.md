# Inventory Module

**Namespace:** `Minimarcket\Modules\Inventory`

The Inventory module is responsible for managing the catalog of products sold in the store. It handles stock levels, pricing, and product metadata.

## Core Components

### 1. ProductService
**Class:** `Minimarcket\Modules\Inventory\Services\ProductService`

The main entry point for inventory operations.

**Key Methods:**
*   `getAllProducts()`: Retrieves all active products.
*   `getProductById($id)`: Fetches a specific product.
*   `updateStock($id, $quantity, $reason)`: Adjusts inventory levels.
*   `createProduct($data)`: Registers a new product.

### 2. Models
*   `Product`: Data Transfer Object representing a single product row.

## Database Tables
*   `products`: Main storage. Columns: `id`, `name`, `stock`, `price_usd`, `price_ves`, `category_id`.
*   `categories`: Product organization.

## Integration
*   used by **Sales Module**: To check stock availability and get prices for the Cart.
*   used by **Supply Chain**: To increase stock when `PurchaseReceipts` are processed.
*   used by **Manufacturing**: To define output products.

## Legacy Compatibility
*   **Proxy:** `ProductManager` (in `funciones/ProductManager.php`) delegates all calls to `ProductService`.
