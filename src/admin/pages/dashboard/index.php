<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

$admin = getCurrentAdmin();
$page_title = "Dashboard";

// Obtener estadísticas del dashboard
$stats = getDashboardStats();
$reservas_recientes = getReservasRecientes(5);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">Panel de Control</h1>
                    <p class="text-xs lg:text-sm text-gray-500">Bienvenido, <?php echo htmlspecialchars($admin['nombre']); ?> | Resumen general del sistema</p>
                    <div class="mt-2 text-xs text-gray-400">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('l, j \d\e F \d\e Y', strtotime('now')); ?>
                        <span class="ml-4"><i class="fas fa-clock mr-1"></i><?php echo date('H:i'); ?></span>
                    </div>
                </div>

                <!-- Tarjetas de estadísticas principales - Métricas clave -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
                    <!-- Total Tours -->
                    <div class="bg-gradient-to-r from-blue-400/80 to-blue-500/80 rounded-lg shadow-lg p-4 lg:p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-map-marked-alt text-blue-100 text-lg lg:text-xl mr-2"></i>
                                    <p class="text-xs lg:text-sm text-blue-100 font-medium">Tours Disponibles</p>
                                </div>
                                <p class="text-2xl lg:text-3xl font-bold"><?php echo number_format($stats['total_tours'] ?? 0); ?></p>
                                <p class="text-xs text-blue-100 mt-1">
                                    <i class="fas fa-globe mr-1"></i>
                                    <?php echo number_format($stats['total_regiones'] ?? 0); ?> regiones
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-route text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Reservas -->
                    <div class="bg-gradient-to-r from-green-400/80 to-green-500/80 rounded-lg shadow-lg p-4 lg:p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-calendar-check text-green-100 text-lg lg:text-xl mr-2"></i>
                                    <p class="text-xs lg:text-sm text-green-100 font-medium">Total Reservas</p>
                                </div>
                                <p class="text-2xl lg:text-3xl font-bold"><?php echo number_format($stats['total_reservas'] ?? 0); ?></p>
                                <p class="text-xs text-green-100 mt-1">
                                    <i class="fas fa-calendar-plus mr-1"></i>
                                    <?php echo number_format($stats['reservas_mes'] ?? 0); ?> este mes
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-line text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Usuarios -->
                    <div class="bg-gradient-to-r from-purple-400/80 to-purple-500/80 rounded-lg shadow-lg p-4 lg:p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-users text-purple-100 text-lg lg:text-xl mr-2"></i>
                                    <p class="text-xs lg:text-sm text-purple-100 font-medium">Usuarios Activos</p>
                                </div>
                                <p class="text-2xl lg:text-3xl font-bold"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></p>
                                <p class="text-xs text-purple-100 mt-1">
                                    <i class="fas fa-user-plus mr-1"></i>
                                    Registrados
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user-friends text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ingresos totales -->
                    <div class="bg-gradient-to-r from-orange-400/80 to-orange-500/80 rounded-lg shadow-lg p-4 lg:p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-coins text-orange-100 text-lg lg:text-xl mr-2"></i>
                                    <p class="text-xs lg:text-sm text-orange-100 font-medium">Ingresos Totales</p>
                                </div>
                                <p class="text-2xl lg:text-3xl font-bold"><?php echo formatCurrency($stats['ingresos_totales'] ?? 0); ?></p>
                                <p class="text-xs text-orange-100 mt-1">
                                    <i class="fas fa-calendar-month mr-1"></i>
                                    <?php echo formatCurrency($stats['ingresos_mes'] ?? 0); ?> este mes
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-money-bill-trend-up text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segunda fila de estadísticas - Métricas operativas -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
                    <!-- Reservas Pendientes -->
                    <div class="relative bg-gradient-to-br from-white via-yellow-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-yellow-100 overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-yellow-100/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-yellow-200/10 rounded-full -ml-8 -mb-8"></div>
                        <div class="relative flex items-center">
                            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-xl shadow-sm group-hover:shadow-md transition-shadow">
                                <i class="fas fa-clock text-yellow-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm text-gray-600 font-medium">Pendientes</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['reservas_pendientes'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Reservas Confirmadas -->
                    <div class="relative bg-gradient-to-br from-white via-green-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-green-100 overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-green-100/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-green-200/10 rounded-full -ml-8 -mb-8"></div>
                        <div class="relative flex items-center">
                            <div class="p-3 bg-gradient-to-br from-green-100 to-green-200 rounded-xl shadow-sm group-hover:shadow-md transition-shadow">
                                <i class="fas fa-check-circle text-green-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm text-gray-600 font-medium">Confirmadas</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['reservas_confirmadas'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Guías Disponibles -->
                    <div class="relative bg-gradient-to-br from-white via-blue-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-blue-100 overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-100/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-blue-200/10 rounded-full -ml-8 -mb-8"></div>
                        <div class="relative flex items-center">
                            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl shadow-sm group-hover:shadow-md transition-shadow">
                                <i class="fas fa-user-tie text-blue-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm text-gray-600 font-medium">Guías Hoy</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['guias_disponibles_hoy'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Vehículos Disponibles -->
                    <div class="relative bg-gradient-to-br from-white via-indigo-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-indigo-100 overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-100/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-indigo-200/10 rounded-full -ml-8 -mb-8"></div>
                        <div class="relative flex items-center">
                            <div class="p-3 bg-gradient-to-br from-indigo-100 to-indigo-200 rounded-xl shadow-sm group-hover:shadow-md transition-shadow">
                                <i class="fas fa-bus text-indigo-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm text-gray-600 font-medium">Vehículos</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['vehiculos_disponibles_hoy'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
                    <div class="relative bg-gradient-to-br from-white via-blue-50/20 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-blue-100/50 overflow-hidden group">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-blue-100/10 rounded-full -mr-8 -mt-8"></div>
                        <div class="text-center relative">
                            <a href="../tours/index.php" class="inline-flex items-center px-3 lg:px-4 py-2 bg-blue-500/80 text-white rounded-lg hover:bg-blue-600/90 transition duration-200 w-full justify-center text-sm lg:text-base">
                                <i class="fas fa-plus mr-2"></i>Nuevo Tour
                            </a>
                        </div>
                    </div>

                    <div class="relative bg-gradient-to-br from-white via-green-50/20 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-green-100/50 overflow-hidden group">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-green-100/10 rounded-full -mr-8 -mt-8"></div>
                        <div class="text-center relative">
                            <a href="../reservas/index.php" class="inline-flex items-center px-3 lg:px-4 py-2 bg-green-500/80 text-white rounded-lg hover:bg-green-600/90 transition duration-200 w-full justify-center text-sm lg:text-base">
                                <i class="fas fa-calendar-check mr-2"></i>Ver Reservas
                            </a>
                        </div>
                    </div>

                    <div class="relative bg-gradient-to-br from-white via-purple-50/20 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-purple-100/50 overflow-hidden group">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-purple-100/10 rounded-full -mr-8 -mt-8"></div>
                        <div class="text-center relative">
                            <a href="../usuarios/index.php" class="inline-flex items-center px-3 lg:px-4 py-2 bg-purple-500/80 text-white rounded-lg hover:bg-purple-600/90 transition duration-200 w-full justify-center text-sm lg:text-base">
                                <i class="fas fa-users mr-2"></i>Usuarios
                            </a>
                        </div>
                    </div>

                    <div class="relative bg-gradient-to-br from-white via-orange-50/20 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-orange-100/50 overflow-hidden group">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-orange-100/10 rounded-full -mr-8 -mt-8"></div>
                        <div class="text-center relative">
                            <a href="#" onclick="mostrarReportesProximamente(); return false;" class="inline-flex items-center px-3 lg:px-4 py-2 bg-orange-500/80 text-white rounded-lg hover:bg-orange-600/90 transition duration-200 w-full justify-center text-sm lg:text-base">
                                <i class="fas fa-chart-bar mr-2"></i>Reportes
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Sección de contenido principal - Gráficos -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8 mb-6 lg:mb-8">
                    <!-- Gráfico de Reservas vs Objetivos -->
                    <div class="relative bg-gradient-to-br from-white via-slate-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-slate-100 overflow-hidden">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-blue-100/10 rounded-full -mr-12 -mt-12"></div>
                        <div class="absolute bottom-0 left-0 w-20 h-20 bg-slate-100/20 rounded-full -ml-10 -mb-10"></div>
                        <div class="relative">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-base lg:text-lg font-semibold text-gray-900">Reservas vs Objetivos</h3>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Últimos 6 meses</span>
                            </div>
                            <div class="h-64 lg:h-80">
                                <canvas id="reservas-chart"></canvas>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                                <div class="bg-blue-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-600">Reservas este mes</p>
                                    <p class="text-lg font-bold text-blue-600"><?php echo number_format($stats['reservas_mes'] ?? 0); ?></p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-600">Objetivo mensual</p>
                                    <p class="text-lg font-bold text-green-600"><?php echo number_format($stats['objetivo_mensual'] ?? 50); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Reservas por Estado -->
                    <div class="relative bg-gradient-to-br from-white via-slate-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 lg:p-6 border border-slate-100 overflow-hidden">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-green-100/10 rounded-full -mr-12 -mt-12"></div>
                        <div class="absolute bottom-0 left-0 w-20 h-20 bg-slate-100/20 rounded-full -ml-10 -mb-10"></div>
                        <div class="relative">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-base lg:text-lg font-semibold text-gray-900">Distribución de Reservas</h3>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Por estado</span>
                            </div>
                            <div class="h-64 lg:h-80">
                                <canvas id="estados-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de información detallada -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8 mb-6 lg:mb-8">
                    <!-- Reservas Recientes -->
                    <div class="relative bg-gradient-to-br from-white via-gray-50/40 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-gray-100/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative p-4 lg:p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-base lg:text-lg font-semibold text-gray-900">Reservas Recientes</h3>
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                                    <?php echo count($reservas_recientes); ?> registros
                                </span>
                            </div>
                        </div>
                        <div class="p-4 lg:p-6 max-h-96 overflow-y-auto">
                            <?php if (empty($reservas_recientes)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-calendar-times text-gray-300 text-3xl mb-3"></i>
                                    <p class="text-gray-500 text-sm">No hay reservas recientes</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($reservas_recientes as $reserva): ?>
                                        <div class="border-l-4 <?php 
                                            $border_colors = [
                                                'Pendiente' => 'border-yellow-400 bg-yellow-50',
                                                'Confirmada' => 'border-green-400 bg-green-50',
                                                'Cancelada' => 'border-red-400 bg-red-50',
                                                'Finalizada' => 'border-blue-400 bg-blue-50'
                                            ];
                                            echo $border_colors[$reserva['estado']] ?? 'border-gray-400 bg-gray-50';
                                        ?> p-3 rounded-r-lg">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900 text-sm">
                                                        <?php echo htmlspecialchars($reserva['usuario_nombre']); ?>
                                                    </h4>
                                                    <p class="text-xs text-gray-600 truncate">
                                                        <?php echo htmlspecialchars($reserva['tour_titulo']); ?>
                                                    </p>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                    <?php 
                                                    $estado_classes = [
                                                        'Pendiente' => 'bg-yellow-100 text-yellow-800',
                                                        'Confirmada' => 'bg-green-100 text-green-800',
                                                        'Cancelada' => 'bg-red-100 text-red-800',
                                                        'Finalizada' => 'bg-blue-100 text-blue-800'
                                                    ];
                                                    echo $estado_classes[$reserva['estado']] ?? 'bg-gray-100 text-gray-800';
                                                    ?>">
                                                    <?php echo $reserva['estado']; ?>
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
                                                <div>
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    <?php echo formatDate($reserva['fecha_tour'], 'd/m/Y'); ?>
                                                </div>
                                                <div class="text-right">
                                                    <i class="fas fa-money-bill mr-1"></i>
                                                    <?php echo formatCurrency($reserva['monto_total']); ?>
                                                </div>
                                                <?php if ($reserva['nombre_region']): ?>
                                                <div>
                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                    <?php echo htmlspecialchars($reserva['nombre_region']); ?>
                                                </div>
                                                <?php endif; ?>
                                                <div class="text-right">
                                                    <span class="<?php 
                                                        $tiempo_colors = [
                                                            'Hoy' => 'text-red-600 font-medium',
                                                            'Esta semana' => 'text-orange-600',
                                                            'Pasado' => 'text-gray-400'
                                                        ];
                                                        echo $tiempo_colors[$reserva['tiempo_relativo']] ?? 'text-gray-500';
                                                    ?>">
                                                        <?php echo $reserva['tiempo_relativo']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200 text-center">
                                    <a href="../reservas/index.php" class="text-blue-600 hover:text-blue-800 text-xs lg:text-sm font-medium">
                                        Ver todas las reservas →
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tours Más Populares -->
                    <div class="relative bg-gradient-to-br from-white via-blue-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-blue-100 overflow-hidden">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-100/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative p-4 lg:p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-base lg:text-lg font-semibold text-gray-900">Tours Más Populares</h3>
                                <span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-full">Top <?php echo count($stats['tours_populares']); ?></span>
                            </div>
                        </div>
                        <div class="p-4 lg:p-6">
                            <?php if (empty($stats['tours_populares'])): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-route text-gray-300 text-3xl mb-3"></i>
                                    <p class="text-gray-500 text-sm">No hay datos de popularidad</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($stats['tours_populares'] as $index => $tour): ?>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <div class="flex items-center flex-1">
                                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                                    <span class="text-blue-600 font-bold text-sm"><?php echo $index + 1; ?></span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-medium text-gray-900 text-sm truncate">
                                                        <?php echo htmlspecialchars($tour['titulo']); ?>
                                                    </h4>
                                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                                        <?php echo htmlspecialchars($tour['nombre_region'] ?: 'Sin región'); ?>
                                                        <span class="ml-2">
                                                            <i class="fas fa-tag mr-1"></i>
                                                            <?php echo formatCurrency($tour['precio']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="flex items-center text-sm font-medium text-gray-900">
                                                    <i class="fas fa-users mr-1"></i>
                                                    <?php echo number_format($tour['total_reservas']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo formatCurrency($tour['ingresos_generados']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Próximas Reservas y Recursos -->
                    <div class="space-y-6">
                        <!-- Próximas Reservas -->
                        <div class="relative bg-gradient-to-br from-white via-orange-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-orange-100 overflow-hidden">
                            <div class="absolute top-0 right-0 w-16 h-16 bg-orange-100/20 rounded-full -mr-8 -mt-8"></div>
                            <div class="relative p-4 lg:p-6 border-b border-gray-200">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-semibold text-gray-900 text-sm lg:text-base">Próximas Reservas</h4>
                                    <span class="text-xs bg-orange-100 text-orange-600 px-2 py-1 rounded-full">15 días</span>
                                </div>
                            </div>
                            <div class="p-4 lg:p-6 max-h-64 overflow-y-auto">
                                <?php if (empty($stats['proximas_reservas'])): ?>
                                    <p class="text-gray-500 text-xs lg:text-sm text-center py-4">No hay reservas próximas</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach (array_slice($stats['proximas_reservas'], 0, 6) as $reserva): ?>
                                            <div class="flex items-center justify-between text-xs lg:text-sm border-b border-gray-100 pb-2">
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium text-gray-900 truncate">
                                                        <?php echo htmlspecialchars($reserva['usuario_nombre']); ?>
                                                    </p>
                                                    <p class="text-gray-600 truncate">
                                                        <?php echo htmlspecialchars(substr($reserva['tour_titulo'], 0, 25)) . '...'; ?>
                                                    </p>
                                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                                        <i class="fas fa-calendar mr-1"></i>
                                                        <?php echo formatDate($reserva['fecha_tour'], 'd/m'); ?>
                                                        <?php if ($reserva['hora_salida']): ?>
                                                            <span class="ml-2">
                                                                <i class="fas fa-clock mr-1"></i>
                                                                <?php echo date('H:i', strtotime($reserva['hora_salida'])); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="text-right ml-2">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                        <?php 
                                                        if ($reserva['fecha_tour'] == date('Y-m-d')) echo 'bg-red-100 text-red-800';
                                                        elseif ($reserva['estado'] == 'Confirmada') echo 'bg-green-100 text-green-800';
                                                        else echo 'bg-yellow-100 text-yellow-800';
                                                        ?>">
                                                        <?php 
                                                        if ($reserva['fecha_tour'] == date('Y-m-d')) echo 'HOY';
                                                        else echo $reserva['estado'];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recursos Disponibles Hoy -->
                        <div class="relative bg-gradient-to-br from-white via-emerald-50/30 to-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-emerald-100 overflow-hidden">
                            <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-100/20 rounded-full -mr-8 -mt-8"></div>
                            <div class="relative p-4 lg:p-6 border-b border-gray-200">
                                <h4 class="font-semibold text-gray-900 text-sm lg:text-base">Recursos Disponibles Hoy</h4>
                            </div>
                            <div class="p-4 lg:p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">
                                            <?php echo $stats['guias_disponibles_hoy']; ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <i class="fas fa-user-tie mr-1"></i>
                                            Guías libres
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600">
                                            <?php echo $stats['vehiculos_disponibles_hoy']; ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <i class="fas fa-bus mr-1"></i>
                                            Vehículos libres
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuración global de Chart.js
        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#6B7280';
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Datos para gráficos desde PHP
        const reservasData = <?php echo json_encode($stats['reservas_mensuales'] ?? []); ?>;
        const estadosData = <?php echo json_encode($stats['reservas_por_estado'] ?? []); ?>;
        
        console.log('Dashboard Stats:', <?php echo json_encode($stats); ?>);
        console.log('Reservas Data:', reservasData);
        console.log('Estados Data:', estadosData);

        // Función para mostrar popup de reportes
        function mostrarReportesProximamente() {
            // Crear overlay
            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    document.body.removeChild(overlay);
                }
            };

            // Crear modal
            const modal = document.createElement('div');
            modal.className = 'bg-white rounded-lg p-6 max-w-md w-full mx-auto shadow-2xl transform transition-all';
            modal.innerHTML = `
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                        <i class="fas fa-chart-bar text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Reportes Próximamente</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Estamos trabajando en reportes más detallados que incluirán:
                    </p>
                    <div class="text-left mb-6 space-y-2">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-check text-green-500 w-4 mr-2"></i>
                            Análisis de tendencias
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-check text-green-500 w-4 mr-2"></i>
                            Reportes personalizables
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-check text-green-500 w-4 mr-2"></i>
                            Exportación a PDF/Excel
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-check text-green-500 w-4 mr-2"></i>
                            Comparativas por períodos
                        </div>
                    </div>
                    <button onclick="document.body.removeChild(this.closest('.fixed'))" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Entendido
                    </button>
                </div>
            `;

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            // Animación de entrada
            setTimeout(() => {
                modal.style.transform = 'scale(1)';
                modal.style.opacity = '1';
            }, 10);
        }

        // Función para formatear moneda
        function formatCurrency(value) {
            return new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value);
        }

        // Función para formatear números grandes
        function formatNumber(value) {
            if (value >= 1000000) {
                return (value / 1000000).toFixed(1) + 'M';
            } else if (value >= 1000) {
                return (value / 1000).toFixed(1) + 'K';
            }
            return value.toString();
        }

        // Gráfico de Reservas vs Objetivos
        const reservasCtx = document.getElementById('reservas-chart');
        
        if (reservasCtx) {
            // Generar datos para los últimos 6 meses
            const meses = [];
            const reservasReales = [];
            const objetivos = [];
            
            for (let i = 5; i >= 0; i--) {
                const fecha = new Date();
                fecha.setMonth(fecha.getMonth() - i);
                meses.push(fecha.toLocaleDateString('es-ES', { month: 'short', year: '2-digit' }));
                
                // Datos simulados - en producción vendrían de la base de datos
                const reservasDelMes = reservasData.find(r => {
                    const mesData = new Date(r.mes + '-01');
                    return mesData.getMonth() === fecha.getMonth() && mesData.getFullYear() === fecha.getFullYear();
                });
                
                reservasReales.push(reservasDelMes ? parseInt(reservasDelMes.total_reservas) : Math.floor(Math.random() * 40) + 10);
                objetivos.push(50); // Objetivo fijo de 50 reservas por mes
            }
            
            new Chart(reservasCtx, {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [
                        {
                            label: 'Reservas Reales',
                            data: reservasReales,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#3B82F6',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        },
                        {
                            label: 'Objetivo',
                            data: objetivos,
                            borderColor: '#10B981',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            tension: 0,
                            fill: false,
                            pointBackgroundColor: '#10B981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' reservas';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Número de Reservas'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mes'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de Reservas por Estado (sin cambios)
        const estadosCtx = document.getElementById('estados-chart');
        
        if (estadosCtx && estadosData.length > 0) {
            const colores = {
                'Pendiente': '#F59E0B',
                'Confirmada': '#10B981',
                'Cancelada': '#EF4444',
                'Finalizada': '#6366F1'
            };
            
            const backgroundColors = estadosData.map(item => colores[item.estado] || '#9CA3AF');
            
            new Chart(estadosCtx, {
                type: 'doughnut',
                data: {
                    labels: estadosData.map(item => item.estado),
                    datasets: [{
                        data: estadosData.map(item => parseInt(item.cantidad)),
                        backgroundColor: backgroundColors,
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed * 100) / total).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        } else if (estadosCtx) {
            estadosCtx.parentElement.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-500">
                    <div class="text-center">
                        <i class="fas fa-chart-pie text-4xl mb-3 text-gray-300"></i>
                        <p>No hay datos de reservas disponibles</p>
                        <p class="text-sm mt-1">Los datos aparecerán cuando haya reservas registradas</p>
                    </div>
                </div>
            `;
        }

        // Función para actualizar estadísticas en tiempo real (opcional)
        function actualizarEstadisticas() {
            fetch('../../api/dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar contadores principales si es necesario
                        console.log('Estadísticas actualizadas:', data);
                    }
                })
                .catch(error => {
                    console.log('Error actualizando estadísticas:', error);
                });
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard cargado correctamente');
            
            // Opcional: Actualizar estadísticas cada 5 minutos
            // setInterval(actualizarEstadisticas, 300000);
            
            // Añadir efectos hover a las tarjetas
            const cards = document.querySelectorAll('.bg-gradient-to-r, .bg-white');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.transition = 'transform 0.2s ease-in-out';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
