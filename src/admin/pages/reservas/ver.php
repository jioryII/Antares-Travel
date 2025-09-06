<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID de la reserva
$id_reserva = intval($_GET['id'] ?? 0);

if (!$id_reserva) {
    header('Location: index.php');
    exit;
}

try {
    $connection = getConnection();
    
    // Obtener datos de la reserva
    $sql = "SELECT 
                r.id_reserva,
                r.fecha_reserva,
                r.fecha_tour,
                r.monto_total,
                r.estado,
                r.observaciones,
                r.origen_reserva,
                t.titulo as tour_titulo,
                t.descripcion as tour_descripcion,
                t.precio as tour_precio,
                t.duracion as tour_duracion,
                t.lugar_salida,
                t.lugar_llegada,
                t.hora_salida,
                t.hora_llegada,
                u.nombre as usuario_nombre,
                u.email as usuario_email,
                u.telefono as usuario_telefono,
                g.nombre as guia_nombre,
                g.apellido as guia_apellido,
                g.telefono as guia_telefono,
                g.email as guia_email,
                reg.nombre_region
            FROM reservas r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            INNER JOIN tours t ON r.id_tour = t.id_tour
            LEFT JOIN guias g ON t.id_guia = g.id_guia
            LEFT JOIN regiones reg ON t.id_region = reg.id_region
            WHERE r.id_reserva = ?";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        header('Location: index.php');
        exit;
    }
    
    // Obtener pasajeros
    $pasajeros_sql = "SELECT * FROM pasajeros WHERE id_reserva = ? ORDER BY tipo_pasajero, nombre";
    $pasajeros_stmt = $connection->prepare($pasajeros_sql);
    $pasajeros_stmt->execute([$id_reserva]);
    $pasajeros = $pasajeros_stmt->fetchAll();
    
    // Obtener pagos
    $pagos_sql = "SELECT * FROM pagos WHERE id_reserva = ? ORDER BY fecha_pago DESC";
    $pagos_stmt = $connection->prepare($pagos_sql);
    $pagos_stmt->execute([$id_reserva]);
    $pagos = $pagos_stmt->fetchAll();
    
    $page_title = "Reserva #" . $reserva['id_reserva'];
    
} catch (Exception $e) {
    error_log("Error al obtener reserva: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Función para obtener clase CSS del estado
function getEstadoClass($estado) {
    $classes = [
        'Pendiente' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'Confirmada' => 'bg-green-100 text-green-800 border-green-200',
        'Cancelada' => 'bg-red-100 text-red-800 border-red-200',
        'Finalizada' => 'bg-blue-100 text-blue-800 border-blue-200'
    ];
    return $classes[$estado] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}

function getEstadoIcon($estado) {
    $icons = [
        'Pendiente' => 'fas fa-clock',
        'Confirmada' => 'fas fa-check-circle',
        'Cancelada' => 'fas fa-times-circle',
        'Finalizada' => 'fas fa-flag-checkered'
    ];
    return $icons[$estado] ?? 'fas fa-question-circle';
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
        .detail-card {
            transition: all 0.3s ease;
        }
        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .status-badge {
            border: 2px solid;
        }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal none -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-6 lg:mb-8">
                    <br><br><br>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <nav class="flex mb-3" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    <li><a href="index.php" class="text-blue-600 hover:text-blue-800">Reservas</a></li>
                                    <li><span class="text-gray-500">/</span></li>
                                    <li><span class="text-gray-500">Reserva #<?php echo $reserva['id_reserva']; ?></span></li>
                                </ol>
                            </nav>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-receipt text-blue-600 mr-3"></i>Reserva #<?php echo $reserva['id_reserva']; ?>
                            </h1>
                            <div class="flex items-center space-x-4">
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo getEstadoClass($reserva['estado']); ?>">
                                    <i class="<?php echo getEstadoIcon($reserva['estado']); ?> mr-2"></i>
                                    <?php echo $reserva['estado']; ?>
                                </span>
                                <span class="text-sm text-gray-500">
                                    Creada el <?php echo formatDate($reserva['fecha_reserva'], 'd/m/Y H:i'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="no-print flex flex-col sm:flex-row gap-2">
                            <a href="editar.php?id=<?php echo $reserva['id_reserva']; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-edit mr-2"></i>Editar
                            </a>
                            <button onclick="window.print()" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-print mr-2"></i>Imprimir
                            </button>
                            <button onclick="cambiarEstado()" 
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-exchange-alt mr-2"></i>Cambiar Estado
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grid Principal -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
                    <!-- Columna Principal -->
                    <div class="xl:col-span-2 space-y-6">
                        <!-- Información del Cliente -->
                        <div class="detail-card bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                                <h3 class="text-lg font-semibold text-white flex items-center">
                                    <i class="fas fa-user mr-3"></i>Información del Cliente
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                                        <i class="fas fa-user text-white text-2xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($reserva['usuario_nombre']); ?></h4>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($reserva['usuario_email']); ?></p>
                                        <?php if ($reserva['usuario_telefono']): ?>
                                            <p class="text-gray-600">
                                                <i class="fas fa-phone mr-1"></i>
                                                <?php echo htmlspecialchars($reserva['usuario_telefono']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Origen de Reserva</label>
                                        <p class="mt-1 text-gray-900"><?php echo $reserva['origen_reserva']; ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Total Pasajeros</label>
                                        <p class="mt-1 text-gray-900"><?php echo count($pasajeros); ?> personas</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Tour -->
                        <div class="detail-card bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                                <h3 class="text-lg font-semibold text-white flex items-center">
                                    <i class="fas fa-map-marked-alt mr-3"></i>Información del Tour
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="mb-4">
                                    <h4 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($reserva['tour_titulo']); ?></h4>
                                    <?php if ($reserva['tour_descripcion']): ?>
                                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($reserva['tour_descripcion']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fecha del Tour</label>
                                        <p class="mt-1 text-lg font-semibold text-gray-900">
                                            <i class="fas fa-calendar-day mr-2 text-green-600"></i>
                                            <?php echo formatDate($reserva['fecha_tour'], 'd/m/Y'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Duración</label>
                                        <p class="mt-1 text-gray-900">
                                            <i class="fas fa-clock mr-2 text-green-600"></i>
                                            <?php echo htmlspecialchars($reserva['tour_duracion']); ?>
                                        </p>
                                    </div>
                                    <?php if ($reserva['lugar_salida']): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Lugar de Salida</label>
                                        <p class="mt-1 text-gray-900">
                                            <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>
                                            <?php echo htmlspecialchars($reserva['lugar_salida']); ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($reserva['hora_salida']): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Hora de Salida</label>
                                        <p class="mt-1 text-gray-900">
                                            <i class="fas fa-clock mr-2 text-green-600"></i>
                                            <?php echo date('H:i', strtotime($reserva['hora_salida'])); ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($reserva['nombre_region']): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Región</label>
                                        <p class="mt-1 text-gray-900">
                                            <i class="fas fa-map mr-2 text-green-600"></i>
                                            <?php echo htmlspecialchars($reserva['nombre_region']); ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Precio Base</label>
                                        <p class="mt-1 text-gray-900">
                                            <i class="fas fa-tag mr-2 text-green-600"></i>
                                            <?php echo formatCurrency($reserva['tour_precio']); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($reserva['guia_nombre']): ?>
                                    <div class="mt-6 pt-6 border-t">
                                        <h5 class="text-sm font-medium text-gray-700 mb-3">Guía Asignado</h5>
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                                <i class="fas fa-user-tie text-white"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($reserva['guia_nombre'] . ' ' . $reserva['guia_apellido']); ?>
                                                </p>
                                                <?php if ($reserva['guia_telefono']): ?>
                                                    <p class="text-sm text-gray-600">
                                                        <i class="fas fa-phone mr-1"></i>
                                                        <?php echo htmlspecialchars($reserva['guia_telefono']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Lista de Pasajeros -->
                        <div class="detail-card bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                                <h3 class="text-lg font-semibold text-white flex items-center">
                                    <i class="fas fa-users mr-3"></i>Pasajeros (<?php echo count($pasajeros); ?>)
                                </h3>
                            </div>
                            <div class="p-6">
                                <?php if (empty($pasajeros)): ?>
                                    <p class="text-gray-500 text-center py-4">No hay pasajeros registrados</p>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($pasajeros as $pasajero): ?>
                                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center mr-4">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($pasajero['nombre'] . ' ' . $pasajero['apellido']); ?>
                                                        </h4>
                                                        <p class="text-sm text-gray-600">
                                                            <?php echo htmlspecialchars($pasajero['dni_pasaporte']); ?>
                                                            <?php if ($pasajero['nacionalidad']): ?>
                                                                • <?php echo htmlspecialchars($pasajero['nacionalidad']); ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        <?php 
                                                        echo $pasajero['tipo_pasajero'] === 'Adulto' ? 'bg-blue-100 text-blue-800' :
                                                             ($pasajero['tipo_pasajero'] === 'Niño' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800');
                                                        ?>">
                                                        <?php echo $pasajero['tipo_pasajero']; ?>
                                                    </span>
                                                    <?php if ($pasajero['telefono']): ?>
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            <i class="fas fa-phone mr-1"></i>
                                                            <?php echo htmlspecialchars($pasajero['telefono']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Lateral -->
                    <div class="space-y-6">
                        <!-- Resumen Financiero -->
                        <div class="detail-card bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-4">
                                <h3 class="text-lg font-semibold text-white flex items-center">
                                    <i class="fas fa-dollar-sign mr-3"></i>Resumen Financiero
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Precio Base del Tour:</span>
                                        <span class="font-medium"><?php echo formatCurrency($reserva['tour_precio']); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Número de Pasajeros:</span>
                                        <span class="font-medium"><?php echo count($pasajeros); ?></span>
                                    </div>
                                    <div class="border-t pt-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-lg font-semibold text-gray-900">Total:</span>
                                            <span class="text-2xl font-bold text-orange-600"><?php echo formatCurrency($reserva['monto_total']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado de Pagos -->
                                <div class="mt-6 pt-6 border-t">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Estado de Pagos</h4>
                                    <?php if (empty($pagos)): ?>
                                        <p class="text-gray-500 text-sm">No hay pagos registrados</p>
                                    <?php else: ?>
                                        <?php 
                                        $total_pagado = array_sum(array_column($pagos, 'monto'));
                                        $saldo_pendiente = $reserva['monto_total'] - $total_pagado;
                                        ?>
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Total Pagado:</span>
                                                <span class="font-medium text-green-600"><?php echo formatCurrency($total_pagado); ?></span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Saldo Pendiente:</span>
                                                <span class="font-medium <?php echo $saldo_pendiente > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                                    <?php echo formatCurrency($saldo_pendiente); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Barra de progreso -->
                                        <div class="mt-4">
                                            <?php $porcentaje_pagado = ($total_pagado / $reserva['monto_total']) * 100; ?>
                                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                <span>Progreso de Pago</span>
                                                <span><?php echo number_format($porcentaje_pagado, 1); ?>%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-green-600 h-2 rounded-full transition-all duration-300" 
                                                     style="width: <?php echo $porcentaje_pagado; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de Pagos -->
                        <?php if (!empty($pagos)): ?>
                        <div class="detail-card bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                                <h3 class="text-lg font-semibold text-white flex items-center">
                                    <i class="fas fa-credit-card mr-3"></i>Historial de Pagos
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-3">
                                    <?php foreach ($pagos as $pago): ?>
                                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                            <div>
                                                <p class="font-medium text-gray-900"><?php echo formatCurrency($pago['monto']); ?></p>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo formatDate($pago['fecha_pago'], 'd/m/Y H:i'); ?>
                                                </p>
                                                <p class="text-xs text-gray-500"><?php echo $pago['metodo_pago']; ?></p>
                                            </div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php 
                                                echo $pago['estado_pago'] === 'Pagado' ? 'bg-green-100 text-green-800' :
                                                     ($pago['estado_pago'] === 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                                ?>">
                                                <?php echo $pago['estado_pago']; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Observaciones -->
                        <?php if ($reserva['observaciones']): ?>
                        <div class="detail-card bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4">
                                <h3 class="text-lg font-semibold text-white flex items-center">
                                    <i class="fas fa-sticky-note mr-3"></i>Observaciones
                                </h3>
                            </div>
                            <div class="p-6">
                                <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($reserva['observaciones']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar estado -->
    <div id="modalCambiarEstado" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Cambiar Estado de Reserva</h3>
                <form id="formCambiarEstado">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estado</label>
                        <select id="nuevoEstado" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Pendiente" <?php echo $reserva['estado'] === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Confirmada" <?php echo $reserva['estado'] === 'Confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                            <option value="Cancelada" <?php echo $reserva['estado'] === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            <option value="Finalizada" <?php echo $reserva['estado'] === 'Finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones (opcional)</label>
                        <textarea id="observacionesCambio" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Motivo del cambio de estado..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="cerrarModalEstado()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Actualizar Estado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function cambiarEstado() {
            document.getElementById('modalCambiarEstado').classList.remove('hidden');
        }

        function cerrarModalEstado() {
            document.getElementById('modalCambiarEstado').classList.add('hidden');
        }

        document.getElementById('formCambiarEstado').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const nuevoEstado = document.getElementById('nuevoEstado').value;
            const observaciones = document.getElementById('observacionesCambio').value;
            
            fetch('cambiar_estado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_reserva: <?php echo $reserva['id_reserva']; ?>,
                    nuevo_estado: nuevoEstado,
                    observaciones: observaciones
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al cambiar el estado: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cambiar el estado');
            });
            
            cerrarModalEstado();
        });
    </script>
</body>
</html>
