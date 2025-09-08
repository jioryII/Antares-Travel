<?php
session_start();
require_once __DIR__ . '/languages/' . (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es') . '.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_email']) || !filter_var($_SESSION['user_email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: auth/login.php?redirect=reserva.php');
    exit;
}

// Set language
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';

// Get user ID
if (!isset($_SESSION['user_id'])) {
    $query_user = "SELECT id_usuario FROM usuarios WHERE email = ?";
    $stmt_user = $conn->prepare($query_user);
    $stmt_user->bind_param("s", $_SESSION['user_email']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($user = $result_user->fetch_assoc()) {
        $_SESSION['user_id'] = $user['id_usuario'];
    } else {
        error_log("User not found for email: {$_SESSION['user_email']}");
        die('Error: User not found.');
    }
    $stmt_user->close();
}

$id_usuario = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? ['paquetes' => [], 'total_paquetes' => 0];

if (empty($cart['paquetes'])) {
    header('Location: ../index.php?empty_cart=1');
    exit;
}

// Handle remove item
if (isset($_POST['remove_item'])) {
    // Verificar si se ha enviado el índice y el ID del tour
    if (isset($_POST['index']) && isset($_POST['tour_id'])) {
        $index_to_remove = filter_input(INPUT_POST, 'index', FILTER_VALIDATE_INT);
        $id_to_remove = filter_input(INPUT_POST, 'tour_id', FILTER_VALIDATE_INT);

        if ($index_to_remove !== false && isset($cart['paquetes'][$index_to_remove])) {
            $item_to_remove = $cart['paquetes'][$index_to_remove];

            // Confirmar que el tour en el índice coincide con el ID enviado
            if ($item_to_remove['id_tour'] == $id_to_remove) {
                // Eliminar el elemento del carrito
                unset($cart['paquetes'][$index_to_remove]);
                
                // Reindexar el array para evitar problemas con los índices
                $cart['paquetes'] = array_values($cart['paquetes']);
                $cart['total_paquetes'] = count($cart['paquetes']);
                $_SESSION['cart'] = $cart;
            } else {
                // Si hay una discrepancia, buscar y eliminar por ID
                foreach ($cart['paquetes'] as $key => $item) {
                    if ($item['id_tour'] == $id_to_remove) {
                        unset($cart['paquetes'][$key]);
                        $cart['paquetes'] = array_values($cart['paquetes']);
                        $cart['total_paquetes'] = count($cart['paquetes']);
                        $_SESSION['cart'] = $cart;
                        break;
                    }
                }
            }
        }
    }
    header('Location: reserva.php');
    exit;
}

// Get tour details
$tour_details = [];
$total_price = 0;
foreach ($cart['paquetes'] as $item) {
    $query_tour = "SELECT id_tour, titulo, descripcion, precio, hora_salida, hora_llegada, lugar_salida FROM tours WHERE id_tour = ?";
    $stmt_tour = $conn->prepare($query_tour);
    $stmt_tour->bind_param("i", $item['id_tour']);
    $stmt_tour->execute();
    $result = $stmt_tour->get_result();
    if ($tour_detail = $result->fetch_assoc()) {
        $tour_detail['total_price'] = $tour_detail['precio'] * ($item['adultos'] + $item['ninos']);
        $total_price += $tour_detail['total_price'];
        $tour_details[$item['id_tour']] = $tour_detail;
    } else {
        error_log("Tour not found for id_tour: {$item['id_tour']}");
        die("Error: Tour ID {$item['id_tour']} not found.");
    }
    $stmt_tour->close();
}

$contacto = [
    'nombre' => $_SESSION['user_name'] ?? '',
    'email' => $_SESSION['user_email'] ?? '',
    'telefono' => $_SESSION['user_phone'] ?? ''
];

function getImagePath($imagePath) {
    $fallbackImage = 'https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=1200&h=600&fit=crop&crop=center';
    if (empty($imagePath) || !is_string($imagePath)) {
        return $fallbackImage;
    }
    if (preg_match('/^https?:\/\//i', $imagePath)) {
        return htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
    }
    $imagePath = str_replace('\\', '/', trim($imagePath, '/\\'));
    $baseDir = '../Uploads/tours/';
    if (strpos($imagePath, 'uploads/') !== false) {
        $baseDir = '../';
    } elseif (strpos($imagePath, 'tours/') !== false) {
        $baseDir = '../Uploads/';
    }
    $fullPath = $baseDir . $imagePath;
    return file_exists($fullPath) ? htmlspecialchars($fullPath, ENT_QUOTES, 'UTF-8') : $fallbackImage;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['cart_title'] ?? 'Your Cart'; ?> - Antares Travel Peru</title>
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
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 10px 30px rgba(162, 119, 65, 0.15);
            --shadow-hover: 0 15px 50px rgba(162, 119, 65, 0.25);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--text-dark);
            background: var(--primary-bg);
            overflow-x: hidden;
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 250, 240, 0.92);
            backdrop-filter: blur(20px);
            z-index: 1000;
            padding: 1.2rem 0;
            transition: var(--transition);
            border-bottom: 1px solid rgba(162, 119, 65, 0.15);
        }

        .navbar.scrolled { background: rgba(255, 250, 240, 0.98); box-shadow: var(--shadow); }
        .nav-container {
            max-width: 1440px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2.5rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            gap: 12px;
            transition: var(--transition);
        }

        .logo img { vertical-align: middle; transition: transform 0.3s ease; }
        .logo:hover img { transform: scale(1.1); }
        .nav-links { display: flex; list-style: none; gap: 2.5rem; }
        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
            position: relative;
        }

        .nav-links a:hover { color: var(--primary-color); }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: var(--transition);
        }

        .nav-links a:hover::after { width: 100%; }
        .auth-buttons { display: flex; align-items: center; gap: 1.2rem; }
        .lang-switch {
            display: flex;
            border: 2px solid var(--primary-color);
            border-radius: 50px;
            overflow: hidden;
            background: var(--white);
        }

        .lang-btn {
            padding: 0.6rem 1.2rem;
            text-decoration: none;
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .lang-btn.active { background: var(--primary-color); color: var(--white); }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.8rem 1.8rem;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-3px);
        }

        .user-profile { display: flex; align-items: center; gap: 0.8rem; }
        .user-profile img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            object-fit: cover;
            transition: var(--transition);
        }

        .user-profile span { font-size: 0.95rem; font-weight: 500; color: var(--text-dark); }
        .logout-btn { color: var(--primary-color); text-decoration: none; transition: var(--transition); }
        .logout-btn:hover { color: var(--primary-dark); }
        .mobile-menu { display: none; flex-direction: column; cursor: pointer; gap: 5px; z-index: 1001; }
        .mobile-menu span {
            width: 28px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
            transition: var(--transition);
        }

        .mobile-menu.active span:first-child { transform: rotate(45deg) translate(6px, 6px); }
        .mobile-menu.active span:nth-child(2) { opacity: 0; }
        .mobile-menu.active span:last-child { transform: rotate(-45deg) translate(8px, -8px); }
        .mobile-nav {
            position: fixed;
            top: 80px;
            right: -100%;
            width: 100%;
            max-width: 320px;
            height: calc(100vh - 80px);
            background: var(--white);
            box-shadow: var(--shadow);
            transition: right 0.4s ease;
            z-index: 999;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .mobile-nav.active { right: 0; }
        .mobile-nav a {
            color: var(--text-dark);
            text-decoration: none;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(162, 119, 65, 0.1);
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
        }

        .mobile-nav a:hover { color: var(--primary-color); padding-left: 1.2rem; }
        .cart-section { max-width: 1200px; margin: 80px auto 2rem; padding: 0 2rem; animation: fadeInUp 0.8s ease-out; }
        .cart-item {
            background: var(--white);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            display: flex;
            gap: 1rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .cart-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
        .cart-details { flex: 1; }
        .tour-info { margin-bottom: 1rem; }
        .tour-icon { color: var(--primary-color); margin-right: 0.5rem; }
        .tour-description { font-style: italic; color: var(--text-light); margin-top: 0.5rem; }
        .quantities { display: flex; gap: 1rem; margin: 0.5rem 0; font-weight: 500; }
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
        }

        .remove-btn:hover { background: #c82333; }
        .contact-form, .passenger-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            box-shadow: var(--shadow);
            animation: fadeInUp 0.8s ease-out;
        }

        .passenger-form {
            border: 1px solid rgba(162, 119, 65, 0.2);
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            animation: fadeInUp 0.5s ease-out;
        }

        .form-group { margin-bottom: 1.5rem; position: relative; }
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid rgba(162, 119, 65, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus { border-color: var(--primary-color); outline: none; }
        .confirm-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .confirm-btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        .iti { width: 100%; }
        .iti__flag-container { padding: 0.5rem; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .footer {
            background: var(--primary-dark);
            color: var(--white);
            padding: 3.5rem 0 1.5rem;
            margin-top: 3rem;
        }

        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2.5rem;
            padding: 0 2.5rem;
        }

        .footer-section h3 { color: var(--primary-light); margin-bottom: 1.2rem; font-size: 1.3rem; }
        .footer-section ul { list-style: none; }
        .footer-section ul li { margin-bottom: 0.7rem; }
        .footer-section a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-section a:hover { color: var(--primary-light); padding-left: 0.5rem; }
        .social-links { display: flex; gap: 1.2rem; margin-top: 1.2rem; }
        .social-link {
            width: 44px;
            height: 44px;
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
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.25);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .cart-item { flex-direction: column; text-align: center; }
            .nav-links, .auth-buttons { display: none; }
            .mobile-menu { display: flex; }
            .cart-section { padding: 0 1rem; }
        }

        .price-info { font-weight: 600; color: var(--primary-color); margin-top: 0.5rem; }

        .custom-admin-btn2 {
        padding: 12px 24px;
        color: #A27741; 
        border: 1px solid #ffffff; 
        border-radius: 50px;
        text-decoration: none;
    }
    </style>
</head>
<body>
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="../index.php#inicio" class="logo">
                <img src="../imagenes/antares_logozz2.png" alt="Antares Travel Peru Logo" height="50" loading="lazy">
                ANTARES TRAVEL PERU
            </a>
            <ul class="nav-links">
                <li><a href="../index.php#inicio"><?php echo $lang['nav_home'] ?? 'Home'; ?></a></li>
                <li><a href="../index.php#tours"><?php echo $lang['nav_tours'] ?? 'Tours'; ?></a></li>
                <li><a href="../index.php#guias"><?php echo $lang['nav_guides'] ?? 'Guides'; ?></a></li>
                <li><a href="../index.php#experiencias"><?php echo $lang['nav_experiences'] ?? 'Experiences'; ?></a></li>
                <li><a href="../index.php#reservas"><?php echo $lang['nav_reservations'] ?? 'Reservations'; ?></a></li>
            </ul>
            <div class="auth-buttons">
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? '../imagenes/default-avatar.png'); ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="../index.php?logout=1" class="logout-btn" title="<?php echo $lang['logout_button'] ?? 'Log Out'; ?>">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <a href="reserva.php" class="btn btn-secondary" style="position: relative;">
                    <i class="fas fa-shopping-cart"></i>
                    <span style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px;"><?php echo $cart['total_paquetes']; ?></span>
                </a>
                <div class="lang-switch">
                    <a href="?lang=es" class="lang-btn <?php echo $current_lang == 'es' ? 'active' : ''; ?>"><?php echo $lang['lang_es'] ?? 'ES'; ?></a>
                    <a href="?lang=en" class="lang-btn <?php echo $current_lang == 'en' ? 'active' : ''; ?>"><?php echo $lang['lang_en'] ?? 'EN'; ?></a>
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
        <a href="../index.php#inicio"><?php echo $lang['nav_home'] ?? 'Home'; ?></a>
        <a href="../index.php#tours"><?php echo $lang['nav_tours'] ?? 'Tours'; ?></a>
        <a href="../index.php#guias"><?php echo $lang['nav_guides'] ?? 'Guides'; ?></a>
        <a href="../index.php#experiencias"><?php echo $lang['nav_experiences'] ?? 'Experiences'; ?></a>
        <a href="../index.php#reservas"><?php echo $lang['nav_reservations'] ?? 'Reservations'; ?></a>
        <div class="mobile-auth-buttons">
            <div class="user-profile">
                <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? '../imagenes/default-avatar.png'); ?>" alt="Avatar">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="../index.php?logout=1" class="logout-btn" title="<?php echo $lang['logout_button'] ?? 'Log Out'; ?>">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <a href="reserva.php" class="btn btn-secondary" style="position: relative;">
                <i class="fas fa-shopping-cart"></i>
                <span style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px;"><?php echo $cart['total_paquetes']; ?></span>
            </a>
            <div class="lang-switch">
                <a href="?lang=es" class="lang-btn <?php echo $current_lang == 'es' ? 'active' : ''; ?>"><?php echo $lang['lang_es'] ?? 'ES'; ?></a>
                <a href="?lang=en" class="lang-btn <?php echo $current_lang == 'en' ? 'active' : ''; ?>"><?php echo $lang['lang_en'] ?? 'EN'; ?></a>
            </div>
        </div>
    </div>

    <section class="cart-section">
        <h1 class="fade-in"><?php echo $lang['cart_title'] ?? 'Your Reservation Cart'; ?></h1>
        <form method="POST" action="reserva.php">
            <?php foreach ($cart['paquetes'] as $index => $item): 
                $detail = $tour_details[$item['id_tour']] ?? [];
                $hora_salida = $detail['hora_salida'] ? date('H:i', strtotime($detail['hora_salida'])) : 'TBD';
                $hora_llegada = $detail['hora_llegada'] ? date('H:i', strtotime($detail['hora_llegada'])) : 'TBD';
                $descripcion = $detail['descripcion'] ?? 'No description available.';
                $lugar_salida = $detail['lugar_salida'] ?? 'Cusco';
            ?>
                <div class="cart-item">
                    <img src="<?php echo getImagePath($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['titulo']); ?>">
                    <div class="cart-details">
                        <h3><?php echo htmlspecialchars($item['titulo']); ?></h3>
                        <div class="tour-info">
                            <i class="fas fa-map-marker-alt tour-icon"></i>
                            <span>Departure: <?php echo htmlspecialchars($lugar_salida); ?></span>
                        </div>
                        <div class="tour-info">
                            <i class="fas fa-clock tour-icon"></i>
                            <span>Time: <?php echo $hora_salida; ?> to <?php echo $hora_llegada; ?></span>
                        </div>
                        <div class="tour-description">
                            <i class="fas fa-info-circle tour-icon"></i>
                            <p><?php echo nl2br(htmlspecialchars(substr($descripcion, 0, 200)) . (strlen($descripcion) > 200 ? '...' : '')); ?></p>
                        </div>
                        <div class="quantities">
                            <span><i class="fas fa-user"></i> Adults: <?php echo $item['adultos']; ?> x $<?php echo number_format($detail['precio'] ?? 0, 2); ?></span>
                            <span><i class="fas fa-child"></i> Children: <?php echo $item['ninos']; ?> x $<?php echo number_format($detail['precio'] ?? 0, 2); ?></span>
                            <span><i class="fas fa-baby"></i> Infants: <?php echo $item['infantes']; ?> x $0.00</span>
                        </div>
                        <div class="price-info">
                            Total for this tour: $<?php echo number_format($detail['total_price'] ?? 0, 2); ?>
                        </div>
                    </div>
                    <form method="POST" action="reserva.php">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <input type="hidden" name="tour_id" value="<?php echo $item['id_tour']; ?>">
                        <button type="submit" name="remove_item" class="remove-btn" onclick="return confirm('<?php echo $lang['remove_confirm'] ?? 'Remove this tour from cart?'; ?>');">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
            <div class="price-info" style="text-align: right; font-size: 1.2rem;">
                Total Price: $<?php echo number_format($total_price, 2); ?>
            </div>
        </form>

        <div class="contact-form">
            <h2><i class="fas fa-user"></i> <?php echo $lang['contact_details'] ?? 'Contact Details (Main Contact)'; ?></h2>
            <form method="POST" action="boleta.php" id="reservaForm">
                <input type="hidden" name="total_tours" value="<?php echo count($cart['paquetes']); ?>">
                <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
                <div class="form-group">
                    <label class="form-label" for="contacto_nombre"><i class="fas fa-user"></i> Name:</label>
                    <input type="text" id="contacto_nombre" name="contacto[nombre]" class="form-input" value="<?php echo htmlspecialchars($contacto['nombre']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="contacto_email"><i class="fas fa-envelope"></i> Email:</label>
                    <input type="email" id="contacto_email" name="contacto[email]" class="form-input" value="<?php echo htmlspecialchars($contacto['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="contacto_telefono"><i class="fas fa-phone"></i> Phone:</label>
                    <input type="tel" id="contacto_telefono" name="contacto[telefono]" class="form-input" value="<?php echo htmlspecialchars($contacto['telefono']); ?>" required>
                </div>

                <?php foreach ($cart['paquetes'] as $tour_index => $item): 
                    $detail = $tour_details[$item['id_tour']] ?? [];
                ?>
                    <div class="passenger-section">
                        <h3><i class="fas fa-map-signs"></i> <?php echo htmlspecialchars($item['titulo']); ?> - Date: <?php echo date('d/m/Y', strtotime($item['fecha'])); ?></h3>
                        <p><i class="fas fa-clock"></i> Time: <?php echo $hora_salida; ?> to <?php echo $hora_llegada; ?></p>
                        <div class="price-info">Price per person: $<?php echo number_format($detail['precio'] ?? 0, 2); ?> (Infants free)</div>

                        <?php 
                        // Adults
                        for ($a = 0; $a < $item['adultos']; $a++): ?>
                            <div class="passenger-form">
                                <h4><i class="fas fa-user"></i> Adult <?php echo $a+1; ?></h4>
                                <input type="hidden" name="pasajeros[<?php echo $tour_index; ?>][adultos][<?php echo $a; ?>][tipo]" value="Adulto">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-user"></i> Name:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][adultos][<?php echo $a; ?>][nombre]" class="form-input" placeholder="Name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-user"></i> Last Name:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][adultos][<?php echo $a; ?>][apellido]" class="form-input" placeholder="Last Name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-globe"></i> Nationality:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][adultos][<?php echo $a; ?>][nacionalidad]" class="form-input" placeholder="Nationality" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-id-card"></i> DNI/Passport:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][adultos][<?php echo $a; ?>][dni_pasaporte]" class="form-input" placeholder="DNI or Passport Number" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-phone"></i> Phone:</label>
                                    <input type="tel" name="pasajeros[<?php echo $tour_index; ?>][adultos][<?php echo $a; ?>][telefono]" class="form-input" placeholder="Phone" required>
                                </div>
                            </div>
                        <?php endfor; 

                        // Children
                        for ($n = 0; $n < $item['ninos']; $n++): ?>
                            <div class="passenger-form">
                                <h4><i class="fas fa-child"></i> Child <?php echo $n+1; ?></h4>
                                <input type="hidden" name="pasajeros[<?php echo $tour_index; ?>][ninos][<?php echo $n; ?>][tipo]" value="Niño">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-user"></i> Name:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][ninos][<?php echo $n; ?>][nombre]" class="form-input" placeholder="Name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-user"></i> Last Name:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][ninos][<?php echo $n; ?>][apellido]" class="form-input" placeholder="Last Name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-globe"></i> Nationality:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][ninos][<?php echo $n; ?>][nacionalidad]" class="form-input" placeholder="Nationality" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-id-card"></i> DNI/Passport:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][ninos][<?php echo $n; ?>][dni_pasaporte]" class="form-input" placeholder="DNI or Passport Number" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-phone"></i> Phone:</label>
                                    <input type="tel" name="pasajeros[<?php echo $tour_index; ?>][ninos][<?php echo $n; ?>][telefono]" class="form-input" placeholder="Phone (optional)">
                                </div>
                            </div>
                        <?php endfor; 

                        // Infants
                        for ($i = 0; $i < $item['infantes']; $i++): ?>
                            <div class="passenger-form">
                                <h4><i class="fas fa-baby"></i> Infant <?php echo $i+1; ?></h4>
                                <input type="hidden" name="pasajeros[<?php echo $tour_index; ?>][infantes][<?php echo $i; ?>][tipo]" value="Infante">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-user"></i> Name:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][infantes][<?php echo $i; ?>][nombre]" class="form-input" placeholder="Name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-user"></i> Last Name:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][infantes][<?php echo $i; ?>][apellido]" class="form-input" placeholder="Last Name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-globe"></i> Nationality:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][infantes][<?php echo $i; ?>][nacionalidad]" class="form-input" placeholder="Nationality" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-id-card"></i> DNI/Passport:</label>
                                    <input type="text" name="pasajeros[<?php echo $tour_index; ?>][infantes][<?php echo $i; ?>][dni_pasaporte]" class="form-input" placeholder="DNI or Passport Number" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-phone"></i> Phone:</label>
                                    <input type="tel" name="pasajeros[<?php echo $tour_index; ?>][infantes][<?php echo $i; ?>][telefono]" class="form-input" placeholder="Phone (optional)">
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="confirm-btn" onclick="return confirm('<?php echo $lang['confirm_reservation'] ?? 'Confirm your reservation? Please ensure all details are correct as they cannot be changed later.'; ?>');">
                    <i class="fas fa-check-circle"></i> Confirm Reservation
                </button>
            </form>
        </div>
    </section>

    <footer class="footer">
    <div class="footer-content">
        <div class="footer-section"> 
            <h3><?php echo $lang['footer_about_title']; ?></h3>
            <p><?php echo $lang['footer_about_text']; ?></p>
            <div class="social-links">
                <a href="https://wa.me/51958940006" class="social-link" target="_blank"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="footer-section">
            <h3><?php echo $lang['footer_contact_title']; ?></h3>
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> <?php echo $lang['footer_contact_address']; ?></li>
                <li><i class="fas fa-phone"></i> +51 958 940 006</li>
                <li><i class="fas fa-envelope"></i> antarestravelperu@gmail.com </li>
                <li><i class="fas fa-globe"></i> www.antarestravelperu.com</li>
            </ul>
        </div>
        <div class="footer-section">
            <h3><?php echo $lang['footer_services_title']; ?></h3>
            <ul>
                <li><a href="../index.php#tours"><?php echo $lang['footer_service_cusco']; ?></a></li>
                <li><a href="../index.php#tours"><?php echo $lang['footer_service_sacred_valley']; ?></a></li>
                <li><a href="../index.php#tours"><?php echo $lang['footer_service_machu_picchu']; ?></a></li>
                <li><a href="../index.php#tours"><?php echo $lang['footer_service_adventure']; ?></a></li>
                <li><a href="../index.php#guias"><?php echo $lang['footer_service_guides']; ?></a></li>
                <li><a href="../index.php#tours"><?php echo $lang['footer_service_transport']; ?></a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3><?php echo $lang['footer_legal_title']; ?></h3>
            <ul>
                <li>RUC: 20XXXXXXXX</li>
                <li><?php echo $lang['footer_legal_license']; ?>: XXXX-XXXX</li>
                <li><a href="#"><?php echo $lang['footer_legal_terms']; ?></a></li>
                <li><a href="#"><?php echo $lang['footer_legal_privacy']; ?></a></li>
                <li><a href="#"><?php echo $lang['footer_legal_cancellation']; ?></a></li>
            </ul>
            <div class="admin-btn-wrapper" style="margin-top:20px; text-align:center;">
                <a href="src/admin" class="custom-admin-btn2">Panel Admin</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p><?php echo $lang['footer_copyright']; ?></p>
    </div>
</footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'fadeInUp 0.6s ease-out';
                        entry.target.style.opacity = '1';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.cart-item, .passenger-section, .passenger-form').forEach(el => {
                el.style.opacity = '0';
                observer.observe(el);
            });

            window.toggleMobileMenu = function() {
                const mobileNav = document.getElementById('mobileNav');
                const mobileMenu = document.querySelector('.mobile-menu');
                mobileNav.classList.toggle('active');
                mobileMenu.classList.toggle('active');
            };

            window.addEventListener('scroll', () => {
                const navbar = document.querySelector('.navbar');
                navbar.classList.toggle('scrolled', window.scrollY > 50);
            });
        });
    </script>
</body>
</html>