<?php
require_once __DIR__ . '/../src/auth/auth_check.php';
require_once __DIR__ . '/../src/config/conexion.php';

$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? $_GET['max_price'] : 99999;

// Consulta para obtener tours con información de ofertas activas
$sql = "SELECT DISTINCT 
            t.id_tour, 
            t.titulo, 
            t.descripcion, 
            t.precio, 
            t.imagen_principal, 
            t.duracion,
            o.id_oferta,
            o.nombre as oferta_nombre,
            o.tipo_oferta,
            o.valor_descuento,
            o.precio_especial,
            o.descripcion as oferta_descripcion,
            o.destacada
        FROM tours t 
        LEFT JOIN ofertas_tours ot ON t.id_tour = ot.id_tour
        LEFT JOIN ofertas o ON ot.id_oferta = o.id_oferta 
            AND o.activa = 1 
            AND (o.fecha_inicio <= CURDATE() OR o.fecha_inicio IS NULL)
            AND (o.fecha_fin >= CURDATE() OR o.fecha_fin IS NULL)
            AND (o.limite_usos IS NULL OR o.limite_usos > o.usos_actuales)
        WHERE t.precio BETWEEN ? AND ?
        ORDER BY t.id_tour, o.destacada DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("dd", $min_price, $max_price);
$stmt->execute();
$result = $stmt->get_result();
$tours_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Procesar datos para agrupar ofertas por tour
$tours = [];
foreach ($tours_data as $row) {
    $tour_id = $row['id_tour'];
    
    if (!isset($tours[$tour_id])) {
        $tours[$tour_id] = [
            'id_tour' => $row['id_tour'],
            'titulo' => $row['titulo'],
            'descripcion' => $row['descripcion'],
            'precio' => $row['precio'],
            'imagen_principal' => $row['imagen_principal'],
            'duracion' => $row['duracion'],
            'ofertas' => []
        ];
    }
    
    // Agregar oferta si existe
    if ($row['id_oferta']) {
        $tours[$tour_id]['ofertas'][] = [
            'id_oferta' => $row['id_oferta'],
            'nombre' => $row['oferta_nombre'],
            'tipo_oferta' => $row['tipo_oferta'],
            'valor_descuento' => $row['valor_descuento'],
            'precio_especial' => $row['precio_especial'],
            'descripcion' => $row['oferta_descripcion'],
            'destacada' => $row['destacada']
        ];
    }
}

// Función para calcular precio final con ofertas
function calcularPrecioConOfertas($precio_original, $ofertas) {
    if (empty($ofertas)) {
        return $precio_original;
    }
    
    $mejor_precio = $precio_original;
    
    foreach ($ofertas as $oferta) {
        switch ($oferta['tipo_oferta']) {
            case 'Porcentaje':
                $precio_con_descuento = $precio_original * (1 - $oferta['valor_descuento'] / 100);
                break;
            case 'Monto_Fijo':
                $precio_con_descuento = max(0, $precio_original - $oferta['valor_descuento']);
                break;
            case 'Precio_Especial':
                $precio_con_descuento = $oferta['precio_especial'];
                break;
            case '2x1':
                $precio_con_descuento = $precio_original; // Se maneja en el carrito
                break;
            case 'Combo':
                $precio_con_descuento = $precio_original; // Se maneja en el carrito
                break;
            default:
                $precio_con_descuento = $precio_original;
        }
        
        $mejor_precio = min($mejor_precio, $precio_con_descuento);
    }
    
    return $mejor_precio;
}

// Función para obtener la mejor oferta
function obtenerMejorOferta($ofertas) {
    if (empty($ofertas)) {
        return null;
    }
    
    // Priorizar ofertas destacadas
    foreach ($ofertas as $oferta) {
        if ($oferta['destacada']) {
            return $oferta;
        }
    }
    
    // Si no hay destacadas, devolver la primera
    return $ofertas[0];
}

$tours = array_values($tours); // Reindexar array
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuestros Tours - Antares Travel</title>
    <link rel="icon" type="image/png" href="../imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles_landing.css">
    <style>
        .page-header {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1526392060635-9d6019884377?w=1200&h=600&fit=crop') no-repeat center center/cover;
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
        .filter-container {
            background: var(--white);
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 50px;
            animation: fadeIn 0.8s ease-out;
        }
        .filter-form {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        .filter-group input {
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 5px;
            font-family: inherit;
            width: 150px;
            transition: var(--transition);
        }
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .tours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }
        .tour-card {
            background: var(--white);
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        .tour-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(162, 119, 65, 0.2);
        }
        .tour-image {
            height: 220px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .tour-price {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary-color);
            color: var(--white);
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
        }
        .tour-price.with-offer {
            background: #10b981;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .tour-price .original-price {
            text-decoration: line-through;
            font-size: 0.8rem;
            opacity: 0.8;
            display: block;
        }
        .tour-offer-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 10px rgba(245, 158, 11, 0.3);
            animation: pulse 2s infinite;
            z-index: 2;
        }
        .tour-offer-badge.destacada {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            animation: glow 2s ease-in-out infinite alternate;
        }
        .tour-offer-badge.porcentaje {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
        }
        .tour-offer-badge.precio-especial {
            background: linear-gradient(135deg, #059669, #047857);
        }
        .tour-offer-badge.combo {
            background: linear-gradient(135deg, #0891b2, #0e7490);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes glow {
            from { box-shadow: 0 3px 15px rgba(220, 38, 38, 0.4); }
            to { box-shadow: 0 3px 25px rgba(220, 38, 38, 0.8), 0 0 30px rgba(220, 38, 38, 0.3); }
        }
        .tour-content {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .tour-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        .tour-duration {
            color: var(--text-light);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tour-description {
            color: var(--text-light);
            line-height: 1.7;
            flex-grow: 1;
            margin-bottom: 20px;
        }
        .no-tours {
            text-align: center;
            padding: 50px;
            background: var(--white);
            border-radius: 5px;
            color: var(--text-light);
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
        <h1 data-es="Nuestros Tours" data-en="Our Tours">Nuestros Tours</h1>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="filter-container">
                <form method="GET" action="tours.php" class="filter-form">
                    <div class="filter-group">
                        <label for="min_price" data-es="Precio Mínimo" data-en="Minimum Price">Precio Mínimo</label>
                        <input type="number" name="min_price" id="min_price" placeholder="0" value="<?php echo htmlspecialchars($min_price); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="max_price" data-es="Precio Máximo" data-en="Maximum Price">Precio Máximo</label>
                        <input type="number" name="max_price" id="max_price" placeholder="99999" value="<?php echo htmlspecialchars($max_price); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" data-es="Filtrar" data-en="Filter">
                        <i class="fas fa-filter"></i>
                        <span>Filtrar</span>
                    </button>
                </form>
            </div>

            <div class="tours-grid">
                <?php if (count($tours) > 0): ?>
                    <?php foreach ($tours as $tour): ?>
                        <?php 
                        $mejor_oferta = obtenerMejorOferta($tour['ofertas']);
                        $precio_original = $tour['precio'];
                        $precio_final = calcularPrecioConOfertas($precio_original, $tour['ofertas']);
                        $tiene_descuento = $precio_final < $precio_original;
                        ?>
                        <div class="tour-card">
                            <div class="tour-image" style="background-image: url('<?php echo htmlspecialchars($tour['imagen_principal']); ?>');">
                                
                                <?php if ($mejor_oferta): ?>
                                    <div class="tour-offer-badge <?php 
                                        echo strtolower(str_replace('_', '-', $mejor_oferta['tipo_oferta']));
                                        echo $mejor_oferta['destacada'] ? ' destacada' : '';
                                    ?>">
                                        <?php 
                                        switch ($mejor_oferta['tipo_oferta']) {
                                            case 'Porcentaje':
                                                echo '-' . $mejor_oferta['valor_descuento'] . '%';
                                                break;
                                            case 'Monto_Fijo':
                                                echo '-$' . number_format($mejor_oferta['valor_descuento']);
                                                break;
                                            case 'Precio_Especial':
                                                echo 'ESPECIAL';
                                                break;
                                            case '2x1':
                                                echo '2x1';
                                                break;
                                            case 'Combo':
                                                echo 'COMBO';
                                                break;
                                            default:
                                                echo 'OFERTA';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="tour-price<?php echo $tiene_descuento ? ' with-offer' : ''; ?>">
                                    <?php if ($tiene_descuento): ?>
                                        <span class="original-price">$<?php echo number_format($precio_original, 2); ?></span>
                                    <?php endif; ?>
                                    $<?php echo number_format($precio_final, 2); ?>
                                </div>
                            </div>
                            <div class="tour-content">
                                <h3 class="tour-title"><?php echo htmlspecialchars($tour['titulo']); ?></h3>
                                <div class="tour-duration">
                                    <i class="fas fa-clock"></i>
                                    <span data-es="Duración:" data-en="Duration:">Duración:</span> <?php echo htmlspecialchars($tour['duracion']); ?>
                                </div>
                                
                                <?php if ($mejor_oferta && !empty($mejor_oferta['descripcion'])): ?>
                                    <div class="tour-offer-description" style="background: #fef3c7; color: #92400e; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 15px; border-left: 3px solid #f59e0b;">
                                        <i class="fas fa-tag" style="margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($mejor_oferta['descripcion']); ?>
                                    </div>
                                <?php endif; ?>

                                <p class="tour-description"><?php echo htmlspecialchars($tour['descripcion']); ?></p>
                                <a href="../src/detalles.php?id=<?php echo $tour['id_tour']; ?>" class="btn btn-secondary" data-es="Ver Detalles" data-en="View Details">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Ver Detalles</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-tours">
                        <h3 data-es="No se encontraron tours" data-en="No tours found">No se encontraron tours</h3>
                        <p data-es="Intenta ajustar los filtros de precio o revisa más tarde." data-en="Try adjusting the price filters or check back later.">Intenta ajustar los filtros de precio o revisa más tarde.</p>
                    </div>
                <?php endif; ?>
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