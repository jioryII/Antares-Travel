<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

$admin = getCurrentAdmin();
$page_title = "Gestión de Tours Diarios";

// Si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'create';
    
    if ($action === 'delete') {
        // Acción de eliminar
        $id_tour_diario = $_POST['id_tour_diario'];
        
        try {
            $connection = getConnection();
            $connection->beginTransaction();
            
            // Obtener datos del tour diario antes de eliminar para limpiar disponibilidades
            $sql_select = "SELECT fecha, id_guia, id_vehiculo FROM tours_diarios WHERE id_tour_diario = ?";
            $stmt_select = $connection->prepare($sql_select);
            $stmt_select->execute([$id_tour_diario]);
            $tour_data = $stmt_select->fetch();
            
            if ($tour_data) {
                // Eliminar el tour diario
                $sql_delete = "DELETE FROM tours_diarios WHERE id_tour_diario = ?";
                $stmt_delete = $connection->prepare($sql_delete);
                $stmt_delete->execute([$id_tour_diario]);
                
                // Liberar disponibilidad de guía
                $sql_update_guia = "UPDATE disponibilidad_guias SET estado = 'Disponible' WHERE id_guia = ? AND fecha = ?";
                $stmt_update_guia = $connection->prepare($sql_update_guia);
                $stmt_update_guia->execute([$tour_data['id_guia'], $tour_data['fecha']]);
                
                // Liberar disponibilidad de vehículo
                $sql_update_vehiculo = "UPDATE disponibilidad_vehiculos SET estado = 'Disponible' WHERE id_vehiculo = ? AND fecha = ?";
                $stmt_update_vehiculo = $connection->prepare($sql_update_vehiculo);
                $stmt_update_vehiculo->execute([$tour_data['id_vehiculo'], $tour_data['fecha']]);
                
                // Log de auditoría
                logActivity($admin['id_admin'], 'DELETE', 'tours_diarios', $id_tour_diario, "Tour diario eliminado para fecha: {$tour_data['fecha']}");
                
                $connection->commit();
                setFlashMessage('success', '✅ Tour diario eliminado correctamente.');
            } else {
                throw new Exception('Tour diario no encontrado.');
            }
            
        } catch (Exception $e) {
            $connection->rollback();
            setFlashMessage('error', '❌ Error al eliminar el tour diario: ' . $e->getMessage());
        }
        
        // Redireccionar para evitar reenvío del formulario
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
        
    } elseif ($action === 'create') {
        // Acción de crear (código existente)
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
        
        // Redireccionar para evitar reenvío del formulario
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
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
    $sql = "SELECT td.id_tour_diario, td.fecha, td.id_tour, td.id_guia, td.id_chofer, td.id_vehiculo, 
                   td.num_adultos, td.num_ninos, td.hora_salida, td.hora_retorno, td.observaciones,
                   t.titulo as tour_titulo, 
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
            
            /* Configuración de scroll para la tabla desktop */
            .desktop-table {
                border: 2px solid #d1d5db;
                border-radius: 0.5rem;
                background: white;
                max-height: 600px;
                overflow-y: auto;
                overflow-x: auto;
            }
            
            /* Header sticky */
            .desktop-table thead {
                position: sticky;
                top: 0;
                z-index: 10;
                background: #f9fafb;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            .desktop-table thead th {
                background: #f9fafb;
                position: sticky;
                top: 0;
            }
            
            /* Scroll personalizado */
            .desktop-table::-webkit-scrollbar {
                height: 8px;
                width: 8px;
            }
            
            .desktop-table::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 4px;
            }
            
            .desktop-table::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 4px;
            }
            
            .desktop-table::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
            
            /* Mejorar separación visual en scroll */
            .desktop-table tbody tr {
                border-bottom: 1px solid #e5e7eb;
            }
            
            .desktop-table tbody tr:hover {
                background-color: #f8fafc;
            }
            
            /* Optimizar contenido de celdas para scroll */
            .desktop-table td {
                white-space: nowrap;
                padding: 12px 16px;
            }
            
            /* Permitir wrap solo en la columna de observaciones */
            .desktop-table td .text-xs.text-gray-500 {
                white-space: normal;
                max-width: 200px;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
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
            <br>
            <div class="p-4 lg:p-8">
                <!-- Header principal simplificado -->
                <div class="mb-6 lg:mb-8">
                    <br><br><br>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-calendar-check text-blue-600 mr-3"></i>Gestión de Tours Diarios
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600">Registra y gestiona tours diarios con asignación de recursos</p>
                        </div>
                    </div>
                </div>

                <!-- Barra de control compacta -->
                <div class="bg-white rounded-lg shadow-lg border-2 border-gray-300 mb-6 p-4">
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
                        <!-- Estadística compacta - IZQUIERDA -->
                        <div class="flex items-center space-x-3">
                            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg p-3 text-white shadow-md">
                                <i class="fas fa-calendar-check text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-600">Tours Programados</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo count($tours_diarios_recientes); ?></p>
                            </div>
                        </div>
                        
                        <!-- Controles - DERECHA -->
                        <div class="flex items-center gap-3 flex-wrap justify-center lg:justify-end">
                            <!-- Estado del sistema -->
                            <div class="bg-green-50 text-green-700 px-3 py-2 rounded-lg border border-green-300 font-medium text-sm whitespace-nowrap">
                                <i class="fas fa-check-circle mr-1"></i>
                                Sistema Activo
                            </div>
                            
                            <!-- Botón nuevo tour -->
                            <button onclick="abrirModal()" 
                                    class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-4 py-2.5 rounded-lg font-medium shadow-md hover:shadow-lg transition-all duration-200 text-sm whitespace-nowrap">
                                <i class="fas fa-plus mr-2"></i>Nuevo Tour Diario
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Módulo de Filtros Minimalista -->
                <div class="bg-white rounded-lg shadow-lg border-2 border-gray-300 mb-6 p-4">
                    <div class="flex flex-col lg:flex-row items-center gap-4">
                        <!-- Título de filtros -->
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-filter text-blue-600"></i>
                            <span class="font-semibold text-gray-700">Filtros:</span>
                        </div>
                        
                        <!-- Filtros -->
                        <div class="flex flex-col lg:flex-row items-center gap-3 flex-1">
                            <!-- Filtro por fecha -->
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600 whitespace-nowrap">Fecha:</label>
                                <input type="date" id="filtro-fecha" 
                                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <!-- Filtro por tour -->
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600 whitespace-nowrap">Tour:</label>
                                <select id="filtro-tour" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos los tours</option>
                                    <?php 
                                    $tours_utilizados = [];
                                    foreach ($tours_diarios_recientes as $td) {
                                        if (!in_array($td['id_tour'], $tours_utilizados)) {
                                            $tours_utilizados[] = $td['id_tour'];
                                            echo "<option value='{$td['id_tour']}'>" . htmlspecialchars($td['tour_titulo']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <!-- Filtro por guía -->
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600 whitespace-nowrap">Guía:</label>
                                <select id="filtro-guia" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todas las guías</option>
                                    <?php 
                                    $guias_utilizados = [];
                                    foreach ($tours_diarios_recientes as $td) {
                                        if (!in_array($td['id_guia'], $guias_utilizados)) {
                                            $guias_utilizados[] = $td['id_guia'];
                                            echo "<option value='{$td['id_guia']}'>" . htmlspecialchars($td['guia_nombre']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Botón limpiar filtros -->
                        <button onclick="limpiarFiltros()" 
                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm border border-gray-300 transition-colors">
                            <i class="fas fa-eraser mr-1"></i>Limpiar
                        </button>
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

                <!-- Tabla de Tours Diarios con scroll -->
                <div class="bg-white rounded-lg shadow-lg border-2 border-gray-300 overflow-hidden">
                    <?php if (empty($tours_diarios_recientes)): ?>
                        <div class="text-center py-12">
                            <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-calendar-times text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No hay tours diarios registrados</h3>
                            <p class="text-gray-500">Los tours programados aparecerán aquí</p>
                        </div>
                    <?php else: ?>
                        <!-- Vista móvil - Cards con scroll --> -->
                        <div class="mobile-grid space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($tours_diarios_recientes as $tour_diario): ?>
                                <div class="bg-white border-2 border-gray-300 rounded-lg p-4 hover:shadow-lg transition-all duration-300">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="font-semibold text-gray-900 text-sm">
                                            <?php echo htmlspecialchars($tour_diario['tour_titulo']); ?>
                                        </h3>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium border border-blue-200">
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
                                    
                                    <!-- Botón eliminar para móvil -->
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <button onclick="eliminarTourDiario(<?php echo $tour_diario['id_tour_diario']; ?>, '<?php echo htmlspecialchars($tour_diario['tour_titulo'], ENT_QUOTES); ?>', '<?php echo formatDate($tour_diario['fecha'], 'd/m/Y'); ?>')" 
                                                class="w-full bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded-lg text-sm border border-red-300 transition-colors">
                                            <i class="fas fa-trash mr-2"></i>Eliminar Tour
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Vista desktop - Tabla -->
                        <div class="desktop-table">
                            <table class="min-w-full">
                                <thead class="sticky-header bg-gray-100 border-b-2 border-gray-300">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tour</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Personal</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Vehículo</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Participantes</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Horario</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($tours_diarios_recientes as $tour_diario): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200 border-b border-gray-200">
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
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex space-x-2">
                                                    <button onclick="eliminarTourDiario(<?php echo $tour_diario['id_tour_diario']; ?>, '<?php echo htmlspecialchars($tour_diario['tour_titulo'], ENT_QUOTES); ?>', '<?php echo formatDate($tour_diario['fecha'], 'd/m/Y'); ?>')" 
                                                            class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg text-sm border border-red-300 transition-colors">
                                                        <i class="fas fa-trash mr-1"></i>
                                                    </button>
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
            // Verificar si estamos en el modal o en la página
            const fechaInput = document.getElementById("fechaModal") || document.getElementById("fecha");
            const fecha = fechaInput ? fechaInput.value : '';
            
            if (!fecha) {
                mostrarError("Por favor, seleccione una fecha primero.");
                return;
            }

            mostrarCargando();

            // Obtener los selects correctos dependiendo del contexto
            const guiaSelect = document.getElementById("id_guiaModal") || document.getElementById("id_guia");
            const choferSelect = document.getElementById("id_choferModal") || document.getElementById("id_chofer");
            const vehiculoSelect = document.getElementById("id_vehiculoModal") || document.getElementById("id_vehiculo");
            
            if (guiaSelect) guiaSelect.innerHTML = "<option value=''>Cargando...</option>";
            if (choferSelect) choferSelect.innerHTML = "<option value=''>Cargando...</option>";
            if (vehiculoSelect) vehiculoSelect.innerHTML = "<option value=''>Cargando...</option>";

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
            // Verificar si estamos en el modal o en la página
            const tourSelect = document.getElementById("id_tourModal") || document.getElementById("id_tour");
            const tourId = tourSelect ? tourSelect.value : '';
            
            const horaSalidaInput = document.getElementById("hora_salidaModal") || document.getElementById("hora_salida");
            const horaRetornoInput = document.getElementById("hora_retornoModal") || document.getElementById("hora_retorno");
            
            if (toursInfo[tourId] && horaSalidaInput && horaRetornoInput) {
                horaSalidaInput.value = toursInfo[tourId].hora_salida || '';
                horaRetornoInput.value = toursInfo[tourId].hora_llegada || '';
            } else if (horaSalidaInput && horaRetornoInput) {
                horaSalidaInput.value = '';
                horaRetornoInput.value = '';
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
            if (tourSelect && tourSelect.value) {
                cargarHorasTour();
            }
            
            inicializarFormulario();
        });
        
        // Función para abrir el modal
        function abrirModal() {
            const modal = document.getElementById('modalTourDiario');
            if (modal) {
                // Limpiar formulario
                const form = document.getElementById('formTourDiario');
                if (form) {
                    form.reset();
                }
                
                // Mostrar modal
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                
                // Enfocar en el primer campo
                setTimeout(() => {
                    const fechaInput = document.getElementById('fechaModal');
                    if (fechaInput) {
                        fechaInput.focus();
                    }
                }, 100);
            }
        }
        
        // Función para cerrar el modal
        function cerrarModal() {
            const modal = document.getElementById('modalTourDiario');
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                
                // Limpiar mensajes de error
                const errorMessages = modal.querySelectorAll('.error-message');
                errorMessages.forEach(msg => msg.remove());
            }
        }
        
        // Cerrar modal con ESC o clic fuera
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModal();
            }
        });
        
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('modalTourDiario');
            if (modal && event.target === modal) {
                cerrarModal();
            }
        });
        
        // Función para eliminar tour diario
        function eliminarTourDiario(id, titulo, fecha) {
            if (confirm(`¿Está seguro de que desea eliminar el tour "${titulo}" programado para el ${fecha}?\n\nEsta acción no se puede deshacer.`)) {
                // Crear formulario para enviar la petición de eliminación
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id_tour_diario';
                idInput.value = id;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                
                // Enviar formulario
                form.submit();
            }
        }
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            document.getElementById('filtro-fecha').value = '';
            document.getElementById('filtro-tour').value = '';
            document.getElementById('filtro-guia').value = '';
            aplicarFiltros();
        }
        
        // Función para aplicar filtros
        function aplicarFiltros() {
            const fechaFiltro = document.getElementById('filtro-fecha').value;
            const tourFiltro = document.getElementById('filtro-tour').value;
            const guiaFiltro = document.getElementById('filtro-guia').value;
            
            // Obtener todas las filas de la tabla (desktop)
            const filasTabla = document.querySelectorAll('.desktop-table tbody tr');
            // Obtener todas las cards (mobile)
            const cardsMobile = document.querySelectorAll('.mobile-grid > div');
            
            let filasMostradas = 0;
            
            // Filtrar filas de tabla (desktop)
            filasTabla.forEach((fila, index) => {
                let mostrar = true;
                
                // Obtener datos de la fila - usando atributos data-* para identificar mejor
                const fechaCelda = fila.querySelector('td:nth-child(2) span').textContent.trim();
                const tourCelda = fila.querySelector('td:nth-child(1) .text-sm').textContent.trim();
                
                // Para el filtro de guía, buscar en el texto que contiene el nombre de la guía
                const personalCelda = fila.querySelector('td:nth-child(3)');
                const guiaElement = personalCelda.querySelector('.fa-user-tie');
                const guiaCelda = guiaElement ? guiaElement.parentElement.textContent.trim() : '';
                
                // Aplicar filtros
                if (fechaFiltro && !fechaCelda.includes(fechaFiltro.split('-').reverse().join('/'))) {
                    mostrar = false;
                }
                
                // Para el filtro de tour, si hay un valor seleccionado, verificar si coincide
                if (tourFiltro) {
                    // Obtener el nombre del tour del select para comparar
                    const tourSelect = document.getElementById('filtro-tour');
                    const selectedOption = tourSelect.querySelector(`option[value="${tourFiltro}"]`);
                    const nombreTourFiltro = selectedOption ? selectedOption.textContent : '';
                    
                    if (nombreTourFiltro && !tourCelda.toLowerCase().includes(nombreTourFiltro.toLowerCase())) {
                        mostrar = false;
                    }
                }
                
                // Para el filtro de guía, si hay un valor seleccionado
                if (guiaFiltro) {
                    // Obtener el nombre de la guía del select para comparar
                    const guiaSelect = document.getElementById('filtro-guia');
                    const selectedOption = guiaSelect.querySelector(`option[value="${guiaFiltro}"]`);
                    const nombreGuiaFiltro = selectedOption ? selectedOption.textContent : '';
                    
                    if (nombreGuiaFiltro && !guiaCelda.toLowerCase().includes(nombreGuiaFiltro.toLowerCase())) {
                        mostrar = false;
                    }
                }
                
                // Mostrar/ocultar fila
                if (mostrar) {
                    fila.style.display = '';
                    filasMostradas++;
                } else {
                    fila.style.display = 'none';
                }
            });
            
            // Filtrar cards móviles
            cardsMobile.forEach((card, index) => {
                let mostrar = true;
                
                // Obtener datos de la card
                const fechaCard = card.querySelector('.bg-blue-100').textContent.trim();
                const tourCard = card.querySelector('h3').textContent.trim();
                
                // Buscar la guía en las cards móviles
                const guiaElements = card.querySelectorAll('.fa-user-tie');
                let guiaCard = '';
                if (guiaElements.length > 0) {
                    guiaCard = guiaElements[0].parentElement.textContent.replace('Guía:', '').trim();
                }
                
                // Aplicar filtros
                if (fechaFiltro && !fechaCard.includes(fechaFiltro.split('-').reverse().join('/'))) {
                    mostrar = false;
                }
                
                // Para el filtro de tour en móvil
                if (tourFiltro) {
                    const tourSelect = document.getElementById('filtro-tour');
                    const selectedOption = tourSelect.querySelector(`option[value="${tourFiltro}"]`);
                    const nombreTourFiltro = selectedOption ? selectedOption.textContent : '';
                    
                    if (nombreTourFiltro && !tourCard.toLowerCase().includes(nombreTourFiltro.toLowerCase())) {
                        mostrar = false;
                    }
                }
                
                // Para el filtro de guía en móvil
                if (guiaFiltro) {
                    const guiaSelect = document.getElementById('filtro-guia');
                    const selectedOption = guiaSelect.querySelector(`option[value="${guiaFiltro}"]`);
                    const nombreGuiaFiltro = selectedOption ? selectedOption.textContent : '';
                    
                    if (nombreGuiaFiltro && !guiaCard.toLowerCase().includes(nombreGuiaFiltro.toLowerCase())) {
                        mostrar = false;
                    }
                }
                
                // Mostrar/ocultar card
                if (mostrar) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Actualizar contador de resultados
            actualizarContadorResultados();
        }
        
        // Event listeners para los filtros
        document.addEventListener('DOMContentLoaded', function() {
            const filtroFecha = document.getElementById('filtro-fecha');
            const filtroTour = document.getElementById('filtro-tour');
            const filtroGuia = document.getElementById('filtro-guia');
            
            if (filtroFecha) filtroFecha.addEventListener('change', aplicarFiltros);
            if (filtroTour) filtroTour.addEventListener('change', aplicarFiltros);
            if (filtroGuia) filtroGuia.addEventListener('change', aplicarFiltros);
            
            // Inicializar contadores
            actualizarContadorResultados();
        });
        
        // Función para actualizar el contador de resultados (opcional)
        function actualizarContadorResultados() {
            const filasVisibles = document.querySelectorAll('.desktop-table tbody tr:not([style*="display: none"])');
            const cardsVisibles = document.querySelectorAll('.mobile-grid > div:not([style*="display: none"])');
            
            // Podrías agregar aquí un elemento para mostrar "X resultados encontrados"
            console.log(`Resultados: ${filasVisibles.length} filas desktop, ${cardsVisibles.length} cards móvil`);
        }
    </script>

    <!-- Modal para crear/editar tour diario -->
    <div id="modalTourDiario" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Header del modal -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                    <div class="flex items-center">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg p-3 text-white shadow-md mr-4">
                            <i class="fas fa-calendar-plus text-xl"></i>
                        </div>
                        <div>
                            <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Nuevo Tour Diario</h3>
                            <p class="text-gray-600 text-sm">Complete los datos para programar un tour</p>
                        </div>
                    </div>
                    <button type="button" onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <!-- Formulario -->
                <form method="POST" id="formTourDiario" class="space-y-6">
                    <input type="hidden" name="action" value="create">
                    <!-- Fecha del Tour -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-calendar text-blue-500 mr-2"></i>
                            Fecha del Tour *
                        </label>
                        <input type="date" name="fecha" id="fechaModal" onchange="cargarDisponibles()" 
                               class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm" 
                               required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Seleccionar Tour -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-map-marked-alt text-blue-500 mr-2"></i>
                            Tour *
                        </label>
                        <select name="id_tour" id="id_tourModal" onchange="cargarHorasTour()" 
                                class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm" 
                                required>
                            <option value="">Seleccione un tour</option>
                            <?php foreach ($tours as $tour): ?>
                                <option value="<?php echo $tour['id_tour']; ?>">
                                    <?php echo htmlspecialchars($tour['titulo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Grid de Personal y Recursos -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Guía -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-user-tie text-green-500 mr-2"></i>
                                Guía *
                            </label>
                            <select name="id_guia" id="id_guiaModal" 
                                    class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm" 
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
                            <select name="id_chofer" id="id_choferModal" 
                                    class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm" 
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
                            <select name="id_vehiculo" id="id_vehiculoModal" 
                                    class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm" 
                                    required>
                                <option value="">Seleccione una fecha primero</option>
                            </select>
                        </div>
                    </div>

                    <!-- Grid de Participantes -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-users text-blue-500 mr-2"></i>
                                Adultos
                            </label>
                            <input type="number" name="num_adultos" id="num_adultosModal" min="0" value="0" 
                                   class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-child text-pink-500 mr-2"></i>
                                Niños
                            </label>
                            <input type="number" name="num_ninos" id="num_ninosModal" min="0" value="0" 
                                   class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm">
                        </div>
                    </div>

                    <!-- Grid de Horarios -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-clock text-green-500 mr-2"></i>
                                Hora Salida *
                            </label>
                            <input type="time" name="hora_salida" id="hora_salidaModal" 
                                   class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-clock text-red-500 mr-2"></i>
                                Hora Retorno *
                            </label>
                            <input type="time" name="hora_retorno" id="hora_retornoModal" 
                                   class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm">
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-sticky-note text-yellow-500 mr-2"></i>
                            Observaciones
                        </label>
                        <textarea name="observaciones" id="observacionesModal" rows="3" 
                                  class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all shadow-sm resize-none" 
                                  placeholder="Notas adicionales sobre el tour..."></textarea>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3 pt-4 border-t-2 border-gray-200">
                        <button type="button" onclick="cerrarModal()" 
                                class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" 
                                class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-6 py-2.5 rounded-lg font-medium shadow-md hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-save mr-2"></i>Registrar Tour Diario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Funciones del modal
        function abrirModalCrear() {
            document.getElementById('modalTourDiario').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModal() {
            document.getElementById('modalTourDiario').classList.add('hidden');
            document.body.style.overflow = 'auto';
            limpiarFormulario();
        }

        function limpiarFormulario() {
            document.getElementById('formTourDiario').reset();
            // Limpiar selects de disponibilidad
            const selects = ['id_guiaModal', 'id_choferModal', 'id_vehiculoModal'];
            selects.forEach(id => {
                const select = document.getElementById(id);
                select.innerHTML = '<option value="">Seleccione una fecha primero</option>';
            });
        }

        // Cerrar modal al hacer clic fuera de él
        document.getElementById('modalTourDiario').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
