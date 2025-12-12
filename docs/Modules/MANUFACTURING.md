# Manufacturing Module

**Namespace:** `Minimarcket\Modules\Manufacturing`

Handles the production of goods from raw materials (e.g., Pizza from Flour, Cheese, etc.).

## Core Components

### 1. ProductionService
**Class:** `Minimarcket\Modules\Manufacturing\Services\ProductionService`

*   `registerProduction($manufacturedId, $qty, $userId)`: The core logic.
    *   Reads the **Recipe** (`production_recipes`).
    *   Deducts stock from **Raw Materials**.
    *   Calculates **Cost of Goods Sold (COGS)** based on material costs.
    *   updates the Stock and Weighted Average Cost of the **Manufactured Product**.
    *   Logs the production history.

*   `searchManufacturedProducts()`: List available recipes/products.
*   `getRecipe($id)`: Detail of ingredients needed.

## Database Tables
*   `manufactured_products`
*   `production_recipes`
*   `production_orders` (History log)
*   `raw_materials` (Read/Update only)

## Legacy Compatibility
*   **Proxy:** `ProductionManager`.
