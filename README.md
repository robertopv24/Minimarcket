# Sistema de Gestión para Minimarket

> Plataforma web integral para la administración de ventas, inventario y compras, optimizada para entornos con doble moneda (USD/VES).

## 📋 Descripción General

Este sistema es una solución completa desarrollada en PHP nativo que permite gestionar el flujo operativo de un minimarket. Su arquitectura está diseñada para mantener la integridad de los datos financieros frente a la volatilidad cambiaria, permitiendo actualizaciones masivas de precios y registros históricos de costos.

## 🚀 Características Principales

### 💰 Gestión Financiera y Precios
* **Sistema Bimonetario:** Manejo simultáneo de precios en Dólares (USD) y Bolívares (VES).
* **Actualización Atómica de Precios:** Algoritmo optimizado (SQL-based) que recalcula miles de productos en milisegundos al cambiar la tasa de cambio global, evitando inconsistencias.
* **Historial de Tasas en Compras:** Las órdenes de compra guardan la tasa de cambio del momento de la transacción para garantizar reportes contables exactos (Ganancias y Pérdidas).

### 🛒 Punto de Venta y Tienda
* **Carrito de Compras:** Flujo completo de selección, validación de stock en tiempo real y checkout.
* **Catálogo Público:** Vista de clientes (`tienda.php`) con filtrado y visualización de disponibilidad.
* **Gestión de Pedidos:** Estados de orden (Pendiente, Pagado, Entregado, Cancelado).

### 📦 Inventario y Proveedores
* **Control de Stock:** Descuento automático tras ventas confirmadas.
* **Gestión de Proveedores:** Base de datos de proveedores y contactos.
* **Ciclo de Compras:**
    1.  Creación de Orden de Compra (Pending).
    2.  Recepción de Mercancía (Received).
    3.  Actualización automática de stock y costos promedio.

### 👤 Gestión de Usuarios
* **Roles y Permisos:** Diferenciación entre Administradores y Usuarios/Clientes.
* **Seguridad:** Encriptación de contraseñas (Bcrypt) y validación de sesiones.



## 🛠️ Stack Tecnológico

* **Backend:** PHP 8.1+ (Sin frameworks, arquitectura MVC personalizada con Managers).
* **Base de Datos:** MySQL 8.0 (Uso de Transacciones ACID para ventas y compras).
* **Frontend:** HTML5, CSS3, Bootstrap 5.
* **JavaScript:** Lógica de cliente para interacciones dinámicas.



## 📂 Estructura del Proyecto

El sistema ha sido refactorizado para eliminar redundancias y centralizar la lógica de negocio:


/
├── admin/          # Panel de Control (Protegido por rol Admin)
├── funciones/      # Lógica de Negocio (Managers: Product, Cart, User, Order...)
├── paginas/        # Vistas públicas (Login, Registro, Tienda, Perfil)
├── templates/      # Componentes reutilizables (Header, Footer, Autoload)
├── uploads/        # Almacenamiento de imágenes de productos y perfiles
├── db.sql          # Estructura inicial de la Base de Datos
└── index.php       # Enrutador principal / Semáforo de entrada




## ⚙️ Instalación y Configuración

1.  **Base de Datos:**

      * Crear una base de datos en MySQL.
      * Importar el archivo `db.sql` para generar las tablas y datos iniciales.

2.  **Conexión:**

      * El sistema utiliza `templates/autoload.php` para cargar la configuración.
      * Verificar las credenciales de conexión en `funciones/conexion.php` (o `Config.php`).

3.  **Servidor:**

      * Desplegar en un servidor Apache/Nginx con soporte para PHP.
      * Asegurarse de que la carpeta `uploads/` tenga permisos de escritura.



## ⚠️ LICENCIA PROPIETARIA

**Este código es privado y de uso restringido.**
Todos los derechos están reservados. No está permitido su uso, copia, modificación o distribución sin la autorización expresa del propietario. No está licenciado como código abierto.
