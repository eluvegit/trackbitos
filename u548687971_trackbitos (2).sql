-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 20-11-2026 a las 00:55:47
-- Versión del servidor: 11.8.9-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u548687971_trackbitos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `model_releases`
--

CREATE TABLE `model_releases` (
  `id` int(11) NOT NULL,
  `sesion_id` int(11) NOT NULL,
  `nombre_modelo` varchar(150) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `fecha` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `moodboard_items`
--

CREATE TABLE `moodboard_items` (
  `id` int(11) NOT NULL,
  `sesion_id` int(11) NOT NULL,
  `situacion_id` int(11) DEFAULT NULL,
  `origen` enum('archivo','enlace','texto') NOT NULL,
  `ruta_archivo` varchar(255) DEFAULT NULL,
  `url_externa` varchar(500) DEFAULT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `buscar_ref` tinyint(1) DEFAULT 0,
  `estado` varchar(12) DEFAULT 'pendiente',
  `escena_id` int(11) DEFAULT NULL,
  `variante_id` int(11) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `creado_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `moodboard_items`
--

INSERT INTO `moodboard_items` (`id`, `sesion_id`, `situacion_id`, `origen`, `ruta_archivo`, `url_externa`, `nota`, `buscar_ref`, `estado`, `escena_id`, `variante_id`, `orden`, `creado_at`) VALUES
(1, 6, 12, 'archivo', 'uploads/sesiones/6/1793631232_6b66a0a6a4cdb2b3e23d.png', NULL, NULL, 0, 'descartada', NULL, NULL, 0, '2026-11-02 15:53:52'),
(2, 6, 12, 'archivo', 'uploads/sesiones/6/1794703190_ab2e173a5813e009353e.png', NULL, NULL, 0, 'toma', 47, NULL, 1, '2026-11-15 01:39:50'),
(3, 6, 4, 'archivo', 'uploads/sesiones/6/1794926737_0bf003fcc677cfe17c07.png', NULL, NULL, 0, 'toma', 48, NULL, 2, '2026-11-17 15:45:37'),
(4, 6, 10, 'archivo', 'uploads/sesiones/6/1794927251_81deae60d7d1988c4c92.png', NULL, NULL, 0, 'toma', 42, NULL, 3, '2026-11-17 15:54:11'),
(5, 6, 9, 'archivo', 'uploads/sesiones/6/1794927254_3500e01e14bf6c4262c2.png', NULL, NULL, 0, 'variante', 64, 33, 4, '2026-11-17 15:54:14'),
(6, 6, 4, 'archivo', 'uploads/sesiones/6/1794927257_7d5f21dc2eeb2c716490.png', NULL, NULL, 0, 'toma', 49, NULL, 5, '2026-11-17 15:54:17'),
(7, 6, 3, 'archivo', 'uploads/sesiones/6/1794927263_77d09f8ed4d24c336397.png', NULL, NULL, 0, 'variante', 41, 9, 6, '2026-11-17 15:54:23'),
(8, 6, 6, 'archivo', 'uploads/sesiones/6/1794927266_3f1e72cb16ac5df269b5.png', NULL, NULL, 0, 'toma', 54, NULL, 7, '2026-11-17 15:54:26'),
(9, 6, 10, 'archivo', 'uploads/sesiones/6/1794927270_ed9f264d4ca602aa7f0e.png', NULL, NULL, 0, 'toma', 61, NULL, 8, '2026-11-17 15:54:30'),
(10, 6, 10, 'archivo', 'uploads/sesiones/6/1794927274_ee8ea782cf59ff436e54.png', NULL, NULL, 0, 'variante', 42, 31, 9, '2026-11-17 15:54:34'),
(11, 6, 8, 'archivo', 'uploads/sesiones/6/1794927278_055d0d5de9f5b4e0dce3.png', NULL, NULL, 0, 'toma', 52, NULL, 10, '2026-11-17 15:54:38'),
(12, 6, 8, 'archivo', 'uploads/sesiones/6/1794927285_3a8da30b946193fe436f.png', NULL, NULL, 0, 'toma', 51, NULL, 11, '2026-11-17 15:54:45'),
(13, 6, 6, 'archivo', 'uploads/sesiones/6/1794927288_5987b053bde31a0fdeab.png', NULL, NULL, 0, 'variante', 54, 13, 12, '2026-11-17 15:54:48'),
(14, 6, 6, 'archivo', 'uploads/sesiones/6/1794927292_5a67f62455b8063c867b.png', NULL, NULL, 0, 'variante', 54, 12, 13, '2026-11-17 15:54:52'),
(15, 6, 6, 'archivo', 'uploads/sesiones/6/1794927302_0efe34169d4c478ac09c.png', NULL, NULL, 0, 'variante', 54, 16, 14, '2026-11-17 15:55:02'),
(16, 6, 6, 'archivo', 'uploads/sesiones/6/1794927305_6257e1be1a1268a77a25.png', NULL, NULL, 0, 'variante', 54, 14, 15, '2026-11-17 15:55:05'),
(17, 6, 6, 'archivo', 'uploads/sesiones/6/1794927308_a8ce02cb673a27dd1371.png', NULL, NULL, 0, 'variante', 54, 34, 16, '2026-11-17 15:55:08'),
(18, 6, 7, 'archivo', 'uploads/sesiones/6/1794927312_58080752d6884faeabac.png', NULL, NULL, 0, 'toma', 56, NULL, 17, '2026-11-17 15:55:12'),
(19, 6, 7, 'archivo', 'uploads/sesiones/6/1794927315_97c281589944c21dcd1d.png', NULL, NULL, 0, 'variante', 56, 18, 18, '2026-11-17 15:55:15'),
(20, 6, 5, 'archivo', 'uploads/sesiones/6/1794927319_e06fa465c7f3d3d2f877.png', NULL, NULL, 0, 'toma', 53, NULL, 19, '2026-11-17 15:55:19'),
(21, 6, 7, 'archivo', 'uploads/sesiones/6/1794927323_3df082b655c966a6a941.png', NULL, NULL, 0, 'variante', 56, 19, 20, '2026-11-17 15:55:23'),
(22, 6, 4, 'archivo', 'uploads/sesiones/6/1794927326_2781cea441c9df9cac47.png', NULL, NULL, 0, 'toma', 67, NULL, 21, '2026-11-17 15:55:26'),
(23, 6, 10, 'archivo', 'uploads/sesiones/6/1794927333_455a03c54ea81b0b67d8.png', NULL, NULL, 0, 'variante', 42, 8, 22, '2026-11-17 15:55:33'),
(24, 6, 7, 'archivo', 'uploads/sesiones/6/1794927337_6c174b6556c6281a4c94.png', NULL, NULL, 0, 'toma', 55, NULL, 23, '2026-11-17 15:55:37'),
(25, 6, 7, 'archivo', 'uploads/sesiones/6/1794927344_37dbf38028658eb3da0c.png', NULL, NULL, 0, 'variante', 56, 20, 24, '2026-11-17 15:55:44'),
(26, 6, 7, 'archivo', 'uploads/sesiones/6/1794927348_fcf018762adc8cb503d7.png', NULL, NULL, 0, 'variante', 57, 28, 25, '2026-11-17 15:55:48'),
(27, 6, 4, 'archivo', 'uploads/sesiones/6/1794927351_9280d516907b3df665c1.png', NULL, NULL, 0, 'variante', 48, 35, 26, '2026-11-17 15:55:51'),
(28, 6, 4, 'archivo', 'uploads/sesiones/6/1794927355_55207836e02acd29b986.png', NULL, NULL, 0, 'variante', 48, 36, 27, '2026-11-17 15:55:55'),
(29, 6, 7, 'archivo', 'uploads/sesiones/6/1794927359_c4152e661bc27bbd75e3.png', NULL, NULL, 0, 'variante', 57, 29, 28, '2026-11-17 15:55:59'),
(30, 6, 7, 'archivo', 'uploads/sesiones/6/1794927363_40c4db6b6374f6a8f802.png', NULL, NULL, 0, 'variante', 57, 27, 29, '2026-11-17 15:56:03'),
(31, 6, 7, 'archivo', 'uploads/sesiones/6/1794927367_f44cc014a171a5d18b2f.png', NULL, NULL, 0, 'variante', 56, 23, 30, '2026-11-17 15:56:07'),
(32, 6, 7, 'archivo', 'uploads/sesiones/6/1794927370_7c9a9ac08e584bd1c176.png', NULL, NULL, 0, 'variante', 55, 17, 31, '2026-11-17 15:56:10'),
(33, 6, 4, 'archivo', 'uploads/sesiones/6/1794927374_5e2b2d03648c3e416647.png', NULL, NULL, 0, 'variante', 49, 37, 32, '2026-11-17 15:56:14'),
(34, 6, 7, 'archivo', 'uploads/sesiones/6/1794927377_5506d88548cf04a20afa.png', NULL, NULL, 0, 'variante', 56, 24, 33, '2026-11-17 15:56:17'),
(35, 6, 10, 'archivo', 'uploads/sesiones/6/1794927382_58d2eca39bf4e0cd4767.png', NULL, NULL, 0, 'toma', 62, NULL, 34, '2026-11-17 15:56:22'),
(36, 6, 13, 'archivo', 'uploads/sesiones/6/1794927387_44b65138aa0f7ac1e661.png', NULL, NULL, 0, 'toma', 66, NULL, 35, '2026-11-17 15:56:27'),
(37, 6, 4, 'archivo', 'uploads/sesiones/6/1794927391_fa5437077fef1489f5f3.png', NULL, NULL, 0, 'variante', 48, 38, 36, '2026-11-17 15:56:31'),
(38, 6, 4, 'archivo', 'uploads/sesiones/6/1794927395_7ff64fcc05601b26293e.png', NULL, NULL, 0, 'variante', 49, 39, 37, '2026-11-17 15:56:35'),
(39, 6, 5, 'archivo', 'uploads/sesiones/6/1794927398_db59f2fb43fc6899107d.png', NULL, NULL, 0, 'toma', 43, NULL, 38, '2026-11-17 15:56:38'),
(40, 6, 10, 'archivo', 'uploads/sesiones/6/1794927402_f07e977b3526f2b8535b.png', NULL, NULL, 0, 'variante', 42, 32, 39, '2026-11-17 15:56:42'),
(41, 6, 9, 'archivo', 'uploads/sesiones/6/1794927406_c72f44b6d0c0dcd66d89.png', NULL, NULL, 0, 'toma', 64, NULL, 40, '2026-11-17 15:56:46'),
(42, 6, 11, 'archivo', 'uploads/sesiones/6/1794927410_05d7de8c6370fd265c28.png', NULL, NULL, 0, 'toma', 60, NULL, 41, '2026-11-17 15:56:50'),
(43, 6, 7, 'archivo', 'uploads/sesiones/6/1794927413_de1c70e2b2369accb82b.png', NULL, NULL, 0, 'toma', 57, NULL, 42, '2026-11-17 15:56:53'),
(44, 6, 4, 'archivo', 'uploads/sesiones/6/1794927420_ead0008766caf7cced25.png', NULL, NULL, 0, 'descartada', NULL, NULL, 43, '2026-11-17 15:57:00'),
(45, 6, 5, 'archivo', 'uploads/sesiones/6/1794927425_aa8a04c819beb8a2570c.png', NULL, NULL, 0, 'variante', 43, 11, 44, '2026-11-17 15:57:05'),
(46, 6, 8, 'archivo', 'uploads/sesiones/6/1794927428_6ccddf231bc49750aefb.png', NULL, NULL, 0, 'toma', 50, NULL, 45, '2026-11-17 15:57:08'),
(47, 6, 7, 'archivo', 'uploads/sesiones/6/1794927436_8405ac5a398c2483a4bb.png', NULL, NULL, 0, 'toma', 63, NULL, 46, '2026-11-17 15:57:16'),
(48, 6, 7, 'archivo', 'uploads/sesiones/6/1794927439_351e8cfdd453d5b91450.png', NULL, NULL, 0, 'variante', 55, 30, 47, '2026-11-17 15:57:19'),
(49, 6, 4, 'archivo', 'uploads/sesiones/6/1794927443_98807c744ed12c02f01b.png', NULL, NULL, 0, 'variante', 49, 40, 48, '2026-11-17 15:57:23'),
(50, 6, 3, 'archivo', 'uploads/sesiones/6/1794927448_43f43c4fb85d8c2059a6.png', NULL, NULL, 0, 'toma', 41, NULL, 49, '2026-11-17 15:57:28'),
(51, 6, 12, 'archivo', 'uploads/sesiones/6/1795037533_f84c73402edf20a49d00.png', NULL, NULL, 0, 'toma', 71, NULL, 50, '2026-11-18 22:32:13'),
(52, 6, 4, 'archivo', 'uploads/sesiones/6/1795037702_9d746a19604dc604b5c5.png', NULL, NULL, 0, 'toma', 72, NULL, 51, '2026-11-18 22:35:02'),
(53, 6, 4, 'archivo', 'uploads/sesiones/6/1795039373_e4512ed1bdfdb6b40054.png', NULL, NULL, 0, 'variante', 49, 49, 52, '2026-11-18 23:02:53'),
(54, 6, 13, 'archivo', 'uploads/sesiones/6/1795062707_533680b49703d52d32bb.png', NULL, NULL, 0, 'variante', 66, 50, 53, '2026-11-19 05:31:47');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `model_releases`
--
ALTER TABLE `model_releases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `model_releases_sesion_id_foreign` (`sesion_id`);

--
-- Indices de la tabla `moodboard_items`
--
ALTER TABLE `moodboard_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `moodboard_items_sesion_id_foreign` (`sesion_id`),
  ADD KEY `moodboard_items_situacion_id_foreign` (`situacion_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `model_releases`
--
ALTER TABLE `model_releases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `moodboard_items`
--
ALTER TABLE `moodboard_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `model_releases`
--
ALTER TABLE `model_releases`
  ADD CONSTRAINT `model_releases_sesion_id_foreign` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `moodboard_items`
--
ALTER TABLE `moodboard_items`
  ADD CONSTRAINT `moodboard_items_sesion_id_foreign` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `moodboard_items_situacion_id_foreign` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
