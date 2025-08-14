-- ==========================================
-- 1. USUARIOS
-- ==========================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(150) UNIQUE NOT NULL,
    foto_perfil VARCHAR(255),
    proveedor_login ENUM('google','facebook') NOT NULL,
    id_proveedor VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================================
-- 2. GUIAS, IDIOMAS, CHOFERES Y VEHÍCULOS
-- ==========================================
CREATE TABLE guias (
    id_guia INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(150) UNIQUE,
    experiencia TEXT,
    estado ENUM('Libre','Ocupado') DEFAULT 'Libre'
);

CREATE TABLE idiomas (
    id_idioma INT AUTO_INCREMENT PRIMARY KEY,
    nombre_idioma VARCHAR(100) NOT NULL -- Ej: Español, Inglés, Francés
);

CREATE TABLE guia_idiomas (
    id_guia INT NOT NULL,
    id_idioma INT NOT NULL,
    PRIMARY KEY (id_guia, id_idioma),
    FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE CASCADE,
    FOREIGN KEY (id_idioma) REFERENCES idiomas(id_idioma) ON DELETE CASCADE
);

CREATE TABLE choferes (
    id_chofer INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    telefono VARCHAR(20),
    licencia VARCHAR(50) UNIQUE
);

CREATE TABLE vehiculos (
    id_vehiculo INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(100),
    modelo VARCHAR(100),
    placa VARCHAR(20) UNIQUE,
    capacidad INT,
    caracteristicas TEXT,
    id_chofer INT,
    FOREIGN KEY (id_chofer) REFERENCES choferes(id_chofer) ON DELETE SET NULL
);

-- ==========================================
-- 3. TOURS
-- ==========================================
CREATE TABLE tours (
    id_tour INT AUTO_INCREMENT PRIMARY KEY,
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
    id_guia INT,
    FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE SET NULL
);

-- ==========================================
-- 4. CALIFICACIONES DE GUÍAS
-- ==========================================
CREATE TABLE calificaciones_guias (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_guia INT NOT NULL,
    calificacion INT CHECK (calificacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE CASCADE
);

-- ==========================================
-- 5. RESERVAS Y PASAJEROS
-- ==========================================
CREATE TABLE reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL, -- Cliente que reserva
    id_tour INT NOT NULL,
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
    id_pasajero INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    documento_identidad VARCHAR(50),
    telefono VARCHAR(20),
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE CASCADE
);

-- ==========================================
-- 6. PAGOS
-- ==========================================
CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('Efectivo','Tarjeta','Transferencia'),
    estado_pago ENUM('Pagado','Pendiente','Fallido') DEFAULT 'Pendiente',
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE CASCADE
);

-- ==========================================
-- 7. DISPONIBILIDAD DE GUÍAS Y VEHÍCULOS
-- ==========================================
CREATE TABLE disponibilidad_guias (
    id_disponibilidad INT AUTO_INCREMENT PRIMARY KEY,
    id_guia INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('Libre','Ocupado') DEFAULT 'Libre',
    id_reserva INT,
    FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE CASCADE,
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL
);

CREATE TABLE disponibilidad_vehiculos (
    id_disponibilidad INT AUTO_INCREMENT PRIMARY KEY,
    id_vehiculo INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('Libre','Ocupado') DEFAULT 'Libre',
    id_reserva INT,
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE CASCADE,
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL
);

-- ==========================================
-- 8. COTIZACIONES (Carrito)
-- ==========================================
CREATE TABLE cotizaciones (
    id_cotizacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_estimado DECIMAL(10,2),
    estado ENUM('Pendiente','Confirmada','Vencida') DEFAULT 'Pendiente',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE detalle_cotizacion (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_cotizacion INT NOT NULL,
    id_tour INT NOT NULL,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2),
    FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE CASCADE,
    FOREIGN KEY (id_tour) REFERENCES tours(id_tour) ON DELETE CASCADE
);

-- ==========================================
-- 9. MURO DE LA EMPRESA Y EXPERIENCIAS USUARIOS
-- ==========================================
CREATE TABLE muro_imagenes (
    id_muro INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200),
    imagen_url VARCHAR(255) NOT NULL,
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

CREATE TABLE experiencias_usuarios (
    id_experiencia INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_tour INT,
    imagen_url VARCHAR(255),
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_tour) REFERENCES tours(id_tour) ON DELETE SET NULL
);