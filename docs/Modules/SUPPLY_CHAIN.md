# Supply Chain Module

**Namespace:** `Minimarcket\Modules\SupplyChain`

Manages external vendors, raw materials ingestion, and procurement processes.

## Core Components

### 1. SupplierService
**Class:** `Minimarcket\Modules\SupplyChain\Services\SupplierService`
CRUD for Vendor management.

### 2. RawMaterialService
**Class:** `Minimarcket\Modules\SupplyChain\Services\RawMaterialService`
Manages the catalog of raw ingredients (e.g., Flour, Tomato Sauce) used in Manufacturing.

*   `getAllMaterials()`
*   `updateStock(...)`

### 3. PurchaseOrderService
**Class:** `Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService`
Manages the lifecycle of buying goods from suppliers.

*   `createorder(...)`
*   `approveOrder(...)`
*   `cancelOrder(...)`

### 4. PurchaseReceiptService
**Class:** `Minimarcket\Modules\SupplyChain\Services\PurchaseReceiptService`
Handles the physical receiving of goods.

*   `createPurchaseReceipt($orderId)`:
    *   Verifies Order status.
    *   Updates Stock (via `ProductService` or directly).
    *   Updates Pricing based on new costs and profit margins.
    *   Closes the Purchase Order.

## Database Tables
*   `suppliers`
*   `raw_materials`
*   `purchase_orders`
*   `purchase_order_items`
*   `purchase_receipts`

## Legacy Compatibility
*   **Proxies:** `SupplierManager`, `RawMaterialManager`, `PurchaseOrderManager`, `PurchaseReceiptManager`.
