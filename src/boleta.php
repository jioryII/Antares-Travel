<?php
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_email']) || !filter_var($_SESSION['user_email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: auth/login.php?redirect=boleta.php');
    exit;
}

$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
require_once __DIR__ . '/languages/' . $current_lang . '.php';

// Initialize variables
$cart = $_SESSION['cart'] ?? ['paquetes' => [], 'total_paquetes' => 0];
$tour_details = [];
$pasajeros_data = [];

// Fetch complete tour details with guide info
if (!empty($cart['paquetes'])) {
    foreach ($cart['paquetes'] as $item) {
        if (!isset($item['id_tour'])) {
            error_log("Invalid tour data in cart: " . print_r($item, true));
            header('Location: reserva.php?error=invalid_tour');
            exit;
        }
        
        // Consulta completa con información del guía
        $query_tour = "SELECT t.id_tour, t.titulo, t.descripcion, t.precio, t.duracion, 
                             t.hora_salida, t.hora_llegada, t.lugar_salida, t.lugar_llegada,
                             t.imagen_principal, g.nombre as guia_nombre, g.apellido as guia_apellido,
                             g.telefono as guia_telefono, g.experiencia as guia_experiencia,
                             r.nombre_region
                       FROM tours t 
                       LEFT JOIN guias g ON t.id_guia = g.id_guia 
                       LEFT JOIN regiones r ON t.id_region = r.id_region
                       WHERE t.id_tour = ?";
        
        $stmt_tour = $conn->prepare($query_tour);
        if (!$stmt_tour) {
            error_log("Prepare failed: " . $conn->error);
            die("Error: Database query preparation failed.");
        }
        $stmt_tour->bind_param("i", $item['id_tour']);
        $stmt_tour->execute();
        $result = $stmt_tour->get_result();
        if ($result && $tour_detail = $result->fetch_assoc()) {
            $tour_detail['total_price'] = $tour_detail['precio'] * ($item['adultos'] + $item['ninos']);
            $tour_detail['fecha'] = $item['fecha'];
            $tour_detail['adultos'] = $item['adultos'];
            $tour_detail['ninos'] = $item['ninos'];
            $tour_detail['infantes'] = $item['infantes'] ?? 0;
            $tour_details[$item['id_tour']] = $tour_detail;
        } else {
            error_log("Tour not found for id_tour: {$item['id_tour']}");
            header('Location: reserva.php?error=tour_not_found');
            exit;
        }
        $stmt_tour->close();
    }
} else {
    header('Location: reserva.php?error=empty_cart');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contacto = $_POST['contacto'] ?? [];
    $pasajeros = $_POST['pasajeros'] ?? [];
    $total_price = floatval($_POST['total_price'] ?? 0);

    error_log("POST Data in boleta.php: " . print_r($_POST, true));

    // Validaciones
    if (empty($contacto['nombre']) || empty($contacto['email']) || !filter_var($contacto['email'], FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid contact data: " . print_r($contacto, true));
        header('Location: reserva.php?error=invalid_contact');
        exit;
    }
    if (empty($pasajeros) || !is_array($pasajeros)) {
        error_log("Invalid passenger data: " . print_r($pasajeros, true));
        header('Location: reserva.php?error=invalid_passengers');
        exit;
    }
    if ($total_price <= 0) {
        error_log("Invalid total price: " . $total_price);
        header('Location: reserva.php?error=invalid_price');
        exit;
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        $reserva_ids = [];
        $email_tours_info = [];
        
        foreach ($cart['paquetes'] as $tour_index => $item) {
            $tour_price = $tour_details[$item['id_tour']]['precio'] ?? 0;
            $monto_total = $tour_price * ($item['adultos'] + $item['ninos']);
            
            $query_reserva = "INSERT INTO reservas (id_usuario, id_tour, fecha_tour, monto_total, estado, origen_reserva) VALUES (?, ?, ?, ?, 'Confirmada', 'Web')";
            $stmt_reserva = $conn->prepare($query_reserva);
            if (!$stmt_reserva) {
                throw new Exception("Prepare failed for reserva: " . $conn->error);
            }
            $stmt_reserva->bind_param("iisd", $_SESSION['user_id'], $item['id_tour'], $item['fecha'], $monto_total);
            
            if (!$stmt_reserva->execute()) {
                throw new Exception("Execute failed for reserva: " . $stmt_reserva->error);
            }
            
            $reserva_id = $conn->insert_id;
            $reserva_ids[$tour_index] = $reserva_id;
            $stmt_reserva->close();

            // Procesar pasajeros y guardar datos para mostrar
            $tour_passengers = [];
            if (isset($pasajeros[$tour_index]) && is_array($pasajeros[$tour_index])) {
                foreach (['adultos', 'ninos', 'infantes'] as $tipo) {
                    if (isset($pasajeros[$tour_index][$tipo]) && is_array($pasajeros[$tour_index][$tipo])) {
                        foreach ($pasajeros[$tour_index][$tipo] as $pax_index => $pax) {
                            if (!is_array($pax)) {
                                continue;
                            }
                            
                            if (empty($pax['nombre']) || empty($pax['apellido']) || 
                                empty($pax['nacionalidad']) || empty($pax['dni_pasaporte'])) {
                                throw new Exception("Datos de pasajero incompletos para {$tipo} #{$pax_index}");
                            }
                            
                            $query_pax = "INSERT INTO pasajeros (id_reserva, nombre, apellido, dni_pasaporte, nacionalidad, telefono, tipo_pasajero) VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $stmt_pax = $conn->prepare($query_pax);
                            if (!$stmt_pax) {
                                throw new Exception("Prepare failed for pasajero: " . $conn->error);
                            }
                            
                            $telefono = $pax['telefono'] ?? '';
                            $tipo_pasajero = ucfirst(substr($tipo, 0, -1));
                            if ($tipo_pasajero === 'Nino') $tipo_pasajero = 'Niño';
                            
                            $stmt_pax->bind_param("issssss", 
                                $reserva_id, 
                                $pax['nombre'], 
                                $pax['apellido'], 
                                $pax['dni_pasaporte'], 
                                $pax['nacionalidad'], 
                                $telefono, 
                                $tipo_pasajero
                            );
                            
                            if (!$stmt_pax->execute()) {
                                throw new Exception("Execute failed for pasajero: " . $stmt_pax->error);
                            }
                            $stmt_pax->close();
                            
                            // Guardar datos completos del pasajero
                            $tour_passengers[] = [
                                'nombre' => $pax['nombre'],
                                'apellido' => $pax['apellido'],
                                'dni_pasaporte' => $pax['dni_pasaporte'],
                                'nacionalidad' => $pax['nacionalidad'],
                                'telefono' => $telefono,
                                'tipo' => $tipo_pasajero
                            ];
                        }
                    }
                }
            }
            
            // Guardar toda la información del tour para mostrar
            $email_tours_info[] = [
                'id_tour' => $item['id_tour'],
                'titulo' => $tour_details[$item['id_tour']]['titulo'],
                'descripcion' => $tour_details[$item['id_tour']]['descripcion'],
                'precio' => $tour_details[$item['id_tour']]['precio'],
                'duracion' => $tour_details[$item['id_tour']]['duracion'],
                'fecha' => $item['fecha'],
                'hora_salida' => $tour_details[$item['id_tour']]['hora_salida'],
                'hora_llegada' => $tour_details[$item['id_tour']]['hora_llegada'],
                'lugar_salida' => $tour_details[$item['id_tour']]['lugar_salida'],
                'lugar_llegada' => $tour_details[$item['id_tour']]['lugar_llegada'],
                'imagen_principal' => $tour_details[$item['id_tour']]['imagen_principal'],
                'guia_nombre' => $tour_details[$item['id_tour']]['guia_nombre'],
                'guia_apellido' => $tour_details[$item['id_tour']]['guia_apellido'],
                'guia_telefono' => $tour_details[$item['id_tour']]['guia_telefono'],
                'nombre_region' => $tour_details[$item['id_tour']]['nombre_region'],
                'adultos' => $item['adultos'],
                'ninos' => $item['ninos'],
                'infantes' => $item['infantes'] ?? 0,
                'passengers' => $tour_passengers,
                'price' => $monto_total,
                'reserva_id' => $reserva_id
            ];
        }

        // Envío de email mejorado
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'antarestravelperu@gmail.com';
            $mail->Password = 'imakmtzajnuetwfy';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('antares.travel.cusco@gmail.com', 'Antares Travel Peru');
            $mail->addAddress($contacto['email'], $contacto['nombre']);
            $mail->isHTML(true);
            $mail->Subject = 'Confirmación de Reserva - Antares Travel Peru';
            
            // Contenido del email mejorado
            $email_content = "
            <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; max-width: 800px; margin: 0 auto; background: #ffffff;'>
                <div style='background: linear-gradient(135deg, #A27741 0%, #B8926A 100%); padding: 30px; text-align: center; border-radius: 15px 15px 0 0;'>
                    <h1 style='color: white; margin: 0; font-size: 2.2em; font-weight: 600;'>🎉 ¡Reserva Confirmada!</h1>
                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 1.1em;'>Antares Travel Peru</p>
                </div>
                
                <div style='padding: 30px;'>
                    <p style='font-size: 1.1em; color: #333; margin-bottom: 25px;'>Estimado/a <strong>{$contacto['nombre']}</strong>,</p>
                    <p style='color: #666; margin-bottom: 30px; line-height: 1.6;'>Su reserva ha sido confirmada exitosamente. A continuación, encontrará todos los detalles de su experiencia con nosotros:</p>
                    
                    <div style='background: #f8f9fa; padding: 25px; border-radius: 12px; margin: 25px 0; border-left: 4px solid #A27741;'>
                        <h3 style='color: #A27741; margin: 0 0 15px 0; font-size: 1.3em;'>📞 Información de Contacto</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr><td style='padding: 8px 0; color: #666; font-weight: 500;'>Nombre:</td><td style='padding: 8px 0; color: #333;'><strong>{$contacto['nombre']}</strong></td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-weight: 500;'>Email:</td><td style='padding: 8px 0; color: #333;'><strong>{$contacto['email']}</strong></td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-weight: 500;'>Teléfono:</td><td style='padding: 8px 0; color: #333;'><strong>" . ($contacto['telefono'] ?? 'No proporcionado') . "</strong></td></tr>
                        </table>
                    </div>";
            
            foreach ($email_tours_info as $index => $tour_info) {
                $guia_info = '';
                if (!empty($tour_info['guia_nombre'])) {
                    $guia_info = $tour_info['guia_nombre'] . ' ' . ($tour_info['guia_apellido'] ?? '');
                    if (!empty($tour_info['guia_telefono'])) {
                        $guia_info .= ' (' . $tour_info['guia_telefono'] . ')';
                    }
                } else {
                    $guia_info = 'Por asignar';
                }

                $email_content .= "
                <div style='border: 2px solid #A27741; border-radius: 15px; margin: 25px 0; overflow: hidden; background: white; box-shadow: 0 4px 15px rgba(162, 119, 65, 0.1);'>
                    <div style='background: linear-gradient(135deg, #A27741, #B8926A); color: white; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 1.6em;'>🏔️ " . htmlspecialchars($tour_info['titulo']) . "</h2>
                        <p style='margin: 5px 0 0 0; opacity: 0.9;'>Reserva #" . str_pad($tour_info['reserva_id'], 6, '0', STR_PAD_LEFT) . "</p>
                    </div>
                    
                    <div style='padding: 25px;'>
                        <div style='display: grid; gap: 20px;'>
                            <div style='background: #f8f9fa; padding: 20px; border-radius: 10px;'>
                                <h4 style='color: #A27741; margin: 0 0 12px 0; font-size: 1.2em;'>📋 Detalles del Tour</h4>
                                <p style='margin: 8px 0; color: #333; line-height: 1.6;'><strong>Descripción:</strong> " . htmlspecialchars(substr($tour_info['descripcion'], 0, 200)) . "...</p>
                                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;'>
                                    <div>
                                        <p style='margin: 5px 0; color: #666;'><strong>📅 Fecha:</strong> " . date('d/m/Y', strtotime($tour_info['fecha'])) . "</p>
                                        <p style='margin: 5px 0; color: #666;'><strong>⏰ Duración:</strong> " . ($tour_info['duracion'] ?? 'No especificado') . "</p>
                                        <p style='margin: 5px 0; color: #666;'><strong>🚌 Salida:</strong> " . ($tour_info['lugar_salida'] ?? 'Por confirmar') . "</p>
                                    </div>
                                    <div>
                                        <p style='margin: 5px 0; color: #666;'><strong>🕐 Hora salida:</strong> " . ($tour_info['hora_salida'] ? date('H:i', strtotime($tour_info['hora_salida'])) : 'Por confirmar') . "</p>
                                        <p style='margin: 5px 0; color: #666;'><strong>🕐 Hora retorno:</strong> " . ($tour_info['hora_llegada'] ? date('H:i', strtotime($tour_info['hora_llegada'])) : 'Por confirmar') . "</p>
                                        <p style='margin: 5px 0; color: #666;'><strong>👨‍🏫 Guía:</strong> {$guia_info}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div style='background: #e8f5e8; padding: 20px; border-radius: 10px;'>
                                <h4 style='color: #A27741; margin: 0 0 15px 0; font-size: 1.2em;'>👥 Pasajeros Registrados</h4>";
                
                if (!empty($tour_info['passengers'])) {
                    $email_content .= "<div style='display: grid; gap: 12px;'>";
                    foreach ($tour_info['passengers'] as $passenger) {
                        $email_content .= "
                        <div style='background: white; padding: 15px; border-radius: 8px; border-left: 3px solid #A27741;'>
                            <p style='margin: 0; font-weight: 600; color: #333;'>" . htmlspecialchars($passenger['nombre'] . ' ' . $passenger['apellido']) . " <span style='background: #A27741; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; margin-left: 8px;'>" . $passenger['tipo'] . "</span></p>
                            <p style='margin: 5px 0 0 0; color: #666; font-size: 0.9em;'>
                                <strong>Doc:</strong> " . htmlspecialchars($passenger['dni_pasaporte']) . " | 
                                <strong>Nacionalidad:</strong> " . htmlspecialchars($passenger['nacionalidad']) . 
                                ($passenger['telefono'] ? " | <strong>Tel:</strong> " . htmlspecialchars($passenger['telefono']) : "") . "
                            </p>
                        </div>";
                    }
                    $email_content .= "</div>";
                } else {
                    $email_content .= "<p style='color: #666; font-style: italic;'>No se registraron pasajeros para este tour.</p>";
                }
                
                $email_content .= "
                            </div>
                            
                            <div style='background: #fff3cd; padding: 20px; border-radius: 10px; text-align: center;'>
                                <h4 style='color: #A27741; margin: 0 0 10px 0;'>💰 Información de Precio</h4>
                                <p style='margin: 5px 0; color: #666;'>Precio por persona: <strong>$" . number_format($tour_info['precio'], 2) . "</strong></p>
                                <p style='margin: 5px 0; color: #666;'>Adultos: {$tour_info['adultos']} | Niños: {$tour_info['ninos']} | Infantes: {$tour_info['infantes']}</p>
                                <div style='background: #A27741; color: white; padding: 15px; border-radius: 8px; margin-top: 15px;'>
                                    <p style='margin: 0; font-size: 1.3em; font-weight: 600;'>Total Tour: $" . number_format($tour_info['price'], 2) . "</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>";
            }
            
            $email_content .= "
                <div style='background: linear-gradient(135deg, #A27741, #B8926A); color: white; padding: 25px; border-radius: 12px; text-align: center; margin: 30px 0;'>
                    <h2 style='margin: 0 0 10px 0; font-size: 2em;'>💸 TOTAL GENERAL</h2>
                    <p style='margin: 0; font-size: 2.2em; font-weight: 700;'>$" . number_format($total_price, 2) . "</p>
                </div>
                
                <div style='background: #f8f9fa; padding: 25px; border-radius: 12px; text-align: center;'>
                    <h3 style='color: #A27741; margin: 0 0 15px 0;'>🙏 ¡Gracias por elegir Antares Travel Peru!</h3>
                    <p style='margin: 10px 0; color: #666; line-height: 1.6;'>Estamos emocionados de ser parte de su aventura. Para cualquier consulta o cambio, no dude en contactarnos:</p>
                    <div style='margin: 20px 0;'>
                        <p style='margin: 8px 0; color: #A27741; font-weight: 600;'>📧 antares.travel.cusco@gmail.com</p>
                        <p style='margin: 8px 0; color: #A27741; font-weight: 600;'>📱 +51 966 217 821</p>
                        <p style='margin: 8px 0; color: #A27741; font-weight: 600;'>🌐 www.antarestravelperu.com</p>
                    </div>
                </div>
                </div>
            </div>";

            $mail->Body = $email_content;
            
            if (!$mail->send()) {
                throw new Exception("Error enviando email: " . $mail->ErrorInfo);
            }

        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            $email_error = true;
        }

        // Commit transaction
        $conn->commit();
        
        // Clear cart
        $_SESSION['cart'] = ['paquetes' => [], 'total_paquetes' => 0];
        
        $success_message = isset($email_error) ? 
            "Reserva confirmada, pero hubo un problema enviando el email de confirmación." :
            "Reserva confirmada y email de confirmación enviado.";
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
        header('Location: reserva.php?error=database_failed&message=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: reserva.php?error=invalid_request');
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['boleta_title'] ?? 'Comprobante de Reserva'; ?> - Antares Travel Peru</title>
    <link rel="icon" type="image/png" href="../imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #FFFAF0;
            --primary-color: #A27741;
            --primary-dark: #8B6332;
            --primary-light: #B8926A;
            --text-dark: #2c2c2c;
            --text-light: #666;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 10px 30px rgba(162, 119, 65, 0.15);
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: linear-gradient(135deg, var(--primary-bg) 0%, #f8f4e6 100%);
            min-height: 100vh;
        }

        .boleta-section {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 1rem;
        }

        .boleta-container {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(162, 119, 65, 0.15);
        }

        .boleta-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .boleta-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1.5" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .boleta-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .boleta-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
        }

        .success-message, .warning-message {
            padding: 1.5rem;
            border-radius: 12px;
            margin: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
        }

        .success-message {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 5px solid var(--success);
        }

        .warning-message {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border-left: 5px solid var(--warning);
        }

        .content-section {
            padding: 2rem;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 5px solid var(--primary-color);
            transition: var(--transition);
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(162, 119, 65, 0.15);
        }

        .info-card h3 {
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tour-card {
            border: 2px solid var(--primary-color);
            border-radius: 20px;
            margin-bottom: 2rem;
            overflow: hidden;
            background: white;
            box-shadow: 0 10px 30px rgba(162, 119, 65, 0.1);
        }

        .tour-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .tour-header h3 {
            font-size: 1.6rem;
            margin: 0;
            font-weight: 600;
        }

        .tour-body {
            padding: 2rem;
        }

        .tour-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }

        .detail-section h4 {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tour-image {
            width: 100%;
            max-width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin: 1rem 0;
        }

        .passengers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .passenger-card {
            background: white;
            padding: 1.2rem;
            border-radius: 10px;
            border-left: 3px solid var(--primary-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .passenger-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .passenger-type {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .passenger-info {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .price-summary {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin: 2rem 0;
        }

        .price-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .price-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .total-price {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 1.5rem;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .contact-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }

        .actions-section {
            background: #f8f9fa;
            padding: 2rem;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1.2rem 2.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            margin: 0.5rem 1rem;
            box-shadow: 0 4px 15px rgba(162, 119, 65, 0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(162, 119, 65, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--info), #20a9cc);
        }

        .reserva-id {
            background: var(--primary-color);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            margin-left: 1rem;
        }

        @media (max-width: 768px) {
            .boleta-header h1 {
                font-size: 2rem;
            }
            
            .tour-details-grid {
                grid-template-columns: 1fr;
            }
            
            .passengers-grid {
                grid-template-columns: 1fr;
            }
            
            .btn {
                margin: 0.5rem 0;
                width: 100%;
                justify-content: center;
            }
            
            .content-section {
                padding: 1rem;
            }
        }

        @media print {
            body { background: white; }
            .btn, .actions-section { display: none; }
            .boleta-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <section class="boleta-section">
        <div class="boleta-container">
            <?php if (isset($success_message)): ?>
                <div class="<?php echo isset($email_error) ? 'warning-message' : 'success-message'; ?>">
                    <i class="fas fa-<?php echo isset($email_error) ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <div class="boleta-header">
                <h1><i class="fas fa-ticket-alt"></i> Comprobante de Reserva</h1>
                <p>¡Gracias por confiar en nosotros para su próxima aventura!</p>
            </div>

            <div class="content-section">
                <!-- Información de Contacto -->
                <div class="info-card">
                    <h3><i class="fas fa-user-circle"></i> Información de Contacto</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <strong><i class="fas fa-user"></i> Nombre:</strong><br>
                            <?php echo htmlspecialchars($contacto['nombre'] ?? 'No proporcionado'); ?>
                        </div>
                        <div class="contact-item">
                            <strong><i class="fas fa-envelope"></i> Email:</strong><br>
                            <?php echo htmlspecialchars($contacto['email'] ?? 'No proporcionado'); ?>
                        </div>
                        <div class="contact-item">
                            <strong><i class="fas fa-phone"></i> Teléfono:</strong><br>
                            <?php echo htmlspecialchars($contacto['telefono'] ?? 'No proporcionado'); ?>
                        </div>
                    </div>
                </div>

                <!-- Tours Reservados -->
                <?php foreach ($email_tours_info as $index => $tour_info): ?>
                    <div class="tour-card">
                        <div class="tour-header">
                            <h3><?php echo htmlspecialchars($tour_info['titulo']); ?></h3>
                            <span class="reserva-id">Reserva #<?php echo str_pad($tour_info['reserva_id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        
                        <div class="tour-body">
                            <div class="tour-details-grid">
                                <!-- Información General -->
                                <div class="detail-section">
                                    <h4><i class="fas fa-info-circle"></i> Información General</h4>
                                    <p><strong>Descripción:</strong><br><?php echo htmlspecialchars($tour_info['descripcion']); ?></p>
                                    <p><strong>Región:</strong> <?php echo htmlspecialchars($tour_info['nombre_region'] ?? 'No especificado'); ?></p>
                                </div>

                                <!-- Detalles del Viaje -->
                                <div class="detail-section">
                                    <h4><i class="fas fa-calendar-alt"></i> Detalles del Viaje</h4>
                                    <p><strong><i class="fas fa-calendar-day"></i> Fecha:</strong> <?php echo date('d/m/Y', strtotime($tour_info['fecha'])); ?></p>
                                    <p><strong><i class="fas fa-clock"></i> Duración:</strong> <?php echo htmlspecialchars($tour_info['duracion'] ?? 'No especificado'); ?></p>
                                    <p><strong><i class="fas fa-map-marker-alt"></i> Lugar de salida:</strong> <?php echo htmlspecialchars($tour_info['lugar_salida'] ?? 'Por confirmar'); ?></p>
                                    <p><strong><i class="fas fa-flag-checkered"></i> Lugar de llegada:</strong> <?php echo htmlspecialchars($tour_info['lugar_llegada'] ?? 'Por confirmar'); ?></p>
                                    <p><strong><i class="fas fa-play-circle"></i> Hora de salida:</strong> <?php echo $tour_info['hora_salida'] ? date('H:i', strtotime($tour_info['hora_salida'])) : 'Por confirmar'; ?></p>
                                    <p><strong><i class="fas fa-stop-circle"></i> Hora de retorno:</strong> <?php echo $tour_info['hora_llegada'] ? date('H:i', strtotime($tour_info['hora_llegada'])) : 'Por confirmar'; ?></p>
                                </div>

                                <!-- Guía -->
                                <div class="detail-section">
                                    <h4><i class="fas fa-user-tie"></i> Información del Guía</h4>
                                    <?php if (!empty($tour_info['guia_nombre'])): ?>
                                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($tour_info['guia_nombre'] . ' ' . ($tour_info['guia_apellido'] ?? '')); ?></p>
                                        <?php if (!empty($tour_info['guia_telefono'])): ?>
                                            <p><strong><i class="fas fa-phone"></i> Teléfono:</strong> <?php echo htmlspecialchars($tour_info['guia_telefono']); ?></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p><em>Guía por asignar</em></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Pasajeros -->
                            <?php if (!empty($tour_info['passengers'])): ?>
                                <div class="detail-section">
                                    <h4><i class="fas fa-users"></i> Pasajeros Registrados (<?php echo count($tour_info['passengers']); ?>)</h4>
                                    <div class="passengers-grid">
                                        <?php foreach ($tour_info['passengers'] as $passenger): ?>
                                            <div class="passenger-card">
                                                <div class="passenger-name">
                                                    <?php echo htmlspecialchars($passenger['nombre'] . ' ' . $passenger['apellido']); ?>
                                                    <span class="passenger-type"><?php echo htmlspecialchars($passenger['tipo']); ?></span>
                                                </div>
                                                <div class="passenger-info">
                                                    <strong>Documento:</strong> <?php echo htmlspecialchars($passenger['dni_pasaporte']); ?><br>
                                                    <strong>Nacionalidad:</strong> <?php echo htmlspecialchars($passenger['nacionalidad']); ?>
                                                    <?php if (!empty($passenger['telefono'])): ?>
                                                        <br><strong>Teléfono:</strong> <?php echo htmlspecialchars($passenger['telefono']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Información de Precios -->
                            <div class="price-summary">
                                <h4><i class="fas fa-calculator"></i> Resumen de Precios</h4>
                                <div class="price-details">
                                    <div class="price-item">
                                        <strong>Precio por persona</strong><br>
                                        $<?php echo number_format($tour_info['precio'], 2); ?>
                                    </div>
                                    <div class="price-item">
                                        <strong>Adultos</strong><br>
                                        <?php echo $tour_info['adultos']; ?> personas
                                    </div>
                                    <div class="price-item">
                                        <strong>Niños</strong><br>
                                        <?php echo $tour_info['ninos']; ?> personas
                                    </div>
                                    <div class="price-item">
                                        <strong>Infantes</strong><br>
                                        <?php echo $tour_info['infantes']; ?> personas
                                    </div>
                                </div>
                                <div class="total-price">
                                    Total del Tour: $<?php echo number_format($tour_info['price'], 2); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Total General -->
                <div class="price-summary">
                    <h2><i class="fas fa-money-bill-wave"></i> TOTAL GENERAL</h2>
                    <div class="total-price" style="font-size: 2.2rem;">
                        $<?php echo number_format($total_price, 2); ?>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="actions-section">
                <p style="margin-bottom: 2rem; color: var(--text-light); font-size: 1.1rem;">
                    <i class="fas fa-heart" style="color: #e74c3c;"></i> 
                    Gracias por elegir Antares Travel Peru para su aventura
                </p>
                <div>
                    <a href="../index.php" class="btn">
                        <i class="fas fa-home"></i> Volver al Inicio
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Imprimir Comprobante
                    </button>
                </div>
                <p style="margin-top: 2rem; color: var(--text-light);">
                    <strong>Contacto:</strong> 
                    <i class="fas fa-envelope"></i> antares.travel.cusco@gmail.com | 
                    <i class="fas fa-phone"></i> +51 966 217 821
                </p>
            </div>
        </div>
    </section>

    <script>
        // Auto-scroll to top on load
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
            
            // Add animation to cards
            const cards = document.querySelectorAll('.tour-card, .info-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(30px)';
                    card.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 200);
            });
        });

        // Print optimization
        window.addEventListener('beforeprint', function() {
            document.body.style.background = 'white';
        });
        
        window.addEventListener('afterprint', function() {
            document.body.style.background = '';
        });
    </script>
</body>
</html>