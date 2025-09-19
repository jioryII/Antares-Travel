<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    
    error_log("Eliminación iniciada - ID: $id_usuario, Motivo: $motivo");
    
    if (!$id_usuario) {
        throw new Exception("ID de usuario no válido");
    }
    
    $connection = getConnection();
    
    // Obtener datos del usuario antes de eliminar
    $usuario_sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
    $usuario_stmt = $connection->prepare($usuario_sql);
    $usuario_stmt->execute([$id_usuario]);
    $usuario = $usuario_stmt->fetch();
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    // Verificar si el usuario tiene reservas activas
    $reservas_activas_sql = "SELECT COUNT(*) as total FROM reservas 
                            WHERE id_usuario = ? 
                            AND estado IN ('pendiente', 'confirmada')";
    $reservas_stmt = $connection->prepare($reservas_activas_sql);
    $reservas_stmt->execute([$id_usuario]);
    $reservas_activas = $reservas_stmt->fetch()['total'];
    
    if ($reservas_activas > 0) {
        throw new Exception("No se puede eliminar el usuario porque tiene $reservas_activas reserva(s) activa(s). Cancele primero las reservas pendientes o confirmadas.");
    }
    
    // Eliminación con manejo completo de foreign keys
    try {
        // Iniciar transacción para asegurar consistencia
        $connection->beginTransaction();
        
        // Lista de tablas con foreign keys a usuarios (en orden de eliminación)
        $tablas_relacionadas = [
            'email_verificacion',
            'calificaciones_guias', 
            'cotizaciones',
            'historial_uso_ofertas',
            'ofertas_usuarios',
            'preferencias_usuario',
            'experiencias',
            'comentarios' // Por si existe
        ];
        
        // 1. Eliminar registros relacionados primero
        foreach ($tablas_relacionadas as $tabla) {
            try {
                $delete_sql = "DELETE FROM $tabla WHERE id_usuario = ?";
                $delete_stmt = $connection->prepare($delete_sql);
                $delete_stmt->execute([$id_usuario]);
                $rows_deleted = $delete_stmt->rowCount();
                if ($rows_deleted > 0) {
                    error_log("Eliminados $rows_deleted registros de $tabla para usuario ID: $id_usuario");
                }
            } catch (Exception $e) {
                error_log("No se pudieron eliminar registros de $tabla (tabla podría no existir o no tener datos): " . $e->getMessage());
                // Continuar con las demás tablas
            }
        }
        
        // 2. Manejar reservas especialmente (actualizar estado en lugar de eliminar)
        try {
            $update_reservas_sql = "UPDATE reservas 
                                   SET estado = 'cancelada_por_eliminacion', 
                                       fecha_actualizacion = NOW() 
                                   WHERE id_usuario = ? 
                                   AND estado NOT IN ('cancelada', 'completada')";
            $update_reservas_stmt = $connection->prepare($update_reservas_sql);
            $update_reservas_stmt->execute([$id_usuario]);
            $reservas_updated = $update_reservas_stmt->rowCount();
            if ($reservas_updated > 0) {
                error_log("Actualizadas $reservas_updated reservas a estado 'cancelada_por_eliminacion' para usuario ID: $id_usuario");
            }
        } catch (Exception $e) {
            error_log("No se pudieron actualizar reservas: " . $e->getMessage());
            // Si no se pueden actualizar, intentar eliminar
            try {
                $delete_reservas_sql = "DELETE FROM reservas WHERE id_usuario = ?";
                $delete_reservas_stmt = $connection->prepare($delete_reservas_sql);
                $delete_reservas_stmt->execute([$id_usuario]);
                $reservas_deleted = $delete_reservas_stmt->rowCount();
                if ($reservas_deleted > 0) {
                    error_log("Eliminadas $reservas_deleted reservas para usuario ID: $id_usuario");
                }
            } catch (Exception $e2) {
                error_log("Tampoco se pudieron eliminar reservas: " . $e2->getMessage());
            }
        }
        
        // 2. Finalmente eliminar el usuario
        $delete_usuario_sql = "DELETE FROM usuarios WHERE id_usuario = ?";
        $delete_stmt = $connection->prepare($delete_usuario_sql);
        
        error_log("Ejecutando eliminación del usuario ID: $id_usuario");
        $delete_stmt->execute([$id_usuario]);
        
        $rows_affected = $delete_stmt->rowCount();
        error_log("Filas afectadas al eliminar usuario: $rows_affected");
        
        if ($rows_affected === 0) {
            // Verificar si el usuario aún existe
            $check_sql = "SELECT COUNT(*) as count FROM usuarios WHERE id_usuario = ?";
            $check_stmt = $connection->prepare($check_sql);
            $check_stmt->execute([$id_usuario]);
            $user_exists = $check_stmt->fetch()['count'];
            
            if ($user_exists > 0) {
                throw new Exception("El usuario existe pero no se pudo eliminar. Puede haber restricciones de foreign key no identificadas. Contacte al administrador del sistema.");
            } else {
                throw new Exception("El usuario con ID $id_usuario no existe en la base de datos.");
            }
        }
        
        // Confirmar la transacción
        $connection->commit();
        error_log("Transacción de eliminación completada exitosamente para usuario ID: $id_usuario");
        
        // Enviar notificación de eliminación si es necesario
        try {
            if ($usuario['email'] && $usuario['email_verificado']) {
                enviarNotificacionEliminacion($usuario['email'], $usuario['nombre'], $motivo);
            }
        } catch (Exception $e) {
            error_log("Error enviando notificación de eliminación: " . $e->getMessage());
        }
        
        // Redirigir con mensaje de éxito
        $mensaje_exito = "Usuario '{$usuario['nombre']}' eliminado exitosamente";
        if ($motivo) {
            $mensaje_exito .= " (Motivo: $motivo)";
        }
        
        header('Location: index.php?success=' . urlencode($mensaje_exito));
        exit;
        
    } catch (Exception $e) {
        // Rollback en caso de error
        if ($connection->inTransaction()) {
            $connection->rollBack();
            error_log("Transacción rollback realizada para usuario ID: $id_usuario");
        }
        error_log("Error eliminando usuario ID $id_usuario: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    $error_message = "Error al eliminar usuario: " . $e->getMessage();
    error_log("Error completo en eliminación: " . $error_message);
    header('Location: index.php?error=' . urlencode($error_message));
    exit;
}

// Función para enviar notificación de eliminación
function enviarNotificacionEliminacion($email, $nombre, $motivo) {
    try {
        // Configurar el email de notificación
        $asunto = "Cuenta eliminada - " . SITE_NAME;
        
        $mensaje = "
        <html>
        <head>
            <title>Cuenta Eliminada</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Cuenta Eliminada</h1>
                </div>
                <div class='content'>
                    <p>Estimado/a " . htmlspecialchars($nombre) . ",</p>
                    
                    <p>Le informamos que su cuenta en " . SITE_NAME . " ha sido eliminada por nuestro equipo de administración.</p>
                    
                    " . ($motivo ? "<p><strong>Motivo:</strong> " . htmlspecialchars($motivo) . "</p>" : "") . "
                    
                    <p>Sus datos personales han sido eliminados de nuestros sistemas de acuerdo con nuestras políticas de privacidad.</p>
                    
                    <p>Si considera que esta eliminación es un error o tiene alguna consulta, puede contactarnos respondiendo a este email.</p>
                    
                    <p>Gracias por haber formado parte de nuestra comunidad.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Configurar headers para HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@antaresstravel.com\r\n";
        $headers .= "Reply-To: support@antaresstravel.com\r\n";
        
        // Enviar email
        return mail($email, $asunto, $mensaje, $headers);
        
    } catch (Exception $e) {
        // Log del error pero no fallar la eliminación
        error_log("Error enviando notificación de eliminación: " . $e->getMessage());
        return false;
    }
}
?>
