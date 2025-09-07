<?php
session_start();

if (isset($_GET['lang']) && ($_GET['lang'] == 'es' || $_GET['lang'] == 'en')) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
require_once __DIR__ . '/src/languages/' . $current_lang . '.php';

require_once __DIR__ . '/src/funtions/google_auth.php';

$client = getGoogleClient();

if (isset($_POST['credential'])) {
    if (procesarGoogleCredential($_POST['credential'], $conn, $client)) {
        header("Location: index.php");
        exit;
    } else {
        echo "❌ Token inválido";
        exit;
    }
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['page_title']; ?></title>
    <link rel="icon" type="image/png" href="imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
            text-decoration: none;
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

        .hero {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .hero-image.active {
            opacity: 1;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(139, 99, 50, 0.7), rgba(91, 121, 124, 0.7));
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        .hero-content {
            text-align: center;
            color: var(--white);
            max-width: 700px;
            margin: 0 auto;
            animation: fadeInUp 1s ease-out;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-indicators {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }

        .hero-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: var(--transition);
        }

        .hero-indicator.active {
            background: var(--white);
        }

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

        .tours-section {
            background: var(--primary-bg);
        }

        .tour-categories {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 40px;
            gap: 15px;
        }

        .category-btn {
            padding: 10px 20px;
            background: var(--white);
            border: 1px solid var(--primary-color);
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            color: var(--primary-color);
        }

        .category-btn.active, .category-btn:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .tours-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .tour-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            opacity: 1;
            transform: none;
        }

        .tour-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(162, 119, 65, 0.15);
        }

        .tour-header {
            padding: 15px;
            background: var(--primary-color);
            color: var(--white);
        }

        .tour-title {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .tour-schedule {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .tour-content {
            padding: 15px;
        }

        .tour-details {
            margin: 10px 0;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .tour-details div {
            margin-bottom: 5px;
        }

        .tour-details i {
            color: var(--primary-color);
            margin-right: 5px;
            width: 16px;
        }

        .tour-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .tour-image {
            height: 180px;
            background-size: cover;
            background-position: center;
        }

        .tour-actions {
            margin-top: 15px;
            text-align: center;
        }

        .guias-section {
            background: var(--white);
        }

        .guias-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .guias-filters select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--primary-light);
            border-radius: 25px;
            background: var(--white);
            cursor: pointer;
            font-size: 1rem;
        }

        .guias-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .guia-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .guia-card:hover {
            transform: translateY(-5px);
        }

        .guia-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 3px solid var(--primary-light);
            object-fit: cover;
        }

        .guia-name {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .guia-rating {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        .guia-rating .star {
            color: #ffd700;
        }

        .experiencias-section {
            background: var(--primary-bg);
        }

        .photos-mural {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }

        .photo-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .photo-item img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s ease;
        }

        .photo-item:hover img {
            transform: scale(1.05);
        }

        .photo-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            padding: 1rem;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .photo-info .small-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid var(--white);
        }

        .photo-info span {
            font-size: 0.9rem;
        }

        .carousel-container {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
        }

        .carousel {
            display: flex;
            transition: transform 0.5s ease;
        }

        .carousel-item {
            flex: 0 0 100%;
            position: relative;
        }

        .experiencia-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin: 0 10px;
        }

        .experiencia-image {
            height: 250px;
            background-size: cover;
            background-position: center;
        }

        .experiencia-content {
            padding: 15px;
        }

        .experiencia-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .experiencia-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary-light);
        }

        .experiencia-name {
            font-weight: 500;
            color: var(--primary-color);
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--primary-color);
        }

        .carousel-nav:hover {
            background: var(--white);
            box-shadow: var(--shadow);
        }

        .carousel-nav.prev {
            left: 10px;
        }

        .carousel-nav.next {
            right: 10px;
        }

        .add-experiencia {
            max-width: 600px;
            margin: 40px auto 0;
            text-align: center;
        }

        .add-experiencia form {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-top: 20px;
        }

        .add-experiencia textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid var(--primary-light);
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .add-experiencia input[type="file"] {
            margin-bottom: 10px;
        }

        .add-experiencia p {
            color: var(--text-light);
            margin-bottom: 10px;
        }

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

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

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

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .section-title {
                font-size: 2rem;
            }

            .tours-container {
                grid-template-columns: 1fr;
            }

            .guias-container {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .container {
                padding: 0 1rem;
            }

            .carousel-nav {
                width: 36px;
                height: 36px;
            }

            .google-signin-container {
                width: 90%;
                max-width: 300px;
                right: 50%;
                transform: translateX(50%);
            }

            .photos-mural {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 1.8rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .tour-card {
                margin: 0 10px;
            }

            .guias-filters {
                flex-direction: column;
            }
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php
    require_once 'src/config/conexion.php';
    
    $is_logged_in = isset($_SESSION['user_email']);
    
    $tours_query = "SELECT t.*, r.nombre_region, g.nombre as guia_nombre 
                    FROM tours t 
                    LEFT JOIN regiones r ON t.id_region = r.id_region 
                    LEFT JOIN guias g ON t.id_guia = g.id_guia 
                    ORDER BY t.id_tour";
    $tours_result = $conn->query($tours_query);
    if (!$tours_result) {
        die("Error executing query: " . $conn->error);
    }
    
    $guias_query = "SELECT g.*, 
                    (SELECT AVG(c.calificacion) FROM calificaciones_guias c WHERE c.id_guia = g.id_guia) as rating_promedio,
                    (SELECT COUNT(*) FROM calificaciones_guias c WHERE c.id_guia = g.id_guia) as total_calificaciones,
                    GROUP_CONCAT(gi.id_idioma SEPARATOR ',') as idiomas
                    FROM guias g 
                    LEFT JOIN guia_idiomas gi ON gi.id_guia = g.id_guia
                    GROUP BY g.id_guia
                    ORDER BY g.id_guia";
    $guias_result = mysqli_query($conn, $guias_query);
    if (!$guias_result) {
        die("Error executing guides query: " . mysqli_error($conn));
    }
    
    $idiomas_query = "SELECT * FROM idiomas ORDER BY nombre_idioma";
    $idiomas_result = $conn->query($idiomas_query);
    
    $experiencias_query = "SELECT e.*, u.nombre, u.avatar_url 
                          FROM experiencias e 
                          LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario 
                          ORDER BY e.fecha_publicacion DESC";
    $experiencias_result = $conn->query($experiencias_query);
    if (!$experiencias_result) {
        error_log("Error executing experiences query: " . $conn->error);
        $experiencias_result = false;
    }
    ?>

    <nav class="navbar">
        <div class="nav-container">
            <a href="#inicio" class="logo">
                <img src="imagenes/antares_logozz2.png" alt="Antares Travel Peru Logo" height="50" loading="lazy">
                ANTARES TRAVEL PERU
            </a>
            <ul class="nav-links">
                <li><a href="#inicio"><?php echo $lang['nav_home']; ?></a></li>
                <li><a href="#tours"><?php echo $lang['nav_tours']; ?></a></li>
                <li><a href="#guias"><?php echo $lang['nav_guides']; ?></a></li>
                <li><a href="#experiencias"><?php echo $lang['nav_experiences']; ?></a></li>
                <li><a href="#reservas"><?php echo $lang['nav_reservations']; ?></a></li>
            </ul>
            <div class="auth-buttons">
                <div class="lang-switch">
                    <a href="?lang=es" class="lang-btn <?php if ($current_lang == 'es') echo 'active'; ?>"><?php echo $lang['lang_es']; ?></a>
                    <a href="?lang=en" class="lang-btn <?php if ($current_lang == 'en') echo 'active'; ?>"><?php echo $lang['lang_en']; ?></a>
                </div>
                <?php if (!$is_logged_in): ?>
                    <a href="src/auth/login.php" class="btn btn-secondary">
                        <i class="fas fa-user"></i> <?php echo $lang['login_button']; ?>
                    </a>

                <?php else: ?>
                    <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? 'imagenes/default-avatar.png'); ?>" alt="Avatar">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <a href="index.php?logout=1" class="logout-btn" title="<?php echo $lang['logout_button']; ?>">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_email'])): ?>
                    <?php 
                    $cart_count = isset($_SESSION['cart']['total_paquetes']) ? $_SESSION['cart']['total_paquetes'] : 0;
                    ?>
                    <a href="carrito.php" id="cart-icon" class="btn btn-secondary" style="position: relative;">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-count" style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px;"><?php echo $cart_count; ?></span>
                    </a>
                <?php endif; ?>
            </div>
            <div class="mobile-menu" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <div class="mobile-nav" id="mobileNav">
        <a href="#inicio"><?php echo $lang['nav_home']; ?></a>
        <a href="#tours"><?php echo $lang['nav_tours']; ?></a>
        <a href="#guias"><?php echo $lang['nav_guides']; ?></a>
        <a href="#experiencias"><?php echo $lang['nav_experiences']; ?></a>
        <a href="#reservas"><?php echo $lang['nav_reservations']; ?></a>
        <div class="mobile-auth-buttons">
            <div class="lang-switch">
                <a href="?lang=es" class="lang-btn <?php if ($current_lang == 'es') echo 'active'; ?>"><?php echo $lang['lang_es']; ?></a>
                <a href="?lang=en" class="lang-btn <?php if ($current_lang == 'en') echo 'active'; ?>"><?php echo $lang['lang_en']; ?></a>
            </div>
            <?php if (!$is_logged_in): ?>
                <button class="btn btn-primary" onclick="toggleGoogleSignin()"><?php echo $lang['login_with_google']; ?></button>
                <a href="src/auth/login.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> <?php echo $lang['login_button']; ?>
                </a>
                <a href="src/auth/register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> <?php echo $lang['register_button']; ?>
                </a>
            <?php else: ?>
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? 'imagenes/default-avatar.png'); ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="index.php?logout=1" class="logout-btn" title="<?php echo $lang['logout_button']; ?>">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="google-signin-container" id="googleSignin">
        <button class="close-btn" onclick="toggleGoogleSignin()">×</button>
        <div id="g_id_signin"
             data-type="standard"
             data-size="large"
             data-theme="outline"
             data-text="sign_in_with"
             data-shape="rectangular"
             data-logo_alignment="left">
        </div>
    </div>

    <section id="inicio" class="hero">
        <div class="hero-image active" style="background-image: url('imagenes/fondo01.jpg')"></div>
        <div class="hero-image" style="background-image: url('imagenes/fondo02.jpg')"></div>
        <div class="hero-image" style="background-image: url('imagenes/fondo03.jpg')"></div>
        <div class="hero-image" style="background-image: url('imagenes/fondo04.jpg')"></div>
        <div class="hero-image" style="background-image: url('imagenes/fondo05.jpg')"></div>
        <div class="hero-bg"></div>
        <div class="container">
            <div class="hero-content">
                <h1><?php echo $lang['hero_title']; ?></h1>
                <p><?php echo $lang['hero_subtitle']; ?></p>
                <div class="tour-actions">
                        <?php if (isset($_SESSION['user_email'])): ?>
                            <button class="btn btn-primary add-to-cart" data-id="<?php echo $tour['id_tour']; ?>" data-titulo="<?php echo htmlspecialchars($tour['titulo']); ?>" data-precio="<?php echo $tour['precio']; ?>">
                                <i class="fas fa-shopping-cart"></i> Agregar al Carrito
                            </button>
                        <?php else: ?>
                            <a href="src/auth/login.php" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> Inicia Sesión para Reservar
                            </a>
                        <?php endif; ?>
                    </div>
            </div>
        </div>
        <div class="hero-indicators">
            <div class="hero-indicator active" onclick="changeHeroImage(0)"></div>
            <div class="hero-indicator" onclick="changeHeroImage(1)"></div>
            <div class="hero-indicator" onclick="changeHeroImage(2)"></div>
            <div class="hero-indicator" onclick="changeHeroImage(3)"></div>
            <div class="hero-indicator" onclick="changeHeroImage(4)"></div>
        </div>
    </section>

    <section id="tours" class="section tours-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title"><?php echo $lang['tours_section_title']; ?></h2>
                <p class="section-subtitle"><?php echo $lang['tours_section_subtitle']; ?></p>
            </div>

            <div class="tour-categories">
                <button class="category-btn active" onclick="filterTours('all')"><?php echo $lang['tours_cat_all']; ?></button>
                <button class="category-btn" onclick="filterTours('cusco')"><?php echo $lang['tours_cat_cusco']; ?></button>
                <button class="category-btn" onclick="filterTours('aventura')"><?php echo $lang['tours_cat_adventure']; ?></button>
                <button class="category-btn" onclick="filterTours('multi-day')"><?php echo $lang['tours_cat_multiday']; ?></button>
            </div>

            <div class="tours-container" id="toursContainer">
                <?php if ($tours_result && $tours_result->num_rows > 0): ?>
                    <?php while ($tour = $tours_result->fetch_assoc()): ?>
                        <?php
                        $categoria = 'cusco';
                        $titulo_lower = strtolower($tour['titulo']);
                        if (strpos($titulo_lower, 'aventura') !== false || 
                            strpos($titulo_lower, 'cuatrimoto') !== false || 
                            strpos($titulo_lower, 'zipline') !== false ||
                            strpos($titulo_lower, 'montana') !== false ||
                            strpos($titulo_lower, 'laguna') !== false) {
                            $categoria = 'aventura';
                        } elseif (strpos($titulo_lower, 'dias') !== false || 
                                 strpos($titulo_lower, 'noche') !== false ||
                                 strpos($titulo_lower, 'inka jungle') !== false ||
                                 strpos($titulo_lower, 'salkantay') !== false) {
                            $categoria = 'multi-day';
                        }
                        
                        $imagen_url = 'https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=600&h=400&fit=crop';
                        if ($categoria == 'aventura') {
                            $imagen_url = 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&h=400&fit=crop';
                        } elseif ($categoria == 'multi-day') {
                            $imagen_url = 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&h=400&fit=crop';
                        }
                        ?>
                        <div class="tour-card" data-category="<?php echo $categoria; ?>">
                            <div class="tour-image" style="background-image: url('<?php echo $tour['imagen_principal'] ?: $imagen_url; ?>')"></div>
                            <div class="tour-header">
                                <h3 class="tour-title"><?php echo htmlspecialchars($tour['titulo']); ?></h3>
                                <div class="tour-schedule"><?php echo htmlspecialchars($tour['duracion'] ?: $lang['tour_card_duration']); ?></div>
                                <?php if ($tour['guia_nombre']): ?>
                                    <div class="tour-guide"><?php echo $lang['tour_card_guide']; ?>: <?php echo htmlspecialchars($tour['guia_nombre']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="tour-content">
                                <?php if ($tour['descripcion']): ?>
                                    <div class="tour-description">
                                        <p><?php echo htmlspecialchars(substr($tour['descripcion'], 0, 100)) . '...'; ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="tour-details">
                                    <?php if ($tour['lugar_salida']): ?>
                                        <div><i class="fas fa-map-marker-alt"></i> <?php echo $lang['tour_card_departure']; ?>: <?php echo htmlspecialchars($tour['lugar_salida']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($tour['hora_salida']): ?>
                                        <div><i class="fas fa-clock"></i> <?php echo $lang['tour_card_time']; ?>: <?php echo date('H:i', strtotime($tour['hora_salida'])); ?></div>
                                    <?php endif; ?>
                                </div>

                                
                                <div class="tour-actions">
                                    <a href="src/reserva.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus"></i> <?php echo $lang['tour_card_book']; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-tours">
                        <p><?php echo $lang['no_tours_available']; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="guias" class="section guias-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title"><?php echo $lang['guides_section_title']; ?></h2>
                <p class="section-subtitle"><?php echo $lang['guides_section_subtitle']; ?></p>
            </div>

            <div class="guias-filters">
                <select id="sortRating" onchange="filterGuias()">
                    <option value=""><?php echo $lang['guides_filter_sort']; ?></option>
                    <option value="asc"><?php echo $lang['guides_filter_sort_asc']; ?></option>
                    <option value="desc"><?php echo $lang['guides_filter_sort_desc']; ?></option>
                </select>
                <select id="filterStatus" onchange="filterGuias()">
                    <option value=""><?php echo $lang['guides_filter_status']; ?></option>
                    <option value="libre"><?php echo $lang['guides_filter_status_free']; ?></option>
                    <option value="ocupado"><?php echo $lang['guides_filter_status_busy']; ?></option>
                </select>
                <select id="filterLanguage" onchange="filterGuias()">
                    <option value=""><?php echo $lang['guides_filter_language']; ?></option>
                    <?php if ($idiomas_result): ?>
                        <?php while($idioma = $idiomas_result->fetch_assoc()): ?>
                            <option value="<?php echo $idioma['id_idioma']; ?>"><?php echo htmlspecialchars($idioma['nombre_idioma']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="guias-container" id="guiasContainer">
                <?php if ($guias_result && $guias_result->num_rows > 0): ?>
                    <?php while ($guia = $guias_result->fetch_assoc()): ?>
                        <?php $rating = floatval($guia['rating_promedio'] ?: 0); ?>
                        <div class="guia-card" data-rating="<?php echo $rating; ?>" data-estado="<?php echo strtolower($guia['estado']); ?>" data-idiomas="<?php echo $guia['idiomas'] ?: ''; ?>">
                            <img src="<?php echo htmlspecialchars($guia['foto_url'] ?: 'imagenes/default-guide.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($guia['nombre']); ?>" 
                                 class="guia-avatar">
                            <h3 class="guia-name">
                                <?php echo htmlspecialchars($guia['nombre'] . ' ' . ($guia['apellido'] ?: '')); ?>
                            </h3>
                            
                            <div class="guia-rating">
                                <?php 
                                $total_reviews = intval($guia['total_calificaciones'] ?: 0);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="fas fa-star <?php echo $i <= $rating ? 'star' : ''; ?>"></i>
                                <?php endfor; ?>
                                <span>(<?php echo $total_reviews; ?> <?php echo $lang['guide_card_reviews']; ?>)</span>
                            </div>
                            
                            <?php if ($guia['experiencia']): ?>
                                <p class="guia-experience"><?php echo htmlspecialchars(substr($guia['experiencia'], 0, 80)) . '...'; ?></p>
                            <?php endif; ?>
                            
                            <div class="guia-contact">
                                <?php if ($guia['telefono']): ?>
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($guia['telefono']); ?></div>
                                <?php endif; ?>
                                <?php if ($guia['email']): ?>
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($guia['email']); ?></div>
                                <?php endif; ?>
                                <div class="guia-status <?php echo strtolower($guia['estado']); ?>">
                                    <i class="fas fa-circle"></i> <?php echo $guia['estado']; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-guides">
                        <p><?php echo $lang['no_guides_available']; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="experiencias" class="section experiencias-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title"><?php echo $lang['experiences_section_title']; ?></h2>
                <p class="section-subtitle"><?php echo $lang['experiences_section_subtitle']; ?></p>
            </div>

            <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 20px;"><?php echo $lang['experiences_gallery_title']; ?></h3>
            <div class="photos-mural">
                <?php 
                if ($experiencias_result) $experiencias_result->data_seek(0);
                $photos = [];
                if($experiencias_result) {
                    while ($experiencia = $experiencias_result->fetch_assoc()): 
                        if ($experiencia['imagen_url']):
                            $photos[] = $experiencia;
                        endif;
                    endwhile;
                }
                ?>
                
                <?php if (!empty($photos)): ?>
                    <?php foreach ($photos as $photo): ?>
                        <div class="photo-item">
                            <img src="<?php echo htmlspecialchars($photo['imagen_url']); ?>" alt="Foto de experiencia">
                            <div class="photo-info">
                                <img src="<?php echo htmlspecialchars($photo['avatar_url'] ?: 'imagenes/default-avatar.png'); ?>" 
                                    alt="Usuario" class="small-avatar">
                                <span><?php echo htmlspecialchars($photo['nombre'] ?: $lang['experiences_anonymous']); ?></span>
                                <span><?php echo date('d/m/Y', strtotime($photo['fecha_publicacion'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-light);"><?php echo $lang['experiences_photos_soon']; ?></p>
                <?php endif; ?>
            </div>

            <div class="carousel-container">
                <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 20px;"><?php echo $lang['experiences_comments_title']; ?></h3>
                <div class="carousel" id="commentsCarousel">
                    <?php 
                    if ($experiencias_result) $experiencias_result->data_seek(0);
                    $comments = [];
                    if($experiencias_result) {
                        while ($experiencia = $experiencias_result->fetch_assoc()): 
                            if ($experiencia['comentario']):
                                $comments[] = $experiencia;
                            endif;
                        endwhile;
                    }
                    ?>
                    
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $index => $comment): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="experiencia-card">
                                    <div class="experiencia-content" style="padding: 30px;">
                                        <div class="experiencia-user" style="margin-bottom: 15px;">
                                            <img src="<?php echo htmlspecialchars($comment['avatar_url'] ?: 'imagenes/default-avatar.png'); ?>" 
                                                 alt="Usuario" class="experiencia-avatar">
                                            <div>
                                                <div class="experiencia-name"><?php echo htmlspecialchars($comment['nombre'] ?: $lang['experiences_anonymous_user']); ?></div>
                                                <div class="experiencia-date"><?php echo date('d/m/Y', strtotime($comment['fecha_publicacion'])); ?></div>
                                            </div>
                                        </div>
                                        <blockquote style="font-style: italic; font-size: 1rem; color: var(--text-light); border-left: 3px solid var(--primary-light); padding-left: 15px; margin: 0;">
                                            "<?php echo htmlspecialchars($comment['comentario']); ?>"
                                        </blockquote>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="carousel-item active">
                            <div class="experiencia-card">
                                <div class="experiencia-content" style="padding: 30px; text-align: center;">
                                    <p style="color: var(--text-light); font-size: 1rem;">
                                        <?php echo $lang['experiences_comments_soon']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($comments) > 1): ?>
                    <button class="carousel-nav prev" onclick="moveCarousel('commentsCarousel', -1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-nav next" onclick="moveCarousel('commentsCarousel', 1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                <?php endif; ?>
            </div>

            <div class="add-experiencia">
                <button onclick="toggleExperienciaForm()" class="btn btn-primary"><?php echo $lang['add_experience_button']; ?></button>
                <div id="experienciaFormContainer" style="display: none;">
                    <?php if (!$is_logged_in): ?>
                        <p><?php echo $lang['add_experience_login_prompt']; ?></p>
                        <a href="src/auth/login.php" class="btn btn-secondary"><?php echo $lang['login_button']; ?></a>
                    <?php else: ?>
                        <form action="src/add_experiencia.php" method="POST" enctype="multipart/form-data">
                            <textarea name="comentario" placeholder="<?php echo $lang['add_experience_placeholder']; ?>" required></textarea>
                            <input type="file" name="foto" accept="image/*">
                            <button type="submit" class="btn btn-primary"><?php echo $lang['add_experience_publish']; ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer" id="contacto">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section"> 
                    <h3><?php echo $lang['footer_about_title']; ?></h3>
                    <p><?php echo $lang['footer_about_text']; ?></p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-tripadvisor"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3><?php echo $lang['footer_contact_title']; ?></h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo $lang['footer_contact_address']; ?></li>
                        <li><i class="fas fa-phone"></i> +51 966 217 821</li>
                        <li><i class="fas fa-phone"></i> +51 958 940 100</li>
                        <li><i class="fas fa-envelope"></i> antares.travel.cusco@gmail.com</li>
                        <li><i class="fas fa-globe"></i> www.antarestravelcusco.com</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3><?php echo $lang['footer_services_title']; ?></h3>
                    <ul>
                        <li><a href="#tours"><?php echo $lang['footer_service_cusco']; ?></a></li>
                        <li><a href="#tours"><?php echo $lang['footer_service_sacred_valley']; ?></a></li>
                        <li><a href="#tours"><?php echo $lang['footer_service_machu_picchu']; ?></a></li>
                        <li><a href="#tours"><?php echo $lang['footer_service_adventure']; ?></a></li>
                        <li><a href="#guias"><?php echo $lang['footer_service_guides']; ?></a></li>
                        <li><a href="#tours"><?php echo $lang['footer_service_transport']; ?></a></li>
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
                </div>
            </div>
            
            <div class="footer-bottom">
                <p><?php echo $lang['footer_copyright']; ?></p>
            </div>
        </div>
    </footer>

    <script>
        let currentHeroImage = 0;
        let currentCommentIndex = 0;
        const heroImages = document.querySelectorAll('.hero-image');
        const heroIndicators = document.querySelectorAll('.hero-indicator');

        document.addEventListener('DOMContentLoaded', function() {
            initializeHeroCarousel();
            initializeScrollAnimations();
            initializeNavbar();
            initializeGoogleSignin();
            filterGuias();
        });

        function initializeHeroCarousel() {
            setInterval(() => {
                currentHeroImage = (currentHeroImage + 1) % heroImages.length;
                changeHeroImage(currentHeroImage);
            }, 5000);
        }

        function changeHeroImage(index) {
            heroImages.forEach(img => img.classList.remove('active'));
            heroIndicators.forEach(indicator => indicator.classList.remove('active'));
            
            heroImages[index].classList.add('active');
            heroIndicators[index].classList.add('active');
            currentHeroImage = index;
        }

        function toggleMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const mobileMenu = document.querySelector('.mobile-menu');
            const googleSignin = document.getElementById('googleSignin');
            
            mobileNav.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            
            const spans = mobileMenu.querySelectorAll('span');
            if (mobileNav.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
                googleSignin.classList.remove('active');
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        }

        function toggleGoogleSignin() {
            const googleSignin = document.getElementById('googleSignin');
            const mobileNav = document.getElementById('mobileNav');
            
            googleSignin.classList.toggle('active');
            if (googleSignin.classList.contains('active')) {
                mobileNav.classList.remove('active');
                document.querySelector('.mobile-menu').classList.remove('active');
                const spans = document.querySelectorAll('.mobile-menu span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
                
                if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
                    setTimeout(() => {
                        try {
                            google.accounts.id.renderButton(
                                document.getElementById("g_id_signin"),
                                {
                                    type: "standard",
                                    size: "large", 
                                    theme: "outline",
                                    text: "sign_in_with",
                                    shape: "rectangular",
                                    logo_alignment: "left"
                                }
                            );
                        } catch (error) {
                            console.log('🔄 Re-rendering Google button...');
                        }
                    }, 100);
                }
            }
        }

        function initializeGoogleSignin() {
            if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
                try {
                    google.accounts.id.initialize({
                        client_id: '<?php echo htmlspecialchars($client->getClientId()); ?>',
                        callback: handleCredentialResponse,
                        auto_select: false,
                        cancel_on_tap_outside: false
                    });

                    google.accounts.id.renderButton(
                        document.getElementById("g_id_signin"),
                        {
                            type: "standard",
                            size: "large",
                            theme: "outline",
                            text: "sign_in_with",
                            shape: "rectangular",
                            logo_alignment: "left"
                        }
                    );

                    console.log('✅ Google One Tap initialized successfully');
                } catch (error) {
                    console.error('❌ Error initializing Google One Tap:', error);
                }
            } else {
                console.log('⏳ Waiting for Google Identity Services to load...');
                setTimeout(initializeGoogleSignin, 100);
            }
        }

        function handleCredentialResponse(response) {
            try {
                console.log('📨 Credential received:', response);
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'credential';
                input.value = response.credential;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            } catch (error) {
                console.error('❌ Error processing credential:', error);
            }
        }

        function filterTours(category) {
            const tourCards = document.querySelectorAll('.tour-card');
            const categoryButtons = document.querySelectorAll('.category-btn');
            
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            tourCards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function filterGuias() {
            const sortRating = document.getElementById('sortRating').value;
            const filterStatus = document.getElementById('filterStatus').value;
            const filterLanguage = document.getElementById('filterLanguage').value;
            const container = document.getElementById('guiasContainer');
            let cards = Array.from(document.querySelectorAll('.guia-card'));

            if (filterStatus) {
                cards = cards.filter(card => card.dataset.estado === filterStatus);
            }

            if (filterLanguage) {
                cards = cards.filter(card => card.dataset.idiomas.split(',').includes(filterLanguage));
            }

            if (sortRating) {
                cards.sort((a, b) => {
                    const ratingA = parseFloat(a.dataset.rating);
                    const ratingB = parseFloat(b.dataset.rating);
                    return sortRating === 'asc' ? ratingA - ratingB : ratingB - ratingA;
                });
            }

            container.innerHTML = '';
            cards.forEach(card => container.appendChild(card));
        }

        function moveCarousel(carouselId, direction) {
            const carousel = document.getElementById(carouselId);
            const items = carousel.querySelectorAll('.carousel-item');
            const totalItems = items.length;
            
            if (totalItems === 0) return;
            
            currentCommentIndex = (currentCommentIndex + direction + totalItems) % totalItems;
            
            items.forEach(item => item.classList.remove('active'));
            items[currentCommentIndex].classList.add('active');
            
            const translateX = -currentCommentIndex * 100;
            carousel.style.transform = `translateX(${translateX}%)`;
        }

        setInterval(() => {
            const commentsCarousel = document.getElementById('commentsCarousel');
            
            if (commentsCarousel && commentsCarousel.querySelectorAll('.carousel-item').length > 1) {
                moveCarousel('commentsCarousel', 1);
            }
        }, 8000);

        function toggleExperienciaForm() {
            const formContainer = document.getElementById('experienciaFormContainer');
            formContainer.style.display = formContainer.style.display === 'none' ? 'block' : 'none';
        }

        function initializeScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'fadeInUp 0.6s ease-out';
                        entry.target.style.opacity = '1';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.tour-card, .guia-card, .section-header').forEach(el => {
                observer.observe(el);
            });
        }

        function initializeNavbar() {
            window.addEventListener('scroll', () => {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 100) {
                    navbar.style.background = 'rgba(255, 250, 240, 0.98)';
                    navbar.style.boxShadow = '0 2px 20px rgba(162, 119, 65, 0.1)';
                } else {
                    navbar.style.background = 'rgba(255, 250, 240, 0.95)';
                    navbar.style.boxShadow = 'none';
                }
            });
        }

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const headerOffset = 80;
                    const elementPosition = target.offsetTop;
                    const offsetPosition = elementPosition - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
                
                const mobileNav = document.getElementById('mobileNav');
                if (mobileNav.classList.contains('active')) {
                    toggleMobileMenu();
                }
            });
        });

        document.addEventListener('click', function(event) {
            const mobileNav = document.getElementById('mobileNav');
            const mobileMenu = document.querySelector('.mobile-menu');
            const googleSignin = document.getElementById('googleSignin');
            
            if (mobileNav.classList.contains('active') && 
                !mobileNav.contains(event.target) && 
                !mobileMenu.contains(event.target)) {
                toggleMobileMenu();
            }
            
            if (googleSignin.classList.contains('active') && 
                !googleSignin.contains(event.target) && 
                !event.target.closest('.btn-primary')) {
                googleSignin.classList.remove('active');
            }
        });

        // Manejo del carrito con AJAX
document.addEventListener('DOMContentLoaded', function() {
    const addButtons = document.querySelectorAll('.add-to-cart');
    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (!<?php echo isset($_SESSION['user_email']) ? 'true' : 'false'; ?>) {
                alert('Debes iniciar sesión para agregar al carrito.');
                window.location.href = 'src/auth/login.php';
                return;
            }

            const idTour = this.dataset.id;
            const titulo = this.dataset.titulo;
            const precio = parseFloat(this.dataset.precio);

            // AJAX para agregar
            fetch('agregar_carrito.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_tour=${idTour}&titulo=${encodeURIComponent(titulo)}&precio=${precio}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar contador
                    document.getElementById('cart-count').textContent = data.total_paquetes;
                    // Notificación
                    alert('Tour agregado al carrito!');
                    // Opcional: Animación en botón
                    this.style.background = 'green';
                    setTimeout(() => { this.style.background = ''; }, 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al agregar al carrito.');
            });
        });
    });
});
    </script>
</body>
</html>