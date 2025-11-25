-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-11-2025 a las 02:23:28
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
-- Base de datos: `gapc_system`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL,
  `reunion_id` int(11) NOT NULL,
  `miembro_ciclo_id` int(11) NOT NULL,
  `estado` enum('PRESENTE','AUSENTE','PERMISO') DEFAULT 'AUSENTE',
  `justificacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo_cargos`
--

CREATE TABLE `catalogo_cargos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `es_directiva` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo_multas`
--

CREATE TABLE `catalogo_multas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `monto_defecto` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciclo`
--

CREATE TABLE `ciclo` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `nombre` varchar(200) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin_estimada` date NOT NULL,
  `duracion` int(11) DEFAULT NULL,
  `tasa_interes_mensual` decimal(5,2) DEFAULT 0.00,
  `estado` enum('ACTIVO','CERRADO','LIQUIDADO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_multas_ciclo`
--

CREATE TABLE `configuracion_multas_ciclo` (
  `id` int(11) NOT NULL,
  `ciclo_id` int(11) NOT NULL,
  `catalogo_multa_id` int(11) NOT NULL,
  `monto_aplicar` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deuda_multa`
--

CREATE TABLE `deuda_multa` (
  `id` int(11) NOT NULL,
  `miembro_ciclo_id` int(11) NOT NULL,
  `reunion_generacion_id` int(11) NOT NULL,
  `reunion_pago_id` int(11) DEFAULT NULL,
  `catalogo_multa_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` enum('PENDIENTE','PAGADA','CONDONADA') DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distrito`
--

CREATE TABLE `distrito` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo`
--

CREATE TABLE `grupo` (
  `id` int(11) NOT NULL,
  `distrito_id` int(11) NOT NULL,
  `promotora_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `fecha_creacion` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `miembro_ciclo`
--

CREATE TABLE `miembro_ciclo` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ciclo_id` int(11) NOT NULL,
  `cargo_id` int(11) NOT NULL,
  `saldo_ahorros` decimal(10,2) DEFAULT 0.00,
  `fecha_ingreso` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo`
--

CREATE TABLE `prestamo` (
  `id` int(11) NOT NULL,
  `miembro_ciclo_id` int(11) NOT NULL,
  `reunion_solicitud_id` int(11) NOT NULL,
  `monto_aprobado` decimal(10,2) NOT NULL,
  `plazo_meses` int(11) NOT NULL,
  `tasa_interes` decimal(5,2) NOT NULL,
  `monto_interes_fijo_mensual` decimal(10,2) NOT NULL,
  `fecha_aprobacion` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('PENDIENTE','ACTIVO','FINALIZADO','MORA') DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reunion`
--

CREATE TABLE `reunion` (
  `id` int(11) NOT NULL,
  `ciclo_id` int(11) NOT NULL,
  `numero_reunion` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('PROGRAMADA','ABIERTA','CERRADA') DEFAULT 'PROGRAMADA',
  `saldo_caja_inicial` decimal(10,2) DEFAULT 0.00,
  `saldo_caja_actual` decimal(10,2) DEFAULT 0.00,
  `total_entradas` decimal(10,2) DEFAULT 0.00,
  `total_salidas` decimal(10,2) DEFAULT 0.00,
  `saldo_fisico_actual` decimal(10,2) DEFAULT 0.00,
  `acta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transaccion_caja`
--

CREATE TABLE `transaccion_caja` (
  `id` int(11) NOT NULL,
  `reunion_id` int(11) NOT NULL,
  `miembro_ciclo_id` int(11) DEFAULT NULL,
  `prestamo_id` int(11) DEFAULT NULL,
  `deuda_multa_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('AHORRO','PAGO_PRESTAMO_CAPITAL','PAGO_PRESTAMO_INTERES','PAGO_MULTA','INGRESO_EXTRA','RETIRO_AHORRO','DESEMBOLSO_PRESTAMO','GASTO_OPERATIVO') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `nombre_completo` varchar(200) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `dui` varchar(9) DEFAULT NULL,
  `telefono` varchar(8) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_asistencia_reunion` (`reunion_id`),
  ADD KEY `fk_asistencia_miembro` (`miembro_ciclo_id`);

--
-- Indices de la tabla `catalogo_cargos`
--
ALTER TABLE `catalogo_cargos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `catalogo_multas`
--
ALTER TABLE `catalogo_multas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ciclo`
--
ALTER TABLE `ciclo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ciclo_grupo` (`grupo_id`);

--
-- Indices de la tabla `configuracion_multas_ciclo`
--
ALTER TABLE `configuracion_multas_ciclo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conf_ciclo` (`ciclo_id`),
  ADD KEY `fk_conf_multa` (`catalogo_multa_id`);

--
-- Indices de la tabla `deuda_multa`
--
ALTER TABLE `deuda_multa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_deuda_miembro` (`miembro_ciclo_id`),
  ADD KEY `fk_deuda_reunion` (`reunion_generacion_id`),
  ADD KEY `fk_deuda_catalogo` (`catalogo_multa_id`),
  ADD KEY `fk_deuda_pago` (`reunion_pago_id`);

--
-- Indices de la tabla `distrito`
--
ALTER TABLE `distrito`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `grupo`
--
ALTER TABLE `grupo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_grupo_distrito` (`distrito_id`),
  ADD KEY `fk_grupo_promotora` (`promotora_id`);

--
-- Indices de la tabla `miembro_ciclo`
--
ALTER TABLE `miembro_ciclo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_miembro_usuario` (`usuario_id`),
  ADD KEY `fk_miembro_ciclo` (`ciclo_id`),
  ADD KEY `fk_miembro_cargo` (`cargo_id`);

--
-- Indices de la tabla `prestamo`
--
ALTER TABLE `prestamo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prestamo_miembro` (`miembro_ciclo_id`),
  ADD KEY `fk_prestamo_reunion` (`reunion_solicitud_id`);

--
-- Indices de la tabla `reunion`
--
ALTER TABLE `reunion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reunion_ciclo` (`ciclo_id`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `transaccion_caja`
--
ALTER TABLE `transaccion_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trans_reunion` (`reunion_id`),
  ADD KEY `fk_trans_miembro` (`miembro_ciclo_id`),
  ADD KEY `fk_trans_prestamo` (`prestamo_id`),
  ADD KEY `fk_trans_multa` (`deuda_multa_id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuario_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `catalogo_cargos`
--
ALTER TABLE `catalogo_cargos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `catalogo_multas`
--
ALTER TABLE `catalogo_multas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ciclo`
--
ALTER TABLE `ciclo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_multas_ciclo`
--
ALTER TABLE `configuracion_multas_ciclo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `deuda_multa`
--
ALTER TABLE `deuda_multa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `distrito`
--
ALTER TABLE `distrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupo`
--
ALTER TABLE `grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `miembro_ciclo`
--
ALTER TABLE `miembro_ciclo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prestamo`
--
ALTER TABLE `prestamo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reunion`
--
ALTER TABLE `reunion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `transaccion_caja`
--
ALTER TABLE `transaccion_caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `fk_asistencia_miembro` FOREIGN KEY (`miembro_ciclo_id`) REFERENCES `miembro_ciclo` (`id`),
  ADD CONSTRAINT `fk_asistencia_reunion` FOREIGN KEY (`reunion_id`) REFERENCES `reunion` (`id`);

--
-- Filtros para la tabla `ciclo`
--
ALTER TABLE `ciclo`
  ADD CONSTRAINT `fk_ciclo_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupo` (`id`);

--
-- Filtros para la tabla `configuracion_multas_ciclo`
--
ALTER TABLE `configuracion_multas_ciclo`
  ADD CONSTRAINT `fk_conf_ciclo` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclo` (`id`),
  ADD CONSTRAINT `fk_conf_multa` FOREIGN KEY (`catalogo_multa_id`) REFERENCES `catalogo_multas` (`id`);

--
-- Filtros para la tabla `deuda_multa`
--
ALTER TABLE `deuda_multa`
  ADD CONSTRAINT `fk_deuda_catalogo` FOREIGN KEY (`catalogo_multa_id`) REFERENCES `catalogo_multas` (`id`),
  ADD CONSTRAINT `fk_deuda_miembro` FOREIGN KEY (`miembro_ciclo_id`) REFERENCES `miembro_ciclo` (`id`),
  ADD CONSTRAINT `fk_deuda_pago` FOREIGN KEY (`reunion_pago_id`) REFERENCES `reunion` (`id`),
  ADD CONSTRAINT `fk_deuda_reunion` FOREIGN KEY (`reunion_generacion_id`) REFERENCES `reunion` (`id`);

--
-- Filtros para la tabla `grupo`
--
ALTER TABLE `grupo`
  ADD CONSTRAINT `fk_grupo_distrito` FOREIGN KEY (`distrito_id`) REFERENCES `distrito` (`id`),
  ADD CONSTRAINT `fk_grupo_promotora` FOREIGN KEY (`promotora_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `miembro_ciclo`
--
ALTER TABLE `miembro_ciclo`
  ADD CONSTRAINT `fk_miembro_cargo` FOREIGN KEY (`cargo_id`) REFERENCES `catalogo_cargos` (`id`),
  ADD CONSTRAINT `fk_miembro_ciclo` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclo` (`id`),
  ADD CONSTRAINT `fk_miembro_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `prestamo`
--
ALTER TABLE `prestamo`
  ADD CONSTRAINT `fk_prestamo_miembro` FOREIGN KEY (`miembro_ciclo_id`) REFERENCES `miembro_ciclo` (`id`),
  ADD CONSTRAINT `fk_prestamo_reunion` FOREIGN KEY (`reunion_solicitud_id`) REFERENCES `reunion` (`id`);

--
-- Filtros para la tabla `reunion`
--
ALTER TABLE `reunion`
  ADD CONSTRAINT `fk_reunion_ciclo` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclo` (`id`);

--
-- Filtros para la tabla `transaccion_caja`
--
ALTER TABLE `transaccion_caja`
  ADD CONSTRAINT `fk_trans_miembro` FOREIGN KEY (`miembro_ciclo_id`) REFERENCES `miembro_ciclo` (`id`),
  ADD CONSTRAINT `fk_trans_multa` FOREIGN KEY (`deuda_multa_id`) REFERENCES `deuda_multa` (`id`),
  ADD CONSTRAINT `fk_trans_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamo` (`id`),
  ADD CONSTRAINT `fk_trans_reunion` FOREIGN KEY (`reunion_id`) REFERENCES `reunion` (`id`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
