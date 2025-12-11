-- phpMyAdmin SQL Dump
-- version 5.2.2-1.fc42
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 11-12-2025 a las 13:03:57
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
-- Estructura de tabla para la tabla `accounts_receivable`
--

CREATE TABLE `accounts_receivable` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Si es empleado',
  `amount` decimal(20,6) NOT NULL,
  `paid_amount` decimal(20,6) DEFAULT 0.000000,
  `status` enum('pending','partial','paid','deducted') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `accounts_receivable`
--

INSERT INTO `accounts_receivable` (`id`, `order_id`, `client_id`, `user_id`, `amount`, `paid_amount`, `status`, `due_date`, `notes`, `created_at`) VALUES
(4, 83, 1, NULL, 60.000000, 60.000000, 'paid', NULL, 'Autorizado por Admin. Ref: 2025-12-10 17:13', '2025-12-10 17:13:55'),
(5, 85, 9, NULL, 100.000000, 0.000000, 'pending', NULL, 'Test Init', '2025-12-11 12:19:17'),
(6, 86, 10, NULL, 100.000000, 0.000000, 'pending', NULL, 'Test Init', '2025-12-11 12:20:54'),
(7, 87, 11, NULL, 100.000000, 0.000000, 'pending', NULL, 'Test Init', '2025-12-11 12:21:31'),
(8, 88, 12, NULL, 100.000000, 0.000000, 'pending', NULL, 'Test Init', '2025-12-11 12:24:47'),
(9, 89, 13, NULL, 100.000000, 45.000000, 'partial', NULL, 'Test Init', '2025-12-11 12:25:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `consumption_type` enum('dine_in','takeaway','delivery') DEFAULT 'dine_in'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart_item_modifiers`
--

CREATE TABLE `cart_item_modifiers` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `modifier_type` enum('add','remove','info') NOT NULL,
  `raw_material_id` int(11) DEFAULT NULL,
  `quantity_adjustment` decimal(10,4) DEFAULT 0.0000,
  `price_adjustment` decimal(20,6) DEFAULT 0.000000,
  `note` varchar(255) DEFAULT NULL,
  `sub_item_index` int(11) DEFAULT 0,
  `is_takeaway` tinyint(1) DEFAULT 0
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `cash_sessions`
--

INSERT INTO `cash_sessions` (`id`, `user_id`, `opening_balance_usd`, `opening_balance_ves`, `closing_balance_usd`, `closing_balance_ves`, `calculated_usd`, `calculated_ves`, `status`, `opened_at`, `closed_at`) VALUES
(1, 4, 0.00, 0.00, 1.00, 250.00, 1.00, 250.00, 'closed', '2025-11-24 04:33:13', '2025-11-24 06:06:26'),
(2, 4, 20.00, 5000.00, 38.00, 7489.00, 38.60, 7489.00, 'closed', '2025-11-24 06:46:00', '2025-11-24 06:54:20'),
(3, 4, 20.00, 2000.00, 25.00, 2200.00, 25.00, 2200.00, 'closed', '2025-11-24 11:25:58', '2025-11-24 11:32:10'),
(4, 4, 5.00, 1000.00, 6.00, 1100.00, 6.00, 1100.00, 'closed', '2025-11-24 12:03:34', '2025-11-24 12:13:32'),
(5, 4, 0.00, 0.00, 1.00, 200.00, 1.00, 200.00, 'closed', '2025-11-24 12:35:56', '2025-11-24 12:37:11'),
(6, 4, 10.00, 1000.00, 11.00, 1200.00, 11.00, 1200.00, 'closed', '2025-11-24 12:37:40', '2025-11-24 12:39:33'),
(7, 4, 5.00, 500.00, 5.00, 500.00, 5.00, 500.00, 'closed', '2025-11-24 15:27:35', '2025-11-24 15:38:45'),
(8, 4, 5.00, 350.00, 5.00, 350.00, 5.00, 350.00, 'closed', '2025-11-24 20:07:59', '2025-11-24 20:10:09'),
(9, 4, 5.00, 500.00, 6.00, 890.00, 6.00, 900.00, 'closed', '2025-11-24 21:35:45', '2025-11-24 21:46:30'),
(10, 4, 5.00, 500.00, 6.00, 990.00, 6.00, 1000.00, 'closed', '2025-11-24 23:40:21', '2025-11-24 23:48:05'),
(11, 7, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'open', '2025-11-25 00:12:55', NULL),
(12, 4, 0.00, 0.00, 196.00, 2500.00, 196.00, 2500.00, 'closed', '2025-11-29 04:43:44', '2025-12-02 07:11:31'),
(13, 21, 100.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'open', '2025-12-10 12:03:29', NULL),
(16, 4, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'open', '2025-12-10 13:55:02', NULL),
(17, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'open', '2025-12-11 12:19:17', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `document_id` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `credit_limit` decimal(20,6) DEFAULT 0.000000,
  `current_debt` decimal(20,6) DEFAULT 0.000000,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `clients`
--

INSERT INTO `clients` (`id`, `name`, `document_id`, `phone`, `email`, `address`, `credit_limit`, `current_debt`, `created_at`) VALUES
(1, 'Cliente Test 69395d01cac5f', 'DOC-69395d01cac5f', '555', '', 'Test Addr', 100.000000, 30.000000, '2025-12-10 11:44:01'),
(2, 'Cliente Test 69395d14b4495', 'DOC-69395d14b4495', '555', NULL, 'Test Addr', 100.000000, 0.000000, '2025-12-10 11:44:20'),
(3, 'Cliente Test 69395d27444bd', 'DOC-69395d27444bd', '555', NULL, 'Test Addr', 100.000000, 0.000000, '2025-12-10 11:44:39'),
(4, 'Cliente Test 69395d388742e', 'DOC-69395d388742e', '555', NULL, 'Test Addr', 100.000000, 0.000000, '2025-12-10 11:44:56'),
(5, 'Cliente Test 69395d516097b', 'DOC-69395d516097b', '555', NULL, 'Test Addr', 100.000000, 0.000000, '2025-12-10 11:45:21'),
(8, 'Client Test 693ab6a6aec5b', 'DOC-693ab6a6aec5b', '555', NULL, 'Test Addr', 100.000000, 0.000000, '2025-12-11 12:18:46'),
(9, 'Client Test 693ab6c5961ca', 'DOC-693ab6c5961ca', '555', NULL, 'Test Addr', 100.000000, 100.000000, '2025-12-11 12:19:17'),
(10, 'Client Test 693ab7263bf0b', 'DOC-693ab7263bf0b', '555', NULL, 'Test Addr', 100.000000, 100.000000, '2025-12-11 12:20:54'),
(11, 'Client Test 693ab74b41005', 'DOC-693ab74b41005', '555', NULL, 'Test Addr', 100.000000, 100.000000, '2025-12-11 12:21:31'),
(12, 'Client Test 693ab80f082aa', 'DOC-693ab80f082aa', '555', NULL, 'Test Addr', 100.000000, 100.000000, '2025-12-11 12:24:47'),
(13, 'Client Test 693ab8559eac4', 'DOC-693ab8559eac4', '555', NULL, 'Test Addr', 100.000000, 55.000000, '2025-12-11 12:25:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_vault`
--

CREATE TABLE `company_vault` (
  `id` int(11) NOT NULL,
  `balance_usd` decimal(12,2) DEFAULT 0.00,
  `balance_ves` decimal(12,2) DEFAULT 0.00,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `company_vault`
--

INSERT INTO `company_vault` (`id`, `balance_usd`, `balance_ves`, `last_updated`) VALUES
(1, 339.00, 12349.00, '2025-12-10 14:45:36');

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
(1, 'site_name', 'PASTELERIA & PIZZERIA J&Y', 'Nombre de la plataforma', '2025-02-21 12:14:27', '2025-12-01 21:44:01'),
(2, 'site_url', 'https://www.miplataforma.com', 'URL de la web', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(3, 'default_language', 'es', 'Idioma predeterminado del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(4, 'timezone', 'America/Caracas', 'Zona horaria del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(5, 'currency', 'USD', 'Moneda predeterminada del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(6, 'exchange_rate', '300', 'Tasa de cambio USD-VES', '2025-02-21 12:14:27', '2025-11-24 23:35:58'),
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
-- Estructura de tabla para la tabla `manufactured_products`
--

CREATE TABLE `manufactured_products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'und',
  `stock` decimal(20,6) DEFAULT 0.000000,
  `unit_cost_average` decimal(20,6) DEFAULT 0.000000,
  `last_production_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `manufactured_products`
--

INSERT INTO `manufactured_products` (`id`, `name`, `unit`, `stock`, `unit_cost_average`, `last_production_date`) VALUES
(1, 'Masa de Pizza (Granel)', 'kg', 16.500000, 1.020000, '2025-11-28 20:55:23'),
(2, 'Salsa Napolitana Lista', 'lt', 3.000000, 3.350000, '2025-11-28 21:11:43'),
(3, 'Tequeño Crudo', 'und', 250.000000, 0.140000, '2025-11-28 21:12:04'),
(4, 'Carne Hamburguesa (150g)', 'und', 120.000000, 0.850000, '2025-11-28 23:18:12'),
(5, 'Empanada de Carne (Cruda)', 'und', 100.000000, 0.300000, '2025-11-28 21:56:13'),
(6, 'Empanada de Queso (Cruda)', 'und', 50.000000, 0.270000, '2025-11-28 21:32:36'),
(7, 'Pastel de Carne (Crudo)', 'und', 300.000000, 0.260000, '2025-11-28 21:14:06'),
(8, 'Pastel de Queso (Crudo)', 'und', 300.000000, 0.240000, '2025-11-28 21:13:50'),
(9, 'Pastel de Papa con Queso (Crudo)', 'und', 300.000000, 0.190000, '2025-11-28 21:13:11'),
(10, 'Tequeyoyo Crudo', 'und', 100.000000, 0.510000, '2025-11-28 21:12:13'),
(11, 'Papita de Yuca Cruda', 'und', 50.000000, 0.190000, '2025-11-28 21:11:10'),
(12, 'Salsa de Ajo Casera', 'lt', 4.000000, 3.860000, '2025-11-28 21:12:48'),
(13, 'Salsa Tártara Casera', 'lt', 4.000000, 3.550000, '2025-11-28 21:12:27'),
(14, 'Salsa de Maíz Casera', 'lt', 4.000000, 3.900000, '2025-11-28 21:12:39'),
(15, 'Arepa Base (Viuda)', 'und', 50.000000, 0.120000, '2025-11-28 20:54:45'),
(16, 'Mezcla para Rebozar (Tumbarrancho)', 'lt', 4.000000, 0.470000, '2025-11-28 21:10:47');

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
  `status` enum('pending','paid','preparing','ready','delivered','cancelled') DEFAULT 'pending',
  `consumption_type` enum('dine_in','takeaway','delivery') DEFAULT 'dine_in',
  `shipping_address` text NOT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `status`, `consumption_type`, `shipping_address`, `shipping_method`, `tracking_number`, `created_at`, `updated_at`) VALUES
(21, 4, 0.80, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-21 23:13:49', '2025-11-29 04:51:26'),
(22, 4, 0.80, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-21 23:14:22', '2025-11-29 04:52:47'),
(23, 4, 1.20, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-21 23:14:29', '2025-11-29 04:52:50'),
(24, 4, 0.66, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-24 00:24:00', '2025-11-29 04:52:51'),
(25, 4, 0.17, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-24 00:25:20', '2025-11-29 04:52:51'),
(28, 4, 0.24, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-24 00:25:28', '2025-11-29 04:52:52'),
(29, 4, 1.20, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-24 00:25:38', '2025-11-29 04:52:52'),
(30, 4, 0.12, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 16:28:06', '2025-11-29 04:52:53'),
(31, 4, 0.10, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 16:28:22', '2025-11-29 04:52:53'),
(32, 4, 0.40, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 17:03:30', '2025-11-29 04:52:54'),
(33, 4, 1.00, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 17:20:19', '2025-11-29 04:52:54'),
(34, 4, 1.23, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 17:56:09', '2025-11-29 04:52:55'),
(35, 4, 0.13, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 18:18:17', '2025-11-29 04:52:55'),
(36, 4, 1.30, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 18:41:32', '2025-11-29 04:52:56'),
(37, 4, 1.00, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 18:48:21', '2025-11-29 04:52:56'),
(38, 4, 2.32, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-09 23:14:38', '2025-11-29 04:52:57'),
(39, 4, 1.20, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-10 00:37:34', '2025-11-29 04:52:57'),
(40, 4, 0.60, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-03-14 23:41:19', '2025-11-29 04:52:58'),
(41, 4, 0.60, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-21 23:18:47', '2025-11-29 04:52:58'),
(42, 4, 3.10, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-24 04:35:06', '2025-11-29 04:52:59'),
(43, 4, 1.50, 'delivered', 'takeaway', 'asdasdasd12', NULL, NULL, '2025-11-24 04:49:28', '2025-11-29 04:52:59'),
(48, 4, 3.00, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 05:30:57', '2025-11-29 04:53:00'),
(49, 4, 6.00, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 05:39:48', '2025-11-29 04:53:00'),
(50, 4, 1.50, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 05:40:56', '2025-11-29 04:53:01'),
(51, 4, 26.60, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 06:47:57', '2025-11-29 04:53:01'),
(52, 4, 8.00, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 06:48:27', '2025-11-29 04:53:01'),
(53, 4, 3.60, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 06:49:11', '2025-11-29 04:53:02'),
(54, 4, 5.00, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 06:50:07', '2025-11-29 04:53:02'),
(55, 4, 8.89, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 06:51:08', '2025-11-29 04:53:03'),
(56, 4, 15.70, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 06:52:02', '2025-11-29 04:53:05'),
(57, 4, 11.22, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 11:31:28', '2025-11-29 04:53:05'),
(58, 4, 3.87, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 12:04:49', '2025-11-29 04:53:06'),
(59, 4, 3.87, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 12:36:46', '2025-11-29 04:53:07'),
(60, 4, 3.87, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 12:39:13', '2025-11-29 04:53:08'),
(61, 4, 3.82, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 15:37:37', '2025-11-29 04:53:10'),
(62, 4, 3.27, 'delivered', 'takeaway', 'Tienda Física', NULL, NULL, '2025-11-24 21:43:03', '2025-11-29 04:53:58'),
(63, 4, 5.06, 'delivered', 'takeaway', 'Tienda Física', NULL, '', '2025-11-24 23:44:45', '2025-11-29 04:53:59'),
(65, 4, 17.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-11-30 06:06:23', '2025-11-30 15:31:54'),
(66, 4, 15.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-11-30 15:32:27', '2025-11-30 15:33:44'),
(67, 4, 24.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-11-30 15:34:33', '2025-12-01 21:40:07'),
(68, 4, 8.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-01 21:37:11', '2025-12-01 21:41:02'),
(69, 4, 15.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-01 21:44:40', '2025-12-02 01:25:41'),
(70, 4, 47.00, 'delivered', 'dine_in', 'Tienda Física', NULL, '', '2025-12-02 00:43:21', '2025-12-02 01:25:27'),
(71, 4, 15.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-02 05:19:43', '2025-12-02 06:58:44'),
(72, 4, 8.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-02 05:48:00', '2025-12-02 06:59:05'),
(73, 4, 15.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-02 05:57:28', '2025-12-02 06:59:08'),
(74, 4, 15.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-02 06:06:20', '2025-12-02 06:59:15'),
(75, 4, 15.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-02 06:38:04', '2025-12-02 06:59:26'),
(76, 4, 17.00, 'delivered', 'dine_in', 'Tienda Física', NULL, '', '2025-12-02 07:01:32', '2025-12-02 07:12:39'),
(80, 4, 15.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-10 13:56:22', '2025-12-10 17:18:39'),
(83, 4, 60.00, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2025-12-10 17:13:55', '2025-12-10 17:13:55'),
(85, 21, 100.00, 'delivered', 'dine_in', 'Test Address', NULL, NULL, '2025-12-11 12:19:17', NULL),
(86, 21, 100.00, 'delivered', 'dine_in', 'Test Address', NULL, NULL, '2025-12-11 12:20:54', NULL),
(87, 21, 100.00, 'delivered', 'dine_in', 'Test Address', NULL, NULL, '2025-12-11 12:21:31', NULL),
(88, 21, 100.00, 'delivered', 'dine_in', 'Test Address', NULL, NULL, '2025-12-11 12:24:47', NULL),
(89, 21, 100.00, 'delivered', 'dine_in', 'Test Address', NULL, NULL, '2025-12-11 12:25:57', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `consumption_type` enum('dine_in','takeaway','delivery') DEFAULT 'dine_in'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `consumption_type`) VALUES
(21, 21, 46, 1, 0.80, 'takeaway'),
(23, 22, 46, 1, 0.80, 'takeaway'),
(25, 23, 28, 1, 1.20, 'takeaway'),
(27, 24, 26, 1, 0.66, 'takeaway'),
(29, 25, 48, 1, 0.17, 'takeaway'),
(34, 28, 42, 8, 0.03, 'takeaway'),
(35, 29, 25, 1, 1.20, 'takeaway'),
(36, 30, 42, 4, 0.03, 'takeaway'),
(37, 31, 19, 1, 0.10, 'takeaway'),
(38, 32, 33, 1, 0.40, 'takeaway'),
(39, 33, 47, 1, 1.00, 'takeaway'),
(40, 34, 24, 1, 1.20, 'takeaway'),
(41, 34, 42, 1, 0.03, 'takeaway'),
(42, 35, 38, 1, 0.13, 'takeaway'),
(43, 36, 20, 10, 0.13, 'takeaway'),
(44, 37, 47, 1, 1.00, 'takeaway'),
(45, 38, 47, 1, 1.00, 'takeaway'),
(46, 38, 42, 4, 0.03, 'takeaway'),
(47, 38, 28, 1, 1.20, 'takeaway'),
(48, 39, 16, 2, 0.60, 'takeaway'),
(49, 40, 16, 1, 0.60, 'takeaway'),
(50, 41, 16, 1, 0.60, 'takeaway'),
(51, 42, 41, 2, 0.80, 'takeaway'),
(52, 42, 4, 1, 1.50, 'takeaway'),
(53, 43, 4, 1, 1.50, 'takeaway'),
(58, 48, 4, 2, 1.50, 'takeaway'),
(59, 49, 41, 1, 0.80, 'takeaway'),
(60, 49, 4, 1, 1.50, 'takeaway'),
(61, 49, 32, 1, 0.60, 'takeaway'),
(62, 49, 21, 1, 1.00, 'takeaway'),
(63, 49, 16, 1, 0.60, 'takeaway'),
(64, 49, 24, 1, 1.20, 'takeaway'),
(65, 49, 19, 3, 0.10, 'takeaway'),
(66, 50, 4, 1, 1.50, 'takeaway'),
(67, 51, 47, 10, 1.20, 'takeaway'),
(68, 51, 21, 5, 1.00, 'takeaway'),
(69, 51, 24, 8, 1.20, 'takeaway'),
(70, 52, 21, 8, 1.00, 'takeaway'),
(71, 53, 25, 3, 1.20, 'takeaway'),
(72, 54, 27, 20, 0.25, 'takeaway'),
(73, 55, 30, 7, 1.27, 'takeaway'),
(74, 56, 17, 10, 1.57, 'takeaway'),
(75, 57, 17, 1, 1.57, 'takeaway'),
(76, 57, 30, 2, 1.27, 'takeaway'),
(77, 57, 27, 1, 0.25, 'takeaway'),
(78, 57, 25, 1, 1.20, 'takeaway'),
(79, 57, 24, 1, 1.20, 'takeaway'),
(80, 57, 4, 1, 1.80, 'takeaway'),
(81, 57, 19, 3, 0.10, 'takeaway'),
(82, 57, 28, 1, 1.20, 'takeaway'),
(83, 57, 26, 1, 0.66, 'takeaway'),
(84, 57, 18, 1, 0.50, 'takeaway'),
(85, 58, 4, 1, 1.80, 'takeaway'),
(86, 58, 17, 1, 1.57, 'takeaway'),
(87, 58, 18, 1, 0.50, 'takeaway'),
(88, 59, 4, 1, 1.80, 'takeaway'),
(89, 59, 17, 1, 1.57, 'takeaway'),
(90, 59, 18, 1, 0.50, 'takeaway'),
(91, 60, 4, 1, 1.80, 'takeaway'),
(92, 60, 17, 1, 1.57, 'takeaway'),
(93, 60, 18, 1, 0.50, 'takeaway'),
(94, 61, 4, 1, 1.80, 'takeaway'),
(95, 61, 19, 1, 0.10, 'takeaway'),
(96, 61, 34, 2, 0.20, 'takeaway'),
(97, 61, 27, 1, 0.25, 'takeaway'),
(98, 61, 30, 1, 1.27, 'takeaway'),
(99, 62, 34, 1, 0.20, 'takeaway'),
(100, 62, 4, 1, 1.80, 'takeaway'),
(101, 62, 30, 1, 1.27, 'takeaway'),
(102, 63, 4, 1, 1.80, 'takeaway'),
(103, 63, 14, 3, 0.40, 'takeaway'),
(104, 63, 19, 2, 0.10, 'takeaway'),
(105, 63, 28, 1, 1.20, 'takeaway'),
(106, 63, 26, 1, 0.66, 'takeaway'),
(108, 65, 88, 1, 17.00, 'dine_in'),
(109, 66, 88, 1, 15.00, 'dine_in'),
(110, 67, 90, 1, 9.00, 'dine_in'),
(111, 67, 88, 1, 15.00, 'dine_in'),
(112, 68, 89, 1, 8.00, 'dine_in'),
(113, 69, 88, 1, 15.00, 'dine_in'),
(114, 70, 87, 1, 15.00, 'dine_in'),
(115, 70, 88, 1, 15.00, 'dine_in'),
(116, 70, 89, 1, 8.00, 'dine_in'),
(117, 70, 90, 1, 9.00, 'dine_in'),
(118, 71, 88, 1, 15.00, 'dine_in'),
(119, 72, 89, 1, 8.00, 'dine_in'),
(120, 73, 88, 1, 15.00, 'dine_in'),
(121, 74, 88, 1, 15.00, 'dine_in'),
(122, 75, 88, 1, 15.00, 'dine_in'),
(123, 76, 88, 1, 17.00, 'dine_in'),
(124, 80, 88, 1, 15.00, 'dine_in'),
(127, 83, 88, 4, 15.00, 'dine_in');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_item_modifiers`
--

CREATE TABLE `order_item_modifiers` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `modifier_type` enum('add','remove','info') NOT NULL,
  `raw_material_id` int(11) DEFAULT NULL,
  `quantity_adjustment` decimal(10,4) DEFAULT 0.0000,
  `price_adjustment_usd` decimal(20,6) DEFAULT 0.000000,
  `note` varchar(255) DEFAULT NULL,
  `sub_item_index` int(11) DEFAULT 0,
  `is_takeaway` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `order_item_modifiers`
--

INSERT INTO `order_item_modifiers` (`id`, `order_item_id`, `modifier_type`, `raw_material_id`, `quantity_adjustment`, `price_adjustment_usd`, `note`, `sub_item_index`, `is_takeaway`) VALUES
(1, 108, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0),
(2, 108, 'add', 42, 0.0500, 1.000000, NULL, 0, 0),
(3, 108, 'info', NULL, 0.0000, 0.000000, NULL, 1, 1),
(4, 108, 'add', 12, 0.0500, 1.000000, NULL, 1, 0),
(5, 108, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0),
(6, 108, 'remove', 16, 0.0000, 0.000000, NULL, 2, 0),
(8, 123, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0),
(9, 123, 'add', 42, 0.0500, 1.000000, NULL, 0, 0),
(10, 123, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0),
(11, 123, 'add', 12, 0.0500, 1.000000, NULL, 1, 0),
(12, 123, 'info', NULL, 0.0000, 0.000000, NULL, 2, 1),
(13, 123, 'remove', 16, 0.0000, 0.000000, NULL, 2, 0);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

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
-- Estructura de tabla para la tabla `payroll_payments`
--

CREATE TABLE `payroll_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(20,6) NOT NULL,
  `deductions_amount` decimal(20,6) DEFAULT 0.000000,
  `payment_date` date NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `payment_method_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `payroll_payments`
--

INSERT INTO `payroll_payments` (`id`, `user_id`, `amount`, `deductions_amount`, `payment_date`, `period_start`, `period_end`, `payment_method_id`, `transaction_id`, `notes`, `created_by`, `created_at`) VALUES
(18, 11, 30.000000, 0.000000, '2025-12-10', NULL, NULL, 2, 115, '', 4, '2025-12-10 14:32:54'),
(19, 10, 30.000000, 0.000000, '2025-12-10', NULL, NULL, 1, 116, '', 4, '2025-12-10 14:33:04'),
(20, 9, 30.000000, 0.000000, '2025-12-10', NULL, NULL, 5, 117, '', 4, '2025-12-10 14:33:15'),
(21, 4, 30.000000, 0.000000, '2025-12-10', NULL, NULL, 4, 118, '', 4, '2025-12-10 14:33:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `production_orders`
--

CREATE TABLE `production_orders` (
  `id` int(11) NOT NULL,
  `manufactured_product_id` int(11) NOT NULL,
  `quantity_produced` decimal(10,4) NOT NULL,
  `labor_cost` decimal(20,6) DEFAULT 0.000000,
  `total_cost` decimal(20,6) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `production_orders`
--

INSERT INTO `production_orders` (`id`, `manufactured_product_id`, `quantity_produced`, `labor_cost`, `total_cost`, `created_by`, `created_at`) VALUES
(1, 15, 50.0000, 0.000000, 6.000000, 4, '2025-11-29 00:54:45'),
(2, 1, 20.0000, 0.000000, 20.400000, 4, '2025-11-29 00:55:23'),
(3, 4, 30.0000, 0.000000, 25.350000, 4, '2025-11-29 01:10:12'),
(4, 16, 4.0000, 0.000000, 1.880000, 4, '2025-11-29 01:10:47'),
(5, 11, 50.0000, 0.000000, 9.650000, 4, '2025-11-29 01:11:10'),
(6, 2, 4.0000, 0.000000, 13.380000, 4, '2025-11-29 01:11:43'),
(7, 3, 250.0000, 0.000000, 35.000000, 4, '2025-11-29 01:12:04'),
(8, 10, 100.0000, 0.000000, 50.600000, 4, '2025-11-29 01:12:13'),
(9, 13, 4.0000, 0.000000, 14.200000, 4, '2025-11-29 01:12:27'),
(10, 14, 4.0000, 0.000000, 15.600000, 4, '2025-11-29 01:12:39'),
(11, 12, 4.0000, 0.000000, 15.440000, 4, '2025-11-29 01:12:48'),
(12, 9, 300.0000, 0.000000, 57.000000, 4, '2025-11-29 01:13:11'),
(13, 8, 300.0000, 0.000000, 70.950000, 4, '2025-11-29 01:13:50'),
(14, 7, 300.0000, 0.000000, 79.200000, 4, '2025-11-29 01:14:06'),
(15, 5, 50.0000, 0.000000, 14.750000, 4, '2025-11-29 01:32:19'),
(16, 6, 50.0000, 0.000000, 13.380000, 4, '2025-11-29 01:32:36'),
(17, 5, 50.0000, 0.000000, 14.750000, 4, '2025-11-29 01:56:13'),
(18, 4, 50.0000, 0.000000, 42.250000, 4, '2025-11-29 02:34:38'),
(19, 4, 20.0000, 0.000000, 16.900000, 4, '2025-11-29 03:16:41'),
(20, 4, 20.0000, 0.000000, 16.900000, 4, '2025-11-29 03:18:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `production_recipes`
--

CREATE TABLE `production_recipes` (
  `id` int(11) NOT NULL,
  `manufactured_product_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `quantity_required` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `production_recipes`
--

INSERT INTO `production_recipes` (`id`, `manufactured_product_id`, `raw_material_id`, `quantity_required`) VALUES
(1, 1, 5, 0.6000),
(2, 1, 6, 0.0100),
(3, 1, 9, 0.0500),
(4, 1, 8, 0.0200),
(5, 2, 22, 1.5000),
(6, 2, 23, 0.1500),
(7, 2, 28, 0.0300),
(8, 3, 13, 0.0150),
(9, 3, 5, 0.0250),
(10, 3, 9, 0.0050),
(11, 4, 19, 0.1500),
(12, 4, 28, 0.0050),
(13, 5, 48, 0.0500),
(14, 5, 19, 0.0400),
(15, 6, 48, 0.0500),
(16, 6, 13, 0.0350),
(17, 7, 5, 0.0400),
(18, 7, 19, 0.0400),
(19, 8, 5, 0.0400),
(20, 8, 13, 0.0350),
(21, 9, 5, 0.0400),
(22, 9, 49, 0.0300),
(23, 9, 13, 0.0200),
(24, 10, 5, 0.0600),
(25, 10, 13, 0.0400),
(26, 10, 17, 0.0300),
(27, 10, 50, 0.0400),
(28, 11, 51, 0.0600),
(29, 11, 13, 0.0250),
(30, 11, 21, 0.0500),
(31, 12, 31, 0.9000),
(32, 12, 28, 0.0500),
(33, 12, 56, 0.0200),
(34, 14, 31, 0.8000),
(35, 14, 57, 0.2000),
(36, 13, 31, 0.8500),
(37, 13, 23, 0.1000),
(38, 15, 48, 0.0800),
(39, 16, 5, 0.3000),
(40, 16, 21, 0.1000),
(41, 16, 32, 0.0500);

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
  `product_type` enum('simple','compound','prepared') DEFAULT 'simple',
  `kitchen_station` enum('pizza','kitchen','bar') DEFAULT 'kitchen',
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `profit_margin` decimal(5,2) NOT NULL DEFAULT 20.00,
  `updated_at` timestamp NULL DEFAULT NULL,
  `linked_manufactured_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price_usd`, `price_ves`, `stock`, `product_type`, `kitchen_station`, `image_url`, `created_at`, `profit_margin`, `updated_at`, `linked_manufactured_id`) VALUES
(4, 'Time doble click', '', 1.80, 540.00, 17, 'simple', 'bar', 'uploads/product_images/product3.jpg', '2025-11-24 23:44:45', 50.00, '2025-11-29 03:41:03', NULL),
(10, 'Trululu Aros', '', 0.04, 12.00, 100, 'simple', 'bar', 'uploads/product_images/product1.jpg', '2025-03-01 00:05:26', 20.00, '2025-11-29 03:41:03', NULL),
(14, 'Pañales', '', 0.40, 120.00, 27, 'simple', 'bar', 'uploads/product_images/67cd4d754632a_1733533014_IMG-20241114-WA0006 (1).jpg', '2025-11-24 23:44:45', 20.00, '2025-11-29 03:41:03', NULL),
(15, 'Champu', '', 0.33, 99.00, 16, 'simple', 'bar', 'uploads/product_images/67cd4d49bea3d_1733536078_IMG-20241015-WA0014 (1).jpg', '2025-03-06 15:28:12', 20.00, '2025-11-29 03:41:03', NULL),
(16, 'Lemon', '', 0.60, 180.00, 17, 'simple', 'bar', 'uploads/product_images/67cd4ca235a8b_1733534473_IMG-20241030-WA0000 (2).jpg', '2025-11-24 05:39:48', 20.00, '2025-11-29 03:41:03', NULL),
(17, 'Derza', '', 1.57, 471.00, 6, 'simple', 'bar', 'uploads/product_images/67cd4fa120cd5_detergente-dersa-bolsa-x-4000-gramos-bicarbonato-manzana.jpg', '2025-11-24 12:39:13', 20.00, '2025-11-29 03:41:03', NULL),
(18, 'Especial ', '', 0.50, 150.00, 18, 'simple', 'bar', 'uploads/product_images/67cd4d21dbcc7_1733534099_IMG-20241030-WA0000 (1).jpg', '2025-11-24 12:39:13', 20.00, '2025-11-29 03:41:03', NULL),
(19, 'Cubitos', '', 0.10, 30.00, 45, 'simple', 'bar', 'uploads/product_images/67cd4b76014ae_1733531654_IMG-20241025-WA0001 (1) (1).jpg', '2025-11-24 23:44:45', 20.00, '2025-11-29 03:41:03', NULL),
(20, 'Ellas Nocturna ', 'Toallas higiénicas', 0.13, 39.00, 24, 'simple', 'bar', 'uploads/product_images/67cc98fc4958b_17414616938876713501792961683079.jpg', '2025-03-09 18:41:32', 20.00, '2025-11-29 03:41:03', NULL),
(21, 'Colgate', 'Crema dental \r\n90g', 1.00, 300.00, 6, 'simple', 'bar', 'uploads/product_images/67cca58b81f96_17414649522948096422167686019999.jpg', '2025-11-24 06:48:27', 20.00, '2025-11-29 03:41:03', NULL),
(22, 'Prestobarba ', '', 0.25, 75.00, 75, 'simple', 'bar', 'uploads/product_images/67cd51437a864_DORCO-AFEITADORAS.jpg', '2025-03-06 15:33:00', 20.00, '2025-11-29 03:41:03', NULL),
(23, 'Nutribela', '', 0.40, 120.00, 7, 'simple', 'bar', 'uploads/product_images/67cd500f6574f_Imagen-de-WhatsApp-2024-10-10-a-las-12.30.19_4a8ef7d3-Photoroom.png', '2025-03-06 15:33:19', 20.00, '2025-11-29 03:41:03', NULL),
(24, 'Azúcar mayagüez ', 'Azúcar blanco refinada \r\n1000g', 1.20, 360.00, 7, 'simple', 'bar', 'uploads/product_images/67cc79102f1ce_17414535682905754480784189297811.jpg', '2025-11-24 11:31:28', 20.00, '2025-11-29 03:41:03', NULL),
(25, 'Arroz Masías', 'Arroz blanco tipo 1\r\n900g', 1.20, 360.00, 9, 'simple', 'bar', 'uploads/product_images/67cc64a4432cd_17414483196827719961249995023379.jpg', '2025-11-24 11:31:28', 20.00, '2025-11-29 03:41:03', NULL),
(26, 'Aceite Vegetal ', 'Aceite Vegetal \r\nImperial mini', 0.66, 198.00, 21, 'simple', 'bar', 'uploads/product_images/67cce78f2024e_17414818136548145782465313964763.jpg', '2025-11-24 23:44:45', 20.00, '2025-11-29 03:41:03', NULL),
(27, 'Boka', '', 0.25, 75.00, 16, 'simple', 'bar', 'uploads/product_images/67cd4aef93c79_product2.jpg', '2025-11-24 15:37:37', 20.00, '2025-11-29 03:41:03', NULL),
(28, 'Cafe La Protectora', 'Cafe \r\n100g', 1.20, 360.00, 20, 'simple', 'bar', 'uploads/product_images/67cc99b963966_17414619171236935147890092267948.jpg', '2025-11-24 23:44:45', 20.00, '2025-11-29 03:41:03', NULL),
(29, 'Chimon', '', 0.42, 126.00, 14, 'simple', 'bar', 'uploads/product_images/67cd4c3c2455c_1733533277_IMG-20241106-WA0003 (1).jpg', '2025-03-06 15:35:07', 20.00, '2025-11-29 03:41:03', NULL),
(30, 'Mantequilla Nelly', 'Mantequilla \r\n250g', 1.27, 381.00, 12, 'simple', 'bar', 'uploads/product_images/67cc9acc5a385_17414621902836174822109289513804.jpg', '2025-11-24 21:43:03', 20.00, '2025-11-29 03:41:03', NULL),
(31, 'Arepa repa', 'Harina de maíz blanco precocida ', 0.78, 234.00, 15, 'simple', 'bar', 'uploads/product_images/67cc75e46297e_1741452750449821995791584165818.jpg', '2025-03-06 15:37:55', 20.00, '2025-11-29 03:41:03', NULL),
(32, 'Suavitel ', '', 0.60, 180.00, 24, 'simple', 'bar', 'uploads/product_images/67cd4c6bd478e_1733534571_IMG-20241030-WA0000 (3).jpg', '2025-11-24 05:39:48', 20.00, '2025-11-29 03:41:03', NULL),
(33, 'Mayonesa ', 'Mayonesa \r\n80g', 0.40, 120.00, 10, 'simple', 'bar', 'uploads/product_images/67ccaaf45983d_17414663352551627909815234022204.jpg', '2025-03-09 17:03:30', 20.00, '2025-11-29 03:41:03', NULL),
(34, 'Huevos ', '', 0.20, 60.00, 90, 'simple', 'bar', 'uploads/product_images/67cd505be5b03_502890.jpg', '2025-11-24 21:43:03', 20.00, '2025-11-29 03:41:03', NULL),
(35, 'Salsa de tomate ', 'Salsa de tomate \r\n80g', 0.48, 144.00, 16, 'simple', 'bar', 'uploads/product_images/67ccaaac9941d_17414662584991869960381084718727.jpg', '2025-03-06 15:39:43', 20.00, '2025-11-29 03:41:03', NULL),
(36, 'Trifogon', '', 0.07, 21.00, 50, 'simple', 'bar', 'uploads/product_images/67cd4bbdac106_1733531955_IMG-20241123-WA0001 (1).jpg', '2025-03-06 15:40:02', 20.00, '2025-11-29 03:41:03', NULL),
(37, 'Sal San Benito ', 'Sal fina de mesa\r\n1k', 0.40, 120.00, 24, 'simple', 'bar', 'uploads/product_images/67cc7b9908576_17414541544023457104180252532273.jpg', '2025-03-06 15:40:16', 20.00, '2025-11-29 03:41:03', NULL),
(38, 'Chupetas', '', 0.13, 39.00, 80, 'simple', 'bar', 'uploads/product_images/67cceb222efe5_17414827442048128366443505538707.jpg', '2025-03-09 18:18:17', 20.00, '2025-11-29 03:41:03', NULL),
(39, 'Bombillo ', '', 0.30, 90.00, 10, 'simple', 'bar', 'uploads/product_images/67cd50dfc2f90_bombillo-110v-hk-luz.jpg', '2025-03-06 15:41:02', 20.00, '2025-11-29 03:41:03', NULL),
(40, 'Yesqueros', '', 0.20, 60.00, 6, 'simple', 'bar', 'uploads/product_images/67cd4dc4e186d_1733537874_IMG-20240429-WA0002 (1).jpg', '2025-03-06 15:41:41', 20.00, '2025-11-29 03:41:03', NULL),
(41, 'Time silver', '', 0.80, 240.00, 7, 'simple', 'bar', 'uploads/product_images/67ccea9a29ebb_17414826339184124622918663184418.jpg', '2025-11-24 05:39:48', 20.00, '2025-11-29 03:41:03', NULL),
(42, 'Chicles de tattoo', '', 0.03, 9.00, 105, 'simple', 'bar', 'uploads/product_images/67ccea2bcaccf_17414825059406151719491204023196.jpg', '2025-03-09 23:14:38', 20.00, '2025-11-29 03:41:03', NULL),
(43, 'Desodorante Speed Stick', 'Desodorante de sobres ', 0.25, 75.00, 97, 'simple', 'bar', 'uploads/product_images/67cca3f811f47_17414644462915797512284519772386.jpg', '2025-03-06 15:47:11', 20.00, '2025-11-29 03:41:03', NULL),
(44, 'Esponja Matrixx', 'Esponja multiusos', 0.25, 75.00, 7, 'simple', 'bar', 'uploads/product_images/67cc95410a92a_17414607633935033918757604367667.jpg', '2025-03-08 19:06:41', 20.00, '2025-11-29 03:41:03', NULL),
(45, 'Esponja chemmer', 'Esponja de acero inoxidable ', 0.30, 90.00, 13, 'simple', 'bar', 'uploads/product_images/67cc964710bf8_17414610426508755076250693373492.jpg', '2025-03-08 19:11:03', 20.00, '2025-11-29 03:41:03', NULL),
(46, 'Time Blue', 'Cigarros ', 0.80, 240.00, 7, 'simple', 'bar', 'uploads/product_images/67cce4178e04b_17414809668196991081838860535850.jpg', '2025-03-09 13:46:56', 20.00, '2025-11-29 03:41:03', NULL),
(47, 'Time 1 click ', 'Cigarros de menta ', 1.20, 360.00, 25, 'simple', 'bar', 'uploads/product_images/67cce486949e9_17414810666162828247057201095744.jpg', '2025-11-24 06:47:57', 20.00, '2025-11-29 03:41:03', NULL),
(48, 'Gomitas Princess', 'Tubos de gomitas ', 0.17, 51.00, 28, 'simple', 'bar', 'uploads/product_images/67ccef4fbce30_1741483825285951028960618843358.jpg', '2025-03-09 14:29:00', 20.00, '2025-11-29 03:41:03', NULL),
(49, 'Coloreti', 'Pastillas de chocolate ', 0.22, 66.00, 22, 'simple', 'bar', 'uploads/product_images/67ccefbb610e0_17414839369026282062442029403121.jpg', '2025-03-09 01:32:43', 20.00, '2025-11-29 03:41:03', NULL),
(50, 'Muuu.. mantequilla ', 'Galletas de mantequilla ', 0.20, 60.00, 18, 'simple', 'bar', 'uploads/product_images/67ccf00d1f51f_17414840199863787932657680490180.jpg', '2025-03-09 01:34:05', 20.00, '2025-11-29 03:41:03', NULL),
(51, 'Galletas Charmy', 'Galletas con relleno de crema ', 0.18, 54.00, 12, 'simple', 'bar', 'uploads/product_images/67ccf0888adb5_17414841411356361203726346495725.jpg', '2025-03-09 01:36:08', 20.00, '2025-11-29 03:41:03', NULL),
(52, 'Oka loka chicle en polvo ', 'Chicle en polvo ', 0.20, 60.00, 12, 'simple', 'bar', 'uploads/product_images/67ccf10c05bdb_1741484263919300001115409146628.jpg', '2025-03-09 01:38:20', 20.00, '2025-11-29 03:41:03', NULL),
(53, 'Caramelos Chaos', 'Caramelos de menta', 0.04, 12.00, 100, 'simple', 'bar', 'uploads/product_images/67cd5297dd2b2_images.jpg', '2025-03-09 02:42:06', 20.00, '2025-11-29 03:41:03', NULL),
(57, 'Pizza Margarita (Mediana)', 'Salsa Napolitana y Queso Mozzarella', 6.00, 1800.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(58, 'Pizza Pepperoni (Mediana)', 'Margarita con doble Pepperoni', 7.50, 2250.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(59, 'Pizza Familiar Full Equipo', 'Jamón, Pepperoni, Maíz, Pimentón, Cebolla', 12.00, 3600.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(60, 'Hamburguesa Clásica', 'Carne 150g, Queso Amarillo, Vegetales y Salsas', 4.50, 1350.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(61, 'Hamburguesa Doble Carne', 'Doble Carne, Doble Queso, Tocineta', 6.50, 1950.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(62, 'Tumbarrancho Clásico', 'Arepa rebozada, Mortadela, Queso de Mano, Repollo, Salsa', 2.50, 750.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(63, 'Tequeyoyo (Unidad)', 'Relleno de Queso, Jamón y Plátano', 1.50, 450.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(64, 'Pastelito de Carne', 'Masa de Trigo y Carne Molida', 1.00, 300.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(65, 'Pastelito de Papa con Queso', 'Clásico Maracucho', 1.00, 300.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(66, 'Empanada de Carne (Maíz)', 'Masa de Maíz frita', 1.20, 360.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(67, 'Ración Tequeños (5 und)', '5 Tequeños full queso con salsa tártara', 4.00, 1200.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(68, 'Papita de Yuca', 'Masa de Yuca rellena de Queso', 1.50, 450.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(69, 'Perro Caliente Normal', 'Salchicha, Repollo, Papitas, Salsas', 1.50, 450.00, 0, 'prepared', 'kitchen', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(70, 'Calzone Relleno', 'Masa de Pizza cerrada con Jamón y Queso', 5.00, 1500.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(71, 'Coca-Cola 2 Litros', 'Refresco botella grande', 2.50, 750.00, 19, 'simple', 'bar', NULL, '2025-11-29 00:13:32', 20.00, '2025-12-10 12:22:06', NULL),
(72, 'Malta Polar', 'Lata fría', 1.00, 300.00, 40, 'simple', 'bar', NULL, '2025-11-29 00:13:32', 20.00, '2025-11-29 03:41:03', NULL),
(78, 'Pizza Hawaiana', 'Jamón, Piña y extra Queso', 8.00, 2400.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 01:23:58', 20.00, '2025-11-29 03:41:03', NULL),
(79, 'Pizza Vegetariana', 'Pimentón, Cebolla, Maíz, Champiñones y Aceitunas', 7.00, 2100.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 01:23:58', 20.00, '2025-11-29 03:41:03', NULL),
(80, 'Pizza 4 Quesos', 'Mozzarella, Parmesano, Amarillo y Duro', 9.00, 2700.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 01:23:58', 20.00, '2025-11-29 03:41:03', NULL),
(81, 'Pizza Pollo y Champiñones', 'Pollo desmechado y Champiñones frescos', 8.50, 2550.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 01:23:58', 20.00, '2025-11-29 03:41:03', NULL),
(82, 'Pizza Pepperoni BORDE DE QUESO', 'Nuestra famosa pepperoni con orilla rellena de mozzarella', 9.50, 2850.00, 0, 'prepared', 'pizza', NULL, '2025-11-29 01:23:58', 20.00, '2025-11-29 03:41:03', NULL),
(87, 'Combo Fiestero (25 Tequeños)', '25 Tequeños fritos + 1 Salsa Tártara + 1 Coca-Cola 2L', 8.00, 2400.00, 0, 'compound', 'kitchen', NULL, '2025-11-29 03:40:43', 20.00, '2025-12-10 12:37:51', NULL),
(88, 'Combo Pareja (2 Pizzas)', '2 Pizzas Medianas (Margarita/Peppe) + 1 Coca-Cola 2L', 15.00, 4500.00, 0, 'compound', 'pizza', NULL, '2025-11-29 03:40:43', 20.00, '2025-11-29 07:34:39', NULL),
(89, 'Combo Maracucho (Cena)', '2 Tumbarranchos + 2 Tequeyoyos + 2 Maltas', 8.00, 2400.00, 0, 'compound', 'kitchen', NULL, '2025-11-29 03:40:43', 20.00, '2025-11-29 03:41:03', NULL),
(90, 'Combo Burger Duo', '2 Hamburguesas Clásicas + Papitas + 2 Maltas', 9.00, 2700.00, 0, 'compound', 'kitchen', NULL, '2025-11-29 03:40:43', 20.00, '2025-11-29 03:41:03', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_components`
--

CREATE TABLE `product_components` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `component_type` enum('raw','manufactured','product') NOT NULL,
  `component_id` int(11) NOT NULL,
  `quantity` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `product_components`
--

INSERT INTO `product_components` (`id`, `product_id`, `component_type`, `component_id`, `quantity`) VALUES
(1, 57, 'manufactured', 1, 0.3500),
(2, 57, 'manufactured', 2, 0.1000),
(3, 57, 'raw', 12, 0.1500),
(5, 62, 'manufactured', 15, 1.0000),
(6, 62, 'manufactured', 16, 0.0500),
(7, 62, 'raw', 58, 0.0800),
(8, 62, 'raw', 53, 0.0400),
(9, 62, 'raw', 44, 0.0300),
(10, 67, 'manufactured', 3, 5.0000),
(11, 67, 'raw', 44, 0.0500),
(12, 67, 'manufactured', 13, 0.0400),
(13, 60, 'manufactured', 4, 1.0000),
(14, 60, 'raw', 10, 1.0000),
(15, 60, 'raw', 14, 0.0300),
(16, 69, 'raw', 52, 1.0000),
(17, 69, 'raw', 11, 1.0000),
(18, 69, 'manufactured', 12, 0.0200),
(19, 63, 'manufactured', 10, 1.0000),
(20, 63, 'raw', 44, 0.0200),
(22, 57, 'raw', 36, 1.0000),
(37, 78, 'manufactured', 1, 0.3500),
(38, 78, 'manufactured', 2, 0.1000),
(39, 78, 'raw', 12, 0.1500),
(40, 78, 'raw', 17, 0.1000),
(41, 78, 'raw', 67, 0.1000),
(42, 78, 'raw', 36, 1.0000),
(43, 79, 'manufactured', 1, 0.3500),
(44, 79, 'manufactured', 2, 0.1000),
(45, 79, 'raw', 12, 0.1500),
(46, 79, 'raw', 36, 1.0000),
(47, 79, 'raw', 24, 0.0300),
(48, 79, 'raw', 23, 0.0300),
(49, 79, 'raw', 26, 0.0400),
(50, 79, 'raw', 57, 0.0400),
(51, 79, 'raw', 68, 0.0300),
(52, 80, 'manufactured', 1, 0.3500),
(53, 80, 'manufactured', 2, 0.1000),
(54, 80, 'raw', 36, 1.0000),
(55, 80, 'raw', 12, 0.1000),
(56, 80, 'raw', 15, 0.0300),
(57, 80, 'raw', 14, 0.0500),
(58, 80, 'raw', 13, 0.0500),
(59, 81, 'manufactured', 1, 0.3500),
(60, 81, 'manufactured', 2, 0.1000),
(61, 81, 'raw', 12, 0.1500),
(62, 81, 'raw', 36, 1.0000),
(63, 81, 'raw', 20, 0.1500),
(64, 81, 'raw', 26, 0.0500),
(65, 82, 'manufactured', 1, 0.3500),
(66, 82, 'manufactured', 2, 0.1000),
(67, 82, 'raw', 12, 0.2500),
(68, 82, 'raw', 16, 0.0600),
(69, 82, 'raw', 36, 1.0000),
(70, 67, 'raw', 41, 1.0000),
(71, 58, 'manufactured', 1, 0.3500),
(72, 58, 'manufactured', 2, 0.1000),
(74, 58, 'raw', 12, 0.1500),
(77, 58, 'raw', 16, 0.1000),
(78, 58, 'raw', 36, 1.0000),
(79, 59, 'manufactured', 1, 0.6000),
(80, 59, 'manufactured', 2, 0.1500),
(81, 59, 'raw', 12, 0.3000),
(106, 61, 'manufactured', 4, 2.0000),
(107, 61, 'raw', 10, 1.0000),
(108, 61, 'raw', 14, 0.0600),
(109, 61, 'raw', 18, 0.0400),
(110, 61, 'raw', 38, 1.0000),
(111, 64, 'manufactured', 7, 1.0000),
(112, 64, 'raw', 44, 0.0150),
(113, 64, 'raw', 40, 1.0000),
(114, 64, 'manufactured', 13, 0.0200),
(115, 65, 'manufactured', 9, 1.0000),
(116, 65, 'raw', 44, 0.0150),
(117, 65, 'raw', 40, 1.0000),
(118, 65, 'manufactured', 13, 0.0200),
(119, 66, 'manufactured', 5, 1.0000),
(120, 66, 'raw', 44, 0.0200),
(121, 66, 'raw', 40, 2.0000),
(122, 68, 'manufactured', 11, 1.0000),
(123, 68, 'raw', 44, 0.0200),
(124, 68, 'manufactured', 13, 0.0300),
(125, 70, 'manufactured', 1, 0.2500),
(126, 70, 'manufactured', 2, 0.0800),
(127, 70, 'raw', 12, 0.1200),
(128, 70, 'raw', 17, 0.0800),
(129, 70, 'raw', 36, 1.0000),
(164, 90, 'product', 60, 2.0000),
(165, 90, 'product', 72, 2.0000),
(166, 89, 'product', 62, 2.0000),
(167, 89, 'product', 63, 2.0000),
(168, 89, 'product', 72, 2.0000),
(170, 88, 'product', 71, 1.0000),
(171, 87, 'product', 67, 5.0000),
(172, 87, 'product', 71, 1.0000),
(173, 88, 'product', 57, 1.0000),
(174, 88, 'product', 58, 1.0000),
(175, 88, 'raw', 36, 2.0000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_valid_extras`
--

CREATE TABLE `product_valid_extras` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `price_override` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `product_valid_extras`
--

INSERT INTO `product_valid_extras` (`id`, `product_id`, `raw_material_id`, `price_override`) VALUES
(1, 78, 17, 1.00),
(2, 78, 57, 1.00),
(3, 78, 16, 1.00),
(4, 71, 42, 1.00),
(5, 57, 12, 1.00);

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
(20, 1, '2025-11-24', '2025-11-27', 4.80, 'received', '2025-11-24 09:14:54', '2025-11-24 09:16:13', 100.00),
(21, 1, '2025-11-24', '2025-11-27', 15.00, 'received', '2025-11-24 15:42:37', '2025-11-24 15:42:59', 200.00),
(22, 1, '2025-12-10', '2025-12-10', 50.00, 'received', '2025-12-10 11:32:53', '2025-12-10 11:32:53', 1.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `item_type` enum('product','raw_material') DEFAULT 'product',
  `item_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(20,6) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `item_type`, `item_id`, `product_id`, `quantity`, `unit_price`, `created_at`, `updated_at`) VALUES
(9, 7, 'product', 47, 47, 10, 1.200000, '2025-11-24 01:10:51', '2025-12-10 15:33:39'),
(10, 8, 'product', 21, 21, 12, 1.000000, '2025-11-24 01:23:07', '2025-12-10 15:33:39'),
(11, 9, 'product', 4, 4, 10, 1.500000, '2025-11-24 01:27:27', '2025-12-10 15:33:39'),
(12, 9, 'product', 32, 32, 24, 0.600000, '2025-11-24 01:27:27', '2025-12-10 15:33:39'),
(13, 10, 'product', 41, 41, 10, 0.800000, '2025-11-24 01:48:00', '2025-12-10 15:33:39'),
(14, 11, 'product', 14, 14, 30, 0.330000, '2025-11-24 02:24:36', '2025-12-10 15:33:39'),
(15, 12, 'product', 47, 47, 10, 1.000000, '2025-11-24 07:00:02', '2025-12-10 15:33:39'),
(16, 13, 'product', 4, 4, 10, 1.500000, '2025-11-24 07:20:01', '2025-12-10 15:33:39'),
(17, 14, 'product', 29, 29, 12, 0.350000, '2025-11-24 07:24:04', '2025-12-10 15:33:39'),
(18, 15, 'product', 37, 37, 20, 0.330000, '2025-11-24 07:26:33', '2025-12-10 15:33:39'),
(19, 16, 'product', 10, 10, 100, 0.030000, '2025-11-24 07:32:36', '2025-12-10 15:33:39'),
(20, 17, 'product', 36, 36, 50, 0.060000, '2025-11-24 07:35:03', '2025-12-10 15:33:39'),
(21, 18, 'product', 39, 39, 10, 0.250000, '2025-11-24 09:02:02', '2025-12-10 15:33:39'),
(22, 19, 'product', 45, 45, 12, 0.250000, '2025-11-24 09:10:01', '2025-12-10 15:33:39'),
(23, 20, 'product', 35, 35, 12, 0.400000, '2025-11-24 09:14:54', '2025-12-10 15:33:39'),
(24, 21, 'product', 4, 4, 10, 1.500000, '2025-11-24 15:42:37', '2025-12-10 15:33:39'),
(25, 22, 'product', 87, 87, 10, 5.000000, '2025-12-10 11:32:53', '2025-12-10 15:33:39');

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
(19, 20, '2025-11-24', '2025-11-24 09:16:13', '2025-11-24 09:16:13'),
(20, 21, '2025-11-24', '2025-11-24 15:42:59', '2025-11-24 15:42:59'),
(21, 22, '2025-12-10', '2025-12-10 11:32:53', '2025-12-10 11:32:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `raw_materials`
--

CREATE TABLE `raw_materials` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `stock_quantity` decimal(20,6) DEFAULT 0.000000,
  `cost_per_unit` decimal(20,6) DEFAULT 0.000000,
  `min_stock` decimal(20,6) DEFAULT 5.000000,
  `is_cooking_supply` tinyint(1) DEFAULT 0,
  `category` enum('ingredient','packaging','supply') DEFAULT 'ingredient',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `raw_materials`
--

INSERT INTO `raw_materials` (`id`, `name`, `unit`, `stock_quantity`, `cost_per_unit`, `min_stock`, `is_cooking_supply`, `category`, `updated_at`) VALUES
(5, 'Harina de Trigo Panadera', 'kg', 138.550000, 1.100000, 50.000000, 0, 'ingredient', '2025-11-29 01:31:10'),
(6, 'Levadura Instantánea', 'kg', 5.800000, 5.000000, 1.000000, 0, 'ingredient', '2025-11-29 00:55:23'),
(7, 'Azúcar Refinada', 'kg', 20.000000, 1.500000, 5.000000, 0, 'ingredient', '2025-11-29 00:43:23'),
(8, 'Sal', 'kg', 19.600000, 0.500000, 2.000000, 0, 'ingredient', '2025-11-29 00:55:23'),
(9, 'Aceite de Oliva (Masa)', 'lt', 17.750000, 6.000000, 2.000000, 0, 'ingredient', '2025-11-29 01:12:04'),
(10, 'Pan de Hamburguesa', 'und', 100.000000, 0.300000, 24.000000, 0, 'ingredient', '2025-11-29 02:37:20'),
(11, 'Pan de Perro Caliente', 'und', 50.000000, 0.250000, 24.000000, 0, 'ingredient', '2025-11-29 01:00:13'),
(12, 'Queso Mozzarella (Bloque)', 'kg', 22.400000, 6.500000, 10.000000, 0, 'ingredient', '2025-12-10 17:13:55'),
(13, 'Queso Duro (Tequeños)', 'kg', 22.750000, 5.500000, 10.000000, 0, 'ingredient', '2025-11-29 01:32:36'),
(14, 'Queso Amarillo (Laminado)', 'kg', 12.000000, 7.000000, 2.000000, 0, 'ingredient', '2025-11-29 01:04:34'),
(15, 'Queso Parmesano', 'kg', 6.000000, 12.000000, 1.000000, 0, 'ingredient', '2025-11-29 01:05:47'),
(16, 'Pepperoni', 'kg', 5.500000, 9.000000, 3.000000, 0, 'ingredient', '2025-12-10 17:13:55'),
(17, 'Jamón de Pierna', 'kg', 35.000000, 6.000000, 5.000000, 0, 'ingredient', '2025-11-29 01:31:37'),
(18, 'Tocineta (Bacon)', 'kg', 8.000000, 8.500000, 3.000000, 0, 'ingredient', '2025-11-29 01:08:52'),
(19, 'Carne Molida Premium', 'kg', 22.000000, 5.500000, 10.000000, 0, 'ingredient', '2025-11-29 03:18:12'),
(20, 'Pechuga de Pollo', 'kg', 8.000000, 4.500000, 5.000000, 0, 'ingredient', '2025-11-29 01:02:15'),
(21, 'Huevos', 'und', 117.100000, 0.150000, 30.000000, 0, 'ingredient', '2025-11-29 01:11:10'),
(22, 'Tomate Manzano', 'kg', 18.000000, 2.000000, 5.000000, 0, 'ingredient', '2025-11-29 01:11:43'),
(23, 'Cebolla Blanca', 'kg', 25.000000, 1.500000, 5.000000, 0, 'ingredient', '2025-11-29 01:30:46'),
(24, 'Pimentón Rojo/Verde', 'kg', 6.000000, 2.500000, 3.000000, 0, 'ingredient', '2025-11-29 01:02:59'),
(25, 'Lechuga Americana', 'kg', 6.000000, 3.000000, 3.000000, 0, 'ingredient', '2025-11-29 00:52:02'),
(26, 'Champiñones (Hongos)', 'kg', 3.000000, 6.000000, 2.000000, 0, 'ingredient', '2025-11-29 00:45:44'),
(27, 'Maíz Dulce (Lata)', 'kg', 8.000000, 3.500000, 5.000000, 0, 'ingredient', '2025-11-29 00:57:11'),
(28, 'Ajo (Pelado)', 'kg', 3.080000, 4.000000, 1.000000, 0, 'ingredient', '2025-11-29 03:18:12'),
(29, 'Salsa Napolitana Base', 'lt', 15.000000, 2.000000, 10.000000, 0, 'ingredient', '2025-11-29 01:08:04'),
(30, 'Ketchup (Galón)', 'lt', 8.000000, 3.000000, 4.000000, 0, 'ingredient', '2025-11-29 00:51:31'),
(31, 'Mayonesa (Galón)', 'lt', 21.800000, 4.000000, 4.000000, 0, 'ingredient', '2025-11-29 01:29:45'),
(32, 'Mostaza (Galón)', 'lt', 7.800000, 2.500000, 2.000000, 0, 'ingredient', '2025-11-29 01:10:47'),
(33, 'Salsa de Ajo (Casera)', 'lt', 6.000000, 2.000000, 5.000000, 0, 'ingredient', '2025-11-29 01:07:38'),
(34, 'Salsa BBQ', 'lt', 6.000000, 3.500000, 2.000000, 0, 'ingredient', '2025-11-29 01:07:20'),
(35, 'Caja Pizza Pequeña (10)', 'und', 200.000000, 0.400000, 50.000000, 1, 'packaging', '2025-11-29 06:01:19'),
(36, 'Caja Pizza Mediana (12)', 'und', 180.000000, 0.600000, 50.000000, 1, 'packaging', '2025-12-10 17:13:55'),
(37, 'Caja Pizza Familiar (16)', 'und', 200.000000, 0.900000, 50.000000, 1, 'packaging', '2025-11-29 06:01:19'),
(38, 'Papel Parafinado (Hamburguesa)', 'und', 200.000000, 0.050000, 100.000000, 1, 'packaging', '2025-11-29 06:01:19'),
(39, 'Bolsa Plástica Delivery', 'und', 100.000000, 0.100000, 50.000000, 1, 'packaging', '2025-11-29 06:01:19'),
(40, 'Servilletas', 'und', 2400.000000, 0.010000, 200.000000, 1, 'ingredient', '2025-11-29 01:08:36'),
(41, 'Envase Salsa Pequeño (1oz)', 'und', 500.000000, 0.030000, 100.000000, 1, 'packaging', '2025-11-29 06:01:19'),
(42, 'Vasos Plásticos', 'und', 499.900000, 0.050000, 50.000000, 1, 'ingredient', '2025-12-02 07:01:32'),
(43, 'Pitillos/Pajillas', 'und', 500.000000, 0.010000, 100.000000, 1, 'ingredient', '2025-11-29 01:03:19'),
(44, 'Aceite Vegetal (Freidora)', 'lt', 30.000000, 2.000000, 20.000000, 1, 'ingredient', '2025-11-29 00:42:26'),
(45, 'Gas (Bombona)', 'und', 42.000000, 15.000000, 1.000000, 1, 'ingredient', '2025-11-29 00:48:31'),
(46, 'Detergente Líquido', 'lt', 20.000000, 1.500000, 2.000000, 1, 'ingredient', '2025-11-29 00:46:24'),
(47, 'Esponjas/Fibras', 'und', 24.000000, 0.500000, 5.000000, 1, 'ingredient', '2025-11-29 00:48:07'),
(48, 'Harina de Maíz Precocida', 'kg', 28.500000, 1.500000, 20.000000, 0, 'ingredient', '2025-11-29 01:56:13'),
(49, 'Papas (Patatas)', 'kg', 21.000000, 1.200000, 10.000000, 0, 'ingredient', '2025-11-29 01:13:11'),
(50, 'Plátano Maduro', 'kg', 20.000000, 1.000000, 15.000000, 0, 'ingredient', '2025-11-29 01:12:13'),
(51, 'Yuca (Mandioca)', 'kg', 29.000000, 0.800000, 20.000000, 0, 'ingredient', '2025-11-29 01:11:10'),
(52, 'Salchicha (Paquete/Unidad)', 'und', 144.000000, 0.150000, 50.000000, 0, 'ingredient', '2025-11-29 01:06:59'),
(53, 'Repollo (Col)', 'kg', 12.000000, 1.000000, 5.000000, 0, 'ingredient', '2025-11-29 01:06:05'),
(54, 'Papitas Rayadas (Lluvia)', 'kg', 12.000000, 4.500000, 5.000000, 0, 'ingredient', '2025-11-29 01:01:40'),
(55, 'Mostaza (Salsa Base)', 'lt', 8.000000, 2.500000, 2.000000, 0, 'ingredient', '2025-11-29 00:59:07'),
(56, 'Cilantro/Perejil', 'kg', 2.920000, 3.000000, 1.000000, 0, 'ingredient', '2025-11-29 01:12:48'),
(57, 'Maíz Dulce (Grano)', 'kg', 7.200000, 3.500000, 5.000000, 0, 'ingredient', '2025-11-29 01:12:39'),
(58, 'Mortadela (Barra)', 'kg', 12.000000, 4.500000, 5.000000, 0, 'ingredient', '2025-11-29 00:58:12'),
(67, 'Piña en Almibar', 'kg', 6.000000, 3.500000, 5.000000, 0, 'ingredient', '2025-11-29 01:29:01'),
(68, 'Aceitunas Negras', 'kg', 8.000000, 6.000000, 2.000000, 0, 'ingredient', '2025-11-29 01:30:09'),
(69, 'Oregano Seco', 'kg', 8.000000, 10.000000, 0.500000, 0, 'ingredient', '2025-11-29 01:31:54');

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
  `reference_type` enum('order','purchase','adjustment','manual','debt_payment') NOT NULL DEFAULT 'manual',
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

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
(50, 6, 'income', 174.00, 'VES', 200.00, 0.87, 4, 'order', 60, 'Cobro Venta #60', 4, '2025-11-24 12:39:13'),
(51, 7, 'income', 5.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 15:27:35'),
(52, 7, 'income', 500.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 15:27:35'),
(53, 7, 'income', 764.00, 'VES', 200.00, 3.82, 5, 'order', 61, 'Cobro Venta #61', 4, '2025-11-24 15:37:37'),
(54, 0, 'expense', 3000.00, 'VES', 200.00, 15.00, 5, 'purchase', 21, 'Pago de Compra #21 (Punto de Venta)', 4, '2025-11-24 15:42:37'),
(55, 8, 'income', 5.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 20:07:59'),
(56, 8, 'income', 350.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 20:07:59'),
(57, 9, 'income', 5.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 21:35:45'),
(58, 9, 'income', 500.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 21:35:45'),
(59, 9, 'income', 1.00, 'USD', 1.00, 1.00, 1, 'order', 62, 'Cobro Venta #62', 4, '2025-11-24 21:43:03'),
(60, 9, 'income', 400.00, 'VES', 300.00, 1.33, 2, 'order', 62, 'Cobro Venta #62', 4, '2025-11-24 21:43:03'),
(61, 9, 'income', 280.00, 'VES', 300.00, 0.93, 5, 'order', 62, 'Cobro Venta #62', 4, '2025-11-24 21:43:03'),
(62, 10, 'income', 5.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 23:40:21'),
(63, 10, 'income', 500.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 4, '2025-11-24 23:40:21'),
(64, 10, 'income', 1.00, 'USD', 1.00, 1.00, 1, 'order', 63, 'Cobro Venta #63', 4, '2025-11-24 23:44:45'),
(65, 10, 'income', 500.00, 'VES', 300.00, 1.67, 2, 'order', 63, 'Cobro Venta #63', 4, '2025-11-24 23:44:45'),
(66, 10, 'income', 718.00, 'VES', 300.00, 2.39, 5, 'order', 63, 'Cobro Venta #63', 4, '2025-11-24 23:44:45'),
(67, 12, 'income', 17.00, 'USD', 1.00, 17.00, 1, 'order', 65, 'Cobro Venta #65', 4, '2025-11-30 06:06:23'),
(68, 12, 'income', 15.00, 'USD', 1.00, 15.00, 1, 'order', 66, 'Cobro Venta #66', 4, '2025-11-30 15:32:27'),
(69, 12, 'income', 24.00, 'USD', 1.00, 24.00, 1, 'order', 67, 'Cobro Venta #67', 4, '2025-11-30 15:34:33'),
(70, 12, 'income', 8.00, 'USD', 1.00, 8.00, 1, 'order', 68, 'Cobro Venta #68', 4, '2025-12-01 21:37:11'),
(71, 12, 'income', 15.00, 'USD', 1.00, 15.00, 1, 'order', 69, 'Cobro Venta #69', 4, '2025-12-01 21:44:40'),
(72, 12, 'income', 47.00, 'USD', 1.00, 47.00, 1, 'order', 70, 'Cobro Venta #70', 4, '2025-12-02 00:43:21'),
(73, 12, 'income', 15.00, 'USD', 1.00, 15.00, 1, 'order', 71, 'Cobro Venta #71', 4, '2025-12-02 05:19:43'),
(74, 12, 'income', 10.00, 'USD', 1.00, 10.00, 1, 'order', 72, 'Cobro Venta #72', 4, '2025-12-02 05:48:00'),
(76, 12, 'expense', 600.00, 'VES', 300.00, 2.00, 2, 'order', 72, 'Vuelto Venta #72', 4, '2025-12-02 05:48:00'),
(77, 12, 'income', 20.00, 'USD', 1.00, 20.00, 1, 'order', 73, 'Cobro Venta #73', 4, '2025-12-02 05:57:28'),
(78, 12, 'expense', 5.00, 'USD', 1.00, 5.00, 1, 'order', 73, 'Vuelto Venta #73', 4, '2025-12-02 05:57:28'),
(79, 12, 'expense', 5.00, 'USD', 1.00, 5.00, 1, 'order', 73, 'Vuelto Venta #73', 4, '2025-12-02 05:57:28'),
(80, 12, 'income', 5000.00, 'VES', 300.00, 16.67, 2, 'order', 74, 'Cobro Venta #74', 4, '2025-12-02 06:06:20'),
(82, 12, 'expense', 500.00, 'VES', 300.00, 1.67, 2, 'order', 74, 'Vuelto Venta #74', 4, '2025-12-02 06:06:20'),
(83, 12, 'income', 20.00, 'USD', 300.00, 20.00, 1, 'order', 75, 'Cobro Venta #75', 4, '2025-12-02 06:38:04'),
(84, 12, 'expense', 1500.00, 'VES', 300.00, 5.00, 2, 'order', 75, 'Vuelto Venta #75', 4, '2025-12-02 06:38:04'),
(85, 12, 'income', 15.00, 'USD', 300.00, 15.00, 1, 'order', 76, 'Cobro Venta #76', 4, '2025-12-02 07:01:32'),
(86, 12, 'income', 200.00, 'VES', 300.00, 0.67, 2, 'order', 76, 'Cobro Venta #76', 4, '2025-12-02 07:01:32'),
(87, 12, 'income', 500.00, 'VES', 300.00, 1.67, 4, 'order', 76, 'Cobro Venta #76', 4, '2025-12-02 07:01:32'),
(88, 12, 'expense', 100.00, 'VES', 300.00, 0.33, 2, 'order', 76, 'Vuelto Venta #76', 4, '2025-12-02 07:01:32'),
(96, 13, 'income', 100.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 21, '2025-12-10 12:03:29'),
(97, 13, 'income', 500.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 21, '2025-12-10 12:03:29'),
(98, 14, 'income', 100.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 26, '2025-12-10 12:07:18'),
(99, 14, 'income', 500.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 26, '2025-12-10 12:07:18'),
(102, 15, 'income', 100.00, 'USD', 1.00, 0.00, 1, 'adjustment', NULL, 'Fondo Inicial de Caja', 30, '2025-12-10 12:13:58'),
(103, 15, 'income', 500.00, 'VES', 1.00, 0.00, 2, 'adjustment', NULL, 'Fondo Inicial de Caja', 30, '2025-12-10 12:13:58'),
(109, 16, 'income', 15.00, 'USD', 300.00, 15.00, 1, 'order', 80, 'Cobro Venta #80', 4, '2025-12-10 13:56:22'),
(115, 16, 'expense', 9000.00, 'VES', 300.00, 30.00, 2, 'adjustment', 18, 'Pago Nómina: Empleado Test (Weekly)', 4, '2025-12-10 14:32:54'),
(116, 16, 'expense', 30.00, 'USD', 300.00, 30.00, 1, 'adjustment', 19, 'Pago Nómina: jose (Weekly)', 4, '2025-12-10 14:33:04'),
(117, 16, 'expense', 9000.00, 'VES', 300.00, 30.00, 5, 'adjustment', 20, 'Pago Nómina: juan (Weekly)', 4, '2025-12-10 14:33:15'),
(118, 16, 'expense', 9000.00, 'VES', 300.00, 30.00, 4, 'adjustment', 21, 'Pago Nómina: roberto (Monthly)', 4, '2025-12-10 14:33:28'),
(119, 16, 'expense', 100.00, 'USD', 300.00, 100.00, 1, 'manual', NULL, 'Transferencia: Efectivo USD → Pago Móvil', 4, '2025-12-10 14:45:05'),
(120, 16, 'income', 30000.00, 'VES', 300.00, 100.00, 4, 'manual', NULL, 'Transferencia: Efectivo USD → Pago Móvil', 4, '2025-12-10 14:45:05'),
(121, 16, 'expense', 100.00, 'USD', 300.00, 100.00, 1, 'manual', NULL, 'Transferencia: Efectivo USD → Punto de Venta', 4, '2025-12-10 14:45:36'),
(122, 16, 'income', 30000.00, 'VES', 300.00, 100.00, 5, 'manual', NULL, 'Transferencia: Efectivo USD → Punto de Venta', 4, '2025-12-10 14:45:36'),
(123, 17, 'income', 3000.00, 'VES', 300.00, 10.00, 2, 'debt_payment', 9, 'Pago de deuda AR#9 - Cliente ID: 13', 1, '2025-12-11 12:25:57'),
(124, 16, 'income', 10.00, 'USD', 300.00, 10.00, 1, 'debt_payment', 4, 'Pago de deuda AR#4 - Cliente ID: 1', 4, '2025-12-11 12:33:23'),
(125, 16, 'income', 10.00, 'USD', 300.00, 10.00, 1, 'debt_payment', 4, 'Pago de deuda AR#4 - Cliente ID: 1', 4, '2025-12-11 12:33:49'),
(126, 16, 'income', 10.00, 'USD', 300.00, 10.00, 1, 'debt_payment', 4, 'Pago de deuda AR#4 - Cliente ID: 1', 4, '2025-12-11 12:51:13'),
(127, 16, 'income', 25.00, 'USD', 300.00, 25.00, 1, 'debt_payment', 9, 'Pago de deuda AR#9 - Cliente ID: 13', 4, '2025-12-11 12:53:56'),
(128, 16, 'income', 3000.00, 'VES', 300.00, 10.00, 2, 'debt_payment', 9, 'Pago de deuda AR#9 - Cliente ID: 13', 4, '2025-12-11 12:54:48');

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
  `salary_amount` decimal(20,6) NOT NULL DEFAULT 0.000000,
  `salary_frequency` enum('weekly','biweekly','monthly') NOT NULL DEFAULT 'monthly',
  `job_role` enum('manager','kitchen','cashier','delivery','other') NOT NULL DEFAULT 'other',
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `document_id`, `address`, `role`, `profile_pic`, `balance`, `salary_amount`, `salary_frequency`, `job_role`, `reset_token`, `token_expiry`, `created_at`, `updated_at`) VALUES
(4, 'roberto', 'robertopv100@gmail.com', '$2y$10$7GPIEd7LC.leGjt7EgKNvOQ/J7Ht.J2gjOC8njG51PAefmEGWa4bq', '04246746570', 'v-19451788', 'asdasdasd12', 'admin', 'default.jpg', 0.00, 30.000000, 'monthly', 'manager', NULL, NULL, '2025-02-23 23:37:13', '2025-12-10 12:29:45'),
(9, 'juan', 'user@demo.com', '$2y$12$obgNO5tiv5.jbRmLcRjazeHFgskEDSamqXHUEklKmF6s713dcY.nu', '04246746570', 'v-19451789', 'to do', 'user', 'default.jpg', 0.00, 30.000000, 'weekly', 'cashier', NULL, NULL, '2025-12-10 08:22:51', '2025-12-10 08:23:16'),
(10, 'jose', 'user2@demo.com', '$2y$12$Qer94RMZDv3F/ckrefgQqeM9K3t52Rufnh4fyGOvGwFDhvFr2Hws2', '04246746570', 'v-19451790', 'to do', 'user', 'default.jpg', 0.00, 30.000000, 'weekly', 'kitchen', NULL, NULL, '2025-12-10 08:24:17', '2025-12-10 08:24:43'),
(11, 'Empleado Test', 'test_payroll_69395c6fd3685@example.com', '$2y$12$m6BzJRKLOsyAzYxoNzs1DOCbqr2SY3ZUBtTA9bMR9plPtjRxSMS72', '555555', 'V69395c6fd3685', 'Calle Test', 'user', 'default.jpg', 0.00, 30.000000, 'weekly', 'kitchen', NULL, NULL, '2025-12-10 11:41:36', '2025-12-10 12:28:37'),
(20, 'UserTest_6939618f32d34', 'auth_test_6939618f32d34@example.com', '$2y$12$U0xZsuW5TPkj0HlRbgR.WuofY/aqcOHWeRbjFthVA2xF6T8Mn7.uO', '555-0000', 'V-6939618f32d34', 'Test Address', 'user', 'default.jpg', 0.00, 0.000000, 'monthly', 'other', NULL, NULL, '2025-12-10 12:03:27', '2025-12-10 12:03:27'),
(21, 'Cashier 6939619195841', 'cash_test_6939619195841@example.com', '$2y$12$azW7giaam63P.D8mMfVcKut5AQNcXACQlSsNCFsOwz1G2SfLOxmIK', '555', 'ID-6939619195841', 'Addr', 'admin', 'default.jpg', 0.00, 0.000000, 'monthly', 'other', NULL, NULL, '2025-12-10 12:03:29', '2025-12-10 12:03:29'),
(22, 'UserTest_693961f177f1e', 'auth_test_693961f177f1e@example.com', '$2y$12$4X4h5YRhNdrB3L0aytJhheeWIoci90dxbfRQLYotNMzWnlRytCG5q', '555-0000', 'V-693961f177f1e', 'Test Address', 'user', 'default.jpg', 0.00, 0.000000, 'monthly', 'other', NULL, NULL, '2025-12-10 12:05:05', '2025-12-10 12:05:05'),
(23, 'UserTest_6939620e47d5c', 'auth_test_6939620e47d5c@example.com', '$2y$12$R.zwUY1sHwZWeZ4uQvqAd.pv0tdUIrbEUReFXb.srSA.Ww2nsIjxa', '555-0000', 'V-6939620e47d5c', 'Test Address', 'user', 'default.jpg', 0.00, 0.000000, 'monthly', 'other', NULL, NULL, '2025-12-10 12:05:34', '2025-12-10 12:05:34'),
(24, 'UserTest_6939624befaae', 'auth_test_6939624befaae@example.com', '$2y$12$r9dl.OID1wIy1npsVRYkXOQDDnnVeyhLIvvkge7xZLj7b3umBLREe', '555-0000', 'V-6939624befaae', 'Test Address', 'user', 'default.jpg', 0.00, 0.000000, 'monthly', 'other', NULL, NULL, '2025-12-10 12:06:36', '2025-12-10 12:06:36');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

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
(20, 'deposit', 'session_close', 1200.00, 'VES', 'Cierre de Caja #6', 6, 4, '2025-11-24 12:39:33'),
(21, 'deposit', 'session_close', 5.00, 'USD', 'Cierre de Caja #7', 7, 4, '2025-11-24 15:38:45'),
(22, 'deposit', 'session_close', 500.00, 'VES', 'Cierre de Caja #7', 7, 4, '2025-11-24 15:38:45'),
(23, 'deposit', 'session_close', 5.00, 'USD', 'Cierre de Caja #8', 8, 4, '2025-11-24 20:10:09'),
(24, 'deposit', 'session_close', 350.00, 'VES', 'Cierre de Caja #8', 8, 4, '2025-11-24 20:10:09'),
(25, 'deposit', 'session_close', 6.00, 'USD', 'Cierre de Caja #9', 9, 4, '2025-11-24 21:46:30'),
(26, 'deposit', 'session_close', 890.00, 'VES', 'Cierre de Caja #9', 9, 4, '2025-11-24 21:46:30'),
(27, 'withdrawal', 'owner_withdrawal', 100.00, 'USD', 'me los bebi', NULL, 4, '2025-11-24 22:02:01'),
(28, 'deposit', 'session_close', 6.00, 'USD', 'Cierre de Caja #10', 10, 4, '2025-11-24 23:48:05'),
(29, 'deposit', 'session_close', 990.00, 'VES', 'Cierre de Caja #10', 10, 4, '2025-11-24 23:48:05'),
(30, 'deposit', 'session_close', 196.00, 'USD', 'Cierre de Caja #12', 12, 4, '2025-12-02 07:11:31'),
(31, 'deposit', 'session_close', 2500.00, 'VES', 'Cierre de Caja #12', 12, 4, '2025-12-02 07:11:31'),
(32, 'deposit', 'session_close', 150.00, 'USD', 'Cierre de Caja #14', 14, 26, '2025-12-10 12:07:18'),
(33, 'deposit', 'session_close', 600.00, 'VES', 'Cierre de Caja #14', 14, 26, '2025-12-10 12:07:18'),
(34, 'deposit', 'session_close', 150.00, 'USD', 'Cierre de Caja #15', 15, 30, '2025-12-10 12:13:58'),
(35, 'deposit', 'session_close', 600.00, 'VES', 'Cierre de Caja #15', 15, 30, '2025-12-10 12:13:58'),
(40, 'withdrawal', 'owner_withdrawal', 9000.00, 'VES', 'Pago Nómina: Empleado Test (ID: 18)', 18, 4, '2025-12-10 14:32:54'),
(41, 'withdrawal', 'owner_withdrawal', 30.00, 'USD', 'Pago Nómina: jose (ID: 19)', 19, 4, '2025-12-10 14:33:04'),
(42, 'withdrawal', 'owner_withdrawal', 100.00, 'USD', 'Transferencia a Pago Móvil', NULL, 4, '2025-12-10 14:45:05'),
(43, 'withdrawal', 'owner_withdrawal', 100.00, 'USD', 'Transferencia a Punto de Venta', NULL, 4, '2025-12-10 14:45:36');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `accounts_receivable`
--
ALTER TABLE `accounts_receivable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_ar_client_status` (`client_id`,`status`),
  ADD KEY `idx_ar_user_status` (`user_id`,`status`);

--
-- Indices de la tabla `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `cart_item_modifiers`
--
ALTER TABLE `cart_item_modifiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`);

--
-- Indices de la tabla `cash_sessions`
--
ALTER TABLE `cash_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cash_sessions_user_status` (`user_id`,`status`);

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
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
-- Indices de la tabla `manufactured_products`
--
ALTER TABLE `manufactured_products`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_status_date` (`status`,`created_at`),
  ADD KEY `idx_orders_user_status` (`user_id`,`status`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_items_order_product` (`order_id`,`product_id`);

--
-- Indices de la tabla `order_item_modifiers`
--
ALTER TABLE `order_item_modifiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_item` (`order_item_id`);

--
-- Indices de la tabla `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `payroll_payments`
--
ALTER TABLE `payroll_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `idx_payroll_user_date` (`user_id`,`payment_date`);

--
-- Indices de la tabla `production_orders`
--
ALTER TABLE `production_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `production_recipes`
--
ALTER TABLE `production_recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_manuf_prod` (`manufactured_product_id`),
  ADD KEY `idx_raw_mat` (`raw_material_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `product_components`
--
ALTER TABLE `product_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prod_component` (`product_id`),
  ADD KEY `idx_product_components_product` (`product_id`,`component_type`);

--
-- Indices de la tabla `product_valid_extras`
--
ALTER TABLE `product_valid_extras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `raw_material_id` (`raw_material_id`);

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
-- Indices de la tabla `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `cash_session_id` (`cash_session_id`),
  ADD KEY `idx_transactions_ref` (`reference_type`,`reference_id`),
  ADD KEY `idx_transactions_session_type` (`cash_session_id`,`type`);

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
-- AUTO_INCREMENT de la tabla `accounts_receivable`
--
ALTER TABLE `accounts_receivable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT de la tabla `cart_item_modifiers`
--
ALTER TABLE `cart_item_modifiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `cash_sessions`
--
ALTER TABLE `cash_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- AUTO_INCREMENT de la tabla `manufactured_products`
--
ALTER TABLE `manufactured_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT de la tabla `order_item_modifiers`
--
ALTER TABLE `order_item_modifiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `payroll_payments`
--
ALTER TABLE `payroll_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `production_orders`
--
ALTER TABLE `production_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `production_recipes`
--
ALTER TABLE `production_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT de la tabla `product_components`
--
ALTER TABLE `product_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=176;

--
-- AUTO_INCREMENT de la tabla `product_valid_extras`
--
ALTER TABLE `product_valid_extras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `purchase_receipts`
--
ALTER TABLE `purchase_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `vault_movements`
--
ALTER TABLE `vault_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `accounts_receivable`
--
ALTER TABLE `accounts_receivable`
  ADD CONSTRAINT `ar_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ar_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ar_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Filtros para la tabla `payroll_payments`
--
ALTER TABLE `payroll_payments`
  ADD CONSTRAINT `payroll_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `product_valid_extras`
--
ALTER TABLE `product_valid_extras`
  ADD CONSTRAINT `product_valid_extras_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_valid_extras_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE;

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
