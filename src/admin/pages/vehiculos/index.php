<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Mensajes de éxito y error
$mensaje_success = $_GET['success'] ?? null;
$mensaje_error = $_GET['error'] ?? null;

try {
    $connection = getConnection();
    
    // Obtener filtros
    $busqueda = $_GET['busqueda'] ?? '';
    $chofer_filter = $_GET['chofer'] ?? '';
    $estado_filter = $_GET['estado'] ?? '';
    
    // Construir query base
    $where_conditions = [];
    $params = [];
    
    if (!empty($busqueda)) {
        $where_conditions[] = "(v.marca LIKE ? OR v.modelo LIKE ? OR v.placa LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    if (!empty($chofer_filter)) {
        if ($chofer_filter === 'sin_asignar') {
            $where_conditions[] = "v.id_chofer IS NULL";
        } else {
            $where_conditions[] = "v.id_chofer = ?";
            $params[] = $chofer_filter;
        }
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Obtener vehículos con estadísticas
    $vehiculos_sql = "SELECT v.*,
                             CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellido, '')) as chofer_nombre,
                             c.telefono as chofer_telefono,
                             COALESCE(tours_stats.total_tours, 0) as total_tours,
                             COALESCE(tours_stats.tours_proximos, 0) as tours_proximos,
                             COALESCE(tours_stats.ultimo_tour, 'Nunca') as ultimo_tour,
                             COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados,
                             COALESCE(disponibilidad_stats.dias_libres, 0) as dias_libres
                      FROM vehiculos v
                      LEFT JOIN choferes c ON v.id_chofer = c.id_chofer
                      LEFT JOIN (
                          SELECT id_vehiculo,
                                 COUNT(*) as total_tours,
                                 SUM(CASE WHEN fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos,
                                 MAX(fecha) as ultimo_tour
                          FROM tours_diarios
                          GROUP BY id_vehiculo
                      ) tours_stats ON v.id_vehiculo = tours_stats.id_vehiculo
                      LEFT JOIN (
                          SELECT id_vehiculo,
                                 SUM(CASE WHEN estado = 'Ocupado' AND fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados,
                                 SUM(CASE WHEN estado = 'Libre' AND fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_libres
                          FROM disponibilidad_vehiculos
                          GROUP BY id_vehiculo
                      ) disponibilidad_stats ON v.id_vehiculo = disponibilidad_stats.id_vehiculo
                      $where_clause
                      ORDER BY v.marca, v.modelo";
    
    $vehiculos_stmt = $connection->prepare($vehiculos_sql);
    $vehiculos_stmt->execute($params);
    $vehiculos = $vehiculos_stmt->fetchAll();
    
    // Obtener estadísticas generales
    $estadisticas_sql = "SELECT 
                            COALESCE(COUNT(*), 0) as total_vehiculos,
                            COALESCE(SUM(CASE WHEN id_chofer IS NOT NULL THEN 1 ELSE 0 END), 0) as con_chofer,
                            COALESCE(SUM(CASE WHEN id_chofer IS NULL THEN 1 ELSE 0 END), 0) as sin_chofer,
                            COALESCE(AVG(capacidad), 0) as capacidad_promedio
                         FROM vehiculos";
    
    $estadisticas_stmt = $connection->prepare($estadisticas_sql);
    $estadisticas_stmt->execute();
    $estadisticas = $estadisticas_stmt->fetch();
    
    // Obtener tours activos hoy
    $tours_hoy_sql = "SELECT COALESCE(COUNT(*), 0) as tours_hoy
                      FROM tours_diarios td
                      INNER JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo
                      WHERE td.fecha = CURDATE()";
    
    $tours_hoy_stmt = $connection->prepare($tours_hoy_sql);
    $tours_hoy_stmt->execute();
    $tours_hoy_result = $tours_hoy_stmt->fetch();
    $tours_hoy = $tours_hoy_result['tours_hoy'] ?? 0;
    
    // Obtener lista de choferes para filtro
    $choferes_sql = "SELECT id_chofer, nombre, apellido FROM choferes ORDER BY nombre, apellido";
    $choferes_stmt = $connection->prepare($choferes_sql);
    $choferes_stmt->execute();
    $choferes = $choferes_stmt->fetchAll();
    
    $page_title = "Gestión de Vehículos";
    
} catch (Exception $e) {
    $error = "Error al cargar vehículos: " . $e->getMessage();
    $vehiculos = [];
    $estadisticas = ['total_vehiculos' => 0, 'con_chofer' => 0, 'sin_chofer' => 0, 'capacidad_promedio' => 0];
    $tours_hoy = 0;
    $choferes = [];
    $page_title = "Error - Vehículos";
}

// Función helper para number_format seguro
function formatNumber($value, $decimales = 0, $separador_decimal = '.', $separador_miles = ',') {
    if ($value === null || $value === '') {
        return '0';
    }
    return number_format((float)$value, $decimales, $separador_decimal, $separador_miles);
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
    <style>
        @media (max-width: 768px) {
            .table-responsive {
                display: none;
            }
            .cards-responsive {
                display: block;
            }
        }
        @media (min-width: 769px) {
            .table-responsive {
                display: block;
            }
            .cards-responsive {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-8">
                    <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                                <i class="fas fa-car text-blue-600 mr-3"></i>Gestión de Vehículos
                            </h1>
                            <p class="text-gray-600 mt-2">Administra la flota de vehículos y sus asignaciones</p>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="exportarVehiculos()" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Exportar
                            </button>
                            <a href="crear.php" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Nuevo Vehículo
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <p class="text-sm text-red-700 mt-1"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas principales -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100">
                                <i class="fas fa-car text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Vehículos</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo formatNumber($estadisticas['total_vehiculos']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100">
                                <i class="fas fa-user-check text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Con Chofer</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo formatNumber($estadisticas['con_chofer']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-orange-100">
                                <i class="fas fa-user-times text-orange-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Disponibles</p>
                                <p class="text-2xl font-bold text-orange-600"><?php echo formatNumber($estadisticas['sin_chofer']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100">
                                <i class="fas fa-calendar-day text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Tours Hoy</p>
                                <p class="text-2xl font-bold text-purple-600"><?php echo formatNumber($tours_hoy); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de éxito y error -->
                <?php if ($mensaje_success): ?>
                <div class="mb-4 lg:mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo htmlspecialchars($mensaje_success); ?></span>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-green-600 hover:text-green-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($mensaje_error): ?>
                <div class="mb-4 lg:mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><?php echo htmlspecialchars($mensaje_error); ?></span>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Filtros y búsqueda -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>"
                                   placeholder="Marca, modelo o placa..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select name="chofer" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos los estados</option>
                                <option value="sin_asignar" <?php echo $chofer_filter === 'sin_asignar' ? 'selected' : ''; ?>>Sin chofer asignado</option>
                                <?php foreach ($choferes as $chofer): ?>
                                    <option value="<?php echo $chofer['id_chofer']; ?>" 
                                            <?php echo $chofer_filter == $chofer['id_chofer'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>Filtrar
                            </button>
                        </div>
                        
                        <div class="flex items-end">
                            <a href="index.php" 
                               class="w-full px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-center">
                                <i class="fas fa-undo mr-2"></i>Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Lista de vehículos -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Vehículos Registrados (<?php echo count($vehiculos); ?>)
                        </h2>
                    </div>

                    <?php if (empty($vehiculos)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-medium text-gray-900 mb-2">No hay vehículos registrados</h3>
                            <p class="text-gray-500 mb-6">Comienza agregando el primer vehículo a tu flota.</p>
                            <a href="crear.php" 
                               class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Agregar Primer Vehículo
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Vista de tabla (desktop) -->
                        <div class="table-responsive overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Vehículo
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Chofer Asignado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Capacidad
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tours
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($vehiculos as $vehiculo): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                                        <i class="fas fa-car text-blue-600"></i>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($vehiculo['placa']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($vehiculo['chofer_nombre'] && trim($vehiculo['chofer_nombre'])): ?>
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo htmlspecialchars(trim($vehiculo['chofer_nombre'])); ?>
                                                    </div>
                                                    <?php if ($vehiculo['chofer_telefono']): ?>
                                                        <div class="text-sm text-gray-500">
                                                            <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($vehiculo['chofer_telefono']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        Sin asignar
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <i class="fas fa-users mr-1"></i><?php echo $vehiculo['capacidad']; ?> personas
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    Total: <?php echo $vehiculo['total_tours']; ?>
                                                </div>
                                                <?php if ($vehiculo['tours_proximos'] > 0): ?>
                                                    <div class="text-sm text-orange-600">
                                                        Próximos: <?php echo $vehiculo['tours_proximos']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($vehiculo['tours_proximos'] > 0): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        En uso
                                                    </span>
                                                <?php elseif ($vehiculo['id_chofer']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Disponible
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Sin chofer
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="ver.php?id=<?php echo $vehiculo['id_vehiculo']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $vehiculo['id_vehiculo']; ?>" 
                                                       class="text-green-600 hover:text-green-900" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="eliminarVehiculo(<?php echo $vehiculo['id_vehiculo']; ?>, '<?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>')" 
                                                            class="text-red-600 hover:text-red-900" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Vista de tarjetas (mobile) -->
                        <div class="cards-responsive p-4 space-y-4">
                            <?php foreach ($vehiculos as $vehiculo): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-car text-blue-600"></i>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>
                                                </h3>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($vehiculo['placa']); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="ver.php?id=<?php echo $vehiculo['id_vehiculo']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="editar.php?id=<?php echo $vehiculo['id_vehiculo']; ?>" 
                                               class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="eliminarVehiculo(<?php echo $vehiculo['id_vehiculo']; ?>, '<?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>')" 
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Chofer:</span>
                                            <?php if ($vehiculo['chofer_nombre'] && trim($vehiculo['chofer_nombre'])): ?>
                                                <span class="font-medium"><?php echo htmlspecialchars(trim($vehiculo['chofer_nombre'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-orange-600">Sin asignar</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Capacidad:</span>
                                            <span class="font-medium"><?php echo $vehiculo['capacidad']; ?> personas</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Tours totales:</span>
                                            <span class="font-medium"><?php echo $vehiculo['total_tours']; ?></span>
                                        </div>
                                        <?php if ($vehiculo['tours_proximos'] > 0): ?>
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600">Tours próximos:</span>
                                                <span class="font-medium text-orange-600"><?php echo $vehiculo['tours_proximos']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Estado:</span>
                                            <?php if ($vehiculo['tours_proximos'] > 0): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    En uso
                                                </span>
                                            <?php elseif ($vehiculo['id_chofer']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Disponible
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Sin chofer
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div id="modalEliminar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Eliminar Vehículo</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        ¿Estás seguro de que deseas eliminar el vehículo <span id="nombreVehiculo" class="font-medium"></span>? 
                        Esta acción no se puede deshacer y se cancelarán todos los tours programados.
                    </p>
                </div>
                <div class="flex gap-3 mt-4">
                    <button onclick="cerrarModal()" 
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button onclick="confirmarEliminacion()" 
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let vehiculoAEliminar = null;

        function eliminarVehiculo(id, nombre) {
            vehiculoAEliminar = id;
            document.getElementById('nombreVehiculo').textContent = nombre;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalEliminar').classList.add('hidden');
            vehiculoAEliminar = null;
        }

        function confirmarEliminacion() {
            if (vehiculoAEliminar) {
                // Mostrar estado de carga
                const botonEliminar = document.querySelector('#modalEliminar button[onclick="confirmarEliminacion()"]');
                const textoOriginal = botonEliminar.innerHTML;
                botonEliminar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Eliminando...';
                botonEliminar.disabled = true;
                
                // Realizar la eliminación
                setTimeout(() => {
                    window.location.href = `eliminar.php?id=${vehiculoAEliminar}`;
                }, 500);
            }
        }

        function exportarVehiculos() {
            window.location.href = 'exportar.php';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalEliminar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });

        // Auto-ocultar mensajes después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const mensajeSuccess = document.querySelector('.bg-green-50');
            const mensajeError = document.querySelector('.bg-red-50');
            
            if (mensajeSuccess) {
                setTimeout(() => {
                    mensajeSuccess.style.opacity = '0';
                    setTimeout(() => mensajeSuccess.remove(), 300);
                }, 5000);
            }
            
            if (mensajeError) {
                setTimeout(() => {
                    mensajeError.style.opacity = '0';
                    setTimeout(() => mensajeError.remove(), 300);
                }, 7000); // Los errores se muestran más tiempo
            }
        });
    </script>
</body>
</html>
