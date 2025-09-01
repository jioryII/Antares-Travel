<?php
// Archivo: config/config.php
// Configuración principal de Antares Travel Admin

// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_antares');
define('DB_USER', 'root');
define('DB_PASS', 'admin942');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('SITE_NAME', 'Antares Travel Admin');
define('BASE_URL', 'http://localhost/Antares-Travel/');
define('ADMIN_URL', BASE_URL . 'src/admin/');

// Configuración de archivos
define('UPLOAD_DIR', __DIR__ . '/../storage/uploads/');
define('UPLOAD_URL', BASE_URL . 'storage/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Configuración de sesión
define('SESSION_LIFETIME', 3600); // 1 hora
define('SESSION_NAME', 'antares_admin_session');

// Configuración de seguridad
define('HASH_ALGORITHM', 'sha256');
define('ENCRYPTION_KEY', 'tu_clave_secreta_aqui_cambiala_en_produccion');

// Configuración de paginación
define('RECORDS_PER_PAGE', 10);

// Zona horaria
date_default_timezone_set('America/Lima');

// Configuración de errores (cambiar a false en producción)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Función para conectar a la base de datos
function getConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ]);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Error de conexión: " . $e->getMessage());
            } else {
                die("Error de conexión a la base de datos");
            }
        }
    }
    
    return $connection;
}

// Función para sanitizar datos de entrada
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para registrar logs de auditoría
function logActivity($admin_id, $accion, $tabla_afectada = null, $registro_id = null, $detalles = null) {
    try {
        $connection = getConnection();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $sql = "INSERT INTO logs_auditoria (admin_id, accion, tabla_afectada, registro_id, detalles, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$admin_id, $accion, $tabla_afectada, $registro_id, $detalles, $ip_address]);
    } catch (Exception $e) {
        // Error silencioso en logs para no interrumpir la aplicación
        if (DEBUG_MODE) {
            error_log("Error en log de auditoría: " . $e->getMessage());
        }
    }
}

// Función para formatear fechas
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// Función para formatear moneda
function formatCurrency($amount) {
    return 'S/ ' . number_format($amount, 2, '.', ',');
}

// Función para obtener información del admin actual
function getCurrentAdmin() {
    if (isset($_SESSION['admin_id'])) {
        try {
            $connection = getConnection();
            $sql = "SELECT id_admin, nombre, email, rol FROM administradores WHERE id_admin = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$_SESSION['admin_id']]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    return null;
}

// Función para verificar permisos
function hasPermission($required_role) {
    $admin = getCurrentAdmin();
    if (!$admin) return false;
    
    $roles_hierarchy = [
        'moderador' => 1,
        'admin' => 2,
        'super_admin' => 3
    ];
    
    $current_level = $roles_hierarchy[$admin['rol']] ?? 0;
    $required_level = $roles_hierarchy[$required_role] ?? 999;
    
    return $current_level >= $required_level;
}

// Función para redireccionar
function redirect($url) {
    header("Location: $url");
    exit();
}

// Función para mostrar mensajes flash
function setFlashMessage($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function getFlashMessage($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $message = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $message;
    }
    return null;
}

// Función para crear directorios de upload si no existen
function createUploadDirectories() {
    $directories = [
        UPLOAD_DIR,
        UPLOAD_DIR . 'tours/',
        UPLOAD_DIR . 'guias/',
        UPLOAD_DIR . 'experiencias/',
        UPLOAD_DIR . 'vehiculos/'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Crear directorios al cargar la configuración
createUploadDirectories();
?>
