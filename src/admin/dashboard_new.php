<?php
// Verificar autenticación
require_once __DIR__ . '/auth/middleware.php';
verificarSesionAdmin();

// Funciones del dashboard
require_once __DIR__ . '/functions/admin_functions.php';

// Obtener datos reales del dashboard
$estadisticas = obtenerEstadisticasDashboard();
$reservas_recientes = obtenerReservasRecientes(5);
$tours_populares = obtenerToursPopulares(5);
$datos_graficos = obtenerDatosGraficos();

$stats = $estadisticas['success'] ? $estadisticas['data'] : [
    'total_usuarios' => 0,
    'total_tours' => 0,
    'total_reservas' => 0,
    'reservas_pendientes' => 0,
    'reservas_confirmadas' => 0,
    'ingresos_mes' => 0,
    'tours_hoy' => 0,
    'guias_disponibles' => 0
];

$reservas = $reservas_recientes['success'] ? $reservas_recientes['data'] : [];
$tours = $tours_populares['success'] ? $tours_populares['data'] : [];
$graficos = $datos_graficos['success'] ? $datos_graficos['data'] : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Antares Travel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include __DIR__ . '/components/sidebar.php'; ?>

        <!-- Contenido principal -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include __DIR__ . '/components/header.php'; ?>

            <!-- Main content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <!-- Título -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-gray-600">Resumen general del sistema</p>
                </div>

                <!-- Tarjetas de estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Usuarios -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Usuarios</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_usuarios']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Tours -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-map-marked-alt text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Tours Activos</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_tours']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Reservas -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-calendar-check text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Reservas</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_reservas']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Ingresos del mes -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-dollar-sign text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Ingresos Mes</p>
                                <p class="text-2xl font-bold text-gray-900">S/. <?php echo number_format($stats['ingresos_mes'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segunda fila de estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Reservas Pendientes -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pendientes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['reservas_pendientes']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Reservas Confirmadas -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-600 rounded-md flex items-center justify-center">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Confirmadas</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['reservas_confirmadas']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Tours Hoy -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-calendar-day text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Tours Hoy</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['tours_hoy']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Guías Disponibles -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-user-tie text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Guías Libres</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['guias_disponibles']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos y contenido principal -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Gráfico de reservas -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reservas por Estado</h3>
                        <canvas id="reservasChart" width="400" height="200"></canvas>
                    </div>

                    <!-- Gráfico de ingresos -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ingresos Últimos 6 Meses</h3>
                        <canvas id="ingresosChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Tablas de datos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Reservas recientes -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Reservas Recientes</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tour</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($reservas)): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                                No hay reservas recientes
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reservas as $reserva): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($reserva['usuario_nombre'] ?? 'Cliente directo'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($reserva['tour_titulo']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    S/. <?php echo number_format($reserva['monto_total'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php
                                                    $color = '';
                                                    switch ($reserva['estado']) {
                                                        case 'Pendiente':
                                                            $color = 'bg-yellow-100 text-yellow-800';
                                                            break;
                                                        case 'Confirmada':
                                                            $color = 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'Cancelada':
                                                            $color = 'bg-red-100 text-red-800';
                                                            break;
                                                        case 'Finalizada':
                                                            $color = 'bg-blue-100 text-blue-800';
                                                            break;
                                                        default:
                                                            $color = 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $color; ?>">
                                                        <?php echo htmlspecialchars($reserva['estado']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            <a href="pages/reservas/index.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Ver todas las reservas →
                            </a>
                        </div>
                    </div>

                    <!-- Tours populares -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Tours Populares</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tour</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reservas</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($tours)): ?>
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                                No hay datos de tours
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tours as $tour): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($tour['titulo']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo number_format($tour['total_reservas']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    S/. <?php echo number_format($tour['ingresos_totales'] ?? 0, 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            <a href="pages/tours/index.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Ver todos los tours →
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Gráfico de reservas por estado
        const reservasCtx = document.getElementById('reservasChart').getContext('2d');
        const reservasChart = new Chart(reservasCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pendiente', 'Confirmada', 'Cancelada', 'Finalizada'],
                datasets: [{
                    data: [
                        <?php echo $stats['reservas_pendientes']; ?>,
                        <?php echo $stats['reservas_confirmadas']; ?>,
                        0, // Canceladas - agregar si es necesario
                        0  // Finalizadas - agregar si es necesario
                    ],
                    backgroundColor: [
                        '#FBB040',
                        '#10B981',
                        '#EF4444',
                        '#3B82F6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de ingresos (datos de ejemplo por ahora)
        const ingresosCtx = document.getElementById('ingresosChart').getContext('2d');
        const ingresosChart = new Chart(ingresosCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Ingresos (S/.)',
                    data: [12000, 15000, 18000, 14000, 20000, <?php echo $stats['ingresos_mes']; ?>],
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'S/. ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
