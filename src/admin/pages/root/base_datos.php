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

$page_title = "Gestión de Base de Datos";

// Función para obtener información de la base de datos
function obtenerInfoBD($pdo) {
    $info = [];
    
    try {
        // Información de la conexión
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $info['nombre_bd'] = $stmt->fetch(PDO::FETCH_ASSOC)['db_name'];
        
        // Versión de MySQL
        $stmt = $pdo->query("SELECT VERSION() as version");
        $info['version_mysql'] = $stmt->fetch(PDO::FETCH_ASSOC)['version'];
        
        // Tamaño de la base de datos
        $stmt = $pdo->prepare("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $stmt->execute();
        $info['tamaño_mb'] = $stmt->fetch(PDO::FETCH_ASSOC)['size_mb'];
        
        // Número de tablas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as tabla_count 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $stmt->execute();
        $info['num_tablas'] = $stmt->fetch(PDO::FETCH_ASSOC)['tabla_count'];
        
        // Información detallada de tablas
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(table_name, 'N/A') as table_name,
                COALESCE(table_rows, 0) as table_rows,
                COALESCE(ROUND(((data_length + index_length) / 1024 / 1024), 2), 0) AS size_mb,
                COALESCE(engine, 'N/A') as engine,
                COALESCE(table_collation, 'N/A') as table_collation,
                create_time
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            AND table_type = 'BASE TABLE'
            ORDER BY COALESCE((data_length + index_length), 0) DESC
        ");
        $stmt->execute();
        $tablas_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filtrar tablas válidas y asegurar que tengan datos completos
        $info['tablas'] = array_filter($tablas_result, function($tabla) {
            return !empty($tabla['table_name']) && $tabla['table_name'] !== 'N/A';
        });
        
    } catch (PDOException $e) {
        error_log("Error al obtener información de BD: " . $e->getMessage());
        $info['error'] = $e->getMessage();
    }
    
    return $info;
}

// Función para ejecutar consulta personalizada PDO
function ejecutarConsultaPDO($pdo, $query) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        // Si es SELECT, retornar resultados
        if (stripos($query, 'SELECT') === 0) {
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'rows_affected' => $stmt->rowCount()
            ];
        } else {
            // Para INSERT, UPDATE, DELETE
            return [
                'success' => true,
                'rows_affected' => $stmt->rowCount()
            ];
        }
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Obtener información de la base de datos
$db_info = obtenerInfoBD($pdo);

// Verificar si hay errores en la obtención de información
if (isset($db_info['error'])) {
    $error_bd = $db_info['error'];
}

// Procesar acciones
$query_result = null;
if ($_POST && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'ejecutar_consulta':
            $consulta = trim($_POST['consulta']);
            if (!empty($consulta)) {
                // Validaciones de seguridad básicas
                $consultas_peligrosas = ['DROP DATABASE', 'DROP SCHEMA', 'TRUNCATE mysql', 'DELETE FROM mysql'];
                $consulta_upper = strtoupper($consulta);
                
                $es_peligrosa = false;
                foreach ($consultas_peligrosas as $peligrosa) {
                    if (strpos($consulta_upper, $peligrosa) !== false) {
                        $es_peligrosa = true;
                        break;
                    }
                }
                
                if ($es_peligrosa) {
                    $query_result = [
                        'success' => false,
                        'error' => 'Consulta bloqueada por seguridad'
                    ];
                } else {
                    $query_result = ejecutarConsultaPDO($pdo, $consulta);
                }
            }
            break;
            
        case 'optimizar_tabla':
            $tabla = $_POST['tabla'];
            $query_result = ejecutarConsultaPDO($pdo, "OPTIMIZE TABLE `{$tabla}`");
            break;
            
        case 'reparar_tabla':
            $tabla = $_POST['tabla'];
            $query_result = ejecutarConsultaPDO($pdo, "REPAIR TABLE `{$tabla}`");
            break;
    }
}

// Consultas predefinidas útiles
$consultas_predefinidas = [
    'Usuarios activos' => "SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'",
    'Reservas del mes' => "SELECT COUNT(*) as total FROM reservas WHERE MONTH(fecha_reserva) = MONTH(CURDATE())",
    'Tours más populares' => "SELECT t.nombre, COUNT(r.id) as reservas FROM tours t LEFT JOIN reservas r ON t.id = r.tour_id GROUP BY t.id ORDER BY reservas DESC LIMIT 10",
    'Administradores' => "SELECT id, nombre, email, rol, estado FROM administradores ORDER BY fecha_creacion DESC",
    'Estadísticas generales' => "SELECT 'Tours' as tipo, COUNT(*) as total FROM tours UNION SELECT 'Usuarios', COUNT(*) FROM usuarios UNION SELECT 'Reservas', COUNT(*) FROM reservas"
];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
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
                                    <i class="fas fa-database mr-4"></i>Gestión de Base de Datos
                                </h1>
                                <p class="text-red-100 font-medium">Panel de SuperAdmin • Administración de BD</p>
                            </div>
                            <div class="hidden lg:block">
                                <div class="flex items-center space-x-4">
                                    <span class="px-4 py-2 bg-red-500 bg-opacity-30 backdrop-blur-sm rounded-full text-red-100 text-sm font-medium">
                                        <i class="fas fa-crown mr-2"></i>SuperAdmin Only
                                    </span>
                                    <span class="px-4 py-2 bg-yellow-500 bg-opacity-30 backdrop-blur-sm rounded-full text-yellow-100 text-sm font-medium">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>¡Cuidado!
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de error de BD si existe -->
                <?php if (isset($error_bd)): ?>
                <div class="mb-6">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-600 mt-0.5 mr-3"></i>
                            <div class="text-red-800">
                                <strong>Error de Base de Datos:</strong> <?php echo htmlspecialchars($error_bd); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Información de la base de datos -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg mr-4">
                                <i class="fas fa-database text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Base de Datos</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($db_info['nombre_bd'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg mr-4">
                                <i class="fas fa-table text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Tablas</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo intval($db_info['num_tablas'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg mr-4">
                                <i class="fas fa-hdd text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Tamaño</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo number_format($db_info['tamaño_mb'] ?? 0, 2); ?> MB</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-orange-100 rounded-lg mr-4">
                                <i class="fas fa-server text-orange-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">MySQL</p>
                                <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars(explode('-', $db_info['version_mysql'] ?? 'N/A')[0]); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ejecutor de consultas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-terminal text-gray-600 mr-3"></i>Ejecutor de Consultas SQL
                    </h3>
                    
                    <!-- Advertencia de seguridad -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-600 mt-0.5 mr-3"></i>
                            <div class="text-red-800">
                                <strong>¡ADVERTENCIA!</strong> Las consultas se ejecutan directamente en la base de datos. 
                                Tenga extremo cuidado con las operaciones de modificación (UPDATE, DELETE, DROP).
                            </div>
                        </div>
                    </div>

                    <form method="POST" class="mb-6">
                        <input type="hidden" name="accion" value="ejecutar_consulta">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Consulta SQL
                            </label>
                            <textarea 
                                name="consulta" 
                                id="sql-editor" 
                                class="w-full h-32 p-3 border border-gray-300 rounded-lg font-mono text-sm"
                                placeholder="SELECT * FROM usuarios LIMIT 10;"><?php echo isset($_POST['consulta']) ? htmlspecialchars($_POST['consulta']) : ''; ?></textarea>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-2">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                                    <i class="fas fa-play mr-2"></i>Ejecutar
                                </button>
                                <button type="button" onclick="limpiarEditor()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                                    <i class="fas fa-eraser mr-1"></i>Limpiar
                                </button>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <label for="consultas-predefinidas" class="text-sm text-gray-600">Consultas rápidas:</label>
                                <select id="consultas-predefinidas" class="border border-gray-300 rounded px-3 py-1 text-sm" onchange="cargarConsultaPredefinida()">
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($consultas_predefinidas as $nombre => $consulta): ?>
                                    <option value="<?php echo htmlspecialchars($consulta); ?>"><?php echo $nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>

                    <!-- Resultado de la consulta -->
                    <?php if ($query_result): ?>
                    <div class="border-t pt-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-chart-bar text-gray-600 mr-2"></i>Resultado de la Consulta
                        </h4>
                        
                        <?php if ($query_result['success']): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <div class="text-green-800">
                                <i class="fas fa-check-circle mr-2"></i>
                                Consulta ejecutada correctamente. Filas afectadas: <?php echo $query_result['rows_affected']; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($query_result['data']) && !empty($query_result['data'])): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <?php foreach (array_keys($query_result['data'][0]) as $columna): ?>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                                            <?php echo htmlspecialchars($columna); ?>
                                        </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($query_result['data'] as $fila): ?>
                                    <tr class="hover:bg-gray-50">
                                        <?php foreach ($fila as $valor): ?>
                                        <td class="px-4 py-2 text-sm text-gray-900 border-r border-gray-200">
                                            <?php echo htmlspecialchars($valor ?? 'NULL'); ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="text-red-800">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                Error: <?php echo htmlspecialchars($query_result['error']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Información de tablas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-table text-gray-600 mr-3"></i>Información de Tablas
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tabla</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamaño</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colación</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creada</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($db_info['tablas'] ?? [] as $tabla): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($tabla['table_name'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($tabla['table_rows'] ?? 0); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo ($tabla['size_mb'] ?? 0); ?> MB
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($tabla['engine'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($tabla['table_collation'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo ($tabla['create_time'] ?? null) ? date('d/m/Y', strtotime($tabla['create_time'])) : 'N/A'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if (!empty($tabla['table_name'])): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="accion" value="optimizar_tabla">
                                                <input type="hidden" name="tabla" value="<?php echo htmlspecialchars($tabla['table_name']); ?>">
                                                <button type="submit" 
                                                        onclick="return confirm('¿Optimizar la tabla <?php echo htmlspecialchars($tabla['table_name']); ?>?')"
                                                        class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-tools"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="accion" value="reparar_tabla">
                                                <input type="hidden" name="tabla" value="<?php echo htmlspecialchars($tabla['table_name']); ?>">
                                                <button type="submit" 
                                                        onclick="return confirm('¿Reparar la tabla <?php echo htmlspecialchars($tabla['table_name']); ?>?')"
                                                        class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-wrench"></i>
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <span class="text-gray-400">No disponible</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configurar CodeMirror para el editor SQL
        const sqlEditor = CodeMirror.fromTextArea(document.getElementById('sql-editor'), {
            mode: 'text/x-sql',
            theme: 'default',
            lineNumbers: true,
            indentUnit: 4,
            indentWithTabs: true,
            autoCloseBrackets: true,
            matchBrackets: true
        });

        function limpiarEditor() {
            sqlEditor.setValue('');
        }

        function cargarConsultaPredefinida() {
            const select = document.getElementById('consultas-predefinidas');
            if (select.value) {
                sqlEditor.setValue(select.value);
                select.value = '';
            }
        }
        
        // Log de acceso
        console.log('Panel de Gestión de BD accedido por SuperAdmin');
        console.log('Usuario:', '<?php echo htmlspecialchars($admin['nombre']); ?>');
        console.log('Timestamp:', '<?php echo date('Y-m-d H:i:s'); ?>');
        
        // Advertencia adicional
        console.warn('ATENCIÓN: Acceso a herramientas de administración de base de datos');
    </script>
</body>
</html>
