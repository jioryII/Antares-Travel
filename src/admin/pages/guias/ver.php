<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del guía
$id_guia = intval($_GET['id'] ?? 0);

if (!$id_guia) {
    header('Location: index.php');
    exit;
}

try {
    $connection = getConnection();
    
    // Obtener datos completos del guía con estadísticas reales
    $guia_sql = "SELECT g.*,
                        COALESCE(tours_stats.total_tours, 0) as total_tours,
                        COALESCE(tours_stats.tours_activos, 0) as tours_activos,
                        COALESCE(diarios_stats.tours_diarios_total, 0) as tours_diarios_total,
                        COALESCE(diarios_stats.tours_completados, 0) as tours_completados,
                        COALESCE(diarios_stats.tours_proximos, 0) as tours_proximos,
                        COALESCE(cal_stats.calificacion_promedio, 0) as calificacion_promedio,
                        COALESCE(cal_stats.total_calificaciones, 0) as total_calificaciones,
                        COALESCE(idiomas_stats.idiomas_count, 0) as idiomas_count,
                        COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados,
                        COALESCE(reservas_stats.ingresos_generados, 0) as ingresos_generados
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
                            SUM(CASE WHEN fecha < CURDATE() THEN 1 ELSE 0 END) as tours_completados,
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
                 LEFT JOIN (
                     SELECT t.id_guia,
                            SUM(r.monto_total) as ingresos_generados
                     FROM tours t
                     INNER JOIN reservas r ON t.id_tour = r.id_tour
                     WHERE r.estado IN ('Confirmada', 'Finalizada')
                     GROUP BY t.id_guia
                 ) reservas_stats ON g.id_guia = reservas_stats.id_guia
                 WHERE g.id_guia = ?";
    
    $guia_stmt = $connection->prepare($guia_sql);
    $guia_stmt->execute([$id_guia]);
    $guia = $guia_stmt->fetch();
    
    if (!$guia) {
        header('Location: index.php?error=Guía no encontrado');
        exit;
    }
    
    // Obtener tours asignados al guía
    $tours_sql = "SELECT t.*, r.nombre_region
                  FROM tours t
                  LEFT JOIN regiones r ON t.id_region = r.id_region
                  WHERE t.id_guia = ?
                  ORDER BY t.titulo";
    $tours_stmt = $connection->prepare($tours_sql);
    $tours_stmt->execute([$id_guia]);
    $tours = $tours_stmt->fetchAll();
    
    // Obtener próximos tours diarios
    $proximos_tours_sql = "SELECT td.*, t.titulo, t.lugar_salida, t.lugar_llegada,
                                  c.nombre as chofer_nombre, c.apellido as chofer_apellido,
                                  v.marca, v.modelo, v.placa
                           FROM tours_diarios td
                           INNER JOIN tours t ON td.id_tour = t.id_tour
                           LEFT JOIN choferes c ON td.id_chofer = c.id_chofer
                           LEFT JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo
                           WHERE td.id_guia = ? AND td.fecha >= CURDATE()
                           ORDER BY td.fecha, td.hora_salida
                           LIMIT 10";
    $proximos_stmt = $connection->prepare($proximos_tours_sql);
    $proximos_stmt->execute([$id_guia]);
    $proximos_tours = $proximos_stmt->fetchAll();
    
    // Obtener calificaciones recientes
    $calificaciones_sql = "SELECT cg.*, u.nombre as usuario_nombre, u.email as usuario_email
                           FROM calificaciones_guias cg
                           LEFT JOIN usuarios u ON cg.id_usuario = u.id_usuario
                           WHERE cg.id_guia = ?
                           ORDER BY cg.fecha DESC
                           LIMIT 5";
    $calificaciones_stmt = $connection->prepare($calificaciones_sql);
    $calificaciones_stmt->execute([$id_guia]);
    $calificaciones = $calificaciones_stmt->fetchAll();
    
    // Obtener idiomas del guía
    $idiomas_sql = "SELECT i.nombre_idioma
                    FROM guia_idiomas gi
                    INNER JOIN idiomas i ON gi.id_idioma = i.id_idioma
                    WHERE gi.id_guia = ?
                    ORDER BY i.nombre_idioma";
    $idiomas_stmt = $connection->prepare($idiomas_sql);
    $idiomas_stmt->execute([$id_guia]);
    $idiomas = $idiomas_stmt->fetchAll();
    
    $page_title = "Guía: " . $guia['nombre'] . ' ' . $guia['apellido'];
    
} catch (Exception $e) {
    $error = "Error al cargar datos del guía: " . $e->getMessage();
    // Inicializar variables por defecto
    $guia = [
        'id_guia' => $id_guia,
        'nombre' => '',
        'apellido' => '',
        'email' => '',
        'telefono' => '',
        'experiencia' => '',
        'estado' => 'Libre',
        'foto_url' => '',
        'total_tours' => 0,
        'tours_activos' => 0,
        'tours_diarios_total' => 0,
        'tours_completados' => 0,
        'tours_proximos' => 0,
        'calificacion_promedio' => 0,
        'total_calificaciones' => 0,
        'idiomas_count' => 0,
        'dias_ocupados' => 0,
        'ingresos_generados' => 0
    ];
    $tours = [];
    $proximos_tours = [];
    $calificaciones = [];
    $idiomas = [];
    $page_title = "Error - Guía";
}

function getEstadoClass($estado) {
    return $estado === 'Libre' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
}

function getEstadoIcon($estado) {
    return $estado === 'Libre' ? 'fas fa-check-circle' : 'fas fa-clock';
}

function getEstadoTourIcon($estado) {
    $estado_lower = strtolower($estado);
    $icons = [
        'pendiente' => 'fas fa-clock',
        'confirmada' => 'fas fa-check',
        'cancelada' => 'fas fa-times',
        'finalizada' => 'fas fa-check-circle'
    ];
    return $icons[$estado_lower] ?? 'fas fa-question';
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
        .timeline-item {
            position: relative;
            padding-left: 3rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-item:last-child::before {
            bottom: 1rem;
        }
        .timeline-dot {
            position: absolute;
            left: 0.75rem;
            top: 0.5rem;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background: #3b82f6;
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
                            <div class="flex items-center mb-2">
                                <a href="index.php" class="text-blue-600 hover:text-blue-800 mr-2">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                                    <i class="fas fa-user-tie text-blue-600 mr-3"></i>Detalles del Guía
                                </h1>
                            </div>
                            <p class="text-sm lg:text-base text-gray-600">Información completa y actividad del guía</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="editar.php?id=<?php echo $guia['id_guia']; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-edit mr-2"></i>Editar Guía
                            </a>
                            <button onclick="cambiarEstado(<?php echo $guia['id_guia']; ?>, '<?php echo $guia['estado']; ?>')" 
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-sync mr-2"></i>Cambiar Estado
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

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Información del Guía -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                            <div class="text-center mb-6">
                                <?php if ($guia['foto_url']): ?>
                                    <img class="h-24 w-24 rounded-full mx-auto mb-4" src="<?php echo htmlspecialchars($guia['foto_url']); ?>" alt="">
                                <?php else: ?>
                                    <div class="h-24 w-24 rounded-full bg-blue-600 flex items-center justify-center mx-auto mb-4">
                                        <span class="text-white font-bold text-2xl">
                                            <?php echo strtoupper(substr($guia['nombre'], 0, 1) . substr($guia['apellido'], 0, 1)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <h2 class="text-xl font-bold text-gray-900">
                                    <?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>
                                </h2>
                                <p class="text-gray-600"><?php echo htmlspecialchars($guia['email']); ?></p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">ID Guía:</span>
                                    <span class="text-sm text-gray-900">#<?php echo $guia['id_guia']; ?></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Teléfono:</span>
                                    <span class="text-sm text-gray-900">
                                        <?php echo $guia['telefono'] ? htmlspecialchars($guia['telefono']) : 'No registrado'; ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Estado:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getEstadoClass($guia['estado']); ?>">
                                        <i class="<?php echo getEstadoIcon($guia['estado']); ?> mr-1"></i>
                                        <?php echo $guia['estado']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas del Guía -->
                        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Estadísticas Principales
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600"><?php echo number_format($guia['total_tours']); ?></div>
                                    <div class="text-sm text-gray-600">Tours Asignados</div>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600"><?php echo number_format($guia['tours_diarios_total']); ?></div>
                                    <div class="text-sm text-gray-600">Tours Realizados</div>
                                </div>
                                <div class="text-center p-4 bg-purple-50 rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600"><?php echo number_format($guia['tours_completados']); ?></div>
                                    <div class="text-sm text-gray-600">Completados</div>
                                </div>
                                <div class="text-center p-4 bg-orange-50 rounded-lg">
                                    <div class="text-2xl font-bold text-orange-600"><?php echo number_format($guia['tours_proximos']); ?></div>
                                    <div class="text-sm text-gray-600">Próximos</div>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas Adicionales -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-star text-yellow-600 mr-2"></i>Calificaciones y Otros
                            </h3>
                            <div class="grid grid-cols-1 gap-4">
                                <!-- Calificación -->
                                <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-star text-yellow-600 mr-3"></i>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">Calificación Promedio</div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $guia['total_calificaciones']; ?> calificación<?php echo $guia['total_calificaciones'] != 1 ? 'es' : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <?php if ($guia['total_calificaciones'] > 0): ?>
                                            <div class="text-2xl font-bold text-yellow-600">
                                                <?php echo number_format($guia['calificacion_promedio'], 1); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">/5.0</div>
                                        <?php else: ?>
                                            <div class="text-sm text-gray-400">Sin calificaciones</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Idiomas -->
                                <div class="flex items-center justify-between p-4 bg-indigo-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-globe text-indigo-600 mr-3"></i>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">Idiomas</div>
                                            <div class="text-xs text-gray-500">
                                                <?php if (count($idiomas) > 0): ?>
                                                    <?php echo implode(', ', array_column($idiomas, 'nombre_idioma')); ?>
                                                <?php else: ?>
                                                    Sin idiomas registrados
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-2xl font-bold text-indigo-600">
                                        <?php echo $guia['idiomas_count']; ?>
                                    </div>
                                </div>

                                <!-- Ingresos -->
                                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-dollar-sign text-green-600 mr-3"></i>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">Ingresos Generados</div>
                                            <div class="text-xs text-gray-500">De tours confirmados</div>
                                        </div>
                                    </div>
                                    <div class="text-2xl font-bold text-green-600">
                                        S/. <?php echo number_format($guia['ingresos_generados'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad y Detalles -->
                    <div class="lg:col-span-2">
                        <!-- Pestañas -->
                        <div class="mb-6">
                            <nav class="flex space-x-8" aria-label="Tabs">
                                <button onclick="mostrarTab('experiencia')" 
                                        class="tab-button active whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                        data-tab="experiencia">
                                    <i class="fas fa-user-graduate mr-2"></i>Experiencia
                                </button>
                                <button onclick="mostrarTab('proximos')" 
                                        class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                        data-tab="proximos">
                                    <i class="fas fa-calendar-plus mr-2"></i>Próximos Tours
                                </button>
                                <button onclick="mostrarTab('historial')" 
                                        class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                        data-tab="historial">
                                    <i class="fas fa-history mr-2"></i>Historial
                                </button>
                            </nav>
                        </div>

                        <!-- Contenido de Experiencia -->
                        <div id="tab-experiencia" class="tab-content">
                            <div class="bg-white rounded-lg shadow-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-user-graduate text-blue-600 mr-2"></i>Experiencia y Descripción
                                </h3>
                                <?php if ($guia['experiencia']): ?>
                                    <div class="prose max-w-none">
                                        <p class="text-gray-700 leading-relaxed whitespace-pre-line">
                                            <?php echo htmlspecialchars($guia['experiencia']); ?>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-user-graduate text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500">No hay información de experiencia registrada</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contenido de Próximos Tours -->
                        <div id="tab-proximos" class="tab-content hidden">
                            <div class="bg-white rounded-lg shadow-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-calendar-plus text-green-600 mr-2"></i>Próximos Tours
                                </h3>
                                <?php if (empty($proximos_tours)): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500">No hay tours programados próximamente</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($proximos_tours as $tour): ?>
                                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <h4 class="font-medium text-gray-900">
                                                            Reserva #<?php echo $tour['id_reserva']; ?>
                                                        </h4>
                                                        <p class="text-sm text-gray-600 mt-1">
                                                            Cliente: <?php echo htmlspecialchars($tour['cliente_nombre'] ?? 'Cliente eliminado'); ?>
                                                        </p>
                                                        <div class="flex items-center mt-2 space-x-4 text-sm text-gray-500">
                                                            <span>
                                                                <i class="fas fa-calendar mr-1"></i>
                                                                <?php echo formatDate($tour['fecha_tour'], 'd/m/Y'); ?>
                                                            </span>
                                                            <span>
                                                                <i class="fas fa-dollar-sign mr-1"></i>
                                                                <?php echo formatCurrency($tour['monto_total']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            <?php echo ucfirst(strtolower($tour['estado'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($proximos_tours) >= 5): ?>
                                        <div class="text-center mt-4">
                                            <a href="../reservas/index.php?guia=<?php echo $guia['id_guia']; ?>" 
                                               class="text-blue-600 hover:text-blue-800 text-sm">
                                                Ver todos los tours →
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contenido de Historial -->
                        <div id="tab-historial" class="tab-content hidden">
                            <div class="bg-white rounded-lg shadow-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-history text-gray-600 mr-2"></i>Historial de Tours
                                </h3>
                                <?php if (empty($tours)): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500">No hay tours en el historial</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($tours as $tour): ?>
                                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <h4 class="font-medium text-gray-900">
                                                            Reserva #<?php echo $tour['id_reserva']; ?>
                                                        </h4>
                                                        <p class="text-sm text-gray-600 mt-1">
                                                            Cliente: <?php echo htmlspecialchars($tour['cliente_nombre'] ?? 'Cliente eliminado'); ?>
                                                        </p>
                                                        <div class="flex items-center mt-2 space-x-4 text-sm text-gray-500">
                                                            <span>
                                                                <i class="fas fa-calendar mr-1"></i>
                                                                <?php echo formatDate($tour['fecha_tour'], 'd/m/Y'); ?>
                                                            </span>
                                                            <span>
                                                                <i class="fas fa-dollar-sign mr-1"></i>
                                                                <?php echo formatCurrency($tour['monto_total']); ?>
                                                            </span>
                                                        </div>
                                                        <?php if ($tour['observaciones']): ?>
                                                            <p class="text-sm text-gray-600 mt-2">
                                                                <i class="fas fa-comment mr-1"></i>
                                                                <?php echo htmlspecialchars($tour['observaciones']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $tour['estado_clase']; ?>">
                                                            <i class="<?php echo getEstadoTourIcon($tour['estado']); ?> mr-1"></i>
                                                            <?php echo ucfirst(strtolower($tour['estado'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($tours) >= 10): ?>
                                        <div class="text-center mt-4">
                                            <a href="../reservas/index.php?guia=<?php echo $guia['id_guia']; ?>" 
                                               class="text-blue-600 hover:text-blue-800 text-sm">
                                                Ver todo el historial →
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mostrarTab(tabName) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remover clase activa de todos los botones
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            });
            
            // Mostrar contenido seleccionado
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            // Activar botón seleccionado
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            activeButton.classList.add('active', 'border-blue-500', 'text-blue-600');
            activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        }

        function cambiarEstado(id, estadoActual) {
            const nuevoEstado = estadoActual === 'Libre' ? 'Ocupado' : 'Libre';
            if (confirm(`¿Deseas cambiar el estado del guía a "${nuevoEstado}"?`)) {
                fetch('cambiar_estado.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        id_guia: id,
                        nuevo_estado: nuevoEstado
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al cambiar estado: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar estado');
                });
            }
        }

        // Inicializar primera pestaña
        document.addEventListener('DOMContentLoaded', function() {
            mostrarTab('experiencia');
        });
    </script>
</body>
</html>
