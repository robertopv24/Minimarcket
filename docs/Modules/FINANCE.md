# Finance Module

**Namespace:** `Minimarcket\Modules\Finance`

The Finance module is the central accounting hub. It tracks money movements, cash registers, company vault, and customer credits.

## Core Components

### 1. TransactionService
**Class:** `Minimarcket\Modules\Finance\Services\TransactionService`
The ledger for all financial events.

*   `registerTransaction(...)`: Generic entry point for recording income/expense.
*   `processOrderPayments(...)`: Specialized helper for Sales.
*   `getPaymentMethods()`: Retrieves active payment types (Cash, Zelle, etc.).

### 2. VaultService
**Class:** `Minimarcket\Modules\Finance\Services\VaultService`
Manages the "Caja Chica" / Company Safe.

*   `getBalance()`: Current total in Vault.
*   `registerMovement(...)`: Deposits or withdrawals from the Vault.

### 3. CreditService
**Class:** `Minimarcket\Modules\Finance\Services\CreditService`
Manages Accounts Receivable (Clients) and Employee Debts.

*   `getOrCreateClient(...)`: Client management.
*   `addDebt(...)`: Adds a new pending payment for a client.
*   `payDebt(...)`: Registers a payment against a debt.

### 4. CashRegisterService
**Class:** `Minimarcket\Modules\Finance\Services\CashRegisterService`
Manages daily cashier sessions (`caja_sesiones`).

*   `openSession($userId)`
*   `closeSession($sessionId)`
*   `getSessionDetails($sessionId)`

### 5. ExchangeRateService
**Class:** `Minimarcket\Modules\Finance\Services\ExchangeRateService`
Manages currency conversion (USD/VES).

*   `getLatestRate()`: Returns current multiplier.

## Database Tables
*   `transactions`
*   `vault_movements`
*   `company_vault`
*   `cash_register_sessions`
*   `clients`
*   `accounts_receivable`
*   `exchange_rate`

## Integration
*   **Used by Sales**: To record payments.
*   **Used by SupplyChain**: To record expenses for Purchase Orders.
*   **Used by HR**: To record salary payments and deduct employee debts.

## Legacy Compatibility
*   **Proxies:** `TransactionManager`, `VaultManager`, `CreditManager`, `CashRegisterManager`, `ExchangeRate`.
