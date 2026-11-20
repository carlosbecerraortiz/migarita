-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-11-2026 a las 04:19:39
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
-- Base de datos: `migarita`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora_login`
--

CREATE TABLE `bitacora_login` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL,
  `navegador` varchar(250) DEFAULT NULL,
  `sistema_operativo` varchar(100) DEFAULT NULL,
  `exito` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `bitacora_login`
--

INSERT INTO `bitacora_login` (`id`, `id_usuario`, `fecha`, `ip`, `navegador`, `sistema_operativo`, `exito`) VALUES
(1, 1, '2026-10-29 09:09:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'Windows', 0),
(2, 1, '2026-10-29 09:12:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'Windows', 1),
(3, 1, '2026-10-29 10:07:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'Windows', 1),
(4, 1, '2026-11-19 20:59:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', 'Windows', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `cedula` varchar(20) NOT NULL CHECK (`cedula` regexp '^[0-9]+$'),
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL CHECK (`telefono` regexp '^[0-9]+$'),
  `correo` varchar(120) DEFAULT NULL CHECK (`correo` like '%@%'),
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `id_tipo_cliente` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `cedula`, `nombre`, `apellido`, `telefono`, `correo`, `fecha_registro`, `id_tipo_cliente`) VALUES
(1, '1065872906', 'Carlos', 'Becerra', '3105536166', 'carlosbortiz30@gmail.com', '2026-06-09 21:20:20', 1),
(2, '1067923485', 'Valentina', 'Suárez', '3112223344', 'valentina.suarez@unicesar.edu.co', '2026-06-09 21:20:20', 1),
(3, '1064751839', 'Santiago', 'Molina', '3123334455', 'santiago.molina@unicesar.edu.co', '2026-06-09 21:20:20', 1),
(4, '1066482917', 'Daniela', 'Vargas', '3134445566', 'daniela.vargas@unicesar.edu.co', '2026-06-09 21:20:20', 2),
(5, '1063598274', 'Camilo', 'Ríos', '3145556677', 'camilo.rios@unicesar.edu.co', '2026-06-09 21:20:20', 3),
(6, '1068274531', 'Paola', 'Mendoza', '3156667788', 'paola.mendoza@unicesar.edu.co', '2026-06-09 21:20:20', 1),
(7, '1065319847', 'Felipe', 'Guerrero', '3167778899', 'felipe.guerrero@unicesar.edu.co', '2026-06-09 21:20:20', 4),
(8, '1067845293', 'Natalia', 'Pineda', '3178889900', 'natalia.pineda@unicesar.edu.co', '2026-06-09 21:20:20', 2),
(9, '1064923786', 'Sebastián', 'Carrillo', '3189990011', 'sebastian.carrillo@unicesar.edu.co', '2026-06-09 21:20:20', 1),
(10, '1066138459', 'Mariana', 'Ospina', '3190001122', 'mariana.ospina@unicesar.edu.co', '2026-06-09 21:20:20', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deudas`
--

CREATE TABLE `deudas` (
  `id` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL CHECK (`monto` <> 0),
  `fecha` datetime DEFAULT current_timestamp(),
  `id_metodo_pago` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `deudas`
--

INSERT INTO `deudas` (`id`, `id_cliente`, `id_usuario`, `descripcion`, `monto`, `fecha`, `id_metodo_pago`) VALUES
(1, 1, 1, 'Empanada', 2500.00, '2026-06-09 21:21:39', NULL),
(2, 2, 1, 'Combo corriente', 5000.00, '2026-06-09 21:21:39', NULL),
(3, 3, 1, 'Jugo natural', 3500.00, '2026-06-09 21:21:39', NULL),
(4, 4, 1, 'Dedito de queso', 1000.00, '2026-06-09 21:21:39', NULL),
(5, 5, 1, 'Combo corriente', 7500.00, '2026-06-09 21:21:39', NULL),
(6, 6, 1, 'Dona', 1500.00, '2026-06-09 21:21:39', NULL),
(7, 7, 1, 'Empanada', 2500.00, '2026-06-09 21:21:39', NULL),
(8, 8, 1, 'Combo corriente', 5000.00, '2026-06-09 21:21:39', NULL),
(9, 9, 1, 'Jugo natural', 3500.00, '2026-06-09 21:21:39', NULL),
(10, 10, 1, 'Café', 2000.00, '2026-06-09 21:21:39', NULL),
(11, 1, 1, 'Empanada', 1000.00, '2026-10-29 09:13:46', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `metodos_pago`
--

INSERT INTO `metodos_pago` (`id`, `nombre`) VALUES
(1, 'efectivo'),
(2, 'transferencia');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_cliente`
--

CREATE TABLE `tipos_cliente` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `tipos_cliente`
--

INSERT INTO `tipos_cliente` (`id`, `nombre`) VALUES
(2, 'administrativo'),
(4, 'egresado'),
(1, 'estudiante'),
(5, 'externo'),
(3, 'profesor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `cedula` varchar(20) NOT NULL CHECK (`cedula` regexp '^[0-9]+$'),
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(120) NOT NULL CHECK (`correo` like '%@%'),
  `usuario` varchar(50) NOT NULL CHECK (`usuario` <> ''),
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('superusuario','usuario') NOT NULL DEFAULT 'usuario',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `cedula`, `nombre`, `apellido`, `correo`, `usuario`, `password_hash`, `rol`, `fecha_registro`) VALUES
(1, '1065872906', 'Carlos Alberto', 'Becerra Ortiz', 'carlosalbertobecerraortiz30@gmail.com', 'carlos', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'superusuario', '2026-06-09 21:17:30');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_saldos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_saldos` (
`id` int(11)
,`cedula` varchar(20)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`telefono` varchar(20)
,`correo` varchar(120)
,`tipo_cliente` varchar(50)
,`saldo` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_saldos`
--
DROP TABLE IF EXISTS `vista_saldos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_saldos`  AS SELECT `c`.`id` AS `id`, `c`.`cedula` AS `cedula`, `c`.`nombre` AS `nombre`, `c`.`apellido` AS `apellido`, `c`.`telefono` AS `telefono`, `c`.`correo` AS `correo`, `tc`.`nombre` AS `tipo_cliente`, coalesce(sum(`d`.`monto`),0) AS `saldo` FROM ((`clientes` `c` left join `deudas` `d` on(`d`.`id_cliente` = `c`.`id`)) left join `tipos_cliente` `tc` on(`tc`.`id` = `c`.`id_tipo_cliente`)) GROUP BY `c`.`id` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora_login`
--
ALTER TABLE `bitacora_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bitacora_usuario` (`id_usuario`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `fk_tipo_cliente` (`id_tipo_cliente`);

--
-- Indices de la tabla `deudas`
--
ALTER TABLE `deudas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_deudas_cliente` (`id_cliente`),
  ADD KEY `fk_deudas_usuario` (`id_usuario`),
  ADD KEY `fk_metodo_pago` (`id_metodo_pago`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipos_cliente`
--
ALTER TABLE `tipos_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora_login`
--
ALTER TABLE `bitacora_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `deudas`
--
ALTER TABLE `deudas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tipos_cliente`
--
ALTER TABLE `tipos_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora_login`
--
ALTER TABLE `bitacora_login`
  ADD CONSTRAINT `fk_bitacora_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_tipo_cliente` FOREIGN KEY (`id_tipo_cliente`) REFERENCES `tipos_cliente` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `deudas`
--
ALTER TABLE `deudas`
  ADD CONSTRAINT `deudas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_deudas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_metodo_pago` FOREIGN KEY (`id_metodo_pago`) REFERENCES `metodos_pago` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
