# HR Module (Human Resources)

**Namespace:** `Minimarcket\Modules\HR`

Handles employee management and payroll processing.

## Core Components

### 1. PayrollService
**Class:** `Minimarcket\Modules\HR\Services\PayrollService`

*   `getPayrollStatus($roleFilter)`: Core reporting dashboard.
    *   Calculates payment due dates based on `salary_frequency` (weekly/biweekly/monthly).
    *   Checks for Pending Debts (integrates with `CreditService`).
    *   Determines if payment is Overdue, Due, or Paid.
    
## Integration
*   **Finance Integration**: It uses `CreditService` to fetch `getPendingEmployeeDebts($userId)` and deduct them from the net salary calculation.

## Database Tables
*   `users` (Contains salary info fields like `salary_amount`, `salary_frequency`, `job_role`).
*   `payroll_payments`

## Legacy Compatibility
*   **Proxy:** `PayrollManager`.
