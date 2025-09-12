<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Parámetros de búsqueda y filtrado
$buscar = $_GET['buscar'] ?? '';
$estado = $_GET['estado'] ?? '';
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
        $where_conditions[] = "(g.nombre LIKE ? OR g.apellido LIKE ? OR g.email LIKE ? OR g.telefono LIKE ?)";
        $search_term = "%$buscar%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($estado) {
        $where_conditions[] = "g.estado = ?";
        $params[] = $estado;
    }
    
    // Contar total de registros
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    $total_sql = "SELECT COUNT(*) FROM guias g $where_clause";
    $total_stmt = $connection->prepare($total_sql);
    $total_stmt->execute($params);
    $total_registros = $total_stmt->fetchColumn();
    
    // Calcular paginación
    $total_paginas = ceil($total_registros / $por_pagina);
    $offset = ($pagina - 1) * $por_pagina;
    
    // Obtener guías con estadísticas reales
    $order_clause = "ORDER BY g.$orden $direccion";
    $limit_clause = "LIMIT $por_pagina OFFSET $offset";
    
    $guias_sql = "SELECT g.*,
                         COALESCE(tours_stats.total_tours, 0) as total_tours,
                         COALESCE(tours_stats.tours_activos, 0) as tours_activos,
                         COALESCE(diarios_stats.tours_diarios_total, 0) as tours_diarios_total,
                         COALESCE(diarios_stats.tours_proximos, 0) as tours_proximos,
                         COALESCE(cal_stats.calificacion_promedio, 0) as calificacion_promedio,
                         COALESCE(cal_stats.total_calificaciones, 0) as total_calificaciones,
                         COALESCE(idiomas_stats.idiomas_count, 0) as idiomas_count,
                         COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados
                  FROM guias g 
                  LEFT JOIN (
                      SELECT id_guia, 
                             COUNT(*) as total_tours,
                             COUNT(*) as tours_activos
                      FROM tours 
                      WHERE id_guia IS NOT NULL
                      GROUP BY id_guia
                  ) tours_stats ON g.id_guia = tours_stats.id_guia
                  LEFT JOIN (
                      SELECT id_guia,
                             COUNT(*) as tours_diarios_total,
                             SUM(CASE WHEN fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos
                      FROM tours_diarios
                      GROUP BY id_guia
                  ) diarios_stats ON g.id_guia = diarios_stats.id_guia
                  LEFT JOIN (
                      SELECT id_guia,
                             ROUND(AVG(calificacion), 1) as calificacion_promedio,
                             COUNT(*) as total_calificaciones
                      FROM calificaciones_guias
                      WHERE calificacion IS NOT NULL
                      GROUP BY id_guia
                  ) cal_stats ON g.id_guia = cal_stats.id_guia
                  LEFT JOIN (
                      SELECT id_guia,
                             COUNT(*) as idiomas_count
                      FROM guia_idiomas
                      GROUP BY id_guia
                  ) idiomas_stats ON g.id_guia = idiomas_stats.id_guia
                  LEFT JOIN (
                      SELECT id_guia,
                             SUM(CASE WHEN estado = 'Ocupado' AND fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados
                      FROM disponibilidad_guias
                      GROUP BY id_guia
                  ) disponibilidad_stats ON g.id_guia = disponibilidad_stats.id_guia
                  $where_clause 
                  $order_clause 
                  $limit_clause";
    
    $guias_stmt = $connection->prepare($guias_sql);
    $guias_stmt->execute($params);
    $guias = $guias_stmt->fetchAll();
    
    // Estadísticas generales mejoradas
    $stats_sql = "SELECT 
                    COUNT(*) as total_guias,
                    SUM(CASE WHEN estado = 'Libre' THEN 1 ELSE 0 END) as guias_libres,
                    SUM(CASE WHEN estado = 'Ocupado' THEN 1 ELSE 0 END) as guias_ocupados,
                    COALESCE(AVG(cal_stats.calificacion_promedio), 0) as calificacion_general,
                    COALESCE(SUM(tours_stats.total_tours), 0) as total_tours_sistema,
                    COALESCE(SUM(diarios_stats.tours_proximos), 0) as tours_proximos_sistema
                  FROM guias g
                  LEFT JOIN (
                      SELECT id_guia, COUNT(*) as total_tours
                      FROM tours 
                      WHERE id_guia IS NOT NULL
                      GROUP BY id_guia
                  ) tours_stats ON g.id_guia = tours_stats.id_guia
                  LEFT JOIN (
                      SELECT id_guia, AVG(calificacion) as calificacion_promedio
                      FROM calificaciones_guias
                      WHERE calificacion IS NOT NULL
                      GROUP BY id_guia
                  ) cal_stats ON g.id_guia = cal_stats.id_guia
                  LEFT JOIN (
                      SELECT id_guia, SUM(CASE WHEN fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos
                      FROM tours_diarios
                      GROUP BY id_guia
                  ) diarios_stats ON g.id_guia = diarios_stats.id_guia";
    $stats_stmt = $connection->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
    $page_title = "Gestión de Guías";
    
} catch (Exception $e) {
    $error = "Error al cargar guías: " . $e->getMessage();
    $guias = [];
    $stats = [
        'total_guias' => 0, 
        'guias_libres' => 0, 
        'guias_ocupados' => 0,
        'calificacion_general' => 0,
        'total_tours_sistema' => 0,
        'tours_proximos_sistema' => 0
    ];
    $total_registros = 0;
    $total_paginas = 0;
    $page_title = "Error - Guías";
}

function getEstadoClass($estado) {
    return $estado === 'Libre' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
}

function getEstadoIcon($estado) {
    return $estado === 'Libre' ? 'fas fa-check-circle' : 'fas fa-clock';
}

function getSortIcon($campo, $orden_actual, $direccion_actual) {
    if ($campo !== $orden_actual) {
        return 'fas fa-sort text-gray-400';
    }
    return $direccion_actual === 'asc' ? 'fas fa-sort-up text-blue-600' : 'fas fa-sort-down text-blue-600';
}

function getSortUrl($campo, $orden_actual, $direccion_actual) {
    global $buscar, $estado;
    $nueva_direccion = ($campo === $orden_actual && $direccion_actual === 'asc') ? 'desc' : 'asc';
    $params = [
        'orden' => $campo,
        'direccion' => $nueva_direccion,
        'buscar' => $buscar,
        'estado' => $estado
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
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-6 lg:mb-8">
                    <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-xl lg:text-3xl font-bold text-gray-900">
                                <i class="fas fa-user-tie text-blue-600 mr-2 lg:mr-3"></i>Gestión de Guías
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600 mt-1">Administra los guías turísticos de la plataforma</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="crear.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-plus mr-2"></i>Nuevo Guía
                            </a>
                            <button onclick="exportarGuias()" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
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
                                <i class="fas fa-user-tie text-blue-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 min-w-0 flex-1">
                                <p class="text-xs lg:text-sm font-medium text-gray-600 truncate">Total Guías</p>
                                <p class="text-xl lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_guias']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-green-100">
                                <i class="fas fa-check-circle text-green-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 min-w-0 flex-1">
                                <p class="text-xs lg:text-sm font-medium text-gray-600 truncate">Libres</p>
                                <p class="text-xl lg:text-2xl font-bold text-green-600"><?php echo number_format($stats['guias_libres']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-yellow-100">
                                <i class="fas fa-clock text-yellow-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 min-w-0 flex-1">
                                <p class="text-xs lg:text-sm font-medium text-gray-600 truncate">Ocupados</p>
                                <p class="text-xl lg:text-2xl font-bold text-yellow-600"><?php echo number_format($stats['guias_ocupados']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-purple-100">
                                <i class="fas fa-star text-purple-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 min-w-0 flex-1">
                                <p class="text-xs lg:text-sm font-medium text-gray-600 truncate">Rating</p>
                                <p class="text-xl lg:text-2xl font-bold text-purple-600">
                                    <?php echo $stats['calificacion_general'] > 0 ? number_format($stats['calificacion_general'], 1) : 'N/A'; ?>
                                    <?php if ($stats['calificacion_general'] > 0): ?>
                                        <span class="text-sm text-gray-500">/5</span>
                                    <?php endif; ?>
                                </p>
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
                                <p class="text-sm font-medium text-gray-600">Total Tours en Sistema</p>
                                <p class="text-2xl font-bold text-indigo-600"><?php echo number_format($stats['total_tours_sistema']); ?></p>
                                <p class="text-xs text-gray-500">Tours asignados a guías</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-orange-100">
                                <i class="fas fa-calendar-day text-orange-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Tours Próximos</p>
                                <p class="text-2xl font-bold text-orange-600"><?php echo number_format($stats['tours_proximos_sistema']); ?></p>
                                <p class="text-xs text-gray-500">Programados desde hoy</p>
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
                                       placeholder="Nombre, apellido, email o teléfono..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Estado -->
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select name="estado" id="estado" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Todos los estados</option>
                                <option value="Libre" <?php echo $estado === 'Libre' ? 'selected' : ''; ?>>Libre</option>
                                <option value="Ocupado" <?php echo $estado === 'Ocupado' ? 'selected' : ''; ?>>Ocupado</option>
                            </select>
                        </div>
                        
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

                <!-- Tabla de guías -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Guías Registrados (<?php echo number_format($total_registros); ?>)
                        </h3>
                    </div>
                    
                    <?php if (empty($guias)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-user-tie text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay guías registrados</h3>
                            <p class="text-gray-500 mb-4">Comienza agregando el primer guía a la plataforma.</p>
                            <a href="crear.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Agregar Primer Guía
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Vista de tarjetas para móvil -->
                        <div class="md:hidden space-y-4 p-4">
                            <?php foreach ($guias as $guia): ?>
                                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                    <!-- Header de la tarjeta -->
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <?php if ($guia['foto_url']): ?>
                                                <img class="h-12 w-12 rounded-full" src="<?php echo htmlspecialchars($guia['foto_url']); ?>" alt="">
                                            <?php else: ?>
                                                <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center">
                                                    <span class="text-white font-medium">
                                                        <?php echo strtoupper(substr($guia['nombre'], 0, 1) . substr($guia['apellido'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>
                                                </h3>
                                                <p class="text-xs text-gray-500">ID: #<?php echo $guia['id_guia']; ?></p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getEstadoClass($guia['estado']); ?>">
                                            <i class="<?php echo getEstadoIcon($guia['estado']); ?> mr-1"></i>
                                            <?php echo $guia['estado']; ?>
                                        </span>
                                    </div>

                                    <!-- Información en grid -->
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <!-- Tours -->
                                        <div class="space-y-1">
                                            <p class="text-xs font-medium text-gray-500">Tours</p>
                                            <div class="flex flex-wrap gap-1">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    <i class="fas fa-map-marked-alt mr-1"></i><?php echo $guia['total_tours']; ?>
                                                </span>
                                                <?php if ($guia['tours_proximos'] > 0): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                                        <i class="fas fa-calendar-day mr-1"></i><?php echo $guia['tours_proximos']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Idiomas -->
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Idiomas</p>
                                            <?php if ($guia['idiomas_count'] > 0): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    <i class="fas fa-globe mr-1"></i><?php echo $guia['idiomas_count']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">Sin idiomas</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Calificación y realizados -->
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Calificación</p>
                                            <?php if ($guia['total_calificaciones'] > 0): ?>
                                                <div class="flex items-center space-x-1">
                                                    <div class="flex items-center">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $guia['calificacion_promedio'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-xs"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="text-xs font-medium"><?php echo number_format($guia['calificacion_promedio'], 1); ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">Sin calificaciones</span>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Realizados</p>
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i><?php echo $guia['tours_diarios_total']; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Contacto -->
                                    <div class="border-t border-gray-100 pt-3 mb-3">
                                        <div class="text-xs text-gray-600 space-y-1">
                                            <div class="flex items-center truncate">
                                                <i class="fas fa-envelope text-gray-400 mr-2 flex-shrink-0"></i>
                                                <span class="truncate"><?php echo htmlspecialchars($guia['email']); ?></span>
                                            </div>
                                            <?php if ($guia['telefono']): ?>
                                                <div class="flex items-center truncate">
                                                    <i class="fas fa-phone text-gray-400 mr-2 flex-shrink-0"></i>
                                                    <span class="truncate"><?php echo htmlspecialchars($guia['telefono']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="flex justify-center space-x-3 pt-2 border-t border-gray-100">
                                        <a href="ver.php?id=<?php echo $guia['id_guia']; ?>" 
                                           class="flex items-center px-3 py-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                            <i class="fas fa-eye mr-2"></i>Ver
                                        </a>
                                        <a href="editar.php?id=<?php echo $guia['id_guia']; ?>" 
                                           class="flex items-center px-3 py-2 text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                            <i class="fas fa-edit mr-2"></i>Editar
                                        </a>
                                        <button onclick="eliminarGuia(<?php echo $guia['id_guia']; ?>, '<?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>')" 
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
                                                Guía
                                                <i class="<?php echo getSortIcon('nombre', $orden, $direccion); ?> ml-2"></i>
                                            </a>
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                            Contacto
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <a href="<?php echo getSortUrl('estado', $orden, $direccion); ?>" class="group inline-flex items-center hover:text-gray-900">
                                                Estado
                                                <i class="<?php echo getSortIcon('estado', $orden, $direccion); ?> ml-2"></i>
                                            </a>
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                            Tours
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">
                                            Calificación
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                            Idiomas
                                        </th>
                                        <th scope="col" class="relative px-4 py-3">
                                            <span class="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($guias as $guia): ?>
                                        <tr class="hover:bg-gray-50">
                                            <!-- Columna Principal: Guía -->
                                            <td class="px-4 py-4">
                                                <div class="flex items-center">
                                                    <?php if ($guia['foto_url']): ?>
                                                        <img class="h-10 w-10 rounded-full flex-shrink-0" src="<?php echo htmlspecialchars($guia['foto_url']); ?>" alt="">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0">
                                                            <span class="text-white font-medium text-sm">
                                                                <?php echo strtoupper(substr($guia['nombre'], 0, 1) . substr($guia['apellido'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="ml-3 min-w-0 flex-1">
                                                        <div class="text-sm font-medium text-gray-900 truncate">
                                                            <?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 truncate">
                                                            ID: #<?php echo $guia['id_guia']; ?>
                                                        </div>
                                                        <!-- Info móvil: contacto visible solo en pantallas pequeñas -->
                                                        <div class="sm:hidden mt-1">
                                                            <div class="text-xs text-gray-600 truncate">
                                                                <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($guia['email']); ?>
                                                            </div>
                                                            <?php if ($guia['telefono']): ?>
                                                                <div class="text-xs text-gray-600 truncate">
                                                                    <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($guia['telefono']); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Contacto (oculto en móvil) -->
                                            <td class="px-4 py-4 hidden sm:table-cell">
                                                <div class="text-sm text-gray-900">
                                                    <div class="flex items-center mb-1 truncate">
                                                        <i class="fas fa-envelope text-gray-400 mr-2 flex-shrink-0"></i>
                                                        <span class="truncate"><?php echo htmlspecialchars($guia['email']); ?></span>
                                                    </div>
                                                    <?php if ($guia['telefono']): ?>
                                                        <div class="flex items-center truncate">
                                                            <i class="fas fa-phone text-gray-400 mr-2 flex-shrink-0"></i>
                                                            <span class="truncate"><?php echo htmlspecialchars($guia['telefono']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            
                                            <!-- Estado -->
                                            <td class="px-4 py-4">
                                                <div>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getEstadoClass($guia['estado']); ?>">
                                                        <i class="<?php echo getEstadoIcon($guia['estado']); ?> mr-1"></i>
                                                        <span class="hidden sm:inline"><?php echo $guia['estado']; ?></span>
                                                    </span>
                                                    <?php if ($guia['dias_ocupados'] > 0): ?>
                                                        <div class="text-xs text-gray-500 mt-1 hidden lg:block">
                                                            <?php echo $guia['dias_ocupados']; ?> días ocupados
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            
                                            <!-- Tours (oculto en tabletas) -->
                                            <td class="px-4 py-4 hidden lg:table-cell">
                                                <div class="space-y-1">
                                                    <div class="flex flex-wrap gap-1">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <i class="fas fa-map-marked-alt mr-1"></i>
                                                            <?php echo $guia['total_tours']; ?>
                                                        </span>
                                                        <?php if ($guia['tours_proximos'] > 0): ?>
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                                <i class="fas fa-calendar-day mr-1"></i>
                                                                <?php echo $guia['tours_proximos']; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <i class="fas fa-check-circle mr-1"></i>
                                                            <?php echo $guia['tours_diarios_total']; ?> realizados
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Calificación (oculto en pantallas grandes) -->
                                            <td class="px-4 py-4 hidden xl:table-cell">
                                                <?php if ($guia['total_calificaciones'] > 0): ?>
                                                    <div class="flex items-center space-x-1">
                                                        <div class="flex items-center">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="fas fa-star <?php echo $i <= $guia['calificacion_promedio'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-xs"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <span class="text-xs font-medium text-gray-900"><?php echo number_format($guia['calificacion_promedio'], 1); ?></span>
                                                        <span class="text-xs text-gray-500">(<?php echo $guia['total_calificaciones']; ?>)</span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-xs">Sin calificaciones</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Idiomas (oculto en móvil) -->
                                            <td class="px-4 py-4 hidden md:table-cell">
                                                <?php if ($guia['idiomas_count'] > 0): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        <i class="fas fa-globe mr-1"></i>
                                                        <?php echo $guia['idiomas_count']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-xs">Sin idiomas</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Acciones -->
                                            <td class="px-4 py-4 text-right">
                                                <div class="flex items-center justify-end space-x-1">
                                                    <a href="ver.php?id=<?php echo $guia['id_guia']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                                       title="Ver detalles">
                                                        <i class="fas fa-eye text-sm"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $guia['id_guia']; ?>" 
                                                       class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors"
                                                       title="Editar">
                                                        <i class="fas fa-edit text-sm"></i>
                                                    </a>
                                                    <button onclick="eliminarGuia(<?php echo $guia['id_guia']; ?>, '<?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>')" 
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
                <h3 class="text-lg font-medium text-gray-900 mt-4">Eliminar Guía</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        ¿Estás seguro de que deseas eliminar al guía <span id="nombreGuia" class="font-medium"></span>? 
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
        let guiaAEliminar = null;

        function eliminarGuia(id, nombre) {
            guiaAEliminar = id;
            document.getElementById('nombreGuia').textContent = nombre;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalEliminar').classList.add('hidden');
            guiaAEliminar = null;
        }

        function confirmarEliminacion() {
            if (guiaAEliminar) {
                window.location.href = `eliminar.php?id=${guiaAEliminar}`;
            }
        }

        function exportarGuias() {
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
