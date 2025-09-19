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
        header("Location: /");
        exit;
    } else {
        echo "❌ Token inválido";
        exit;
    }
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /");
    exit;
}

function limitRequests($ip, $max_requests = 10, $time_window = 1) {
    $cache_dir = __DIR__ . '/cache/';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    $file = $cache_dir . 'rate_limit_' . md5($ip) . '.txt';
    $current_time = time();

    if (file_exists($file)) {
        $data = file_get_contents($file);
        list($count, $last_time) = explode(':', $data);

        if ($current_time - $last_time < $time_window) {
            if ($count >= $max_requests) {
                http_response_code(429);
                die('Too Many Requests');
            }
            file_put_contents($file, ($count + 1) . ':' . $last_time);
        } else {
            file_put_contents($file, '1:' . $current_time);
        }
    } else {
        file_put_contents($file, '1:' . $current_time);
    }
}

$ip = $_SERVER['REMOTE_ADDR'];
limitRequests($ip);
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang == 'es' ? 'Turismo en Cusco | Tours a Machu Picchu y Valle Sagrado - Antares Travel Perú' : 'Cusco Tourism | Machu Picchu and Sacred Valley Tours - Antares Travel Peru'; ?></title>
    <meta name="description" content="<?php echo $current_lang == 'es' ? 'Explora Cusco con Antares Travel Perú. Tours a Machu Picchu, Valle Sagrado y aventuras únicas. ¡Reserva tu experiencia inolvidable!' : 'Explore Cusco with Antares Travel Peru. Machu Picchu, Sacred Valley, and unique adventure tours. Book your unforgettable experience now!'; ?>">
    <meta name="keywords" content="<?php echo $current_lang == 'es' ? 'turismo en Cusco, tours a Machu Picchu, Valle Sagrado, agencia de viajes Cusco, tours de aventura Perú, guías turísticos Cusco, reservas Machu Picchu' : 'Cusco tourism, Machu Picchu tours, Sacred Valley, Cusco travel agency, Peru adventure tours, Cusco tour guides, Machu Picchu bookings'; ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Antares Travel Perú">
    <meta name="geo.region" content="PE-CUS">
    <meta name="geo.placename" content="Cusco, Perú">
    <meta name="geo.position" content="-13.5167;-71.9781">
    <link rel="canonical" href="https://www.antarestravelperu.com<?php echo $current_lang == 'es' ? '' : '/en'; ?>">
    <link rel="icon" type="image/png" href="imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "TravelAgency",
      "name": "Antares Travel Perú",
      "description": "<?php echo $current_lang == 'es' ? 'Agencia de turismo en Cusco especializada en tours a Machu Picchu, Valle Sagrado y aventuras en Perú.' : 'Travel agency in Cusco specializing in Machu Picchu, Sacred Valley, and adventure tours in Peru.'; ?>",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Calle Plateros 365",
        "addressLocality": "Cusco",
        "addressRegion": "Cusco",
        "postalCode": "08002",
        "addressCountry": "PE"
      },
      "telephone": "+51 958 940 006",
      "email": "antarestravelperu@gmail.com",
      "url": "https://www.antarestravelperu.com",
      "image": "https://www.antarestravelperu.com/imagenes/antares_logozz3.png",
      "openingHours": "Mo-Su 08:00-20:00",
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": "-13.5167",
        "longitude": "-71.9781"
      },
      "sameAs": [
        "https://wa.me/51958940006",
        "https://www.facebook.com/antarestravelperu",
        "https://www.instagram.com/antarestravelperu",
        "https://www.tripadvisor.com/AntaresTravelPeru"
      ],
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "reviewCount": "150"
      },
      "offers": [
        {
          "@type": "Offer",
          "name": "<?php echo $current_lang == 'es' ? 'Tour a Machu Picchu' : 'Machu Picchu Tour'; ?>",
          "description": "<?php echo $current_lang == 'es' ? 'Tour de 1 día a Machu Picchu desde Cusco con transporte y guía incluido.' : 'One-day Machu Picchu tour from Cusco with transportation and guide included.'; ?>",
          "url": "https://www.antarestravelperu.com/tours/tour-machu-picchu",
          "priceCurrency": "PEN",
          "price": "300"
        },
        {
          "@type": "Offer",
          "name": "<?php echo $current_lang == 'es' ? 'Tour al Valle Sagrado' : 'Sacred Valley Tour'; ?>",
          "description": "<?php echo $current_lang == 'es' ? 'Explora el Valle Sagrado desde Cusco con visitas a Pisac, Ollantaytambo y Chinchero.' : 'Explore the Sacred Valley from Cusco with visits to Pisac, Ollantaytambo, and Chinchero.'; ?>",
          "url": "https://www.antarestravelperu.com/tours/tour-valle-sagrado",
          "priceCurrency": "PEN",
          "price": "150"
        }
      ]
    }
    </script>
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
            --shadow-light: 0 4px 20px rgba(162, 119, 65, 0.08);
            --shadow-medium: 0 8px 30px rgba(162, 119, 65, 0.12);
            --shadow-heavy: 0 16px 40px rgba(162, 119, 65, 0.16);
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            --gradient-secondary: linear-gradient(135deg, var(--secondary-color), #6B8A8D);
            --gradient-hero: linear-gradient(135deg, rgba(139, 99, 50, 0.85), rgba(91, 121, 124, 0.75));
            --border-radius: 16px;
            --transition-quick: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --transition-smooth: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --transition-slow: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --elastic: cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--primary-bg);
            overflow-x: hidden;
            opacity: 0;
            animation: pageLoad 1s ease-out forwards;
        }

        @keyframes pageLoad {
            to {
                opacity: 1;
            }
        }

        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.8s ease, visibility 0.8s ease;
        }

        .loading-screen.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loading-content {
            text-align: center;
            color: var(--white);
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 250, 240, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 1000;
            padding: 1rem 0;
            transition: var(--transition-smooth);
            border-bottom: 1px solid rgba(162, 119, 65, 0.1);
            transform: translateY(-100%);
            animation: slideDown 1.2s var(--elastic) 0.5s forwards;
        }

        @keyframes slideDown {
            to {
                transform: translateY(0);
            }
        }

        .navbar.scrolled {
            background: rgba(255, 250, 240, 0.98);
            box-shadow: var(--shadow-light);
            padding: 0.7rem 0;
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
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            gap: 12px;
            transition: var(--transition-smooth);
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo img {
            transition: var(--transition-smooth);
            border-radius: 8px;
        }

        .logo:hover img {
            transform: rotate(5deg);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2.5rem;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-smooth);
            position: relative;
            padding: 0.5rem 0;
        }

        .nav-links a:hover {
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .nav-links a::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--gradient-primary);
            transition: var(--transition-smooth);
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .nav-links a:hover::before {
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
            border-radius: 50px;
            overflow: hidden;
            transition: var(--transition-smooth);
        }

        .lang-switch:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .lang-btn {
            padding: 0.6rem 1.2rem;
            text-decoration: none;
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            transition: var(--transition-smooth);
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .lang-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: var(--transition-smooth);
            z-index: -1;
        }

        .lang-btn:hover::before,
        .lang-btn.active::before {
            left: 0;
        }

        .lang-btn.active,
        .lang-btn:hover {
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
            transition: var(--transition-smooth);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transform: perspective(1px) translateZ(0);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition-smooth);
        }

        .btn:hover::before {
            left: 100%;
            transition: var(--transition-quick);
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-light);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: var(--shadow-medium);
        }

        .btn-primary:active {
            transform: translateY(-1px) scale(0.98);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            position: relative;
            z-index: 1;
        }

        .btn-secondary::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--gradient-primary);
            transition: var(--transition-smooth);
            z-index: -1;
            border-radius: 50px;
        }

        .btn-secondary:hover::after {
            width: 100%;
        }

        .btn-secondary:hover {
            color: var(--white);
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            position: relative;
            padding: 0.5rem;
            border-radius: 50px;
            transition: var(--transition-smooth);
        }

        .user-profile:hover {
            background: rgba(162, 119, 65, 0.1);
            transform: translateY(-2px);
        }

        .user-profile img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            object-fit: cover;
            transition: var(--transition-smooth);
        }

        .user-profile:hover img {
            transform: scale(1.1);
            box-shadow: var(--shadow-light);
        }

        .user-profile span {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-dark);
            transition: var(--transition-smooth);
        }

        .user-profile:hover span {
            color: var(--primary-color);
        }

        .user-profile .logout-btn {
            padding: 0.5rem;
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            border-radius: 50%;
            transition: var(--transition-smooth);
        }

        .user-profile .logout-btn:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: rotate(180deg);
        }

        .hero {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* MEJORA PRINCIPAL: Transiciones suaves para imágenes del hero */
        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: scale(1.1);
            transition: opacity 2s cubic-bezier(0.4, 0, 0.2, 1), 
                        transform 12s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .hero-image.active {
            opacity: 1;
            transform: scale(1.2);
        }

        .hero-image.fade-out {
            opacity: 0;
            transform: scale(1.15);
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-hero);
            transition: opacity 1s ease;
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
            opacity: 0;
            transform: translateY(80px) scale(0.9);
            animation: heroContentSlide 1.8s var(--elastic) 1.5s forwards;
        }

        @keyframes heroContentSlide {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .hero-content h1 {
            font-size: clamp(2.5rem, 5vw, 3.8rem);
            margin-bottom: 1.5rem;
            font-weight: 700;
            text-shadow: 2px 4px 8px rgba(0,0,0,0.3);
            line-height: 1.2;
        }

        .hero-content p {
            font-size: clamp(1rem, 2.5vw, 1.3rem);
            margin-bottom: 2.5rem;
            opacity: 0.95;
            text-shadow: 1px 2px 4px rgba(0,0,0,0.3);
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-buttons .btn {
            transform: translateY(60px) scale(0.8);
            opacity: 0;
            animation: buttonSlide 1.2s var(--bounce) forwards;
        }

        .hero-buttons .btn:nth-child(1) {
            animation-delay: 2s;
        }

        .hero-buttons .btn:nth-child(2) {
            animation-delay: 2.3s;
        }

        @keyframes buttonSlide {
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .hero-indicators {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 3;
            opacity: 0;
            animation: indicatorsSlide 1s ease 2.5s forwards;
        }

        @keyframes indicatorsSlide {
            to {
                opacity: 1;
            }
        }

        .hero-indicator {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: var(--transition-smooth);
            position: relative;
        }

        .hero-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: var(--white);
            border-radius: 50%;
            transition: var(--transition-smooth);
            transform: translate(-50%, -50%);
        }

        .hero-indicator:hover::before,
        .hero-indicator.active::before {
            width: 100%;
            height: 100%;
        }

        .hero-indicator:hover {
            transform: scale(1.3);
        }

        .section {
            padding: 100px 0;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
            opacity: 0;
            transform: translateY(80px) scale(0.9);
            transition: var(--transition-slow);
        }

        .section-header.animate {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 2.8rem);
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            position: relative;
            font-weight: 700;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
            animation: underlineGrow 1.5s var(--bounce) 0.8s forwards;
        }

        @keyframes underlineGrow {
            to {
                width: 80px;
            }
        }

        .section-subtitle {
            font-size: 1.15rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .tours-section {
            background: var(--primary-bg);
            position: relative;
        }

        .tours-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to bottom, transparent, rgba(162, 119, 65, 0.03));
            pointer-events: none;
        }

        .tour-categories {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 50px;
            gap: 20px;
        }

        .category-btn {
            padding: 12px 24px;
            background: var(--white);
            border: 2px solid var(--primary-color);
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition-smooth);
            color: var(--primary-color);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }

        .category-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: var(--transition-smooth);
            z-index: -1;
        }

        .category-btn:hover::before,
        .category-btn.active::before {
            left: 0;
        }

        .category-btn.active,
        .category-btn:hover {
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .tours-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            perspective: 1000px;
        }

        .tour-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: var(--transition-slow);
            opacity: 0;
            transform: translateY(100px) rotateX(15deg) scale(0.8);
            position: relative;
        }

        .tour-card.animate {
            opacity: 1;
            transform: translateY(0) rotateX(0) scale(1);
        }

        .tour-card:hover {
            transform: translateY(-15px) scale(1.03) rotateX(-2deg);
            box-shadow: var(--shadow-heavy);
        }

        .tour-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition-quick);
            z-index: 1;
        }

        .tour-card:hover::before {
            left: 100%;
        }

        .tour-image {
            height: 220px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }

        .tour-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,0.1));
            transition: var(--transition-smooth);
        }

        .tour-card:hover .tour-image::after {
            background: linear-gradient(to bottom, transparent 30%, rgba(0,0,0,0.2));
        }

        .tour-header {
            padding: 20px;
            background: var(--gradient-primary);
            color: var(--white);
            position: relative;
        }

        .tour-title {
            margin: 0 0 8px 0;
            font-size: 1.4rem;
            font-weight: 600;
            line-height: 1.3;
        }

        .tour-schedule {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .tour-content {
            padding: 20px;
        }

        .tour-details {
            margin: 15px 0;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .tour-details div {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            transition: var(--transition-smooth);
        }

        .tour-details div:hover i {
            transform: scale(1.3) rotate(10deg);
        }

        .tour-actions {
            margin-top: 20px;
            text-align: center;
        }

        .guias-section {
            background: var(--white);
            position: relative;
        }

        .guias-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to bottom, rgba(162, 119, 65, 0.03), transparent);
            pointer-events: none;
        }

        .guias-filters {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .guias-filters select {
            padding: 0.8rem 1.5rem;
            border: 2px solid var(--primary-light);
            border-radius: 50px;
            background: var(--white);
            cursor: pointer;
            font-size: 1rem;
            color: var(--text-dark);
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-light);
        }

        .guias-filters select:hover,
        .guias-filters select:focus {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-medium);
            transform: translateY(-2px);
            outline: none;
        }

        .guias-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .guia-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition-slow);
            opacity: 0;
            transform: translateY(80px) scale(0.85) rotateY(10deg);
            position: relative;
            overflow: hidden;
        }

        .guia-card.animate {
            opacity: 1;
            transform: translateY(0) scale(1) rotateY(0);
        }

        .guia-card:hover {
            transform: translateY(-12px) scale(1.02) rotateY(-2deg);
            box-shadow: var(--shadow-heavy);
        }

        .guia-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: var(--transition-smooth);
        }

        .guia-card:hover::before {
            transform: scaleX(1);
        }

        .guia-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 4px solid var(--primary-light);
            object-fit: cover;
            transition: var(--transition-smooth);
            position: relative;
        }

        .guia-avatar::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: var(--gradient-primary);
            opacity: 0;
            transition: var(--transition-smooth);
            z-index: -1;
        }

        .guia-card:hover .guia-avatar {
            transform: scale(1.1) rotate(5deg);
        }

        .guia-card:hover .guia-avatar::before {
            opacity: 1;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(-5deg); opacity: 0.7; }
            50% { transform: scale(1.05) rotate(5deg); opacity: 0.3; }
        }

        .guia-name {
            font-size: 1.4rem;
            color: var(--primary-color);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .guia-rating {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-bottom: 15px;
            align-items: center;
        }

        .guia-rating .star {
            color: #ffd700;
            transition: var(--transition-smooth);
        }

        .guia-card:hover .guia-rating .star {
            animation: starTwinkle 1.5s ease-in-out infinite;
        }

        @keyframes starTwinkle {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.2) rotate(10deg); }
        }

        .experiencias-section {
            background: var(--primary-bg);
            position: relative;
        }

        .experiencias-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to bottom, transparent, rgba(162, 119, 65, 0.03));
            pointer-events: none;
        }

        .photos-mural {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .photo-item {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: var(--transition-slow);
            opacity: 0;
            transform: translateY(60px) scale(0.9) rotateZ(3deg);
        }

        .photo-item.animate {
            opacity: 1;
            transform: translateY(0) scale(1) rotateZ(0);
        }

        .photo-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
            transition: var(--transition-slow);
        }

        .photo-item:hover {
            transform: translateY(-12px) scale(1.02) rotateZ(-2deg);
            box-shadow: var(--shadow-heavy);
        }

        .photo-item:hover img {
            transform: scale(1.1) rotate(2deg);
        }

        .photo-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 20px;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateY(15px);
            transition: var(--transition-smooth);
        }

        .photo-item:hover .photo-info {
            transform: translateY(0);
        }

        .photo-info .small-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid var(--white);
            transition: var(--transition-smooth);
        }

        .photo-item:hover .photo-info .small-avatar {
            transform: scale(1.1) rotate(-5deg);
        }

        .carousel-container {
            position: relative;
            overflow: hidden;
            border-radius: var(--border-radius);
            margin-bottom: 40px;
        }

        .carousel {
            display: flex;
            transition: transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .carousel-item {
            flex: 0 0 100%;
            position: relative;
        }

        .experiencia-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-medium);
            margin: 0 15px;
            transition: var(--transition-smooth);
        }

        .experiencia-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-heavy);
        }

        .experiencia-content {
            padding: 35px;
        }

        .experiencia-user {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .experiencia-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid var(--primary-light);
            transition: var(--transition-smooth);
        }

        .experiencia-card:hover .experiencia-avatar {
            transform: scale(1.15) rotate(10deg);
        }

        .experiencia-name {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition-smooth);
            color: var(--primary-color);
            font-size: 1.2rem;
            box-shadow: var(--shadow-light);
            z-index: 10;
        }

        .carousel-nav:hover {
            background: var(--white);
            box-shadow: var(--shadow-medium);
            transform: translateY(-50%) scale(1.15) rotate(10deg);
        }

        .carousel-nav.prev {
            left: 15px;
        }

        .carousel-nav.next {
            right: 15px;
        }

        .add-experiencia {
            max-width: 600px;
            margin: 50px auto 0;
            text-align: center;
        }

        .add-experiencia form {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
            margin-top: 25px;
            transition: var(--transition-smooth);
        }

        .add-experiencia form:hover {
            box-shadow: var(--shadow-heavy);
            transform: translateY(-5px);
        }

        .add-experiencia textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid var(--primary-light);
            border-radius: 12px;
            margin-bottom: 15px;
            font-family: inherit;
            resize: vertical;
            transition: var(--transition-smooth);
        }

        .add-experiencia textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(162, 119, 65, 0.1);
            transform: scale(1.02);
        }

        .add-experiencia input[type="file"] {
            margin-bottom: 15px;
            padding: 10px;
            border: 2px dashed var(--primary-light);
            border-radius: 12px;
            width: 100%;
            transition: var(--transition-smooth);
        }

        .add-experiencia input[type="file"]:hover {
            border-color: var(--primary-color);
            background: rgba(162, 119, 65, 0.05);
            transform: scale(1.02);
        }

        .footer {
            background: var(--primary-dark);
            color: var(--white);
            padding: 60px 0 30px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gradient-primary);
        }

        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 3rem;
            padding: 0 2.5rem;
        }

        .footer-section h3 {
            color: var(--primary-light);
            margin-bottom: 20px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .footer-section p, .footer-section li {
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 10px;
            line-height: 1.6;
            transition: var(--transition-smooth);
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: var(--transition-smooth);
            position: relative;
        }

        .footer-section a:hover {
            color: var(--primary-light);
            transform: translateX(8px);
        }

        .footer-section a::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--primary-light);
            transition: var(--transition-smooth);
        }

        .footer-section a:hover::before {
            width: 100%;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .social-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transform: scale(0);
            transition: var(--transition-smooth);
            border-radius: 50%;
        }

        .social-link:hover::before {
            transform: scale(1);
        }

        .social-link:hover {
            transform: translateY(-5px) scale(1.15) rotate(10deg);
            box-shadow: var(--shadow-medium);
        }

        .social-link i {
            position: relative;
            z-index: 1;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 40px;
        }

        .whatsapp-button {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: var(--gradient-secondary);
            border-radius: 50%;
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-medium);
            animation: whatsappPulse 3s infinite;
        }

        @keyframes whatsappPulse {
            0%, 100% { 
                box-shadow: var(--shadow-medium), 0 0 0 0 rgba(37, 211, 102, 0.7);
                transform: scale(1);
            }
            50% { 
                box-shadow: var(--shadow-heavy), 0 0 0 15px rgba(37, 211, 102, 0);
                transform: scale(1.05);
            }
        }

        .whatsapp-button:hover {
            background: #128C7E;
            transform: scale(1.2) rotate(10deg);
            animation: none;
            box-shadow: var(--shadow-heavy);
        }

        .whatsapp-button i {
            color: var(--white);
            font-size: 32px;
            transition: var(--transition-smooth);
        }

        .whatsapp-button:hover i {
            transform: scale(1.1) rotate(-10deg);
        }

        .mobile-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
            z-index: 1001;
            padding: 10px;
            transition: var(--transition-smooth);
        }

        .mobile-menu:hover {
            transform: scale(1.1);
        }

        .mobile-menu span {
            width: 28px;
            height: 3px;
            background: var(--primary-color);
            transition: var(--transition-smooth);
            border-radius: 2px;
        }

        .mobile-nav {
            position: fixed;
            top: 80px;
            right: -100%;
            width: 100%;
            max-width: 350px;
            height: calc(100vh - 80px);
            background: var(--white);
            box-shadow: var(--shadow-heavy);
            transition: var(--transition-slow);
            z-index: 999;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .mobile-nav.active {
            right: 0;
        }

        .mobile-nav a {
            color: var(--text-dark);
            text-decoration: none;
            padding: 1.2rem 0;
            border-bottom: 1px solid rgba(162, 119, 65, 0.1);
            font-weight: 500;
            transition: var(--transition-smooth);
            position: relative;
        }

        .mobile-nav a:hover {
            color: var(--primary-color);
            transform: translateX(15px) scale(1.02);
        }

        .mobile-nav a::before {
            content: '';
            position: absolute;
            left: 0;
            bottom: -1px;
            width: 0;
            height: 2px;
            background: var(--gradient-primary);
            transition: var(--transition-smooth);
        }

        .mobile-nav a:hover::before {
            width: 100%;
        }

        .mobile-auth-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .google-signin-container {
            position: fixed;
            top: 90px;
            right: 25px;
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-heavy);
            z-index: 1001;
            display: none;
            transition: var(--transition-smooth);
            border: 1px solid rgba(162, 119, 65, 0.1);
        }

        .google-signin-container.active {
            display: block;
            animation: slideInDown 0.5s var(--elastic);
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .google-signin-container .close-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: none;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition-smooth);
            padding: 5px;
            border-radius: 50%;
        }

        .google-signin-container .close-btn:hover {
            color: var(--primary-color);
            background: rgba(162, 119, 65, 0.1);
            transform: rotate(180deg) scale(1.1);
        }

        #cart-icon, #cart-icon-mobile {
            position: relative !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }

        #cart-count, #cart-count-mobile {
            position: absolute !important;
            top: -10px !important;
            right: -10px !important;
            background: linear-gradient(135deg, #e53e3e, #ff6b6b) !important;
            color: white !important;
            border-radius: 50% !important;
            width: 24px !important;
            height: 24px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            border: 2px solid white !important;
            box-shadow: var(--shadow-medium) !important;
            z-index: 10 !important;
            min-width: 24px !important;
            line-height: 1 !important;
            animation: cartBounce 0.6s var(--bounce);
        }

        @keyframes cartBounce {
            0% { transform: scale(0) rotate(-180deg); }
            50% { transform: scale(1.4) rotate(10deg); }
            100% { transform: scale(1) rotate(0); }
        }

        #cart-count[data-count="0"], 
        #cart-count-mobile[data-count="0"] {
            display: none !important;
        }

        /* MEJORAS EN ANIMACIONES DE SCROLL */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(80px) scale(0.9);
            transition: opacity 1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                        transform 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .animate-on-scroll.animate {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .stagger-animation > * {
            opacity: 0;
            transform: translateY(60px) scale(0.85) rotateX(15deg);
            transition: var(--transition-slow);
        }

        .stagger-animation.animate > *:nth-child(1) { 
            animation: slideUpStagger 1s var(--bounce) 0.1s forwards; 
        }
        .stagger-animation.animate > *:nth-child(2) { 
            animation: slideUpStagger 1s var(--bounce) 0.25s forwards; 
        }
        .stagger-animation.animate > *:nth-child(3) { 
            animation: slideUpStagger 1s var(--bounce) 0.4s forwards; 
        }
        .stagger-animation.animate > *:nth-child(4) { 
            animation: slideUpStagger 1s var(--bounce) 0.55s forwards; 
        }
        .stagger-animation.animate > *:nth-child(5) { 
            animation: slideUpStagger 1s var(--bounce) 0.7s forwards; 
        }
        .stagger-animation.animate > *:nth-child(6) { 
            animation: slideUpStagger 1s var(--bounce) 0.85s forwards; 
        }

        @keyframes slideUpStagger {
            to {
                opacity: 1;
                transform: translateY(0) scale(1) rotateX(0);
            }
        }

        /* Efectos especiales para elementos individuales */
        .tour-categories .category-btn {
            opacity: 0;
            transform: translateY(30px) scale(0.9);
            transition: var(--transition-slow);
        }

        .tour-categories.animate .category-btn:nth-child(1) { 
            animation: slideUpStagger 0.8s var(--elastic) 0.1s forwards; 
        }
        .tour-categories.animate .category-btn:nth-child(2) { 
            animation: slideUpStagger 0.8s var(--elastic) 0.2s forwards; 
        }
        .tour-categories.animate .category-btn:nth-child(3) { 
            animation: slideUpStagger 0.8s var(--elastic) 0.3s forwards; 
        }
        .tour-categories.animate .category-btn:nth-child(4) { 
            animation: slideUpStagger 0.8s var(--elastic) 0.4s forwards; 
        }

        .guias-filters select {
            opacity: 0;
            transform: translateY(30px) scale(0.9);
            transition: var(--transition-slow);
        }

        .guias-filters.animate select:nth-child(1) { 
            animation: slideUpStagger 0.8s var(--elastic) 0.1s forwards; 
        }
        .guias-filters.animate select:nth-child(2) { 
            animation: slideUpStagger 0.8s var(--elastic) 0.2s forwards; 
        }
        .guias-filters.animate select:nth-child(3) { 
            animation: slideUpStagger 0.8s var(--elastic) 0.3s forwards; 
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
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
                gap: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .tours-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .guias-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }

            .container {
                padding: 0 1rem;
            }

            .carousel-nav {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .google-signin-container {
                width: 90%;
                max-width: 300px;
                right: 50%;
                transform: translateX(50%);
            }

            .photos-mural {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }

            .whatsapp-button {
                width: 55px;
                height: 55px;
                bottom: 20px;
                right: 20px;
            }

            .whatsapp-button i {
                font-size: 26px;
            }

            #cart-count, #cart-count-mobile {
                width: 22px !important;
                height: 22px !important;
                font-size: 10px !important;
                top: -8px !important;
                right: -8px !important;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .tour-card {
                margin: 0 5px;
            }

            .guias-filters {
                flex-direction: column;
                gap: 1rem;
            }

            .guias-filters select {
                width: 100%;
            }

            .whatsapp-button {
                width: 50px;
                height: 50px;
            }

            .whatsapp-button i {
                font-size: 22px;
            }

            .section {
                padding: 60px 0;
            }
        }
    </style>
</head>
<body>
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h3>Cargando experiencias únicas...</h3>
        </div>
    </div>

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
    
    $cart_count = isset($_SESSION['cart']['total_paquetes']) ? $_SESSION['cart']['total_paquetes'] : 0;
    
    $login_to_book = isset($lang['login_to_book']) ? $lang['login_to_book'] : ($current_lang == 'es' ? 'Inicia sesión para reservar' : 'Log in to book');
    ?>

    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#inicio" class="logo">
                <img src="imagenes/antares_logozz2.png" alt="<?php echo $current_lang == 'es' ? 'Antares Travel Perú Logo' : 'Antares Travel Peru Logo'; ?>" height="50" loading="lazy">
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
                <?php if (!$is_logged_in): ?>
                    <a href="src/auth/login.php" class="btn btn-secondary">
                        <i class="fas fa-user"></i> <?php echo $lang['login_button']; ?>
                    </a>
                <?php else: ?>
                    <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? 'imagenes/default-avatar.png'); ?>" alt="<?php echo $current_lang == 'es' ? 'Avatar de usuario' : 'User avatar'; ?>" loading="lazy">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <a href="/?logout=1" class="logout-btn" title="<?php echo $lang['logout_button']; ?>">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                    <a href="reserva.php" id="cart-icon" class="btn btn-secondary" style="position: relative;">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-count" data-count="<?php echo $cart_count; ?>"><?php echo $cart_count; ?></span>
                    </a>
                <?php endif; ?>
                <div class="lang-switch">
                    <a href="?lang=es" class="lang-btn <?php if ($current_lang == 'es') echo 'active'; ?>"><?php echo $lang['lang_es']; ?></a>
                    <a href="?lang=en" class="lang-btn <?php if ($current_lang == 'en') echo 'active'; ?>"><?php echo $lang['lang_en']; ?></a>
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
        <a href="#inicio"><?php echo $lang['nav_home']; ?></a>
        <a href="#tours"><?php echo $lang['nav_tours']; ?></a>
        <a href="#guias"><?php echo $lang['nav_guides']; ?></a>
        <a href="#experiencias"><?php echo $lang['nav_experiences']; ?></a>
        <a href="#reservas"><?php echo $lang['nav_reservations']; ?></a>
        <div class="mobile-auth-buttons">
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
                    <img src="<?php echo htmlspecialchars($_SESSION['user_picture'] ?? 'imagenes/default-avatar.png'); ?>" alt="<?php echo $current_lang == 'es' ? 'Avatar de usuario' : 'User avatar'; ?>" loading="lazy">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="/?logout=1" class="logout-btn" title="<?php echo $lang['logout_button']; ?>">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <a href="reserva.php" id="cart-icon-mobile" class="btn btn-secondary" style="position: relative;">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count-mobile" data-count="<?php echo $cart_count; ?>"><?php echo $cart_count; ?></span>
                </a>
            <?php endif; ?>
            <div class="lang-switch">
                <a href="?lang=es" class="lang-btn <?php if ($current_lang == 'es') echo 'active'; ?>"><?php echo $lang['lang_es']; ?></a>
                <a href="?lang=en" class="lang-btn <?php if ($current_lang == 'en') echo 'active'; ?>"><?php echo $lang['lang_en']; ?></a>
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

    <section id="inicio" class="hero">
        <div class="hero-image active" style="background-image: url('imagenes/fondo01.jpg')"></div>
        <div class="hero-image" style="background-image: url('imagenes/fondo02.jpg')"></div>
        <div class="hero-image" style="background-image: url('imagenes/fondo03.jpg')"></div>
        <div class="hero-image" style="background-image: url('imagenes/fondo04.jpg')"></div>
        <div class="hero-image" style="background-image: url('imagenes/fondo05.jpg')"></div>
        <div class="hero-bg"></div>
        <div class="container">
            <div class="hero-content">
                <h1><?php echo $current_lang == 'es' ? 'Turismo en Cusco y Machu Picchu con Antares Travel Perú' : 'Cusco and Machu Picchu Tourism with Antares Travel Peru'; ?></h1>
                <p><?php echo $lang['hero_subtitle']; ?></p>
                <div class="hero-buttons">
                    <a href="#tours" class="btn btn-primary">
                        <i class="fas fa-compass"></i><span><?php echo $lang['hero_button_explore']; ?></span>
                    </a>
                    <?php if ($is_logged_in): ?>
                        <a href="src/reserva.php" class="btn btn-primary" style="background: rgba(248, 243, 233, 0.95); color: var(--primary-color);">
                            <i class="fas fa-shopping-cart"></i><span><?php echo $lang['hero_button_book']; ?></span>
                        </a>
                    <?php else: ?>
                        <a href="src/auth/login.php" class="btn btn-primary" style="background: rgba(248, 243, 233, 0.95); color: var(--primary-color);">
                            <i class="fas fa-user"></i><span><?php echo $login_to_book; ?></span>
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
            <div class="section-header animate-on-scroll">
                <h2 class="section-title"><?php echo $current_lang == 'es' ? 'Tours Populares en Cusco y Machu Picchu' : 'Popular Tours in Cusco and Machu Picchu'; ?></h2>
                <p class="section-subtitle"><?php echo $lang['tours_section_subtitle']; ?></p>
            </div>

            <div class="tour-categories animate-on-scroll">
                <button class="category-btn active" onclick="filterTours('all')"><?php echo $lang['tours_cat_all']; ?></button>
                <button class="category-btn" onclick="filterTours('cusco')"><?php echo $lang['tours_cat_cusco']; ?></button>
                <button class="category-btn" onclick="filterTours('aventura')"><?php echo $lang['tours_cat_adventure']; ?></button>
                <button class="category-btn" onclick="filterTours('multi-day')"><?php echo $lang['tours_cat_multiday']; ?></button>
            </div>

            <div class="tours-container stagger-animation" id="toursContainer">
                <?php if ($tours_result && $tours_result->num_rows > 0): ?>
                    <?php $tours_result->data_seek(0); ?>
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
                                    <a href="src/detalles.php?id_tour=<?php echo $tour['id_tour']; ?>" class="btn btn-primary">
                                        <i class="fas fa-info-circle"></i> <?php echo $lang['tour_card_details']; ?>
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
            <div class="section-header animate-on-scroll">
                <h2 class="section-title"><?php echo $current_lang == 'es' ? 'Guías Turísticos en Cusco' : 'Tour Guides in Cusco'; ?></h2>
                <p class="section-subtitle"><?php echo $lang['guides_section_subtitle']; ?></p>
            </div>

            <div class="guias-filters animate-on-scroll">
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

            <div class="guias-container stagger-animation" id="guiasContainer">
                <?php if ($guias_result && $guias_result->num_rows > 0): ?>
                    <?php while ($guia = $guias_result->fetch_assoc()): ?>
                        <?php $rating = floatval($guia['rating_promedio'] ?: 0); ?>
                        <div class="guia-card" data-rating="<?php echo $rating; ?>" data-estado="<?php echo strtolower($guia['estado']); ?>" data-idiomas="<?php echo $guia['idiomas'] ?: ''; ?>">
                            <img src="<?php echo htmlspecialchars($guia['foto_url'] ?: 'imagenes/default-guide.jpg'); ?>" 
                                 alt="<?php echo $current_lang == 'es' ? 'Guía turístico en Cusco ' . htmlspecialchars($guia['nombre']) : 'Cusco tour guide ' . htmlspecialchars($guia['nombre']); ?>" 
                                 class="guia-avatar" loading="lazy">
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
            <div class="section-header animate-on-scroll">
                <h2 class="section-title"><?php echo $current_lang == 'es' ? 'Experiencias de Turismo en Cusco' : 'Cusco Tourism Experiences'; ?></h2>
                <p class="section-subtitle"><?php echo $lang['experiences_section_subtitle']; ?></p>
            </div>

            <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 30px;" class="animate-on-scroll"><?php echo $lang['experiences_gallery_title']; ?></h3>
            <div class="photos-mural stagger-animation">
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
                            <img src="<?php echo htmlspecialchars($photo['imagen_url']); ?>" alt="<?php echo $current_lang == 'es' ? 'Foto de experiencia en Cusco' : 'Cusco experience photo'; ?>" loading="lazy">
                            <div class="photo-info">
                                <img src="<?php echo htmlspecialchars($photo['avatar_url'] ?: 'imagenes/default-avatar.png'); ?>" 
                                    alt="<?php echo $current_lang == 'es' ? 'Avatar de usuario' : 'User avatar'; ?>" class="small-avatar" loading="lazy">
                                <span><?php echo htmlspecialchars($photo['nombre'] ?: $lang['experiences_anonymous']); ?></span>
                                <span><?php echo date('d/m/Y', strtotime($photo['fecha_publicacion'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-light);"><?php echo $lang['experiences_photos_soon']; ?></p>
                <?php endif; ?>
            </div>

            <div class="carousel-container animate-on-scroll">
                <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 30px;"><?php echo $lang['experiences_comments_title']; ?></h3>
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
                                    <div class="experiencia-content">
                                        <div class="experiencia-user">
                                            <img src="<?php echo htmlspecialchars($comment['avatar_url'] ?: 'imagenes/default-avatar.png'); ?>" 
                                                 alt="<?php echo $current_lang == 'es' ? 'Avatar de usuario' : 'User avatar'; ?>" class="experiencia-avatar" loading="lazy">
                                            <div>
                                                <div class="experiencia-name"><?php echo htmlspecialchars($comment['nombre'] ?: $lang['experiences_anonymous_user']); ?></div>
                                                <div class="experiencia-date"><?php echo date('d/m/Y', strtotime($comment['fecha_publicacion'])); ?></div>
                                            </div>
                                        </div>
                                        <blockquote style="font-style: italic; font-size: 1.1rem; color: var(--text-light); border-left: 4px solid var(--primary-light); padding-left: 20px; margin: 0; line-height: 1.6;">
                                            "<?php echo htmlspecialchars($comment['comentario']); ?>"
                                        </blockquote>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="carousel-item active">
                            <div class="experiencia-card">
                                <div class="experiencia-content">
                                    <p style="color: var(--text-light); font-size: 1.1rem; text-align: center;">
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

            <div class="add-experiencia animate-on-scroll">
                <button onclick="toggleExperienciaForm()" class="btn btn-primary"><?php echo $lang['add_experience_button']; ?></button>
                <div id="experienciaFormContainer" style="display: none;">
                    <?php if (!$is_logged_in): ?>
                        <p style="margin: 20px 0; color: var(--text-light);"><?php echo $lang['add_experience_login_prompt']; ?></p>
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
                    <li><a href="#"><?php echo $lang['footer_legal_terms']; ?></a></li>
                    <li><a href="#"><?php echo $lang['footer_legal_privacy']; ?></a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p><?php echo $lang['footer_copyright']; ?></p>
        </div>
    </footer>

    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        let currentHeroImage = 0;
        let currentCommentIndex = 0;
        let isLoading = true;
        const heroImages = document.querySelectorAll('.hero-image');
        const heroIndicators = document.querySelectorAll('.hero-indicator');

        document.addEventListener('DOMContentLoaded', function() {
            initializeLoadingScreen();
            initializeHeroCarousel();
            initializeScrollAnimations();
            initializeNavbar();
            initializeGoogleSignin();
            filterGuias();
        });

        function initializeLoadingScreen() {
            setTimeout(() => {
                const loadingScreen = document.getElementById('loadingScreen');
                loadingScreen.classList.add('hidden');
                isLoading = false;
                
                setTimeout(() => {
                    triggerScrollAnimations();
                }, 500);
            }, 2000);
        }

        function initializeHeroCarousel() {

            setInterval(() => {
                if (!isLoading) {
                    currentHeroImage = (currentHeroImage + 1) % heroImages.length;
                    changeHeroImage(currentHeroImage);
                }
            }, 6000);
        }

        function changeHeroImage(index) {
            heroImages.forEach((img, i) => {
                img.classList.remove('active');
                if (i === index) {
                    setTimeout(() => img.classList.add('active'), 50);
                }
            });
            
            heroIndicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
            
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
                spans[0].style.transform = 'rotate(45deg) translate(6px, 6px)';
                spans[1].style.opacity = '0';
                spans[1].style.transform = 'translateX(20px)';
                spans[2].style.transform = 'rotate(-45deg) translate(8px, -7px)';
                googleSignin.classList.remove('active');
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[1].style.transform = 'none';
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
                spans[1].style.transform = 'none';
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
                        auto_select: true,
                        cancel_on_tap_outside: true
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

                    // Mostrar automáticamente el One Tap si el usuario no está logueado
                    <?php if (!isset($_SESSION['user_email'])): ?>
                    google.accounts.id.prompt((notification) => {
                        console.log('🔔 One Tap notification:', notification);
                        if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                            console.log('⚠️ One Tap no se mostró:', notification.getNotDisplayedReason());
                        }
                    });
                    <?php endif; ?>

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
            
            tourCards.forEach((card, index) => {
                card.style.transform = 'translateY(20px) scale(0.95)';
                card.style.opacity = '0';
                
                setTimeout(() => {
                    if (category === 'all' || card.dataset.category === category) {
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.transform = 'translateY(0) scale(1)';
                            card.style.opacity = '1';
                        }, 50 + (index * 50));
                    } else {
                        card.style.display = 'none';
                    }
                }, index * 50);
            });
        }

        function filterGuias() {
            const sortRating = document.getElementById('sortRating').value;
            const filterStatus = document.getElementById('filterStatus').value;
            const filterLanguage = document.getElementById('filterLanguage').value;
            const container = document.getElementById('guiasContainer');
            let cards = Array.from(document.querySelectorAll('.guia-card'));

            cards.forEach(card => {
                card.style.transform = 'translateY(20px) scale(0.95)';
                card.style.opacity = '0';
            });

            setTimeout(() => {
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
                cards.forEach((card, index) => {
                    container.appendChild(card);
                    setTimeout(() => {
                        card.style.transform = 'translateY(0) scale(1)';
                        card.style.opacity = '1';
                    }, index * 100);
                });
            }, 300);
        }

        function moveCarousel(carouselId, direction) {
            const carousel = document.getElementById(carouselId);
            const items = carousel.querySelectorAll('.carousel-item');
            const totalItems = items.length;
            
            if (totalItems === 0) return;
            
            currentCommentIndex = (currentCommentIndex + direction + totalItems) % totalItems;
            
            carousel.style.transition = 'transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            
            items.forEach(item => item.classList.remove('active'));
            items[currentCommentIndex].classList.add('active');
            
            const translateX = -currentCommentIndex * 100;
            carousel.style.transform = `translateX(${translateX}%)`;
        }

        setInterval(() => {
            const commentsCarousel = document.getElementById('commentsCarousel');
            if (commentsCarousel && commentsCarousel.querySelectorAll('.carousel-item').length > 1 && !isLoading) {
                moveCarousel('commentsCarousel', 1);
            }
        }, 10000);

        function toggleExperienciaForm() {
            const formContainer = document.getElementById('experienciaFormContainer');
            const isVisible = formContainer.style.display !== 'none';
            
            if (isVisible) {
                formContainer.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => {
                    formContainer.style.display = 'none';
                }, 300);
            } else {
                formContainer.style.display = 'block';
                formContainer.style.animation = 'slideIn 0.3s ease-out forwards';
            }
        }

        function initializeScrollAnimations() {
            const observerOptions = {
                threshold: 0.15,
                rootMargin: '0px 0px -100px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        
                        if (element.classList.contains('animate-on-scroll')) {
                            element.classList.add('animate');
                        }
                        
                        if (element.classList.contains('stagger-animation')) {
                            element.classList.add('animate');
                        }
                        
                        if (element.classList.contains('section-header')) {
                            element.classList.add('animate');
                        }
                        
                        if (element.classList.contains('tour-card')) {
                            setTimeout(() => {
                                element.classList.add('animate');
                            }, Math.random() * 200);
                        }
                        
                        if (element.classList.contains('guia-card')) {
                            setTimeout(() => {
                                element.classList.add('animate');
                            }, Math.random() * 300);
                        }
                        
                        if (element.classList.contains('photo-item')) {
                            setTimeout(() => {
                                element.classList.add('animate');
                            }, Math.random() * 150);
                        }
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.animate-on-scroll, .stagger-animation, .section-header, .tour-card, .guia-card, .photo-item').forEach(el => {
                observer.observe(el);
            });
        }

        function triggerScrollAnimations() {

            const elements = document.querySelectorAll('.animate-on-scroll, .stagger-animation, .section-header');
            elements.forEach((element, index) => {
                const rect = element.getBoundingClientRect();
                const isInView = rect.top < window.innerHeight && rect.bottom > 0;
                
                if (isInView) {
                    setTimeout(() => {
                        element.classList.add('animate');
                    }, index * 200);
                }
            });
        }

        function initializeNavbar() {
            const navbar = document.getElementById('navbar');
            let lastScrollY = window.scrollY;
            
            window.addEventListener('scroll', () => {
                const currentScrollY = window.scrollY;
                
                if (currentScrollY > 100) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
                
                if (currentScrollY > lastScrollY && currentScrollY > 200) {
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    navbar.style.transform = 'translateY(0)';
                }
                
                lastScrollY = currentScrollY;
            });
        }

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const headerOffset = 100;
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

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        `;
        document.head.appendChild(style);

        function throttle(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        window.changeHeroImage = changeHeroImage;
        window.toggleMobileMenu = toggleMobileMenu;
        window.toggleGoogleSignin = toggleGoogleSignin;
        window.filterTours = filterTours;
        window.filterGuias = filterGuias;
        window.moveCarousel = moveCarousel;
        window.toggleExperienciaForm = toggleExperienciaForm;
    </script>
</body>
</html>
