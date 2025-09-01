<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Solo procesar POST con JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Configurar respuesta JSON
header('Content-Type: application/json');

try {
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id_usuario'])) {
        throw new Exception("Datos no válidos");
    }
    
    $id_usuario = intval($input['id_usuario']);
    
    if (!$id_usuario) {
        throw new Exception("ID de usuario no válido");
    }
    
    $connection = getConnection();
    
    // Verificar que el usuario existe
    $check_sql = "SELECT id_usuario, email, nombre, email_verificado FROM usuarios WHERE id_usuario = ?";
    $check_stmt = $connection->prepare($check_sql);
    $check_stmt->execute([$id_usuario]);
    $usuario = $check_stmt->fetch();
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    if ($usuario['email_verificado']) {
        throw new Exception("El email ya está verificado");
    }
    
    // Marcar email como verificado
    $update_sql = "UPDATE usuarios SET 
                   email_verificado = 1, 
                   fecha_verificacion = NOW(),
                   token_verificacion = NULL,
                   actualizado_en = NOW()
                   WHERE id_usuario = ?";
    
    $update_stmt = $connection->prepare($update_sql);
    $update_stmt->execute([$id_usuario]);
    
    if ($update_stmt->rowCount() === 0) {
        throw new Exception("No se pudo actualizar el estado de verificación");
    }
    
    // Eliminar token de verificación si existe
    $delete_token_sql = "DELETE FROM tokens_verificacion WHERE id_usuario = ?";
    $connection->prepare($delete_token_sql)->execute([$id_usuario]);
    
    // Enviar email de confirmación al usuario
    $email_enviado = enviarEmailVerificacionCompletada($usuario['email'], $usuario['nombre']);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Email verificado exitosamente' . ($email_enviado ? '. Se ha enviado una confirmación al usuario.' : '.'),
        'usuario' => [
            'id' => $usuario['id_usuario'],
            'email' => $usuario['email'],
            'verificado' => true
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function enviarEmailVerificacionCompletada($email, $nombre) {
    try {
        $asunto = "Email verificado - " . SITE_NAME;
        
        $mensaje = "
        <html>
        <head>
            <title>Email Verificado</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .button { 
                    display: inline-block; 
                    padding: 12px 30px; 
                    background: #007bff; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0; 
                }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .success-icon { font-size: 48px; color: #28a745; text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>¡Email Verificado!</h1>
                </div>
                <div class='content'>
                    <div class='success-icon'>✓</div>
                    
                    <p>¡Hola " . htmlspecialchars($nombre) . "!</p>
                    
                    <p>Nos complace informarte que tu dirección de email ha sido <strong>verificada exitosamente</strong> por nuestro equipo de administración.</p>
                    
                    <p>Ya puedes acceder a todas las funcionalidades de tu cuenta en " . SITE_NAME . ":</p>
                    
                    <ul>
                        <li>Realizar reservas de paquetes turísticos</li>
                        <li>Gestionar tu perfil y preferencias</li>
                        <li>Recibir ofertas y promociones exclusivas</li>
                        <li>Dejar reseñas y calificaciones</li>
                    </ul>
                    
                    <div style='text-align: center;'>
                        <a href='" . BASE_URL . "/login' class='button'>Acceder a mi cuenta</a>
                    </div>
                    
                    <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
                    
                    <p>¡Gracias por ser parte de nuestra comunidad de viajeros!</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". Todos los derechos reservados.</p>
                    <p>Si no solicitaste esta verificación, por favor contacta con nuestro soporte.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@antaresstravel.com\r\n";
        $headers .= "Reply-To: support@antaresstravel.com\r\n";
        
        return mail($email, $asunto, $mensaje, $headers);
        
    } catch (Exception $e) {
        error_log("Error enviando email de verificación completada: " . $e->getMessage());
        return false;
    }
}
?>
