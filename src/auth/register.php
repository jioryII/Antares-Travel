<?php
session_start();

$lang = 'es';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $lang = $_GET['lang'];
}

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../funtions/usuarios.php";
require_once __DIR__ . "/enviar_correo.php"; 

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = $_POST['nombre'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $usuarioExistente = obtenerUsuarioPorEmail($conn, $email);
    if ($usuarioExistente) {
        $error = "Ya existe una cuenta registrada con ese correo. Puedes iniciar sesión o usar otro correo.";
    } else {
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = uniqid('avatar_') . "." . $ext;
            $rutaDestino = __DIR__ . "/../../storage/uploads/avatars/" . $nombreArchivo;

            if (!is_dir(__DIR__ . "/../../storage/uploads/avatars/")) {
                mkdir(__DIR__ . "/../../storage/uploads/avatars/", 0777, true);
            }

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $rutaDestino)) {
                $avatar = "storage/uploads/avatars/" . $nombreArchivo;
            } else {
                $error = "❌ Error al subir el avatar.";
            }
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $idUsuario = insertarUsuario($conn, $nombre, $email, $password_hash, 'manual', null, $avatar, null);

        if ($idUsuario && is_numeric($idUsuario)) {

            $token = bin2hex(random_bytes(32));
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $sqlToken = "INSERT INTO email_verificacion (id_usuario, token, fecha_expiracion) 
                         VALUES (?, ?, ?)";
            try {
                $stmt = $conn->prepare($sqlToken);
                $stmt->bind_param("iss", $idUsuario, $token, $fechaExpiracion);

                if ($stmt->execute()) {
                    $link = "https://jiory.opalstacked.com/Antares-Travel/src/auth/verificar_email.php?token=" . $token;
                    if (enviarCorreoVerificacion($email, $nombre, $link)) {
                        $popup = true;
                    } else {
                        $error = "❌ Error al enviar el correo de verificación.";
                    }
                } else {
                    throw new Exception("Error al guardar el token");
                }
            } catch (Exception $e) {
                error_log("Error en verificación: " . $e->getMessage());
                $error = "❌ Error en el proceso de verificación";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>"></html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Antares Travel</title>
    
    <link rel="stylesheet" href="../../public/assets/css/styles_landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-H...==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-bg) 0%, #f8f6f3 50%, var(--primary-bg) 100%);
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(162, 119, 65, 0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .register-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="%23A27741" opacity="0.1"/><circle cx="80" cy="40" r="1" fill="%23A27741" opacity="0.1"/><circle cx="40" cy="80" r="1.5" fill="%235B797C" opacity="0.1"/></svg>');
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        .register-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 5px;
            box-shadow: 
                0 25px 60px rgba(162, 119, 65, 0.15),
                0 8px 16px rgba(162, 119, 65, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            padding: 50px 40px;
            max-width: 480px;
            width: 100%;
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(162, 119, 65, 0.1);
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(162, 119, 65, 0.1), transparent);
            transition: left 0.5s;
        }

        .register-card:hover::before {
            left: 100%;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .register-title {
            font-size: 2.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .register-subtitle {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            animation: shake 0.5s ease-in-out;
        }

        .error-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 1.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-container {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 18px 55px 18px 20px;
            border: 2px solid rgba(162, 119, 65, 0.2);
            border-radius: 5px;
            font-family: inherit;
            font-size: 16px;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(162, 119, 65, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-input:focus + .input-icon {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.2rem;
            transition: var(--transition);
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
            padding: 5px;
            border-radius: 50%;
        }

        .password-toggle:hover {
            color: var(--primary-color);
            background: rgba(162, 119, 65, 0.1);
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            left: -9999px;
        }

        .file-input-button {
            width: 100%;
            padding: 18px 55px 18px 20px;
            border: 2px dashed rgba(162, 119, 65, 0.3);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            cursor: pointer;
            transition: var(--transition);
            text-align: left;
            color: var(--text-light);
            font-size: 16px;
        }

        .file-input-button:hover {
            border-color: var(--primary-color);
            border-style: solid;
            background: rgba(255, 255, 255, 0.95);
        }

        .file-input-button.has-file {
            color: var(--primary-color);
            border-style: solid;
            border-color: var(--primary-color);
        }

        .register-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: var(--white);
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .register-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .register-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(162, 119, 65, 0.4);
        }

        .btn-text {
            position: relative;
            z-index: 2;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: var(--text-light);
            font-size: 14px;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(162, 119, 65, 0.3), transparent);
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 20px;
            position: relative;
        }

        .social-providers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(60px, auto));
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }


        .social-btn {
            width: 60px;
            height: 60px;
            background: var(--white);
            border: 2px solid rgba(162, 119, 65, 0.1);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .social-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .social-btn:hover::before {
            left: 100%;
        }

        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(162, 119, 65, 0.15);
            border-color: var(--primary-color);
        }

        .social-btn i {
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .social-btn:hover i {
            transform: scale(1.1);
        }

        .google { color:rgb(118, 83, 41); }
        .apple { color: rgb(118, 83, 41); }
        .microsoft { color: rgb(118, 83, 41); }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-light);
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s;
        }

        .login-link a:hover::after {
            width: 100%;
        }

        .login-link a:hover {
            color: var(--primary-dark);
        }

        .form-group {
            animation: fadeInUp 0.6s ease-out forwards;
            animation-delay: calc(var(--i) * 0.1s);
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
            opacity: 0;
            animation: fadeIn 0.3s ease-out forwards;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        .popup-content {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            position: relative;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            transform: translateY(50px);
            animation: slideInUp 0.4s ease-out 0.1s forwards;
        }

        .popup-content h3 {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .popup-content p {
            color: var(--text-dark);
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .popup-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 30px;
        }

        .popup-btn {
            padding: 15px 25px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .popup-btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
        }

        .popup-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(162, 119, 65, 0.3);
        }

        .popup-btn-secondary {
            background: rgba(162, 119, 65, 0.1);
            color: var(--primary-color);
            border: 2px solid rgba(162, 119, 65, 0.2);
        }

        .popup-btn-secondary:hover {
            background: rgba(162, 119, 65, 0.2);
            transform: translateY(-1px);
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(162, 119, 65, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
            margin: 20px 0;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-element {
            position: absolute;
            background: rgba(162, 119, 65, 0.1);
            border-radius: 50%;
            animation: floatMove 15s infinite linear;
        }

        .floating-element:nth-child(1) {
            width: 20px;
            height: 20px;
            top: 20%;
            left: 10%;
            animation-delay: -5s;
        }

        .floating-element:nth-child(2) {
            width: 15px;
            height: 15px;
            top: 60%;
            left: 90%;
            animation-delay: -10s;
        }

        .floating-element:nth-child(3) {
            width: 25px;
            height: 25px;
            top: 80%;
            left: 20%;
            animation-delay: -15s;
        }

        @keyframes floatMove {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        @media (max-width: 768px) {
            .register-wrapper {
                padding: 15px;
            }

            .register-card {
                padding: 40px 25px;
                margin: 10px;
                border-radius: 5px;
            }

            .register-title {
                font-size: 1.8rem;
            }

            .social-providers {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 12px;
                justify-content: center;
            }

            .social-btn {
                width: 55px;
                height: 55px;
            }

            .form-input, .file-input-button {
                padding: 16px 50px 16px 18px;
                font-size: 15px;
            }

            .register-btn {
                padding: 16px;
                font-size: 15px;
            }

            .popup-content {
                padding: 30px 20px;
            }

            .popup-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .register-card {
                padding: 30px 20px;
            }

            .register-title {
                font-size: 1.6rem;
            }

            .social-providers {
                grid-template-columns: repeat(auto-fit, minmax(50px, auto));
                gap: 10px;
                justify-content: center; 
            }

            .social-btn {
                width: 50px;
                height: 50px;
            }

            .social-btn i {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>

        <div class="register-wrapper">
            <div class="register-card">
                <div class="register-header">
                    <div class="register-logo">
                        <img src="../../imagenes/antares_logozz2.png" alt="Antares Travel Logo" style="max-width: 150px; height: auto; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;">
                    </div>
                    <h2 class="register-title">Únete a Antares Travel</h2>
                    <p class="register-subtitle">Crea tu cuenta y comienza tu aventura</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="registerForm">
                    <div class="form-group" style="--i: 1">
                        <div class="input-container">
                            <input 
                                type="text" 
                                name="nombre" 
                                class="form-input" 
                                placeholder="Nombre completo" 
                                required
                                autocomplete="name"
                            >
                            <i class="input-icon fas fa-user"></i>
                        </div>
                    </div>

                    <div class="form-group" style="--i: 2">
                        <div class="input-container">
                            <input 
                                type="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="Correo electrónico" 
                                required
                                autocomplete="email"
                            >
                            <i class="input-icon fas fa-envelope"></i>
                        </div>
                    </div>

                    <div class="form-group" style="--i: 3">
                        <div class="input-container">
                            <input 
                                type="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Contraseña" 
                                required
                                autocomplete="new-password"
                                id="passwordInput"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="--i: 4">
                        <div class="file-input-wrapper">
                            <input type="file" name="avatar" accept="image/*" class="file-input" id="avatarInput">
                            <label for="avatarInput" class="file-input-button" id="fileLabel">
                                <i class="fas fa-cloud-upload-alt" style="margin-right: 10px;"></i>
                                Seleccionar avatar (opcional)
                            </label>
                            <i class="input-icon fas fa-image"></i>
                        </div>
                    </div>

                    <button type="submit" class="register-btn" style="--i: 5">
                        <span class="btn-text">
                            <i class="fas fa-user-plus"></i>
                            Crear Cuenta
                        </span>
                    </button>
                </form>

                <div class="divider" style="--i: 6">
                    <span>O regístrate con</span>
                </div>
                <div class="social-providers" style="--i: 7">
                    <a href="oauth_callback.php?provider=google" class="social-btn" title="Google">
                        <i class="fab fa-google google"></i>
                    </a>
                    <a href="oauth_callback.php?provider=apple" class="social-btn" title="Apple">
                        <i class="fab fa-apple apple"></i>
                    </a>
                    <a href="oauth_callback.php?provider=microsoft" class="social-btn" title="Microsoft">
                        <i class="fab fa-windows microsoft"></i>
                    </a>
                </div>

                <div class="login-link" style="--i: 8">
                    ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($popup) && $popup): ?>
        <div class="popup-overlay" id="popup">
            <div class="popup-content">
                <i class="fas fa-envelope-circle-check" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h3>¡Revisa tu correo!</h3>
                <p>Te hemos enviado un enlace de verificación a <strong><?php echo htmlspecialchars($email); ?></strong></p>
                <p>Por favor, verifica tu cuenta antes de iniciar sesión.</p>
                
                <div class="spinner"></div>
                
                <div class="popup-buttons">
                    <form action="reenviar_verificacion.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <button type="submit" class="popup-btn popup-btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Reenviar correo de verificación
                        </button>
                    </form>
                    <a href="login.php" class="popup-btn popup-btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        Continuar al login
                    </a>
                </div>
            </div>
        </div>
        <script>
            setTimeout(function() {
                window.location.href = "login.php";
            }, 300000);
        </script>
    <?php endif; ?>

    <script>
        const initialLang = '<?php echo $lang; ?>';
        localStorage.setItem('language', initialLang);

        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const formGroups = document.querySelectorAll('.form-group, .divider, .social-providers, .login-link');
            
            formGroups.forEach((group, index) => {
                group.style.animationDelay = `${(index + 1) * 0.1}s`;
            });

            const fileInput = document.getElementById('avatarInput');
            const fileLabel = document.getElementById('fileLabel');
            
            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    const fileName = this.files[0].name;
                    fileLabel.innerHTML = `<i class="fas fa-check" style="margin-right: 10px; color: var(--primary-color);"></i>${fileName}`;
                    fileLabel.classList.add('has-file');
                } else {
                    fileLabel.innerHTML = '<i class="fas fa-cloud-upload-alt" style="margin-right: 10px;"></i>Seleccionar avatar (opcional)';
                    fileLabel.classList.remove('has-file');
                }
            });

            createParticles();
        });

        function createParticles() {
            const container = document.querySelector('.floating-elements');
            
            for (let i = 0; i < 5; i++) {
                setTimeout(() => {
                    const particle = document.createElement('div');
                    particle.className = 'floating-element';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                    particle.style.animationDelay = Math.random() * 5 + 's';
                    container.appendChild(particle);
                
                    setTimeout(() => {
                        if (particle.parentNode) {
                            particle.parentNode.removeChild(particle);
                        }
                    }, 20000);
                }, i * 2000);
            }
        }

        setInterval(createParticles, 10000);

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = document.querySelector('.register-btn');
            const btnText = btn.querySelector('.btn-text');
            
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando cuenta...';
            btn.style.pointerEvents = 'none';
            
            setTimeout(() => {
                if (document.querySelector('.error-message')) {
                    btnText.innerHTML = '<i class="fas fa-user-plus"></i> Crear Cuenta';
                    btn.style.pointerEvents = 'auto';
                }
            }, 1000);
        });

        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.style.transform = 'translateY(0)';
            });
        });

        const fileInputWrapper = document.querySelector('.file-input-wrapper');
        const fileInputButton = document.querySelector('.file-input-button');

        fileInputWrapper.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileInputButton.style.borderColor = 'var(--primary-color)';
            fileInputButton.style.borderStyle = 'solid';
            fileInputButton.style.background = 'rgba(162, 119, 65, 0.1)';
        });

        fileInputWrapper.addEventListener('dragleave', function(e) {
            e.preventDefault();
            if (!fileInputButton.classList.contains('has-file')) {
                fileInputButton.style.borderStyle = 'dashed';
                fileInputButton.style.borderColor = 'rgba(162, 119, 65, 0.3)';
                fileInputButton.style.background = 'rgba(255, 255, 255, 0.8)';
            }
        });

        fileInputWrapper.addEventListener('drop', function(e) {
            e.preventDefault();
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('avatarInput').files = files;
                const event = new Event('change', { bubbles: true });
                document.getElementById('avatarInput').dispatchEvent(event);
            }
            const langButtons = document.querySelectorAll('.lang-btn');
    const langElements = document.querySelectorAll('[data-es][data-en]');
    let currentLang = initialLang || 'es';

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
        localStorage.setItem('language', lang);
        
        langButtons.forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
        });
    }

    updateLanguage(currentLang);

    langButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const lang = btn.getAttribute('data-lang');
            updateLanguage(lang);
        });
    });
});
    </script>
</body>
</html>