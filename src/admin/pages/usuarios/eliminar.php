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
    
    // Iniciar transacción
    $connection->beginTransaction();
    
    try {
        // Registrar la eliminación en el log antes de eliminar
        // TODO: Implementar función de registro de actividad
        // registrarActividadAdmin(...)
        
        // Saltar eliminación de reseñas (tabla no existe)
        // $delete_resenas_sql = "DELETE FROM resenas WHERE id_usuario = ?";
        // $connection->prepare($delete_resenas_sql)->execute([$id_usuario]);
        
        // Actualizar reservas para mantener referencia histórica pero anonimizar
        $update_reservas_sql = "UPDATE reservas SET 
                               nombre_cliente = 'Usuario eliminado',
                               email_cliente = 'eliminado@sistema.com',
                               telefono_cliente = NULL,
                               notas_admin = CONCAT(COALESCE(notas_admin, ''), 
                                   '\n[SISTEMA] Usuario original eliminado el ', NOW(), 
                                   ' por admin ID ', ?, 
                                   CASE WHEN ? != '' THEN CONCAT(' - Motivo: ', ?) ELSE '' END)
                               WHERE id_usuario = ?";
        $connection->prepare($update_reservas_sql)->execute([
            $admin['id_admin'], 
            $motivo, 
            $motivo, 
            $id_usuario
        ]);
        
        // Eliminar tokens de verificación y recuperación
        $delete_tokens_sql = "DELETE FROM tokens_verificacion WHERE id_usuario = ?";
        $connection->prepare($delete_tokens_sql)->execute([$id_usuario]);
        
        $delete_recovery_sql = "DELETE FROM tokens_recuperacion WHERE id_usuario = ?";
        $connection->prepare($delete_recovery_sql)->execute([$id_usuario]);
        
        // Eliminar sesiones activas del usuario
        $delete_sesiones_sql = "DELETE FROM sesiones_usuario WHERE id_usuario = ?";
        $connection->prepare($delete_sesiones_sql)->execute([$id_usuario]);
        
        // Finalmente eliminar el usuario
        $delete_usuario_sql = "DELETE FROM usuarios WHERE id_usuario = ?";
        $delete_stmt = $connection->prepare($delete_usuario_sql);
        $delete_stmt->execute([$id_usuario]);
        
        if ($delete_stmt->rowCount() === 0) {
            throw new Exception("No se pudo eliminar el usuario");
        }
        
        // Confirmar transacción
        $connection->commit();
        
        // Enviar notificación de eliminación si es necesario
        if ($usuario['email'] && $usuario['email_verificado']) {
            enviarNotificacionEliminacion($usuario['email'], $usuario['nombre'], $motivo);
        }
        
        // Redirigir con mensaje de éxito
        $mensaje_exito = "Usuario eliminado exitosamente";
        if ($motivo) {
            $mensaje_exito .= " (Motivo: $motivo)";
        }
        
        header('Location: index.php?success=' . urlencode($mensaje_exito));
        exit;
        
    } catch (Exception $e) {
        $connection->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
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
