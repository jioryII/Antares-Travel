<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

$admin = getCurrentAdmin();
$page_title = "Gestión de Tours Diarios";

// Si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fecha = $_POST['fecha'];
    $id_tour = $_POST['id_tour'];
    $id_guia = $_POST['id_guia'];
    $id_chofer = $_POST['id_chofer'];
    $id_vehiculo = $_POST['id_vehiculo'];
    $num_adultos = $_POST['num_adultos'] ?? 0;
    $num_ninos = $_POST['num_ninos'] ?? 0;
    $hora_salida = $_POST['hora_salida'];
    $hora_retorno = $_POST['hora_retorno'];
    $observaciones = $_POST['observaciones'] ?? '';

    try {
        $connection = getConnection();
        
        // Iniciar transacción
        $connection->beginTransaction();

        // Insertar tour diario
        $sql = "INSERT INTO tours_diarios 
            (fecha, id_tour, id_guia, id_chofer, id_vehiculo, num_adultos, num_ninos, hora_salida, hora_retorno, observaciones) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$fecha, $id_tour, $id_guia, $id_chofer, $id_vehiculo, $num_adultos, $num_ninos, $hora_salida, $hora_retorno, $observaciones]);
        
        $tour_diario_id = $connection->lastInsertId();

        // Actualizar disponibilidad de guías (usando estructura real de la tabla)
        $sql_check_guia = "SELECT id_disponibilidad FROM disponibilidad_guias WHERE id_guia = ? AND fecha = ?";
        $stmt_check = $connection->prepare($sql_check_guia);
        $stmt_check->execute([$id_guia, $fecha]);
        
        if ($stmt_check->fetch()) {
            $sql_update_guia = "UPDATE disponibilidad_guias SET estado = 'Ocupado' WHERE id_guia = ? AND fecha = ?";
            $stmt_update = $connection->prepare($sql_update_guia);
            $stmt_update->execute([$id_guia, $fecha]);
        } else {
            $sql_insert_guia = "INSERT INTO disponibilidad_guias (id_guia, fecha, estado) VALUES (?, ?, 'Ocupado')";
            $stmt_insert = $connection->prepare($sql_insert_guia);
            $stmt_insert->execute([$id_guia, $fecha]);
        }

        // Actualizar disponibilidad de vehículos (usando estructura real de la tabla)
        $sql_check_vehiculo = "SELECT id_disponibilidad FROM disponibilidad_vehiculos WHERE id_vehiculo = ? AND fecha = ?";
        $stmt_check = $connection->prepare($sql_check_vehiculo);
        $stmt_check->execute([$id_vehiculo, $fecha]);
        
        if ($stmt_check->fetch()) {
            $sql_update_vehiculo = "UPDATE disponibilidad_vehiculos SET estado = 'Ocupado' WHERE id_vehiculo = ? AND fecha = ?";
            $stmt_update = $connection->prepare($sql_update_vehiculo);
            $stmt_update->execute([$id_vehiculo, $fecha]);
        } else {
            $sql_insert_vehiculo = "INSERT INTO disponibilidad_vehiculos (id_vehiculo, fecha, estado) VALUES (?, ?, 'Ocupado')";
            $stmt_insert = $connection->prepare($sql_insert_vehiculo);
            $stmt_insert->execute([$id_vehiculo, $fecha]);
        }

        // Nota: No hay tabla separada para disponibilidad de choferes, 
        // la disponibilidad se maneja a través de la tabla tours_diarios

        // Confirmar transacción
        $connection->commit();
        
        // Log de auditoría
        logActivity($admin['id_admin'], 'INSERT', 'tours_diarios', $tour_diario_id, "Tour diario registrado para fecha: $fecha");
        
        setFlashMessage('success', '✅ Tour diario registrado correctamente.');
        
    } catch (Exception $e) {
        $connection->rollback();
        setFlashMessage('error', '❌ Error al registrar el tour diario: ' . $e->getMessage());
    }
}

// Obtener información de tours para cargar horas por defecto
$tours_info = [];
try {
    $connection = getConnection();
    $sql = "SELECT id_tour, titulo, hora_salida, hora_llegada FROM tours ORDER BY titulo";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $tours = $stmt->fetchAll();
    
    foreach ($tours as $tour) {
        $tours_info[$tour['id_tour']] = [
            'titulo' => $tour['titulo'],
            'hora_salida' => $tour['hora_salida'],
            'hora_llegada' => $tour['hora_llegada']
        ];
    }
} catch (Exception $e) {
    $tours = [];
}

// Obtener tours diarios recientes
try {
    $sql = "SELECT td.*, t.titulo as tour_titulo, 
                   CONCAT(g.nombre, ' ', g.apellido) as guia_nombre,
                   CONCAT(c.nombre, ' ', c.apellido) as chofer_nombre,
                   CONCAT(v.marca, ' ', v.modelo, ' - ', v.placa) as vehiculo_info
            FROM tours_diarios td
            LEFT JOIN tours t ON td.id_tour = t.id_tour
            LEFT JOIN guias g ON td.id_guia = g.id_guia
            LEFT JOIN choferes c ON td.id_chofer = c.id_chofer
            LEFT JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo
            ORDER BY td.fecha DESC, td.hora_salida DESC
            LIMIT 10";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $tours_diarios_recientes = $stmt->fetchAll();
} catch (Exception $e) {
    $tours_diarios_recientes = [];
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .no-disponible { color: #f59e0b; font-style: italic; }
        .disponible { color: #10b981; }
        
        /* Glassmorphism effects */
        .glass-card {
            background: rgba(243, 244, 246, 0.98);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(209, 213, 219, 0.4);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
        }
        
        .form-glass {
            background: linear-gradient(135deg, rgba(243, 244, 246, 0.99) 0%, rgba(229, 231, 235, 0.97) 100%);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(209, 213, 219, 0.5);
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.15);
        }
        
        /* Soft background colors */
        .soft-blue-bg {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid rgba(191, 219, 254, 0.3);
        }
        
        .soft-gray-bg {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        }
        
        /* Animated gradient backgrounds - Removed for clean design */
        
        /* Floating particles animation - Removed for clean design */
        
        /* Enhanced form styles */
        .form-input {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(209, 213, 219, 0.7);
        }
        
        .form-input:focus {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.15);
            background: rgba(255, 255, 255, 1);
            border-color: #3b82f6;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .mobile-card {
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                border: 1px solid rgba(209, 213, 219, 0.6);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            }
            
            .mobile-grid {
                display: block;
            }
            
            .desktop-table {
                display: none;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-grid {
                display: none;
            }
            
            .desktop-table {
                display: block;
            }
        }
        
        /* Button hover effects */
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-white min-h-screen">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen soft-gray-bg">
            <br><br><br>
            <div class="p-4 lg:p-8">
                <!-- Encabezado mejorado -->
                <div class="mb-6 lg:mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo $page_title; ?></h1>
                            <p class="text-gray-600 mt-1 text-sm lg:text-base">Registra y gestiona tours diarios con asignación de recursos</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="glass-card rounded-lg p-3 text-center">
                                <div class="text-blue-600 text-xl mb-1">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="text-xs text-gray-600 font-medium">Tours Programados</div>
                                <div class="text-lg font-bold text-gray-900"><?php echo count($tours_diarios_recientes); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensajes Flash mejorados -->
                <?php if ($success_msg = getFlashMessage('success')): ?>
                    <div class="mb-4 lg:mb-6 glass-card border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-r-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
                            <span class="font-medium"><?php echo $success_msg; ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_msg = getFlashMessage('error')): ?>
                    <div class="mb-4 lg:mb-6 glass-card border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-r-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-3 text-lg"></i>
                            <span class="font-medium"><?php echo $error_msg; ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
                    <!-- Formulario de Registro -->
                    <div class="form-glass rounded-xl shadow-xl p-6 lg:p-8">
                        <div class="flex items-center mb-6 lg:mb-8">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-3 mr-4">
                                <i class="fas fa-calendar-plus text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl lg:text-2xl font-bold text-gray-900">Registrar Nuevo Tour Diario</h2>
                                <p class="text-gray-600 text-sm">Complete los datos para programar un tour</p>
                            </div>
                        </div>

                    <form method="POST" class="space-y-6">
                        <!-- Fecha del Tour -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                Fecha del Tour *
                            </label>
                            <input type="date" name="fecha" id="fecha" onchange="cargarDisponibles()" 
                                   class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm" 
                                   required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Seleccionar Tour -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-map-marked-alt text-blue-500 mr-2"></i>
                                Tour *
                            </label>
                            <select name="id_tour" id="id_tour" onchange="cargarHorasTour()" 
                                    class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm" 
                                    required>
                                <option value="">Seleccione un tour</option>
                                <?php foreach ($tours as $tour): ?>
                                    <option value="<?php echo $tour['id_tour']; ?>">
                                        <?php echo htmlspecialchars($tour['titulo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Recursos en grid responsive -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-users-cog text-blue-500 mr-2"></i>
                                Asignación de Recursos
                            </h3>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <!-- Guía -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-user-tie text-green-500 mr-2"></i>
                                        Guía *
                                    </label>
                                    <select name="id_guia" id="id_guia" 
                                            class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm" 
                                            required>
                                        <option value="">Seleccione una fecha primero</option>
                                    </select>
                                </div>

                                <!-- Chofer -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-id-card text-purple-500 mr-2"></i>
                                        Chofer *
                                    </label>
                                    <select name="id_chofer" id="id_chofer" 
                                            class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm" 
                                            required>
                                        <option value="">Seleccione una fecha primero</option>
                                    </select>
                                </div>

                                <!-- Vehículo -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-car text-orange-500 mr-2"></i>
                                        Vehículo *
                                    </label>
                                    <select name="id_vehiculo" id="id_vehiculo" 
                                            class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm" 
                                            required>
                                        <option value="">Seleccione una fecha primero</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Participantes -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-users text-blue-500 mr-2"></i>
                                Participantes
                            </h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-user text-gray-600 mr-2"></i>
                                        Adultos
                                    </label>
                                    <input type="number" name="num_adultos" min="0" value="0" 
                                           class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-child text-gray-600 mr-2"></i>
                                        Niños
                                    </label>
                                    <input type="number" name="num_ninos" min="0" value="0" 
                                           class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Horarios -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-clock text-blue-500 mr-2"></i>
                                Horarios
                            </h3>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-play-circle text-green-500 mr-2"></i>
                                        Hora de Salida *
                                    </label>
                                    <input type="time" name="hora_salida" id="hora_salida" 
                                           class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm" 
                                           required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-stop-circle text-red-500 mr-2"></i>
                                        Hora de Retorno
                                    </label>
                                    <input type="time" name="hora_retorno" id="hora_retorno" 
                                           class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-sticky-note text-yellow-500 mr-2"></i>
                                Observaciones
                            </label>
                            <textarea name="observaciones" rows="3" 
                                      class="form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 shadow-sm resize-none" 
                                      placeholder="Observaciones adicionales sobre el tour..."></textarea>
                        </div>

                        <!-- Botón de envío -->
                        <div class="pt-6 border-t border-gray-200">
                            <button type="submit" 
                                    class="btn-primary w-full text-white py-4 px-6 rounded-lg font-semibold text-lg shadow-lg">
                                <i class="fas fa-save mr-3"></i>Registrar Tour Diario
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Tours Diarios Recientes -->
                <div class="form-glass rounded-xl shadow-xl p-6 lg:p-8">
                    <div class="flex items-center mb-6 lg:mb-8">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-full p-3 mr-4">
                            <i class="fas fa-history text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl lg:text-2xl font-bold text-gray-900">Tours Diarios Recientes</h2>
                            <p class="text-gray-600 text-sm">Últimos tours programados</p>
                        </div>
                    </div>

                    <?php if (empty($tours_diarios_recientes)): ?>
                        <div class="text-center py-12">
                            <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-calendar-times text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No hay tours diarios registrados</h3>
                            <p class="text-gray-500">Los tours programados aparecerán aquí</p>
                        </div>
                    <?php else: ?>
                        <!-- Vista móvil - Cards -->
                        <div class="mobile-grid space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($tours_diarios_recientes as $tour_diario): ?>
                                <div class="mobile-card rounded-lg p-4 hover:shadow-lg transition-all duration-300">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="font-semibold text-gray-900 text-sm">
                                            <?php echo htmlspecialchars($tour_diario['tour_titulo']); ?>
                                        </h3>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                                            <?php echo formatDate($tour_diario['fecha'], 'd/m/Y'); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="space-y-2 text-xs text-gray-600">
                                        <div class="flex items-center">
                                            <i class="fas fa-user-tie w-4 text-green-500 mr-2"></i>
                                            <span class="font-medium">Guía:</span>
                                            <span class="ml-1"><?php echo htmlspecialchars($tour_diario['guia_nombre']); ?></span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-id-card w-4 text-purple-500 mr-2"></i>
                                            <span class="font-medium">Chofer:</span>
                                            <span class="ml-1"><?php echo htmlspecialchars($tour_diario['chofer_nombre']); ?></span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-car w-4 text-orange-500 mr-2"></i>
                                            <span class="font-medium">Vehículo:</span>
                                            <span class="ml-1"><?php echo htmlspecialchars($tour_diario['vehiculo_info']); ?></span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-users w-4 text-blue-500 mr-2"></i>
                                            <span class="font-medium">Participantes:</span>
                                            <span class="ml-1"><?php echo $tour_diario['num_adultos']; ?> adultos, <?php echo $tour_diario['num_ninos']; ?> niños</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-clock w-4 text-gray-500 mr-2"></i>
                                            <span class="font-medium">Horario:</span>
                                            <span class="ml-1"><?php echo $tour_diario['hora_salida']; ?> - <?php echo $tour_diario['hora_retorno']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($tour_diario['observaciones'])): ?>
                                        <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                            <div class="flex items-start">
                                                <i class="fas fa-sticky-note text-yellow-500 mr-2 mt-0.5 text-xs"></i>
                                                <div>
                                                    <span class="text-xs font-medium text-yellow-800">Observaciones:</span>
                                                    <p class="text-xs text-yellow-700 mt-1"><?php echo htmlspecialchars($tour_diario['observaciones']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Vista desktop - Tabla -->
                        <div class="desktop-table overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50 border-b-2 border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tour</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Personal</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Vehículo</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Participantes</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Horario</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($tours_diarios_recientes as $tour_diario): ?>
                                        <tr class="hover:bg-blue-50 transition-colors duration-200">
                                            <td class="px-6 py-4">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($tour_diario['tour_titulo']); ?>
                                                    </div>
                                                    <?php if (!empty($tour_diario['observaciones'])): ?>
                                                        <div class="text-xs text-gray-500 mt-1 flex items-center">
                                                            <i class="fas fa-sticky-note text-yellow-500 mr-1"></i>
                                                            <?php echo htmlspecialchars(substr($tour_diario['observaciones'], 0, 50)); ?>
                                                            <?php if (strlen($tour_diario['observaciones']) > 50): ?>...<?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                                                    <?php echo formatDate($tour_diario['fecha'], 'd/m/Y'); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="space-y-1">
                                                    <div class="text-sm flex items-center">
                                                        <i class="fas fa-user-tie text-green-500 mr-2"></i>
                                                        <span><?php echo htmlspecialchars($tour_diario['guia_nombre']); ?></span>
                                                    </div>
                                                    <div class="text-sm flex items-center">
                                                        <i class="fas fa-id-card text-purple-500 mr-2"></i>
                                                        <span><?php echo htmlspecialchars($tour_diario['chofer_nombre']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm flex items-center">
                                                    <i class="fas fa-car text-orange-500 mr-2"></i>
                                                    <span><?php echo htmlspecialchars($tour_diario['vehiculo_info']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900 flex items-center">
                                                    <i class="fas fa-users text-blue-500 mr-2"></i>
                                                    <span><?php echo $tour_diario['num_adultos']; ?> adultos</span>
                                                    <?php if ($tour_diario['num_ninos'] > 0): ?>
                                                        <span class="text-gray-500 ml-1">, <?php echo $tour_diario['num_ninos']; ?> niños</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="space-y-1">
                                                    <div class="text-sm flex items-center">
                                                        <i class="fas fa-play-circle text-green-500 mr-1"></i>
                                                        <span><?php echo $tour_diario['hora_salida']; ?></span>
                                                    </div>
                                                    <?php if (!empty($tour_diario['hora_retorno'])): ?>
                                                        <div class="text-sm flex items-center">
                                                            <i class="fas fa-stop-circle text-red-500 mr-1"></i>
                                                            <span><?php echo $tour_diario['hora_retorno']; ?></span>
                                                        </div>
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
            </div>
        </div>
    </div>

    <!-- Indicador de carga global -->
    <div id="loading-indicator" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-xl p-8 flex items-center space-x-4 border border-gray-300 shadow-2xl">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
            <span class="text-gray-700 font-medium text-lg">Cargando disponibilidad...</span>
        </div>
    </div>

    <script>
        // Datos de tours con sus horarios
        const toursInfo = <?php echo json_encode($tours_info); ?>;
        
        // Funciones de UI
        function mostrarCargando() {
            document.getElementById('loading-indicator').classList.remove('hidden');
        }
        
        function ocultarCargando() {
            document.getElementById('loading-indicator').classList.add('hidden');
        }
        
        function mostrarError(mensaje) {
            // Crear notificación de error
            const notificacion = document.createElement('div');
            notificacion.className = 'fixed top-4 right-4 bg-white border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-r-lg z-50 max-w-md shadow-xl border border-gray-300';
            notificacion.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-3 text-lg"></i>
                    <span class="font-medium">${mensaje}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(notificacion);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (notificacion.parentElement) {
                    notificacion.remove();
                }
            }, 5000);
        }
        
        function cargarDisponibles() {
            const fecha = document.getElementById("fecha").value;
            if (!fecha) {
                mostrarError("Por favor, seleccione una fecha primero.");
                return;
            }

            mostrarCargando();

            // Mostrar loading en los selects
            const guiaSelect = document.getElementById("id_guia");
            const choferSelect = document.getElementById("id_chofer");
            const vehiculoSelect = document.getElementById("id_vehiculo");
            
            guiaSelect.innerHTML = "<option value=''>Cargando...</option>";
            choferSelect.innerHTML = "<option value=''>Cargando...</option>";
            vehiculoSelect.innerHTML = "<option value=''>Cargando...</option>";

            fetch(`tours_diarios_ajax.php?action=check_availability&fecha=${fecha}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                ocultarCargando();
                if (!data.success) {
                    if (data.redirect_login) {
                        mostrarError(`${data.error}\n\nSe redirigirá al login.`);
                        setTimeout(() => {
                            window.location.href = data.login_url || '../../auth/login.php';
                        }, 2000);
                        return;
                    }
                    if (data.install_required) {
                        mostrarError(`${data.error}\n\nSe redirigirá al instalador.`);
                        setTimeout(() => {
                            window.location.href = '../../install_tours_diarios.php';
                        }, 2000);
                        return;
                    }
                    throw new Error(data.error || 'Error desconocido');
                }
                
                // Cargar guías con mejor UX
                guiaSelect.innerHTML = "";
                if (!data.data.guias || data.data.guias.length === 0) {
                    guiaSelect.innerHTML = "<option value=''>No hay guías disponibles</option>";
                } else {
                    data.data.guias.forEach(g => {
                        const option = document.createElement("option");
                        option.value = g.id_guia;
                        option.textContent = `${g.nombre} ${g.apellido}`;
                        
                        if (g.estado === 'Ocupado') {
                            option.classList.add("no-disponible");
                            option.textContent += " (No disponible)";
                            option.disabled = true;
                        } else {
                            option.classList.add("disponible");
                        }
                        
                        guiaSelect.appendChild(option);
                    });
                }

                // Cargar choferes con mejor UX
                choferSelect.innerHTML = "";
                if (!data.data.choferes || data.data.choferes.length === 0) {
                    choferSelect.innerHTML = "<option value=''>No hay choferes disponibles</option>";
                } else {
                    data.data.choferes.forEach(c => {
                        const option = document.createElement("option");
                        option.value = c.id_chofer;
                        option.textContent = `${c.nombre} ${c.apellido}`;
                        
                        if (c.estado === 'Ocupado') {
                            option.classList.add("no-disponible");
                            option.textContent += " (No disponible)";
                            option.disabled = true;
                        } else {
                            option.classList.add("disponible");
                        }
                        
                        choferSelect.appendChild(option);
                    });
                }

                // Cargar vehículos con mejor UX
                vehiculoSelect.innerHTML = "";
                if (!data.data.vehiculos || data.data.vehiculos.length === 0) {
                    vehiculoSelect.innerHTML = "<option value=''>No hay vehículos disponibles</option>";
                } else {
                    data.data.vehiculos.forEach(v => {
                        const option = document.createElement("option");
                        option.value = v.id_vehiculo;
                        option.textContent = `[${v.placa}] ${v.marca} ${v.modelo}`;
                        
                        if (v.estado === 'Ocupado') {
                            option.classList.add("no-disponible");
                            option.textContent += " (No disponible)";
                            option.disabled = true;
                        } else {
                            option.classList.add("disponible");
                        }
                        
                        vehiculoSelect.appendChild(option);
                    });
                }
                
                // Debug info si está habilitado
                if (data.debug) {
                    console.log('Disponibilidad cargada:', data.debug);
                }
            })
            .catch(error => {
                ocultarCargando();
                console.error("Error al cargar disponibilidad:", error);
                
                let errorMessage = "Error al cargar la disponibilidad. ";
                if (error.message.includes('HTTP error')) {
                    errorMessage += "Error de conexión con el servidor.";
                } else if (error.message.includes('tabla')) {
                    errorMessage += "Las tablas necesarias no existen. ¿Desea instalar el módulo?";
                    if (confirm(errorMessage)) {
                        window.location.href = '../../install_tours_diarios.php';
                        return;
                    }
                } else {
                    errorMessage += error.message || "Por favor, intente nuevamente.";
                }
                
                mostrarError(errorMessage);
                
                // Restaurar estado inicial en caso de error
                guiaSelect.innerHTML = "<option value=''>Error al cargar</option>";
                choferSelect.innerHTML = "<option value=''>Error al cargar</option>";
                vehiculoSelect.innerHTML = "<option value=''>Error al cargar</option>";
            });
        }
        
        function cargarHorasTour() {
            const tourId = document.getElementById("id_tour").value;
            if (toursInfo[tourId]) {
                document.getElementById("hora_salida").value = toursInfo[tourId].hora_salida || '';
                document.getElementById("hora_retorno").value = toursInfo[tourId].hora_llegada || '';
            } else {
                document.getElementById("hora_salida").value = '';
                document.getElementById("hora_retorno").value = '';
            }
        }
        
        // Mejorar experiencia de usuario del formulario
        function inicializarFormulario() {
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Registrando...';
                    
                    // Re-habilitar después de 5 segundos por si hay error
                    setTimeout(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save mr-3"></i>Registrar Tour Diario';
                        }
                    }, 5000);
                });
            }
        }
        
        // Cargar horas al iniciar si ya hay un tour seleccionado
        document.addEventListener("DOMContentLoaded", function() {
            const tourSelect = document.getElementById("id_tour");
            if (tourSelect.value) {
                cargarHorasTour();
            }
            
            inicializarFormulario();
        });
    </script>
</body>
</html>
