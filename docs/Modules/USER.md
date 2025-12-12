# User Module

**Namespace:** `Minimarcket\Modules\User`

Manages system users, authentication, and access control.

## Core Components

### 1. UserService
**Class:** `Minimarcket\Modules\User\Services\UserService`

*   `authenticate($email, $password)`: Verifies credentials (password_verify).
*   `getUserById($id)`: Fetches profile.
*   `checkPermission($userId, $permission)`: (If implemented) RBAC checks.
*   `createUser(...)`, `updateUser(...)`, `deleteUser(...)`.

## Database Tables
*   `users`
*   `user_sessions` (Optional, if DB tracked)

## Legacy Compatibility
*   **Proxy:** `UserManager`.
