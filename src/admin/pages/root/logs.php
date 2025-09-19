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

$page_title = "Logs del Sistema";

// Obtener logs de PHP
$php_error_log = ini_get('error_log') ?: '/var/log/php_errors.log';
$apache_error_log = '/var/log/apache2/error.log';
$apache_access_log = '/var/log/apache2/access.log';

// Función para leer las últimas líneas de un archivo
function leerUltimasLineas($archivo, $lineas = 50) {
    if (!file_exists($archivo) || !is_readable($archivo)) {
        return "Archivo no encontrado o sin permisos de lectura: " . $archivo;
    }
    
    try {
        // Leer archivo con límite de memoria
        if (filesize($archivo) > 1024 * 1024) { // Si el archivo es mayor a 1MB
            // Usar tail shell para archivos grandes
            $comando = "tail -n {$lineas} " . escapeshellarg($archivo) . " 2>&1";
            $salida = shell_exec($comando);
            return $salida ?: "No se pudo leer el archivo";
        } else {
            // Leer archivo completo y tomar las últimas líneas
            $contenido = file($archivo, FILE_IGNORE_NEW_LINES);
            if ($contenido === false) {
                return "Error al leer el archivo";
            }
            
            $total_lineas = count($contenido);
            $inicio = max(0, $total_lineas - $lineas);
            $ultimas_lineas = array_slice($contenido, $inicio);
            
            return implode("\n", $ultimas_lineas);
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para obtener información del archivo
function obtenerInfoArchivo($archivo) {
    if (!file_exists($archivo)) {
        return [
            'existe' => false,
            'tamaño' => 'N/A',
            'modificado' => 'N/A',
            'permisos' => 'N/A'
        ];
    }
    
    return [
        'existe' => true,
        'tamaño' => formatBytes(filesize($archivo)),
        'modificado' => date('Y-m-d H:i:s', filemtime($archivo)),
        'permisos' => substr(sprintf('%o', fileperms($archivo)), -4)
    ];
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Logs de la aplicación
$app_logs_dir = dirname(dirname(dirname(__DIR__))) . '/logs/';
$app_error_log = $app_logs_dir . 'error.log';
$app_access_log = $app_logs_dir . 'access.log';

// Crear directorio de logs si no existe
if (!is_dir($app_logs_dir)) {
    mkdir($app_logs_dir, 0755, true);
}

// Obtener logs recientes de la base de datos si existe tabla de logs
$db_logs = [];
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'logs_sistema'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT * FROM logs_sistema ORDER BY fecha_hora DESC LIMIT 100");
        $stmt->execute();
        $db_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Tabla de logs no existe
}

// Información de los archivos de log
$logs_info = [
    'PHP Error Log' => obtenerInfoArchivo($php_error_log),
    'Apache Error Log' => obtenerInfoArchivo($apache_error_log),
    'Apache Access Log' => obtenerInfoArchivo($apache_access_log),
    'App Error Log' => obtenerInfoArchivo($app_error_log),
    'App Access Log' => obtenerInfoArchivo($app_access_log)
];

// Procesar acciones
if ($_POST && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'limpiar_logs':
            $archivo = $_POST['archivo'];
            if (in_array($archivo, [$app_error_log, $app_access_log])) {
                file_put_contents($archivo, '');
                header('Location: logs.php?success=logs_limpiados');
                exit;
            }
            break;
    }
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
                <!-- Encabezado -->
                <div class="mb-8">
                    <br><br><br>
                    <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-xl shadow-lg border border-red-700 p-6 lg:p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-3xl lg:text-4xl font-light text-white mb-2 flex items-center">
                                    <i class="fas fa-file-alt mr-4"></i>Logs del Sistema
                                </h1>
                                <p class="text-red-100 font-medium">Panel de SuperAdmin • Monitoreo y Diagnóstico</p>
                            </div>
                            <div class="hidden lg:block">
                                <span class="px-4 py-2 bg-red-500 bg-opacity-30 backdrop-blur-sm rounded-full text-red-100 text-sm font-medium">
                                    <i class="fas fa-crown mr-2"></i>SuperAdmin Only
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de estado -->
                <?php if (isset($_GET['success'])): ?>
                <div class="mb-6">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-600 mt-0.5 mr-3"></i>
                            <div class="text-green-800">
                                Logs limpiados correctamente.
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Información de archivos de log -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-3"></i>Estado de Archivos de Log
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($logs_info as $nombre => $info): ?>
                        <div class="border rounded-lg p-4 <?php echo $info['existe'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'; ?>">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900"><?php echo $nombre; ?></h4>
                                <i class="fas <?php echo $info['existe'] ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600'; ?>"></i>
                            </div>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div>Tamaño: <?php echo $info['tamaño']; ?></div>
                                <div>Modificado: <?php echo $info['modificado']; ?></div>
                                <div>Permisos: <?php echo $info['permisos']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tabs de logs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" id="log-tabs">
                            <button onclick="mostrarLog('php-error')" 
                                    class="log-tab active border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                PHP Error Log
                            </button>
                            <button onclick="mostrarLog('apache-error')" 
                                    class="log-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                Apache Error
                            </button>
                            <button onclick="mostrarLog('apache-access')" 
                                    class="log-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                Apache Access
                            </button>
                            <button onclick="mostrarLog('app-error')" 
                                    class="log-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                App Error
                            </button>
                            <button onclick="mostrarLog('app-access')" 
                                    class="log-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                App Access
                            </button>
                        </nav>
                    </div>

                    <!-- PHP Error Log -->
                    <div id="php-error" class="log-content">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">PHP Error Log</h3>
                                <div class="text-sm text-gray-500">Últimas 50 líneas</div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap"><?php echo htmlspecialchars(leerUltimasLineas($php_error_log)); ?></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Apache Error Log -->
                    <div id="apache-error" class="log-content hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Apache Error Log</h3>
                                <div class="text-sm text-gray-500">Últimas 50 líneas</div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-red-400 text-sm font-mono whitespace-pre-wrap"><?php echo htmlspecialchars(leerUltimasLineas($apache_error_log)); ?></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Apache Access Log -->
                    <div id="apache-access" class="log-content hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Apache Access Log</h3>
                                <div class="text-sm text-gray-500">Últimas 50 líneas</div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-blue-400 text-sm font-mono whitespace-pre-wrap"><?php echo htmlspecialchars(leerUltimasLineas($apache_access_log)); ?></pre>
                            </div>
                        </div>
                    </div>

                    <!-- App Error Log -->
                    <div id="app-error" class="log-content hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Application Error Log</h3>
                                <div class="flex items-center space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="accion" value="limpiar_logs">
                                        <input type="hidden" name="archivo" value="<?php echo $app_error_log; ?>">
                                        <button type="submit" 
                                                onclick="return confirm('¿Está seguro de que desea limpiar este log?')"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                            <i class="fas fa-trash-alt mr-1"></i>Limpiar
                                        </button>
                                    </form>
                                    <div class="text-sm text-gray-500">Últimas 50 líneas</div>
                                </div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-red-400 text-sm font-mono whitespace-pre-wrap"><?php echo htmlspecialchars(leerUltimasLineas($app_error_log)); ?></pre>
                            </div>
                        </div>
                    </div>

                    <!-- App Access Log -->
                    <div id="app-access" class="log-content hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Application Access Log</h3>
                                <div class="flex items-center space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="accion" value="limpiar_logs">
                                        <input type="hidden" name="archivo" value="<?php echo $app_access_log; ?>">
                                        <button type="submit" 
                                                onclick="return confirm('¿Está seguro de que desea limpiar este log?')"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                            <i class="fas fa-trash-alt mr-1"></i>Limpiar
                                        </button>
                                    </form>
                                    <div class="text-sm text-gray-500">Últimas 50 líneas</div>
                                </div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-blue-400 text-sm font-mono whitespace-pre-wrap"><?php echo htmlspecialchars(leerUltimasLineas($app_access_log)); ?></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($db_logs)): ?>
                <!-- Logs de la base de datos -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-database text-purple-600 mr-3"></i>Logs de la Base de Datos
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nivel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detalles</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach (array_slice($db_logs, 0, 20) as $log): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['fecha_hora'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php 
                                            switch($log['nivel']) {
                                                case 'error':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                case 'warning':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'info':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($log['nivel']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($log['usuario'] ?? 'Sistema'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($log['accion']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                        <?php echo htmlspecialchars($log['detalles']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function mostrarLog(logId) {
            // Ocultar todos los contenidos de log
            document.querySelectorAll('.log-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remover clase active de todos los tabs
            document.querySelectorAll('.log-tab').forEach(tab => {
                tab.classList.remove('active', 'border-red-500', 'text-red-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar el contenido seleccionado
            document.getElementById(logId).classList.remove('hidden');
            
            // Activar el tab seleccionado
            event.target.classList.remove('border-transparent', 'text-gray-500');
            event.target.classList.add('active', 'border-red-500', 'text-red-600');
        }
        
        // Auto-refresh cada 30 segundos
        setInterval(() => {
            if (confirm('¿Desea actualizar los logs?')) {
                location.reload();
            }
        }, 30000);
        
        // Log de acceso
        console.log('Panel de Logs del Sistema accedido por SuperAdmin');
        console.log('Usuario:', '<?php echo htmlspecialchars($admin['nombre']); ?>');
        console.log('Timestamp:', '<?php echo date('Y-m-d H:i:s'); ?>');
    </script>
</body>
</html>
