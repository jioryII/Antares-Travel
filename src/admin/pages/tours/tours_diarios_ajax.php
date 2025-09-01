<?php
require_once '../../config/config.php';

// Establecer conexión a la base de datos
$pdo = getConnection();

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Verificar autenticación para AJAX
function verificarSesionAjax() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        echo json_encode([
            'success' => false,
            'error' => 'Sesión no válida',
            'redirect_login' => true,
            'login_url' => '../../auth/login.php'
        ]);
        exit;
    }
    
    if (!isset($_SESSION['admin_rol']) || $_SESSION['admin_rol'] !== 'admin') {
        echo json_encode([
            'success' => false,
            'error' => 'Permisos insuficientes',
            'redirect_login' => true,
            'login_url' => '../../auth/login.php'
        ]);
        exit;
    }
    
    return true;
}

// Verificar autenticación
verificarSesionAjax();

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar que las tablas existan
    $tablas_requeridas = ['tours_diarios', 'guias', 'choferes', 'vehiculos'];
    $tablas_faltantes = [];
    
    foreach ($tablas_requeridas as $tabla) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '" . $tabla . "'");
            if ($stmt->rowCount() === 0) {
                $tablas_faltantes[] = $tabla;
            }
        } catch (Exception $e) {
            $tablas_faltantes[] = $tabla;
        }
    }
    
    if (!empty($tablas_faltantes)) {
        echo json_encode([
            'success' => false,
            'error' => 'Tablas no encontradas: ' . implode(', ', $tablas_faltantes),
            'install_required' => true,
            'redirect_url' => '../../install_tours_diarios.php'
        ]);
        exit;
    }

    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'check_availability':
            checkAvailability();
            break;
            
        case 'get_guides':
            getGuides();
            break;
            
        case 'get_drivers':
            getDrivers();
            break;
            
        case 'get_vehicles':
            getVehicles();
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'action' => $_GET['action'] ?? 'no_action'
        ]
    ]);
}

function checkAvailability() {
    global $pdo;
    
    $fecha = $_GET['fecha'] ?? '';
    
    if (empty($fecha)) {
        throw new Exception('Fecha requerida');
    }
    
    // Validar formato de fecha
    if (!DateTime::createFromFormat('Y-m-d', $fecha)) {
        throw new Exception('Formato de fecha inválido');
    }
    
    // Verificar si existe la tabla tours_diarios para hacer el JOIN
    $tabla_tours_existe = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'tours_diarios'");
        $tabla_tours_existe = ($stmt->rowCount() > 0);
    } catch (Exception $e) {
        // Si hay error, asumimos que no existe
    }
    
    if ($tabla_tours_existe) {
        // Obtener guías disponibles con verificación de ocupación
        $stmt = $pdo->prepare("
            SELECT g.id_guia, g.nombre, g.apellido, g.telefono, g.experiencia,
                   CASE WHEN td.id_tour_diario IS NOT NULL THEN 'Ocupado' ELSE 'Libre' END as estado
            FROM guias g
            LEFT JOIN tours_diarios td ON g.id_guia = td.id_guia AND td.fecha = ?
            WHERE g.estado = 'Libre'
            ORDER BY g.nombre, g.apellido
        ");
        $stmt->execute([$fecha]);
        $guias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener choferes disponibles con verificación de ocupación
        $stmt = $pdo->prepare("
            SELECT c.id_chofer, c.nombre, c.apellido, c.telefono, c.licencia,
                   CASE WHEN td.id_tour_diario IS NOT NULL THEN 'Ocupado' ELSE 'Libre' END as estado
            FROM choferes c
            LEFT JOIN tours_diarios td ON c.id_chofer = td.id_chofer AND td.fecha = ?
            ORDER BY c.nombre, c.apellido
        ");
        $stmt->execute([$fecha]);
        $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener vehículos disponibles con verificación de ocupación
        $stmt = $pdo->prepare("
            SELECT v.id_vehiculo, v.marca, v.modelo, v.placa, v.capacidad, v.caracteristicas,
                   CASE WHEN td.id_tour_diario IS NOT NULL THEN 'Ocupado' ELSE 'Libre' END as estado
            FROM vehiculos v
            LEFT JOIN tours_diarios td ON v.id_vehiculo = td.id_vehiculo AND td.fecha = ?
            ORDER BY v.marca, v.modelo
        ");
        $stmt->execute([$fecha]);
        $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Sin tabla tours_diarios, solo mostrar recursos disponibles
        $stmt = $pdo->prepare("
            SELECT id_guia, nombre, apellido, telefono, experiencia, 'Libre' as estado
            FROM guias 
            WHERE estado = 'Libre'
            ORDER BY nombre, apellido
        ");
        $stmt->execute();
        $guias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT id_chofer, nombre, apellido, telefono, licencia, 'Libre' as estado
            FROM choferes 
            ORDER BY nombre, apellido
        ");
        $stmt->execute();
        $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT id_vehiculo, marca, modelo, placa, capacidad, caracteristicas, 'Libre' as estado
            FROM vehiculos 
            ORDER BY marca, modelo
        ");
        $stmt->execute();
        $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'guias' => $guias,
            'choferes' => $choferes,
            'vehiculos' => $vehiculos,
            'fecha' => $fecha,
            'total_guias' => count($guias),
            'total_choferes' => count($choferes),
            'total_vehiculos' => count($vehiculos)
        ]
    ]);
}

function getGuides() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id_guia, nombre, apellido, telefono, experiencia, estado
        FROM guias 
        WHERE estado = 'Libre'
        ORDER BY nombre, apellido
    ");
    $stmt->execute();
    $guias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $guias
    ]);
}

function getDrivers() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id_chofer, nombre, apellido, telefono, licencia, 'Libre' as estado
        FROM choferes 
        ORDER BY nombre, apellido
    ");
    $stmt->execute();
    $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $choferes
    ]);
}

function getVehicles() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id_vehiculo, marca, modelo, placa, capacidad, caracteristicas, 'Libre' as estado
        FROM vehiculos 
        ORDER BY marca, modelo
    ");
    $stmt->execute();
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $vehiculos
    ]);
}
?>
