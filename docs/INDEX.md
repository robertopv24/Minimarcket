# Minimarcket Developer Wiki

Welcome to the comprehensive documentation for the Minimarcket system. This wiki covers architecture, business logic modules, and development guides.

## 📘 End-User Documentation
*   [Manual de Usuario (Español)](manual_usuario.html) - Guía completa para cajeros y administradores.

## 🏛️ Architecture & Core
*   [System Architecture](ARCHITECTURE.md) - High-level overview of the Modular Monolith, Container, and Proxy patterns.
*   [Architectural Decisions](DECISIONS.md) - Record of key technical decisions (ADRs).
*   [Dependency Analysis](dependency_analysis.md) - Analysis of legacy dependencies and refactoring strategy.

## 📦 Business Modules
Detailed documentation for each functional domain:
*   [Inventory Module](Modules/INVENTORY.md) - Products, stock management, categories.
*   [Sales Module](Modules/SALES.md) - Cart, Orders, Checkout flow.
*   [Finance Module](Modules/FINANCE.md) - Vault, Cash Registers, Transactions, Credits, Exchange Rates.
*   [User Module](Modules/USER.md) - Authentication, Roles, Profile management.
*   [Supply Chain Module](Modules/SUPPLY_CHAIN.md) - Suppliers, Raw Materials, Purchase Orders & Receipts.
*   [HR Module](Modules/HR.md) - Payroll, Employees.
*   [Manufacturing Module](Modules/MANUFACTURING.md) - Production recipes, manufactured products.

## 🛠️ Developer Guides
*   [Development Guide](Guides/DEVELOPMENT.md) - Coding standards, how to create a new Service, how to use the Container.
*   [Getting Started](Guides/GETTING_STARTED.md) - Installation and environment setup.
*   [Testing](Guides/TESTING.md) - How to verify modules.

## 🔗 Quick Links
*   [Service Contracts](service_contracts.php) (Reference Interface Definitions)
*   [Phase 1 Plan](plan_fase_1.md) (Historical)
