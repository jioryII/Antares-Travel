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
<body class="bg-gray-100">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen bg-gray-100">
            <div class="p-6 lg:p-10">
                <!-- Encabezado -->
                <div class="mb-8 lg:mb-12">
                    <br><br><br>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-3xl lg:text-4xl font-light text-gray-900 mb-2">
                                    Mi Perfil Profesional
                                </h1>
                                <p class="text-gray-600 font-medium">Panel de administración • Gestión de cuenta</p>
                            </div>
                            <div class="hidden lg:block">
                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">Última sesión</p>
                                        <p class="font-semibold text-gray-900"><?php echo date('d/m/Y H:i'); ?></p>
                                    </div>
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-shield-alt text-green-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Usuario -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 lg:gap-10 mb-8 lg:mb-12">
                    <!-- Tarjeta de Perfil Principal -->
                    <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-slate-900 to-blue-900 px-8 py-10">
                            <div class="flex items-center">
                                <div class="w-24 h-24 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-8 backdrop-blur-sm">
                                    <i class="fas fa-user-tie text-white text-4xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h2 class="text-3xl font-light text-white mb-2"><?php echo htmlspecialchars($admin['nombre']); ?></h2>
                                    <p class="text-blue-100 text-lg mb-3"><?php echo htmlspecialchars($admin['email']); ?></p>
                                    <div class="flex items-center space-x-4">
                                        <span class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 backdrop-blur-sm rounded-full text-white font-medium">
                                            <i class="fas fa-star mr-2"></i><?php echo htmlspecialchars($admin['rol']); ?>
                                        </span>
                                        <span class="inline-flex items-center px-4 py-2 bg-green-500 bg-opacity-20 backdrop-blur-sm rounded-full text-green-100 font-medium">
                                            <i class="fas fa-circle text-green-400 mr-2 text-xs"></i>En línea
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Información de la Sesión</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Último acceso</span>
                                        <span class="text-gray-900 font-semibold"><?php echo date('d/m/Y H:i'); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Estado de cuenta</span>
                                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                            <i class="fas fa-check-circle mr-1"></i>Verificada
                                        </span>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">ID de Sesión</span>
                                        <span class="font-mono text-sm text-gray-800 bg-gray-50 px-3 py-1 rounded-lg">
                                            <?php echo substr(session_id(), 0, 12) . '...'; ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Nivel de acceso</span>
                                        <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                            <i class="fas fa-key mr-1"></i>Completo
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de Estadísticas -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-chart-line text-blue-600 mr-3"></i>Resumen Ejecutivo
                        </h3>
                        <div class="space-y-6">
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-blue-600 font-medium text-sm">Tours Disponibles</p>
                                        <p class="text-2xl font-bold text-blue-800"><?php echo number_format($stats['total_tours'] ?? 0); ?></p>
                                    </div>
                                    <i class="fas fa-map-marked-alt text-blue-600 text-2xl"></i>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-green-600 font-medium text-sm">Reservas Totales</p>
                                        <p class="text-2xl font-bold text-green-800"><?php echo number_format($stats['total_reservas'] ?? 0); ?></p>
                                    </div>
                                    <i class="fas fa-calendar-check text-green-600 text-2xl"></i>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-purple-600 font-medium text-sm">Usuarios Activos</p>
                                        <p class="text-2xl font-bold text-purple-800"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></p>
                                    </div>
                                    <i class="fas fa-users text-purple-600 text-2xl"></i>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-orange-600 font-medium text-sm">Pendientes</p>
                                        <p class="text-2xl font-bold text-orange-800"><?php echo number_format($stats['reservas_pendientes'] ?? 0); ?></p>
                                    </div>
                                    <i class="fas fa-clock text-orange-600 text-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Debug (Solo si DEBUG_MODE está activo) -->
                <?php if (DEBUG_MODE): ?>
                <div class="mb-8 lg:mb-12">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-8 py-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-light text-white flex items-center">
                                        <i class="fas fa-code mr-3"></i>Panel de Desarrollo
                                    </h3>
                                    <p class="text-slate-300 text-sm mt-1">Información técnica y diagnósticos del sistema</p>
                                </div>
                                <span class="px-4 py-2 bg-yellow-500 bg-opacity-20 backdrop-blur-sm rounded-full text-yellow-200 text-sm font-medium">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Modo Debug Activo
                                </span>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                <!-- Información del Usuario Admin -->
                                <div class="bg-slate-50 rounded-lg p-6 border border-slate-200">
                                    <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                        <i class="fas fa-user-shield text-blue-600 mr-2"></i>Administrador
                                    </h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Nombre:</span>
                                            <span class="font-medium text-slate-900"><?php echo htmlspecialchars($admin['nombre']); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Email:</span>
                                            <span class="font-medium text-slate-900"><?php echo htmlspecialchars($admin['email']); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Rol:</span>
                                            <span class="font-medium text-slate-900"><?php echo htmlspecialchars($admin['rol']); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">ID:</span>
                                            <span class="font-mono text-slate-900"><?php echo htmlspecialchars($admin['id'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estadísticas de Debug -->
                                <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                                    <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                        <i class="fas fa-database text-blue-600 mr-2"></i>Base de Datos
                                    </h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Estadísticas:</span>
                                            <span class="font-medium text-slate-900"><?php echo count($stats); ?> registros</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Reservas recientes:</span>
                                            <span class="font-medium text-slate-900"><?php echo count($reservas_recientes); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Debug activo:</span>
                                            <span class="font-medium text-green-600"><?php echo DEBUG_MODE ? 'Sí' : 'No'; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Base de datos:</span>
                                            <span class="font-mono text-slate-900"><?php echo DB_NAME; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Sistema -->
                                <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                                    <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                        <i class="fas fa-server text-green-600 mr-2"></i>Sistema
                                    </h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">PHP:</span>
                                            <span class="font-medium text-slate-900"><?php echo $system_info['php_version']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Zona horaria:</span>
                                            <span class="font-medium text-slate-900"><?php echo $system_info['timezone']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Memoria:</span>
                                            <span class="font-medium text-slate-900"><?php echo $system_info['memory_usage']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Pico memoria:</span>
                                            <span class="font-medium text-slate-900"><?php echo $system_info['memory_peak']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información Detallada del Sistema -->
                            <div class="mt-8 bg-slate-50 rounded-lg p-6 border border-slate-200">
                                <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                    <i class="fas fa-info-circle text-slate-600 mr-2"></i>Detalles Técnicos del Servidor
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-sm">
                                    <div class="space-y-3">
                                        <div>
                                            <span class="text-slate-600 block">Servidor:</span>
                                            <span class="font-mono text-slate-900"><?php echo $system_info['server_software']; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">Tiempo actual:</span>
                                            <span class="font-mono text-slate-900"><?php echo $system_info['current_time']; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">Session ID:</span>
                                            <span class="font-mono text-slate-900 text-xs"><?php echo $system_info['session_id']; ?></span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <span class="text-slate-600 block">Document Root:</span>
                                            <span class="font-mono text-slate-900 text-xs break-all"><?php echo $system_info['document_root']; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">User Agent:</span>
                                            <span class="font-mono text-slate-900 text-xs break-all"><?php echo substr($system_info['user_agent'], 0, 80) . '...'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración de la Aplicación -->
                            <div class="mt-8 bg-purple-50 rounded-lg p-6 border border-purple-200">
                                <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                    <i class="fas fa-cogs text-purple-600 mr-2"></i>Configuración de la Aplicación
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 text-sm">
                                    <div class="space-y-3">
                                        <div>
                                            <span class="text-slate-600 block">Base URL:</span>
                                            <span class="font-mono text-slate-900"><?php echo BASE_URL; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">Admin URL:</span>
                                            <span class="font-mono text-slate-900"><?php echo ADMIN_URL; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">Nombre del sitio:</span>
                                            <span class="font-mono text-slate-900"><?php echo SITE_NAME; ?></span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <span class="text-slate-600 block">DB Host:</span>
                                            <span class="font-mono text-slate-900"><?php echo DB_HOST; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">DB Name:</span>
                                            <span class="font-mono text-slate-900"><?php echo DB_NAME; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">DB Charset:</span>
                                            <span class="font-mono text-slate-900"><?php echo DB_CHARSET; ?></span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <span class="text-slate-600 block">Session Lifetime:</span>
                                            <span class="font-mono text-slate-900"><?php echo SESSION_LIFETIME; ?>s</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">Max File Size:</span>
                                            <span class="font-mono text-slate-900"><?php echo formatBytes(MAX_FILE_SIZE); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 block">Records per Page:</span>
                                            <span class="font-mono text-slate-900"><?php echo RECORDS_PER_PAGE; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Panel de Acciones Profesionales -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                    <div class="mb-8">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Centro de Control</h3>
                        <p class="text-gray-600">Gestión rápida de funciones administrativas</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Editar Perfil -->
                        <div class="group bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200 hover:border-blue-300 transition-all duration-300 hover:shadow-lg">
                            <div class="flex flex-col h-full">
                                <div class="w-14 h-14 bg-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-user-edit text-white text-xl"></i>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-2">Editar Perfil</h4>
                                <p class="text-sm text-gray-600 mb-4 flex-grow">Actualizar información personal y preferencias</p>
                                <button class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                    Modificar
                                </button>
                            </div>
                        </div>

                        <!-- Seguridad -->
                        <div class="group bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200 hover:border-green-300 transition-all duration-300 hover:shadow-lg">
                            <div class="flex flex-col h-full">
                                <div class="w-14 h-14 bg-green-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-shield-alt text-white text-xl"></i>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-2">Seguridad</h4>
                                <p class="text-sm text-gray-600 mb-4 flex-grow">Cambiar contraseña y configurar autenticación</p>
                                <button class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                                    Configurar
                                </button>
                            </div>
                        </div>

                        <!-- Preferencias -->
                        <div class="group bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200 hover:border-purple-300 transition-all duration-300 hover:shadow-lg">
                            <div class="flex flex-col h-full">
                                <div class="w-14 h-14 bg-purple-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-cog text-white text-xl"></i>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-2">Preferencias</h4>
                                <p class="text-sm text-gray-600 mb-4 flex-grow">Notificaciones y configuración de la interfaz</p>
                                <button class="w-full bg-purple-600 text-white py-3 rounded-lg hover:bg-purple-700 transition-colors font-medium">
                                    Personalizar
                                </button>
                            </div>
                        </div>

                        <!-- Cerrar Sesión -->
                        <div class="group bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-6 border border-slate-200 hover:border-red-300 transition-all duration-300 hover:shadow-lg">
                            <div class="flex flex-col h-full">
                                <div class="w-14 h-14 bg-slate-600 group-hover:bg-red-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-all duration-300">
                                    <i class="fas fa-sign-out-alt text-white text-xl"></i>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-2">Finalizar Sesión</h4>
                                <p class="text-sm text-gray-600 mb-4 flex-grow">Cerrar sesión de forma segura</p>
                                <a href="../../auth/logout.php" class="block w-full bg-slate-600 hover:bg-red-600 text-white py-3 rounded-lg transition-colors font-medium text-center">
                                    Salir
                                </a>
                            </div>
                        </div>
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
