-- FitCore Gym - Esquema de base de datos
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS crm_envios, crm_campanas, crm_actividades, crm_leads, anulaciones, membresia_congelamientos, ia_consultas, caja_movimientos, cajas, movimientos_stock, venta_detalle, ventas, productos, gastos, evaluaciones, reservas, horarios_clase, clases,
  entrenadores, asistencias, pagos, membresias, socios, planes, usuarios, roles, configuracion;

CREATE TABLE roles (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id TINYINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  usuario VARCHAR(40) NOT NULL UNIQUE,
  email VARCHAR(120) NULL,
  password VARCHAR(255) NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE planes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NULL,
  duracion_dias SMALLINT UNSIGNED NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  color VARCHAR(9) NOT NULL DEFAULT '#ff5a1f',
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE socios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(12) NOT NULL UNIQUE,
  dni VARCHAR(15) NULL,
  nombres VARCHAR(80) NOT NULL,
  apellidos VARCHAR(80) NOT NULL,
  fecha_nacimiento DATE NULL,
  genero ENUM('M','F','O') NOT NULL DEFAULT 'M',
  telefono VARCHAR(20) NULL,
  email VARCHAR(120) NULL,
  direccion VARCHAR(200) NULL,
  contacto_emergencia VARCHAR(100) NULL,
  telefono_emergencia VARCHAR(20) NULL,
  objetivo VARCHAR(80) NULL,
  foto VARCHAR(200) NULL,
  fecha_registro DATE NOT NULL,
  estado ENUM('activo','inactivo','suspendido') NOT NULL DEFAULT 'activo',
  observaciones TEXT NULL,
  INDEX idx_estado (estado),
  INDEX idx_registro (fecha_registro),
  INDEX idx_nac (fecha_nacimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE membresias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  socio_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  descuento DECIMAL(10,2) NOT NULL DEFAULT 0,
  estado ENUM('activa','vencida','cancelada','congelada') NOT NULL DEFAULT 'activa',
  usuario_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fin (fecha_fin),
  INDEX idx_socio (socio_id),
  FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES planes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  membresia_id INT UNSIGNED NULL,
  socio_id INT UNSIGNED NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  metodo ENUM('efectivo','tarjeta','yape','plin','transferencia') NOT NULL DEFAULT 'efectivo',
  concepto VARCHAR(150) NULL,
  fecha_pago DATETIME NOT NULL,
  usuario_id INT UNSIGNED NULL,
  INDEX idx_fecha (fecha_pago),
  FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
  FOREIGN KEY (membresia_id) REFERENCES membresias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asistencias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  socio_id INT UNSIGNED NOT NULL,
  ingreso DATETIME NOT NULL,
  salida DATETIME NULL,
  metodo ENUM('dni','qr','huella','manual') NOT NULL DEFAULT 'dni',
  INDEX idx_ingreso (ingreso),
  FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE entrenadores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombres VARCHAR(80) NOT NULL,
  apellidos VARCHAR(80) NOT NULL,
  especialidad VARCHAR(100) NULL,
  telefono VARCHAR(20) NULL,
  email VARCHAR(120) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clases (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NULL,
  entrenador_id INT UNSIGNED NULL,
  cupo SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  color VARCHAR(9) NOT NULL DEFAULT '#ff5a1f',
  estado TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (entrenador_id) REFERENCES entrenadores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE horarios_clase (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  clase_id INT UNSIGNED NOT NULL,
  dia_semana TINYINT UNSIGNED NOT NULL COMMENT '1=lunes ... 7=domingo',
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  sala VARCHAR(40) NULL,
  FOREIGN KEY (clase_id) REFERENCES clases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  horario_id INT UNSIGNED NOT NULL,
  socio_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  estado ENUM('reservada','asistio','cancelada') NOT NULL DEFAULT 'reservada',
  INDEX idx_fecha (fecha),
  FOREIGN KEY (horario_id) REFERENCES horarios_clase(id) ON DELETE CASCADE,
  FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  categoria VARCHAR(60) NULL,
  precio_compra DECIMAL(10,2) NOT NULL DEFAULT 0,
  precio_venta DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 5,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ventas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  socio_id INT UNSIGNED NULL,
  total DECIMAL(10,2) NOT NULL,
  metodo ENUM('efectivo','tarjeta','yape','plin','transferencia') NOT NULL DEFAULT 'efectivo',
  fecha DATETIME NOT NULL,
  usuario_id INT UNSIGNED NULL,
  INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE venta_detalle (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  venta_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  cantidad INT NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE gastos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concepto VARCHAR(150) NOT NULL,
  categoria VARCHAR(60) NULL,
  monto DECIMAL(10,2) NOT NULL,
  fecha DATE NOT NULL,
  usuario_id INT UNSIGNED NULL,
  INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE evaluaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  socio_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  peso DECIMAL(5,2) NULL,
  talla DECIMAL(4,2) NULL,
  grasa DECIMAL(4,1) NULL,
  cintura DECIMAL(5,1) NULL,
  pecho DECIMAL(5,1) NULL,
  brazo DECIMAL(5,1) NULL,
  pierna DECIMAL(5,1) NULL,
  notas VARCHAR(255) NULL,
  FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE configuracion (
  clave VARCHAR(60) PRIMARY KEY,
  valor TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (id, nombre) VALUES (1,'Administrador'),(2,'Recepcionista'),(3,'Entrenador'),(4,'Contador');
INSERT INTO configuracion (clave, valor) VALUES
 ('gimnasio_nombre','FitCore Gym'),('gimnasio_ruc',''),('gimnasio_direccion','Av. Principal 123, Lima'),
 ('gimnasio_telefono','999 999 999'),('moneda','S/'),('aforo_maximo','120'),('dias_aviso_vencimiento','7');
