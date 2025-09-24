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
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../../../../imagenes/antares_logozz2.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <br>
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
                <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 mb-6 lg:mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Búsqueda en tiempo real -->
                        <div class="flex-1 lg:max-w-md">
                            <div class="relative">
                                <input type="text" id="buscar-tiempo-real" 
                                       placeholder="Buscar por nombre, apellido, teléfono o licencia..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="flex gap-2">
                            <button onclick="limpiarFiltros()" 
                                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                                <i class="fas fa-times mr-2"></i>Limpiar
                            </button>
                            <button onclick="exportarChoferes()" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-download mr-2"></i>Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de choferes -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Choferes Registrados 
                            <span id="total-registros">(<?php echo number_format($total_registros); ?>)</span>
                            <span id="registros-filtrados" class="text-blue-600 hidden"></span>
                        </h3>
                    </div>
                    
                    <!-- Vista desktop con scroll -->
                    <div class="hidden md:block">
                        <div class="overflow-auto max-h-[600px]">
                            <table class="min-w-full">
                                <!-- Header sticky -->
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                            #
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors">
                                            <div class="flex items-center">
                                                Chofer
                                                <i class="fas fa-sort ml-2 text-gray-400"></i>
                                            </div>
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contacto
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Licencia
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Vehículos
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tours
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-choferes" class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($choferes as $index => $chofer): ?>
                                        <tr class="hover:bg-gray-50 chofer-row" data-nombre="<?php echo strtolower($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>" 
                                            data-telefono="<?php echo strtolower($chofer['telefono'] ?? ''); ?>" 
                                            data-licencia="<?php echo strtolower($chofer['licencia'] ?? ''); ?>">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $chofer['id_chofer']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <!-- Foto de perfil -->
                                                    <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center overflow-hidden border-2 border-white shadow-lg flex-shrink-0">
                                                        <?php 
                                                        $foto_url = $chofer['foto_url'] ?? '';
                                                        $mostrar_foto = false;
                                                        $foto_src = '';
                                                        
                                                        if (!empty($foto_url)) {
                                                            // Manejar rutas tanto nuevas (completas) como legacy (solo nombre)
                                                            $foto_path = strpos($foto_url, 'storage/uploads/choferes/') === 0 
                                                                ? "../../../../" . $foto_url 
                                                                : "../../../../storage/uploads/choferes/" . $foto_url;
                                                            $foto_src = strpos($foto_url, 'storage/uploads/choferes/') === 0 
                                                                ? "../../../../" . $foto_url 
                                                                : "../../../../storage/uploads/choferes/" . $foto_url;
                                                            
                                                            $mostrar_foto = file_exists($foto_path);
                                                        }
                                                        
                                                        if ($mostrar_foto): ?>
                                                            <img src="<?php echo htmlspecialchars($foto_src); ?>" 
                                                                 alt="<?php echo htmlspecialchars($chofer['nombre']); ?>" 
                                                                 class="h-full w-full object-cover rounded-full">
                                                        <?php else: ?>
                                                            <span class="text-white font-medium text-sm">
                                                                <?php echo strtoupper(substr($chofer['nombre'], 0, 1) . substr($chofer['apellido'] ?? '', 0, 1)); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ml-4 min-w-0 flex-1">
                                                        <div class="text-sm font-medium text-gray-900 truncate">
                                                            <?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500 truncate">ID: #<?php echo $chofer['id_chofer']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($chofer['telefono']): ?>
                                                    <div class="flex items-center text-sm text-gray-900">
                                                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                                                        <?php echo htmlspecialchars($chofer['telefono']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-sm">No registrado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($chofer['licencia']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <i class="fas fa-id-card mr-1"></i>
                                                        <?php echo htmlspecialchars($chofer['licencia']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-sm">Sin licencia</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($chofer['total_vehiculos'] > 0): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-car mr-1"></i>
                                                        <?php echo $chofer['total_vehiculos']; ?> vehículo<?php echo $chofer['total_vehiculos'] > 1 ? 's' : ''; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Sin vehículos
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php if ($chofer['total_tours'] > 0): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            <i class="fas fa-map-marked-alt mr-1"></i><?php echo $chofer['total_tours']; ?> total
                                                        </span>
                                                        <?php if ($chofer['tours_proximos'] > 0): ?>
                                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                                                <i class="fas fa-calendar-day mr-1"></i><?php echo $chofer['tours_proximos']; ?> próximos
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-xs">Sin tours</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="ver.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900 transition-colors" 
                                                       title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                                       class="text-yellow-600 hover:text-yellow-900 transition-colors" 
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="confirmarEliminar(<?php echo $chofer['id_chofer']; ?>, '<?php echo htmlspecialchars($chofer['nombre']); ?>')" 
                                                            class="text-red-600 hover:text-red-900 transition-colors" 
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Vista móvil con tarjetas -->
                    <div class="md:hidden" id="vista-movil">
                        <div id="tarjetas-choferes" class="space-y-4 p-4">
                            <?php foreach ($choferes as $chofer): ?>
                                <div class="chofer-card bg-white border border-gray-200 rounded-lg p-4 shadow-sm" 
                                     data-nombre="<?php echo strtolower($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>" 
                                     data-telefono="<?php echo strtolower($chofer['telefono'] ?? ''); ?>" 
                                     data-licencia="<?php echo strtolower($chofer['licencia'] ?? ''); ?>">
                                    <!-- Header de la tarjeta -->
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <!-- Foto de perfil -->
                                            <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center overflow-hidden border-2 border-white shadow-lg flex-shrink-0">
                                                <?php 
                                                $foto_url = $chofer['foto_url'] ?? '';
                                                $mostrar_foto = false;
                                                $foto_src = '';
                                                
                                                if (!empty($foto_url)) {
                                                    // Manejar rutas tanto nuevas (completas) como legacy (solo nombre)
                                                    $foto_path = strpos($foto_url, 'storage/uploads/choferes/') === 0 
                                                        ? "../../../../" . $foto_url 
                                                        : "../../../../storage/uploads/choferes/" . $foto_url;
                                                    $foto_src = strpos($foto_url, 'storage/uploads/choferes/') === 0 
                                                        ? "../../../../" . $foto_url 
                                                        : "../../../../storage/uploads/choferes/" . $foto_url;
                                                    
                                                    $mostrar_foto = file_exists($foto_path);
                                                }
                                                
                                                if ($mostrar_foto): ?>
                                                    <img src="<?php echo htmlspecialchars($foto_src); ?>" 
                                                         alt="<?php echo htmlspecialchars($chofer['nombre']); ?>" 
                                                         class="h-full w-full object-cover rounded-full">
                                                <?php else: ?>
                                                    <span class="text-white font-medium">
                                                        <?php echo strtoupper(substr($chofer['nombre'], 0, 1) . substr($chofer['apellido'] ?? '', 0, 1)); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-3 min-w-0 flex-1">
                                                <h3 class="text-sm font-semibold text-gray-900 truncate">
                                                    <?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                                </h3>
                                                <p class="text-xs text-gray-500">ID: #<?php echo $chofer['id_chofer']; ?></p>
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="ver.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                               class="text-blue-600 hover:text-blue-900 p-1">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="editar.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                               class="text-yellow-600 hover:text-yellow-900 p-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Información del chofer -->
                                    <div class="space-y-2 mb-3">
                                        <?php if ($chofer['telefono']): ?>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-phone text-gray-400 mr-2"></i>
                                                <?php echo htmlspecialchars($chofer['telefono']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($chofer['licencia']): ?>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-id-card text-gray-400 mr-2"></i>
                                                Licencia: <?php echo htmlspecialchars($chofer['licencia']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Estadísticas -->
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <?php if ($chofer['total_vehiculos'] > 0): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-car mr-1"></i><?php echo $chofer['total_vehiculos']; ?> vehículo<?php echo $chofer['total_vehiculos'] > 1 ? 's' : ''; ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($chofer['total_tours'] > 0): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-map-marked-alt mr-1"></i><?php echo $chofer['total_tours']; ?> tours
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($chofer['tours_proximos'] > 0): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                                <i class="fas fa-calendar-day mr-1"></i><?php echo $chofer['tours_proximos']; ?> próximos
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Botones de acción -->
                                    <div class="flex gap-2 pt-3 border-t border-gray-100">
                                        <a href="ver.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                           class="flex-1 px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-center text-sm">
                                            <i class="fas fa-eye mr-1"></i>Ver
                                        </a>
                                        <a href="editar.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                           class="flex-1 px-3 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition-colors text-center text-sm">
                                            <i class="fas fa-edit mr-1"></i>Editar
                                        </a>
                                        <button onclick="confirmarEliminar(<?php echo $chofer['id_chofer']; ?>, '<?php echo htmlspecialchars($chofer['nombre']); ?>')" 
                                                class="px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition-colors text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Mensaje cuando no hay resultados -->
                    <div id="no-results" class="text-center py-12 hidden">
                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron choferes</h3>
                        <p class="text-gray-500">Intenta con otros términos de búsqueda.</p>
                    </div>
                    
                    <!-- Mensaje cuando no hay choferes -->
                    <?php if (empty($choferes)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-id-card text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay choferes registrados</h3>
                            <p class="text-gray-500 mb-4">Comienza agregando el primer chofer a la plataforma.</p>
                            <a href="crear.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Agregar Primer Chofer
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div id="modalEliminar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[450px] shadow-xl rounded-lg bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Eliminar Chofer</h3>
                <div class="mt-2 px-4 py-3">
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        ¿Estás seguro de que deseas eliminar al chofer <span id="nombreChofer" class="font-medium"></span>? 
                        Esta acción no se puede deshacer.
                    </p>
                    
                    <!-- Advertencia de datos relacionados -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-0.5"></i>
                            <div class="text-xs text-yellow-700">
                                <strong>Advertencia:</strong> Se eliminarán todos los datos del chofer, pero los vehículos asignados quedarán sin chofer para poder reasignarlos.
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-left">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-clipboard-list mr-1"></i>Motivo de eliminación (opcional):
                        </label>
                        <textarea id="motivoEliminacion" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm resize-none"
                                  placeholder="Ej: Finalización de contrato, cambio de empleo, violación de normas..."></textarea>
                        <div class="text-xs text-gray-400 mt-1">
                            Este motivo quedará registrado en el historial administrativo
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-3 px-4 py-4">
                    <button id="btnConfirmarEliminar" 
                            class="px-6 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Eliminar Chofer
                    </button>
                    <button onclick="cerrarModal()" 
                            class="px-6 py-2 bg-gray-200 text-gray-800 text-sm font-semibold rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables para el filtrado en tiempo real
        let timeoutId;
        const debounceDelay = 300;
        
        // Elementos del DOM
        const searchInput = document.getElementById('buscar-tiempo-real');
        const tablaChoferes = document.getElementById('tabla-choferes');
        const tarjetasChoferes = document.getElementById('tarjetas-choferes');
        const noResults = document.getElementById('no-results');
        const totalRegistros = document.getElementById('total-registros');
        const registrosFiltrados = document.getElementById('registros-filtrados');
        
        // Función de filtrado en tiempo real
        function filtrarChoferes() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            
            // Obtener todas las filas y tarjetas
            const filasDesktop = document.querySelectorAll('.chofer-row');
            const tarjetasMovil = document.querySelectorAll('.chofer-card');
            
            let visibleCount = 0;
            
            // Filtrar filas de desktop
            filasDesktop.forEach(fila => {
                const nombre = fila.dataset.nombre || '';
                const telefono = fila.dataset.telefono || '';
                const licencia = fila.dataset.licencia || '';
                
                const coincide = nombre.includes(searchTerm) || 
                                telefono.includes(searchTerm) || 
                                licencia.includes(searchTerm);
                
                if (coincide) {
                    fila.style.display = '';
                    visibleCount++;
                } else {
                    fila.style.display = 'none';
                }
            });
            
            // Filtrar tarjetas de móvil
            tarjetasMovil.forEach(tarjeta => {
                const nombre = tarjeta.dataset.nombre || '';
                const telefono = tarjeta.dataset.telefono || '';
                const licencia = tarjeta.dataset.licencia || '';
                
                const coincide = nombre.includes(searchTerm) || 
                                telefono.includes(searchTerm) || 
                                licencia.includes(searchTerm);
                
                if (coincide) {
                    tarjeta.style.display = '';
                } else {
                    tarjeta.style.display = 'none';
                }
            });
            
            // Actualizar contadores y mostrar/ocultar mensaje de no resultados
            if (searchTerm && visibleCount === 0) {
                noResults.classList.remove('hidden');
                if (tablaChoferes) tablaChoferes.parentElement.style.display = 'none';
                if (tarjetasChoferes) tarjetasChoferes.style.display = 'none';
            } else {
                noResults.classList.add('hidden');
                if (tablaChoferes) tablaChoferes.parentElement.style.display = '';
                if (tarjetasChoferes) tarjetasChoferes.style.display = '';
            }
            
            // Actualizar contador de resultados
            if (searchTerm) {
                totalRegistros.classList.add('hidden');
                registrosFiltrados.classList.remove('hidden');
                registrosFiltrados.textContent = `(${visibleCount} resultado${visibleCount !== 1 ? 's' : ''} encontrado${visibleCount !== 1 ? 's' : ''})`;
            } else {
                totalRegistros.classList.remove('hidden');
                registrosFiltrados.classList.add('hidden');
            }
        }
        
        // Función de búsqueda con debounce
        function debouncedSearch() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(filtrarChoferes, debounceDelay);
        }
        
        // Event listeners
        if (searchInput) {
            searchInput.addEventListener('input', debouncedSearch);
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(timeoutId);
                    filtrarChoferes();
                }
            });
        }
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            if (searchInput) {
                searchInput.value = '';
                filtrarChoferes();
                searchInput.focus();
            }
        }
        
        // Función para confirmar eliminación con modal
        let choferAEliminar = null;

        function confirmarEliminar(id, nombre) {
            choferAEliminar = id;
            document.getElementById('nombreChofer').textContent = nombre;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalEliminar').classList.add('hidden');
            document.getElementById('motivoEliminacion').value = '';
            choferAEliminar = null;
        }

        // Confirmar eliminación
        document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
            if (choferAEliminar) {
                const motivo = document.getElementById('motivoEliminacion').value;
                const btnEliminar = this;
                const btnCancelar = document.querySelector('button[onclick="cerrarModal()"]');
                
                // Deshabilitar botones durante la eliminación
                btnEliminar.disabled = true;
                btnEliminar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Eliminando...';
                btnEliminar.className = 'px-6 py-2 bg-gray-400 text-white text-sm font-semibold rounded-lg cursor-not-allowed';
                btnCancelar.disabled = true;
                
                // Crear formulario y enviar
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar.php';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_chofer';
                inputId.value = choferAEliminar;
                form.appendChild(inputId);
                
                const inputMotivo = document.createElement('input');
                inputMotivo.type = 'hidden';
                inputMotivo.name = 'motivo';
                inputMotivo.value = motivo;
                form.appendChild(inputMotivo);
                
                document.body.appendChild(form);
                form.submit();
            }
        });

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
        
        // Función para exportar
        function exportarChoferes() {
            window.location.href = 'exportar.php';
        }
        
        // Inicializar tooltips o funcionalidades adicionales si es necesario
        document.addEventListener('DOMContentLoaded', function() {
            // Enfocar el campo de búsqueda si existe
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>
