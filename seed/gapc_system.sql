-- 1. CREACIÓN DE LA BASE DE DATOS
CREATE DATABASE IF NOT EXISTS gapc_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gapc_system;

-- Desactivar chequeo de llaves foráneas temporalmente para evitar errores al crear
SET FOREIGN_KEY_CHECKS=0;

-- 2. TABLAS DE CATÁLOGOS Y SISTEMA
CREATE TABLE Distrito (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(150) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Rol (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  descripcion TEXT,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Catalogo_Cargos (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  es_directiva TINYINT(1) DEFAULT 0, -- 1=Directiva, 0=Miembro
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Catalogo_Multas (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(200) NOT NULL,
  monto_defecto DECIMAL(10,2) DEFAULT 0.00,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABLAS DE USUARIOS Y GRUPOS
CREATE TABLE Usuario (
  id INT(11) NOT NULL AUTO_INCREMENT,
  rol_id INT(11) NOT NULL,
  nombre_completo VARCHAR(200) NOT NULL,
  email VARCHAR(100) DEFAULT NULL,
  password VARCHAR(255) DEFAULT NULL,
  dui VARCHAR(20) DEFAULT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  direccion TEXT DEFAULT NULL,
  estado ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  PRIMARY KEY (id),
  KEY fk_usuario_rol (rol_id),
  CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES Rol (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Grupo (
  id INT(11) NOT NULL AUTO_INCREMENT,
  distrito_id INT(11) NOT NULL,
  promotora_id INT(11) NOT NULL,
  nombre VARCHAR(200) NOT NULL,
  fecha_creacion DATE DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_grupo_distrito (distrito_id),
  KEY fk_grupo_promotora (promotora_id),
  CONSTRAINT fk_grupo_distrito FOREIGN KEY (distrito_id) REFERENCES Distrito (id),
  CONSTRAINT fk_grupo_promotora FOREIGN KEY (promotora_id) REFERENCES Usuario (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABLAS OPERATIVAS DEL CICLO
CREATE TABLE Ciclo (
  id INT(11) NOT NULL AUTO_INCREMENT,
  grupo_id INT(11) NOT NULL,
  nombre VARCHAR(200) DEFAULT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin_estimada DATE NOT NULL,
  duracion INT(11) DEFAULT NULL, -- Meses
  tasa_interes_mensual DECIMAL(5,2) DEFAULT 0.00,
  estado ENUM('ACTIVO','CERRADO','LIQUIDADO') DEFAULT 'ACTIVO',
  PRIMARY KEY (id),
  KEY fk_ciclo_grupo (grupo_id),
  CONSTRAINT fk_ciclo_grupo FOREIGN KEY (grupo_id) REFERENCES Grupo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Configuracion_Multas_Ciclo (
  id INT(11) NOT NULL AUTO_INCREMENT,
  ciclo_id INT(11) NOT NULL,
  catalogo_multa_id INT(11) NOT NULL,
  monto_aplicar DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY fk_conf_ciclo (ciclo_id),
  KEY fk_conf_multa (catalogo_multa_id),
  CONSTRAINT fk_conf_ciclo FOREIGN KEY (ciclo_id) REFERENCES Ciclo (id),
  CONSTRAINT fk_conf_multa FOREIGN KEY (catalogo_multa_id) REFERENCES Catalogo_Multas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Miembro_Ciclo (
  id INT(11) NOT NULL AUTO_INCREMENT,
  usuario_id INT(11) NOT NULL,
  ciclo_id INT(11) NOT NULL,
  cargo_id INT(11) NOT NULL,
  saldo_ahorros DECIMAL(10,2) DEFAULT 0.00,
  fecha_ingreso DATE DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_miembro_usuario (usuario_id),
  KEY fk_miembro_ciclo (ciclo_id),
  KEY fk_miembro_cargo (cargo_id),
  CONSTRAINT fk_miembro_usuario FOREIGN KEY (usuario_id) REFERENCES Usuario (id),
  CONSTRAINT fk_miembro_ciclo FOREIGN KEY (ciclo_id) REFERENCES Ciclo (id),
  CONSTRAINT fk_miembro_cargo FOREIGN KEY (cargo_id) REFERENCES Catalogo_Cargos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABLAS DE REUNIONES Y TRANSACCIONES
CREATE TABLE Reunion (
  id INT(11) NOT NULL AUTO_INCREMENT,
  ciclo_id INT(11) NOT NULL,
  numero_reunion INT(11) NOT NULL,
  fecha DATE NOT NULL,
  estado ENUM('PROGRAMADA','ABIERTA','CERRADA') DEFAULT 'PROGRAMADA',
  saldo_caja_inicial DECIMAL(10,2) DEFAULT 0.00,
  saldo_caja_actual DECIMAL(10,2) DEFAULT 0.00,
  total_entradas DECIMAL(10,2) DEFAULT 0.00,
  total_salidas DECIMAL(10,2) DEFAULT 0.00,
  saldo_fisico_actual DECIMAL(10,2) DEFAULT 0.00,
  acta TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_reunion_ciclo (ciclo_id),
  CONSTRAINT fk_reunion_ciclo FOREIGN KEY (ciclo_id) REFERENCES Ciclo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Asistencia (
  id INT(11) NOT NULL AUTO_INCREMENT,
  reunion_id INT(11) NOT NULL,
  miembro_ciclo_id INT(11) NOT NULL,
  estado ENUM('PRESENTE','AUSENTE','PERMISO') DEFAULT 'AUSENTE',
  justificacion VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_asistencia_reunion (reunion_id),
  KEY fk_asistencia_miembro (miembro_ciclo_id),
  CONSTRAINT fk_asistencia_reunion FOREIGN KEY (reunion_id) REFERENCES Reunion (id),
  CONSTRAINT fk_asistencia_miembro FOREIGN KEY (miembro_ciclo_id) REFERENCES Miembro_Ciclo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Deuda_Multa (
  id INT(11) NOT NULL AUTO_INCREMENT,
  miembro_ciclo_id INT(11) NOT NULL,
  reunion_generacion_id INT(11) NOT NULL,
  reunion_pago_id INT(11) DEFAULT NULL, -- Columna agregada para registro de pago
  catalogo_multa_id INT(11) NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  estado ENUM('PENDIENTE','PAGADA','CONDONADA') DEFAULT 'PENDIENTE',
  PRIMARY KEY (id),
  KEY fk_deuda_miembro (miembro_ciclo_id),
  KEY fk_deuda_reunion_gen (reunion_generacion_id),
  KEY fk_deuda_reunion_pago (reunion_pago_id),
  KEY fk_deuda_catalogo (catalogo_multa_id),
  CONSTRAINT fk_deuda_catalogo FOREIGN KEY (catalogo_multa_id) REFERENCES Catalogo_Multas (id),
  CONSTRAINT fk_deuda_miembro FOREIGN KEY (miembro_ciclo_id) REFERENCES Miembro_Ciclo (id),
  CONSTRAINT fk_deuda_reunion_gen FOREIGN KEY (reunion_generacion_id) REFERENCES Reunion (id),
  CONSTRAINT fk_deuda_reunion_pago FOREIGN KEY (reunion_pago_id) REFERENCES Reunion (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Prestamo (
  id INT(11) NOT NULL AUTO_INCREMENT,
  miembro_ciclo_id INT(11) NOT NULL,
  reunion_solicitud_id INT(11) NOT NULL,
  monto_aprobado DECIMAL(10,2) NOT NULL,
  plazo_meses INT(11) NOT NULL,
  tasa_interes DECIMAL(5,2) NOT NULL,
  monto_interes_fijo_mensual DECIMAL(10,2) NOT NULL,
  fecha_aprobacion DATE DEFAULT NULL,
  fecha_vencimiento DATE DEFAULT NULL,
  estado ENUM('PENDIENTE','ACTIVO','FINALIZADO','MORA') DEFAULT 'PENDIENTE',
  PRIMARY KEY (id),
  KEY fk_prestamo_miembro (miembro_ciclo_id),
  KEY fk_prestamo_reunion (reunion_solicitud_id),
  CONSTRAINT fk_prestamo_miembro FOREIGN KEY (miembro_ciclo_id) REFERENCES Miembro_Ciclo (id),
  CONSTRAINT fk_prestamo_reunion FOREIGN KEY (reunion_solicitud_id) REFERENCES Reunion (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Transaccion_Caja (
  id INT(11) NOT NULL AUTO_INCREMENT,
  reunion_id INT(11) NOT NULL,
  miembro_ciclo_id INT(11) DEFAULT NULL,
  prestamo_id INT(11) DEFAULT NULL,
  deuda_multa_id INT(11) DEFAULT NULL,
  tipo_movimiento ENUM('AHORRO','PAGO_PRESTAMO_CAPITAL','PAGO_PRESTAMO_INTERES','PAGO_MULTA','INGRESO_EXTRA','RETIRO_AHORRO','DESEMBOLSO_PRESTAMO','GASTO_OPERATIVO') NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  observacion VARCHAR(255) DEFAULT NULL,
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_trans_reunion (reunion_id),
  KEY fk_trans_miembro (miembro_ciclo_id),
  KEY fk_trans_prestamo (prestamo_id),
  KEY fk_trans_multa (deuda_multa_id),
  CONSTRAINT fk_trans_multa FOREIGN KEY (deuda_multa_id) REFERENCES Deuda_Multa (id),
  CONSTRAINT fk_trans_miembro FOREIGN KEY (miembro_ciclo_id) REFERENCES Miembro_Ciclo (id),
  CONSTRAINT fk_trans_prestamo FOREIGN KEY (prestamo_id) REFERENCES Prestamo (id),
  CONSTRAINT fk_trans_reunion FOREIGN KEY (reunion_id) REFERENCES Reunion (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1; 
COMMIT;