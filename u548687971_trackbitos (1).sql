-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 20-11-2026 a las 00:11:58
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
-- Estructura de tabla para la tabla `rodajes_escenas`
--

CREATE TABLE `rodajes_escenas` (
  `id` int(10) UNSIGNED NOT NULL,
  `proyecto_id` int(10) UNSIGNED NOT NULL,
  `situacion_id` int(11) DEFAULT NULL,
  `escena_bloque` varchar(120) DEFAULT NULL,
  `formato` varchar(10) DEFAULT NULL,
  `escena_tomas` varchar(60) DEFAULT NULL,
  `escena_ubicacion` varchar(200) DEFAULT NULL,
  `ubicacion_id` int(11) DEFAULT NULL,
  `punto_id` int(11) DEFAULT NULL,
  `setup_id` int(11) DEFAULT NULL,
  `setup_video_id` int(11) DEFAULT NULL,
  `franja` varchar(10) DEFAULT NULL,
  `prioridad` varchar(3) DEFAULT 'imp',
  `props` text DEFAULT NULL,
  `escena_descripcion` text DEFAULT NULL,
  `escena_objetivo` text DEFAULT NULL,
  `escena_accion` text DEFAULT NULL,
  `escena_efecto_especial` varchar(200) DEFAULT NULL,
  `escena_cont_previa` text DEFAULT NULL,
  `escena_cont_posterior` text DEFAULT NULL,
  `camara_modelo` varchar(120) DEFAULT NULL,
  `camara_optica` varchar(120) DEFAULT NULL,
  `camara_apertura` varchar(40) DEFAULT NULL,
  `camara_fps` varchar(30) DEFAULT NULL,
  `camara_velocidad` varchar(40) DEFAULT NULL,
  `camara_iso` varchar(40) DEFAULT NULL,
  `camara_wb` varchar(60) DEFAULT NULL,
  `camara_nd` varchar(40) DEFAULT NULL,
  `camara_tipo_plano` varchar(60) DEFAULT NULL,
  `camara_angulo` varchar(60) DEFAULT NULL,
  `camara_movimiento` varchar(120) DEFAULT NULL,
  `camara_soporte` varchar(40) DEFAULT NULL,
  `plano_ref_lugar_texto` text DEFAULT NULL,
  `plano_ref_inspiracion_texto` text DEFAULT NULL,
  `plano_esquema_iluminacion` text DEFAULT NULL,
  `plano_hora_dia` varchar(30) DEFAULT NULL,
  `plano_objetos` text DEFAULT NULL,
  `plano_actores` char(1) DEFAULT 'N',
  `plano_toma_alternativa` text DEFAULT NULL,
  `plano_notas` text DEFAULT NULL,
  `sonido_ambiente` char(1) DEFAULT 'N',
  `sonido_antiviento` char(1) DEFAULT 'N',
  `sonido_dialogo_escrito` text DEFAULT NULL,
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `orden_rodaje` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `toma_estado` varchar(12) DEFAULT NULL,
  `toma_estado_at` datetime DEFAULT NULL,
  `toma_estado_foto` varchar(12) DEFAULT NULL,
  `toma_estado_foto_at` datetime DEFAULT NULL,
  `toma_nota` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rodajes_escenas`
--

INSERT INTO `rodajes_escenas` (`id`, `proyecto_id`, `situacion_id`, `escena_bloque`, `formato`, `escena_tomas`, `escena_ubicacion`, `ubicacion_id`, `punto_id`, `setup_id`, `setup_video_id`, `franja`, `prioridad`, `props`, `escena_descripcion`, `escena_objetivo`, `escena_accion`, `escena_efecto_especial`, `escena_cont_previa`, `escena_cont_posterior`, `camara_modelo`, `camara_optica`, `camara_apertura`, `camara_fps`, `camara_velocidad`, `camara_iso`, `camara_wb`, `camara_nd`, `camara_tipo_plano`, `camara_angulo`, `camara_movimiento`, `camara_soporte`, `plano_ref_lugar_texto`, `plano_ref_inspiracion_texto`, `plano_esquema_iluminacion`, `plano_hora_dia`, `plano_objetos`, `plano_actores`, `plano_toma_alternativa`, `plano_notas`, `sonido_ambiente`, `sonido_antiviento`, `sonido_dialogo_escrito`, `orden`, `orden_rodaje`, `created_at`, `updated_at`, `deleted_at`, `toma_estado`, `toma_estado_at`, `toma_estado_foto`, `toma_estado_foto_at`, `toma_nota`) VALUES
(1, 1, NULL, 'INICIO', NULL, 'Distintos planos del exterior como introducción del sitio', 'En el exterior', 1, NULL, 1, NULL, NULL, 'imp', NULL, 'Escena donde se muestra la fachada y los principales detalles', 'Enseñar el lugar desde fuera cuando llegas', '', '', '', '', 'Sony a7IV', '20mm', '2.8', '50', '100', '800', '5600', 'si 7 pasos', 'General', 'Frontal', 'Dolly', 'Gimbal', '', 'https://www.tiktok.com/@cohvisuals/video/7268352731268533537', 'Luz ambiente de día', 'Sin personas', '', 'N', '', 'Prestar especial atención a los elementos de la fachada característicos', 'N', 'N', '', 1, NULL, '2025-09-23 05:15:27', '2026-11-14 21:40:38', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, NULL, 'AMBIENTE', NULL, 'Las distintas zonas del local y detalles', 'En el interior y exterior', 2, NULL, 2, NULL, NULL, 'imp', NULL, 'Describir visualmente los distintos espacios y todos los detalles que tiene en cada uno de ellos para estar', 'Exhibir las distintos ambientes que dispone el local y sus detalles', '', '', '', '', 'Sony a7IV', '20mm y 35mm', '1.8 y 2.8', '50', '100', '800', '5600 y 2800', 'si y no', 'Medio', 'Frontal', 'Dolly', 'Mano', '', 'https://www.tiktok.com/@cohvisuals/video/7268352731268533537', 'Luz ambiente de los distintos sitios', 'Sin personas', '', 'N', '', 'Centrarse en detalles de la barra, neveras y figuras que se tienen', 'N', 'N', '', 2, NULL, '2025-09-23 05:39:50', '2025-10-08 06:02:18', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 1, NULL, 'TRANSICIÓN - Ambiente a Desayuno', NULL, 'Camarero sirviendo un desayuno', 'Exterior', 3, NULL, 3, NULL, NULL, 'imp', NULL, 'El camarero con un plato y una tostada la sirve en la mesa, puede ser en seguimiento o POV', 'Pasar de la escena de detalles al servicio de desayuno', 'Servir plato a la mesa sonriente', '', '', '', 'Sony a7IV', '35mm', '2.8', '25', '50', '800', '5600', 'no', 'General', 'Lateral', 'Seguimiento', 'Mano', '', 'https://www.tiktok.com/@cohvisuals/video/7268352731268533537\r\nhttps://www.tiktok.com/@jan.views/video/7501799184249507094?_r=1&_t=ZN-8zXxDuDkg9W\r\nhttps://es.pinterest.com/pin/20829217022284050/', 'Iluminación global lateral en el interior, en exterior la que haya.', 'Tostadas', '', 'N', 'Si no se puede fuera, probar dentro. Probar con el cámarer POV y sino a la mesa.', '', 'N', 'N', '', 3, NULL, '2025-09-23 05:47:10', '2025-10-08 06:04:00', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 1, NULL, 'DESAYUNO', NULL, 'Plano detalle de la tostada con titulo', 'Exterior', 3, NULL, 4, NULL, NULL, 'imp', NULL, 'Se enseña la tostada servida junto al nombre en una mesa bien presentada con su bebida', 'Enseñar el producto estrella del desayuno, la tostada', '', '', 'Debe ser la misma que la tostada servida por el camarero', '', 'Sony a7IV', '50mm', '2.8', '50', '100', '800', '5600', 'si, 4 pasos', 'Primer plano', 'Frontal', 'Fijo', 'Trípode', '', 'https://es.pinterest.com/pin/1829656094597840/', 'En exterior la de ambiente, dentro un esquema clasico a 45º con una luz en contra para bordes', 'Tostadas', 'Tostada con bebida tipo café o similar', 'N', '', 'Preguntar si es más en exterior o en interior. Mollete con jamón.', 'N', 'N', '', 4, NULL, '2025-09-23 05:55:00', '2025-10-08 06:04:04', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 1, NULL, 'DESAYUNO', NULL, 'Detalle en giro de la tostada', 'Exterior', 3, NULL, 6, NULL, NULL, 'imp', NULL, 'Enseña con más detalle girando la cámara alrededor de la tostada', 'Mostrar los detalles de la tostada', '', '', '', '', 'Sony a7IV', '35mm', '4', '50', '125', '800', '5600', 'si 2 pasos', 'Detalle', 'Frontal', 'Giro', 'Otro', '', '', 'Ambiental', 'Tostadas', 'Tostada y bebida', 'N', '', 'Probar distintos modos de giro para pillar los detalles mejor. 50 fps, velocidad media de dolly.', 'N', 'N', '', 6, NULL, '2025-09-23 06:02:24', '2025-10-08 06:04:10', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 1, NULL, 'DESAYUNO', NULL, 'Toma POV enseñando los ingredientes de la tostada', 'Exterior', 3, NULL, 7, NULL, NULL, 'imp', NULL, 'Una persona agarra la tostada y la examina mientras títulos en la pantalla aparecen los ingredientes tostada.', 'Describir de que es la tostada.', 'Persona agarra la tostada', 'Titulos linkados a los ingredientes', 'Misma tostada', '', 'Sony a7IV', '20mm', '2.8', '25', '50', '800', '5600', 'si 4 pasos', 'General', 'Frontal', 'Fijo', 'Trípode', '', 'https://es.pinterest.com/pin/2392606048195372/', 'La existente', 'Tostadas', 'Tostada en su plato', 'S', '', 'Averiguar como poner el tripode y se abarca la perspectiva de enseñar la tostada. Probar con distintas variedades si existen', 'N', 'N', '', 7, NULL, '2025-09-23 06:09:51', '2025-10-08 06:04:14', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 1, NULL, 'DESAYUNO', NULL, 'Mesa agregando y quitando las distintas variedades de panes ', 'Exterior', 3, NULL, 5, NULL, NULL, 'imp', NULL, 'En una mesa colocar todas los platos e ir quitando y agregando bebidas y tostadas, patés ibéricos. Integral, bollo y mollete.', 'Enseñar todas las variedades', '', 'Agregar y quitar en toma fija para efecto aparecer y desaparecer', '', '', 'Sony a7IV', '35mm', '4', '25', '50', '800', '5600', 'si', 'Medio', 'Picado', 'Fijo', 'Trípode', '', 'https://www.tiktok.com/@valterkuechel/video/7499959100302822663\r\nhttps://es.pinterest.com/pin/2462974792321110/', 'Existente si es en exterior', 'Tostadas', 'Todas las variantes de tostadas, y todas las bebidas habituales.', 'N', 'Alternativa con un solo plato cambiando', 'Mollete con jamón, con jamón York, carne mechada, carne mechada con queso y/o tomate, jamón con tomate. Colacao, infusión, te, capuchino, zumos, batidos, Cocacola', 'N', 'N', '', 5, NULL, '2025-09-23 06:19:13', '2025-10-08 06:04:07', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 1, NULL, 'TRANSICION - Desayuno a Mediodía', NULL, 'Echando cerveza en una copa', 'Exterior', 3, NULL, 8, NULL, NULL, 'imp', NULL, 'Alguien sirve una cerveza en un vaso largo', 'Pasar del desayuno al mediodia', 'Echar cerveza al vaso', 'Se llena solo el vaso de cerveza', 'En el mismo entorno', '', 'Sony a7IV', '50mm', '2.8', '25', '50', '800', '5600', 'si 4 pasos en sombra', 'Detalle', 'Frontal', 'Fijo', 'Trípode', '', 'https://www.tiktok.com/@valterkuechel/video/7499959100302822663', 'La existente', 'Tartas', '', 'N', 'Si no es solo, que alguien la eche al vaso', '', 'N', 'N', '', 8, NULL, '2025-09-23 16:18:41', '2025-10-08 06:05:47', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 1, NULL, 'MEDIODIA', NULL, 'Tomas en giro de distintas opciones de mediodia', 'Exterior', 3, NULL, 9, NULL, NULL, 'imp', NULL, 'Se toman las distintas opciones puestas en la misma mesa, con el mismo giro de cámara y en rampa de velocidad se van cambiando las opciones.', 'Enseñar las opciones de mediodia', '', 'Giro en rampas de velocidad', '', '', 'Sony a7IV', '35mm', '2.8', '50', '125', '800', '5600', 'si 4 pasos', 'Primer plano', 'Picado', 'Giro', 'Mano', '', 'https://www.tiktok.com/@valterkuechel/video/7499959100302822663', 'La existente', 'Copas', 'Vasos, kikos, copa, tonica, cerveza botellín', 'N', '', 'Cerveza, queso, olivas, patatas, copa de vino, rioja, gin tonic', 'N', 'N', '', 9, NULL, '2025-09-23 16:22:48', '2025-10-08 06:04:52', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 1, NULL, 'TRANSICION - Mediodía a Tarde', NULL, 'Pasar del mediodia a la tarde con un brindis', 'Exterior', 3, NULL, 10, NULL, NULL, 'imp', NULL, 'Un brindis entre 3 personas pasamos la cámara por detrás de alguien que pasa a tener una tarta que debe tener un café', 'Transición entre las copas a los postres', 'Brindis con copas y probar la tarta', 'Ocultación de la transición con la espalda de la persona', 'Brindis entre 3 personas', 'Tarta rompiendo un trozo y con un café que será premisa para las siguientes escenas', 'Sony a7IV', '35mm o 50mm', '2.8', '25', '50', '800', '5600', 'si 4 pasos', 'Primer plano', 'Picado', 'Paneo', 'Mano', '', 'https://www.tiktok.com/@valterkuechel/video/7460384784351530245\r\n', 'La existente', 'Pendiente tarta', 'Copas, tarta, plato', 'S', '', '', 'N', 'N', '', 10, NULL, '2025-09-23 16:27:09', '2025-10-16 19:46:45', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 1, NULL, 'TARDE', NULL, 'Elaboración del café en máquina', 'Interior', 4, NULL, 9, NULL, NULL, 'imp', NULL, 'Se crean todas las tomas necesarias para describir la elaboración del café.\r\n- Echar el café en el portafiltro\r\n- Se rasea\r\n- Se inserta\r\n- Expulsa café\r\n- Se espuma la lecha (opcional)\r\n- Echar la leche al café\r\n- Se sirve en la barra\r\n', 'Explicar el proceso de elaboración del café con leche.', 'Preparar el café por un camarero', '', 'Tomar la referencia del café de la toma anterior.', 'Servir el café limpio y pasar al resto de bebidas y tartas en planos compuestos', 'Sony a7IV', '35mm', '1.8', '50', '125', '800', '5600', 'no', 'Detalle', 'Picado', 'Giro', 'Mano', '', 'https://www.tiktok.com/@jan.views/video/7521856398834076950?_r=1&_t=ZN-8zXx7kmIpp0', 'Iluminación lateral trasera para resaltar el ambiente y crear estética más cinemetica.', 'Actores', 'Cafetera, café y demás elementos para preparar el café', 'N', '', 'Ver el proceso antes para determinar las tomas exactas', 'N', 'N', '', 11, NULL, '2025-09-23 18:42:01', '2025-10-08 06:03:27', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 1, NULL, 'TARDE', NULL, 'Planos compuestos de tartas y bebidas', 'Interior', 4, NULL, 9, NULL, NULL, 'imp', NULL, 'A través de varias tomas hacer una composición de tres columnas verticales, para mostrar el proceso de servir, tomar y detalles de las tartas, bebidas de la tarde.', 'Enseñar las opciones', 'comer la tarta, servir la tarta, probar un trozo\r\n', '', 'Introducir algún plano de la elaboración del café', '', 'Sony a7IV', '35mm', '1.8', '50', '100', '800', '5600', 'no', 'Detalle', 'Cenital', 'Fijo', 'Mano', '', 'https://es.pinterest.com/pin/1055599908367402/\r\nhttps://es.pinterest.com/pin/1829656094597840/\r\nhttps://www.tiktok.com/@palmiermedia/video/7527987613832088854', 'Esquema iluminación alto y trasero más rebote en la parte frontal', 'Pendiente tarta', 'tartas, bebidas', 'S', '', 'Detalle de que se combinen tanto cenitales como detalles y varias opciones (ver opciones disponibles)', 'N', 'N', '', 12, NULL, '2025-09-23 18:47:21', '2025-10-16 19:46:29', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 1, NULL, 'TARDE', NULL, 'Detalles de la tarta cortando un trozo', 'Interior', 4, NULL, 11, NULL, NULL, 'imp', NULL, 'Primer plano de detalle de la tarta en al mesa donde alguien corta un trozo para comer', 'Enseñar a pantalla completa el detalle de las tartas', 'Cortar la tarta con un tenedor', 'Cambiar en transición ligera por otra tarta', '', '', 'Sony a7IV', '50mm', '2.8 al menos si no más para ganar profun', '50', '125', '800', '5600', 'no', 'Detalle', 'Frontal', 'Giro', 'Otro', '', '', 'Iluminación trasera principalmente con rebote frontal', 'Pendiente tarta', 'tarta, tenedor y plato', 'S', '', 'Con el dolly portatil y girando sobre la tarta que se prueba, se echa hacia atrás y se repite con otra tarta.', 'N', 'N', '', 13, NULL, '2025-09-23 19:16:28', '2025-10-16 19:47:19', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 1, NULL, 'TARDE', NULL, 'Aparecer distintos platos y copas de opciones', 'Interior', 4, NULL, 5, NULL, NULL, 'imp', NULL, 'En una mesa se compone distintas opciones de la tarde, que si tarta, kikos, cerveza, copa, tonica, botellines, etc.', 'Enseñar la carta de la tarde transicionando opciones en el mismo sitio', '', 'Cambiar los elementos con el plano estatico', 'Pasar a la tarta primero como en el plano anterior', 'Terminar con una copa como se hará en la escena siguietne', 'Sony a7IV', '35mm', '2.8', '25', '50', '800', '5600', 'no', 'Medio', 'Picado', 'Fijo', 'Trípode', '', 'https://www.tiktok.com/@angelovideomaker/video/7485804862001794326?_t=ZN-8zXy6GgSoCc&_r=1', 'Iluminación trasera con rebote frontal', 'Pendiente tarta', 'Tartas, kikos, patatas, dulces, cerveza, copas, etc', 'N', '', 'CUIDADO CON EL FOCO, DEJAR LA COPA ATRÄS PARA EVITAR SOMBRA DE LA COMIDA o ALEJADO. dejar en manual el foco. Hacer lista de opciones de la cristalera. \r\n\r\nPalmeras de chocolate y de huevo, lacitos, 3-4 tipos de tartas, cortadillo, gofres, cerveza, Cocacola, batidos, zumos, gin tonic, copas ', 'N', 'N', '', 14, NULL, '2025-09-23 19:23:40', '2025-10-16 19:47:56', NULL, NULL, NULL, NULL, NULL, NULL),
(15, 1, NULL, 'TARDE', NULL, 'Tomar dos clientes una bebida para pasar a la noche', 'Interior', 4, NULL, 12, NULL, NULL, 'imp', NULL, 'En una mesa alta dos personas se toman la última copa de la tarde, de cerveza y otra copa de bebida para pasar al bloque de la noche con más copas. Elegir el fondo de neveras.', 'Hacer de puente entre la tarde y la noche acabando', 'Levantar las copas para beber dos personas con sus bebidas', '', 'Copa que tenia en el ultimo plano de la anterior toma', 'Plano fijo como la transición a la noche', 'Sony a7IV', '50mm', '2 o menos', '50', '125', '800', '5600', 'no', 'General', 'Frontal', 'Fijo', 'Mano', '', '', 'Iluminación global, puede ser la existente. Si no un globo e iluminar suave solo para no generar ruido.', 'Actores', 'Copa y cerveza en una mesa alta', 'S', 'Como alternativa agregar transición a dos copas de la noche', '50mm para comprimir el plano y eliminar las caras, toma corta y frontal con el fondo de las neveras que dan color y variedad..', 'N', 'N', '', 15, NULL, '2025-09-23 19:29:09', '2025-10-08 06:03:30', NULL, NULL, NULL, NULL, NULL, NULL),
(16, 1, NULL, 'TRANSICION - Tarde a Noche', NULL, 'Fachada que cambia del día a la noche', 'Exterior', 3, NULL, 13, NULL, NULL, 'imp', NULL, 'Toma fija mirando a la fachada y carte de la entrada en el que cambia de tarde a noche, puede ser camara rapida o de composición de fotos.', 'Pasar de la tarde a la noche con el cambio de luz en el local.', '', 'Transición de fotos rápidas', '', '', 'Sony a7IV', '35mm o 20mm', '5.6', 'foto fija', '400', '100', '5600', 'no', 'General', 'Contrapicado', 'Fijo', 'Trípode', '', '', 'La existente', 'Pendiente timelapse', '', 'N', 'Puede ser con foto fija o video continuo según tarde el tiempo de pasar la luz.', 'Puede no esperar a oscurecer del todo sino a notarse menos luz. 19:15 hay luz y atardecer ya rondará las 20h', 'N', 'N', '', 16, NULL, '2025-09-23 19:33:20', '2025-10-16 19:48:38', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 1, NULL, 'CIERRE', NULL, 'Se abandona el local como se entra al principio del video', 'Exterior', 3, NULL, 18, NULL, NULL, 'imp', NULL, 'Plano dolly desde la entrada afuera enseñando la fachada como al principio del video pero de sentido inverso', '', '', '', '', '', '', '', '', '', '', '', '', '', 'General', 'Frontal', 'Fijo', 'Trípode', '', '', '', 'Sin personas', '', 'N', '', '', 'N', 'N', '', 23, NULL, '2025-09-23 19:36:47', '2025-10-08 06:02:43', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 1, NULL, 'NOCHE', NULL, 'Ambiente nocturno de distintas zonas', 'En el interior y exterior', 2, NULL, 14, NULL, NULL, 'imp', NULL, 'Enseñar las distintas zonas con la gente disfrutando de los ambientes del local en tomas en cámara en mano.', 'Mostrar los espacios con la gente', '', '', '', '', 'Sony a7IV', '20mm', '1.8', '50', '125', '800 o 4000', '5600', 'no', 'General', 'Frontal', 'Seguimiento', 'Mano', '', 'https://www.tiktok.com/@cohvisuals/video/7268352731268533537', '', 'Sin personas', '', 'S', '', '', 'N', 'N', '', 17, NULL, '2025-09-23 19:37:47', '2025-10-08 06:02:26', NULL, NULL, NULL, NULL, NULL, NULL),
(19, 1, NULL, 'NOCHE', NULL, 'Paneo de la barra previo a elaborar la copa', 'Interior', 4, NULL, 15, NULL, NULL, 'imp', NULL, 'Enseña lo que hay detrás de la barra a través de un dolly en un paneo horizontal y contrapicado', 'Mostrar toda la variedad de botellas de distintos alcoholes que hay', '', '', '', '', 'Sony a7IV', '20mm o 35mm', '2.8', '50', '125', '800', '5600', 'no', 'Medio', 'Contrapicado', 'Dolly', 'Otro', '', 'https://www.tiktok.com/@cohvisuals/video/7268352731268533537', 'Iluminación global, probar con rebote al techo quizás.', 'Sin personas', '', 'N', '', '', 'N', 'N', '', 18, NULL, '2025-09-23 19:38:19', '2025-10-08 06:02:30', NULL, NULL, NULL, NULL, NULL, NULL),
(20, 1, NULL, 'NOCHE', NULL, 'Elaboración del mojito', 'Interior', 4, NULL, 16, NULL, NULL, 'imp', NULL, 'Todas las etapas de elaborar el mojito\r\n- Poner el azúcar\r\n- Poner la hierbabuena\r\n- Machacar\r\n- Poner el hielo\r\n- Poner el alcohol y refresco', 'Enseñar el proceso de elaboración del mojito', 'Camarero preparando el mojito', 'Cámara lenta', '', '', 'Sony a7IV', '35mm o 50mm', '2.8', '50', '125', '800', '5600', 'no', 'Primer plano', 'Frontal', 'Seguimiento', 'Mano', '', 'https://www.youtube.com/watch?v=P2EOVODrSzs\r\nhttps://www.tiktok.com/@estudiodap/video/7459502778034048278?_r=1&_t=ZN-8zXvOHCEcaM', 'Iluminación que no ocupe sitio en la barra pero preferiblemente lateral', 'Actores', 'Copa ', 'S', '', 'Prestar atención a ser movimientos, preguntar previamente como es el proceso de elaboración para dividir la escena', 'N', 'N', '', 20, NULL, '2025-09-23 19:39:52', '2025-10-08 06:03:33', NULL, NULL, NULL, NULL, NULL, NULL),
(21, 1, NULL, 'NOCHE', NULL, 'Mientras se echa una bebida mostrar todas las opciones', 'Interior', 4, NULL, 8, NULL, NULL, 'imp', NULL, 'En un plano contrapicado en la barra se enseña todas la bebidas alcohólicas que se tienen whisky, ginebra, coñac, cerveza, etc, mientras se sirve una de ellas', 'Enseñar toda la gama de botellas, unas 10 por ejemplo', 'servir una copa concreta', 'cambiando las botellas mientras se combina con otro plano que se está echando la copa', '', '', 'Sony a7IV', '50mm', '2.8', '25', '50', '800', '5600', 'no', 'Primer plano', 'Contrapicado', 'Fijo', 'Trípode', '', 'https://www.tiktok.com/@angelovideomaker/video/7388501801487666464', 'Lateral y un poco trasera bastante fuerte para aumentar el brillo', 'Sin personas', 'Copa y distintas botellas', 'N', '', 'Buscar todas las opciones que hay', 'N', 'N', '', 19, NULL, '2025-09-23 19:40:34', '2025-10-08 06:02:39', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 1, NULL, 'NOCHE', NULL, 'Todas las versiones de mojios, san francisco y piña colada', 'Interior', 4, NULL, 5, NULL, NULL, 'imp', NULL, 'En una mesa aparecen y desaparecen bebidas de las distintas opciones de copas', 'Enseñar las bebidas elaboradas', '', 'Aparecer y desaparecer', 'Previamente se sirve por un camarero una de ellas que se elabora en barra antes de pasar a todas ellas en la mesa para transicionar', '', 'Sony a7IV', '35mm', '2.8', '25', '50', '800', '5600', 'no', 'Medio', 'Picado', 'Fijo', 'Trípode', '', 'https://www.tiktok.com/@valterkuechel/video/7499959100302822663', 'Lateral y un poco picado a 45º1', 'Pendiente copas', 'copas de licores elaborados', 'N', '', 'Encontrar distintos vasos y la composición buscar equilibrio de colores y se distinga que son cada uno ', 'N', 'N', '', 21, NULL, '2025-09-23 19:41:54', '2025-10-16 19:49:02', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 1, NULL, 'NOCHE', NULL, 'Tomas individuales que cambian y pasan a la ultima que baja', 'Interior', 4, NULL, 8, NULL, NULL, 'imp', NULL, 'Se presentan individualmente las distintas opciones de bebida por la noche, copas, cervezas, elaboradas, y una última se acaba para hacer de transición, también puede ser comida.', 'Enseñar todas las opciones pero con más detalle y terminar con la transición', '', 'Cambiando las botellas y opciones nocturnas', '', 'terminar con la copa terminándose', 'Sony a7IV', '50mm', '2.8', '25', '50', '800', '5600', 'no', 'Primer plano', 'Picado', 'Fijo', 'Trípode', '', 'https://www.tiktok.com/@valterkuechel/video/7499959100302822663', 'Buscar especulares con iluminación lateral', 'Pendiente copas', 'copas, botellines, y comidas a ser posible', 'N', '', '-PONER EL VASO DETRAS DEL PLATO PARA NO MEZCLAR LAS SOMBRAS DE LOS PLATOS. \r\n- TENER CUIDADO EL ENFOQUE\r\nVer si se puede incluir comida. (PUEDE INCLUIR CARTEL DE CADA COSA?)\r\n', 'N', 'N', '', 22, NULL, '2025-09-23 19:43:42', '2025-10-16 19:50:05', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 1, NULL, 'NOCHE', NULL, 'Detrás de la barra sacando las bebidas', 'En el interior', 5, NULL, 17, NULL, NULL, 'imp', NULL, 'Toma de la camarera detras de la barra mientras trabaja y sirve las bebidas en la bandeja para llevarla por otro camarero.', 'Enseñar a la camarera en su puesto de trabajo', 'Pone en la barra las bebidas que necesita el otro camarero', '', '', '', 'Sony a7IV', '50mm', '2.8', '50fps', '1/100', '800', '5600', 'no', 'Medio', 'Frontal', 'Fijo', 'Mano', '', 'https://www.gettyimages.es/detail/v%C3%ADdeo/young-barista-serves-coffee-to-woman-in-cafe-v%C3%ADdeos-de-stock/1401387204?adppopup=true\r\nhttps://www.gettyimages.es/detail/v%C3%ADdeo/waitress-japanese-ethnicity-at-the-coffee-shop-pel%C3%ADculas-de-stock/1390131471?adppopup=true\r\nhttps://www.gettyimages.es/detail/v%C3%ADdeo/portrait-of-a-waitress-behind-the-counter-pel%C3%ADculas-de-stock/1255912151?adppopup=true', 'Luz lateral y arriba simular luz ambiente, quizás el softbox largo a lo ancho', 'Actores', 'Copas y bebidas con bandeja', 'S', '', 'Centrarse en la expresión ', 'N', 'N', '', 21, NULL, '2025-10-07 06:26:04', '2025-10-27 16:00:22', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 1, NULL, 'TRANSICIÓN - Ambiente a Desayuno', NULL, 'Sirviendo los desayunos', 'En el exterior', 1, NULL, 1, NULL, NULL, 'imp', NULL, 'Camarera hace el recorrido desde la barra hasta la mesa para servir el desayuno', 'Enseñar la acción de la camarera', 'Coger los platos de la barra a la bandeja y caminar hasta la mesa y servir los desayunos', '', '', '', 'Sony a7IV', '20mm', '2.8', '50', '1/100', '800', '5600', 'si', 'Medio', 'Frontal', 'Seguimiento', 'Gimbal', '', 'https://www.gettyimages.es/detail/v%C3%ADdeo/young-waitress-serving-drinks-at-beautiful-rooftop-pel%C3%ADculas-de-stock/1188475447?adppopup=true\r\nhttps://www.gettyimages.es/detail/v%C3%ADdeo/young-waitress-serving-drinks-at-beautiful-rooftop-pel%C3%ADculas-de-stock/1188478532?adppopup=true\r\nhttps://www.gettyimages.es/detail/v%C3%ADdeo/waitress-delivering-food-to-group-of-friends-dining-v%C3%ADdeos-de-stock/457447436?adppopup=true\r\nhttps://www.gettyimages.es/detail/v%C3%ADdeo/happy-latin-american-waitress-taking-the-order-of-a-pel%C3%ADculas-de-stock/1485850533?adppopup=true', 'Luz ambiente', 'Actores', '', 'N', '', '', 'N', 'N', '', 3, NULL, '2025-10-07 15:57:44', '2025-10-27 16:03:42', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 3, NULL, 'Tipo carnet', NULL, 'Tomas con el encuadre parecido al tamaño carnet', 'Pintura', 6, NULL, 19, NULL, NULL, 'imp', NULL, '', '', '', '', '', '', 'Sony a7IV', '50mm', '1.2-1.8', '', '', '100', '', '', 'General', 'Frontal', 'Fijo', 'Trípode', '', '', 'Iluminación lateral combinada con la del sol', 'CABEZA', '', 'N', '', '', 'N', 'N', '', 1, NULL, '2025-10-20 02:22:04', '2025-10-20 17:16:15', NULL, NULL, NULL, NULL, NULL, NULL),
(27, 3, NULL, 'Sentada en la silla', NULL, 'Sentada en la silla', '', NULL, NULL, 20, NULL, NULL, 'imp', NULL, 'Sentada en la silla', '', '', '', '', '', '', '', '', '', '', '', '', '', 'General', 'Frontal', 'Fijo', 'Trípode', '', '', '', 'SILLA', '', 'N', '', '', 'N', 'N', '', 3, NULL, '2025-10-20 17:11:28', '2025-10-20 17:15:50', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 3, NULL, 'Cabeza', NULL, 'Tomas de la cabeza lateral y soñadora', '', NULL, NULL, 20, NULL, NULL, 'imp', NULL, 'Tomas de la cabeza lateral y soñadora', '', '', '', '', '', '', '', '', '', '', '', '', '', 'General', 'Frontal', 'Fijo', 'Trípode', '', '', '', 'CABEZA', '', 'N', '', '', 'N', 'N', '', 2, NULL, '2025-10-20 17:12:13', '2025-10-20 17:16:06', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 3, NULL, 'Silla con los brazos', NULL, 'Silla con los brazos', '', NULL, NULL, 20, NULL, NULL, 'imp', NULL, 'Silla con los brazos', '', '', '', '', '', '', '', '', '', '', '', '', '', 'General', 'Frontal', 'Fijo', 'Trípode', '', '', '', 'SILLA', '', 'N', '', '', 'N', 'N', '', 4, NULL, '2025-10-20 17:12:40', '2025-10-20 17:16:23', NULL, NULL, NULL, NULL, NULL, NULL),
(30, 3, NULL, 'Manos a la cara', NULL, 'Manos a la cara', '', NULL, NULL, 20, NULL, NULL, 'imp', NULL, 'Manos a la cara', '', '', '', '', '', '', '', '', '', '', '', '', '', 'General', 'Frontal', 'Fijo', 'Trípode', '', '', '', 'MANOS', '', 'N', '', '', 'N', 'N', '', 5, NULL, '2025-10-20 17:13:06', '2025-10-20 17:16:45', NULL, NULL, NULL, NULL, NULL, NULL),
(31, 3, NULL, 'Creativa borroso', NULL, 'Creativa borroso', '', NULL, NULL, 20, NULL, NULL, 'imp', NULL, 'Creativa borroso', '', '', '', '', '', '', '', '', '', '', '', '', '', 'General', 'Frontal', 'Fijo', 'Trípode', '', '', '', 'CREATIVO', '', 'N', '', '', 'N', 'N', '', 6, NULL, '2025-10-20 17:13:23', '2025-10-20 17:16:33', NULL, NULL, NULL, NULL, NULL, NULL),
(32, 4, NULL, 'Hook', NULL, 'No sabes que regalar', 'En el taller', 7, NULL, 21, NULL, NULL, 'imp', NULL, 'Mirando a cámara directamente como si lo cogiera como un movil, preguntarte la duda al espectador', 'Plantear el problema', 'Saliendo del taller', '', '', '', 'Sony a7IV', '16mm', '1.8', '24', '1/50', '800', '5600', 'no', 'Medio', 'Frontal', 'Seguimiento', 'Mano', '', '', 'Luz lateral de una ventana con un softbox', '', '', 'S', '', '', 'N', 'N', '- ¿No sabes qué regalar? Se acerca el día y buscas algo original y personal y no se te ocurre nada. ¿Te ha pasado no?', 1, NULL, '2026-02-07 06:18:49', '2026-02-17 20:27:17', NULL, NULL, NULL, NULL, NULL, NULL),
(33, 4, NULL, 'IDENTIFICACION', NULL, 'Busqueda de regalos', 'Ordenador', 8, NULL, 22, NULL, NULL, 'imp', NULL, 'Se pone en ejemplo lo que hacemos cuando buscamos un regalo distinto por internet, mirando en el portatil distintas opciones en amazon o google.', 'Enseñar distintas alternativas no validas', 'scrollear la pantalla con distintas paginas', '', '', '', 'Sony a7IV', '50mm', '1.8', '24', '1/50', '800', '5600', 'no', 'Detalle', 'Lateral', 'Fijo', 'Trípode', '', '', 'Luz ambiente con luz simulando la del techo', '', 'Ordenador MAC', 'S', '', '', 'N', 'N', '- Si eres de las que se pasan horas buscando en Google o Amazon y acaban con menos ideas que al principio… quédate.', 2, NULL, '2026-02-07 06:23:42', '2026-02-17 20:27:24', NULL, NULL, NULL, NULL, NULL, NULL),
(34, 4, NULL, 'PROMESA', NULL, 'Mostrar una pieza impactante', 'En la mesa', 9, NULL, 23, NULL, NULL, 'imp', NULL, 'Se muestra en la escena algunos ejemplos de lo mejor que tenga posibles regalos sobre la mesa pasando y dandoles la vuelta. Mostrar la caja personalizada!', 'Enseñar algunos productos clave', 'Girar piezas y abrir caja. ', '', '', '', 'Sony a7IV', '35mm', '2.8', '24', '1/50', '800', '5600', 'no', 'Detalle', 'Frontal', 'Giro', 'Otro', '', '', 'Luz lateral y cenital para mostrar bien las piezas, evitar brillos innecesario.', '', 'Playmobil, caja', 'N', '', 'Mostrar la caja personalizada! Utilizar el dolly automatico.', 'N', 'N', 'Te muestro una solución diferente y totalmente personalizada. Puedes tener tu cantante favorito, tu personaje de la infancia o un mini-tu personalizado con todo tipo de detalle.', 3, NULL, '2026-02-12 05:04:14', '2026-02-17 20:27:32', NULL, NULL, NULL, NULL, NULL, NULL),
(35, 4, NULL, 'VARIEDAD', NULL, 'Montaje rápido de temáticas', '', NULL, NULL, 24, NULL, NULL, 'imp', NULL, 'Detalle de las manos con distinta variedad de propuestas de regalos, familias, trabajos, pegatinas, la caja.', 'Enseñar todo tipo de ejemplos personalizados', 'Mostrar las cajas, contenido, y muñecos girandolos', '', '', '', 'Sony a7IV', '50mm', '2.8', '24', '1/50', '800', '5600', 'no', 'Detalle', 'Picado', 'Paneo', 'Mano', '', '', 'Luz lateral y cenital para mostrar bien las piezas, evitar brillos innecesario.', '', 'Caja, playmobil, peanas, familia, etc..', 'N', '', 'Alargar según de los distintos productos, enumerarlos.', 'N', 'N', 'Hago Playmobil personalizados por temáticas, profesiones, personajes famosos, hobbies, parejas, familias… prácticamente cualquier idea.', 4, NULL, '2026-02-12 05:14:00', '2026-02-17 20:27:39', NULL, NULL, NULL, NULL, NULL, NULL),
(36, 4, NULL, 'DIFERENCIAL', NULL, 'Detalle de pintura / proceso', 'Mesa', 10, NULL, 21, NULL, NULL, 'imp', NULL, 'Trabajando en una mesa con los playmobil se esmera en detalles personalizados', 'Mostrar los mínusculo y distintas personalizaciones', 'Creando distintos detalles colocados a mano', '', '', '', 'Sony a7IV', '16mm', '2.8', '24', '1/50', '800', '5600', 'no', 'Detalle', 'Picado', 'Dolly', 'Mano', '', '', 'Luz lateral y cenital para mostrar bien las piezas, evitar brillos innecesario.', '', 'Playmobil, pincel, detalles de muñecos', 'N', '', '', 'N', 'N', 'Cada pieza está hecha a mano, cuidando cada detalle.  Te llevas un regalo realmente original y único, porque se diseña y se personaliza exclusivamente para ti.', 5, NULL, '2026-02-12 05:14:33', '2026-02-17 20:27:46', NULL, NULL, NULL, NULL, NULL, NULL),
(37, 4, NULL, 'PRECIO / ACCESIBILIDAD', NULL, 'Mostrar distintos niveles de complejidad', 'En la mesa', 9, NULL, 21, NULL, NULL, 'imp', NULL, 'Mostrar tipos de detalles, XL u opciones más grandes.', 'Dar a entender la horquilla de precio enseñando detalles complejos', 'Hablar a cámara', '', '', '', 'Sony a7IV', '16mm', '2.8', '24', '1/50', '800', '5600', 'no', 'Medio', 'Frontal', 'Fijo', 'Mano', '', '', 'Luz de ventana', '', 'Playmobil y detalles', 'N', '', '', 'N', 'N', 'El precio se ajusta según el grado de personalización y el tamaño del proyecto, para que haya opciones para todos los presupuestos.', 6, NULL, '2026-02-12 05:15:06', '2026-02-17 20:27:53', NULL, NULL, NULL, NULL, NULL, NULL),
(38, 4, NULL, 'CIERRE / CTA', NULL, 'Mejor pieza / plano tuyo', 'Junto a estanteria o algo elevado', 11, NULL, 25, NULL, NULL, 'imp', NULL, 'Mirando a cámara se presenta la opción a la cámara mientras está en algo para pasar a la acción', 'Mostrar objetos potentes de playmobil y tu a la cámara mientras colocas cosas', 'Colocar cosas en alguna estanteria u playmobil, pinturas etc', '', '', '', 'Sony a7IV', '35mm', '2.8', '24', '1/50', '800', '5600', 'no', 'Medio', 'Lateral', 'Fijo', 'Mano', '', '', 'Luz de ventana y rebote clasico leve', '', 'playmobil o pinturas', 'N', '', '', 'N', 'N', 'Ya sabes, si quieres un regalo distinto aquí te doy una buena idea, escríbeme y la convertimos en tu mejor regalo.', 7, NULL, '2026-02-12 05:20:02', '2026-02-22 04:53:03', NULL, NULL, NULL, NULL, NULL, NULL),
(39, 5, NULL, '', 'ambas', '', 'Calle Larga/lenceria', 12, NULL, 28, NULL, 'dia', 'imp', NULL, 'Retrato a cámara de pie', '', '', '', '', '', 'Sony a7IV', '50mm', '', '', '', '', '', '', '', '', '', 'Mano', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 1, NULL, '2026-11-15 01:41:49', '2026-11-19 17:11:19', '2026-11-18 21:54:42', NULL, NULL, NULL, NULL, NULL),
(40, 5, NULL, 'Calle larga', NULL, '', '', NULL, NULL, NULL, NULL, NULL, 'imp', NULL, '', '', '', '', '', '', 'Sony a7IV', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 1, NULL, '2026-11-15 01:41:54', '2026-11-15 01:42:05', '2026-11-15 01:42:05', NULL, NULL, NULL, NULL, NULL),
(41, 5, 3, 'Cajero', 'ambas', '', 'Cruce con calle corredera', 13, 4, 27, 29, 'dia', 'imp', '[\"Tarjeta crédito falsa 💳\"]', 'Metiendo tarjeta', '', '', '', '', '', 'Sony a7IV', '35mm', '', '', '', '', '', '', '', '', '', 'Mano', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 8, NULL, '2026-11-16 06:59:12', '2026-11-19 16:09:37', NULL, NULL, NULL, NULL, NULL, NULL),
(42, 5, 10, 'Banco o escalera sentada', 'ambas', '', 'Banco plaza arenal', 14, NULL, 27, 29, 'noche', 'imp', '[\"Movil\"]', 'Mirando el movil', '', '', '', '', '', 'Sony a7IV', '35mm', '', '', '', '', '', '', '', '', '', 'Mano', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 15, NULL, '2026-11-16 23:38:58', '2026-11-19 16:09:37', NULL, NULL, NULL, NULL, NULL, NULL),
(43, 5, 5, 'Compras', 'ambas', '', 'Calle Larga/lenceria', 12, NULL, 28, 29, 'dia', 'imp', '[\"Bolsas de papel\"]', 'Caminando con bolsas', '', '', '', '', '', 'Sony a7IV', '50mm', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 13, NULL, '2026-11-16 23:39:12', '2026-11-19 17:11:19', NULL, NULL, NULL, NULL, NULL, NULL),
(44, 5, 4, 'Retratos por la calle', NULL, '', 'Plaza arenal', 18, 1, NULL, NULL, NULL, 'imp', NULL, 'Junto a cartel/tiovivo/luces luminosas', '', '', '', '', '', 'Sony a7IV', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 4, NULL, '2026-11-16 23:39:13', '2026-11-18 22:28:12', '2026-11-17 17:27:08', NULL, NULL, NULL, NULL, NULL),
(45, 5, 4, 'Retratos por la calle', NULL, '', 'Cruce con calle corredera', 13, NULL, NULL, NULL, NULL, 'imp', NULL, 'Business', '', '', '', '', '', 'Sony a7IV', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 5, NULL, '2026-11-17 00:28:43', '2026-11-18 22:28:12', '2026-11-17 17:27:20', NULL, NULL, NULL, NULL, NULL),
(46, 5, 4, 'Retratos por la calle', NULL, '', 'Calle Larga/lenceria', 12, NULL, NULL, NULL, NULL, 'imp', NULL, 'Movimiento lo mismo ', '', '', '', '', '', 'Sony a7IV', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 6, NULL, '2026-11-17 00:34:19', '2026-11-18 22:28:12', '2026-11-17 17:27:29', NULL, NULL, NULL, NULL, NULL),
(47, 5, 12, 'Fiesta nocturna', 'ambas', '', 'Plaza arenal', 18, NULL, 26, 29, 'noche', 'imp', '[\"Copa\",\"Botella de sidra\"]', 'Saliendo de fiesta', '', '', '', '', '', 'Sony a7IV', '16mm', '', '', '', '', '', '', '', '', '', 'Mano', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 22, NULL, '2026-11-17 15:59:12', '2026-11-19 16:09:45', NULL, NULL, NULL, NULL, NULL, NULL),
(48, 5, 4, 'Retratos por la calle', 'ambas', '', 'Calle Larga/lenceria', 12, NULL, 27, 29, 'noche', 'imp', NULL, 'Caminando por la calle', '', '', '', '', '', 'Sony a7IV', '35mm', '', '', '', '', '', '', '', '', '', 'Mano', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 2, NULL, '2026-11-17 15:59:40', '2026-11-19 16:09:36', NULL, NULL, NULL, NULL, NULL, NULL),
(49, 5, 4, 'Retratos por la calle', 'ambas', '', 'Calle Larga/lenceria', 12, NULL, 27, 29, 'dia', 'imp', NULL, 'Caminando mirando el movil', '', '', '', '', '', 'Sony a7IV', '35mm', '1.8', '24', '1/400', '200', '5600', 'no', 'Medio', 'Contrapicado', 'Seguimiento', 'Mano', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 3, NULL, '2026-11-17 17:08:51', '2026-11-19 16:09:36', NULL, NULL, NULL, NULL, NULL, NULL),
(50, 5, 8, 'Business', 'ambas', NULL, 'Cruce con calle corredera', 13, 5, 27, 29, 'dia', 'imp', NULL, 'Mirando el movil con edificio al fondo', NULL, NULL, NULL, NULL, NULL, NULL, '35mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 10, NULL, '2026-11-17 17:25:12', '2026-11-19 23:01:43', NULL, NULL, NULL, NULL, NULL, NULL),
(51, 5, 8, 'Business', 'ambas', '', 'Cruce con calle corredera', 13, NULL, 28, 29, 'dia', 'opc', NULL, 'A través del cristal con colaboracion de una tienda', '', '', '', '', '', 'Sony a7IV', '50mm', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 11, NULL, '2026-11-17 17:25:27', '2026-11-19 17:11:19', NULL, NULL, NULL, NULL, NULL, NULL),
(52, 5, 8, 'Business', 'ambas', '', 'Cruce con calle corredera', 13, NULL, 27, 29, 'dia', 'imp', NULL, 'Mirando a través de las gafas que se vea el edificio', '', '', '', '', '', 'Sony a7IV', '35mm', '', '', '', '', '', '', '', '', '', 'Mano', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 12, NULL, '2026-11-17 17:25:46', '2026-11-19 23:01:40', NULL, NULL, NULL, NULL, NULL, NULL),
(53, 5, 5, 'Compras', 'ambas', NULL, 'Calle Larga/lenceria', 12, NULL, 28, 29, 'dia', 'imp', '[\"Bolsas de papel\"]', 'Mirando escaparates', NULL, NULL, NULL, NULL, NULL, NULL, '50mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 14, NULL, '2026-11-17 20:54:38', '2026-11-19 17:11:19', NULL, NULL, NULL, NULL, NULL, NULL),
(54, 5, 6, 'Angulares', 'ambas', NULL, 'Cruce con calle corredera', 13, 5, 30, 29, 'dia', 'imp', NULL, 'Contrapicado extremo mirando al movil con edificio al fondo', NULL, NULL, NULL, NULL, NULL, NULL, '16mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 9, NULL, '2026-11-17 20:56:11', '2026-11-19 16:09:36', NULL, NULL, NULL, NULL, NULL, NULL),
(55, 5, 7, 'Movil nocturno', 'ambas', NULL, 'Plaza arenal', 18, 1, 28, 29, 'noche', 'imp', '[\"Carcasa de movil\",\"Movil\"]', 'Junto a cartel luminoso', NULL, NULL, NULL, NULL, NULL, NULL, '50mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 18, NULL, '2026-11-17 20:57:08', '2026-11-19 17:11:19', NULL, NULL, NULL, NULL, NULL, NULL),
(56, 5, 7, 'Movil nocturno', 'ambas', '', 'Calle Larga/lenceria', 12, NULL, 28, 29, 'noche', 'imp', '[\"Movil\"]', 'Mirando el movil nocturno con brillo en la cara', '', '', '', '', '', 'Sony a7IV', '50mm', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 19, NULL, '2026-11-17 20:57:29', '2026-11-19 17:11:19', NULL, NULL, NULL, NULL, NULL, NULL),
(57, 5, 7, 'Movil nocturno', 'ambas', NULL, 'Cruce con calle corredera', 13, 5, 28, 29, 'noche', 'imp', '[\"Movil\"]', 'Plano detalle solo manos', NULL, NULL, NULL, NULL, NULL, NULL, '50mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 20, NULL, '2026-11-17 20:59:06', '2026-11-19 17:11:19', NULL, NULL, NULL, NULL, NULL, NULL),
(58, 5, 7, 'Movil nocturno', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'imp', NULL, 'Plano detalle 2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 18, NULL, '2026-11-17 20:59:19', '2026-11-17 20:59:28', '2026-11-17 20:59:28', NULL, NULL, NULL, NULL, NULL),
(59, 5, 7, 'Movil nocturno', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'imp', NULL, 'Plano detalle', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 18, NULL, '2026-11-17 20:59:34', '2026-11-17 20:59:40', '2026-11-17 20:59:40', NULL, NULL, NULL, NULL, NULL),
(60, 5, 11, 'Comida en la mano', 'ambas', NULL, 'Calle Larga/lenceria', 12, NULL, 27, 29, 'noche', 'imp', '[\"Comida dulce\"]', 'Sosteniendo algún postre', NULL, NULL, NULL, NULL, NULL, NULL, '35mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 25, NULL, '2026-11-17 22:09:24', '2026-11-19 16:09:45', NULL, NULL, NULL, NULL, NULL, NULL),
(61, 5, 10, 'Banco o escalera sentada', 'ambas', NULL, 'Banco plaza arenal', 14, NULL, 26, 29, 'noche', 'imp', NULL, 'Sentada mirando a camera flashito', NULL, NULL, NULL, NULL, NULL, NULL, '16mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 16, NULL, '2026-11-17 22:09:43', '2026-11-19 16:09:37', NULL, NULL, NULL, NULL, NULL, NULL),
(62, 5, 10, 'Banco o escalera sentada', 'ambas', NULL, 'Banco plaza arenal', 14, NULL, 26, 29, 'noche', 'imp', NULL, 'Sentada en escalera flashito', NULL, NULL, NULL, NULL, NULL, NULL, '16mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 17, NULL, '2026-11-17 22:10:03', '2026-11-19 16:09:37', NULL, NULL, NULL, NULL, NULL, NULL),
(63, 5, 7, 'Movil nocturno', 'ambas', NULL, 'Plaza arenal', 18, NULL, 28, 29, 'noche', 'imp', '[\"Taza de café de papel\"]', 'Con café en taza de papel', NULL, NULL, NULL, NULL, NULL, NULL, '50mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 21, NULL, '2026-11-17 22:10:45', '2026-11-19 17:11:19', NULL, NULL, NULL, NULL, NULL, NULL),
(64, 5, 9, 'Lo mismo', 'ambas', NULL, 'Calle Larga/lenceria', 12, NULL, 27, 29, 'noche', 'imp', NULL, 'Movimiento lineal', NULL, NULL, NULL, NULL, NULL, NULL, '35mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 24, NULL, '2026-11-17 22:10:58', '2026-11-19 16:09:45', NULL, NULL, NULL, NULL, NULL, NULL),
(65, 5, 9, 'Lo mismo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'imp', NULL, 'Giro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 23, NULL, '2026-11-17 22:11:03', '2026-11-17 22:11:19', '2026-11-17 22:11:19', NULL, NULL, NULL, NULL, NULL),
(66, 5, 13, 'Retratos posados', 'ambas', '', 'Frente gallo azul', 19, NULL, 28, 29, 'dia', 'imp', NULL, 'Mirando a la cámara tranquila', '', '', '', '', '', 'Sony a7IV', '50mm', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'N', '', '', 'N', 'N', '', 1, NULL, '2026-11-17 22:12:51', '2026-11-19 23:02:25', NULL, NULL, NULL, NULL, NULL, NULL),
(67, 5, 4, 'Retratos por la calle', 'ambas', NULL, 'Calle Larga/lenceria', 12, NULL, 27, 29, 'dia', 'imp', NULL, 'Para cruzar un Calle', NULL, NULL, NULL, NULL, NULL, NULL, '35mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 4, NULL, '2026-11-17 22:13:10', '2026-11-19 16:09:36', NULL, NULL, NULL, NULL, NULL, NULL),
(68, 5, 6, 'Angulares', 'ambas', NULL, 'Cruce con calle corredera', 13, 5, 30, NULL, 'noche', 'imp', NULL, 'Contrapicado extremo mirando al movil con edificio al fondo', NULL, NULL, NULL, NULL, NULL, NULL, '16mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 25, NULL, '2026-11-18 13:04:14', '2026-11-19 16:09:23', '2026-11-18 23:11:07', NULL, NULL, NULL, NULL, NULL),
(69, 5, 4, 'Retratos por la calle', 'ambas', NULL, 'Calle Larga/lenceria', 12, NULL, 27, 29, 'noche', 'imp', NULL, 'Caminando mirando el movil', NULL, NULL, NULL, NULL, NULL, NULL, '35mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 5, NULL, '2026-11-18 13:04:14', '2026-11-19 16:09:36', NULL, NULL, NULL, NULL, NULL, NULL),
(70, 5, 4, 'Retratos por la calle', 'ambas', NULL, 'Calle Larga/lenceria', 12, NULL, 27, 29, 'noche', 'imp', NULL, 'Para cruzar un Calle', NULL, NULL, NULL, NULL, NULL, NULL, '35mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 6, NULL, '2026-11-18 13:04:14', '2026-11-19 16:09:36', NULL, NULL, NULL, NULL, NULL, NULL),
(71, 5, 12, 'Fiesta nocturna', 'ambas', NULL, 'Plaza arenal', 18, NULL, 30, 29, 'noche', 'imp', NULL, 'Selfie vista POV', NULL, NULL, NULL, NULL, NULL, NULL, '16mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 23, NULL, '2026-11-18 22:32:30', '2026-11-19 16:09:45', NULL, NULL, NULL, NULL, NULL, NULL),
(72, 5, 4, 'Retratos por la calle', 'ambas', NULL, 'Plaza arenal', 18, NULL, 30, 29, 'noche', 'imp', NULL, 'Quita de la cabeza', NULL, NULL, NULL, NULL, NULL, NULL, '16mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mano', NULL, NULL, NULL, NULL, NULL, 'N', NULL, NULL, 'N', 'N', NULL, 7, NULL, '2026-11-18 22:39:49', '2026-11-19 16:09:36', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rodajes_escena_imagenes`
--

CREATE TABLE `rodajes_escena_imagenes` (
  `id` int(10) UNSIGNED NOT NULL,
  `escena_id` int(10) UNSIGNED NOT NULL,
  `categoria` enum('lugar_objetos','inspiracion') NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rodajes_escena_imagenes`
--

INSERT INTO `rodajes_escena_imagenes` (`id`, `escena_id`, `categoria`, `ruta`, `titulo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'inspiracion', 'images/rodajes/1/1758597437_1f0d82788a46699321c3.png', NULL, '2025-09-23 05:17:17', '2025-09-23 05:17:17', NULL),
(2, 1, 'inspiracion', 'images/rodajes/1/1758597437_f54066bfbf659daf1e13.png', NULL, '2025-09-23 05:17:17', '2025-09-23 05:17:17', NULL),
(3, 1, 'lugar_objetos', 'images/rodajes/1/1758597494_08e0503ede934247a928.png', NULL, '2025-09-23 05:18:14', '2025-09-23 05:18:14', NULL),
(4, 1, 'lugar_objetos', 'images/rodajes/1/1758597494_587cd9d3fd38d78553b5.png', NULL, '2025-09-23 05:18:14', '2025-09-23 05:18:14', NULL),
(5, 2, 'lugar_objetos', 'images/rodajes/2/1758598920_63061cc7c3d3324c01ec.png', NULL, '2025-09-23 05:42:00', '2025-09-23 05:42:00', NULL),
(6, 2, 'lugar_objetos', 'images/rodajes/2/1758598920_4954b96653868c37f8af.png', NULL, '2025-09-23 05:42:00', '2025-09-23 05:42:00', NULL),
(7, 2, 'lugar_objetos', 'images/rodajes/2/1758598920_9fbdbe6b8401e091e2a9.png', NULL, '2025-09-23 05:42:00', '2025-09-23 05:42:00', NULL),
(8, 2, 'lugar_objetos', 'images/rodajes/2/1758598920_0fbba5cc99057721cc8b.png', NULL, '2025-09-23 05:42:00', '2025-09-23 05:42:00', NULL),
(9, 2, 'inspiracion', 'images/rodajes/2/1758598920_c05e2bab6940fefb2fa2.png', NULL, '2025-09-23 05:42:00', '2025-09-23 05:42:00', NULL),
(10, 2, 'inspiracion', 'images/rodajes/2/1758598920_8c2d9b4015590da8d924.png', NULL, '2025-09-23 05:42:00', '2025-09-23 05:42:00', NULL),
(11, 2, 'inspiracion', 'images/rodajes/2/1758598920_66982c9d0a2b63c81839.png', NULL, '2025-09-23 05:42:00', '2025-09-23 05:42:00', NULL),
(12, 3, 'lugar_objetos', 'images/rodajes/3/1758599337_62d16b9157d52f7d7131.png', NULL, '2025-09-23 05:48:57', '2025-09-23 05:48:57', NULL),
(13, 3, 'inspiracion', 'images/rodajes/3/1758599337_90027f792ab810211101.png', NULL, '2025-09-23 05:48:57', '2025-09-23 05:48:57', NULL),
(14, 3, 'inspiracion', 'images/rodajes/3/1758599337_c604818a9886e7094996.png', NULL, '2025-09-23 05:48:57', '2025-09-23 05:48:57', NULL),
(15, 4, 'lugar_objetos', 'images/rodajes/4/1758599812_a0a9818f821b8f5a6d61.png', NULL, '2025-09-23 05:56:52', '2025-09-23 05:56:52', NULL),
(16, 4, 'inspiracion', 'images/rodajes/4/1758599812_4157d8bfc6b2630dc15b.png', NULL, '2025-09-23 05:56:52', '2025-09-23 05:56:52', NULL),
(17, 5, 'lugar_objetos', 'images/rodajes/5/1758600260_dea25d34950f6de2fe81.png', NULL, '2025-09-23 06:04:20', '2025-09-23 06:04:20', NULL),
(18, 6, 'lugar_objetos', 'images/rodajes/6/1758600672_21c4e5d6f845ae294008.png', NULL, '2025-09-23 06:11:12', '2025-09-23 06:11:12', NULL),
(19, 6, 'inspiracion', 'images/rodajes/6/1758600672_f90b4f92ad57d788d5cc.png', NULL, '2025-09-23 06:11:12', '2025-09-23 06:11:12', NULL),
(20, 7, 'lugar_objetos', 'images/rodajes/7/1758601153_b1b96e3692b117f6472e.png', NULL, '2025-09-23 06:19:13', '2025-09-23 06:19:13', NULL),
(21, 7, 'inspiracion', 'images/rodajes/7/1758601153_b45764bb88ec8a075419.jpg', NULL, '2025-09-23 06:19:13', '2025-09-23 06:19:13', NULL),
(22, 7, 'inspiracion', 'images/rodajes/7/1758601153_f4182257ea703c81ded2.jpg', NULL, '2025-09-23 06:19:13', '2025-09-23 06:19:13', NULL),
(23, 8, 'lugar_objetos', 'images/rodajes/8/1758637121_984f184e993be10910eb.png', NULL, '2025-09-23 16:18:41', '2025-09-23 16:18:41', NULL),
(24, 8, 'inspiracion', 'images/rodajes/8/1758637121_45a80bca05e2ce247046.jpg', NULL, '2025-09-23 16:18:41', '2025-09-23 16:18:41', NULL),
(25, 9, 'lugar_objetos', 'images/rodajes/9/1758637432_7d9268df8233b167343b.png', NULL, '2025-09-23 16:23:52', '2025-09-23 16:23:52', NULL),
(26, 9, 'lugar_objetos', 'images/rodajes/9/1758637432_614e65105cd0d77dfd2f.png', NULL, '2025-09-23 16:23:52', '2025-09-23 16:23:52', NULL),
(27, 9, 'lugar_objetos', 'images/rodajes/9/1758637432_5f0e8367934a8ebc52fd.png', NULL, '2025-09-23 16:23:52', '2025-09-23 16:23:52', NULL),
(28, 9, 'inspiracion', 'images/rodajes/9/1758637432_718c02f1f2a5d3488b3e.png', NULL, '2025-09-23 16:23:52', '2025-09-23 16:23:52', NULL),
(29, 9, 'inspiracion', 'images/rodajes/9/1758637432_71506f4ea26c8cd5de5e.png', NULL, '2025-09-23 16:23:52', '2025-09-23 16:23:52', NULL),
(30, 9, 'inspiracion', 'images/rodajes/9/1758637432_84eb48c733dfe2da6ac3.png', NULL, '2025-09-23 16:23:52', '2025-09-23 16:23:52', NULL),
(31, 10, 'lugar_objetos', 'images/rodajes/10/1758645196_f825abe0dd7f51f5cd1a.png', NULL, '2025-09-23 18:33:16', '2025-09-23 18:33:16', NULL),
(32, 10, 'lugar_objetos', 'images/rodajes/10/1758645196_3b2eddd55c47999cfdf9.png', NULL, '2025-09-23 18:33:16', '2025-09-23 18:33:16', NULL),
(33, 11, 'lugar_objetos', 'images/rodajes/11/1758645742_9e2164059d60839b01df.png', NULL, '2025-09-23 18:42:22', '2025-09-23 18:42:22', NULL),
(34, 11, 'inspiracion', 'images/rodajes/11/1758645742_c953d183170a8e11bcce.jpg', NULL, '2025-09-23 18:42:22', '2025-09-23 18:42:22', NULL),
(35, 12, 'lugar_objetos', 'images/rodajes/12/1758646159_dafca643466ebf602392.png', NULL, '2025-09-23 18:49:19', '2025-09-23 18:49:19', NULL),
(36, 12, 'inspiracion', 'images/rodajes/12/1758646159_6751a4a2d8520fcd705f.png', NULL, '2025-09-23 18:49:19', '2025-09-23 18:49:19', NULL),
(37, 12, 'inspiracion', 'images/rodajes/12/1758646159_ff79865b2c4d8588d644.png', NULL, '2025-09-23 18:49:19', '2025-09-23 18:49:19', NULL),
(38, 12, 'inspiracion', 'images/rodajes/12/1758646159_1892fe46a76291a47543.png', NULL, '2025-09-23 18:49:19', '2025-09-23 18:49:19', NULL),
(39, 12, 'inspiracion', 'images/rodajes/12/1758646159_22f70f86161178fdcbbc.png', NULL, '2025-09-23 18:49:19', '2025-09-23 18:49:19', NULL),
(40, 12, 'inspiracion', 'images/rodajes/12/1758646159_1cf0bd0585b2c56ad214.png', NULL, '2025-09-23 18:49:19', '2025-09-23 18:49:19', NULL),
(41, 12, 'inspiracion', 'images/rodajes/12/1758646159_a84c669c197444ed7fbb.png', NULL, '2025-09-23 18:49:19', '2025-09-23 18:49:19', NULL),
(42, 13, 'lugar_objetos', 'images/rodajes/13/1758647877_93eea7f74025935aabf7.png', NULL, '2025-09-23 19:17:57', '2025-09-23 19:17:57', NULL),
(43, 13, 'inspiracion', 'images/rodajes/13/1758647877_14aeeed6d59ccf39e60a.png', NULL, '2025-09-23 19:17:57', '2025-09-23 19:17:57', NULL),
(44, 14, 'lugar_objetos', 'images/rodajes/14/1758648238_2a8d0950aa6a3eaef1ab.png', NULL, '2025-09-23 19:23:58', '2025-09-23 19:23:58', NULL),
(45, 14, 'lugar_objetos', 'images/rodajes/14/1758648238_c1af7341d641614cc559.png', NULL, '2025-09-23 19:23:58', '2025-09-23 19:23:58', NULL),
(46, 14, 'inspiracion', 'images/rodajes/14/1758648238_a84edc61bba9546bf63b.png', NULL, '2025-09-23 19:23:58', '2025-09-23 19:23:58', NULL),
(47, 14, 'inspiracion', 'images/rodajes/14/1758648238_5d994c3d1298e7d2e71f.png', NULL, '2025-09-23 19:23:58', '2025-09-23 19:23:58', NULL),
(48, 14, 'inspiracion', 'images/rodajes/14/1758648238_3d1d7745ae13c545cd12.png', NULL, '2025-09-23 19:23:58', '2025-09-23 19:23:58', NULL),
(49, 15, 'lugar_objetos', 'images/rodajes/15/1758648592_572f72a03a2e74daffd7.png', NULL, '2025-09-23 19:29:52', '2025-09-23 19:29:52', NULL),
(50, 15, 'inspiracion', 'images/rodajes/15/1758648592_dfb60dd04b84d6f7de8c.png', NULL, '2025-09-23 19:29:52', '2025-09-23 19:29:52', NULL),
(51, 15, 'inspiracion', 'images/rodajes/15/1758648592_f7f55e7cbc08ad12c7de.png', NULL, '2025-09-23 19:29:52', '2025-09-23 19:29:52', NULL),
(52, 16, 'lugar_objetos', 'images/rodajes/16/1758648832_f1e5750c436bcbed32ef.png', NULL, '2025-09-23 19:33:52', '2025-09-23 19:33:52', NULL),
(53, 16, 'inspiracion', 'images/rodajes/16/1758648832_0e245462797a14182021.png', NULL, '2025-09-23 19:33:52', '2025-09-23 19:33:52', NULL),
(54, 17, 'lugar_objetos', 'images/rodajes/17/1758649543_ddee4eb459b21ca35441.png', NULL, '2025-09-23 19:45:43', '2025-09-23 19:45:43', NULL),
(55, 18, 'lugar_objetos', 'images/rodajes/18/1758649582_d8a546b8402219347720.png', NULL, '2025-09-23 19:46:22', '2025-09-23 19:46:22', NULL),
(56, 18, 'inspiracion', 'images/rodajes/18/1758649582_8476b596432bff6af9eb.png', NULL, '2025-09-23 19:46:22', '2025-09-23 19:46:22', NULL),
(57, 19, 'lugar_objetos', 'images/rodajes/19/1758649652_a5b7c1c6c7dbe854486a.png', NULL, '2025-09-23 19:47:32', '2025-09-23 19:47:32', NULL),
(58, 19, 'inspiracion', 'images/rodajes/19/1758649652_7d81e3cca966c508b38c.png', NULL, '2025-09-23 19:47:32', '2025-09-23 19:47:32', NULL),
(59, 21, 'lugar_objetos', 'images/rodajes/21/1758649829_3163efb4a5d99aabf833.png', NULL, '2025-09-23 19:50:29', '2025-09-23 19:50:29', NULL),
(60, 21, 'inspiracion', 'images/rodajes/21/1758649829_1ecd5db0240436671f94.png', NULL, '2025-09-23 19:50:29', '2025-09-23 19:50:29', NULL),
(61, 21, 'inspiracion', 'images/rodajes/21/1758649829_f4cd94870e837ff399a9.png', NULL, '2025-09-23 19:50:29', '2025-09-23 19:50:29', NULL),
(62, 21, 'inspiracion', 'images/rodajes/21/1758649829_8831f2a0081c62dc1290.png', NULL, '2025-09-23 19:50:29', '2025-09-23 19:50:29', NULL),
(63, 20, 'lugar_objetos', 'images/rodajes/20/1758649964_53b6838bdd037debade6.png', NULL, '2025-09-23 19:52:44', '2025-09-23 19:52:44', NULL),
(64, 20, 'inspiracion', 'images/rodajes/20/1758649964_2100f7dfcdebb50de2c7.jpg', NULL, '2025-09-23 19:52:44', '2025-09-24 01:35:54', '2025-09-24 01:35:54'),
(65, 20, 'inspiracion', 'images/rodajes/20/1758649964_6e2318877cdc4a4b6dd6.jpg', NULL, '2025-09-23 19:52:44', '2025-09-24 01:35:57', '2025-09-24 01:35:57'),
(66, 20, 'inspiracion', 'images/rodajes/20/1758650021_103a56be80c8baa90866.png', NULL, '2025-09-23 19:53:41', '2025-09-23 19:53:41', NULL),
(67, 20, 'inspiracion', 'images/rodajes/20/1758650021_cb0e0f523b3982f5e984.png', NULL, '2025-09-23 19:53:41', '2025-09-23 19:53:41', NULL),
(68, 22, 'lugar_objetos', 'images/rodajes/22/1758650087_bd095fe60edf09f91398.png', NULL, '2025-09-23 19:54:47', '2025-09-23 19:54:47', NULL),
(69, 22, 'inspiracion', 'images/rodajes/22/1758650087_04bbe3fe4c23fd821634.jpg', NULL, '2025-09-23 19:54:47', '2025-09-23 19:54:47', NULL),
(70, 23, 'lugar_objetos', 'images/rodajes/23/1758650193_4b9225f5dce572218756.png', NULL, '2025-09-23 19:56:33', '2025-09-23 19:56:33', NULL),
(71, 23, 'inspiracion', 'images/rodajes/23/1758650193_fda83f0a9f15262239f3.png', NULL, '2025-09-23 19:56:33', '2025-09-23 19:56:33', NULL),
(72, 23, 'inspiracion', 'images/rodajes/23/1758650193_d06a99b069266e137681.png', NULL, '2025-09-23 19:56:33', '2025-09-23 19:56:33', NULL),
(73, 23, 'inspiracion', 'images/rodajes/23/1758650193_30d26e6d0da30fda5e2b.png', NULL, '2025-09-23 19:56:33', '2025-09-23 19:56:33', NULL),
(74, 23, 'inspiracion', 'images/rodajes/23/1758650193_9e08bb85ec20ff1be17c.jpg', NULL, '2025-09-23 19:56:33', '2025-09-23 19:56:33', NULL),
(75, 5, 'inspiracion', 'images/rodajes/5/1758662293_09bed89d0667d6993eb6.jpg', NULL, '2025-09-23 23:18:13', '2025-09-23 23:18:13', NULL),
(76, 17, 'inspiracion', 'images/rodajes/17/1758670430_a61620be96829aa4a8c4.png', NULL, '2025-09-24 01:33:50', '2025-09-24 01:33:50', NULL),
(77, 20, 'inspiracion', 'images/rodajes/20/1758670582_c838836578704e43c86f.jpg', NULL, '2025-09-24 01:36:22', '2025-09-24 01:36:22', NULL),
(78, 20, 'inspiracion', 'images/rodajes/20/1758670582_e530cd34308da8ac1b4b.jpg', NULL, '2025-09-24 01:36:22', '2025-09-24 01:36:22', NULL),
(79, 10, 'inspiracion', 'images/rodajes/10/1758673010_e7a768d4d94f6563835f.png', NULL, '2025-09-24 02:16:50', '2025-09-24 02:16:50', NULL),
(80, 10, 'inspiracion', 'images/rodajes/10/1758673010_73f5982a2d963201351c.png', NULL, '2025-09-24 02:16:50', '2025-09-24 02:16:50', NULL),
(81, 12, 'lugar_objetos', 'images/rodajes/12/1759549835_76d82b89746e850ca330.jpg', NULL, '2025-10-04 05:50:35', '2025-10-04 05:50:35', NULL),
(82, 24, 'inspiracion', 'images/rodajes/24/1759845195_f3c6638faeef2e3ad572.jpg', NULL, '2025-10-07 15:53:15', '2025-10-07 15:53:15', NULL),
(83, 24, 'inspiracion', 'images/rodajes/24/1759845195_e8cf7513f2c71097be3b.jpg', NULL, '2025-10-07 15:53:15', '2025-10-07 15:53:15', NULL),
(84, 24, 'inspiracion', 'images/rodajes/24/1759845195_c7fd12cc7af2036417b0.jpg', NULL, '2025-10-07 15:53:15', '2025-10-07 15:53:15', NULL),
(85, 24, 'lugar_objetos', 'images/rodajes/24/1759845271_9f060cf2a2e2ca1134cb.png', NULL, '2025-10-07 15:54:31', '2025-10-07 15:54:31', NULL),
(86, 25, 'inspiracion', 'images/rodajes/25/1759845547_62da6cd0c3284cf6b9e0.jpg', NULL, '2025-10-07 15:59:07', '2025-10-07 15:59:07', NULL),
(87, 25, 'inspiracion', 'images/rodajes/25/1759845547_0e7ced82eae8b19fa7c3.jpg', NULL, '2025-10-07 15:59:07', '2025-10-07 15:59:07', NULL),
(88, 25, 'inspiracion', 'images/rodajes/25/1759845547_8c30533d3fea534476db.jpg', NULL, '2025-10-07 15:59:07', '2025-10-07 15:59:07', NULL),
(89, 25, 'inspiracion', 'images/rodajes/25/1759845547_30d478102686f513c7d1.jpg', NULL, '2025-10-07 15:59:07', '2025-10-07 15:59:07', NULL),
(90, 25, 'lugar_objetos', 'images/rodajes/25/1759845579_36fef7b6d9b012e96bd9.png', NULL, '2025-10-07 15:59:39', '2025-10-07 15:59:39', NULL),
(91, 26, 'inspiracion', 'images/rodajes/26/1760973221_ebd105848c8b7674bc4f.jpg', NULL, '2025-10-20 17:13:41', '2025-10-20 17:13:41', NULL),
(92, 26, 'inspiracion', 'images/rodajes/26/1760973221_75cf1bded7b4c6f64292.jpg', NULL, '2025-10-20 17:13:41', '2025-10-20 17:13:41', NULL),
(93, 26, 'inspiracion', 'images/rodajes/26/1760973221_9bed2e399e2b57fc6c71.jpg', NULL, '2025-10-20 17:13:41', '2025-10-20 17:13:41', NULL),
(94, 26, 'inspiracion', 'images/rodajes/26/1760973221_16de210a621e8c1cbe2f.jpg', NULL, '2025-10-20 17:13:41', '2025-10-20 17:13:41', NULL),
(95, 28, 'inspiracion', 'images/rodajes/28/1760973249_14d5ddc62adedc23a399.jpg', NULL, '2025-10-20 17:14:09', '2025-10-20 17:14:09', NULL),
(96, 28, 'inspiracion', 'images/rodajes/28/1760973249_dd7a10152e1b07eb924c.jpg', NULL, '2025-10-20 17:14:09', '2025-10-20 17:14:09', NULL),
(97, 28, 'inspiracion', 'images/rodajes/28/1760973249_e30d69cc75564a32c99a.jpg', NULL, '2025-10-20 17:14:09', '2025-10-20 17:14:09', NULL),
(98, 28, 'inspiracion', 'images/rodajes/28/1760973249_4b2d3c24e58541e371ba.jpg', NULL, '2025-10-20 17:14:09', '2025-10-20 17:14:09', NULL),
(99, 28, 'inspiracion', 'images/rodajes/28/1760973249_7b7ef19989c25e69ee9b.jpg', NULL, '2025-10-20 17:14:09', '2025-10-20 17:14:09', NULL),
(100, 28, 'inspiracion', 'images/rodajes/28/1760973249_45adc609d0e023f142ee.jpg', NULL, '2025-10-20 17:14:09', '2025-10-20 17:14:09', NULL),
(101, 27, 'inspiracion', 'images/rodajes/27/1760973262_5f0dd9fc4e21e9a776c6.jpg', NULL, '2025-10-20 17:14:22', '2025-10-20 17:14:22', NULL),
(102, 27, 'inspiracion', 'images/rodajes/27/1760973262_0d41391bfef32a4b1897.jpg', NULL, '2025-10-20 17:14:22', '2025-10-20 17:14:22', NULL),
(103, 27, 'inspiracion', 'images/rodajes/27/1760973262_998c2b2746b57f33e3b6.jpg', NULL, '2025-10-20 17:14:22', '2025-10-20 17:14:22', NULL),
(104, 29, 'inspiracion', 'images/rodajes/29/1760973274_dc558ef6884a5be8940b.jpg', NULL, '2025-10-20 17:14:34', '2025-10-20 17:14:34', NULL),
(105, 29, 'inspiracion', 'images/rodajes/29/1760973274_26f280863a50aba3bae8.jpg', NULL, '2025-10-20 17:14:34', '2025-10-20 17:14:34', NULL),
(106, 29, 'inspiracion', 'images/rodajes/29/1760973274_a29d95d63fb914b27aa3.jpg', NULL, '2025-10-20 17:14:34', '2025-10-20 17:14:34', NULL),
(107, 29, 'inspiracion', 'images/rodajes/29/1760973274_74465b6cfaacb14623fd.jpg', NULL, '2025-10-20 17:14:34', '2025-10-20 17:14:34', NULL),
(108, 30, 'inspiracion', 'images/rodajes/30/1760973286_cea4e5bfc642e004b5e2.jpg', NULL, '2025-10-20 17:14:46', '2025-10-20 17:14:46', NULL),
(109, 30, 'inspiracion', 'images/rodajes/30/1760973286_67e14ca7659a77d8abc2.jpg', NULL, '2025-10-20 17:14:46', '2025-10-20 17:14:46', NULL),
(110, 30, 'inspiracion', 'images/rodajes/30/1760973286_136dce7f9ccdfc5a7af7.jpg', NULL, '2025-10-20 17:14:46', '2025-10-20 17:14:46', NULL),
(111, 30, 'inspiracion', 'images/rodajes/30/1760973286_9c72feecf2b6078844ac.jpg', NULL, '2025-10-20 17:14:46', '2025-10-20 17:14:46', NULL),
(112, 31, 'inspiracion', 'images/rodajes/31/1760973307_18f3b30a5b9beeb48cdb.jpg', NULL, '2025-10-20 17:15:07', '2025-10-20 17:15:07', NULL),
(113, 32, 'inspiracion', 'images/rodajes/32/1770441657_350bd3f2479a517eef5f.jpg', NULL, '2026-02-07 06:20:57', '2026-02-07 06:20:57', NULL),
(114, 33, 'inspiracion', 'images/rodajes/33/1770441822_9e0943eb3b25e5332528.jpg', NULL, '2026-02-07 06:23:42', '2026-02-12 05:21:15', '2026-02-12 05:21:15'),
(115, 33, 'inspiracion', 'images/rodajes/33/1770870085_afbdfebdd98d214fb2fe.jpg', NULL, '2026-02-12 05:21:25', '2026-02-12 05:22:02', '2026-02-12 05:22:02'),
(116, 34, 'inspiracion', 'images/rodajes/34/1770870108_e13b9ea22fedccc7564b.jpg', NULL, '2026-02-12 05:21:48', '2026-02-12 05:21:48', NULL),
(117, 33, 'inspiracion', 'images/rodajes/33/1770870118_b02f62198c95a881f7f1.jpg', NULL, '2026-02-12 05:21:58', '2026-02-12 05:21:58', NULL),
(118, 35, 'inspiracion', 'images/rodajes/35/1770870157_a3c7950091cc0b1484e1.jpg', NULL, '2026-02-12 05:22:37', '2026-02-12 05:22:37', NULL),
(119, 36, 'inspiracion', 'images/rodajes/36/1770870174_805171ec192be432cbee.jpg', NULL, '2026-02-12 05:22:54', '2026-02-12 05:22:54', NULL),
(120, 37, 'inspiracion', 'images/rodajes/37/1770870188_47d17a2af107ee635439.jpg', NULL, '2026-02-12 05:23:08', '2026-02-12 05:23:08', NULL),
(121, 38, 'inspiracion', 'images/rodajes/38/1770870200_5d8b16ab67a329afa5cc.jpg', NULL, '2026-02-12 05:23:20', '2026-02-12 05:23:20', NULL),
(122, 32, 'inspiracion', 'images/rodajes/32/1771184374_f59deaa8d9821bfe9b27.mp4', NULL, '2026-02-15 20:39:34', '2026-02-15 20:39:34', NULL),
(123, 33, 'inspiracion', 'images/rodajes/33/1771184425_615cccc9d51664410f15.mp4', NULL, '2026-02-15 20:40:25', '2026-02-17 06:04:16', '2026-02-17 06:04:16'),
(124, 34, 'inspiracion', 'images/rodajes/34/1771184437_b9121767e7cc9ea88b1e.mp4', NULL, '2026-02-15 20:40:37', '2026-02-17 06:03:49', '2026-02-17 06:03:49'),
(125, 35, 'inspiracion', 'images/rodajes/35/1771184444_b778893f2f056fa46c3a.mp4', NULL, '2026-02-15 20:40:44', '2026-02-17 06:00:58', '2026-02-17 06:00:58'),
(126, 36, 'inspiracion', 'images/rodajes/36/1771184453_bd91d679e8eab9584055.mp4', NULL, '2026-02-15 20:40:53', '2026-02-15 20:40:53', NULL),
(127, 37, 'inspiracion', 'images/rodajes/37/1771184463_68a2221744cfe9e24d76.mp4', NULL, '2026-02-15 20:41:03', '2026-02-15 20:41:03', NULL),
(128, 38, 'inspiracion', 'images/rodajes/38/1771184470_15a4a6f60cc0a5fe8a56.mp4', NULL, '2026-02-15 20:41:10', '2026-02-15 20:41:10', NULL),
(129, 35, 'inspiracion', 'images/rodajes/35/1771304470_706538351f8a1cc9f1e5.mp4', NULL, '2026-02-17 06:01:10', '2026-02-17 06:01:10', NULL),
(130, 34, 'inspiracion', 'images/rodajes/34/1771304645_dbcf438f6bd6e3716ac2.mp4', NULL, '2026-02-17 06:04:05', '2026-02-17 06:04:05', NULL),
(131, 33, 'inspiracion', 'images/rodajes/33/1771304662_2b4148ca13abcfc0cb2c.mp4', NULL, '2026-02-17 06:04:22', '2026-02-17 06:04:22', NULL),
(132, 32, 'lugar_objetos', 'images/rodajes/32/1771356437_7aeebeff51cfc85a5cb6.jpg', NULL, '2026-02-17 20:27:17', '2026-02-17 20:27:17', NULL),
(133, 33, 'lugar_objetos', 'images/rodajes/33/1771356444_aa378b5a6e34f92fe87a.jpg', NULL, '2026-02-17 20:27:24', '2026-02-17 20:27:24', NULL),
(134, 34, 'lugar_objetos', 'images/rodajes/34/1771356452_d567ea61a7edb1082772.jpg', NULL, '2026-02-17 20:27:32', '2026-02-17 20:27:32', NULL),
(135, 35, 'lugar_objetos', 'images/rodajes/35/1771356459_5a539ac24225279c1bc6.jpg', NULL, '2026-02-17 20:27:39', '2026-02-17 20:27:39', NULL),
(136, 36, 'lugar_objetos', 'images/rodajes/36/1771356466_c1c8a2af08186ee548c5.jpg', NULL, '2026-02-17 20:27:46', '2026-02-17 20:27:46', NULL),
(137, 37, 'lugar_objetos', 'images/rodajes/37/1771356473_be9207f1b8fd3042befa.jpg', NULL, '2026-02-17 20:27:53', '2026-02-17 20:27:53', NULL),
(138, 38, 'lugar_objetos', 'images/rodajes/38/1771356480_bbfbb94bc1c1511d8b3f.jpg', NULL, '2026-02-17 20:28:00', '2026-02-17 20:28:00', NULL),
(139, 68, 'inspiracion', 'images/rodajes/68/dd1cab362e40_1794927266_3f1e72cb16ac5df269b5.png', 'Referencia (de la toma de día)', '2026-11-18 13:04:14', '2026-11-18 13:04:14', NULL),
(140, 69, 'inspiracion', 'images/rodajes/69/b1ccc514e6a7_1794927257_7d5f21dc2eeb2c716490.png', 'Referencia (de la toma de día)', '2026-11-18 13:04:14', '2026-11-18 13:04:14', NULL),
(141, 70, 'inspiracion', 'images/rodajes/70/063c91c796ab_1794927326_2781cea441c9df9cac47.png', 'Referencia (de la toma de día)', '2026-11-18 13:04:14', '2026-11-18 13:04:14', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rodajes_pizarra_orden`
--

CREATE TABLE `rodajes_pizarra_orden` (
  `id` int(10) UNSIGNED NOT NULL,
  `escena_id` int(10) UNSIGNED NOT NULL,
  `vista` varchar(20) NOT NULL,
  `posicion` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rodajes_pizarra_orden`
--

INSERT INTO `rodajes_pizarra_orden` (`id`, `escena_id`, `vista`, `posicion`) VALUES
(3, 39, 'montaje', 1),
(4, 47, 'ubicacion', 1),
(5, 49, 'ubicacion', 2),
(6, 50, 'ubicacion', 3),
(7, 51, 'ubicacion', 4),
(8, 52, 'ubicacion', 5),
(9, 53, 'ubicacion', 6),
(10, 54, 'ubicacion', 7),
(11, 55, 'ubicacion', 8),
(12, 56, 'ubicacion', 9),
(13, 57, 'ubicacion', 10),
(14, 39, 'ubicacion', 11),
(15, 43, 'ubicacion', 12),
(16, 48, 'ubicacion', 13),
(17, 41, 'ubicacion', 14),
(18, 42, 'ubicacion', 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rodajes_proyectos`
--

CREATE TABLE `rodajes_proyectos` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `codigo` varchar(60) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `sesion_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rodajes_proyectos`
--

INSERT INTO `rodajes_proyectos` (`id`, `titulo`, `codigo`, `descripcion`, `sesion_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'La antigua', NULL, 'Video promocional de la cafetería', NULL, '2025-09-23 05:07:12', '2025-09-23 05:07:12', NULL),
(3, 'Retrato mama', 'Mama', 'BRIEF\r\nIluminación Luz natural de ventana + Stripbox o softbox + stripbox\r\nVestido de color llamativo y con estampado y/o blanco\r\nFondo en una esquina de la habitación junto a la ventana\r\nFondo de un color, llevar fondo rosa\r\nButacón como escenario y buscar una silla con respaldar\r\n35mm + 50mm', NULL, '2025-10-20 02:21:35', '2025-10-20 17:10:54', NULL),
(4, 'Reel Regalos Playmobil', 'Hermana', 'Reel para promocionar regalos personalizados de playmobil\r\n', NULL, '2026-02-07 06:14:43', '2026-02-16 05:43:35', NULL),
(5, 'Mujer en la calle', NULL, 'Especial esfuerzo en las fotos nocturnas de mirando al movil y paparazzi.', 6, '2026-11-15 01:41:25', '2026-11-18 22:25:25', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rodajes_puntos`
--

CREATE TABLE `rodajes_puntos` (
  `id` int(11) NOT NULL,
  `ubicacion_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `x` decimal(5,2) DEFAULT NULL,
  `y` decimal(5,2) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rodajes_puntos`
--

INSERT INTO `rodajes_puntos` (`id`, `ubicacion_id`, `nombre`, `x`, `y`, `orden`) VALUES
(1, 18, 'Cartel luiminoso', NULL, NULL, 0),
(2, 18, 'Banco', NULL, NULL, 1),
(4, 13, 'Cajero', NULL, NULL, 0),
(5, 13, 'Edifico de cristalera', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rodajes_setups`
--

CREATE TABLE `rodajes_setups` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` varchar(5) DEFAULT NULL,
  `optica` varchar(60) DEFAULT NULL,
  `soporte` varchar(30) DEFAULT NULL,
  `apertura` varchar(30) DEFAULT NULL,
  `velocidad` varchar(30) DEFAULT NULL,
  `iso` varchar(30) DEFAULT NULL,
  `fps` varchar(10) DEFAULT NULL,
  `nd` varchar(30) DEFAULT NULL,
  `luz` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rodajes_setups`
--

INSERT INTO `rodajes_setups` (`id`, `proyecto_id`, `nombre`, `tipo`, `optica`, `soporte`, `apertura`, `velocidad`, `iso`, `fps`, `nd`, `luz`, `notas`, `imagen`, `orden`) VALUES
(1, 1, '20mm · Gimbal · 50 fps', NULL, '20mm', 'Gimbal', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 0),
(2, 1, '20mm y 35mm · Mano · 50 fps', NULL, '20mm y 35mm', 'Mano', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 1),
(3, 1, '35mm · Mano · 25 fps', NULL, '35mm', 'Mano', NULL, NULL, NULL, '25', NULL, NULL, NULL, NULL, 2),
(4, 1, '50mm · Trípode · 50 fps', NULL, '50mm', 'Trípode', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 3),
(5, 1, '35mm · Trípode · 25 fps', NULL, '35mm', 'Trípode', NULL, NULL, NULL, '25', NULL, NULL, NULL, NULL, 4),
(6, 1, '35mm · Otro · 50 fps', NULL, '35mm', 'Otro', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 5),
(7, 1, '20mm · Trípode · 25 fps', NULL, '20mm', 'Trípode', NULL, NULL, NULL, '25', NULL, NULL, NULL, NULL, 6),
(8, 1, '50mm · Trípode · 25 fps', NULL, '50mm', 'Trípode', NULL, NULL, NULL, '25', NULL, NULL, NULL, NULL, 7),
(9, 1, '35mm · Mano · 50 fps', NULL, '35mm', 'Mano', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 8),
(10, 1, '35mm o 50mm · Mano · 25 fps', NULL, '35mm o 50mm', 'Mano', NULL, NULL, NULL, '25', NULL, NULL, NULL, NULL, 9),
(11, 1, '50mm · Otro · 50 fps', NULL, '50mm', 'Otro', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 10),
(12, 1, '50mm · Mano · 50 fps', NULL, '50mm', 'Mano', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 11),
(13, 1, '35mm o 20mm · Trípode · foto fija', NULL, '35mm o 20mm', 'Trípode', NULL, NULL, NULL, 'foto fija', NULL, NULL, NULL, NULL, 12),
(14, 1, '20mm · Mano · 50 fps', NULL, '20mm', 'Mano', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 13),
(15, 1, '20mm o 35mm · Otro · 50 fps', NULL, '20mm o 35mm', 'Otro', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 14),
(16, 1, '35mm o 50mm · Mano · 50 fps', NULL, '35mm o 50mm', 'Mano', NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, 15),
(17, 1, '50mm · Mano · 50fps', NULL, '50mm', 'Mano', NULL, NULL, NULL, '50fps', NULL, NULL, NULL, NULL, 16),
(18, 1, 'Trípode', NULL, NULL, 'Trípode', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 17),
(19, 3, '50mm · Trípode', NULL, '50mm', 'Trípode', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(20, 3, 'Trípode', NULL, NULL, 'Trípode', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(21, 4, '16mm · Mano · 24 fps', NULL, '16mm', 'Mano', NULL, NULL, NULL, '24', NULL, NULL, NULL, NULL, 0),
(22, 4, '50mm · Trípode · 24 fps', NULL, '50mm', 'Trípode', NULL, NULL, NULL, '24', NULL, NULL, NULL, NULL, 1),
(23, 4, '35mm · Otro · 24 fps', NULL, '35mm', 'Otro', NULL, NULL, NULL, '24', NULL, NULL, NULL, NULL, 2),
(24, 4, '50mm · Mano · 24 fps', NULL, '50mm', 'Mano', NULL, NULL, NULL, '24', NULL, NULL, NULL, NULL, 3),
(25, 4, '35mm · Mano · 24 fps', NULL, '35mm', 'Mano', NULL, NULL, NULL, '24', NULL, NULL, NULL, NULL, 4),
(26, 5, 'Flashito 16mm', 'foto', '16mm', 'Mano', NULL, NULL, NULL, NULL, NULL, 'Flash', 'Flashito', NULL, 0),
(27, 5, '35mm plano medio', 'foto', '35mm', 'Mano', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(28, 5, '50mm retratero', 'foto', '50mm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(29, 5, '35mm vespid +  Foco', 'video', '35mm Vespid', 'Mano', '1.8', '1/50', '4000', '25', 'NO', 'Foco cuadrado al lado de la cámara', NULL, NULL, 3),
(30, 5, '16mm angular', 'foto', '16mm', 'Mano', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rodajes_ubicaciones`
--

CREATE TABLE `rodajes_ubicaciones` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `notas` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `orden_setups` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rodajes_ubicaciones`
--

INSERT INTO `rodajes_ubicaciones` (`id`, `proyecto_id`, `nombre`, `notas`, `imagen`, `orden`, `orden_setups`) VALUES
(1, 1, 'En el exterior', NULL, NULL, 0, NULL),
(2, 1, 'En el interior y exterior', NULL, NULL, 1, NULL),
(3, 1, 'Exterior', NULL, NULL, 2, NULL),
(4, 1, 'Interior', NULL, NULL, 3, NULL),
(5, 1, 'En el interior', NULL, NULL, 4, NULL),
(6, 3, 'Pintura', NULL, NULL, 0, NULL),
(7, 4, 'En el taller', NULL, NULL, 0, NULL),
(8, 4, 'Ordenador', NULL, NULL, 1, NULL),
(9, 4, 'En la mesa', NULL, NULL, 2, NULL),
(10, 4, 'Mesa', NULL, NULL, 3, NULL),
(11, 4, 'Junto a estanteria o algo elevado', NULL, NULL, 4, NULL),
(12, 5, 'Calle Larga/lenceria', NULL, NULL, 1, '[27,28,26,29,30]'),
(13, 5, 'Cruce con calle corredera', NULL, NULL, 2, NULL),
(14, 5, 'Banco plaza arenal', NULL, NULL, 3, NULL),
(18, 5, 'Plaza arenal', NULL, NULL, 4, NULL),
(19, 5, 'Frente gallo azul', NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rodajes_variantes`
--

CREATE TABLE `rodajes_variantes` (
  `id` int(11) NOT NULL,
  `escena_id` int(11) NOT NULL,
  `texto` varchar(255) NOT NULL,
  `prioridad` varchar(3) NOT NULL DEFAULT 'imp',
  `props` text DEFAULT NULL,
  `moodboard_item_id` int(11) DEFAULT NULL,
  `toma_estado` varchar(12) DEFAULT NULL,
  `toma_estado_at` datetime DEFAULT NULL,
  `toma_estado_foto` varchar(12) DEFAULT NULL,
  `toma_estado_foto_at` datetime DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rodajes_variantes`
--

INSERT INTO `rodajes_variantes` (`id`, `escena_id`, `texto`, `prioridad`, `props`, `moodboard_item_id`, `toma_estado`, `toma_estado_at`, `toma_estado_foto`, `toma_estado_foto_at`, `orden`) VALUES
(8, 42, 'Con el porattil', 'imp', '[\"Tablet\"]', 23, NULL, NULL, NULL, NULL, 0),
(9, 41, 'Por la espalda', 'imp', NULL, 7, NULL, NULL, NULL, NULL, 0),
(10, 41, 'Plano corto a la tarjeta', 'imp', NULL, NULL, NULL, NULL, NULL, NULL, 1),
(11, 43, 'Sonrisa volviendose', 'imp', NULL, 45, NULL, NULL, NULL, NULL, 1),
(12, 54, 'Con el cielo solo', 'imp', NULL, 14, NULL, NULL, NULL, NULL, 1),
(13, 54, 'Con las manos levantadas', 'imp', NULL, 13, NULL, NULL, NULL, NULL, 3),
(14, 54, 'Del otro lado solo cielo', 'imp', NULL, 16, NULL, NULL, NULL, NULL, 2),
(16, 54, 'Apoyada a la pared', 'imp', NULL, 15, NULL, NULL, NULL, NULL, 4),
(17, 55, 'Apoyado en la pared', 'imp', NULL, 32, NULL, NULL, NULL, NULL, 0),
(18, 56, 'Mirando a cámara', 'imp', NULL, 19, NULL, NULL, NULL, NULL, 0),
(19, 56, 'Del otro lado y con edificios al fondo', 'imp', NULL, 21, NULL, NULL, NULL, NULL, 1),
(20, 56, 'De frente', 'imp', NULL, 25, NULL, NULL, NULL, NULL, 2),
(23, 56, 'Plano abierto', 'imp', NULL, 31, NULL, NULL, NULL, NULL, 3),
(24, 56, 'De lateral', 'imp', NULL, 34, NULL, NULL, NULL, NULL, 4),
(27, 57, 'Plano detalle 2', 'imp', NULL, 30, NULL, NULL, NULL, NULL, 0),
(28, 57, 'Plano detalle', 'imp', '[\"Tablet\"]', 26, NULL, NULL, NULL, NULL, 1),
(29, 57, 'Plano a solo la pantalla', 'imp', NULL, 29, NULL, NULL, NULL, NULL, 2),
(30, 55, 'Apoyado en la pared toma lateral', 'imp', NULL, 48, NULL, NULL, NULL, NULL, 1),
(31, 42, 'Toma del movil', 'imp', NULL, 10, NULL, NULL, NULL, NULL, 1),
(32, 42, 'Desde atrás', 'imp', NULL, 40, NULL, NULL, NULL, NULL, 2),
(33, 64, 'Giro', 'imp', NULL, 5, NULL, NULL, NULL, NULL, 0),
(34, 54, 'Con la mano en el pecho', 'imp', NULL, 17, NULL, NULL, NULL, NULL, 0),
(35, 48, 'De espaldas', 'imp', NULL, 27, NULL, NULL, NULL, NULL, 1),
(36, 48, 'De espaldas plano abierto', 'imp', NULL, 28, NULL, NULL, NULL, NULL, 2),
(37, 49, 'Ángulo bajo', 'imp', NULL, 33, NULL, NULL, NULL, NULL, 0),
(38, 48, 'Alegre', 'imp', NULL, 37, NULL, NULL, NULL, NULL, 0),
(39, 49, 'Tomándose un selfie', 'imp', NULL, 38, NULL, NULL, NULL, NULL, 2),
(40, 49, 'Desde el costado', 'imp', NULL, 49, NULL, NULL, NULL, NULL, 1),
(46, 69, 'Ángulo bajo', 'imp', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(47, 69, 'Desde el costado', 'imp', NULL, NULL, NULL, NULL, NULL, NULL, 1),
(48, 69, 'Tomándose un selfie', 'imp', NULL, NULL, NULL, NULL, NULL, NULL, 2),
(49, 49, 'Variante', 'imp', NULL, 53, NULL, NULL, NULL, NULL, 3),
(50, 66, 'Variante', 'imp', '[\"Gafas naranjas\"]', 54, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `estado_foto` enum('idea','planificacion','edicion','subiendo','completado') DEFAULT NULL,
  `estado_video` enum('idea','planificacion','edicion','subiendo','completado') DEFAULT NULL,
  `pausada` tinyint(1) DEFAULT 0,
  `entrega_modelos` enum('no_aplica','pendiente','entregado') DEFAULT 'no_aplica',
  `fecha_sesion` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `briefing` text DEFAULT NULL,
  `creada_at` datetime DEFAULT NULL,
  `actualizada_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id`, `titulo`, `estado_foto`, `estado_video`, `pausada`, `entrega_modelos`, `fecha_sesion`, `notas`, `briefing`, `creada_at`, `actualizada_at`) VALUES
(1, 'Danza con Jeno ', 'subiendo', 'edicion', 0, 'entregado', '2026-06-19', '', '', '2026-07-28 07:03:08', '2026-07-30 00:47:10'),
(2, 'Business con Yors', 'subiendo', 'subiendo', 0, 'entregado', '2025-07-05', 'Sesion de negocios y cafeteria con Yors', '', '2026-07-28 15:25:58', '2026-08-30 06:28:21'),
(3, 'Supermercado', 'idea', 'idea', 0, 'no_aplica', NULL, '', '', '2026-07-30 00:48:07', '2026-07-30 00:48:07'),
(4, 'Documental ntwe', 'planificacion', 'planificacion', 0, 'no_aplica', '2026-10-01', '', '', '2026-07-30 00:48:52', '2026-07-30 00:48:52'),
(5, 'Sarah ', 'idea', 'idea', 0, 'no_aplica', NULL, '', 'Estilo como de Juansuponatime', '2026-08-06 03:37:45', '2026-08-06 03:37:45'),
(6, 'Mujer en la calle', 'planificacion', 'planificacion', 0, 'pendiente', '2026-11-21', 'Especial esfuerzo en las fotos nocturnas de mirando al movil y paparazzi.', '<p>Retratar a una mujer real, con estilo y seguridad, disfrutando del centro de Jerez cuando cae la noche.</p><p><br></p><p>Paseando, mirando escaparates, consultando el móvil, tomando algo, riéndose. Momentos naturales, nada de poses forzadas.</p><p><br></p><p>Luces cálidas, calles con vida y una protagonista con vitalidad</p><p><br></p><p><strong>Cómo será la sesión</strong></p><p><br></p><p>Duración: Entre una hora y hora y media.</p><p>Empezar antes del atardecer y aprovechar ambos ambientes de luz.</p><p><br></p><p><strong>Vestuario:</strong></p><p><br></p><p>Un único look base, variedad con capas.</p><p><br></p><p>Vaquero o pantalón oscuro y una prenda lisa, sin logotipos ni estampados llamativos. </p><p><br></p><p>Oscuro abajo y claro arriba tonos marrones con algún color</p><p>llamativo azul, rojo, verde.</p><p><br></p><ul><li>Bufandas y pañuelos</li><li>Chaqueta, gabardina o abrigo</li><li>Gorro, bolso, gafas</li><li>Si tienes alguna prenda favorita, ¡tráela!</li></ul><p><br></p><p>Maquillaje y peinado: naturales, como para salir a</p><p>dar una vuelta.</p><p><br></p><p><strong>Cómo será la sesión</strong></p><p><br></p><p>Localización: En el centro de Jerez, zona de plaza arenal, calle lancería, pescadería vieja, etc.</p><p><br></p><p><strong>Escenas:</strong></p><p><br></p><ul><li>De compras: paseando con bolsas, mirando unescaparate, saliendo de una tienda con una sonrisa.</li></ul><p><br></p><ul><li>Con el móvil: consultando un mensaje, haciendo una foto de la calle, buscando un sitio en el mapa o hablando por teléfono mientras caminas.</li></ul><p><br></p><ul><li>Tomando algo: café, picando algo, o simplementesentada viendo pasar a la gente.</li></ul><p><br></p><ul><li>Riendo y disfrutando: caminando con el abrigo alviento, ajustándose la bufanda, mirando haciaarriba a las luces o riéndote de algo espontáneo.</li></ul>', '2026-11-02 15:44:24', '2026-11-18 22:25:35'),
(7, 'Escalada', 'subiendo', 'subiendo', 0, 'no_aplica', '2024-03-08', NULL, NULL, '2026-11-15 01:14:43', '2026-11-15 01:14:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesion_equipo`
--

CREATE TABLE `sesion_equipo` (
  `id` int(11) NOT NULL,
  `sesion_id` int(11) NOT NULL,
  `item` varchar(150) NOT NULL,
  `tipo` varchar(10) NOT NULL DEFAULT 'equipo',
  `marcado` tinyint(1) NOT NULL DEFAULT 0,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesion_equipo`
--

INSERT INTO `sesion_equipo` (`id`, `sesion_id`, `item`, `tipo`, `marcado`, `orden`) VALUES
(1, 6, 'Sony a7III', 'equipo', 0, 0),
(2, 6, 'Sony a7IV', 'equipo', 0, 1),
(3, 6, '35mm', 'equipo', 0, 2),
(4, 6, '50mm', 'equipo', 0, 3),
(5, 6, '16mm', 'equipo', 0, 4),
(6, 6, '35mm cine', 'equipo', 0, 5),
(7, 6, 'Flash encima de cámara', 'equipo', 0, 6),
(8, 6, 'Baterías', 'equipo', 0, 7),
(9, 6, 'SD', 'equipo', 0, 8),
(10, 6, 'Cargar baterías', 'equipo', 0, 9),
(11, 6, 'Borrar SD', 'equipo', 0, 10),
(12, 6, 'Cargar flash', 'equipo', 0, 11),
(13, 6, 'Probar \"lo mismo\" con flash', 'equipo', 0, 12),
(14, 6, 'Botella de sidra', 'props', 0, 13),
(15, 6, 'Copa', 'props', 0, 14),
(16, 6, 'Carcasa de movil', 'props', 0, 15),
(17, 6, 'Gafas naranjas', 'props', 0, 16),
(18, 6, 'Tarjeta crédito falsa 💳', 'props', 0, 17),
(19, 6, 'Bolsas de papel', 'props', 0, 18),
(20, 6, 'Taza de café de papel', 'props', 0, 19),
(21, 6, 'Comida dulce', 'props', 0, 20),
(22, 6, 'Movil', 'props', 0, 999),
(23, 6, 'Tablet', 'props', 0, 999);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesion_historial_estados`
--

CREATE TABLE `sesion_historial_estados` (
  `id` int(11) NOT NULL,
  `sesion_id` int(11) NOT NULL,
  `parte` enum('foto','video') NOT NULL,
  `estado` enum('idea','planificacion','edicion','subiendo','completado') DEFAULT NULL,
  `cambiado_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesion_historial_estados`
--

INSERT INTO `sesion_historial_estados` (`id`, `sesion_id`, `parte`, `estado`, `cambiado_at`) VALUES
(1, 1, 'foto', 'subiendo', '2026-07-28 07:03:17'),
(2, 1, 'video', 'subiendo', '2026-07-28 07:03:20'),
(3, 1, 'video', 'edicion', '2026-07-28 07:03:22'),
(4, 2, 'foto', 'subiendo', '2026-07-28 15:26:07'),
(5, 2, 'video', 'subiendo', '2026-07-28 15:26:09'),
(6, 2, 'video', 'subiendo', '2026-08-30 06:28:21'),
(7, 7, 'foto', 'edicion', '2026-11-15 01:14:45'),
(8, 7, 'foto', 'subiendo', '2026-11-15 01:14:46'),
(9, 7, 'video', 'subiendo', '2026-11-15 01:14:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesion_mensajes_modelo`
--

CREATE TABLE `sesion_mensajes_modelo` (
  `id` int(11) NOT NULL,
  `sesion_id` int(11) NOT NULL,
  `model_release_id` int(11) DEFAULT NULL,
  `nombre_modelo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `creado_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `rodajes_escenas`
--
ALTER TABLE `rodajes_escenas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proyecto_id` (`proyecto_id`),
  ADD KEY `idx_orden` (`orden`),
  ADD KEY `idx_rodajes_escenas_situacion` (`situacion_id`);

--
-- Indices de la tabla `rodajes_escena_imagenes`
--
ALTER TABLE `rodajes_escena_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_escena_id` (`escena_id`),
  ADD KEY `idx_categoria` (`categoria`);

--
-- Indices de la tabla `rodajes_pizarra_orden`
--
ALTER TABLE `rodajes_pizarra_orden`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `escena_id_vista` (`escena_id`,`vista`);

--
-- Indices de la tabla `rodajes_proyectos`
--
ALTER TABLE `rodajes_proyectos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_codigo` (`codigo`),
  ADD KEY `idx_rodajes_proyectos_sesion` (`sesion_id`);

--
-- Indices de la tabla `rodajes_puntos`
--
ALTER TABLE `rodajes_puntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ubicacion_id` (`ubicacion_id`);

--
-- Indices de la tabla `rodajes_setups`
--
ALTER TABLE `rodajes_setups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proyecto_id` (`proyecto_id`);

--
-- Indices de la tabla `rodajes_ubicaciones`
--
ALTER TABLE `rodajes_ubicaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proyecto_id` (`proyecto_id`);

--
-- Indices de la tabla `rodajes_variantes`
--
ALTER TABLE `rodajes_variantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escena_id` (`escena_id`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sesion_equipo`
--
ALTER TABLE `sesion_equipo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sesion_equipo_sesion_id_foreign` (`sesion_id`);

--
-- Indices de la tabla `sesion_historial_estados`
--
ALTER TABLE `sesion_historial_estados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sesion_historial_estados_sesion_id_foreign` (`sesion_id`);

--
-- Indices de la tabla `sesion_mensajes_modelo`
--
ALTER TABLE `sesion_mensajes_modelo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sesion_mensajes_modelo_sesion_id_foreign` (`sesion_id`),
  ADD KEY `sesion_mensajes_modelo_model_release_id_foreign` (`model_release_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `rodajes_escenas`
--
ALTER TABLE `rodajes_escenas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT de la tabla `rodajes_escena_imagenes`
--
ALTER TABLE `rodajes_escena_imagenes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT de la tabla `rodajes_pizarra_orden`
--
ALTER TABLE `rodajes_pizarra_orden`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `rodajes_proyectos`
--
ALTER TABLE `rodajes_proyectos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `rodajes_puntos`
--
ALTER TABLE `rodajes_puntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `rodajes_setups`
--
ALTER TABLE `rodajes_setups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `rodajes_ubicaciones`
--
ALTER TABLE `rodajes_ubicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `rodajes_variantes`
--
ALTER TABLE `rodajes_variantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `sesion_equipo`
--
ALTER TABLE `sesion_equipo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `sesion_historial_estados`
--
ALTER TABLE `sesion_historial_estados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `sesion_mensajes_modelo`
--
ALTER TABLE `sesion_mensajes_modelo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `rodajes_escenas`
--
ALTER TABLE `rodajes_escenas`
  ADD CONSTRAINT `fk_escena_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `rodajes_proyectos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rodajes_escena_imagenes`
--
ALTER TABLE `rodajes_escena_imagenes`
  ADD CONSTRAINT `fk_img_escena` FOREIGN KEY (`escena_id`) REFERENCES `rodajes_escenas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesion_equipo`
--
ALTER TABLE `sesion_equipo`
  ADD CONSTRAINT `sesion_equipo_sesion_id_foreign` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesion_historial_estados`
--
ALTER TABLE `sesion_historial_estados`
  ADD CONSTRAINT `sesion_historial_estados_sesion_id_foreign` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesion_mensajes_modelo`
--
ALTER TABLE `sesion_mensajes_modelo`
  ADD CONSTRAINT `sesion_mensajes_modelo_model_release_id_foreign` FOREIGN KEY (`model_release_id`) REFERENCES `model_releases` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sesion_mensajes_modelo_sesion_id_foreign` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
