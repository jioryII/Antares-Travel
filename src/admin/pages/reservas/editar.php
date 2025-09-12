<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Editar Reserva";

// Obtener ID de la reserva
$id_reserva = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id_reserva) {
    header("Location: index.php");
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $connection = getConnection();
        $connection->beginTransaction();
        
        // Datos de la reserva
        $id_usuario = intval($_POST['id_usuario']);
        $id_tour = intval($_POST['id_tour']);
        $fecha_tour = $_POST['fecha_tour'];
        $observaciones = $_POST['observaciones'] ?? '';
        $origen_reserva = $_POST['origen_reserva'] ?? 'Presencial';
        
        // Obtener precio del tour
        $tour_sql = "SELECT precio FROM tours WHERE id_tour = ?";
        $tour_stmt = $connection->prepare($tour_sql);
        $tour_stmt->execute([$id_tour]);
        $tour = $tour_stmt->fetch();
        
        if (!$tour) {
            throw new Exception("Tour no encontrado");
        }
        
        // Calcular monto total basado en número de pasajeros
        $pasajeros = $_POST['pasajeros'] ?? [];
        $num_pasajeros = count($pasajeros);
        $monto_total = $tour['precio'] * $num_pasajeros;
        
        // Actualizar reserva
        $reserva_sql = "UPDATE reservas SET id_usuario = ?, id_tour = ?, fecha_tour = ?, monto_total = ?, 
                        observaciones = ?, origen_reserva = ? WHERE id_reserva = ?";
        $reserva_stmt = $connection->prepare($reserva_sql);
        $reserva_stmt->execute([$id_usuario, $id_tour, $fecha_tour, $monto_total, $observaciones, $origen_reserva, $id_reserva]);
        
        // Eliminar pasajeros existentes
        $delete_pasajeros_sql = "DELETE FROM pasajeros WHERE id_reserva = ?";
        $delete_pasajeros_stmt = $connection->prepare($delete_pasajeros_sql);
        $delete_pasajeros_stmt->execute([$id_reserva]);
        
        // Insertar nuevos pasajeros
        $pasajero_sql = "INSERT INTO pasajeros (id_reserva, nombre, apellido, dni_pasaporte, nacionalidad, telefono, tipo_pasajero) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pasajero_stmt = $connection->prepare($pasajero_sql);
        
        foreach ($pasajeros as $pasajero) {
            if (!empty($pasajero['nombre']) && !empty($pasajero['apellido']) && !empty($pasajero['dni_pasaporte'])) {
                $pasajero_stmt->execute([
                    $id_reserva,
                    $pasajero['nombre'],
                    $pasajero['apellido'],
                    $pasajero['dni_pasaporte'],
                    $pasajero['nacionalidad'] ?? '',
                    $pasajero['telefono'] ?? '',
                    $pasajero['tipo_pasajero'] ?? 'Adulto'
                ]);
            }
        }
        
        // Procesar pagos existentes si se enviaron
        if (isset($_POST['pagos'])) {
            foreach ($_POST['pagos'] as $pago_id => $pago_data) {
                if (!empty($pago_data['monto']) && $pago_data['monto'] > 0) {
                    $fecha_pago = !empty($pago_data['fecha_pago']) ? $pago_data['fecha_pago'] : date('Y-m-d H:i:s');
                    
                    // Actualizar pago existente
                    $update_pago_sql = "UPDATE pagos SET 
                                        monto = ?, 
                                        metodo_pago = ?, 
                                        estado_pago = ?, 
                                        fecha_pago = ? 
                                        WHERE id_pago = ? AND id_reserva = ?";
                    $update_pago_stmt = $connection->prepare($update_pago_sql);
                    $update_pago_stmt->execute([
                        floatval($pago_data['monto']),
                        $pago_data['metodo_pago'] ?? 'Efectivo',
                        $pago_data['estado_pago'] ?? 'Pagado',
                        $fecha_pago,
                        intval($pago_id),
                        $id_reserva
                    ]);
                }
            }
        }
        
        $connection->commit();
        
        // Redireccionar a la página de la reserva
        header("Location: ver.php?id=$id_reserva&success=1");
        exit;
        
    } catch (Exception $e) {
        $connection->rollback();
        $error = "Error al actualizar la reserva: " . $e->getMessage();
    }
}

// Obtener datos de la reserva
try {
    $connection = getConnection();
    
    // Obtener reserva con datos relacionados
    $reserva_sql = "SELECT r.*, u.nombre as cliente_nombre, u.email as cliente_email,
                           t.titulo as tour_titulo, t.precio as tour_precio, t.duracion as tour_duracion,
                           reg.nombre_region
                    FROM reservas r
                    LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                    LEFT JOIN tours t ON r.id_tour = t.id_tour
                    LEFT JOIN regiones reg ON t.id_region = reg.id_region
                    WHERE r.id_reserva = ?";
    $reserva_stmt = $connection->prepare($reserva_sql);
    $reserva_stmt->execute([$id_reserva]);
    $reserva = $reserva_stmt->fetch();
    
    if (!$reserva) {
        header("Location: index.php");
        exit;
    }
    
    // Obtener pasajeros de la reserva
    $pasajeros_sql = "SELECT * FROM pasajeros WHERE id_reserva = ? ORDER BY id_pasajero ASC";
    $pasajeros_stmt = $connection->prepare($pasajeros_sql);
    $pasajeros_stmt->execute([$id_reserva]);
    $pasajeros_actuales = $pasajeros_stmt->fetchAll();
    
    // Obtener pagos de la reserva
    $pagos_sql = "SELECT * FROM pagos WHERE id_reserva = ? ORDER BY fecha_pago DESC";
    $pagos_stmt = $connection->prepare($pagos_sql);
    $pagos_stmt->execute([$id_reserva]);
    $pagos_actuales = $pagos_stmt->fetchAll();
    
    // Obtener métodos de pago dinámicamente desde la base de datos
    $metodos_pago = [];
    $estados_pago = [];
    try {
        $metodos_sql = "SHOW COLUMNS FROM pagos LIKE 'metodo_pago'";
        $metodos_result = $connection->query($metodos_sql)->fetch();
        if ($metodos_result && isset($metodos_result['Type'])) {
            preg_match("/^enum\((.+)\)$/", $metodos_result['Type'], $matches);
            if ($matches) {
                $metodos_pago = str_getcsv($matches[1], ',', "'");
            }
        }
        
        $estados_sql = "SHOW COLUMNS FROM pagos LIKE 'estado_pago'";
        $estados_result = $connection->query($estados_sql)->fetch();
        if ($estados_result && isset($estados_result['Type'])) {
            preg_match("/^enum\((.+)\)$/", $estados_result['Type'], $matches);
            if ($matches) {
                $estados_pago = str_getcsv($matches[1], ',', "'");
            }
        }
    } catch (Exception $e) {
        error_log("Error al obtener ENUMs: " . $e->getMessage());
    }
    
    // Obtener usuarios
    $usuarios_sql = "SELECT id_usuario, nombre, email FROM usuarios ORDER BY nombre ASC";
    $usuarios = $connection->query($usuarios_sql)->fetchAll();
    
    // Obtener tours activos
    $tours_sql = "SELECT t.id_tour, t.titulo, t.precio, t.duracion, r.nombre_region 
                  FROM tours t 
                  LEFT JOIN regiones r ON t.id_region = r.id_region 
                  ORDER BY t.titulo ASC";
    $tours = $connection->query($tours_sql)->fetchAll();
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
    $reserva = null;
}

if (!$reserva) {
    header("Location: index.php");
    exit;
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
        .form-section {
            transition: all 0.3s ease;
        }
        .form-section:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .pasajero-card {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        .pasajero-card.filled {
            border: 2px solid #3b82f6;
            background-color: #eff6ff;
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
                            <nav class="flex mb-3" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    <li><a href="index.php" class="text-blue-600 hover:text-blue-800">Reservas</a></li>
                                    <li><span class="text-gray-500">/</span></li>
                                    <li><a href="ver.php?id=<?php echo $id_reserva; ?>" class="text-blue-600 hover:text-blue-800">Reserva #<?php echo $id_reserva; ?></a></li>
                                    <li><span class="text-gray-500">/</span></li>
                                    <li><span class="text-gray-500">Editar</span></li>
                                </ol>
                            </nav>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-edit text-blue-600 mr-3"></i>Editar Reserva #<?php echo $id_reserva; ?>
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600">Cliente: <?php echo htmlspecialchars($reserva['cliente_nombre']); ?></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="ver.php?id=<?php echo $id_reserva; ?>" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Volver
                            </a>
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

                <!-- Estado de la reserva -->
                <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <i class="fas fa-info-circle text-yellow-400 mr-3 mt-1"></i>
                        <div>
                            <h3 class="text-sm font-medium text-yellow-800">Estado actual</h3>
                            <p class="text-sm text-yellow-700 mt-1">
                                Esta reserva está en estado: <strong><?php echo htmlspecialchars($reserva['estado']); ?></strong>
                                <?php if ($reserva['estado'] === 'Confirmada'): ?>
                                    <br>Ten cuidado al editar reservas confirmadas ya que pueden afectar las operaciones.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <form method="POST" id="formEditarReserva" class="space-y-6">
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <!-- Columna Principal -->
                        <div class="xl:col-span-2 space-y-6">
                            <!-- Información Básica -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-info-circle mr-3"></i>Información Básica
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Cliente *</label>
                                            <select name="id_usuario" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">Seleccionar cliente...</option>
                                                <?php foreach ($usuarios as $usuario): ?>
                                                    <option value="<?php echo $usuario['id_usuario']; ?>" <?php echo ($usuario['id_usuario'] == $reserva['id_usuario']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($usuario['nombre'] . ' (' . $usuario['email'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tour *</label>
                                            <select name="id_tour" id="tourSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">Seleccionar tour...</option>
                                                <?php foreach ($tours as $tour): ?>
                                                    <option value="<?php echo $tour['id_tour']; ?>" 
                                                            data-precio="<?php echo $tour['precio']; ?>"
                                                            data-duracion="<?php echo htmlspecialchars($tour['duracion']); ?>"
                                                            data-region="<?php echo htmlspecialchars($tour['nombre_region']); ?>"
                                                            <?php echo ($tour['id_tour'] == $reserva['id_tour']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($tour['titulo']); ?> - <?php echo formatCurrency($tour['precio']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha del Tour *</label>
                                            <input type="date" name="fecha_tour" required 
                                                   value="<?php echo $reserva['fecha_tour']; ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Origen de Reserva</label>
                                            <select name="origen_reserva" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="Presencial" <?php echo ($reserva['origen_reserva'] === 'Presencial') ? 'selected' : ''; ?>>Presencial</option>
                                                <option value="Web" <?php echo ($reserva['origen_reserva'] === 'Web') ? 'selected' : ''; ?>>Web</option>
                                                <option value="Llamada" <?php echo ($reserva['origen_reserva'] === 'Llamada') ? 'selected' : ''; ?>>Llamada</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                        <textarea name="observaciones" rows="3" 
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                  placeholder="Notas adicionales sobre la reserva..."><?php echo htmlspecialchars($reserva['observaciones']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Pasajeros -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-white flex items-center">
                                            <i class="fas fa-users mr-3"></i>Pasajeros
                                        </h3>
                                        <button type="button" onclick="agregarPasajero()" 
                                                class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-3 py-1 rounded-lg text-sm transition-colors">
                                            <i class="fas fa-plus mr-1"></i>Agregar Pasajero
                                        </button>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div id="pasajerosContainer">
                                        <!-- Los pasajeros existentes se cargarán aquí -->
                                    </div>
                                    <div class="mt-4">
                                        <button type="button" onclick="agregarPasajero()" 
                                                class="w-full pasajero-card rounded-lg p-4 text-center text-gray-500 hover:text-blue-600 hover:border-blue-300 transition-colors">
                                            <i class="fas fa-plus text-2xl mb-2"></i>
                                            <p>Agregar Pasajero</p>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Gestión de Pagos -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-credit-card mr-3"></i>Editar Pagos Existentes
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <!-- Resumen de pagos actual -->
                                    <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium text-blue-800">Total Pagado Actual:</span>
                                            <span id="totalPagadoActual" class="text-lg font-bold text-blue-600">
                                                <?php 
                                                $total_pagado_actual = array_sum(array_column($pagos_actuales, 'monto'));
                                                echo formatCurrency($total_pagado_actual); 
                                                ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-blue-800">Saldo Pendiente:</span>
                                            <span id="saldoPendienteActual" class="text-lg font-bold <?php echo ($reserva['monto_total'] - $total_pagado_actual) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo formatCurrency($reserva['monto_total'] - $total_pagado_actual); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div id="pagosContainer">
                                        <!-- Los pagos existentes se cargarán aquí -->
                                    </div>
                                    
                                    <?php if (empty($pagos_actuales)): ?>
                                    <div class="text-center py-8 text-gray-500">
                                        <i class="fas fa-receipt text-4xl mb-3"></i>
                                        <p>No hay pagos registrados para esta reserva</p>
                                        <p class="text-sm mt-1">Los pagos se pueden agregar desde el formulario de creación de reservas</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Lateral - Resumen -->
                        <div class="space-y-6">
                            <!-- Resumen de la Reserva -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-calculator mr-3"></i>Resumen de Reserva
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Precio por Persona:</span>
                                            <span id="resumenPrecio" class="font-medium"><?php echo formatCurrency($reserva['tour_precio']); ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Número de Pasajeros:</span>
                                            <span id="resumenPasajeros" class="font-medium"><?php echo count($pasajeros_actuales); ?></span>
                                        </div>
                                        <div class="border-t pt-4">
                                            <div class="flex justify-between items-center">
                                                <span class="text-lg font-semibold text-gray-900">Total:</span>
                                                <span id="resumenTotal" class="text-2xl font-bold text-orange-600"><?php echo formatCurrency($reserva['monto_total']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumen de Pagos -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-money-bill-wave mr-3"></i>Estado de Pagos
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Total a Pagar:</span>
                                            <span class="font-medium"><?php echo formatCurrency($reserva['monto_total']); ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Total Pagado:</span>
                                            <span id="totalPagadoResumen" class="font-medium text-green-600">
                                                <?php echo formatCurrency($total_pagado_actual); ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center border-t pt-3">
                                            <span class="font-semibold text-gray-900">Saldo:</span>
                                            <span id="saldoResumen" class="font-bold text-lg <?php echo ($reserva['monto_total'] - $total_pagado_actual) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo formatCurrency($reserva['monto_total'] - $total_pagado_actual); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Barra de progreso de pagos -->
                                    <div class="mt-4">
                                        <?php 
                                        $porcentaje_pagado = $reserva['monto_total'] > 0 ? ($total_pagado_actual / $reserva['monto_total']) * 100 : 0;
                                        ?>
                                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                                            <span>Progreso de Pago</span>
                                            <span id="porcentajePago"><?php echo number_format($porcentaje_pagado, 1); ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div id="barraProgresoPago" class="bg-green-600 h-2 rounded-full transition-all duration-300" 
                                                 style="width: <?php echo $porcentaje_pagado; ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-6 pt-6 border-t">
                                        <button type="submit" 
                                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                            <i class="fas fa-save mr-2"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let pasajeroCount = 0;
        let precioPorPersona = <?php echo $reserva['tour_precio']; ?>;
        
        // Datos desde PHP
        const pasajerosExistentes = <?php echo json_encode($pasajeros_actuales); ?>;
        const pagosExistentes = <?php echo json_encode($pagos_actuales); ?>;
        const metodosPago = <?php echo json_encode($metodos_pago); ?>;
        const estadosPago = <?php echo json_encode($estados_pago); ?>;
        const montoTotalReserva = <?php echo $reserva['monto_total']; ?>;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar pasajeros existentes
            pasajerosExistentes.forEach(pasajero => {
                cargarPasajeroExistente(pasajero);
            });
            
            // Cargar pagos existentes
            pagosExistentes.forEach(pago => {
                cargarPagoExistente(pago);
            });
            
            actualizarResumenPagos();
        });

        // Manejar selección de tour
        document.getElementById('tourSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                precioPorPersona = parseFloat(selectedOption.dataset.precio);
                
                document.getElementById('tourPrecio').textContent = formatCurrency(precioPorPersona);
                document.getElementById('tourDuracion').textContent = selectedOption.dataset.duracion || '-';
                document.getElementById('tourRegion').textContent = selectedOption.dataset.region || '-';
            } else {
                precioPorPersona = 0;
            }
            
            actualizarResumen();
        });

        // Funciones de Pagos
        function cargarPagoExistente(pago) {
            const container = document.getElementById('pagosContainer');
            
            const pagoDiv = document.createElement('div');
            pagoDiv.className = 'border border-blue-200 rounded-lg p-4 mb-4 bg-blue-50';
            pagoDiv.id = `pago-${pago.id_pago}`;
            
            const fechaPago = pago.fecha_pago ? new Date(pago.fecha_pago).toISOString().slice(0, 16) : '';
            
            pagoDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-900">
                        <i class="fas fa-edit text-blue-600 mr-2"></i>
                        Pago #${pago.id_pago} - ${formatCurrency(pago.monto)}
                    </h4>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        ${pago.estado_pago === 'Pagado' ? 'bg-green-100 text-green-800' :
                          pago.estado_pago === 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                        ${pago.estado_pago}
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">S/</span>
                            <input type="number" name="pagos[${pago.id_pago}][monto]" required 
                                   value="${pago.monto || ''}" step="0.01" min="0"
                                   class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="actualizarResumenPagos()">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago *</label>
                        <select name="pagos[${pago.id_pago}][metodo_pago]" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            ${metodosPago.map(metodo => 
                                `<option value="${metodo}" ${pago.metodo_pago === metodo ? 'selected' : ''}>${metodo}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="pagos[${pago.id_pago}][estado_pago]" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                onchange="actualizarEstadoPago(${pago.id_pago})">
                            ${estadosPago.map(estado => 
                                `<option value="${estado}" ${pago.estado_pago === estado ? 'selected' : ''}>${estado}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y Hora</label>
                        <input type="datetime-local" name="pagos[${pago.id_pago}][fecha_pago]" 
                               value="${fechaPago}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                <div class="mt-3 p-2 bg-gray-100 rounded text-xs text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Pago registrado el: ${pago.fecha_pago ? new Date(pago.fecha_pago).toLocaleDateString('es-PE') : 'No especificado'}
                </div>
            `;
            
            container.appendChild(pagoDiv);
        }

        function actualizarEstadoPago(pagoId) {
            const pagoDiv = document.getElementById(`pago-${pagoId}`);
            const estadoSelect = pagoDiv.querySelector('select[name*="[estado_pago]"]');
            const badge = pagoDiv.querySelector('.inline-flex');
            
            // Actualizar el badge visual
            const estado = estadoSelect.value;
            badge.textContent = estado;
            badge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                estado === 'Pagado' ? 'bg-green-100 text-green-800' :
                estado === 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'
            }`;
        }

        function actualizarResumenPagos() {
            const pagoInputs = document.querySelectorAll('input[name*="[monto]"]');
            let totalPagos = 0;
            
            pagoInputs.forEach(input => {
                const monto = parseFloat(input.value) || 0;
                totalPagos += monto;
            });
            
            const saldoPendiente = montoTotalReserva - totalPagos;
            const porcentaje = montoTotalReserva > 0 ? (totalPagos / montoTotalReserva) * 100 : 0;
            
            // Actualizar resumen principal de pagos
            document.getElementById('totalPagadoActual').textContent = formatCurrency(totalPagos);
            const saldoElement = document.getElementById('saldoPendienteActual');
            saldoElement.textContent = formatCurrency(saldoPendiente);
            
            // Actualizar resumen lateral
            document.getElementById('totalPagadoResumen').textContent = formatCurrency(totalPagos);
            const saldoResumenElement = document.getElementById('saldoResumen');
            saldoResumenElement.textContent = formatCurrency(saldoPendiente);
            
            // Actualizar porcentaje y barra de progreso
            document.getElementById('porcentajePago').textContent = porcentaje.toFixed(1) + '%';
            document.getElementById('barraProgresoPago').style.width = porcentaje + '%';
            
            // Cambiar colores según el saldo
            const colorClass = saldoPendiente > 0 ? 'text-red-600' : 
                              saldoPendiente === 0 ? 'text-green-600' : 'text-blue-600';
            
            saldoElement.className = 'text-lg font-bold ' + colorClass;
            saldoResumenElement.className = 'font-bold text-lg ' + colorClass;
            
            // Cambiar color de la barra de progreso
            const barraElement = document.getElementById('barraProgresoPago');
            if (porcentaje >= 100) {
                barraElement.className = 'bg-green-600 h-2 rounded-full transition-all duration-300';
            } else if (porcentaje >= 50) {
                barraElement.className = 'bg-yellow-500 h-2 rounded-full transition-all duration-300';
            } else {
                barraElement.className = 'bg-red-500 h-2 rounded-full transition-all duration-300';
            }
        }

        function cargarPasajeroExistente(pasajero) {
            pasajeroCount++;
            const container = document.getElementById('pasajerosContainer');
            
            const pasajeroDiv = document.createElement('div');
            pasajeroDiv.className = 'pasajero-card filled border rounded-lg p-4 mb-4';
            pasajeroDiv.id = `pasajero-${pasajeroCount}`;
            
            pasajeroDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-900">Pasajero #${pasajeroCount}</h4>
                    <button type="button" onclick="eliminarPasajero(${pasajeroCount})" 
                            class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][nombre]" required 
                               value="${pasajero.nombre || ''}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][apellido]" required 
                               value="${pasajero.apellido || ''}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">DNI/Pasaporte *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][dni_pasaporte]" required 
                               value="${pasajero.dni_pasaporte || ''}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="pasajeros[${pasajeroCount}][tipo_pasajero]" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Adulto" ${pasajero.tipo_pasajero === 'Adulto' ? 'selected' : ''}>Adulto</option>
                            <option value="Niño" ${pasajero.tipo_pasajero === 'Niño' ? 'selected' : ''}>Niño</option>
                            <option value="Infante" ${pasajero.tipo_pasajero === 'Infante' ? 'selected' : ''}>Infante</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nacionalidad</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][nacionalidad]" 
                               value="${pasajero.nacionalidad || ''}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ej: Peruana">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="tel" name="pasajeros[${pasajeroCount}][telefono]" 
                               value="${pasajero.telefono || ''}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ej: +51 999 888 777">
                    </div>
                </div>
            `;
            
            container.appendChild(pasajeroDiv);
        }

        function agregarPasajero() {
            pasajeroCount++;
            const container = document.getElementById('pasajerosContainer');
            
            const pasajeroDiv = document.createElement('div');
            pasajeroDiv.className = 'pasajero-card border rounded-lg p-4 mb-4';
            pasajeroDiv.id = `pasajero-${pasajeroCount}`;
            
            pasajeroDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-900">Pasajero #${pasajeroCount}</h4>
                    <button type="button" onclick="eliminarPasajero(${pasajeroCount})" 
                            class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][nombre]" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][apellido]" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">DNI/Pasaporte *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][dni_pasaporte]" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="pasajeros[${pasajeroCount}][tipo_pasajero]" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Adulto">Adulto</option>
                            <option value="Niño">Niño</option>
                            <option value="Infante">Infante</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nacionalidad</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][nacionalidad]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ej: Peruana">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="tel" name="pasajeros[${pasajeroCount}][telefono]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ej: +51 999 888 777">
                    </div>
                </div>
            `;
            
            container.appendChild(pasajeroDiv);
            actualizarResumen();
        }

        function eliminarPasajero(id) {
            const pasajeroDiv = document.getElementById(`pasajero-${id}`);
            if (pasajeroDiv) {
                pasajeroDiv.remove();
                actualizarResumen();
            }
        }

        function validarPasajero(id) {
            const pasajeroDiv = document.getElementById(`pasajero-${id}`);
            const inputs = pasajeroDiv.querySelectorAll('input[required]');
            let filled = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    filled = false;
                }
            });
            
            if (filled) {
                pasajeroDiv.classList.add('filled');
            } else {
                pasajeroDiv.classList.remove('filled');
            }
        }

        function actualizarResumen() {
            const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
            const total = precioPorPersona * numPasajeros;
            
            document.getElementById('resumenPrecio').textContent = formatCurrency(precioPorPersona);
            document.getElementById('resumenPasajeros').textContent = numPasajeros;
            document.getElementById('resumenTotal').textContent = formatCurrency(total);
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN',
                minimumFractionDigits: 2
            }).format(amount);
        }

        // Validación del formulario
        document.getElementById('formEditarReserva').addEventListener('submit', function(e) {
            const pasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card');
            
            if (pasajeros.length === 0) {
                e.preventDefault();
                alert('Debe tener al menos un pasajero');
                return false;
            }

            // Validar que todos los pasajeros tengan datos básicos
            let valid = true;
            pasajeros.forEach(pasajero => {
                const inputs = pasajero.querySelectorAll('input[required]');
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.classList.add('border-red-500');
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });
            });

            if (!valid) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios de los pasajeros');
                return false;
            }

            // Validar pagos existentes si hay cambios
            const pagos = document.querySelectorAll('#pagosContainer div[id^="pago-"]');
            pagos.forEach(pago => {
                const montoInput = pago.querySelector('input[name*="[monto]"]');
                const metodoSelect = pago.querySelector('select[name*="[metodo_pago]"]');
                const estadoSelect = pago.querySelector('select[name*="[estado_pago]"]');
                
                if (montoInput && montoInput.value) {
                    if (!metodoSelect.value || !estadoSelect.value) {
                        valid = false;
                        if (!metodoSelect.value) metodoSelect.classList.add('border-red-500');
                        if (!estadoSelect.value) estadoSelect.classList.add('border-red-500');
                    } else {
                        metodoSelect.classList.remove('border-red-500');
                        estadoSelect.classList.remove('border-red-500');
                    }
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios de los pagos');
                return false;
            }
        });

        // Inicializar resumen al cargar
        actualizarResumen();
    </script>
</body>
</html>
