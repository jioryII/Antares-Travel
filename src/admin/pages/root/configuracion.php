<?php
require_once '../../config/config.php';
require_once '../../../config/conexion.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar que sea SuperAdmin
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Solo SuperAdmin puede acceder
if (!isset($_SESSION['admin_rol']) || $_SESSION['admin_rol'] !== 'superadmin') {
    header('Location: ../dashboard/?error=acceso_denegado');
    exit;
}

$page_title = "Configuración del Sistema";

// Información completa del sistema (Solo SuperAdmin)
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'No disponible',
    'current_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'memory_usage' => formatBytes(memory_get_usage(true)),
    'memory_peak' => formatBytes(memory_get_peak_usage(true)),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'session_id' => session_id(),
    'session_name' => session_name(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'No disponible',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'No disponible',
    'server_admin' => $_SERVER['SERVER_ADMIN'] ?? 'No disponible',
    'loaded_extensions' => count(get_loaded_extensions()),
    'include_path' => get_include_path()
];

// Configuración de la aplicación
$app_config = [
    'base_url' => BASE_URL,
    'admin_url' => ADMIN_URL,
    'site_name' => SITE_NAME,
    'db_host' => DB_HOST,
    'db_name' => DB_NAME,
    'db_charset' => DB_CHARSET,
    'session_lifetime' => SESSION_LIFETIME,
    'max_file_size' => formatBytes(MAX_FILE_SIZE),
    'records_per_page' => RECORDS_PER_PAGE,
    'debug_mode' => DEBUG_MODE ? 'Activo' : 'Inactivo',
    'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'No definido'
];

// Estadísticas detalladas
$stats = getDashboardStats();
$reservas_recientes = getReservasRecientes(10);

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../../../../imagenes/antares_logozz2.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen bg-gray-100">
            <div class="p-6 lg:p-10">
                <!-- Encabezado de Seguridad -->
                <div class="mb-8">
                    <br><br><br>
                    <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-xl shadow-lg border border-red-700 p-6 lg:p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-3xl lg:text-4xl font-light text-white mb-2 flex items-center">
                                    <i class="fas fa-shield-alt mr-4"></i>Configuración del Sistema
                                </h1>
                                <p class="text-red-100 font-medium">Panel de SuperAdmin • Información Técnica Sensible</p>
                            </div>
                            <div class="hidden lg:block">
                                <div class="flex items-center space-x-4">
                                    <span class="px-4 py-2 bg-red-500 bg-opacity-30 backdrop-blur-sm rounded-full text-red-100 text-sm font-medium">
                                        <i class="fas fa-crown mr-2"></i>SuperAdmin Only
                                    </span>
                                    <span class="px-4 py-2 bg-yellow-500 bg-opacity-30 backdrop-blur-sm rounded-full text-yellow-100 text-sm font-medium">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Confidencial
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advertencia de Seguridad -->
                <div class="mb-8">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-red-800 mb-2">Información Sensible</h3>
                                <p class="text-red-700">
                                    Esta página contiene información técnica sensible del sistema. El acceso está restringido únicamente a SuperAdministradores. 
                                    No comparta esta información con personal no autorizado.
                                </p>
                                <div class="mt-3 text-sm text-red-600">
                                    <strong>Acceso registrado:</strong> <?php echo date('Y-m-d H:i:s'); ?> - Usuario: <?php echo htmlspecialchars($admin['email']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
                    <!-- Detalles del Servidor -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-server text-blue-600 mr-3"></i>Información del Servidor
                        </h3>
                        <div class="space-y-4 text-sm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">PHP Version:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['php_version']; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Memoria Actual:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['memory_usage']; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Pico de Memoria:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['memory_peak']; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Límite de Memoria:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['memory_limit']; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Tiempo Ejecución:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['max_execution_time']; ?>s</span>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Max Upload:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['upload_max_filesize']; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Max POST:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['post_max_size']; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Zona Horaria:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['timezone']; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Extensiones:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['loaded_extensions']; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">IP Cliente:</span>
                                        <span class="font-mono text-gray-900"><?php echo $system_info['remote_addr']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de la Aplicación -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-cogs text-green-600 mr-3"></i>Configuración de la Aplicación
                        </h3>
                        <div class="space-y-4 text-sm">
                            <div class="space-y-3">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Nombre del Sitio:</span>
                                    <span class="font-mono text-gray-900"><?php echo $app_config['site_name']; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Base URL:</span>
                                    <span class="font-mono text-gray-900 text-xs break-all"><?php echo $app_config['base_url']; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Admin URL:</span>
                                    <span class="font-mono text-gray-900 text-xs break-all"><?php echo $app_config['admin_url']; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">DB Host:</span>
                                    <span class="font-mono text-gray-900"><?php echo $app_config['db_host']; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">DB Name:</span>
                                    <span class="font-mono text-gray-900"><?php echo $app_config['db_name']; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">DB Charset:</span>
                                    <span class="font-mono text-gray-900"><?php echo $app_config['db_charset']; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Session Lifetime:</span>
                                    <span class="font-mono text-gray-900"><?php echo $app_config['session_lifetime']; ?>s</span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Max File Size:</span>
                                    <span class="font-mono text-gray-900"><?php echo $app_config['max_file_size']; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Debug Mode:</span>
                                    <span class="font-mono <?php echo DEBUG_MODE ? 'text-red-600' : 'text-green-600'; ?>">
                                        <?php echo $app_config['debug_mode']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Técnica Detallada -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-info-circle text-orange-600 mr-3"></i>Detalles Técnicos
                    </h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 text-sm">
                        <div class="space-y-4">
                            <div>
                                <span class="text-gray-600 font-medium block mb-2">Software del Servidor:</span>
                                <span class="font-mono text-gray-900 bg-gray-50 p-2 rounded block"><?php echo $system_info['server_software']; ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium block mb-2">Document Root:</span>
                                <span class="font-mono text-gray-900 bg-gray-50 p-2 rounded block break-all text-xs"><?php echo $system_info['document_root']; ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium block mb-2">Include Path:</span>
                                <span class="font-mono text-gray-900 bg-gray-50 p-2 rounded block break-all text-xs"><?php echo $system_info['include_path']; ?></span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <span class="text-gray-600 font-medium block mb-2">Session ID:</span>
                                <span class="font-mono text-gray-900 bg-gray-50 p-2 rounded block break-all text-xs"><?php echo $system_info['session_id']; ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium block mb-2">Session Name:</span>
                                <span class="font-mono text-gray-900 bg-gray-50 p-2 rounded block"><?php echo $system_info['session_name']; ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium block mb-2">User Agent:</span>
                                <span class="font-mono text-gray-900 bg-gray-50 p-2 rounded block break-all text-xs"><?php echo $system_info['user_agent']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas del Sistema -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-chart-bar text-purple-600 mr-3"></i>Estadísticas del Sistema
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-blue-600 font-medium text-sm">Tours Totales</p>
                                    <p class="text-2xl font-bold text-blue-800"><?php echo number_format($stats['total_tours'] ?? 0); ?></p>
                                </div>
                                <i class="fas fa-map-marked-alt text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-green-600 font-medium text-sm">Reservas</p>
                                    <p class="text-2xl font-bold text-green-800"><?php echo number_format($stats['total_reservas'] ?? 0); ?></p>
                                </div>
                                <i class="fas fa-calendar-check text-green-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-6 border border-purple-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-purple-600 font-medium text-sm">Usuarios</p>
                                    <p class="text-2xl font-bold text-purple-800"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></p>
                                </div>
                                <i class="fas fa-users text-purple-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-orange-600 font-medium text-sm">Reservas Recientes</p>
                                    <p class="text-2xl font-bold text-orange-800"><?php echo count($reservas_recientes); ?></p>
                                </div>
                                <i class="fas fa-clock text-orange-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Log de acceso de SuperAdmin
        console.log('Panel de Configuración del Sistema accedido por SuperAdmin');
        console.log('Usuario:', '<?php echo htmlspecialchars($admin['nombre']); ?>');
        console.log('Timestamp:', '<?php echo date('Y-m-d H:i:s'); ?>');
        
        // Funciones de seguridad
        document.addEventListener('DOMContentLoaded', function() {
            // Deshabilitar clic derecho
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                console.warn('Acceso restringido: Información sensible');
                return false;
            });
            
            // Deshabilitar F12 y combinaciones de teclas de desarrollo
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F12' || 
                    (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                    (e.ctrlKey && e.shiftKey && e.key === 'J') ||
                    (e.ctrlKey && e.key === 'U')) {
                    e.preventDefault();
                    console.warn('Herramientas de desarrollo deshabilitadas en modo producción');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
