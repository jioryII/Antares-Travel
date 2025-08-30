<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

$admin = obtenerAdminActual();
$page_title = "Mi Perfil";

// Obtener estadísticas para debug
$stats = getDashboardStats();
$reservas_recientes = getReservasRecientes(5);

// Información del sistema
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'No disponible',
    'current_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'memory_usage' => formatBytes(memory_get_usage(true)),
    'memory_peak' => formatBytes(memory_get_peak_usage(true)),
    'session_id' => session_id(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'No disponible'
];

// Función para formatear bytes
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-6 lg:mb-8">
                    <br><br><br>
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-user-circle text-blue-600 mr-3"></i>Mi Perfil
                    </h1>
                    <p class="text-sm lg:text-base text-gray-600">Información personal y configuración de cuenta</p>
                </div>

                <!-- Información del Usuario -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8 mb-6 lg:mb-8">
                    <!-- Tarjeta de Perfil -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-8">
                            <div class="flex items-center">
                                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-6">
                                    <i class="fas fa-user text-white text-3xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($admin['nombre']); ?></h2>
                                    <p class="text-blue-100"><?php echo htmlspecialchars($admin['email']); ?></p>
                                    <span class="inline-block mt-2 px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm text-white">
                                        <i class="fas fa-crown mr-1"></i><?php echo htmlspecialchars($admin['rol']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Último acceso:</span>
                                    <span class="font-medium"><?php echo date('d/m/Y H:i'); ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Estado:</span>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                        <i class="fas fa-circle text-green-500 mr-1"></i>Activo
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Sesión ID:</span>
                                    <span class="font-mono text-sm text-gray-800 bg-gray-100 px-2 py-1 rounded">
                                        <?php echo substr(session_id(), 0, 10) . '...'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas Rápidas -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Estadísticas Rápidas
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['total_tours'] ?? 0); ?></div>
                                <div class="text-sm text-gray-600">Tours Activos</div>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['total_reservas'] ?? 0); ?></div>
                                <div class="text-sm text-gray-600">Total Reservas</div>
                            </div>
                            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                <div class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></div>
                                <div class="text-sm text-gray-600">Usuarios</div>
                            </div>
                            <div class="text-center p-4 bg-purple-50 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600"><?php echo number_format($stats['reservas_pendientes'] ?? 0); ?></div>
                                <div class="text-sm text-gray-600">Pendientes</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Debug (Solo si DEBUG_MODE está activo) -->
                <?php if (DEBUG_MODE): ?>
                <div class="mb-6 lg:mb-8">
                    <div class="debug-card rounded-lg shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-white border-opacity-20">
                            <h3 class="text-xl font-semibold flex items-center">
                                <i class="fas fa-bug mr-3"></i>Información de Debug
                                <span class="ml-3 px-2 py-1 bg-white bg-opacity-20 rounded-full text-sm">
                                    Modo Desarrollo
                                </span>
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">Información técnica del sistema y aplicación</p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Información del Usuario Admin -->
                                <div class="bg-white bg-opacity-10 rounded-lg p-4">
                                    <h4 class="font-semibold text-white mb-3 flex items-center">
                                        <i class="fas fa-user-shield mr-2"></i>Usuario Admin
                                    </h4>
                                    <div class="space-y-2 text-sm text-blue-100">
                                        <div><strong>Nombre:</strong> <?php echo htmlspecialchars($admin['nombre']); ?></div>
                                        <div><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></div>
                                        <div><strong>Rol:</strong> <?php echo htmlspecialchars($admin['rol']); ?></div>
                                        <div><strong>ID:</strong> <?php echo htmlspecialchars($admin['id'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>

                                <!-- Estadísticas de Debug -->
                                <div class="bg-white bg-opacity-10 rounded-lg p-4">
                                    <h4 class="font-semibold text-white mb-3 flex items-center">
                                        <i class="fas fa-database mr-2"></i>Estadísticas
                                    </h4>
                                    <div class="space-y-2 text-sm text-blue-100">
                                        <div><strong>Total estadísticas:</strong> <?php echo count($stats); ?></div>
                                        <div><strong>Reservas recientes:</strong> <?php echo count($reservas_recientes); ?></div>
                                        <div><strong>Modo Debug:</strong> <?php echo DEBUG_MODE ? 'Activo' : 'Inactivo'; ?></div>
                                        <div><strong>Base de datos:</strong> <?php echo DB_NAME; ?></div>
                                    </div>
                                </div>

                                <!-- Información del Sistema -->
                                <div class="bg-white bg-opacity-10 rounded-lg p-4">
                                    <h4 class="font-semibold text-white mb-3 flex items-center">
                                        <i class="fas fa-server mr-2"></i>Sistema
                                    </h4>
                                    <div class="space-y-2 text-sm text-blue-100">
                                        <div><strong>PHP:</strong> <?php echo $system_info['php_version']; ?></div>
                                        <div><strong>Zona horaria:</strong> <?php echo $system_info['timezone']; ?></div>
                                        <div><strong>Memoria actual:</strong> <?php echo $system_info['memory_usage']; ?></div>
                                        <div><strong>Memoria pico:</strong> <?php echo $system_info['memory_peak']; ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información Detallada del Sistema -->
                            <div class="mt-6 bg-white bg-opacity-10 rounded-lg p-4">
                                <h4 class="font-semibold text-white mb-3 flex items-center">
                                    <i class="fas fa-info-circle mr-2"></i>Información Detallada del Sistema
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm text-blue-100">
                                    <div>
                                        <div class="mb-2"><strong>Servidor:</strong> <?php echo $system_info['server_software']; ?></div>
                                        <div class="mb-2"><strong>Tiempo actual:</strong> <?php echo $system_info['current_time']; ?></div>
                                        <div class="mb-2"><strong>Session ID:</strong> <span class="font-mono"><?php echo $system_info['session_id']; ?></span></div>
                                    </div>
                                    <div>
                                        <div class="mb-2"><strong>Document Root:</strong> <span class="font-mono text-xs"><?php echo $system_info['document_root']; ?></span></div>
                                        <div class="mb-2"><strong>User Agent:</strong> <span class="font-mono text-xs"><?php echo substr($system_info['user_agent'], 0, 50) . '...'; ?></span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración de la Aplicación -->
                            <div class="mt-6 bg-white bg-opacity-10 rounded-lg p-4">
                                <h4 class="font-semibold text-white mb-3 flex items-center">
                                    <i class="fas fa-cogs mr-2"></i>Configuración de la Aplicación
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-sm text-blue-100">
                                    <div>
                                        <div class="mb-2"><strong>Base URL:</strong> <?php echo BASE_URL; ?></div>
                                        <div class="mb-2"><strong>Admin URL:</strong> <?php echo ADMIN_URL; ?></div>
                                        <div class="mb-2"><strong>Site Name:</strong> <?php echo SITE_NAME; ?></div>
                                    </div>
                                    <div>
                                        <div class="mb-2"><strong>DB Host:</strong> <?php echo DB_HOST; ?></div>
                                        <div class="mb-2"><strong>DB Name:</strong> <?php echo DB_NAME; ?></div>
                                        <div class="mb-2"><strong>DB Charset:</strong> <?php echo DB_CHARSET; ?></div>
                                    </div>
                                    <div>
                                        <div class="mb-2"><strong>Session Lifetime:</strong> <?php echo SESSION_LIFETIME; ?>s</div>
                                        <div class="mb-2"><strong>Max File Size:</strong> <?php echo formatBytes(MAX_FILE_SIZE); ?></div>
                                        <div class="mb-2"><strong>Records per Page:</strong> <?php echo RECORDS_PER_PAGE; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Acciones Rápidas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                    <div class="info-card bg-white rounded-lg shadow p-6 text-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-edit text-blue-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Editar Perfil</h3>
                        <p class="text-sm text-gray-600 mb-4">Actualiza tu información personal</p>
                        <button class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Editar
                        </button>
                    </div>

                    <div class="info-card bg-white rounded-lg shadow p-6 text-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-key text-green-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Cambiar Contraseña</h3>
                        <p class="text-sm text-gray-600 mb-4">Actualiza tu contraseña</p>
                        <button class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition-colors">
                            Cambiar
                        </button>
                    </div>

                    <div class="info-card bg-white rounded-lg shadow p-6 text-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-bell text-yellow-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Notificaciones</h3>
                        <p class="text-sm text-gray-600 mb-4">Configura tus alertas</p>
                        <button class="w-full bg-yellow-600 text-white py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                            Configurar
                        </button>
                    </div>

                    <div class="info-card bg-white rounded-lg shadow p-6 text-center">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-sign-out-alt text-red-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Cerrar Sesión</h3>
                        <p class="text-sm text-gray-600 mb-4">Salir del sistema</p>
                        <a href="../../auth/logout.php" class="block w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition-colors">
                            Salir
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Log para debug
        console.log('Página de perfil cargada correctamente');
        <?php if (DEBUG_MODE): ?>
        console.log('Modo debug activo');
        console.log('Admin info:', <?php echo json_encode($admin); ?>);
        console.log('System info:', <?php echo json_encode($system_info); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
