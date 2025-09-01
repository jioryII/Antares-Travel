-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema db_antares
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema db_antares
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `db_antares` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
USE `db_antares` ;

-- -----------------------------------------------------
-- Table `db_antares`.`administradores`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`administradores` (
  `id_admin` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `salt` VARCHAR(64) NULL DEFAULT NULL,
  `email_verificado` TINYINT(1) NULL DEFAULT '0',
  `token_verificacion` VARCHAR(255) NULL DEFAULT NULL,
  `token_expira` DATETIME NULL DEFAULT NULL,
  `rol` ENUM('superadmin', 'admin', 'operaciones', 'ventas', 'soporte') NULL DEFAULT 'admin',
  `ultimo_login` DATETIME NULL DEFAULT NULL,
  `intentos_fallidos` INT NULL DEFAULT '0',
  `bloqueado` TINYINT(1) NULL DEFAULT '0',
  `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_admin`),
  UNIQUE INDEX `email` (`email` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`usuarios` (
  `id_usuario` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NULL DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verificado` TINYINT(1) NULL DEFAULT '0',
  `password_hash` TEXT NULL DEFAULT NULL,
  `proveedor_oauth` ENUM('google', 'apple', 'microsoft', 'manual') NULL DEFAULT 'manual',
  `id_proveedor` VARCHAR(255) NULL DEFAULT NULL,
  `avatar_url` TEXT NULL DEFAULT NULL,
  `telefono` VARCHAR(20) NULL DEFAULT NULL,
  `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE INDEX `email` (`email` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 13
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`guias`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`guias` (
  `id_guia` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NULL DEFAULT NULL,
  `telefono` VARCHAR(20) NULL DEFAULT NULL,
  `email` VARCHAR(150) NULL DEFAULT NULL,
  `experiencia` TEXT NULL DEFAULT NULL,
  `estado` ENUM('Libre', 'Ocupado') NULL DEFAULT 'Libre',
  `foto_url` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id_guia`),
  UNIQUE INDEX `email` (`email` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 9
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`calificaciones_guias`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`calificaciones_guias` (
  `id_calificacion` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED NOT NULL,
  `id_guia` INT UNSIGNED NOT NULL,
  `calificacion` INT NULL DEFAULT NULL,
  `comentario` TEXT NULL DEFAULT NULL,
  `fecha` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_calificacion`),
  INDEX `id_usuario` (`id_usuario` ASC) VISIBLE,
  INDEX `id_guia` (`id_guia` ASC) VISIBLE,
  CONSTRAINT `calificaciones_guias_ibfk_1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `db_antares`.`usuarios` (`id_usuario`)
    ON DELETE CASCADE,
  CONSTRAINT `calificaciones_guias_ibfk_2`
    FOREIGN KEY (`id_guia`)
    REFERENCES `db_antares`.`guias` (`id_guia`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`choferes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`choferes` (
  `id_chofer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NULL DEFAULT NULL,
  `telefono` VARCHAR(20) NULL DEFAULT NULL,
  `licencia` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`id_chofer`),
  UNIQUE INDEX `licencia` (`licencia` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 9
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`configuraciones_admin`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`configuraciones_admin` (
  `id_config_admin` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT UNSIGNED NOT NULL,
  `clave` VARCHAR(100) NOT NULL,
  `valor` TEXT NULL DEFAULT NULL,
  `descripcion` TEXT NULL DEFAULT NULL,
  `tipo` ENUM('string', 'number', 'boolean', 'json') NULL DEFAULT 'string',
  `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config_admin`),
  UNIQUE INDEX `unique_admin_config` (`id_admin` ASC, `clave` ASC) VISIBLE,
  CONSTRAINT `configuraciones_admin_ibfk_1`
    FOREIGN KEY (`id_admin`)
    REFERENCES `db_antares`.`administradores` (`id_admin`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`configuraciones_sistema`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`configuraciones_sistema` (
  `id_config` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(100) NOT NULL,
  `valor` TEXT NULL DEFAULT NULL,
  `descripcion` TEXT NULL DEFAULT NULL,
  `tipo` ENUM('string', 'number', 'boolean', 'json') NULL DEFAULT 'string',
  `categoria` VARCHAR(50) NULL DEFAULT NULL,
  `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config`),
  UNIQUE INDEX `clave` (`clave` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`cotizaciones`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`cotizaciones` (
  `id_cotizacion` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED NOT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `total_estimado` DECIMAL(10,2) NULL DEFAULT NULL,
  `estado` ENUM('Pendiente', 'Confirmada', 'Vencida') NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`id_cotizacion`),
  INDEX `id_usuario` (`id_usuario` ASC) VISIBLE,
  CONSTRAINT `cotizaciones_ibfk_1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `db_antares`.`usuarios` (`id_usuario`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`regiones`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`regiones` (
  `id_region` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_region` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id_region`),
  UNIQUE INDEX `nombre_region` (`nombre_region` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 27
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`tours`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`tours` (
  `id_tour` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(200) NOT NULL,
  `descripcion` TEXT NULL DEFAULT NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `duracion` VARCHAR(100) NULL DEFAULT NULL,
  `id_region` INT UNSIGNED NULL DEFAULT NULL,
  `lugar_salida` VARCHAR(200) NULL DEFAULT NULL,
  `lugar_llegada` VARCHAR(200) NULL DEFAULT NULL,
  `hora_salida` TIME NULL DEFAULT NULL,
  `hora_llegada` TIME NULL DEFAULT NULL,
  `imagen_principal` VARCHAR(255) NULL DEFAULT NULL,
  `id_guia` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id_tour`),
  INDEX `id_guia` (`id_guia` ASC) VISIBLE,
  INDEX `id_region` (`id_region` ASC) VISIBLE,
  CONSTRAINT `tours_ibfk_1`
    FOREIGN KEY (`id_guia`)
    REFERENCES `db_antares`.`guias` (`id_guia`)
    ON DELETE SET NULL,
  CONSTRAINT `tours_ibfk_2`
    FOREIGN KEY (`id_region`)
    REFERENCES `db_antares`.`regiones` (`id_region`)
    ON DELETE SET NULL)
ENGINE = InnoDB
AUTO_INCREMENT = 17
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`detalle_cotizacion`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`detalle_cotizacion` (
  `id_detalle` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_cotizacion` INT UNSIGNED NOT NULL,
  `id_tour` INT UNSIGNED NOT NULL,
  `cantidad` INT NULL DEFAULT '1',
  `precio_unitario` DECIMAL(10,2) NULL DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  INDEX `id_cotizacion` (`id_cotizacion` ASC) VISIBLE,
  INDEX `id_tour` (`id_tour` ASC) VISIBLE,
  CONSTRAINT `detalle_cotizacion_ibfk_1`
    FOREIGN KEY (`id_cotizacion`)
    REFERENCES `db_antares`.`cotizaciones` (`id_cotizacion`)
    ON DELETE CASCADE,
  CONSTRAINT `detalle_cotizacion_ibfk_2`
    FOREIGN KEY (`id_tour`)
    REFERENCES `db_antares`.`tours` (`id_tour`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`reservas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`reservas` (
  `id_reserva` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED NULL DEFAULT NULL,
  `id_administrador` INT UNSIGNED NULL DEFAULT NULL,
  `id_tour` INT UNSIGNED NOT NULL,
  `fecha_reserva` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_tour` DATE NOT NULL,
  `monto_total` DECIMAL(10,2) NOT NULL,
  `estado` ENUM('Pendiente', 'Confirmada', 'Cancelada', 'Finalizada') NULL DEFAULT 'Pendiente',
  `observaciones` TEXT NULL DEFAULT NULL,
  `origen_reserva` ENUM('Web', 'Presencial', 'Llamada') NULL DEFAULT 'Web',
  PRIMARY KEY (`id_reserva`),
  INDEX `id_usuario` (`id_usuario` ASC) VISIBLE,
  INDEX `id_tour` (`id_tour` ASC) VISIBLE,
  CONSTRAINT `reservas_ibfk_1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `db_antares`.`usuarios` (`id_usuario`)
    ON DELETE CASCADE,
  CONSTRAINT `reservas_ibfk_2`
    FOREIGN KEY (`id_tour`)
    REFERENCES `db_antares`.`tours` (`id_tour`)
    ON DELETE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`disponibilidad_guias`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`disponibilidad_guias` (
  `id_disponibilidad` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_guia` INT UNSIGNED NOT NULL,
  `fecha` DATE NOT NULL,
  `estado` ENUM('Libre', 'Ocupado') NULL DEFAULT 'Libre',
  `id_reserva` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id_disponibilidad`),
  INDEX `id_guia` (`id_guia` ASC) VISIBLE,
  INDEX `id_reserva` (`id_reserva` ASC) VISIBLE,
  CONSTRAINT `disponibilidad_guias_ibfk_1`
    FOREIGN KEY (`id_guia`)
    REFERENCES `db_antares`.`guias` (`id_guia`)
    ON DELETE CASCADE,
  CONSTRAINT `disponibilidad_guias_ibfk_2`
    FOREIGN KEY (`id_reserva`)
    REFERENCES `db_antares`.`reservas` (`id_reserva`)
    ON DELETE SET NULL)
ENGINE = InnoDB
AUTO_INCREMENT = 9
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`vehiculos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`vehiculos` (
  `id_vehiculo` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `marca` VARCHAR(100) NULL DEFAULT NULL,
  `modelo` VARCHAR(100) NULL DEFAULT NULL,
  `placa` VARCHAR(20) NULL DEFAULT NULL,
  `capacidad` INT NULL DEFAULT NULL,
  `caracteristicas` TEXT NULL DEFAULT NULL,
  `id_chofer` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id_vehiculo`),
  UNIQUE INDEX `placa` (`placa` ASC) VISIBLE,
  INDEX `id_chofer` (`id_chofer` ASC) VISIBLE,
  CONSTRAINT `vehiculos_ibfk_1`
    FOREIGN KEY (`id_chofer`)
    REFERENCES `db_antares`.`choferes` (`id_chofer`)
    ON DELETE SET NULL)
ENGINE = InnoDB
AUTO_INCREMENT = 9
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`disponibilidad_vehiculos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`disponibilidad_vehiculos` (
  `id_disponibilidad` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_vehiculo` INT UNSIGNED NOT NULL,
  `fecha` DATE NOT NULL,
  `estado` ENUM('Libre', 'Ocupado') NULL DEFAULT 'Libre',
  `id_reserva` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id_disponibilidad`),
  INDEX `id_vehiculo` (`id_vehiculo` ASC) VISIBLE,
  INDEX `id_reserva` (`id_reserva` ASC) VISIBLE,
  CONSTRAINT `disponibilidad_vehiculos_ibfk_1`
    FOREIGN KEY (`id_vehiculo`)
    REFERENCES `db_antares`.`vehiculos` (`id_vehiculo`)
    ON DELETE CASCADE,
  CONSTRAINT `disponibilidad_vehiculos_ibfk_2`
    FOREIGN KEY (`id_reserva`)
    REFERENCES `db_antares`.`reservas` (`id_reserva`)
    ON DELETE SET NULL)
ENGINE = InnoDB
AUTO_INCREMENT = 7
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`email_verificacion`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`email_verificacion` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `id_usuario` (`id_usuario` ASC) VISIBLE,
  CONSTRAINT `email_verificacion_ibfk_1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `db_antares`.`usuarios` (`id_usuario`))
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`experiencias`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`experiencias` (
  `id_experiencia` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `imagen_url` VARCHAR(255) NOT NULL,
  `comentario` TEXT NULL DEFAULT NULL,
  `fecha_publicacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id_experiencia`),
  INDEX `id_usuario` (`id_usuario` ASC) VISIBLE,
  CONSTRAINT `experiencias_ibfk_1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `db_antares`.`usuarios` (`id_usuario`)
    ON DELETE SET NULL)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`idiomas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`idiomas` (
  `id_idioma` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_idioma` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_idioma`))
ENGINE = InnoDB
AUTO_INCREMENT = 17
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`guia_idiomas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`guia_idiomas` (
  `id_guia` INT UNSIGNED NOT NULL,
  `id_idioma` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_guia`, `id_idioma`),
  INDEX `id_idioma` (`id_idioma` ASC) VISIBLE,
  CONSTRAINT `guia_idiomas_ibfk_1`
    FOREIGN KEY (`id_guia`)
    REFERENCES `db_antares`.`guias` (`id_guia`)
    ON DELETE CASCADE,
  CONSTRAINT `guia_idiomas_ibfk_2`
    FOREIGN KEY (`id_idioma`)
    REFERENCES `db_antares`.`idiomas` (`id_idioma`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`pagos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`pagos` (
  `id_pago` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_reserva` INT UNSIGNED NOT NULL,
  `fecha_pago` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `monto` DECIMAL(10,2) NOT NULL,
  `metodo_pago` ENUM('Efectivo', 'Tarjeta', 'Transferencia') NULL DEFAULT NULL,
  `estado_pago` ENUM('Pagado', 'Pendiente', 'Fallido') NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`id_pago`),
  INDEX `id_reserva` (`id_reserva` ASC) VISIBLE,
  CONSTRAINT `pagos_ibfk_1`
    FOREIGN KEY (`id_reserva`)
    REFERENCES `db_antares`.`reservas` (`id_reserva`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`pasajeros`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`pasajeros` (
  `id_pasajero` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_reserva` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `dni_pasaporte` VARCHAR(20) NOT NULL,
  `nacionalidad` VARCHAR(50) NULL DEFAULT NULL,
  `telefono` VARCHAR(20) NULL DEFAULT NULL,
  `tipo_pasajero` ENUM('Adulto', 'Niño', 'Infante') NULL DEFAULT 'Adulto',
  PRIMARY KEY (`id_pasajero`),
  INDEX `id_reserva` (`id_reserva` ASC) VISIBLE,
  CONSTRAINT `pasajeros_ibfk_1`
    FOREIGN KEY (`id_reserva`)
    REFERENCES `db_antares`.`reservas` (`id_reserva`)
    ON DELETE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 10
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`preferencias_usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`preferencias_usuario` (
  `id_preferencia` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED NOT NULL,
  `clave` VARCHAR(100) NOT NULL,
  `valor` TEXT NULL DEFAULT NULL,
  `tipo` ENUM('string', 'number', 'boolean', 'json') NULL DEFAULT 'string',
  `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_preferencia`),
  UNIQUE INDEX `unique_user_preference` (`id_usuario` ASC, `clave` ASC) VISIBLE,
  CONSTRAINT `preferencias_usuario_ibfk_1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `db_antares`.`usuarios` (`id_usuario`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `db_antares`.`tours_diarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `db_antares`.`tours_diarios` (
  `id_tour_diario` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `id_tour` INT UNSIGNED NOT NULL,
  `id_guia` INT UNSIGNED NOT NULL,
  `id_chofer` INT UNSIGNED NOT NULL,
  `id_vehiculo` INT UNSIGNED NOT NULL,
  `num_adultos` INT UNSIGNED NULL DEFAULT '0',
  `num_ninos` INT UNSIGNED NULL DEFAULT '0',
  `hora_salida` TIME NOT NULL,
  `hora_retorno` TIME NULL DEFAULT NULL,
  `observaciones` TEXT NULL DEFAULT NULL,
  `fecha_registro` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tour_diario`),
  INDEX `id_tour` (`id_tour` ASC) VISIBLE,
  INDEX `id_guia` (`id_guia` ASC) VISIBLE,
  INDEX `id_chofer` (`id_chofer` ASC) VISIBLE,
  INDEX `id_vehiculo` (`id_vehiculo` ASC) VISIBLE,
  CONSTRAINT `tours_diarios_ibfk_1`
    FOREIGN KEY (`id_tour`)
    REFERENCES `db_antares`.`tours` (`id_tour`),
  CONSTRAINT `tours_diarios_ibfk_2`
    FOREIGN KEY (`id_guia`)
    REFERENCES `db_antares`.`guias` (`id_guia`),
  CONSTRAINT `tours_diarios_ibfk_3`
    FOREIGN KEY (`id_chofer`)
    REFERENCES `db_antares`.`choferes` (`id_chofer`),
  CONSTRAINT `tours_diarios_ibfk_4`
    FOREIGN KEY (`id_vehiculo`)
    REFERENCES `db_antares`.`vehiculos` (`id_vehiculo`))
ENGINE = InnoDB
AUTO_INCREMENT = 12
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
