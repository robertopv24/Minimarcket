-- phpMyAdmin SQL Dump
-- version 5.2.2-1.fc42
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 24-11-2025 a las 15:17:15
-- Versión del servidor: 10.11.11-MariaDB
-- Versión de PHP: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `minimarket`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cash_sessions`
--

CREATE TABLE `cash_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `opening_balance_usd` decimal(10,2) DEFAULT 0.00,
  `opening_balance_ves` decimal(10,2) DEFAULT 0.00,
  `closing_balance_usd` decimal(10,2) DEFAULT 0.00,
  `closing_balance_ves` decimal(10,2) DEFAULT 0.00,
  `calculated_usd` decimal(10,2) DEFAULT 0.00,
  `calculated_ves` decimal(10,2) DEFAULT 0.00,
  `status` enum('open','closed') DEFAULT 'open',
  `opened_at` timestamp NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cash_sessions`
--

INSERT INTO `cash_sessions` (`id`, `user_id`, `opening_balance_usd`, `opening_balance_ves`, `closing_balance_usd`, `closing_balance_ves`, `calculated_usd`, `calculated_ves`, `status`, `opened_at`, `closed_at`) VALUES
(1, 4, 0.00, 0.00, 1.00, 250.00, 1.00, 250.00, 'closed', '2025-11-24 04:33:13', '2025-11-24 06:06:26'),
(2, 4, 20.00, 5000.00, 38.00, 7489.00, 38.60, 7489.00, 'closed', '2025-11-24 06:46:00', '2025-11-24 06:54:20'),
(3, 4, 20.00, 2000.00, 25.00, 2200.00, 25.00, 2200.00, 'closed', '2025-11-24 11:25:58', '2025-11-24 11:32:10'),
(4, 4, 5.00, 1000.00, 6.00, 1100.00, 6.00, 1100.00, 'closed', '2025-11-24 12:03:34', '2025-11-24 12:13:32'),
(5, 4, 0.00, 0.00, 1.00, 200.00, 1.00, 200.00, 'closed', '2025-11-24 12:35:56', '2025-11-24 12:37:11'),
(6, 4, 10.00, 1000.00, 11.00, 1200.00, 11.00, 1200.00, 'closed', '2025-11-24 12:37:40', '2025-11-24 12:39:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_vault`
--

CREATE TABLE `company_vault` (
  `id` int(11) NOT NULL,
  `balance_usd` decimal(12,2) DEFAULT 0.00,
  `balance_ves` decimal(12,2) DEFAULT 0.00,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `company_vault`
--

INSERT INTO `company_vault` (`id`, `balance_usd`, `balance_ves`, `last_updated`) VALUES
(1, 211.00, 14979.00, '2025-11-24 12:39:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `global_config`
--

CREATE TABLE `global_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `global_config`
--

INSERT INTO `global_config` (`id`, `config_key`, `config_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Mi Plataforma Web', 'Nombre de la plataforma', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(2, 'site_url', 'https://www.miplataforma.com', 'URL de la web', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(3, 'default_language', 'es', 'Idioma predeterminado del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(4, 'timezone', 'America/Caracas', 'Zona horaria del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(5, 'currency', 'USD', 'Moneda predeterminada del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(6, 'exchange_rate', '200', 'Tasa de cambio USD-VES', '2025-02-21 12:14:27', '2025-11-24 10:51:24'),
(7, 'admin_email', 'admin@miplataforma.com', 'Correo del administrador principal', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(8, 'support_email', 'soporte@miplataforma.com', 'Correo de soporte', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(9, 'maintenance_mode', '0', 'Modo mantenimiento (1 = activado, 0 = desactivado)', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(10, 'registration_enabled', '1', 'Permitir nuevos registros de usuarios', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(11, 'max_login_attempts', '5', 'Intentos máximos de inicio de sesión antes de bloqueo', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(12, 'password_reset_token_expiry', '3600', 'Tiempo de expiración del token de recuperación en segundos', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(13, 'session_timeout', '1800', 'Duración de sesión en segundos', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(14, 'jwt_secret_key', 'clave_secreta_segura', 'Clave secreta para autenticación JWT', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(15, 'enable_2fa', '1', 'Habilitar autenticación en dos pasos', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(16, 'smtp_host', 'smtp.gmail.com', 'Servidor SMTP', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(17, 'smtp_port', '587', 'Puerto SMTP', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(18, 'smtp_user', 'noreply@miplataforma.com', 'Correo SMTP', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(19, 'smtp_password', 'hashed_smtp_password', 'Contraseña SMTP cifrada', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(20, 'smtp_secure', 'tls', 'Tipo de cifrado SMTP (ssl/tls)', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(21, 'smtp_from_name', 'Soporte Plataforma', 'Nombre del remitente en correos', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(22, 'max_upload_size', '10MB', 'Tamaño máximo permitido para subida de archivos', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(23, 'allowed_file_types', 'jpg,png,pdf,docx', 'Tipos de archivos permitidos en subida', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(24, 'storage_path', '/uploads/', 'Ruta de almacenamiento de archivos', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(25, 'image_quality', '90', 'Calidad de compresión de imágenes (1-100)', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(26, 'api_google_maps_key', 'TU_CLAVE_AQUI', 'Clave API de Google Maps', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(27, 'api_recaptcha_key', 'TU_CLAVE_AQUI', 'Clave API de Google reCAPTCHA', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(28, 'api_payment_gateway_key', 'TU_CLAVE_AQUI', 'Clave API del proveedor de pagos', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(29, 'webhook_url', 'https://www.miplataforma.com/webhook', 'URL para recibir notificaciones de pagos', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(30, 'tax_percentage', '16', 'Porcentaje de impuesto sobre las ventas', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(31, 'invoice_prefix', 'INV-', 'Prefijo para las facturas generadas', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(32, 'invoice_footer', 'Gracias por su compra en Mi Plataforma Web', 'Mensaje en el pie de factura', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(33, 'enable_email_notifications', '1', 'Habilitar notificaciones por correo', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(34, 'enable_sms_notifications', '0', 'Habilitar notificaciones por SMS', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(35, 'enable_push_notifications', '1', 'Habilitar notificaciones push', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(36, 'notification_frequency', 'daily', 'Frecuencia de notificaciones (daily, weekly, instant)', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(37, 'facebook_url', 'https://facebook.com/miplataforma', 'Página de Facebook de la empresa', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(38, 'twitter_url', 'https://twitter.com/miplataforma', 'Perfil de Twitter de la empresa', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(39, 'instagram_url', 'https://instagram.com/miplataforma', 'Perfil de Instagram de la empresa', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(40, 'linkedin_url', 'https://linkedin.com/company/miplataforma', 'Perfil de LinkedIn de la empresa', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(41, 'google_analytics_id', 'UA-XXXXXXXXX', 'ID de Google Analytics', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(42, 'seo_meta_description', 'La mejor plataforma para comprar y vender online', 'Descripción SEO de la web', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(43, 'seo_meta_keywords', 'compras, ventas, ecommerce, tecnología', 'Palabras clave SEO', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(44, 'enable_caching', '1', 'Habilitar caché para mejorar el rendimiento', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(45, 'cache_expiry_time', '3600', 'Tiempo de expiración de caché en segundos', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(46, 'enable_debug_mode', '0', 'Habilitar modo de depuración (1 = sí, 0 = no)', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(47, 'max_concurrent_users', '1000', 'Máximo de usuarios concurrentes en el sistema', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(48, 'homepage_layout', 'grid', 'Diseño de la página principal (grid, list, slider)', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(49, 'enable_dark_mode', '1', 'Habilitar modo oscuro en la UI', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(50, 'allow_guest_checkout', '1', 'Permitir compras sin registro', '2025-02-21 12:14:28', '2025-03-10 06:18:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` enum('public','private','admin','authenticated') NOT NULL DEFAULT 'public',
  `type` enum('header','sidebar','footer','mobile') NOT NULL DEFAULT 'header',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `menus`
--

INSERT INTO `menus` (`id`, `parent_id`, `title`, `description`, `url`, `icon`, `position`, `is_active`, `visibility`, `type`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Inicio', 'Página principal', '/index.php', 'fa-home', 1, 1, 'public', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(2, NULL, 'Tienda', 'Explora nuestros productos', '', 'fa-shopping-cart', 2, 1, 'public', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(3, NULL, 'Nosotros', 'Conoce más sobre nuestra empresa', '/paginas/nosotros.php', 'fa-info-circle', 3, 1, 'public', 'footer', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(4, NULL, 'Contacto', 'Contáctanos', '/paginas/contacto.php', 'fa-envelope', 4, 1, 'public', 'footer', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(6, 2, 'Mis Compras', 'Historial de compras', '/paginas/carrito.php', 'fa-list-alt', 2, 1, 'authenticated', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(8, NULL, 'Panel de Control', 'Administración general', '', 'fa-cogs', 1, 1, 'admin', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(9, 8, 'Usuarios', 'Administrar usuarios', '/admin/users', 'fa-users', 2, 1, 'admin', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(10, 2, 'Productos', 'Administrar inventario', '/paginas/tienda.php', 'fa-boxes', 2, 1, 'authenticated', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(11, 8, 'Órdenes', 'Gestión de ventas', '/admin/orders', 'fa-shopping-basket', 4, 1, 'admin', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(12, 8, 'Reportes', 'Informes del sistema', '/admin/reports', 'fa-chart-bar', 5, 1, 'admin', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(20, NULL, 'Modo Oscuro', 'Activar modo oscuro', '#', 'fa-moon', 99, 1, 'public', 'mobile', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(30, NULL, 'Términos y Condiciones', 'Reglas del sitio', '/paginas/terminos.php', 'fa-file-alt', 1, 1, 'public', 'footer', '2025-02-23 23:09:00', '2025-03-10 06:18:14'),
(31, NULL, 'Política de Privacidad', 'Cómo protegemos tu información', '/paginas/privacidad.php', 'fa-shield-alt', 2, 1, 'public', 'footer', '2025-02-23 23:09:00', '2025-03-10 06:18:14'),
(32, NULL, 'Soporte', 'Centro de ayuda', '/paginas/soporte.php', 'fa-life-ring', 3, 1, 'public', 'footer', '2025-02-23 23:09:00', '2025-03-10 06:18:14'),
(33, NULL, 'Regístrate', 'formulario de registro', '/paginas/register.php', 'fa-user-plus', 100, 1, 'public', 'sidebar', '2025-02-24 02:49:55', '2025-03-10 06:18:14'),
(34, NULL, 'Inicia Sesión', 'formulario para Iniciar Sesión', '/paginas/login.php', 'fa-laptop', 99, 1, 'public', 'sidebar', '2025-02-24 03:33:35', '2025-03-10 06:18:14'),
(35, NULL, 'Perfil', 'Perfil de Usuario', '/paginas/perfil.php', 'fa-laptop', 1, 1, 'authenticated', 'sidebar', '2025-02-24 03:33:35', '2025-03-10 06:18:14'),
(36, NULL, 'cerrar Sesión', 'cerrar Sesión', '/paginas/logout.php', 'fa-laptop', 98, 1, 'authenticated', 'sidebar', '2025-02-24 03:33:35', '2025-03-10 06:18:14'),
(37, 8, 'Administración', 'Panel Administración general', '/admin/index.php', 'fa-cogs', 1, 1, 'admin', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(38, 8, 'Menus', 'Panel Administración de menus', '/admin/menus.php', 'fa-cogs', 100, 1, 'admin', 'header', '2025-02-21 13:36:32', '2025-03-10 06:18:14'),
(39, NULL, 'Menus', NULL, 'Panel Administración de menus', NULL, 0, 1, 'public', '', '2025-02-25 16:19:59', '2025-03-10 06:18:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_roles`
--

CREATE TABLE `menu_roles` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `role` enum('admin','business','user','guest') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `status`, `shipping_address`, `shipping_method`, `tracking_number`, `created_at`, `updated_at`) VALUES
(21, 4, 0.80, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-21 23:13:49', NULL),
(22, 4, 0.80, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-21 23:14:22', NULL),
(23, 4, 1.20, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-21 23:14:29', NULL),
(24, 4, 0.66, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-24 00:24:00', NULL),
(25, 4, 0.17, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-24 00:25:20', NULL),
(28, 4, 0.24, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-24 00:25:28', NULL),
(29, 4, 1.20, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-24 00:25:38', NULL),
(30, 4, 0.12, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 16:28:06', '2025-11-24 05:32:58'),
(31, 4, 0.10, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 16:28:22', '2025-11-24 05:33:18'),
(32, 4, 0.40, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 17:03:30', '2025-11-24 05:33:28'),
(33, 4, 1.00, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 17:20:19', '2025-11-24 05:33:08'),
(34, 4, 1.23, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 17:56:09', '2025-11-24 05:33:47'),
(35, 4, 0.13, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 18:18:17', '2025-11-24 05:33:55'),
(36, 4, 1.30, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 18:41:32', '2025-11-24 05:33:36'),
(37, 4, 1.00, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 18:48:21', '2025-11-24 05:34:08'),
(38, 4, 2.32, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-09 23:14:38', '2025-11-24 05:34:35'),
(39, 4, 1.20, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-10 00:37:34', '2025-11-24 05:34:43'),
(40, 4, 0.60, 'paid', 'asdasdasd12', NULL, NULL, '2025-03-14 23:41:19', '2025-11-24 05:34:54'),
(41, 4, 0.60, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-21 23:18:47', '2025-11-24 05:35:04'),
(42, 4, 3.10, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-24 04:35:06', NULL),
(43, 4, 1.50, 'paid', 'asdasdasd12', NULL, NULL, '2025-11-24 04:49:28', '2025-11-24 05:35:12'),
(48, 4, 3.00, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 05:30:57', '2025-11-24 05:30:57'),
(49, 4, 6.00, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 05:39:48', '2025-11-24 05:39:48'),
(50, 4, 1.50, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 05:40:56', '2025-11-24 05:40:56'),
(51, 4, 26.60, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 06:47:57', '2025-11-24 06:47:57'),
(52, 4, 8.00, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 06:48:27', '2025-11-24 06:48:27'),
(53, 4, 3.60, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 06:49:11', '2025-11-24 06:49:12'),
(54, 4, 5.00, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 06:50:07', '2025-11-24 06:50:07'),
(55, 4, 8.89, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 06:51:08', '2025-11-24 06:51:08'),
(56, 4, 15.70, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 06:52:02', '2025-11-24 06:52:02'),
(57, 4, 11.22, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 11:31:28', '2025-11-24 11:31:28'),
(58, 4, 3.87, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 12:04:49', '2025-11-24 12:04:49'),
(59, 4, 3.87, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 12:36:46', '2025-11-24 12:36:46'),
(60, 4, 3.87, 'paid', 'Tienda Física', NULL, NULL, '2025-11-24 12:39:13', '2025-11-24 12:39:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(21, 21, 46, 1, 0.80),
(23, 22, 46, 1, 0.80),
(25, 23, 28, 1, 1.20),
(27, 24, 26, 1, 0.66),
(29, 25, 48, 1, 0.17),
(34, 28, 42, 8, 0.03),
(35, 29, 25, 1, 1.20),
(36, 30, 42, 4, 0.03),
(37, 31, 19, 1, 0.10),
(38, 32, 33, 1, 0.40),
(39, 33, 47, 1, 1.00),
(40, 34, 24, 1, 1.20),
(41, 34, 42, 1, 0.03),
(42, 35, 38, 1, 0.13),
(43, 36, 20, 10, 0.13),
(44, 37, 47, 1, 1.00),
(45, 38, 47, 1, 1.00),
(46, 38, 42, 4, 0.03),
(47, 38, 28, 1, 1.20),
(48, 39, 16, 2, 0.60),
(49, 40, 16, 1, 0.60),
(50, 41, 16, 1, 0.60),
(51, 42, 41, 2, 0.80),
(52, 42, 4, 1, 1.50),
(53, 43, 4, 1, 1.50),
(58, 48, 4, 2, 1.50),
(59, 49, 41, 1, 0.80),
(60, 49, 4, 1, 1.50),
(61, 49, 32, 1, 0.60),
(62, 49, 21, 1, 1.00),
(63, 49, 16, 1, 0.60),
(64, 49, 24, 1, 1.20),
(65, 49, 19, 3, 0.10),
(66, 50, 4, 1, 1.50),
(67, 51, 47, 10, 1.20),
(68, 51, 21, 5, 1.00),
(69, 51, 24, 8, 1.20),
(70, 52, 21, 8, 1.00),
(71, 53, 25, 3, 1.20),
(72, 54, 27, 20, 0.25),
(73, 55, 30, 7, 1.27),
(74, 56, 17, 10, 1.57),
(75, 57, 17, 1, 1.57),
(76, 57, 30, 2, 1.27),
(77, 57, 27, 1, 0.25),
(78, 57, 25, 1, 1.20),
(79, 57, 24, 1, 1.20),
(80, 57, 4, 1, 1.80),
(81, 57, 19, 3, 0.10),
(82, 57, 28, 1, 1.20),
(83, 57, 26, 1, 0.66),
(84, 57, 18, 1, 0.50),
(85, 58, 4, 1, 1.80),
(86, 58, 17, 1, 1.57),
(87, 58, 18, 1, 0.50),
(88, 59, 4, 1, 1.80),
(89, 59, 17, 1, 1.57),
(90, 59, 18, 1, 0.50),
(91, 60, 4, 1, 1.80),
(92, 60, 17, 1, 1.57),
(93, 60, 18, 1, 0.50);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `currency` enum('USD','VES') NOT NULL,
  `type` enum('cash','bank','digital') NOT NULL DEFAULT 'cash',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `currency`, `type`, `is_active`) VALUES
(1, 'Efectivo USD', 'USD', 'cash', 1),
(2, 'Efectivo VES', 'VES', 'cash', 1),
(3, 'Zelle', 'USD', 'digital', 1),
(4, 'Pago Móvil', 'VES', 'bank', 1),
(5, 'Punto de Venta', 'VES', 'bank', 1),
(6, 'Binance USDT', 'USD', 'digital', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_usd` decimal(10,2) NOT NULL,
  `price_ves` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `profit_margin` decimal(5,2) NOT NULL DEFAULT 20.00,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price_usd`, `price_ves`, `stock`, `image_url`, `created_at`, `profit_margin`, `updated_at`) VALUES
(4, 'Time doble click', '', 1.80, 360.00, 10, 'uploads/product_images/product3.jpg', '2025-11-24 12:39:13', 20.00, '2025-11-24 10:51:24'),
(10, 'Trululu Aros', '', 0.04, 8.00, 100, 'uploads/product_images/product1.jpg', '2025-03-01 00:05:26', 20.00, '2025-11-24 10:51:24'),
(14, 'Pañales', '', 0.40, 80.00, 30, 'uploads/product_images/67cd4d754632a_1733533014_IMG-20241114-WA0006 (1).jpg', '2025-03-06 15:27:45', 20.00, '2025-11-24 10:51:24'),
(15, 'Champu', '', 0.33, 66.00, 16, 'uploads/product_images/67cd4d49bea3d_1733536078_IMG-20241015-WA0014 (1).jpg', '2025-03-06 15:28:12', 20.00, '2025-11-24 10:51:24'),
(16, 'Lemon', '', 0.60, 120.00, 17, 'uploads/product_images/67cd4ca235a8b_1733534473_IMG-20241030-WA0000 (2).jpg', '2025-11-24 05:39:48', 20.00, '2025-11-24 10:51:24'),
(17, 'Derza', '', 1.57, 314.00, 6, 'uploads/product_images/67cd4fa120cd5_detergente-dersa-bolsa-x-4000-gramos-bicarbonato-manzana.jpg', '2025-11-24 12:39:13', 20.00, '2025-11-24 10:51:24'),
(18, 'Especial ', '', 0.50, 100.00, 18, 'uploads/product_images/67cd4d21dbcc7_1733534099_IMG-20241030-WA0000 (1).jpg', '2025-11-24 12:39:13', 20.00, '2025-11-24 10:51:24'),
(19, 'Cubitos', '', 0.10, 20.00, 48, 'uploads/product_images/67cd4b76014ae_1733531654_IMG-20241025-WA0001 (1) (1).jpg', '2025-11-24 11:31:28', 20.00, '2025-11-24 10:51:24'),
(20, 'Ellas Nocturna ', 'Toallas higiénicas', 0.13, 26.00, 24, 'uploads/product_images/67cc98fc4958b_17414616938876713501792961683079.jpg', '2025-03-09 18:41:32', 20.00, '2025-11-24 10:51:24'),
(21, 'Colgate', 'Crema dental \r\n90g', 1.00, 200.00, 6, 'uploads/product_images/67cca58b81f96_17414649522948096422167686019999.jpg', '2025-11-24 06:48:27', 20.00, '2025-11-24 10:51:24'),
(22, 'Prestobarba ', '', 0.25, 50.00, 75, 'uploads/product_images/67cd51437a864_DORCO-AFEITADORAS.jpg', '2025-03-06 15:33:00', 20.00, '2025-11-24 10:51:24'),
(23, 'Nutribela', '', 0.40, 80.00, 7, 'uploads/product_images/67cd500f6574f_Imagen-de-WhatsApp-2024-10-10-a-las-12.30.19_4a8ef7d3-Photoroom.png', '2025-03-06 15:33:19', 20.00, '2025-11-24 10:51:24'),
(24, 'Azúcar mayagüez ', 'Azúcar blanco refinada \r\n1000g', 1.20, 240.00, 7, 'uploads/product_images/67cc79102f1ce_17414535682905754480784189297811.jpg', '2025-11-24 11:31:28', 20.00, '2025-11-24 10:51:24'),
(25, 'Arroz Masías', 'Arroz blanco tipo 1\r\n900g', 1.20, 240.00, 9, 'uploads/product_images/67cc64a4432cd_17414483196827719961249995023379.jpg', '2025-11-24 11:31:28', 20.00, '2025-11-24 10:51:24'),
(26, 'Aceite Vegetal ', 'Aceite Vegetal \r\nImperial mini', 0.66, 132.00, 22, 'uploads/product_images/67cce78f2024e_17414818136548145782465313964763.jpg', '2025-11-24 11:31:28', 20.00, '2025-11-24 10:51:24'),
(27, 'Boka', '', 0.25, 50.00, 17, 'uploads/product_images/67cd4aef93c79_product2.jpg', '2025-11-24 11:31:28', 20.00, '2025-11-24 10:51:24'),
(28, 'Cafe La Protectora', 'Cafe \r\n100g', 1.20, 240.00, 6, 'uploads/product_images/67cc99b963966_17414619171236935147890092267948.jpg', '2025-11-24 11:31:28', 20.00, '2025-11-24 10:51:24'),
(29, 'Chimon', '', 0.42, 84.00, 14, 'uploads/product_images/67cd4c3c2455c_1733533277_IMG-20241106-WA0003 (1).jpg', '2025-03-06 15:35:07', 20.00, '2025-11-24 10:51:24'),
(30, 'Mantequilla Nelly', 'Mantequilla \r\n250g', 1.27, 254.00, 3, 'uploads/product_images/67cc9acc5a385_17414621902836174822109289513804.jpg', '2025-11-24 11:31:28', 20.00, '2025-11-24 10:51:24'),
(31, 'Arepa repa', 'Harina de maíz blanco precocida ', 0.78, 156.00, 15, 'uploads/product_images/67cc75e46297e_1741452750449821995791584165818.jpg', '2025-03-06 15:37:55', 20.00, '2025-11-24 10:51:24'),
(32, 'Suavitel ', '', 0.60, 120.00, 24, 'uploads/product_images/67cd4c6bd478e_1733534571_IMG-20241030-WA0000 (3).jpg', '2025-11-24 05:39:48', 20.00, '2025-11-24 10:51:24'),
(33, 'Mayonesa ', 'Mayonesa \r\n80g', 0.40, 80.00, 10, 'uploads/product_images/67ccaaf45983d_17414663352551627909815234022204.jpg', '2025-03-09 17:03:30', 20.00, '2025-11-24 10:51:24'),
(34, 'Huevos ', '', 0.20, 40.00, 6, 'uploads/product_images/67cd505be5b03_502890.jpg', '2025-03-06 15:39:17', 20.00, '2025-11-24 10:51:24'),
(35, 'Salsa de tomate ', 'Salsa de tomate \r\n80g', 0.48, 96.00, 16, 'uploads/product_images/67ccaaac9941d_17414662584991869960381084718727.jpg', '2025-03-06 15:39:43', 20.00, '2025-11-24 10:51:24'),
(36, 'Trifogon', '', 0.07, 14.00, 50, 'uploads/product_images/67cd4bbdac106_1733531955_IMG-20241123-WA0001 (1).jpg', '2025-03-06 15:40:02', 20.00, '2025-11-24 10:51:24'),
(37, 'Sal San Benito ', 'Sal fina de mesa\r\n1k', 0.40, 80.00, 24, 'uploads/product_images/67cc7b9908576_17414541544023457104180252532273.jpg', '2025-03-06 15:40:16', 20.00, '2025-11-24 10:51:24'),
(38, 'Chupetas', '', 0.13, 26.00, 80, 'uploads/product_images/67cceb222efe5_17414827442048128366443505538707.jpg', '2025-03-09 18:18:17', 20.00, '2025-11-24 10:51:24'),
(39, 'Bombillo ', '', 0.30, 60.00, 10, 'uploads/product_images/67cd50dfc2f90_bombillo-110v-hk-luz.jpg', '2025-03-06 15:41:02', 20.00, '2025-11-24 10:51:24'),
(40, 'Yesqueros', '', 0.20, 40.00, 6, 'uploads/product_images/67cd4dc4e186d_1733537874_IMG-20240429-WA0002 (1).jpg', '2025-03-06 15:41:41', 20.00, '2025-11-24 10:51:24'),
(41, 'Time silver', '', 0.80, 160.00, 7, 'uploads/product_images/67ccea9a29ebb_17414826339184124622918663184418.jpg', '2025-11-24 05:39:48', 20.00, '2025-11-24 10:51:24'),
(42, 'Chicles de tattoo', '', 0.03, 6.00, 105, 'uploads/product_images/67ccea2bcaccf_17414825059406151719491204023196.jpg', '2025-03-09 23:14:38', 20.00, '2025-11-24 10:51:24'),
(43, 'Desodorante Speed Stick', 'Desodorante de sobres ', 0.25, 50.00, 97, 'uploads/product_images/67cca3f811f47_17414644462915797512284519772386.jpg', '2025-03-06 15:47:11', 20.00, '2025-11-24 10:51:24'),
(44, 'Esponja Matrixx', 'Esponja multiusos', 0.25, 50.00, 7, 'uploads/product_images/67cc95410a92a_17414607633935033918757604367667.jpg', '2025-03-08 19:06:41', 20.00, '2025-11-24 10:51:24'),
(45, 'Esponja chemmer', 'Esponja de acero inoxidable ', 0.30, 60.00, 13, 'uploads/product_images/67cc964710bf8_17414610426508755076250693373492.jpg', '2025-03-08 19:11:03', 20.00, '2025-11-24 10:51:24'),
(46, 'Time Blue', 'Cigarros ', 0.80, 160.00, 7, 'uploads/product_images/67cce4178e04b_17414809668196991081838860535850.jpg', '2025-03-09 13:46:56', 20.00, '2025-11-24 10:51:24'),
(47, 'Time 1 click ', 'Cigarros de menta ', 1.20, 240.00, 25, 'uploads/product_images/67cce486949e9_17414810666162828247057201095744.jpg', '2025-11-24 06:47:57', 20.00, '2025-11-24 10:51:24'),
(48, 'Gomitas Princess', 'Tubos de gomitas ', 0.17, 34.00, 28, 'uploads/product_images/67ccef4fbce30_1741483825285951028960618843358.jpg', '2025-03-09 14:29:00', 20.00, '2025-11-24 10:51:24'),
(49, 'Coloreti', 'Pastillas de chocolate ', 0.22, 44.00, 22, 'uploads/product_images/67ccefbb610e0_17414839369026282062442029403121.jpg', '2025-03-09 01:32:43', 20.00, '2025-11-24 10:51:24'),
(50, 'Muuu.. mantequilla ', 'Galletas de mantequilla ', 0.20, 40.00, 18, 'uploads/product_images/67ccf00d1f51f_17414840199863787932657680490180.jpg', '2025-03-09 01:34:05', 20.00, '2025-11-24 10:51:24'),
(51, 'Galletas Charmy', 'Galletas con relleno de crema ', 0.18, 36.00, 12, 'uploads/product_images/67ccf0888adb5_17414841411356361203726346495725.jpg', '2025-03-09 01:36:08', 20.00, '2025-11-24 10:51:24'),
(52, 'Oka loka chicle en polvo ', 'Chicle en polvo ', 0.20, 40.00, 12, 'uploads/product_images/67ccf10c05bdb_1741484263919300001115409146628.jpg', '2025-03-09 01:38:20', 20.00, '2025-11-24 10:51:24'),
(53, 'Caramelos Chaos', 'Caramelos de menta', 0.04, 8.00, 100, 'uploads/product_images/67cd5297dd2b2_images.jpg', '2025-03-09 02:42:06', 20.00, '2025-11-24 10:51:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','received','canceled') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exchange_rate` decimal(10,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `supplier_id`, `order_date`, `expected_delivery_date`, `total_amount`, `status`, `created_at`, `updated_at`, `exchange_rate`) VALUES
(7, 1, '2025-10-01', '2025-11-20', 12.00, 'received', '2025-11-24 01:10:51', '2025-11-24 01:11:26', 300.00),
(8, 1, '2025-11-23', '2025-11-23', 12.00, 'received', '2025-11-24 01:23:07', '2025-11-24 01:23:34', 310.00),
(9, 1, '2025-11-23', '2025-11-23', 29.40, 'received', '2025-11-24 01:27:27', '2025-11-24 01:27:47', 310.00),
(10, 1, '2025-11-23', '2025-11-23', 8.00, 'received', '2025-11-24 01:48:00', '2025-11-24 01:48:33', 310.00),
(11, 1, '2025-11-23', '2025-11-23', 9.90, 'received', '2025-11-24 02:24:36', '2025-11-24 02:24:53', 100.00),
(12, 1, '2025-11-24', '2025-11-24', 10.00, 'received', '2025-11-24 07:00:02', '2025-11-24 07:00:27', 100.00),
(13, 1, '2025-11-24', '2025-11-27', 15.00, 'received', '2025-11-24 07:20:01', '2025-11-24 07:20:35', 100.00),
(14, 1, '2025-11-24', '2025-11-27', 4.20, 'received', '2025-11-24 07:24:04', '2025-11-24 07:24:50', 100.00),
(15, 1, '2025-11-24', '2025-11-27', 6.60, 'received', '2025-11-24 07:26:33', '2025-11-24 07:28:26', 100.00),
(16, 1, '2025-11-24', '2025-11-27', 3.00, 'received', '2025-11-24 07:32:36', '2025-11-24 07:33:01', 100.00),
(17, 1, '2025-11-24', '2025-11-27', 3.00, 'received', '2025-11-24 07:35:03', '2025-11-24 07:35:24', 100.00),
(18, 1, '2025-11-24', '2025-11-27', 2.50, 'received', '2025-11-24 09:02:02', '2025-11-24 09:04:05', 100.00),
(19, 1, '2025-11-24', '2025-11-27', 3.00, 'received', '2025-11-24 09:10:00', '2025-11-24 09:11:32', 100.00),
(20, 1, '2025-11-24', '2025-11-27', 4.80, 'received', '2025-11-24 09:14:54', '2025-11-24 09:16:13', 100.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `product_id`, `quantity`, `unit_price`, `created_at`, `updated_at`) VALUES
(9, 7, 47, 10, 1.20, '2025-11-24 01:10:51', '2025-11-24 01:10:51'),
(10, 8, 21, 12, 1.00, '2025-11-24 01:23:07', '2025-11-24 01:23:07'),
(11, 9, 4, 10, 1.50, '2025-11-24 01:27:27', '2025-11-24 01:27:27'),
(12, 9, 32, 24, 0.60, '2025-11-24 01:27:27', '2025-11-24 01:27:27'),
(13, 10, 41, 10, 0.80, '2025-11-24 01:48:00', '2025-11-24 01:48:00'),
(14, 11, 14, 30, 0.33, '2025-11-24 02:24:36', '2025-11-24 02:24:36'),
(15, 12, 47, 10, 1.00, '2025-11-24 07:00:02', '2025-11-24 07:00:02'),
(16, 13, 4, 10, 1.50, '2025-11-24 07:20:01', '2025-11-24 07:20:01'),
(17, 14, 29, 12, 0.35, '2025-11-24 07:24:04', '2025-11-24 07:24:04'),
(18, 15, 37, 20, 0.33, '2025-11-24 07:26:33', '2025-11-24 07:26:33'),
(19, 16, 10, 100, 0.03, '2025-11-24 07:32:36', '2025-11-24 07:32:36'),
(20, 17, 36, 50, 0.06, '2025-11-24 07:35:03', '2025-11-24 07:35:03'),
(21, 18, 39, 10, 0.25, '2025-11-24 09:02:02', '2025-11-24 09:02:02'),
(22, 19, 45, 12, 0.25, '2025-11-24 09:10:01', '2025-11-24 09:10:01'),
(23, 20, 35, 12, 0.40, '2025-11-24 09:14:54', '2025-11-24 09:14:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_receipts`
--

CREATE TABLE `purchase_receipts` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `purchase_receipts`
--

INSERT INTO `purchase_receipts` (`id`, `purchase_order_id`, `receipt_date`, `created_at`, `updated_at`) VALUES
(6, 7, '2025-11-22', '2025-11-24 01:11:26', '2025-11-24 01:11:26'),
(7, 8, '2025-11-23', '2025-11-24 01:23:34', '2025-11-24 01:23:34'),
(8, 9, '2025-11-23', '2025-11-24 01:27:47', '2025-11-24 01:27:47'),
(9, 10, '2025-11-23', '2025-11-24 01:48:33', '2025-11-24 01:48:33'),
(10, 11, '2025-11-23', '2025-11-24 02:24:53', '2025-11-24 02:24:53'),
(11, 12, '2025-11-24', '2025-11-24 07:00:27', '2025-11-24 07:00:27'),
(12, 13, '2025-11-24', '2025-11-24 07:20:35', '2025-11-24 07:20:35'),
(13, 14, '2025-11-24', '2025-11-24 07:24:50', '2025-11-24 07:24:50'),
(14, 15, '2025-11-24', '2025-11-24 07:28:26', '2025-11-24 07:28:26'),
(15, 16, '2025-11-24', '2025-11-24 07:33:01', '2025-11-24 07:33:01'),
(16, 17, '2025-11-24', '2025-11-24 07:35:24', '2025-11-24 07:35:24'),
(17, 18, '2025-11-24', '2025-11-24 09:04:05', '2025-11-24 09:04:05'),
(18, 19, '2025-11-24', '2025-11-24 09:11:32', '2025-11-24 09:11:32'),
(19, 20, '2025-11-24', '2025-11-24 09:16:13', '2025-11-24 09:16:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'El arabito', 'compras al mayor', 'Proveedor@example.com', '04246746571', 'to do', '2025-03-09 05:49:39', '2025-03-10 06:18:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `cash_session_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` enum('USD','VES') NOT NULL,
  `exchange_rate` decimal(10,2) NOT NULL DEFAULT 1.00,
  `amount_usd_ref` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method_id` int(11) NOT NULL,
  `reference_type` enum('order','purchase','adjustment') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transactions`
--

INSERT INTO `transactions` (`id`, `cash_session_id`, `type`, `amount`, `currency`, `exchange_rate`, `amount_usd_ref`, `payment_method_id`, `reference_type`, `reference_id`, `description`, `created_by`, `created_at`) VALUES
(1, 1, 'income', 1.00, 'USD', 1.00, 1.00, 1, 'order', 48, 'Cobro Venta #48', 4, '2025-11-24 05:30:57'),
(2, 1, 'income', 100.00, 'VES', 100.00, 1.00, 2, 'order', 48, 'Cobro Venta #48', 4, '2025-11-24 05:30:57'),
(3, 1, 'income', 50.00, 'VES', 100.00, 0.50, 4, 'order', 48, 'Cobro Venta #48', 4, '2025-11-24 05:30:57'),
(4, 1, 'income', 50.00, 'VES', 100.00, 0.50, 5, 'order', 48, 'Cobro Venta #48', 4, '2025-11-24 05:30:57'),
(5, 1, 'income', 600.00, 'VES', 100.00, 6.00, 5, 'order', 49, 'Cobro Venta #49', 4, '2025-11-24 05:39:48'),
(6, 1, 'income', 150.00, 'VES', 100.00, 1.50, 2, 'order', 50, 'Cobro Venta #50', 4, '2025-11-24 05:40:56'),
(7, 2, 'income', 20.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 06:46:00'),
(8, 2, 'income', 5000.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 06:46:00'),
(9, 2, 'income', 5.00, 'USD', 1.00, 5.00, 1, 'order', 51, 'Cobro Venta #51', 4, '2025-11-24 06:47:57'),
(10, 2, 'income', 1500.00, 'VES', 100.00, 15.00, 2, 'order', 51, 'Cobro Venta #51', 4, '2025-11-24 06:47:57'),
(11, 2, 'income', 660.00, 'VES', 100.00, 6.60, 5, 'order', 51, 'Cobro Venta #51', 4, '2025-11-24 06:47:57'),
(12, 2, 'income', 600.00, 'VES', 100.00, 6.00, 2, 'order', 52, 'Cobro Venta #52', 4, '2025-11-24 06:48:27'),
(13, 2, 'income', 200.00, 'VES', 100.00, 2.00, 4, 'order', 52, 'Cobro Venta #52', 4, '2025-11-24 06:48:27'),
(14, 2, 'income', 3.60, 'USD', 1.00, 3.60, 1, 'order', 53, 'Cobro Venta #53', 4, '2025-11-24 06:49:12'),
(15, 2, 'income', 5.00, 'USD', 1.00, 5.00, 1, 'order', 54, 'Cobro Venta #54', 4, '2025-11-24 06:50:07'),
(16, 2, 'income', 5.00, 'USD', 1.00, 5.00, 1, 'order', 55, 'Cobro Venta #55', 4, '2025-11-24 06:51:08'),
(17, 2, 'income', 389.00, 'VES', 100.00, 3.89, 2, 'order', 55, 'Cobro Venta #55', 4, '2025-11-24 06:51:08'),
(18, 2, 'income', 1570.00, 'VES', 100.00, 15.70, 5, 'order', 56, 'Cobro Venta #56', 4, '2025-11-24 06:52:02'),
(19, 0, 'expense', 1500.00, 'VES', 100.00, 15.00, 2, 'purchase', 13, 'Pago de Compra #13 (Efectivo VES)', 4, '2025-11-24 07:20:01'),
(20, 0, 'expense', 420.00, 'VES', 100.00, 4.20, 2, 'purchase', 14, 'Pago de Compra #14 (Efectivo VES)', 4, '2025-11-24 07:24:04'),
(21, 0, 'expense', 660.00, 'VES', 100.00, 6.60, 2, 'purchase', 15, 'Pago de Compra #15 (Efectivo VES)', 4, '2025-11-24 07:26:33'),
(22, 0, 'expense', 300.00, 'VES', 100.00, 3.00, 2, 'purchase', 16, 'Pago de Compra #16 (Efectivo VES)', 4, '2025-11-24 07:32:36'),
(23, 0, 'expense', 300.00, 'VES', 100.00, 3.00, 2, 'purchase', 17, 'Pago de Compra #17 (Efectivo VES)', 4, '2025-11-24 07:35:03'),
(24, 0, 'expense', 250.00, 'VES', 100.00, 2.50, 2, 'purchase', 18, 'Pago de Compra #18 (Efectivo VES)', 4, '2025-11-24 09:02:02'),
(25, 0, 'expense', 300.00, 'VES', 100.00, 3.00, 2, 'purchase', 19, 'Pago de Compra #19 (Efectivo VES)', 4, '2025-11-24 09:10:01'),
(26, 0, 'expense', 480.00, 'VES', 100.00, 4.80, 2, 'purchase', 20, 'Pago de Compra #20 (Efectivo VES)', 4, '2025-11-24 09:14:54'),
(27, 3, 'income', 20.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 11:25:58'),
(28, 3, 'income', 2000.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 11:25:58'),
(29, 3, 'income', 5.00, 'USD', 1.00, 5.00, 1, 'order', 57, 'Cobro Venta #57', 4, '2025-11-24 11:31:28'),
(30, 3, 'income', 200.00, 'VES', 200.00, 1.00, 2, 'order', 57, 'Cobro Venta #57', 4, '2025-11-24 11:31:28'),
(31, 3, 'income', 3.00, 'USD', 1.00, 3.00, 3, 'order', 57, 'Cobro Venta #57', 4, '2025-11-24 11:31:28'),
(32, 3, 'income', 200.00, 'VES', 200.00, 1.00, 4, 'order', 57, 'Cobro Venta #57', 4, '2025-11-24 11:31:28'),
(33, 3, 'income', 244.00, 'VES', 200.00, 1.22, 5, 'order', 57, 'Cobro Venta #57', 4, '2025-11-24 11:31:28'),
(34, 4, 'income', 5.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 12:03:34'),
(35, 4, 'income', 1000.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 12:03:34'),
(36, 4, 'income', 1.00, 'USD', 1.00, 1.00, 1, 'order', 58, 'Cobro Venta #58', 4, '2025-11-24 12:04:49'),
(37, 4, 'income', 100.00, 'VES', 200.00, 0.50, 2, 'order', 58, 'Cobro Venta #58', 4, '2025-11-24 12:04:49'),
(38, 4, 'income', 1.00, 'USD', 1.00, 1.00, 3, 'order', 58, 'Cobro Venta #58', 4, '2025-11-24 12:04:49'),
(39, 4, 'income', 174.00, 'VES', 200.00, 0.87, 4, 'order', 58, 'Cobro Venta #58', 4, '2025-11-24 12:04:49'),
(40, 4, 'income', 100.00, 'VES', 200.00, 0.50, 5, 'order', 58, 'Cobro Venta #58', 4, '2025-11-24 12:04:49'),
(41, 5, 'income', 1.00, 'USD', 1.00, 1.00, 1, 'order', 59, 'Cobro Venta #59', 4, '2025-11-24 12:36:46'),
(42, 5, 'income', 200.00, 'VES', 200.00, 1.00, 2, 'order', 59, 'Cobro Venta #59', 4, '2025-11-24 12:36:46'),
(43, 5, 'income', 1.00, 'USD', 1.00, 1.00, 3, 'order', 59, 'Cobro Venta #59', 4, '2025-11-24 12:36:46'),
(44, 5, 'income', 174.00, 'VES', 200.00, 0.87, 4, 'order', 59, 'Cobro Venta #59', 4, '2025-11-24 12:36:46'),
(45, 6, 'income', 10.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 12:37:40'),
(46, 6, 'income', 1000.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 12:37:40'),
(47, 6, 'income', 1.00, 'USD', 1.00, 1.00, 1, 'order', 60, 'Cobro Venta #60', 4, '2025-11-24 12:39:13'),
(48, 6, 'income', 200.00, 'VES', 200.00, 1.00, 2, 'order', 60, 'Cobro Venta #60', 4, '2025-11-24 12:39:13'),
(49, 6, 'income', 1.00, 'USD', 1.00, 1.00, 3, 'order', 60, 'Cobro Venta #60', 4, '2025-11-24 12:39:13'),
(50, 6, 'income', 174.00, 'VES', 200.00, 0.87, 4, 'order', 60, 'Cobro Venta #60', 4, '2025-11-24 12:39:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `document_id` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `profile_pic` varchar(255) DEFAULT 'default.jpg',
  `balance` decimal(10,2) DEFAULT 0.00,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `document_id`, `address`, `role`, `profile_pic`, `balance`, `reset_token`, `token_expiry`, `created_at`, `updated_at`) VALUES
(4, 'roberto', 'robertopv100@gmail.com', '$2y$10$7GPIEd7LC.leGjt7EgKNvOQ/J7Ht.J2gjOC8njG51PAefmEGWa4bq', '04246746570', 'v-19451788', 'asdasdasd12', 'admin', 'default.jpg', 0.00, NULL, NULL, '2025-02-23 23:37:13', '2025-03-10 06:18:16'),
(6, 'Alejandro ', 'usuario@example.com', '$2y$10$A7PaXa4J80agTrYXSbn0pOhIEVH1eL.L2f7zq8GNg7czjf34VpNe6', '04245555555', '12345678', 'Dhshdhdjshdj', 'user', 'default.jpg', 0.00, NULL, NULL, '2025-03-10 00:45:30', '2025-03-10 06:18:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vault_movements`
--

CREATE TABLE `vault_movements` (
  `id` int(11) NOT NULL,
  `type` enum('deposit','withdrawal') NOT NULL,
  `origin` enum('session_close','manual_deposit','supplier_payment','owner_withdrawal') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` enum('USD','VES') NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vault_movements`
--

INSERT INTO `vault_movements` (`id`, `type`, `origin`, `amount`, `currency`, `description`, `reference_id`, `created_by`, `created_at`) VALUES
(1, 'deposit', 'session_close', 58.00, 'USD', 'Cierre de Caja #2', 2, 4, '2025-11-24 06:54:20'),
(2, 'deposit', 'session_close', 12489.00, 'VES', 'Cierre de Caja #2', 2, 4, '2025-11-24 06:54:20'),
(3, 'withdrawal', 'owner_withdrawal', 10.00, 'USD', 'pago de mercancía', NULL, 4, '2025-11-24 07:01:49'),
(4, 'deposit', 'manual_deposit', 100.00, 'USD', 'inversión de capital', NULL, 4, '2025-11-24 07:03:35'),
(5, 'withdrawal', 'supplier_payment', 1500.00, 'VES', 'Pago a Proveedor - Compra #13', 13, 4, '2025-11-24 07:20:01'),
(6, 'withdrawal', 'supplier_payment', 420.00, 'VES', 'Pago a Proveedor - Compra #14', 14, 4, '2025-11-24 07:24:04'),
(7, 'withdrawal', 'supplier_payment', 660.00, 'VES', 'Pago a Proveedor - Compra #15', 15, 4, '2025-11-24 07:26:33'),
(8, 'withdrawal', 'supplier_payment', 300.00, 'VES', 'Pago a Proveedor - Compra #16', 16, 4, '2025-11-24 07:32:36'),
(9, 'withdrawal', 'supplier_payment', 300.00, 'VES', 'Pago a Proveedor - Compra #17', 17, 4, '2025-11-24 07:35:03'),
(10, 'withdrawal', 'supplier_payment', 250.00, 'VES', 'Pago a Proveedor - Compra #18', 18, 4, '2025-11-24 09:02:02'),
(11, 'withdrawal', 'supplier_payment', 300.00, 'VES', 'Pago a Proveedor - Compra #19', 19, 4, '2025-11-24 09:10:01'),
(12, 'withdrawal', 'supplier_payment', 480.00, 'VES', 'Pago a Proveedor - Compra #20', 20, 4, '2025-11-24 09:14:54'),
(13, 'deposit', 'session_close', 45.00, 'USD', 'Cierre de Caja #3', 3, 4, '2025-11-24 11:32:10'),
(14, 'deposit', 'session_close', 4200.00, 'VES', 'Cierre de Caja #3', 3, 4, '2025-11-24 11:32:10'),
(15, 'deposit', 'session_close', 6.00, 'USD', 'Cierre de Caja #4', 4, 4, '2025-11-24 12:13:32'),
(16, 'deposit', 'session_close', 1100.00, 'VES', 'Cierre de Caja #4', 4, 4, '2025-11-24 12:13:32'),
(17, 'deposit', 'session_close', 1.00, 'USD', 'Cierre de Caja #5', 5, 4, '2025-11-24 12:37:11'),
(18, 'deposit', 'session_close', 200.00, 'VES', 'Cierre de Caja #5', 5, 4, '2025-11-24 12:37:11'),
(19, 'deposit', 'session_close', 11.00, 'USD', 'Cierre de Caja #6', 6, 4, '2025-11-24 12:39:33'),
(20, 'deposit', 'session_close', 1200.00, 'VES', 'Cierre de Caja #6', 6, 4, '2025-11-24 12:39:33');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `cash_sessions`
--
ALTER TABLE `cash_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `company_vault`
--
ALTER TABLE `company_vault`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `global_config`
--
ALTER TABLE `global_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`),
  ADD UNIQUE KEY `config_key_2` (`config_key`),
  ADD UNIQUE KEY `idx_config_key` (`config_key`);

--
-- Indices de la tabla `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indices de la tabla `menu_roles`
--
ALTER TABLE `menu_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indices de la tabla `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_items_ibfk_1` (`purchase_order_id`),
  ADD KEY `purchase_order_items_ibfk_2` (`product_id`);

--
-- Indices de la tabla `purchase_receipts`
--
ALTER TABLE `purchase_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- Indices de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cash_session_id` (`cash_session_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `document_id` (`document_id`);

--
-- Indices de la tabla `vault_movements`
--
ALTER TABLE `vault_movements`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de la tabla `cash_sessions`
--
ALTER TABLE `cash_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `company_vault`
--
ALTER TABLE `company_vault`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `global_config`
--
ALTER TABLE `global_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `menu_roles`
--
ALTER TABLE `menu_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT de la tabla `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `purchase_receipts`
--
ALTER TABLE `purchase_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `vault_movements`
--
ALTER TABLE `vault_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `menu_roles`
--
ALTER TABLE `menu_roles`
  ADD CONSTRAINT `menu_roles_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Filtros para la tabla `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `purchase_receipts`
--
ALTER TABLE `purchase_receipts`
  ADD CONSTRAINT `purchase_receipts_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
