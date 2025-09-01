<?php
require_once __DIR__ . '/../src/auth/auth_check.php';
require_once __DIR__ . '/../src/config/conexion.php';

$sql = "SELECT g.nombre, g.apellido, g.experiencia, g.estado, g.foto_url, 
               GROUP_CONCAT(i.nombre_idioma SEPARATOR ', ') as idiomas
        FROM guias g
        LEFT JOIN guia_idiomas gi ON g.id_guia = gi.id_guia
        LEFT JOIN idiomas i ON gi.id_idioma = i.id_idioma
        GROUP BY g.id_guia, g.nombre, g.apellido, g.experiencia, g.estado, g.foto_url";
        
$result = $conn->query($sql);
$guias = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuestros Guías - Antares Travel</title>
    <link rel="icon" type="image/png" href="../imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles_landing.css">
    <style>
        .page-header {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=600&fit=crop') no-repeat center center/cover;
            padding: 180px 0 100px;
            text-align: center;
            color: var(--white);
        }
        .page-header h1 {
            font-size: 3.5rem;
            font-weight: 700;
        }
        .main-content {
            padding: 80px 0;
            background-color: var(--primary-bg);
        }
        .guides-grid-page {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }
        .guide-card-page {
            background: var(--white);
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            text-align: center;
            position: relative;
        }
        .guide-card-page:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(162, 119, 65, 0.2);
        }
        .guide-image-page {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            margin: 30px auto 20px;
            border: 5px solid var(--white);
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .guide-content-page {
            padding: 0 30px 30px;
        }
        .guide-name-page {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        .guide-languages {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 15px;
        }
        .guide-experience {
            color: var(--text-light);
            line-height: 1.7;
        }
        .guide-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--white);
        }
        .status-libre {
            background-color: #28a745;
        }
        .status-ocupado {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar scrolled">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                <img src="../imagenes/antares_logozz2.png" alt="Antares Travel Logo" height="50" loading="lazy">
                ANTARES TRAVEL
            </a>
            <ul class="nav-links">
                 <li><a href="../index.php" data-es="Inicio" data-en="Home">Inicio</a></li>
                <li><a href="tours.php" data-es="Tours" data-en="Tours">Tours</a></li>
                <li><a href="guias.php" data-es="Guías" data-en="Guides">Guías</a></li>
            </ul>
            <div class="auth-buttons">
                <div class="lang-switch">
                    <button class="lang-btn active" data-lang="es">ES</button>
                    <button class="lang-btn" data-lang="en">EN</button>
                </div>
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_picture']); ?>" alt="Avatar de usuario">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="../index.php?logout=1" class="btn btn-primary" data-es="Cerrar Sesión" data-en="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
            <div class="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <div class="mobile-nav">
        <a href="../index.php" data-es="Inicio" data-en="Home">Inicio</a>
        <a href="tours.php" data-es="Tours" data-en="Tours">Tours</a>
        <a href="guias.php" data-es="Guías" data-en="Guides">Guías</a>
        <div class="mobile-auth-buttons">
            <div class="lang-switch">
                <button class="lang-btn active" data-lang="es">ES</button>
                <button class="lang-btn" data-lang="en">EN</button>
            </div>
            <div class="user-profile">
                <img src="<?php echo htmlspecialchars($_SESSION['user_picture']); ?>" alt="Avatar de usuario">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="../index.php?logout=1" class="btn btn-primary" data-es="Cerrar Sesión" data-en="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <header class="page-header">
        <h1 data-es="Nuestros Guías Expertos" data-en="Our Expert Guides">Nuestros Guías Expertos</h1>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="guides-grid-page">
                <?php foreach ($guias as $guia): ?>
                    <div class="guide-card-page">
                        <div class="guide-status <?php echo strtolower($guia['estado']) === 'libre' ? 'status-libre' : 'status-ocupado'; ?>">
                            <?php echo htmlspecialchars($guia['estado']); ?>
                        </div>
                        <div class="guide-image-page" style="background-image: url('<?php echo htmlspecialchars($guia['foto_url']); ?>');"></div>
                        <div class="guide-content-page">
                            <h3 class="guide-name-page"><?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?></h3>
                            <p class="guide-languages">
                                <i class="fas fa-language"></i> <?php echo htmlspecialchars($guia['idiomas'] ?: 'No especificado'); ?>
                            </p>
                            <p class="guide-experience"><?php echo htmlspecialchars($guia['experiencia']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>ANTARES TRAVEL</h3>
                    <p data-es="Más que una agencia de viajes, somos creadores de experiencias extraordinarias. Desde 2010, hemos ayudado a miles de viajeros a descubrir la magia de Cusco y sus alrededores de manera auténtica y memorable." 
                       data-en="More than a travel agency, we are creators of extraordinary experiences. Since 2010, we have helped thousands of travelers discover the magic of Cusco and its surroundings in an authentic and memorable way.">
                        Más que una agencia de viajes, somos creadores de experiencias extraordinarias. Desde 2010, hemos ayudado a miles de viajeros a descubrir la magia de Cusco y sus alrededores de manera auténtica y memorable.
                    </p>
                </div>
                <div class="footer-section">
                    <h3 data-es="Experiencias" data-en="Experiences">Experiencias</h3>
                    <ul>
                        <li><a href="#" data-es="Aventura Extrema" data-en="Extreme Adventure"><i class="fas fa-mountain"></i> Aventura Extrema</a></li>
                        <li><a href="#" data-es="Viajes Culturales" data-en="Cultural Trips"><i class="fas fa-users"></i> Viajes Culturales</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 data-es="Contacto" data-en="Contact">Contacto</h3>
                    <ul>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> Av. Sol 123, Cusco, Perú</a></li>
                        <li><a href="tel:+51084234567"><i class="fas fa-phone"></i> +51 84 234 567</a></li>
                        <li><a href="mailto:info@antarestravel.com"><i class="fas fa-envelope"></i> info@antarestravel.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>
                    <span data-es="&copy; 2024 Antares Travel. Todos los derechos reservados." data-en="&copy; 2024 Antares Travel. All rights reserved.">&copy; 2024 Antares Travel. Todos los derechos reservados.</span> | 
                    <a href="#" style="color: var(--primary-light);" data-es="Política de Privacidad" data-en="Privacy Policy">Política de Privacidad</a>
                </p>
            </div>
        </div>
    </footer>
    <script src="public/assets/js/main.js"></script>
</body>
</html>