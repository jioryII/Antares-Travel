<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Nueva Reserva";

// Función para obtener o crear usuario administrador automáticamente
function obtenerOCrearUsuarioAdmin($admin_email, $admin_nombre, $connection) {
    // Extraer username del email del administrador
    $username = explode('@', $admin_email)[0]; // Obtener parte antes del @
    $username = strtolower($username); // Convertir a minúsculas
    
    // Generar datos del usuario admin
    $nombre_usuario_admin = "Admin {$admin_nombre}"; // Agregar prefijo "Admin" al nombre
    $email_usuario_admin = "{$username}@antares.com"; // Sin prefijo "admin"
    
    // Verificar si ya existe este usuario admin
    $check_sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
    $check_stmt = $connection->prepare($check_sql);
    $check_stmt->execute([$email_usuario_admin]);
    $usuario_existente = $check_stmt->fetch();
    
    if ($usuario_existente) {
        // Reutilizar usuario existente
        return $usuario_existente['id_usuario'];
    }
    
    // Crear nuevo usuario admin
    $crear_sql = "INSERT INTO usuarios (nombre, email, telefono, email_verificado, proveedor_oauth, id_proveedor) 
                  VALUES (?, ?, ?, 0, 'manual', ?)";
    $id_proveedor = 'admin_user_' . $username . '_' . time();
    $crear_stmt = $connection->prepare($crear_sql);
    $crear_stmt->execute([
        $nombre_usuario_admin,
        $email_usuario_admin,
        '000-000-000', // Teléfono por defecto para usuarios admin
        $id_proveedor
    ]);
    
    return $connection->lastInsertId();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $connection = getConnection();
        $connection->beginTransaction();
        
        // Obtener o crear usuario admin automáticamente
        $id_usuario_admin = obtenerOCrearUsuarioAdmin($admin['email'], $admin['nombre'], $connection);
        
        // Datos de la reserva
        $id_tour = intval($_POST['id_tour']);
        $fecha_tour = $_POST['fecha_tour'];
        $observaciones = $_POST['observaciones'] ?? '';
        $origen_reserva = $_POST['origen_reserva'] ?? 'Presencial';
        
        // Datos del pago
        $metodo_pago = $_POST['metodo_pago'] ?? null;
        $monto_pago = floatval($_POST['monto_pago'] ?? 0);
        $estado_pago = $_POST['estado_pago'] ?? 'Pendiente';
        
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
        
        // Insertar reserva
        $reserva_sql = "INSERT INTO reservas (id_usuario, id_administrador, id_tour, fecha_tour, monto_total, observaciones, origen_reserva, estado) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente')";
        $reserva_stmt = $connection->prepare($reserva_sql);
        $reserva_stmt->execute([$id_usuario_admin, $admin['id_admin'], $id_tour, $fecha_tour, $monto_total, $observaciones, $origen_reserva]);
        
        $id_reserva = $connection->lastInsertId();
        
        // Insertar pasajeros
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
        
        // Insertar pago si se especificó método de pago
        if (!empty($metodo_pago) && $monto_pago > 0) {
            $pago_sql = "INSERT INTO pagos (id_reserva, monto, metodo_pago, estado_pago) VALUES (?, ?, ?, ?)";
            $pago_stmt = $connection->prepare($pago_sql);
            $pago_stmt->execute([$id_reserva, $monto_pago, $metodo_pago, $estado_pago]);
        }
        
        $connection->commit();
        
        // Redireccionar a la página de la reserva creada
        header("Location: ver.php?id=$id_reserva&success=1");
        exit;
        
    } catch (Exception $e) {
        $connection->rollback();
        $error = "Error al crear la reserva: " . $e->getMessage();
    }
}

// Obtener datos para los formularios
try {
    $connection = getConnection();
    
    // Obtener tours activos
    $tours_sql = "SELECT t.id_tour, t.titulo, t.precio, t.duracion, r.nombre_region 
                  FROM tours t 
                  LEFT JOIN regiones r ON t.id_region = r.id_region 
                  ORDER BY t.titulo ASC";
    $tours = $connection->query($tours_sql)->fetchAll();
    
    // Obtener opciones de ENUM para métodos de pago
    $metodos_pago_sql = "SHOW COLUMNS FROM pagos LIKE 'metodo_pago'";
    $metodos_result = $connection->query($metodos_pago_sql)->fetch();
    $metodos_pago = [];
    
    if ($metodos_result && isset($metodos_result['Type'])) {
        // Extraer valores del ENUM: enum('Efectivo','Tarjeta','Transferencia')
        preg_match_all("/'([^']+)'/", $metodos_result['Type'], $matches);
        $metodos_pago = $matches[1];
    }
    
    // Obtener opciones de ENUM para estados de pago
    $estados_pago_sql = "SHOW COLUMNS FROM pagos LIKE 'estado_pago'";
    $estados_result = $connection->query($estados_pago_sql)->fetch();
    $estados_pago = [];
    
    if ($estados_result && isset($estados_result['Type'])) {
        // Extraer valores del ENUM: enum('Pagado','Pendiente','Fallido')
        preg_match_all("/'([^']+)'/", $estados_result['Type'], $matches);
        $estados_pago = $matches[1];
    }
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
    $tours = [];
    $metodos_pago = ['Efectivo', 'Tarjeta', 'Transferencia']; // Fallback
    $estados_pago = ['Pagado', 'Pendiente', 'Fallido']; // Fallback
}// Función para obtener icono de método de pago
function getMetodoPagoIcon($metodo) {
    $iconos = [
        'Efectivo' => 'fas fa-money-bill-wave',
        'Tarjeta' => 'fas fa-credit-card',
        'Transferencia' => 'fas fa-university',
        'PayPal' => 'fab fa-paypal',
        'Yape' => 'fas fa-mobile-alt',
        'Plin' => 'fas fa-mobile-alt',
        'Criptomonedas' => 'fab fa-bitcoin'
    ];
    return $iconos[$metodo] ?? 'fas fa-money-bill';
}

// Función para obtener icono de estado de pago
function getEstadoPagoIcon($estado) {
    $iconos = [
        'Pagado' => 'fas fa-check-circle',
        'Pendiente' => 'fas fa-clock',
        'Fallido' => 'fas fa-times-circle',
        'Procesando' => 'fas fa-spinner',
        'Reembolsado' => 'fas fa-undo',
        'Cancelado' => 'fas fa-ban'
    ];
    return $iconos[$estado] ?? 'fas fa-question-circle';
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
                                    <li><span class="text-gray-500">Nueva Reserva</span></li>
                                </ol>
                            </nav>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-plus-circle text-blue-600 mr-3"></i>Nueva Reserva
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600">Crear una nueva reserva administrativa</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="index.php" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
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

                <!-- Formulario -->
                <form method="POST" id="formNuevaReserva" class="space-y-6">
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
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Reservado por</label>
                                            <div class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                                        <i class="fas fa-user-shield text-white text-xs"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">
                                                            Admin <?php echo htmlspecialchars($admin['nombre']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Se creará usuario automáticamente
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tour *</label>
                                            <select name="id_tour" id="tourSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">Seleccionar tour...</option>
                                                <?php foreach ($tours as $tour): ?>
                                                    <option value="<?php echo $tour['id_tour']; ?>" 
                                                            data-precio="<?php echo $tour['precio']; ?>"
                                                            data-duracion="<?php echo htmlspecialchars($tour['duracion']); ?>"
                                                            data-region="<?php echo htmlspecialchars($tour['nombre_region']); ?>">
                                                        <?php echo htmlspecialchars($tour['titulo']); ?> - <?php echo formatCurrency($tour['precio']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha del Tour *</label>
                                            <input type="date" name="fecha_tour" required 
                                                   min="<?php echo date('Y-m-d'); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Origen de Reserva</label>
                                            <select name="origen_reserva" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="Presencial">Presencial</option>
                                                <option value="Web">Web</option>
                                                <option value="Llamada">Llamada</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                        <textarea name="observaciones" rows="3" 
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                  placeholder="Notas adicionales sobre la reserva..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de Pago -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-credit-card mr-3"></i>Información de Pago
                                    </h3>
                                    <p class="text-emerald-100 text-sm mt-1">Configure los detalles de pago (opcional)</p>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="fas fa-money-bill-wave text-emerald-600 mr-2"></i>Método de Pago
                                            </label>
                                            <select name="metodo_pago" id="metodoPago" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                                <option value="">Sin pago por ahora</option>
                                                <?php foreach ($metodos_pago as $metodo): ?>
                                                    <option value="<?php echo htmlspecialchars($metodo); ?>">
                                                        <i class="<?php echo getMetodoPagoIcon($metodo); ?>"></i> <?php echo htmlspecialchars($metodo); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="text-xs text-gray-500 mt-1">Deje vacío si el pago se realizará después</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="fas fa-dollar-sign text-emerald-600 mr-2"></i>Monto del Pago
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">S/</span>
                                                <input type="number" name="monto_pago" id="montoPago" 
                                                       step="0.01" min="0" placeholder="0.00"
                                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Monto parcial o total del pago</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="fas fa-check-circle text-emerald-600 mr-2"></i>Estado del Pago
                                            </label>
                                            <select name="estado_pago" id="estadoPago" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                                <?php foreach ($estados_pago as $estado): ?>
                                                    <option value="<?php echo htmlspecialchars($estado); ?>" 
                                                            <?php echo $estado === 'Pendiente' ? 'selected' : ''; ?>>
                                                        <i class="<?php echo getEstadoPagoIcon($estado); ?>"></i> <?php echo htmlspecialchars($estado); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Botones de acción rápida para pagos -->
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button type="button" onclick="setPagoCompleto()" 
                                                class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-sm hover:bg-emerald-200 transition-colors">
                                            <i class="fas fa-check mr-1"></i>Pago Completo
                                        </button>
                                        <button type="button" onclick="setPagoMitad()" 
                                                class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-lg text-sm hover:bg-yellow-200 transition-colors">
                                            <i class="fas fa-divide mr-1"></i>Pago 50%
                                        </button>
                                        <button type="button" onclick="setPagoPorcentaje(30)" 
                                                class="px-3 py-1 bg-orange-100 text-orange-700 rounded-lg text-sm hover:bg-orange-200 transition-colors">
                                            <i class="fas fa-percent mr-1"></i>30% Adelanto
                                        </button>
                                        <button type="button" onclick="setPagoPersonalizado()" 
                                                class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200 transition-colors">
                                            <i class="fas fa-edit mr-1"></i>Monto Personalizado
                                        </button>
                                        <button type="button" onclick="clearPago()" 
                                                class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition-colors">
                                            <i class="fas fa-times mr-1"></i>Sin Pago
                                        </button>
                                    </div>

                                    <!-- Información adicional -->
                                    <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                        <div class="flex items-start">
                                            <i class="fas fa-info-circle text-emerald-600 mr-2 mt-0.5"></i>
                                            <div class="text-sm text-emerald-700">
                                                <p class="font-medium">Información sobre pagos:</p>
                                                <ul class="mt-1 space-y-1 text-xs">
                                                    <li>• <strong>Flexibilidad total:</strong> Puede ingresar cualquier monto, mayor o menor al precio del tour</li>
                                                    <li>• <strong>Pagos parciales:</strong> Ideales para adelantos o cuotas</li>
                                                    <li>• <strong>Pagos superiores:</strong> Para incluir propinas, servicios extras o conceptos adicionales</li>
                                                    <li>• Se pueden realizar múltiples pagos para una misma reserva</li>
                                                    <li>• Métodos de pago disponibles: <?php echo implode(', ', $metodos_pago); ?></li>
                                                    <li>• Estados posibles: <?php echo implode(', ', $estados_pago); ?></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Tour Seleccionado -->
                            <div id="tourInfo" class="form-section bg-white rounded-lg shadow-lg overflow-hidden hidden">
                                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-map-marked-alt mr-3"></i>Información del Tour
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Precio por Persona</label>
                                            <p id="tourPrecio" class="mt-1 text-lg font-semibold text-green-600">-</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Duración</label>
                                            <p id="tourDuracion" class="mt-1 text-gray-900">-</p>
                                        </div>
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
                                        <!-- Los pasajeros se agregarán aquí dinámicamente -->
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
                        </div>

                        <!-- Columna Lateral - Resumen -->
                        <div class="space-y-6">
                            <!-- Resumen de la Reserva -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-calculator mr-3"></i>Resumen
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Precio por Persona:</span>
                                            <span id="resumenPrecio" class="font-medium">S/ 0.00</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Número de Pasajeros:</span>
                                            <span id="resumenPasajeros" class="font-medium">0</span>
                                        </div>
                                        <div class="border-t pt-4">
                                            <div class="flex justify-between items-center">
                                                <span class="text-lg font-semibold text-gray-900">Total:</span>
                                                <span id="resumenTotal" class="text-2xl font-bold text-orange-600">S/ 0.00</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Información del pago en tiempo real -->
                                        <div id="resumenPago" class="border-t pt-4 hidden">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-600">Pago Registrado:</span>
                                                <span id="resumenMontoPago" class="text-sm font-medium text-emerald-600">S/ 0.00</span>
                                            </div>
                                            <div class="flex justify-between items-center mt-1">
                                                <span class="text-xs text-gray-500">Estado:</span>
                                                <span id="resumenEstadoPago" class="text-xs text-gray-700">-</span>
                                            </div>
                                            <div id="resumenDiferenciaPago" class="mt-2 text-xs text-center p-2 rounded hidden">
                                                <!-- Aquí se mostrará si es pago parcial, completo o excedente -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-6 pt-6 border-t">
                                        <button type="submit" 
                                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                            <i class="fas fa-save mr-2"></i>Crear Reserva
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Ayuda -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <i class="fas fa-info-circle text-blue-400 mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-800">Información</h4>
                                        <ul class="text-sm text-blue-700 mt-2 space-y-1">
                                            <li>• Todos los campos marcados con * son obligatorios</li>
                                            <li>• Debe agregar al menos un pasajero</li>
                                            <li>• El precio total se calcula automáticamente</li>
                                            <li>• Se creará automáticamente un usuario para esta reserva</li>
                                            <li>• La reserva se registrará a nombre del administrador</li>
                                            <li>• El pago es opcional al crear la reserva</li>
                                            <li>• Puede registrar pagos parciales o el total</li>
                                        </ul>
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
        // Datos dinámicos del ENUM pasados desde PHP
        const metodosPago = <?php echo json_encode($metodos_pago); ?>;
        const estadosPago = <?php echo json_encode($estados_pago); ?>;
        const iconosMetodosPago = <?php echo json_encode(array_map('getMetodoPagoIcon', $metodos_pago)); ?>;
        const iconosEstadosPago = <?php echo json_encode(array_map('getEstadoPagoIcon', $estados_pago)); ?>;
        
        let pasajeroCount = 0;
        let precioPorPersona = 0;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            agregarPasajero(); // Agregar el primer pasajero por defecto
            
            // Debug: Mostrar información de los ENUMs cargados (solo en desarrollo)
            console.log('Métodos de pago disponibles:', metodosPago);
            console.log('Estados de pago disponibles:', estadosPago);
            console.log('Iconos para métodos:', iconosMetodosPago);
            console.log('Iconos para estados:', iconosEstadosPago);
        });

        // Manejar selección de tour
        document.getElementById('tourSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const tourInfo = document.getElementById('tourInfo');
            
            if (selectedOption.value) {
                precioPorPersona = parseFloat(selectedOption.dataset.precio);
                
                document.getElementById('tourPrecio').textContent = formatCurrency(precioPorPersona);
                document.getElementById('tourDuracion').textContent = selectedOption.dataset.duracion || '-';
                
                tourInfo.classList.remove('hidden');
            } else {
                tourInfo.classList.add('hidden');
                precioPorPersona = 0;
            }
            
            actualizarResumen();
        });

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

        // Funciones para manejo de pagos
        function setPagoCompleto() {
            const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
            const total = precioPorPersona * numPasajeros;
            
            if (total > 0) {
                document.getElementById('montoPago').value = total.toFixed(2);
                document.getElementById('estadoPago').value = 'Pagado';
                
                // Sugerir método de pago si no está seleccionado
                const metodoPago = document.getElementById('metodoPago');
                if (!metodoPago.value && metodosPago.length > 0) {
                    // Preferir Efectivo si está disponible, sino usar el primero disponible
                    const preferenciaMetodo = metodosPago.includes('Efectivo') ? 'Efectivo' : metodosPago[0];
                    metodoPago.value = preferenciaMetodo;
                }
            } else {
                alert('Primero seleccione un tour y agregue pasajeros para calcular el monto total');
            }
        }

        function setPagoMitad() {
            const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
            const total = precioPorPersona * numPasajeros;
            
            if (total > 0) {
                const mitad = total / 2;
                document.getElementById('montoPago').value = mitad.toFixed(2);
                document.getElementById('estadoPago').value = 'Pagado';
                
                // Sugerir método de pago si no está seleccionado
                const metodoPago = document.getElementById('metodoPago');
                if (!metodoPago.value && metodosPago.length > 0) {
                    // Preferir Efectivo si está disponible, sino usar el primero disponible
                    const preferenciaMetodo = metodosPago.includes('Efectivo') ? 'Efectivo' : metodosPago[0];
                    metodoPago.value = preferenciaMetodo;
                }
            } else {
                alert('Primero seleccione un tour y agregue pasajeros para calcular el monto total');
            }
        }

        function clearPago() {
            document.getElementById('metodoPago').value = '';
            document.getElementById('montoPago').value = '';
            // Usar el estado por defecto dinámicamente
            const estadoDefecto = estadosPago.includes('Pendiente') ? 'Pendiente' : estadosPago[0];
            document.getElementById('estadoPago').value = estadoDefecto;
        }

        // Nueva función para establecer pago por porcentaje
        function setPagoPorcentaje(porcentaje) {
            const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
            const total = precioPorPersona * numPasajeros;
            
            if (total > 0) {
                const montoPorcentaje = (total * porcentaje) / 100;
                document.getElementById('montoPago').value = montoPorcentaje.toFixed(2);
                document.getElementById('estadoPago').value = 'Pagado';
                
                // Sugerir método de pago si no está seleccionado
                const metodoPago = document.getElementById('metodoPago');
                if (!metodoPago.value && metodosPago.length > 0) {
                    const preferenciaMetodo = metodosPago.includes('Efectivo') ? 'Efectivo' : metodosPago[0];
                    metodoPago.value = preferenciaMetodo;
                }
            } else {
                alert('Primero seleccione un tour y agregue pasajeros para calcular el porcentaje');
            }
        }

        // Nueva función para pago personalizado
        function setPagoPersonalizado() {
            const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
            const total = precioPorPersona * numPasajeros;
            
            let mensaje = 'Ingrese el monto del pago:';
            if (total > 0) {
                mensaje += `\n\nReferencia:\n• Total del tour: S/ ${total.toFixed(2)}\n• 50%: S/ ${(total * 0.5).toFixed(2)}\n• 30%: S/ ${(total * 0.3).toFixed(2)}`;
            }
            
            const montoPersonalizado = prompt(mensaje);
            
            if (montoPersonalizado !== null && montoPersonalizado !== '') {
                const monto = parseFloat(montoPersonalizado);
                
                if (!isNaN(monto) && monto > 0) {
                    document.getElementById('montoPago').value = monto.toFixed(2);
                    document.getElementById('estadoPago').value = monto >= total ? 'Pagado' : 'Pagado';
                    
                    // Sugerir método de pago si no está seleccionado
                    const metodoPago = document.getElementById('metodoPago');
                    if (!metodoPago.value && metodosPago.length > 0) {
                        const preferenciaMetodo = metodosPago.includes('Efectivo') ? 'Efectivo' : metodosPago[0];
                        metodoPago.value = preferenciaMetodo;
                    }
                    
                    // Trigger del evento input para actualizar la información contextual
                    document.getElementById('montoPago').dispatchEvent(new Event('input'));
                } else {
                    alert('Por favor ingrese un monto válido mayor a 0');
                }
            }
        }

        // Validar monto del pago cuando cambie
        document.getElementById('montoPago').addEventListener('input', function() {
            const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
            const total = precioPorPersona * numPasajeros;
            const montoPago = parseFloat(this.value) || 0;
            
            // Mostrar información contextual sobre el monto
            const infoElement = this.parentElement.nextElementSibling;
            
            if (montoPago > 0) {
                if (montoPago > total && total > 0) {
                    // Monto mayor al total
                    infoElement.textContent = `Monto mayor al total del tour (+S/ ${(montoPago - total).toFixed(2)})`;
                    infoElement.className = 'text-xs text-blue-600 mt-1 font-medium';
                } else if (montoPago < total && total > 0) {
                    // Pago parcial
                    const porcentaje = ((montoPago / total) * 100).toFixed(1);
                    infoElement.textContent = `Pago parcial: ${porcentaje}% del total`;
                    infoElement.className = 'text-xs text-orange-600 mt-1 font-medium';
                } else if (montoPago === total && total > 0) {
                    // Pago completo
                    infoElement.textContent = 'Pago completo del tour';
                    infoElement.className = 'text-xs text-green-600 mt-1 font-medium';
                } else {
                    // Tour no seleccionado aún
                    infoElement.textContent = 'Monto personalizado';
                    infoElement.className = 'text-xs text-gray-500 mt-1';
                }
            } else {
                // Restaurar texto original
                infoElement.textContent = 'Monto parcial o total del pago';
                infoElement.className = 'text-xs text-gray-500 mt-1';
            }
            
            // Remover cualquier validación de error
            this.setCustomValidity('');
            this.classList.remove('border-red-500');
            
            // Actualizar resumen de pago
            actualizarResumenPago();
        });

        // Validar que si hay método de pago, debe haber monto
        document.getElementById('metodoPago').addEventListener('change', function() {
            const montoPago = document.getElementById('montoPago');
            
            if (this.value && !montoPago.value) {
                montoPago.focus();
                montoPago.setCustomValidity('Debe especificar un monto si selecciona un método de pago');
            } else {
                montoPago.setCustomValidity('');
            }
            
            // Actualizar resumen de pago
            actualizarResumenPago();
        });

        // También agregar listener al estado de pago
        document.getElementById('estadoPago').addEventListener('change', function() {
            actualizarResumenPago();
        });

        // Nueva función para actualizar el resumen de pago
        function actualizarResumenPago() {
            const metodoPago = document.getElementById('metodoPago').value;
            const montoPago = parseFloat(document.getElementById('montoPago').value) || 0;
            const estadoPago = document.getElementById('estadoPago').value;
            const resumenPago = document.getElementById('resumenPago');
            
            if (metodoPago && montoPago > 0) {
                // Mostrar la sección de resumen de pago
                resumenPago.classList.remove('hidden');
                
                // Actualizar valores
                document.getElementById('resumenMontoPago').textContent = formatCurrency(montoPago);
                document.getElementById('resumenEstadoPago').textContent = estadoPago;
                
                // Calcular y mostrar diferencia con el total
                const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
                const total = precioPorPersona * numPasajeros;
                const diferenciaPago = document.getElementById('resumenDiferenciaPago');
                
                if (total > 0) {
                    diferenciaPago.classList.remove('hidden');
                    
                    if (montoPago > total) {
                        const excedente = montoPago - total;
                        diferenciaPago.textContent = `💡 Pago excedente: +${formatCurrency(excedente)}`;
                        diferenciaPago.className = 'mt-2 text-xs text-center p-2 rounded bg-blue-50 text-blue-700 border border-blue-200';
                    } else if (montoPago < total) {
                        const faltante = total - montoPago;
                        const porcentaje = ((montoPago / total) * 100).toFixed(1);
                        diferenciaPago.textContent = `⚠️ Pago parcial: ${porcentaje}% (Falta: ${formatCurrency(faltante)})`;
                        diferenciaPago.className = 'mt-2 text-xs text-center p-2 rounded bg-orange-50 text-orange-700 border border-orange-200';
                    } else {
                        diferenciaPago.textContent = '✅ Pago completo del tour';
                        diferenciaPago.className = 'mt-2 text-xs text-center p-2 rounded bg-green-50 text-green-700 border border-green-200';
                    }
                } else {
                    diferenciaPago.classList.add('hidden');
                }
            } else {
                // Ocultar la sección de resumen de pago
                resumenPago.classList.add('hidden');
            }
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN',
                minimumFractionDigits: 2
            }).format(amount);
        }

        // Validación del formulario
        document.getElementById('formNuevaReserva').addEventListener('submit', function(e) {
            const pasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card');
            
            if (pasajeros.length === 0) {
                e.preventDefault();
                alert('Debe agregar al menos un pasajero');
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

            // Validar información de pago
            const metodoPago = document.getElementById('metodoPago').value;
            const montoPago = parseFloat(document.getElementById('montoPago').value) || 0;
            const estadoPago = document.getElementById('estadoPago').value;
            
            if (metodoPago && montoPago <= 0) {
                e.preventDefault();
                alert('Si selecciona un método de pago, debe especificar un monto mayor a 0');
                document.getElementById('montoPago').focus();
                return false;
            }

            if (!metodoPago && montoPago > 0) {
                e.preventDefault();
                alert('Si especifica un monto de pago, debe seleccionar un método de pago');
                document.getElementById('metodoPago').focus();
                return false;
            }

            // Validar que los valores están en los ENUMs permitidos
            if (metodoPago && !metodosPago.includes(metodoPago)) {
                e.preventDefault();
                alert('El método de pago seleccionado no es válido');
                return false;
            }

            if (estadoPago && !estadosPago.includes(estadoPago)) {
                e.preventDefault();
                alert('El estado de pago seleccionado no es válido');
                return false;
            }

            // Confirmar antes de enviar si hay información de pago
            if (metodoPago && montoPago > 0) {
                const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
                const total = precioPorPersona * numPasajeros;
                let mensajeConfirmacion = `¿Confirma que desea registrar un pago de S/ ${montoPago.toFixed(2)} por ${metodoPago}?`;
                
                // Agregar información contextual sobre el monto
                if (total > 0) {
                    if (montoPago > total) {
                        mensajeConfirmacion += `\n\nNota: El monto es S/ ${(montoPago - total).toFixed(2)} mayor al total del tour.`;
                    } else if (montoPago < total) {
                        const porcentaje = ((montoPago / total) * 100).toFixed(1);
                        mensajeConfirmacion += `\n\nNota: Pago parcial del ${porcentaje}% del total.`;
                    }
                }
                
                const confirmar = confirm(mensajeConfirmacion);
                if (!confirmar) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>
