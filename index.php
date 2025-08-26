<?php
session_start();
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
    cerrarSesion();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antares Travel - Descubre el Mundo</title>
    <link rel="icon" type="image/png" href="imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/styles_landing.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="#inicio" class="logo">
                <img src="imagenes/antares_logozz2.png" alt="Antares Travel Logo" height="50" loading="lazy">
                ANTARES TRAVEL
            </a>
            <ul class="nav-links">
                <li><a href="#inicio" data-es="Inicio" data-en="Home">Inicio</a></li>
                <li><a href="#destinos" data-es="Destinos" data-en="Destinations">Destinos</a></li>
                <li><a href="#guias" data-es="Guías" data-en="Guides">Guías</a></li>
                <li><a href="#fotos" data-es="Fotos" data-en="Photos">Fotos</a></li>
                <li><a href="#reservas" data-es="Reservas" data-en="Reservations">Reservas</a></li>
            </ul>
            <div class="auth-buttons">
                <div class="lang-switch">
                    <button class="lang-btn active" data-lang="es">ES</button>
                    <button class="lang-btn" data-lang="en">EN</button>
                </div>
                        <?php if (!isset($_SESSION['user_email'])): ?>
                            <a href="src/auth/login.php?lang=<?php echo isset($_GET['lang']) ? $_GET['lang'] : 'es'; ?>" class="btn btn-secondary" data-es="Iniciar Sesión" data-en="Login">
                                <i class="fas fa-user"></i>
                                Iniciar Sesión
                            </a>
                            <a href="src/auth/register.php?lang=<?php echo isset($_GET['lang']) ? $_GET['lang'] : 'es'; ?>" class="btn btn-primary" data-es="Registrarse" data-en="Sign Up">
                                <i class="fas fa-user-plus"></i>
                                <span>Registrarse</span>
                            </a>
                            <?php else: ?>
                    <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_picture']); ?>" alt="Avatar de usuario">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <a href="index.php?logout=1" class="btn btn-primary" data-es="Cerrar Sesión" data-en="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    <div class="mobile-nav">
        <a href="#inicio" data-es="Inicio" data-en="Home">Inicio</a>
        <a href="#destinos" data-es="Destinos" data-en="Destinations">Destinos</a>
        <a href="#guias" data-es="Guías" data-en="Guides">Guías</a>
        <a href="#fotos" data-es="Fotos" data-en="Photos">Fotos</a>
        <a href="#reservas" data-es="Reservas" data-en="Reservations">Reservas</a>
        <div class="mobile-auth-buttons">
            <div class="lang-switch">
                <button class="lang-btn active" data-lang="es">ES</button>
                <button class="lang-btn" data-lang="en">EN</button>
            </div>
                <?php if (!isset($_SESSION['user_email'])): ?>
                    <a href="src/auth/login.php?lang=<?php echo isset($_GET['lang']) ? $_GET['lang'] : 'es'; ?>" class="btn btn-secondary" data-es="Iniciar Sesión" data-en="Login">
                        <i class="fas fa-user"></i>
                        <span>Iniciar Sesión</span>
                    </a>
                    <a href="src/auth/register.php?lang=<?php echo isset($_GET['lang']) ? $_GET['lang'] : 'es'; ?>" class="btn btn-primary" data-es="Registrarse" data-en="Sign Up">
                        <i class="fas fa-user-plus"></i>
                        <span>Registrarse</span>
                    </a>
                    <?php else: ?>
                <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_picture']); ?>" alt="Avatar de usuario">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <a href="index.php?logout=1" class="btn btn-primary" data-es="Cerrar Sesión" data-en="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php endif; ?>
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
                <h1 data-es="Descubre el Mundo con Antares Travel" data-en="Discover the World with Antares Travel"><strong>Descubre el Mundo con Antares Travel</strong></h1>
                <p data-es="Experiencias únicas que transforman tu forma de viajar. Desde aventuras épicas hasta escapadas relajantes, creamos momentos inolvidables en los destinos más extraordinarios del mundo." 
                   data-en="Unique experiences that transform the way you travel. From epic adventures to relaxing getaways, we create unforgettable moments in the world's most extraordinary destinations.">
                    Experiencias únicas que transforman tu forma de viajar. Desde aventuras épicas hasta escapadas relajantes, creamos momentos inolvidables en los destinos más extraordinarios del mundo.
                </p>
                <?php if (!isset($_SESSION['user_email'])): ?>
                    <div id="g_id_onload"
                         data-client_id="454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com"
                         data-context="signin"
                         data-ux_mode="popup"
                         data-auto_prompt="true"
                         data-callback="handleCredentialResponse">
                    </div>
                <?php endif; ?>
                <div class="hero-buttons">
                    <a href="#destinos" class="btn btn-primary" data-es="Explorar Destinos" data-en="Explore Destinations">
                        <i class="fas fa-compass"></i>
                        <span>Explorar Destinos</span>
                    </a>
                    <a href="#reservas" class="btn btn-secondary" data-es="Reservar Ahora" data-en="Book Now">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Reservar Ahora</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="hero-indicators">
            <div class="indicator active" data-slide="0"></div>
            <div class="indicator" data-slide="1"></div>
            <div class="indicator" data-slide="2"></div>
            <div class="indicator" data-slide="3"></div>
            <div class="indicator" data-slide="4"></div>
        </div>
    </section>
    <section id="destinos" class="section destinations">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title" data-es="Destinos Extraordinarios" data-en="Extraordinary Destinations">Destinos Extraordinarios</h2>
                <p class="section-subtitle" data-es="Descubre lugares mágicos cerca de Cusco, cuidadosamente seleccionados para ofrecerte experiencias auténticas e inolvidables en el corazón del antiguo Imperio Inca." 
                   data-en="Discover magical places near Cusco, carefully selected to offer you authentic and unforgettable experiences in the heart of the ancient Inca Empire.">
                    Descubre lugares mágicos cerca de Cusco, cuidadosamente seleccionados para ofrecerte experiencias auténticas e inolvidables en el corazón del antiguo Imperio Inca.
                </p>
            </div>
            <div class="destinations-grid">
                <div class="destination-card slide-in-left">
                    <div class="destination-image" style="background-image: url('https://images.unsplash.com/photo-1526392060635-9d6019884377?w=600&h=400&fit=crop')">
                        <div class="destination-overlay">
                            <div class="overlay-content">
                                <i class="fas fa-mountain fa-3x"></i>
                                <p data-es="La ciudadela perdida de los incas" data-en="The lost citadel of the Incas">La ciudadela perdida de los incas</p>
                            </div>
                        </div>
                    </div>
                    <div class="destination-content">
                        <h3 class="destination-title" data-es="Machu Picchu" data-en="Machu Picchu">Machu Picchu</h3>
                        <p class="destination-desc" data-es="Explora la misteriosa ciudadela inca, declarada Patrimonio de la Humanidad. Una experiencia que combina historia milenaria, arquitectura impresionante y paisajes de ensueño." 
                           data-en="Explore the mysterious Inca citadel, declared a World Heritage Site. An experience that combines ancient history, impressive architecture, and dreamlike landscapes.">
                            Explora la misteriosa ciudadela inca, declarada Patrimonio de la Humanidad. Una experiencia que combina historia milenaria, arquitectura impresionante y paisajes de ensueño.
                        </p>
                        <a href="destinos.php" class="btn btn-secondary protected-link" data-es="Ver Más Destinos" data-en="View More Destinations">
                            <i class="fas fa-eye"></i>
                            <span>Ver Más Destinos</span>
                        </a>
                    </div>
                </div>
                <div class="destination-card slide-in-right">
                    <div class="destination-image" style="background-image: url('https://image-tc.galaxy.tf/wijpeg-7s1v8e5km8dojs4ckc2dhyi4t/valle-sagrado-destino-unico_wide.jpg?crop=26%2C0%2C1548%2C871')">
                        <div class="destination-overlay">
                            <div class="overlay-content">
                                <i class="fas fa-palette fa-3x"></i>
                                <p data-es="El valle de los colores" data-en="The valley of colors">El valle de los colores</p>
                            </div>
                        </div>
                    </div>
                    <div class="destination-content">
                        <h3 class="destination-title" data-es="Valle Sagrado" data-en="Sacred Valley">Valle Sagrado</h3>
                        <p class="destination-desc" data-es="Recorre el místico Valle Sagrado con sus terrazas ancestrales, pueblos tradicionales y paisajes que han permanecido inalterados por siglos." 
                           data-en="Explore the mystical Sacred Valley with its ancestral terraces, traditional villages, and landscapes that have remained unchanged for centuries.">
                            Recorre el místico Valle Sagrado con sus terrazas ancestrales, pueblos tradicionales y paisajes que han permanecido inalterados por siglos.
                        </p>
                        <a href="destinos.php" class="btn btn-secondary" data-es="Ver Más Destinos" data-en="View More Destinations">
                            <i class="fas fa-eye"></i>
                            <span>Ver Más Destinos</span>
                        </a>
                    </div>
                </div>
                <div class="destination-card slide-in-left">
                    <div class="destination-image" style="background-image: url('https://vivedestinos.com/wp-content/uploads/2024/06/4-1024x538.jpg')">
                        <div class="destination-overlay">
                            <div class="overlay-content">
                                <i class="fas fa-mountain fa-3x"></i>
                                <p data-es="La montaña de siete colores" data-en="The mountain of seven colors">La montaña de siete colores</p>
                            </div>
                        </div>
                    </div>
                    <div class="destination-content">
                        <h3 class="destination-title" data-es="Vinicunca" data-en="Vinicunca">Vinicunca</h3>
                        <p class="destination-desc" data-es="Descubre la famosa Montaña de 7 Colores, un fenómeno natural único que te dejará sin aliento con sus tonalidades minerales espectaculares." 
                           data-en="Discover the famous Rainbow Mountain, a unique natural phenomenon that will leave you breathless with its spectacular mineral hues.">
                            Descubre la famosa Montaña de 7 Colores, un fenómeno natural único que te dejará sin aliento con sus tonalidades minerales espectaculares.
                        </p>
                        <a href="destinos.php" class="btn btn-secondary" data-es="Ver Más Destinos" data-en="View More Destinations">
                            <i class="fas fa-eye"></i>
                            <span>Ver Más Destinos</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="guias" class="section guides">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title" data-es="Guías Especializados" data-en="Expert Guides">Guías Especializados</h2>
                <p class="section-subtitle" data-es="Conoce a nuestro equipo de guías expertos locales certificados, apasionados por compartir la historia, cultura y secretos ancestrales de cada destino con conocimiento profundo y experiencia única." 
                   data-en="Meet our team of certified local expert guides, passionate about sharing the history, culture, and ancestral secrets of each destination with deep knowledge and unique experience.">
                    Conoce a nuestro equipo de guías expertos locales certificados, apasionados por compartir la historia, cultura y secretos ancestrales de cada destino con conocimiento profundo y experiencia única.
                </p>
            </div>
            <div class="guides-grid">
                <div class="guide-card fade-in">
                    <div class="guide-image" style="background-image: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=face')">
                        <div class="guide-overlay">
                            <p data-es="15 años de experiencia guiando en Machu Picchu" data-en="15 years of experience guiding in Machu Picchu">15 años de experiencia guiando en Machu Picchu</p>
                        </div>
                    </div>
                    <div class="guide-content">
                        <h3 class="guide-name" data-es="Carlos Quispe" data-en="Carlos Quispe">Carlos Quispe</h3>
                        <p class="guide-specialty" data-es="Especialista en Historia Inca" data-en="Inca History Specialist">Especialista en Historia Inca</p>
                        <p class="guide-desc" data-es="Guía certificado con profundo conocimiento de la cultura inca y arqueología andina. Experto en rutas de trekking y ceremonias ancestrales." 
                           data-en="Certified guide with deep knowledge of Inca culture and Andean archaeology. Expert in trekking routes and ancestral ceremonies.">
                            Guía certificado con profundo conocimiento de la cultura inca y arqueología andina. Experto en rutas de trekking y ceremonias ancestrales.
                        </p>
                        <a href="guias.php" class="btn btn-secondary protected-link" data-es="Ver Todos los Guías" data-en="View All Guides">
                            <i class="fas fa-users"></i>
                            <span>Ver Todos los Guías</span>
                        </a>
                    </div>
                </div>
                <div class="guide-card fade-in">
                    <div class="guide-image" style="background-image: url('https://images.unsplash.com/photo-1494790108755-2616b612b547?w=400&h=400&fit=crop&crop=face')">
                        <div class="guide-overlay">
                            <p data-es="Experta en flora y fauna andina" data-en="Expert in Andean flora and fauna">Experta en flora y fauna andina</p>
                        </div>
                    </div>
                    <div class="guide-content">
                        <h3 class="guide-name" data-es="María Huamán" data-en="María Huamán">María Huamán</h3>
                        <p class="guide-specialty" data-es="Guía de Naturaleza" data-en="Nature Guide">Guía de Naturaleza</p>
                        <p class="guide-desc" data-es="Especialista en ecoturismo y biodiversidad andina. Conocedora de plantas medicinales y tradiciones ancestrales de las comunidades locales." 
                           data-en="Specialist in ecotourism and Andean biodiversity. Expert in medicinal plants and ancestral traditions of local communities.">
                            Especialista en ecoturismo y biodiversidad andina. Conocedora de plantas medicinales y tradiciones ancestrales de las comunidades locales.
                        </p>
                        <a href="guias.php" class="btn btn-secondary" data-es="Ver Todos los Guías" data-en="View All Guides">
                            <i class="fas fa-users"></i>
                            <span>Ver Todos los Guías</span>
                        </a>
                    </div>
                </div>
                <div class="guide-card fade-in">
                    <div class="guide-image" style="background-image: url('https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=400&fit=crop&crop=face')">
                        <div class="guide-overlay">
                            <p data-es="Aventurero y montañista experimentado" data-en="Adventurer and experienced mountaineer">Aventurero y montañista experimentado</p>
                        </div>
                    </div>
                    <div class="guide-content">
                        <h3 class="guide-name" data-es="Javier Condori" data-en="Javier Condori">Javier Condori</h3>
                        <p class="guide-specialty" data-es="Guía de Aventura" data-en="Adventure Guide">Guía de Aventura</p>
                        <p class="guide-desc" data-es="Guía especializado en rutas de alta montaña y aventuras extremas. Certificado en primeros auxilios y rescate en montaña." 
                           data-en="Guide specialized in high mountain routes and extreme adventures. Certified in first aid and mountain rescue.">
                            Guía especializado en rutas de alta montaña y aventuras extremas. Certificado en primeros auxilios y rescate en montaña.
                        </p>
                        <a href="guias.php" class="btn btn-secondary" data-es="Ver Todos los Guías" data-en="View All Guides">
                            <i class="fas fa-users"></i>
                            <span>Ver Todos los Guías</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="fotos" class="section photos">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title" data-es="Galería de Experiencias" data-en="Experience Gallery">Galería de Experiencias</h2>
                <p class="section-subtitle" data-es="Momentos capturados de nuestras aventuras más extraordinarias. Cada imagen cuenta una historia de descubrimiento, emoción y conexión con la majestuosa belleza de los Andes peruanos." 
                   data-en="Captured moments from our most extraordinary adventures. Each image tells a story of discovery, emotion, and connection with the majestic beauty of the Peruvian Andes.">
                    Momentos capturados de nuestras aventuras más extraordinarias. Cada imagen cuenta una historia de descubrimiento, emoción y conexión con la majestuosa belleza de los Andes peruanos.
                </p>
            </div>
            <div class="photos-grid">
                <div class="photo-card slide-in-left">
                    <div class="photo-image" style="background-image: url('https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=600&h=400&fit=crop')"></div>
                    <div class="photo-overlay">
                        <h4 class="photo-title" data-es="Amanecer en Machu Picchu" data-en="Sunrise at Machu Picchu">Amanecer en Machu Picchu</h4>
                        <p class="photo-location" data-es="Ciudadela Inca" data-en="Inca Citadel">Ciudadela Inca</p>
                    </div>
                </div>
                <div class="photo-card fade-in">
                    <div class="photo-image" style="background-image: url('https://images.unsplash.com/photo-1531065208531-4036c0dba3ca?w=600&h=400&fit=crop')"></div>
                    <div class="photo-overlay">
                        <h4 class="photo-title" data-es="Montaña de Colores" data-en="Rainbow Mountain">Montaña de Colores</h4>
                        <p class="photo-location" data-es="Vinicunca" data-en="Vinicunca">Vinicunca</p>
                    </div>
                </div>
                <div class="photo-card slide-in-right">
                    <div class="photo-image" style="background-image: url('https://images.unsplash.com/photo-1594736797933-d0401ba6fe65?w=600&h=400&fit=crop')"></div>
                    <div class="photo-overlay">
                        <h4 class="photo-title" data-es="Valle Sagrado" data-en="Sacred Valley">Valle Sagrado</h4>
                        <p class="photo-location" data-es="Terrazas Incas" data-en="Inca Terraces">Terrazas Incas</p>
                    </div>
                </div>
                <div class="photo-card slide-in-left">
                    <div class="photo-image" style="background-image: url('https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=600&h=400&fit=crop')"></div>
                    <div class="photo-overlay">
                        <h4 class="photo-title" data-es="Laguna Humantay" data-en="Humantay Lake">Laguna Humantay</h4>
                        <p class="photo-location" data-es="Cordillera Vilcabamba" data-en="Vilcabamba Range">Cordillera Vilcabamba</p>
                    </div>
                </div>
                <div class="photo-card fade-in">
                    <div class="photo-image" style="background-image: url('https://images.unsplash.com/photo-1511593358241-7eea1f3c84e5?w=600&h=400&fit=crop')"></div>
                    <div class="photo-overlay">
                        <h4 class="photo-title" data-es="Salar de Uyuni" data-en="Uyuni Salt Flats">Salar de Uyuni</h4>
                        <p class="photo-location" data-es="Bolivia" data-en="Bolivia">Bolivia</p>
                    </div>
                </div>
                <div class="photo-card slide-in-right">
                    <div class="photo-image" style="background-image: url('https://images.unsplash.com/photo-1582391505156-5a7ba52473bd?w=600&h=400&fit=crop')"></div>
                    <div class="photo-overlay">
                        <h4 class="photo-title" data-es="Camino Inca" data-en="Inca Trail">Camino Inca</h4>
                        <p class="photo-location" data-es="Sendero Ancestral" data-en="Ancient Trail">Sendero Ancestral</p>
                    </div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 50px;">
                <a href="fotos.php" class="btn btn-primary" data-es="Ver Más Fotos" data-en="View More Photos">
                    <i class="fas fa-images"></i>
                    <span>Ver Más Fotos</span>
                </a>
            </div>
        </div>
    </section>

    <section id="comentarios" class="section comments-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title" data-es="Lo que dicen nuestros viajeros" data-en="What Our Travelers Say">Lo que dicen nuestros viajeros</h2>
                <p class="section-subtitle" data-es="Experiencias reales de quienes han viajado con nosotros." data-en="Real experiences from those who have traveled with us.">Experiencias reales de quienes han viajado con nosotros.</p>
            </div>
            
            <div class="comments-header">
                <div class="filters">
                    <button class="filter-btn active" data-filter="recent" data-es="Más Recientes" data-en="Most Recent">Más Recientes</button>
                    <button class="filter-btn" data-filter="highest" data-es="Mejor Valorados" data-en="Highest Rated">Mejor Valorados</button>
                    <button class="filter-btn" data-filter="lowest" data-es="Menor Valoración" data-en="Lowest Rated">Menor Valoración</button>
                </div>
            </div>

            <div class="comments-grid" id="comments-container">

            </div>

            <div class="add-comment-section">
                <?php if ($is_logged_in): ?>
                    <h3 data-es="Comparte tu experiencia" data-en="Share Your Experience">Comparte tu experiencia</h3>
                    <form id="comment-form">
                        <div class="form-group star-rating-input">
                            <input type="radio" id="star5" name="rating" value="5"><label for="star5" title="5 estrellas">★</label>
                            <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 estrellas">★</label>
                            <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 estrellas">★</label>
                            <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 estrellas">★</label>
                            <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 estrella">★</label>
                        </div>
                        <div class="form-group">
                            <textarea name="comment" rows="4" required data-es-placeholder="Escribe tu comentario aquí..." data-en-placeholder="Write your comment here..."></textarea>
                        </div>
                        <div style="text-align: center;">
                            <button type="submit" class="btn btn-primary" data-es="Publicar Comentario" data-en="Post Comment">
                                <i class="fas fa-paper-plane"></i>
                                <span>Publicar Comentario</span>
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="login-prompt">
                        <h3 data-es="¿Quieres dejar un comentario?" data-en="Want to leave a comment?">¿Quieres dejar un comentario?</h3>
                        <p data-es="Inicia sesión para compartir tu experiencia con la comunidad." data-en="Log in to share your experience with the community."></p>
                        <a href="src/auth/login.php" class="btn btn-primary" data-es="Iniciar Sesión" data-en="Login">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section id="reservas" class="section reservas">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title" data-es="Reserva tu Aventura" data-en="Book Your Adventure">Reserva tu Aventura</h2>
                <p class="section-subtitle" data-es="Comienza tu experiencia única llenando nuestro formulario. Nuestro equipo se contactará contigo para personalizar tu aventura perfecta en los destinos más extraordinarios de Perú." 
                   data-en="Start your unique experience by filling out our form. Our team will contact you to customize your perfect adventure in Peru's most extraordinary destinations.">
                    Comienza tu experiencia única llenando nuestro formulario. Nuestro equipo se contactará contigo para personalizar tu aventura perfecta en los destinos más extraordinarios de Perú.
                </p>
            </div>
            <div class="reserva-form fade-in">
                <form>
                    <div class="form-row">
                        <div class="form-group">
                            <label data-es="Nombre" data-en="Name">Nombre</label>
                            <input type="text" required>
                        </div>
                        <div class="form-group">
                            <label data-es="Apellido" data-en="Last Name">Apellido</label>
                            <input type="text" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label data-es="Email" data-en="Email">Email</label>
                            <input type="email" required>
                        </div>
                        <div class="form-group">
                            <label data-es="Teléfono" data-en="Phone">Teléfono</label>
                            <input type="tel" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label data-es="Destino de Interés" data-en="Destination of Interest">Destino de Interés</label>
                            <select required>
                                <option value="" data-es="Seleccionar destino" data-en="Select destination">Seleccionar destino</option>
                                <option value="machu-picchu" data-es="Machu Picchu" data-en="Machu Picchu">Machu Picchu</option>
                                <option value="valle-sagrado" data-es="Valle Sagrado" data-en="Sacred Valley">Valle Sagrado</option>
                                <option value="vinicunca" data-es="Vinicunca" data-en="Vinicunca">Vinicunca</option>
                                <option value="salar-uyuni" data-es="Salar de Uyuni" data-en="Uyuni Salt Flats">Salar de Uyuni</option>
                                <option value="camino-inca" data-es="Camino Inca" data-en="Inca Trail">Camino Inca</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label data-es="Número de Personas" data-en="Number of People">Número de Personas</label>
                            <select required>
                                <option value="">1</option>
                                <option value="">2</option>
                                <option value="">3</option>
                                <option value="">4</option>
                                <option value="">5+</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label data-es="Fecha Preferida" data-en="Preferred Date">Fecha Preferida</label>
                            <input type="date" required>
                        </div>
                        <div class="form-group">
                            <label data-es="Duración del Viaje" data-en="Trip Duration">Duración del Viaje</label>
                            <select required>
                                <option value="" data-es="Seleccionar duración" data-en="Select duration">Seleccionar duración</option>
                                <option value="1-day" data-es="1 día" data-en="1 day">1 día</option>
                                <option value="2-3-days" data-es="2-3 días" data-en="2-3 days">2-3 días</option>
                                <option value="4-7-days" data-es="4-7 días" data-en="4-7 days">4-7 días</option>
                                <option value="1-week+" data-es="1 semana+" data-en="1 week+">1 semana+</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label data-es="Comentarios Adicionales" data-en="Additional Comments">Comentarios Adicionales</label>
                        <textarea rows="4" placeholder="Cuéntanos sobre tus intereses específicos, nivel de experiencia, necesidades especiales, etc."></textarea>
                    </div>
                    <div style="text-align: center;">
                        <button type="submit" class="btn btn-primary" data-es="Enviar Reserva" data-en="Send Reservation">
                            <i class="fas fa-paper-plane"></i>
                            <span>Enviar Reserva</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>ANTARES TRAVEL</h3>
                    <p data-es="Más que una agencia de viajes, somos creadores de experiencias extraordinarias. Desde 2010, hemos ayudado a miles de viajeros a descubrir la magia de Cusco y sus alrededores de manera auténtica y memorable." 
                       data-en="More than a travel agency, we are creators of extraordinary experiences. Since 2010, we have helped thousands of travelers discover the magic of Cusco and its surroundings in an authentic and memorable way.">
                        Más que una agencia de viajes, somos creadores de experiencias extraordinarias. Desde 2010, hemos ayudado a miles de viajeros a descubrir la magia de Cusco y sus alrededores de manera auténtica y memorable.
                    </p>
                    <p><strong data-es="Registro Turístico" data-en="Tourist Registration">Registro Turístico:</strong> AT-2024-001</p>
                    <p><strong data-es="Certificación" data-en="Certification">Certificación:</strong> ISO 9001:2015</p>
                    <div style="margin-top: 20px;">
                        <a href="#" style="margin-right: 15px; font-size: 1.5rem; color: var(--primary-light);"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="margin-right: 15px; font-size: 1.5rem; color: var(--primary-light);"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="margin-right: 15px; font-size: 1.5rem; color: var(--primary-light);"><i class="fab fa-youtube"></i></a>
                        <a href="#" style="margin-right: 15px; font-size: 1.5rem; color: var(--primary-light);"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3 data-es="Experiencias" data-en="Experiences">Experiencias</h3>
                    <ul>
                        <li><a href="#" data-es="Aventura Extrema" data-en="Extreme Adventure"><i class="fas fa-mountain"></i> Aventura Extrema</a></li>
                        <li><a href="#" data-es="Viajes Culturales" data-en="Cultural Trips"><i class="fas fa-users"></i> Viajes Culturales</a></li>
                        <li><a href="#" data-es="Escapadas Románticas" data-en="Romantic Getaways"><i class="fas fa-heart"></i> Escapadas Románticas</a></li>
                        <li><a href="#" data-es="Viajes Familiares" data-en="Family Trips"><i class="fas fa-child"></i> Viajes Familiares</a></li>
                        <li><a href="#" data-es="Experiencias de Lujo" data-en="Luxury Experiences"><i class="fas fa-gem"></i> Experiencias de Lujo</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 data-es="Contacto" data-en="Contact">Contacto</h3>
                    <ul>
                        <li><a href="#" data-es="Av. Sol 123, Cusco, Perú" data-en="Av. Sol 123, Cusco, Peru"><i class="fas fa-map-marker-alt"></i> Av. Sol 123, Cusco, Perú</a></li>
                        <li><a href="tel:+51084234567"><i class="fas fa-phone"></i> +51 84 234 567</a></li>
                        <li><a href="tel:+51999888777"><i class="fas fa-mobile-alt"></i> +51 999 888 777</a></li>
                        <li><a href="mailto:info@antarestravel.com"><i class="fas fa-envelope"></i> info@antarestravel.com</a></li>
                        <li><a href="#"><i class="fas fa-globe"></i> www.antarestravel.com</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 data-es="Horarios" data-en="Hours">Horarios</h3>
                    <ul>
                        <li><i class="fas fa-clock"></i> <span data-es="Lunes - Viernes: 8:00 - 18:00" data-en="Monday - Friday: 8:00 AM - 6:00 PM">Lunes - Viernes: 8:00 - 18:00</span></li>
                        <li><i class="fas fa-clock"></i> <span data-es="Sábados: 9:00 - 16:00" data-en="Saturdays: 9:00 AM - 4:00 PM">Sábados: 9:00 - 16:00</span></li>
                        <li><i class="fas fa-clock"></i> <span data-es="Domingos: 10:00 - 14:00" data-en="Sundays: 10:00 AM - 2:00 PM">Domingos: 10:00 - 14:00</span></li>
                        <li><i class="fas fa-phone"></i> <span data-es="Emergencias 24/7" data-en="24/7 Emergencies">Emergencias 24/7</span></li>
                    </ul>
                    <div style="margin-top: 20px;">
                        <a href="#reservas" class="btn btn-primary" data-es="Reservar Ahora" data-en="Book Now">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Reservar Ahora</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>
                    <span data-es="&copy; 2024 Antares Travel. Todos los derechos reservados." data-en="&copy; 2024 Antares Travel. All rights reserved.">&copy; 2024 Antares Travel. Todos los derechos reservados.</span> | 
                    <a href="#" style="color: var(--primary-light);" data-es="Política de Privacidad" data-en="Privacy Policy">Política de Privacidad</a> | 
                    <a href="#" style="color: var(--primary-light);" data-es="Términos y Condiciones" data-en="Terms and Conditions">Términos y Condiciones</a>
                </p>
            </div>
        </div>
    </footer>
    <script>
        function handleCredentialResponse(response) {
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "index.php";
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "credential";
            input.value = response.credential;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        const langButtons = document.querySelectorAll('.lang-btn');
        const langElements = document.querySelectorAll('[data-es][data-en]');
        let currentLang = 'es';

        function updateLanguage(lang) {
            langElements.forEach(element => {
                const text = element.getAttribute(`data-${lang}`);
                if (text) {
                    if (element.querySelector('i')) {
                        const icon = element.querySelector('i').outerHTML;
                        element.innerHTML = `${icon} <span>${text}</span>`;
                    } else {
                        element.textContent = text;
                    }
                }
            });
            document.documentElement.lang = lang;
            currentLang = lang;
            langButtons.forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
            });
        }

        const savedLang = localStorage.getItem('language') || 'es';
        updateLanguage(savedLang);

        langButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const lang = btn.getAttribute('data-lang');
                updateLanguage(lang);
                localStorage.setItem('language', lang);
            });
        });

        const heroImages = document.querySelectorAll('.hero-image');
        const indicators = document.querySelectorAll('.indicator');
        let currentSlide = 0;

        function showSlide(index) {
            const currentActive = document.querySelector('.hero-image.active');
            heroImages.forEach((img, i) => {
                img.classList.remove('active', 'prev');
            });
            if (currentActive) {
                currentActive.classList.add('prev');
            }
            heroImages[index].classList.add('active');
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % heroImages.length;
            showSlide(currentSlide);
        }

        setInterval(nextSlide, 8000);

        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 100);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right').forEach(el => {
            observer.observe(el);
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const navHeight = document.querySelector('.navbar').offsetHeight;
                    const targetPosition = target.offsetTop - navHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    const mobileNav = document.querySelector('.mobile-nav');
                    const mobileMenu = document.querySelector('.mobile-menu');
                    if (mobileNav.classList.contains('active')) {
                        mobileNav.classList.remove('active');
                        mobileMenu.classList.remove('active');
                    }
                }
            });
        });

        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            const mobileNav = document.querySelector('.mobile-nav');
            const mobileMenu = document.querySelector('.mobile-menu');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
                if (mobileNav.classList.contains('active')) {
                    mobileNav.classList.remove('active');
                    mobileMenu.classList.remove('active');
                }
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        const mobileMenu = document.querySelector('.mobile-menu');
        const mobileNav = document.querySelector('.mobile-nav');

        mobileMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileNav.classList.toggle('active');
        });

        document.querySelectorAll('.destination-card, .guide-card, .photo-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroContent = document.querySelector('.hero-content');
            const heroImages = document.querySelectorAll('.hero-image');
            if (heroContent && scrolled < window.innerHeight) {
                heroContent.style.transform = `translateY(${scrolled * 0.3}px)`;
                heroImages.forEach(img => {
                    img.style.transform = `translateY(${scrolled * 0.2}px)`;
                });
            }
        });

        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.background = 'rgba(255,255,255,0.3)';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple-animation 0.6s linear';
                ripple.style.pointerEvents = 'none';
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
                this.appendChild(ripple);
                setTimeout(() => {
                    ripple.remove();
                }, 800);
            });
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            .btn {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(style);

        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Enviando...</span>';
                submitBtn.disabled = true;
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> <span>¡Enviado!</span>';
                    submitBtn.style.background = '#28a745';
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        submitBtn.style.background = '';
                        form.reset();
                    }, 2000);
                }, 1500);
            });
        }

        window.addEventListener('load', () => {
            document.body.style.opacity = '1';
            const heroContent = document.querySelector('.hero-content');
            if (heroContent) {
                heroContent.style.animation = 'heroSlideUp 1s ease-out';
            }
        });

        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.3s ease-in-out';

        document.addEventListener('DOMContentLoaded', () => {
            const langButtons = document.querySelectorAll('.lang-btn');
            const translatableElements = document.querySelectorAll('[data-es], [data-en]');
            let currentLang = localStorage.getItem('language') || 'es';

            function updateLanguage(lang) {
                translatableElements.forEach(el => {
                    const text = el.getAttribute(`data-${lang}`);
                    if (text) {

                        const icon = el.querySelector('i');
                        if (el.tagName === 'A' || el.tagName === 'BUTTON' && el.querySelector('span')) {
                            el.querySelector('span').textContent = text;
                        } else {
                            el.textContent = text;
                        }
                        if(icon) el.prepend(icon);
                    }

                    const placeholderText = el.getAttribute(`data-${lang}-placeholder`);
                    if(placeholderText) {
                        el.placeholder = placeholderText;
                    }
                });

                document.documentElement.lang = lang;
                currentLang = lang;
                langButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.lang === lang));
                localStorage.setItem('language', lang);
            }

            langButtons.forEach(btn => {
                btn.addEventListener('click', () => updateLanguage(btn.dataset.lang));
            });
            
            updateLanguage(currentLang);

            document.querySelectorAll('.protected-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!isLoggedIn) {
                    e.preventDefault();
                    const lang = localStorage.getItem('language') || 'es';
                    const originalHref = this.getAttribute('href');
                    if (originalHref.includes('login')) {
                        window.location.href = `src/auth/login.php?lang=${lang}`;
                    } else {
                        window.location.href = `src/auth/login.php?lang=${lang}`;
                    }
                }
            });
        });

            const commentsContainer = document.getElementById('comments-container');
            const filterBtns = document.querySelectorAll('.filter-btn');

            async function fetchComments(filter = 'recent') {
                try {

                    const response = await fetch(`src/api/fetch_comments.php?filter=${filter}`);
                    if (!response.ok) throw new Error('Network response was not ok');
                    
                    const comments = await response.json();
                    commentsContainer.innerHTML = ''; 

                    if (comments.length === 0) {
                        commentsContainer.innerHTML = `<p data-es="No hay comentarios aún. ¡Sé el primero!" data-en="No comments yet. Be the first!">No hay comentarios aún. ¡Sé el primero!</p>`;
                    } else {
                        comments.forEach(comment => {
                            const stars = '★'.repeat(comment.calificacion) + '☆'.repeat(5 - comment.calificacion);
                            const commentCard = `
                                <div class="comment-card">
                                    <div class="comment-card-header">
                                        <img src="${comment.avatar_url || 'imagenes/default_avatar.png'}" alt="Avatar">
                                        <div>
                                            <div class="comment-user-name">${comment.nombre}</div>
                                            <div class="comment-rating">${stars}</div>
                                        </div>
                                    </div>
                                    <p class="comment-body">${comment.comentario}</p>
                                </div>
                            `;
                            commentsContainer.innerHTML += commentCard;
                        });
                    }
                    updateLanguage(currentLang);
                } catch (error) {
                    console.error('Error fetching comments:', error);
                    commentsContainer.innerHTML = `<p data-es="Error al cargar comentarios." data-en="Error loading comments.">Error al cargar comentarios.</p>`;
                }
            }
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    fetchComments(btn.dataset.filter);
                });
            });

            fetchComments();
            
            const commentForm = document.getElementById('comment-form');
            if(commentForm) {
                commentForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;

                    try {
                        const response = await fetch('src/api/submit_comment.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if(result.success) {
                            fetchComments();
                            this.reset();
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error submitting comment:', error);
                        alert('Hubo un error al enviar tu comentario.');
                    } finally {
                        submitBtn.disabled = false;
                        updateLanguage(currentLang);
                    }
                });
            }
        });
    </script>
</body>
</html>