<?php
require_once __DIR__ . "/../../vendor/autoload.php"; // Ajusta la ruta según tu proyecto
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoVerificacion($email, $nombre, $link) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'andiquispe9422@gmail.com'; // tu Gmail
        $mail->Password   = 'rytiivlavpjznoww'; // contraseña de app
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Remitente y destinatario
        $mail->setFrom('noreply@antares.com', 'Antares Travel');
        $mail->addAddress($email, $nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Verifica tu correo - Antares Travel';
        $mail->Body = "
          <div style='font-family: Arial, sans-serif; background: #f3f4f6; padding: 32px; border-radius: 12px; max-width: 480px; margin: auto;'>
            <h2 style='color: #2563eb; text-align: center;'>¡Bienvenido a Antares Travel!</h2>
            <p style='font-size: 16px; color: #222; text-align: center;'>Hola <strong>$nombre</strong>,</p>
            <p style='font-size: 16px; color: #222; text-align: center;'>
              Gracias por registrarte. Para activar tu cuenta, por favor haz clic en el siguiente botón:
            </p>
            <div style='text-align: center; margin: 24px 0;'>
              <a href='$link' style='background: #2563eb; color: #fff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 18px; display: inline-block;'>
                Verificar mi cuenta
              </a>
            </div>
            <p style='font-size: 14px; color: #555; text-align: center;'>
              Si no solicitaste este registro, puedes ignorar este mensaje.<br>
              <span style='color: #888;'>Este enlace expirará en 24 horas.</span>
            </p>
            <hr style='margin: 32px 0; border: none; border-top: 1px solid #e5e7eb;'>
            <p style='font-size: 12px; color: #888; text-align: center;'>
              © ".date('Y')." Antares Travel. Todos los derechos reservados.
            </p>
          </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Si falla, devuelve el error
        return "Error al enviar correo: {$mail->ErrorInfo}";
    }
}
?>
