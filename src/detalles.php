<?php
session_start();

if (isset($_GET['lang']) && ($_GET['lang'] == 'es' || $_GET['lang'] == 'en')) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
require_once __DIR__ . '/languages/' . $current_lang . '.php';

require_once __DIR__ . '/config/conexion.php';

$id_tour = isset($_GET['id_tour']) ? intval($_GET['id_tour']) : 0;
$tour = null;

if ($id_tour > 0) {
    $query = "SELECT t.*, r.nombre_region, g.nombre as guia_nombre, g.foto_url as guia_foto 
              FROM tours t 
              LEFT JOIN regiones r ON t.id_region = r.id_region 
              LEFT JOIN guias g ON t.id_guia = g.id_guia 
              WHERE t.id_tour = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_tour);
    $stmt->execute();
    $result = $stmt->get_result();
    $tour = $result->fetch_assoc();
}

if (!$tour) {
    header("Location: ../index.php");
    exit;
}

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
    
    if (file_exists($fullPath)) {
        return htmlspecialchars($fullPath, ENT_QUOTES, 'UTF-8');
    }
    
    return $fallbackImage;
}

$is_logged_in = isset($_SESSION['user_email']);
$cart_count = isset($_SESSION['cart']['total_paquetes']) ? $_SESSION['cart']['total_paquetes'] : 0;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = ['paquetes' => [], 'total_paquetes' => 0];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tour['titulo']) . ' - ' . $lang['page_title']; ?></title>
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
            --error-color: #d32f2f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .navbar.scrolled {
            background: rgba(255, 250, 240, 0.98);
            box-shadow: var(--shadow);
        }

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

        .logo img {
            vertical-align: middle;
            transition: transform 0.3s ease;
        }

        .logo:hover img {
            transform: scale(1.1);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2.5rem;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

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

        .nav-links a:hover::after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

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

        .lang-btn.active {
            background: var(--primary-color);
            color: var(--white);
        }

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

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
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
            gap: 0.8rem;
        }

        .user-profile img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            object-fit: cover;
            transition: var(--transition);
        }

        .user-profile img:hover {
            transform: scale(1.15);
        }

        .user-profile span {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .logout-btn {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .logout-btn:hover {
            color: var(--primary-dark);
        }

        .mobile-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 5px;
            z-index: 1001;
        }

        .mobile-menu span {
            width: 28px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
            transition: var(--transition);
        }

        .mobile-menu.active span:first-child {
            transform: rotate(45deg) translate(6px, 6px);
        }

        .mobile-menu.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu.active span:last-child {
            transform: rotate(-45deg) translate(8px, -8px);
        }

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

        .mobile-nav.active {
            right: 0;
        }

        .mobile-nav a {
            color: var(--text-dark);
            text-decoration: none;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(162, 119, 65, 0.1);
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
        }

        .mobile-nav a:hover {
            color: var(--primary-color);
            padding-left: 1.2rem;
        }

        .detail-image {
            position: relative;
            height: 65vh;
            min-height: 450px;
            margin-top: 80px;
            overflow: hidden;
            display: flex;
            align-items: flex-end;
            background: linear-gradient(45deg, var(--primary-dark), var(--primary-color));
        }

        .detail-image-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: transform 0.5s ease;
            animation: parallaxFloat 25s ease-in-out infinite;
        }

        .detail-image:hover .detail-image-bg {
            transform: scale(1.05);
        }

        .detail-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                rgba(139, 99, 50, 0.65) 0%,
                rgba(162, 119, 65, 0.45) 50%,
                rgba(91, 121, 124, 0.65) 100%
            );
            z-index: 1;
        }

        .image-fallback {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: var(--white);
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            z-index: 2;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-fallback.active {
            opacity: 1;
        }

        .detail-header {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 2.5rem;
            color: var(--white);
        }

        .detail-header h1 {
            font-size: clamp(2.2rem, 5.5vw, 4rem);
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 3px 3px 10px rgba(0,0,0,0.6);
            animation: fadeInUp 1s ease-out;
        }

        .detail-section {
            max-width: 1280px;
            margin: 0 auto;
            padding: 3.5rem 2.5rem;
        }

        .detail-content {
            background: var(--white);
            border-radius: 25px;
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: fadeInUp 0.9s ease-out;
        }

        .detail-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0;
        }

        .info-card {
            background: var(--white);
            padding: 2.5rem;
            text-align: center;
            transition: var(--transition);
            position: relative;
            border: 1px solid rgba(162, 119, 65, 0.1);
            animation: fadeInUp 0.7s ease-out;
        }

        .info-card:hover {
            background: var(--primary-bg);
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .info-card:nth-child(even) {
            background: rgba(162, 119, 65, 0.05);
        }

        .info-card i {
            color: var(--primary-color);
            font-size: 2.2rem;
            margin-bottom: 1.2rem;
            transition: var(--transition);
        }

        .info-card:hover i {
            transform: scale(1.15);
            color: var(--primary-dark);
        }

        .info-card h3 {
            margin: 1rem 0 0.6rem;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .info-card p {
            margin: 0;
            color: var(--text-light);
            font-size: 1rem;
        }

        .detail-description {
            padding: 3.5rem;
            background: var(--white);
        }

        .detail-description h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1.2rem;
            position: relative;
            display: inline-block;
        }

        .detail-description h2::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary-light);
            transition: width 0.3s ease;
        }

        .detail-description:hover h2::after {
            width: 100px;
        }

        .detail-description p {
            line-height: 1.9;
            color: var(--text-dark);
            margin-bottom: 1.8rem;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.2rem;
            margin-top: 2rem;
        }

        .schedule-item {
            background: var(--primary-bg);
            padding: 1.2rem;
            border-radius: 12px;
            border-left: 5px solid var(--primary-color);
            transition: var(--transition);
        }

        .schedule-item:hover {
            transform: translateX(8px);
            box-shadow: var(--shadow);
        }

        .schedule-item i {
            color: var(--primary-color);
            margin-right: 0.6rem;
        }

        .schedule-item strong {
            font-weight: 600;
            color: var(--text-dark);
        }

        .reserve-section {
            padding: 2.5rem 3.5rem;
            text-align: center;
            background: var(--primary-bg);
        }

        .reserve-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-label i {
            margin-right: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid rgba(162, 119, 65, 0.2);
            border-radius: 8px;
            font-size: 1rem;
        }

        .counter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            justify-content: center;
        }

        .counter-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .counter-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
        }

        .counter-value {
            font-size: 1.2rem;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }

        .submit-btn {
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

        .submit-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .reserve-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 1.2rem 3.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: var(--white);
            border: none;
            border-radius: 50px;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .reserve-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s ease;
        }

        .reserve-btn:hover::before {
            left: 100%;
        }

        .reserve-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-4px) scale(1.05);
            box-shadow: var(--shadow-hover);
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

        .footer-section h3 {
            color: var(--primary-light);
            margin-bottom: 1.2rem;
            font-size: 1.3rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.7rem;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-section a:hover {
            color: var(--primary-light);
            padding-left: 0.5rem;
        }

        .social-links {
            display: flex;
            gap: 1.2rem;
            margin-top: 1.2rem;
        }

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

        .error-message {
            color: var(--error-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
            text-align: left;
        }

        .error-message.active {
            display: block;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes parallaxFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        @media (max-width: 1024px) {
            .nav-container {
                padding: 0 1.5rem;
            }

            .detail-section {
                padding: 2.5rem 1.5rem;
            }

            .detail-image {
                height: 55vh;
                min-height: 380px;
            }
        }

        @media (max-width: 768px) {
            .nav-links, .auth-buttons {
                display: none;
            }

            .mobile-menu {
                display: flex;
            }

            .detail-image {
                height: 50vh;
                min-height: 320px;
                margin-top: 70px;
            }

            .detail-image-bg {
                background-attachment: scroll;
            }

            .detail-header {
                padding: 1.5rem;
            }

            .detail-header h1 {
                font-size: clamp(1.8rem, 4.5vw, 3rem);
            }

            .detail-section {
                padding: 2rem 1rem;
            }

            .detail-info {
                grid-template-columns: 1fr;
            }

            .detail-description, .reserve-section {
                padding: 2rem 1.2rem;
            }

            .schedule-grid {
                grid-template-columns: 1fr;
            }

            .reserve-btn {
                padding: 1rem 2.5rem;
                font-size: 1rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 0 1rem;
            }

            .social-links {
                justify-content: center;
            }

            .counter-group {
                gap: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .detail-header h1 {
                font-size: clamp(1.6rem, 4vw, 2.2rem);
            }

            .info-card {
                padding: 1.8rem;
            }

            .info-card i {
                font-size: 1.8rem;
            }

            .detail-description h2 {
                font-size: 1.6rem;
            }

            .detail-description {
                padding: 1.8rem 1rem;
            }

            .reserve-btn {
                padding: 0.9rem 2rem;
                font-size: 0.95rem;
            }
        }

        .loading {
            display: inline-block;
            width: 22px;
            height: 22px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .custom-admin-btn2 {
            padding: 12px 24px;
            color: #A27741; 
            border: 1px solid #ffffff; 
            border-radius: 50px;
            text-decoration: none;
        }

        .total-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-top: 1rem;
        }

        .custom-admin-btn2 {
        padding: 12px 24px;
        color: #A27741; 
        border: 1px solid #ffffff; 
        border-radius: 50px;
        text-decoration: none;
    }

    .whatsapp-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #25D366;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: var(--transition);
        }

        .whatsapp-button:hover {
            background: #128C7E;
            transform: scale(1.1);
        }

        .whatsapp-button i {
            color: var(--white);
            font-size: 30px;
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

            .whatsapp-button {
                width: 50px;
                height: 50px;
            }

            .whatsapp-button i {
                font-size: 24px;
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

            .whatsapp-button {
                width: 45px;
                height: 45px;
            }

            .whatsapp-button i {
                font-size: 20px;
            }

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
                <li><a href="../index.php#inicio"><?php echo $lang['nav_home']; ?></a></li>
                <li><a href="../index.php#tours"><?php echo $lang['nav_tours']; ?></a></li>
                <li><a href="../index.php#guias"><?php echo $lang['nav_guides']; ?></a></li>
                <li><a href="../index.php#experiencias"><?php echo $lang['nav_experiences']; ?></a></li>
                <li><a href="../index.php#reservas"><?php echo $lang['nav_reservations']; ?></a></li>
            </ul>
            <div class="auth-buttons">
                <?php if (!$is_logged_in): ?>
                    <a href="../src/auth/login.php" class="btn btn-secondary">
                        <i class="fas fa-user"></i> <?php echo $lang['login_button']; ?>
                    </a>
                <?php else: ?>
                    <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? '../imagenes/default-avatar.png'); ?>" alt="Avatar">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <a href="../index.php?logout=1" class="logout-btn" title="<?php echo $lang['logout_button']; ?>">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                    <div style="position: relative; display: inline-block;">
                        <a href="reserva.php" id="cart-icon" class="btn btn-secondary">
                            <i class="fas fa-shopping-cart"></i>
                        </a>
                        <span id="cart-count" data-count="<?php echo $cart_count; ?>" 
                                style="position:absolute;top:-8px;right:-8px;background:red;color:#fff;border-radius:50%;min-width:20px;height:20px;padding:0 5px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:bold;z-index:1;">
                            <?php echo $cart_count; ?>
                        </span>
                        </div>

                <?php endif; ?>
                <div class="lang-switch">
                    <a href="?lang=es&id_tour=<?php echo $id_tour; ?>" class="lang-btn <?php if ($current_lang == 'es') echo 'active'; ?>"><?php echo $lang['lang_es']; ?></a>
                    <a href="?lang=en&id_tour=<?php echo $id_tour; ?>" class="lang-btn <?php if ($current_lang == 'en') echo 'active'; ?>"><?php echo $lang['lang_en']; ?></a>
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
        <a href="../index.php#inicio"><?php echo $lang['nav_home']; ?></a>
        <a href="../index.php#tours"><?php echo $lang['nav_tours']; ?></a>
        <a href="../index.php#guias"><?php echo $lang['nav_guides']; ?></a>
        <a href="../index.php#experiencias"><?php echo $lang['nav_experiences']; ?></a>
        <a href="../index.php#reservas"><?php echo $lang['nav_reservations']; ?></a>
        <div class="mobile-auth-buttons">
            <?php if (!$is_logged_in): ?>
                <button class="btn btn-primary" onclick="toggleGoogleSignin()">Google</button>
                <a href="../src/auth/login.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> <?php echo $lang['login_button']; ?>
                </a>
                <a href="../src/auth/register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> <?php echo $lang['register_button']; ?>
                </a>
            <?php else: ?>
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? '../imagenes/default-avatar.png'); ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="../index.php?logout=1" class="logout-btn" title="<?php echo $lang['logout_button']; ?>">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <div style="position: relative; display: inline-block;">
                    <a href="reserva.php" id="cart-icon-mobile" class="btn btn-secondary">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                    <span id="cart-count-mobile" data-count="<?php echo $cart_count; ?>"
                            style="position:absolute;top:-8px;right:-8px;background:red;color:#fff;border-radius:50%;min-width:20px;height:20px;padding:0 5px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:bold;z-index:1;">
                        <?php echo $cart_count; ?>
                    </span>
                    </div>

            <?php endif; ?>
            <div class="lang-switch">
                <a href="?lang=es&id_tour=<?php echo $id_tour; ?>" class="lang-btn <?php if ($current_lang == 'es') echo 'active'; ?>"><?php echo $lang['lang_es']; ?></a>
                <a href="?lang=en&id_tour=<?php echo $id_tour; ?>" class="lang-btn <?php if ($current_lang == 'en') echo 'active'; ?>"><?php echo $lang['lang_en']; ?></a>
            </div>
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
    <a href="https://wa.me/51958940006" class="whatsapp-button" target="_blank" title="<?php echo $current_lang == 'es' ? 'Contacta por WhatsApp' : 'Contact via WhatsApp'; ?>">
        <i class="fab fa-whatsapp"></i>
    </a>
    <section class="detail-section">
        <?php 
        $imagePath = getImagePath($tour['imagen_principal']);
        ?>
        <div class="detail-image">
            <div class="detail-image-bg" style="background-image: url('<?php echo $imagePath; ?>');"></div>
            <div class="image-fallback" id="imageFallback">
                <i class="fas fa-image" style="font-size: 3rem; margin-right: 1rem;"></i>
                <?php echo htmlspecialchars($tour['titulo']); ?>
            </div>
            <div class="detail-header">
                <h1><?php echo htmlspecialchars($tour['titulo']); ?></h1>
            </div>
        </div>
        <div class="detail-content">
            <div class="detail-info">
                <div class="info-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3><?php echo $lang['detail_price'] ?? 'Precio'; ?></h3>
                    <p><?php echo htmlspecialchars($tour['precio'] ? '$' . number_format($tour['precio'], 2) : ($lang['detail_tbd'] ?? 'Por determinar')); ?> / <?php echo $lang['per_person'] ?? 'por persona'; ?></p>
                </div>
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $lang['detail_duration'] ?? 'Duración'; ?></h3>
                    <p><?php echo htmlspecialchars($tour['duracion'] ?? ($lang['detail_tbd'] ?? 'Por determinar')); ?></p>
                </div>
                <div class="info-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3><?php echo $lang['detail_region'] ?? 'Región'; ?></h3>
                    <p><?php echo htmlspecialchars($tour['nombre_region'] ?? ($lang['detail_tbd'] ?? 'Por determinar')); ?></p>
                </div>
                <div class="info-card">
                    <i class="fas fa-user"></i>
                    <h3><?php echo $lang['detail_guide'] ?? 'Guía'; ?></h3>
                    <p><?php echo htmlspecialchars($tour['guia_nombre'] ?? ($lang['detail_tbd'] ?? 'Por determinar')); ?></p>
                </div>
            </div>
            <div class="detail-description">
                <h2><?php echo $lang['detail_description'] ?? 'Descripción'; ?></h2>
                <p><?php echo nl2br(htmlspecialchars($tour['descripcion'] ?? ($lang['detail_tbd'] ?? 'Por determinar'))); ?></p>
            </div>
            <div class="detail-description">
                <h2><?php echo $lang['detail_schedule'] ?? 'Itinerario'; ?></h2>
                <div class="schedule-grid">
                    <div class="schedule-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <strong><?php echo $lang['detail_departure'] ?? 'Lugar de Salida'; ?>:</strong> <?php echo htmlspecialchars($tour['lugar_salida'] ?? ($lang['detail_tbd'] ?? 'Por determinar')); ?>
                    </div>
                    <div class="schedule-item">
                        <i class="fas fa-clock"></i>
                        <strong><?php echo $lang['detail_departure_time'] ?? 'Hora de Salida'; ?>:</strong> <?php echo $tour['hora_salida'] ? date('H:i', strtotime($tour['hora_salida'])) : ($lang['detail_tbd'] ?? 'Por determinar'); ?>
                    </div>
                    <div class="schedule-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <strong><?php echo $lang['detail_arrival'] ?? 'Lugar de Llegada'; ?>:</strong> <?php echo htmlspecialchars($tour['lugar_llegada'] ?? ($lang['detail_tbd'] ?? 'Por determinar')); ?>
                    </div>
                    <div class="schedule-item">
                        <i class="fas fa-clock"></i>
                        <strong><?php echo $lang['detail_arrival_time'] ?? 'Hora de Llegada'; ?>:</strong> <?php echo $tour['hora_llegada'] ? date('H:i', strtotime($tour['hora_llegada'])) : ($lang['detail_tbd'] ?? 'Por determinar'); ?>
                    </div>
                </div>
            </div>
            <div class="reserve-section">
                <?php if ($is_logged_in): ?>
                    <form method="POST" action="process_add_to_cart.php" class="reserve-form" id="reserveForm">
                        <input type="hidden" name="id_tour" value="<?php echo $id_tour; ?>">
                        <div class="form-group">
                            <label class="form-label" for="fecha"><i class="fas fa-calendar-alt"></i><?php echo $lang['select_date'] ?? 'Seleccionar Fecha'; ?></label>
                            <input type="date" id="fecha" name="fecha" class="form-input" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            <div id="dateErrorMessage" class="error-message"><?php echo $lang['select_date_error'] ?? 'Por favor, seleccione una fecha primero.'; ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-user"></i><?php echo $lang['adult'] ?? 'Adulto'; ?> (12+)</label>
                            <div class="counter-group">
                                <button type="button" class="counter-btn" onclick="decrement('adultos')" disabled>-</button>
                                <span class="counter-value" id="adultos">1</span>
                                <input type="hidden" name="adultos" id="adultos_hidden" value="1">
                                <button type="button" class="counter-btn" onclick="increment('adultos')">+</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-child"></i><?php echo $lang['child'] ?? 'Niño'; ?> (3-11)</label>
                            <div class="counter-group">
                                <button type="button" class="counter-btn" onclick="decrement('ninos')" disabled>-</button>
                                <span class="counter-value" id="ninos">0</span>
                                <input type="hidden" name="ninos" id="ninos_hidden" value="0">
                                <button type="button" class="counter-btn" onclick="increment('ninos')">+</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-baby"></i><?php echo $lang['infant'] ?? 'Infantes'; ?> (0-2) - <?php echo $lang['free'] ?? 'Gratis'; ?></label>
                            <div class="counter-group">
                                <button type="button" class="counter-btn" onclick="decrement('infantes')" disabled>-</button>
                                <span class="counter-value" id="infantes">0</span>
                                <input type="hidden" name="infantes" id="infantes_hidden" value="0">
                                <button type="button" class="counter-btn" onclick="increment('infantes')">+</button>
                            </div>
                        </div>
                        <div class="total-price" id="totalPrice">
                            <?php echo $lang['total'] ?? 'Total'; ?>: $<?php echo number_format($tour['precio'], 2); ?>
                        </div>
                        <button type="submit" class="submit-btn" id="submitBtn" disabled><i class="fas fa-shopping-cart"></i> <?php echo $lang['add_to_cart'] ?? 'Agregar al Carrito'; ?></button>
                    </form>
                <?php else: ?>
                    <a href="../src/auth/login.php" class="reserve-btn">
                        <i class="fas fa-user"></i> <?php echo $lang['login_to_book'] ?? 'Inicia sesión para reservar'; ?>
                    </a>
                <?php endif; ?>
            </div>
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
                    <li><a href="https://maps.app.goo.gl/hpDo9q2vNQ238Ln46" target="_blank"><i class="fas fa-map-marker-alt"></i> <?php echo $lang['footer_contact_address']; ?></a></li>
                    <li><a href="tel:+51958940006"><i class="fas fa-phone"></i> +51 958 940 006</a></li>
                    <li><a href="mailto:antarestravelperu@gmail.com"><i class="fas fa-envelope"></i> antarestravelperu@gmail.com</a></li>
                    <li><a href="https://www.antarestravelperu.com" target="_blank"><i class="fas fa-globe"></i> www.antarestravelperu.com</a></li>
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
                    <li><a href="#"><?php echo $lang['footer_legal_terms']; ?></a></li>
                    <li><a href="#"><?php echo $lang['footer_legal_privacy']; ?></a></li>
                </ul>

            </div>
        </div>
        <div class="footer-bottom">
            <p><?php echo $lang['footer_copyright']; ?></p>
        </div>
    </footer>

    <script>
        const tourPrice = <?php echo $tour['precio'] ? $tour['precio'] : 0; ?>;
        const submitBtn = document.getElementById('submitBtn');
        const fechaInput = document.getElementById('fecha');
        const totalPriceElement = document.getElementById('totalPrice');
        const dateErrorMessage = document.getElementById('dateErrorMessage');

        function updateTotalPrice() {
            const adults = parseInt(document.getElementById('adultos').textContent);
            const children = parseInt(document.getElementById('ninos').textContent);
            const total = (adults + children) * tourPrice;
            totalPriceElement.textContent = `<?php echo $lang['total'] ?? 'Total'; ?>: $${total.toFixed(2)}`;
        }

        function checkFormValidity() {
            const fecha = fechaInput.value;
            const adults = parseInt(document.getElementById('adultos').textContent);
            const isValid = fecha && adults > 0;
            submitBtn.disabled = !isValid;
            dateErrorMessage.classList.toggle('active', !fecha);
        }

        function increment(type) {
            let value = parseInt(document.getElementById(type).textContent);
            value = Math.min(value + 1, 10);
            document.getElementById(type).textContent = value;
            document.getElementById(type + '_hidden').value = value;
            if (type === 'adultos') {
                document.getElementById('adultos').previousElementSibling.disabled = value <= 1;
            } else {
                document.getElementById(type).previousElementSibling.disabled = value <= 0;
            }
            updateTotalPrice();
            checkFormValidity();
        }

        function decrement(type) {
            let value = parseInt(document.getElementById(type).textContent);
            if (type === 'adultos' && value <= 1) return;
            value = Math.max(value - 1, 0);
            document.getElementById(type).textContent = value;
            document.getElementById(type + '_hidden').value = value;
            if (type === 'adultos') {
                document.getElementById('adultos').previousElementSibling.disabled = value <= 1;
            } else {
                document.getElementById(type).previousElementSibling.disabled = value <= 0;
            }
            updateTotalPrice();
            checkFormValidity();
        }

        submitBtn.addEventListener('click', function() {
            if (submitBtn.disabled) {
                dateErrorMessage.classList.add('active');
            }
        });

        document.getElementById('reserveForm').addEventListener('submit', function(e) {
            if (parseInt(document.getElementById('adultos_hidden').value) === 0) {
                e.preventDefault();
                alert('<?php echo $lang['at_least_one_adult'] ?? 'Debe seleccionar al menos 1 adulto.'; ?>');
            }
        });

        fechaInput.addEventListener('change', checkFormValidity);

        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            const tourImage = document.querySelector('.detail-image-bg');
            const imageFallback = document.getElementById('imageFallback');
            const imageSrc = tourImage.style.backgroundImage.slice(5, -2);

            if (imageSrc) {
                const img = new Image();
                img.onload = function() {
                    imageFallback.classList.remove('active');
                };
                img.onerror = function() {
                    tourImage.style.backgroundImage = 'url(https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=1200&h=600&fit=crop&crop=center)';
                    imageFallback.classList.add('active');
                };
                img.src = imageSrc;
            } else {
                imageFallback.classList.add('active');
            }

            window.toggleMobileMenu = function() {
                const mobileNav = document.getElementById('mobileNav');
                const mobileMenu = document.querySelector('.mobile-menu');
                mobileNav.classList.toggle('active');
                mobileMenu.classList.toggle('active');
            }

            window.toggleGoogleSignin = function() {
                const googleSignin = document.getElementById('googleSignin');
                googleSignin.classList.toggle('active');
                if (googleSignin.classList.contains('active')) {
                    const mobileNav = document.getElementById('mobileNav');
                    mobileNav.classList.remove('active');
                    document.querySelector('.mobile-menu').classList.remove('active');
                }
            }

            const infoCards = document.querySelectorAll('.info-card, .schedule-item');
            infoCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.15}s`;
                card.style.animation = 'fadeInUp 0.7s ease-out both';
            });

            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            checkFormValidity();
            updateTotalPrice();
        });
    </script>
</body>
</html>