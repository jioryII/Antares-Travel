<?php
session_start();
require_once __DIR__ . '/src/config/conexion.php';
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
    session_start();
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

$is_logged_in = isset($_SESSION['user_email']);
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
    <script>
        // Configuración de Google One Tap
        window.onload = function () {
            google.accounts.id.initialize({
                client_id: '454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com',
                callback: handleCredentialResponse,
                auto_select: true,
                cancel_on_tap_outside: false
            });

            // Mostrar One Tap solo si no está logueado
            <?php if (!$is_logged_in): ?>
            google.accounts.id.prompt((notification) => {
                console.log('One Tap notification:', notification);
            });
            <?php endif; ?>
        };

        // Función para manejar la respuesta del credential
        function handleCredentialResponse(response) {
            console.log('Credential received:', response);
            
            // Crear un formulario para enviar el credential al servidor
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'credential';
            input.value = response.credential;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <style>
        .tours-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .tour-categories {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 40px;
            gap: 15px;
        }
        
        .category-btn {
            padding: 12px 25px;
            background: white;
            border: 2px solid #4e73df;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .category-btn.active, .category-btn:hover {
            background: #4e73df;
            color: white;
        }
        
        .tours-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .tour-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .tour-card:hover {
            transform: translateY(-10px);
        }
        
        .tour-header {
            padding: 20px;
            background: #4e73df;
            color: white;
        }
        
        .tour-title {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .tour-schedule {
            margin: 5px 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .tour-content {
            padding: 20px;
        }
        
        .tour-includes {
            margin: 15px 0;
        }
        
        .tour-includes ul {
            padding-left: 20px;
            margin: 10px 0;
        }
        
        .tour-includes li {
            margin-bottom: 8px;
            position: relative;
        }
        
        .tour-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #4e73df;
            margin: 15px 0;
        }
        
        .tour-contact {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .contact-info {
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .tour-image {
            height: 200px;
            background-size: cover;
            background-position: center;
        }
    </style>
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
                <li><a href="#tours" data-es="Tours" data-en="Tours">Tours</a></li>
                <li><a href="<?php echo $is_logged_in ? 'guias.php' : 'src/auth/login.php'; ?>" data-es="Guías" data-en="Guides">Guías</a></li>
                <li><a href="<?php echo $is_logged_in ? 'fotos.php' : 'src/auth/login.php'; ?>" data-es="Fotos" data-en="Photos">Fotos</a></li>
                <li><a href="#reservas" data-es="Reservas" data-en="Reservations">Reservas</a></li>
            </ul>
            <div class="auth-buttons">
                <div class="lang-switch">
                    <button class="lang-btn active" data-lang="es">ES</button>
                    <button class="lang-btn" data-lang="en">EN</button>
                </div>
                <?php if (!$is_logged_in): ?>
                    <a href="src/auth/login.php" class="btn btn-secondary" data-es="Iniciar Sesión" data-en="Login">
                        <i class="fas fa-user"></i>
                        <span>Iniciar Sesión</span>
                    </a>
                    <a href="src/auth/register.php" class="btn btn-primary" data-es="Registrarse" data-en="Sign Up">
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
        <a href="#tours" data-es="Tours" data-en="Tours">Tours</a>
        <a href="<?php echo $is_logged_in ? 'guias.php' : 'src/auth/login.php'; ?>" data-es="Guías" data-en="Guides">Guías</a>
        <a href="<?php echo $is_logged_in ? 'fotos.php' : 'src/auth/login.php'; ?>" data-es="Fotos" data-en="Photos">Fotos</a>
        <a href="#reservas" data-es="Reservas" data-en="Reservations">Reservas</a>
        <div class="mobile-auth-buttons">
            <div class="lang-switch">
                <button class="lang-btn active" data-lang="es">ES</button>
                <button class="lang-btn" data-lang="en">EN</button>
            </div>
            <?php if (!$is_logged_in): ?>
                <a href="src/auth/login.php" class="btn btn-secondary" data-es="Iniciar Sesión" data-en="Login"><i class="fas fa-user"></i><span>Iniciar Sesión</span></a>
                <a href="src/auth/register.php" class="btn btn-primary" data-es="Registrarse" data-en="Sign Up"><i class="fas fa-user-plus"></i><span>Registrarse</span></a>
            <?php else: ?>
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_picture']); ?>" alt="Avatar de usuario">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="index.php?logout=1" class="btn btn-primary" data-es="Cerrar Sesión" data-en="Logout"><i class="fas fa-sign-out-alt"></i></a>
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
                <p data-es="Experiencias únicas que transforman tu forma de viajar. Desde aventuras épicas hasta escapadas relajantes, creamos momentos inolvidables en los destinos más extraordinarios del mundo." data-en="Unique experiences that transform the way you travel. From epic adventures to relaxing getaways, we create unforgettable moments in the world's most extraordinary destinations.">Experiencias únicas que transforman tu forma de viajar. Desde aventuras épicas hasta escapadas relajantes, creamos momentos inolvidables en los destinos más extraordinarios del mundo.</p>
                <div class="hero-buttons">
                    <a href="#tours" class="btn btn-primary" data-es="Explorar Tours" data-en="Explore Tours"><i class="fas fa-compass"></i><span>Explorar Tours</span></a>
                    <a href="#reservas" class="btn btn-secondary" data-es="Reservar Ahora" data-en="Book Now"><i class="fas fa-calendar-alt"></i><span>Reservar Ahora</span></a>
                </div>
            </div>
        </div>
        <div class="hero-indicators"></div>
    </section>

    <section id="tours" class="section tours-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title" data-es="Nuestros Tours" data-en="Our Tours">Nuestros Tours</h2>
                <p class="section-subtitle" data-es="Descubre nuestras experiencias únicas en Cusco y sus alrededores" data-en="Discover our unique experiences in Cusco and surroundings">Descubre nuestras experiencias únicas en Cusco y sus alrededores</p>
            </div>

            <div class="tour-categories">
                <button class="category-btn active" data-category="all">Todos los Tours</button>
                <button class="category-btn" data-category="cusco">Cusco y Valle Sagrado</button>
                <button class="category-btn" data-category="aventura">Aventura</button>
                <button class="category-btn" data-category="multi-day">Tours Multi-día</button>
            </div>

            <div class="tours-container">
                <!-- Tour 1: MORAY Y MINAS DE SAL -->
                <div class="tour-card" data-category="cusco">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">MORAY Y MINAS DE SAL</h3>
                        <div class="tour-schedule">Todos los días de 9:00 a.m. a 3:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Turístico</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 2: VALLE SUR -->
                <div class="tour-card" data-category="cusco">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1531065208531-4036c0dba3ca?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">VALLE SUR</h3>
                        <div class="tour-schedule">Todos los días de: 9:00 a.m. a 2:30 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Guía Bilingüe (Español / Inglés)</li>
                                <li>Tipón, Pikillagta y Andahuaylillas</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 3: CUATRIMOTOS MORAY SALINERAS -->
                <div class="tour-card" data-category="aventura">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">CUATRIMOTOS MORAY SALINERAS</h3>
                        <div class="tour-schedule">Salidas de 7:00 a.m. a 1:00 p.m. / 1:15 p.m. a 6:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Transporte ida y retorno</li>
                                <li>Cuatrimotos</li>
                                <li>Equipo (Casco, Rodilleras, Coderas)</li>
                                <li>Guía Profesional</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 4: TRANSPORTE PRIVADO -->
                <div class="tour-card" data-category="cusco">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">TRANSPORTE PRIVADO</h3>
                        <div class="tour-schedule">CUSCO - OLLANTAYTAMBO - CUSCO</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 5: CITY TOUR -->
                <div class="tour-card" data-category="cusco">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">CITY TOUR</h3>
                        <div class="tour-schedule">Todos los días de: 10:00 a.m. a 1:00 p.m. / 2:00 p.m. a 6:30 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>Visitamos:</strong>
                            <ul>
                                <li>K'orikancha</li>
                                <li>Sacsayhuaman</li>
                                <li>Q'engo</li>
                                <li>Pucapucara</li>
                                <li>Tambomachay</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 6: VALLE SAGRADO DE LOS INCAS -->
                <div class="tour-card" data-category="cusco">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1594736797933-d0401ba6fe65?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">VALLE SAGRADO DE LOS INCAS</h3>
                        <div class="tour-schedule">Todos los días de: 9:00 a.m. - 7:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>Visitamos:</strong>
                            <ul>
                                <li>Mercado típico</li>
                                <li>Grupos arqueológicos de Pisaq</li>
                                <li>Ollantaytambo</li>
                                <li>Chinchero</li>
                            </ul>
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Turístico y Guía Bilingüe (almuerzo opcional)</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 7: SUPER VALLE -->
                <div class="tour-card" data-category="cusco">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1594736797933-d0401ba6fe65?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">SUPER VALLE</h3>
                        <div class="tour-schedule">Todos los días de: 7:00 a.m. - 7:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>Visitamos:</strong>
                            <ul>
                                <li>Iglesia de Chincheros</li>
                                <li>Moray</li>
                                <li>Minas de Sal</li>
                                <li>Ollantaytambo</li>
                                <li>Pisaq grupo arqueológico</li>
                                <li>Mercado</li>
                            </ul>
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Turístico y Guía Bilingüe (almuerzo opcional)</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 8: A MACHUPICCHU -->
                <div class="tour-card" data-category="multi-day">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">A MACHUPICCHU</h3>
                        <div class="tour-schedule">Todos los días</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Transfer Hotel/Estación/Hotel</li>
                                <li>Tickets de tren ida y vuelta</li>
                                <li>Tickets de bus subida y bajada</li>
                                <li>Tickets de ingreso</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 9: MACHUPICCHU BY CAR -->
                <div class="tour-card" data-category="multi-day">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">MACHUPICCHU BY CAR</h3>
                        <div class="tour-schedule">02 Dias / 01 Noche</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Cusco / Hidroeléctrica / Cusco</li>
                                <li>Comidas 1D - 1A - 1C</li>
                                <li>1 Noche Hostal</li>
                                <li>Ingreso a Machupicchu (Guía Profesional)</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Adulto... Estudiante...</div>
                    </div>
                </div>

                <!-- Tour 10: INKA JUNGLE -->
                <div class="tour-card" data-category="multi-day">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">INKA JUNGLE</h3>
                        <div class="tour-schedule">04 Dias / 03 Noches</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Cusco - Abra Malaga</li>
                                <li>Bicicletas (Santa María)</li>
                                <li>Comida 3D - 3A - 3C</li>
                                <li>Tickets de Ingreso a Machupicchu</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                                <li>03 noches Hostal</li>
                                <li>Tickets de Tren de retorno (Aguas Calientes - Cusco)</li>
                                <li>Bus Ollantaytambo - Cusco</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 11: SALKANTAY -->
                <div class="tour-card" data-category="multi-day">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">SALKANTAY</h3>
                        <div class="tour-schedule">05 Dias / 04 Noches</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus a Mollepata</li>
                                <li>Equipo (Carpas y Matras)</li>
                                <li>Caballos</li>
                                <li>Comida (4D - 4A - 4C)</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                                <li>01 noche Hostal en Aguas Calientes</li>
                                <li>Tickets de tren de retorno en Back packer</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Adulto... Estudiante...</div>
                    </div>
                </div>

                <!-- Tour 12: TOUR A PUNO -->
                <div class="tour-card" data-category="multi-day">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1571974402611-9c6d5b7f5823?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">TOUR A PUNO</h3>
                        <div class="tour-schedule">
                            <div>Uros: Medio día 3 horas</div>
                            <div>Uros - Taquile: 1 día</div>
                            <div>Uros - Taquile - Amantani: 2 días / 1 Noche</div>
                        </div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 13: MONTANA DE COLORES -->
                <div class="tour-card" data-category="aventura">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1531065208531-4036c0dba3ca?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">MONTANA DE COLORES</h3>
                        <div class="tour-schedule">Todos los días de: 4:00 a.m. a 6:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Turístico</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                                <li>Alimentación 1D - 1A</li>
                                <li>Entrada</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 14: MONTANA DE COLORES PALCOYO -->
                <div class="tour-card" data-category="aventura">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1531065208531-4036c0dba3ca?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">MONTANA DE COLORES PALCOYO</h3>
                        <div class="tour-schedule">Todos los días de: 6:30 a.m. a 5:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Turístico</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                                <li>Almuerzo</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 15: LAGUNA HUMANTAY -->
                <div class="tour-card" data-category="aventura">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">LAGUNA HUMANTAY</h3>
                        <div class="tour-schedule">Todos los días de: 4:00 a.m. a 6:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Transporte Turístico</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                                <li>Desayuno y Almuerzo</li>
                                <li>Entrada</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 16: 7 LAGUNAS - AUSANGATE -->
                <div class="tour-card" data-category="aventura">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1571974402611-9c6d5b7f5823?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">7 LAGUNAS - AUSANGATE</h3>
                        <div class="tour-schedule">De: 4:00 a.m. a 6:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Turístico</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                                <li>Desayuno y Almuerzo</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 17: WAQRAPUCARA -->
                <div class="tour-card" data-category="aventura">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">WAQRAPUCARA</h3>
                        <div class="tour-schedule">Todos los días de 4:00 a.m. a 7:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Turístico</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                                <li>Desayuno y Almuerzo</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 18: ZIPLINE -->
                <div class="tour-card" data-category="aventura">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">ZIPLINE</h3>
                        <div class="tour-schedule">Todos los días</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Transporte Turístico</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>

                <!-- Tour 19: QUESWACHACA -->
                <div class="tour-card" data-category="aventura">
                    <div class="tour-image" style="background-image: url('https://images.unsplash.com/photo-1571974402611-9c6d5b7f5823?w=600&h=400&fit=crop')"></div>
                    <div class="tour-header">
                        <h3 class="tour-title">QUESWACHACA</h3>
                        <div class="tour-schedule">De: 4:00 a.m. a 6:00 p.m.</div>
                    </div>
                    <div class="tour-content">
                        <div class="tour-includes">
                            <strong>Visitamos:</strong>
                            <ul>
                                <li>4 lagunas</li>
                                <li>Museo Tupac Amaru</li>
                                <li>Qheswachaca</li>
                            </ul>
                            <strong>INCLUYE:</strong>
                            <ul>
                                <li>Bus Turístico</li>
                                <li>Guía Bilingüe (Español - Inglés)</li>
                                <li>Desayuno y almuerzo</li>
                            </ul>
                        </div>
                        <div class="tour-price">PRECIO: Consultar</div>
                    </div>
                </div>
            </div>

            <div class="tour-contact">
                <h3 data-es="¿Interesado en alguno de nuestros tours?" data-en="Interested in any of our tours?">¿Interesado en alguno de nuestros tours?</h3>
                <p data-es="Contáctanos para más información y precios" data-en="Contact us for more information and prices">Contáctanos para más información y precios</p>
                <a href="#reservas" class="btn btn-primary" data-es="Consultar Precios" data-en="Check Prices"><i class="fas fa-info-circle"></i><span>Consultar Precios</span></a>
                <div class="contact-info">
                    <div><i class="fas fa-phone"></i> +51 966 217 821 / +51 958 940 100</div>
                    <div><i class="fas fa-envelope"></i> antares.travel.cusco@gmail.com</div>
                </div>
            </div>
        </div>
    </section>

    <section id="destinos" class="section destinations">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title" data-es="Destinos Extraordinarios" data-en="Extraordinary Destinations">Destinos Extraordinarios</h2>
                <p class="section-subtitle" data-es="Descubre lugares mágicos cerca de Cusco, cuidadosamente seleccionados para ofrecerte experiencias auténticas e inolvidables en el corazón del antiguo Imperio Inca." data-en="Discover magical places near Cusco, carefully selected to offer you authentic and unforgettable experiences in the heart of the ancient Inca Empire.">Descubre lugares mágicos cerca de Cusco, cuidadosamente seleccionados para ofrecerte experiencias auténticas e inolvidables en el corazón del antiguo Imperio Inca.</p>
            </div>
            <div class="destinations-grid">
                <div class="destination-card slide-in-left">
                    <div class="destination-image" style="background-image: url('https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=600&h=400&fit=crop')"></div>
                    <div class="destination-content">
                        <h3 class="destination-title" data-es="Machu Picchu" data-en="Machu Picchu">Machu Picchu</h3>
                        <p class="destination-desc" data-es="Explora la misteriosa ciudadela inca, declarada Patrimonio de la Humanidad. Una experiencia que combina historia milenaria, arquitectura impresionante y paisajes de ensueño." data-en="Explore the mysterious Inca citadel, declared a World Heritage Site. An experience that combines ancient history, impressive architecture, and dreamlike landscapes.">Explora la misteriosa ciudadela inca, declarada Patrimonio de la Humanidad. Una experiencia que combina historia milenaria, arquitectura impresionante y paisajes de ensueño.</p>
                        <a href="#tours" class="btn btn-secondary" data-es="Ver Tours" data-en="View Tours"><i class="fas fa-eye"></i><span>Ver Tours</span></a>
                    </div>
                </div>
                <div class="destination-card slide-in-right">
                    <div class="destination-image" style="background-image: url('https://image-tc.galaxy.tf/wijpeg-7s1v8e5km8dojs4ckc2dhyi4t/valle-sagrado-destino-unico_wide.jpg?crop=26%2C0%2C1548%2C871')"></div>
                    <div class="destination-content">
                        <h3 class="destination-title" data-es="Valle Sagrado" data-en="Sacred Valley">Valle Sagrado</h3>
                        <p class="destination-desc" data-es="Recorre el místico Valle Sagrado con sus terrazas ancestrales, pueblos tradicionales y paisajes que han permanecido inalterados por siglos." data-en="Explore the mystical Sacred Valley with its ancestral terraces, traditional villages, and landscapes that have remained unchanged for centuries.">Recorre el místico Valle Sagrado con sus terrazas ancestrales, pueblos tradicionales y paisajes que han permanecido inalterados por siglos.</p>
                        <a href="#tours" class="btn btn-secondary" data-es="Ver Tours" data-en="View Tours"><i class="fas fa-eye"></i><span>Ver Tours</span></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Resto del código existente... -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ... código existente ...

            // Filtrado de tours por categoría
            const categoryButtons = document.querySelectorAll('.category-btn');
            const tourCards = document.querySelectorAll('.tour-card');
            
            categoryButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const category = button.dataset.category;
                    
                    // Actualizar botones activos
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    // Filtrar tours
                    tourCards.forEach(card => {
                        if (category === 'all' || card.dataset.category === category) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>