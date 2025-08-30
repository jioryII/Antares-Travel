<?php
/**
 * Script de verificación e inicialización de base de datos
 * Antares Travel - Módulo de Tours
 */

require_once __DIR__ . '/../config/config.php';

try {
    // Conectar a la base de datos
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Usar la base de datos
    $pdo->exec("USE " . DB_NAME);
    
    echo "✅ Conexión a base de datos exitosa\n";
    
    // Verificar y crear tabla de regiones
    $sql_regiones = "
        CREATE TABLE IF NOT EXISTS regiones (
            id_region INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql_regiones);
    echo "✅ Tabla 'regiones' verificada/creada\n";
    
    // Insertar regiones por defecto si no existen
    $check_regiones = $pdo->query("SELECT COUNT(*) as total FROM regiones")->fetch();
    if ($check_regiones['total'] == 0) {
        $regiones_default = [
            ['Cusco', 'Región del Cusco con Machu Picchu'],
            ['Arequipa', 'Región de Arequipa con el Cañón del Colca'],
            ['Lima', 'Región de Lima y alrededores'],
            ['Ica', 'Región de Ica con Paracas y Nazca'],
            ['Puno', 'Región de Puno con el Lago Titicaca']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO regiones (nombre, descripcion) VALUES (?, ?)");
        foreach ($regiones_default as $region) {
            $stmt->execute($region);
        }
        echo "✅ Regiones por defecto insertadas\n";
    }
    
    // Verificar y crear tabla de guías
    $sql_guias = "
        CREATE TABLE IF NOT EXISTS guias (
            id_guia INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(50) NOT NULL,
            apellido VARCHAR(50) NOT NULL,
            telefono VARCHAR(20),
            email VARCHAR(100),
            estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql_guias);
    echo "✅ Tabla 'guias' verificada/creada\n";
    
    // Insertar guías por defecto si no existen
    $check_guias = $pdo->query("SELECT COUNT(*) as total FROM guias")->fetch();
    if ($check_guias['total'] == 0) {
        $guias_default = [
            ['Carlos', 'Mendoza', '987654321', 'carlos@antares.com'],
            ['Ana', 'Rodriguez', '987654322', 'ana@antares.com'],
            ['Luis', 'Vargas', '987654323', 'luis@antares.com']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO guias (nombre, apellido, telefono, email) VALUES (?, ?, ?, ?)");
        foreach ($guias_default as $guia) {
            $stmt->execute($guia);
        }
        echo "✅ Guías por defecto insertados\n";
    }
    
    // Verificar y crear tabla de tours
    $sql_tours = "
        CREATE TABLE IF NOT EXISTS tours (
            id_tour INT PRIMARY KEY AUTO_INCREMENT,
            titulo VARCHAR(200) NOT NULL,
            descripcion TEXT NOT NULL,
            precio DECIMAL(10,2) NOT NULL,
            duracion VARCHAR(50) NOT NULL,
            region_id INT,
            dificultad ENUM('Fácil', 'Intermedio', 'Difícil', 'Extremo') DEFAULT 'Fácil',
            capacidad_maxima INT NOT NULL,
            incluye TEXT NOT NULL,
            no_incluye TEXT,
            lugar_salida VARCHAR(150) NOT NULL,
            ubicacion VARCHAR(150) NOT NULL,
            imagen_principal VARCHAR(255),
            guia_id INT,
            lugar_llegada VARCHAR(150),
            hora_salida TIME,
            hora_llegada TIME,
            recomendaciones TEXT,
            que_llevar TEXT,
            politicas TEXT,
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fecha_eliminacion TIMESTAMP NULL,
            FOREIGN KEY (region_id) REFERENCES regiones(id_region),
            FOREIGN KEY (guia_id) REFERENCES guias(id_guia)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql_tours);
    echo "✅ Tabla 'tours' verificada/creada\n";
    
    // Verificar y crear tabla de reservas (para la relación)
    $sql_reservas = "
        CREATE TABLE IF NOT EXISTS reservas (
            id_reserva INT PRIMARY KEY AUTO_INCREMENT,
            id_tour INT NOT NULL,
            cliente_nombre VARCHAR(100) NOT NULL,
            cliente_email VARCHAR(100) NOT NULL,
            cliente_telefono VARCHAR(20),
            fecha_reserva DATE NOT NULL,
            cantidad_personas INT NOT NULL,
            monto_total DECIMAL(10,2) NOT NULL,
            estado ENUM('Pendiente', 'Confirmada', 'Cancelada') DEFAULT 'Pendiente',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_tour) REFERENCES tours(id_tour)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql_reservas);
    echo "✅ Tabla 'reservas' verificada/creada\n";
    
    // Crear directorio de uploads si no existe
    $upload_dir = __DIR__ . '/../../../uploads/tours/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "✅ Directorio de uploads creado\n";
    }
    
    echo "\n🎉 Base de datos inicializada correctamente\n";
    echo "📊 Estadísticas:\n";
    
    // Mostrar estadísticas
    $stats = [
        'regiones' => $pdo->query("SELECT COUNT(*) as total FROM regiones")->fetch()['total'],
        'guias' => $pdo->query("SELECT COUNT(*) as total FROM guias")->fetch()['total'],
        'tours' => $pdo->query("SELECT COUNT(*) as total FROM tours")->fetch()['total'],
        'reservas' => $pdo->query("SELECT COUNT(*) as total FROM reservas")->fetch()['total']
    ];
    
    foreach ($stats as $tabla => $total) {
        echo "   • {$tabla}: {$total} registros\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
