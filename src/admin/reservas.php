<?php
require_once 'middleware.php';
require_once 'functions/reservas_functions.php';

$admin = getCurrentAdmin();
$page_title = "Gestión de Reservas";

// Obtener parámetros de búsqueda
$search = $_GET['search'] ?? '';
$estado_filter = $_GET['estado'] ?? '';
$fecha_filter = $_GET['fecha'] ?? '';
$page = intval($_GET['page'] ?? 1);

// Obtener reservas
$result = getReservas($page, RECORDS_PER_PAGE, $search, $estado_filter, $fecha_filter);
$reservas = $result['reservas'];
$total_pages = $result['total_pages'];
$current_page = $result['current_page'];

// Obtener estadísticas para el encabezado
$stats = getReservasStats();
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
    <?php include 'components/header.php'; ?>
    
    <div class="flex">
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 ml-64 p-8">
            <!-- Encabezado con estadísticas -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Gestión de Reservas</h1>
                        <p class="text-gray-600 mt-1">Administra las reservas de tours</p>
                    </div>
                    <button onclick="showCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-plus mr-2"></i>Nueva Reserva
                    </button>
                </div>
                
                <!-- Tarjetas de estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Reservas</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_reservas'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-calendar-plus text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Este Mes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['mes_actual'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-dollar-sign text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Ingresos Mes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['ingresos_mes'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-clock text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Pendientes</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php 
                                    $pendientes = 0;
                                    foreach ($stats['por_estado'] as $estado) {
                                        if ($estado['estado'] === 'Pendiente') {
                                            $pendientes = $estado['cantidad'];
                                            break;
                                        }
                                    }
                                    echo $pendientes;
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros de búsqueda -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="ID, nombre o email..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los estados</option>
                            <option value="Pendiente" <?php echo $estado_filter === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Confirmada" <?php echo $estado_filter === 'Confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                            <option value="Cancelada" <?php echo $estado_filter === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            <option value="Finalizada" <?php echo $estado_filter === 'Finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha del Tour</label>
                        <input type="date" name="fecha" value="<?php echo htmlspecialchars($fecha_filter); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-end gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-search mr-2"></i>Buscar
                        </button>
                        <a href="reservas.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition duration-200">
                            <i class="fas fa-times mr-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Tabla de reservas -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
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
                                        <i class="fas fa-calendar-times text-4xl mb-4"></i>
                                        <p>No se encontraron reservas</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reservas as $reserva): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?php echo $reserva['id_reserva']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reserva['usuario_nombre']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($reserva['usuario_email']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reserva['tour_titulo']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($reserva['nombre_region']); ?> • <?php echo $reserva['tour_duracion']; ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($reserva['fecha_tour'], 'd/m/Y'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo $reserva['total_pasajeros']; ?> personas
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatCurrency($reserva['monto_total']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $estado_classes = [
                                                'Pendiente' => 'bg-yellow-100 text-yellow-800',
                                                'Confirmada' => 'bg-green-100 text-green-800',
                                                'Cancelada' => 'bg-red-100 text-red-800',
                                                'Finalizada' => 'bg-blue-100 text-blue-800'
                                            ];
                                            $class = $estado_classes[$reserva['estado']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $class; ?>">
                                                <?php echo $reserva['estado']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="viewReserva(<?php echo $reserva['id_reserva']; ?>)" 
                                                        class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editEstado(<?php echo $reserva['id_reserva']; ?>, '<?php echo $reserva['estado']; ?>')" 
                                                        class="text-green-600 hover:text-green-900" title="Cambiar estado">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (hasPermission('admin')): ?>
                                                <button onclick="deleteReserva(<?php echo $reserva['id_reserva']; ?>)" 
                                                        class="text-red-600 hover:text-red-900" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-200">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado_filter); ?>&fecha=<?php echo urlencode($fecha_filter); ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Anterior
                                </a>
                            <?php endif; ?>
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado_filter); ?>&fecha=<?php echo urlencode($fecha_filter); ?>" 
                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Siguiente
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Página <span class="font-medium"><?php echo $current_page; ?></span>
                                    de <span class="font-medium"><?php echo $total_pages; ?></span>
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == $current_page): ?>
                                            <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">
                                                <?php echo $i; ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado_filter); ?>&fecha=<?php echo urlencode($fecha_filter); ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal para ver detalles -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Detalles de la Reserva</h3>
                        <button onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div id="viewModalContent" class="p-6">
                    <!-- El contenido se carga dinámicamente -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para cambiar estado -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Cambiar Estado de Reserva</h3>
                </div>
                <div class="p-6">
                    <form id="editEstadoForm" onsubmit="updateEstado(event)">
                        <input type="hidden" id="editReservaId">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estado</label>
                            <select id="editEstado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="Pendiente">Pendiente</option>
                                <option value="Confirmada">Confirmada</option>
                                <option value="Cancelada">Cancelada</option>
                                <option value="Finalizada">Finalizada</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Actualizar Estado
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/reservas.js"></script>
</body>
</html>
