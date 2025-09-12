<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del vehículo
$id_vehiculo = intval($_GET['id'] ?? 0);

if (!$id_vehiculo) {
    header('Location: index.php');
    exit;
}

try {
    $connection = getConnection();
    
    // Obtener información del vehículo con estadísticas
    $vehiculo_sql = "SELECT v.*,
                            CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellido, '')) as chofer_nombre,
                            c.telefono as chofer_telefono,
                            c.licencia as chofer_licencia,
                            COALESCE(tours_stats.total_tours, 0) as total_tours,
                            COALESCE(tours_stats.tours_proximos, 0) as tours_proximos,
                            COALESCE(tours_stats.tours_completados, 0) as tours_completados,
                            COALESCE(tours_stats.ultimo_tour, 'Nunca') as ultimo_tour,
                            COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados,
                            COALESCE(disponibilidad_stats.dias_libres, 0) as dias_libres
                     FROM vehiculos v
                     LEFT JOIN choferes c ON v.id_chofer = c.id_chofer
                     LEFT JOIN (
                         SELECT id_vehiculo,
                                COUNT(*) as total_tours,
                                SUM(CASE WHEN fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos,
                                SUM(CASE WHEN fecha < CURDATE() THEN 1 ELSE 0 END) as tours_completados,
                                MAX(fecha) as ultimo_tour
                         FROM tours_diarios
                         GROUP BY id_vehiculo
                     ) tours_stats ON v.id_vehiculo = tours_stats.id_vehiculo
                     LEFT JOIN (
                         SELECT id_vehiculo,
                                SUM(CASE WHEN estado = 'Ocupado' AND fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados,
                                SUM(CASE WHEN estado = 'Libre' AND fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_libres
                         FROM disponibilidad_vehiculos
                         GROUP BY id_vehiculo
                     ) disponibilidad_stats ON v.id_vehiculo = disponibilidad_stats.id_vehiculo
                     WHERE v.id_vehiculo = ?";
    
    $vehiculo_stmt = $connection->prepare($vehiculo_sql);
    $vehiculo_stmt->execute([$id_vehiculo]);
    $vehiculo = $vehiculo_stmt->fetch();
    
    if (!$vehiculo) {
        header('Location: index.php?error=Vehículo no encontrado');
        exit;
    }
    
    // Obtener próximos tours del vehículo
    $tours_sql = "SELECT td.*, t.titulo as tour_nombre, t.precio,
                         CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellido, '')) as chofer_tour,
                         CONCAT(COALESCE(g.nombre, ''), ' ', COALESCE(g.apellido, '')) as guia_nombre
                  FROM tours_diarios td
                  INNER JOIN tours t ON td.id_tour = t.id_tour
                  LEFT JOIN choferes c ON td.id_chofer = c.id_chofer
                  LEFT JOIN guias g ON td.id_guia = g.id_guia
                  WHERE td.id_vehiculo = ? AND td.fecha >= CURDATE()
                  ORDER BY td.fecha ASC, td.hora_salida ASC
                  LIMIT 10";
    
    $tours_stmt = $connection->prepare($tours_sql);
    $tours_stmt->execute([$id_vehiculo]);
    $proximos_tours = $tours_stmt->fetchAll();
    
    // Obtener historial de tours (últimos 20)
    $historial_sql = "SELECT td.*, t.titulo as tour_nombre, t.precio,
                             CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellido, '')) as chofer_tour,
                             CONCAT(COALESCE(g.nombre, ''), ' ', COALESCE(g.apellido, '')) as guia_nombre
                      FROM tours_diarios td
                      INNER JOIN tours t ON td.id_tour = t.id_tour
                      LEFT JOIN choferes c ON td.id_chofer = c.id_chofer
                      LEFT JOIN guias g ON td.id_guia = g.id_guia
                      WHERE td.id_vehiculo = ? AND td.fecha < CURDATE()
                      ORDER BY td.fecha DESC, td.hora_salida DESC
                      LIMIT 20";
    
    $historial_stmt = $connection->prepare($historial_sql);
    $historial_stmt->execute([$id_vehiculo]);
    $historial_tours = $historial_stmt->fetchAll();
    
    // Obtener disponibilidad próxima (próximos 30 días)
    $disponibilidad_sql = "SELECT fecha, estado
                          FROM disponibilidad_vehiculos
                          WHERE id_vehiculo = ? 
                          AND fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                          ORDER BY fecha ASC";
    
    $disponibilidad_stmt = $connection->prepare($disponibilidad_sql);
    $disponibilidad_stmt->execute([$id_vehiculo]);
    $disponibilidad = $disponibilidad_stmt->fetchAll();
    
    $page_title = "Vehículo: " . $vehiculo['marca'] . ' ' . $vehiculo['modelo'];
    
} catch (Exception $e) {
    $error = "Error al cargar vehículo: " . $e->getMessage();
    $page_title = "Error - Vehículo";
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
                <!-- Navegación -->
                <div class="mb-6">
                    <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="index.php" class="text-gray-600 hover:text-blue-600 inline-flex items-center">
                                    <i class="fas fa-car mr-2"></i>
                                    Vehículos
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                    <span class="text-gray-500">Detalles del Vehículo</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

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
                <?php else: ?>
                    <!-- Encabezado del vehículo -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="flex items-center">
                                <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-car text-white text-2xl"></i>
                                </div>
                                <div class="ml-6">
                                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                                        <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>
                                    </h1>
                                    <p class="text-gray-600">Placa: <?php echo htmlspecialchars($vehiculo['placa']); ?></p>
                                    <p class="text-gray-600">ID: #<?php echo $vehiculo['id_vehiculo']; ?></p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <a href="editar.php?id=<?php echo $vehiculo['id_vehiculo']; ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-edit mr-2"></i>Editar
                                </a>
                                <button onclick="eliminarVehiculo(<?php echo $vehiculo['id_vehiculo']; ?>, '<?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>')" 
                                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Eliminar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas principales -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100">
                                    <i class="fas fa-users text-blue-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Capacidad</p>
                                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($vehiculo['capacidad']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100">
                                    <i class="fas fa-map-marked-alt text-green-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Tours Totales</p>
                                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($vehiculo['total_tours']); ?></p>
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
                                    <p class="text-2xl font-bold text-orange-600"><?php echo number_format($vehiculo['tours_proximos']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100">
                                    <i class="fas fa-clock text-purple-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Días Ocupados</p>
                                    <p class="text-2xl font-bold text-purple-600"><?php echo number_format($vehiculo['dias_ocupados']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del vehículo y chofer -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Información del vehículo -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Información del Vehículo
                            </h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Marca y Modelo</label>
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Placa</label>
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($vehiculo['placa']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacidad</label>
                                    <p class="text-gray-900 font-medium">
                                        <i class="fas fa-users mr-2"></i><?php echo $vehiculo['capacidad']; ?> personas
                                    </p>
                                </div>
                                <?php if ($vehiculo['caracteristicas']): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Características</label>
                                        <p class="text-gray-900"><?php echo htmlspecialchars($vehiculo['caracteristicas']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Información del chofer -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-semibold text-gray-900">
                                    <i class="fas fa-user text-blue-600 mr-2"></i>Chofer Asignado
                                </h2>
                                <?php if (!$vehiculo['id_chofer']): ?>
                                    <button onclick="abrirModalAsignarChofer()" 
                                            class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                        <i class="fas fa-plus mr-1"></i>Asignar
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($vehiculo['id_chofer'] && trim($vehiculo['chofer_nombre'])): ?>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars(trim($vehiculo['chofer_nombre'])); ?></p>
                                    </div>
                                    <?php if ($vehiculo['chofer_telefono']): ?>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                            <div class="flex items-center">
                                                <i class="fas fa-phone text-gray-400 mr-2"></i>
                                                <span class="text-gray-900"><?php echo htmlspecialchars($vehiculo['chofer_telefono']); ?></span>
                                                <a href="tel:<?php echo htmlspecialchars($vehiculo['chofer_telefono']); ?>" 
                                                   class="ml-2 text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($vehiculo['chofer_licencia']): ?>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Licencia</label>
                                            <p class="text-gray-900"><?php echo htmlspecialchars($vehiculo['chofer_licencia']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex gap-2 pt-2">
                                        <a href="../choferes/ver.php?id=<?php echo $vehiculo['id_chofer']; ?>" 
                                           class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                            <i class="fas fa-eye mr-1"></i>Ver Perfil
                                        </a>
                                        <button onclick="desasignarChofer()" 
                                                class="inline-flex items-center px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm">
                                            <i class="fas fa-unlink mr-1"></i>Desasignar
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-user-times text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Sin chofer asignado</h3>
                                    <p class="text-gray-500 mb-4">Este vehículo no tiene un chofer asignado actualmente.</p>
                                    <button onclick="abrirModalAsignarChofer()" 
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-plus mr-2"></i>Asignar Chofer
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Próximos tours -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">
                            <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>Próximos Tours (<?php echo count($proximos_tours); ?>)
                        </h2>

                        <?php if (empty($proximos_tours)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-alt text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Sin tours próximos</h3>
                                <p class="text-gray-500">Este vehículo no tiene tours programados próximamente.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Fecha y Hora
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Tour
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Chofer
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Guía
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Capacidad
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($proximos_tours as $tour): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo date('d/m/Y', strtotime($tour['fecha'])); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo date('H:i', strtotime($tour['hora_salida'])); ?>
                                                        <?php if ($tour['hora_retorno']): ?>
                                                            - <?php echo date('H:i', strtotime($tour['hora_retorno'])); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($tour['tour_nombre']); ?>
                                                    </div>
                                                    <?php if ($tour['precio']): ?>
                                                        <div class="text-sm text-gray-500">
                                                            $<?php echo number_format($tour['precio'], 2); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php if ($tour['chofer_tour'] && trim($tour['chofer_tour'])): ?>
                                                        <div class="text-sm text-gray-900">
                                                            <?php echo htmlspecialchars(trim($tour['chofer_tour'])); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">Sin asignar</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php if ($tour['guia_nombre'] && trim($tour['guia_nombre'])): ?>
                                                        <div class="text-sm text-gray-900">
                                                            <?php echo htmlspecialchars(trim($tour['guia_nombre'])); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">Sin asignar</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm">
                                                        <?php if ($tour['num_adultos'] || $tour['num_ninos']): ?>
                                                            <div class="text-gray-900">
                                                                <i class="fas fa-user mr-1"></i><?php echo $tour['num_adultos']; ?> adultos
                                                            </div>
                                                            <?php if ($tour['num_ninos'] > 0): ?>
                                                                <div class="text-gray-500">
                                                                    <i class="fas fa-child mr-1"></i><?php echo $tour['num_ninos']; ?> niños
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-gray-400">Sin especificar</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Historial de tours -->
                    <?php if (!empty($historial_tours)): ?>
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">
                                <i class="fas fa-history text-blue-600 mr-2"></i>Historial de Tours (Últimos <?php echo count($historial_tours); ?>)
                            </h2>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Fecha
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Tour
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Chofer
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Guía
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Participantes
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($historial_tours as $tour): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo date('d/m/Y', strtotime($tour['fecha'])); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($tour['tour_nombre']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php if ($tour['chofer_tour'] && trim($tour['chofer_tour'])): ?>
                                                        <div class="text-sm text-gray-900">
                                                            <?php echo htmlspecialchars(trim($tour['chofer_tour'])); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php if ($tour['guia_nombre'] && trim($tour['guia_nombre'])): ?>
                                                        <div class="text-sm text-gray-900">
                                                            <?php echo htmlspecialchars(trim($tour['guia_nombre'])); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo ($tour['num_adultos'] + $tour['num_ninos']); ?> personas
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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
                <h3 class="text-lg font-medium text-gray-900 mt-4">Eliminar Vehículo</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        ¿Estás seguro de que deseas eliminar el vehículo <span id="nombreVehiculo" class="font-medium"></span>? 
                        Esta acción no se puede deshacer y se cancelarán todos los tours programados.
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

    <!-- Modal para asignar chofer -->
    <div id="modalAsignarChofer" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900">Asignar Chofer al Vehículo</h3>
                <button onclick="cerrarModalAsignarChofer()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="choferes-loading" class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-4"></i>
                <p class="text-gray-500">Cargando choferes disponibles...</p>
            </div>
            
            <div id="choferes-disponibles" class="hidden">
                <!-- Los choferes se cargarán aquí dinámicamente -->
            </div>
        </div>
    </div>

    <script>
        let vehiculoAEliminar = null;
        const vehiculoId = <?php echo $id_vehiculo; ?>;

        function eliminarVehiculo(id, nombre) {
            vehiculoAEliminar = id;
            document.getElementById('nombreVehiculo').textContent = nombre;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalEliminar').classList.add('hidden');
            vehiculoAEliminar = null;
        }

        function confirmarEliminacion() {
            if (vehiculoAEliminar) {
                window.location.href = `eliminar.php?id=${vehiculoAEliminar}`;
            }
        }

        function abrirModalAsignarChofer() {
            document.getElementById('modalAsignarChofer').classList.remove('hidden');
            cargarChoferesDisponibles();
        }

        function cerrarModalAsignarChofer() {
            document.getElementById('modalAsignarChofer').classList.add('hidden');
        }

        async function cargarChoferesDisponibles() {
            const loading = document.getElementById('choferes-loading');
            const container = document.getElementById('choferes-disponibles');

            loading.classList.remove('hidden');
            container.classList.add('hidden');

            try {
                const response = await fetch('gestionar_chofer.php?action=get_disponibles');
                const data = await response.json();

                if (data.success) {
                    mostrarChoferesDisponibles(data.choferes);
                    container.classList.remove('hidden');
                } else {
                    container.innerHTML = '<p class="text-center text-gray-500 py-8">No hay choferes disponibles</p>';
                    container.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error al cargar choferes:', error);
                container.innerHTML = '<p class="text-center text-red-500 py-8">Error al cargar choferes</p>';
                container.classList.remove('hidden');
            }

            loading.classList.add('hidden');
        }

        function mostrarChoferesDisponibles(choferes) {
            const container = document.getElementById('choferes-disponibles');
            
            if (choferes.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-user-times text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay choferes disponibles</h3>
                        <p class="text-gray-500">Todos los choferes ya tienen vehículos asignados o no hay choferes registrados.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${choferes.map(chofer => `
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        ${chofer.nombre} ${chofer.apellido || ''}
                                    </h3>
                                    ${chofer.telefono ? `<p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i>${chofer.telefono}</p>` : ''}
                                    ${chofer.licencia ? `<p class="text-sm text-gray-600"><i class="fas fa-id-badge mr-1"></i>${chofer.licencia}</p>` : ''}
                                </div>
                                <button onclick="asignarChofer(${chofer.id_chofer})" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-link mr-1"></i>Asignar
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        async function asignarChofer(choferId) {
            try {
                const response = await fetch('gestionar_chofer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'asignar',
                        vehiculo_id: vehiculoId,
                        chofer_id: choferId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Chofer asignado exitosamente');
                    cerrarModalAsignarChofer();
                    location.reload();
                } else {
                    alert('Error al asignar chofer: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al asignar chofer');
            }
        }

        async function desasignarChofer() {
            if (!confirm('¿Estás seguro de que deseas desasignar el chofer de este vehículo?')) {
                return;
            }

            try {
                const response = await fetch('gestionar_chofer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'desasignar',
                        vehiculo_id: vehiculoId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Chofer desasignado exitosamente');
                    location.reload();
                } else {
                    alert('Error al desasignar chofer: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al desasignar chofer');
            }
        }

        // Cerrar modales al hacer clic fuera
        document.getElementById('modalEliminar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        document.getElementById('modalAsignarChofer').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalAsignarChofer();
            }
        });

        // Cerrar modales con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
                cerrarModalAsignarChofer();
            }
        });
    </script>
</body>
</html>
