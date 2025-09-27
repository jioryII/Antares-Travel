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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://accounts.google.com">
    <link rel="preload" as="image" href="imagenes/fondo01.jpg">
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
    --text-medium: #444444;
    --text-light: #666;
    --text-subtle: #888;
    --white: #ffffff;
    --border-light: rgba(162, 119, 65, 0.08);
    --border-medium: rgba(162, 119, 65, 0.15);
    --shadow-light: 0 2px 12px rgba(162, 119, 65, 0.06);
    --shadow-medium: 0 4px 20px rgba(162, 119, 65, 0.1);
    --shadow-heavy: 0 8px 32px rgba(162, 119, 65, 0.15);
    --shadow-intense: 0 16px 48px rgba(162, 119, 65, 0.2);
    --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    --gradient-secondary: linear-gradient(135deg, var(--secondary-color), #6B8A8D);
    --gradient-hero: linear-gradient(135deg, rgba(139, 99, 50, 0.85), rgba(91, 121, 124, 0.75));
    --gradient-subtle: linear-gradient(135deg, rgba(162, 119, 65, 0.02), rgba(91, 121, 124, 0.01));
    --border-radius-sm: 6px;
    --border-radius: 12px;
    --border-radius-lg: 16px;
    --border-radius-xl: 24px;
    --transition-quick: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    --transition-smooth: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    --transition-slow: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    --elastic: cubic-bezier(0.175, 0.885, 0.32, 1.275);
    --bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 32px;
    --spacing-2xl: 48px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
    font-size: 16px;
}

body {
    font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    line-height: 1.7;
    color: var(--text-dark);
    background: var(--primary-bg);
    overflow-x: hidden;
    opacity: 0;
    animation: pageLoad 0.8s ease-out forwards;
    font-weight: 400;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
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
    background: 
        radial-gradient(circle at 20% 30%, rgba(162, 119, 65, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(139, 99, 50, 0.2) 0%, transparent 50%),
        linear-gradient(135deg, #8B6332 0%, #A27741 25%, #B8926A 50%,rgb(154, 142, 99) 75%, #8B6332 100%);
    background-size: 400% 400%, 300% 300%, 100% 100%;
    animation: incaGradient 1s ease-in-out infinite;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    transition: opacity 0.6s ease, visibility 0.6s ease;
    overflow: hidden;
}

.loading-screen::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        repeating-linear-gradient(
            45deg,
            transparent,
            transparent 2px,
            rgba(139, 99, 50, 0.08) 2px,
            rgba(139, 99, 50, 0.08) 4px
        ),
        repeating-linear-gradient(
            -45deg,
            transparent,
            transparent 3px,
            rgba(162, 119, 65, 0.06) 3px,
            rgba(162, 119, 65, 0.06) 6px
        );
    animation: stoneTexture 1s ease-in-out infinite;
}

.loading-screen::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 15% 15%, rgba(139, 99, 50, 0.3) 1px, transparent 1px),
        radial-gradient(circle at 85% 25%, rgba(184, 146, 106, 0.25) 1px, transparent 1px),
        radial-gradient(circle at 25% 85%, rgba(162, 119, 65, 0.2) 1px, transparent 1px),
        radial-gradient(circle at 75% 75%, rgba(91, 121, 124, 0.3) 1px, transparent 1px);
    background-size: 80px 80px, 60px 60px, 100px 100px, 70px 70px;
    animation: incaStones 1s ease-in-out infinite;
    opacity: 0.5;
}

.loading-screen.hidden {
    opacity: 0;
    visibility: hidden;
}

.loading-content {
    text-align: center;
    color: #FFFAF0;
    position: relative;
    z-index: 10;
    filter: drop-shadow(0 0 8px rgba(162, 119, 65, 0.4));
}

.loading-spinner {
    width: 64px;
    height: 64px;
    position: relative;
    margin: 0 auto 24px;
}

.loading-spinner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-top: 3px solid #A27741;
    border-right: 3px solid #8B6332;
    border-radius: 50%;
    animation: incaSpin 2.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) infinite;
    filter: drop-shadow(0 0 12px rgba(162, 119, 65, 0.5));
}

.loading-spinner::after {
    content: '';
    position: absolute;
    top: 6px;
    left: 6px;
    width: calc(100% - 12px);
    height: calc(100% - 12px);
    border: 2px solid transparent;
    border-bottom: 2px solid #B8926A;
    border-left: 2px solid #5B797C;
    border-radius: 50%;
    animation: incaSpinReverse 2s ease-in-out infinite;
}

.inca-pattern {
    position: absolute;
    width: 96px;
    height: 96px;
    border: 2px solid rgba(162, 119, 65, 0.3);
    border-radius: 0;
    transform: rotate(45deg);
    animation: incaFloat 6s ease-in-out infinite;
}

.inca-pattern::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 60%;
    height: 60%;
    border: 1px solid rgba(139, 99, 50, 0.4);
    transform: translate(-50%, -50%) rotate(45deg);
    animation: incaPulse 3s ease-in-out infinite;
}

.inca-pattern::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 30%;
    height: 30%;
    background: rgba(91, 121, 124, 0.5);
    transform: translate(-50%, -50%) rotate(45deg);
    animation: incaGlow 5s ease-in-out infinite;
}

.loading-text {
    font-family: 'Georgia', serif;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    background: linear-gradient(
        45deg,
        #A27741 0%,
        #FFFAF0 25%,
        #8B6332 50%,
        #B8926A 75%,
        #A27741 100%
    );
    background-size: 200% 200%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: textWeave 4s ease-in-out infinite, textGlow 2.5s ease-in-out infinite alternate;
    margin-top: 8px;
}

@keyframes incaGradient {
    0%, 100% {
        background-position: 0% 50%, 0% 50%, 0% 0%;
    }
    25% {
        background-position: 100% 50%, 50% 100%, 0% 0%;
    }
    50% {
        background-position: 100% 100%, 100% 0%, 0% 0%;
    }
    75% {
        background-position: 0% 100%, 50% 50%, 0% 0%;
    }
}

@keyframes stoneTexture {
    0% {
        transform: translateX(0) translateY(0);
    }
    25% {
        transform: translateX(-3px) translateY(2px);
    }
    50% {
        transform: translateX(2px) translateY(-3px);
    }
    75% {
        transform: translateX(-2px) translateY(3px);
    }
    100% {
        transform: translateX(0) translateY(0);
    }
}

@keyframes incaStones {
    0% {
        background-position: 0% 0%, 0% 0%, 0% 0%, 0% 0%;
    }
    100% {
        background-position: 80px 80px, -60px 60px, 100px -100px, -70px 70px;
    }
}

@keyframes incaSpin {
    0% {
        transform: rotate(0deg) scale(1);
        border-top-color: #A27741;
        border-right-color: #8B6332;
    }
    25% {
        transform: rotate(90deg) scale(1.01);
        border-top-color: #8B6332;
        border-right-color: #B8926A;
    }
    50% {
        transform: rotate(180deg) scale(1);
        border-top-color: #B8926A;
        border-right-color: #5B797C;
    }
    75% {
        transform: rotate(270deg) scale(1.01);
        border-top-color: #5B797C;
        border-right-color: #A27741;
    }
    100% {
        transform: rotate(360deg) scale(1);
        border-top-color: #A27741;
        border-right-color: #8B6332;
    }
}

@keyframes incaSpinReverse {
    0% {
        transform: rotate(0deg);
        border-bottom-color: #B8926A;
        border-left-color: #5B797C;
    }
    50% {
        border-bottom-color: #5B797C;
        border-left-color: #A27741;
    }
    100% {
        transform: rotate(-360deg);
        border-bottom-color: #B8926A;
        border-left-color: #5B797C;
    }
}

@keyframes incaFloat {
    0%, 100% {
        transform: rotate(45deg) translateY(0px) scale(1);
        opacity: 0.4;
    }
    50% {
        transform: rotate(45deg) translateY(-12px) scale(1.03);
        opacity: 0.7;
    }
}

@keyframes incaPulse {
    0%, 100% {
        transform: translate(-50%, -50%) rotate(45deg) scale(1);
        border-color: rgba(139, 99, 50, 0.3);
    }
    50% {
        transform: translate(-50%, -50%) rotate(45deg) scale(1.1);
        border-color: rgba(162, 119, 65, 0.6);
    }
}

@keyframes incaGlow {
    0%, 100% {
        background: rgba(91, 121, 124, 0.4);
        transform: translate(-50%, -50%) rotate(45deg) scale(1);
    }
    50% {
        background: rgba(162, 119, 65, 0.7);
        transform: translate(-50%, -50%) rotate(45deg) scale(1.15);
    }
}

@keyframes textWeave {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes textGlow {
    0% {
        filter: drop-shadow(0 0 4px rgba(162, 119, 65, 0.4));
    }
    100% {
        filter: drop-shadow(0 0 16px rgba(162, 119, 65, 0.7));
    }
}

.loading-particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    overflow: hidden;
}

.particle {
    position: absolute;
    width: 3px;
    height: 3px;
    background: rgba(162, 119, 65, 0.5);
    border-radius: 50%;
    animation: particleFloat 10s ease-in-out infinite;
}

.particle:nth-child(odd) {
    background: rgba(184, 146, 106, 0.4);
    animation-duration: 8s;
    animation-delay: -1.5s;
}

.particle:nth-child(3n) {
    background: rgba(91, 121, 124, 0.3);
    animation-duration: 12s;
    animation-delay: -3s;
}

@keyframes particleFloat {
    0% {
        transform: translateY(100vh) translateX(0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 0.5;
    }
    90% {
        opacity: 0.5;
    }
    100% {
        transform: translateY(-80px) translateX(40px) rotate(180deg);
        opacity: 0;
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}


@keyframes slideDown {
    to {
        transform: translateY(0);
    }
}

.navbar {
    position: fixed;
    top: 0;
    width: 100%;
    background: transparent;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    z-index: 1000;
    padding: 16px 0;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border-bottom: 1px solid transparent;
    transform: translateY(-100%);
    animation: slideDown 1s var(--elastic) 0.4s forwards;
}

.navbar.scrolled {
    background: rgba(255, 250, 240, 0.98);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    box-shadow: var(--shadow-light);
    padding: 12px 0;
    border-bottom-color: var(--border-light);
}
.navbar .logo span {
    color:rgb(255, 223, 153); 
    text-shadow: 1px 1px 3px rgba(0,0,0,0.4); 
    transition: var(--transition-smooth);
}
.navbar.scrolled .logo span {
    color: var(--primary-color); 
    text-shadow: none;
}
.navbar .logo:hover span {
    color:rgb(255, 233, 185); 
}

.navbar.scrolled .logo:hover span {
    color: var(--primary-dark); 
}
.logo {
    display: flex;
    align-items: center;
    font-size: 20px;
    font-weight: 700;
    color: #F5F5DC;
    text-decoration: none;
    gap: var(--spacing-sm);
    transition: var(--transition-smooth);
    text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
}

.navbar.scrolled .logo {
    color: var(--primary-color);
    text-shadow: none;
}

.nav-links a {
    color: #F5F5DC;
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    transition: var(--transition-smooth);
    position: relative;
    padding: var(--spacing-sm) 0;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.navbar.scrolled .nav-links a {
    color: var(--text-medium);
    text-shadow: none;
}

.nav-links a::before {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    width: 0;
    height: 2px;
    background:rgb(245, 220, 220); 
    transition: var(--transition-smooth);
    transform: translateX(-50%);
    border-radius: 1px;
    box-shadow: 0 0 4px rgba(245, 245, 220, 0.5);
}

.navbar.scrolled .nav-links a::before {
    background: var(--gradient-primary);
    box-shadow: none;
}

.nav-links a:hover {
    color: #FFFAF0; 
    transform: translateY(-1px);
}

.navbar.scrolled .nav-links a:hover {
    color: var(--primary-color);
}

.nav-links a:hover::before {
    width: 100%;
}

.lang-switch {
    display: flex;
    border: 1.5px solid rgba(245, 245, 220, 0.4);
    border-radius: 32px;
    overflow: hidden;
    transition: var(--transition-smooth);
    background: rgba(238, 3, 3, 0.77);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.navbar.scrolled .lang-switch {
    border-color: var(--border-medium);
    background: var(--white);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.lang-btn {
    padding: 8px 14px;
    text-decoration: none;
    background: transparent;
    color: #F5F5DC; 
    cursor: pointer;
    transition: var(--transition-smooth);
    font-weight: 600;
    font-size: 13px;
    position: relative;
    overflow: hidden;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.navbar.scrolled .lang-btn {
    color: var(--primary-color);
    text-shadow: none;
}

.lang-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(245, 245, 220, 0.2);
    transition: var(--transition-smooth);
    z-index: -1;
}

.navbar.scrolled .lang-btn::before {
    background: var(--gradient-primary);
}

.lang-btn:hover::before,
.lang-btn.active::before {
    left: 0;
}

.lang-btn.active,
.lang-btn:hover {
    color: #FFFAF0; 
}

.navbar.scrolled .lang-btn.active,
.navbar.scrolled .lang-btn:hover {
    color: var(--white);
}


#cart-icon, #cart-icon-mobile {
    position: relative !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 12px !important;
    border-radius: 50% !important;
    background: rgba(245, 245, 220, 0.15) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border: 2px solid rgba(245, 245, 220, 0.3) !important;
    transition: var(--transition-smooth) !important;
    color: #F5F5DC !important; 
    font-size: 18px !important;
    width: 44px !important;
    height: 44px !important;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2) !important;
    z-index: 1 !important;
    isolation: isolate !important;
}

.navbar.scrolled #cart-icon, 
.navbar.scrolled #cart-icon-mobile {
    background: transparent !important;
    backdrop-filter: none !important;  
    -webkit-backdrop-filter: none !important;
    border-color: var(--border-medium) !important;
    color: var(--text-medium) !important;
    text-shadow: none !important;
}
#cart-icon:hover, #cart-icon-mobile:hover {
    background: rgba(245, 245, 220, 0.25) !important;
    transform: scale(1.08) !important;
    color: #FFFAF0 !important;
    border-color: rgba(245, 245, 220, 0.5) !important;
    box-shadow: 0 4px 16px rgba(245, 245, 220, 0.2) !important;
}

.navbar.scrolled #cart-icon:hover, 
.navbar.scrolled #cart-icon-mobile:hover {
    background: rgba(162, 119, 65, 0.08) !important;
    color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    box-shadow: var(--shadow-light) !important;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    position: relative;
    padding: var(--spacing-xs);
    border-radius: 32px;
    transition: var(--transition-smooth);
    background: rgb(255, 255, 255);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(245, 245, 220, 0.2);
}

.navbar.scrolled .user-profile {
    background: transparent;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    border-color: transparent;
}

.user-profile:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.navbar.scrolled .user-profile:hover {
    background: rgba(162, 65, 65, 0.08);
}

.user-profile span {
    font-size: 14px;
    font-weight: 500;
    color: #F5F5DC;
    transition: var(--transition-smooth);
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.navbar.scrolled .user-profile span {
    color: var(--text-medium);
    text-shadow: none;
}

.user-profile:hover span {
    color: #FFFAF0;
}

.navbar.scrolled .user-profile:hover span {
    color: var(--primary-color);
}

.navbar .btn-primary {
    background: rgba(245, 245, 220, 0.2);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #F5F5DC;
    box-shadow: 0 4px 20px rgba(245, 245, 220, 0.15);
    border: 1px solid rgba(245, 245, 220, 0.2);
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.navbar.scrolled .btn-primary {
    background: var(--gradient-primary);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    box-shadow: var(--shadow-light);
    border: none;
    color: var(--white);
    text-shadow: none;
}

.navbar .btn-secondary {
    background: rgba(245, 245, 220, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #F5F5DC;
    border: 1.5px solid rgba(245, 245, 220, 0.3);
    position: relative;
    z-index: 1;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.navbar.scrolled .btn-secondary {
    background: transparent;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    color: var(--primary-color);
    border-color: var(--border-medium);
    text-shadow: none;
}

.navbar .btn-secondary::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 100%;
    background: rgba(245, 245, 220, 0.25);
    transition: var(--transition-smooth);
    z-index: -1;
    border-radius: 32px;
}

.navbar.scrolled .btn-secondary::after {
    background: var(--gradient-primary);
}

.navbar .btn-secondary:hover::after {
    width: 100%;
}

.navbar .btn-secondary:hover {
    color: #FFFAF0;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(245, 245, 220, 0.2);
}

.navbar.scrolled .btn-secondary:hover {
    color: var(--white);
    box-shadow: var(--shadow-medium);
}
@media (max-width: 768px) {
    .navbar {
        padding: 12px 0;
    }

    .navbar.scrolled {
        padding: 8px 0;
    }
}
.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 var(--spacing-xl);
}

.logo {
    display: flex;
    align-items: center;
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
    text-decoration: none;
    gap: var(--spacing-sm);
    transition: var(--transition-smooth);
}

.logo:hover {
    transform: scale(1.02);
}

.logo img {
    transition: var(--transition-smooth);
    border-radius: var(--border-radius-sm);
}

.logo:hover img {
    transform: rotate(3deg);
}

.nav-links {
    display: flex;
    list-style: none;
    gap: var(--spacing-2xl);
}

.nav-links li {
    position: relative;
}

.nav-links a {
    color: #F5F5DC; 
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    transition: var(--transition-smooth);
    position: relative;
    padding: var(--spacing-sm) 0;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.nav-links a:hover {
    color: var(--primary-color);
    transform: translateY(-1px);
}

.nav-links a::before {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 50%;
    width: 0;
    height: 2px;
    background: var(--gradient-primary);
    transition: var(--transition-smooth);
    transform: translateX(-50%);
    border-radius: 1px;
}

.nav-links a:hover::before {
    width: 100%;
}

.auth-buttons {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    position: relative;
}

.lang-switch {
    display: flex;
    border: 1.5px solid var(--border-medium);
    border-radius: 32px;
    overflow: hidden;
    transition: var(--transition-smooth);
    background: var(--white);
}

.lang-switch:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-light);
    border-color: var(--primary-color);
}

.lang-btn {
    padding: 8px 14px;
    text-decoration: none;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    transition: var(--transition-smooth);
    font-weight: 600;
    font-size: 13px;
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
    gap: var(--spacing-sm);
    padding: 10px 20px;
    border: none;
    border-radius: 32px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: var(--transition-smooth);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transform: perspective(1px) translateZ(0);
    white-space: nowrap;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    transition: var(--transition-quick);
}

.btn:hover::before {
    left: 100%;
    transition: var(--transition-quick);
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
    border: 1.5px solid var(--border-medium);
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
    border-radius: 32px;
}

.btn-secondary:hover::after {
    width: 100%;
}

.btn-secondary:hover {
    color: var(--white);
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    position: relative;
    padding: var(--spacing-xs);
    border-radius: 32px;
    transition: var(--transition-smooth);
    background: rgba(245, 245, 220, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(245, 245, 220, 0.2);
}

.user-profile:hover {
    background: rgba(162, 119, 65, 0.08);
    transform: translateY(-1px);
}

.user-profile img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid var(--primary-light);
    object-fit: cover;
    transition: var(--transition-smooth);
}

.user-profile:hover img {
    transform: scale(1.05);
    box-shadow: var(--shadow-light);
}

.user-profile span {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-medium);
    transition: var(--transition-smooth);
}

.user-profile:hover span {
    color: var(--primary-color);
}

.user-profile .logout-btn {
    padding: var(--spacing-xs);
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

.hero-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transform: scale(1.05);
    transition: opacity 1.8s cubic-bezier(0.4, 0, 0.2, 1), 
                transform 10s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.hero-image.active {
    opacity: 1;
    transform: scale(1.15);
}

.hero-image.fade-out {
    opacity: 0;
    transform: scale(1.1);
}

.hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--gradient-hero);
    transition: opacity 0.8s ease;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-xl);
    position: relative;
    z-index: 2;
}

.hero-content {
    text-align: center;
    color: var(--white);
    max-width: 680px;
    margin: 0 auto;
    opacity: 0;
    transform: translateY(60px) scale(0.95);
    animation: heroContentSlide 1.5s var(--elastic) 1.2s forwards;
}

@keyframes heroContentSlide {
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.hero-content h1 {
    font-size: clamp(2.2rem, 4.5vw, 3.4rem);
    margin-bottom: var(--spacing-lg);
    font-weight: 700;
    text-shadow: 2px 4px 12px rgba(0,0,0,0.3);
    line-height: 1.2;
    letter-spacing: -0.5px;
}

.hero-content p {
    font-size: clamp(1rem, 2.2vw, 1.2rem);
    margin-bottom: var(--spacing-2xl);
    opacity: 0.95;
    text-shadow: 1px 2px 6px rgba(0,0,0,0.25);
    line-height: 1.6;
    font-weight: 400;
}

.hero-buttons {
    display: flex;
    gap: var(--spacing-lg);
    justify-content: center;
    flex-wrap: wrap;
}

.hero-buttons .btn {
    transform: translateY(40px) scale(0.9);
    opacity: 0;
    animation: buttonSlide 1s var(--bounce) forwards;
    padding: 12px 24px;
    font-size: 15px;
}

.hero-buttons .btn:nth-child(1) {
    animation-delay: 1.8s;
}

.hero-buttons .btn:nth-child(2) {
    animation-delay: 2s;
}

@keyframes buttonSlide {
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.hero-indicators {
    position: absolute;
    bottom: var(--spacing-xl);
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: var(--spacing-sm);
    z-index: 3;
    opacity: 0;
    animation: indicatorsSlide 0.8s ease 2.2s forwards;
}

@keyframes indicatorsSlide {
    to {
        opacity: 1;
    }
}

.hero-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
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
    transform: scale(1.2);
}

.section {
    padding: 80px 0;
    position: relative;
}

.section-header {
    text-align: center;
    margin-bottom: 64px;
    opacity: 0;
    transform: translateY(60px) scale(0.95);
    transition: var(--transition-slow);
}

.section-header.animate {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.section-title {
    font-size: clamp(1.8rem, 3.5vw, 2.4rem);
    color: var(--primary-color);
    margin-bottom: var(--spacing-lg);
    position: relative;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 3px;
    background: var(--gradient-primary);
    border-radius: 2px;
    animation: underlineGrow 1.2s var(--bounce) 0.6s forwards;
}

@keyframes underlineGrow {
    to {
        width: 64px;
    }
}

.section-subtitle {
    font-size: 16px;
    color: var(--text-light);
    max-width: 560px;
    margin: 0 auto;
    line-height: 1.7;
    font-weight: 400;
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
    height: 80px;
    background: linear-gradient(to bottom, transparent, rgba(162, 119, 65, 0.02));
    pointer-events: none;
}

.tour-categories {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 48px;
    gap: var(--spacing-md);
}

.category-btn {
    padding: 10px 20px;
    background: var(--white);
    border: 1.5px solid var(--border-medium);
    border-radius: 32px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
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
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
    border-color: transparent;
}

.tours-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-xl);
    perspective: 1000px;
}


.tour-card {
    box-shadow: 0 4px 16px rgb(0, 0, 0);
    background: #FBF4E8;
    border-radius: 20px;
    overflow: hidden;
    transition: var(--transition-slow);
    opacity: 0;
    transform: translateY(80px) rotateX(10deg) scale(0.9);
    position: relative;
    height: 500px;
    display: flex;
    flex-direction: column;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border: 1px solidrgba(248, 242, 232, 0.34);
    isolation: isolate;
}

.tour-card.animate {
    opacity: 1;
    transform: translateY(0) rotateX(0) scale(1);
}

.tour-card {
    box-shadow: 0 2px 8px rgba(128, 128, 128, 0.24), 
                0 1px 3px rgba(128, 128, 128, 0.23); 
}
.tour-card:hover {
    transform: none;
}
.tour-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        to bottom,
        transparent 0%,
        transparent 30%,
        rgba(248, 242, 232, 0.05) 35%,
        rgba(248, 242, 232, 0.15) 40%,
        rgba(248, 242, 232, 0.35) 45%,
        rgba(248, 242, 232, 0.55) 50%,
        rgba(248, 242, 232, 0.75) 55%,
        rgba(248, 242, 232, 0.9) 60%,
        rgb(248, 242, 232) 65%
    );
    pointer-events: none;
    z-index: 1;
    border-radius: var(--border-radius-lg);
}
.tour-card:hover::before {
    background: linear-gradient(
        to bottom,
        transparent 0%,
        transparent 30%,
        rgba(248, 242, 232, 0.02) 35%,
        rgba(248, 242, 232, 0.1) 40%,
        rgba(248, 242, 232, 0.25) 45%,
        rgba(248, 242, 232, 0.45) 50%,
        rgba(248, 242, 232, 0.65) 55%,
        rgba(248, 242, 232, 0.85) 60%,
        rgb(248, 242, 232) 65%
    );
}
.tour-image {
    display: none;
}

.tour-image::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px; 
    background: linear-gradient(
        to bottom, 
        rgba(162, 119, 65, 0) 0%,           
        rgba(162, 119, 65, 0.02) 30%,     
        rgba(162, 119, 65, 0.05) 70%,      
        rgba(162, 119, 65, 0.08) 100%      
    );
}



.tour-header {
    padding: var(--spacing-lg);
    background: var(--gradient-primary);
    color: var(--white);
    position: relative;
}

.tour-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--primary-dark);
    margin: 0 0 var(--spacing-md) 0;
    line-height: 1.3;
    letter-spacing: -0.2px;
    height: 48px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-shadow: 0 4px 6px rgba(255, 255, 255, 0.9);
    position: relative;
    z-index: 3;
    flex-shrink: 0;
}
.tour-info {
    flex: 1;
    max-height: none; 
    overflow: visible; 
    margin-bottom: var(--spacing-md);
    display: flex;
    flex-direction: column;
}
.tour-info-fixed {
    flex-shrink: 0; 
    margin-bottom: var(--spacing-sm);
}
.tour-info-variable {
    flex: 1;
    overflow: hidden;
    max-height: 60px;
}
.tour-info-item.duration,
.tour-info-item.schedule {
    flex-shrink: 0; 
    margin-bottom: var(--spacing-xs);
}
.tour-info-item.description {
    max-height: 40px;
    overflow: hidden;
}

.tour-info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: var(--spacing-xs);
    font-size: 14px;
    color: var(--text-medium);
    line-height: 1.3;
    min-height: 20px;
    text-shadow: 0 1px 1px rgba(255, 255, 255, 0.6);
}
.tour-info-item i {
    color: var(--primary-color);
    margin-right: var(--spacing-sm);
    font-size: 13px;
    margin-top: 2px;
    width: 16px;
    flex-shrink: 0;
}

.tour-schedule {
    font-size: 13px;
    opacity: 0.9;
    font-weight: 400;
}
.tour-includes {
    color: var(--text-light);
    font-size: 13px;
    line-height: 1.4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.tour-duration,
.tour-schedule {
    color: var(--text-medium);
    font-weight: 500;
    white-space: nowrap;
    overflow: visible;
}

.tour-content {
    flex: 1;
    padding: var(--spacing-lg);
    background: transparent;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
    z-index: 2;
    margin-top: 200px;
    min-height: 240px;
}


.tour-content::before {
    display: none;
}
.tour-title,
.tour-info,
.tour-actions {
    position: relative;
    z-index: 3;
}

.tour-details {
    margin: var(--spacing-md) 0;
    font-size: 14px;
    color: var(--text-light);
}

.tour-details div {
    margin-bottom: var(--spacing-sm);
    display: flex;
    align-items: center;
    transition: var(--transition-smooth);
}

.tour-details div:hover i {
    transform: scale(1.2) rotate(8deg);
}

.tour-actions {
    text-align: center;
    margin-top: auto; 
    padding-top: var(--spacing-sm);
    flex-shrink: 0; 
}

.tour-actions .btn::before {
    display: none; 
}

.tour-actions .btn {
    background: var(--gradient-primary);
    color: var(--white);
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-sm);
    transition: all 0.3s ease; 
    box-shadow: 0 2px 12px rgba(162, 119, 65, 0.2);
    position: relative;
    overflow: hidden;
}
.tour-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgb(235, 163, 74);
    background: var(--primary-dark);
}
.tour-actions .btn i {
    font-size: 12px;
}
.guias-section {
    background: #F4EBDC;
    position: relative;
}

.guias-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 80px;
    background: linear-gradient(to bottom, rgba(162, 119, 65, 0.02), transparent);
    pointer-events: none;
}

.guias-filters {
    display: flex;
    justify-content: center;
    gap: var(--spacing-lg);
    margin-bottom: 48px;
    flex-wrap: wrap;
}

.guias-filters select {
    padding: 10px 18px;
    border: 1.5px solid var(--border-medium);
    border-radius: 32px;
    background: var(--white);
    cursor: pointer;
    font-size: 14px;
    color: var(--text-medium);
    transition: var(--transition-smooth);
    box-shadow: var(--shadow-light);
    font-weight: 500;
}

.guias-filters select:hover,
.guias-filters select:focus {
    border-color: var(--primary-color);
    box-shadow: var(--shadow-medium);
    transform: translateY(-1px);
    outline: none;
}

.guias-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-xl);
}

.guia-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-2xl);
    text-align: center;
    box-shadow: var(--shadow-light);
    transition: var(--transition-slow);
    opacity: 0;
    transform: translateY(60px) scale(0.9) rotateY(8deg);
    position: relative;
    overflow: hidden;
    border: 1px solid var(--border-light);
}

.guia-card.animate {
    opacity: 1;
    transform: translateY(0) scale(1) rotateY(0);
}

.guia-card:hover {
    transform: translateY(-10px) scale(1.01) rotateY(-1deg);
    box-shadow: var(--shadow-heavy);
}

.guia-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--gradient-primary);
    transform: scaleX(0);
    transition: var(--transition-smooth);
}

.guia-card:hover::before {
    transform: scaleX(1);
}

.guia-avatar {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    margin: 0 auto var(--spacing-lg);
    border: 3px solid var(--primary-light);
    object-fit: cover;
    transition: var(--transition-smooth);
    position: relative;
}

.guia-avatar::before {
    content: '';
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    border-radius: 50%;
    background: var(--gradient-primary);
    opacity: 0;
    transition: var(--transition-smooth);
    z-index: -1;
}

.guia-card:hover .guia-avatar {
    transform: scale(1.08) rotate(3deg);
}

.guia-card:hover .guia-avatar::before {
    opacity: 1;
    animation: pulse 1.8s infinite;
}

@keyframes pulse {
    0%, 100% { 
        transform: scale(1) rotate(-3deg); 
        opacity: 0.6; 
    }
    50% { 
        transform: scale(1.03) rotate(3deg); 
        opacity: 0.2; 
    }
}

.guia-name {
    font-size: 18px;
    color: var(--primary-color);
    margin-bottom: var(--spacing-sm);
    font-weight: 600;
    letter-spacing: -0.2px;
}

.guia-rating {
    display: flex;
    justify-content: center;
    gap: 3px;
    margin-bottom: var(--spacing-md);
    align-items: center;
}

.guia-rating .star {
    color: #ffd700;
    transition: var(--transition-smooth);
    font-size: 14px;
}

.guia-card:hover .guia-rating .star {
    animation: starTwinkle 1.2s ease-in-out infinite;
}

@keyframes starTwinkle {
    0%, 100% { 
        transform: scale(1) rotate(0deg); 
    }
    50% { 
        transform: scale(1.15) rotate(8deg); 
    }
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
    height: 80px;
    background: linear-gradient(to bottom, transparent, rgba(162, 119, 65, 0.02));
    pointer-events: none;
}

.photos-mural {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: 48px;
}

.photo-item {
    position: relative;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-light);
    transition: var(--transition-slow);
    opacity: 0;
    transform: translateY(50px) scale(0.95) rotateZ(2deg);
    border: 1px solid var(--border-light);
}

.photo-item.animate {
    opacity: 1;
    transform: translateY(0) scale(1) rotateZ(0);
}

.photo-item img {
    width: 100%;
    height: 280px;
    object-fit: cover;
    display: block;
    transition: var(--transition-slow);
}

.photo-item:hover {
    transform: translateY(-10px) scale(1.01) rotateZ(-1deg);
    box-shadow: var(--shadow-heavy);
}

.photo-item:hover img {
    transform: scale(1.08) rotate(1deg);
}

.photo-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.75), transparent);
    padding: var(--spacing-lg);
    color: var(--white);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    transform: translateY(12px);
    transition: var(--transition-smooth);
}

.photo-item:hover .photo-info {
    transform: translateY(0);
}

.photo-info .small-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid var(--white);
    transition: var(--transition-smooth);
}

.photo-item:hover .photo-info .small-avatar {
    transform: scale(1.08) rotate(-3deg);
}

.carousel-container {
    position: relative;
    overflow: hidden;
    border-radius: var(--border-radius-lg);
    margin-bottom: var(--spacing-2xl);
}

.carousel {
    display: flex;
    transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.carousel-item {
    flex: 0 0 100%;
    position: relative;
}

.experiencia-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-medium);
    margin: 0 var(--spacing-md);
    transition: var(--transition-smooth);
    border: 1px solid var(--border-light);
}

.experiencia-card:hover {
    transform: translateY(-6px) scale(1.01);
    box-shadow: var(--shadow-heavy);
}

.experiencia-content {
    padding: var(--spacing-2xl);
}

.experiencia-user {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.experiencia-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2.5px solid var(--primary-light);
    transition: var(--transition-smooth);
}

.experiencia-card:hover .experiencia-avatar {
    transform: scale(1.1) rotate(8deg);
}

.experiencia-name {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 16px;
    letter-spacing: -0.2px;
}

.carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.96);
    border: none;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition-smooth);
    color: var(--primary-color);
    font-size: 16px;
    box-shadow: var(--shadow-light);
    z-index: 10;
    border: 1px solid var(--border-light);
}

.carousel-nav:hover {
    background: var(--white);
    box-shadow: var(--shadow-medium);
    transform: translateY(-50%) scale(1.1) rotate(8deg);
}

.carousel-nav.prev {
    left: var(--spacing-md);
}

.carousel-nav.next {
    right: var(--spacing-md);
}

.add-experiencia {
    max-width: 580px;
    margin: 48px auto 0;
    text-align: center;
}

.add-experiencia form {
    background: var(--white);
    padding: var(--spacing-2xl);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-medium);
    margin-top: var(--spacing-lg);
    transition: var(--transition-smooth);
    border: 1px solid var(--border-light);
}

.add-experiencia form:hover {
    box-shadow: var(--shadow-heavy);
    transform: translateY(-4px);
}

.add-experiencia textarea {
    width: 100%;
    min-height: 100px;
    padding: var(--spacing-md);
    border: 1.5px solid var(--border-medium);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-md);
    font-family: inherit;
    resize: vertical;
    transition: var(--transition-smooth);
    font-size: 14px;
    line-height: 1.6;
}

.add-experiencia textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(162, 119, 65, 0.08);
    transform: scale(1.01);
}

.add-experiencia input[type="file"] {
    margin-bottom: var(--spacing-md);
    padding: var(--spacing-sm);
    border: 2px dashed var(--border-medium);
    border-radius: var(--border-radius);
    width: 100%;
    transition: var(--transition-smooth);
}

.add-experiencia input[type="file"]:hover {
    border-color: var(--primary-color);
    background: rgba(162, 119, 65, 0.04);
    transform: scale(1.01);
}

.footer {
    background: var(--primary-dark);
    color: var(--white);
    padding: 64px 0 var(--spacing-xl);
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
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: var(--spacing-2xl);
    padding: 0 var(--spacing-xl);
}

.footer-section h3 {
    color: var(--primary-light);
    margin-bottom: var(--spacing-lg);
    font-size: 18px;
    font-weight: 600;
    letter-spacing: -0.2px;
}

.footer-section p, .footer-section li {
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: var(--spacing-sm);
    line-height: 1.6;
    transition: var(--transition-smooth);
    font-size: 14px;
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
    transform: translateX(6px);
}

.footer-section a::before {
    content: '';
    position: absolute;
    bottom: -1px;
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
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

.social-link {
    width: 40px;
    height: 40px;
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
    transform: translateY(-4px) scale(1.1) rotate(8deg);
    box-shadow: var(--shadow-medium);
}

.social-link i {
    position: relative;
    z-index: 1;
    font-size: 16px;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    padding-top: var(--spacing-lg);
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    margin-top: var(--spacing-2xl);
    font-size: 14px;
}

.whatsapp-button {
    position: fixed;
    bottom: var(--spacing-lg);
    right: var(--spacing-lg);
    background: var(--gradient-secondary);
    border-radius: 50%;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    transition: var(--transition-smooth);
    box-shadow: var(--shadow-medium);
    animation: whatsappPulse 2.5s infinite;
}

@keyframes whatsappPulse {
    0%, 100% { 
        box-shadow: var(--shadow-medium), 0 0 0 0 rgba(37, 211, 102, 0.6);
        transform: scale(1);
    }
    50% { 
        box-shadow: var(--shadow-heavy), 0 0 0 12px rgba(37, 211, 102, 0);
        transform: scale(1.03);
    }
}

.whatsapp-button:hover {
    background: #128C7E;
    transform: scale(1.15) rotate(8deg);
    animation: none;
    box-shadow: var(--shadow-heavy);
}

.whatsapp-button i {
    color: var(--white);
    font-size: 26px;
    transition: var(--transition-smooth);
}

.whatsapp-button:hover i {
    transform: scale(1.08) rotate(-8deg);
}

.mobile-menu {
    display: none;
    flex-direction: column;
    cursor: pointer;
    gap: 3px;
    z-index: 1001;
    padding: var(--spacing-sm);
    transition: var(--transition-smooth);
}

.mobile-menu:hover {
    transform: scale(1.05);
}

.mobile-menu span {
    width: 24px;
    height: 2.5px;
    background: var(--primary-color);
    transition: var(--transition-smooth);
    border-radius: 2px;
}

.mobile-nav {
    position: fixed;
    top: 64px;
    right: -100%;
    width: 100%;
    max-width: 320px;
    height: calc(100vh - 64px);
    background: var(--white);
    box-shadow: var(--shadow-heavy);
    transition: var(--transition-slow);
    z-index: 999;
    padding: var(--spacing-2xl);
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
    border-left: 1px solid var(--border-light);
}

.mobile-nav.active {
    right: 0;
}

.mobile-nav a {
    color: var(--text-medium);
    text-decoration: none;
    padding: var(--spacing-md) 0;
    border-bottom: 1px solid var(--border-light);
    font-weight: 500;
    font-size: 15px;
    transition: var(--transition-smooth);
    position: relative;
}

.mobile-nav a:hover {
    color: var(--primary-color);
    transform: translateX(12px) scale(1.01);
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
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

.google-signin-container {
    position: fixed;
    top: 72px;
    right: var(--spacing-lg);
    background: var(--white);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-heavy);
    z-index: 1001;
    display: none;
    transition: var(--transition-smooth);
    border: 1px solid var(--border-light);
}

.google-signin-container.active {
    display: block;
    animation: slideInDown 0.4s var(--elastic);
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-24px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.google-signin-container .close-btn {
    position: absolute;
    top: var(--spacing-sm);
    right: var(--spacing-sm);
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: var(--text-light);
    transition: var(--transition-smooth);
    padding: var(--spacing-xs);
    border-radius: 50%;
}

.google-signin-container .close-btn:hover {
    color: var(--primary-color);
    background: rgba(162, 119, 65, 0.08);
    transform: rotate(180deg) scale(1.08);
}

#cart-icon, #cart-icon-mobile {
    position: relative !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

#cart-count, #cart-count-mobile {
    position: fixed !important;
    top: var(--cart-counter-top, 10px) !important;
    right: var(--cart-counter-right, 20px) !important;
    background: linear-gradient(135deg, #dc2626, #ef4444) !important;
    color: white !important;
    border-radius: 50% !important;
    width: 20px !important;
    height: 20px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 10px !important;
    font-weight: 700 !important;
    font-family: -apple-system, BlinkMacSystemFont, sans-serif !important;
    border: 2px solid white !important;
    box-shadow: 
        0 2px 8px rgba(220, 38, 38, 0.5),
        0 0 0 1px rgba(0, 0, 0, 0.1),
        0 4px 16px rgba(220, 38, 38, 0.25) !important;
    z-index: 9999 !important;
    isolation: isolate !important;
    pointer-events: none !important;
    user-select: none !important;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    transform: scale(0) !important;
    opacity: 0 !important;
}

@media (max-width: 768px) {
    #cart-count, #cart-count-mobile {
        width: 20px !important;
        height: 20px !important;
        font-size: 10px !important;
        top: -5px !important;
        right: -5px !important;
        border-width: 1.5px !important;
    }
}

@media (max-width: 480px) {
    #cart-count, #cart-count-mobile {
        width: 18px !important;
        height: 18px !important;
        font-size: 9px !important;
        top: -4px !important;
        right: -4px !important;
    }
}


@keyframes cartBounce {
    0% { 
        transform: scale(0) rotate(-180deg); 
        opacity: 0;
    }
    50% { 
        transform: scale(1.4) rotate(8deg); 
        opacity: 1;
    }
    100% { 
        transform: scale(1) rotate(0); 
        opacity: 1;
    }
}

#cart-count:not([data-count="0"]), 
#cart-count-mobile:not([data-count="0"]) {
    transform: scale(1) !important;
    opacity: 1 !important;
    animation: cartCounterShow 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards !important;
}

#cart-count[data-count="0"], 
#cart-count-mobile[data-count="0"] {
    display: none !important;
}

.animate-on-scroll {
    opacity: 0;
    transform: translateY(60px) scale(0.95);
    transition: opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.animate-on-scroll.animate {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.stagger-animation > * {
    opacity: 0;
    transform: translateY(50px) scale(0.9) rotateX(10deg);
    transition: var(--transition-slow);
}

.stagger-animation.animate > *:nth-child(1) { 
    animation: slideUpStagger 0.8s var(--bounce) 0.08s forwards; 
}
.stagger-animation.animate > *:nth-child(2) { 
    animation: slideUpStagger 0.8s var(--bounce) 0.2s forwards; 
}
.stagger-animation.animate > *:nth-child(3) { 
    animation: slideUpStagger 0.8s var(--bounce) 0.32s forwards; 
}
.stagger-animation.animate > *:nth-child(4) { 
    animation: slideUpStagger 0.8s var(--bounce) 0.44s forwards; 
}
.stagger-animation.animate > *:nth-child(5) { 
    animation: slideUpStagger 0.8s var(--bounce) 0.56s forwards; 
}
.stagger-animation.animate > *:nth-child(6) { 
    animation: slideUpStagger 0.8s var(--bounce) 0.68s forwards; 
}

@keyframes slideUpStagger {
    to {
        opacity: 1;
        transform: translateY(0) scale(1) rotateX(0);
    }
}

.tour-categories .category-btn {
    opacity: 0;
    transform: translateY(24px) scale(0.95);
    transition: var(--transition-slow);
}

.tour-categories.animate .category-btn:nth-child(1) { 
    animation: slideUpStagger 0.6s var(--elastic) 0.08s forwards; 
}
.tour-categories.animate .category-btn:nth-child(2) { 
    animation: slideUpStagger 0.6s var(--elastic) 0.16s forwards; 
}
.tour-categories.animate .category-btn:nth-child(3) { 
    animation: slideUpStagger 0.6s var(--elastic) 0.24s forwards; 
}
.tour-categories.animate .category-btn:nth-child(4) { 
    animation: slideUpStagger 0.6s var(--elastic) 0.32s forwards; 
}

.guias-filters select {
    opacity: 0;
    transform: translateY(24px) scale(0.95);
    transition: var(--transition-slow);
}

.guias-filters.animate select:nth-child(1) { 
    animation: slideUpStagger 0.6s var(--elastic) 0.08s forwards; 
}
.guias-filters.animate select:nth-child(2) { 
    animation: slideUpStagger 0.6s var(--elastic) 0.16s forwards; 
}
.guias-filters.animate select:nth-child(3) { 
    animation: slideUpStagger 0.6s var(--elastic) 0.24s forwards; 
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

@media (max-width: 1024px) {
    .nav-container {
        padding: 0 var(--spacing-lg);
    }
    
    .container {
        padding: 0 var(--spacing-lg);
    }
    
    .section {
        padding: 64px 0;
    }
    
    .hero-content h1 {
        font-size: clamp(2rem, 4vw, 2.8rem);
    }
    
    .tours-container {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--spacing-lg);
    }
    
    .guias-container {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--spacing-lg);
    }
}


@media (max-width: 768px) {
    :root {
        --spacing-xs: 3px;
        --spacing-sm: 6px;
        --spacing-md: 12px;
        --spacing-lg: 18px;
        --spacing-xl: 24px;
        --spacing-2xl: 36px;
    }

    .navbar {
        padding: 8px 0;
    }

    .navbar.scrolled {
        padding: 6px 0;
    }

    .nav-container {
        padding: 0 var(--spacing-lg);
    }

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
        font-size: clamp(1.8rem, 6vw, 2.4rem);
        margin-bottom: var(--spacing-lg);
    }

    .hero-content p {
        font-size: clamp(0.9rem, 3vw, 1.1rem);
        margin-bottom: var(--spacing-xl);
    }

    .hero-buttons {
        flex-direction: column;
        align-items: center;
        gap: var(--spacing-md);
    }

    .hero-buttons .btn {
        width: 100%;
        max-width: 280px;
        justify-content: center;
        padding: 12px 24px;
    }

    .section {
        padding: 48px 0;
    }

    .section-title {
        font-size: clamp(1.6rem, 5vw, 2rem);
        margin-bottom: var(--spacing-md);
    }

    .section-header {
        margin-bottom: 48px;
    }

    .container {
        padding: 0 var(--spacing-lg);
    }

    .tours-container {
        grid-template-columns: 1fr;
        gap: var(--spacing-lg);
    }

    .tour-categories {
        gap: var(--spacing-sm);
        margin-bottom: 32px;
    }

    .category-btn {
        padding: 8px 16px;
        font-size: 12px;
    }

    .guias-container {
        grid-template-columns: 1fr;
        gap: var(--spacing-lg);
    }

    .guias-filters {
        flex-direction: column;
        gap: var(--spacing-md);
        margin-bottom: 32px;
    }

    .guias-filters select {
        width: 100%;
        padding: 10px 16px;
    }

    .photos-mural {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: var(--spacing-md);
        margin-bottom: 32px;
    }

    .photo-item img {
        height: 240px;
    }

    .carousel-nav {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }

    .carousel-nav.prev {
        left: var(--spacing-sm);
    }

    .carousel-nav.next {
        right: var(--spacing-sm);
    }

    .experiencia-content {
        padding: var(--spacing-lg);
    }

    .add-experiencia {
        margin: 32px auto 0;
    }

    .add-experiencia form {
        padding: var(--spacing-lg);
        margin-top: var(--spacing-md);
    }

    .footer {
        padding: 48px 0 var(--spacing-lg);
    }

    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: var(--spacing-xl);
        padding: 0 var(--spacing-lg);
    }

    .google-signin-container {
        width: 90%;
        max-width: 280px;
        right: 50%;
        transform: translateX(50%);
        top: 64px;
    }

    .whatsapp-button {
        width: 48px;
        height: 48px;
        bottom: var(--spacing-md);
        right: var(--spacing-md);
    }

    .whatsapp-button i {
        font-size: 22px;
    }

    #cart-count, #cart-count-mobile {
        width: 18px !important;
        height: 18px !important;
        font-size: 9px !important;
        top: -6px !important;
        right: -6px !important;
    }

    .btn {
        padding: 10px 18px;
        font-size: 13px;
    }

    .tour-header {
        display: none;
    }

    .tour-title {
        font-size: 16px;
        margin-bottom: var(--spacing-xs);
    }

    .tour-schedule {
        font-size: 12px;
    }

    .tour-content {
        padding: var(--spacing-md);
    }

    .tour-details {
    display: none;
}
    .guia-card {
        padding: var(--spacing-lg);
    }

    .guia-avatar {
        width: 80px;
        height: 80px;
        margin-bottom: var(--spacing-md);
    }

    .guia-name {
        font-size: 16px;
        margin-bottom: var(--spacing-xs);
    }

    .guia-rating {
        margin-bottom: var(--spacing-sm);
    }

    .guia-rating .star {
        font-size: 13px;
    }

    .experiencia-user {
        gap: var(--spacing-sm);
        margin-bottom: var(--spacing-md);
    }

    .experiencia-avatar {
        width: 36px;
        height: 36px;
    }

    .experiencia-name {
        font-size: 14px;
    }

    .social-links {
        gap: var(--spacing-sm);
        justify-content: center;
    }

    .social-link {
        width: 36px;
        height: 36px;
    }

    .social-link i {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .hero-content h1 {
        font-size: clamp(1.6rem, 7vw, 2rem);
    }

    .hero-content p {
        font-size: clamp(0.85rem, 4vw, 1rem);
    }

    .btn {
        padding: 10px 16px;
        font-size: 12px;
    }

    .hero-buttons .btn {
        width: 100%;
        max-width: 240px;
    }

    .section-title {
        font-size: clamp(1.4rem, 6vw, 1.8rem);
    }

    .section-subtitle {
        font-size: 14px;
    }

    .category-btn {
        padding: 7px 14px;
        font-size: 11px;
    }

    .photos-mural {
        grid-template-columns: 1fr;
        gap: var(--spacing-sm);
    }

    .photo-item img {
        height: 220px;
    }

    .whatsapp-button {
        width: 44px;
        height: 44px;
    }

    .whatsapp-button i {
        font-size: 20px;
    }

    .section {
        padding: 40px 0;
    }

    .container {
        padding: 0 var(--spacing-md);
    }

    .nav-container {
        padding: 0 var(--spacing-md);
    }

    .footer-content {
        padding: 0 var(--spacing-md);
    }

    .mobile-nav {
        padding: var(--spacing-lg);
        max-width: 280px;
    }

    .guias-filters select {
        padding: 9px 14px;
        font-size: 13px;
    }

    .loading-text {
        font-size: 14px;
        letter-spacing: 1.5px;
    }

    .loading-spinner {
        width: 56px;
        height: 56px;
        margin-bottom: 20px;
    }

    .hero-indicators {
        bottom: var(--spacing-lg);
        gap: var(--spacing-xs);
    }

    .hero-indicator {
        width: 8px;
        height: 8px;
    }

    .tour-image {
        height: 180px;
    }

    .add-experiencia textarea {
        min-height: 80px;
        padding: var(--spacing-sm);
        font-size: 13px;
    }

    .footer-section h3 {
        font-size: 16px;
        margin-bottom: var(--spacing-md);
    }

    .footer-section p, .footer-section li {
        font-size: 13px;
        margin-bottom: var(--spacing-xs);
    }

    .footer-bottom {
        font-size: 12px;
        padding-top: var(--spacing-md);
        margin-top: var(--spacing-lg);
    }
}

@media (max-width: 360px) {
    .container {
        padding: 0 var(--spacing-sm);
    }

    .nav-container {
        padding: 0 var(--spacing-sm);
    }

    .hero-buttons .btn {
        max-width: 200px;
        padding: 9px 14px;
    }

    .mobile-nav {
        max-width: 260px;
        padding: var(--spacing-md);
    }

    .whatsapp-button {
        width: 40px;
        height: 40px;
        bottom: var(--spacing-sm);
        right: var(--spacing-sm);
    }

    .whatsapp-button i {
        font-size: 18px;
    }

    .tour-header,
    .tour-content {
        padding: var(--spacing-sm);
    }

    .guia-card {
        padding: var(--spacing-md);
    }

    .experiencia-content {
        padding: var(--spacing-md);
    }

    .add-experiencia form {
        padding: var(--spacing-md);
    }
}

.scroll-to-top {
    position: fixed;
    bottom: 80px;
    right: var(--spacing-lg);
    background: var(--gradient-primary);
    border: none;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-size: 16px;
    cursor: pointer;
    transition: var(--transition-smooth);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    z-index: 999;
    box-shadow: var(--shadow-medium);
}

.scroll-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.scroll-to-top:hover {
    transform: translateY(-3px) scale(1.08);
    box-shadow: var(--shadow-heavy);
}

@media (max-width: 768px) {
    .scroll-to-top {
        bottom: 60px;
        right: var(--spacing-md);
        width: 40px;
        height: 40px;
        font-size: 14px;
    }
}
@media (max-width: 768px) {
    .tour-card {
        height: 380px;
    }
    
    .tour-image {
        height: 50%;
    }
    
    .tour-content {
        padding: var(--spacing-md);
    }
    
    .tour-title {
        font-size: 16px;
        margin-bottom: var(--spacing-sm);
    }
    
    .tour-info-item {
        font-size: 13px;
        margin-bottom: var(--spacing-xs);
    }
    
    .tour-actions .btn {
        padding: 8px 16px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .tour-card {
        height: 360px;
    }
    
    .tour-image {
        height: 48%;
    }
    
    .tour-title {
        font-size: 15px;
    }
    
    .tour-info-item {
        font-size: 12px;
    }
}

.tours-loading {
    position: relative;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.9);
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.tours-loading.active {
    opacity: 1;
    transform: scale(1);
}

.tours-loading-spinner {
    width: 48px;
    height: 48px;
    border: 3px solid transparent;
    border-top: 3px solid var(--primary-color);
    border-right: 3px solid var(--primary-light);
    border-radius: 50%;
    animation: toursLoadingSpin 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) infinite;
    position: relative;
}

.tours-loading-spinner::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: calc(100% - 6px);
    height: calc(100% - 6px);
    border: 2px solid transparent;
    border-bottom: 2px solid var(--secondary-color);
    border-radius: 50%;
    animation: toursLoadingSpinReverse 1s ease-in-out infinite;
}

.tours-loading-text {
    margin-top: 16px;
    color: var(--primary-color);
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0.5px;
}

@keyframes toursLoadingSpin {
    0% { 
        transform: rotate(0deg); 
        border-top-color: var(--primary-color);
        border-right-color: var(--primary-light);
    }
    50% { 
        transform: rotate(180deg); 
        border-top-color: var(--primary-light);
        border-right-color: var(--secondary-color);
    }
    100% { 
        transform: rotate(360deg); 
        border-top-color: var(--primary-color);
        border-right-color: var(--primary-light);
    }
}

@keyframes toursLoadingSpinReverse {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(-360deg); }
}

.tours-container.filtering {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.tours-container.showing {
    opacity: 1;
    transform: translateY(0) scale(1);
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
/*
 * Mural de fotos
 */
.mural-container {
    background: #FFFAF0;
    position: relative;
    padding: 40px 20px;
    min-height: 600px;
    overflow: hidden;
    border-radius: 12px;
}

.photos-mural {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.polaroid-frame {
    background: #fff;
    padding: 12px 12px 48px 12px;
    box-shadow: 
        0 4px 6px rgba(0,0,0,0.1),
        0 1px 3px rgba(0,0,0,0.08),
        0 8px 15px rgba(0,0,0,0.05);
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    max-width: 280px;
    margin: 0 auto;
    will-change: transform;
}

.polaroid-frame:nth-child(1) {
    transform: rotate(-3deg);
    background: linear-gradient(135deg, #fff 0%, #faf8f6 100%);
}

.polaroid-frame:nth-child(2) {
    transform: rotate(2deg);
    background: linear-gradient(135deg, #fff 0%, #f6f8fa 100%);
}

.polaroid-frame:nth-child(3) {
    transform: rotate(-4deg);
    background: linear-gradient(135deg, #fff 0%, #faf6f8 100%);
}

.polaroid-frame:nth-child(4) {
    transform: rotate(1deg);
    background: linear-gradient(135deg, #fff 0%, #f6faf7 100%);
}

.polaroid-frame:nth-child(5) {
    transform: rotate(-2deg);
    background: linear-gradient(135deg, #fff 0%, #faf7f6 100%);
}

.polaroid-frame:nth-child(6) {
    transform: rotate(3deg);
    background: linear-gradient(135deg, #fff 0%, #f6f7fa 100%);
}

.polaroid-frame:nth-child(7) {
    transform: rotate(-1deg);
    background: linear-gradient(135deg, #fff 0%, #f8faf6 100%);
}

.polaroid-frame:nth-child(8) {
    transform: rotate(2deg);
    background: linear-gradient(135deg, #fff 0%, #faf6f7 100%);
}

.polaroid-frame:hover {
    transform: rotate(0deg) scale(1.05);
    box-shadow: 
        0 8px 16px rgba(0,0,0,0.15),
        0 3px 6px rgba(0,0,0,0.1),
        0 16px 32px rgba(0,0,0,0.08);
    z-index: 10;
}

.polaroid-image-container {
    position: relative;
    width: 100%;
    background: #1a1a1a;
    overflow: hidden;
}

.polaroid-image {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
    transition: opacity 0.3s ease;
}

.polaroid-image.portrait {
    aspect-ratio: 3/4;
}

.polaroid-image.landscape {
    aspect-ratio: 4/3;
}

.polaroid-image.square {
    aspect-ratio: 1/1;
}

.polaroid-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 8px 4px 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'Courier New', monospace;
}

.polaroid-user {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #4a4a4a;
    font-weight: 500;
    max-width: 60%;
}

.polaroid-user img {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #e0e0e0;
}

.polaroid-user span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.polaroid-date {
    font-size: 10px;
    color: #888;
    font-style: italic;
}

@media (max-width: 768px) {
    .mural-container {
        padding: 30px 15px;
        min-height: 500px;
    }
    
    .photos-mural {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 20px;
    }
    
    .polaroid-frame {
        padding: 8px 8px 36px 8px;
        max-width: 200px;
    }
    
    .polaroid-info {
        padding: 6px 3px 4px;
    }
    
    .polaroid-user {
        font-size: 10px;
    }
    
    .polaroid-user img {
        width: 16px;
        height: 16px;
    }
    
    .polaroid-date {
        font-size: 9px;
    }
}

@media (max-width: 480px) {
    .photos-mural {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 15px;
    }
    
    .polaroid-frame {
        max-width: 180px;
        padding: 6px 6px 32px 6px;
    }
}

.fade-in {
    animation: fadeIn 0.6s ease-out forwards;
    opacity: 0;
}

@keyframes fadeIn {
    to {
        opacity: 1;
    }
}

.polaroid-frame:nth-child(1) { animation-delay: 0.1s; }
.polaroid-frame:nth-child(2) { animation-delay: 0.2s; }
.polaroid-frame:nth-child(3) { animation-delay: 0.3s; }
.polaroid-frame:nth-child(4) { animation-delay: 0.4s; }
.polaroid-frame:nth-child(5) { animation-delay: 0.5s; }
.polaroid-frame:nth-child(6) { animation-delay: 0.6s; }
.polaroid-frame:nth-child(7) { animation-delay: 0.7s; }
.polaroid-frame:nth-child(8) { animation-delay: 0.8s; }
.comentarios-carousel {
    width: 100%;
    height: 450px;
    overflow: hidden;
    position: relative;
    background: var(--primary-bg);
    border-radius: var(--border-radius-lg);
    margin: 48px 0;
}

.comentarios-track {
    display: flex;
    height: 100%;
    will-change: transform;
    cursor: grab;
    user-select: none;
    -webkit-user-select: none;
}

.comentarios-track:active {
    cursor: grabbing;
}

.comentarios-track.dragging {
    cursor: grabbing;
}

.comentario-card {
    background: #F1E9DB;
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-2xl);
    margin-right: var(--spacing-lg);
    min-width: 300px;
    max-width: 300px;
    height: 370px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: var(--shadow-medium);
    transition: var(--transition-smooth);
    border: 1px solid var(--border-light);
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}

.comentario-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--gradient-primary);
    transform: scaleX(0);
    transition: var(--transition-smooth);
}

.comentario-card:hover::before {
    transform: scaleX(1);
}

.comentario-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-heavy);
}

.comentario-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 2px solid var(--primary-light);
    object-fit: cover;
    margin-bottom: var(--spacing-sm);
    transition: var(--transition-smooth);
    position: relative;
    flex-shrink: 0;
}

.comentario-avatar::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border-radius: 50%;
    background: var(--gradient-primary);
    opacity: 0;
    transition: var(--transition-smooth);
    z-index: -1;
}

.comentario-card:hover .comentario-avatar {
    transform: scale(1.05);
}

.comentario-card:hover .comentario-avatar::before {
    opacity: 1;
    animation: avatarPulse 2s infinite;
}

.comentario-texto {
    flex: 1;
    font-style: italic;
    font-size: 16px;
    line-height: 1.5;
    color: var(--text-medium);
    margin: var(--spacing-md) 0;
    display: -webkit-box;
    -webkit-line-clamp: 8;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-align: justify;
    hyphens: auto;
}

.comentario-info {
    margin-top: auto;
    flex-shrink: 0;
}

.comentario-nombre {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 14px;
    margin-bottom: 2px;
    letter-spacing: -0.1px;
}

.comentario-fecha {
    font-size: 11px;
    color: var(--text-light);
    opacity: 0.7;
}

@keyframes slideInfinite {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(calc(-324px * var(--total-cards)));
    }
}

@keyframes avatarPulse {
    0%, 100% {
        transform: scale(1.05);
        opacity: 0.6;
    }
    50% {
        transform: scale(1.08);
        opacity: 0.3;
    }
}

@media (max-width: 768px) {
    .comentarios-carousel {
        height: 380px;
        margin: 32px 0;
    }
    
    .comentario-card {
        min-width: 260px;
        max-width: 260px;
        height: 320px;
        padding: var(--spacing-md);
    }
    
    .comentario-avatar {
        width: 44px;
        height: 44px;
        margin-bottom: var(--spacing-xs);
    }
    
    .comentario-texto {
        font-size: 14px;
        -webkit-line-clamp: 6;
        margin: var(--spacing-sm) 0;
    }
    
    .comentario-nombre {
        font-size: 13px;
    }
    
    .comentario-fecha {
        font-size: 10px;
    }
}

@media (max-width: 480px) {
    .comentarios-carousel {
        height: 340px;
    }
    
    .comentario-card {
        min-width: 220px;
        max-width: 220px;
        height: 280px;
        padding: var(--spacing-sm);
    }
    
    .comentario-avatar {
        width: 40px;
        height: 40px;
    }
    
    .comentario-texto {
        font-size: 13px;
        -webkit-line-clamp: 5;
    }
    
    .comentario-nombre {
        font-size: 12px;
    }
    
    .comentario-fecha {
        font-size: 9px;
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
                <span style="font-family: 'Times New Roman', Times, serif;">ANTARES TRAVEL PERU</span>
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
                    <a href="src/reserva.php" id="cart-icon" class="btn btn-secondary" style="position: relative;">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-count" data-count="<?php echo $cart_count; ?>"><?php echo $cart_count; ?></span>
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
                <a href="src/reserva.php" id="cart-icon-mobile" class="btn btn-secondary" style="position: relative;">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count-mobile" data-count="<?php echo $cart_count; ?>"><?php echo $cart_count; ?></span>
                </a>
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
                
                <h1><?php echo $current_lang == 'es' ? 'Turismo en Cusco - Antares Travel Perú' : 'Cusco Tourism with Antares Travel Peru'; ?></h1>
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
                            
                            <div class="tour-content">
                                <h3 class="tour-title"><?php echo htmlspecialchars($tour['titulo']); ?></h3>
                                
                                <div class="tour-info">

                                    <div class="tour-info-fixed">

                                    <div class="tour-info-variable">
                                        <?php if ($tour['descripcion']): ?>
                                            <div class="tour-info-item description">
                                                <i class="fas fa-check-circle"></i>
                                                <div>
                                                    <span><?php echo $current_lang == 'es' ? 'Incluye' : 'Includes'; ?>:</span>
                                                    <div class="tour-includes">
                                                        <?php echo htmlspecialchars(substr($tour['descripcion'], 0, 80) . '...'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                        <?php if ($tour['duracion']): ?>
                                            <div class="tour-info-item duration">
                                                <i class="fas fa-clock"></i>
                                                <div>
                                                    <span><?php echo $current_lang == 'es' ? 'Duración' : 'Duration'; ?>:</span>
                                                    <div class="tour-duration"><?php echo htmlspecialchars($tour['duracion']); ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($tour['hora_salida']): ?>
                                            <div class="tour-info-item schedule">
                                                <i class="fas fa-calendar-alt"></i>
                                                <div>
                                                    <span><?php echo $current_lang == 'es' ? 'Horario' : 'Schedule'; ?>:</span>
                                                    <div class="tour-schedule">
                                                        <?php echo date('H:i', strtotime($tour['hora_salida'])); ?> - 
                                                        <?php echo $tour['hora_llegada'] ? date('H:i', strtotime($tour['hora_llegada'])) : '19:00'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    

                                </div>

                                <div class="tour-actions">
                                    <a href="src/detalles.php?id_tour=<?php echo $tour['id_tour']; ?>" class="btn btn-primary">
                                        <i class="fas fa-info-circle"></i> <?php echo $current_lang == 'es' ? 'Más información' : 'More information'; ?>
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
<!-- 
    <section id="guias" class="section guias-section">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <h2 class="section-title"><#?php echo $current_lang == 'es' ? 'Guías Turísticos en Cusco' : 'Tour Guides in Cusco'; ?></h2>
                <p class="section-subtitle"><#?php echo $lang['guides_section_subtitle']; ?></p>
            </div>

            <div class="guias-filters animate-on-scroll">
                <select id="sortRating" onchange="filterGuias()">
                    <option value=""><#?php echo $lang['guides_filter_sort']; ?></option>
                    <option value="asc"><#?php echo $lang['guides_filter_sort_asc']; ?></option>
                    <option value="desc"><#?php echo $lang['guides_filter_sort_desc']; ?></option>
                </select>
                <select id="filterStatus" onchange="filterGuias()">
                    <option value=""><#?php echo $lang['guides_filter_status']; ?></option>
                    <option value="libre"><#?php echo $lang['guides_filter_status_free']; ?></option>
                    <option value="ocupado"><#?php echo $lang['guides_filter_status_busy']; ?></option>
                </select>
                <select id="filterLanguage" onchange="filterGuias()">
                    <option value=""></*?php echo $lang['guides_filter_language']; ?></option>
                    <# ?php if ($idiomas_result): ?>
                        <# ?php while($idioma = $idiomas_result->fetch_assoc()): ?>
                            <option value="<# ?php echo $idioma['id_idioma']; ?>"><# ?php echo htmlspecialchars($idioma['nombre_idioma']); ?></option>
                        <# ?php endwhile; ?>
                    <# ?php endif; ?>
                </select>
            </div>

            <div class="guias-container stagger-animation" id="guiasContainer">
                <# ?php if ($guias_result && $guias_result->num_rows > 0): ?>
                    <# ?php while ($guia = $guias_result->fetch_assoc()): ?>
                        <# ?php $rating = floatval($guia['rating_promedio'] ?: 0); ?>
                        <div class="guia-card" data-rating="<# ?php echo $rating; ?>" data-estado="<# ?php echo strtolower($guia['estado']); ?>" data-idiomas="<# ?php echo $guia['idiomas'] ?: ''; ?>">
                            <img src="<# ?php echo htmlspecialchars($guia['foto_url'] ?: 'imagenes/default-guide.jpg'); ?>" 
                                 alt="<# ?php echo $current_lang == 'es' ? 'Guía turístico en Cusco ' . htmlspecialchars($guia['nombre']) : 'Cusco tour guide ' . htmlspecialchars($guia['nombre']); ?>" 
                                 class="guia-avatar" loading="lazy">
                            <h3 class="guia-name">
                                <# ?php echo htmlspecialchars($guia['nombre'] . ' ' . ($guia['apellido'] ?: '')); ?>
                            </h3>
                            
                            <div class="guia-rating">
                                <# ?php 
                                $total_reviews = intval($guia['total_calificaciones'] ?: 0);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="fas fa-star <# ?php echo $i <= $rating ? 'star' : ''; ?>"></i>
                                <# ?php endfor; ?>
                                <span>(<# ?php echo $total_reviews; ?> <# ?php echo $lang['guide_card_reviews']; ?>)</span>
                            </div>
                            
                            <# ?php if ($guia['experiencia']): ?>
                                <p class="guia-experience"><# ?php echo htmlspecialchars(substr($guia['experiencia'], 0, 80)) . '...'; ?></p>
                            <# ?php endif; ?>
                            
                            <div class="guia-contact">
                                <# ?php if ($guia['telefono']): ?>
                                    <div><i class="fas fa-phone"></i> <# ?php echo htmlspecialchars($guia['telefono']); ?></div>
                                <# ?php endif; ?>
                                <# ?php if ($guia['email']): ?>
                                    <div><i class="fas fa-envelope"></i> <# ?php echo htmlspecialchars($guia['email']); ?></div>
                                <# ?php endif; ?>
                                <div class="guia-status <# ?php echo strtolower($guia['estado']); ?>">
                                    <i class="fas fa-circle"></i> <# ?php echo $guia['estado']; ?>
                                </div>
                            </div>
                        </div>
                    <# ?php endwhile; ?>
                <# ?php else: ?>
                    <div class="no-guides">
                        <p><# ?php echo $lang['no_guides_available']; ?></p>
                    </div>
                <# ?php endif; ?>
            </div>
        </div>
    </section>
-->
    <section id="experiencias" class="section experiencias-section">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <h2 class="section-title"><?php echo $current_lang == 'es' ? 'Experiencias de Turismo en Cusco' : 'Cusco Tourism Experiences'; ?></h2>
                <p class="section-subtitle"><?php echo $lang['experiences_section_subtitle']; ?></p>
            </div>

            <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 30px;" class="animate-on-scroll"><?php echo $lang['experiences_gallery_title']; ?></h3>
            <div class="mural-container">
                <div class="photos-mural">
                    <?php
                    $photos_query = "SELECT e.imagen_url, e.fecha_publicacion, u.nombre, u.avatar_url 
                                    FROM experiencias e 
                                    LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario 
                                    WHERE e.imagen_url IS NOT NULL AND e.imagen_url != ''
                                    ORDER BY e.fecha_publicacion DESC 
                                    LIMIT 8";
                    $photos_result = $conn->query($photos_query);
                    
                    if ($photos_result && $photos_result->num_rows > 0):
                        while ($photo = $photos_result->fetch_assoc()):
                            $image_url = $photo['imagen_url'];
                            list($width, $height) = @getimagesize($image_url) ?: [4, 3];
                            $ratio = $width / $height;
                            $orientation = 'square';
                            if ($ratio > 1.2) $orientation = 'landscape';
                            elseif ($ratio < 0.9) $orientation = 'portrait';
                    ?>
                        <div class="polaroid-frame fade-in">
                            <div class="polaroid-image-container">
                                <img src="<?php echo htmlspecialchars($photo['imagen_url']); ?>" 
                                    alt="Foto de experiencia" 
                                    class="polaroid-image <?php echo $orientation; ?>"
                                    loading="lazy">
                            </div>
                            <div class="polaroid-info">
                                <div class="polaroid-user">
                                    <img src="<?php echo htmlspecialchars($photo['avatar_url'] ?: 'imagenes/default-avatar.png'); ?>" 
                                        alt="Avatar">
                                    <span><?php echo htmlspecialchars($photo['nombre'] ?: 'Anónimo'); ?></span>
                                </div>
                                <div class="polaroid-date">
                                    <?php echo date('d/m/y', strtotime($photo['fecha_publicacion'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <p style="grid-column: 1/-1; text-align: center; color: #666; font-style: italic;">
                            Las fotos aparecerán aquí pronto...
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="carousel-container animate-on-scroll">
                <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 30px;"><?php echo $lang['experiences_comments_title']; ?></h3>

                <div class="comentarios-carousel">
                    <div class="comentarios-track" id="comentariosTrack">
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
                        
                        if (!empty($comments)):
                            $comments = array_merge($comments, $comments, $comments);
                            foreach ($comments as $comment):
                        ?>
                            <div class="comentario-card">
                                <img src="<?php echo htmlspecialchars($comment['avatar_url'] ?: 'imagenes/default-avatar.png'); ?>" 
                                    alt="<?php echo $current_lang == 'es' ? 'Avatar de usuario' : 'User avatar'; ?>" 
                                    class="comentario-avatar" 
                                    loading="lazy">
                                
                                <div class="comentario-texto">
                                    "<?php echo htmlspecialchars($comment['comentario']); ?>"
                                </div>
                                
                                <div class="comentario-info">
                                    <div class="comentario-nombre">
                                        <?php echo htmlspecialchars($comment['nombre'] ?: $lang['experiences_anonymous_user']); ?>
                                    </div>
                                    <div class="comentario-fecha">
                                        <?php echo date('d/m/Y', strtotime($comment['fecha_publicacion'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <div class="comentario-card">
                                <div class="comentario-avatar" style="background: var(--gradient-primary);"></div>
                                <div class="comentario-texto">
                                    <?php echo $lang['experiences_comments_soon']; ?>
                                </div>
                                <div class="comentario-info">
                                    <div class="comentario-nombre">Antares Travel</div>
                                    <div class="comentario-fecha"><?php echo date('d/m/Y'); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
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
            initializeNavbar();
            
            setTimeout(() => {
                initializeHeroCarousel();
                initializeScrollAnimations();
            }, 100);
            
            setTimeout(() => {
                initializeGoogleSignin();
                filterGuias();
            }, 500);
        });

        function initializeLoadingScreen() {
            setTimeout(() => {
                document.getElementById('loadingScreen').classList.add('hidden');
                isLoading = false;
            }, 500);
        }

        function lazyLoadContent() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '200px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const section = entry.target;
                        if (section.id === 'guias' && !section.dataset.loaded) {
                            loadGuiasContent();
                            section.dataset.loaded = 'true';
                        }
                        if (section.id === 'experiencias' && !section.dataset.loaded) {
                            loadExperienciasContent();
                            section.dataset.loaded = 'true';
                        }
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('#guias, #experiencias').forEach(section => {
                observer.observe(section);
            });
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
            const toursContainer = document.getElementById('toursContainer');
            
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            toursContainer.classList.add('filtering');
            toursContainer.classList.remove('showing');
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'tours-loading';
            loadingDiv.innerHTML = `
                <div>
                    <div class="tours-loading-spinner"></div>
                    <div class="tours-loading-text">${category === 'all' ? 'Cargando todos los tours...' : 'Filtrando tours...'}</div>
                </div>
            `;
            
            toursContainer.parentNode.insertBefore(loadingDiv, toursContainer.nextSibling);
            
            setTimeout(() => {
                loadingDiv.classList.add('active');
            }, 50);
            
            setTimeout(() => {
                let visibleCards = [];
                
                tourCards.forEach(card => {
                    if (category === 'all' || card.dataset.category === category) {
                        visibleCards.push(card);
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                loadingDiv.classList.remove('active');
                
                setTimeout(() => {
                    loadingDiv.remove();
                    toursContainer.classList.remove('filtering');
                    toursContainer.classList.add('showing');
                    
                    visibleCards.forEach((card, index) => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(30px) scale(0.95)';
                        
                        setTimeout(() => {
                            card.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0) scale(1)';
                        }, 100 + (index * 80));
                    });
                    
                }, 300);
                
            }, 1000); 
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
                
                if (currentScrollY > 250) {
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
        function initializeInfiniteComments() {
    const track = document.getElementById('comentariosTrack');
    if (!track) return;
    
    const cards = track.querySelectorAll('.comentario-card');
    if (cards.length === 0) return;
    
    const cardWidth = 324;
    const totalOriginalCards = cards.length / 3;
    
    let translateX = 0;
    let isDragging = false;
    let startX = 0;
    let startTranslateX = 0;
    let animationId = null;
    const speed = 0.5;
    
    function updatePosition() {
        track.style.transform = `translateX(${translateX}px)`;
    }
    
    function animate() {
        if (!isDragging) {
            translateX -= speed;
            
            if (translateX <= -(cardWidth * totalOriginalCards)) {
                translateX = 0;
            }
            
            updatePosition();
        }
        animationId = requestAnimationFrame(animate);
    }
    
    function handleStart(e) {
        isDragging = true;
        track.classList.add('dragging');
        
        const clientX = e.type === 'mousedown' ? e.clientX : e.touches[0].clientX;
        startX = clientX;
        startTranslateX = translateX;
        
        e.preventDefault();
        
        document.addEventListener('mousemove', handleMove, { passive: false });
        document.addEventListener('touchmove', handleMove, { passive: false });
        document.addEventListener('mouseup', handleEnd);
        document.addEventListener('touchend', handleEnd);
    }
    
    function handleMove(e) {
        if (!isDragging) return;
        
        e.preventDefault();
        
        const clientX = e.type === 'mousemove' ? e.clientX : e.touches[0].clientX;
        const deltaX = clientX - startX;
        
        translateX = startTranslateX + deltaX;
        
        const minTranslate = -(cardWidth * totalOriginalCards);
        const maxTranslate = cardWidth;
        
        if (translateX > maxTranslate) {
            translateX = minTranslate + (translateX - maxTranslate);
        } else if (translateX < minTranslate) {
            translateX = maxTranslate + (translateX - minTranslate);
        }
        
        updatePosition();
    }
    
    function handleEnd() {
        isDragging = false;
        track.classList.remove('dragging');
        
        document.removeEventListener('mousemove', handleMove);
        document.removeEventListener('touchmove', handleMove);
        document.removeEventListener('mouseup', handleEnd);
        document.removeEventListener('touchend', handleEnd);
    }
    
    track.addEventListener('mousedown', handleStart);
    track.addEventListener('touchstart', handleStart, { passive: false });
    track.addEventListener('dragstart', (e) => e.preventDefault());
    track.addEventListener('selectstart', (e) => e.preventDefault());
    
    animate();
    
    return () => {
        if (animationId) {
            cancelAnimationFrame(animationId);
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initializeInfiniteComments, 1000);
    const tourCards = document.querySelectorAll('.tour-card');
    
    tourCards.forEach(card => {
        const tourImage = card.querySelector('.tour-image');
        if (tourImage) {
            const backgroundImage = tourImage.style.backgroundImage;
            if (backgroundImage) {
                card.style.backgroundImage = backgroundImage;
            }
        }
    });
});
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
