-- Esquema de PRUEBAS equivalente a migarita.sql (MySQL), adaptado a SQLite.
-- Se usa en las pruebas de integración (BD en memoria) y funcionales (BD en archivo temporal).
-- Mantiene las mismas tablas, columnas, llaves únicas, llaves foráneas,
-- restricciones CHECK de negocio y la vista vista_saldos.

CREATE TABLE tipos_cliente (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE metodos_pago (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula         VARCHAR(20)  NOT NULL UNIQUE CHECK (cedula GLOB '[0-9]*' AND cedula NOT GLOB '*[^0-9]*'),
    nombre         VARCHAR(100) NOT NULL,
    apellido       VARCHAR(100) NOT NULL,
    correo         VARCHAR(120) NOT NULL UNIQUE CHECK (correo LIKE '%@%'),
    usuario        VARCHAR(50)  NOT NULL UNIQUE CHECK (usuario <> ''),
    password_hash  VARCHAR(255) NOT NULL,
    rol            TEXT NOT NULL DEFAULT 'usuario' CHECK (rol IN ('superusuario', 'usuario')),
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula          VARCHAR(20)  NOT NULL UNIQUE CHECK (cedula GLOB '[0-9]*' AND cedula NOT GLOB '*[^0-9]*'),
    nombre          VARCHAR(100) NOT NULL,
    apellido        VARCHAR(100) NOT NULL,
    telefono        VARCHAR(20) CHECK (telefono GLOB '[0-9]*' AND telefono NOT GLOB '*[^0-9]*'),
    correo          VARCHAR(120) CHECK (correo LIKE '%@%'),
    fecha_registro  DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_tipo_cliente INTEGER NOT NULL DEFAULT 1 REFERENCES tipos_cliente (id) ON UPDATE CASCADE
);

CREATE TABLE deudas (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cliente     INTEGER NOT NULL REFERENCES clientes (id) ON DELETE CASCADE,
    id_usuario     INTEGER NOT NULL REFERENCES usuarios (id),
    descripcion    VARCHAR(255),
    monto          DECIMAL(10,2) NOT NULL CHECK (monto <> 0),
    fecha          DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_metodo_pago INTEGER REFERENCES metodos_pago (id) ON DELETE SET NULL
);

CREATE TABLE bitacora_login (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    id_usuario        INTEGER REFERENCES usuarios (id) ON DELETE SET NULL,
    fecha             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip                VARCHAR(45),
    navegador         VARCHAR(250),
    sistema_operativo VARCHAR(100),
    exito             INTEGER NOT NULL DEFAULT 1
);

-- Misma definición que la vista de MySQL: saldo = SUMA(monto) de cada cliente.
CREATE VIEW vista_saldos AS
SELECT c.id, c.cedula, c.nombre, c.apellido, c.telefono, c.correo,
       tc.nombre AS tipo_cliente,
       COALESCE(SUM(d.monto), 0) AS saldo
FROM clientes c
LEFT JOIN deudas d ON d.id_cliente = c.id
LEFT JOIN tipos_cliente tc ON tc.id = c.id_tipo_cliente
GROUP BY c.id;

-- Catálogos (mismos valores que migarita.sql)
INSERT INTO metodos_pago (id, nombre) VALUES (1, 'efectivo'), (2, 'transferencia');
INSERT INTO tipos_cliente (id, nombre) VALUES
    (1, 'estudiante'), (2, 'administrativo'), (3, 'profesor'), (4, 'egresado'), (5, 'externo');
