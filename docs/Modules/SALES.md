# Sales Module

**Namespace:** `Minimarcket\Modules\Sales`

The Sales module manages the point-of-sale operations, including the shopping cart and order processing.

## Core Components

### 1. CartService
**Class:** `Minimarcket\Modules\Sales\Services\CartService`
Manages the temporary state of a customer's purchase before finalization.

*   `getCart($userId)`: Retrieves current cart items.
*   `addToCart($userId, $productId, $qty)`: Adds items.
*   `removeFromCart($cartId)`: Removes items.
*   `clearCart($userId)`: Empties the cart.

### 2. OrderService
**Class:** `Minimarcket\Modules\Sales\Services\OrderService`
Handles the conversion of a Cart into a permanent Order.

*   `createOrder($userId, $cartItems)`: Creates a new order record.
*   `finalizeOrder($orderId, $paymentData)`: Processes payment and deducts stock.
*   `getOrderDetails($orderId)`: Retrieves full order info.

## Database Tables
*   `orders`: Header information (date, total, user).
*   `order_items`: Line items linked to products.
*   `cart`: Temporary handling for active sessions.

## Integration
*   **Depends on Inventory**: To check stock and get prices.
*   **Depends on Finance**: To register income transactions via `TransactionService`.

## Legacy Compatibility
*   **Proxy:** `CartManager` -> `CartService`
*   **Proxy:** `OrderManager` -> `OrderService`
