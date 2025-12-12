# Análisis de Dependencias - Proyecto Minimarcket

## 1. Estructura de Importaciones

El patrón dominante es la importación directa de archivos relativos:
```php
require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';
require_once '../templates/footer.php';
```

### Autoload.php
Este archivo actúa como el "Service Container" implícito actual, instanciando todas las clases globales:
- `$db` (Database)
- `$config` (Config)
- `$dbConfig` (ExchangeRate)
- `$userManager`
- `$productManager`
- `$orderManager`
- `$cartManager`
- `$supplierManager`
- `$purchaseOrderManager`
- `$cashRegisterManager`
- `$transactionManager`
- `$vaultManager`
- `$rawMaterialManager`
- `$productionManager`
- `$payrollManager`
- `$creditManager`
- `$printerHelper`
- `$purchaseReceiptManager`

## 2. Acoplamiento Funcional (Servicios Sugeridos)

Analizando los nombres de archivos y el uso de Managers, proponemos la siguiente agrupación en módulos:

### A. Inventory Module
**Responsabilidad:** Gestión de productos, insumos y recetas.
**Dependencias Actuales:**
- `ProductManager.php`
- `RawMaterialManager.php`
- `ProductionManager.php`
- `InsumosManager` (implicito en admin/insumos.php)
**Vistas Afectadas:**
- `admin/productos.php`, `add_product.php`, `edit_product.php`, `delete_product.php`
- `admin/insumos.php`
- `admin/manufactura.php`
- `admin/configurar_receta.php`

### B. Sales Module
**Responsabilidad:** Proceso de venta, carrito y checkout.
**Dependencias Actuales:**
- `OrderManager.php`
- `CartManager.php`
- `CreditManager.php` (para pagos a crédito)
**Vistas Afectadas:**
- `paginas/tienda.php`
- `paginas/carrito.php`
- `paginas/checkout.php`
- `paginas/process_checkout.php` (Critical Logic)
- `admin/ventas.php`, `ver_venta.php`, `editar_venta.php`

### C. Finance Module
**Responsabilidad:** Flujo de caja, transacciones y reportes financieros.
**Dependencias Actuales:**
- `CashRegisterManager.php`
- `TransactionManager.php`
- `VaultManager.php`
- `ExchangeRate.php`
**Vistas Afectadas:**
- `admin/caja_chica.php`
- `admin/cobranzas.php`
- `admin/reportes.php`
- `admin/reportes_caja.php`
- `admin/ver_cierre.php`

### D. Supply Chain Module
**Responsabilidad:** Proveedores y compras.
**Dependencias Actuales:**
- `SupplierManager.php`
- `PurchaseOrderManager.php`
- `PurchaseReceiptManager.php`
**Vistas Afectadas:**
- `admin/proveedores.php`, `add_supplier.php`
- `admin/compras.php`, `add_purchase_order.php`
- `admin/process_purchase_order.php`

### E. User & Admin Module
**Responsabilidad:** Autenticación y gestión de usuarios.
**Dependencias Actuales:**
- `UserManager.php`
- `PayrollManager.php`
**Vistas Afectadas:**
- `admin/usuarios.php`
- `admin/nomina.php`
- `paginas/login.php`, `register.php`, `perfil.php`

## 3. Riesgos de Migración

1.  **Global state en `autoload.php`**: Moverse a un container requerirá refactorizar todos los scripts que asumen la existencia de variables globales como `$productManager`.
2.  **HTML mezclado con PHP**: `admin/*.php` a menudo imprime HTML directamente mientras procesa lógica. Esto dificultará extraer solo la lógica a Controladores.
3.  **Rutas relativas**: Los `require_once '../'` se romperán si movemos archivos.

## 4. Plan de Mitigación (Phase 2 Preparation)

- Crear `src/Container.php` (DI rudimentario) para reemplazar las instanciaciones manuales de `autoload.php`.
- Mantener `autoload.php` como un "Bridge" que use el Container para exportar variables globales, manteniendo la compatibilidad con el código legacy mientras se migra servicio por servicio.
