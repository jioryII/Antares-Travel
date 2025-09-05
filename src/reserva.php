<?php
// reserva.php
session_start();
require_once 'config/conexion.php';
require '../vendor/autoload.php'; // Para Dompdf

use Dompdf\Dompdf;

if (!isset($_SESSION['user_email'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_email = $_SESSION['user_email'];
$user_query = "SELECT id_usuario, nombre FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$id_usuario = $user['id_usuario'] ?? null;
$user_name = $user['nombre'] ?? '';

$tours_query = "SELECT id_tour, titulo, precio FROM tours ORDER BY titulo";
$tours_result = $conn->query($tours_query);

$is_whatsapp = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_tour = $_POST['id_tour'] ?? '';
    $fecha_tour = $_POST['fecha_tour'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';

    // Arrays para múltiples pasajeros
    $nombres = $_POST['nombre'] ?? [];
    $apellidos = $_POST['apellido'] ?? [];
    $dnis = $_POST['dni'] ?? [];
    $nacionalidades = $_POST['nacionalidad'] ?? [];
    $telefonos = $_POST['telefono'] ?? [];
    $tipos = $_POST['tipo'] ?? [];

    if (isset($_POST['whatsapp'])) {
        $is_whatsapp = true;
    }

    // Validación básica
    $errors = [];
    if (empty($id_tour) || empty($fecha_tour)) {
        $errors[] = 'Tour y fecha son requeridos.';
    }
    $num_pasajeros = count($nombres);
    if ($num_pasajeros < 1) {
        $errors[] = 'Debe agregar al menos un pasajero.';
    }
    for ($i = 0; $i < $num_pasajeros; $i++) {
        if (empty($nombres[$i]) || empty($apellidos[$i]) || empty($dnis[$i])) {
            $errors[] = "Pasajero " . ($i + 1) . ": Nombre, apellido y DNI son requeridos.";
        }
        // Tipo por defecto
        $tipos[$i] = $tipos[$i] ?? 'Adulto';
    }
    if ($is_whatsapp && empty($telefonos[0])) {
        $errors[] = 'El teléfono del primer pasajero es requerido para enviar por WhatsApp.';
    }

    if (empty($errors)) {
        // Obtener precio del tour
        $tour_query = "SELECT precio, titulo FROM tours WHERE id_tour = ?";
        $stmt = $conn->prepare($tour_query);
        $stmt->bind_param("i", $id_tour);
        $stmt->execute();
        $tour_result = $stmt->get_result();
        $tour = $tour_result->fetch_assoc();
        $precio_unitario = $tour['precio'] ?? 0;
        $monto_total = $precio_unitario * $num_pasajeros; // Asumiendo mismo precio por pasajero
        $tour_titulo = $tour['titulo'] ?? '';

        // Transacción para inserts atómicos
        $conn->begin_transaction();
        try {
            // Insertar reserva
            $insert_reserva = "INSERT INTO reservas (id_usuario, id_tour, fecha_tour, monto_total, observaciones, estado) VALUES (?, ?, ?, ?, ?, 'Pendiente')";
            $stmt = $conn->prepare($insert_reserva);
            $stmt->bind_param("iisds", $id_usuario, $id_tour, $fecha_tour, $monto_total, $observaciones);
            $stmt->execute();
            $id_reserva = $conn->insert_id;

            // Insertar pasajeros
            $insert_pasajero = "INSERT INTO pasajeros (id_reserva, nombre, apellido, dni_pasaporte, nacionalidad, telefono, tipo_pasajero) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_pasajero);
            for ($i = 0; $i < $num_pasajeros; $i++) {
                $nacionalidad = $nacionalidades[$i] ?? '';
                $telefono = $telefonos[$i] ?? '';
                $stmt->bind_param("issssss", $id_reserva, $nombres[$i], $apellidos[$i], $dnis[$i], $nacionalidad, $telefono, $tipos[$i]);
                $stmt->execute();
            }

            $conn->commit();

            // Generar PDF
            $pasajeros_html = '<table><tr><th>Nombre Completo</th><th>DNI/Pasaporte</th><th>Nacionalidad</th><th>Teléfono</th><th>Tipo</th></tr>';
            for ($i = 0; $i < $num_pasajeros; $i++) {
                $nombre_completo = htmlspecialchars($nombres[$i] . ' ' . $apellidos[$i]);
                $pasajeros_html .= '<tr><td>' . $nombre_completo . '</td><td>' . htmlspecialchars($dnis[$i]) . '</td><td>' . htmlspecialchars($nacionalidades[$i] ?? '') . '</td><td>' . htmlspecialchars($telefonos[$i] ?? '') . '</td><td>' . htmlspecialchars($tipos[$i]) . '</td></tr>';
            }
            $pasajeros_html .= '</table>';

            $html = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    h1 { color: #A27741; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Boleta de Reserva - Antares Travel Peru</h1>
                <p>ID Reserva: ' . $id_reserva . '</p>
                <p>Usuario: ' . htmlspecialchars($user_name) . '</p>
                <p>Tour: ' . htmlspecialchars($tour_titulo) . '</p>
                <p>Fecha: ' . $fecha_tour . '</p>
                <p>Monto Total: S/ ' . $monto_total . '</p>
                
                <h2>Pasajeros</h2>
                ' . $pasajeros_html . '
                
                <p>Observaciones: ' . htmlspecialchars($observaciones) . '</p>
            </body>
            </html>';

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdf_output = $dompdf->output();
            $pdf_filename = 'reserva_' . $id_reserva . '.pdf';
            $pdf_path = '../uploads/boletas/' . $pdf_filename;

            // Crear directorio si no existe
            if (!file_exists('../uploads/boletas/')) {
                mkdir('../uploads/boletas/', 0777, true);
            }

            file_put_contents($pdf_path, $pdf_output);

            $pdf_url = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/boletas/' . $pdf_filename; // Ajustar a tu dominio

            if ($is_whatsapp) {
                // Preparar número de WhatsApp del primer pasajero (asumiendo Perú: +51)
                $telefono_ws = preg_replace('/[^0-9]/', '', $telefonos[0] ?? ''); // Limpiar
                if (strlen($telefono_ws) == 9) { // Número peruano típico
                    $telefono_ws = '51' . $telefono_ws;
                } elseif (strlen($telefono_ws) == 10 && substr($telefono_ws, 0, 1) == '0') {
                    $telefono_ws = '51' . substr($telefono_ws, 1);
                } elseif (!str_starts_with($telefono_ws, '51') && !str_starts_with($telefono_ws, '+51')) {
                    $telefono_ws = '51' . $telefono_ws;
                }
                // Mensaje para "guardar" en su propio WhatsApp
                $nombre_ws = $nombres[0] ?? '';
                $apellido_ws = $apellidos[0] ?? '';
                $message = urlencode("Boleta de reserva para $nombre_ws $apellido_ws: $pdf_url");
                $wa_url = "https://wa.me/$telefono_ws?text=$message";
                header("Location: $wa_url");
                exit;
            } else {
                // Descargar PDF
                $dompdf->stream($pdf_filename, ["Attachment" => true]);
                exit;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Error al procesar la reserva: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        echo "<script>alert('" . implode("\\n", $errors) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - Antares Travel Peru</title>
    <link rel="icon" type="image/png" href="../imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #FFFAF0;
            --primary-color: #A27741;
            --primary-dark: #8B6332;
            --primary-light: #B8926A;
            --secondary-color: #5B797C;
            --text-dark: #2c2c2c;
            --text-light: #666;
            --white: #ffffff;
            --transition: all 0.3s ease;
            --shadow: 0 8px 24px rgba(162, 119, 65, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--primary-bg);
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 250, 240, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 1rem 0;
            transition: var(--transition);
            border-bottom: 1px solid rgba(162, 119, 65, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: var(--transition);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .lang-switch {
            display: flex;
            border: 2px solid var(--primary-color);
            border-radius: 25px;
            overflow: hidden;
        }

        .lang-btn {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
        }

        .lang-btn.active {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
            height: 40px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            object-fit: cover;
        }

        .user-profile span {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .user-profile .logout-btn {
            padding: 0.5rem;
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
        }

        /* Google Sign-in Popover */
        .google-signin-container {
            position: fixed;
            top: 80px;
            right: 20px;
            background: var(--white);
            padding: 1rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            z-index: 1001;
            display: none;
            transition: var(--transition);
        }

        .google-signin-container.active {
            display: block;
        }

        .google-signin-container .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--text-light);
        }

        /* Mobile Menu */
        .mobile-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
            z-index: 1001;
        }

        .mobile-menu span {
            width: 25px;
            height: 3px;
            background: var(--primary-color);
            transition: var(--transition);
        }

        .mobile-nav {
            position: fixed;
            top: 80px;
            right: -100%;
            width: 100%;
            max-width: 300px;
            height: calc(100vh - 80px);
            background: var(--white);
            box-shadow: var(--shadow);
            transition: var(--transition);
            z-index: 999;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .mobile-nav.active {
            right: 0;
        }

        .mobile-nav a {
            color: var(--text-dark);
            text-decoration: none;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(162, 119, 65, 0.1);
            font-weight: 500;
        }

        .mobile-auth-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }

        /* Sections */
        .section {
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary-light);
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        form {
            max-width: 600px;
            margin: 0 auto;
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        form div {
            margin-bottom: 15px;
        }

        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        form input, form select, form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--primary-light);
            border-radius: 5px;
        }

        form button {
            margin-right: 10px;
        }

        .pasajero {
            border: 1px solid var(--primary-light);
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .remove-pasajero {
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
        }

        /* Footer */
        .footer {
            background: var(--primary-dark);
            color: var(--white);
            padding: 40px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-section h3 {
            color: var(--primary-light);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .footer-section p, .footer-section li {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-section a:hover {
            color: var(--primary-light);
        }

        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 15px;
        }

        .social-link {
            width: 36px;
            height: 36px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: var(--transition);
        }

        .social-link:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 15px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .auth-buttons {
                display: none;
            }

            .mobile-menu {
                display: flex;
            }

            .container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php#inicio" class="logo">
                <img src="../imagenes/antares_logozz2.png" alt="Antares Travel Peru Logo" height="50" loading="lazy">
                ANTARES TRAVEL PERU
            </a>
            <ul class="nav-links">
                <li><a href="../index.php#inicio">Inicio</a></li>
                <li><a href="../index.php#tours">Tours</a></li>
                <li><a href="../index.php#guias">Guías</a></li>
                <li><a href="../index.php#experiencias">Experiencias</a></li>
                <li><a href="../index.php#reservas">Reservas</a></li>
            </ul>
            <div class="auth-buttons">
                <div class="lang-switch">
                    <button class="lang-btn active" data-lang="es">ES</button>
                    <button class="lang-btn" data-lang="en">EN</button>
                    <button class="lang-btn" data-lang="fr">FR</button>
                </div>
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? '../imagenes/default-avatar.png'); ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="../index.php?logout=1" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
            <div class="mobile-menu" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <div class="mobile-nav" id="mobileNav">
        <a href="../index.php#inicio">Inicio</a>
        <a href="../index.php#tours">Tours</a>
        <a href="../index.php#guias">Guías</a>
        <a href="../index.php#experiencias">Experiencias</a>
        <a href="../index.php#reservas">Reservas</a>
        <div class="mobile-auth-buttons">
            <div class="lang-switch">
                <button class="lang-btn active" data-lang="es">ES</button>
                <button class="lang-btn" data-lang="en">EN</button>
                <button class="lang-btn" data-lang="fr">FR</button>
            </div>
            <div class="user-profile">
                <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? '../imagenes/default-avatar.png'); ?>" alt="Avatar">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="../index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Formulario de Reserva</h2>
                <p class="section-subtitle">Completa los datos para realizar tu reserva</p>
            </div>

            <form method="POST" action="">
                <div>
                    <label for="id_tour">Tour:</label>
                    <select name="id_tour" id="id_tour" required>
                        <?php while ($tour = $tours_result->fetch_assoc()): ?>
                            <option value="<?php echo $tour['id_tour']; ?>">
                                <?php echo htmlspecialchars($tour['titulo']); ?> - S/ <?php echo $tour['precio']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="fecha_tour">Fecha del Tour:</label>
                    <input type="date" name="fecha_tour" id="fecha_tour" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <h3>Pasajeros</h3>
                <div id="pasajeros">
                    <div class="pasajero">
                        <h4>Pasajero 1</h4>
                        <div>
                            <label for="nombre[]">Nombre:</label>
                            <input type="text" name="nombre[]" required>
                        </div>
                        <div>
                            <label for="apellido[]">Apellido:</label>
                            <input type="text" name="apellido[]" required>
                        </div>
                        <div>
                            <label for="dni[]">DNI/Pasaporte:</label>
                            <input type="text" name="dni[]" required>
                        </div>
                        <div>
                            <label for="nacionalidad[]">Nacionalidad:</label>
                            <input type="text" name="nacionalidad[]">
                        </div>
                        <div>
                            <label for="telefono[]">Teléfono (para WhatsApp, ingrese con +51 si es Perú):</label>
                            <input type="text" name="telefono[]" placeholder="+51 999999999">
                        </div>
                        <div>
                            <label for="tipo[]">Tipo de Pasajero:</label>
                            <select name="tipo[]">
                                <option value="Adulto">Adulto</option>
                                <option value="Niño">Niño</option>
                                <option value="Infante">Infante</option>
                            </select>
                        </div>
                        <button type="button" class="remove-pasajero" style="display:none;" onclick="removePasajero(this)">Eliminar Pasajero</button>
                    </div>
                </div>
                <button type="button" onclick="addPasajero()">Añadir Pasajero</button>

                <div>
                    <label for="observaciones">Observaciones:</label>
                    <textarea name="observaciones" id="observaciones"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Reservar y Descargar PDF</button>
                <button type="submit" name="whatsapp" value="1" class="btn btn-secondary">Reservar y Guardar en WhatsApp</button>
            </form>
        </div>
    </section>

    <footer class="footer" id="contacto">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Antares Travel Peru</h3>
                    <p>Somos una empresa especializada en turismo receptivo en la ciudad del Cusco, con más de 10 años de experiencia brindando servicios de calidad y experiencias inolvidables a nuestros viajeros.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-tripadvisor"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Calle Triunfo 392, Cusco - Perú</li>
                        <li><i class="fas fa-phone"></i> +51 966 217 821</li>
                        <li><i class="fas fa-phone"></i> +51 958 940 100</li>
                        <li><i class="fas fa-envelope"></i> antares.travel.cusco@gmail.com</li>
                        <li><i class="fas fa-globe"></i> www.antarestravelcusco.com</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Servicios</h3>
                    <ul>
                        <li><a href="../index.php#tours">Tours en Cusco</a></li>
                        <li><a href="../index.php#tours">Valle Sagrado</a></li>
                        <li><a href="../index.php#tours">Machu Picchu</a></li>
                        <li><a href="../index.php#tours">Tours de Aventura</a></li>
                        <li><a href="../index.php#guias">Guías Profesionales</a></li>
                        <li><a href="../index.php#tours">Transporte Turístico</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Información Legal</h3>
                    <ul>
                        <li>RUC: 20XXXXXXXXX</li>
                        <li>Licencia de Turismo: XXXX-XXXX</li>
                        <li><a href="#">Términos y Condiciones</a></li>
                        <li><a href="#">Política de Privacidad</a></li>
                        <li><a href="#">Política de Cancelación</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Antares Travel Peru. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const mobileMenu = document.querySelector('.mobile-menu');
            
            mobileNav.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            
            const spans = mobileMenu.querySelectorAll('span');
            if (mobileNav.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileNav = document.getElementById('mobileNav');
            const mobileMenu = document.querySelector('.mobile-menu');
            
            if (mobileNav.classList.contains('active') && 
                !mobileNav.contains(event.target) && 
                !mobileMenu.contains(event.target)) {
                toggleMobileMenu();
            }
        });

        let pasajeroCount = 1;

        function addPasajero() {
            pasajeroCount++;
            const pasajerosDiv = document.getElementById('pasajeros');
            const newPasajero = pasajerosDiv.firstElementChild.cloneNode(true);
            newPasajero.querySelector('h4').textContent = `Pasajero ${pasajeroCount}`;
            newPasajero.querySelector('.remove-pasajero').style.display = 'block';
            // Limpiar valores
            const inputs = newPasajero.querySelectorAll('input');
            inputs.forEach(input => input.value = '');
            const select = newPasajero.querySelector('select');
            select.value = 'Adulto';
            pasajerosDiv.appendChild(newPasajero);
        }

        function removePasajero(button) {
            const pasajeroDiv = button.parentElement;
            pasajeroDiv.remove();
            // Reenumerar
            const pasajeros = document.querySelectorAll('.pasajero');
            pasajeros.forEach((p, index) => {
                p.querySelector('h4').textContent = `Pasajero ${index + 1}`;
                if (index === 0) {
                    p.querySelector('.remove-pasajero').style.display = 'none';
                }
            });
            pasajeroCount--;
        }
    </script>
</body>
</html>