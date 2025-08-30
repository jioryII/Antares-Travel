<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Parámetros de búsqueda y filtrado
$buscar = $_GET['buscar'] ?? '';
$orden = $_GET['orden'] ?? 'nombre';
$direccion = $_GET['direccion'] ?? 'asc';
$pagina = intval($_GET['pagina'] ?? 1);
$por_pagina = 20;

try {
    $connection = getConnection();
    
    // Construir consulta base
    $where_conditions = [];
    $params = [];
    
    if ($buscar) {
        $where_conditions[] = "(c.nombre LIKE ? OR c.apellido LIKE ? OR c.telefono LIKE ? OR c.licencia LIKE ?)";
        $search_term = "%$buscar%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Contar total de registros
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    $total_sql = "SELECT COUNT(*) FROM choferes c $where_clause";
    $total_stmt = $connection->prepare($total_sql);
    $total_stmt->execute($params);
    $total_registros = $total_stmt->fetchColumn();
    
    // Calcular paginación
    $total_paginas = ceil($total_registros / $por_pagina);
    $offset = ($pagina - 1) * $por_pagina;
    
    // Obtener choferes con estadísticas reales
    $order_clause = "ORDER BY c.$orden $direccion";
    $limit_clause = "LIMIT $por_pagina OFFSET $offset";
    
    $choferes_sql = "SELECT c.*,
                            COALESCE(vehiculos_stats.total_vehiculos, 0) as total_vehiculos,
                            COALESCE(vehiculos_stats.vehiculos_activos, 0) as vehiculos_activos,
                            COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados,
                            COALESCE(tours_stats.total_tours, 0) as total_tours,
                            COALESCE(tours_stats.tours_proximos, 0) as tours_proximos
                     FROM choferes c 
                     LEFT JOIN (
                         SELECT id_chofer, 
                                COUNT(*) as total_vehiculos,
                                COUNT(*) as vehiculos_activos
                         FROM vehiculos 
                         WHERE id_chofer IS NOT NULL
                         GROUP BY id_chofer
                     ) vehiculos_stats ON c.id_chofer = vehiculos_stats.id_chofer
                     LEFT JOIN (
                         SELECT v.id_chofer,
                                SUM(CASE WHEN dv.estado = 'Ocupado' AND dv.fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados
                         FROM vehiculos v
                         LEFT JOIN disponibilidad_vehiculos dv ON v.id_vehiculo = dv.id_vehiculo
                         WHERE v.id_chofer IS NOT NULL
                         GROUP BY v.id_chofer
                     ) disponibilidad_stats ON c.id_chofer = disponibilidad_stats.id_chofer
                     LEFT JOIN (
                         SELECT v.id_chofer,
                                COUNT(td.id_tour_diario) as total_tours,
                                SUM(CASE WHEN td.fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos
                         FROM vehiculos v
                         LEFT JOIN tours_diarios td ON v.id_vehiculo = td.id_vehiculo
                         WHERE v.id_chofer IS NOT NULL
                         GROUP BY v.id_chofer
                     ) tours_stats ON c.id_chofer = tours_stats.id_chofer
                     $where_clause 
                     $order_clause 
                     $limit_clause";
    
    $choferes_stmt = $connection->prepare($choferes_sql);
    $choferes_stmt->execute($params);
    $choferes = $choferes_stmt->fetchAll();
    
    // Estadísticas generales mejoradas
    $stats_sql = "SELECT 
                    COUNT(*) as total_choferes,
                    COALESCE(SUM(vehiculos_stats.total_vehiculos), 0) as total_vehiculos_sistema,
                    COALESCE(SUM(disponibilidad_stats.dias_ocupados), 0) as dias_ocupados_sistema,
                    COALESCE(SUM(tours_stats.total_tours), 0) as total_tours_sistema,
                    COALESCE(SUM(tours_stats.tours_proximos), 0) as tours_proximos_sistema,
                    COUNT(CASE WHEN vehiculos_stats.total_vehiculos > 0 THEN 1 END) as choferes_con_vehiculos
                  FROM choferes c
                  LEFT JOIN (
                      SELECT id_chofer, COUNT(*) as total_vehiculos
                      FROM vehiculos 
                      WHERE id_chofer IS NOT NULL
                      GROUP BY id_chofer
                  ) vehiculos_stats ON c.id_chofer = vehiculos_stats.id_chofer
                  LEFT JOIN (
                      SELECT v.id_chofer, SUM(CASE WHEN dv.estado = 'Ocupado' AND dv.fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados
                      FROM vehiculos v
                      LEFT JOIN disponibilidad_vehiculos dv ON v.id_vehiculo = dv.id_vehiculo
                      WHERE v.id_chofer IS NOT NULL
                      GROUP BY v.id_chofer
                  ) disponibilidad_stats ON c.id_chofer = disponibilidad_stats.id_chofer
                  LEFT JOIN (
                      SELECT v.id_chofer, COUNT(td.id_tour_diario) as total_tours, SUM(CASE WHEN td.fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos
                      FROM vehiculos v
                      LEFT JOIN tours_diarios td ON v.id_vehiculo = td.id_vehiculo
                      WHERE v.id_chofer IS NOT NULL
                      GROUP BY v.id_chofer
                  ) tours_stats ON c.id_chofer = tours_stats.id_chofer";
    $stats_stmt = $connection->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
    $page_title = "Gestión de Choferes";
    
} catch (Exception $e) {
    $error = "Error al cargar choferes: " . $e->getMessage();
    $choferes = [];
    $stats = [
        'total_choferes' => 0, 
        'choferes_con_vehiculos' => 0, 
        'total_vehiculos_sistema' => 0,
        'dias_ocupados_sistema' => 0,
        'total_tours_sistema' => 0,
        'tours_proximos_sistema' => 0
    ];
    $total_registros = 0;
    $total_paginas = 0;
    $page_title = "Error - Choferes";
}

function getSortIcon($campo, $orden_actual, $direccion_actual) {
    if ($campo !== $orden_actual) {
        return 'fas fa-sort text-gray-400';
    }
    return $direccion_actual === 'asc' ? 'fas fa-sort-up text-blue-600' : 'fas fa-sort-down text-blue-600';
}

function getSortUrl($campo, $orden_actual, $direccion_actual) {
    global $buscar;
    $nueva_direccion = ($campo === $orden_actual && $direccion_actual === 'asc') ? 'desc' : 'asc';
    $params = [
        'orden' => $campo,
        'direccion' => $nueva_direccion,
        'buscar' => $buscar
    ];
    return 'index.php?' . http_build_query(array_filter($params));
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
                    <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-xl lg:text-3xl font-bold text-gray-900">
                                <i class="fas fa-id-card text-blue-600 mr-2 lg:mr-3"></i>Gestión de Choferes
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600 mt-1">Administra los choferes y conductores de la plataforma</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="crear.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-plus mr-2"></i>Nuevo Chofer
                            </a>
                            <button onclick="exportarChoferes()" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-download mr-2"></i>Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mostrar errores -->
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

                <!-- Estadísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-blue-100">
                                <i class="fas fa-id-card text-blue-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 min-w-0 flex-1">
                                <p class="text-xs lg:text-sm font-medium text-gray-600 truncate">Total Choferes</p>
                                <p class="text-xl lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_choferes']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-green-100">
                                <i class="fas fa-car text-green-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 min-w-0 flex-1">
                                <p class="text-xs lg:text-sm font-medium text-gray-600 truncate">Con Vehículos</p>
                                <p class="text-xl lg:text-2xl font-bold text-green-600"><?php echo number_format($stats['choferes_con_vehiculos']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-purple-100">
                                <i class="fas fa-truck text-purple-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 min-w-0 flex-1">
                                <p class="text-xs lg:text-sm font-medium text-gray-600 truncate">Vehículos</p>
                                <p class="text-xl lg:text-2xl font-bold text-purple-600"><?php echo number_format($stats['total_vehiculos_sistema']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-orange-100">
                                <i class="fas fa-calendar-day text-orange-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 min-w-0 flex-1">
                                <p class="text-xs lg:text-sm font-medium text-gray-600 truncate">Tours Próximos</p>
                                <p class="text-xl lg:text-2xl font-bold text-orange-600"><?php echo number_format($stats['tours_proximos_sistema']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segunda fila de estadísticas (oculta en móvil) -->
                <div class="hidden md:grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-indigo-100">
                                <i class="fas fa-map-marked-alt text-indigo-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Tours Realizados</p>
                                <p class="text-2xl font-bold text-indigo-600"><?php echo number_format($stats['total_tours_sistema']); ?></p>
                                <p class="text-xs text-gray-500">Por todos los choferes</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Días Ocupados</p>
                                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['dias_ocupados_sistema']); ?></p>
                                <p class="text-xs text-gray-500">En el sistema</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros y búsqueda -->
                <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 mb-6 lg:mb-8">
                    <form method="GET" class="space-y-4 lg:space-y-0 lg:grid lg:grid-cols-4 lg:gap-4">
                        <!-- Búsqueda -->
                        <div class="lg:col-span-2">
                            <label for="buscar" class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <div class="relative">
                                <input type="text" name="buscar" id="buscar" value="<?php echo htmlspecialchars($buscar); ?>" 
                                       placeholder="Nombre, apellido, teléfono o licencia..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Espacio adicional para futuros filtros -->
                        <div></div>
                        
                        <!-- Botones -->
                        <div class="flex lg:items-end gap-2">
                            <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-search mr-2"></i>Buscar
                            </button>
                            <a href="index.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                        
                        <!-- Campos ocultos para mantener ordenación -->
                        <input type="hidden" name="orden" value="<?php echo htmlspecialchars($orden); ?>">
                        <input type="hidden" name="direccion" value="<?php echo htmlspecialchars($direccion); ?>">
                    </form>
                </div>

                <!-- Tabla de choferes -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Choferes Registrados (<?php echo number_format($total_registros); ?>)
                        </h3>
                    </div>
                    
                    <?php if (empty($choferes)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-id-card text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay choferes registrados</h3>
                            <p class="text-gray-500 mb-4">Comienza agregando el primer chofer a la plataforma.</p>
                            <a href="crear.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Agregar Primer Chofer
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Vista de tarjetas para móvil -->
                        <div class="md:hidden space-y-4 p-4">
                            <?php foreach ($choferes as $chofer): ?>
                                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                    <!-- Header de la tarjeta -->
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center">
                                                <span class="text-white font-medium">
                                                    <?php echo strtoupper(substr($chofer['nombre'], 0, 1) . substr($chofer['apellido'] ?? '', 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                                </h3>
                                                <p class="text-xs text-gray-500">ID: #<?php echo $chofer['id_chofer']; ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Información en grid -->
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <!-- Vehículos -->
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Vehículos</p>
                                            <?php if ($chofer['total_vehiculos'] > 0): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-car mr-1"></i><?php echo $chofer['total_vehiculos']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">Sin vehículos</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Tours -->
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Tours</p>
                                            <?php if ($chofer['total_tours'] > 0): ?>
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fas fa-map-marked-alt mr-1"></i><?php echo $chofer['total_tours']; ?>
                                                    </span>
                                                    <?php if ($chofer['tours_proximos'] > 0): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                                            <i class="fas fa-calendar-day mr-1"></i><?php echo $chofer['tours_proximos']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">Sin tours</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Licencia y contacto -->
                                    <div class="border-t border-gray-100 pt-3 mb-3">
                                        <div class="text-xs text-gray-600 space-y-1">
                                            <?php if ($chofer['licencia']): ?>
                                                <div class="flex items-center">
                                                    <i class="fas fa-id-badge text-gray-400 mr-2 flex-shrink-0"></i>
                                                    <span>Lic: <?php echo htmlspecialchars($chofer['licencia']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($chofer['telefono']): ?>
                                                <div class="flex items-center truncate">
                                                    <i class="fas fa-phone text-gray-400 mr-2 flex-shrink-0"></i>
                                                    <span class="truncate"><?php echo htmlspecialchars($chofer['telefono']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="flex justify-center space-x-3 pt-2 border-t border-gray-100">
                                        <a href="ver.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                           class="flex items-center px-3 py-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                            <i class="fas fa-eye mr-2"></i>Ver
                                        </a>
                                        <a href="editar.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                           class="flex items-center px-3 py-2 text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                            <i class="fas fa-edit mr-2"></i>Editar
                                        </a>
                                        <button onclick="eliminarChofer(<?php echo $chofer['id_chofer']; ?>, '<?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>')" 
                                                class="flex items-center px-3 py-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                            <i class="fas fa-trash mr-2"></i>Eliminar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Tabla para pantallas medianas y grandes -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <a href="<?php echo getSortUrl('nombre', $orden, $direccion); ?>" class="group inline-flex items-center hover:text-gray-900">
                                                Chofer
                                                <i class="<?php echo getSortIcon('nombre', $orden, $direccion); ?> ml-2"></i>
                                            </a>
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                            Contacto
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <a href="<?php echo getSortUrl('licencia', $orden, $direccion); ?>" class="group inline-flex items-center hover:text-gray-900">
                                                Licencia
                                                <i class="<?php echo getSortIcon('licencia', $orden, $direccion); ?> ml-2"></i>
                                            </a>
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Vehículos
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">
                                            Tours
                                        </th>
                                        <th scope="col" class="relative px-4 py-3">
                                            <span class="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($choferes as $chofer): ?>
                                        <tr class="hover:bg-gray-50">
                                            <!-- Columna Principal: Chofer -->
                                            <td class="px-4 py-4">
                                                <div class="flex items-center">
                                                    <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0">
                                                        <span class="text-white font-medium text-sm">
                                                            <?php echo strtoupper(substr($chofer['nombre'], 0, 1) . substr($chofer['apellido'] ?? '', 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                    <div class="ml-3 min-w-0 flex-1">
                                                        <div class="text-sm font-medium text-gray-900 truncate">
                                                            <?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 truncate">
                                                            ID: #<?php echo $chofer['id_chofer']; ?>
                                                        </div>
                                                        <!-- Info móvil: contacto visible solo en pantallas pequeñas -->
                                                        <div class="lg:hidden mt-1">
                                                            <?php if ($chofer['telefono']): ?>
                                                                <div class="text-xs text-gray-600 truncate">
                                                                    <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($chofer['telefono']); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Contacto (oculto en tablets) -->
                                            <td class="px-4 py-4 hidden lg:table-cell">
                                                <div class="text-sm text-gray-900">
                                                    <?php if ($chofer['telefono']): ?>
                                                        <div class="flex items-center truncate">
                                                            <i class="fas fa-phone text-gray-400 mr-2 flex-shrink-0"></i>
                                                            <span class="truncate"><?php echo htmlspecialchars($chofer['telefono']); ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-sm">Sin teléfono</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            
                                            <!-- Licencia -->
                                            <td class="px-4 py-4">
                                                <?php if ($chofer['licencia']): ?>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-id-badge text-gray-400 mr-2"></i>
                                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($chofer['licencia']); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-sm">Sin licencia</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Vehículos -->
                                            <td class="px-4 py-4">
                                                <?php if ($chofer['total_vehiculos'] > 0): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-car mr-1"></i>
                                                        <?php echo $chofer['total_vehiculos']; ?> vehículo<?php echo $chofer['total_vehiculos'] > 1 ? 's' : ''; ?>
                                                    </span>
                                                    <?php if ($chofer['dias_ocupados'] > 0): ?>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <?php echo $chofer['dias_ocupados']; ?> días ocupados
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-sm">Sin vehículos</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Tours (oculto en pantallas grandes) -->
                                            <td class="px-4 py-4 hidden xl:table-cell">
                                                <?php if ($chofer['total_tours'] > 0): ?>
                                                    <div class="space-y-1">
                                                        <div class="flex flex-wrap gap-1">
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                <i class="fas fa-map-marked-alt mr-1"></i>
                                                                <?php echo $chofer['total_tours']; ?>
                                                            </span>
                                                            <?php if ($chofer['tours_proximos'] > 0): ?>
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                                    <i class="fas fa-calendar-day mr-1"></i>
                                                                    <?php echo $chofer['tours_proximos']; ?> próximos
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-xs">Sin tours</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Acciones -->
                                            <td class="px-4 py-4 text-right">
                                                <div class="flex items-center justify-end space-x-1">
                                                    <a href="ver.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                                       title="Ver detalles">
                                                        <i class="fas fa-eye text-sm"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                                       class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors"
                                                       title="Editar">
                                                        <i class="fas fa-edit text-sm"></i>
                                                    </a>
                                                    <button onclick="eliminarChofer(<?php echo $chofer['id_chofer']; ?>, '<?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>')" 
                                                            class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash text-sm"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 flex justify-between sm:hidden">
                                        <?php if ($pagina > 1): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                Anterior
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($pagina < $total_paginas): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" 
                                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                Siguiente
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm text-gray-700">
                                                Mostrando
                                                <span class="font-medium"><?php echo (($pagina - 1) * $por_pagina) + 1; ?></span>
                                                a
                                                <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_registros); ?></span>
                                                de
                                                <span class="font-medium"><?php echo number_format($total_registros); ?></span>
                                                resultados
                                            </p>
                                        </div>
                                        <div>
                                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                <?php if ($pagina > 1): ?>
                                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" 
                                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $inicio = max(1, $pagina - 2);
                                                $fin = min($total_paginas, $pagina + 2);
                                                
                                                for ($i = $inicio; $i <= $fin; $i++):
                                                ?>
                                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" 
                                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i === $pagina ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                <?php endfor; ?>
                                                
                                                <?php if ($pagina < $total_paginas): ?>
                                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" 
                                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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
                <h3 class="text-lg font-medium text-gray-900 mt-4">Eliminar Chofer</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        ¿Estás seguro de que deseas eliminar al chofer <span id="nombreChofer" class="font-medium"></span>? 
                        Esta acción no se puede deshacer.
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
        let choferAEliminar = null;

        function eliminarChofer(id, nombre) {
            choferAEliminar = id;
            document.getElementById('nombreChofer').textContent = nombre;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalEliminar').classList.add('hidden');
            choferAEliminar = null;
        }

        function confirmarEliminacion() {
            if (choferAEliminar) {
                window.location.href = `eliminar.php?id=${choferAEliminar}`;
            }
        }

        function exportarChoferes() {
            const params = new URLSearchParams(window.location.search);
            params.set('exportar', '1');
            window.location.href = 'exportar.php?' + params.toString();
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
    </script>
</body>
</html>
