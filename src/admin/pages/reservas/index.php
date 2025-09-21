<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Gestión de Reservas";

// Parámetros de filtrado (eliminamos paginación)
$filtro_estado = $_GET['estado'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

// Construcción de la consulta SQL con filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_estado)) {
    $where_conditions[] = "r.estado = ?";
    $params[] = $filtro_estado;
}

if (!empty($filtro_fecha)) {
    $where_conditions[] = "DATE(r.fecha_tour) = ?";
    $params[] = $filtro_fecha;
}

if (!empty($busqueda)) {
    $where_conditions[] = "(u.nombre LIKE ? OR u.email LIKE ? OR t.titulo LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obtener todas las reservas sin paginación
try {
    $connection = getConnection();
    
    // Obtener reservas ordenadas por fecha de reserva más reciente
    $sql = "SELECT 
                r.id_reserva,
                r.fecha_reserva,
                r.fecha_tour,
                r.monto_total,
                r.estado,
                r.observaciones,
                r.origen_reserva,
                t.titulo as tour_titulo,
                t.precio as tour_precio,
                u.nombre as usuario_nombre,
                u.email as usuario_email,
                u.telefono as usuario_telefono,
                (SELECT COUNT(*) FROM pasajeros p WHERE p.id_reserva = r.id_reserva) as num_pasajeros,
                COALESCE((SELECT SUM(pg.monto) FROM pagos pg WHERE pg.id_reserva = r.id_reserva AND pg.estado_pago = 'Pagado'), 0) as monto_pagado
            FROM reservas r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            INNER JOIN tours t ON r.id_tour = t.id_tour
            $where_clause
            ORDER BY r.fecha_reserva DESC";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll();
    
    // Obtener estadísticas rápidas
    $stats_sql = "SELECT 
                    estado,
                    COUNT(*) as cantidad
                  FROM reservas 
                  GROUP BY estado";
    $stats_stmt = $connection->query($stats_sql);
    $estadisticas = $stats_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error al obtener reservas: " . $e->getMessage());
    $reservas = [];
    $estadisticas = [];
}

// Función para obtener clase CSS del estado
function getEstadoClass($estado) {
    $classes = [
        'Pendiente' => 'bg-yellow-100 text-yellow-800',
        'Confirmada' => 'bg-green-100 text-green-800',
        'Cancelada' => 'bg-red-100 text-red-800',
        'Finalizada' => 'bg-blue-100 text-blue-800'
    ];
    return $classes[$estado] ?? 'bg-gray-100 text-gray-800';
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
    <style>
        .table-container {
            overflow-x: auto;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            max-height: 600px;
            overflow-y: auto;
        }
        
        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-container thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .status-filter:hover {
            transform: translateY(-1px);
        }
        
        .reservation-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Scroll personalizado */
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Estilos para scroll en móvil */
        .mobile-cards {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .mobile-cards::-webkit-scrollbar {
            width: 6px;
        }
        
        .mobile-cards::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .mobile-cards::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        /* Estilos para móviles */
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
                flex-direction: column;
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
                grid-template-columns: repeat(4, 1fr);
            }
            .filter-form {
                flex-direction: row;
                gap: 1rem;
            }
            .filter-actions {
                flex-direction: row;
                gap: 0.5rem;
            }
        }
        
        .mobile-card {
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease-in-out;
        }
        
        .mobile-card:hover {
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
        
        /* Animación de carga para el scroll */
        .table-container.loading {
            position: relative;
        }
        
        .table-container.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 20;
        }

        /* Responsividad */
        @media (max-width: 768px) {
            .mobile-grid {
                display: block;
            }
            
            .desktop-table {
                display: none;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-grid {
                display: none;
            }
            
            .desktop-table {
                display: block;
            }
            
            /* Configuración de scroll horizontal para desktop */
            .desktop-table {
                border: 2px solid #d1d5db;
                border-radius: 0.5rem;
                background: white;
            }
            
            .desktop-table .table-scroll-container {
                overflow-x: auto;
                overflow-y: auto;
                max-height: 600px; /* Altura máxima para activar scroll vertical */
                position: relative;
            }
            
            /* Scroll personalizado para ambos ejes */
            .desktop-table .table-scroll-container::-webkit-scrollbar {
                height: 8px;
                width: 8px;
            }
            
            .desktop-table .table-scroll-container::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 4px;
            }
            
            .desktop-table .table-scroll-container::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 4px;
            }
            
            .desktop-table .table-scroll-container::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
            
            .desktop-table table {
                width: 100%;
                table-layout: auto;
            }
            
            /* Header sticky */
            .desktop-table thead {
                position: sticky;
                top: 0;
                z-index: 10;
                background: #f9fafb;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            .desktop-table thead th {
                background: #f9fafb;
                position: sticky;
                top: 0;
            }
            
            /* Solo aplicar anchos mínimos para evitar distorsión */
            .desktop-table th,
            .desktop-table td {
                white-space: nowrap;
                padding: 12px 16px;
            }
            
            /* Mejorar separación visual en scroll */
            .desktop-table tbody tr {
                border-bottom: 1px solid #e5e7eb;
            }
            
            .desktop-table tbody tr:hover {
                background-color: #f8fafc;
            }
            
            /* Anchos mínimos solo donde sea crítico */
            .desktop-table th:nth-child(2),
            .desktop-table td:nth-child(2) { 
                min-width: 200px;
                max-width: 200px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .desktop-table th:nth-child(3),
            .desktop-table td:nth-child(3) { 
                min-width: 220px;
                max-width: 220px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }
        
        /* Estilos para el modal de eliminación */
        #modalEliminar .bg-yellow-50 {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        #modalEliminar button:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen"><br>
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-6 lg:mb-8">
                    <br><br><br>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-calendar-check text-blue-600 mr-3"></i>Gestión de Reservas
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600">Administra todas las reservas del sistema</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="crear.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Nueva Reserva
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="stats-grid grid gap-3 lg:gap-6 mb-6">
                    <?php 
                    $total_todas = array_sum(array_column($estadisticas, 'cantidad'));
                    $stats_display = [
                        'Todas' => $total_todas,
                        'Pendiente' => 0,
                        'Confirmada' => 0,
                        'Finalizada' => 0
                    ];
                    
                    foreach ($estadisticas as $stat) {
                        $stats_display[$stat['estado']] = $stat['cantidad'];
                    }
                    
                    $stat_colors = [
                        'Todas' => 'blue',
                        'Pendiente' => 'yellow',
                        'Confirmada' => 'green',
                        'Finalizada' => 'purple'
                    ];
                    
                    foreach ($stats_display as $estado => $cantidad):
                        $color = $stat_colors[$estado];
                        $is_active = ($filtro_estado === $estado) || ($estado === 'Todas' && empty($filtro_estado));
                    ?>
                        <a href="?estado=<?php echo $estado === 'Todas' ? '' : $estado; ?><?php echo $busqueda ? "&buscar=$busqueda" : ''; ?><?php echo $filtro_fecha ? "&fecha=$filtro_fecha" : ''; ?>" 
                           class="status-filter block bg-white rounded-lg shadow p-3 lg:p-4 transition-all duration-200 <?php echo $is_active ? "ring-2 ring-$color-500" : 'hover:shadow-md'; ?>">
                            <div class="flex items-center">
                                <div class="p-2 bg-<?php echo $color; ?>-100 rounded-lg">
                                    <i class="fas fa-calendar-check text-<?php echo $color; ?>-600 text-sm lg:text-lg"></i>
                                </div>
                                <div class="ml-2 lg:ml-3">
                                    <p class="text-xs text-gray-600"><?php echo $estado; ?></p>
                                    <p class="text-base lg:text-lg font-bold text-gray-900"><?php echo number_format($cantidad); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros y Búsqueda - Tiempo Real -->
                <div class="bg-white rounded-lg shadow mb-6 p-4">
                    <div class="flex flex-col lg:flex-row items-center gap-4">
                        <!-- Título de filtros -->
                        
                        
                        <!-- Filtros -->
                        <div class="flex flex-col lg:flex-row items-center gap-3 flex-1">
                            <!-- Búsqueda en tiempo real -->
                            <div class="flex items-center space-x-2 w-full lg:w-auto">
                                <label class="text-sm text-gray-600 whitespace-nowrap"></label>
                                <div class="relative flex-1 lg:w-64">
                                    <input type="text" id="filtro-busqueda" placeholder="Cliente, email o tour..."
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <button id="limpiar-busqueda" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 hidden">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Filtro por estado -->
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600 whitespace-nowrap"></label>
                                <select id="filtro-estado" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Confirmada">Confirmada</option>
                                    <option value="Cancelada">Cancelada</option>
                                    <option value="Finalizada">Finalizada</option>
                                </select>
                            </div>
                            
                            <!-- Filtro por fecha -->
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600 whitespace-nowrap">Fecha:</label>
                                <input type="date" id="filtro-fecha" 
                                       class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <!-- Filtro por tour -->
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600 whitespace-nowrap"></label>
                                <select id="filtro-tour" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos los tours</option>
                                    <?php 
                                    $tours_utilizados = [];
                                    foreach ($reservas as $reserva) {
                                        if (!in_array($reserva['tour_titulo'], $tours_utilizados)) {
                                            $tours_utilizados[] = $reserva['tour_titulo'];
                                            echo "<option value='" . htmlspecialchars($reserva['tour_titulo']) . "'>" . htmlspecialchars($reserva['tour_titulo']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Botón limpiar filtros y contador de resultados -->
                        <div class="flex items-center gap-3">
                            <span id="contador-resultados" class="text-sm text-gray-600">
                                <i class="fas fa-list mr-1"></i>
                                <span id="total-resultados"><?php echo count($reservas); ?></span> 
                            </span>
                            <button onclick="limpiarFiltros()" 
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm border border-gray-300 transition-colors">
                                <i class="fas fa-eraser mr-1"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Reservas Responsiva -->
                <?php if (empty($reservas)): ?>
                    <div class="bg-white rounded-lg shadow p-6 text-center">
                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                        <p class="text-lg font-medium text-gray-900 mb-2">No se encontraron reservas</p>
                        <p class="text-sm text-gray-500">Intenta ajustar los filtros de búsqueda</p>
                    </div>
                <?php else: ?>
                    <!-- Vista móvil - Cards con scroll -->
                    <div class="mobile-grid space-y-4 max-h-96 overflow-y-auto">
                        <?php foreach ($reservas as $reserva): ?>
                            <div class="mobile-card bg-white border-2 border-gray-300 rounded-lg p-4 hover:shadow-lg transition-all duration-300">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="font-semibold text-gray-900 text-sm">
                                        <?php echo htmlspecialchars($reserva['usuario_nombre']); ?>
                                    </h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getEstadoClass($reserva['estado']); ?>">
                                        <?php echo $reserva['estado']; ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-2 text-xs text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope w-4 text-blue-500 mr-2"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($reserva['usuario_email']); ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-map-marked-alt w-4 text-green-500 mr-2"></i>
                                        <span class="font-medium"><?php echo htmlspecialchars($reserva['tour_titulo']); ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar w-4 text-purple-500 mr-2"></i>
                                        <span class="font-medium text-xs text-gray-500"><?php echo formatDate($reserva['fecha_tour']); ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-users w-4 text-orange-500 mr-2"></i>
                                        <span><?php echo $reserva['num_pasajeros']; ?> pasajeros</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-dollar-sign w-4 text-green-600 mr-2"></i>
                                        <span><?php echo formatCurrency($reserva['monto_pagado']); ?> / <?php echo formatCurrency($reserva['monto_total']); ?></span>
                                    </div>
                                </div>

                                <!-- Acciones móvil -->
                                <div class="flex justify-between items-center pt-3 border-t border-gray-100 mt-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="ver.php?id=<?php echo $reserva['id_reserva']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 transition-colors flex items-center">
                                            <i class="fas fa-eye text-sm"></i>
                                            <span class="ml-1 text-xs">Ver</span>
                                        </a>
                                        <a href="editar.php?id=<?php echo $reserva['id_reserva']; ?>" 
                                           class="text-green-600 hover:text-green-800 transition-colors flex items-center">
                                            <i class="fas fa-edit text-sm"></i>
                                            <span class="ml-1 text-xs">Editar</span>
                                        </a>
                                        
                                        <!-- Separador visual para acciones de estado -->
                                        <?php if ($reserva['estado'] === 'Pendiente' || $reserva['estado'] === 'Confirmada'): ?>
                                            <span class="text-gray-300">|</span>
                                        <?php endif; ?>
                                        
                                        <!-- Botones de cambio de estado móvil -->
                                        <?php if ($reserva['estado'] === 'Pendiente'): ?>
                                            <button onclick="cambiarEstado(<?php echo $reserva['id_reserva']; ?>, 'Confirmada')" 
                                                    class="text-green-600 hover:text-green-800 transition-colors flex items-center bg-green-50 px-2 py-1 rounded">
                                                <i class="fas fa-check text-sm"></i>
                                                <span class="ml-1 text-xs font-medium">Confirmar</span>
                                            </button>
                                            <button onclick="cambiarEstado(<?php echo $reserva['id_reserva']; ?>, 'Cancelada')" 
                                                    class="text-red-600 hover:text-red-800 transition-colors flex items-center bg-red-50 px-2 py-1 rounded">
                                                <i class="fas fa-times text-sm"></i>
                                                <span class="ml-1 text-xs font-medium">Cancelar</span>
                                            </button>
                                        <?php elseif ($reserva['estado'] === 'Confirmada'): ?>
                                            <button onclick="cambiarEstado(<?php echo $reserva['id_reserva']; ?>, 'Finalizada')" 
                                                    class="text-blue-600 hover:text-blue-800 transition-colors flex items-center bg-blue-50 px-2 py-1 rounded">
                                                <i class="fas fa-flag-checkered text-sm"></i>
                                                <span class="ml-1 text-xs font-medium">Finalizar</span>
                                            </button>
                                            <button onclick="cambiarEstado(<?php echo $reserva['id_reserva']; ?>, 'Cancelada')" 
                                                    class="text-red-600 hover:text-red-800 transition-colors flex items-center bg-red-50 px-2 py-1 rounded">
                                                <i class="fas fa-times text-sm"></i>
                                                <span class="ml-1 text-xs font-medium">Cancelar</span>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($reserva['estado'] === 'Pendiente' || $reserva['estado'] === 'Confirmada'): ?>
                                            <span class="text-gray-300">|</span>
                                        <?php endif; ?>
                                        
                                        <button onclick="eliminarReserva(<?php echo $reserva['id_reserva']; ?>)" 
                                                class="text-red-600 hover:text-red-800 transition-colors flex items-center">
                                            <i class="fas fa-trash text-sm"></i>
                                            <span class="ml-1 text-xs">Eliminar</span>
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($reserva['fecha_reserva'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Vista desktop - Tabla -->
                    <div class="desktop-table">
                        <div class="table-scroll-container">
                            <table>
                                <thead class="sticky-header bg-gray-100 border-b-2 border-gray-300">
                                    <tr>
                                        <th class="px-4 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ID</th>
                                        <th class="px-4 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Cliente</th>
                                        <th class="px-4 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tour</th>
                                        <th class="px-4 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Fecha Tour</th>
                                        <th class="px-4 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Pasajeros</th>
                                        <th class="px-4 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Monto Pagado</th>
                                        <th class="px-4 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Estado</th>
                                        <th class="px-4 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($reservas as $reserva): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200 border-b border-gray-200">
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                            #<?php echo $reserva['id_reserva']; ?>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                                    <i class="fas fa-user text-white text-xs"></i>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-sm font-medium text-gray-900" title="<?php echo htmlspecialchars($reserva['usuario_nombre']); ?>">
                                                        <?php echo htmlspecialchars($reserva['usuario_nombre']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500" title="<?php echo htmlspecialchars($reserva['usuario_email']); ?>">
                                                        <?php echo htmlspecialchars($reserva['usuario_email']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900" title="<?php echo htmlspecialchars($reserva['tour_titulo']); ?>">
                                                    <?php echo htmlspecialchars($reserva['tour_titulo']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo formatCurrency($reserva['tour_precio']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full font-medium">
                                                <?php echo formatDate($reserva['fecha_tour'], 'd/m/Y'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <i class="fas fa-users mr-1"></i>
                                                <?php echo $reserva['num_pasajeros']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo formatCurrency($reserva['monto_pagado']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                de <?php echo formatCurrency($reserva['monto_total']); ?>
                                                <?php
                                                $porcentaje_pagado = $reserva['monto_total'] > 0 ? ($reserva['monto_pagado'] / $reserva['monto_total']) * 100 : 0;
                                                ?>
                                                <?php if ($porcentaje_pagado > 0 && $porcentaje_pagado < 100): ?>
                                                    <span class="text-xs text-orange-500">
                                                        (<?php echo round($porcentaje_pagado); ?>%)
                                                    </span>
                                                <?php elseif ($reserva['monto_pagado'] >= $reserva['monto_total'] && $reserva['monto_total'] > 0): ?>
                                                    <span class="text-xs text-green-500">
                                                        ✓ Completo
                                                    </span>
                                                <?php elseif ($reserva['monto_pagado'] == 0): ?>
                                                    <span class="text-xs text-red-500">
                                                        Sin pagos
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getEstadoClass($reserva['estado']); ?>">
                                                <?php echo $reserva['estado']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-center space-x-2">
                                                <a href="ver.php?id=<?php echo $reserva['id_reserva']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="editar.php?id=<?php echo $reserva['id_reserva']; ?>" 
                                                   class="text-green-600 hover:text-green-900" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <!-- Botones de cambio de estado -->
                                                <?php if ($reserva['estado'] === 'Pendiente'): ?>
                                                    <button onclick="cambiarEstado(<?php echo $reserva['id_reserva']; ?>, 'Confirmada')" 
                                                            class="text-green-600 hover:text-green-900" title="Confirmar reserva">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button onclick="cambiarEstado(<?php echo $reserva['id_reserva']; ?>, 'Cancelada')" 
                                                            class="text-red-600 hover:text-red-900" title="Cancelar reserva">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php elseif ($reserva['estado'] === 'Confirmada'): ?>
                                                    <button onclick="cambiarEstado(<?php echo $reserva['id_reserva']; ?>, 'Finalizada')" 
                                                            class="text-blue-600 hover:text-blue-900" title="Finalizar reserva">
                                                        <i class="fas fa-flag-checkered"></i>
                                                    </button>
                                                    <button onclick="cambiarEstado(<?php echo $reserva['id_reserva']; ?>, 'Cancelada')" 
                                                            class="text-red-600 hover:text-red-900" title="Cancelar reserva">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button onclick="eliminarReserva(<?php echo $reserva['id_reserva']; ?>)" 
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
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div id="modalEliminar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-2">⚠️ Eliminar Reserva</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 mb-2">
                        <strong>¿Estás seguro de que deseas eliminar esta reserva?</strong>
                    </p>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Se eliminarán automáticamente:</strong><br>
                                    • Todos los pagos asociados<br>
                                    • Información de pasajeros<br>
                                    • Historial de ofertas aplicadas<br>
                                    • Esta acción NO se puede deshacer
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de eliminación (opcional):</label>
                        <textarea name="motivo" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                                  placeholder="Ejemplo: Reserva duplicada, error de sistema, cancelación por cliente..."></textarea>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="btnConfirmarEliminar" class="px-6 py-3 bg-red-600 text-white text-base font-medium rounded-md w-auto hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 mr-3 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Confirmar Eliminación
                    </button>
                    <button onclick="cerrarModalEliminar()" class="px-6 py-3 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-auto hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let reservaAEliminar = null;
        let datosReserva = null;

        function eliminarReserva(id) {
            reservaAEliminar = id;
            
            // Buscar datos de la reserva en la tabla
            const filas = document.querySelectorAll('.desktop-table tbody tr');
            const cards = document.querySelectorAll('.mobile-grid > div');
            
            let clienteNombre = 'Desconocido';
            let tourTitulo = 'Desconocido';
            
            // Buscar en tabla desktop
            filas.forEach(fila => {
                const idCell = fila.querySelector('td:first-child');
                if (idCell && idCell.textContent.includes('#' + id)) {
                    clienteNombre = fila.querySelector('td:nth-child(2) .text-sm.font-medium')?.textContent || 'Desconocido';
                    tourTitulo = fila.querySelector('td:nth-child(3) .text-sm.font-medium')?.textContent || 'Desconocido';
                }
            });
            
            // Si no se encontró en desktop, buscar en cards móviles
            if (clienteNombre === 'Desconocido') {
                cards.forEach(card => {
                    // Buscar por ID en algún atributo o contenido (esto podría necesitar ajuste según tu implementación)
                    const botonEliminar = card.querySelector(`button[onclick*="${id}"]`);
                    if (botonEliminar) {
                        clienteNombre = card.querySelector('h3')?.textContent || 'Desconocido';
                        tourTitulo = card.querySelector('.font-medium')?.textContent || 'Desconocido';
                    }
                });
            }
            
            // Actualizar el título del modal con información de la reserva
            const tituloModal = document.querySelector('#modalEliminar h3');
            if (tituloModal) {
                tituloModal.innerHTML = `⚠️ Eliminar Reserva #${id}`;
            }
            
            // Actualizar la descripción con datos específicos
            const descripcionModal = document.querySelector('#modalEliminar .text-sm.text-gray-500');
            if (descripcionModal) {
                descripcionModal.innerHTML = `
                    <strong>¿Estás seguro de eliminar esta reserva?</strong><br>
                    <span class="text-blue-700">Cliente:</span> ${clienteNombre}<br>
                    <span class="text-green-700">Tour:</span> ${tourTitulo}
                `;
            }
            
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModalEliminar() {
            document.getElementById('modalEliminar').classList.add('hidden');
            reservaAEliminar = null;
            
            // Limpiar el textarea
            const textarea = document.querySelector('textarea[name="motivo"]');
            if (textarea) {
                textarea.value = '';
            }
            
            // Restaurar el botón a su estado original
            const btn = document.getElementById('btnConfirmarEliminar');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-trash mr-2"></i>Confirmar Eliminación';
                btn.disabled = false;
            }
        }

        document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
            if (reservaAEliminar) {
                const motivo = document.querySelector('textarea[name="motivo"]')?.value || '';
                const btn = this;
                
                // Mostrar indicador de carga
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Eliminando...';
                btn.disabled = true;
                
                // Crear formulario para enviar la eliminación
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar.php';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id_reserva';
                idInput.value = reservaAEliminar;
                
                const motivoInput = document.createElement('input');
                motivoInput.type = 'hidden';
                motivoInput.name = 'motivo';
                motivoInput.value = motivo;
                
                form.appendChild(idInput);
                form.appendChild(motivoInput);
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Filtros en tiempo real
        function aplicarFiltros() {
            const busquedaFiltro = document.getElementById('filtro-busqueda').value.toLowerCase();
            const estadoFiltro = document.getElementById('filtro-estado').value;
            const fechaFiltro = document.getElementById('filtro-fecha').value;
            const tourFiltro = document.getElementById('filtro-tour').value;
            
            // Debug: mostrar valores de filtros
            if (fechaFiltro) {
                console.log('Filtro por fecha:', fechaFiltro);
            }
            
            // Obtener todas las filas de la tabla (desktop)
            const filasTabla = document.querySelectorAll('.desktop-table tbody tr:not(.no-results)');
            // Obtener todas las cards (mobile)
            const cardsMobile = document.querySelectorAll('.mobile-grid > div');
            
            let filasVisibles = 0;
            let cardsVisibles = 0;
            
            // Filtrar filas de tabla (desktop)
            filasTabla.forEach((fila) => {
                let mostrar = true;
                
                // Obtener datos de la fila
                const cliente = fila.querySelector('td:nth-child(2) .text-sm.font-medium').textContent.toLowerCase();
                const email = fila.querySelector('td:nth-child(2) .text-sm.text-gray-500').textContent.toLowerCase();
                const tour = fila.querySelector('td:nth-child(3) .text-sm.font-medium').textContent.toLowerCase();
                const fechaTour = fila.querySelector('td:nth-child(4)').textContent.trim();
                const estado = fila.querySelector('td:nth-child(7) span').textContent.trim();
                
                // Debug: mostrar formato de fecha encontrado
                if (fechaFiltro) {
                    console.log('Fecha en tabla:', fechaTour);
                }
                
                // Aplicar filtros
                if (busquedaFiltro && !(cliente.includes(busquedaFiltro) || email.includes(busquedaFiltro) || tour.includes(busquedaFiltro))) {
                    mostrar = false;
                }
                
                if (estadoFiltro && estado !== estadoFiltro) {
                    mostrar = false;
                }
                
                if (fechaFiltro) {
                    // La fecha en la tabla tiene formato "dd/mm/yyyy hh:mm"
                    const fechaTexto = fechaTour.trim();
                    let fechaEncontrada = false;
                    
                    // Extraer solo la parte de fecha (sin hora) en formato dd/mm/yyyy
                    const fechaRegex = /(\d{1,2})\/(\d{1,2})\/(\d{4})/;
                    const match = fechaTexto.match(fechaRegex);
                    
                    if (match) {
                        const [, dia, mes, año] = match;
                        const fechaTabla = new Date(año, mes - 1, dia);
                        const fechaFiltroDate = new Date(fechaFiltro);
                        
                        fechaEncontrada = (
                            fechaTabla.getFullYear() === fechaFiltroDate.getFullYear() &&
                            fechaTabla.getMonth() === fechaFiltroDate.getMonth() &&
                            fechaTabla.getDate() === fechaFiltroDate.getDate()
                        );
                        
                        // Debug
                        console.log(`Comparando: ${dia}/${mes}/${año} con filtro ${fechaFiltro} = ${fechaEncontrada}`);
                    }
                    
                    if (!fechaEncontrada) {
                        mostrar = false;
                    }
                }
                
                if (tourFiltro && !tour.includes(tourFiltro.toLowerCase())) {
                    mostrar = false;
                }
                
                // Mostrar/ocultar fila
                if (mostrar) {
                    fila.style.display = '';
                    filasVisibles++;
                } else {
                    fila.style.display = 'none';
                }
            });
            
            // Filtrar cards móviles
            cardsMobile.forEach((card) => {
                let mostrar = true;
                
                // Obtener datos de la card
                const cliente = card.querySelector('h3').textContent.toLowerCase();
                const email = card.querySelector('.text-xs.text-gray-700').textContent.toLowerCase();
                const tour = card.querySelector('.font-medium.text-gray-900.text-sm.mb-1').textContent.toLowerCase();
                const fechaTour = card.querySelector('.text-xs.text-gray-500').textContent;
                const estadoElement = card.querySelector('.status-badge');
                const estado = estadoElement ? estadoElement.textContent.trim() : '';
                
                // Aplicar filtros
                if (busquedaFiltro && !(cliente.includes(busquedaFiltro) || email.includes(busquedaFiltro) || tour.includes(busquedaFiltro))) {
                    mostrar = false;
                }
                
                if (estadoFiltro && estado !== estadoFiltro) {
                    mostrar = false;
                }
                
                if (fechaFiltro) {
                    // La fecha en la tabla móvil también tiene formato "dd/mm/yyyy hh:mm" o "Fecha: dd/mm/yyyy hh:mm"
                    const fechaTexto = fechaTour.trim();
                    let fechaEncontrada = false;
                    
                    // Extraer solo la parte de fecha (sin hora) en formato dd/mm/yyyy
                    const fechaRegex = /(\d{1,2})\/(\d{1,2})\/(\d{4})/;
                    const match = fechaTexto.match(fechaRegex);
                    
                    if (match) {
                        const [, dia, mes, año] = match;
                        const fechaCard = new Date(año, mes - 1, dia);
                        const fechaFiltroDate = new Date(fechaFiltro);
                        
                        fechaEncontrada = (
                            fechaCard.getFullYear() === fechaFiltroDate.getFullYear() &&
                            fechaCard.getMonth() === fechaFiltroDate.getMonth() &&
                            fechaCard.getDate() === fechaFiltroDate.getDate()
                        );
                        
                        // Debug móvil
                        console.log(`Móvil - Comparando: ${dia}/${mes}/${año} con filtro ${fechaFiltro} = ${fechaEncontrada}`);
                    } else {
                        console.log('Móvil - No se pudo extraer fecha de:', fechaTexto);
                    }
                    
                    if (!fechaEncontrada) {
                        mostrar = false;
                    }
                }
                
                if (tourFiltro && !tour.includes(tourFiltro.toLowerCase())) {
                    mostrar = false;
                }
                
                // Mostrar/ocultar card
                if (mostrar) {
                    card.style.display = '';
                    cardsVisibles++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Actualizar contador de resultados
            const totalResultados = Math.max(filasVisibles, cardsVisibles);
            document.getElementById('total-resultados').textContent = totalResultados;
            
            // Mostrar/ocultar botón limpiar búsqueda
            const btnLimpiarBusqueda = document.getElementById('limpiar-busqueda');
            if (busquedaFiltro) {
                btnLimpiarBusqueda.classList.remove('hidden');
            } else {
                btnLimpiarBusqueda.classList.add('hidden');
            }
            
            // Manejar mensaje "no se encontraron resultados"
            manejarMensajeVacio(totalResultados);
        }
        
        function limpiarFiltros() {
            document.getElementById('filtro-busqueda').value = '';
            document.getElementById('filtro-estado').value = '';
            document.getElementById('filtro-fecha').value = '';
            document.getElementById('filtro-tour').value = '';
            aplicarFiltros();
        }
        
        function manejarMensajeVacio(totalResultados) {
            const filaVacia = document.querySelector('.desktop-table tbody .no-results');
            const cardVacia = document.querySelector('.mobile-grid .no-results');
            
            if (totalResultados === 0) {
                // Crear mensaje para tabla si no existe
                if (!filaVacia) {
                    const tbody = document.querySelector('.desktop-table tbody');
                    const tr = document.createElement('tr');
                    tr.className = 'no-results';
                    tr.innerHTML = `
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium">No se encontraron reservas</p>
                            <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                        </td>
                    `;
                    tbody.appendChild(tr);
                }
                
                // Crear mensaje para móvil si no existe
                if (!cardVacia) {
                    const container = document.querySelector('.mobile-grid');
                    const div = document.createElement('div');
                    div.className = 'no-results bg-white rounded-lg shadow p-6 text-center';
                    div.innerHTML = `
                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                        <p class="text-lg font-medium text-gray-900 mb-2">No se encontraron reservas</p>
                        <p class="text-sm text-gray-500">Intenta ajustar los filtros de búsqueda</p>
                    `;
                    container.appendChild(div);
                }
            } else {
                // Remover mensajes de "no encontrado" si existen
                if (filaVacia) filaVacia.remove();
                if (cardVacia) cardVacia.remove();
            }
        }
        
        // Event listeners para los filtros
        document.addEventListener('DOMContentLoaded', function() {
            const filtroBusqueda = document.getElementById('filtro-busqueda');
            const filtroEstado = document.getElementById('filtro-estado');
            const filtroFecha = document.getElementById('filtro-fecha');
            const filtroTour = document.getElementById('filtro-tour');
            const btnLimpiarBusqueda = document.getElementById('limpiar-busqueda');
            
            // Búsqueda en tiempo real con debounce
            let timeoutBusqueda;
            if (filtroBusqueda) {
                filtroBusqueda.addEventListener('input', function() {
                    clearTimeout(timeoutBusqueda);
                    timeoutBusqueda = setTimeout(aplicarFiltros, 300);
                });
            }
            
            if (filtroEstado) filtroEstado.addEventListener('change', aplicarFiltros);
            if (filtroFecha) filtroFecha.addEventListener('change', aplicarFiltros);
            if (filtroTour) filtroTour.addEventListener('change', aplicarFiltros);
            
            // Botón limpiar búsqueda
            if (btnLimpiarBusqueda) {
                btnLimpiarBusqueda.addEventListener('click', function() {
                    filtroBusqueda.value = '';
                    aplicarFiltros();
                    filtroBusqueda.focus();
                });
            }
        });

        // Función para cambiar el estado de una reserva
        function cambiarEstado(idReserva, nuevoEstado) {
            // Confirmación del usuario
            const estadoTextos = {
                'Confirmada': 'confirmar',
                'Cancelada': 'cancelar',
                'Finalizada': 'finalizar'
            };
            
            const accion = estadoTextos[nuevoEstado] || 'cambiar';
            const mensaje = `¿Estás seguro de que deseas ${accion} esta reserva?`;
            
            if (!confirm(mensaje)) {
                return;
            }
            
            // Mostrar indicador de carga
            const botones = document.querySelectorAll(`button[onclick*="${idReserva}"]`);
            botones.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.5';
            });
            
            // Realizar petición AJAX
            fetch('cambiar_estado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_reserva: idReserva,
                    nuevo_estado: nuevoEstado,
                    observaciones: `Estado cambiado a ${nuevoEstado} desde el panel administrativo`
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    alert(data.message || `Reserva ${accion}da exitosamente`);
                    
                    // Recargar la página para mostrar los cambios
                    window.location.reload();
                } else {
                    // Mostrar mensaje de error
                    alert('Error: ' + (data.message || 'No se pudo cambiar el estado'));
                    
                    // Rehabilitar botones
                    botones.forEach(btn => {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión. Por favor intenta nuevamente.');
                
                // Rehabilitar botones
                botones.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
            });
        }
    </script>
</body>
</html>
