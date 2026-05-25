-- ============================================================
-- SISTEMA SIEM ACADÉMICO - VERSION MEJORADA
-- MySQL 8.x - Compatible con XAMPP/phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS siem_academico
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE siem_academico;

-- ============================================================
-- TABLA 1: ROLES
-- ============================================================

CREATE TABLE Roles (
    id_rol INT NOT NULL AUTO_INCREMENT,
    nombre_rol VARCHAR(30) NOT NULL,
    descripcion VARCHAR(120),

    CONSTRAINT pk_roles PRIMARY KEY (id_rol),
    CONSTRAINT uq_roles_nombre UNIQUE (nombre_rol),

    CONSTRAINT chk_roles_nombre
    CHECK (CHAR_LENGTH(TRIM(nombre_rol)) >= 3),
 
    -- Solo letras y espacios
    CONSTRAINT chk_roles_nombre_texto
    CHECK (
        nombre_rol REGEXP '^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$'
    )    	

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 2: USUARIOS
-- ============================================================

CREATE TABLE Usuarios (
    id_usuario INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(20) NOT NULL,
    apellido VARCHAR(20) NOT NULL,
    correo VARCHAR(30) NOT NULL,
    contrasena VARCHAR(30) NOT NULL,

    estado ENUM(
        'activo',
        'inactivo',
        'suspendido'
    ) NOT NULL DEFAULT 'activo',

    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    id_rol INT NOT NULL,

    CONSTRAINT pk_usuarios PRIMARY KEY (id_usuario),

    CONSTRAINT uq_correo UNIQUE (correo),

    CONSTRAINT fk_usr_rol
    FOREIGN KEY (id_rol)
    REFERENCES Roles(id_rol)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT chk_nombre_usuario
    CHECK (CHAR_LENGTH(TRIM(nombre)) >= 2),

    CONSTRAINT chk_apellido_usuario
    CHECK (CHAR_LENGTH(TRIM(apellido)) >= 2),

    CONSTRAINT chk_correo
    CHECK (correo LIKE '%@%.%')

) ENGINE=InnoDB;
insert into Usuarios (correo,nombre,contrasena,apellido)VALUES
("admin@siem.com","administrador","123456","admin1");


-- ============================================================
-- TABLA 3: EQUIPOS
-- ============================================================

CREATE TABLE Equipos (
    id_equipo INT NOT NULL AUTO_INCREMENT,
    nombre_equipo VARCHAR(40) NOT NULL,
    direccion_ip VARCHAR(45) NOT NULL,
    sistema_operativo VARCHAR(30),
    ubicacion VARCHAR(80),

    estado ENUM(
        'activo',
        'inactivo',
        'mantenimiento'
    ) NOT NULL DEFAULT 'activo',

    CONSTRAINT pk_equipos PRIMARY KEY (id_equipo),

    CONSTRAINT uq_eq_ip UNIQUE (direccion_ip),

    CONSTRAINT chk_nombre_equipo
    CHECK (CHAR_LENGTH(TRIM(nombre_equipo)) >= 3),

    CONSTRAINT chk_ip_equipo
    CHECK (direccion_ip <> '')

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 4: LOGS
-- ============================================================

CREATE TABLE Logs (
    id_log INT NOT NULL AUTO_INCREMENT,
    id_equipo INT NOT NULL,
    tipo_log VARCHAR(30) NOT NULL,

    severidad ENUM(
        'INFO',
        'WARNING',
        'ERROR',
        'CRITICAL'
    ) NOT NULL DEFAULT 'INFO',

    mensaje TEXT NOT NULL,

    fecha_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_logs PRIMARY KEY (id_log),

    CONSTRAINT fk_log_equipo
    FOREIGN KEY (id_equipo)
    REFERENCES Equipos(id_equipo)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    INDEX idx_logs_severidad (severidad),
    INDEX idx_logs_fecha (fecha_evento),
    INDEX idx_logs_tipo (tipo_log),

    CONSTRAINT chk_tipo_log
    CHECK (CHAR_LENGTH(TRIM(tipo_log)) >= 3)

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 5: ALERTAS
-- ============================================================

CREATE TABLE Alertas (
    id_alerta INT NOT NULL AUTO_INCREMENT,
    id_log INT NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    descripcion TEXT,

    nivel_riesgo ENUM(
        'BAJO',
        'MEDIO',
        'ALTO',
        'CRITICO'
    ) NOT NULL DEFAULT 'MEDIO',

    fecha_alerta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    estado ENUM(
        'nueva',
        'revisada',
        'cerrada',
        'falso_positivo'
    ) NOT NULL DEFAULT 'nueva',

    CONSTRAINT pk_alertas PRIMARY KEY (id_alerta),

    CONSTRAINT fk_ale_log
    FOREIGN KEY (id_log)
    REFERENCES Logs(id_log)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    INDEX idx_alertas_estado (estado),
    INDEX idx_alertas_nivel_riesgo (nivel_riesgo),

    CONSTRAINT chk_titulo_alerta
    CHECK (CHAR_LENGTH(TRIM(titulo)) >= 5)

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 6: INCIDENTES
-- ============================================================

CREATE TABLE Incidentes (
    id_incidente INT NOT NULL AUTO_INCREMENT,
    id_alerta INT NOT NULL,
    id_usuario INT NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    descripcion TEXT,

    prioridad ENUM(
        'BAJA',
        'MEDIA',
        'ALTA',
        'CRITICA'
    ) NOT NULL DEFAULT 'MEDIA',

    estado ENUM(
        'abierto',
        'en_progreso',
        'resuelto',
        'cerrado'
    ) NOT NULL DEFAULT 'abierto',

    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    fecha_cierre DATETIME NULL,

    CONSTRAINT pk_incidentes PRIMARY KEY (id_incidente),

    CONSTRAINT fk_inc_alerta
    FOREIGN KEY (id_alerta)
    REFERENCES Alertas(id_alerta)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT fk_inc_usuario
    FOREIGN KEY (id_usuario)
    REFERENCES Usuarios(id_usuario)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    INDEX idx_inc_estado (estado),
    INDEX idx_inc_prioridad (prioridad),

    CONSTRAINT chk_fechas_incidente
    CHECK (
        fecha_cierre IS NULL
        OR fecha_cierre >= fecha_inicio
    )

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 7: REGLAS_DETECCION
-- ============================================================

CREATE TABLE Reglas_Deteccion (
    id_regla INT NOT NULL AUTO_INCREMENT,
    nombre_regla VARCHAR(50) NOT NULL,
    descripcion VARCHAR(150),

    severidad ENUM(
        'INFO',
        'WARNING',
        'ERROR',
        'CRITICAL'
    ) NOT NULL DEFAULT 'WARNING',

    patron_busqueda VARCHAR(120) NOT NULL,

    estado ENUM(
        'activa',
        'inactiva'
    ) NOT NULL DEFAULT 'activa',

    CONSTRAINT pk_reglas PRIMARY KEY (id_regla),

    CONSTRAINT uq_nombre_regla UNIQUE (nombre_regla),

    CONSTRAINT chk_nombre_regla
    CHECK (CHAR_LENGTH(TRIM(nombre_regla)) >= 3)

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 8: LOGS_REGLAS
-- ============================================================

CREATE TABLE Logs_Reglas (
    id_log_regla INT NOT NULL AUTO_INCREMENT,
    id_log INT NOT NULL,
    id_regla INT NOT NULL,
    fecha_deteccion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_logs_reglas PRIMARY KEY (id_log_regla),

    CONSTRAINT fk_lr_log
    FOREIGN KEY (id_log)
    REFERENCES Logs(id_log)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

    CONSTRAINT fk_lr_regla
    FOREIGN KEY (id_regla)
    REFERENCES Reglas_Deteccion(id_regla)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

    CONSTRAINT uq_log_regla UNIQUE (id_log, id_regla)

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 9: REPORTES
-- ============================================================

CREATE TABLE Reportes (
    id_reporte INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    descripcion TEXT,
    fecha_generacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_reportes PRIMARY KEY (id_reporte),

    CONSTRAINT fk_rep_usuario
    FOREIGN KEY (id_usuario)
    REFERENCES Usuarios(id_usuario)
    ON UPDATE CASCADE
    ON DELETE RESTRICT

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 10: HISTORIAL_SESIONES
-- ============================================================

CREATE TABLE Historial_Sesiones (
    id_sesion INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,

    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    fecha_fin DATETIME NULL,

    ip_acceso VARCHAR(45) NOT NULL,

    estado_sesion ENUM(
        'activa',
        'cerrada',
        'expirada'
    ) NOT NULL DEFAULT 'activa',

    CONSTRAINT pk_sesiones PRIMARY KEY (id_sesion),

    CONSTRAINT fk_ses_usuario
    FOREIGN KEY (id_usuario)
    REFERENCES Usuarios(id_usuario)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    INDEX idx_ses_usuario (id_usuario),
    INDEX idx_ses_inicio (fecha_inicio),

    CONSTRAINT chk_fechas_sesion
    CHECK (
        fecha_fin IS NULL
        OR fecha_fin >= fecha_inicio
    )

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 11: EVENTOS_RED
-- ============================================================

CREATE TABLE Eventos_Red (
    id_evento INT NOT NULL AUTO_INCREMENT,
    id_equipo INT NOT NULL,
    protocolo VARCHAR(10) NOT NULL,

    puerto_origen INT NULL,
    puerto_destino INT NULL,

    fecha_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_eventos_red PRIMARY KEY (id_evento),

    CONSTRAINT fk_evt_equipo
    FOREIGN KEY (id_equipo)
    REFERENCES Equipos(id_equipo)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    INDEX idx_er_protocolo (protocolo),
    INDEX idx_er_fecha (fecha_evento),

    CONSTRAINT chk_puerto_origen
    CHECK (
        puerto_origen IS NULL
        OR puerto_origen BETWEEN 1 AND 65535
    ),

    CONSTRAINT chk_puerto_destino
    CHECK (
        puerto_destino IS NULL
        OR puerto_destino BETWEEN 1 AND 65535
    )

) ENGINE=InnoDB;

-- ============================================================
-- TABLA 12: DIRECCIONES_IP
-- ============================================================

CREATE TABLE Direcciones_IP (
    id_ip INT NOT NULL AUTO_INCREMENT,

    direccion_ip VARCHAR(45) NOT NULL,

    pais VARCHAR(40),

    reputacion ENUM(
        'limpia',
        'sospechosa',
        'maliciosa',
        'desconocida'
    ) NOT NULL DEFAULT 'desconocida',

    CONSTRAINT pk_ips PRIMARY KEY (id_ip),

    CONSTRAINT uq_ip UNIQUE (direccion_ip),

    INDEX idx_ip_reputacion (reputacion),

    CONSTRAINT chk_ip_vacia
    CHECK (direccion_ip <> '')

) ENGINE=InnoDB;