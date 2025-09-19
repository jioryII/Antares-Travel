<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Mi Perfil";

// Obtener estadísticas básicas (sin información sensible)
$stats = getDashboardStats();

// Solo mostrar información técnica a SuperAdmin
$is_superadmin = isset($_SESSION['admin_rol']) && $_SESSION['admin_rol'] === 'superadmin';

// Información básica del sistema (solo para SuperAdmin)
$system_info = [];
if ($is_superadmin && DEBUG_MODE) {
    $system_info = [
        'php_version' => PHP_VERSION,
        'current_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get(),
        'memory_usage' => formatBytes(memory_get_usage(true)),
        'session_status' => 'Activa'
    ];
}

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
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../../../../imagenes/antares_logozz2.png">
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
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Información de la Cuenta</h3>
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
                                        <span class="text-gray-600 font-medium">Tipo de cuenta</span>
                                        <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                            <i class="fas fa-star mr-1"></i><?php echo ucfirst(htmlspecialchars($admin['rol'])); ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="text-gray-600 font-medium">Nivel de acceso</span>
                                        <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                            <i class="fas fa-key mr-1"></i>Autorizado
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

                <!-- Panel de Diagnóstico (Solo SuperAdmin en modo Debug) -->
                <?php if ($is_superadmin && DEBUG_MODE): ?>
                <div class="mb-8 lg:mb-12">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-8 py-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-light text-white flex items-center">
                                        <i class="fas fa-tools mr-3"></i>Panel de Diagnóstico
                                    </h3>
                                    <p class="text-slate-300 text-sm mt-1">Información técnica del sistema (Solo SuperAdmin)</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-3 py-1 bg-red-500 bg-opacity-20 backdrop-blur-sm rounded-full text-red-200 text-sm font-medium">
                                        <i class="fas fa-crown mr-1"></i>SuperAdmin
                                    </span>
                                    <span class="px-3 py-1 bg-yellow-500 bg-opacity-20 backdrop-blur-sm rounded-full text-yellow-200 text-sm font-medium">
                                        <i class="fas fa-bug mr-1"></i>Debug
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-amber-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-amber-800">Información de Diagnóstico</h4>
                                        <p class="mt-1 text-sm text-amber-700">
                                            Para acceder a información técnica detallada del sistema, visite el 
                                            <a href="../root/configuracion.php" class="font-semibold underline hover:text-amber-900">
                                                Panel de Configuración del Sistema
                                            </a> en la sección Root.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Información Básica del Sistema -->
                                <div class="bg-slate-50 rounded-lg p-6 border border-slate-200">
                                    <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                        <i class="fas fa-server text-blue-600 mr-2"></i>Estado del Sistema
                                    </h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">PHP Version:</span>
                                            <span class="font-medium text-slate-900"><?php echo $system_info['php_version']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Zona Horaria:</span>
                                            <span class="font-medium text-slate-900"><?php echo $system_info['timezone']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Memoria en Uso:</span>
                                            <span class="font-medium text-slate-900"><?php echo $system_info['memory_usage']; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estadísticas de la Aplicación -->
                                <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                                    <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                        <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Estadísticas
                                    </h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Tours:</span>
                                            <span class="font-medium text-slate-900"><?php echo number_format($stats['total_tours'] ?? 0); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Reservas:</span>
                                            <span class="font-medium text-slate-900"><?php echo number_format($stats['total_reservas'] ?? 0); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Usuarios:</span>
                                            <span class="font-medium text-slate-900"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado de la Sesión -->
                                <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                                    <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                        <i class="fas fa-user-shield text-green-600 mr-2"></i>Sesión Actual
                                    </h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Usuario:</span>
                                            <span class="font-medium text-slate-900"><?php echo htmlspecialchars($admin['nombre']); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Rol:</span>
                                            <span class="font-medium text-slate-900"><?php echo htmlspecialchars($admin['rol']); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Estado:</span>
                                            <span class="font-medium text-green-600"><?php echo $system_info['session_status']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enlaces a Herramientas Root -->
                            <div class="mt-8 bg-red-50 rounded-lg p-6 border border-red-200">
                                <h4 class="font-semibold text-slate-900 mb-4 flex items-center">
                                    <i class="fas fa-tools text-red-600 mr-2"></i>Herramientas de SuperAdmin
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <a href="../root/configuracion.php" class="flex items-center p-3 bg-white rounded-lg border hover:border-red-300 transition-colors group">
                                        <i class="fas fa-cogs text-red-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-700 group-hover:text-red-700">Configuración</span>
                                    </a>
                                    <a href="../root/logs.php" class="flex items-center p-3 bg-white rounded-lg border hover:border-red-300 transition-colors group">
                                        <i class="fas fa-file-alt text-red-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-700 group-hover:text-red-700">Logs</span>
                                    </a>
                                    <a href="../root/base_datos.php" class="flex items-center p-3 bg-white rounded-lg border hover:border-red-300 transition-colors group">
                                        <i class="fas fa-database text-red-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-700 group-hover:text-red-700">Base de Datos</span>
                                    </a>
                                    <a href="../root/administradores.php" class="flex items-center p-3 bg-white rounded-lg border hover:border-red-300 transition-colors group">
                                        <i class="fas fa-user-shield text-red-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-700 group-hover:text-red-700">Administradores</span>
                                    </a>
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
        // Inicialización del perfil de usuario
        console.log('Perfil cargado correctamente');
        
        <?php if ($is_superadmin && DEBUG_MODE): ?>
        // Información de debug solo para SuperAdmin
        console.log('SuperAdmin Debug Mode activo');
        console.log('Admin:', '<?php echo htmlspecialchars($admin['nombre']); ?>');
        console.log('Rol:', '<?php echo htmlspecialchars($admin['rol']); ?>');
        <?php else: ?>
        console.log('Modo usuario estándar');
        <?php endif; ?>
        
        // Funciones de interactividad
        document.addEventListener('DOMContentLoaded', function() {
            // Animaciones de entrada suaves
            const cards = document.querySelectorAll('.bg-white');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
