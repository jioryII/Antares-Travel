<?php
// Verificar autenticación
require_once __DIR__ . '/../../auth/middleware.php';
verificarSesionAdmin();

require_once __DIR__ . '/../../functions/tours_functions.php';

// Función para convertir hora de 24h a 12h con AM/PM
function convertirHora12($hora24) {
    if (empty($hora24)) return 'No especificada';
    
    $partes = explode(':', $hora24);
    $horas = intval($partes[0]);
    $minutos = $partes[1];
    $periodo = 'AM';
    
    if ($horas == 0) {
        $horas = 12;
    } elseif ($horas == 12) {
        $periodo = 'PM';
    } elseif ($horas > 12) {
        $horas = $horas - 12;
        $periodo = 'PM';
    }
    
    return $horas . ':' . $minutos . ' ' . $periodo;
}

// Parámetros de búsqueda y paginación
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 10;

// Recopilar filtros (solo búsqueda)
$filtros = [];
if (!empty($_GET['busqueda'])) {
    $filtros['busqueda'] = trim($_GET['busqueda']);
}

// Obtener tours con filtros
$resultado = obtenerTours($pagina, $por_pagina, $filtros);
$tours = $resultado['success'] ? $resultado['data'] : [];
$total_paginas = $resultado['success'] ? $resultado['total_paginas'] : 1;
$total_tours = $resultado['success'] ? $resultado['total'] : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tours - Antares Travel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Estilos para móviles */
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
                display: block;
            }
            .mobile-cards {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../components/header.php'; ?>
    
    <div class="flex">
        <?php include __DIR__ . '/../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-6 lg:mb-8">
                    <br><br><br>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-map-marked-alt text-blue-600 mr-3"></i>Gestión de Tours
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600">Administra todos los tours disponibles</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <button onclick="abrirModalCrear()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Nuevo Tour
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Barra de búsqueda mejorada -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg shadow mb-6 p-4">
                    <form method="GET" class="space-y-4">
                        <!-- Búsqueda principal con diseño mejorado -->
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-500"></i>
                                </div>
                                <input type="text" name="busqueda" value="<?php echo htmlspecialchars($filtros['busqueda'] ?? ''); ?>" 
                                       placeholder="Buscar tours por título o descripción..."
                                       class="w-full pl-10 pr-4 py-3 border border-gray-400 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm"
                                       autocomplete="off">

                                <?php if (!empty($filtros['busqueda'])): ?>
                                    <div class="mt-2 flex items-center text-xs">
                                        <div class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md border border-blue-200">
                                            <i class="fas fa-filter mr-1"></i>
                                            Filtrando por: "<strong><?php echo htmlspecialchars($filtros['busqueda']); ?></strong>"
                                            <span class="ml-2 text-blue-600">(<?php echo $total_tours; ?> resultado<?php echo $total_tours != 1 ? 's' : ''; ?>)</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg flex items-center justify-center transition-colors shadow-md hover:shadow-lg">
                                    <i class="fas fa-search mr-2"></i>
                                    Buscar
                                </button>
                                <?php if (!empty($filtros)): ?>
                                    <a href="index.php" class="bg-gray-400 hover:bg-gray-500 text-white px-6 py-3 rounded-lg flex items-center justify-center transition-colors shadow-md hover:shadow-lg">
                                        <i class="fas fa-times mr-2"></i>
                                        Limpiar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Header con estadística y status integrados -->
                <div class="bg-white rounded-lg shadow-md border border-gray-200 mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Estadística principal -->
                            <div class="flex items-center space-x-4">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-3 text-white">
                                    <i class="fas fa-map-marked-alt text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Tours Registrados</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_tours); ?></p>
                                    <?php if (!empty($filtros['busqueda'])): ?>
                                        <p class="text-xs text-blue-600">
                                            <i class="fas fa-filter mr-1"></i>Resultados filtrados
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Estado del sistema -->
                            <div class="flex items-center space-x-6">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500 uppercase">Estado</p>
                                    <div class="flex items-center mt-1">
                                        <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                                        <span class="text-sm text-gray-700">Sistema Activo</span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($tours)): ?>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500 uppercase">Esta Página</p>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo count($tours); ?> tours</p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($total_paginas > 1): ?>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500 uppercase">Paginación</p>
                                    <p class="text-sm text-gray-700">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barra de acciones rápidas -->
                    <div class="px-6 py-3 bg-gray-100 border-t border-gray-200">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center space-x-2 text-sm text-gray-600">
                                <i class="fas fa-info-circle"></i>
                                <span id="contador-filtros">
                                    <?php if (empty($tours)): ?>
                                        No hay tours para mostrar
                                    <?php else: ?>
                                        Sistema de gestión de tours
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <?php if (!empty($filtros['busqueda'])): ?>
                                    <a href="index.php" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-md hover:bg-blue-200 transition-colors">
                                        <i class="fas fa-times mr-1"></i>Limpiar filtro
                                    </a>
                                <?php endif; ?>
                                
                                <button onclick="location.reload()" class="text-xs text-gray-500 hover:text-gray-700 transition-colors">
                                    <i class="fas fa-sync-alt mr-1"></i>Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de tours -->
                <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                    <!-- Vista móvil - Cards -->
                    <div class="block lg:hidden">
                        <?php if (empty($tours)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                                <?php if (!empty($filtros)): ?>
                                    <h3 class="text-lg font-medium text-gray-700 mb-2">No se encontraron tours</h3>
                                    <p class="text-sm text-gray-500 mb-4">
                                        No hay tours que coincidan con la búsqueda:
                                    </p>
                                    <div class="bg-gray-50 p-3 rounded-lg text-left inline-block">
                                        <p><strong>Término buscado:</strong> "<?php echo htmlspecialchars($filtros['busqueda']); ?>"</p>
                                    </div>
                                    <br><br>
                                    <a href="index.php" class="text-blue-600 hover:text-blue-800 underline">
                                        <i class="fas fa-times mr-1"></i>Limpiar búsqueda
                                    </a>
                                <?php else: ?>
                                    <h3 class="text-lg font-medium text-gray-700 mb-2">No hay tours registrados</h3>
                                    <p class="text-sm text-gray-500 mb-4">Comienza creando tu primer tour</p>
                                    <button onclick="abrirModalCrear()" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                        <i class="fas fa-plus mr-2"></i>Crear Primer Tour
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-gray-200">
                                <?php foreach ($tours as $tour): ?>
                                    <div class="tour-card p-4 cursor-pointer hover:bg-blue-100 transition-colors duration-200 border-b border-gray-100" onclick="verTour(<?php echo $tour['id_tour']; ?>)">
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
                                                    <h3 class="text-sm font-medium text-gray-900 truncate tour-titulo">
                                                        <?php echo htmlspecialchars($tour['titulo']); ?>
                                                    </h3>
                                                    <span class="text-sm font-semibold text-green-600">
                                                        S/. <?php echo number_format($tour['precio'], 2); ?>
                                                    </span>
                                                </div>
                                                <p class="text-xs text-gray-500 mb-2 tour-descripcion">
                                                    <?php echo htmlspecialchars($tour['descripcion']); ?>
                                                </p>
                                                <div class="flex justify-between items-center text-xs text-gray-500 mb-3">
                                                    <span><i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars($tour['duracion']); ?></span>
                                                    <div class="flex space-x-3">
                                                        <?php if (!empty($tour['hora_salida'])): ?>
                                                            <span><i class="fas fa-play-circle text-green-500 mr-1"></i><?php echo htmlspecialchars(convertirHora12($tour['hora_salida'])); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($tour['hora_llegada'])): ?>
                                                            <span><i class="fas fa-stop-circle text-red-500 mr-1"></i><?php echo htmlspecialchars(convertirHora12($tour['hora_llegada'])); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex justify-end space-x-2" onclick="event.stopPropagation()">
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
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-100 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Tour
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Descripción
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Precio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Duración
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Horarios
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-300">
                                <?php if (empty($tours)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                                                <?php if (!empty($filtros)): ?>
                                                    <h3 class="text-lg font-medium text-gray-700 mb-2">No se encontraron tours</h3>
                                                    <p class="text-sm text-gray-500 mb-4">
                                                        No hay tours que coincidan con la búsqueda:
                                                        <?php if (!empty($filtros['busqueda'])): ?>
                                                            <strong>"<?php echo htmlspecialchars($filtros['busqueda']); ?>"</strong>
                                                        <?php endif; ?>
                                                    </p>
                                                    <a href="index.php" class="text-blue-600 hover:text-blue-800 underline">
                                                        <i class="fas fa-times mr-1"></i>Limpiar búsqueda
                                                    </a>
                                                <?php else: ?>
                                                    <h3 class="text-lg font-medium text-gray-700 mb-2">No hay tours registrados</h3>
                                                    <p class="text-sm text-gray-500 mb-4">Comienza creando tu primer tour</p>
                                                    <button onclick="abrirModalCrear()" 
                                                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                                        <i class="fas fa-plus mr-2"></i>Crear Primer Tour
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tours as $tour): ?>
                                        <tr class="tour-row hover:bg-blue-100 cursor-pointer transition-colors duration-200 border-b border-gray-200" onclick="verTour(<?php echo $tour['id_tour']; ?>)">
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
                                                        <div class="text-sm font-medium text-gray-900 tour-titulo">
                                                            <?php echo htmlspecialchars($tour['titulo']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                                <div class="line-clamp-3 tour-descripcion">
                                                    <?php echo htmlspecialchars($tour['descripcion']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                S/. <?php echo number_format($tour['precio'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($tour['duracion']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="space-y-1">
                                                    <?php if (!empty($tour['hora_salida'])): ?>
                                                        <div class="flex items-center text-xs">
                                                            <i class="fas fa-play-circle text-green-500 mr-1"></i>
                                                            <span><?php echo htmlspecialchars(convertirHora12($tour['hora_salida'])); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($tour['hora_llegada'])): ?>
                                                        <div class="flex items-center text-xs">
                                                            <i class="fas fa-stop-circle text-red-500 mr-1"></i>
                                                            <span><?php echo htmlspecialchars(convertirHora12($tour['hora_llegada'])); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (empty($tour['hora_salida']) && empty($tour['hora_llegada'])): ?>
                                                        <span class="text-gray-400 text-xs">Sin horarios</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" onclick="event.stopPropagation()">
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
                        <?php
                        // Construir query string para mantener filtro de búsqueda
                        $query_params = [];
                        if (!empty($filtros['busqueda'])) $query_params['busqueda'] = $filtros['busqueda'];
                        
                        function buildUrl($pagina, $params) {
                            $params['pagina'] = $pagina;
                            return '?' . http_build_query($params);
                        }
                        ?>
                        
                        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <?php if ($pagina > 1): ?>
                                        <a href="<?php echo buildUrl($pagina - 1, $query_params); ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Anterior
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($pagina < $total_paginas): ?>
                                        <a href="<?php echo buildUrl($pagina + 1, $query_params); ?>" 
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
                                                <a href="<?php echo buildUrl(1, $query_params); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <i class="fas fa-angle-double-left"></i>
                                                </a>
                                                <a href="<?php echo buildUrl($pagina - 1, $query_params); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <i class="fas fa-angle-left"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $inicio = max(1, $pagina - 2);
                                            $fin = min($total_paginas, $pagina + 2);
                                            
                                            for ($i = $inicio; $i <= $fin; $i++): ?>
                                                <a href="<?php echo buildUrl($i, $query_params); ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 <?php echo $i == $pagina ? 'bg-blue-50 border-blue-500 text-blue-600' : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>
                                            
                                            <?php if ($pagina < $total_paginas): ?>
                                                <a href="<?php echo buildUrl($pagina + 1, $query_params); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <i class="fas fa-angle-right"></i>
                                                </a>
                                                <a href="<?php echo buildUrl($total_paginas, $query_params); ?>" 
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
    <div id="modalTour" class="fixed inset-0 bg-black bg-opacity-60 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-6 border w-11/12 md:w-3/4 lg:w-1/2 shadow-2xl rounded-xl bg-gradient-to-br from-white to-gray-50">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-6 pb-4 border-b-2 border-blue-200">
                    <div class="flex items-center space-x-3">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-2">
                            <i class="fas fa-map-marked-alt text-white text-lg"></i>
                        </div>
                        <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Nuevo Tour</h3>
                    </div>
                    <button onclick="cerrarModal()" class="text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full p-2 transition-all duration-200">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <form id="formTour" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" id="tour_id" name="tour_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Título -->
                        <div class="md:col-span-2">
                            <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-heading text-blue-500 mr-2"></i>
                                Título *
                            </label>
                            <input type="text" id="titulo" name="titulo" required 
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 bg-white hover:border-blue-400 shadow-sm">
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label for="descripcion" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-align-left text-blue-500 mr-2"></i>
                                Descripción *
                            </label>
                            <textarea id="descripcion" name="descripcion" rows="4" required
                                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 bg-white hover:border-blue-400 shadow-sm resize-none"></textarea>
                        </div>

                        <!-- Precio -->
                        <div>
                            <label for="precio" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-dollar-sign text-gray-600 mr-2"></i>
                                Precio (S/.) *
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-medium">S/.</span>
                                <input type="number" id="precio" name="precio" step="0.01" min="0" required 
                                       class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 bg-white hover:border-blue-400 shadow-sm">
                            </div>
                        </div>

                        <!-- Duración -->
                        <div>
                            <label for="duracion" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-clock text-gray-600 mr-2"></i>
                                Duración *
                            </label>
                            <input type="text" id="duracion" name="duracion" required 
                                   placeholder="Ej: 1 día, 8 horas"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 bg-white hover:border-blue-400 shadow-sm">
                        </div>

                        <!-- Hora de inicio -->
                        <div>
                            <label for="hora_salida" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-play-circle text-blue-600 mr-2"></i>
                                Hora de Inicio
                            </label>
                            <input type="time" id="hora_salida" name="hora_salida" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 bg-white hover:border-blue-400 shadow-sm">
                        </div>

                        <!-- Hora de fin -->
                        <div>
                            <label for="hora_llegada" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-stop-circle text-gray-600 mr-2"></i>
                                Hora de Fin
                            </label>
                            <input type="time" id="hora_llegada" name="hora_llegada" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 bg-white hover:border-blue-400 shadow-sm">
                        </div>

                        <!-- Imagen -->
                        <div class="md:col-span-2">
                            <label for="imagen" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-camera text-blue-500 mr-2"></i>
                                Imagen Principal
                            </label>
                            <div class="relative">
                                <input type="file" id="imagen" name="imagen" accept="image/*" 
                                       class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 bg-white hover:border-blue-400 shadow-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <p class="text-sm text-gray-500 mt-2 flex items-center">
                                <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                                Formatos: JPG, PNG, WEBP. Máximo 5MB.
                            </p>
                            <div id="imagenPreview" class="mt-4 hidden">
                                <div class="border-2 border-dashed border-blue-300 rounded-lg p-4 bg-blue-50">
                                    <img id="imagenPreviewImg" src="" alt="Preview" class="h-40 w-40 object-cover rounded-lg mx-auto shadow-md">
                                    <p class="text-center text-sm text-blue-600 mt-2 font-medium">Vista previa de la imagen</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 mt-8 pt-6 border-t-2 border-gray-200">
                        <button type="button" onclick="cerrarModal()" 
                                class="px-6 py-3 border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition-all duration-200 font-medium shadow-sm">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" id="btnGuardar" 
                                class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i class="fas fa-save mr-2"></i>Guardar Tour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del tour -->
    <div id="modalVerTour" class="fixed inset-0 bg-black bg-opacity-60 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-6 border w-11/12 md:w-2/3 lg:w-1/2 shadow-2xl rounded-xl bg-gradient-to-br from-white to-blue-50">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-6 pb-4 border-b-2 border-blue-200">
                    <div class="flex items-center space-x-3">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-2">
                            <i class="fas fa-eye text-white text-lg"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Detalles del Tour</h3>
                    </div>
                    <button onclick="cerrarModalVer()" class="text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full p-2 transition-all duration-200">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div id="detallesTour" class="space-y-6">
                    <!-- Se carga dinámicamente -->
                </div>
                
                <div class="flex justify-end mt-8 pt-6 border-t-2 border-gray-200">
                    <button onclick="cerrarModalVer()" 
                            class="px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i class="fas fa-times mr-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuración y variables globales
        let tourEditando = null;
        
        // Función para resaltar términos de búsqueda
        function resaltarTerminos(texto, termino) {
            if (!termino || !texto) return texto;
            
            const regex = new RegExp(`(${termino})`, 'gi');
            return texto.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
        }
        
        // Función para convertir hora de 24h a 12h con AM/PM
        function convertirHora12(hora24) {
            if (!hora24) return 'No especificada';
            
            const [horas, minutos] = hora24.split(':');
            let hora = parseInt(horas);
            const mins = minutos;
            let periodo = 'AM';
            
            if (hora === 0) {
                hora = 12;
            } else if (hora === 12) {
                periodo = 'PM';
            } else if (hora > 12) {
                hora = hora - 12;
                periodo = 'PM';
            }
            
            return `${hora}:${mins} ${periodo}`;
        }
        
        // Aplicar resaltado si hay búsqueda activa
        document.addEventListener('DOMContentLoaded', function() {
            const busqueda = '<?php echo addslashes($filtros['busqueda'] ?? ''); ?>';
            if (busqueda) {
                // Resaltar en títulos y descripciones
                const titulos = document.querySelectorAll('.tour-titulo');
                const descripciones = document.querySelectorAll('.tour-descripcion');
                
                titulos.forEach(function(elemento) {
                    elemento.innerHTML = resaltarTerminos(elemento.textContent, busqueda);
                });
                
                descripciones.forEach(function(elemento) {
                    elemento.innerHTML = resaltarTerminos(elemento.textContent, busqueda);
                });
            }
        });
        
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
                <div class="space-y-6">
                    <!-- Imagen del tour -->
                    <div class="text-center">
                        ${tour.imagen_principal 
                            ? `<img src="/Antares-Travel/${tour.imagen_principal}" alt="Tour" class="w-full h-64 object-cover rounded-lg mx-auto">`
                            : `<div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center mx-auto">
                                 <i class="fas fa-image text-gray-400 text-4xl"></i>
                               </div>`
                        }
                    </div>
                    
                    <!-- Título -->
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900">${tour.titulo}</h3>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-2">Descripción:</h4>
                        <p class="text-gray-700 leading-relaxed">${tour.descripcion}</p>
                    </div>
                    
                    <!-- Información del tour en grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Precio -->
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <h4 class="font-semibold text-gray-900 mb-1">Precio</h4>
                            <p class="text-2xl font-bold text-green-600">S/. ${parseFloat(tour.precio).toFixed(2)}</p>
                        </div>
                        
                        <!-- Duración -->
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <h4 class="font-semibold text-gray-900 mb-1">Duración</h4>
                            <p class="text-lg text-blue-600"><i class="fas fa-clock mr-2"></i>${tour.duracion}</p>
                        </div>
                    </div>
                    
                    <!-- Horarios -->
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-3">Horarios del Tour</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600 mb-1">Hora de Inicio</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-play-circle text-green-500 mr-2"></i>
                                    ${convertirHora12(tour.hora_salida)}
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600 mb-1">Hora de Fin</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-stop-circle text-red-500 mr-2"></i>
                                    ${convertirHora12(tour.hora_llegada)}
                                </p>
                            </div>
                        </div>
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
            // Resaltar términos de búsqueda
            const busqueda = '<?php echo addslashes($filtros['busqueda'] ?? ''); ?>';
            if (busqueda) {
                // Resaltar en títulos y descripciones
                const titulos = document.querySelectorAll('.tour-titulo');
                const descripciones = document.querySelectorAll('.tour-descripcion');
                
                titulos.forEach(function(elemento) {
                    elemento.innerHTML = resaltarTerminos(elemento.textContent, busqueda);
                });
                
                descripciones.forEach(function(elemento) {
                    elemento.innerHTML = resaltarTerminos(elemento.textContent, busqueda);
                });
            }
            
            // Inicializar filtro en tiempo real
            filtrarToursEnTiempoReal();
            
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
            // La responsividad del sidebar se maneja automáticamente por las clases de Tailwind
        });
        
        // Función de filtro en tiempo real (lado cliente)
        function filtrarToursEnTiempoReal() {
            const inputBusqueda = document.querySelector('input[name="busqueda"]');
            const tourCards = document.querySelectorAll('.tour-card');
            const tourRows = document.querySelectorAll('.tour-row');
            const contadorResultados = document.getElementById('contador-filtros');
            
            if (!inputBusqueda) return;
            
            // Evento de búsqueda en tiempo real
            inputBusqueda.addEventListener('input', function(e) {
                const termino = e.target.value.toLowerCase().trim();
                let contadorVisible = 0;
                
                // Filtrar cards (vista móvil)
                tourCards.forEach(card => {
                    const titulo = card.querySelector('.tour-titulo')?.textContent.toLowerCase() || '';
                    const descripcion = card.querySelector('.tour-descripcion')?.textContent.toLowerCase() || '';
                    
                    const coincide = titulo.includes(termino) || descripcion.includes(termino);
                    
                    if (coincide || termino === '') {
                        card.style.display = 'block';
                        contadorVisible++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Filtrar filas (vista desktop)
                tourRows.forEach(row => {
                    const titulo = row.querySelector('.tour-titulo')?.textContent.toLowerCase() || '';
                    const descripcion = row.querySelector('.tour-descripcion')?.textContent.toLowerCase() || '';
                    
                    const coincide = titulo.includes(termino) || descripcion.includes(termino);
                    
                    if (coincide || termino === '') {
                        row.style.display = 'table-row';
                        contadorVisible++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Actualizar contador si existe
                if (contadorResultados) {
                    if (termino === '') {
                        contadorResultados.textContent = 'Todos los tours mostrados';
                        contadorResultados.className = 'text-sm text-gray-600';
                    } else {
                        contadorResultados.textContent = `${contadorVisible} tour${contadorVisible !== 1 ? 's' : ''} encontrado${contadorVisible !== 1 ? 's' : ''}`;
                        contadorResultados.className = contadorVisible > 0 ? 'text-sm text-green-600' : 'text-sm text-red-600';
                    }
                }
                
                // Mostrar/ocultar mensaje de "no encontrados"
                mostrarMensajeNoEncontrados(contadorVisible === 0 && termino !== '');
            });
        }
        
        // Función para mostrar mensaje cuando no hay resultados
        function mostrarMensajeNoEncontrados(mostrar) {
            let mensajeElement = document.getElementById('mensaje-no-encontrados');
            
            if (mostrar && !mensajeElement) {
                // Crear mensaje si no existe
                mensajeElement = document.createElement('div');
                mensajeElement.id = 'mensaje-no-encontrados';
                mensajeElement.className = 'p-6 text-center text-gray-500 bg-gray-50 rounded-lg';
                mensajeElement.innerHTML = `
                    <i class="fas fa-search text-3xl text-gray-300 mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No se encontraron tours</h3>
                    <p class="text-sm text-gray-500">
                        No hay tours que coincidan con tu búsqueda. 
                        <button onclick="document.querySelector('input[name=\\'busqueda\\']').value=''; document.querySelector('input[name=\\'busqueda\\']').dispatchEvent(new Event('input'));" 
                                class="text-blue-600 hover:text-blue-800 underline ml-1">
                            Limpiar filtro
                        </button>
                    </p>
                `;
                
                // Insertar en ambas vistas
                const vistaMovil = document.querySelector('.block.lg\\:hidden .divide-y');
                const vistaDesktop = document.querySelector('.hidden.lg\\:block tbody');
                
                if (vistaMovil) {
                    vistaMovil.appendChild(mensajeElement);
                }
                if (vistaDesktop) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td colspan="6">${mensajeElement.outerHTML}</td>`;
                    vistaDesktop.appendChild(tr);
                }
            } else if (!mostrar && mensajeElement) {
                // Remover mensaje si existe
                mensajeElement.remove();
                // También remover de la tabla si existe
                const mensajeTabla = document.querySelector('tbody tr td #mensaje-no-encontrados');
                if (mensajeTabla) {
                    mensajeTabla.closest('tr').remove();
                }
            }
        }
        
        // Inicialización cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar que el sidebar funcione correctamente
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar && overlay) {
                console.log('Tours module: Sidebar y overlay encontrados correctamente');
                console.log('Tours module: Ancho de ventana:', window.innerWidth);
                
                // Asegurar estado inicial correcto
                if (window.innerWidth < 1024) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = 'auto';
                }
            } else {
                console.error('Tours module: No se encontraron elementos del sidebar');
            }
        });
    </script>
</body>
</html>
