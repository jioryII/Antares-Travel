<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Manejar eliminación directa desde modal (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_guia'])) {
    $id_guia = (int)$_POST['id_guia'];
    $motivo = $_POST['motivo'] ?? '';

    if (!$id_guia) {
        header('Location: index.php?error=' . urlencode('ID de guía no proporcionado'));
        exit;
    }

    try {
        $connection = getConnection();
        $connection->beginTransaction();
        
        // Verificar que el guía existe y obtener sus datos completos
        $stmt_verificar = $connection->prepare("
            SELECT g.id_guia, g.nombre, g.apellido, g.email, g.telefono, g.foto_url, g.estado,
                   COUNT(DISTINCT t.id_tour) as tours_asignados,
                   COUNT(DISTINCT td.id_tour_diario) as tours_diarios_futuros,
                   COUNT(DISTINCT r.id_reserva) as reservas_futuras,
                   COUNT(DISTINCT cg.id_calificacion) as total_calificaciones,
                   COUNT(DISTINCT dg.id_disponibilidad) as dias_disponibilidad,
                   COUNT(DISTINCT gi.id_idioma) as idiomas_asociados
            FROM guias g
            LEFT JOIN tours t ON g.id_guia = t.id_guia
            LEFT JOIN tours_diarios td ON g.id_guia = td.id_guia AND td.fecha >= CURDATE()
            LEFT JOIN reservas r ON t.id_tour = r.id_tour AND r.fecha_tour >= CURDATE() AND r.estado IN ('Pendiente', 'Confirmada')
            LEFT JOIN calificaciones_guias cg ON g.id_guia = cg.id_guia
            LEFT JOIN disponibilidad_guias dg ON g.id_guia = dg.id_guia
            LEFT JOIN guia_idiomas gi ON g.id_guia = gi.id_guia
            WHERE g.id_guia = ?
            GROUP BY g.id_guia
        ");
        
        $stmt_verificar->execute([$id_guia]);
        $guia_info = $stmt_verificar->fetch();
        
        if (!$guia_info) {
            throw new Exception('El guía especificado no existe');
        }
        
        // Verificar si hay tours diarios futuros que impidan la eliminación
        if ($guia_info['tours_diarios_futuros'] > 0) {
            throw new Exception('No se puede eliminar el guía porque tiene tours programados para fechas futuras. Cancele o reasigne estos tours primero.');
        }
        
        // Verificar si hay reservas futuras activas
        if ($guia_info['reservas_futuras'] > 0) {
            throw new Exception('No se puede eliminar el guía porque tiene reservas activas para fechas futuras. Cancele estas reservas primero.');
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
                    VALUES (?, 'ELIMINAR', 'guias', ?, ?, ?, NOW())
                ");
                
                $detalles_log = json_encode([
                    'nombre_completo' => $guia_info['nombre'] . ' ' . $guia_info['apellido'],
                    'email' => $guia_info['email'],
                    'telefono' => $guia_info['telefono'],
                    'estado' => $guia_info['estado'],
                    'tours_asignados' => $guia_info['tours_asignados'],
                    'total_calificaciones' => $guia_info['total_calificaciones'],
                    'idiomas_asociados' => $guia_info['idiomas_asociados'],
                    'dias_disponibilidad' => $guia_info['dias_disponibilidad'],
                    'motivo_eliminacion' => $motivo
                ]);
                
                $stmt_log->execute([
                    $admin['id'],
                    $id_guia,
                    $detalles_log,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            } else {
                // Usar configuraciones_admin con clave única como fallback
                $timestamp = date('Y-m-d_H-i-s_') . uniqid();
                $clave_unica = "eliminacion_guia_{$id_guia}_{$timestamp}";
                
                $stmt_auditoria = $connection->prepare("
                    INSERT INTO configuraciones_admin (id_admin, clave, valor, descripcion, tipo, creado_en) 
                    VALUES (?, ?, ?, ?, 'json', NOW())
                ");
                
                $datos_auditoria = json_encode([
                    'id_guia_eliminado' => $id_guia,
                    'nombre_completo' => $guia_info['nombre'] . ' ' . $guia_info['apellido'],
                    'email' => $guia_info['email'],
                    'telefono' => $guia_info['telefono'],
                    'estado' => $guia_info['estado'],
                    'tours_asignados' => $guia_info['tours_asignados'],
                    'total_calificaciones' => $guia_info['total_calificaciones'],
                    'idiomas_asociados' => $guia_info['idiomas_asociados'],
                    'dias_disponibilidad' => $guia_info['dias_disponibilidad'],
                    'motivo_eliminacion' => $motivo,
                    'eliminado_por' => $admin['id'],
                    'fecha_eliminacion' => date('Y-m-d H:i:s'),
                    'ip_eliminacion' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                $descripcion_auditoria = "Eliminación del guía {$guia_info['nombre']} {$guia_info['apellido']} (ID: {$id_guia})";
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
            error_log("Error en auditoría de eliminación de guía ID {$id_guia}: " . $log_error->getMessage());
        }
        
        // Eliminar foto del servidor si existe y es local
        if (!empty($guia_info['foto_url'])) {
            $foto_path = $guia_info['foto_url'];
            
            // Solo eliminar si es una ruta local (no URL externa)
            if (!preg_match('/^https?:\/\//i', $foto_path)) {
                // Construir la ruta completa del archivo
                $ruta_completa = __DIR__ . '/../../../../' . ltrim($foto_path, '/');
                if (file_exists($ruta_completa) && is_file($ruta_completa)) {
                    unlink($ruta_completa);
                }
            }
        }
        
        // PASO 1: Actualizar tours para desasignar el guía (SET NULL como indica la FK)
        $stmt_tours = $connection->prepare("UPDATE tours SET id_guia = NULL WHERE id_guia = ?");
        $stmt_tours->execute([$id_guia]);
        $tours_actualizados = $stmt_tours->rowCount();
        
        // PASO 2: Eliminar registros relacionados que tienen CASCADE
        
        // Eliminar calificaciones del guía (CASCADE)
        $stmt_calificaciones = $connection->prepare("DELETE FROM calificaciones_guias WHERE id_guia = ?");
        $stmt_calificaciones->execute([$id_guia]);
        $calificaciones_eliminadas = $stmt_calificaciones->rowCount();
        
        // Eliminar disponibilidad del guía (CASCADE)
        $stmt_disponibilidad = $connection->prepare("DELETE FROM disponibilidad_guias WHERE id_guia = ?");
        $stmt_disponibilidad->execute([$id_guia]);
        $disponibilidades_eliminadas = $stmt_disponibilidad->rowCount();
        
        // Eliminar relación con idiomas (CASCADE)
        $stmt_idiomas = $connection->prepare("DELETE FROM guia_idiomas WHERE id_guia = ?");
        $stmt_idiomas->execute([$id_guia]);
        $idiomas_eliminados = $stmt_idiomas->rowCount();
        
        // PASO 3: Eliminar tours_diarios (no tiene FK CASCADE definida)
        $stmt_tours_diarios = $connection->prepare("DELETE FROM tours_diarios WHERE id_guia = ?");
        $stmt_tours_diarios->execute([$id_guia]);
        $tours_diarios_eliminados = $stmt_tours_diarios->rowCount();
        
        // PASO 4: Finalmente eliminar el guía
        $stmt_eliminar = $connection->prepare("DELETE FROM guias WHERE id_guia = ?");
        $stmt_eliminar->execute([$id_guia]);
        
        if ($stmt_eliminar->rowCount() === 0) {
            throw new Exception('No se pudo eliminar el guía. Puede que ya haya sido eliminado.');
        }
        
        // Confirmar transacción
        $connection->commit();
        
        // Construir mensaje de éxito con detalles
        $mensaje_success = "Guía eliminado exitosamente: {$guia_info['nombre']} {$guia_info['apellido']}";
        
        $detalles = [];
        if ($tours_actualizados > 0) {
            $detalles[] = "{$tours_actualizados} tour(s) desasignado(s)";
        }
        if ($calificaciones_eliminadas > 0) {
            $detalles[] = "{$calificaciones_eliminadas} calificación(es) eliminada(s)";
        }
        if ($disponibilidades_eliminadas > 0) {
            $detalles[] = "{$disponibilidades_eliminadas} registro(s) de disponibilidad eliminado(s)";
        }
        if ($tours_diarios_eliminados > 0) {
            $detalles[] = "{$tours_diarios_eliminados} tour(s) diario(s) eliminado(s)";
        }
        if ($idiomas_eliminados > 0) {
            $detalles[] = "{$idiomas_eliminados} idioma(s) desasociado(s)";
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
        error_log("Error al eliminar guía ID {$id_guia}: " . $e->getMessage());
        
        // Redirigir con mensaje de error
        $mensaje_error = "Error al eliminar el guía: " . $e->getMessage();
        header('Location: index.php?error=' . urlencode($mensaje_error));
        exit;
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error de base de datos
        if ($connection && $connection->inTransaction()) {
            $connection->rollBack();
        }
        
        // Registrar error de base de datos
        error_log("Error de base de datos al eliminar guía ID {$id_guia}: " . $e->getMessage());
        
        // Mensaje de error más amigable para el usuario
        $mensaje_error = "Error de base de datos. No se pudo eliminar el guía.";
        
        // Agregar detalles específicos para ciertos errores
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $mensaje_error .= " El guía tiene registros relacionados que impiden su eliminación.";
        } elseif (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
            $mensaje_error .= " El guía especificado no fue encontrado.";
        }
        
        header('Location: index.php?error=' . urlencode($mensaje_error));
        exit;
    }
}

// Manejo de página de confirmación (GET) - Método anterior para compatibilidad
$id_guia = intval($_GET['id'] ?? 0);

if (!$id_guia) {
    header('Location: index.php?error=' . urlencode('ID de guía no válido'));
    exit;
}

try {
    $connection = getConnection();
    
    // Obtener información completa del guía para la página de confirmación
    $guia_sql = "
        SELECT g.*, 
               COUNT(DISTINCT t.id_tour) as tours_asignados,
               COUNT(DISTINCT td.id_tour_diario) as tours_diarios_futuros,
               COUNT(DISTINCT r.id_reserva) as reservas_futuras,
               COUNT(DISTINCT cg.id_calificacion) as total_calificaciones,
               COUNT(DISTINCT dg.id_disponibilidad) as dias_disponibilidad,
               COUNT(DISTINCT gi.id_idioma) as idiomas_asociados
        FROM guias g
        LEFT JOIN tours t ON g.id_guia = t.id_guia
        LEFT JOIN tours_diarios td ON g.id_guia = td.id_guia AND td.fecha >= CURDATE()
        LEFT JOIN reservas r ON t.id_tour = r.id_tour AND r.fecha_tour >= CURDATE() AND r.estado IN ('Pendiente', 'Confirmada')
        LEFT JOIN calificaciones_guias cg ON g.id_guia = cg.id_guia
        LEFT JOIN disponibilidad_guias dg ON g.id_guia = dg.id_guia
        LEFT JOIN guia_idiomas gi ON g.id_guia = gi.id_guia
        WHERE g.id_guia = ?
        GROUP BY g.id_guia
    ";
    $guia_stmt = $connection->prepare($guia_sql);
    $guia_stmt->execute([$id_guia]);
    $guia = $guia_stmt->fetch();
    
    if (!$guia) {
        header('Location: index.php?error=' . urlencode('Guía no encontrado'));
        exit;
    }
    
    $reservas_activas = $guia['reservas_futuras'];
    $total_reservas = $guia['reservas_futuras']; // Para simplificar en esta versión
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $confirmar = $_POST['confirmar'] ?? '';
        $motivo = $_POST['motivo'] ?? '';
        
        if ($confirmar === 'SI_ELIMINAR') {
            // Redirigir al método POST principal
            echo '<form id="deleteForm" method="POST" action="eliminar.php">';
            echo '<input type="hidden" name="id_guia" value="' . $id_guia . '">';
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

$page_title = "Eliminar Guía: " . $guia['nombre'] . ' ' . $guia['apellido'];
?>

$page_title = "Eliminar Guía: " . $guia['nombre'] . ' ' . $guia['apellido'];
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
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>Eliminar Guía
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
                    <!-- Información del guía a eliminar -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-user-tie text-blue-600 mr-2"></i>Información del Guía a Eliminar
                        </h3>
                        
                        <div class="flex items-center mb-4">
                            <?php if ($guia['foto_url']): ?>
                                <img class="h-16 w-16 rounded-full" src="<?php echo htmlspecialchars($guia['foto_url']); ?>" alt="">
                            <?php else: ?>
                                <div class="h-16 w-16 rounded-full bg-blue-600 flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">
                                        <?php echo strtoupper(substr($guia['nombre'], 0, 1) . substr($guia['apellido'], 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>
                                </h4>
                                <p class="text-gray-600"><?php echo htmlspecialchars($guia['email']); ?></p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $guia['estado'] === 'Libre' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <i class="<?php echo $guia['estado'] === 'Libre' ? 'fas fa-check-circle' : 'fas fa-clock'; ?> mr-1"></i>
                                    <?php echo $guia['estado']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                            <div>
                                <span class="text-sm font-medium text-gray-500">ID del Guía:</span>
                                <p class="text-sm text-gray-900">#<?php echo $guia['id_guia']; ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Teléfono:</span>
                                <p class="text-sm text-gray-900"><?php echo $guia['telefono'] ? htmlspecialchars($guia['telefono']) : 'No registrado'; ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Tours Asignados:</span>
                                <p class="text-sm text-gray-900"><?php echo $guia['tours_asignados']; ?> tour(s)</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Calificaciones:</span>
                                <p class="text-sm text-gray-900"><?php echo $guia['total_calificaciones']; ?> calificación(es)</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Idiomas:</span>
                                <p class="text-sm text-gray-900"><?php echo $guia['idiomas_asociados']; ?> idioma(s)</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Días de Disponibilidad:</span>
                                <p class="text-sm text-gray-900"><?php echo $guia['dias_disponibilidad']; ?> registro(s)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Advertencia sobre datos relacionados -->
                    <?php if ($guia['tours_asignados'] > 0 || $guia['total_calificaciones'] > 0 || $guia['tours_diarios_futuros'] > 0): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-yellow-800">¡Atención! - Datos Relacionados</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Este guía tiene datos asociados:</p>
                                        <ul class="list-disc list-inside mt-2 space-y-1">
                                            <?php if ($guia['tours_asignados'] > 0): ?>
                                                <li><strong><?php echo $guia['tours_asignados']; ?></strong> tour(s) asignado(s) (se desasignarán)</li>
                                            <?php endif; ?>
                                            <?php if ($guia['total_calificaciones'] > 0): ?>
                                                <li><strong><?php echo $guia['total_calificaciones']; ?></strong> calificación(es) (se eliminarán)</li>
                                            <?php endif; ?>
                                            <?php if ($guia['dias_disponibilidad'] > 0): ?>
                                                <li><strong><?php echo $guia['dias_disponibilidad']; ?></strong> registro(s) de disponibilidad (se eliminarán)</li>
                                            <?php endif; ?>
                                            <?php if ($guia['idiomas_asociados'] > 0): ?>
                                                <li><strong><?php echo $guia['idiomas_asociados']; ?></strong> idioma(s) asociado(s) (se desasociarán)</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                        
                    <?php if ($guia['tours_diarios_futuros'] > 0): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-ban text-red-400 mr-3 mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-red-800">¡Error Crítico!</h3>
                                    <p class="mt-2 text-sm text-red-700">
                                        Este guía tiene <strong><?php echo $guia['tours_diarios_futuros']; ?> tour(s) diario(s)</strong> programado(s) para fechas futuras. 
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
                                        Este guía tiene <strong><?php echo $reservas_activas; ?> reserva(s) activa(s)</strong> para fechas futuras. 
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
                        
                        <?php if ($guia['tours_diarios_futuros'] > 0 || $reservas_activas > 0): ?>
                            <!-- Formulario deshabilitado si hay impedimentos -->
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-lock text-red-500 mr-2"></i>
                                    <span class="text-sm font-medium text-red-800">
                                        No se puede eliminar este guía debido a los problemas mencionados arriba.
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
                                              placeholder="Ej: Solicitud del guía, baja del servicio, violación de términos..."></textarea>
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
                                        <i class="fas fa-trash mr-2"></i>Eliminar Guía
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
                                    <li>Se eliminarán todas las calificaciones y disponibilidades del guía</li>
                                    <li>Los tours asignados quedarán sin guía (se pueden reasignar)</li>
                                    <li>Se registrará esta acción en el historial administrativo con auditoría completa</li>
                                    <li>Si tiene foto local, el archivo será eliminado del servidor</li>
                                    <?php if ($guia['tours_diarios_futuros'] > 0): ?>
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
            
            const toursAsignados = <?php echo $guia['tours_asignados']; ?>;
            const calificaciones = <?php echo $guia['total_calificaciones']; ?>;
            const idiomas = <?php echo $guia['idiomas_asociados']; ?>;
            
            let mensaje = '¿Está seguro de que desea eliminar este guía?\n\n';
            
            if (toursAsignados > 0) {
                mensaje += `• Se desasignarán ${toursAsignados} tour(s)\n`;
            }
            if (calificaciones > 0) {
                mensaje += `• Se eliminarán ${calificaciones} calificación(es)\n`;
            }
            if (idiomas > 0) {
                mensaje += `• Se desasociarán ${idiomas} idioma(s)\n`;
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
