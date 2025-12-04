# Minimarket POS - Sistema de Gestión Integral

## ⚠️ LICENCIA PROPIETARIA - TODOS LOS DERECHOS RESERVADOS

**Copyright © 2025 [Roberto J. Perozo V.]**

Este software y su código fuente son propiedad intelectual exclusiva del autor. 
Se prohíbe expresamente sin autorización por escrito:

- La distribución total o parcial del código
- La modificación, ingeniería inversa o descompilación
- El uso comercial en múltiples establecimientos
- La copia para cualquier fin diferente al uso autorizado
- La incorporación en otros proyectos o sistemas
- La venta, alquiler o cesión de licencias no autorizadas
- La publicación en repositorios públicos o compartidos

El uso de este software está limitado al establecimiento o negocio para el cual 
fue originalmente desarrollado o autorizado. Cada instalación adicional requiere 
una licencia por separado.

Para obtener una licencia comercial o permiso de uso, contacte directamente al 
propietario mediante los canales oficiales.

**Advertencia:** El incumplimiento de estos términos constituye violación de 
derechos de autor y puede resultar en acciones legales.

---

## 📋 Descripción del Sistema

**Minimarket POS** es un sistema de gestión empresarial completo diseñado 
específicamente para minimercados, pequeñas tiendas, cafeterías y negocios de 
alimentos. La plataforma integra todas las operaciones comerciales en una 
solución unificada que abarca punto de venta, control de inventario, gestión de 
compras, producción, tesorería y análisis financiero avanzado.

Desarrollado con arquitectura modular y enfoque en usabilidad, el sistema 
optimiza las operaciones diarias, reduce errores humanos, proporciona control 
financiero detallado y ofrece visibilidad completa del negocio en tiempo real.

## 🎯 Características Principales

### 1. 🛒 **Punto de Venta (POS) Avanzado**
- **Interfaz Táctil Optimizada:** Diseñada para uso rápido en entorno comercial
- **Carrito Inteligente:** Gestión de productos simples, preparados y combos
- **Sistema de Modificaciones:** Agregar/eliminar ingredientes, notas especiales por ítem
- **Consumo Múltiple:** Opciones para consumo en local (mesa) o para llevar con lógica diferenciada
- **Múltiples Métodos de Pago:** Efectivo (USD/VES), transferencias bancarias, pago móvil, Zelle
- **Cálculo Automático de Vueltos:** En ambas monedas con tasa de cambio actualizada
- **Impresión Automática:** Tickets para cliente y cocina simultáneamente
- **Pantalla Cocina (KDS):** Sistema de display para órdenes en preparación
- **Gestión de Mesas:** Asignación y seguimiento (para restaurantes/cafeterías)

### 2. 📦 **Gestión de Inventario Inteligente**
- **Tipos de Productos Multi-nivel:**
  - **Simples:** Stock físico directo (refrescos, snacks, enlatados)
  - **Preparados:** Descuento automático de ingredientes según receta (pizzas, perros calientes, platos)
  - **Combos/Paquetes:** Agrupación de productos con precio especial y descuento implícito
- **Control de Stock en Tiempo Real:** Actualización inmediata tras ventas, compras o producción
- **Stock Virtual Calculado:** Para productos preparados, basado en disponibilidad de ingredientes
- **Alertas de Stock Bajo:** Configurables por producto con notificaciones visibles
- **Valorización de Inventario:** En USD y Bs con cálculo automático
- **Rotación de Productos:** Seguimiento de movimientos y antigüedad
- **Gestión de Lotes:** Control básico de caducidad (implementable)

### 3. 🏭 **Sistema de Producción y Recetas**
- **Gestión de Materias Primas:** Clasificación en ingredientes directos e indirectos (insumos)
- **Recetas Detalladas:** Componentes por tipo (materia prima, productos de cocina, productos finales)
- **Costeo Automático:** Cálculo de costo unitario promediado por producción
- **Registro de Producción:** Descuento automático de ingredientes, actualización de stock
- **Control de Calidad:** Trazabilidad de producción por lote y responsable
- **Recetas Multi-nivel:** Productos que usan otros productos manufacturados
- **Extras Permitidos:** Configuración de ingredientes adicionales con precio extra
- **Mermas y Rendimientos:** Control de desperdicios y eficiencia productiva

### 4. 💰 **Tesorería y Control Financiero Integral**
- **Caja Diaria por Usuario:** Apertura, operación y cierre individual por cajero
- **Bóveda Central:** Custodia de efectivo principal del negocio
- **Múltiples Métodos de Pago:** Configurables por moneda (USD, VES) y tipo (efectivo, bancario, electrónico)
- **Transacciones Automatizadas:** Registro contable automático de todas las operaciones
- **Movimientos de Fondos:** Depósitos, retiros, transferencias internas con trazabilidad
- **Auditoría Completa:** Historial detallado de todos los movimientos con usuario y timestamp
- **Cuadre de Caja:** Cálculo automático de lo esperado vs. lo contado
- **Diferencia de Cuadre:** Identificación y registro de sobrantes/faltantes
- **Cierre de Turno:** Reporte detallado por método de pago y moneda

### 5. 📊 **Compras y Gestión de Proveedores**
- **Órdenes de Compra:** Creación, seguimiento y recepción con múltiples productos
- **Integración Contable:** Pago automático desde tesorería al registrar compra
- **Gestión de Proveedores:** Datos completos (contacto, dirección, histórico)
- **Recepción de Mercancía:** Actualización automática de stock y recalculo de precios de venta
- **Control de Estado:** Pendiente, recibido, cancelado con fechas estimadas vs. reales
- **Histórico de Compras:** Análisis de gastos por proveedor y período
- **Comparación de Precios:** Seguimiento de costos unitarios por producto
- **Pedidos Automáticos:** Sugerencias basadas en stock mínimo (funcionalidad básica)

### 6. 📈 **Reportes y Business Intelligence**
- **Dashboard Ejecutivo:** KPIs en tiempo real (ventas hoy, semana, mes, año)
- **Reporte de Rentabilidad:** Margen bruto y neto por período con desglose de costos
- **Análisis de Ventas:** Por producto, método de pago, hora del día, cajero
- **Auditoría de Caja:** Diferencias y cuadres históricos por usuario
- **Top Productos:** Más vendidos por volumen e ingresos con tendencias
- **Estado Operativo:** Alertas y métricas críticas (stock bajo, pedidos pendientes)
- **Distribución de Ingresos:** Por método de pago y tipo de producto
- **Reporte de Compras:** Análisis de gastos y comparativa con ventas
- **Utilidad por Producto:** Cálculo de margen individual basado en costos actuales

### 7. 👥 **Gestión de Usuarios y Seguridad**
- **Roles Definidos:** Administrador (acceso total) y Cajero/Usuario (operativo)
- **Control de Acceso Granular:** Por módulos y funciones específicas
- **Autenticación Segura:** Hash SHA256 para contraseñas, sesiones protegidas
- **Registro de Actividades:** Trazabilidad de operaciones por usuario
- **Perfiles de Usuario:** Datos personales, contacto, documento de identidad
- **Reset de Contraseñas:** Sistema seguro con validaciones
- **Bloqueo por Intentos:** Protección contra fuerza bruta
- **Sesiones Concurrentes:** Control para evitar accesos duplicados

### 8. ⚙️ **Configuración y Personalización**
- **Tasa de Cambio Dinámica:** Actualización en tiempo real con recalculo automático de precios
- **Márgenes de Ganancia:** Configurables por producto con override individual
- **Impresión Personalizada:** Formatos de ticket configurables (cabecera, mensajes, logo)
- **Monedas Soportadas:** USD y VES con conversión automática
- **Categorías de Productos:** Organización visual y reportes por categoría
- **Almacenes Múltiples:** Estructura básica para expansión multi-sucursal
- **Backup Automático:** Configuración de respaldos programados
- **Parámetros del Sistema:** Configuración centralizada en base de datos

## 🛠️ Arquitectura Técnica

### **Backend - Núcleo del Sistema**
- **Lenguaje:** PHP 7.4+ (Compatible con PHP 8.x)
- **Paradigma:** Programación Orientada a Objetos (POO) estricta
- **Patrón Arquitectónico:** Modelo-Vista-Controlador (MVC) personalizado
- **Base de Datos:** MySQL 5.7+ / MariaDB 10.3+ (InnoDB engine)
- **Conexión a BD:** PDO (PHP Data Objects) con prepared statements
- **Manejo de Errores:** Logging exhaustivo a archivo y base de datos
- **Transacciones:** Operaciones atómicas para integridad financiera
- **Sesiones:** PHP Native Sessions con regeneración de ID
- **Autoloading:** Carga automática de clases y dependencias

### **Frontend - Interfaz de Usuario**
- **HTML5:** Estructura semántica y accesible
- **CSS3:** Bootstrap 5.2.3 + personalizaciones específicas
- **JavaScript:** Vanilla ES6+ (sin frameworks pesados)
- **AJAX:** Fetch API para operaciones asíncronas
- **Responsive Design:** Adaptable a tablets, laptops y pantallas táctiles
- **Iconografía:** Font Awesome 6.4.0
- **Fuentes:** Sistema default con fallback a sans-serif
- **Temas:** Claro/oscuro básico (implementable)

### **Integraciones y Servicios**
- **Impresión:** CUPS (Common UNIX Printing System) para tickets térmicos
- **Email:** PHPMailer para notificaciones y reportes automáticos
- **APIs RESTful:** Endpoints AJAX para operaciones específicas
- **Sistema de Archivos:** Gestión local con estructura organizada
- **Upload de Imágenes:** Procesamiento y optimización básica
- **Cron Jobs:** Tareas programadas para backups y reportes

### **Base de Datos - Esquema**
- **25+ Tablas Relacionales:** Normalizadas hasta 3NF
- **Claves Foráneas:** Integridad referencial con ON DELETE CASCADE
- **Índices Optimizados:** Para consultas frecuentes y reportes
- **Vistas:** Para consultas complejas y reportes
- **Triggers:** Para auditoría y mantenimiento de datos
- **Backups:** Estructura para respaldos incrementales

## 📋 Requisitos del Sistema

### **Requisitos Mínimos (Entorno de Producción)**
```
Servidor Web:
  - Apache 2.4+ o Nginx 1.18+
  - PHP 7.4.0 o superior
  - Extensiones PHP requeridas:
    • PDO MySQL
    • GD (para procesamiento de imágenes)
    • OpenSSL
    • MBString
    • JSON
    • Session
    • cURL (recomendado)
  
Base de Datos:
  - MySQL 5.7.7+ o MariaDB 10.3+
  - InnoDB como motor de almacenamiento
  - UTF8mb4 para soporte completo de caracteres
  - 100MB mínimo de espacio libre
  
Servidor:
  - 1GB RAM mínimo
  - 2GB espacio en disco
  - Procesador de 2 núcleos
  - Linux/Unix (recomendado) o Windows Server
  
Cliente/Navegador:
  - Chrome 80+, Firefox 75+, Safari 14+, Edge 80+
  - JavaScript habilitado
  - Resolución mínima: 1024x768
  - Conexión a red local estable
```

### **Requisitos Recomendados (Alto Rendimiento)**
```
Servidor Web:
  - Apache 2.4+ con mod_php o PHP-FPM
  - PHP 8.1+ con OPCache habilitado
  - Memcached o Redis para sesiones (opcional)
  
Base de Datos:
  - MySQL 8.0+ o MariaDB 10.6+
  - 4GB RAM dedicada
  - SSD para almacenamiento
  - Backup automático configurado
  
Servidor:
  - 4GB RAM o más
  - 10GB espacio en disco (para logs y backups)
  - Procesador de 4 núcleos
  - Sistema operativo Linux (Ubuntu LTS, CentOS)
  - Firewall configurado (iptables/ufw)
  
Red:
  - Conexión cableada para estaciones POS
  - Wi-Fi para dispositivos móviles (opcional)
  - Switch gestionado para segmentación (opcional)
  
Impresión:
  - Impresora térmica de 80mm compatible con ESC/POS
  - CUPS configurado y probado
  - Papel térmico de calidad
```

### **Requisitos de Desarrollo**
```
- Git para control de versiones
- Editor de código (VS Code, PHPStorm, Sublime)
- XAMPP/WAMP/MAMP o Docker para entorno local
- phpMyAdmin o Adminer para gestión de BD
- Navegadores múltiples para testing
- Herramientas de debugging (Xdebug)
```

## 🚀 Instalación y Configuración

### **Paso 1: Preparación del Servidor**

```bash
# Actualizar sistema (Ubuntu/Debian ejemplo)
sudo apt update && sudo apt upgrade -y

# Instalar Apache, PHP y extensiones
sudo apt install apache2 php libapache2-mod-php php-mysql php-gd php-curl php-mbstring php-xml php-zip -y

# Instalar MySQL
sudo apt install mysql-server -y

# Instalar herramientas adicionales
sudo apt install git curl unzip -y

# Configurar permisos
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

### **Paso 2: Clonar/Subir el Proyecto**

```bash
# Opción A: Clonar desde repositorio privado (requiere acceso)
git clone [URL_PRIVADA_DEL_REPOSITORIO] /var/www/html/minimarket

# Opción B: Subir archivos manualmente via FTP/SFTP
# Subir todos los archivos a /var/www/html/

# Establecer permisos correctos
cd /var/www/html/minimarket
sudo chmod 777 -R uploads/
sudo chmod 644 -R *.php
sudo chmod 644 -R funciones/*.php
sudo chmod 644 -R admin/*.php
sudo chmod 644 -R paginas/*.php
```

### **Paso 3: Configurar Base de Datos**

```sql
-- Conectarse a MySQL
mysql -u root -p

-- Crear base de datos
CREATE DATABASE minimarket_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Crear usuario dedicado
CREATE USER 'minimarket_user'@'localhost' 
IDENTIFIED BY 'ContraseñaSegura123!';

-- Otorgar privilegios
GRANT ALL PRIVILEGES ON minimarket_db.* 
TO 'minimarket_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Importar estructura inicial
USE minimarket_db;
SOURCE /ruta/completa/a/db.sql;

-- Verificar tablas creadas
SHOW TABLES;
```

### **Paso 4: Configurar Variables de Entorno**

Crear archivo `.env` en la raíz del proyecto:

```env
# ============================================
# CONFIGURACIÓN DE BASE DE DATOS
# ============================================
DB_HOST=localhost
DB_PORT=3306
DB_NAME=minimarket_db
DB_USER=minimarket_user
DB_PASSWORD=ContraseñaSegura123!

# ============================================
# CONFIGURACIÓN DEL SITIO
# ============================================
SITE_NAME=Mi Minimarket
SITE_URL=http://localhost/minimarket
DEFAULT_CURRENCY=USD
TIMEZONE=America/Caracas
LANGUAGE=es_VE

# ============================================
# TASA DE CAMBIO Y MONEDAS
# ============================================
EXCHANGE_RATE=36.50
ENABLE_AUTO_RATE_UPDATE=0
RATE_UPDATE_SOURCE=bcv  # bcv, paralelo, fixer

# ============================================
# CONFIGURACIÓN DE IMPRESIÓN
# ============================================
PRINTER_NAME=Gezhi_Thermal
PRINTER_WIDTH=80  # caracteres por línea
PRINT_KITCHEN_TICKET=1
PRINT_CUSTOMER_TICKET=1
PRINT_AUTOMATICALLY=1

# ============================================
# CONFIGURACIÓN DE EMAIL (OPCIONAL)
# ============================================
SMTP_ENABLED=0
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu_email@gmail.com
SMTP_PASSWORD=tu_app_password
SMTP_ENCRYPTION=tls
EMAIL_FROM=noreply@minimarket.com

# ============================================
# SEGURIDAD Y SESIONES
# ============================================
SESSION_TIMEOUT=3600  # segundos
MAX_LOGIN_ATTEMPTS=5
MIN_PASSWORD_LENGTH=8
ENABLE_HTTPS=0  # Cambiar a 1 si usa SSL

# ============================================
# BACKUP Y MANTENIMIENTO
# ============================================
AUTO_BACKUP=1
BACKUP_DAYS_TO_KEEP=30
BACKUP_TIME=02:00

# ============================================
# CONFIGURACIONES AVANZADAS
# ============================================
DEBUG_MODE=0
LOG_LEVEL=error  # debug, info, warning, error
CACHE_ENABLED=1
```

### **Paso 5: Configurar Apache Virtual Host**

```apache
# /etc/apache2/sites-available/minimarket.conf
<VirtualHost *:80>
    ServerName minimarket.local
    ServerAdmin admin@minimarket.com
    DocumentRoot /var/www/html/minimarket
    
    <Directory /var/www/html/minimarket>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Seguridad básica
        <FilesMatch "\.(env|log|sql)$">
            Require all denied
        </FilesMatch>
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/minimarket_error.log
    CustomLog ${APACHE_LOG_DIR}/minimarket_access.log combined
    
    # PHP settings
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 300
    php_value max_input_time 300
</VirtualHost>
```

```bash
# Habilitar el sitio
sudo a2ensite minimarket.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### **Paso 6: Configurar Impresora Térmica**

```bash
# Instalar CUPS si no está instalado
sudo apt install cups -y

# Agregar usuario web al grupo lpadmin
sudo usermod -a -G lpadmin www-data

# Configurar impresora (ejemplo para USB)
sudo lpadmin -p Gezhi_Thermal -E -v usb://GZ/Designer%20Series?serial=123456 -m drv:///sample.drv/generic.ppd

# Probar impresión
echo "Test de impresion" | lp -d Gezhi_Thermal

# Verificar estado
lpstat -p -d
```

### **Paso 7: Configurar Cron Jobs**

```bash
# Editar crontab del usuario web
sudo crontab -u www-data -e

# Agregar las siguientes líneas:
# Backup diario a las 2 AM
0 2 * * * /usr/bin/php /var/www/html/minimarket/backup.php >/dev/null 2>&1

# Limpieza de sesiones antiguas cada hora
0 * * * * find /var/lib/php/sessions -type f -mmin +120 -delete

# Actualización de tasa de cambio a las 8 AM
0 8 * * * /usr/bin/php /var/www/html/minimarket/update_rate.php >/dev/null 2>&1
```

### **Paso 8: Primer Acceso y Configuración Inicial**

1. **Acceder al sistema:** `http://tuserver.com/minimarket/`
2. **Crear primer usuario administrador:**
   - Ir a: `http://tuserver.com/minimarket/paginas/register.php`
   - Completar formulario (el primer usuario se crea como administrador automáticamente)
   - O usar credenciales por defecto si se importó db.sql con datos demo

3. **Configurar métodos de pago:**
   - Acceder como administrador
   - Ir a: Admin → Tesorería → Verificar métodos de pago
   - Asegurarse que existan: Efectivo USD, Efectivo VES, Transferencias

4. **Configurar tasa de cambio inicial:**
   - En Dashboard administrativo, actualizar tasa BCV/Paralelo
   - Esto recalculará automáticamente todos los precios en Bs

5. **Realizar primera apertura de caja:**
   - Iniciar sesión como cajero
   - Ir a: Páginas → Apertura de Caja
   - Registrar fondo inicial en USD y Bs según disponibilidad

## 📁 Estructura Completa del Proyecto

```
Minimarcket/
│
├── 📁 admin/                           # Panel administrativo completo
│   ├── add_product.php                # Formulario agregar productos
│   ├── add_purchase_order.php         # Registrar compra a proveedor
│   ├── add_purchase_receipt.php       # Recepción de mercancía
│   ├── add_supplier.php               # Agregar nuevo proveedor
│   ├── agregar_usuario.php            # Crear nuevo usuario
│   ├── caja_chica.php                 # Tesorería y bóveda central
│   ├── compras.php                    # Listado y gestión de compras
│   ├── configurar_receta.php          # Configurar recetas de productos
│   ├── delete_product.php             # Eliminar producto (con confirmación)
│   ├── delete_purchase_order.php      # Eliminar orden de compra
│   ├── delete_supplier.php            # Eliminar proveedor
│   ├── edit_product.php               # Editar producto existente
│   ├── edit_purchase_order.php        # Modificar orden de compra
│   ├── edit_supplier.php              # Editar datos de proveedor
│   ├── editar_usuario.php             # Modificar usuario
│   ├── editar_venta.php               # Editar estado de venta
│   ├── eliminar_usuario.php           # Eliminar usuario (con confirmación)
│   ├── index.php                      # Dashboard administrativo principal
│   ├── insumos.php                    # Gestión de materias primas
│   ├── manufactura.php                # Producción y recetas cocina
│   ├── process_purchase_order.php     # Procesar órdenes de compra
│   ├── process_purchase_receipt.php   # Procesar recepción
│   ├── process_supplier.php           # Procesar proveedores
│   ├── productos.php                  # Gestión completa de inventario
│   ├── proveedores.php                # Listado de proveedores
│   ├── reportes_caja.php              # Auditoría de cierres de caja
│   ├── reportes.php                   # Reportes financieros y rentabilidad
│   ├── usuarios.php                   # Gestión de usuarios y roles
│   ├── ventas.php                     # Historial y gestión de ventas
│   ├── ver_cierre.php                 # Detalle de cierre de caja
│   └── ver_venta.php                  # Detalle completo de venta
│
├── 📁 ajax/                           # Endpoints para operaciones AJAX
│   ├── get_cart_item.php              # Obtener detalles de ítem del carrito
│   └── imprimir_ticket.php            # Endpoint para impresión de tickets
│
├── 📁 datos/                          # Datos estáticos y archivos de configuración
│   └── productos.json                 # Catálogo de productos demo (opcional)
│
├── 📁 funciones/                      # Núcleo lógico - Clases y Managers
│   ├── CartManager.php                # Gestión completa del carrito de compras
│   ├── CashRegisterManager.php        # Gestión de caja diaria y cuadres
│   ├── Config.php                     # Configuración global del sistema
│   ├── conexion.php                   # Conexión a base de datos (singleton)
│   ├── EmailController.php            # Controlador de envío de correos
│   ├── ExchangeRate.php               # Gestión de tasa de cambio
│   ├── Menus.php                      # Gestión de menús de navegación
│   ├── OrderManager.php               # Gestión completa de órdenes/ventas
│   ├── PrinterHelper.php              # Servicio de impresión de tickets
│   ├── ProductionManager.php          # Gestión de producción y recetas
│   ├── ProductManager.php             # Gestión centralizada de productos
│   ├── PurchaseOrderManager.php       # Gestión de órdenes de compra
│   ├── PurchaseReceiptManager.php     # Gestión de recepción de compras
│   ├── RawMaterialManager.php         # Gestión de materias primas
│   ├── SupplierManager.php            # Gestión de proveedores
│   ├── TransactionManager.php         # Gestión de transacciones financieras
│   ├── UserManager.php                # Gestión de usuarios y autenticación
│   └── VaultManager.php               # Gestión de bóveda/tesorería central
│
├── 📁 paginas/                        # Frontend - Páginas operativas
│   ├── apertura_caja.php              # Apertura de caja diaria
│   ├── carrito.php                    # Carrito de compras y modificaciones
│   ├── checkout.php                   # Proceso final de pago
│   ├── cierre_caja.php                # Cierre de caja y cuadre
│   ├── contacto.php                   # Página de contacto
│   ├── despacho.php                   # Gestión de entregas (opcional)
│   ├── index.php                      # Página principal pública
│   ├── kds_tv.php                     # Pantalla de cocina (Kitchen Display)
│   ├── login.php                      # Inicio de sesión
│   ├── logout.php                     # Cierre de sesión
│   ├── nosotros.php                   # Página "Nosotros"
│   ├── password_reset.php             # Recuperación de contraseña
│   ├── perfil.php                     # Perfil de usuario
│   ├── privacidad.php                 # Política de privacidad
│   ├── process_checkout.php           # Procesamiento del pago
│   ├── register.php                   # Registro de nuevos usuarios
│   ├── soporte.php                    # Página de soporte
│   ├── status.php                     # Estado del sistema
│   ├── terminos.php                   # Términos y condiciones
│   ├── ticket.php                     # Visualización de ticket
│   └── tienda.php                     # Punto de venta principal (POS)
│
├── 📁 templates/                      # Plantillas reutilizables
│   ├── autoload.php                   # Carga automática de dependencias
│   ├── footer.php                     # Pie de página común
│   ├── header.php                     # Cabecera común con menús
│   └── menu.php                       # Sistema de menús de navegación
│
├── 📁 tv/                             # Pantallas secundarias y displays
│   └── index.html                     # Pantalla de información/publicidad
│
├── 📁 uploads/                        # Archivos subidos por usuarios
│   └── product_images/                # Imágenes de productos
│       └── default.jpg                # Imagen por defecto
│
├── .env.example                       # Ejemplo de variables de entorno
├── .htaccess                          # Reglas de Apache (URLs amigables, seguridad)
├── backup.php                         # Script de backup automático
├── composer.json                      # Dependencias PHP (si aplica)
├── db.sql                             # Esquema completo de base de datos
├── index.php                          # Punto de entrada principal
├── LICENSE                            # Licencia propietaria
├── README.md                          # Este archivo
└── update_rate.php                    # Script de actualización de tasa
```

## 🔐 Configuración de Seguridad

### **1. Configuración de Apache (.htaccess)**
```apache
# Protección de archivos sensibles
<FilesMatch "\.(env|sql|log|json)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevenir listado de directorios
Options -Indexes

# Proteger directorios específicos
<Directory "/funciones">
    Order deny,allow
    Deny from all
    Allow from 127.0.0.1
</Directory>

# Redirección HTTPS (si está configurado)
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

# Prevenir acceso directo a includes
RewriteCond %{REQUEST_URI} \.php$
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^.*$ - [F,L,NC]
```

### **2. Configuración de PHP (php.ini)**
```ini
; Seguridad básica
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Límites de ejecución
max_execution_time = 300
max_input_time = 300
memory_limit = 256M

; Subida de archivos
upload_max_filesize = 10M
post_max_size = 12M

; Sesiones
session.cookie_httponly = 1
session.cookie_secure = 1  ; Habilitar si usa HTTPS
session.use_strict_mode = 1
session.gc_maxlifetime = 3600
```

### **3. Configuración de MySQL**
```sql
-- Políticas de contraseñas
SET GLOBAL validate_password.policy = MEDIUM;
SET GLOBAL validate_password.length = 12;
SET GLOBAL validate_password.number_count = 2;
SET GLOBAL validate_password.special_char_count = 1;

-- Remover usuarios anónimos
DELETE FROM mysql.user WHERE User='';

-- Remover acceso root remoto
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Aplicar cambios
FLUSH PRIVILEGES;
```

### **4. Configuración de Firewall (UFW - Ubuntu)**
```bash
# Habilitar firewall
sudo ufw enable

# Reglas básicas
sudo ufw allow 22/tcp        # SSH
sudo ufw allow 80/tcp        # HTTP
sudo ufw allow 443/tcp       # HTTPS
sudo ufw allow 3306/tcp      # MySQL (solo si acceso remoto necesario)

# Denegar todo lo demás
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Ver estado
sudo ufw status verbose
```

## 📖 Guía de Uso Básico

### **🟢 Para Cajeros/Nuevos Usuarios**

#### **Inicio del Día:**
1. **Acceder al sistema:** `http://tudominio.com/paginas/login.php`
2. **Abrir caja:** `Páginas → Apertura de Caja`
   - Ingresar fondo inicial en USD y Bs (según lo disponible)
   - Confirmar apertura
3. **Acceder al POS:** `Páginas → Tienda`

#### **Proceso de Venta:**
1. **Seleccionar productos:**
   - Buscar por nombre o categoría
   - Click en producto para agregar al carrito
   - Para productos preparados, aparecerán opciones de modificación

2. **Modificar productos (si aplica):**
   - Click en "Modificar" junto al producto en carrito
   - Seleccionar consumo: MESA o LLEVAR
   - Remover ingredientes no deseados (SIN ...)
   - Agregar extras disponibles (+ ...)
   - Añadir nota especial si es necesario
   - Guardar modificaciones

3. **Finalizar venta:**
   - Click en "Pagar" en carrito
   - Verificar total en USD y Bs
   - Seleccionar método(s) de pago
   - Ingresar monto recibido (sistema calcula vuelto automático)
   - Confirmar pago

4. **Entrega:**
   - Sistema imprime automáticamente:
     - Ticket para cliente (con total y detalles de pago)
     - Ticket para cocina (con modificaciones y especificaciones)
   - Entregar producto(s) al cliente
   - Para pedidos "LLEVAR", incluir empaques necesarios

#### **Cierre de Turno:**
1. **Acceder a cierre:** `Páginas → Cierre de Caja`
2. **Contar efectivo físico:**
   - Ingresar dólares contados
   - Ingresar bolívares contados
3. **Verificar diferencia:**
   - Sistema muestra diferencia automáticamente
   - Si hay diferencia significativa (>$0.50 o >50Bs), investigar
4. **Confirmar cierre:**
   - Sistema genera reporte detallado
   - Efectivo se transfiere automáticamente a bóveda central
   - Turno queda registrado para auditoría

### **🔴 Para Administradores**

#### **Gestión Diaria:**
1. **Revisar dashboard:** `Admin → Dashboard`
   - Ventas del día, semana, mes
   - Estado de caja y bóveda
   - Alertas de stock bajo
   - Pedidos pendientes

2. **Gestión de Inventario:**
   - `Admin → Productos`: Agregar, editar, eliminar productos
   - `Admin → Insumos`: Control de materias primas
   - `Admin → Configurar Receta`: Para productos preparados

3. **Control Financiero:**
   - `Admin → Tesorería`: Movimientos de bóveda, depósitos, retiros
   - `Admin → Reportes`: Análisis de rentabilidad, ventas por método, etc.
   - `Admin → Auditoría de Caja`: Revisar cierres de cajeros

#### **Compras a Proveedores:**
1. **Crear orden de compra:** `Admin → Compras → Nueva Compra`
   - Seleccionar proveedor
   - Agregar productos, cantidades, precios unitarios
   - Seleccionar método de pago (si es efectivo, descuenta de bóveda)
   - Confirmar orden

2. **Recibir mercancía:** `Admin → Compras → Recibir Mercancía`
   - Seleccionar orden pendiente
   - Confirmar recepción (actualiza stock automáticamente)
   - Sistema recalcula precios de venta basado en nuevos costos

#### **Producción:**
1. **Elaborar productos:** `Admin → Manufactura`
   - Seleccionar producto a producir
   - Ingresar cantidad
   - Confirmar producción (descuenta ingredientes, actualiza stock)

#### **Usuarios:**
1. **Gestión de personal:** `Admin → Usuarios`
   - Agregar nuevos usuarios (cajeros, administradores)
   - Editar datos existentes
   - Eliminar usuarios (con confirmación)

## 🔄 Flujos de Trabajo Completos

### **Flujo 1: Venta de Producto Simple**
```
Cliente selecciona producto → Agregar al carrito → Ir a pagar → 
Seleccionar método de pago → Ingresar monto → Sistema calcula vuelto → 
Confirmar → Imprimir ticket → Descontar stock → Registrar transacción → 
Actualizar caja del cajero
```

### **Flujo 2: Venta de Producto Preparado con Modificaciones**
```
Cliente selecciona pizza → Agregar al carrito → Modificar → 
Seleccionar LLEVAR → Quitar cebolla → Agregar tocineta extra → 
Guardar → Ir a pagar → Pago mixto (50% efectivo, 50% transferencia) → 
Confirmar → Imprimir tickets (cliente + cocina) → 
Descontar ingredientes según receta modificada → Registrar transacciones múltiples → 
Actualizar caja y bóveda según métodos de pago
```

### **Flujo 3: Compra a Proveedor y Recepción**
```
Crear orden de compra a proveedor → Agregar 10 unidades de producto X a $5 c/u → 
Pago con efectivo USD → Confirmar (descuenta $50 de bóveda) → 
Proveedor entrega mercancía → Registrar recepción → 
Aumentar stock en 10 unidades → Recalcular precio de venta basado en nuevo costo → 
Producto disponible para venta
```

### **Flujo 4: Producción Interna**
```
Verificar stock bajo de masa para pizza → Ir a manufactura → 
Seleccionar "Masa Pizza" → Producir 5kg → 
Sistema descuenta harina, agua, levadura, etc. → 
Aumenta stock de masa en 5kg → Actualiza costo promedio → 
Masa disponible para preparar pizzas
```

### **Flujo 5: Cierre Diario y Cuadre**
```
Finalizar turno → Ir a cierre de caja → 
Contar efectivo físico: $350 USD, 12,500 Bs → 
Ingresar montos → Sistema calcula: 
  - Esperado: $345.50 USD, 12,450 Bs  
  - Diferencia: +$4.50 USD, +50 Bs → 
Analizar diferencias (vueltos no registrados, errores) → 
Confirmar cierre → Sistema transfiere a bóveda → 
Genera reporte auditado → Fin de turno
```

## 📊 Estructura de Base de Datos

### **Tablas Principales:**

#### **1. Core del Sistema**
- **users:** Usuarios del sistema (admin, cajeros)
- **products:** Catálogo de productos (simples, preparados, combos)
- **product_components:** Recetas de productos preparados
- **product_valid_extras:** Extras permitidos por producto
- **raw_materials:** Materias primas e insumos
- **manufactured_products:** Productos de cocina intermedios

#### **2. Ventas y Órdenes**
- **orders:** Encabezado de ventas/pedidos
- **order_items:** Detalle de productos vendidos
- **order_item_modifiers:** Modificaciones por ítem
- **cart:** Carrito temporal de compras
- **cart_item_modifiers:** Modificaciones en carrito

#### **3. Tesorería y Finanzas**
- **cash_sessions:** Sesiones de caja por usuario
- **transactions:** Transacciones financieras (ingresos/egresos)
- **payment_methods:** Métodos de pago configurados
- **vault_movements:** Movimientos de bóveda central
- **global_config:** Configuración del sistema

#### **4. Compras y Proveedores**
- **suppliers:** Proveedores del negocio
- **purchase_orders:** Órdenes de compra
- **purchase_order_items:** Detalle de compras
- **purchase_receipts:** Recepción de mercancía

#### **5. Producción**
- **production_recipes:** Recetas de producción
- **production_orders:** Órdenes de producción

### **Relaciones Clave:**
- `orders.user_id` → `users.id` (Venta pertenece a usuario)
- `order_items.order_id` → `orders.id` (Ítems pertenecen a orden)
- `transactions.cash_session_id` → `cash_sessions.id` (Transacción en sesión)
- `product_components.product_id` → `products.id` (Componentes de producto)
- `purchase_orders.supplier_id` → `suppliers.id` (Compra a proveedor)

## 🛡️ Seguridad y Auditoría

### **Características de Seguridad Implementadas:**

#### **Autenticación:**
- Hash SHA256 para contraseñas
- Salting automático
- Bloqueo tras 5 intentos fallidos
- Sesiones con timeout configurable
- Regeneración de ID de sesión

#### **Autorización:**
- Control por roles (admin, user)
- Verificación en cada acceso administrativo
- Middleware de autenticación en includes
- Redirección automática a login si no autenticado

#### **Protección de Datos:**
- Prepared statements PDO en todas las consultas
- Escape de salidas HTML (htmlspecialchars)
- Validación de tipos y rangos en formularios
- Sanitización de entradas de usuario

#### **Auditoría:**
- Log de todas las transacciones financieras
- Registro de cierres de caja con diferencias
- Historial de movimientos de bóveda
- Trazabilidad de modificaciones críticas

#### **Seguridad de Archivos:**
- .htaccess protegiendo archivos sensibles
- Validación de tipos MIME en uploads
- Renombrado único para archivos subidos
- Directivas anti-directory-listing

### **Prácticas Recomendadas Adicionales:**

1. **Certificado SSL:** Implementar HTTPS para todo el tráfico
2. **WAF (Web Application Firewall):** ModSecurity o similar
3. **Backups Encriptados:** GPG para backups fuera del servidor
4. **Monitoreo de Logs:** Herramientas como Fail2ban
5. **Actualizaciones Regulares:** Parches de seguridad para OS y software

## ⚡ Optimización y Rendimiento

### **Optimizaciones Implementadas:**

#### **Base de Datos:**
- Índices en campos de búsqueda frecuente
- Normalización adecuada (hasta 3NF)
- Consultas optimizadas con EXPLAIN
- Limpieza programada de datos temporales

#### **PHP:**
- OPCache habilitado (recomendado)
- Autoloading eficiente
- Minimización de includes innecesarios
- Cache de consultas frecuentes

#### **Frontend:**
- Bootstrap local (sin CDN)
- JavaScript modular y optimizado
- Imágenes comprimidas y en tamaño adecuado
- Minificación de CSS/JS (pendiente)

### **Recomendaciones para Alto Tráfico:**

1. **Caché:** Implementar Memcached o Redis para:
   - Catálogo de productos
   - Precios y tasas
   - Datos de configuración

2. **CDN:** Para assets estáticos si hay múltiples ubicaciones

3. **Balance de Carga:** Nginx como reverse proxy + múltiples instancias PHP-FPM

4. **Replicación de BD:** Master-slave para lecturas frecuentes

5. **Compresión:** Gzip/Brotli para transferencias

## 🚨 Solución de Problemas

### **Problemas Comunes y Soluciones:**

#### **1. Error de Conexión a Base de Datos**
```
Síntoma: "Error de conexión" o página en blanco
Solución:
  1. Verificar .env (DB_HOST, DB_USER, DB_PASSWORD)
  2. Comprobar que MySQL esté corriendo: sudo systemctl status mysql
  3. Verificar permisos de usuario: mysql -u [usuario] -p
  4. Revisar firewall: sudo ufw status
```

#### **2. Stock No Coincide**
```
Síntoma: Cantidades en sistema diferentes a físico
Solución:
  1. Verificar tipo de producto (simple vs preparado)
  2. Revisar recepciones de compra pendientes
  3. Auditoría de transacciones: Admin → Reportes
  4. Verificar producciones no registradas
  5. Revisar órdenes canceladas no restauradas
```

#### **3. Impresión No Funciona**
```
Síntoma: Tickets no se imprimen, error en consola
Solución:
  1. Verificar CUPS: lpstat -p
  2. Probar impresión directa: echo "test" | lp -d [nombre]
  3. Permisos: sudo usermod -a -G lpadmin www-data
  4. Reiniciar servicios: sudo systemctl restart cups apache2
  5. Verificar papel y conexión de impresora
```

#### **4. Caja No Cuadra**
```
Síntoma: Diferencia significativa en cierre
Solución:
  1. Verificar apertura correcta (fondo inicial registrado)
  2. Revisar ventas anuladas no procesadas correctamente
  3. Comprobar método de pago en ventas (efectivo vs transferencia)
  4. Verificar vueltos registrados como transacciones
  5. Auditoría completa de transacciones del día
```

#### **5. Lentitud del Sistema**
```
Síntoma: Páginas cargan lentamente
Solución:
  1. Verificar logs de error PHP y Apache
  2. Optimizar consultas lentas (habilitar slow query log)
  3. Revisar uso de memoria: free -h
  4. Limpiar sesiones antiguas
  5. Considerar upgrade de hardware si persistente
```

### **Procedimientos de Recuperación:**

#### **Recuperación de Backup:**
```bash
# 1. Detener tráfico al sistema
sudo systemctl stop apache2

# 2. Restaurar base de datos
mysql -u root -p minimarket_db < backup_YYYYMMDD.sql

# 3. Restaurar archivos (si necesario)
tar -xzf backup_completo_YYYYMMDD.tar.gz -C /var/www/html/

# 4. Restaurar permisos
chown -R www-data:www-data /var/www/html/minimarket
chmod -R 755 /var/www/html/minimarket
chmod 777 /var/www/html/minimarket/uploads/

# 5. Reinciar servicios
sudo systemctl start apache2 mysql
```

#### **Recuperación de Contraseña de Administrador:**
```sql
-- Si se olvida contraseña de admin
UPDATE users 
SET password = SHA2('nueva_contraseña_temporal', 256) 
WHERE email = 'admin@email.com';

-- Luego cambiar desde el perfil una vez logeado
```

## 🔧 Mantenimiento Regular

### **Diario:**
- [ ] Verificar backups automáticos
- [ ] Revisar logs de errores
- [ ] Monitorear espacio en disco
- [ ] Verificar cierres de caja completos

### **Semanal:**
- [ ] Optimizar tablas de base de datos
- [ ] Limpiar sesiones y datos temporales
- [ ] Revisar y actualizar tasas de cambio
- [ ] Verificar stock crítico y hacer pedidos

### **Mensual:**
- [ ] Actualizar software del servidor (security patches)
- [ ] Revisar y rotar logs
- [ ] Auditoría completa de transacciones
- [ ] Análisis de reportes de rentabilidad
- [ ] Verificar integridad de backups

### **Anual:**
- [ ] Auditoría de seguridad completa
- [ ] Revisión de permisos y usuarios
- [ ] Actualización mayor del sistema (si aplica)
- [ ] Capacitación de personal nuevo

## 📞 Soporte y Contacto

### **Canales de Soporte:**

#### **Soporte Técnico Inmediato:**
- **Teléfono:** [+58 424-6746570]
- **Email:** [robertopv24@gmail.com]
- **WhatsApp:** [+58 424-6746570]

#### **Soporte por Ticket:**
- Sistema integrado de tickets (en desarrollo)
- Respuesta en 24-48 horas hábiles

#### **Documentación Adicional:**
- Manual de usuario completo (PDF disponible)
- Video-tutoriales de operación básica
- FAQs y soluciones comunes en wiki interna

### **Horarios de Soporte:**
- **Lunes a Viernes:** 8:00 AM - 6:00 PM
- **Sábados:** 9:00 AM - 1:00 PM
- **Emergencias:** 24/7 para caídas críticas del sistema

### **Política de Actualizaciones:**
- **Parches de Seguridad:** Aplicación inmediata
- **Actualizaciones Menores:** Cada 3-6 meses
- **Actualizaciones Mayores:** Anuales o según necesidad
- **Notificación:** 15 días previos a actualizaciones que afecten flujos

### **Servicios Profesionales Disponibles:**
1. **Instalación y Configuración:** Presencial o remota
2. **Capacitación de Personal:** On-site o virtual
3. **Desarrollo de Personalizaciones:** Según requerimientos
4. **Migración de Datos:** Desde otros sistemas
5. **Integraciones:** Con contabilidad, delivery apps, etc.
6. **Soporte Prioritario:** SLA con respuesta garantizada

## 🔮 Roadmap y Futuras Mejoras

### **Próxima Versión (Q2 2026):**
- [ ] App móvil para toma de pedidos remotos
- [ ] Integración con delivery (PedidosYa, Rappi)
- [ ] Sistema de fidelización de clientes
- [ ] Reportes gráficos más avanzados
- [ ] Notificaciones push para stock bajo

### **Versión 3.0 (Q4 2026):**
- [ ] Multi-sucursal con sincronización en tiempo real
- [ ] App para inventario con código de barras
- [ ] Integración con sistemas contables (Siigo, Sage)
- [ ] Dashboard predictivo con IA básica
- [ ] Sistema de turnos para empleados

### **Largo Plazo (2027+):**
- [ ] Plataforma completamente en la nube
- [ ] API pública para desarrolladores
- [ ] E-commerce integrado
- [ ] Business Intelligence avanzado
- [ ] Machine learning para predicción de ventas

## 📄 Información Legal y Compliance

### **Cumplimiento Normativo:**
- **LOPD/GDPR:** Datos personales de clientes y empleados protegidos
- **Normativas Fiscales:** Registro detallado para auditorías SUNAT/DIAN/SENIAT
- **Facturación Electrónica:** Estructura preparada para integración
- **Retenciones:** Cálculo automático de impuestos (configurable por país)

### **Términos de Servicio:**
1. **Propiedad:** El software es propiedad intelectual del desarrollador
2. **Licencia:** Por establecimiento, no transferible
3. **Soporte:** Incluido por 12 meses desde instalación
4. **Actualizaciones:** Parches de seguridad incluidos, features nuevas pueden tener costo
5. **Responsabilidad:** Limitada a costo de la licencia

### **Política de Privacidad:**
- Datos de clientes solo para procesamiento de órdenes
- No se comparte información con terceros sin consentimiento
- Derecho a olvido implementado (eliminación de datos personales)
- Encriptación de datos sensibles en tránsito y en reposo

---

## ✅ Checklist de Implementación

### **Pre-Instalación:**
- [ ] Servidor con requisitos mínimos confirmados
- [ ] Dominio y DNS configurados (opcional)
- [ ] Certificado SSL obtenido (altamente recomendado)
- [ ] Personal identificado (administrador, cajeros)
- [ ] Procedimientos operativos definidos

### **Instalación:**
- [ ] Base de datos creada e importada
- [ ] Archivos del sistema subidos
- [ ] Permisos de archivos configurados
- [ ] Variables de entorno establecidas
- [ ] Usuario administrador creado

### **Configuración:**
- [ ] Métodos de pago configurados
- [ ] Tasa de cambio establecida
- [ ] Impresora configurada y probada
- [ ] Productos básicos cargados
- [ ] Proveedores principales registrados

### **Pruebas:**
- [ ] Login y autenticación funcionando
- [ ] Ventas de prueba completadas
- [ ] Cierre de caja probado
- [ ] Impresión de tickets verificada
- [ ] Backup y restore probados

### **Go-Live:**
- [ ] Personal capacitado
- [ ] Apertura de caja inicial realizada
- [ ] Sistema en producción
- [ ] Monitoreo activado
- [ ] Soporte contactable

---

## 🎯 Conclusión

**Minimarket POS** es una solución completa, robusta y profesional para la gestión de pequeños y medianos negocios en el sector de alimentos y venta minorista. Con más de 25,000 líneas de código cuidadosamente desarrolladas, el sistema ofrece:

- **Control total** sobre todas las operaciones del negocio
- **Seguridad financiera** con auditoría completa
- **Eficiencia operativa** mediante automatización inteligente
- **Toma de decisiones informada** con reportes en tiempo real
- **Escalabilidad** para crecimiento futuro del negocio

Este sistema representa años de experiencia en desarrollo de software empresarial y comprensión profunda de las necesidades operativas de negocios de alimentos y retail.

---

**© 2025 [Roberto J. Perozo V.]. Todos los derechos reservados.**

*Este documento y el software descrito son propiedad intelectual. Queda prohibida cualquier forma de reproducción, distribución o modificación sin autorización expresa por escrito del propietario.*

**Última actualización:** Diciembre 2025  
**Versión del Sistema:** 2.0.0  
**Estado:** Producción - Estable
