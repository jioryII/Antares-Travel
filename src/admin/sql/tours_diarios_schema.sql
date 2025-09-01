-- Script SQL para crear las tablas necesarias para Tours Diarios
-- Ejecutar en la base de datos db_antares

-- Tabla para tours diarios
CREATE TABLE IF NOT EXISTS `tours_diarios` (
    `id_tour_diario` int(11) NOT NULL AUTO_INCREMENT,
    `fecha` date NOT NULL,
    `id_tour` int(11) NOT NULL,
    `id_guia` int(11) NOT NULL,
    `id_chofer` int(11) NOT NULL,
    `id_vehiculo` int(11) NOT NULL,
    `num_adultos` int(11) DEFAULT 0,
    `num_ninos` int(11) DEFAULT 0,
    `hora_salida` time NOT NULL,
    `hora_retorno` time DEFAULT NULL,
    `observaciones` text,
    `estado` enum('Programado','En_Curso','Finalizado','Cancelado') DEFAULT 'Programado',
    `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_tour_diario`),
    KEY `idx_fecha` (`fecha`),
    KEY `idx_tour` (`id_tour`),
    KEY `idx_guia` (`id_guia`),
    KEY `idx_chofer` (`id_chofer`),
    KEY `idx_vehiculo` (`id_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para disponibilidad de guías
CREATE TABLE IF NOT EXISTS `disponibilidad_guias` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_guia` int(11) NOT NULL,
    `fecha` date NOT NULL,
    `estado` enum('Disponible','Ocupado','No_Disponible') DEFAULT 'Disponible',
    `motivo` varchar(255) DEFAULT NULL,
    `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_guia_fecha` (`id_guia`, `fecha`),
    KEY `idx_fecha` (`fecha`),
    KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para disponibilidad de choferes
CREATE TABLE IF NOT EXISTS `chofer_disponibilidad` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_chofer` int(11) NOT NULL,
    `id_tour` int(11) DEFAULT NULL,
    `fecha` date NOT NULL,
    `estado` enum('Disponible','No Disponible') DEFAULT 'Disponible',
    `motivo` varchar(255) DEFAULT NULL,
    `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_chofer_fecha` (`id_chofer`, `fecha`),
    KEY `idx_fecha` (`fecha`),
    KEY `idx_estado` (`estado`),
    KEY `idx_tour` (`id_tour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para disponibilidad de vehículos
CREATE TABLE IF NOT EXISTS `disponibilidad_vehiculos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_vehiculo` int(11) NOT NULL,
    `fecha` date NOT NULL,
    `estado` enum('Disponible','Ocupado','Mantenimiento','No_Disponible') DEFAULT 'Disponible',
    `motivo` varchar(255) DEFAULT NULL,
    `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_vehiculo_fecha` (`id_vehiculo`, `fecha`),
    KEY `idx_fecha` (`fecha`),
    KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de guías (si no existe)
CREATE TABLE IF NOT EXISTS `guias` (
    `id_guia` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `apellido` varchar(100) NOT NULL,
    `telefono` varchar(20) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `fecha_nacimiento` date DEFAULT NULL,
    `direccion` text,
    `experiencia_anos` int(11) DEFAULT 0,
    `idiomas` text,
    `certificaciones` text,
    `activo` tinyint(1) DEFAULT 1,
    `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_guia`),
    KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de choferes (si no existe)
CREATE TABLE IF NOT EXISTS `choferes` (
    `id_chofer` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `apellido` varchar(100) NOT NULL,
    `telefono` varchar(20) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `licencia` varchar(50) NOT NULL,
    `fecha_vencimiento_licencia` date DEFAULT NULL,
    `fecha_nacimiento` date DEFAULT NULL,
    `direccion` text,
    `experiencia_anos` int(11) DEFAULT 0,
    `activo` tinyint(1) DEFAULT 1,
    `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_chofer`),
    KEY `idx_activo` (`activo`),
    KEY `idx_licencia` (`licencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de vehículos (si no existe)
CREATE TABLE IF NOT EXISTS `vehiculos` (
    `id_vehiculo` int(11) NOT NULL AUTO_INCREMENT,
    `placa` varchar(20) NOT NULL,
    `marca` varchar(50) NOT NULL,
    `modelo` varchar(50) NOT NULL,
    `ano` int(11) DEFAULT NULL,
    `color` varchar(30) DEFAULT NULL,
    `capacidad` int(11) NOT NULL DEFAULT 1,
    `tipo` enum('Automovil','Minivan','Bus','Microbus','Camioneta') DEFAULT 'Automovil',
    `combustible` enum('Gasolina','Diesel','Gas','Hibrido','Electrico') DEFAULT 'Gasolina',
    `estado` enum('Excelente','Bueno','Regular','Malo') DEFAULT 'Bueno',
    `kilometraje` decimal(10,2) DEFAULT 0.00,
    `fecha_soat` date DEFAULT NULL,
    `fecha_revision_tecnica` date DEFAULT NULL,
    `activo` tinyint(1) DEFAULT 1,
    `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_vehiculo`),
    UNIQUE KEY `unique_placa` (`placa`),
    KEY `idx_activo` (`activo`),
    KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de ejemplo para guías
INSERT IGNORE INTO `guias` (`id_guia`, `nombre`, `apellido`, `telefono`, `email`, `experiencia_anos`, `idiomas`) VALUES
(1, 'Carlos', 'Mendoza', '987654321', 'carlos.mendoza@antares.com', 5, 'Español, Inglés'),
(2, 'María', 'González', '987654322', 'maria.gonzalez@antares.com', 3, 'Español, Inglés, Francés'),
(3, 'José', 'Ramírez', '987654323', 'jose.ramirez@antares.com', 7, 'Español, Inglés'),
(4, 'Ana', 'Torres', '987654324', 'ana.torres@antares.com', 4, 'Español, Inglés, Portugués');

-- Insertar datos de ejemplo para choferes
INSERT IGNORE INTO `choferes` (`id_chofer`, `nombre`, `apellido`, `telefono`, `email`, `licencia`, `experiencia_anos`) VALUES
(1, 'Pedro', 'Silva', '987123456', 'pedro.silva@antares.com', 'A1-123456', 8),
(2, 'Juan', 'Vargas', '987123457', 'juan.vargas@antares.com', 'A2-789012', 6),
(3, 'Luis', 'Castillo', '987123458', 'luis.castillo@antares.com', 'A1-345678', 10),
(4, 'Roberto', 'Herrera', '987123459', 'roberto.herrera@antares.com', 'A2-901234', 5);

-- Insertar datos de ejemplo para vehículos
INSERT IGNORE INTO `vehiculos` (`id_vehiculo`, `placa`, `marca`, `modelo`, `capacidad`, `tipo`) VALUES
(1, 'ABC-123', 'Toyota', 'Hiace', 12, 'Minivan'),
(2, 'DEF-456', 'Hyundai', 'H1', 15, 'Minivan'),
(3, 'GHI-789', 'Mercedes-Benz', 'Sprinter', 20, 'Bus'),
(4, 'JKL-012', 'Ford', 'Transit', 14, 'Minivan'),
(5, 'MNO-345', 'Chevrolet', 'N300', 18, 'Bus');

-- Crear índices adicionales para optimización
CREATE INDEX IF NOT EXISTS `idx_tours_diarios_fecha_estado` ON `tours_diarios` (`fecha`, `estado`);
CREATE INDEX IF NOT EXISTS `idx_disponibilidad_guias_fecha_estado` ON `disponibilidad_guias` (`fecha`, `estado`);
CREATE INDEX IF NOT EXISTS `idx_chofer_disponibilidad_fecha_estado` ON `chofer_disponibilidad` (`fecha`, `estado`);
CREATE INDEX IF NOT EXISTS `idx_disponibilidad_vehiculos_fecha_estado` ON `disponibilidad_vehiculos` (`fecha`, `estado`);
