<?php
// Verificar autenticación
require_once __DIR__ . '/../../auth/middleware.php';
verificarSesionAdmin();

require_once __DIR__ . '/../../functions/tours_functions.php';

// Parámetros de búsqueda y paginación
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$por_pagina = 10;

// Obtener tours
$resultado = obtenerTours($pagina, $por_pagina, $busqueda);
$tours = $resultado['success'] ? $resultado['data'] : [];
$total_paginas = $resultado['success'] ? $resultado['total_paginas'] : 1;
$total_tours = $resultado['success'] ? $resultado['total'] : 0;

// Obtener datos para los selects
$regiones_result = obtenerRegiones();
$regiones = $regiones_result['success'] ? $regiones_result['data'] : [];

$guias_result = obtenerGuiasDisponibles();
$guias = $guias_result['success'] ? $guias_result['data'] : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tours - Antares Travel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../assets/css/responsive.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../components/header.php'; ?>
    
    <div class="flex">
        <?php include __DIR__ . '/../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <br><br><br>
            <div class="p-4 lg:p-8">
                <!-- Título y acciones mejorados -->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Gestión de Tours</h1>
                        <p class="text-gray-600 text-sm lg:text-base">Administra todos los tours disponibles</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <!-- Botón de exportar -->
                        <button onclick="exportarTours()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            <span class="hidden sm:inline">Exportar</span>
                        </button>
                        
                        <!-- Botón de estadísticas -->
                        <button onclick="verEstadisticasDetalladas()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line mr-2"></i>
                            <span class="hidden sm:inline">Estadísticas</span>
                        </button>
                        
                        <!-- Botón principal -->
                        <button onclick="abrirModalCrear()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i>
                            Nuevo Tour
                        </button>
                    </div>
                </div>

                <!-- Barra de búsqueda y filtros mejorados -->
                <div class="bg-white rounded-lg shadow mb-6 p-4">
                    <form method="GET" class="space-y-4">
                        <!-- Búsqueda principal -->
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" 
                                       placeholder="Buscar tours por título, descripción, lugar..."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center justify-center">
                                <i class="fas fa-search mr-2"></i>Buscar
                            </button>
                            <?php if (!empty($busqueda)): ?>
                                <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Limpiar
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Filtros adicionales -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200">
                            <!-- Filtro por región -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Región</label>
                                <select name="region" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todas las regiones</option>
                                    <?php foreach ($regiones as $region): ?>
                                        <option value="<?php echo $region['id_region']; ?>" <?php echo (isset($_GET['region']) && $_GET['region'] == $region['id_region']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($region['nombre_region']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Filtro por guía -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado del Guía</label>
                                <select name="guia_estado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Cualquier estado</option>
                                    <option value="con_guia" <?php echo (isset($_GET['guia_estado']) && $_GET['guia_estado'] == 'con_guia') ? 'selected' : ''; ?>>Con guía asignado</option>
                                    <option value="sin_guia" <?php echo (isset($_GET['guia_estado']) && $_GET['guia_estado'] == 'sin_guia') ? 'selected' : ''; ?>>Sin guía asignado</option>
                                </select>
                            </div>
                            
                            <!-- Filtro por rango de precio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rango de Precio</label>
                                <div class="flex space-x-2">
                                    <input type="number" name="precio_min" placeholder="Mín" 
                                           value="<?php echo htmlspecialchars($_GET['precio_min'] ?? ''); ?>"
                                           class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <input type="number" name="precio_max" placeholder="Máx" 
                                           value="<?php echo htmlspecialchars($_GET['precio_max'] ?? ''); ?>"
                                           class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Estadísticas mejoradas con más métricas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow p-4 lg:p-6 text-white">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-white bg-opacity-20 rounded-md flex items-center justify-center">
                                    <i class="fas fa-map-marked-alt text-white"></i>
                                </div>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-blue-100">Total Tours</p>
                                <p class="text-lg lg:text-2xl font-bold"><?php echo number_format($total_tours); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow p-4 lg:p-6 text-white">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-white bg-opacity-20 rounded-md flex items-center justify-center">
                                    <i class="fas fa-user-tie text-white"></i>
                                </div>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-green-100">Guías Disponibles</p>
                                <p class="text-lg lg:text-2xl font-bold"><?php echo count($guias); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow p-4 lg:p-6 text-white">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-white bg-opacity-20 rounded-md flex items-center justify-center">
                                    <i class="fas fa-map text-white"></i>
                                </div>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-purple-100">Regiones</p>
                                <p class="text-lg lg:text-2xl font-bold"><?php echo count($regiones); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow p-4 lg:p-6 text-white">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-white bg-opacity-20 rounded-md flex items-center justify-center">
                                    <i class="fas fa-calendar-check text-white"></i>
                                </div>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-orange-100">Tours Activos</p>
                                <p class="text-lg lg:text-2xl font-bold"><?php 
                                    // Contar tours con reservas recientes
                                    $tours_activos = 0;
                                    foreach ($tours as $tour) {
                                        if ($tour['total_reservas'] > 0) {
                                            $tours_activos++;
                                        }
                                    }
                                    echo number_format($tours_activos); 
                                ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de tours -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Vista móvil - Cards -->
                    <div class="block lg:hidden">
                        <?php if (empty($tours)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <?php if (!empty($busqueda)): ?>
                                    No se encontraron tours que coincidan con la búsqueda
                                <?php else: ?>
                                    No hay tours registrados
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-gray-200">
                                <?php foreach ($tours as $tour): ?>
                                    <div class="p-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <?php if (!empty($tour['imagen_principal'])): ?>
                                                    <img src="/Antares-Travel/<?php echo htmlspecialchars($tour['imagen_principal']); ?>" 
                                                         alt="Tour" class="h-16 w-16 rounded-lg object-cover">
                                                <?php else: ?>
                                                    <div class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h3 class="text-sm font-medium text-gray-900 truncate">
                                                        <?php echo htmlspecialchars($tour['titulo']); ?>
                                                    </h3>
                                                    <span class="text-sm font-semibold text-green-600">
                                                        S/. <?php echo number_format($tour['precio'], 2); ?>
                                                    </span>
                                                </div>
                                                <p class="text-xs text-gray-500 mb-2">
                                                    <?php echo htmlspecialchars(substr($tour['descripcion'], 0, 80)) . (strlen($tour['descripcion']) > 80 ? '...' : ''); ?>
                                                </p>
                                                <div class="flex justify-between items-center text-xs text-gray-500 mb-3">
                                                    <span><i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars($tour['duracion']); ?></span>
                                                    <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($tour['nombre_region'] ?? 'Sin región'); ?></span>
                                                    <span><i class="fas fa-users mr-1"></i><?php echo number_format($tour['total_reservas']); ?> reservas</span>
                                                </div>
                                                <div class="flex justify-end space-x-2">
                                                    <button onclick="verTour(<?php echo $tour['id_tour']; ?>)" 
                                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="editarTour(<?php echo $tour['id_tour']; ?>)" 
                                                            class="p-2 text-green-600 hover:bg-green-50 rounded" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="eliminarTour(<?php echo $tour['id_tour']; ?>)" 
                                                            class="p-2 text-red-600 hover:bg-red-50 rounded" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Vista desktop - Tabla -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tour
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Precio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Duración
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Región
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Guía
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reservas
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($tours)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            <?php if (!empty($busqueda)): ?>
                                                No se encontraron tours que coincidan con la búsqueda
                                            <?php else: ?>
                                                No hay tours registrados
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tours as $tour): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-12 w-12">
                                                        <?php if (!empty($tour['imagen_principal'])): ?>
                                                            <img src="/Antares-Travel/<?php echo htmlspecialchars($tour['imagen_principal']); ?>" 
                                                                 alt="Tour" class="h-12 w-12 rounded-lg object-cover">
                                                        <?php else: ?>
                                                            <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                                                <i class="fas fa-image text-gray-400"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($tour['titulo']); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars(substr($tour['descripcion'], 0, 50)) . (strlen($tour['descripcion']) > 50 ? '...' : ''); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                S/. <?php echo number_format($tour['precio'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($tour['duracion']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($tour['nombre_region'] ?? 'Sin región'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php 
                                                if ($tour['guia_nombre']) {
                                                    echo htmlspecialchars($tour['guia_nombre'] . ' ' . $tour['guia_apellido']);
                                                } else {
                                                    echo '<span class="text-gray-400">Sin asignar</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo number_format($tour['total_reservas']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button onclick="verTour(<?php echo $tour['id_tour']; ?>)" 
                                                            class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="editarTour(<?php echo $tour['id_tour']; ?>)" 
                                                            class="text-green-600 hover:text-green-900" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="eliminarTour(<?php echo $tour['id_tour']; ?>)" 
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

                <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <?php if ($pagina > 1): ?>
                                        <a href="?pagina=<?php echo $pagina - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Anterior
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($pagina < $total_paginas): ?>
                                        <a href="?pagina=<?php echo $pagina + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>" 
                                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Siguiente
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Mostrando página <span class="font-medium"><?php echo $pagina; ?></span> de <span class="font-medium"><?php echo $total_paginas; ?></span>
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <?php if ($pagina > 1): ?>
                                                <a href="?pagina=1&busqueda=<?php echo urlencode($busqueda); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <i class="fas fa-angle-double-left"></i>
                                                </a>
                                                <a href="?pagina=<?php echo $pagina - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <i class="fas fa-angle-left"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $inicio = max(1, $pagina - 2);
                                            $fin = min($total_paginas, $pagina + 2);
                                            
                                            for ($i = $inicio; $i <= $fin; $i++): ?>
                                                <a href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 <?php echo $i == $pagina ? 'bg-blue-50 border-blue-500 text-blue-600' : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>
                                            
                                            <?php if ($pagina < $total_paginas): ?>
                                                <a href="?pagina=<?php echo $pagina + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <i class="fas fa-angle-right"></i>
                                                </a>
                                                <a href="?pagina=<?php echo $total_paginas; ?>&busqueda=<?php echo urlencode($busqueda); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <i class="fas fa-angle-double-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicador de carga global -->
    <div id="loading-indicator" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="text-gray-700 font-medium">Procesando...</span>
        </div>
    </div>

    <!-- Modal para crear/editar tour -->
    <div id="modalTour" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Nuevo Tour</h3>
                    <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="formTour" enctype="multipart/form-data">
                    <input type="hidden" id="tour_id" name="tour_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Título -->
                        <div class="md:col-span-2">
                            <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                            <input type="text" id="titulo" name="titulo" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                            <textarea id="descripcion" name="descripcion" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <!-- Precio -->
                        <div>
                            <label for="precio" class="block text-sm font-medium text-gray-700 mb-1">Precio (S/.) *</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Duración -->
                        <div>
                            <label for="duracion" class="block text-sm font-medium text-gray-700 mb-1">Duración *</label>
                            <input type="text" id="duracion" name="duracion" required 
                                   placeholder="Ej: 1 día, 8 horas"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Región -->
                        <div>
                            <label for="id_region" class="block text-sm font-medium text-gray-700 mb-1">Región</label>
                            <select id="id_region" name="id_region" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Seleccionar región</option>
                                <?php foreach ($regiones as $region): ?>
                                    <option value="<?php echo $region['id_region']; ?>">
                                        <?php echo htmlspecialchars($region['nombre_region']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Guía -->
                        <div>
                            <label for="id_guia" class="block text-sm font-medium text-gray-700 mb-1">Guía Asignado</label>
                            <select id="id_guia" name="id_guia" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Sin asignar</option>
                                <?php foreach ($guias as $guia): ?>
                                    <option value="<?php echo $guia['id_guia']; ?>">
                                        <?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Lugar de salida -->
                        <div>
                            <label for="lugar_salida" class="block text-sm font-medium text-gray-700 mb-1">Lugar de Salida *</label>
                            <input type="text" id="lugar_salida" name="lugar_salida" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Lugar de llegada -->
                        <div>
                            <label for="lugar_llegada" class="block text-sm font-medium text-gray-700 mb-1">Lugar de Llegada *</label>
                            <input type="text" id="lugar_llegada" name="lugar_llegada" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Hora de salida -->
                        <div>
                            <label for="hora_salida" class="block text-sm font-medium text-gray-700 mb-1">Hora de Salida</label>
                            <input type="time" id="hora_salida" name="hora_salida" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Hora de llegada -->
                        <div>
                            <label for="hora_llegada" class="block text-sm font-medium text-gray-700 mb-1">Hora de Llegada</label>
                            <input type="time" id="hora_llegada" name="hora_llegada" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Imagen -->
                        <div class="md:col-span-2">
                            <label for="imagen" class="block text-sm font-medium text-gray-700 mb-1">Imagen Principal</label>
                            <input type="file" id="imagen" name="imagen" accept="image/*" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-sm text-gray-500 mt-1">Formatos: JPG, PNG, WEBP. Máximo 5MB.</p>
                            <div id="imagenPreview" class="mt-2 hidden">
                                <img id="imagenPreviewImg" src="" alt="Preview" class="h-32 w-32 object-cover rounded-lg">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                        <button type="button" onclick="cerrarModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" id="btnGuardar" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Guardar Tour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del tour -->
    <div id="modalVerTour" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Detalles del Tour</h3>
                    <button onclick="cerrarModalVer()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div id="detallesTour">
                    <!-- Se carga dinámicamente -->
                </div>
                
                <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
                    <button onclick="cerrarModalVer()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuración y variables globales
        let tourEditando = null;
        
        // Funciones de UI
        function mostrarError(mensaje) {
            alert('Error: ' + mensaje);
        }
        
        function mostrarExito(mensaje) {
            alert('Éxito: ' + mensaje);
        }
        
        function mostrarCargando() {
            document.getElementById('loading-indicator').classList.remove('hidden');
        }
        
        function ocultarCargando() {
            document.getElementById('loading-indicator').classList.add('hidden');
        }
        
        // Funciones de modales
        function abrirModalCrear() {
            limpiarFormulario();
            document.getElementById('modalTitle').textContent = 'Nuevo Tour';
            document.getElementById('modalTour').classList.remove('hidden');
            tourEditando = null;
        }
        
        function cerrarModal() {
            document.getElementById('modalTour').classList.add('hidden');
            limpiarFormulario();
        }
        
        function cerrarModalVer() {
            document.getElementById('modalVerTour').classList.add('hidden');
        }
        
        function limpiarFormulario() {
            document.getElementById('formTour').reset();
            document.getElementById('tour_id').value = '';
            document.getElementById('imagenPreview').classList.add('hidden');
            
            // Limpiar errores
            const errores = document.querySelectorAll('.error-message');
            errores.forEach(error => error.remove());
            
            // Limpiar bordes de error
            const campos = document.querySelectorAll('.border-red-500');
            campos.forEach(campo => {
                campo.classList.remove('border-red-500');
                campo.classList.add('border-gray-300');
            });
        }
        
        // Función para ver detalles de un tour
        function verTour(idTour) {
            mostrarCargando();
            
            fetch('../../api/tours.php?action=obtener&id=' + idTour)
                .then(response => response.json())
                .then(data => {
                    ocultarCargando();
                    if (data.success) {
                        mostrarDetallesModal(data.data);
                    } else {
                        mostrarError('Error al cargar el tour: ' + data.message);
                    }
                })
                .catch(error => {
                    ocultarCargando();
                    mostrarError('Error al cargar el tour: ' + error.message);
                });
        }
        
        // Función para mostrar detalles en modal
        function mostrarDetallesModal(tour) {
            const html = `
                <div class="space-y-4">
                    ${tour.imagen_principal ? `
                        <div class="mb-4">
                            <img src="/Antares-Travel/${tour.imagen_principal}" alt="${tour.titulo}" 
                                 class="w-full h-48 object-cover rounded-lg">
                        </div>
                    ` : ''}
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-900">Título:</h4>
                            <p class="text-gray-700">${tour.titulo}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Precio:</h4>
                            <p class="text-gray-700">S/. ${parseFloat(tour.precio).toFixed(2)}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Duración:</h4>
                            <p class="text-gray-700">${tour.duracion}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Región:</h4>
                            <p class="text-gray-700">${tour.nombre_region || 'Sin región'}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Lugar de Salida:</h4>
                            <p class="text-gray-700">${tour.lugar_salida}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Lugar de Llegada:</h4>
                            <p class="text-gray-700">${tour.lugar_llegada}</p>
                        </div>
                        ${tour.hora_salida ? `
                            <div>
                                <h4 class="font-semibold text-gray-900">Hora de Salida:</h4>
                                <p class="text-gray-700">${tour.hora_salida}</p>
                            </div>
                        ` : ''}
                        ${tour.hora_llegada ? `
                            <div>
                                <h4 class="font-semibold text-gray-900">Hora de Llegada:</h4>
                                <p class="text-gray-700">${tour.hora_llegada}</p>
                            </div>
                        ` : ''}
                        <div>
                            <h4 class="font-semibold text-gray-900">Guía:</h4>
                            <p class="text-gray-700">${tour.nombre_guia ? tour.nombre_guia + ' ' + tour.apellido_guia : 'Sin asignar'}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Total Reservas:</h4>
                            <p class="text-gray-700">${tour.total_reservas || 0}</p>
                        </div>
                    </div>
                    
                    <div class="col-span-2">
                        <h4 class="font-semibold text-gray-900">Descripción:</h4>
                        <p class="text-gray-700">${tour.descripcion}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('detallesTour').innerHTML = html;
            document.getElementById('modalVerTour').classList.remove('hidden');
        }
        
        // Función para editar tour
        function editarTour(idTour) {
            mostrarCargando();
            tourEditando = idTour;
            
            fetch('../../api/tours.php?action=obtener&id=' + idTour)
                .then(response => response.json())
                .then(data => {
                    ocultarCargando();
                    if (data.success) {
                        cargarDatosFormulario(data.data);
                        document.getElementById('modalTitle').textContent = 'Editar Tour';
                        document.getElementById('modalTour').classList.remove('hidden');
                    } else {
                        mostrarError('Error al cargar el tour: ' + data.message);
                    }
                })
                .catch(error => {
                    ocultarCargando();
                    mostrarError('Error al cargar el tour: ' + error.message);
                });
        }
        
        // Función para cargar datos en el formulario
        function cargarDatosFormulario(tour) {
            document.getElementById('tour_id').value = tour.id_tour;
            document.getElementById('titulo').value = tour.titulo;
            document.getElementById('descripcion').value = tour.descripcion;
            document.getElementById('precio').value = tour.precio;
            document.getElementById('duracion').value = tour.duracion;
            document.getElementById('id_region').value = tour.id_region || '';
            document.getElementById('id_guia').value = tour.id_guia || '';
            document.getElementById('lugar_salida').value = tour.lugar_salida;
            document.getElementById('lugar_llegada').value = tour.lugar_llegada;
            document.getElementById('hora_salida').value = tour.hora_salida || '';
            document.getElementById('hora_llegada').value = tour.hora_llegada || '';
            
            // Mostrar imagen actual si existe
            if (tour.imagen_principal) {
                document.getElementById('imagenPreviewImg').src = '/Antares-Travel/' + tour.imagen_principal;
                document.getElementById('imagenPreview').classList.remove('hidden');
            }
        }
        
        // Función para eliminar tour
        function eliminarTour(idTour) {
            if (confirm('¿Estás seguro de que deseas eliminar este tour? Esta acción no se puede deshacer.')) {
                mostrarCargando();
                
                fetch('../../api/tours.php?action=eliminar&id=' + idTour, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    ocultarCargando();
                    if (data.success) {
                        mostrarExito('Tour eliminado exitosamente');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        mostrarError('Error al eliminar el tour: ' + data.message);
                    }
                })
                .catch(error => {
                    ocultarCargando();
                    mostrarError('Error al eliminar el tour: ' + error.message);
                });
            }
        }
        
        // Función para manejar el envío del formulario
        function manejarEnvioFormulario(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const esEdicion = tourEditando !== null;
            
            let url = '../../api/tours.php?action=';
            url += esEdicion ? 'actualizar&id=' + tourEditando : 'crear';
            
            mostrarCargando();
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                ocultarCargando();
                if (data.success) {
                    mostrarExito(esEdicion ? 'Tour actualizado exitosamente' : 'Tour creado exitosamente');
                    cerrarModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    mostrarError('Error: ' + data.message);
                }
            })
            .catch(error => {
                ocultarCargando();
                mostrarError('Error al procesar: ' + error.message);
            });
        }
        
        // Funciones adicionales
        function exportarTours() {
            mostrarExito('Función de exportación disponible próximamente');
        }
        
        function verEstadisticasDetalladas() {
            mostrarExito('Función de estadísticas detalladas disponible próximamente');
        }
        
        // Validación de imagen
        function validarImagen(file) {
            const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            const tamanoMaximo = 5 * 1024 * 1024; // 5MB
            
            if (!tiposPermitidos.includes(file.type)) {
                mostrarError('Tipo de archivo no permitido. Use JPG, PNG, WEBP o GIF.');
                return false;
            }
            
            if (file.size > tamanoMaximo) {
                mostrarError('El archivo es demasiado grande. Máximo 5MB.');
                return false;
            }
            
            return true;
        }
        
        // Inicialización cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar eventos del formulario
            const formTour = document.getElementById('formTour');
            if (formTour) {
                formTour.addEventListener('submit', manejarEnvioFormulario);
            }
            
            // Preview de imagen
            const inputImagen = document.getElementById('imagen');
            if (inputImagen) {
                inputImagen.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file && validarImagen(file)) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('imagenPreviewImg').src = e.target.result;
                            document.getElementById('imagenPreview').classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Funcionalidad responsiva básica
            initializeResponsive();
        });
        
        // Funciones de responsive básico
        function initializeResponsive() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('-translate-x-full');
                    if (overlay) {
                        overlay.classList.toggle('hidden');
                    }
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                });
            }
            
            // Cerrar sidebar en cambio de tamaño
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    if (sidebar) sidebar.classList.add('-translate-x-full');
                    if (overlay) overlay.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>
