<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Manejar eliminación directa desde modal (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_chofer'])) {
    $id_chofer = (int)$_POST['id_chofer'];
    $motivo = $_POST['motivo'] ?? '';

    if (!$id_chofer) {
        header('Location: index.php?error=' . urlencode('ID de chofer no proporcionado'));
        exit;
    }

    try {
        $connection = getConnection();
        $connection->beginTransaction();
        
        // Verificar que el chofer existe y obtener sus datos completos
        $stmt_verificar = $connection->prepare("
            SELECT c.id_chofer, c.nombre, c.apellido, c.telefono, c.licencia, c.foto_url,
                   COUNT(DISTINCT v.id_vehiculo) as vehiculos_asignados,
                   COUNT(DISTINCT td.id_tour_diario) as tours_diarios_futuros,
                   COUNT(DISTINCT r.id_reserva) as reservas_futuras,
                   COUNT(DISTINCT dv.id_disponibilidad) as dias_disponibilidad,
                   COUNT(DISTINCT CASE WHEN td.fecha >= CURDATE() THEN td.id_tour_diario END) as tours_futuros_count
            FROM choferes c
            LEFT JOIN vehiculos v ON c.id_chofer = v.id_chofer
            LEFT JOIN tours_diarios td ON v.id_vehiculo = td.id_vehiculo
            LEFT JOIN reservas r ON td.id_tour = r.id_tour AND r.fecha_tour >= CURDATE() AND r.estado IN ('Pendiente', 'Confirmada')
            LEFT JOIN disponibilidad_vehiculos dv ON v.id_vehiculo = dv.id_vehiculo
            WHERE c.id_chofer = ?
            GROUP BY c.id_chofer
        ");
        
        $stmt_verificar->execute([$id_chofer]);
        $chofer_info = $stmt_verificar->fetch();
        
        if (!$chofer_info) {
            throw new Exception('El chofer especificado no existe');
        }
        
        // Verificar si hay tours diarios futuros que impidan la eliminación
        if ($chofer_info['tours_futuros_count'] > 0) {
            throw new Exception('No se puede eliminar el chofer porque tiene tours programados para fechas futuras. Cancele o reasigne estos tours primero.');
        }
        
        // Verificar si hay reservas futuras activas
        if ($chofer_info['reservas_futuras'] > 0) {
            throw new Exception('No se puede eliminar el chofer porque tiene reservas activas para fechas futuras. Cancele estas reservas primero.');
        }
        
        // Registrar la eliminación para auditoría
        // Opción 1: Intentar usar tabla de logs si existe, sino usar error_log
        try {
            // Verificar si existe tabla logs_auditoria
            $check_table = $connection->query("SHOW TABLES LIKE 'logs_auditoria'");
            
            if ($check_table->rowCount() > 0) {
                // Usar tabla de logs si existe
                $stmt_log = $connection->prepare("
                    INSERT INTO logs_auditoria (admin_id, accion, tabla_afectada, registro_id, detalles, ip_address, created_at) 
                    VALUES (?, 'ELIMINAR', 'choferes', ?, ?, ?, NOW())
                ");
                
                $detalles_log = json_encode([
                    'nombre_completo' => $chofer_info['nombre'] . ' ' . $chofer_info['apellido'],
                    'telefono' => $chofer_info['telefono'],
                    'licencia' => $chofer_info['licencia'],
                    'vehiculos_asignados' => $chofer_info['vehiculos_asignados'],
                    'dias_disponibilidad' => $chofer_info['dias_disponibilidad'],
                    'motivo_eliminacion' => $motivo
                ]);
                
                $stmt_log->execute([
                    $admin['id'],
                    $id_chofer,
                    $detalles_log,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            } else {
                // Usar configuraciones_admin con clave única como fallback
                $timestamp = date('Y-m-d_H-i-s_') . uniqid();
                $clave_unica = "eliminacion_chofer_{$id_chofer}_{$timestamp}";
                
                $stmt_auditoria = $connection->prepare("
                    INSERT INTO configuraciones_admin (id_admin, clave, valor, descripcion, tipo, creado_en) 
                    VALUES (?, ?, ?, ?, 'json', NOW())
                ");
                
                $datos_auditoria = json_encode([
                    'id_chofer_eliminado' => $id_chofer,
                    'nombre_completo' => $chofer_info['nombre'] . ' ' . $chofer_info['apellido'],
                    'telefono' => $chofer_info['telefono'],
                    'licencia' => $chofer_info['licencia'],
                    'vehiculos_asignados' => $chofer_info['vehiculos_asignados'],
                    'dias_disponibilidad' => $chofer_info['dias_disponibilidad'],
                    'motivo_eliminacion' => $motivo,
                    'eliminado_por' => $admin['id'],
                    'fecha_eliminacion' => date('Y-m-d H:i:s'),
                    'ip_eliminacion' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                $descripcion_auditoria = "Eliminación del chofer {$chofer_info['nombre']} {$chofer_info['apellido']} (ID: {$id_chofer})";
                if (!empty($motivo)) {
                    $descripcion_auditoria .= " - Motivo: {$motivo}";
                }
                
                $stmt_auditoria->execute([
                    $admin['id'],
                    $clave_unica,
                    $datos_auditoria,
                    $descripcion_auditoria
                ]);
            }
        } catch (Exception $log_error) {
            // Si falla el registro de auditoría, continuar con la eliminación
            // pero registrar en error_log para debugging
            error_log("Error en auditoría de eliminación de chofer ID {$id_chofer}: " . $log_error->getMessage());
        }
        
        // Eliminar foto del servidor si existe y es local
        if (!empty($chofer_info['foto_url'])) {
            $foto_path = $chofer_info['foto_url'];
            
            // Solo eliminar si es una ruta local (no URL externa)
            if (!preg_match('/^https?:\/\//i', $foto_path)) {
                // Construir la ruta completa del archivo
                $ruta_completa = __DIR__ . '/../../../../' . ltrim($foto_path, '/');
                if (file_exists($ruta_completa) && is_file($ruta_completa)) {
                    unlink($ruta_completa);
                }
            }
        }
        
        // PASO 1: Actualizar vehículos para desasignar el chofer (SET NULL como indica la FK)
        $stmt_vehiculos = $connection->prepare("UPDATE vehiculos SET id_chofer = NULL WHERE id_chofer = ?");
        $stmt_vehiculos->execute([$id_chofer]);
        $vehiculos_actualizados = $stmt_vehiculos->rowCount();
        
        // PASO 2: Los tours_diarios y disponibilidad_vehiculos se manejan automáticamente
        // ya que están relacionados con vehículos, no directamente con choferes
        
        // PASO 3: Finalmente eliminar el chofer
        $stmt_eliminar = $connection->prepare("DELETE FROM choferes WHERE id_chofer = ?");
        $stmt_eliminar->execute([$id_chofer]);
        
        if ($stmt_eliminar->rowCount() === 0) {
            throw new Exception('No se pudo eliminar el chofer. Puede que ya haya sido eliminado.');
        }
        
        // Confirmar transacción
        $connection->commit();
        
        // Construir mensaje de éxito con detalles
        $mensaje_success = "Chofer eliminado exitosamente: {$chofer_info['nombre']} {$chofer_info['apellido']}";
        
        $detalles = [];
        if ($vehiculos_actualizados > 0) {
            $detalles[] = "{$vehiculos_actualizados} vehículo(s) desasignado(s)";
        }
        
        if (!empty($detalles)) {
            $mensaje_success .= " (" . implode(", ", $detalles) . ")";
        }
        
        header('Location: index.php?success=' . urlencode($mensaje_success));
        exit;
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if ($connection && $connection->inTransaction()) {
            $connection->rollBack();
        }
        
        // Registrar error para debugging
        error_log("Error al eliminar chofer ID {$id_chofer}: " . $e->getMessage());
        
        // Redirigir con mensaje de error
        $mensaje_error = "Error al eliminar el chofer: " . $e->getMessage();
        header('Location: index.php?error=' . urlencode($mensaje_error));
        exit;
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error de base de datos
        if ($connection && $connection->inTransaction()) {
            $connection->rollBack();
        }
        
        // Registrar error de base de datos
        error_log("Error de base de datos al eliminar chofer ID {$id_chofer}: " . $e->getMessage());
        
        // Mensaje de error más amigable para el usuario
        $mensaje_error = "Error de base de datos. No se pudo eliminar el chofer.";
        
        // Agregar detalles específicos para ciertos errores
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $mensaje_error .= " El chofer tiene registros relacionados que impiden su eliminación.";
        } elseif (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
            $mensaje_error .= " El chofer especificado no fue encontrado.";
        }
        
        header('Location: index.php?error=' . urlencode($mensaje_error));
        exit;
    }
}

// Manejo de página de confirmación (GET) - Método anterior para compatibilidad
$id_chofer = intval($_GET['id'] ?? 0);

if (!$id_chofer) {
    header('Location: index.php?error=' . urlencode('ID de chofer no válido'));
    exit;
}

try {
    $connection = getConnection();
    
    // Obtener información completa del chofer para la página de confirmación
    $chofer_sql = "
        SELECT c.*, 
               COUNT(DISTINCT v.id_vehiculo) as vehiculos_asignados,
               COUNT(DISTINCT td.id_tour_diario) as tours_diarios_futuros,
               COUNT(DISTINCT r.id_reserva) as reservas_futuras,
               COUNT(DISTINCT dv.id_disponibilidad) as dias_disponibilidad,
               COUNT(DISTINCT CASE WHEN td.fecha >= CURDATE() THEN td.id_tour_diario END) as tours_futuros_count
        FROM choferes c
        LEFT JOIN vehiculos v ON c.id_chofer = v.id_chofer
        LEFT JOIN tours_diarios td ON v.id_vehiculo = td.id_vehiculo
        LEFT JOIN reservas r ON td.id_tour = r.id_tour AND r.fecha_tour >= CURDATE() AND r.estado IN ('Pendiente', 'Confirmada')
        LEFT JOIN disponibilidad_vehiculos dv ON v.id_vehiculo = dv.id_vehiculo
        WHERE c.id_chofer = ?
        GROUP BY c.id_chofer
    ";
    $chofer_stmt = $connection->prepare($chofer_sql);
    $chofer_stmt->execute([$id_chofer]);
    $chofer = $chofer_stmt->fetch();
    
    if (!$chofer) {
        header('Location: index.php?error=' . urlencode('Chofer no encontrado'));
        exit;
    }
    
    $reservas_activas = $chofer['reservas_futuras'];
    $tours_futuros = $chofer['tours_futuros_count'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $confirmar = $_POST['confirmar'] ?? '';
        $motivo = $_POST['motivo'] ?? '';
        
        if ($confirmar === 'SI_ELIMINAR') {
            // Redirigir al método POST principal
            echo '<form id="deleteForm" method="POST" action="eliminar.php">';
            echo '<input type="hidden" name="id_chofer" value="' . $id_chofer . '">';
            echo '<input type="hidden" name="motivo" value="' . htmlspecialchars($motivo) . '">';
            echo '</form>';
            echo '<script>document.getElementById("deleteForm").submit();</script>';
            exit;
        } else {
            $error = "Debe confirmar la eliminación escribiendo 'SI_ELIMINAR'";
        }
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode("Error al procesar solicitud: " . $e->getMessage()));
    exit;
}

$page_title = "Eliminar Chofer: " . $chofer['nombre'] . ' ' . $chofer['apellido'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                                <h1 class="text-2xl lg:text-3xl font-bold text-red-600">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>Eliminar Chofer
                                </h1>
                            </div>
                            <p class="text-sm lg:text-base text-gray-600">Esta acción no se puede deshacer</p>
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

                <div class="max-w-2xl mx-auto">
                    <!-- Información del chofer a eliminar -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-user-tie text-blue-600 mr-2"></i>Información del Chofer a Eliminar
                        </h3>
                        
                        <div class="flex items-center mb-4">
                            <?php if ($chofer['foto_url']): ?>
                                <img class="h-16 w-16 rounded-full" src="<?php echo htmlspecialchars($chofer['foto_url']); ?>" alt="">
                            <?php else: ?>
                                <div class="h-16 w-16 rounded-full bg-blue-600 flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">
                                        <?php echo strtoupper(substr($chofer['nombre'], 0, 1) . substr($chofer['apellido'], 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($chofer['nombre'] . ' ' . $chofer['apellido']); ?>
                                </h4>
                                <?php if ($chofer['telefono']): ?>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($chofer['telefono']); ?></p>
                                <?php endif; ?>
                                <?php if ($chofer['licencia']): ?>
                                    <p class="text-sm text-gray-500">Licencia: <?php echo htmlspecialchars($chofer['licencia']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                            <div>
                                <span class="text-sm font-medium text-gray-500">ID del Chofer:</span>
                                <p class="text-sm text-gray-900">#<?php echo $chofer['id_chofer']; ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Licencia:</span>
                                <p class="text-sm text-gray-900"><?php echo $chofer['licencia'] ? htmlspecialchars($chofer['licencia']) : 'No registrada'; ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Vehículos Asignados:</span>
                                <p class="text-sm text-gray-900"><?php echo $chofer['vehiculos_asignados']; ?> vehículo(s)</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Tours Diarios:</span>
                                <p class="text-sm text-gray-900"><?php echo $chofer['tours_diarios_futuros']; ?> tour(s)</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Días de Disponibilidad:</span>
                                <p class="text-sm text-gray-900"><?php echo $chofer['dias_disponibilidad']; ?> registro(s)</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Teléfono:</span>
                                <p class="text-sm text-gray-900"><?php echo $chofer['telefono'] ? htmlspecialchars($chofer['telefono']) : 'No registrado'; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Advertencia sobre datos relacionados -->
                    <?php if ($chofer['vehiculos_asignados'] > 0 || $tours_futuros > 0): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-yellow-800">¡Atención! - Datos Relacionados</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Este chofer tiene datos asociados:</p>
                                        <ul class="list-disc list-inside mt-2 space-y-1">
                                            <?php if ($chofer['vehiculos_asignados'] > 0): ?>
                                                <li><strong><?php echo $chofer['vehiculos_asignados']; ?></strong> vehículo(s) asignado(s) (se desasignarán)</li>
                                            <?php endif; ?>
                                            <?php if ($chofer['dias_disponibilidad'] > 0): ?>
                                                <li><strong><?php echo $chofer['dias_disponibilidad']; ?></strong> registro(s) de disponibilidad (se mantendrán para los vehículos)</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                        
                    <?php if ($tours_futuros > 0): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-ban text-red-400 mr-3 mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-red-800">¡Error Crítico!</h3>
                                    <p class="mt-2 text-sm text-red-700">
                                        Este chofer tiene <strong><?php echo $tours_futuros; ?> tour(s) diario(s)</strong> programado(s) para fechas futuras. 
                                        <strong>No se puede eliminar hasta que cancele o reasigne estos tours.</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($reservas_activas > 0): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-ban text-red-400 mr-3 mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-red-800">¡Advertencia Crítica!</h3>
                                    <p class="mt-2 text-sm text-red-700">
                                        Este chofer tiene <strong><?php echo $reservas_activas; ?> reserva(s) activa(s)</strong> para fechas futuras. 
                                        <strong>No se puede eliminar hasta que cancele estas reservas.</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de confirmación -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-red-600 mb-4">
                            <i class="fas fa-trash text-red-600 mr-2"></i>Confirmar Eliminación
                        </h3>
                        
                        <?php if ($tours_futuros > 0 || $reservas_activas > 0): ?>
                            <!-- Formulario deshabilitado si hay impedimentos -->
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-lock text-red-500 mr-2"></i>
                                    <span class="text-sm font-medium text-red-800">
                                        No se puede eliminar este chofer debido a los problemas mencionados arriba.
                                    </span>
                                </div>
                            </div>
                            <div class="flex justify-center">
                                <a href="index.php" 
                                   class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver a la Lista
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Formulario habilitado -->
                            <form method="POST" onsubmit="return confirmarEliminacion()">
                                <!-- Motivo de eliminación -->
                                <div class="mb-4">
                                    <label for="motivo" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-clipboard-list mr-1"></i>Motivo de eliminación (opcional):
                                    </label>
                                    <textarea name="motivo" id="motivo" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"
                                              placeholder="Ej: Finalización de contrato, cambio de empleo, violación de normas..."></textarea>
                                    <div class="text-xs text-gray-400 mt-1">
                                        Este motivo quedará registrado en el historial administrativo
                                    </div>
                                </div>
                                
                                <!-- Confirmación por escrito -->
                                <div class="mb-6">
                                    <label for="confirmar" class="block text-sm font-medium text-gray-700 mb-2">
                                        Para confirmar la eliminación, escriba exactamente: <code class="bg-gray-100 px-2 py-1 rounded">SI_ELIMINAR</code>
                                    </label>
                                    <input type="text" name="confirmar" id="confirmar" required
                                           class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                           placeholder="Escriba SI_ELIMINAR para confirmar">
                                </div>
                                
                                <!-- Botones -->
                                <div class="flex justify-end space-x-3">
                                    <a href="index.php" 
                                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-times mr-2"></i>Cancelar
                                    </a>
                                    <button type="submit" 
                                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                        <i class="fas fa-trash mr-2"></i>Eliminar Chofer
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Información adicional -->
                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-info-circle text-blue-400 mr-3 mt-1"></i>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-blue-800">Información Importante</h3>
                                <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                                    <li>Esta acción no se puede deshacer</li>
                                    <li>Los vehículos asignados quedarán sin chofer (se pueden reasignar)</li>
                                    <li>Se registrará esta acción en el historial administrativo con auditoría completa</li>
                                    <li>Si tiene foto local, el archivo será eliminado del servidor</li>
                                    <?php if ($tours_futuros > 0): ?>
                                        <li class="text-red-600 font-medium">No se puede eliminar: hay tours diarios futuros programados</li>
                                    <?php endif; ?>
                                    <?php if ($reservas_activas > 0): ?>
                                        <li class="text-red-600 font-medium">No se puede eliminar: hay reservas activas para fechas futuras</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmarEliminacion() {
            const confirmar = document.getElementById('confirmar').value;
            if (confirmar !== 'SI_ELIMINAR') {
                alert('Debe escribir exactamente "SI_ELIMINAR" para confirmar');
                return false;
            }
            
            const vehiculosAsignados = <?php echo $chofer['vehiculos_asignados']; ?>;
            
            let mensaje = '¿Está seguro de que desea eliminar este chofer?\n\n';
            
            if (vehiculosAsignados > 0) {
                mensaje += `• Se desasignarán ${vehiculosAsignados} vehículo(s)\n`;
            }
            
            mensaje += '\nEsta acción no se puede deshacer.';
            
            return confirm(mensaje);
        }

        // Enfocar automáticamente el campo de confirmación
        document.addEventListener('DOMContentLoaded', function() {
            const confirmarInput = document.getElementById('confirmar');
            if (confirmarInput) {
                confirmarInput.focus();
            }
        });
    </script>
</body>
</html>
