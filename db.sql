-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-02-2026 a las 04:41:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `consumption_type` enum('dine_in','takeaway','delivery') DEFAULT 'dine_in',
  `parent_cart_id` int(11) DEFAULT NULL,
  `price_override` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `consumption_type`, `parent_cart_id`, `price_override`) VALUES
(190, 33, 216, 1, 'dine_in', NULL, NULL),
(191, 33, 206, 1, 'dine_in', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart_item_modifiers`
--

CREATE TABLE `cart_item_modifiers` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `modifier_type` enum('add','remove','info','side','companion') NOT NULL,
  `component_id` int(11) DEFAULT NULL,
  `quantity_adjustment` decimal(10,4) DEFAULT 0.0000,
  `price_adjustment` decimal(20,6) DEFAULT 0.000000,
  `note` varchar(255) DEFAULT NULL,
  `sub_item_index` int(11) DEFAULT 0,
  `is_takeaway` tinyint(1) DEFAULT 0,
  `component_type` enum('raw','manufactured','product') DEFAULT 'raw'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `cart_item_modifiers`
--

INSERT INTO `cart_item_modifiers` (`id`, `cart_id`, `modifier_type`, `component_id`, `quantity_adjustment`, `price_adjustment`, `note`, `sub_item_index`, `is_takeaway`, `component_type`) VALUES
(863, 0, '', 4, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(899, 0, '', 4, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(944, 0, '', 4, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(945, 0, '', 4, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(1508, 0, '', 4, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(1548, 0, '', 4, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(1805, 0, '', 12, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(1808, 1807, '', 23, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(1814, 0, '', 10, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(1817, 0, '', 12, 0.0000, 0.000000, 'Linked to ProductCompanion #', -1, 0, ''),
(1818, 190, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(1819, 190, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(1820, 191, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(1821, 191, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(1822, 191, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `cash_sessions`
--

INSERT INTO `cash_sessions` (`id`, `user_id`, `opening_balance_usd`, `opening_balance_ves`, `closing_balance_usd`, `closing_balance_ves`, `calculated_usd`, `calculated_ves`, `status`, `opened_at`, `closed_at`) VALUES
(1, 4, 0.00, 0.00, 32.00, 0.00, 32.00, 0.00, 'closed', '2025-12-16 04:03:53', '2026-01-19 05:39:50'),
(2, 4, 0.00, 0.00, 90.00, 0.00, 90.00, 0.00, 'closed', '2026-01-19 05:42:19', '2026-01-21 03:22:44'),
(3, 4, 0.00, 0.00, 79.00, -245.00, 79.00, -245.00, 'closed', '2026-01-21 03:43:22', '2026-01-21 06:29:02'),
(4, 4, 0.00, 0.00, 416.50, 0.00, 416.50, 0.00, 'closed', '2026-01-21 20:12:25', '2026-02-09 20:10:40'),
(5, 33, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'open', '2026-02-09 20:11:51', NULL),
(6, 4, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'open', '2026-02-12 20:33:12', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `kitchen_station` enum('pizza','kitchen','bar') DEFAULT 'kitchen',
  `icon` varchar(50) DEFAULT 'fa-tag',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `kitchen_station`, `icon`, `description`, `created_at`) VALUES
(1, 'HAMBURGUESAS', 'kitchen', 'fa-hamburger', NULL, '2026-01-25 01:07:13'),
(2, 'PIZZAS', 'pizza', 'fa-pizza-slice', '', '2026-01-25 01:07:13'),
(3, 'BEBIDAS', 'bar', 'fa-glass-whiskey', '', '2026-01-25 01:07:13'),
(4, 'COMBOS', 'kitchen', 'fa-box-open', '', '2026-01-25 01:07:13'),
(5, 'EXTRAS', 'kitchen', 'fa-utensils', '', '2026-01-25 01:07:13'),
(6, 'PAN', 'kitchen', 'fa-hotdog', '', '2026-01-25 01:26:28'),
(7, 'PIZZAS PREMIUM', 'pizza', 'fa-pizza-slice', '', '2026-01-25 03:51:38');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `clients`
--

INSERT INTO `clients` (`id`, `name`, `document_id`, `phone`, `email`, `address`, `credit_limit`, `current_debt`, `created_at`) VALUES
(1, 'juan perez', '12345678', '04245555555', 'cliente_12345678@local.com', '', 0.000000, 0.000000, '2026-01-19 06:39:00'),
(8, 'juan gonsalez', '12345677', '04245555556', 'cliente_12345677@local.com', '', 0.000000, 0.000000, '2026-01-19 07:08:57'),
(10, 'roberto perozo', '19451788', '04246746570', 'cliente_19451788@local.com', '', 0.000000, 0.000000, '2026-02-07 01:13:02'),
(12, 'robero gonsalez', '19451789', '04246746571', 'cliente_19451789@local.com', '', 0.000000, 0.000000, '2026-02-07 01:35:56'),
(13, 'robero perez', '19451787', '', 'cliente_19451787@local.com', '', 0.000000, 0.000000, '2026-02-12 21:22:34'),
(14, 'robero fermin', '12378739', '', 'cliente_12378739@local.com', '', 0.000000, 0.000000, '2026-02-12 21:28:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_vault`
--

CREATE TABLE `company_vault` (
  `id` int(11) NOT NULL,
  `balance_usd` decimal(12,2) DEFAULT 0.00,
  `balance_ves` decimal(12,2) DEFAULT 0.00,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `company_vault`
--

INSERT INTO `company_vault` (`id`, `balance_usd`, `balance_ves`, `last_updated`) VALUES
(1, 406.80, 0.00, '2026-02-13 21:47:52');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `global_config`
--

INSERT INTO `global_config` (`id`, `config_key`, `config_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'QUE TAL PIZZA', 'Nombre de la plataforma', '2025-02-21 12:14:27', '2025-12-22 23:36:30'),
(2, 'site_url', 'https://www.miplataforma.com', 'URL de la web', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(3, 'default_language', 'es', 'Idioma predeterminado del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(4, 'timezone', 'America/Caracas', 'Zona horaria del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(5, 'currency', 'USD', 'Moneda predeterminada del sistema', '2025-02-21 12:14:27', '2025-03-10 06:18:14'),
(6, 'exchange_rate', '500', 'Tasa de cambio USD-VES', '2025-02-21 12:14:27', '2026-02-10 03:44:40'),
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
(50, 'allow_guest_checkout', '1', 'Permitir compras sin registro', '2025-02-21 12:14:28', '2025-03-10 06:18:14'),
(51, 'kds_refresh_interval', '10', NULL, '2026-02-02 11:02:55', '2026-02-02 11:03:11'),
(52, 'kds_color_llevar', '#ef4444', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(53, 'kds_color_local', '#3b82f6', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(54, 'kds_sound_enabled', '1', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(55, 'kds_warning_time_medium', '15', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(56, 'kds_warning_time_late', '25', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(57, 'kds_use_short_codes', '1', NULL, '2026-02-02 11:02:55', '2026-02-02 11:03:15'),
(58, 'kds_color_card_bg', '#ffffff', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(59, 'kds_color_mixed_bg', '#aaa9a7', NULL, '2026-02-02 11:02:55', '2026-02-06 22:46:19'),
(60, 'kds_color_mod_add', '#198754', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(61, 'kds_color_mod_remove', '#dc3545', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(62, 'kds_color_mod_side', '#0030f0', NULL, '2026-02-02 11:02:55', '2026-02-06 22:47:21'),
(63, 'kds_product_name_color', '#000000', NULL, '2026-02-02 11:02:55', '2026-02-02 11:02:55'),
(64, 'kds_sound_url_kitchen', '../assets/sounds/ping_a.mp3', NULL, '2026-02-02 11:02:55', '2026-02-02 11:06:50'),
(65, 'kds_sound_url_pizza', '../assets/sounds/ping_b.mp3', NULL, '2026-02-02 11:02:55', '2026-02-02 11:06:50'),
(66, 'kds_sound_url_dispatch', '../assets/sounds/ping_c.mp3', NULL, '2026-02-02 11:02:55', '2026-02-02 11:06:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kds_item_status`
--

CREATE TABLE `kds_item_status` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `sub_item_index` int(11) DEFAULT 0,
  `station` varchar(50) NOT NULL,
  `is_ready` tinyint(1) DEFAULT 0,
  `ready_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `kds_item_status`
--

INSERT INTO `kds_item_status` (`id`, `order_item_id`, `sub_item_index`, `station`, `is_ready`, `ready_at`) VALUES
(1, 1, 0, 'kitchen', 0, '2026-01-24 21:29:50'),
(3, 37, 1, 'kitchen', 1, '2026-01-24 22:38:11'),
(4, 37, 2, 'kitchen', 1, '2026-01-24 22:38:13'),
(5, 37, 3, 'kitchen', 1, '2026-01-24 22:38:14'),
(6, 37, 4, 'kitchen', 1, '2026-01-24 22:38:15'),
(7, 37, 5, 'kitchen', 1, '2026-01-24 22:38:16'),
(8, 37, 6, 'kitchen', 1, '2026-01-24 22:38:17'),
(9, 37, 7, 'kitchen', 1, '2026-01-24 22:38:18'),
(10, 37, 8, 'kitchen', 1, '2026-01-24 22:38:19'),
(11, 37, 9, 'kitchen', 1, '2026-01-24 22:39:00'),
(12, 37, 10, 'kitchen', 1, '2026-01-24 22:38:58'),
(13, 38, 0, 'kitchen', 1, '2026-01-24 22:38:55');

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
  `last_production_date` datetime DEFAULT NULL,
  `min_stock` decimal(20,6) DEFAULT 0.000000,
  `short_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `manufactured_products`
--

INSERT INTO `manufactured_products` (`id`, `name`, `unit`, `stock`, `unit_cost_average`, `last_production_date`, `min_stock`, `short_code`) VALUES
(1, 'Masa Pizza', 'kg', 9.950000, 1.391400, '2026-02-13 17:49:22', 0.000000, NULL),
(2, 'Salsa Napoles', 'lt', 6.790000, 0.121250, '2026-02-06 15:42:59', 0.000000, NULL),
(3, 'Carne de hamburguesa', 'kg', 7.880000, 7.810815, '2026-02-06 15:39:55', 0.000000, NULL),
(4, 'Carne de hamburguesa Americana', 'kg', 3.280000, 11.100300, '2026-02-06 15:40:07', 0.000000, NULL),
(5, 'Carne Mechada', 'kg', 4.000000, 17.202000, '2026-02-06 15:40:21', 0.000000, NULL),
(6, 'File de Pollo', 'kg', 3.280000, 6.710810, '2026-02-06 15:40:36', 0.000000, NULL),
(7, 'Pernil Preparado', 'kg', 4.000000, 31.862000, '2026-02-06 15:42:12', 0.000000, NULL),
(8, 'Lomito preparado', 'kg', 4.000000, 15.081591, '2026-02-06 15:40:48', 0.000000, NULL),
(9, 'Mezcla para Rebozar (Tumbarrancho)', 'kg', 4.000000, 1.457750, '2026-02-06 15:42:24', 0.000000, NULL),
(10, 'Viuda (Tumbarrancho)', 'und', 49.000000, 0.050600, '2026-02-06 15:43:10', 0.000000, NULL),
(11, 'Wasakaka', 'kg', 4.000000, 6.803000, '2026-02-06 15:43:22', 0.000000, NULL),
(12, 'salsa americana', 'kg', 1.700000, 4.923600, '2026-02-06 15:42:35', 0.000000, NULL),
(13, 'Mezcla para Rebozar (crispy)', 'kg', 3.620000, 4.398062, '2026-02-06 15:41:59', 0.000000, NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `exchange_rate` decimal(10,4) DEFAULT 1.0000,
  `status` enum('pending','paid','preparing','ready','delivered','cancelled') DEFAULT 'pending',
  `consumption_type` enum('dine_in','takeaway','delivery') DEFAULT 'dine_in',
  `shipping_address` text NOT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `kds_kitchen_ready` tinyint(1) DEFAULT 0,
  `kds_pizza_ready` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `exchange_rate`, `status`, `consumption_type`, `shipping_address`, `shipping_method`, `tracking_number`, `created_at`, `updated_at`, `kds_kitchen_ready`, `kds_pizza_ready`) VALUES
(1, 4, 14.00, 490.0000, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2026-01-17 03:20:59', '2026-02-13 20:47:55', 0, 0),
(2, 4, 4.00, 490.0000, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2026-01-17 22:06:06', '2026-02-13 20:47:55', 0, 0),
(4, 4, 18.00, 490.0000, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2026-01-19 03:26:38', '2026-02-13 20:47:55', 0, 0),
(5, 4, 23.00, 490.0000, 'delivered', 'dine_in', 'Tienda Física', NULL, NULL, '2026-01-19 07:01:16', '2026-02-13 20:47:55', 0, 0),
(7, 4, 33.50, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, '', '2026-01-20 22:58:30', '2026-02-13 20:47:55', 0, 0),
(8, 4, 21.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 00:29:04', '2026-02-13 20:47:55', 0, 0),
(9, 4, 20.50, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 00:39:46', '2026-02-13 20:47:55', 0, 0),
(10, 4, 13.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 03:09:20', '2026-02-13 20:47:55', 0, 0),
(11, 4, 23.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 03:44:23', '2026-02-13 20:47:55', 0, 0),
(12, 4, 5.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 04:26:10', '2026-02-13 20:47:55', 0, 0),
(13, 4, 39.50, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 04:35:41', '2026-02-13 20:47:55', 0, 0),
(14, 4, 11.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 05:54:07', '2026-02-13 20:47:55', 0, 0),
(15, 4, 2.54, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 20:14:12', '2026-02-13 20:47:55', 0, 0),
(16, 4, 13.54, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 20:18:48', '2026-02-13 20:47:55', 0, 0),
(17, 4, 5.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 21:55:52', '2026-02-13 20:47:55', 1, 0),
(18, 4, 23.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-21 22:06:20', '2026-02-13 20:47:55', 1, 1),
(21, 4, 33.54, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-01-24 22:37:47', '2026-02-13 20:47:55', 1, 1),
(22, 4, 25.54, 490.0000, 'delivered', 'dine_in', 'juan gonsalez', NULL, NULL, '2026-01-25 04:27:56', '2026-02-13 20:47:55', 1, 1),
(23, 4, 17.00, 490.0000, 'delivered', 'dine_in', 'juan gonsalez', NULL, NULL, '2026-01-25 08:05:39', '2026-02-13 20:47:55', 1, 1),
(24, 4, 230.00, 490.0000, 'delivered', 'dine_in', 'juan gonsalez', NULL, NULL, '2026-01-25 08:17:00', '2026-02-13 20:47:55', 1, 1),
(25, 4, 2.00, 490.0000, 'delivered', 'dine_in', 'juan gonsalez', NULL, NULL, '2026-02-02 11:05:17', '2026-02-13 20:47:55', 1, 0),
(26, 4, 6.50, 490.0000, 'delivered', 'dine_in', 'juan gonsalez', NULL, NULL, '2026-02-02 12:31:52', '2026-02-13 20:47:55', 1, 0),
(27, 4, 6.50, 490.0000, 'delivered', 'dine_in', 'juan gonsalez', NULL, NULL, '2026-02-02 13:04:42', '2026-02-13 20:47:55', 1, 0),
(28, 4, 6.50, 490.0000, 'delivered', 'dine_in', 'juan gonsalez', NULL, NULL, '2026-02-02 13:21:26', '2026-02-13 20:47:55', 1, 0),
(29, 4, 24.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-02-06 22:42:22', '2026-02-13 20:47:55', 1, 1),
(30, 4, 17.00, 490.0000, 'delivered', 'dine_in', 'roberto perozo', NULL, NULL, '2026-02-08 00:44:48', '2026-02-13 20:47:55', 1, 1),
(31, 4, 28.00, 490.0000, 'delivered', 'dine_in', 'roberto perozo', NULL, NULL, '2026-02-09 19:17:21', '2026-02-13 20:47:55', 1, 1),
(32, 4, 11.00, 490.0000, 'delivered', 'dine_in', 'roberto perozo', NULL, NULL, '2026-02-09 19:34:44', '2026-02-13 20:47:55', 0, 1),
(33, 4, 8.50, 490.0000, 'delivered', 'dine_in', 'roberto perozo', NULL, NULL, '2026-02-09 19:35:20', '2026-02-13 20:47:55', 1, 0),
(34, 33, 11.00, 490.0000, 'delivered', 'dine_in', 'juan perez', NULL, NULL, '2026-02-09 20:18:38', '2026-02-13 20:47:55', 0, 1),
(35, 4, 18.00, 500.0000, 'delivered', 'dine_in', 'robero fermin', NULL, NULL, '2026-02-12 22:29:32', '2026-02-13 20:47:55', 1, 0),
(36, 4, 16.00, 500.0000, 'ready', 'dine_in', 'robero gonsalez', NULL, NULL, '2026-02-12 22:37:38', '2026-02-13 20:47:55', 1, 1),
(42, 4, 2.00, 500.0000, 'preparing', 'dine_in', 'robero perez', NULL, NULL, '2026-02-13 03:04:23', '2026-02-13 20:47:55', 0, 0),
(43, 4, 4.00, 500.0000, 'preparing', 'dine_in', 'robero gonsalez', NULL, NULL, '2026-02-13 03:15:32', '2026-02-13 20:47:55', 0, 0),
(44, 4, 16.00, 500.0000, 'ready', 'dine_in', 'roberto perozo', NULL, NULL, '2026-02-13 18:57:18', '2026-02-13 20:47:55', 0, 1),
(45, 4, 15.00, 500.0000, 'ready', 'dine_in', 'robero gonsalez', NULL, NULL, '2026-02-13 18:59:05', '2026-02-13 20:47:55', 0, 1),
(46, 4, 12.00, 500.0000, 'ready', 'dine_in', 'robero perez', NULL, NULL, '2026-02-13 18:59:51', '2026-02-13 20:47:55', 1, 1),
(47, 4, 4.00, 500.0000, 'ready', 'dine_in', 'juan gonsalez', NULL, NULL, '2026-02-13 19:02:18', '2026-02-13 20:47:55', 1, 0);

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
  `cost_at_sale` decimal(10,2) DEFAULT 0.00,
  `consumption_type` enum('dine_in','takeaway','delivery') DEFAULT 'dine_in',
  `parent_item_id` int(11) DEFAULT NULL,
  `price_override` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `cost_at_sale`, `consumption_type`, `parent_item_id`, `price_override`) VALUES
(1, 1, 180, 2, 5.00, 2.11, 'dine_in', NULL, NULL),
(2, 1, 180, 1, 4.00, 2.11, 'dine_in', NULL, NULL),
(3, 2, 180, 1, 4.00, 2.11, 'dine_in', NULL, NULL),
(7, 4, 201, 1, 18.00, 11.72, 'dine_in', NULL, NULL),
(8, 5, 138, 1, 23.00, 19.90, 'dine_in', NULL, NULL),
(13, 7, 138, 1, 23.00, 19.90, 'dine_in', NULL, NULL),
(14, 7, 180, 1, 5.00, 2.11, 'dine_in', NULL, NULL),
(16, 7, 101, 1, 1.50, 1.08, 'dine_in', NULL, NULL),
(17, 8, 202, 1, 15.00, 9.98, 'dine_in', NULL, NULL),
(18, 8, 180, 1, 4.00, 2.11, 'dine_in', NULL, NULL),
(19, 8, 134, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(20, 9, 193, 1, 11.00, 7.17, 'dine_in', NULL, NULL),
(21, 9, 180, 1, 5.00, 2.11, 'dine_in', NULL, NULL),
(22, 9, 136, 1, 3.00, 2.40, 'dine_in', NULL, NULL),
(23, 9, 101, 1, 1.50, 1.08, 'dine_in', NULL, NULL),
(24, 10, 194, 1, 13.00, 11.75, 'dine_in', NULL, NULL),
(25, 11, 138, 1, 23.00, 19.90, 'dine_in', NULL, NULL),
(26, 12, 180, 1, 5.00, 2.11, 'dine_in', NULL, NULL),
(27, 13, 146, 1, 16.50, 11.77, 'dine_in', NULL, NULL),
(28, 13, 138, 1, 23.00, 19.90, 'dine_in', NULL, NULL),
(29, 14, 193, 1, 11.00, 7.17, 'dine_in', NULL, NULL),
(30, 15, 134, 1, 2.54, 1.60, 'dine_in', NULL, NULL),
(31, 16, 193, 1, 11.00, 7.17, 'dine_in', NULL, NULL),
(32, 16, 134, 1, 2.54, 1.60, 'dine_in', NULL, NULL),
(33, 17, 180, 1, 5.00, 2.11, 'dine_in', NULL, NULL),
(34, 18, 138, 1, 23.00, 19.90, 'dine_in', NULL, NULL),
(37, 21, 146, 1, 15.00, 11.77, 'dine_in', NULL, NULL),
(38, 21, 205, 1, 16.00, 10.37, 'dine_in', NULL, NULL),
(39, 21, 134, 1, 2.54, 1.60, 'dine_in', NULL, NULL),
(40, 22, 134, 1, 2.54, 1.60, 'dine_in', NULL, NULL),
(41, 22, 175, 1, 4.00, 0.53, 'dine_in', NULL, NULL),
(42, 22, 179, 1, 4.00, 2.17, 'dine_in', NULL, NULL),
(43, 22, 140, 1, 15.00, 11.53, 'dine_in', NULL, NULL),
(44, 23, 139, 1, 17.00, 15.24, 'dine_in', NULL, NULL),
(45, 24, 158, 40, 4.00, 2.71, 'dine_in', NULL, NULL),
(46, 24, 164, 35, 2.00, 1.47, 'dine_in', NULL, NULL),
(47, 25, 91, 1, 2.00, 1.02, 'dine_in', NULL, NULL),
(48, 26, 97, 1, 6.50, 5.46, 'dine_in', NULL, NULL),
(49, 27, 97, 1, 6.50, 5.46, 'dine_in', NULL, NULL),
(50, 28, 97, 1, 6.50, 5.46, 'dine_in', NULL, NULL),
(51, 28, 174, 1, 0.00, 1.11, 'dine_in', NULL, NULL),
(52, 29, 141, 1, 13.00, 13.63, 'dine_in', NULL, NULL),
(53, 29, 206, 1, 11.00, 5.57, 'dine_in', NULL, NULL),
(54, 30, 139, 1, 17.00, 15.24, 'dine_in', NULL, NULL),
(55, 31, 141, 1, 13.00, 13.63, 'dine_in', NULL, NULL),
(56, 31, 140, 1, 15.00, 11.53, 'dine_in', NULL, NULL),
(57, 32, 206, 1, 11.00, 5.57, 'dine_in', NULL, NULL),
(58, 33, 97, 1, 8.50, 5.46, 'dine_in', NULL, NULL),
(59, 33, 174, 1, 0.00, 1.11, 'dine_in', NULL, NULL),
(60, 34, 206, 1, 11.00, 5.57, 'dine_in', NULL, NULL),
(61, 35, 216, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(62, 35, 216, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(63, 35, 143, 1, 12.00, 9.72, 'dine_in', NULL, NULL),
(64, 35, 216, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(65, 36, 216, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(66, 36, 206, 1, 11.00, 5.57, 'dine_in', NULL, NULL),
(67, 36, 174, 1, 3.00, 1.11, 'dine_in', NULL, NULL),
(77, 42, 216, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(78, 43, 216, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(79, 43, 216, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(80, 44, 216, 1, 2.00, 1.60, 'dine_in', NULL, NULL),
(81, 44, 206, 1, 14.00, 5.57, 'dine_in', NULL, NULL),
(82, 45, 140, 1, 15.00, 11.53, 'dine_in', NULL, NULL),
(83, 46, 145, 1, 12.00, 9.63, 'dine_in', NULL, NULL),
(84, 47, 180, 1, 4.00, 2.11, 'dine_in', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_item_modifiers`
--

CREATE TABLE `order_item_modifiers` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `modifier_type` enum('add','remove','info','side','companion') NOT NULL,
  `component_id` int(11) DEFAULT NULL,
  `quantity_adjustment` decimal(10,4) DEFAULT 0.0000,
  `price_adjustment_usd` decimal(20,6) DEFAULT 0.000000,
  `note` varchar(255) DEFAULT NULL,
  `sub_item_index` int(11) DEFAULT 0,
  `is_takeaway` tinyint(1) DEFAULT 0,
  `component_type` enum('raw','manufactured','product') DEFAULT 'raw'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `order_item_modifiers`
--

INSERT INTO `order_item_modifiers` (`id`, `order_item_id`, `modifier_type`, `component_id`, `quantity_adjustment`, `price_adjustment_usd`, `note`, `sub_item_index`, `is_takeaway`, `component_type`) VALUES
(1, 1, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(2, 1, 'remove', 52, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(3, 1, 'remove', 18, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(4, 1, 'add', 108, 0.0500, 1.000000, NULL, 0, 0, 'raw'),
(5, 1, 'side', 6, 0.0800, 0.000000, NULL, 0, 0, 'manufactured'),
(8, 2, 'info', NULL, 0.0000, 0.000000, 'sin lechuga', -1, 0, 'raw'),
(9, 2, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(10, 2, 'remove', 18, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(11, 2, 'side', 8, 0.0800, 0.000000, NULL, 0, 0, 'manufactured'),
(15, 3, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(16, 3, 'side', 6, 0.0800, 0.000000, NULL, 0, 0, 'manufactured'),
(18, 8, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(19, 8, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(20, 8, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(21, 8, 'side', 53, 100.0000, 0.000000, NULL, 2, 0, 'raw'),
(22, 8, 'side', 29, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(23, 8, 'side', 7, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(24, 8, 'side', 36, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(25, 8, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(26, 8, 'side', 53, 100.0000, 0.000000, NULL, 3, 0, 'raw'),
(27, 8, 'side', 29, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(28, 8, 'side', 7, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(29, 8, 'side', 36, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(30, 8, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(31, 8, 'side', 30, 0.1500, 0.000000, NULL, 4, 0, 'raw'),
(32, 8, 'side', 53, 100.0000, 0.000000, NULL, 4, 0, 'raw'),
(33, 8, 'side', 29, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(34, 8, 'side', 7, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(79, 13, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(80, 13, 'info', NULL, 0.0000, 0.000000, NULL, 1, 1, 'raw'),
(81, 13, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(82, 13, 'side', 37, 100.0000, 0.000000, NULL, 2, 0, 'raw'),
(83, 13, 'side', 95, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(84, 13, 'side', 28, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(85, 13, 'side', 30, 0.1500, 0.000000, NULL, 2, 0, 'raw'),
(86, 13, 'info', NULL, 0.0000, 0.000000, NULL, 3, 1, 'raw'),
(87, 13, 'side', 30, 0.1500, 0.000000, NULL, 3, 0, 'raw'),
(88, 13, 'side', 53, 100.0000, 0.000000, NULL, 3, 0, 'raw'),
(89, 13, 'side', 7, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(90, 13, 'side', 36, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(91, 13, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(92, 13, 'side', 54, 80.0000, 0.000000, NULL, 4, 0, 'raw'),
(93, 13, 'side', 29, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(94, 13, 'side', 7, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(95, 13, 'side', 36, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(110, 14, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(111, 14, 'remove', 52, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(112, 14, 'remove', 18, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(113, 14, 'add', 108, 0.0500, 1.000000, NULL, 0, 0, 'raw'),
(114, 14, 'side', 6, 0.0800, 0.000000, NULL, 0, 0, 'manufactured'),
(117, 15, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(118, 15, 'remove', 18, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(119, 15, 'remove', 52, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(120, 16, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(121, 16, 'remove', 18, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(122, 16, 'remove', 52, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(123, 20, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(124, 20, 'side', 28, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(125, 20, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw'),
(126, 20, 'side', 53, 100.0000, 0.000000, NULL, 0, 0, 'raw'),
(127, 20, 'side', 7, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(130, 21, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(131, 21, 'remove', 52, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(132, 21, 'add', 108, 0.0500, 1.000000, NULL, 0, 0, 'raw'),
(133, 21, 'side', 6, 0.0800, 0.000000, NULL, 0, 0, 'manufactured'),
(137, 23, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(138, 23, 'remove', 18, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(140, 24, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(141, 24, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw'),
(142, 24, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(143, 24, 'side', 7, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(147, 25, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(148, 25, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(149, 25, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(150, 25, 'side', 30, 0.1500, 0.000000, NULL, 2, 0, 'raw'),
(151, 25, 'side', 53, 100.0000, 0.000000, NULL, 2, 0, 'raw'),
(152, 25, 'side', 29, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(153, 25, 'side', 7, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(154, 25, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(155, 25, 'side', 30, 0.1500, 0.000000, NULL, 3, 0, 'raw'),
(156, 25, 'side', 53, 100.0000, 0.000000, NULL, 3, 0, 'raw'),
(157, 25, 'side', 29, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(158, 25, 'side', 7, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(159, 25, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(160, 25, 'side', 30, 0.1500, 0.000000, NULL, 4, 0, 'raw'),
(161, 25, 'side', 53, 100.0000, 0.000000, NULL, 4, 0, 'raw'),
(162, 25, 'side', 29, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(163, 25, 'side', 7, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(178, 26, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(179, 26, 'remove', 52, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(180, 26, 'add', 108, 0.0500, 1.000000, NULL, 0, 0, 'raw'),
(181, 26, 'side', 8, 0.0800, 0.000000, NULL, 0, 0, 'manufactured'),
(185, 27, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(186, 27, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(187, 27, 'remove', 52, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(188, 27, 'side', 3, 0.0400, 0.000000, NULL, 1, 0, 'manufactured'),
(189, 27, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(190, 27, 'remove', 52, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(191, 27, 'side', 3, 0.0400, 0.000000, NULL, 2, 0, 'manufactured'),
(192, 27, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(193, 27, 'remove', 52, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(194, 27, 'side', 3, 0.0400, 0.000000, NULL, 3, 0, 'manufactured'),
(195, 27, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(196, 27, 'remove', 52, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(197, 27, 'side', 3, 0.0400, 0.000000, NULL, 4, 0, 'manufactured'),
(198, 27, 'info', NULL, 0.0000, 0.000000, NULL, 5, 0, 'raw'),
(199, 27, 'remove', 52, 0.0000, 0.000000, NULL, 5, 0, 'raw'),
(200, 27, 'side', 3, 0.0400, 0.000000, NULL, 5, 0, 'manufactured'),
(201, 27, 'info', NULL, 0.0000, 0.000000, NULL, 6, 0, 'raw'),
(202, 27, 'remove', 52, 0.0000, 0.000000, NULL, 6, 0, 'raw'),
(203, 27, 'side', 3, 0.0400, 0.000000, NULL, 6, 0, 'manufactured'),
(204, 27, 'info', NULL, 0.0000, 0.000000, NULL, 7, 0, 'raw'),
(205, 27, 'remove', 52, 0.0000, 0.000000, NULL, 7, 0, 'raw'),
(206, 27, 'side', 3, 0.0400, 0.000000, NULL, 7, 0, 'manufactured'),
(207, 27, 'info', NULL, 0.0000, 0.000000, NULL, 8, 0, 'raw'),
(208, 27, 'remove', 52, 0.0000, 0.000000, NULL, 8, 0, 'raw'),
(209, 27, 'side', 6, 0.0450, 0.500000, NULL, 8, 0, 'manufactured'),
(210, 27, 'info', NULL, 0.0000, 0.000000, NULL, 9, 0, 'raw'),
(211, 27, 'remove', 52, 0.0000, 0.000000, NULL, 9, 0, 'raw'),
(212, 27, 'side', 6, 0.0450, 0.500000, NULL, 9, 0, 'manufactured'),
(213, 27, 'info', NULL, 0.0000, 0.000000, NULL, 10, 0, 'raw'),
(214, 27, 'remove', 52, 0.0000, 0.000000, NULL, 10, 0, 'raw'),
(215, 27, 'side', 6, 0.0450, 0.500000, NULL, 10, 0, 'manufactured'),
(216, 28, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(217, 28, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(218, 28, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(219, 28, 'side', 30, 0.1500, 0.000000, NULL, 2, 0, 'raw'),
(220, 28, 'side', 53, 100.0000, 0.000000, NULL, 2, 0, 'raw'),
(221, 28, 'side', 29, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(222, 28, 'side', 7, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(223, 28, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(224, 28, 'side', 30, 0.1500, 0.000000, NULL, 3, 0, 'raw'),
(225, 28, 'side', 53, 100.0000, 0.000000, NULL, 3, 0, 'raw'),
(226, 28, 'side', 29, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(227, 28, 'side', 7, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(228, 28, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(229, 28, 'side', 30, 0.1500, 0.000000, NULL, 4, 0, 'raw'),
(230, 28, 'side', 53, 100.0000, 0.000000, NULL, 4, 0, 'raw'),
(231, 28, 'side', 29, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(232, 28, 'side', 7, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(247, 29, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(248, 29, 'remove', 31, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(249, 29, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw'),
(250, 29, 'side', 53, 100.0000, 0.000000, NULL, 0, 0, 'raw'),
(251, 29, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(252, 29, 'side', 7, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(253, 31, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(254, 31, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw'),
(255, 31, 'side', 53, 100.0000, 0.000000, NULL, 0, 0, 'raw'),
(256, 31, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(257, 31, 'side', 7, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(260, 32, 'info', NULL, 0.0000, 0.000000, 'Delivery', -1, 0, 'raw'),
(261, 32, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(263, 33, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(264, 33, 'remove', 52, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(265, 33, 'add', 108, 0.0500, 1.000000, NULL, 0, 0, 'raw'),
(266, 33, 'side', 6, 0.0800, 0.000000, NULL, 0, 0, 'manufactured'),
(270, 34, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(271, 34, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(272, 34, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(273, 34, 'side', 30, 0.1500, 0.000000, NULL, 2, 0, 'raw'),
(274, 34, 'side', 53, 100.0000, 0.000000, NULL, 2, 0, 'raw'),
(275, 34, 'side', 29, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(276, 34, 'side', 7, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(277, 34, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(278, 34, 'side', 30, 0.1500, 0.000000, NULL, 3, 0, 'raw'),
(279, 34, 'side', 53, 100.0000, 0.000000, NULL, 3, 0, 'raw'),
(280, 34, 'side', 29, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(281, 34, 'side', 7, 0.1000, 0.000000, NULL, 3, 0, 'raw'),
(282, 34, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(283, 34, 'side', 30, 0.1500, 0.000000, NULL, 4, 0, 'raw'),
(284, 34, 'side', 53, 100.0000, 0.000000, NULL, 4, 0, 'raw'),
(285, 34, 'side', 29, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(286, 34, 'side', 7, 0.1000, 0.000000, NULL, 4, 0, 'raw'),
(287, 35, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(288, 35, 'add', 1, 0.0500, 2.500000, NULL, 0, 0, 'manufactured'),
(290, 36, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(291, 36, 'add', 1, 1.0000, 2.500000, NULL, 0, 0, 'manufactured'),
(292, 37, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(293, 37, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(294, 37, 'side', 3, 0.0400, 0.000000, NULL, 1, 0, 'manufactured'),
(295, 37, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(296, 37, 'side', 3, 0.0400, 0.000000, NULL, 2, 0, 'manufactured'),
(297, 37, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(298, 37, 'side', 3, 0.0400, 0.000000, NULL, 3, 0, 'manufactured'),
(299, 37, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(300, 37, 'side', 3, 0.0400, 0.000000, NULL, 4, 0, 'manufactured'),
(301, 37, 'info', NULL, 0.0000, 0.000000, NULL, 5, 0, 'raw'),
(302, 37, 'side', 3, 0.0400, 0.000000, NULL, 5, 0, 'manufactured'),
(303, 37, 'info', NULL, 0.0000, 0.000000, NULL, 6, 0, 'raw'),
(304, 37, 'side', 3, 0.0400, 0.000000, NULL, 6, 0, 'manufactured'),
(305, 37, 'info', NULL, 0.0000, 0.000000, NULL, 7, 0, 'raw'),
(306, 37, 'side', 3, 0.0400, 0.000000, NULL, 7, 0, 'manufactured'),
(307, 37, 'info', NULL, 0.0000, 0.000000, NULL, 8, 0, 'raw'),
(308, 37, 'side', 3, 0.0400, 0.000000, NULL, 8, 0, 'manufactured'),
(309, 37, 'info', NULL, 0.0000, 0.000000, NULL, 9, 0, 'raw'),
(310, 37, 'side', 3, 0.0400, 0.000000, NULL, 9, 0, 'manufactured'),
(311, 37, 'info', NULL, 0.0000, 0.000000, NULL, 10, 0, 'raw'),
(312, 37, 'side', 3, 0.0400, 0.000000, NULL, 10, 0, 'manufactured'),
(323, 38, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(324, 38, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(325, 38, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw'),
(326, 38, 'side', 53, 100.0000, 0.000000, NULL, 0, 0, 'raw'),
(327, 38, 'side', 7, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(330, 41, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(331, 42, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(332, 43, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(333, 43, 'info', NULL, 0.0000, 0.000000, NULL, 1, 1, 'raw'),
(334, 43, 'side', 29, 0.1000, 0.000000, NULL, 1, 0, 'raw'),
(335, 43, 'side', 30, 0.1500, 0.000000, NULL, 1, 0, 'raw'),
(336, 43, 'info', NULL, 0.0000, 0.000000, NULL, 2, 1, 'raw'),
(337, 43, 'side', 29, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(338, 43, 'side', 7, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(339, 44, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(340, 44, 'info', NULL, 0.0000, 0.000000, NULL, 1, 1, 'raw'),
(341, 44, 'side', 29, 0.1000, 0.000000, NULL, 1, 0, 'raw'),
(342, 44, 'side', 30, 0.1500, 0.000000, NULL, 1, 0, 'raw'),
(343, 44, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(344, 44, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(345, 44, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(346, 44, 'info', NULL, 0.0000, 0.000000, NULL, 5, 0, 'raw'),
(347, 44, 'info', NULL, 0.0000, 0.000000, NULL, 6, 0, 'raw'),
(354, 47, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(355, 47, 'side', 6, 0.0450, 0.500000, NULL, 0, 0, 'manufactured'),
(357, 48, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(358, 48, 'companion', 174, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(360, 49, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(361, 49, 'companion', 174, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(362, 53, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(363, 53, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(364, 53, 'side', 7, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(365, 54, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(366, 54, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(367, 54, 'side', 29, 0.1000, 0.000000, NULL, 1, 0, 'raw'),
(368, 54, 'side', 30, 0.1500, 0.000000, NULL, 1, 0, 'raw'),
(369, 54, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(370, 54, 'side', 93, 1.0000, 0.000000, NULL, 2, 0, 'product'),
(371, 54, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(372, 54, 'side', 6, 0.0900, 0.000000, NULL, 3, 0, 'manufactured'),
(373, 54, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(374, 54, 'info', NULL, 0.0000, 0.000000, NULL, 5, 0, 'raw'),
(375, 54, 'side', 15, 1.0000, 0.000000, NULL, 5, 0, 'raw'),
(376, 54, 'info', NULL, 0.0000, 0.000000, NULL, 6, 0, 'raw'),
(377, 54, 'side', 4, 1.0000, 0.000000, NULL, 6, 0, 'raw'),
(380, 55, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(381, 55, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(382, 55, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(383, 55, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(384, 55, 'side', 98, 1.0000, 0.000000, NULL, 2, 0, 'product'),
(385, 55, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(386, 55, 'side', 207, 1.0000, 0.000000, NULL, 3, 0, 'product'),
(387, 56, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(388, 56, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(389, 56, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(390, 56, 'side', 29, 0.1000, 0.000000, NULL, 1, 0, 'raw'),
(391, 56, 'side', 30, 0.1500, 0.000000, NULL, 1, 0, 'raw'),
(392, 56, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(393, 56, 'side', 29, 0.1000, 0.000000, NULL, 2, 0, 'raw'),
(394, 56, 'side', 30, 0.1500, 0.000000, NULL, 2, 0, 'raw'),
(402, 57, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(403, 57, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw'),
(404, 57, 'side', 28, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(405, 58, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(406, 58, 'side', 207, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(408, 60, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(409, 60, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw'),
(410, 60, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(411, 61, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(412, 61, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(414, 62, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(415, 62, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(417, 63, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(418, 63, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(419, 63, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(420, 63, 'side', 6, 0.0900, 0.000000, NULL, 1, 0, 'manufactured'),
(421, 63, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(422, 63, 'side', 6, 0.0900, 0.000000, NULL, 2, 0, 'manufactured'),
(423, 63, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(424, 63, 'side', 6, 0.0900, 0.000000, NULL, 3, 0, 'manufactured'),
(432, 64, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(433, 64, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(435, 65, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(436, 65, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(438, 66, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(439, 66, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(440, 66, 'side', 30, 0.1500, 0.000000, NULL, 0, 0, 'raw'),
(468, 77, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(469, 77, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(471, 78, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(472, 78, 'side', 214, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(474, 79, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(475, 79, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(477, 80, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(478, 80, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(480, 81, 'info', NULL, 0.0000, 0.000000, NULL, 0, 1, 'raw'),
(481, 81, 'add', 31, 0.2000, 3.000000, NULL, 0, 0, 'raw'),
(482, 81, 'side', 29, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(483, 81, 'side', 7, 0.1000, 0.000000, NULL, 0, 0, 'raw'),
(487, 82, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(488, 82, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(489, 82, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(490, 82, 'side', 30, 0.1500, 0.000000, NULL, 1, 0, 'raw'),
(491, 82, 'side', 29, 0.1000, 0.000000, NULL, 1, 0, 'raw'),
(492, 82, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(493, 82, 'side', 30, 0.1500, 0.000000, NULL, 2, 0, 'raw'),
(494, 82, 'side', 53, 100.0000, 0.000000, NULL, 2, 0, 'raw'),
(502, 83, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(503, 83, 'side', 208, 1.0000, 0.000000, NULL, 0, 0, 'product'),
(504, 83, 'info', NULL, 0.0000, 0.000000, NULL, 1, 0, 'raw'),
(505, 83, 'side', 30, 0.1500, 0.000000, NULL, 1, 0, 'raw'),
(506, 83, 'side', 29, 0.1000, 0.000000, NULL, 1, 0, 'raw'),
(507, 83, 'info', NULL, 0.0000, 0.000000, NULL, 2, 0, 'raw'),
(508, 83, 'side', 3, 0.0400, 0.000000, NULL, 2, 0, 'manufactured'),
(509, 83, 'info', NULL, 0.0000, 0.000000, NULL, 3, 0, 'raw'),
(510, 83, 'side', 3, 0.0400, 0.000000, NULL, 3, 0, 'manufactured'),
(511, 83, 'info', NULL, 0.0000, 0.000000, NULL, 4, 0, 'raw'),
(512, 83, 'side', 3, 0.0400, 0.000000, NULL, 4, 0, 'manufactured'),
(517, 84, 'info', NULL, 0.0000, 0.000000, NULL, 0, 0, 'raw'),
(518, 84, 'side', 6, 0.0900, 0.000000, NULL, 0, 0, 'manufactured');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `production_orders`
--

INSERT INTO `production_orders` (`id`, `manufactured_product_id`, `quantity_produced`, `labor_cost`, `total_cost`, `created_by`, `created_at`) VALUES
(37, 3, 8.0000, 0.000000, 62.486520, 4, '2026-02-06 19:39:55'),
(38, 4, 4.0000, 0.000000, 44.401202, 4, '2026-02-06 19:40:07'),
(39, 5, 4.0000, 0.000000, 68.808000, 4, '2026-02-06 19:40:21'),
(40, 6, 4.0000, 0.000000, 26.843240, 4, '2026-02-06 19:40:36'),
(41, 8, 4.0000, 0.000000, 60.326362, 4, '2026-02-06 19:40:48'),
(42, 1, 8.0000, 0.000000, 11.131200, 4, '2026-02-06 19:41:45'),
(43, 13, 4.0000, 0.000000, 17.592248, 4, '2026-02-06 19:41:59'),
(44, 7, 4.0000, 0.000000, 127.448000, 4, '2026-02-06 19:42:12'),
(45, 9, 4.0000, 0.000000, 5.831000, 4, '2026-02-06 19:42:24'),
(46, 12, 2.0000, 0.000000, 9.847200, 4, '2026-02-06 19:42:35'),
(47, 2, 8.0000, 0.000000, 0.970000, 4, '2026-02-06 19:42:59'),
(48, 10, 50.0000, 0.000000, 2.530000, 4, '2026-02-06 19:43:10'),
(49, 11, 4.0000, 0.000000, 27.212000, 4, '2026-02-06 19:43:22'),
(50, 1, 8.0000, 0.000000, 11.131200, 4, '2026-02-13 21:49:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `production_recipes`
--

CREATE TABLE `production_recipes` (
  `id` int(11) NOT NULL,
  `manufactured_product_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `quantity_required` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `production_recipes`
--

INSERT INTO `production_recipes` (`id`, `manufactured_product_id`, `raw_material_id`, `quantity_required`) VALUES
(1, 1, 2, 1.0000),
(2, 1, 35, 0.0020),
(3, 1, 34, 0.0420),
(4, 1, 1, 0.0420),
(5, 1, 49, 0.5000),
(6, 1, 40, 0.0800),
(7, 2, 74, 0.0220),
(8, 2, 54, 11.0000),
(9, 2, 40, 0.0055),
(11, 2, 66, 3.2400),
(12, 2, 1, 0.0032),
(13, 2, 65, 0.5400),
(29, 5, 5, 1.0000),
(30, 5, 68, 24.0000),
(31, 5, 67, 8.0000),
(32, 5, 69, 8.0000),
(33, 5, 70, 4.0000),
(34, 5, 20, 0.1600),
(36, 5, 85, 50.0000),
(37, 5, 86, 100.0000),
(38, 5, 87, 100.0000),
(39, 5, 54, 100.0000),
(47, 7, 68, 2.0000),
(48, 7, 70, 4.0000),
(49, 7, 66, 2.0000),
(50, 7, 88, 0.5000),
(51, 7, 89, 1.8000),
(57, 10, 92, 0.0500),
(58, 10, 40, 0.0020),
(65, 11, 87, 40.0000),
(66, 11, 93, 40.0000),
(67, 11, 74, 0.0200),
(68, 11, 40, 0.0100),
(69, 12, 72, 100.0000),
(70, 12, 94, 100.0000),
(71, 12, 16, 0.4000),
(72, 12, 19, 0.4000),
(85, 11, 19, 0.9000),
(95, 3, 83, 0.5376),
(96, 3, 84, 0.2143),
(97, 3, 20, 0.1023),
(98, 3, 17, 0.0699),
(99, 3, 74, 0.0429),
(100, 3, 68, 19.5489),
(101, 3, 67, 4.5113),
(102, 3, 70, 4.5113),
(103, 3, 69, 4.5113),
(104, 4, 68, 26.3158),
(105, 4, 83, 0.8772),
(106, 4, 70, 5.2632),
(107, 4, 74, 0.0351),
(108, 4, 20, 0.0281),
(109, 4, 17, 0.0281),
(110, 6, 70, 3.0722),
(111, 6, 68, 18.4332),
(112, 6, 6, 0.7680),
(113, 6, 2, 0.1536),
(114, 6, 66, 1.5361),
(115, 6, 74, 0.0307),
(116, 6, 20, 0.0246),
(121, 13, 99, 236.9668),
(122, 13, 91, 4.7393),
(123, 13, 2, 0.4739),
(124, 13, 15, 5.6872),
(125, 8, 3, 0.9141),
(126, 8, 68, 24.6801),
(127, 8, 70, 6.3985),
(128, 8, 17, 0.0548),
(129, 9, 2, 0.3750),
(130, 9, 15, 1.8750),
(131, 9, 18, 0.0750),
(132, 9, 91, 6.2500);

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
  `linked_manufactured_id` int(11) DEFAULT NULL,
  `max_sides` int(11) DEFAULT 0,
  `contour_logic_type` enum('standard','proportional') DEFAULT 'standard',
  `min_stock` int(11) DEFAULT 5,
  `category_id` int(11) DEFAULT NULL,
  `short_code` varchar(50) DEFAULT NULL,
  `is_visible` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price_usd`, `price_ves`, `stock`, `product_type`, `kitchen_station`, `image_url`, `created_at`, `profit_margin`, `updated_at`, `linked_manufactured_id`, `max_sides`, `contour_logic_type`, `min_stock`, `category_id`, `short_code`, `is_visible`) VALUES
(91, 'MINI ', '', 2.00, 1000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/ad0b8457e7d895856c073200f96ed844.png', '2025-12-27 20:55:13', 20.00, '2026-02-10 03:44:40', NULL, 1, 'standard', 5, 1, NULL, 1),
(93, 'POLLO CRISPY ', '', 5.00, 2500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/16aef05c9945a8245692ebacdb314da9.png', '2025-12-27 20:58:49', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 1, NULL, 0),
(97, 'AMERICANA ', '', 8.50, 4250.00, 0, 'prepared', 'kitchen', 'uploads/product_images/21834e9ed9cb0dfa3e9f34db69f5ad78.png', '2025-12-27 21:10:34', 20.00, '2026-02-10 03:44:40', NULL, 1, 'standard', 5, 1, NULL, 1),
(98, 'CRISPY ', '', 8.50, 4250.00, 0, 'prepared', 'kitchen', 'uploads/product_images/11399e3da013d3f605e2535a53fd85f2.png', '2025-12-27 21:11:21', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 1, NULL, 0),
(100, 'OKLAHOMA', '', 8.50, 4250.00, 0, 'prepared', 'kitchen', 'uploads/product_images/7fff51a7c01af46d7a7a23eaddd74a80.png', '2025-12-27 21:13:37', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 1, NULL, 0),
(101, 'PAN', '', 1.50, 750.00, 0, 'prepared', 'kitchen', 'uploads/product_images/f87ead2c1dadd1e74e8deaafe33900fd.png', '2025-12-27 21:15:52', 20.00, '2026-02-10 03:44:40', NULL, 1, 'standard', 5, 6, NULL, 1),
(134, 'REFRESCO ', '', 2.00, 1000.00, 0, 'compound', NULL, 'uploads/product_images/c21daf1f1fe88b03cde1854275cdc148.png', '2025-12-27 22:03:18', 30.00, '2026-02-12 20:26:48', NULL, 1, 'standard', 5, 3, NULL, 0),
(135, 'NESTEA', '', 3.00, 1500.00, 50, 'simple', NULL, 'uploads/product_images/0166cc0b690c3ba0e9326d3ffee8109e.png', '2025-12-27 22:03:45', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 3, NULL, 1),
(136, 'PAPELÓN', '', 3.00, 1500.00, 49, 'simple', NULL, 'uploads/product_images/80dad7a8d57e91e02f57f612922d5bea.png', '2025-12-27 22:04:09', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 3, NULL, 1),
(137, 'JUGO ', '', 1.00, 500.00, 50, 'simple', NULL, 'uploads/product_images/2c55fc33305b12e855c98638f97b5311.png', '2025-12-27 22:04:44', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 3, NULL, 1),
(138, 'COMBO 1', 'PIZZA FAMILIAR X3\r\nREFRESCO x2', 23.00, 11500.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:06:51', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(139, 'COMBO 2', 'PIZZA FAMILIAR\r\nWHOPER / PERROS X2\r\nPAPAS FRITAS\r\nREFRESCO', 17.00, 8500.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:09:14', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(140, 'COMBO 3', 'PIZZA FAMILIAR X2\r\nREFRESCO', 15.00, 7500.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:10:17', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(141, 'COMBO 4', 'AMERICANAS X2\r\nPAPAS FRITAS\r\nREFRESCO', 13.00, 6500.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:11:24', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(142, 'COMBO 5', 'PIZZA FAMILIAR X3 (1/2 KILO DE QUESO CON BORDE DE QUESO)\r\nREFRESCO x2', 35.00, 17500.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:14:24', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(143, 'COMBO 6', 'WHOPERS X3\r\nREFRESCO', 12.00, 6000.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:15:47', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(144, 'COMBO 7', 'PIZZA FAMILIAR\r\nMINIS X4\r\nPAPAS FRITAS\r\nREFRESCO', 15.00, 7500.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:16:59', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(145, 'COMBO 8', 'PIZZA FAMILIAR\r\nMINIS X3\r\nREFRESCO', 12.00, 6000.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:18:02', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(146, 'COMBO 9', 'MINIS X10 (CARNE / POLLO)\r\nREFRESCO', 15.00, 7500.00, 0, 'compound', 'kitchen', 'default.jpg', '2025-12-27 22:20:23', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 4, NULL, 1),
(153, 'WHOPPER ESPECIAL MARACUCHA', '', 12.00, 6000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/095879541c6987b785c950229e6b62ce.png', '2025-12-27 23:01:49', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 1, NULL, 1),
(154, 'WHOPPER ESPECIAL MEGA', '', 12.00, 6000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/6372d80bd8f86dc5cd322b8010b48a72.png', '2025-12-27 23:03:08', 20.00, '2026-02-10 03:44:40', NULL, 3, 'standard', 5, 1, NULL, 1),
(155, 'WHOPPER ESPECIAL TRIFÁSICA', '', 12.00, 6000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/375cf1015e13a189733d41511e7b8d69.png', '2025-12-27 23:03:44', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 1, NULL, 1),
(157, 'CRISPY CESAR', '', 8.50, 4250.00, 0, 'prepared', 'kitchen', 'uploads/product_images/de745b201a8558fa6d9143cba677e248.png', '2025-12-29 22:43:04', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 1, NULL, 0),
(158, 'WHOPPER', '', 5.00, 2500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/d315de58cbc243c6bd378d9fa9b327a4.png', '2025-12-29 23:47:09', 20.00, '2026-02-10 03:44:40', NULL, 1, 'standard', 5, 1, 'WS', 1),
(160, 'WHOPPER ESPECIAL', '', 8.00, 4000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/ee3ddeba9e7f8c3b6f1abb66615726e9.png', '2025-12-30 01:12:46', 20.00, '2026-02-10 03:44:40', NULL, 2, 'standard', 5, 1, 'WE', 1),
(164, 'PAN  SALCHIQUESO', '', 2.00, 1000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/96c1ec527c47902b5ff37dcce8cb20ec.png', '2026-01-16 00:25:57', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 6, NULL, 1),
(165, ' PAN SALCHIHUEVO', '', 2.00, 1000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/fda0d279dbcb6fad87a53917c0123a7f.png', '2026-01-16 00:27:57', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 6, NULL, 1),
(166, 'PAN HUEVO Y CEBÚ', '', 2.00, 1000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/caaabb5cc49143eb1e505036ba240349.png', '2026-01-16 00:30:00', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 6, NULL, 1),
(167, 'PAN CARNE MECHADA', '', 4.00, 2000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/7ada077a505b3b03c8aa2b48cb95ecbf.png', '2026-01-16 00:32:13', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 6, NULL, 1),
(168, 'PAN POLLO', '', 4.00, 2000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/e3908b9e4093363e93fafa6eaeb61b58.png', '2026-01-16 00:40:11', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 6, NULL, 1),
(169, 'PAN LOMO', '', 4.00, 2000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/63bdc6ec4b717cc913e57121dffa5892.png', '2026-01-16 00:41:46', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 6, NULL, 1),
(170, 'PATACÓN', '', 9.00, 4500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/a29b29c4800e2f2aa5b6729ea26d8116.png', '2026-01-16 01:12:07', 20.00, '2026-02-10 03:44:40', NULL, 2, 'standard', 5, 5, NULL, 1),
(171, 'PATACON GRATINADO', '', 11.00, 5500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/be23338ea72793b6b2096bee37e71329.png', '2026-01-16 01:18:46', 20.00, '2026-02-10 03:44:40', NULL, 2, 'standard', 5, 5, NULL, 1),
(172, 'SALCHIPAPAS', '', 9.00, 4500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/0bb16bd7a0d159c22ae3ef9c26035d98.png', '2026-01-16 01:24:29', 20.00, '2026-02-10 03:44:40', NULL, 2, 'standard', 5, 5, NULL, 1),
(173, 'MEGA SALCHIPAPAS', '', 20.00, 10000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/6ca61a48a6ab2f754f9b6b71f782514a.png', '2026-01-16 01:29:53', 20.00, '2026-02-10 03:44:40', NULL, 3, 'standard', 5, 5, NULL, 1),
(174, 'PAPAS FRITAS', '', 3.00, 1500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/1e422b9c7f3122daaa0398a63acb7c84.png', '2026-01-16 01:58:32', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 5, NULL, 1),
(175, 'TEQUEÑOS', '', 4.00, 2000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/4a4d9ac676ee64e4b6685c21524532df.png', '2026-01-16 02:01:16', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 5, NULL, 1),
(176, 'NUGGET CON PAPAS FRITAS', '', 5.00, 2500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/82deeea66962459f1e8f7a7e8ab5edc2.png', '2026-01-16 02:34:07', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 5, NULL, 1),
(178, 'TUMBARRANCHO SENCILLO', '', 1.50, 750.00, 0, 'prepared', 'kitchen', 'uploads/product_images/6e6f086108a5ed15c8364f76c5c13639.png', '2026-01-16 02:57:38', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 5, NULL, 1),
(179, 'TUMBARRANCHO ESPECIAL', '', 4.00, 2000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/aaaacb9b1e543a061a2246c6873ab88f.png', '2026-01-16 03:36:29', 20.00, '2026-02-10 03:44:40', NULL, 1, 'standard', 5, 5, NULL, 1),
(180, 'AREPA', '', 4.00, 2000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/f16d6e545f1b1689d82d5adf48902d59.png', '2026-01-16 03:39:38', 20.00, '2026-02-10 03:44:40', NULL, 1, 'standard', 5, 5, NULL, 1),
(181, 'AREPON', '', 9.00, 4500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/d85a13e5d77f9a147ce0626b80ea28af.png', '2026-01-16 03:43:27', 20.00, '2026-02-10 03:44:40', NULL, 2, 'standard', 5, 5, NULL, 1),
(182, 'NAPOLES', '', 8.00, 4000.00, 0, 'prepared', 'pizza', 'uploads/product_images/113ad44b522f71f3a54bc57906ae0551.png', '2026-01-16 03:57:51', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(183, 'MAÍZ', '', 8.00, 4000.00, 0, 'prepared', 'pizza', 'uploads/product_images/001eff0fd536b5b295427f7380e8a657.png', '2026-01-16 04:23:16', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(184, 'JAMÓN', '', 8.00, 4000.00, 0, 'prepared', 'pizza', 'uploads/product_images/4fd7645bd2506b1117779a2573d6fa3a.png', '2026-01-17 21:58:50', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(185, 'TOCINETA', '', 11.00, 5500.00, 0, 'prepared', 'pizza', 'uploads/product_images/298e41d02e4411d7daa79c23677bfcf4.png', '2026-01-17 22:00:16', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(186, 'PEPPERONI', '', 10.00, 5000.00, 0, 'prepared', 'pizza', 'uploads/product_images/c3219f12bf93d5ca5c0326f023684e94.png', '2026-01-17 22:01:32', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(187, 'CEBOLLA', '', 8.00, 4000.00, 0, 'prepared', 'pizza', 'uploads/product_images/80254a94aec825724988d341dde39a9f.png', '2026-01-17 22:03:37', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(188, 'PIMENTON', '', 8.00, 4000.00, 0, 'prepared', 'pizza', 'uploads/product_images/cd6942877f0ab9f08f94909da6b3693d.png', '2026-01-17 22:10:18', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(189, 'ACEITUNAS NEGRAS', '', 8.00, 4000.00, 0, 'prepared', 'pizza', 'uploads/product_images/01fc80baaae7d3aa39fd5b5e60d104d8.png', '2026-01-17 22:13:16', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(190, 'SALAMI', '', 11.00, 5500.00, 0, 'prepared', 'pizza', 'uploads/product_images/ff9f9a16234246c77f5cc3cca8f7b95a.png', '2026-01-17 22:35:32', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(191, 'CHAMPIÑÓN', '', 11.00, 5500.00, 0, 'prepared', 'pizza', 'uploads/product_images/c0f29cfda155aa6cb95be4b153a5f275.png', '2026-01-17 22:39:28', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(192, 'MEDIO KILO DE QUESO', '', 14.00, 7000.00, 0, 'prepared', 'pizza', 'uploads/product_images/ad084b9f9e529a3041651dac58d26397.png', '2026-01-17 23:01:19', 20.00, '2026-02-10 03:44:40', NULL, 4, 'proportional', 5, 7, NULL, 1),
(193, '4 ESTACIONES', '', 11.00, 5500.00, 0, 'prepared', 'pizza', 'uploads/product_images/9e683131a5de1c4cddb92a339df592bc.png', '2026-01-17 23:22:37', 20.00, '2026-02-10 03:44:40', NULL, 4, 'proportional', 5, 2, NULL, 1),
(194, '5 INGREDIENTES', '', 13.00, 6500.00, 0, 'prepared', 'pizza', 'uploads/product_images/82c28558e886b92b253caaf5877b2649.png', '2026-01-17 23:51:56', 20.00, '2026-02-10 03:44:40', NULL, 5, 'standard', 5, 2, NULL, 1),
(195, 'MARGARITA', '', 13.00, 6500.00, 0, 'prepared', 'pizza', 'uploads/product_images/5fe5b6214b53674e53d9555b475df1eb.png', '2026-01-18 00:04:03', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(196, 'PRIMAVERA', '', 13.00, 6500.00, 0, 'prepared', 'pizza', 'uploads/product_images/b2f20566bdb5bd1506600e85c8587739.png', '2026-01-18 00:10:32', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 2, NULL, 1),
(197, 'DOBLE RELLENA', '', 18.00, 9000.00, 0, 'prepared', 'pizza', 'uploads/product_images/577a124a65bc704a7419bebc4c5cbeb2.png', '2026-01-18 00:14:40', 20.00, '2026-02-10 03:44:40', NULL, 4, 'proportional', 5, 7, NULL, 1),
(198, 'CUBANA', '', 18.00, 9000.00, 0, 'prepared', 'pizza', 'uploads/product_images/487f1dd1cf90a3ecf8c7896b5b60d871.png', '2026-01-18 00:36:15', 20.00, '2026-02-10 03:44:40', NULL, 4, 'proportional', 5, 7, NULL, 1),
(199, 'BORDE DE TEQUEÑO', '', 18.00, 9000.00, 0, 'prepared', 'pizza', 'uploads/product_images/7a8841f2e8ca35c0aac94cb22d669a3f.png', '2026-01-18 00:37:42', 20.00, '2026-02-10 03:44:40', NULL, 4, 'proportional', 5, 7, NULL, 1),
(200, 'MARACUCHA', '', 15.00, 7500.00, 0, 'prepared', 'pizza', 'uploads/product_images/f11b956fddb5a6b2f7b63c441ff1ee2c.png', '2026-01-18 00:38:31', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 7, NULL, 1),
(201, 'POLLO BBQ', '', 18.00, 9000.00, 0, 'prepared', 'pizza', 'uploads/product_images/b24950b4c54885fb6afd6223feceb5a6.png', '2026-01-18 00:39:17', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 7, NULL, 1),
(202, 'AMERICANA', '', 15.00, 7500.00, 0, 'prepared', 'pizza', 'uploads/product_images/0a656825233811e568d70ef6879ad417.png', '2026-01-18 01:30:09', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 7, NULL, 1),
(205, 'MEDIO KILO DE QUESO CON BORDE DE QUESO', '', 16.00, 8000.00, 0, 'prepared', 'kitchen', 'uploads/product_images/ad084b9f9e529a3041651dac58d26397.png', '2026-01-21 21:35:49', 20.00, '2026-02-10 03:44:40', NULL, 4, 'proportional', 5, 7, NULL, 1),
(206, 'FAMILIAR', '', 11.00, 5500.00, 0, 'prepared', 'kitchen', 'uploads/product_images/9e683131a5de1c4cddb92a339df592bc.png', '2026-01-25 03:57:02', 20.00, '2026-02-10 03:44:40', NULL, 2, 'proportional', 5, 2, 'F', 1),
(207, 'AMERICANA', '', 8.50, 4250.00, 0, 'prepared', 'kitchen', 'uploads/product_images/21834e9ed9cb0dfa3e9f34db69f5ad78.png', '2026-02-08 01:17:47', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 1, NULL, 0),
(208, 'COCA-COLA', '', 1.20, 600.00, 24, 'simple', 'kitchen', 'default.jpg', '2026-02-08 02:08:15', 20.00, '2026-02-13 21:47:31', NULL, 0, 'standard', 5, 3, NULL, 0),
(209, 'CHINOTTO', '', 2.00, 1000.00, 20, 'simple', 'kitchen', 'default.jpg', '2026-02-08 02:20:23', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 3, NULL, 0),
(211, 'NARANJA', '', 2.00, 1000.00, 20, 'simple', 'kitchen', 'default.jpg', '2026-02-08 03:23:50', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 3, NULL, 0),
(212, 'MANZANITA', '', 2.00, 1000.00, 20, 'simple', 'kitchen', 'default.jpg', '2026-02-08 03:25:04', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 3, NULL, 0),
(213, 'TORONJA', '', 2.00, 1000.00, 20, 'simple', 'kitchen', 'default.jpg', '2026-02-08 03:25:36', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 3, NULL, 0),
(214, 'UVA', '', 1.20, 600.00, 12, 'simple', 'kitchen', 'default.jpg', '2026-02-08 03:26:05', 20.00, '2026-02-13 21:47:31', NULL, 0, 'standard', 5, 3, NULL, 0),
(215, 'FRESCOLITA', '', 2.00, 1000.00, 20, 'simple', 'kitchen', 'default.jpg', '2026-02-08 03:28:19', 20.00, '2026-02-10 03:44:40', NULL, 0, 'standard', 5, 3, NULL, 0),
(216, 'REFRESCO', '', 2.00, 1000.00, 0, 'compound', 'kitchen', 'uploads/product_images/c21daf1f1fe88b03cde1854275cdc148.png', '2026-02-12 20:27:09', 30.00, '2026-02-12 20:27:35', NULL, 0, 'standard', 5, 3, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_companions`
--

CREATE TABLE `product_companions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `companion_id` int(11) NOT NULL,
  `quantity` decimal(10,4) DEFAULT 1.0000,
  `price_override` decimal(10,2) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `product_companions`
--

INSERT INTO `product_companions` (`id`, `product_id`, `companion_id`, `quantity`, `price_override`, `is_default`) VALUES
(4, 97, 174, 1.0000, 0.00, 1),
(10, 202, 134, 1.0000, 0.00, 1),
(11, 201, 134, 1.0000, 0.00, 1),
(12, 200, 134, 1.0000, 0.00, 1),
(13, 199, 134, 1.0000, 0.00, 1),
(14, 198, 134, 1.0000, 0.00, 1),
(15, 197, 134, 1.0000, 0.00, 1),
(16, 196, 134, 1.0000, 0.00, 1),
(17, 195, 134, 1.0000, 0.00, 1),
(18, 194, 134, 1.0000, 0.00, 1),
(19, 193, 134, 1.0000, 0.00, 1),
(20, 192, 134, 1.0000, 0.00, 1),
(23, 205, 216, 1.0000, 0.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_companion_components`
--

CREATE TABLE `product_companion_components` (
  `id` int(11) NOT NULL,
  `product_companion_id` int(11) NOT NULL,
  `component_type` enum('raw','manufactured','product') NOT NULL,
  `component_id` int(11) NOT NULL,
  `quantity` decimal(10,6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_companion_components`
--

INSERT INTO `product_companion_components` (`id`, `product_companion_id`, `component_type`, `component_id`, `quantity`) VALUES
(15, 4, 'raw', 14, 0.150000);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

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
(175, 88, 'raw', 36, 2.0000),
(176, 138, 'product', 134, 2.0000),
(177, 139, 'product', 134, 1.0000),
(178, 140, 'product', 134, 1.0000),
(179, 141, 'product', 134, 1.0000),
(181, 142, 'product', 134, 2.0000),
(182, 143, 'product', 134, 1.0000),
(183, 144, 'product', 134, 1.0000),
(184, 145, 'product', 134, 1.0000),
(185, 146, 'product', 134, 1.0000),
(188, 91, 'raw', 25, 1.0000),
(189, 91, 'raw', 52, 0.0100),
(190, 91, 'raw', 16, 0.0250),
(191, 91, 'raw', 19, 0.0250),
(192, 91, 'raw', 18, 0.0100),
(193, 91, 'raw', 13, 0.0200),
(196, 156, 'raw', 25, 1.0000),
(197, 156, 'raw', 52, 0.0100),
(198, 156, 'raw', 16, 0.0250),
(199, 156, 'raw', 19, 0.0250),
(200, 156, 'raw', 18, 0.0100),
(201, 156, 'raw', 13, 0.0200),
(202, 156, 'raw', 10, 0.0100),
(203, 156, 'manufactured', 3, 0.0400),
(204, 92, 'raw', 25, 1.0000),
(205, 92, 'raw', 52, 0.0100),
(206, 92, 'raw', 16, 0.0250),
(207, 92, 'raw', 19, 0.0250),
(208, 92, 'raw', 18, 0.0100),
(209, 92, 'raw', 13, 0.0200),
(212, 92, 'manufactured', 6, 0.0450),
(214, 91, 'raw', 108, 0.1250),
(215, 92, 'raw', 108, 0.1250),
(217, 94, 'raw', 109, 1.0000),
(218, 95, 'raw', 109, 1.0000),
(219, 96, 'raw', 109, 1.0000),
(220, 147, 'raw', 109, 1.0000),
(229, 97, 'raw', 26, 1.0000),
(231, 99, 'raw', 26, 1.0000),
(233, 94, 'raw', 108, 0.5000),
(234, 94, 'raw', 13, 0.0200),
(235, 97, 'raw', 11, 2.0000),
(236, 97, 'raw', 7, 0.0250),
(237, 97, 'manufactured', 12, 0.0600),
(238, 97, 'raw', 55, 24.0000),
(239, 97, 'raw', 52, 0.0100),
(240, 97, 'raw', 56, 0.0250),
(243, 98, 'raw', 7, 0.0250),
(246, 98, 'raw', 52, 0.0200),
(247, 98, 'raw', 56, 0.0250),
(248, 98, 'manufactured', 6, 0.1800),
(251, 157, 'raw', 7, 0.0250),
(254, 157, 'raw', 52, 0.0200),
(255, 157, 'raw', 56, 0.0250),
(256, 157, 'manufactured', 6, 0.1800),
(263, 100, 'raw', 54, 20.0000),
(265, 94, 'raw', 16, 0.0250),
(266, 94, 'raw', 19, 0.0250),
(267, 94, 'raw', 18, 0.0100),
(268, 94, 'raw', 52, 0.0100),
(269, 94, 'raw', 56, 0.0250),
(270, 94, 'raw', 8, 0.0150),
(271, 94, 'manufactured', 6, 0.0900),
(280, 93, 'manufactured', 6, 0.0900),
(281, 95, 'raw', 108, 0.5000),
(282, 95, 'raw', 13, 0.0200),
(283, 95, 'raw', 16, 0.0250),
(284, 95, 'raw', 19, 0.0250),
(285, 95, 'raw', 18, 0.0100),
(286, 95, 'raw', 52, 0.0100),
(287, 95, 'raw', 56, 0.0250),
(288, 95, 'raw', 8, 0.0150),
(290, 95, 'manufactured', 5, 0.0800),
(291, 96, 'raw', 108, 0.5000),
(292, 96, 'raw', 13, 0.0200),
(293, 96, 'raw', 16, 0.0250),
(294, 96, 'raw', 19, 0.0250),
(295, 96, 'raw', 18, 0.0100),
(296, 96, 'raw', 52, 0.0100),
(297, 96, 'raw', 56, 0.0250),
(298, 96, 'raw', 8, 0.0150),
(299, 96, 'manufactured', 8, 0.0800),
(300, 158, 'raw', 109, 1.0000),
(301, 158, 'raw', 108, 0.5000),
(302, 158, 'raw', 13, 0.0200),
(303, 158, 'raw', 16, 0.0250),
(304, 158, 'raw', 19, 0.0250),
(305, 158, 'raw', 18, 0.0100),
(306, 158, 'raw', 52, 0.0100),
(307, 158, 'raw', 56, 0.0250),
(308, 158, 'raw', 8, 0.0150),
(311, 147, 'raw', 108, 0.5000),
(312, 147, 'raw', 13, 0.0400),
(313, 147, 'raw', 16, 0.0500),
(314, 147, 'raw', 19, 0.0500),
(315, 147, 'raw', 18, 0.0200),
(316, 147, 'raw', 52, 0.0200),
(317, 147, 'raw', 56, 0.0250),
(318, 147, 'raw', 8, 0.0150),
(319, 147, 'raw', 12, 0.0200),
(320, 147, 'raw', 7, 0.0250),
(321, 147, 'manufactured', 6, 0.1800),
(322, 148, 'raw', 109, 1.0000),
(323, 148, 'raw', 108, 0.5000),
(324, 148, 'raw', 13, 0.0400),
(325, 148, 'raw', 16, 0.0500),
(326, 148, 'raw', 19, 0.0500),
(327, 148, 'raw', 18, 0.0200),
(328, 148, 'raw', 52, 0.0200),
(329, 148, 'raw', 56, 0.0250),
(330, 148, 'raw', 8, 0.0150),
(331, 148, 'raw', 12, 0.0200),
(332, 148, 'raw', 7, 0.0250),
(333, 148, 'raw', 9, 0.2400),
(345, 149, 'manufactured', 6, 0.1800),
(346, 150, 'raw', 109, 1.0000),
(347, 150, 'raw', 108, 0.5000),
(348, 150, 'raw', 13, 0.0400),
(349, 150, 'raw', 16, 0.0500),
(350, 150, 'raw', 19, 0.0500),
(351, 150, 'raw', 18, 0.0200),
(352, 150, 'raw', 52, 0.0200),
(353, 150, 'raw', 56, 0.0250),
(354, 150, 'raw', 8, 0.0150),
(355, 150, 'raw', 12, 0.0200),
(356, 150, 'raw', 7, 0.0250),
(357, 150, 'manufactured', 8, 0.1800),
(358, 151, 'raw', 109, 1.0000),
(359, 151, 'raw', 108, 0.5000),
(360, 151, 'raw', 13, 0.0400),
(361, 151, 'raw', 16, 0.0500),
(362, 151, 'raw', 19, 0.0500),
(363, 151, 'raw', 18, 0.0200),
(364, 151, 'raw', 52, 0.0200),
(365, 151, 'raw', 56, 0.0250),
(366, 151, 'raw', 8, 0.0150),
(367, 151, 'raw', 12, 0.0200),
(368, 151, 'raw', 7, 0.0250),
(369, 151, 'manufactured', 5, 0.1800),
(370, 152, 'raw', 109, 1.0000),
(371, 152, 'raw', 108, 0.5000),
(372, 152, 'raw', 13, 0.0400),
(373, 152, 'raw', 16, 0.0500),
(374, 152, 'raw', 19, 0.0500),
(375, 152, 'raw', 18, 0.0200),
(376, 152, 'raw', 52, 0.0200),
(377, 152, 'raw', 56, 0.0250),
(378, 152, 'raw', 8, 0.0150),
(379, 152, 'raw', 12, 0.0200),
(380, 152, 'raw', 7, 0.0250),
(381, 152, 'manufactured', 3, 0.0900),
(382, 152, 'manufactured', 6, 0.0900),
(383, 153, 'raw', 109, 1.0000),
(385, 153, 'raw', 13, 0.0400),
(386, 153, 'raw', 16, 0.0500),
(387, 153, 'raw', 19, 0.0500),
(388, 153, 'raw', 18, 0.0200),
(389, 153, 'raw', 52, 0.0200),
(390, 153, 'raw', 56, 0.0250),
(391, 153, 'raw', 8, 0.0150),
(392, 153, 'raw', 12, 0.0200),
(393, 153, 'raw', 7, 0.0250),
(395, 153, 'raw', 21, 0.5000),
(396, 154, 'raw', 109, 1.0000),
(397, 154, 'raw', 108, 1.0000),
(398, 154, 'raw', 13, 0.0400),
(399, 154, 'raw', 16, 0.0500),
(400, 154, 'raw', 19, 0.0500),
(401, 154, 'raw', 18, 0.0200),
(402, 154, 'raw', 52, 0.0200),
(403, 154, 'raw', 56, 0.0250),
(404, 154, 'raw', 8, 0.0300),
(405, 154, 'raw', 12, 0.0400),
(406, 154, 'raw', 7, 0.0500),
(408, 155, 'raw', 109, 1.0000),
(409, 155, 'raw', 108, 1.0000),
(410, 155, 'raw', 13, 0.0400),
(411, 155, 'raw', 16, 0.0500),
(412, 155, 'raw', 19, 0.0500),
(413, 155, 'raw', 18, 0.0200),
(414, 155, 'raw', 52, 0.0200),
(415, 155, 'raw', 56, 0.0400),
(416, 155, 'raw', 8, 0.0300),
(417, 155, 'raw', 12, 0.0400),
(418, 155, 'raw', 7, 0.0500),
(422, 159, 'raw', 109, 1.0000),
(423, 159, 'raw', 108, 0.5000),
(424, 159, 'raw', 13, 0.0200),
(425, 159, 'raw', 16, 0.0250),
(426, 159, 'raw', 19, 0.0250),
(427, 159, 'raw', 18, 0.0100),
(428, 159, 'raw', 52, 0.0100),
(429, 159, 'raw', 56, 0.0250),
(430, 159, 'raw', 8, 0.0150),
(432, 159, 'manufactured', 7, 0.0800),
(433, 160, 'raw', 109, 1.0000),
(434, 160, 'raw', 108, 0.5000),
(435, 160, 'raw', 13, 0.0400),
(436, 160, 'raw', 16, 0.0500),
(437, 160, 'raw', 19, 0.0500),
(438, 160, 'raw', 18, 0.0200),
(439, 160, 'raw', 52, 0.0200),
(440, 160, 'raw', 56, 0.0250),
(441, 160, 'raw', 8, 0.0150),
(442, 160, 'raw', 12, 0.0200),
(443, 160, 'raw', 7, 0.0250),
(446, 93, 'manufactured', 13, 0.1400),
(447, 149, 'manufactured', 13, 0.2800),
(448, 98, 'manufactured', 13, 0.2400),
(449, 157, 'manufactured', 13, 0.2400),
(450, 101, 'raw', 24, 1.0000),
(451, 101, 'raw', 13, 0.0300),
(452, 101, 'raw', 19, 0.0300),
(453, 101, 'raw', 16, 0.0300),
(454, 101, 'raw', 18, 0.0100),
(455, 101, 'raw', 52, 0.0100),
(457, 101, 'raw', 110, 4.0000),
(458, 161, 'raw', 24, 1.0000),
(459, 161, 'raw', 13, 0.0300),
(460, 161, 'raw', 19, 0.0300),
(461, 161, 'raw', 16, 0.0300),
(462, 161, 'raw', 18, 0.0100),
(463, 161, 'raw', 52, 0.0100),
(465, 161, 'raw', 110, 4.0000),
(466, 161, 'raw', 15, 1.0000),
(467, 162, 'raw', 24, 1.0000),
(468, 162, 'raw', 13, 0.0300),
(469, 162, 'raw', 19, 0.0300),
(470, 162, 'raw', 16, 0.0300),
(471, 162, 'raw', 18, 0.0100),
(472, 162, 'raw', 52, 0.0100),
(473, 162, 'raw', 110, 4.0000),
(475, 162, 'raw', 108, 0.5000),
(476, 163, 'raw', 24, 1.0000),
(477, 163, 'raw', 13, 0.0300),
(478, 163, 'raw', 19, 0.0300),
(479, 163, 'raw', 16, 0.0300),
(480, 163, 'raw', 18, 0.0100),
(481, 163, 'raw', 52, 0.0100),
(482, 163, 'raw', 110, 4.0000),
(483, 163, 'raw', 108, 0.5000),
(484, 164, 'raw', 24, 1.0000),
(485, 164, 'raw', 13, 0.0300),
(486, 164, 'raw', 19, 0.0300),
(487, 164, 'raw', 16, 0.0300),
(488, 164, 'raw', 18, 0.0100),
(489, 164, 'raw', 52, 0.0100),
(490, 164, 'raw', 4, 1.0000),
(491, 164, 'raw', 110, 4.0000),
(492, 164, 'raw', 108, 0.5000),
(493, 165, 'raw', 24, 1.0000),
(494, 165, 'raw', 13, 0.0300),
(495, 165, 'raw', 19, 0.0300),
(496, 165, 'raw', 16, 0.0300),
(497, 165, 'raw', 18, 0.0100),
(498, 165, 'raw', 52, 0.0100),
(499, 165, 'raw', 4, 1.0000),
(500, 165, 'raw', 110, 4.0000),
(501, 165, 'raw', 15, 1.0000),
(502, 166, 'raw', 24, 1.0000),
(503, 166, 'raw', 13, 0.0300),
(504, 166, 'raw', 19, 0.0300),
(505, 166, 'raw', 16, 0.0300),
(506, 166, 'raw', 18, 0.0100),
(507, 166, 'raw', 52, 0.0100),
(508, 166, 'raw', 110, 4.0000),
(509, 166, 'raw', 15, 1.0000),
(510, 166, 'raw', 108, 0.5000),
(511, 167, 'raw', 24, 1.0000),
(512, 167, 'raw', 13, 0.0300),
(513, 167, 'raw', 19, 0.0300),
(514, 167, 'raw', 16, 0.0300),
(515, 167, 'raw', 18, 0.0100),
(516, 167, 'raw', 52, 0.0100),
(518, 167, 'raw', 110, 4.0000),
(519, 167, 'manufactured', 5, 0.0800),
(520, 167, 'raw', 108, 0.5000),
(521, 168, 'raw', 24, 1.0000),
(522, 168, 'raw', 13, 0.0300),
(523, 168, 'raw', 19, 0.0300),
(524, 168, 'raw', 16, 0.0300),
(525, 168, 'raw', 18, 0.0100),
(526, 168, 'raw', 52, 0.0100),
(527, 168, 'raw', 110, 4.0000),
(529, 168, 'raw', 108, 0.5000),
(530, 168, 'manufactured', 6, 0.0900),
(531, 169, 'raw', 24, 1.0000),
(532, 169, 'raw', 13, 0.0300),
(533, 169, 'raw', 19, 0.0300),
(534, 169, 'raw', 16, 0.0300),
(535, 169, 'raw', 18, 0.0100),
(536, 169, 'raw', 52, 0.0100),
(537, 169, 'raw', 110, 4.0000),
(539, 169, 'raw', 108, 0.5000),
(540, 169, 'manufactured', 8, 0.0800),
(542, 170, 'raw', 108, 0.5000),
(544, 170, 'raw', 16, 0.0500),
(545, 170, 'raw', 19, 0.0500),
(546, 170, 'raw', 18, 0.0200),
(547, 170, 'raw', 52, 0.0200),
(548, 170, 'raw', 56, 0.0250),
(549, 170, 'raw', 8, 0.0150),
(550, 170, 'raw', 12, 0.0200),
(553, 170, 'raw', 15, 1.0000),
(554, 170, 'raw', 22, 2.0000),
(556, 171, 'raw', 108, 0.5000),
(557, 171, 'raw', 16, 0.0500),
(558, 171, 'raw', 19, 0.0500),
(559, 171, 'raw', 18, 0.0200),
(560, 171, 'raw', 52, 0.0200),
(561, 171, 'raw', 56, 0.0250),
(562, 171, 'raw', 8, 0.0150),
(563, 171, 'raw', 12, 0.0200),
(564, 171, 'raw', 15, 1.0000),
(565, 171, 'raw', 22, 2.0000),
(567, 171, 'raw', 31, 0.2000),
(568, 172, 'raw', 108, 0.5000),
(569, 172, 'raw', 16, 0.0500),
(570, 172, 'raw', 19, 0.0500),
(571, 172, 'raw', 18, 0.0200),
(572, 172, 'raw', 52, 0.0200),
(573, 172, 'raw', 56, 0.0250),
(574, 172, 'raw', 8, 0.0150),
(575, 172, 'raw', 12, 0.0200),
(579, 172, 'raw', 110, 6.0000),
(580, 172, 'raw', 14, 0.4000),
(581, 173, 'raw', 108, 1.5000),
(582, 173, 'raw', 16, 0.0700),
(583, 173, 'raw', 19, 0.0700),
(584, 173, 'raw', 18, 0.0300),
(585, 173, 'raw', 52, 0.0400),
(586, 173, 'raw', 56, 0.0500),
(587, 173, 'raw', 8, 0.0450),
(588, 173, 'raw', 12, 0.0600),
(590, 173, 'raw', 110, 15.0000),
(591, 173, 'raw', 14, 1.0000),
(592, 173, 'raw', 4, 6.0000),
(593, 173, 'raw', 15, 1.0000),
(594, 174, 'raw', 14, 0.2500),
(596, 175, 'manufactured', 1, 0.0400),
(597, 175, 'raw', 32, 0.0400),
(598, 175, 'raw', 78, 1.0000),
(599, 175, 'manufactured', 11, 0.0200),
(600, 174, 'raw', 78, 1.0000),
(601, 174, 'raw', 16, 0.0200),
(602, 176, 'raw', 14, 0.2000),
(603, 176, 'raw', 78, 1.0000),
(604, 176, 'raw', 16, 0.0200),
(605, 176, 'raw', 100, 0.1000),
(607, 178, 'raw', 52, 0.0100),
(608, 178, 'raw', 16, 0.0250),
(609, 178, 'raw', 19, 0.0250),
(610, 178, 'raw', 18, 0.0100),
(613, 178, 'raw', 108, 0.1250),
(614, 178, 'manufactured', 10, 1.0000),
(615, 178, 'raw', 101, 12.0000),
(616, 178, 'manufactured', 9, 0.0800),
(617, 179, 'raw', 52, 0.0100),
(618, 179, 'raw', 16, 0.0250),
(619, 179, 'raw', 19, 0.0250),
(620, 179, 'raw', 18, 0.0100),
(621, 179, 'raw', 108, 0.5000),
(622, 179, 'manufactured', 10, 1.0000),
(624, 179, 'manufactured', 9, 0.0800),
(626, 180, 'raw', 52, 0.0100),
(627, 180, 'raw', 16, 0.0250),
(628, 180, 'raw', 19, 0.0250),
(629, 180, 'raw', 18, 0.0100),
(630, 180, 'raw', 108, 0.5000),
(631, 180, 'manufactured', 10, 1.0000),
(634, 181, 'raw', 108, 0.5000),
(635, 181, 'raw', 16, 0.0500),
(636, 181, 'raw', 19, 0.0500),
(637, 181, 'raw', 18, 0.0200),
(638, 181, 'raw', 52, 0.0200),
(639, 181, 'raw', 56, 0.0250),
(640, 181, 'raw', 8, 0.0150),
(641, 181, 'raw', 12, 0.0200),
(643, 181, 'raw', 110, 6.0000),
(645, 181, 'manufactured', 10, 5.0000),
(646, 181, 'raw', 15, 1.0000),
(647, 172, 'raw', 4, 2.0000),
(648, 182, 'manufactured', 1, 0.5500),
(649, 182, 'manufactured', 2, 0.1100),
(650, 182, 'raw', 31, 0.3000),
(651, 182, 'raw', 33, 0.0600),
(652, 182, 'raw', 2, 0.0500),
(653, 183, 'manufactured', 1, 0.5500),
(654, 183, 'manufactured', 2, 0.1100),
(655, 183, 'raw', 31, 0.3000),
(656, 183, 'raw', 33, 0.0600),
(657, 183, 'raw', 2, 0.0500),
(660, 183, 'raw', 30, 0.1500),
(661, 184, 'manufactured', 1, 0.5500),
(662, 184, 'manufactured', 2, 0.1100),
(663, 184, 'raw', 31, 0.3000),
(664, 184, 'raw', 33, 0.0600),
(665, 184, 'raw', 2, 0.0500),
(666, 184, 'raw', 29, 0.1000),
(667, 185, 'manufactured', 1, 0.5500),
(668, 185, 'manufactured', 2, 0.1100),
(669, 185, 'raw', 31, 0.3000),
(670, 185, 'raw', 33, 0.0600),
(671, 185, 'raw', 2, 0.0500),
(672, 185, 'raw', 7, 0.1000),
(673, 186, 'manufactured', 1, 0.5500),
(674, 186, 'manufactured', 2, 0.1100),
(675, 186, 'raw', 31, 0.3000),
(676, 186, 'raw', 33, 0.0600),
(677, 186, 'raw', 2, 0.0500),
(678, 186, 'raw', 28, 0.1000),
(679, 187, 'manufactured', 1, 0.5500),
(680, 187, 'manufactured', 2, 0.1100),
(681, 187, 'raw', 31, 0.3000),
(682, 187, 'raw', 33, 0.0600),
(683, 187, 'raw', 2, 0.0500),
(684, 187, 'raw', 54, 80.0000),
(685, 188, 'manufactured', 1, 0.5500),
(686, 188, 'manufactured', 2, 0.1100),
(687, 188, 'raw', 31, 0.3000),
(688, 188, 'raw', 33, 0.0600),
(689, 188, 'raw', 2, 0.0500),
(690, 188, 'raw', 53, 100.0000),
(691, 189, 'manufactured', 1, 0.5500),
(692, 189, 'manufactured', 2, 0.1100),
(693, 189, 'raw', 31, 0.3000),
(694, 189, 'raw', 33, 0.0600),
(695, 189, 'raw', 2, 0.0500),
(696, 189, 'raw', 37, 100.0000),
(697, 190, 'manufactured', 1, 0.5500),
(698, 190, 'manufactured', 2, 0.1100),
(699, 190, 'raw', 31, 0.3000),
(700, 190, 'raw', 33, 0.0600),
(701, 190, 'raw', 2, 0.0500),
(702, 191, 'manufactured', 1, 0.5500),
(703, 191, 'manufactured', 2, 0.1100),
(704, 191, 'raw', 31, 0.3000),
(705, 191, 'raw', 33, 0.0600),
(706, 191, 'raw', 2, 0.0500),
(707, 191, 'raw', 36, 0.1000),
(708, 190, 'raw', 95, 0.1000),
(709, 192, 'manufactured', 1, 0.5500),
(710, 192, 'manufactured', 2, 0.1100),
(711, 192, 'raw', 31, 0.5000),
(712, 192, 'raw', 33, 0.0600),
(713, 192, 'raw', 2, 0.0500),
(714, 193, 'manufactured', 1, 0.5500),
(715, 193, 'manufactured', 2, 0.1100),
(716, 193, 'raw', 31, 0.3000),
(717, 193, 'raw', 33, 0.0600),
(718, 193, 'raw', 2, 0.0500),
(719, 194, 'manufactured', 1, 0.5500),
(720, 194, 'manufactured', 2, 0.1100),
(721, 194, 'raw', 31, 0.3000),
(722, 194, 'raw', 33, 0.0600),
(723, 194, 'raw', 2, 0.0500),
(726, 195, 'manufactured', 1, 0.5500),
(727, 195, 'manufactured', 2, 0.1100),
(728, 195, 'raw', 31, 0.3000),
(729, 195, 'raw', 33, 0.0600),
(730, 195, 'raw', 2, 0.0500),
(731, 195, 'raw', 29, 0.1000),
(732, 195, 'raw', 30, 0.1500),
(734, 195, 'raw', 36, 0.1000),
(735, 196, 'manufactured', 1, 0.5500),
(736, 196, 'manufactured', 2, 0.1100),
(737, 196, 'raw', 31, 0.3000),
(738, 196, 'raw', 33, 0.0600),
(739, 196, 'raw', 2, 0.0500),
(740, 196, 'raw', 29, 0.1000),
(741, 196, 'raw', 30, 0.1500),
(743, 196, 'raw', 7, 0.1000),
(744, 197, 'manufactured', 1, 0.8000),
(745, 197, 'manufactured', 2, 0.1600),
(746, 197, 'raw', 31, 0.3000),
(747, 197, 'raw', 33, 0.0600),
(748, 197, 'raw', 2, 0.0500),
(749, 197, 'raw', 32, 0.2000),
(750, 197, 'manufactured', 5, 0.1600),
(751, 198, 'manufactured', 1, 0.7500),
(752, 198, 'manufactured', 2, 0.1600),
(753, 198, 'raw', 31, 1.0000),
(754, 198, 'raw', 33, 0.0600),
(755, 198, 'raw', 2, 0.0500),
(756, 199, 'manufactured', 1, 0.6500),
(757, 199, 'manufactured', 2, 0.1100),
(758, 199, 'raw', 31, 0.3000),
(759, 199, 'raw', 33, 0.0600),
(760, 199, 'raw', 2, 0.0500),
(761, 200, 'manufactured', 1, 0.5500),
(762, 200, 'manufactured', 2, 0.1100),
(763, 200, 'raw', 31, 0.3000),
(764, 200, 'raw', 33, 0.0600),
(765, 200, 'raw', 2, 0.0500),
(766, 201, 'manufactured', 1, 0.6500),
(767, 201, 'manufactured', 2, 0.1100),
(768, 201, 'raw', 31, 0.3000),
(769, 201, 'raw', 33, 0.0600),
(770, 201, 'raw', 2, 0.0500),
(771, 199, 'raw', 32, 0.4000),
(772, 200, 'raw', 32, 0.2000),
(773, 200, 'raw', 9, 0.3000),
(774, 200, 'raw', 23, 3.0000),
(775, 201, 'manufactured', 6, 0.1800),
(776, 201, 'raw', 17, 0.0500),
(777, 201, 'raw', 29, 0.1000),
(778, 201, 'raw', 7, 0.1000),
(779, 201, 'raw', 32, 0.2000),
(780, 202, 'manufactured', 1, 0.5500),
(781, 202, 'manufactured', 2, 0.1100),
(782, 202, 'raw', 31, 0.3000),
(783, 202, 'raw', 33, 0.0600),
(784, 202, 'raw', 2, 0.0500),
(785, 202, 'raw', 83, 0.1800),
(786, 202, 'raw', 28, 0.1000),
(787, 202, 'raw', 29, 0.1000),
(789, 203, 'manufactured', 1, 0.5500),
(790, 203, 'manufactured', 2, 0.1100),
(791, 203, 'raw', 31, 0.3000),
(792, 203, 'raw', 33, 0.0600),
(793, 203, 'raw', 2, 0.0500),
(794, 203, 'raw', 83, 0.1800),
(795, 203, 'raw', 28, 0.1000),
(796, 203, 'raw', 29, 0.1000),
(798, 204, 'manufactured', 1, 0.5500),
(799, 204, 'manufactured', 2, 0.1100),
(800, 204, 'raw', 31, 0.3000),
(801, 204, 'raw', 33, 0.0600),
(802, 204, 'raw', 2, 0.0500),
(803, 204, 'raw', 83, 0.1800),
(804, 204, 'raw', 28, 0.1000),
(805, 204, 'raw', 29, 0.1000),
(806, 204, 'raw', 20, 0.1000),
(808, 146, 'product', 91, 10.0000),
(809, 205, 'manufactured', 1, 0.5500),
(810, 205, 'manufactured', 2, 0.1100),
(811, 205, 'raw', 31, 0.5000),
(812, 205, 'raw', 33, 0.0600),
(813, 205, 'raw', 2, 0.0500),
(814, 205, 'raw', 32, 0.2000),
(815, 142, 'product', 205, 3.0000),
(816, 206, 'manufactured', 1, 0.5500),
(817, 206, 'manufactured', 2, 0.1100),
(818, 206, 'raw', 31, 0.3000),
(819, 206, 'raw', 33, 0.0600),
(820, 206, 'raw', 2, 0.0500),
(821, 143, 'product', 158, 3.0000),
(822, 145, 'product', 206, 1.0000),
(823, 145, 'product', 91, 3.0000),
(824, 144, 'product', 206, 1.0000),
(825, 144, 'product', 91, 4.0000),
(826, 144, 'product', 174, 1.0000),
(828, 141, 'product', 174, 1.0000),
(829, 140, 'product', 206, 2.0000),
(830, 139, 'product', 206, 1.0000),
(831, 139, 'product', 158, 2.0000),
(832, 139, 'product', 174, 1.0000),
(833, 139, 'product', 101, 2.0000),
(834, 171, 'raw', 79, 1.0000),
(835, 153, 'manufactured', 5, 0.1600),
(836, 153, 'raw', 32, 0.1500),
(837, 155, 'manufactured', 4, 0.0900),
(838, 155, 'manufactured', 6, 0.0900),
(839, 155, 'raw', 9, 0.1200),
(865, 141, 'product', 97, 2.0000),
(866, 170, 'raw', 13, 0.0400),
(867, 138, 'product', 206, 3.0000),
(868, 100, 'manufactured', 4, 0.1800),
(876, 207, 'manufactured', 4, 0.1800),
(878, 216, 'product', 134, 1.0000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_component_overrides`
--

CREATE TABLE `product_component_overrides` (
  `id` int(11) NOT NULL,
  `component_row_id` int(11) NOT NULL,
  `ingredient_type` enum('raw','manufactured','product') NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(15,6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_component_overrides`
--

INSERT INTO `product_component_overrides` (`id`, `component_row_id`, `ingredient_type`, `ingredient_id`, `quantity`) VALUES
(16, 830, 'manufactured', 1, 0.550000),
(17, 830, 'manufactured', 2, 0.110000),
(18, 830, 'raw', 31, 0.300000),
(19, 830, 'raw', 33, 0.060000),
(20, 830, 'raw', 2, 0.050000),
(21, 829, 'manufactured', 1, 0.550000),
(22, 829, 'manufactured', 2, 0.110000),
(23, 829, 'raw', 31, 0.300000),
(24, 829, 'raw', 33, 0.060000),
(25, 829, 'raw', 2, 0.050000),
(26, 824, 'manufactured', 1, 0.550000),
(27, 824, 'manufactured', 2, 0.110000),
(28, 824, 'raw', 31, 0.300000),
(29, 824, 'raw', 33, 0.060000),
(30, 824, 'raw', 2, 0.050000),
(31, 825, 'raw', 25, 1.000000),
(32, 825, 'raw', 52, 0.010000),
(33, 825, 'raw', 16, 0.025000),
(34, 825, 'raw', 19, 0.025000),
(35, 825, 'raw', 18, 0.010000),
(36, 825, 'raw', 13, 0.020000),
(37, 825, 'raw', 108, 0.125000),
(38, 822, 'manufactured', 1, 0.550000),
(39, 822, 'manufactured', 2, 0.110000),
(40, 822, 'raw', 31, 0.300000),
(41, 822, 'raw', 33, 0.060000),
(42, 822, 'raw', 2, 0.050000),
(43, 823, 'raw', 25, 1.000000),
(44, 823, 'raw', 52, 0.010000),
(45, 823, 'raw', 16, 0.025000),
(46, 823, 'raw', 19, 0.025000),
(47, 823, 'raw', 18, 0.010000),
(48, 823, 'raw', 13, 0.020000),
(49, 823, 'raw', 108, 0.125000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_component_side_overrides`
--

CREATE TABLE `product_component_side_overrides` (
  `id` int(11) NOT NULL,
  `component_row_id` int(11) NOT NULL,
  `side_type` enum('raw','manufactured','product') NOT NULL,
  `side_id` int(11) NOT NULL,
  `quantity` decimal(15,6) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_component_side_overrides`
--

INSERT INTO `product_component_side_overrides` (`id`, `component_row_id`, `side_type`, `side_id`, `quantity`, `is_default`) VALUES
(22, 830, 'raw', 54, 80.000000, 0),
(23, 830, 'raw', 29, 0.100000, 0),
(24, 830, 'raw', 30, 0.150000, 0),
(25, 830, 'raw', 28, 0.100000, 0),
(26, 830, 'raw', 53, 100.000000, 0),
(27, 829, 'raw', 54, 80.000000, 0),
(28, 829, 'raw', 29, 0.100000, 0),
(29, 829, 'raw', 30, 0.150000, 0),
(30, 829, 'raw', 28, 0.100000, 0),
(31, 829, 'raw', 53, 100.000000, 0),
(32, 824, 'raw', 54, 80.000000, 0),
(33, 824, 'raw', 29, 0.100000, 0),
(34, 824, 'raw', 30, 0.150000, 0),
(35, 824, 'raw', 28, 0.100000, 0),
(36, 824, 'raw', 53, 100.000000, 0),
(37, 825, 'manufactured', 3, 0.040000, 0),
(38, 822, 'raw', 54, 80.000000, 0),
(39, 822, 'raw', 29, 0.100000, 0),
(40, 822, 'raw', 30, 0.150000, 0),
(41, 822, 'raw', 28, 0.100000, 0),
(42, 822, 'raw', 53, 100.000000, 0),
(43, 823, 'manufactured', 3, 0.040000, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_default_modifiers`
--

CREATE TABLE `product_default_modifiers` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `modifier_type` enum('add','remove','side','info') NOT NULL,
  `sub_item_index` int(11) NOT NULL DEFAULT 0,
  `component_id` int(11) DEFAULT NULL,
  `component_type` enum('raw','manufactured','product') DEFAULT NULL,
  `quantity_adjustment` decimal(10,3) DEFAULT 1.000,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `is_takeaway` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_default_modifiers`
--

INSERT INTO `product_default_modifiers` (`id`, `product_id`, `modifier_type`, `sub_item_index`, `component_id`, `component_type`, `quantity_adjustment`, `price_adjustment`, `note`, `is_takeaway`, `created_at`) VALUES
(78, 205, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-01-23 22:07:51'),
(79, 205, 'side', 0, 29, 'raw', 0.100, 0.00, NULL, 0, '2026-01-23 22:07:51'),
(80, 205, 'side', 0, 30, 'raw', 0.150, 0.00, NULL, 0, '2026-01-23 22:07:51'),
(81, 205, 'side', 0, 53, 'raw', 100.000, 0.00, NULL, 0, '2026-01-23 22:07:51'),
(82, 205, 'side', 0, 7, 'raw', 0.100, 0.00, NULL, 0, '2026-01-23 22:07:51'),
(83, 198, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-01-24 21:04:49'),
(84, 198, 'side', 0, 29, 'raw', 0.100, 0.00, NULL, 0, '2026-01-24 21:04:49'),
(85, 198, 'side', 0, 30, 'raw', 0.150, 0.00, NULL, 0, '2026-01-24 21:04:49'),
(86, 198, 'side', 0, 53, 'raw', 100.000, 0.00, NULL, 0, '2026-01-24 21:04:49'),
(87, 198, 'side', 0, 7, 'raw', 0.100, 0.00, NULL, 0, '2026-01-24 21:04:49'),
(88, 193, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-06 00:37:30'),
(89, 193, 'side', 0, 29, 'raw', 0.100, 0.00, NULL, 0, '2026-02-06 00:37:30'),
(90, 193, 'side', 0, 30, 'raw', 0.150, 0.00, NULL, 0, '2026-02-06 00:37:30'),
(91, 193, 'side', 0, 53, 'raw', 100.000, 0.00, NULL, 0, '2026-02-06 00:37:30'),
(92, 193, 'side', 0, 7, 'raw', 0.100, 0.00, NULL, 0, '2026-02-06 00:37:30'),
(129, 194, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-07 19:50:56'),
(130, 194, 'side', 0, 54, 'raw', 80.000, 0.00, NULL, 0, '2026-02-07 19:50:56'),
(131, 194, 'side', 0, 53, 'raw', 100.000, 0.00, NULL, 0, '2026-02-07 19:50:56'),
(132, 216, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:35:30'),
(133, 216, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:35:30'),
(142, 139, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:39:42'),
(143, 139, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:39:42'),
(144, 139, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:39:42'),
(145, 139, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:39:42'),
(146, 139, 'info', 3, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:39:42'),
(147, 139, 'info', 4, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:39:42'),
(148, 139, 'info', 5, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:39:42'),
(149, 139, 'info', 6, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:39:42'),
(150, 140, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:40:23'),
(151, 140, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:40:23'),
(152, 140, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:40:23'),
(153, 140, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:40:23'),
(154, 141, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:41:37'),
(155, 141, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:41:37'),
(156, 141, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:41:37'),
(157, 141, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:41:37'),
(158, 141, 'info', 3, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:41:37'),
(159, 142, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:23'),
(160, 142, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:42:23'),
(161, 142, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:23'),
(162, 142, 'side', 1, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:42:23'),
(163, 142, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:23'),
(164, 142, 'info', 3, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:23'),
(165, 142, 'info', 4, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:23'),
(166, 143, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:58'),
(167, 143, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:42:58'),
(168, 143, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:58'),
(169, 143, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:58'),
(170, 143, 'info', 3, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:42:58'),
(171, 144, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:19'),
(172, 144, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:43:19'),
(173, 144, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:19'),
(174, 144, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:19'),
(175, 144, 'info', 3, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:19'),
(176, 144, 'info', 4, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:19'),
(177, 144, 'info', 5, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:19'),
(178, 144, 'info', 6, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:19'),
(188, 145, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(189, 145, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(190, 145, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(191, 145, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(192, 145, 'side', 2, 3, 'manufactured', 0.040, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(193, 145, 'info', 3, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(194, 145, 'side', 3, 3, 'manufactured', 0.040, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(195, 145, 'info', 4, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(196, 145, 'side', 4, 3, 'manufactured', 0.040, 0.00, NULL, 0, '2026-02-12 20:43:57'),
(197, 146, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(198, 146, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(199, 146, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(200, 146, 'side', 1, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(201, 146, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(202, 146, 'side', 2, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(203, 146, 'info', 3, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(204, 146, 'side', 3, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(205, 146, 'info', 4, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(206, 146, 'side', 4, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(207, 146, 'info', 5, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(208, 146, 'side', 5, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(209, 146, 'info', 6, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(210, 146, 'side', 6, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(211, 146, 'info', 7, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(212, 146, 'side', 7, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(213, 146, 'info', 8, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(214, 146, 'side', 8, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(215, 146, 'info', 9, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(216, 146, 'side', 9, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(217, 146, 'info', 10, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:44:19'),
(218, 146, 'side', 10, 6, 'manufactured', 0.045, 0.50, NULL, 0, '2026-02-12 20:44:19'),
(219, 138, 'info', -1, NULL, NULL, 1.000, 0.00, 'Test de nota global', 0, '2026-02-12 20:45:52'),
(220, 138, 'info', 0, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:45:52'),
(221, 138, 'side', 0, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:45:52'),
(222, 138, 'info', 1, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:45:52'),
(223, 138, 'side', 1, 208, 'product', 1.000, 0.00, NULL, 0, '2026-02-12 20:45:52'),
(224, 138, 'info', 2, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:45:52'),
(225, 138, 'info', 3, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:45:52'),
(226, 138, 'info', 4, NULL, NULL, 1.000, 0.00, NULL, 0, '2026-02-12 20:45:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_packaging`
--

CREATE TABLE `product_packaging` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `quantity` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `product_packaging`
--

INSERT INTO `product_packaging` (`id`, `product_id`, `raw_material_id`, `quantity`) VALUES
(1, 180, 46, 1.0000),
(2, 180, 48, 4.0000),
(3, 192, 39, 1.0000),
(4, 193, 39, 1.0000),
(5, 194, 39, 1.0000),
(6, 195, 39, 1.0000),
(7, 196, 39, 1.0000),
(8, 197, 39, 1.0000),
(9, 198, 39, 1.0000),
(10, 199, 39, 1.0000),
(11, 200, 39, 1.0000),
(12, 201, 39, 1.0000),
(13, 202, 39, 1.0000),
(14, 203, 39, 1.0000),
(15, 204, 39, 1.0000),
(16, 205, 39, 1.0000),
(17, 206, 39, 1.0000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_valid_extras`
--

CREATE TABLE `product_valid_extras` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `component_type` enum('raw','manufactured','product') DEFAULT 'raw',
  `component_id` int(11) DEFAULT NULL,
  `quantity_required` decimal(10,4) DEFAULT 1.0000,
  `price_override` decimal(10,2) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `product_valid_extras`
--

INSERT INTO `product_valid_extras` (`id`, `product_id`, `component_type`, `component_id`, `quantity_required`, `price_override`, `is_default`) VALUES
(7, 155, 'raw', 9, 0.1200, 2.00, 0),
(8, 180, 'raw', 108, 0.5000, 1.00, 0),
(9, 182, 'raw', 29, 0.1000, 1.50, 0),
(10, 182, 'raw', 30, 0.1000, 1.50, 0),
(11, 182, 'raw', 7, 0.1000, 3.00, 0),
(12, 182, 'raw', 28, 0.1000, 2.00, 0),
(13, 182, 'raw', 95, 0.1000, 4.00, 0),
(14, 182, 'raw', 36, 0.1000, 2.00, 0),
(15, 182, 'raw', 20, 0.1000, 3.00, 0),
(16, 182, 'raw', 23, 3.0000, 2.00, 0),
(17, 182, 'raw', 54, 80.0000, 1.00, 0),
(18, 182, 'raw', 53, 100.0000, 1.00, 0),
(19, 182, 'raw', 31, 0.2000, 3.00, 0),
(20, 182, 'raw', 32, 0.2000, 3.00, 0),
(24, 158, 'manufactured', 5, 0.0800, 2.00, 0),
(25, 158, 'raw', 9, 0.1200, 2.00, 0),
(26, 158, 'manufactured', 6, 0.0900, 2.00, 0),
(27, 158, 'manufactured', 8, 0.0800, 2.00, 0),
(28, 158, 'manufactured', 7, 0.0800, 2.00, 0),
(30, 91, 'manufactured', 5, 0.0800, 2.00, 0),
(31, 91, 'raw', 9, 0.1200, 2.00, 0),
(32, 91, 'manufactured', 6, 0.0900, 2.00, 0),
(33, 91, 'manufactured', 8, 0.0800, 2.00, 0),
(34, 91, 'manufactured', 7, 0.0800, 2.00, 0),
(36, 93, 'manufactured', 5, 0.0800, 2.00, 0),
(37, 93, 'raw', 9, 0.1200, 2.00, 0),
(38, 93, 'manufactured', 6, 0.0900, 2.00, 0),
(39, 93, 'manufactured', 8, 0.0800, 2.00, 0),
(40, 93, 'manufactured', 7, 0.0800, 2.00, 0),
(42, 160, 'manufactured', 5, 0.0800, 2.00, 0),
(43, 160, 'raw', 9, 0.1200, 2.00, 0),
(44, 160, 'manufactured', 6, 0.0900, 2.00, 0),
(45, 160, 'manufactured', 8, 0.0800, 2.00, 0),
(46, 160, 'manufactured', 7, 0.0800, 2.00, 0),
(54, 153, 'manufactured', 5, 0.0800, 2.00, 0),
(55, 153, 'raw', 9, 0.1200, 2.00, 0),
(56, 153, 'manufactured', 6, 0.0900, 2.00, 0),
(57, 153, 'manufactured', 8, 0.0800, 2.00, 0),
(58, 153, 'manufactured', 7, 0.0800, 2.00, 0),
(60, 154, 'manufactured', 5, 0.0800, 2.00, 0),
(61, 154, 'raw', 9, 0.1200, 2.00, 0),
(62, 154, 'manufactured', 6, 0.0900, 2.00, 0),
(63, 154, 'manufactured', 8, 0.0800, 2.00, 0),
(64, 154, 'manufactured', 7, 0.0800, 2.00, 0),
(66, 155, 'manufactured', 5, 0.0800, 2.00, 0),
(67, 155, 'manufactured', 6, 0.0900, 2.00, 0),
(68, 155, 'manufactured', 8, 0.0800, 2.00, 0),
(69, 155, 'manufactured', 7, 0.0800, 2.00, 0),
(70, 155, 'manufactured', 4, 0.0900, 2.00, 0),
(71, 158, 'manufactured', 4, 0.0900, 2.00, 0),
(73, 154, 'manufactured', 4, 0.0900, 2.00, 0),
(74, 153, 'manufactured', 4, 0.0900, 2.00, 0),
(76, 93, 'manufactured', 4, 0.0900, 2.00, 0),
(77, 91, 'manufactured', 4, 0.0900, 2.00, 0),
(78, 160, 'manufactured', 4, 0.0900, 2.00, 0),
(85, 97, 'manufactured', 5, 0.0800, 2.00, 0),
(86, 97, 'raw', 9, 0.1200, 2.00, 0),
(87, 97, 'manufactured', 6, 0.0900, 2.00, 0),
(88, 97, 'manufactured', 8, 0.0800, 2.00, 0),
(89, 97, 'manufactured', 7, 0.0800, 2.00, 0),
(90, 97, 'manufactured', 4, 0.0900, 2.00, 0),
(109, 101, 'raw', 15, 1.0000, 0.50, 0),
(110, 101, 'raw', 4, 1.0000, 0.50, 0),
(111, 101, 'raw', 108, 0.5000, 1.00, 0),
(112, 101, 'manufactured', 5, 0.0800, 2.00, 0),
(113, 101, 'raw', 9, 0.1200, 2.00, 0),
(114, 101, 'manufactured', 6, 0.0900, 2.00, 0),
(115, 101, 'manufactured', 8, 0.0800, 2.00, 0),
(116, 101, 'manufactured', 7, 0.0800, 2.00, 0),
(117, 101, 'manufactured', 4, 0.0900, 2.00, 0),
(118, 91, 'raw', 7, 0.0250, 1.00, 0),
(119, 91, 'raw', 8, 0.0300, 1.00, 0),
(120, 91, 'raw', 12, 0.0400, 1.00, 0),
(121, 91, 'raw', 108, 0.5000, 1.00, 0),
(122, 91, 'raw', 15, 1.0000, 0.50, 0),
(123, 169, 'raw', 15, 1.0000, 0.50, 0),
(124, 169, 'raw', 4, 1.0000, 0.50, 0),
(125, 169, 'raw', 108, 0.5000, 1.00, 0),
(126, 169, 'manufactured', 5, 0.0800, 2.00, 0),
(127, 169, 'raw', 9, 0.1200, 2.00, 0),
(128, 169, 'manufactured', 6, 0.0900, 2.00, 0),
(129, 169, 'manufactured', 8, 0.0800, 2.00, 0),
(130, 169, 'manufactured', 7, 0.0800, 2.00, 0),
(131, 169, 'manufactured', 4, 0.0900, 2.00, 0),
(132, 168, 'raw', 15, 1.0000, 0.50, 0),
(133, 168, 'raw', 4, 1.0000, 0.50, 0),
(134, 168, 'raw', 108, 0.5000, 1.00, 0),
(135, 168, 'manufactured', 5, 0.0800, 2.00, 0),
(136, 168, 'raw', 9, 0.1200, 2.00, 0),
(137, 168, 'manufactured', 6, 0.0900, 2.00, 0),
(138, 168, 'manufactured', 8, 0.0800, 2.00, 0),
(139, 168, 'manufactured', 7, 0.0800, 2.00, 0),
(140, 168, 'manufactured', 4, 0.0900, 2.00, 0),
(141, 167, 'raw', 15, 1.0000, 0.50, 0),
(142, 167, 'raw', 4, 1.0000, 0.50, 0),
(143, 167, 'raw', 108, 0.5000, 1.00, 0),
(144, 167, 'manufactured', 5, 0.0800, 2.00, 0),
(145, 167, 'raw', 9, 0.1200, 2.00, 0),
(146, 167, 'manufactured', 6, 0.0900, 2.00, 0),
(147, 167, 'manufactured', 8, 0.0800, 2.00, 0),
(148, 167, 'manufactured', 7, 0.0800, 2.00, 0),
(149, 167, 'manufactured', 4, 0.0900, 2.00, 0),
(150, 166, 'raw', 15, 1.0000, 0.50, 0),
(151, 166, 'raw', 4, 1.0000, 0.50, 0),
(152, 166, 'raw', 108, 0.5000, 1.00, 0),
(153, 166, 'manufactured', 5, 0.0800, 2.00, 0),
(154, 166, 'raw', 9, 0.1200, 2.00, 0),
(155, 166, 'manufactured', 6, 0.0900, 2.00, 0),
(156, 166, 'manufactured', 8, 0.0800, 2.00, 0),
(157, 166, 'manufactured', 7, 0.0800, 2.00, 0),
(158, 166, 'manufactured', 4, 0.0900, 2.00, 0),
(159, 165, 'raw', 15, 1.0000, 0.50, 0),
(160, 165, 'raw', 4, 1.0000, 0.50, 0),
(161, 165, 'raw', 108, 0.5000, 1.00, 0),
(162, 165, 'manufactured', 5, 0.0800, 2.00, 0),
(163, 165, 'raw', 9, 0.1200, 2.00, 0),
(164, 165, 'manufactured', 6, 0.0900, 2.00, 0),
(165, 165, 'manufactured', 8, 0.0800, 2.00, 0),
(166, 165, 'manufactured', 7, 0.0800, 2.00, 0),
(167, 165, 'manufactured', 4, 0.0900, 2.00, 0),
(168, 164, 'raw', 15, 1.0000, 0.50, 0),
(169, 164, 'raw', 4, 1.0000, 0.50, 0),
(170, 164, 'raw', 108, 0.5000, 1.00, 0),
(171, 164, 'manufactured', 5, 0.0800, 2.00, 0),
(172, 164, 'raw', 9, 0.1200, 2.00, 0),
(173, 164, 'manufactured', 6, 0.0900, 2.00, 0),
(174, 164, 'manufactured', 8, 0.0800, 2.00, 0),
(175, 164, 'manufactured', 7, 0.0800, 2.00, 0),
(176, 164, 'manufactured', 4, 0.0900, 2.00, 0),
(177, 160, 'raw', 7, 0.0250, 1.00, 0),
(178, 160, 'raw', 8, 0.0300, 1.00, 0),
(179, 160, 'raw', 12, 0.0400, 1.00, 0),
(180, 160, 'raw', 108, 0.5000, 1.00, 0),
(181, 160, 'raw', 15, 1.0000, 0.50, 0),
(182, 158, 'raw', 7, 0.0250, 1.00, 0),
(183, 158, 'raw', 8, 0.0300, 1.00, 0),
(184, 158, 'raw', 12, 0.0400, 1.00, 0),
(185, 158, 'raw', 108, 0.5000, 1.00, 0),
(186, 158, 'raw', 15, 1.0000, 0.50, 0),
(187, 155, 'raw', 7, 0.0250, 1.00, 0),
(188, 155, 'raw', 8, 0.0300, 1.00, 0),
(189, 155, 'raw', 12, 0.0400, 1.00, 0),
(190, 155, 'raw', 108, 0.5000, 1.00, 0),
(191, 155, 'raw', 15, 1.0000, 0.50, 0),
(192, 154, 'raw', 7, 0.0250, 1.00, 0),
(193, 154, 'raw', 8, 0.0300, 1.00, 0),
(194, 154, 'raw', 12, 0.0400, 1.00, 0),
(195, 154, 'raw', 108, 0.5000, 1.00, 0),
(196, 154, 'raw', 15, 1.0000, 0.50, 0),
(197, 153, 'raw', 7, 0.0250, 1.00, 0),
(198, 153, 'raw', 8, 0.0300, 1.00, 0),
(199, 153, 'raw', 12, 0.0400, 1.00, 0),
(200, 153, 'raw', 108, 0.5000, 1.00, 0),
(201, 153, 'raw', 15, 1.0000, 0.50, 0),
(207, 93, 'raw', 7, 0.0250, 1.00, 0),
(208, 93, 'raw', 8, 0.0300, 1.00, 0),
(209, 93, 'raw', 12, 0.0400, 1.00, 0),
(210, 93, 'raw', 108, 0.5000, 1.00, 0),
(211, 93, 'raw', 15, 1.0000, 0.50, 0),
(212, 97, 'raw', 7, 0.0250, 1.00, 0),
(213, 97, 'raw', 8, 0.0300, 1.00, 0),
(214, 97, 'raw', 12, 0.0400, 1.00, 0),
(215, 97, 'raw', 108, 0.5000, 1.00, 0),
(216, 97, 'raw', 15, 1.0000, 0.50, 0),
(232, 170, 'manufactured', 5, 0.0800, 2.00, 0),
(233, 170, 'raw', 9, 0.1200, 2.00, 0),
(234, 170, 'manufactured', 6, 0.0900, 2.00, 0),
(235, 170, 'manufactured', 8, 0.0800, 2.00, 0),
(236, 170, 'manufactured', 7, 0.0800, 2.00, 0),
(237, 170, 'manufactured', 4, 0.0900, 2.00, 0),
(238, 170, 'raw', 7, 0.0250, 1.00, 0),
(239, 170, 'raw', 8, 0.0300, 1.00, 0),
(240, 170, 'raw', 12, 0.0400, 1.00, 0),
(241, 170, 'raw', 108, 0.5000, 1.00, 0),
(242, 170, 'raw', 15, 1.0000, 0.50, 0),
(243, 171, 'manufactured', 5, 0.0800, 2.00, 0),
(244, 171, 'raw', 9, 0.1200, 2.00, 0),
(245, 171, 'manufactured', 6, 0.0900, 2.00, 0),
(246, 171, 'manufactured', 8, 0.0800, 2.00, 0),
(247, 171, 'manufactured', 7, 0.0800, 2.00, 0),
(248, 171, 'manufactured', 4, 0.0900, 2.00, 0),
(249, 171, 'raw', 7, 0.0250, 1.00, 0),
(250, 171, 'raw', 8, 0.0300, 1.00, 0),
(251, 171, 'raw', 12, 0.0400, 1.00, 0),
(252, 171, 'raw', 108, 0.5000, 1.00, 0),
(253, 171, 'raw', 15, 1.0000, 0.50, 0),
(254, 172, 'manufactured', 5, 0.0800, 2.00, 0),
(255, 172, 'raw', 9, 0.1200, 2.00, 0),
(256, 172, 'manufactured', 6, 0.0900, 2.00, 0),
(257, 172, 'manufactured', 8, 0.0800, 2.00, 0),
(258, 172, 'manufactured', 7, 0.0800, 2.00, 0),
(259, 172, 'manufactured', 4, 0.0900, 2.00, 0),
(260, 172, 'raw', 7, 0.0250, 1.00, 0),
(261, 172, 'raw', 8, 0.0300, 1.00, 0),
(262, 172, 'raw', 12, 0.0400, 1.00, 0),
(263, 172, 'raw', 108, 0.5000, 1.00, 0),
(264, 172, 'raw', 15, 1.0000, 0.50, 0),
(265, 173, 'manufactured', 5, 0.0800, 2.00, 0),
(266, 173, 'raw', 9, 0.1200, 2.00, 0),
(267, 173, 'manufactured', 6, 0.0900, 2.00, 0),
(268, 173, 'manufactured', 8, 0.0800, 2.00, 0),
(269, 173, 'manufactured', 7, 0.0800, 2.00, 0),
(270, 173, 'manufactured', 4, 0.0900, 2.00, 0),
(271, 173, 'raw', 7, 0.0250, 1.00, 0),
(272, 173, 'raw', 8, 0.0300, 1.00, 0),
(273, 173, 'raw', 12, 0.0400, 1.00, 0),
(274, 173, 'raw', 108, 0.5000, 1.00, 0),
(275, 173, 'raw', 15, 1.0000, 0.50, 0),
(276, 178, 'manufactured', 5, 0.0800, 2.00, 0),
(277, 178, 'raw', 9, 0.1200, 2.00, 0),
(278, 178, 'manufactured', 6, 0.0900, 2.00, 0),
(279, 178, 'manufactured', 8, 0.0800, 2.00, 0),
(280, 178, 'manufactured', 7, 0.0800, 2.00, 0),
(281, 178, 'manufactured', 4, 0.0900, 2.00, 0),
(282, 178, 'raw', 7, 0.0250, 1.00, 0),
(283, 178, 'raw', 8, 0.0300, 1.00, 0),
(284, 178, 'raw', 12, 0.0400, 1.00, 0),
(285, 178, 'raw', 108, 0.5000, 1.00, 0),
(286, 178, 'raw', 15, 1.0000, 0.50, 0),
(287, 179, 'manufactured', 5, 0.0800, 2.00, 0),
(288, 179, 'raw', 9, 0.1200, 2.00, 0),
(289, 179, 'manufactured', 6, 0.0900, 2.00, 0),
(290, 179, 'manufactured', 8, 0.0800, 2.00, 0),
(291, 179, 'manufactured', 7, 0.0800, 2.00, 0),
(292, 179, 'manufactured', 4, 0.0900, 2.00, 0),
(293, 179, 'raw', 7, 0.0250, 1.00, 0),
(294, 179, 'raw', 8, 0.0300, 1.00, 0),
(295, 179, 'raw', 12, 0.0400, 1.00, 0),
(296, 179, 'raw', 108, 0.5000, 1.00, 0),
(297, 179, 'raw', 15, 1.0000, 0.50, 0),
(298, 180, 'manufactured', 5, 0.0800, 2.00, 0),
(299, 180, 'raw', 9, 0.1200, 2.00, 0),
(300, 180, 'manufactured', 6, 0.0900, 2.00, 0),
(301, 180, 'manufactured', 8, 0.0800, 2.00, 0),
(302, 180, 'manufactured', 7, 0.0800, 2.00, 0),
(303, 180, 'manufactured', 4, 0.0900, 2.00, 0),
(304, 180, 'raw', 7, 0.0250, 1.00, 0),
(305, 180, 'raw', 8, 0.0300, 1.00, 0),
(306, 180, 'raw', 12, 0.0400, 1.00, 0),
(307, 180, 'raw', 15, 1.0000, 0.50, 0),
(308, 181, 'manufactured', 5, 0.0800, 2.00, 0),
(309, 181, 'raw', 9, 0.1200, 2.00, 0),
(310, 181, 'manufactured', 6, 0.0900, 2.00, 0),
(311, 181, 'manufactured', 8, 0.0800, 2.00, 0),
(312, 181, 'manufactured', 7, 0.0800, 2.00, 0),
(313, 181, 'manufactured', 4, 0.0900, 2.00, 0),
(314, 181, 'raw', 7, 0.0250, 1.00, 0),
(315, 181, 'raw', 8, 0.0300, 1.00, 0),
(316, 181, 'raw', 12, 0.0400, 1.00, 0),
(317, 181, 'raw', 108, 0.5000, 1.00, 0),
(318, 181, 'raw', 15, 1.0000, 0.50, 0),
(319, 206, 'raw', 29, 0.1000, 1.50, 0),
(320, 206, 'raw', 30, 0.1000, 1.50, 0),
(321, 206, 'raw', 7, 0.1000, 3.00, 0),
(322, 206, 'raw', 28, 0.1000, 2.00, 0),
(323, 206, 'raw', 95, 0.1000, 4.00, 0),
(324, 206, 'raw', 36, 0.1000, 2.00, 0),
(325, 206, 'raw', 37, 100.0000, 3.00, 0),
(326, 206, 'raw', 23, 3.0000, 2.00, 0),
(327, 206, 'raw', 54, 80.0000, 1.00, 0),
(328, 206, 'raw', 53, 100.0000, 1.00, 0),
(329, 206, 'raw', 31, 0.2000, 3.00, 0),
(330, 206, 'raw', 32, 0.2000, 3.00, 0),
(331, 182, 'raw', 37, 100.0000, 3.00, 0),
(332, 183, 'raw', 29, 0.1000, 1.50, 0),
(333, 183, 'raw', 30, 0.1000, 1.50, 0),
(334, 183, 'raw', 7, 0.1000, 3.00, 0),
(335, 183, 'raw', 28, 0.1000, 2.00, 0),
(336, 183, 'raw', 95, 0.1000, 4.00, 0),
(337, 183, 'raw', 36, 0.1000, 2.00, 0),
(338, 183, 'raw', 37, 100.0000, 3.00, 0),
(339, 183, 'raw', 23, 3.0000, 2.00, 0),
(340, 183, 'raw', 54, 80.0000, 1.00, 0),
(341, 183, 'raw', 53, 100.0000, 1.00, 0),
(342, 183, 'raw', 31, 0.2000, 3.00, 0),
(343, 183, 'raw', 32, 0.2000, 3.00, 0),
(344, 184, 'raw', 29, 0.1000, 1.50, 0),
(345, 184, 'raw', 30, 0.1000, 1.50, 0),
(346, 184, 'raw', 7, 0.1000, 3.00, 0),
(347, 184, 'raw', 28, 0.1000, 2.00, 0),
(348, 184, 'raw', 95, 0.1000, 4.00, 0),
(349, 184, 'raw', 36, 0.1000, 2.00, 0),
(350, 184, 'raw', 37, 100.0000, 3.00, 0),
(351, 184, 'raw', 23, 3.0000, 2.00, 0),
(352, 184, 'raw', 54, 80.0000, 1.00, 0),
(353, 184, 'raw', 53, 100.0000, 1.00, 0),
(354, 184, 'raw', 31, 0.2000, 3.00, 0),
(355, 184, 'raw', 32, 0.2000, 3.00, 0),
(356, 185, 'raw', 29, 0.1000, 1.50, 0),
(357, 185, 'raw', 30, 0.1000, 1.50, 0),
(358, 185, 'raw', 7, 0.1000, 3.00, 0),
(359, 185, 'raw', 28, 0.1000, 2.00, 0),
(360, 185, 'raw', 95, 0.1000, 4.00, 0),
(361, 185, 'raw', 36, 0.1000, 2.00, 0),
(362, 185, 'raw', 37, 100.0000, 3.00, 0),
(363, 185, 'raw', 23, 3.0000, 2.00, 0),
(364, 185, 'raw', 54, 80.0000, 1.00, 0),
(365, 185, 'raw', 53, 100.0000, 1.00, 0),
(366, 185, 'raw', 31, 0.2000, 3.00, 0),
(367, 185, 'raw', 32, 0.2000, 3.00, 0),
(368, 188, 'raw', 29, 0.1000, 1.50, 0),
(369, 188, 'raw', 30, 0.1000, 1.50, 0),
(370, 188, 'raw', 7, 0.1000, 3.00, 0),
(371, 188, 'raw', 28, 0.1000, 2.00, 0),
(372, 188, 'raw', 95, 0.1000, 4.00, 0),
(373, 188, 'raw', 36, 0.1000, 2.00, 0),
(374, 188, 'raw', 37, 100.0000, 3.00, 0),
(375, 188, 'raw', 23, 3.0000, 2.00, 0),
(376, 188, 'raw', 54, 80.0000, 1.00, 0),
(377, 188, 'raw', 53, 100.0000, 1.00, 0),
(378, 188, 'raw', 31, 0.2000, 3.00, 0),
(379, 188, 'raw', 32, 0.2000, 3.00, 0),
(380, 187, 'raw', 29, 0.1000, 1.50, 0),
(381, 187, 'raw', 30, 0.1000, 1.50, 0),
(382, 187, 'raw', 7, 0.1000, 3.00, 0),
(383, 187, 'raw', 28, 0.1000, 2.00, 0),
(384, 187, 'raw', 95, 0.1000, 4.00, 0),
(385, 187, 'raw', 36, 0.1000, 2.00, 0),
(386, 187, 'raw', 37, 100.0000, 3.00, 0),
(387, 187, 'raw', 23, 3.0000, 2.00, 0),
(388, 187, 'raw', 54, 80.0000, 1.00, 0),
(389, 187, 'raw', 53, 100.0000, 1.00, 0),
(390, 187, 'raw', 31, 0.2000, 3.00, 0),
(391, 187, 'raw', 32, 0.2000, 3.00, 0),
(392, 186, 'raw', 29, 0.1000, 1.50, 0),
(393, 186, 'raw', 30, 0.1000, 1.50, 0),
(394, 186, 'raw', 7, 0.1000, 3.00, 0),
(395, 186, 'raw', 28, 0.1000, 2.00, 0),
(396, 186, 'raw', 95, 0.1000, 4.00, 0),
(397, 186, 'raw', 36, 0.1000, 2.00, 0),
(398, 186, 'raw', 37, 100.0000, 3.00, 0),
(399, 186, 'raw', 23, 3.0000, 2.00, 0),
(400, 186, 'raw', 54, 80.0000, 1.00, 0),
(401, 186, 'raw', 53, 100.0000, 1.00, 0),
(402, 186, 'raw', 31, 0.2000, 3.00, 0),
(403, 186, 'raw', 32, 0.2000, 3.00, 0),
(404, 189, 'raw', 29, 0.1000, 1.50, 0),
(405, 189, 'raw', 30, 0.1000, 1.50, 0),
(406, 189, 'raw', 7, 0.1000, 3.00, 0),
(407, 189, 'raw', 28, 0.1000, 2.00, 0),
(408, 189, 'raw', 95, 0.1000, 4.00, 0),
(409, 189, 'raw', 36, 0.1000, 2.00, 0),
(410, 189, 'raw', 37, 100.0000, 3.00, 0),
(411, 189, 'raw', 23, 3.0000, 2.00, 0),
(412, 189, 'raw', 54, 80.0000, 1.00, 0),
(413, 189, 'raw', 53, 100.0000, 1.00, 0),
(414, 189, 'raw', 31, 0.2000, 3.00, 0),
(415, 189, 'raw', 32, 0.2000, 3.00, 0),
(416, 190, 'raw', 29, 0.1000, 1.50, 0),
(417, 190, 'raw', 30, 0.1000, 1.50, 0),
(418, 190, 'raw', 7, 0.1000, 3.00, 0),
(419, 190, 'raw', 28, 0.1000, 2.00, 0),
(420, 190, 'raw', 95, 0.1000, 4.00, 0),
(421, 190, 'raw', 36, 0.1000, 2.00, 0),
(422, 190, 'raw', 37, 100.0000, 3.00, 0),
(423, 190, 'raw', 23, 3.0000, 2.00, 0),
(424, 190, 'raw', 54, 80.0000, 1.00, 0),
(425, 190, 'raw', 53, 100.0000, 1.00, 0),
(426, 190, 'raw', 31, 0.2000, 3.00, 0),
(427, 190, 'raw', 32, 0.2000, 3.00, 0),
(428, 191, 'raw', 29, 0.1000, 1.50, 0),
(429, 191, 'raw', 30, 0.1000, 1.50, 0),
(430, 191, 'raw', 7, 0.1000, 3.00, 0),
(431, 191, 'raw', 28, 0.1000, 2.00, 0),
(432, 191, 'raw', 95, 0.1000, 4.00, 0),
(433, 191, 'raw', 36, 0.1000, 2.00, 0),
(434, 191, 'raw', 37, 100.0000, 3.00, 0),
(435, 191, 'raw', 23, 3.0000, 2.00, 0),
(436, 191, 'raw', 54, 80.0000, 1.00, 0),
(437, 191, 'raw', 53, 100.0000, 1.00, 0),
(438, 191, 'raw', 31, 0.2000, 3.00, 0),
(439, 191, 'raw', 32, 0.2000, 3.00, 0),
(440, 192, 'raw', 29, 0.1000, 1.50, 0),
(441, 192, 'raw', 30, 0.1000, 1.50, 0),
(442, 192, 'raw', 7, 0.1000, 3.00, 0),
(443, 192, 'raw', 28, 0.1000, 2.00, 0),
(444, 192, 'raw', 95, 0.1000, 4.00, 0),
(445, 192, 'raw', 36, 0.1000, 2.00, 0),
(446, 192, 'raw', 37, 100.0000, 3.00, 0),
(447, 192, 'raw', 23, 3.0000, 2.00, 0),
(448, 192, 'raw', 54, 80.0000, 1.00, 0),
(449, 192, 'raw', 53, 100.0000, 1.00, 0),
(450, 192, 'raw', 31, 0.2000, 3.00, 0),
(451, 192, 'raw', 32, 0.2000, 3.00, 0),
(452, 193, 'raw', 29, 0.1000, 1.50, 0),
(453, 193, 'raw', 30, 0.1000, 1.50, 0),
(454, 193, 'raw', 7, 0.1000, 3.00, 0),
(455, 193, 'raw', 28, 0.1000, 2.00, 0),
(456, 193, 'raw', 95, 0.1000, 4.00, 0),
(457, 193, 'raw', 36, 0.1000, 2.00, 0),
(458, 193, 'raw', 37, 100.0000, 3.00, 0),
(459, 193, 'raw', 23, 3.0000, 2.00, 0),
(460, 193, 'raw', 54, 80.0000, 1.00, 0),
(461, 193, 'raw', 53, 100.0000, 1.00, 0),
(462, 193, 'raw', 31, 0.2000, 3.00, 0),
(463, 193, 'raw', 32, 0.2000, 3.00, 0),
(464, 194, 'raw', 29, 0.1000, 1.50, 0),
(465, 194, 'raw', 30, 0.1000, 1.50, 0),
(466, 194, 'raw', 7, 0.1000, 3.00, 0),
(467, 194, 'raw', 28, 0.1000, 2.00, 0),
(468, 194, 'raw', 95, 0.1000, 4.00, 0),
(469, 194, 'raw', 36, 0.1000, 2.00, 0),
(470, 194, 'raw', 37, 100.0000, 3.00, 0),
(471, 194, 'raw', 23, 3.0000, 2.00, 0),
(472, 194, 'raw', 54, 80.0000, 1.00, 0),
(473, 194, 'raw', 53, 100.0000, 1.00, 0),
(474, 194, 'raw', 31, 0.2000, 3.00, 0),
(475, 194, 'raw', 32, 0.2000, 3.00, 0),
(476, 195, 'raw', 29, 0.1000, 1.50, 0),
(477, 195, 'raw', 30, 0.1000, 1.50, 0),
(478, 195, 'raw', 7, 0.1000, 3.00, 0),
(479, 195, 'raw', 28, 0.1000, 2.00, 0),
(480, 195, 'raw', 95, 0.1000, 4.00, 0),
(481, 195, 'raw', 36, 0.1000, 2.00, 0),
(482, 195, 'raw', 37, 100.0000, 3.00, 0),
(483, 195, 'raw', 23, 3.0000, 2.00, 0),
(484, 195, 'raw', 54, 80.0000, 1.00, 0),
(485, 195, 'raw', 53, 100.0000, 1.00, 0),
(486, 195, 'raw', 31, 0.2000, 3.00, 0),
(487, 195, 'raw', 32, 0.2000, 3.00, 0),
(488, 196, 'raw', 29, 0.1000, 1.50, 0),
(489, 196, 'raw', 30, 0.1000, 1.50, 0),
(490, 196, 'raw', 7, 0.1000, 3.00, 0),
(491, 196, 'raw', 28, 0.1000, 2.00, 0),
(492, 196, 'raw', 95, 0.1000, 4.00, 0),
(493, 196, 'raw', 36, 0.1000, 2.00, 0),
(494, 196, 'raw', 37, 100.0000, 3.00, 0),
(495, 196, 'raw', 23, 3.0000, 2.00, 0),
(496, 196, 'raw', 54, 80.0000, 1.00, 0),
(497, 196, 'raw', 53, 100.0000, 1.00, 0),
(498, 196, 'raw', 31, 0.2000, 3.00, 0),
(499, 196, 'raw', 32, 0.2000, 3.00, 0),
(500, 197, 'raw', 29, 0.1000, 1.50, 0),
(501, 197, 'raw', 30, 0.1000, 1.50, 0),
(502, 197, 'raw', 7, 0.0250, 1.00, 0),
(503, 197, 'raw', 28, 0.1000, 2.00, 0),
(504, 197, 'raw', 95, 0.1000, 4.00, 0),
(505, 197, 'raw', 36, 0.1000, 2.00, 0),
(506, 197, 'raw', 37, 100.0000, 3.00, 0),
(507, 197, 'raw', 23, 3.0000, 2.00, 0),
(508, 197, 'raw', 54, 80.0000, 1.00, 0),
(509, 197, 'raw', 53, 100.0000, 1.00, 0),
(510, 197, 'raw', 31, 0.2000, 3.00, 0),
(511, 197, 'raw', 32, 0.2000, 3.00, 0),
(512, 198, 'raw', 29, 0.1000, 1.50, 0),
(513, 198, 'raw', 30, 0.1000, 1.50, 0),
(514, 198, 'raw', 7, 0.1000, 3.00, 0),
(515, 198, 'raw', 28, 0.1000, 2.00, 0),
(516, 198, 'raw', 95, 0.1000, 4.00, 0),
(517, 198, 'raw', 36, 0.1000, 2.00, 0),
(518, 198, 'raw', 37, 100.0000, 3.00, 0),
(519, 198, 'raw', 23, 3.0000, 2.00, 0),
(520, 198, 'raw', 54, 80.0000, 1.00, 0),
(521, 198, 'raw', 53, 100.0000, 1.00, 0),
(522, 198, 'raw', 31, 0.2000, 3.00, 0),
(523, 198, 'raw', 32, 0.2000, 3.00, 0),
(524, 199, 'raw', 29, 0.1000, 1.50, 0),
(525, 199, 'raw', 30, 0.1000, 1.50, 0),
(526, 199, 'raw', 7, 0.1000, 3.00, 0),
(527, 199, 'raw', 28, 0.1000, 2.00, 0),
(528, 199, 'raw', 95, 0.1000, 4.00, 0),
(529, 199, 'raw', 36, 0.1000, 2.00, 0),
(530, 199, 'raw', 37, 100.0000, 3.00, 0),
(531, 199, 'raw', 23, 3.0000, 2.00, 0),
(532, 199, 'raw', 54, 80.0000, 1.00, 0),
(533, 199, 'raw', 53, 100.0000, 1.00, 0),
(534, 199, 'raw', 31, 0.2000, 3.00, 0),
(535, 199, 'raw', 32, 0.2000, 3.00, 0),
(536, 200, 'raw', 29, 0.1000, 1.50, 0),
(537, 200, 'raw', 30, 0.1000, 1.50, 0),
(538, 200, 'raw', 7, 0.0250, 1.00, 0),
(539, 200, 'raw', 28, 0.1000, 2.00, 0),
(540, 200, 'raw', 95, 0.1000, 4.00, 0),
(541, 200, 'raw', 36, 0.1000, 2.00, 0),
(542, 200, 'raw', 37, 100.0000, 3.00, 0),
(543, 200, 'raw', 23, 3.0000, 2.00, 0),
(544, 200, 'raw', 54, 80.0000, 1.00, 0),
(545, 200, 'raw', 53, 100.0000, 1.00, 0),
(546, 200, 'raw', 31, 0.2000, 3.00, 0),
(547, 200, 'raw', 32, 0.2000, 3.00, 0),
(548, 201, 'raw', 29, 0.1000, 1.50, 0),
(549, 201, 'raw', 30, 0.1000, 1.50, 0),
(550, 201, 'raw', 7, 0.0250, 1.00, 0),
(551, 201, 'raw', 28, 0.1000, 2.00, 0),
(552, 201, 'raw', 95, 0.1000, 4.00, 0),
(553, 201, 'raw', 36, 0.1000, 2.00, 0),
(554, 201, 'raw', 37, 100.0000, 3.00, 0),
(555, 201, 'raw', 23, 3.0000, 2.00, 0),
(556, 201, 'raw', 54, 80.0000, 1.00, 0),
(557, 201, 'raw', 53, 100.0000, 1.00, 0),
(558, 201, 'raw', 31, 0.2000, 3.00, 0),
(559, 201, 'raw', 32, 0.2000, 3.00, 0),
(560, 202, 'raw', 29, 0.1000, 1.50, 0),
(561, 202, 'raw', 30, 0.1000, 1.50, 0),
(562, 202, 'raw', 7, 0.0250, 1.00, 0),
(563, 202, 'raw', 28, 0.1000, 2.00, 0),
(564, 202, 'raw', 95, 0.1000, 4.00, 0),
(565, 202, 'raw', 36, 0.1000, 2.00, 0),
(566, 202, 'raw', 37, 100.0000, 3.00, 0),
(567, 202, 'raw', 23, 3.0000, 2.00, 0),
(568, 202, 'raw', 54, 80.0000, 1.00, 0),
(569, 202, 'raw', 53, 100.0000, 1.00, 0),
(570, 202, 'raw', 31, 0.2000, 3.00, 0),
(571, 202, 'raw', 32, 0.2000, 3.00, 0),
(572, 205, 'raw', 29, 0.1000, 1.50, 0),
(573, 205, 'raw', 30, 0.1000, 1.50, 0),
(574, 205, 'raw', 7, 0.1000, 3.00, 0),
(575, 205, 'raw', 28, 0.1000, 2.00, 0),
(576, 205, 'raw', 95, 0.1000, 4.00, 0),
(577, 205, 'raw', 36, 0.1000, 2.00, 0),
(578, 205, 'raw', 37, 100.0000, 3.00, 0),
(579, 205, 'raw', 23, 3.0000, 2.00, 0),
(580, 205, 'raw', 54, 80.0000, 1.00, 0),
(581, 205, 'raw', 53, 100.0000, 1.00, 0),
(582, 205, 'raw', 31, 0.2000, 3.00, 0),
(583, 205, 'raw', 32, 0.2000, 3.00, 0),
(584, 202, 'manufactured', 5, 0.0800, 2.00, 0),
(585, 202, 'raw', 9, 0.1200, 2.00, 0),
(586, 202, 'manufactured', 6, 0.0900, 2.00, 0),
(587, 202, 'manufactured', 8, 0.0800, 2.00, 0),
(588, 202, 'manufactured', 7, 0.0800, 2.00, 0),
(589, 202, 'manufactured', 4, 0.0900, 2.00, 0),
(594, 197, 'manufactured', 5, 0.0800, 2.00, 0),
(595, 197, 'raw', 9, 0.1200, 2.00, 0),
(596, 197, 'manufactured', 6, 0.0900, 2.00, 0),
(597, 197, 'manufactured', 8, 0.0800, 2.00, 0),
(598, 197, 'manufactured', 7, 0.0800, 2.00, 0),
(599, 197, 'manufactured', 4, 0.0900, 2.00, 0),
(600, 200, 'manufactured', 5, 0.0800, 2.00, 0),
(601, 200, 'raw', 9, 0.1200, 2.00, 0),
(602, 200, 'manufactured', 6, 0.0900, 2.00, 0),
(603, 200, 'manufactured', 8, 0.0800, 2.00, 0),
(604, 200, 'manufactured', 7, 0.0800, 2.00, 0),
(605, 200, 'manufactured', 4, 0.0900, 2.00, 0),
(606, 201, 'manufactured', 5, 0.0800, 2.00, 0),
(607, 201, 'raw', 9, 0.1200, 2.00, 0),
(608, 201, 'manufactured', 6, 0.0900, 2.00, 0),
(609, 201, 'manufactured', 8, 0.0800, 2.00, 0),
(610, 201, 'manufactured', 7, 0.0800, 2.00, 0),
(611, 201, 'manufactured', 4, 0.0900, 2.00, 0),
(612, 158, 'product', 93, 1.0000, 2.00, 0),
(613, 160, 'product', 93, 1.0000, 2.00, 0),
(614, 155, 'product', 93, 1.0000, 2.00, 0),
(615, 154, 'product', 93, 1.0000, 2.00, 0),
(616, 153, 'product', 93, 1.0000, 2.00, 0),
(617, 97, 'product', 93, 1.0000, 2.00, 0),
(625, 202, 'product', 93, 1.0000, 2.00, 0),
(630, 201, 'product', 93, 1.0000, 2.00, 0),
(635, 200, 'product', 93, 1.0000, 2.00, 0),
(640, 197, 'product', 93, 1.0000, 2.00, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_valid_sides`
--

CREATE TABLE `product_valid_sides` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `component_type` enum('raw','manufactured','product') NOT NULL,
  `component_id` int(11) NOT NULL,
  `quantity` decimal(10,4) NOT NULL,
  `price_override` decimal(20,6) DEFAULT 0.000000,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `product_valid_sides`
--

INSERT INTO `product_valid_sides` (`id`, `product_id`, `component_type`, `component_id`, `quantity`, `price_override`, `is_default`) VALUES
(2, 180, 'manufactured', 8, 0.0800, 0.000000, 0),
(3, 180, 'manufactured', 6, 0.0900, 0.000000, 0),
(5, 192, 'raw', 95, 0.1000, 0.000000, 0),
(6, 192, 'raw', 28, 0.1000, 0.000000, 0),
(7, 192, 'raw', 30, 0.1500, 0.000000, 0),
(8, 192, 'raw', 54, 80.0000, 0.000000, 0),
(9, 192, 'raw', 53, 100.0000, 0.000000, 0),
(10, 192, 'raw', 29, 0.1000, 0.000000, 0),
(11, 192, 'raw', 7, 0.1000, 0.000000, 0),
(12, 192, 'raw', 36, 0.1000, 0.000000, 0),
(14, 193, 'raw', 37, 100.0000, 0.000000, 0),
(15, 193, 'raw', 95, 0.1000, 0.000000, 0),
(16, 193, 'raw', 28, 0.1000, 0.000000, 0),
(17, 193, 'raw', 30, 0.1500, 0.000000, 0),
(18, 193, 'raw', 54, 80.0000, 0.000000, 0),
(19, 193, 'raw', 53, 100.0000, 0.000000, 0),
(20, 193, 'raw', 29, 0.1000, 0.000000, 0),
(21, 193, 'raw', 7, 0.1000, 0.000000, 0),
(22, 193, 'raw', 36, 0.1000, 0.000000, 0),
(23, 192, 'raw', 37, 100.0000, 0.000000, 0),
(24, 194, 'raw', 37, 100.0000, 0.000000, 0),
(25, 194, 'raw', 95, 0.1000, 0.000000, 0),
(26, 194, 'raw', 28, 0.1000, 0.000000, 0),
(27, 194, 'raw', 30, 0.1500, 0.000000, 0),
(30, 194, 'raw', 29, 0.1000, 0.000000, 0),
(31, 194, 'raw', 7, 0.1000, 0.000000, 0),
(32, 194, 'raw', 36, 0.1000, 0.000000, 0),
(33, 197, 'raw', 37, 100.0000, 0.000000, 0),
(34, 197, 'raw', 95, 0.1000, 0.000000, 0),
(35, 197, 'raw', 28, 0.1000, 0.000000, 0),
(36, 197, 'raw', 30, 0.1500, 0.000000, 0),
(37, 197, 'raw', 54, 80.0000, 0.000000, 0),
(38, 197, 'raw', 53, 100.0000, 0.000000, 0),
(39, 197, 'raw', 29, 0.1000, 0.000000, 0),
(40, 197, 'raw', 7, 0.1000, 0.000000, 0),
(41, 197, 'raw', 36, 0.1000, 0.000000, 0),
(42, 198, 'raw', 37, 100.0000, 0.000000, 0),
(43, 198, 'raw', 95, 0.1000, 0.000000, 0),
(44, 198, 'raw', 28, 0.1000, 0.000000, 0),
(45, 198, 'raw', 30, 0.1500, 0.000000, 0),
(46, 198, 'raw', 54, 80.0000, 0.000000, 0),
(47, 198, 'raw', 53, 100.0000, 0.000000, 0),
(48, 198, 'raw', 29, 0.1000, 0.000000, 0),
(49, 198, 'raw', 7, 0.1000, 0.000000, 0),
(50, 198, 'raw', 36, 0.1000, 0.000000, 0),
(51, 199, 'raw', 37, 100.0000, 0.000000, 0),
(52, 199, 'raw', 95, 0.1000, 0.000000, 0),
(53, 199, 'raw', 28, 0.1000, 0.000000, 0),
(54, 199, 'raw', 30, 0.1500, 0.000000, 0),
(55, 199, 'raw', 54, 80.0000, 0.000000, 0),
(56, 199, 'raw', 53, 100.0000, 0.000000, 0),
(57, 199, 'raw', 29, 0.1000, 0.000000, 0),
(58, 199, 'raw', 7, 0.1000, 0.000000, 0),
(59, 199, 'raw', 36, 0.1000, 0.000000, 0),
(64, 91, 'manufactured', 6, 0.0450, 0.500000, 0),
(65, 91, 'manufactured', 3, 0.0400, 0.000000, 0),
(66, 205, 'raw', 95, 0.1000, 0.000000, 0),
(67, 205, 'raw', 28, 0.1000, 0.000000, 0),
(68, 205, 'raw', 30, 0.1500, 0.000000, 0),
(69, 205, 'raw', 54, 80.0000, 0.000000, 0),
(70, 205, 'raw', 53, 100.0000, 0.000000, 0),
(71, 205, 'raw', 29, 0.1000, 0.000000, 0),
(72, 205, 'raw', 7, 0.1000, 0.000000, 0),
(73, 205, 'raw', 36, 0.1000, 0.000000, 0),
(74, 205, 'raw', 37, 100.0000, 0.000000, 0),
(75, 206, 'raw', 37, 100.0000, 0.000000, 0),
(76, 206, 'raw', 95, 0.1000, 0.000000, 0),
(77, 206, 'raw', 28, 0.1000, 0.000000, 0),
(78, 206, 'raw', 30, 0.1500, 0.000000, 0),
(79, 206, 'raw', 54, 80.0000, 0.000000, 0),
(80, 206, 'raw', 53, 100.0000, 0.000000, 0),
(81, 206, 'raw', 29, 0.1000, 0.000000, 0),
(82, 206, 'raw', 7, 0.1000, 0.000000, 0),
(83, 206, 'raw', 36, 0.1000, 0.000000, 0),
(85, 158, 'manufactured', 7, 0.0800, 0.000000, 0),
(86, 158, 'manufactured', 8, 0.0800, 0.000000, 0),
(87, 158, 'manufactured', 5, 0.0800, 0.000000, 0),
(88, 158, 'manufactured', 6, 0.0900, 0.000000, 0),
(89, 158, 'raw', 9, 0.1200, 0.000000, 0),
(91, 160, 'manufactured', 7, 0.0800, 0.000000, 0),
(92, 160, 'manufactured', 8, 0.0800, 0.000000, 0),
(93, 160, 'manufactured', 5, 0.0800, 0.000000, 0),
(94, 160, 'manufactured', 6, 0.0900, 0.000000, 0),
(95, 160, 'raw', 9, 0.1200, 0.000000, 0),
(97, 154, 'manufactured', 7, 0.0800, 0.000000, 0),
(98, 154, 'manufactured', 8, 0.0800, 0.000000, 0),
(99, 154, 'manufactured', 5, 0.0800, 0.000000, 0),
(100, 154, 'manufactured', 6, 0.0900, 0.000000, 0),
(101, 154, 'raw', 9, 0.1200, 0.000000, 0),
(102, 158, 'manufactured', 4, 0.0900, 0.000000, 0),
(103, 160, 'manufactured', 4, 0.0900, 0.000000, 0),
(104, 154, 'manufactured', 4, 0.0900, 0.000000, 0),
(105, 101, 'raw', 4, 1.0000, 0.000000, 0),
(106, 101, 'raw', 15, 1.0000, 0.000000, 0),
(107, 101, 'raw', 108, 0.5000, 0.000000, 0),
(108, 170, 'manufactured', 7, 0.0800, 0.000000, 0),
(109, 170, 'manufactured', 8, 0.0800, 0.000000, 0),
(110, 170, 'manufactured', 5, 0.0800, 0.000000, 0),
(111, 170, 'manufactured', 6, 0.0900, 0.000000, 0),
(112, 170, 'raw', 9, 0.1200, 0.000000, 0),
(113, 170, 'manufactured', 4, 0.0900, 0.000000, 0),
(114, 171, 'manufactured', 7, 0.0800, 0.000000, 0),
(115, 171, 'manufactured', 8, 0.0800, 0.000000, 0),
(116, 171, 'manufactured', 5, 0.0800, 0.000000, 0),
(117, 171, 'manufactured', 6, 0.0900, 0.000000, 0),
(118, 171, 'raw', 9, 0.1200, 0.000000, 0),
(119, 171, 'manufactured', 4, 0.0900, 0.000000, 0),
(120, 172, 'manufactured', 7, 0.0800, 0.000000, 0),
(121, 172, 'manufactured', 8, 0.0800, 0.000000, 0),
(122, 172, 'manufactured', 5, 0.0800, 0.000000, 0),
(123, 172, 'manufactured', 6, 0.0900, 0.000000, 0),
(124, 172, 'raw', 9, 0.1200, 0.000000, 0),
(125, 172, 'manufactured', 4, 0.0900, 0.000000, 0),
(126, 173, 'manufactured', 7, 0.0800, 0.000000, 0),
(127, 173, 'manufactured', 8, 0.0800, 0.000000, 0),
(128, 173, 'manufactured', 5, 0.0800, 0.000000, 0),
(129, 173, 'manufactured', 6, 0.0900, 0.000000, 0),
(130, 173, 'raw', 9, 0.1200, 0.000000, 0),
(131, 173, 'manufactured', 4, 0.0900, 0.000000, 0),
(132, 179, 'manufactured', 7, 0.0800, 0.000000, 0),
(133, 179, 'manufactured', 8, 0.0800, 0.000000, 0),
(134, 179, 'manufactured', 5, 0.0800, 0.000000, 0),
(135, 179, 'manufactured', 6, 0.0900, 0.000000, 0),
(136, 179, 'raw', 9, 0.1200, 0.000000, 0),
(137, 179, 'manufactured', 4, 0.0900, 0.000000, 0),
(138, 180, 'manufactured', 7, 0.0800, 0.000000, 0),
(139, 180, 'manufactured', 5, 0.0800, 0.000000, 0),
(140, 180, 'raw', 9, 0.1200, 0.000000, 0),
(141, 180, 'manufactured', 4, 0.0900, 0.000000, 0),
(142, 181, 'manufactured', 7, 0.0800, 0.000000, 0),
(143, 181, 'manufactured', 8, 0.0800, 0.000000, 0),
(144, 181, 'manufactured', 5, 0.0800, 0.000000, 0),
(145, 181, 'manufactured', 6, 0.0900, 0.000000, 0),
(146, 181, 'raw', 9, 0.1200, 0.000000, 0),
(147, 181, 'manufactured', 4, 0.0900, 0.000000, 0),
(148, 194, 'raw', 54, 80.0000, 0.000000, 0),
(149, 194, 'raw', 53, 100.0000, 0.000000, 0),
(151, 158, 'product', 93, 1.0000, 0.000000, 0),
(152, 160, 'product', 93, 1.0000, 0.000000, 0),
(153, 97, 'product', 100, 1.0000, 0.000000, 0),
(155, 97, 'product', 207, 1.0000, 0.000000, 0),
(156, 97, 'product', 98, 1.0000, 0.000000, 0),
(157, 97, 'product', 157, 1.0000, 0.000000, 0),
(158, 134, 'product', 208, 1.0000, 0.000000, 0),
(159, 134, 'product', 209, 1.0000, 0.000000, 0),
(160, 210, 'product', 208, 1.0000, 0.000000, 0),
(161, 134, 'product', 211, 1.0000, 0.000000, 0),
(162, 134, 'product', 212, 1.0000, 0.000000, 0),
(163, 134, 'product', 213, 1.0000, 0.000000, 0),
(164, 134, 'product', 214, 1.0000, 0.000000, 0),
(165, 134, 'product', 215, 1.0000, 0.000000, 0);

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
  `payment_status` enum('paid','pending','partial') DEFAULT 'pending',
  `paid_amount` decimal(16,2) DEFAULT 0.00,
  `status` enum('pending','received','canceled') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exchange_rate` decimal(10,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `supplier_id`, `order_date`, `expected_delivery_date`, `total_amount`, `payment_status`, `paid_amount`, `status`, `created_at`, `updated_at`, `exchange_rate`) VALUES
(10, 1, '2026-02-13', '2026-02-16', 36.00, 'paid', 0.00, 'received', '2026-02-13 21:47:11', '2026-02-13 21:47:52', 500.00);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `item_type`, `item_id`, `product_id`, `quantity`, `unit_price`, `created_at`, `updated_at`) VALUES
(20, 10, 'product', 214, 214, 12, 1.000000, '2026-02-13 21:47:11', '2026-02-13 21:47:11'),
(21, 10, 'product', 208, 208, 24, 1.000000, '2026-02-13 21:47:11', '2026-02-13 21:47:11');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `purchase_receipts`
--

INSERT INTO `purchase_receipts` (`id`, `purchase_order_id`, `receipt_date`, `created_at`, `updated_at`) VALUES
(14, 10, '2026-02-13', '2026-02-13 21:47:31', '2026-02-13 21:47:31');

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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `short_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `raw_materials`
--

INSERT INTO `raw_materials` (`id`, `name`, `unit`, `stock_quantity`, `cost_per_unit`, `min_stock`, `is_cooking_supply`, `category`, `updated_at`, `short_code`) VALUES
(1, 'azucar', 'kg', 8.527200, 1.500000, 5.000000, 0, 'ingredient', '2026-02-13 21:49:22', NULL),
(2, 'harina de trigo', 'kg', 103.384400, 1.200000, 25.000000, 0, 'ingredient', '2026-02-13 21:49:22', NULL),
(3, 'lomito', 'kg', 10.859000, 16.000000, 2.500000, 0, 'ingredient', '2026-02-06 19:40:48', NULL),
(4, 'salchicha', 'und', 60.000000, 0.370000, 50.000000, 0, 'ingredient', '2026-02-08 00:44:49', NULL),
(5, 'carne mechada', 'kg', 8.000000, 16.000000, 2.500000, 0, 'ingredient', '2026-02-06 19:48:10', NULL),
(6, 'pollo', 'kg', 9.856000, 8.000000, 3.000000, 0, 'ingredient', '2026-02-06 19:48:10', NULL),
(7, 'tosineta', 'kg', 15.575000, 20.000000, 2.000000, 0, 'ingredient', '2026-02-13 18:57:18', 'T'),
(8, 'jamon ahumado', 'kg', 8.280000, 10.000000, 0.500000, 0, 'ingredient', '2026-02-12 22:29:32', NULL),
(9, 'chuleta ahumada', 'kg', 2.000000, 10.000000, 1.000000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(11, 'facilitas', 'und', 32.000000, 0.300000, 24.000000, 0, 'ingredient', '2026-02-09 19:35:20', NULL),
(12, 'queso amarillo', 'kg', 1.000000, 11.000000, 0.500000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(13, 'papitas rayadas', 'kg', 17.290000, 4.000000, 10.000000, 0, 'ingredient', '2026-02-13 18:59:52', NULL),
(14, 'papas fritas', 'kg', 18.250000, 4.000000, 10.000000, 0, 'ingredient', '2026-02-12 22:37:38', NULL),
(15, 'huevo', 'und', 339.253600, 0.240000, 60.000000, 0, 'ingredient', '2026-02-08 00:44:49', NULL),
(16, 'salsa de tomate', 'kg', 11.305000, 3.750000, 8.000000, 0, 'ingredient', '2026-02-13 19:02:18', NULL),
(17, 'salsa BBQ', 'kg', 3.688500, 3.000000, 3.500000, 0, 'ingredient', '2026-02-06 19:40:48', NULL),
(18, 'salsa mostaza', 'kg', 4.360000, 5.770000, 3.000000, 0, 'ingredient', '2026-02-13 19:02:18', NULL),
(19, 'salsa mayonesa', 'kg', 10.445000, 7.400000, 7.200000, 0, 'ingredient', '2026-02-13 19:02:18', NULL),
(20, 'aceite vegetal', 'lt', 29.159700, 3.000000, 17.000000, 1, 'ingredient', '2026-02-06 19:40:36', NULL),
(21, 'platano amarillo (patacon)', 'und', 20.000000, 0.500000, 10.000000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(22, 'platano verde (patacon)', 'und', 20.000000, 0.500000, 10.000000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(23, 'platano amarillo (pizza)', 'und', 24.000000, 0.340000, 12.000000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(24, 'pan de perro', 'und', 39.000000, 0.200000, 40.000000, 0, 'ingredient', '2026-02-08 00:44:49', NULL),
(25, 'pan mini', 'und', 70.000000, 0.200000, 48.000000, 0, 'ingredient', '2026-02-13 18:59:52', NULL),
(26, 'pan de americana', 'und', 52.000000, 0.360000, 30.000000, 0, 'ingredient', '2026-02-09 19:35:20', NULL),
(27, 'vinagre', 'lt', 4.000000, 1.500000, 2.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(28, 'peperoni', 'kg', 1.650000, 11.000000, 1.000000, 0, 'ingredient', '2026-02-09 19:34:44', 'P'),
(29, 'jamon de pierna', 'kg', 7.350000, 7.000000, 5.000000, 0, 'ingredient', '2026-02-13 18:59:52', 'J'),
(30, 'maiz', 'kg', 6.475000, 5.500000, 5.000000, 0, 'ingredient', '2026-02-13 18:59:52', 'M'),
(31, 'queso mozzarella', 'kg', 36.800000, 8.500000, 24.000000, 0, 'ingredient', '2026-02-13 18:59:52', NULL),
(32, 'queso pasteurizado', 'kg', 11.560000, 7.500000, 6.000000, 0, 'ingredient', '2026-01-25 04:27:56', NULL),
(33, 'mantequilla', 'kg', 1.840000, 4.700000, 2.000000, 0, 'ingredient', '2026-02-13 18:59:52', NULL),
(34, 'manteca', 'kg', 8.572000, 1.700000, 5.000000, 0, 'ingredient', '2026-02-13 21:49:22', NULL),
(35, 'levadura', 'kg', 0.332000, 9.000000, 0.200000, 0, 'ingredient', '2026-02-13 21:49:22', NULL),
(36, 'champiñones', 'kg', 5.600000, 5.900000, 3.000000, 0, 'ingredient', '2026-02-06 22:40:01', 'CH'),
(37, 'aceitunas negras', 'gr', 860.000000, 0.020000, 480.000000, 0, 'ingredient', '2026-02-06 22:40:01', 'AC'),
(38, 'caja de pizza normal', 'und', 50.000000, 0.600000, 25.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(39, 'caja de pizza personalizada', 'und', 88.000000, 0.750000, 50.000000, 1, 'packaging', '2026-02-13 18:57:18', NULL),
(40, 'sal', 'kg', 6.773000, 0.300000, 5.000000, 0, 'ingredient', '2026-02-13 21:49:22', NULL),
(41, 'vasos 77', 'und', 1000.000000, 0.020000, 500.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(44, 'guantes caja', 'und', 100.000000, 0.090000, 50.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(45, 'axion 850gr', 'gr', 3400.000000, 0.010000, 1700.000000, 1, 'ingredient', '2026-01-17 01:31:28', NULL),
(46, 'bolsa de 5kg', 'und', 990.000000, 0.012000, 500.000000, 1, 'packaging', '2026-02-13 19:02:18', NULL),
(48, 'papel de envolver', 'gr', 7960.000000, 0.010000, 4000.000000, 1, 'packaging', '2026-02-13 19:02:18', NULL),
(49, 'botellon de agua ', 'lt', 19.000000, 0.030000, 18.000000, 1, 'ingredient', '2026-02-13 21:49:22', NULL),
(50, 'toallin ', 'und', 4.000000, 0.700000, 2.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(52, 'lechuga', 'kg', 8.640000, 1.000000, 5.000000, 0, 'ingredient', '2026-02-13 19:02:18', NULL),
(53, 'pimenton', 'gr', 3150.000000, 0.000182, 500.000000, 0, 'ingredient', '2026-02-13 18:59:05', 'PIME'),
(54, 'cebolla redonda', 'gr', 2186.000000, 0.001000, 500.000000, 0, 'ingredient', '2026-02-06 22:40:01', 'C'),
(55, 'pepinillo', 'gr', 1808.000000, 0.006300, 1000.000000, 0, 'ingredient', '2026-02-09 19:35:20', NULL),
(56, 'tomate', 'kg', 18.575000, 1.000000, 10.000000, 0, 'ingredient', '2026-02-12 22:29:32', NULL),
(57, 'esponja doble uso', 'und', 12.000000, 0.270000, 6.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(58, 'esponja de alambre', 'und', 12.000000, 0.270000, 6.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(59, 'escoba', 'und', 6.000000, 3.000000, 3.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(60, 'lampazo', 'und', 6.000000, 3.000000, 3.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(61, 'jabon liquido', 'lt', 10.000000, 0.400000, 5.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(62, 'cloro', 'lt', 10.000000, 0.400000, 5.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(63, 'desengrasante ', 'lt', 10.000000, 0.700000, 5.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(64, 'desinfectante', 'lt', 10.000000, 0.400000, 5.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(65, 'pimienta', 'gr', 492.440000, 0.028000, 250.000000, 0, 'ingredient', '2026-02-06 19:42:59', NULL),
(66, 'oregano', 'gr', 926.351200, 0.007000, 500.000000, 0, 'ingredient', '2026-02-06 19:42:59', NULL),
(67, 'aliño ', 'gr', 813.930500, 0.005000, 500.000000, 0, 'ingredient', '2026-02-06 19:40:21', NULL),
(68, 'adobo', 'gr', 2100.541900, 0.010000, 500.000000, 0, 'ingredient', '2026-02-06 19:42:12', NULL),
(69, 'curry ', 'gr', 813.930500, 0.008000, 500.000000, 0, 'ingredient', '2026-02-06 19:40:21', NULL),
(70, 'comino', 'gr', 636.735900, 0.007000, 500.000000, 0, 'ingredient', '2026-02-06 19:42:12', NULL),
(71, 'cebolla en polvo', 'gr', 200.000000, 0.020000, 100.000000, 0, 'ingredient', '2026-01-17 01:31:28', NULL),
(72, 'paprika dulce', 'gr', 900.000000, 0.003636, 100.000000, 0, 'ingredient', '2026-02-06 19:42:35', NULL),
(73, 'albaca ', 'gr', 1000.000000, 0.007000, 500.000000, 0, 'ingredient', '2026-01-17 01:31:28', NULL),
(74, 'ajo', 'kg', 8.319900, 3.000000, 2.500000, 0, 'ingredient', '2026-02-06 19:48:10', NULL),
(75, 'cucharillas', 'und', 100.000000, 0.022000, 50.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(76, 'cuchillos', 'und', 100.000000, 0.019000, 50.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(77, 'tenedor', 'und', 100.000000, 0.025000, 50.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(78, 'vasos de 1 oz con tapa', 'und', 392.000000, 0.034000, 200.000000, 1, 'packaging', '2026-02-12 22:37:38', NULL),
(79, 'envase de aluminio 788', 'und', 24.000000, 0.200000, 12.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(80, 'servilleta z 160h', 'und', 1600.000000, 0.006250, 800.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(81, 'bolsa de 30kg', 'und', 200.000000, 0.100000, 100.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(82, 'cinta plastica', 'und', 4.000000, 0.400000, 2.000000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(83, 'carne molida', 'kg', 6.124000, 12.000000, 5.000000, 0, 'ingredient', '2026-02-06 19:40:07', NULL),
(84, 'pan rallado', 'kg', 0.502700, 2.000000, 0.500000, 0, 'ingredient', '2026-02-06 19:39:55', NULL),
(85, 'aji', 'gr', 1950.000000, 0.001000, 100.000000, 0, 'ingredient', '2026-02-06 19:48:10', NULL),
(86, 'ajo porro', 'gr', 700.000000, 0.001000, 100.000000, 0, 'ingredient', '2026-02-06 19:40:21', NULL),
(87, 'cebolla larga', 'gr', 460.000000, 0.001000, 100.000000, 0, 'ingredient', '2026-02-06 19:43:22', NULL),
(88, 'vini tinto', 'lt', 2.000000, 6.000000, 1.000000, 0, 'ingredient', '2026-02-06 19:42:12', NULL),
(89, 'Pernil', 'kg', 5.200000, 16.000000, 3.000000, 0, 'ingredient', '2026-02-06 19:42:12', NULL),
(91, 'colorante amarillo', 'gr', 398.128400, 0.020000, 250.000000, 0, 'ingredient', '2026-02-06 19:42:24', NULL),
(92, 'harina de maiz', 'kg', 20.750000, 1.000000, 5.000000, 0, 'ingredient', '2026-02-06 19:43:10', NULL),
(93, 'cilantro', 'gr', 1960.000000, 0.001000, 100.000000, 0, 'ingredient', '2026-02-06 19:48:10', NULL),
(94, 'ajo en polvo', 'gr', 2200.000000, 0.001000, 250.000000, 0, 'ingredient', '2026-02-06 19:48:10', NULL),
(95, 'salami', 'kg', 4.100000, 30.000000, 0.100000, 0, 'ingredient', '2026-02-06 22:40:01', 'S'),
(96, 'humo liquido', 'ml', 400.000000, 0.020000, 200.000000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(97, 'hielo cubos ', 'und', 1.000000, 2.000000, 0.500000, 1, 'ingredient', '2026-01-17 01:31:29', NULL),
(98, 'queso parmesano', 'gr', 500.000000, 0.020000, 250.000000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(99, 'conflei', 'gr', 3044.132800, 0.010000, 4.000000, 0, 'ingredient', '2026-02-06 19:41:59', NULL),
(100, 'nugguet', 'kg', 1.000000, 10.000000, 0.500000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(101, 'mortadela', 'gr', 400.000000, 0.003000, 200.000000, 0, 'ingredient', '2026-01-17 01:31:29', NULL),
(102, 'papel termico', 'und', 50.000000, 0.200000, 25.000000, 0, 'packaging', '2026-01-17 02:52:11', NULL),
(103, 'calcomania', 'und', 200.000000, 0.022000, 100.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(104, 'envase ct1', 'und', 100.000000, 0.070000, 50.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(105, 'envase ct2', 'und', 100.000000, 0.110000, 50.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(106, 'envase ct3', 'und', 100.000000, 0.160000, 50.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(107, 'bolsa de papel', 'und', 40.000000, 0.150000, 20.000000, 1, 'packaging', '2026-01-17 02:52:11', NULL),
(108, 'queso cebu', 'und', 69.500000, 0.670000, 60.000000, 0, 'ingredient', '2026-02-13 19:02:18', NULL),
(109, 'pan whopper', 'und', 112.000000, 0.461026, 30.000000, 0, 'ingredient', '2026-02-12 22:29:32', NULL),
(110, 'queso de año', 'gr', 236.000000, 0.010000, 200.000000, 0, 'ingredient', '2026-02-08 00:44:49', NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `transactions`
--

INSERT INTO `transactions` (`id`, `cash_session_id`, `type`, `amount`, `currency`, `exchange_rate`, `amount_usd_ref`, `payment_method_id`, `reference_type`, `reference_id`, `description`, `created_by`, `created_at`) VALUES
(1, 1, 'income', 10.00, 'USD', 490.00, 10.00, 1, 'order', 1, 'Cobro Venta #1', 4, '2026-01-17 03:20:59'),
(2, 1, 'income', 4.00, 'USD', 490.00, 4.00, 3, 'order', 1, 'Cobro Venta #1', 4, '2026-01-17 03:20:59'),
(3, 1, 'income', 4.00, 'USD', 490.00, 4.00, 1, 'order', 2, 'Cobro Venta #2', 4, '2026-01-17 22:06:06'),
(5, 1, 'income', 20.00, 'USD', 490.00, 20.00, 1, 'order', 4, 'Cobro Venta #4', 4, '2026-01-19 03:26:38'),
(6, 1, 'expense', 2.00, 'USD', 490.00, 2.00, 1, 'order', 4, 'Vuelto Venta #4', 4, '2026-01-19 03:26:38'),
(7, 2, 'income', 23.00, 'USD', 490.00, 23.00, 1, 'order', 5, 'Cobro Venta #5', 4, '2026-01-19 07:01:16'),
(8, 2, 'income', 33.00, 'USD', 490.00, 33.00, 1, 'order', 7, 'Cobro Venta #7', 4, '2026-01-20 22:58:30'),
(9, 2, 'income', 245.00, 'VES', 490.00, 0.50, 5, 'order', 7, 'Cobro Venta #7', 4, '2026-01-20 22:58:30'),
(10, 2, 'income', 21.00, 'USD', 490.00, 21.00, 1, 'order', 8, 'Cobro Venta #8', 4, '2026-01-21 00:29:04'),
(11, 2, 'income', 10045.00, 'VES', 490.00, 20.50, 5, 'order', 9, 'Cobro Venta #9', 4, '2026-01-21 00:39:46'),
(12, 2, 'income', 13.00, 'USD', 490.00, 13.00, 1, 'order', 10, 'Cobro Venta #10', 4, '2026-01-21 03:09:20'),
(13, 3, 'income', 23.00, 'USD', 490.00, 23.00, 1, 'order', 11, 'Cobro Venta #11', 4, '2026-01-21 03:44:23'),
(14, 3, 'income', 5.00, 'USD', 490.00, 5.00, 1, 'order', 12, 'Cobro Venta #12', 4, '2026-01-21 04:26:10'),
(15, 3, 'income', 40.00, 'USD', 490.00, 40.00, 1, 'order', 13, 'Cobro Venta #13', 4, '2026-01-21 04:35:41'),
(16, 3, 'expense', 245.00, 'VES', 490.00, 0.50, 2, 'order', 13, 'Vuelto Venta #13', 4, '2026-01-21 04:35:41'),
(17, 3, 'income', 11.00, 'USD', 490.00, 11.00, 1, 'order', 14, 'Cobro Venta #14', 4, '2026-01-21 05:54:07'),
(18, 0, 'expense', 15.00, 'USD', 490.00, 15.00, 1, 'purchase', 1, 'Pago de Compra #1 (Efectivo USD)', 4, '2026-01-21 06:39:59'),
(19, 0, 'expense', 15.00, 'USD', 490.00, 15.00, 1, 'purchase', 2, 'Pago de Compra #2 (Efectivo USD)', 4, '2026-01-21 06:40:17'),
(20, 0, 'expense', 15.00, 'USD', 490.00, 15.00, 1, 'purchase', 3, 'Pago de Compra #3 (Efectivo USD)', 4, '2026-01-21 06:40:24'),
(21, 0, 'expense', 60.00, 'USD', 490.00, 60.00, 1, 'purchase', 4, 'Pago de Compra #4 (Efectivo USD)', 4, '2026-01-21 07:07:40'),
(22, 0, 'expense', 18.00, 'USD', 490.00, 18.00, 1, 'purchase', 5, 'Pago de Compra #5 (Efectivo USD)', 4, '2026-01-21 08:09:51'),
(23, 0, 'expense', 18.00, 'USD', 490.00, 18.00, 1, 'purchase', 6, 'Pago de Compra #6 (Efectivo USD)', 4, '2026-01-21 08:12:57'),
(24, 3, 'expense', 30.00, 'USD', 490.00, 30.00, 1, 'adjustment', 1, 'Pago Nómina: roberto (Monthly)', 4, '2026-01-21 18:22:54'),
(25, 3, 'expense', 10.00, 'USD', 490.00, 10.00, 1, 'adjustment', 2, 'Pago Nómina: Test Client (Monthly)', 4, '2026-01-21 18:22:57'),
(26, 0, 'expense', 11.70, 'USD', 490.00, 11.70, 1, 'purchase', 7, 'Pago de Compra #7 (Efectivo USD)', 4, '2026-01-21 19:04:15'),
(27, 4, 'income', 1244.60, 'VES', 490.00, 2.54, 5, 'order', 15, 'Cobro Venta #15', 4, '2026-01-21 20:14:12'),
(28, 4, 'income', 6634.60, 'VES', 490.00, 13.54, 5, 'order', 16, 'Cobro Venta #16', 4, '2026-01-21 20:18:48'),
(29, 4, 'income', 5.00, 'USD', 490.00, 5.00, 1, 'order', 17, 'Cobro Venta #17', 4, '2026-01-21 21:55:52'),
(30, 4, 'income', 23.00, 'USD', 490.00, 23.00, 1, 'order', 18, 'Cobro Venta #18', 4, '2026-01-21 22:06:20'),
(31, 4, 'income', 33.00, 'USD', 490.00, 33.00, 1, 'order', 21, 'Cobro Venta #21', 4, '2026-01-24 22:37:47'),
(32, 4, 'income', 264.60, 'VES', 490.00, 0.54, 5, 'order', 21, 'Cobro Venta #21', 4, '2026-01-24 22:37:47'),
(33, 4, 'income', 12514.60, 'VES', 490.00, 25.54, 5, 'order', 22, 'Cobro Venta #22', 4, '2026-01-25 04:27:56'),
(34, 4, 'income', 17.00, 'USD', 490.00, 17.00, 1, 'order', 23, 'Cobro Venta #23', 4, '2026-01-25 08:05:39'),
(35, 4, 'income', 230.00, 'USD', 490.00, 230.00, 1, 'order', 24, 'Cobro Venta #24', 4, '2026-01-25 08:17:00'),
(36, 4, 'income', 2.00, 'USD', 490.00, 2.00, 1, 'order', 25, 'Cobro Venta #25', 4, '2026-02-02 11:05:17'),
(37, 4, 'income', 5.00, 'USD', 490.00, 5.00, 1, 'order', 26, 'Cobro Venta #26', 4, '2026-02-02 12:31:52'),
(38, 4, 'income', 735.00, 'VES', 490.00, 1.50, 4, 'order', 26, 'Cobro Venta #26', 4, '2026-02-02 12:31:52'),
(39, 4, 'income', 6.50, 'USD', 490.00, 6.50, 1, 'order', 27, 'Cobro Venta #27', 4, '2026-02-02 13:04:42'),
(40, 4, 'income', 6.50, 'USD', 490.00, 6.50, 1, 'order', 28, 'Cobro Venta #28', 4, '2026-02-02 13:21:27'),
(41, 4, 'income', 24.00, 'USD', 490.00, 24.00, 1, 'order', 29, 'Cobro Venta #29', 4, '2026-02-06 22:42:22'),
(42, 4, 'income', 17.00, 'USD', 490.00, 17.00, 1, 'order', 30, 'Cobro Venta #30', 4, '2026-02-08 00:44:48'),
(43, 4, 'income', 28.00, 'USD', 490.00, 28.00, 1, 'order', 31, 'Cobro Venta #31', 4, '2026-02-09 19:17:21'),
(44, 4, 'income', 11.00, 'USD', 490.00, 11.00, 1, 'order', 32, 'Cobro Venta #32', 4, '2026-02-09 19:34:44'),
(45, 4, 'income', 8.50, 'USD', 490.00, 8.50, 1, 'order', 33, 'Cobro Venta #33', 4, '2026-02-09 19:35:20'),
(47, 5, 'income', 11.00, 'USD', 490.00, 11.00, 1, 'order', 34, 'Cobro Venta #34', 33, '2026-02-09 20:18:38'),
(48, 5, 'income', 0.01, 'USD', 490.00, 0.01, 3, 'order', 34, 'Cobro Venta #34', 33, '2026-02-09 20:18:38'),
(49, 6, 'income', 18.00, 'USD', 500.00, 18.00, 1, 'order', 35, 'Cobro Venta #35', 4, '2026-02-12 22:29:32'),
(50, 6, 'income', 16.00, 'USD', 500.00, 16.00, 1, 'order', 36, 'Cobro Venta #36', 4, '2026-02-12 22:37:38'),
(56, 6, 'income', 2.00, 'USD', 500.00, 2.00, 1, 'order', 42, 'Cobro Venta #42', 4, '2026-02-13 03:04:23'),
(57, 6, 'income', 4.00, 'USD', 500.00, 4.00, 1, 'order', 43, 'Cobro Venta #43', 4, '2026-02-13 03:15:32'),
(58, 6, 'income', 16.00, 'USD', 500.00, 16.00, 1, 'order', 44, 'Cobro Venta #44', 4, '2026-02-13 18:57:18'),
(59, 6, 'income', 7500.00, 'VES', 500.00, 15.00, 4, 'order', 45, 'Cobro Venta #45', 4, '2026-02-13 18:59:05'),
(60, 6, 'income', 6000.00, 'VES', 500.00, 12.00, 2, 'order', 46, 'Cobro Venta #46', 4, '2026-02-13 18:59:51'),
(61, 6, 'income', 2000.00, 'VES', 500.00, 4.00, 5, 'order', 47, 'Cobro Venta #47', 4, '2026-02-13 19:02:18'),
(62, 0, 'expense', 36.00, 'USD', 500.00, 36.00, 1, 'purchase', 10, 'Pago de Compra #10 (Efectivo USD)', 4, '2026-02-13 21:47:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tv_playlist_items`
--

CREATE TABLE `tv_playlist_items` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `custom_title` varchar(255) DEFAULT NULL,
  `custom_description` text DEFAULT NULL,
  `custom_image_url` varchar(255) DEFAULT NULL,
  `custom_price` varchar(50) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 10,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `show_suggestion` tinyint(1) DEFAULT 0,
  `suggestion_text` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tv_playlist_items`
--

INSERT INTO `tv_playlist_items` (`id`, `product_id`, `custom_title`, `custom_description`, `custom_image_url`, `custom_price`, `duration_seconds`, `sort_order`, `is_active`, `show_suggestion`, `suggestion_text`) VALUES
(1, 138, '', '', NULL, '', 10, 0, 1, 1, ''),
(2, 139, '', '', NULL, '', 10, 0, 1, 1, ''),
(3, 140, '', '', NULL, '', 10, 0, 1, 1, ''),
(4, 141, '', '', NULL, '', 10, 0, 1, 1, ''),
(5, 142, '', '', NULL, '', 10, 0, 1, 1, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tv_settings`
--

CREATE TABLE `tv_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `document_id`, `address`, `role`, `profile_pic`, `balance`, `salary_amount`, `salary_frequency`, `job_role`, `reset_token`, `token_expiry`, `created_at`, `updated_at`) VALUES
(4, 'roberto', 'robertopv100@gmail.com', '$2y$10$7GPIEd7LC.leGjt7EgKNvOQ/J7Ht.J2gjOC8njG51PAefmEGWa4bq', '04246746570', 'v-19451788', 'asdasdasd12', 'admin', 'default.jpg', 0.00, 30.000000, 'monthly', 'manager', NULL, NULL, '2025-02-23 23:37:13', '2025-12-10 12:29:45'),
(31, 'Test Client', 'cliente_123456@local.com', '$2y$12$6v3nSh4iXCUqc8iS9Z/o4.dm.LgLRbMRne85Rn3AwXZPAwmCGVpaK', '555555', '123456', '', 'user', 'default.jpg', 0.00, 10.000000, 'monthly', 'cashier', NULL, NULL, '2026-01-19 06:01:55', '2026-01-19 06:53:37'),
(33, 'cajero', 'cajero@gmail.com', '$2y$10$hBQ8LMcjb4eugZ2z3WS90OaiPWsyJeyW2vVt0RCckg1qORbYIc/C.', '', '12345173', '', 'user', 'default.jpg', 0.00, 0.000000, 'weekly', 'cashier', NULL, NULL, '2026-02-09 19:46:03', '2026-02-09 20:08:52'),
(34, 'pizza', 'pizza@gmail.com', '$2y$10$BPgpWsxu7a7dnluw0cRAWe9Vlt8.FWM2b06O0r1SDQdjCGtICJ.Jq', '', '12837918273', '', 'user', 'default.jpg', 0.00, 0.000000, 'weekly', 'kitchen', NULL, NULL, '2026-02-09 19:46:35', '2026-02-13 18:44:36'),
(35, 'cocina', 'cocina@gmail.com', '$2y$10$nyGoatApX1HW55nfegtUcuV3xi2lL4oWmN98RGr9g86QGR0bdAtfu', '', '1827391827', '', 'user', 'default.jpg', 0.00, 0.000000, 'monthly', 'kitchen', NULL, NULL, '2026-02-09 19:47:03', '2026-02-13 18:45:11');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `vault_movements`
--

INSERT INTO `vault_movements` (`id`, `type`, `origin`, `amount`, `currency`, `description`, `reference_id`, `created_by`, `created_at`) VALUES
(1, 'deposit', 'session_close', 32.00, 'USD', 'Cierre de Caja #1', 1, 4, '2026-01-19 05:39:50'),
(2, 'deposit', 'session_close', 90.00, 'USD', 'Cierre de Caja #2', 2, 4, '2026-01-21 03:22:44'),
(3, 'deposit', 'session_close', 79.00, 'USD', 'Cierre de Caja #3', 3, 4, '2026-01-21 06:29:03'),
(4, 'withdrawal', 'supplier_payment', 15.00, 'USD', 'Pago a Proveedor - Compra #1', 1, 4, '2026-01-21 06:39:59'),
(5, 'withdrawal', 'supplier_payment', 15.00, 'USD', 'Pago a Proveedor - Compra #2', 2, 4, '2026-01-21 06:40:17'),
(6, 'withdrawal', 'supplier_payment', 15.00, 'USD', 'Pago a Proveedor - Compra #3', 3, 4, '2026-01-21 06:40:24'),
(7, 'withdrawal', 'supplier_payment', 60.00, 'USD', 'Pago a Proveedor - Compra #4', 4, 4, '2026-01-21 07:07:40'),
(8, 'withdrawal', 'supplier_payment', 18.00, 'USD', 'Pago a Proveedor - Compra #5', 5, 4, '2026-01-21 08:09:51'),
(9, 'deposit', 'manual_deposit', 18.00, 'USD', 'pago de mercancía por devolución', NULL, 4, '2026-01-21 08:11:32'),
(10, 'withdrawal', 'supplier_payment', 18.00, 'USD', 'Pago a Proveedor - Compra #6', 6, 4, '2026-01-21 08:12:57'),
(11, 'withdrawal', 'owner_withdrawal', 30.00, 'USD', 'Pago Nómina: roberto (ID: 1)', 1, 4, '2026-01-21 18:22:54'),
(12, 'withdrawal', 'owner_withdrawal', 10.00, 'USD', 'Pago Nómina: Test Client (ID: 2)', 2, 4, '2026-01-21 18:22:57'),
(13, 'withdrawal', 'supplier_payment', 11.70, 'USD', 'Pago a Proveedor - Compra #7', 7, 4, '2026-01-21 19:04:15'),
(15, 'deposit', 'session_close', 416.50, 'USD', 'Cierre de Caja #4', 4, 4, '2026-02-09 20:10:40'),
(16, 'withdrawal', 'supplier_payment', 36.00, 'USD', 'Pago a Proveedor - Compra #10', 10, 4, '2026-02-13 21:47:52');

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
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_document_id` (`document_id`);

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
-- Indices de la tabla `kds_item_status`
--
ALTER TABLE `kds_item_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item_station` (`order_item_id`,`sub_item_index`,`station`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Indices de la tabla `product_companions`
--
ALTER TABLE `product_companions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `companion_id` (`companion_id`);

--
-- Indices de la tabla `product_companion_components`
--
ALTER TABLE `product_companion_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_companion_id` (`product_companion_id`);

--
-- Indices de la tabla `product_components`
--
ALTER TABLE `product_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prod_component` (`product_id`),
  ADD KEY `idx_product_components_product` (`product_id`,`component_type`);

--
-- Indices de la tabla `product_component_overrides`
--
ALTER TABLE `product_component_overrides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `component_row_id` (`component_row_id`);

--
-- Indices de la tabla `product_component_side_overrides`
--
ALTER TABLE `product_component_side_overrides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `component_row_id` (`component_row_id`);

--
-- Indices de la tabla `product_default_modifiers`
--
ALTER TABLE `product_default_modifiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `product_packaging`
--
ALTER TABLE `product_packaging`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `product_valid_extras`
--
ALTER TABLE `product_valid_extras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `product_valid_sides`
--
ALTER TABLE `product_valid_sides`
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
-- Indices de la tabla `tv_playlist_items`
--
ALTER TABLE `tv_playlist_items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tv_settings`
--
ALTER TABLE `tv_settings`
  ADD PRIMARY KEY (`setting_key`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT de la tabla `cart_item_modifiers`
--
ALTER TABLE `cart_item_modifiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1823;

--
-- AUTO_INCREMENT de la tabla `cash_sessions`
--
ALTER TABLE `cash_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `company_vault`
--
ALTER TABLE `company_vault`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `global_config`
--
ALTER TABLE `global_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT de la tabla `kds_item_status`
--
ALTER TABLE `kds_item_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `manufactured_products`
--
ALTER TABLE `manufactured_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de la tabla `order_item_modifiers`
--
ALTER TABLE `order_item_modifiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=520;

--
-- AUTO_INCREMENT de la tabla `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `payroll_payments`
--
ALTER TABLE `payroll_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `production_orders`
--
ALTER TABLE `production_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `production_recipes`
--
ALTER TABLE `production_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT de la tabla `product_companions`
--
ALTER TABLE `product_companions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `product_companion_components`
--
ALTER TABLE `product_companion_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `product_components`
--
ALTER TABLE `product_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=879;

--
-- AUTO_INCREMENT de la tabla `product_component_overrides`
--
ALTER TABLE `product_component_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `product_component_side_overrides`
--
ALTER TABLE `product_component_side_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `product_default_modifiers`
--
ALTER TABLE `product_default_modifiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=227;

--
-- AUTO_INCREMENT de la tabla `product_packaging`
--
ALTER TABLE `product_packaging`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `product_valid_extras`
--
ALTER TABLE `product_valid_extras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=653;

--
-- AUTO_INCREMENT de la tabla `product_valid_sides`
--
ALTER TABLE `product_valid_sides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT de la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `purchase_receipts`
--
ALTER TABLE `purchase_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `tv_playlist_items`
--
ALTER TABLE `tv_playlist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `vault_movements`
--
ALTER TABLE `vault_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
-- Filtros para la tabla `kds_item_status`
--
ALTER TABLE `kds_item_status`
  ADD CONSTRAINT `kds_item_status_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE;

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
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `product_companion_components`
--
ALTER TABLE `product_companion_components`
  ADD CONSTRAINT `product_companion_components_ibfk_1` FOREIGN KEY (`product_companion_id`) REFERENCES `product_companions` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `product_component_overrides`
--
ALTER TABLE `product_component_overrides`
  ADD CONSTRAINT `product_component_overrides_ibfk_1` FOREIGN KEY (`component_row_id`) REFERENCES `product_components` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `product_component_side_overrides`
--
ALTER TABLE `product_component_side_overrides`
  ADD CONSTRAINT `product_component_side_overrides_ibfk_1` FOREIGN KEY (`component_row_id`) REFERENCES `product_components` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `product_default_modifiers`
--
ALTER TABLE `product_default_modifiers`
  ADD CONSTRAINT `product_default_modifiers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `product_valid_extras`
--
ALTER TABLE `product_valid_extras`
  ADD CONSTRAINT `product_valid_extras_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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
