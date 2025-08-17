CREATE DATABASE IF NOT EXISTS db_antares;
USE db_antares;

-- ==========================================
-- ADMINISTRADORES
-- ==========================================
CREATE TABLE administradores (
    id_admin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Identificación
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    
    -- Seguridad de acceso
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(64) NULL, -- opcional, si manejas sal manual
    
    -- Verificación de correo
    email_verificado BOOLEAN DEFAULT FALSE,
    token_verificacion VARCHAR(255) NULL,
    token_expira DATETIME NULL,
    
    -- Nivel de acceso
    rol ENUM('superadmin','editor','soporte') DEFAULT 'editor',
    
    -- Control de login
    ultimo_login DATETIME NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado BOOLEAN DEFAULT FALSE,
    
    -- Auditoría
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================
-- USUARIOS
-- ==========================================
CREATE TABLE usuarios (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  
  nombre VARCHAR(100),
  email VARCHAR(255) UNIQUE NOT NULL,
  email_verificado BOOLEAN DEFAULT FALSE,

  -- Login tradicional
  password_hash TEXT,

  -- Social login
  proveedor_oauth ENUM('google', 'facebook', 'apple', 'microsoft', 'telefono', 'manual') DEFAULT 'manual',
  id_proveedor VARCHAR(255),

  -- Datos opcionales
  avatar_url TEXT,
  telefono VARCHAR(20),
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE email_verificacion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);


-- ==========================================
-- GUIAS, IDIOMAS, CHOFERES Y VEHÍCULOS
-- ==========================================
CREATE TABLE guias (
    id_guia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(150) UNIQUE,
    experiencia TEXT,
    estado ENUM('Libre','Ocupado') DEFAULT 'Libre'
);

CREATE TABLE idiomas (
    id_idioma INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_idioma VARCHAR(100) NOT NULL
);

CREATE TABLE guia_idiomas (
    id_guia INT UNSIGNED NOT NULL,
    id_idioma INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_guia, id_idioma),
    FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE CASCADE,
    FOREIGN KEY (id_idioma) REFERENCES idiomas(id_idioma) ON DELETE CASCADE
);

CREATE TABLE choferes (
    id_chofer INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    telefono VARCHAR(20),
    licencia VARCHAR(50) UNIQUE
);

CREATE TABLE vehiculos (
    id_vehiculo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(100),
    modelo VARCHAR(100),
    placa VARCHAR(20) UNIQUE,
    capacidad INT,
    caracteristicas TEXT,
    id_chofer INT UNSIGNED,
    FOREIGN KEY (id_chofer) REFERENCES choferes(id_chofer) ON DELETE SET NULL
);

-- ==========================================
-- TOURS
-- ==========================================
CREATE TABLE tours (
    id_tour INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    duracion VARCHAR(100),
    lugar_salida VARCHAR(200),
    lugar_llegada VARCHAR(200),
    fecha_disponible DATE,
    hora_salida TIME,
    hora_llegada TIME,
    cupos_disponibles INT,
    categoria VARCHAR(100),
    imagen_principal VARCHAR(255),
    politica_cancelacion TEXT,
    id_guia INT UNSIGNED,
    FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE SET NULL
);

-- ==========================================
-- CALIFICACIONES DE GUÍAS
-- ==========================================
CREATE TABLE calificaciones_guias (
    id_calificacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    id_guia INT UNSIGNED NOT NULL,
    calificacion INT CHECK (calificacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE CASCADE
);

-- ==========================================
-- RESERVAS Y PASAJEROS
-- ==========================================
CREATE TABLE reservas (
    id_reserva INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    id_tour INT UNSIGNED NOT NULL,
    fecha_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_tour DATE NOT NULL,
    hora_tour TIME,
    monto_total DECIMAL(10,2),
    tipo_pago ENUM('Efectivo','Tarjeta','Transferencia'),
    num_personas INT,
    estado ENUM('Pendiente','Confirmada','Cancelada','Finalizada') DEFAULT 'Pendiente',
    observaciones TEXT,
    origen_reserva ENUM('Web','Presencial','Llamada') DEFAULT 'Web',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_tour) REFERENCES tours(id_tour) ON DELETE CASCADE
);

CREATE TABLE pasajeros (
    id_pasajero INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    documento_identidad VARCHAR(50),
    telefono VARCHAR(20),
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE CASCADE
);

-- ==========================================
-- PAGOS
-- ==========================================
CREATE TABLE pagos (
    id_pago INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT UNSIGNED NOT NULL,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('Efectivo','Tarjeta','Transferencia'),
    estado_pago ENUM('Pagado','Pendiente','Fallido') DEFAULT 'Pendiente',
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE CASCADE
);

-- ==========================================
-- DISPONIBILIDAD DE GUÍAS Y VEHÍCULOS
-- ==========================================
CREATE TABLE disponibilidad_guias (
    id_disponibilidad INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_guia INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('Libre','Ocupado') DEFAULT 'Libre',
    id_reserva INT UNSIGNED,
    FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE CASCADE,
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL
);

CREATE TABLE disponibilidad_vehiculos (
    id_disponibilidad INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_vehiculo INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('Libre','Ocupado') DEFAULT 'Libre',
    id_reserva INT UNSIGNED,
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE CASCADE,
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL
);

-- ==========================================
-- COTIZACIONES
-- ==========================================
CREATE TABLE cotizaciones (
    id_cotizacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_estimado DECIMAL(10,2),
    estado ENUM('Pendiente','Confirmada','Vencida') DEFAULT 'Pendiente',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE detalle_cotizacion (
    id_detalle INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cotizacion INT UNSIGNED NOT NULL,
    id_tour INT UNSIGNED NOT NULL,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2),
    FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE CASCADE,
    FOREIGN KEY (id_tour) REFERENCES tours(id_tour) ON DELETE CASCADE
);

-- ==========================================
-- MURO DE LA EMPRESA Y EXPERIENCIAS USUARIOS
-- ==========================================
CREATE TABLE muro_imagenes (
    id_muro INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200),
    imagen_url VARCHAR(255) NOT NULL,
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT UNSIGNED,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

CREATE TABLE experiencias_usuarios (
    id_experiencia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    id_tour INT UNSIGNED,
    imagen_url VARCHAR(255),
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_tour) REFERENCES tours(id_tour) ON DELETE SET NULL
);
