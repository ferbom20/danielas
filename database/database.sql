-- =========================================================
-- SISTEMA DE CONTROL DE ESTACIONAMIENTO RESIDENCIAL
-- Base de datos: estacionamiento_db
-- =========================================================

CREATE DATABASE IF NOT EXISTS estacionamiento_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE estacionamiento_db;

-- ---------------------------------------------------------
-- TABLA: usuarios (encargados de garita / administradores)
-- ---------------------------------------------------------
CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin','garita') NOT NULL DEFAULT 'garita',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABLA: torres
-- ---------------------------------------------------------
CREATE TABLE torres (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABLA: puestos (46 en total, 10 de visitantes)
-- ---------------------------------------------------------
CREATE TABLE puestos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(10) NOT NULL UNIQUE,
    tipo ENUM('residente','visitante') NOT NULL DEFAULT 'residente',
    estado ENUM('disponible','ocupado','mercado') NOT NULL DEFAULT 'disponible',
    torre_id INT UNSIGNED NULL,
    CONSTRAINT fk_puesto_torre FOREIGN KEY (torre_id) REFERENCES torres(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_puesto_estado (estado),
    INDEX idx_puesto_tipo (tipo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABLA: personas (registro único por cédula)
-- ---------------------------------------------------------
CREATE TABLE personas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    es_residente TINYINT(1) NOT NULL DEFAULT 0,
    torre_id INT UNSIGNED NULL,
    apartamento VARCHAR(10) NULL,
    qr_token CHAR(64) NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_persona_torre FOREIGN KEY (torre_id) REFERENCES torres(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_persona_cedula (cedula),
    INDEX idx_persona_qr (qr_token)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABLA: vehiculos
-- ---------------------------------------------------------
CREATE TABLE vehiculos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    persona_id INT UNSIGNED NOT NULL,
    placa VARCHAR(15) NOT NULL UNIQUE,
    marca VARCHAR(50) NULL,
    modelo VARCHAR(50) NULL,
    color VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehiculo_persona FOREIGN KEY (persona_id) REFERENCES personas(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_vehiculo_placa (placa)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABLA: movimientos (entradas / salidas)
-- ---------------------------------------------------------
CREATE TABLE movimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    persona_id INT UNSIGNED NOT NULL,
    vehiculo_id INT UNSIGNED NOT NULL,
    puesto_id INT UNSIGNED NOT NULL,
    tipo_entrada ENUM('residente','visitante','mercado') NOT NULL,
    torre_visita_id INT UNSIGNED NULL,
    apartamento_visita VARCHAR(10) NULL,
    fecha_entrada DATETIME NOT NULL,
    fecha_salida DATETIME NULL,
    tiempo_total_minutos INT UNSIGNED NULL,
    limite_minutos INT UNSIGNED NOT NULL,
    monto DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    estado ENUM('activo','finalizado') NOT NULL DEFAULT 'activo',
    usuario_entrada_id INT UNSIGNED NULL,
    usuario_salida_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_persona FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE CASCADE,
    CONSTRAINT fk_mov_vehiculo FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    CONSTRAINT fk_mov_puesto FOREIGN KEY (puesto_id) REFERENCES puestos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mov_torre_visita FOREIGN KEY (torre_visita_id) REFERENCES torres(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_usuario_entrada FOREIGN KEY (usuario_entrada_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_usuario_salida FOREIGN KEY (usuario_salida_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_mov_estado (estado),
    INDEX idx_mov_fecha_entrada (fecha_entrada),
    INDEX idx_mov_tipo (tipo_entrada)
) ENGINE=InnoDB;

-- Nota: la unicidad de "un solo movimiento activo por vehículo" se controla
-- a nivel de aplicación con transacciones (SELECT ... FOR UPDATE antes de
-- insertar en api/entrada.php), ya que un índice único sobre (vehiculo_id, estado)
-- impediría tener múltiples registros 'finalizado' históricos para el mismo vehículo.
CREATE INDEX idx_mov_vehiculo_estado ON movimientos (vehiculo_id, estado);

-- =========================================================
-- DATOS INICIALES
-- =========================================================

-- Usuario administrador por defecto -> usuario: admin   password: Admin123!
INSERT INTO usuarios (username, password_hash, nombre, rol) VALUES
('admin', '$2b$10$SeERORNe4Mm/3Wi5J.ENeewtvKhZ.lpQFmHirmYTvAayfsnGBvAy2', 'Administrador', 'admin');
-- Usuario de garita por defecto -> usuario: garita  password: Garita123!
INSERT INTO usuarios (username, password_hash, nombre, rol) VALUES
('garita', '$2b$10$BnV5/GDuf78T9gYdKN8o1.XnNL8fh2r7n/r/eE16qNdVAKLCM7zTK', 'Encargado Garita', 'garita');

-- 6 Torres
INSERT INTO torres (nombre) VALUES
('Torre 1'), ('Torre 2'), ('Torre 3'), ('Torre 4'), ('Torre 5'), ('Torre 6');

-- 46 puestos: 36 residentes (R-01 a R-36) + 10 visitantes (V-01 a V-10)
-- Distribuidos de forma proporcional entre las 6 torres (INSERTs planos,
-- compatibles con cualquier cliente MySQL/MariaDB, incluyendo phpMyAdmin)
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-01', 'residente', 'disponible', 1);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-02', 'residente', 'disponible', 2);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-03', 'residente', 'disponible', 3);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-04', 'residente', 'disponible', 4);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-05', 'residente', 'disponible', 5);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-06', 'residente', 'disponible', 6);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-07', 'residente', 'disponible', 1);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-08', 'residente', 'disponible', 2);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-09', 'residente', 'disponible', 3);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-10', 'residente', 'disponible', 4);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-11', 'residente', 'disponible', 5);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-12', 'residente', 'disponible', 6);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-13', 'residente', 'disponible', 1);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-14', 'residente', 'disponible', 2);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-15', 'residente', 'disponible', 3);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-16', 'residente', 'disponible', 4);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-17', 'residente', 'disponible', 5);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-18', 'residente', 'disponible', 6);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-19', 'residente', 'disponible', 1);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-20', 'residente', 'disponible', 2);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-21', 'residente', 'disponible', 3);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-22', 'residente', 'disponible', 4);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-23', 'residente', 'disponible', 5);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-24', 'residente', 'disponible', 6);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-25', 'residente', 'disponible', 1);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-26', 'residente', 'disponible', 2);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-27', 'residente', 'disponible', 3);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-28', 'residente', 'disponible', 4);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-29', 'residente', 'disponible', 5);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-30', 'residente', 'disponible', 6);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-31', 'residente', 'disponible', 1);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-32', 'residente', 'disponible', 2);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-33', 'residente', 'disponible', 3);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-34', 'residente', 'disponible', 4);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-35', 'residente', 'disponible', 5);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('R-36', 'residente', 'disponible', 6);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-01', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-02', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-03', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-04', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-05', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-06', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-07', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-08', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-09', 'visitante', 'disponible', NULL);
INSERT INTO puestos (numero, tipo, estado, torre_id) VALUES ('V-10', 'visitante', 'disponible', NULL);

