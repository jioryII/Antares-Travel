<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Gestión de Reservas";

// Parámetros de filtrado y paginación
$filtro_estado = $_GET['estado'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$busqueda = $_GET['buscar'] ?? '';
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

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

// Obtener total de registros para paginación
try {
    $connection = getConnection();
    
    $count_sql = "SELECT COUNT(*) as total 
                  FROM reservas r
                  INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
                  INNER JOIN tours t ON r.id_tour = t.id_tour
                  $where_clause";
    
    $count_stmt = $connection->prepare($count_sql);
    $count_stmt->execute($params);
    $total_registros = $count_stmt->fetch()['total'];
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    // Obtener reservas con paginación
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
                (SELECT COUNT(*) FROM pasajeros p WHERE p.id_reserva = r.id_reserva) as num_pasajeros
            FROM reservas r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            INNER JOIN tours t ON r.id_tour = t.id_tour
            $where_clause
            ORDER BY r.fecha_reserva DESC
            LIMIT $registros_por_pagina OFFSET $offset";
    
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
    $total_registros = 0;
    $total_paginas = 0;
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
        }
        .status-filter:hover {
            transform: translateY(-1px);
        }
        .reservation-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
                            <button onclick="exportarReservas()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6">
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
                           class="status-filter block bg-white rounded-lg shadow p-4 transition-all duration-200 <?php echo $is_active ? "ring-2 ring-$color-500" : 'hover:shadow-md'; ?>">
                            <div class="flex items-center">
                                <div class="p-2 bg-<?php echo $color; ?>-100 rounded-lg">
                                    <i class="fas fa-calendar-check text-<?php echo $color; ?>-600 text-lg"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs text-gray-600"><?php echo $estado; ?></p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo number_format($cantidad); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros y Búsqueda -->
                <div class="bg-white rounded-lg shadow mb-6 p-4">
                    <form method="GET" class="flex flex-col lg:flex-row gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <div class="relative">
                                <input type="text" name="buscar" value="<?php echo htmlspecialchars($busqueda); ?>" 
                                       placeholder="Buscar por cliente, email o tour..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select name="estado" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Todos los estados</option>
                                <option value="Pendiente" <?php echo $filtro_estado === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="Confirmada" <?php echo $filtro_estado === 'Confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                <option value="Cancelada" <?php echo $filtro_estado === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                <option value="Finalizada" <?php echo $filtro_estado === 'Finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del Tour</label>
                            <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div class="flex items-end gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-filter mr-1"></i>Filtrar
                            </button>
                            <a href="index.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                <i class="fas fa-times mr-1"></i>Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Reservas -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="table-container">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tour</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Tour</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pasajeros</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($reservas)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-lg font-medium">No se encontraron reservas</p>
                                            <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservas as $reserva): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                #<?php echo $reserva['id_reserva']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                                        <i class="fas fa-user text-white text-xs"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($reserva['usuario_nombre']); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($reserva['usuario_email']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($reserva['tour_titulo']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    Precio base: <?php echo formatCurrency($reserva['tour_precio']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo formatDate($reserva['fecha_tour']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-users mr-1"></i>
                                                    <?php echo $reserva['num_pasajeros']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo formatCurrency($reserva['monto_total']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getEstadoClass($reserva['estado']); ?>">
                                                    <?php echo $reserva['estado']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <a href="ver.php?id=<?php echo $reserva['id_reserva']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $reserva['id_reserva']; ?>" 
                                                       class="text-green-600 hover:text-green-900" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="eliminarReserva(<?php echo $reserva['id_reserva']; ?>)" 
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

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <?php if ($pagina > 1): ?>
                                        <a href="?pagina=<?php echo $pagina - 1; ?><?php echo $filtro_estado ? "&estado=$filtro_estado" : ''; ?><?php echo $busqueda ? "&buscar=$busqueda" : ''; ?><?php echo $filtro_fecha ? "&fecha=$filtro_fecha" : ''; ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Anterior
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($pagina < $total_paginas): ?>
                                        <a href="?pagina=<?php echo $pagina + 1; ?><?php echo $filtro_estado ? "&estado=$filtro_estado" : ''; ?><?php echo $busqueda ? "&buscar=$busqueda" : ''; ?><?php echo $filtro_fecha ? "&fecha=$filtro_fecha" : ''; ?>" 
                                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Siguiente
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Mostrando <span class="font-medium"><?php echo ($offset + 1); ?></span> a 
                                            <span class="font-medium"><?php echo min($offset + $registros_por_pagina, $total_registros); ?></span> de 
                                            <span class="font-medium"><?php echo $total_registros; ?></span> resultados
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                                <a href="?pagina=<?php echo $i; ?><?php echo $filtro_estado ? "&estado=$filtro_estado" : ''; ?><?php echo $busqueda ? "&buscar=$busqueda" : ''; ?><?php echo $filtro_fecha ? "&fecha=$filtro_fecha" : ''; ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $pagina ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>
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

    <!-- Modal de confirmación para eliminar -->
    <div id="modalEliminar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-2">Eliminar Reserva</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 mb-4">
                        ¿Estás seguro de que deseas eliminar esta reserva? Esta acción no se puede deshacer.
                    </p>
                    <div class="text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de eliminación (opcional):</label>
                        <textarea name="motivo" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                                  placeholder="Ingrese el motivo de la eliminación..."></textarea>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="btnConfirmarEliminar" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-auto hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300 mr-2">
                        Eliminar
                    </button>
                    <button onclick="cerrarModalEliminar()" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-auto hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let reservaAEliminar = null;

        function eliminarReserva(id) {
            reservaAEliminar = id;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModalEliminar() {
            document.getElementById('modalEliminar').classList.add('hidden');
            reservaAEliminar = null;
        }

        document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
            if (reservaAEliminar) {
                const motivo = document.querySelector('textarea[name="motivo"]')?.value || '';
                
                // Crear formulario para enviar los datos
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar.php';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_reserva';
                inputId.value = reservaAEliminar;
                form.appendChild(inputId);
                
                const inputMotivo = document.createElement('input');
                inputMotivo.type = 'hidden';
                inputMotivo.name = 'motivo';
                inputMotivo.value = motivo;
                form.appendChild(inputMotivo);
                
                document.body.appendChild(form);
                form.submit();
            }
            cerrarModalEliminar();
        });

        function exportarReservas() {
            const params = new URLSearchParams(window.location.search);
            params.set('exportar', '1');
            window.location.href = 'exportar.php?' + params.toString();
        }

        // Auto-submit del formulario de filtros cuando cambian los selects
        document.querySelectorAll('select[name="estado"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>
