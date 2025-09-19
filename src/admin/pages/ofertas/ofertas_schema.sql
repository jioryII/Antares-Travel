-- Archivo SQL para crear las tablas del módulo de ofertas
-- Ejecutar en la base de datos db_antares

-- -----------------------------------------------------
-- Table `db_antares`.`ofertas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`ofertas` (
  `id_oferta` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(200) NOT NULL COMMENT 'Nombre descriptivo de la oferta',
  `descripcion` TEXT NULL DEFAULT NULL COMMENT 'Descripción detallada de la oferta',
  `tipo_oferta` ENUM('Porcentaje', 'Monto_Fijo', 'Precio_Especial', '2x1', 'Combo') NOT NULL DEFAULT 'Porcentaje' COMMENT 'Tipo de descuento aplicado',
  
  -- Configuración del descuento
  `valor_descuento` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Valor del descuento (% o monto fijo)',
  `precio_especial` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Precio especial para ofertas de precio fijo',
  
  -- Validez temporal
  `fecha_inicio` DATETIME NOT NULL COMMENT 'Fecha y hora de inicio de la oferta',
  `fecha_fin` DATETIME NOT NULL COMMENT 'Fecha y hora de finalización de la oferta',
  
  -- Restricciones y límites
  `limite_usos` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Número máximo de usos de la oferta',
  `usos_actuales` INT UNSIGNED NULL DEFAULT 0 COMMENT 'Número actual de usos',
  `limite_por_usuario` INT UNSIGNED NULL DEFAULT 1 COMMENT 'Máximo de usos por usuario',
  `monto_minimo` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Monto mínimo de compra para aplicar',
  
  -- Aplicabilidad
  `aplicable_a` ENUM('Todos', 'Tours_Especificos', 'Usuarios_Especificos', 'Nuevos_Usuarios') NOT NULL DEFAULT 'Todos',
  `codigo_promocional` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Código que deben ingresar los usuarios',
  
  -- Estado y visibilidad
  `estado` ENUM('Activa', 'Pausada', 'Finalizada', 'Borrador') NOT NULL DEFAULT 'Borrador',
  `visible_publica` TINYINT(1) NULL DEFAULT 1 COMMENT 'Si la oferta es visible públicamente',
  `destacada` TINYINT(1) NULL DEFAULT 0 COMMENT 'Si la oferta debe mostrarse destacada',
  
  -- Configuración adicional
  `imagen_banner` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Imagen promocional de la oferta',
  `terminos_condiciones` TEXT NULL DEFAULT NULL COMMENT 'Términos y condiciones específicos',
  `mensaje_promocional` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Mensaje a mostrar al usuario',
  
  -- Auditoría
  `creado_por` INT UNSIGNED NULL DEFAULT NULL COMMENT 'ID del administrador que creó la oferta',
  `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id_oferta`),
  UNIQUE INDEX `codigo_promocional` (`codigo_promocional` ASC) VISIBLE,
  INDEX `idx_fechas_oferta` (`fecha_inicio` ASC, `fecha_fin` ASC) VISIBLE,
  INDEX `idx_estado_visible` (`estado` ASC, `visible_publica` ASC) VISIBLE,
  INDEX `creado_por` (`creado_por` ASC) VISIBLE,
  
  CONSTRAINT `ofertas_ibfk_1`
    FOREIGN KEY (`creado_por`)
    REFERENCES `db_antares`.`administradores` (`id_admin`)
    ON DELETE SET NULL
) ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;

-- -----------------------------------------------------
-- Table `db_antares`.`ofertas_tours`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`ofertas_tours` (
  `id_oferta` INT UNSIGNED NOT NULL,
  `id_tour` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_oferta`, `id_tour`),
  INDEX `id_tour` (`id_tour` ASC) VISIBLE,
  CONSTRAINT `ofertas_tours_ibfk_1`
    FOREIGN KEY (`id_oferta`)
    REFERENCES `db_antares`.`ofertas` (`id_oferta`)
    ON DELETE CASCADE,
  CONSTRAINT `ofertas_tours_ibfk_2`
    FOREIGN KEY (`id_tour`)
    REFERENCES `db_antares`.`tours` (`id_tour`)
    ON DELETE CASCADE
) ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;

-- -----------------------------------------------------
-- Table `db_antares`.`ofertas_usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`ofertas_usuarios` (
  `id_oferta` INT UNSIGNED NOT NULL,
  `id_usuario` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_oferta`, `id_usuario`),
  INDEX `id_usuario` (`id_usuario` ASC) VISIBLE,
  CONSTRAINT `ofertas_usuarios_ibfk_1`
    FOREIGN KEY (`id_oferta`)
    REFERENCES `db_antares`.`ofertas` (`id_oferta`)
    ON DELETE CASCADE,
  CONSTRAINT `ofertas_usuarios_ibfk_2`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `db_antares`.`usuarios` (`id_usuario`)
    ON DELETE CASCADE
) ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;

-- -----------------------------------------------------
-- Table `db_antares`.`historial_uso_ofertas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`historial_uso_ofertas` (
  `id_uso` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_oferta` INT UNSIGNED NOT NULL,
  `id_usuario` INT UNSIGNED NOT NULL,
  `id_reserva` INT UNSIGNED NOT NULL,
  `monto_descuento` DECIMAL(10,2) NOT NULL COMMENT 'Monto del descuento aplicado',
  `fecha_uso` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_uso`),
  INDEX `id_oferta` (`id_oferta` ASC) VISIBLE,
  INDEX `id_usuario` (`id_usuario` ASC) VISIBLE,
  INDEX `id_reserva` (`id_reserva` ASC) VISIBLE,
  CONSTRAINT `historial_uso_ofertas_ibfk_1`
    FOREIGN KEY (`id_oferta`)
    REFERENCES `db_antares`.`ofertas` (`id_oferta`)
    ON DELETE CASCADE,
  CONSTRAINT `historial_uso_ofertas_ibfk_2`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `db_antares`.`usuarios` (`id_usuario`)
    ON DELETE CASCADE,
  CONSTRAINT `historial_uso_ofertas_ibfk_3`
    FOREIGN KEY (`id_reserva`)
    REFERENCES `db_antares`.`reservas` (`id_reserva`)
    ON DELETE CASCADE
) ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;

-- -----------------------------------------------------
-- Modificación a la tabla reservas para agregar referencia a ofertas
-- -----------------------------------------------------
ALTER TABLE `db_antares`.`reservas` 
ADD COLUMN `id_oferta_aplicada` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Oferta aplicada a esta reserva',
ADD COLUMN `descuento_aplicado` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Monto del descuento aplicado',
ADD INDEX `id_oferta_aplicada` (`id_oferta_aplicada` ASC),
ADD CONSTRAINT `reservas_ofertas_ibfk`
  FOREIGN KEY (`id_oferta_aplicada`)
  REFERENCES `db_antares`.`ofertas` (`id_oferta`)
  ON DELETE SET NULL;

-- -----------------------------------------------------
-- Datos de ejemplo para testing
-- -----------------------------------------------------

-- Insertar algunas ofertas de ejemplo
INSERT INTO `db_antares`.`ofertas` (
    `nombre`, 
    `descripcion`, 
    `tipo_oferta`, 
    `valor_descuento`, 
    `fecha_inicio`, 
    `fecha_fin`, 
    `aplicable_a`, 
    `codigo_promocional`, 
    `estado`, 
    `visible_publica`, 
    `destacada`, 
    `mensaje_promocional`,
    `limite_por_usuario`
) VALUES 
(
    'Descuento de Verano 2025', 
    'Oferta especial para tours de verano con 20% de descuento', 
    'Porcentaje', 
    20.00, 
    '2024-12-01 00:00:00', 
    '2025-03-31 23:59:59', 
    'Todos', 
    'VERANO2025', 
    'Activa', 
    1, 
    1, 
    '¡Aprovecha nuestro descuento especial de verano!',
    2
),
(
    'Primera Reserva', 
    'Descuento especial para usuarios nuevos en su primera reserva', 
    'Monto_Fijo', 
    50.00, 
    '2024-01-01 00:00:00', 
    '2025-12-31 23:59:59', 
    'Nuevos_Usuarios', 
    'BIENVENIDO', 
    'Activa', 
    1, 
    0, 
    'Bienvenido a Antares Travel, disfruta tu primer descuento',
    1
),
(
    'Tour Familiar 2x1', 
    'Oferta especial familiar: paga un tour y lleva dos personas', 
    '2x1', 
    NULL, 
    '2024-11-01 00:00:00', 
    '2024-12-31 23:59:59', 
    'Tours_Especificos', 
    'FAMILIA2X1', 
    'Pausada', 
    1, 
    0, 
    'Oferta perfecta para salir en familia',
    1
),
(
    'Precio Especial Machu Picchu', 
    'Precio especial para el tour a Machu Picchu durante noviembre', 
    'Precio_Especial', 
    NULL, 
    '2024-11-01 00:00:00', 
    '2024-11-30 23:59:59', 
    'Tours_Especificos', 
    'MACHUPICCHU', 
    'Borrador', 
    0, 
    1, 
    'Precio especial para conocer la maravilla del mundo',
    1
);

-- Actualizar precio especial para la oferta de Machu Picchu
UPDATE `db_antares`.`ofertas` SET `precio_especial` = 299.00 WHERE `codigo_promocional` = 'MACHUPICCHU';

-- -----------------------------------------------------
-- Trigger para actualizar usos_actuales automáticamente
-- -----------------------------------------------------
DELIMITER $$

CREATE TRIGGER `actualizar_usos_oferta` 
AFTER INSERT ON `historial_uso_ofertas`
FOR EACH ROW
BEGIN
    UPDATE ofertas 
    SET usos_actuales = (
        SELECT COUNT(*) 
        FROM historial_uso_ofertas 
        WHERE id_oferta = NEW.id_oferta
    ) 
    WHERE id_oferta = NEW.id_oferta;
END$$

DELIMITER ;

-- -----------------------------------------------------
-- Índices adicionales para optimización
-- -----------------------------------------------------
CREATE INDEX `idx_ofertas_vigentes` ON `db_antares`.`ofertas` (`estado`, `fecha_inicio`, `fecha_fin`);
CREATE INDEX `idx_historial_fecha` ON `db_antares`.`historial_uso_ofertas` (`fecha_uso`);
CREATE INDEX `idx_reservas_oferta` ON `db_antares`.`reservas` (`id_oferta_aplicada`);

-- Mensaje de confirmación
SELECT 'Módulo de Ofertas creado exitosamente' as mensaje;
