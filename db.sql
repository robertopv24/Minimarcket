-- phpMyAdmin SQL Dump
-- version 5.2.3-1.fc42
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 29-01-2026 a las 00:26:34
-- Versión del servidor: 10.11.11-MariaDB
-- Versión de PHP: 8.4.16

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
(1, 'azucar', 'kg', 8.339600, 1.500000, 5.000000, 0, 'ingredient', '2026-01-27 20:30:36'),
(2, 'harina de trigo', 'kg', 101.391900, 1.200000, 25.000000, 0, 'ingredient', '2026-01-27 20:39:49'),
(3, 'lomito', 'kg', 13.601300, 16.000000, 2.500000, 0, 'ingredient', '2026-01-27 08:12:27'),
(4, 'salchicha', 'und', 59.000000, 0.370000, 50.000000, 0, 'ingredient', '2026-01-27 20:19:15'),
(5, 'carne mechada', 'kg', 2.000000, 16.000000, 2.500000, 0, 'ingredient', '2026-01-27 08:11:02'),
(6, 'pollo', 'kg', 4.160000, 8.000000, 3.000000, 0, 'ingredient', '2026-01-27 08:12:14'),
(7, 'tosineta', 'kg', 1.875000, 20.000000, 2.000000, 0, 'ingredient', '2026-01-27 20:13:51'),
(8, 'jamon ahumado', 'kg', 0.280000, 10.000000, 0.500000, 0, 'ingredient', '2026-01-27 20:23:36'),
(9, 'chuleta ahumada', 'kg', 1.640000, 10.000000, 1.000000, 0, 'ingredient', '2026-01-27 20:19:15'),
(11, 'facilitas', 'und', 48.000000, 0.300000, 24.000000, 0, 'ingredient', '2026-01-17 01:31:29'),
(12, 'queso amarillo', 'kg', 0.960000, 11.000000, 0.500000, 0, 'ingredient', '2026-01-27 20:23:36'),
(13, 'papitas rayadas', 'kg', 17.370000, 4.000000, 10.000000, 0, 'ingredient', '2026-01-27 20:19:15'),
(14, 'papas fritas', 'kg', 19.500000, 4.000000, 10.000000, 0, 'ingredient', '2026-01-27 20:19:15'),
(15, 'huevo', 'und', 61.940200, 0.240000, 60.000000, 0, 'ingredient', '2026-01-27 20:23:36'),
(16, 'salsa de tomate', 'kg', 11.880000, 3.750000, 8.000000, 0, 'ingredient', '2026-01-27 20:23:36'),
(17, 'salsa BBQ', 'kg', 4.426500, 3.000000, 3.500000, 0, 'ingredient', '2026-01-27 08:12:27'),
(18, 'salsa mostaza', 'kg', 4.615000, 5.770000, 3.000000, 0, 'ingredient', '2026-01-27 20:23:36'),
(19, 'salsa mayonesa', 'kg', 7.620000, 7.400000, 7.200000, 0, 'ingredient', '2026-01-27 20:23:36'),
(20, 'aceite vegetal', 'lt', 30.353900, 3.000000, 17.000000, 1, 'ingredient', '2026-01-27 08:12:14'),
(21, 'platano amarillo (patacon)', 'und', 20.000000, 0.500000, 10.000000, 0, 'ingredient', '2026-01-17 01:31:29'),
(22, 'platano verde (patacon)', 'und', 20.000000, 0.500000, 10.000000, 0, 'ingredient', '2026-01-17 01:31:29'),
(23, 'platano amarillo (pizza)', 'und', 24.000000, 0.340000, 12.000000, 0, 'ingredient', '2026-01-17 01:31:29'),
(24, 'pan de perro', 'und', 39.000000, 0.200000, 40.000000, 0, 'ingredient', '2026-01-27 20:19:15'),
(25, 'pan mini', 'und', 74.000000, 0.200000, 48.000000, 0, 'ingredient', '2026-01-24 22:37:47'),
(26, 'pan de americana', 'und', 60.000000, 0.360000, 30.000000, 0, 'ingredient', '2026-01-17 01:31:29'),
(27, 'vinagre', 'lt', 4.000000, 1.500000, 2.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(28, 'peperoni', 'kg', 1.400000, 11.000000, 1.000000, 0, 'ingredient', '2026-01-27 20:39:49'),
(29, 'jamon de pierna', 'kg', 7.300000, 7.000000, 5.000000, 0, 'ingredient', '2026-01-27 20:39:49'),
(30, 'maiz', 'kg', 6.700000, 5.500000, 5.000000, 0, 'ingredient', '2026-01-27 20:39:49'),
(31, 'queso mozzarella', 'kg', 37.000000, 8.500000, 24.000000, 0, 'ingredient', '2026-01-27 20:39:49'),
(32, 'queso pasteurizado', 'kg', 11.560000, 7.500000, 6.000000, 0, 'ingredient', '2026-01-25 04:27:56'),
(33, 'mantequilla', 'kg', 1.840000, 4.700000, 2.000000, 0, 'ingredient', '2026-01-27 20:39:49'),
(34, 'manteca', 'kg', 8.362000, 1.700000, 5.000000, 0, 'ingredient', '2026-01-27 20:30:36'),
(35, 'levadura', 'kg', 0.322000, 9.000000, 0.200000, 0, 'ingredient', '2026-01-27 20:30:36'),
(36, 'champiñones', 'kg', 5.600000, 5.900000, 3.000000, 0, 'ingredient', '2026-01-20 22:58:30'),
(37, 'aceitunas negras', 'gr', 860.000000, 0.020000, 480.000000, 0, 'ingredient', '2026-01-20 22:58:30'),
(38, 'caja de pizza normal', 'und', 50.000000, 0.600000, 25.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(39, 'caja de pizza personalizada', 'und', 91.000000, 0.750000, 50.000000, 1, 'packaging', '2026-01-26 03:24:23'),
(40, 'sal', 'kg', 6.521500, 0.300000, 5.000000, 0, 'ingredient', '2026-01-27 20:30:36'),
(41, 'vasos 77', 'und', 1000.000000, 0.020000, 500.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(44, 'guantes caja', 'und', 100.000000, 0.090000, 50.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(45, 'axion 850gr', 'gr', 3400.000000, 0.010000, 1700.000000, 1, 'ingredient', '2026-01-17 01:31:28'),
(46, 'bolsa de 5kg', 'und', 991.000000, 0.012000, 500.000000, 1, 'packaging', '2026-01-21 21:55:52'),
(48, 'papel de envolver', 'gr', 7964.000000, 0.010000, 4000.000000, 1, 'packaging', '2026-01-21 21:55:52'),
(49, 'botellon de agua ', 'lt', 16.500000, 0.030000, 18.000000, 1, 'ingredient', '2026-01-27 20:30:36'),
(50, 'toallin ', 'und', 4.000000, 0.700000, 2.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(52, 'lechuga', 'kg', 8.770000, 0.001000, 5.000000, 0, 'ingredient', '2026-01-27 20:23:36'),
(53, 'pimenton', 'gr', 500.000000, 0.000182, 500.000000, 0, 'ingredient', '2026-01-27 08:14:25'),
(54, 'cebolla redonda', 'gr', 500.000000, 0.001000, 500.000000, 0, 'ingredient', '2026-01-27 08:15:58'),
(55, 'pepinillo', 'gr', 500.000000, 0.006300, 1000.000000, 0, 'ingredient', '2026-01-27 08:15:49'),
(56, 'tomate', 'kg', 18.800000, 1.000000, 10.000000, 0, 'ingredient', '2026-01-27 20:23:36'),
(57, 'esponja doble uso', 'und', 12.000000, 0.270000, 6.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(58, 'esponja de alambre', 'und', 12.000000, 0.270000, 6.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(59, 'escoba', 'und', 6.000000, 3.000000, 3.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(60, 'lampazo', 'und', 6.000000, 3.000000, 3.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(61, 'jabon liquido', 'lt', 10.000000, 0.400000, 5.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(62, 'cloro', 'lt', 10.000000, 0.400000, 5.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(63, 'desengrasante ', 'lt', 10.000000, 0.700000, 5.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(64, 'desinfectante', 'lt', 10.000000, 0.400000, 5.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(65, 'pimienta', 'gr', 500.000000, 0.028000, 250.000000, 0, 'ingredient', '2026-01-27 08:15:38'),
(66, 'oregano', 'gr', 500.000000, 0.007000, 500.000000, 0, 'ingredient', '2026-01-27 08:15:29'),
(67, 'aliño ', 'gr', 500.000000, 0.005000, 500.000000, 0, 'ingredient', '2026-01-27 08:15:21'),
(68, 'adobo', 'gr', 500.000000, 0.010000, 500.000000, 0, 'ingredient', '2026-01-27 08:15:13'),
(69, 'curry ', 'gr', 500.000000, 0.008000, 500.000000, 0, 'ingredient', '2026-01-27 08:15:06'),
(70, 'comino', 'gr', 500.000000, 0.007000, 500.000000, 0, 'ingredient', '2026-01-27 08:14:58'),
(71, 'cebolla en polvo', 'gr', 200.000000, 0.020000, 100.000000, 0, 'ingredient', '2026-01-17 01:31:28'),
(72, 'paprika dulce', 'gr', 500.000000, 0.003636, 100.000000, 0, 'ingredient', '2026-01-27 08:14:51'),
(74, 'ajo', 'kg', 3.031600, 3.000000, 2.500000, 0, 'ingredient', '2026-01-27 08:13:14'),
(75, 'cucharillas', 'und', 100.000000, 0.022000, 50.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(76, 'cuchillos', 'und', 100.000000, 0.019000, 50.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(77, 'tenedor', 'und', 100.000000, 0.025000, 50.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(78, 'vasos de 1 oz con tapa', 'und', 397.000000, 0.034000, 200.000000, 1, 'packaging', '2026-01-27 20:19:15'),
(79, 'envase de aluminio 788', 'und', 24.000000, 0.200000, 12.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(80, 'servilleta z 160h', 'und', 1600.000000, 0.006250, 800.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(81, 'bolsa de 30kg', 'und', 200.000000, 0.100000, 100.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(82, 'cinta plastica', 'und', 4.000000, 0.400000, 2.000000, 1, 'ingredient', '2026-01-17 01:31:29'),
(83, 'carne molida', 'kg', 12.158800, 12.000000, 5.000000, 0, 'ingredient', '2026-01-27 08:12:07'),
(84, 'pan rallado', 'kg', 2.002800, 2.000000, 0.500000, 0, 'ingredient', '2026-01-27 08:12:02'),
(85, 'aji', 'gr', 50.000000, 0.001000, 100.000000, 0, 'ingredient', '2026-01-27 08:11:02'),
(86, 'ajo porro', 'gr', 900.000000, 0.001000, 100.000000, 0, 'ingredient', '2026-01-27 08:11:02'),
(87, 'cebolla larga', 'gr', 780.000000, 0.001000, 100.000000, 0, 'ingredient', '2026-01-27 08:13:14'),
(88, 'vini tinto', 'lt', 3.500000, 6.000000, 1.000000, 0, 'ingredient', '2026-01-27 08:12:46'),
(89, 'Pernil', 'kg', 10.600000, 16.000000, 3.000000, 0, 'ingredient', '2026-01-27 08:12:46'),
(91, 'colorante amarillo', 'gr', 431.096300, 0.020000, 250.000000, 0, 'ingredient', '2026-01-27 08:12:40'),
(92, 'harina de maiz', 'kg', 22.750000, 1.000000, 5.000000, 0, 'ingredient', '2026-01-27 08:12:59'),
(93, 'cilantro', 'gr', 80.000000, 0.001000, 100.000000, 0, 'ingredient', '2026-01-27 08:13:14'),
(94, 'ajo en polvo', 'gr', 300.000000, 0.001000, 250.000000, 0, 'ingredient', '2026-01-27 08:11:38'),
(95, 'salami', 'kg', 4.000000, 30.000000, 0.100000, 0, 'ingredient', '2026-01-27 20:39:49'),
(96, 'humo liquido', 'ml', 400.000000, 0.020000, 200.000000, 0, 'ingredient', '2026-01-17 01:31:29'),
(97, 'hielo cubos ', 'und', 1.000000, 2.000000, 0.500000, 1, 'ingredient', '2026-01-17 01:31:29'),
(98, 'queso parmesano', 'gr', 500.000000, 0.020000, 250.000000, 0, 'ingredient', '2026-01-17 01:31:29'),
(99, 'conflei', 'gr', 3755.033200, 0.010000, 4.000000, 0, 'ingredient', '2026-01-27 08:12:40'),
(100, 'nugguet', 'kg', 1.000000, 10.000000, 0.500000, 0, 'ingredient', '2026-01-17 01:31:29'),
(101, 'mortadela', 'gr', 500.000000, 0.003000, 200.000000, 0, 'ingredient', '2026-01-27 08:14:40'),
(102, 'papel termico', 'und', 50.000000, 0.200000, 25.000000, 0, 'packaging', '2026-01-17 02:52:11'),
(103, 'calcomania', 'und', 200.000000, 0.022000, 100.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(104, 'envase ct1', 'und', 100.000000, 0.070000, 50.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(105, 'envase ct2', 'und', 100.000000, 0.110000, 50.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(106, 'envase ct3', 'und', 100.000000, 0.160000, 50.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(107, 'bolsa de papel', 'und', 40.000000, 0.150000, 20.000000, 1, 'packaging', '2026-01-17 02:52:11'),
(108, 'queso cebu', 'und', 70.500000, 0.670000, 60.000000, 0, 'ingredient', '2026-01-27 20:23:36'),
(109, 'pan whopper', 'und', 13.000000, 0.820000, 30.000000, 0, 'ingredient', '2026-01-27 20:19:15'),
(110, 'queso de año', 'gr', 230.000000, 0.010000, 200.000000, 0, 'ingredient', '2026-01-27 20:23:36');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
