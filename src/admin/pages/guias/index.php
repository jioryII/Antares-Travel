<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Gestión de Guías";

try {
    $connection = getConnection();
    
    // Obtener todos los guías sin paginación (para filtros en tiempo real)
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
                  ORDER BY g.nombre ASC";
    
    $guias_stmt = $connection->prepare($guias_sql);
    $guias_stmt->execute();
    $guias = $guias_stmt->fetchAll();
    
    // Procesar las URLs de fotos para corregir las rutas
    foreach ($guias as &$guia) {
        if (!empty($guia['foto_url'])) {
            // Si es una URL completa (HTTP/HTTPS), dejarla como está
            if (preg_match('/^https?:\/\//i', $guia['foto_url'])) {
                // URL externa - no hacer nada
                continue;
            }
            // Si es una ruta local, ajustar la ruta relativa
            elseif (!preg_match('/^https?:\/\//i', $guia['foto_url'])) {
                // Ruta local: convertir a ruta relativa desde la ubicación actual
                $guia['foto_url'] = '../../../../' . ltrim($guia['foto_url'], '/');
            }
        }
    }
    unset($guia); // Romper la referencia
    
    $total_guias = count($guias);
    
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
    
    $stats = $connection->query($stats_sql)->fetch();
    
} catch (Exception $e) {
    $error = "Error al cargar guías: " . $e->getMessage();
    $guias = [];
    $total_registros = 0;
    $total_paginas = 0;
    $stats = [];
}

// Manejar mensajes de éxito/error
$mensaje_success = $_GET['success'] ?? null;
$mensaje_error = $_GET['error'] ?? null;

function getEstadoClass($estado) {
    return $estado === 'Libre' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
}

function getEstadoIcon($estado) {
    return $estado === 'Libre' ? 'fas fa-check-circle' : 'fas fa-clock';
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
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .desktop-table {
                display: none;
            }
            .mobile-cards {
                display: block;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-form {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .filter-actions {
                flex-direction: column;
                width: 100%;
            }
            .filter-actions button,
            .filter-actions a {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (min-width: 769px) {
            .desktop-table {
                display: block;
            }
            .mobile-cards {
                display: none;
            }
            .stats-grid {
                grid-template-columns: repeat(5, 1fr);
            }
            .filter-form {
                grid-template-columns: repeat(5, 1fr);
                gap: 1rem;
            }
            .filter-actions {
                flex-direction: row;
                gap: 0.5rem;
            }
        }
        
        /* Configuración de scroll para la tabla desktop */
        .desktop-table {
            border: 2px solid #d1d5db;
            border-radius: 0.5rem;
            background: white;
            max-height: 600px;
            overflow: hidden;
            position: relative;
        }
        
        .desktop-table .table-container {
            max-height: 600px;
            overflow-y: auto;
            overflow-x: auto;
        }
        
        .desktop-table table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        /* Header sticky con mejor configuración */
        .desktop-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #f9fafb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }
        
        .desktop-table thead th {
            background: #f9fafb;
            position: sticky;
            top: 0;
            z-index: 15;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .desktop-table tbody {
            background: white;
        }
        
        /* Scroll personalizado mejorado */
        .desktop-table .table-container::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        
        .desktop-table .table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .desktop-table .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .desktop-table .table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
            
        /* Mejorar separación visual en scroll */
        .desktop-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s ease;
        }
        
        .desktop-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Optimizar contenido de celdas para scroll */
        .desktop-table td {
            padding: 12px 16px;
            vertical-align: middle;
        }
        
        .desktop-table th {
            padding: 12px 16px;
            white-space: nowrap;
            font-weight: 600;
        }
        
        /* Anchos específicos para columnas críticas */
        .desktop-table th:nth-child(1),
        .desktop-table td:nth-child(1) { 
            min-width: 250px;
            max-width: 300px;
        }
        
        .desktop-table th:nth-child(2),
        .desktop-table td:nth-child(2) { 
            min-width: 160px;
            max-width: 180px;
            text-align: center;
        }
        
        .desktop-table th:nth-child(3),
        .desktop-table td:nth-child(3) { 
            min-width: 120px;
            max-width: 140px;
            text-align: center;
        }
        
        .desktop-table th:nth-child(4),
        .desktop-table td:nth-child(4) { 
            min-width: 150px;
            max-width: 180px;
            text-align: center;
        }
        
        .desktop-table th:nth-child(5),
        .desktop-table td:nth-child(5) { 
            min-width: 120px;
            max-width: 140px;
            text-align: center;
        }
        
        .desktop-table th:nth-child(6),
        .desktop-table td:nth-child(6) { 
            min-width: 180px;
            max-width: 200px;
            text-align: center;
        }
        
        /* Estilos para las tarjetas móviles */
        .guia-card {
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease-in-out;
        }
        
        .guia-card:hover {
            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }
        
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
    </style>
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
                    <br><br><br>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-user-tie text-blue-600 mr-3"></i>Gestión de Guías
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600">Administra los guías turísticos de la plataforma</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="crear.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Nuevo Guía
                            </a>
                            <button onclick="exportarGuias()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notificaciones de éxito/error -->
                <?php if ($mensaje_success): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700 font-medium">
                                    <?php echo htmlspecialchars($mensaje_success); ?>
                                </p>
                            </div>
                            <div class="ml-auto">
                                <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                                        class="text-green-400 hover:text-green-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($mensaje_error): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700 font-medium">
                                    <?php echo htmlspecialchars($mensaje_error); ?>
                                </p>
                            </div>
                            <div class="ml-auto">
                                <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                                        class="text-red-400 hover:text-red-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

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

                <!-- Estadísticas Rápidas -->
                <div class="stats-grid grid gap-3 lg:gap-6 mb-6">
                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-blue-100">
                                <i class="fas fa-user-tie text-blue-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Total Guías</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_guias'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-green-100">
                                <i class="fas fa-check-circle text-green-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Libres</p>
                                <p class="text-lg lg:text-2xl font-bold text-green-600"><?php echo number_format($stats['guias_libres'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-yellow-100">
                                <i class="fas fa-clock text-yellow-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Ocupados</p>
                                <p class="text-lg lg:text-2xl font-bold text-yellow-600"><?php echo number_format($stats['guias_ocupados'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-purple-100">
                                <i class="fas fa-star text-purple-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Rating Promedio</p>
                                <p class="text-lg lg:text-2xl font-bold text-purple-600">
                                    <?php echo ($stats['calificacion_general'] ?? 0) > 0 ? number_format($stats['calificacion_general'], 1) : 'N/A'; ?>
                                    <?php if (($stats['calificacion_general'] ?? 0) > 0): ?>
                                        <span class="text-sm text-gray-500">/5</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-indigo-100">
                                <i class="fas fa-map-marked-alt text-indigo-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Tours Sistema</p>
                                <p class="text-lg lg:text-2xl font-bold text-indigo-600"><?php echo number_format($stats['total_tours_sistema'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 mb-6">
                    <div class="filter-form grid gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por nombre</label>
                            <div class="relative">
                                <input type="text" id="filtro-nombre" 
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full text-sm pr-8"
                                       placeholder="Nombre del guía">
                                <button type="button" id="limpiar-nombre" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por email</label>
                            <div class="relative">
                                <input type="email" id="filtro-email" 
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full text-sm pr-8"
                                       placeholder="Email del guía">
                                <button type="button" id="limpiar-email" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select id="filtro-estado" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full text-sm">
                                <option value="">Todos los estados</option>
                                <option value="Libre">Libre</option>
                                <option value="Ocupado">Ocupado</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Calificación</label>
                            <select id="filtro-calificacion" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full text-sm">
                                <option value="">Todas las calificaciones</option>
                                <option value="5">5 estrellas</option>
                                <option value="4">4+ estrellas</option>
                                <option value="3">3+ estrellas</option>
                                <option value="sin-calificacion">Sin calificaciones</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions flex items-center gap-3">
                            <span id="contador-resultados" class="text-sm text-gray-600">
                                <i class="fas fa-list mr-1"></i>
                                <span id="total-resultados"><?php echo count($guias); ?></span> guías
                            </span>
                            <button type="button" onclick="limpiarFiltros()" 
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm border border-gray-300 transition-colors">
                                <i class="fas fa-eraser mr-1"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de guías - Vista Desktop con Scroll -->
                <div class="desktop-table bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="table-container">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guía</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calificación</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($guias)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-user-tie text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-lg font-medium">No se encontraron guías</p>
                                            <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($guias as $guia): ?>
                                        <tr class="hover:bg-gray-50">
                                            <!-- Columna Principal: Guía -->
                                            <td class="px-6 py-4">
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
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Contacto -->
                                            <td class="px-6 py-4">
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
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getEstadoClass($guia['estado']); ?>">
                                                    <i class="<?php echo getEstadoIcon($guia['estado']); ?> mr-1"></i>
                                                    <?php echo $guia['estado']; ?>
                                                </span>
                                                <?php if ($guia['dias_ocupados'] > 0): ?>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        <?php echo $guia['dias_ocupados']; ?> días ocupados
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Tours -->
                                            <td class="px-6 py-4">
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
                                            
                                            <!-- Calificación -->
                                            <td class="px-6 py-4">
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
                                            
                                            <!-- Acciones -->
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-2">
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
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vista Mobile - Tarjetas -->
                <div class="mobile-cards space-y-4">
                    <?php if (empty($guias)): ?>
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <i class="fas fa-user-tie text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-900 mb-2">No se encontraron guías</p>
                            <p class="text-sm text-gray-500">Intenta ajustar los filtros de búsqueda</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($guias as $guia): ?>
                            <div class="guia-card bg-white p-4 border border-gray-200">
                                <!-- Header de la tarjeta -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <?php if ($guia['foto_url']): ?>
                                                <img class="h-12 w-12 rounded-full" src="<?php echo htmlspecialchars($guia['foto_url']); ?>" alt="">
                                            <?php else: ?>
                                                <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center">
                                                    <span class="text-white font-medium text-sm">
                                                        <?php echo strtoupper(substr($guia['nombre'], 0, 1) . substr($guia['apellido'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="font-semibold text-gray-900 text-sm">
                                                <?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>
                                            </h3>
                                            <p class="text-xs text-gray-500">ID: #<?php echo $guia['id_guia']; ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($guia['email']); ?></p>
                                            <?php if ($guia['telefono']): ?>
                                                <p class="text-xs text-gray-400">
                                                    <i class="fas fa-phone mr-1"></i>
                                                    <?php echo htmlspecialchars($guia['telefono']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="status-badge <?php echo getEstadoClass($guia['estado']); ?>">
                                        <i class="<?php echo getEstadoIcon($guia['estado']); ?> mr-1"></i>
                                        <?php echo $guia['estado']; ?>
                                    </span>
                                </div>

                                <!-- Tours y calificación -->
                                <div class="grid grid-cols-2 gap-3 mb-4 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-blue-600"><?php echo $guia['total_tours']; ?></div>
                                        <div class="text-xs text-gray-500">Tours</div>
                                    </div>
                                    <div class="text-center">
                                        <?php if ($guia['total_calificaciones'] > 0): ?>
                                            <div class="text-lg font-bold text-yellow-600"><?php echo number_format($guia['calificacion_promedio'], 1); ?></div>
                                            <div class="text-xs text-gray-500">Rating (<?php echo $guia['total_calificaciones']; ?>)</div>
                                        <?php else: ?>
                                            <div class="text-lg font-bold text-gray-400">N/A</div>
                                            <div class="text-xs text-gray-500">Sin rating</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Footer con acciones -->
                                <div class="flex justify-center space-x-4 pt-3 border-t border-gray-100">
                                    <a href="ver.php?id=<?php echo $guia['id_guia']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 transition-colors">
                                        <i class="fas fa-eye text-sm"></i>
                                        <span class="ml-1 text-xs">Ver</span>
                                    </a>
                                    <a href="editar.php?id=<?php echo $guia['id_guia']; ?>" 
                                       class="text-green-600 hover:text-green-800 transition-colors">
                                        <i class="fas fa-edit text-sm"></i>
                                        <span class="ml-1 text-xs">Editar</span>
                                    </a>
                                    <button onclick="eliminarGuia(<?php echo $guia['id_guia']; ?>, '<?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>')" 
                                            class="text-red-600 hover:text-red-800 transition-colors">
                                        <i class="fas fa-trash text-sm"></i>
                                        <span class="ml-1 text-xs">Eliminar</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Eliminar Guía</h3>
                <div class="mt-2 px-4 py-3">
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        ¿Estás seguro de que deseas eliminar al guía <span id="nombreGuia" class="font-medium"></span>? 
                        Esta acción no se puede deshacer.
                    </p>
                    
                    <!-- Advertencia de datos relacionados -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-0.5"></i>
                            <div class="text-xs text-yellow-700">
                                <strong>Advertencia:</strong> Se eliminarán todos los datos del guía, pero los tours históricos se mantendrán de forma anónima para conservar registros.
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-left">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-clipboard-list mr-1"></i>Motivo de eliminación (opcional):
                        </label>
                        <textarea id="motivoEliminacion" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm resize-none"
                                  placeholder="Ej: Solicitud del guía, baja del servicio, violación de términos..."></textarea>
                        <div class="text-xs text-gray-400 mt-1">
                            Este motivo quedará registrado en el historial administrativo
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-3 px-4 py-4">
                    <button id="btnConfirmarEliminar" 
                            class="px-6 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Eliminar Guía
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
        let guiaAEliminar = null;

        function eliminarGuia(id, nombre) {
            guiaAEliminar = id;
            document.getElementById('nombreGuia').textContent = nombre;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalEliminar').classList.add('hidden');
            document.getElementById('motivoEliminacion').value = '';
            guiaAEliminar = null;
        }

        // Confirmar eliminación
        document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
            if (guiaAEliminar) {
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
                inputId.name = 'id_guia';
                inputId.value = guiaAEliminar;
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

        // ============================================
        // FILTROS EN TIEMPO REAL
        // ============================================
        
        let filtroTimeout;
        
        // Función para filtrar guías
        function filtrarGuias() {
            const filtroNombre = document.getElementById('filtro-nombre')?.value.toLowerCase() || '';
            const filtroEmail = document.getElementById('filtro-email')?.value.toLowerCase() || '';
            const filtroEstado = document.getElementById('filtro-estado')?.value || '';
            const filtroCalificacion = document.getElementById('filtro-calificacion')?.value || '';
            
            // Filtrar tabla desktop
            const filasTabla = document.querySelectorAll('.desktop-table tbody tr');
            let visiblesTabla = 0;
            
            filasTabla.forEach(function(fila) {
                // Verificar si es la fila de "no hay resultados"
                if (fila.querySelector('td[colspan]')) {
                    fila.style.display = 'none';
                    return;
                }
                
                let visible = true;
                
                // Filtro por nombre (incluyendo email en el campo de guía)
                if (filtroNombre) {
                    const celdaGuia = fila.cells[0];
                    const textoGuia = celdaGuia ? celdaGuia.textContent.toLowerCase() : '';
                    if (!textoGuia.includes(filtroNombre)) {
                        visible = false;
                    }
                }
                
                // Filtro por email específico
                if (filtroEmail) {
                    const celdaContacto = fila.cells[1];
                    const textoEmail = celdaContacto ? celdaContacto.textContent.toLowerCase() : '';
                    if (!textoEmail.includes(filtroEmail)) {
                        visible = false;
                    }
                }
                
                // Filtro por estado
                if (filtroEstado) {
                    const celdaEstado = fila.cells[2];
                    const textoEstado = celdaEstado ? celdaEstado.textContent.toLowerCase() : '';
                    if (!textoEstado.includes(filtroEstado.toLowerCase())) {
                        visible = false;
                    }
                }
                
                // Filtro por calificación
                if (filtroCalificacion) {
                    const celdaCalificacion = fila.cells[4];
                    if (celdaCalificacion) {
                        const textoCalificacion = celdaCalificacion.textContent.toLowerCase();
                        
                        if (filtroCalificacion === 'sin-calificacion') {
                            if (!textoCalificacion.includes('sin calificaciones')) {
                                visible = false;
                            }
                        } else {
                            const estrellas = celdaCalificacion.querySelectorAll('.text-yellow-400');
                            const numEstrellas = estrellas.length;
                            const minEstrellas = parseInt(filtroCalificacion);
                            
                            if (numEstrellas < minEstrellas) {
                                visible = false;
                            }
                        }
                    }
                }
                
                fila.style.display = visible ? '' : 'none';
                if (visible) visiblesTabla++;
            });
            
            // Filtrar tarjetas móviles
            const tarjetas = document.querySelectorAll('.guia-card');
            let visiblesTarjetas = 0;
            
            tarjetas.forEach(function(tarjeta) {
                let visible = true;
                
                // Filtro por nombre
                if (filtroNombre) {
                    const textoTarjeta = tarjeta.textContent.toLowerCase();
                    if (!textoTarjeta.includes(filtroNombre)) {
                        visible = false;
                    }
                }
                
                // Filtro por email
                if (filtroEmail) {
                    const textoTarjeta = tarjeta.textContent.toLowerCase();
                    if (!textoTarjeta.includes(filtroEmail)) {
                        visible = false;
                    }
                }
                
                // Filtro por estado
                if (filtroEstado) {
                    const badgeEstado = tarjeta.querySelector('.status-badge');
                    const textoEstado = badgeEstado ? badgeEstado.textContent.toLowerCase() : '';
                    if (!textoEstado.includes(filtroEstado.toLowerCase())) {
                        visible = false;
                    }
                }
                
                // Filtro por calificación
                if (filtroCalificacion) {
                    const textoTarjeta = tarjeta.textContent.toLowerCase();
                    
                    if (filtroCalificacion === 'sin-calificacion') {
                        if (!textoTarjeta.includes('sin rating')) {
                            visible = false;
                        }
                    } else {
                        const ratingElement = tarjeta.querySelector('.text-yellow-600');
                        if (ratingElement && ratingElement.textContent !== 'N/A') {
                            const rating = parseFloat(ratingElement.textContent);
                            const minRating = parseInt(filtroCalificacion);
                            if (rating < minRating) {
                                visible = false;
                            }
                        } else if (filtroCalificacion !== 'sin-calificacion') {
                            visible = false;
                        }
                    }
                }
                
                tarjeta.style.display = visible ? '' : 'none';
                if (visible) visiblesTarjetas++;
            });
            
            // Actualizar contador
            document.getElementById('total-resultados').textContent = Math.max(visiblesTabla, visiblesTarjetas);
            
            // Mostrar mensaje si no hay resultados
            mostrarMensajeNoResultados(visiblesTabla, visiblesTarjetas);
        }
        
        // Función para mostrar mensaje de no resultados
        function mostrarMensajeNoResultados(visiblesTabla, visiblesTarjetas) {
            // Para tabla desktop
            const tablaBody = document.querySelector('.desktop-table tbody');
            if (tablaBody) {
                let filaSinResultados = tablaBody.querySelector('tr[data-no-results]');
                
                if (visiblesTabla === 0) {
                    if (!filaSinResultados) {
                        filaSinResultados = document.createElement('tr');
                        filaSinResultados.setAttribute('data-no-results', 'true');
                        filaSinResultados.innerHTML = `
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium">No se encontraron guías</p>
                                <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                            </td>
                        `;
                        tablaBody.appendChild(filaSinResultados);
                    }
                    filaSinResultados.style.display = '';
                } else if (filaSinResultados) {
                    filaSinResultados.style.display = 'none';
                }
            }
            
            // Para tarjetas móviles
            const contenedorTarjetas = document.querySelector('.mobile-cards');
            if (contenedorTarjetas) {
                let tarjetaSinResultados = contenedorTarjetas.querySelector('[data-no-results]');
                
                if (visiblesTarjetas === 0) {
                    if (!tarjetaSinResultados) {
                        tarjetaSinResultados = document.createElement('div');
                        tarjetaSinResultados.setAttribute('data-no-results', 'true');
                        tarjetaSinResultados.className = 'bg-white rounded-lg shadow p-6 text-center';
                        tarjetaSinResultados.innerHTML = `
                            <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-900 mb-2">No se encontraron guías</p>
                            <p class="text-sm text-gray-500">Intenta ajustar los filtros de búsqueda</p>
                        `;
                        contenedorTarjetas.appendChild(tarjetaSinResultados);
                    }
                    tarjetaSinResultados.style.display = '';
                } else if (tarjetaSinResultados) {
                    tarjetaSinResultados.style.display = 'none';
                }
            }
        }
        
        // Función con debounce para optimizar rendimiento
        function filtrarConDebounce() {
            clearTimeout(filtroTimeout);
            filtroTimeout = setTimeout(filtrarGuias, 300);
        }
        
        // Event listeners para los campos de filtro
        document.addEventListener('DOMContentLoaded', function() {
            const filtroNombre = document.getElementById('filtro-nombre');
            const filtroEmail = document.getElementById('filtro-email');
            const filtroEstado = document.getElementById('filtro-estado');
            const filtroCalificacion = document.getElementById('filtro-calificacion');
            
            if (filtroNombre) {
                filtroNombre.addEventListener('input', filtrarConDebounce);
                
                // Botón limpiar nombre
                const limpiarNombre = document.getElementById('limpiar-nombre');
                filtroNombre.addEventListener('input', function() {
                    limpiarNombre.style.display = this.value ? 'block' : 'none';
                });
                limpiarNombre.addEventListener('click', function() {
                    filtroNombre.value = '';
                    this.style.display = 'none';
                    filtrarGuias();
                });
            }
            
            if (filtroEmail) {
                filtroEmail.addEventListener('input', filtrarConDebounce);
                
                // Botón limpiar email
                const limpiarEmail = document.getElementById('limpiar-email');
                filtroEmail.addEventListener('input', function() {
                    limpiarEmail.style.display = this.value ? 'block' : 'none';
                });
                limpiarEmail.addEventListener('click', function() {
                    filtroEmail.value = '';
                    this.style.display = 'none';
                    filtrarGuias();
                });
            }
            
            if (filtroEstado) {
                filtroEstado.addEventListener('change', filtrarGuias);
            }
            
            if (filtroCalificacion) {
                filtroCalificacion.addEventListener('change', filtrarGuias);
            }
        });
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            document.getElementById('filtro-nombre').value = '';
            document.getElementById('filtro-email').value = '';
            document.getElementById('filtro-estado').value = '';
            document.getElementById('filtro-calificacion').value = '';
            
            // Ocultar botones de limpiar
            document.getElementById('limpiar-nombre').style.display = 'none';
            document.getElementById('limpiar-email').style.display = 'none';
            
            filtrarGuias();
        }
    </script>
</body>
</html>
