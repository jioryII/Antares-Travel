<?php
require_once __DIR__ . "/../../../vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía correo de verificación para administradores
 */
function enviarCorreoVerificacionAdmin($email, $nombre, $link) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Gmail (misma cuenta que usuarios)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'andiquispe9422@gmail.com'; // misma cuenta que usuarios
        $mail->Password   = 'rytiivlavpjznoww'; // misma contraseña de app
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Remitente y destinatario
        $mail->setFrom('noreply@antares.com', 'Antares Travel - Administración');
        $mail->addAddress($email, $nombre);

        // Contenido del correo para administradores
        $mail->isHTML(true);
        $mail->Subject = 'Verificación de cuenta - Panel de Administración Antares Travel';
        $mail->Body = "
          <div style='font-family: Arial, sans-serif; background: #f8fafc; padding: 32px; border-radius: 12px; max-width: 600px; margin: auto;'>
            <div style='text-align: center; margin-bottom: 32px;'>
              <img src='http://localhost:8000/imagenes/antares_logozz2.png' alt='Antares Travel' style='max-width: 150px; height: auto;'>
            </div>
            
            <div style='background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
              <h2 style='color: #1f2937; text-align: center; margin-bottom: 24px; font-size: 24px;'>🛡️ Verificación de Cuenta de Administrador</h2>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 16px;'>
                Hola <strong style='color: #d97706;'>$nombre</strong>,
              </p>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 24px;'>
                Tu cuenta de administrador ha sido creada exitosamente. Para activar tu acceso al panel de administración, 
                por favor verifica tu correo electrónico haciendo clic en el botón de abajo:
              </p>
              
              <div style='text-align: center; margin: 32px 0;'>
                <a href='$link' style='
                  background: linear-gradient(135deg, #d97706, #f59e0b); 
                  color: white; 
                  padding: 16px 32px; 
                  border-radius: 8px; 
                  text-decoration: none; 
                  font-weight: bold; 
                  font-size: 16px; 
                  display: inline-block;
                  box-shadow: 0 4px 14px rgba(217, 119, 6, 0.3);
                '>
                  ✅ Verificar Cuenta de Administrador
                </a>
              </div>
              
              <div style='background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 16px; margin: 24px 0;'>
                <p style='font-size: 14px; color: #92400e; margin: 0; text-align: center;'>
                  <strong>⚠️ Importante:</strong> Este es un enlace de verificación para el panel de administración. 
                  No compartas este correo con nadie.
                </p>
              </div>
              
              <p style='font-size: 14px; color: #6b7280; text-align: center; margin-top: 24px;'>
                Si no solicitaste esta cuenta, por favor ignora este mensaje y contacta al administrador del sistema.<br>
                <span style='color: #9ca3af;'>Este enlace expirará en 24 horas por seguridad.</span>
              </p>
            </div>
            
            <hr style='margin: 32px 0; border: none; border-top: 1px solid #e5e7eb;'>
            
            <p style='font-size: 12px; color: #9ca3af; text-align: center;'>
              © ".date('Y')." Antares Travel - Panel de Administración. Todos los derechos reservados.<br>
              <span style='color: #d1d5db;'>Este es un correo automático, no responder.</span>
            </p>
          </div>";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar correo de verificación admin: {$mail->ErrorInfo}");
        return "Error al enviar correo: {$mail->ErrorInfo}";
    }
}

/**
 * Envía correo de notificación de cuenta activada
 */
function enviarCorreoActivacionCompleta($email, $nombre) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'andiquispe9422@gmail.com';
        $mail->Password   = 'rytiivlavpjznoww';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('noreply@antares.com', 'Antares Travel - Administración');
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = '✅ Correo Verificado - Pendiente de Aprobación | Antares Travel';
        $mail->Body = "
          <div style='font-family: Arial, sans-serif; background: #f8fafc; padding: 32px; border-radius: 12px; max-width: 600px; margin: auto;'>
            <div style='text-align: center; margin-bottom: 32px;'>
              <img src='http://localhost:8000/imagenes/antares_logozz2.png' alt='Antares Travel' style='max-width: 150px; height: auto;'>
            </div>
            
            <div style='background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
              <h2 style='color: #f59e0b; text-align: center; margin-bottom: 24px; font-size: 24px;'>⏳ Correo Verificado - Esperando Aprobación</h2>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 24px;'>
                Hola <strong style='color: #d97706;'>$nombre</strong>,
              </p>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 24px;'>
                Tu correo electrónico ha sido <strong>verificado exitosamente</strong>. 
                Sin embargo, tu cuenta está <strong>pendiente de aprobación</strong> por un superadministrador.
              </p>
              
              <div style='background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 16px; margin: 24px 0;'>
                <p style='font-size: 14px; color: #92400e; margin: 0; text-align: center;'>
                  <strong>📝 Estado Actual:</strong><br>
                  ✅ Correo verificado<br>
                  ⏳ Esperando aprobación de administrador<br>
                  📅 Verificado: " . date('d/m/Y H:i') . "
                </p>
              </div>
              
              <p style='font-size: 14px; color: #6b7280; text-align: center; margin-top: 24px;'>
                Te notificaremos por correo electrónico cuando tu cuenta sea aprobada y puedas acceder al panel de administración.
              </p>
            </div>
            
            <hr style='margin: 32px 0; border: none; border-top: 1px solid #e5e7eb;'>
            
            <p style='font-size: 12px; color: #9ca3af; text-align: center;'>
              © ".date('Y')." Antares Travel - Panel de Administración. Todos los derechos reservados.
            </p>
          </div>";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar correo de activación completa: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Envía correo de solicitud de aprobación a superadministradores
 */
function enviarCorreoSolicitudAprobacion($email_superadmin, $nombre_superadmin, $nombre_solicitante, $email_solicitante, $token_aprobacion, $token_rechazo) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'andiquispe9422@gmail.com';
        $mail->Password   = 'rytiivlavpjznoww';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('noreply@antares.com', 'Antares Travel - Sistema de Administración');
        $mail->addAddress($email_superadmin, $nombre_superadmin);

        $link_aprobar = "http://localhost:8000/src/admin/auth/aprobar_admin.php?token=$token_aprobacion&accion=aprobar";
        $link_rechazar = "http://localhost:8000/src/admin/auth/aprobar_admin.php?token=$token_rechazo&accion=rechazar";

        $mail->isHTML(true);
        $mail->Subject = '🚨 Solicitud de Aprobación - Nuevo Administrador | Antares Travel';
        $mail->Body = "
          <div style='font-family: Arial, sans-serif; background: #f8fafc; padding: 32px; border-radius: 12px; max-width: 600px; margin: auto;'>
            <div style='text-align: center; margin-bottom: 32px;'>
              <img src='http://localhost:8000/imagenes/antares_logozz2.png' alt='Antares Travel' style='max-width: 150px; height: auto;'>
            </div>
            
            <div style='background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
              <h2 style='color: #dc2626; text-align: center; margin-bottom: 24px; font-size: 24px;'>🚨 Solicitud de Aprobación de Administrador</h2>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 24px;'>
                Hola <strong style='color: #d97706;'>$nombre_superadmin</strong>,
              </p>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 24px;'>
                Un nuevo administrador ha completado el proceso de verificación de correo y 
                <strong>requiere tu aprobación</strong> para acceder al panel de administración.
              </p>
              
              <div style='background: #f3f4f6; border-radius: 8px; padding: 24px; margin: 24px 0;'>
                <h3 style='color: #1f2937; margin: 0 0 16px 0; text-align: center;'>📋 Detalles del Solicitante</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                  <tr>
                    <td style='padding: 8px; font-weight: bold; color: #374151;'>👤 Nombre:</td>
                    <td style='padding: 8px; color: #6b7280;'>$nombre_solicitante</td>
                  </tr>
                  <tr>
                    <td style='padding: 8px; font-weight: bold; color: #374151;'>📧 Email:</td>
                    <td style='padding: 8px; color: #6b7280;'>$email_solicitante</td>
                  </tr>
                  <tr>
                    <td style='padding: 8px; font-weight: bold; color: #374151;'>📅 Solicitud:</td>
                    <td style='padding: 8px; color: #6b7280;'>" . date('d/m/Y H:i') . "</td>
                  </tr>
                </table>
              </div>
              
              <div style='text-align: center; margin: 32px 0;'>
                <a href='$link_aprobar' style='
                  background: linear-gradient(135deg, #059669, #10b981); 
                  color: white; 
                  padding: 14px 28px; 
                  border-radius: 8px; 
                  text-decoration: none; 
                  font-weight: bold; 
                  font-size: 16px; 
                  display: inline-block;
                  margin: 8px;
                  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
                '>
                  ✅ APROBAR ACCESO
                </a>
                
                <a href='$link_rechazar' style='
                  background: linear-gradient(135deg, #dc2626, #ef4444); 
                  color: white; 
                  padding: 14px 28px; 
                  border-radius: 8px; 
                  text-decoration: none; 
                  font-weight: bold; 
                  font-size: 16px; 
                  display: inline-block;
                  margin: 8px;
                  box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
                '>
                  ❌ RECHAZAR SOLICITUD
                </a>
              </div>
              
              <div style='background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 16px; margin: 24px 0;'>
                <p style='font-size: 14px; color: #991b1b; margin: 0; text-align: center;'>
                  <strong>⚠️ Importante:</strong> Esta solicitud expirará en 72 horas. 
                  Revisa cuidadosamente antes de aprobar el acceso al panel de administración.
                </p>
              </div>
            </div>
            
            <hr style='margin: 32px 0; border: none; border-top: 1px solid #e5e7eb;'>
            
            <p style='font-size: 12px; color: #9ca3af; text-align: center;'>
              © ".date('Y')." Antares Travel - Sistema de Aprobación Automática.<br>
              <span style='color: #d1d5db;'>Este correo es confidencial y está dirigido solo a superadministradores.</span>
            </p>
          </div>";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar correo de solicitud de aprobación: {$mail->ErrorInfo}");
        return "Error al enviar correo: {$mail->ErrorInfo}";
    }
}

/**
 * Envía correo de acceso aprobado
 */
function enviarCorreoAccesoAprobado($email, $nombre) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'andiquispe9422@gmail.com';
        $mail->Password   = 'rytiivlavpjznoww';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('noreply@antares.com', 'Antares Travel - Administración');
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = '🎉 ¡Acceso Aprobado! - Panel de Administración | Antares Travel';
        $mail->Body = "
          <div style='font-family: Arial, sans-serif; background: #f8fafc; padding: 32px; border-radius: 12px; max-width: 600px; margin: auto;'>
            <div style='text-align: center; margin-bottom: 32px;'>
              <img src='http://localhost:8000/imagenes/antares_logozz2.png' alt='Antares Travel' style='max-width: 150px; height: auto;'>
            </div>
            
            <div style='background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
              <h2 style='color: #059669; text-align: center; margin-bottom: 24px; font-size: 28px;'>🎉 ¡Felicidades! Acceso Aprobado</h2>
              
              <p style='font-size: 18px; color: #374151; text-align: center; margin-bottom: 24px;'>
                Hola <strong style='color: #d97706;'>$nombre</strong>,
              </p>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 32px;'>
                ¡Excelentes noticias! Tu solicitud de acceso al panel de administración ha sido 
                <strong style='color: #059669;'>APROBADA</strong> por un superadministrador.
              </p>
              
              <div style='text-align: center; margin: 32px 0;'>
                <a href='http://localhost:8000/src/admin/auth/login.php' style='
                  background: linear-gradient(135deg, #059669, #10b981); 
                  color: white; 
                  padding: 16px 32px; 
                  border-radius: 8px; 
                  text-decoration: none; 
                  font-weight: bold; 
                  font-size: 18px; 
                  display: inline-block;
                  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
                '>
                  🚀 ACCEDER AL PANEL DE ADMINISTRACIÓN
                </a>
              </div>
              
              <div style='background: #ecfdf5; border: 1px solid #10b981; border-radius: 6px; padding: 20px; margin: 24px 0;'>
                <h3 style='color: #065f46; margin: 0 0 12px 0; text-align: center;'>📊 Detalles de tu Cuenta</h3>
                <div style='text-align: center; color: #065f46;'>
                  <p style='margin: 8px 0;'><strong>📧 Email:</strong> $email</p>
                  <p style='margin: 8px 0;'><strong>🎯 Rol:</strong> Administrador</p>
                  <p style='margin: 8px 0;'><strong>✅ Estado:</strong> Activa</p>
                  <p style='margin: 8px 0;'><strong>📅 Aprobada:</strong> " . date('d/m/Y H:i') . "</p>
                </div>
              </div>
              
              <p style='font-size: 14px; color: #6b7280; text-align: center; margin-top: 24px;'>
                Ya puedes iniciar sesión con tus credenciales y comenzar a usar todas las funcionalidades del panel de administración.
              </p>
            </div>
            
            <hr style='margin: 32px 0; border: none; border-top: 1px solid #e5e7eb;'>
            
            <p style='font-size: 12px; color: #9ca3af; text-align: center;'>
              © ".date('Y')." Antares Travel - Panel de Administración.<br>
              <span style='color: #d1d5db;'>Bienvenido al equipo administrativo de Antares Travel.</span>
            </p>
          </div>";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar correo de acceso aprobado: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Envía correo de acceso rechazado
 */
function enviarCorreoAccesoRechazado($email, $nombre) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'andiquispe9422@gmail.com';
        $mail->Password   = 'rytiivlavpjznoww';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('noreply@antares.com', 'Antares Travel - Administración');
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = '❌ Solicitud de Acceso Rechazada | Antares Travel';
        $mail->Body = "
          <div style='font-family: Arial, sans-serif; background: #f8fafc; padding: 32px; border-radius: 12px; max-width: 600px; margin: auto;'>
            <div style='text-align: center; margin-bottom: 32px;'>
              <img src='http://localhost:8000/imagenes/antares_logozz2.png' alt='Antares Travel' style='max-width: 150px; height: auto;'>
            </div>
            
            <div style='background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
              <h2 style='color: #dc2626; text-align: center; margin-bottom: 24px; font-size: 24px;'>❌ Solicitud de Acceso Rechazada</h2>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 24px;'>
                Hola <strong style='color: #d97706;'>$nombre</strong>,
              </p>
              
              <p style='font-size: 16px; color: #374151; text-align: center; margin-bottom: 24px;'>
                Lamentamos informarte que tu solicitud de acceso al panel de administración 
                ha sido <strong style='color: #dc2626;'>rechazada</strong> por un superadministrador.
              </p>
              
              <div style='background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 20px; margin: 24px 0;'>
                <p style='font-size: 14px; color: #991b1b; margin: 0; text-align: center;'>
                  <strong>📋 Detalles:</strong><br>
                  • Tu cuenta ha sido eliminada del sistema<br>
                  • No tienes acceso al panel de administración<br>
                  • Fecha de decisión: " . date('d/m/Y H:i') . "
                </p>
              </div>
              
              <p style='font-size: 14px; color: #6b7280; text-align: center; margin-top: 24px;'>
                Si crees que esto es un error o necesitas más información, por favor contacta directamente 
                con el equipo de administración de Antares Travel.
              </p>
              
              <div style='text-align: center; margin: 32px 0;'>
                <a href='mailto:admin@antares.com' style='
                  background: linear-gradient(135deg, #6b7280, #9ca3af); 
                  color: white; 
                  padding: 12px 24px; 
                  border-radius: 8px; 
                  text-decoration: none; 
                  font-weight: bold; 
                  font-size: 14px; 
                  display: inline-block;
                '>
                  📧 Contactar Administración
                </a>
              </div>
            </div>
            
            <hr style='margin: 32px 0; border: none; border-top: 1px solid #e5e7eb;'>
            
            <p style='font-size: 12px; color: #9ca3af; text-align: center;'>
              © ".date('Y')." Antares Travel - Sistema de Administración.
            </p>
          </div>";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar correo de acceso rechazado: {$mail->ErrorInfo}");
        return false;
    }
}
?>
