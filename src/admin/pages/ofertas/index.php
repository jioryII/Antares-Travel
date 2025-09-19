<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';

// Verificar sesión de administrador
verificarSesionAdmin();

// Parámetros de búsqueda y filtrado
$buscar = $_GET['buscar'] ?? '';
$estado = $_GET['estado'] ?? '';
$tipo_oferta = $_GET['tipo_oferta'] ?? '';
$orden = $_GET['orden'] ?? 'fecha_inicio';
$direccion = $_GET['direccion'] ?? 'desc';
$pagina = intval($_GET['pagina'] ?? 1);
$por_pagina = 20;

try {
    $connection = getConnection();
    
    // Construir consulta base
    $where_conditions = [];
    $params = [];
    
    if ($buscar) {
        $where_conditions[] = "(o.nombre LIKE ? OR o.descripcion LIKE ? OR o.codigo_promocional LIKE ?)";
        $search_term = "%$buscar%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($estado) {
        $where_conditions[] = "o.estado = ?";
        $params[] = $estado;
    }
    
    if ($tipo_oferta) {
        $where_conditions[] = "o.tipo_oferta = ?";
        $params[] = $tipo_oferta;
    }
    
    // Contar total de registros
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    $total_sql = "SELECT COUNT(*) FROM ofertas o $where_clause";
    $total_stmt = $connection->prepare($total_sql);
    $total_stmt->execute($params);
    $total_registros = $total_stmt->fetchColumn();
    
    // Calcular paginación
    $total_paginas = ceil($total_registros / $por_pagina);
    $offset = ($pagina - 1) * $por_pagina;
    
    // Obtener ofertas con estadísticas
    $order_clause = "ORDER BY o.$orden $direccion";
    $limit_clause = "LIMIT $por_pagina OFFSET $offset";
    
    $ofertas_sql = "SELECT o.*,
                           CONCAT(a.nombre) as creado_por_nombre,
                           COALESCE(uso_stats.total_usos, 0) as total_usos,
                           COALESCE(uso_stats.total_descuento, 0) as total_descuento,
                           COALESCE(tours_stats.tours_aplicables, 0) as tours_aplicables,
                           COALESCE(usuarios_stats.usuarios_aplicables, 0) as usuarios_aplicables
                    FROM ofertas o 
                    LEFT JOIN administradores a ON o.creado_por = a.id_admin
                    LEFT JOIN (
                        SELECT id_oferta, 
                               COUNT(*) as total_usos,
                               SUM(monto_descuento) as total_descuento
                        FROM historial_uso_ofertas 
                        GROUP BY id_oferta
                    ) uso_stats ON o.id_oferta = uso_stats.id_oferta
                    LEFT JOIN (
                        SELECT id_oferta, COUNT(*) as tours_aplicables
                        FROM ofertas_tours 
                        GROUP BY id_oferta
                    ) tours_stats ON o.id_oferta = tours_stats.id_oferta
                    LEFT JOIN (
                        SELECT id_oferta, COUNT(*) as usuarios_aplicables
                        FROM ofertas_usuarios 
                        GROUP BY id_oferta
                    ) usuarios_stats ON o.id_oferta = usuarios_stats.id_oferta
                    $where_clause 
                    $order_clause 
                    $limit_clause";
    
    $ofertas_stmt = $connection->prepare($ofertas_sql);
    $ofertas_stmt->execute($params);
    $ofertas = $ofertas_stmt->fetchAll();
    
    // Estadísticas generales
    $stats_sql = "SELECT 
                    COUNT(*) as total_ofertas,
                    SUM(CASE WHEN estado = 'Activa' THEN 1 ELSE 0 END) as ofertas_activas,
                    SUM(CASE WHEN estado = 'Pausada' THEN 1 ELSE 0 END) as ofertas_pausadas,
                    SUM(CASE WHEN estado = 'Finalizada' THEN 1 ELSE 0 END) as ofertas_finalizadas,
                    SUM(CASE WHEN estado = 'Borrador' THEN 1 ELSE 0 END) as ofertas_borrador,
                    COALESCE(SUM(uso_stats.total_usos), 0) as total_usos_sistema,
                    COALESCE(SUM(uso_stats.total_descuento), 0) as total_descuento_sistema,
                    SUM(CASE WHEN fecha_fin >= CURDATE() AND estado = 'Activa' THEN 1 ELSE 0 END) as ofertas_vigentes
                  FROM ofertas o
                  LEFT JOIN (
                      SELECT id_oferta, COUNT(*) as total_usos, SUM(monto_descuento) as total_descuento
                      FROM historial_uso_ofertas 
                      GROUP BY id_oferta
                  ) uso_stats ON o.id_oferta = uso_stats.id_oferta";
    $stats_stmt = $connection->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
    // Obtener un tour de ejemplo para los enlaces de demostración
    $tour_ejemplo_sql = "SELECT id_tour, titulo FROM tours WHERE 1=1 ORDER BY id_tour ASC LIMIT 1";
    try {
        $tour_ejemplo_stmt = $connection->query($tour_ejemplo_sql);
        $tour_ejemplo = $tour_ejemplo_stmt ? $tour_ejemplo_stmt->fetch() : null;
        $id_tour_ejemplo = $tour_ejemplo ? $tour_ejemplo['id_tour'] : 1;
    } catch (Exception $e) {
        $id_tour_ejemplo = 1; // Fallback por defecto
    }

    // Rutas dinámicas basadas en la estructura del servidor
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $project_path = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
    
    $rutas = [
        'tours' => $base_url . $project_path . '/public/tours.php',
        'detalles' => $base_url . $project_path . '/src/detalles.php',
        'reserva' => $base_url . $project_path . '/src/reserva.php',
        'dashboard' => '../dashboard/index.php'  // Ruta relativa interna
    ];
    
    $page_title = "Gestión de Ofertas";
    
} catch (Exception $e) {
    $error = "Error al cargar ofertas: " . $e->getMessage();
    $ofertas = [];
    $stats = [
        'total_ofertas' => 0, 
        'ofertas_activas' => 0, 
        'ofertas_pausadas' => 0,
        'ofertas_finalizadas' => 0,
        'ofertas_borrador' => 0,
        'total_usos_sistema' => 0,
        'total_descuento_sistema' => 0,
        'ofertas_vigentes' => 0
    ];
    $total_registros = 0;
    $total_paginas = 0;
    $page_title = "Error - Ofertas";
}

function getEstadoClass($estado) {
    switch($estado) {
        case 'Activa': return 'bg-green-100 text-green-800';
        case 'Pausada': return 'bg-yellow-100 text-yellow-800';
        case 'Finalizada': return 'bg-gray-100 text-gray-800';
        case 'Borrador': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getEstadoIcon($estado) {
    switch($estado) {
        case 'Activa': return 'fas fa-check-circle';
        case 'Pausada': return 'fas fa-pause-circle';
        case 'Finalizada': return 'fas fa-times-circle';
        case 'Borrador': return 'fas fa-edit';
        default: return 'fas fa-question-circle';
    }
}

function getTipoOfertaClass($tipo) {
    switch($tipo) {
        case 'Porcentaje': return 'bg-purple-100 text-purple-800';
        case 'Monto_Fijo': return 'bg-indigo-100 text-indigo-800';
        case 'Precio_Especial': return 'bg-pink-100 text-pink-800';
        case '2x1': return 'bg-orange-100 text-orange-800';
        case 'Combo': return 'bg-teal-100 text-teal-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getSortIcon($campo, $orden_actual, $direccion_actual) {
    if ($campo !== $orden_actual) {
        return 'fas fa-sort text-gray-400';
    }
    return $direccion_actual === 'asc' ? 'fas fa-sort-up text-blue-600' : 'fas fa-sort-down text-blue-600';
}

function getSortUrl($campo, $orden_actual, $direccion_actual) {
    global $buscar, $estado, $tipo_oferta;
    $nueva_direccion = ($campo === $orden_actual && $direccion_actual === 'asc') ? 'desc' : 'asc';
    $params = [
        'orden' => $campo,
        'direccion' => $nueva_direccion,
        'buscar' => $buscar,
        'estado' => $estado,
        'tipo_oferta' => $tipo_oferta
    ];
    return 'index.php?' . http_build_query(array_filter($params));
}

function formatearFecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

function formatearMonto($monto) {
    return 'S/ ' . number_format($monto, 2);
}

function estaVigente($fecha_inicio, $fecha_fin, $estado) {
    $ahora = date('Y-m-d H:i:s');
    return $estado === 'Activa' && $fecha_inicio <= $ahora && $fecha_fin >= $ahora;
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
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .desktop-table {
                display: none;
            }
            .mobile-cards {
                display: block;
            }
        }
        
        @media (min-width: 769px) {
            .desktop-table {
                display: table;
            }
            .mobile-cards {
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
                <div class="mb-6 lg:mb-8">
                    <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-xl lg:text-3xl font-bold text-gray-900">
                                <i class="fas fa-tags text-red-600 mr-2 lg:mr-3"></i>Gestión de Ofertas
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600 mt-1">Administra las ofertas y promociones del sistema</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="crear.php" class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm">
                                <i class="fas fa-plus mr-2"></i>Nueva Oferta
                            </a>
                            <button onclick="exportarOfertas()" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
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
                                <h3 class="text-red-800 font-medium">Error</h3>
                                <p class="text-red-700 mt-1"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 card-hover">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-red-100">
                                <i class="fas fa-tags text-red-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Total Ofertas</p>
                                <p class="text-lg lg:text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total_ofertas']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 card-hover">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-green-100">
                                <i class="fas fa-check-circle text-green-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Activas</p>
                                <p class="text-lg lg:text-2xl font-semibold text-green-600"><?php echo number_format($stats['ofertas_activas']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 card-hover">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-blue-100">
                                <i class="fas fa-calendar-check text-blue-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Vigentes</p>
                                <p class="text-lg lg:text-2xl font-semibold text-blue-600"><?php echo number_format($stats['ofertas_vigentes']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 card-hover">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-purple-100">
                                <i class="fas fa-chart-line text-purple-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Total Usos</p>
                                <p class="text-lg lg:text-2xl font-semibold text-purple-600"><?php echo number_format($stats['total_usos_sistema']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segunda fila de estadísticas -->
                <div class="hidden md:grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Ahorrado</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo formatearMonto($stats['total_descuento_sistema']); ?></p>
                            </div>
                            <div class="p-3 rounded-full bg-green-100">
                                <i class="fas fa-coins text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-xs text-gray-600">
                            <p>Dinero ahorrado por los clientes</p>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">En Pausa</p>
                                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['ofertas_pausadas']); ?></p>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100">
                                <i class="fas fa-pause-circle text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Borradores</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['ofertas_borrador']); ?></p>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100">
                                <i class="fas fa-edit text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acceso Rápido a Funcionalidades del Módulo -->
                <div class="bg-gradient-to-r from-red-50 to-orange-50 rounded-lg shadow-lg p-6 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-tools text-red-600 mr-2"></i>
                                Funciones del Módulo de Ofertas
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">Acceso directo a todas las funcionalidades</p>
                        </div>
                        <div class="hidden md:flex items-center text-sm text-red-600">
                            <i class="fas fa-tags mr-1"></i>
                            Gestión completa
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <!-- Crear Nueva Oferta -->
                        <a href="crear.php" class="group">
                            <div class="bg-white rounded-lg p-4 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-l-4 border-green-500">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-green-200 transition-colors">
                                        <i class="fas fa-plus text-green-600 text-xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">Crear Oferta</h4>
                                    <p class="text-xs text-gray-500">Nueva promoción</p>
                                    <div class="mt-2 text-xs text-green-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-arrow-right mr-1"></i>Crear
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Exportar Ofertas -->
                        <div class="group cursor-pointer" onclick="exportarOfertas()">
                            <div class="bg-white rounded-lg p-4 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-l-4 border-blue-500">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-blue-200 transition-colors">
                                        <i class="fas fa-download text-blue-600 text-xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">Exportar</h4>
                                    <p class="text-xs text-gray-500">Descargar CSV</p>
                                    <div class="mt-2 text-xs text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-file-csv mr-1"></i>CSV
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ver Ofertas Activas -->
                        <a href="?estado=Activa" class="group">
                            <div class="bg-white rounded-lg p-4 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-l-4 border-emerald-500">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-emerald-200 transition-colors">
                                        <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">Activas</h4>
                                    <p class="text-xs text-gray-500"><?php echo number_format($stats['ofertas_activas']); ?> ofertas</p>
                                    <div class="mt-2 text-xs text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-filter mr-1"></i>Filtrar
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Ver Ofertas Pausadas -->
                        <a href="?estado=Pausada" class="group">
                            <div class="bg-white rounded-lg p-4 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-l-4 border-yellow-500">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-yellow-200 transition-colors">
                                        <i class="fas fa-pause-circle text-yellow-600 text-xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">Pausadas</h4>
                                    <p class="text-xs text-gray-500"><?php echo number_format($stats['ofertas_pausadas']); ?> ofertas</p>
                                    <div class="mt-2 text-xs text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-filter mr-1"></i>Filtrar
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Ver por Tipo - Porcentaje -->
                        <a href="?tipo_oferta=Porcentaje" class="group">
                            <div class="bg-white rounded-lg p-4 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-l-4 border-purple-500">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-purple-200 transition-colors">
                                        <i class="fas fa-percentage text-purple-600 text-xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">Por %</h4>
                                    <p class="text-xs text-gray-500">Descuento %</p>
                                    <div class="mt-2 text-xs text-purple-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-filter mr-1"></i>Ver
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Ver por Tipo - Monto Fijo -->
                        <a href="?tipo_oferta=Monto_Fijo" class="group">
                            <div class="bg-white rounded-lg p-4 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-l-4 border-indigo-500">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-indigo-200 transition-colors">
                                        <i class="fas fa-dollar-sign text-indigo-600 text-xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">Monto</h4>
                                    <p class="text-xs text-gray-500">Descuento fijo</p>
                                    <div class="mt-2 text-xs text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-filter mr-1"></i>Ver
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Ver por Tipo - 2x1 -->
                        <a href="?tipo_oferta=2x1" class="group">
                            <div class="bg-white rounded-lg p-4 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-l-4 border-pink-500">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-pink-200 transition-colors">
                                        <i class="fas fa-gift text-pink-600 text-xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">2x1</h4>
                                    <p class="text-xs text-gray-500">Promociones</p>
                                    <div class="mt-2 text-xs text-pink-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-filter mr-1"></i>Ver
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Limpiar Filtros -->
                        <a href="index.php" class="group">
                            <div class="bg-white rounded-lg p-4 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-l-4 border-gray-500">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-gray-200 transition-colors">
                                        <i class="fas fa-refresh text-gray-600 text-xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">Todos</h4>
                                    <p class="text-xs text-gray-500">Limpiar filtros</p>
                                    <div class="mt-2 text-xs text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-list mr-1"></i>Ver todo
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <!-- Información de uso -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-green-50 rounded-lg border-l-4 border-green-400">
                            <div class="flex">
                                <i class="fas fa-chart-line text-green-600 mt-1 mr-3"></i>
                                <div>
                                    <h5 class="font-medium text-green-900 mb-1">Estadísticas del Sistema</h5>
                                    <p class="text-sm text-green-800">
                                        Total de usos: <strong><?php echo number_format($stats['total_usos_sistema']); ?></strong> |
                                        Ahorro generado: <strong><?php echo formatearMonto($stats['total_descuento_sistema']); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                            <div class="flex">
                                <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                                <div>
                                    <h5 class="font-medium text-blue-900 mb-1">Acciones Rápidas</h5>
                                    <p class="text-sm text-blue-800">
                                        Usa los botones de arriba para crear, filtrar o exportar ofertas. 
                                        Los enlaces te llevan directamente a las funciones del módulo.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros y búsqueda -->
                <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 mb-6 lg:mb-8">
                    <form method="GET" class="space-y-4 lg:space-y-0 lg:flex lg:items-end lg:space-x-4">
                        <div class="flex-1">
                            <label for="buscar" class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" name="buscar" id="buscar" placeholder="Nombre, descripción o código..." 
                                   value="<?php echo htmlspecialchars($buscar); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div class="lg:w-48">
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select name="estado" id="estado" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                                <option value="">Todos los estados</option>
                                <option value="Activa" <?php echo $estado === 'Activa' ? 'selected' : ''; ?>>Activa</option>
                                <option value="Pausada" <?php echo $estado === 'Pausada' ? 'selected' : ''; ?>>Pausada</option>
                                <option value="Finalizada" <?php echo $estado === 'Finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                                <option value="Borrador" <?php echo $estado === 'Borrador' ? 'selected' : ''; ?>>Borrador</option>
                            </select>
                        </div>
                        <div class="lg:w-48">
                            <label for="tipo_oferta" class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                            <select name="tipo_oferta" id="tipo_oferta" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                                <option value="">Todos los tipos</option>
                                <option value="Porcentaje" <?php echo $tipo_oferta === 'Porcentaje' ? 'selected' : ''; ?>>Porcentaje</option>
                                <option value="Monto_Fijo" <?php echo $tipo_oferta === 'Monto_Fijo' ? 'selected' : ''; ?>>Monto Fijo</option>
                                <option value="Precio_Especial" <?php echo $tipo_oferta === 'Precio_Especial' ? 'selected' : ''; ?>>Precio Especial</option>
                                <option value="2x1" <?php echo $tipo_oferta === '2x1' ? 'selected' : ''; ?>>2x1</option>
                                <option value="Combo" <?php echo $tipo_oferta === 'Combo' ? 'selected' : ''; ?>>Combo</option>
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>Buscar
                            </button>
                            <a href="index.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabla de ofertas - Desktop -->
                <div class="desktop-table bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo getSortUrl('nombre', $orden, $direccion); ?>" class="flex items-center hover:text-red-600">
                                            Oferta
                                            <i class="<?php echo getSortIcon('nombre', $orden, $direccion); ?> ml-1"></i>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo getSortUrl('tipo_oferta', $orden, $direccion); ?>" class="flex items-center hover:text-red-600">
                                            Tipo
                                            <i class="<?php echo getSortIcon('tipo_oferta', $orden, $direccion); ?> ml-1"></i>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Descuento
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo getSortUrl('fecha_inicio', $orden, $direccion); ?>" class="flex items-center hover:text-red-600">
                                            Vigencia
                                            <i class="<?php echo getSortIcon('fecha_inicio', $orden, $direccion); ?> ml-1"></i>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo getSortUrl('estado', $orden, $direccion); ?>" class="flex items-center hover:text-red-600">
                                            Estado
                                            <i class="<?php echo getSortIcon('estado', $orden, $direccion); ?> ml-1"></i>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usos
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($ofertas)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-tags text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg font-medium mb-2">No se encontraron ofertas</p>
                                            <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ofertas as $oferta): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="flex items-start">
                                                    <?php if ($oferta['imagen_banner']): ?>
                                                        <img class="h-12 w-12 rounded-lg object-cover mr-3" src="../../<?php echo htmlspecialchars($oferta['imagen_banner']); ?>" alt="Banner">
                                                    <?php else: ?>
                                                        <div class="h-12 w-12 rounded-lg bg-red-100 flex items-center justify-center mr-3">
                                                            <i class="fas fa-tag text-red-600"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 truncate">
                                                            <?php echo htmlspecialchars($oferta['nombre']); ?>
                                                            <?php if ($oferta['destacada']): ?>
                                                                <i class="fas fa-star text-yellow-500 ml-1" title="Oferta destacada"></i>
                                                            <?php endif; ?>
                                                        </p>
                                                        <?php if ($oferta['codigo_promocional']): ?>
                                                            <p class="text-xs text-gray-500">
                                                                Código: <span class="font-mono bg-gray-100 px-1 rounded"><?php echo htmlspecialchars($oferta['codigo_promocional']); ?></span>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if ($oferta['descripcion']): ?>
                                                            <p class="text-xs text-gray-500 mt-1 line-clamp-2"><?php echo htmlspecialchars(substr($oferta['descripcion'], 0, 100)); ?>...</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getTipoOfertaClass($oferta['tipo_oferta']); ?>">
                                                    <?php echo str_replace('_', ' ', $oferta['tipo_oferta']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <?php if ($oferta['tipo_oferta'] === 'Porcentaje'): ?>
                                                    <span class="font-semibold text-green-600"><?php echo $oferta['valor_descuento']; ?>%</span>
                                                <?php elseif ($oferta['tipo_oferta'] === 'Monto_Fijo'): ?>
                                                    <span class="font-semibold text-green-600">-<?php echo formatearMonto($oferta['valor_descuento']); ?></span>
                                                <?php elseif ($oferta['tipo_oferta'] === 'Precio_Especial'): ?>
                                                    <span class="font-semibold text-blue-600"><?php echo formatearMonto($oferta['precio_especial']); ?></span>
                                                <?php else: ?>
                                                    <span class="font-semibold text-purple-600"><?php echo $oferta['tipo_oferta']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <div>
                                                    <p class="font-medium">Desde: <?php echo formatearFecha($oferta['fecha_inicio']); ?></p>
                                                    <p class="text-gray-500">Hasta: <?php echo formatearFecha($oferta['fecha_fin']); ?></p>
                                                    <?php if (estaVigente($oferta['fecha_inicio'], $oferta['fecha_fin'], $oferta['estado'])): ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mt-1">
                                                            <i class="fas fa-circle text-green-400 mr-1" style="font-size: 6px;"></i>
                                                            Vigente
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getEstadoClass($oferta['estado']); ?>">
                                                    <i class="<?php echo getEstadoIcon($oferta['estado']); ?> mr-1"></i>
                                                    <?php echo $oferta['estado']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <div class="text-center">
                                                    <p class="font-semibold text-gray-900"><?php echo number_format($oferta['total_usos']); ?></p>
                                                    <?php if ($oferta['limite_usos']): ?>
                                                        <p class="text-xs text-gray-500">de <?php echo number_format($oferta['limite_usos']); ?></p>
                                                        <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                                                            <div class="bg-red-600 h-1 rounded-full" style="width: <?php echo min(100, ($oferta['total_usos'] / $oferta['limite_usos']) * 100); ?>%"></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-xs text-gray-500">Sin límite</p>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <a href="ver.php?id=<?php echo $oferta['id_oferta']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $oferta['id_oferta']; ?>" 
                                                       class="text-yellow-600 hover:text-yellow-900" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($oferta['estado'] === 'Activa'): ?>
                                                        <button onclick="pausarOferta(<?php echo $oferta['id_oferta']; ?>)" 
                                                                class="text-orange-600 hover:text-orange-900" title="Pausar">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    <?php elseif ($oferta['estado'] === 'Pausada'): ?>
                                                        <button onclick="activarOferta(<?php echo $oferta['id_oferta']; ?>)" 
                                                                class="text-green-600 hover:text-green-900" title="Activar">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button onclick="eliminarOferta(<?php echo $oferta['id_oferta']; ?>)" 
                                                            class="text-red-600 hover:text-red-900" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cards móviles -->
                <div class="mobile-cards space-y-4">
                    <?php if (empty($ofertas)): ?>
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <i class="fas fa-tags text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-900 mb-2">No se encontraron ofertas</p>
                            <p class="text-gray-600">Intenta ajustar los filtros de búsqueda</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ofertas as $oferta): ?>
                            <div class="bg-white rounded-lg shadow-lg p-4 card-hover">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-start">
                                        <?php if ($oferta['imagen_banner']): ?>
                                            <img class="h-12 w-12 rounded-lg object-cover mr-3" src="../../<?php echo htmlspecialchars($oferta['imagen_banner']); ?>" alt="Banner">
                                        <?php else: ?>
                                            <div class="h-12 w-12 rounded-lg bg-red-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-tag text-red-600"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($oferta['nombre']); ?>
                                                <?php if ($oferta['destacada']): ?>
                                                    <i class="fas fa-star text-yellow-500 ml-1"></i>
                                                <?php endif; ?>
                                            </h3>
                                            <?php if ($oferta['codigo_promocional']): ?>
                                                <p class="text-xs text-gray-500 font-mono bg-gray-100 px-1 rounded inline-block mt-1">
                                                    <?php echo htmlspecialchars($oferta['codigo_promocional']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getEstadoClass($oferta['estado']); ?>">
                                        <i class="<?php echo getEstadoIcon($oferta['estado']); ?> mr-1"></i>
                                        <?php echo $oferta['estado']; ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-gray-500">Tipo</p>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getTipoOfertaClass($oferta['tipo_oferta']); ?>">
                                            <?php echo str_replace('_', ' ', $oferta['tipo_oferta']); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Descuento</p>
                                        <p class="text-sm font-semibold text-gray-900">
                                            <?php if ($oferta['tipo_oferta'] === 'Porcentaje'): ?>
                                                <?php echo $oferta['valor_descuento']; ?>%
                                            <?php elseif ($oferta['tipo_oferta'] === 'Monto_Fijo'): ?>
                                                -<?php echo formatearMonto($oferta['valor_descuento']); ?>
                                            <?php elseif ($oferta['tipo_oferta'] === 'Precio_Especial'): ?>
                                                <?php echo formatearMonto($oferta['precio_especial']); ?>
                                            <?php else: ?>
                                                <?php echo $oferta['tipo_oferta']; ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p class="text-xs text-gray-500">Vigencia</p>
                                    <p class="text-sm text-gray-900">
                                        <?php echo formatearFecha($oferta['fecha_inicio']); ?> - <?php echo formatearFecha($oferta['fecha_fin']); ?>
                                    </p>
                                    <?php if (estaVigente($oferta['fecha_inicio'], $oferta['fecha_fin'], $oferta['estado'])): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mt-1">
                                            <i class="fas fa-circle text-green-400 mr-1" style="font-size: 6px;"></i>
                                            Vigente
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-4">
                                    <p class="text-xs text-gray-500">Usos</p>
                                    <div class="flex items-center">
                                        <span class="text-sm font-semibold text-gray-900"><?php echo number_format($oferta['total_usos']); ?></span>
                                        <?php if ($oferta['limite_usos']): ?>
                                            <span class="text-sm text-gray-500 ml-1">/ <?php echo number_format($oferta['limite_usos']); ?></span>
                                            <div class="flex-1 ml-3">
                                                <div class="w-full bg-gray-200 rounded-full h-1">
                                                    <div class="bg-red-600 h-1 rounded-full" style="width: <?php echo min(100, ($oferta['total_usos'] / $oferta['limite_usos']) * 100); ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <a href="ver.php?id=<?php echo $oferta['id_oferta']; ?>" 
                                           class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?php echo $oferta['id_oferta']; ?>" 
                                           class="text-yellow-600 hover:text-yellow-800">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($oferta['estado'] === 'Activa'): ?>
                                            <button onclick="pausarOferta(<?php echo $oferta['id_oferta']; ?>)" 
                                                    class="text-orange-600 hover:text-orange-800">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        <?php elseif ($oferta['estado'] === 'Pausada'): ?>
                                            <button onclick="activarOferta(<?php echo $oferta['id_oferta']; ?>)" 
                                                    class="text-green-600 hover:text-green-800">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="eliminarOferta(<?php echo $oferta['id_oferta']; ?>)" 
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php if ($oferta['creado_por_nombre']): ?>
                                        <p class="text-xs text-gray-500">
                                            Por: <?php echo htmlspecialchars($oferta['creado_por_nombre']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="bg-white rounded-lg shadow-lg px-4 py-3 mt-6 flex items-center justify-between border-t border-gray-200 sm:px-6">
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
                                    <span class="font-medium"><?php echo $total_registros; ?></span>
                                    ofertas
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php
                                    $inicio = max(1, $pagina - 2);
                                    $fin = min($total_paginas, $pagina + 2);
                                    
                                    if ($pagina > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif;
                                    
                                    for ($i = $inicio; $i <= $fin; $i++): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $pagina ? 'z-10 bg-red-50 border-red-500 text-red-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor;
                                    
                                    if ($pagina < $total_paginas): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Funciones para gestionar ofertas
        function pausarOferta(idOferta) {
            if (confirm('¿Estás seguro de que deseas pausar esta oferta?')) {
                fetch('procesar_oferta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=pausar&id=${idOferta}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al pausar la oferta: ' + error);
                });
            }
        }

        function activarOferta(idOferta) {
            if (confirm('¿Estás seguro de que deseas activar esta oferta?')) {
                fetch('procesar_oferta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=activar&id=${idOferta}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al activar la oferta: ' + error);
                });
            }
        }

        function eliminarOferta(idOferta) {
            if (confirm('¿Estás seguro de que deseas eliminar esta oferta? Esta acción no se puede deshacer.')) {
                fetch('procesar_oferta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=eliminar&id=${idOferta}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al eliminar la oferta: ' + error);
                });
            }
        }

        function exportarOfertas() {
            const url = new URL('exportar.php', window.location);
            const params = new URLSearchParams(window.location.search);
            params.forEach((value, key) => {
                url.searchParams.append(key, value);
            });
            window.open(url.toString(), '_blank');
        }

        // Filtrado en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const buscarInput = document.getElementById('buscar');
            if (buscarInput) {
                let timeoutId;
                buscarInput.addEventListener('input', function() {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(function() {
                        document.querySelector('form').submit();
                    }, 500);
                });
            }
        });

        // Mostrar información de API
        function mostrarInfoAPI() {
            const modalHTML = `
                <div id="apiModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" onclick="cerrarModalAPI()">
                    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white" onclick="event.stopPropagation()">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900">
                                <i class="fas fa-code text-red-600 mr-2"></i>
                                API de Validación de Ofertas
                            </h3>
                            <button onclick="cerrarModalAPI()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">Endpoint:</h4>
                                <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">
                                    POST /src/api/validar_ofertas.php
                                </code>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">Ejemplo de Uso:</h4>
                                <pre class="bg-gray-800 text-green-400 p-3 rounded text-sm overflow-x-auto"><code>{
    "codigo_promocional": "VERANO25",
    "tours": [
        {"id": 1, "precio": 500, "cantidad": 2}
    ],
    "id_usuario": 123
}</code></pre>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">Funcionalidades:</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li><i class="fas fa-check text-green-600 mr-2"></i>Validación de códigos promocionales</li>
                                    <li><i class="fas fa-check text-green-600 mr-2"></i>Cálculo automático de descuentos</li>
                                    <li><i class="fas fa-check text-green-600 mr-2"></i>Verificación de elegibilidad por usuario</li>
                                    <li><i class="fas fa-check text-green-600 mr-2"></i>Control de límites de uso y fechas</li>
                                </ul>
                            </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                                <p class="text-sm text-blue-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Esta API es utilizada automáticamente por el sistema de carrito 
                                    para validar y aplicar ofertas en tiempo real.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        function cerrarModalAPI() {
            const modal = document.getElementById('apiModal');
            if (modal) {
                modal.remove();
            }
        }

        // Mostrar documentación
        function mostrarDocumentacion() {
            const modalHTML = `
                <div id="docModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" onclick="cerrarModalDoc()">
                    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white" onclick="event.stopPropagation()">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900">
                                <i class="fas fa-book text-indigo-600 mr-2"></i>
                                Documentación del Sistema de Ofertas
                            </h3>
                            <button onclick="cerrarModalDoc()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-6 max-h-96 overflow-y-auto">
                            <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-400">
                                <h4 class="font-semibold text-green-800 mb-2">✅ Archivos Verificados Existentes:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                    <div class="bg-white p-2 rounded">📄 /public/tours.php</div>
                                    <div class="bg-white p-2 rounded">📄 /src/detalles.php</div>
                                    <div class="bg-white p-2 rounded">📄 /src/reserva.php</div>
                                    <div class="bg-white p-2 rounded">📄 /src/admin/pages/dashboard/index.php</div>
                                    <div class="bg-white p-2 rounded">📄 /src/api/validar_ofertas.php</div>
                                    <div class="bg-white p-2 rounded">📄 Módulo Ofertas Completo</div>
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                                <h4 class="font-semibold text-blue-800 mb-2">🎯 Cómo usar el sistema:</h4>
                                <ol class="list-decimal list-inside space-y-2 text-sm text-blue-700">
                                    <li>Crear ofertas desde este panel administrativo</li>
                                    <li>Las ofertas aparecen automáticamente en /public/tours.php con badges</li>
                                    <li>Los detalles de ofertas se muestran en /src/detalles.php</li>
                                    <li>Los usuarios aplican códigos en /src/reserva.php</li>
                                    <li>Las estadísticas se ven en el dashboard</li>
                                </ol>
                            </div>
                            
                            <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                                <h4 class="font-semibold text-yellow-800 mb-2">⚠️ Si hay problemas de enlace:</h4>
                                <ul class="list-disc list-inside space-y-1 text-sm text-yellow-700">
                                    <li>Verificar que el servidor web esté ejecutándose</li>
                                    <li>Comprobar la configuración de rutas del servidor</li>
                                    <li>Asegurar permisos correctos de archivos</li>
                                    <li>Verificar que hay datos de tours en la base de datos</li>
                                    <li>Comprobar configuración de base de datos en /src/config/conexion.php</li>
                                </ul>
                            </div>
                            
                            <div class="bg-purple-50 p-4 rounded-lg border-l-4 border-purple-400">
                                <h4 class="font-semibold text-purple-800 mb-2">📋 Archivos de documentación:</h4>
                                <ul class="text-sm space-y-1">
                                    <li>📄 <strong>README.md</strong> - Documentación del módulo</li>
                                    <li>📄 <strong>INTEGRACION_COMPLETA.md</strong> - Documentación técnica detallada</li>
                                    <li>📄 <strong>ofertas_schema.sql</strong> - Esquema de base de datos</li>
                                    <li>📄 <strong>OFERTAS_COMPLETADO.md</strong> - Resumen de implementación</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        function cerrarModalDoc() {
            const modal = document.getElementById('docModal');
            if (modal) {
                modal.remove();
            }
        }
    </script>
</body>
</html>
