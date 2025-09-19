<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Función auxiliar para manejar valores nulos de forma segura
function safeHtmlSpecialChars($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Obtener ID del chofer
$id_chofer = intval($_GET['id'] ?? 0);

if (!$id_chofer) {
    header('Location: index.php');
    exit;
}

try {
    $connection = getConnection();
    
    // Verificar si existe el campo foto_url
    $has_foto_column = false;
    try {
        $check_column_sql = "SHOW COLUMNS FROM choferes LIKE 'foto_url'";
        $check_column_stmt = $connection->prepare($check_column_sql);
        $check_column_stmt->execute();
        $has_foto_column = ($check_column_stmt->fetch() !== false);
    } catch (Exception $e) {
        $has_foto_column = false;
    }
    
    // Obtener información del chofer con estadísticas
    $chofer_sql = "SELECT c.*,
                          COALESCE(vehiculos_stats.total_vehiculos, 0) as total_vehiculos,
                          COALESCE(tours_stats.total_tours, 0) as total_tours,
                          COALESCE(tours_stats.tours_proximos, 0) as tours_proximos,
                          COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados
                   FROM choferes c 
                   LEFT JOIN (
                       SELECT id_chofer, COUNT(*) as total_vehiculos
                       FROM vehiculos 
                       WHERE id_chofer = ?
                       GROUP BY id_chofer
                   ) vehiculos_stats ON c.id_chofer = vehiculos_stats.id_chofer
                   LEFT JOIN (
                       SELECT v.id_chofer,
                              COUNT(td.id_tour_diario) as total_tours,
                              SUM(CASE WHEN td.fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos
                       FROM vehiculos v
                       LEFT JOIN tours_diarios td ON v.id_vehiculo = td.id_vehiculo
                       WHERE v.id_chofer = ?
                       GROUP BY v.id_chofer
                   ) tours_stats ON c.id_chofer = tours_stats.id_chofer
                   LEFT JOIN (
                       SELECT v.id_chofer,
                              SUM(CASE WHEN dv.estado = 'Ocupado' AND dv.fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados
                       FROM vehiculos v
                       LEFT JOIN disponibilidad_vehiculos dv ON v.id_vehiculo = dv.id_vehiculo
                       WHERE v.id_chofer = ?
                       GROUP BY v.id_chofer
                   ) disponibilidad_stats ON c.id_chofer = disponibilidad_stats.id_chofer
                   WHERE c.id_chofer = ?";
    
    $chofer_stmt = $connection->prepare($chofer_sql);
    $chofer_stmt->execute([$id_chofer, $id_chofer, $id_chofer, $id_chofer]);
    $chofer = $chofer_stmt->fetch();
    
    if (!$chofer) {
        header('Location: index.php?error=Chofer no encontrado');
        exit;
    }
    
    // Obtener vehículos del chofer
    $vehiculos_sql = "SELECT v.*,
                             COALESCE(tours_stats.total_tours, 0) as tours_asignados,
                             COALESCE(tours_stats.tours_proximos, 0) as tours_proximos,
                             COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados
                      FROM vehiculos v
                      LEFT JOIN (
                          SELECT id_vehiculo,
                                 COUNT(*) as total_tours,
                                 SUM(CASE WHEN fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos
                          FROM tours_diarios
                          GROUP BY id_vehiculo
                      ) tours_stats ON v.id_vehiculo = tours_stats.id_vehiculo
                      LEFT JOIN (
                          SELECT id_vehiculo,
                                 SUM(CASE WHEN estado = 'Ocupado' AND fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados
                          FROM disponibilidad_vehiculos
                          GROUP BY id_vehiculo
                      ) disponibilidad_stats ON v.id_vehiculo = disponibilidad_stats.id_vehiculo
                      WHERE v.id_chofer = ?
                      ORDER BY v.marca, v.modelo";
    
    $vehiculos_stmt = $connection->prepare($vehiculos_sql);
    $vehiculos_stmt->execute([$id_chofer]);
    $vehiculos = $vehiculos_stmt->fetchAll();
    
    // Obtener próximos tours del chofer
    $tours_sql = "SELECT td.*, t.titulo as tour_nombre, t.precio,
                         v.marca, v.modelo, v.placa,
                         g.nombre as guia_nombre, g.apellido as guia_apellido
                  FROM tours_diarios td
                  INNER JOIN tours t ON td.id_tour = t.id_tour
                  INNER JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo
                  LEFT JOIN guias g ON td.id_guia = g.id_guia
                  WHERE v.id_chofer = ? AND td.fecha >= CURDATE()
                  ORDER BY td.fecha ASC, td.hora_salida ASC
                  LIMIT 10";
    
    $tours_stmt = $connection->prepare($tours_sql);
    $tours_stmt->execute([$id_chofer]);
    $proximos_tours = $tours_stmt->fetchAll();
    
    $page_title = "Chofer: " . $chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '');
    
} catch (Exception $e) {
    $error = "Error al cargar chofer: " . $e->getMessage();
    $page_title = "Error - Chofer";
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
    <style>
        .tab-button {
            transition: all 0.2s ease;
        }
        .tab-button:hover {
            border-color: #d1d5db;
        }
        .tab-button.active {
            border-color: #3b82f6 !important;
            color: #3b82f6 !important;
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
                <!-- Navegación -->
                <div class="mb-6">
                    <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="index.php" class="text-gray-600 hover:text-blue-600 inline-flex items-center">
                                    <i class="fas fa-id-card mr-2"></i>
                                    Choferes
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                    <span class="text-gray-500">Detalles del Chofer</span>
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
                    <!-- Encabezado del chofer -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="flex items-center">
                                <!-- Foto del chofer -->
                                <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0 overflow-hidden border-4 border-white shadow-lg">
                                    <?php 
                                    $foto_url = ($has_foto_column && !empty($chofer['foto_url'])) ? $chofer['foto_url'] : '';
                                    $mostrar_foto = false;
                                    $foto_src = '';
                                    
                                    if ($foto_url) {
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
                                        <img src="<?php echo $foto_src; ?>" 
                                             alt="Foto de <?php echo safeHtmlSpecialChars($chofer['nombre']); ?>" 
                                             class="h-full w-full object-cover rounded-full">
                                    <?php else: ?>
                                        <span class="text-white font-bold text-2xl">
                                            <?php echo strtoupper(substr($chofer['nombre'], 0, 1) . substr(safeHtmlSpecialChars($chofer['apellido']), 0, 1)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-6">
                                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                                        <?php echo safeHtmlSpecialChars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                    </h1>
                                    <p class="text-gray-600">ID: #<?php echo $chofer['id_chofer']; ?></p>
                                    <?php if (!empty($chofer['licencia'])): ?>
                                        <div class="flex items-center mt-2">
                                            <i class="fas fa-id-badge text-gray-400 mr-2"></i>
                                            <span class="text-sm text-gray-600">Licencia: <?php echo safeHtmlSpecialChars($chofer['licencia']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($chofer['telefono'])): ?>
                                        <div class="flex items-center mt-1">
                                            <i class="fas fa-phone text-gray-400 mr-2"></i>
                                            <span class="text-sm text-gray-600">Teléfono: <?php echo safeHtmlSpecialChars($chofer['telefono']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <a href="editar.php?id=<?php echo $chofer['id_chofer']; ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-edit mr-2"></i>Editar
                                </a>
                                <button onclick="eliminarChofer(<?php echo $chofer['id_chofer']; ?>, '<?php echo safeHtmlSpecialChars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>')" 
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
                                <div class="p-3 rounded-full bg-green-100">
                                    <i class="fas fa-car text-green-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Vehículos</p>
                                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($chofer['total_vehiculos']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100">
                                    <i class="fas fa-map-marked-alt text-blue-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Tours Totales</p>
                                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($chofer['total_tours']); ?></p>
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
                                    <p class="text-2xl font-bold text-orange-600"><?php echo number_format($chofer['tours_proximos']); ?></p>
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
                                    <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($chofer['dias_ocupados']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de contacto -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            <i class="fas fa-address-card text-blue-600 mr-2"></i>Información de Contacto
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <?php if ($chofer['telefono']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($chofer['telefono']); ?></span>
                                        <a href="tel:<?php echo htmlspecialchars($chofer['telefono']); ?>" 
                                           class="ml-2 text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-400">No especificado</p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Licencia de Conducir</label>
                                <?php if ($chofer['licencia']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-id-badge text-gray-400 mr-2"></i>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($chofer['licencia']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-400">No especificada</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Vehículos asignados -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900">
                                <i class="fas fa-car text-blue-600 mr-2"></i>Vehículos Asignados (<?php echo count($vehiculos); ?>)
                            </h2>
                            <button onclick="abrirModalAsignarVehiculo()" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-plus mr-2"></i>Asignar Vehículo
                            </button>
                        </div>

                        <?php if (empty($vehiculos)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-car text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Sin vehículos asignados</h3>
                                <p class="text-gray-500 mb-4">Este chofer no tiene vehículos asignados actualmente.</p>
                                <button onclick="abrirModalAsignarVehiculo()" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Asignar Primer Vehículo
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($vehiculos as $vehiculo): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                        <div class="flex items-start justify-between mb-3">
                                            <div>
                                                <h3 class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>
                                                </h3>
                                                <p class="text-sm text-gray-600">Placa: <?php echo htmlspecialchars($vehiculo['placa']); ?></p>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <a href="../vehiculos/ver.php?id=<?php echo $vehiculo['id_vehiculo']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button onclick="desasignarVehiculo(<?php echo $vehiculo['id_vehiculo']; ?>, '<?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>')"
                                                        class="text-red-600 hover:text-red-800 text-sm"
                                                        title="Desasignar vehículo">
                                                    <i class="fas fa-unlink"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600">Capacidad:</span>
                                                <span class="font-medium"><?php echo $vehiculo['capacidad']; ?> personas</span>
                                            </div>
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600">Tours asignados:</span>
                                                <span class="font-medium"><?php echo $vehiculo['tours_asignados']; ?></span>
                                            </div>
                                            <?php if ($vehiculo['tours_proximos'] > 0): ?>
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-600">Tours próximos:</span>
                                                    <span class="font-medium text-orange-600"><?php echo $vehiculo['tours_proximos']; ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($vehiculo['dias_ocupados'] > 0): ?>
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-600">Días ocupados:</span>
                                                    <span class="font-medium text-yellow-600"><?php echo $vehiculo['dias_ocupados']; ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Próximos tours -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">
                            <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>Próximos Tours (<?php echo count($proximos_tours); ?>)
                        </h2>

                        <?php if (empty($proximos_tours)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-alt text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Sin tours próximos</h3>
                                <p class="text-gray-500">Este chofer no tiene tours programados próximamente.</p>
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
                                                Vehículo
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Guía Asignado
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
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($tour['marca'] . ' ' . $tour['modelo']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($tour['placa']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php if ($tour['guia_nombre']): ?>
                                                        <div class="text-sm text-gray-900">
                                                            <?php echo htmlspecialchars($tour['guia_nombre'] . ' ' . ($tour['guia_apellido'] ?? '')); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            Guía ID: #<?php echo $tour['id_guia']; ?>
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
                <h3 class="text-lg font-medium text-gray-900 mt-4">Eliminar Chofer</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        ¿Estás seguro de que deseas eliminar al chofer <span id="nombreChofer" class="font-medium"></span>? 
                        Esta acción no se puede deshacer y se desasignarán todos sus vehículos.
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

    <!-- Modal para asignar vehículo -->
    <div id="modalAsignarVehiculo" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900">Asignar Vehículo</h3>
                <button onclick="cerrarModalAsignar()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="cambiarTab('disponibles')" id="tab-disponibles" 
                            class="tab-button active py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                        Vehículos Disponibles
                    </button>
                    <button onclick="cambiarTab('nuevo')" id="tab-nuevo" 
                            class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Crear Nuevo Vehículo
                    </button>
                </nav>
            </div>

            <!-- Tab content: Vehículos disponibles -->
            <div id="content-disponibles" class="tab-content">
                <div id="vehiculos-loading" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">Cargando vehículos disponibles...</p>
                </div>
                <div id="vehiculos-disponibles" class="hidden">
                    <!-- Los vehículos se cargarán aquí dinámicamente -->
                </div>
                <div id="no-vehiculos" class="hidden text-center py-8">
                    <i class="fas fa-car text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay vehículos disponibles</h3>
                    <p class="text-gray-500 mb-4">Todos los vehículos ya tienen chofer asignado.</p>
                    <button onclick="cambiarTab('nuevo')" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Crear Nuevo Vehículo
                    </button>
                </div>
            </div>

            <!-- Tab content: Crear nuevo vehículo -->
            <div id="content-nuevo" class="tab-content hidden">
                <form id="form-nuevo-vehiculo" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Marca *</label>
                            <input type="text" id="nueva-marca" name="marca" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: Toyota">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Modelo *</label>
                            <input type="text" id="nuevo-modelo" name="modelo" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: Hiace">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Placa *</label>
                            <input type="text" id="nueva-placa" name="placa" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: ABC-123" style="text-transform: uppercase;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Capacidad *</label>
                            <input type="number" id="nueva-capacidad" name="capacidad" min="1" max="50" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: 15">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Características</label>
                        <textarea id="nuevas-caracteristicas" name="caracteristicas" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Aire acondicionado, GPS, WiFi, etc."></textarea>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="cerrarModalAsignar()" 
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Crear y Asignar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let choferAEliminar = null;
        const choferId = <?php echo $chofer['id_chofer']; ?>;

        // Funciones para modal de eliminar
        function eliminarChofer(id, nombre) {
            choferAEliminar = id;
            document.getElementById('nombreChofer').textContent = nombre;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalEliminar').classList.add('hidden');
            choferAEliminar = null;
        }

        function confirmarEliminacion() {
            if (choferAEliminar) {
                window.location.href = `eliminar.php?id=${choferAEliminar}`;
            }
        }

        // Funciones para modal de asignar vehículo
        function abrirModalAsignarVehiculo() {
            document.getElementById('modalAsignarVehiculo').classList.remove('hidden');
            cargarVehiculosDisponibles();
        }

        function cerrarModalAsignar() {
            document.getElementById('modalAsignarVehiculo').classList.add('hidden');
            // Reset formulario
            document.getElementById('form-nuevo-vehiculo').reset();
            // Reset tabs
            cambiarTab('disponibles');
        }

        function cambiarTab(tab) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            // Remover clases activas de todos los tabs
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar contenido seleccionado
            document.getElementById(`content-${tab}`).classList.remove('hidden');
            // Activar tab seleccionado
            const activeTab = document.getElementById(`tab-${tab}`);
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.classList.add('border-blue-500', 'text-blue-600');

            if (tab === 'disponibles') {
                cargarVehiculosDisponibles();
            }
        }

        async function cargarVehiculosDisponibles() {
            const loading = document.getElementById('vehiculos-loading');
            const container = document.getElementById('vehiculos-disponibles');
            const noVehiculos = document.getElementById('no-vehiculos');

            loading.classList.remove('hidden');
            container.classList.add('hidden');
            noVehiculos.classList.add('hidden');

            try {
                const response = await fetch('asignar_vehiculo.php?action=get_disponibles');
                const data = await response.json();

                if (data.success && data.vehiculos.length > 0) {
                    mostrarVehiculosDisponibles(data.vehiculos);
                    container.classList.remove('hidden');
                } else {
                    noVehiculos.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error al cargar vehículos:', error);
                noVehiculos.classList.remove('hidden');
            }

            loading.classList.add('hidden');
        }

        function mostrarVehiculosDisponibles(vehiculos) {
            const container = document.getElementById('vehiculos-disponibles');
            
            container.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    ${vehiculos.map(vehiculo => `
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        ${vehiculo.marca} ${vehiculo.modelo}
                                    </h3>
                                    <p class="text-sm text-gray-600">Placa: ${vehiculo.placa}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Capacidad:</span>
                                    <span class="font-medium">${vehiculo.capacidad} personas</span>
                                </div>
                                ${vehiculo.caracteristicas ? `
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">Características:</span>
                                        <p class="mt-1">${vehiculo.caracteristicas}</p>
                                    </div>
                                ` : ''}
                            </div>
                            
                            <button onclick="asignarVehiculo(${vehiculo.id_vehiculo})" 
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-link mr-2"></i>Asignar a Chofer
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        async function asignarVehiculo(vehiculoId) {
            try {
                const response = await fetch('asignar_vehiculo.php', {
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
                    alert('Vehículo asignado exitosamente');
                    cerrarModalAsignar();
                    location.reload(); // Recargar para mostrar el nuevo vehículo
                } else {
                    alert('Error al asignar vehículo: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al asignar vehículo');
            }
        }

        async function desasignarVehiculo(vehiculoId, vehiculoNombre) {
            if (!confirm(`¿Estás seguro de que deseas desasignar el vehículo "${vehiculoNombre}"? El vehículo quedará disponible para otros choferes.`)) {
                return;
            }

            try {
                const response = await fetch('asignar_vehiculo.php', {
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
                    alert('Vehículo desasignado exitosamente');
                    location.reload(); // Recargar para actualizar la lista
                } else {
                    alert('Error al desasignar vehículo: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al desasignar vehículo');
            }
        }

        // Manejar formulario de nuevo vehículo
        document.getElementById('form-nuevo-vehiculo').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                action: 'crear_y_asignar',
                chofer_id: choferId,
                marca: formData.get('marca'),
                modelo: formData.get('modelo'),
                placa: formData.get('placa').toUpperCase(),
                capacidad: formData.get('capacidad'),
                caracteristicas: formData.get('caracteristicas')
            };

            try {
                const response = await fetch('asignar_vehiculo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Vehículo creado y asignado exitosamente');
                    cerrarModalAsignar();
                    location.reload(); // Recargar para mostrar el nuevo vehículo
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al crear vehículo');
            }
        });

        // Formatear placa mientras se escribe
        document.getElementById('nueva-placa').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        // Cerrar modales al hacer clic fuera
        document.getElementById('modalEliminar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        document.getElementById('modalAsignarVehiculo').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalAsignar();
            }
        });

        // Cerrar modales con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
                cerrarModalAsignar();
            }
        });
    </script>
</body>
</html>
