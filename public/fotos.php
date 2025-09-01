<?php
require_once __DIR__ . '../src/auth/auth_check.php';
require_once __DIR__ . '../src/config/conexion.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['comentario']) && !empty(trim($_POST['comentario'])) && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $id_usuario = $_SESSION['user_id'];
        $comentario = trim($_POST['comentario']);

        $target_dir = "storage/uploads/experiencias/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
        $file_name = uniqid('exp_', true) . '.' . $file_ext;
        $target_file = $target_dir . $file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_types)) {
            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
                $db_path = "/" . $target_file; 
                $sql_insert = "INSERT INTO experiencias (imagen_url, comentario, id_usuario) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql_insert);
                $stmt->bind_param("ssi", $db_path, $comentario, $id_usuario);
                if ($stmt->execute()) {
                    $success = "¡Tu experiencia ha sido publicada con éxito!";
                } else {
                    $error = "Error al guardar la experiencia en la base de datos.";
                }
                $stmt->close();
            } else {
                $error = "Hubo un error al subir tu archivo.";
            }
        } else {
            $error = "Solo se permiten archivos de imagen (JPG, JPEG, PNG, GIF).";
        }
    } else {
        $error = "Por favor, completa todos los campos: sube una imagen y escribe un comentario.";
    }
}

$sql_select = "SELECT e.imagen_url, e.comentario, e.fecha_publicacion, u.nombre, u.avatar_url 
               FROM experiencias e
               JOIN usuarios u ON e.id_usuario = u.id_usuario
               ORDER BY e.fecha_publicacion DESC";
$result = $conn->query($sql_select);
$experiencias = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería de Experiencias - Antares Travel</title>
    <link rel="icon" type="image/png" href="../imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles_landing.css">
    <style>
        .page-header { background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1517732306149-e8f829eb588a?w=1200&h=600&fit=crop') no-repeat center center/cover; padding: 180px 0 100px; text-align: center; color: var(--white); }
        .page-header h1 { font-size: 3.5rem; font-weight: 700; }
        .main-content { padding: 80px 0; background-color: var(--primary-bg); }
        .upload-section { background: var(--white); padding: 40px; border-radius: 5px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 50px; }
        .upload-section h2 { text-align: center; margin-bottom: 30px; font-size: 1.8rem; color: var(--text-dark); }
        .upload-form .form-group { margin-bottom: 20px; }
        .upload-form label { display: block; margin-bottom: 8px; font-weight: 500; }
        .upload-form textarea, .upload-form input[type="file"] { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 5px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: 500;}
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .gallery-grid { column-count: 3; column-gap: 20px; }
        .gallery-item { background: var(--white); margin-bottom: 20px; display: inline-block; width: 100%; border-radius: 5px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .gallery-item img { width: 100%; height: auto; display: block; }
        .gallery-content { padding: 15px; }
        .gallery-author { display: flex; align-items: center; margin-bottom: 10px; }
        .gallery-author img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; object-fit: cover; }
        .gallery-author-info { font-size: 0.9rem; }
        .gallery-author-info strong { color: var(--text-dark); }
        .gallery-author-info span { color: var(--text-light); display: block; font-size: 0.8rem; }
        .gallery-comment { color: var(--text-light); line-height: 1.6; font-size: 0.95rem; }
        @media (max-width: 992px) { .gallery-grid { column-count: 2; } }
        @media (max-width: 768px) { .gallery-grid { column-count: 1; } }
    </style>
</head>
<body>
    <nav class="navbar scrolled">
        <div class="nav-container">
            <a href="../index.php" class="logo"><img src="../imagenes/antares_logozz2.png" alt="Antares Travel Logo" height="50"> ANTARES TRAVEL</a>
            <ul class="nav-links">
                <li><a href="../index.php" data-es="Inicio" data-en="Home">Inicio</a></li>
                <li><a href="tours.php" data-es="Tours" data-en="Tours">Tours</a></li>
                <li><a href="guias.php" data-es="Guías" data-en="Guides">Guías</a></li>
                <li><a href="fotos.php" data-es="Fotos" data-en="Photos">Fotos</a></li>
            </ul>
            <div class="auth-buttons">
                <div class="lang-switch"><button class="lang-btn active" data-lang="es">ES</button><button class="lang-btn" data-lang="en">EN</button></div>
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_picture']); ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="../index.php?logout=1" class="btn btn-primary" data-es="Cerrar Sesión" data-en="Logout"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
            <div class="mobile-menu"><span></span><span></span><span></span></div>
        </div>
    </nav>
    <header class="page-header">
        <h1 data-es="Galería de Experiencias" data-en="Experience Gallery">Galería de Experiencias</h1>
    </header>
    <main class="main-content">
        <div class="container">
            <section class="upload-section">
                <h2 data-es="Comparte tu Aventura" data-en="Share Your Adventure">Comparte tu Aventura</h2>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                <form action="fotos.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="imagen" data-es="Sube tu mejor foto" data-en="Upload your best photo">Sube tu mejor foto</label>
                        <input type="file" name="imagen" id="imagen" required accept="image/jpeg,image/png,image/gif">
                    </div>
                    <div class="form-group">
                        <label for="comentario" data-es="Cuéntanos tu experiencia" data-en="Tell us your experience">Cuéntanos tu experiencia</label>
                        <textarea name="comentario" id="comentario" rows="4" required maxlength="500"></textarea>
                    </div>
                    <div style="text-align: center;">
                        <button type="submit" class="btn btn-primary" data-es="Publicar Experiencia" data-en="Post Experience">
                            <i class="fas fa-paper-plane"></i>
                            <span>Publicar Experiencia</span>
                        </button>
                    </div>
                </form>
            </section>
            <section class="gallery">
                <div class="gallery-grid">
                    <?php if (count($experiencias) > 0): ?>
                        <?php foreach ($experiencias as $exp): ?>
                            <div class="gallery-item">
                                <img src="<?php echo htmlspecialchars($exp['imagen_url']); ?>" alt="Experiencia de viaje">
                                <div class="gallery-content">
                                    <div class="gallery-author">
                                        <img src="<?php echo htmlspecialchars($exp['avatar_url'] ? '/Antares-Travel/' . $exp['avatar_url'] : '/Antares-Travel/storage/uploads/avatars/default.png'); ?>" alt="Avatar de usuario">
                                        <div class="gallery-author-info">
                                            <strong><?php echo htmlspecialchars($exp['nombre']); ?></strong>
                                            <span><?php echo date("d M Y, H:i", strtotime($exp['fecha_publicacion'])); ?></span>
                                        </div>
                                    </div>
                                    <p class="gallery-comment"><?php echo htmlspecialchars($exp['comentario']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center;" data-es="Aún no hay experiencias publicadas. ¡Sé el primero en compartir la tuya!" data-en="No experiences posted yet. Be the first to share yours!">Aún no hay experiencias publicadas. ¡Sé el primero en compartir la tuya!</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <footer class="footer">
        <div class="container"><div class="footer-bottom"><p><span data-es="&copy; 2024 Antares Travel. Todos los derechos reservados." data-en="&copy; 2024 Antares Travel. All rights reserved.">&copy; 2024 Antares Travel. Todos los derechos reservados.</span></p></div></div>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html>