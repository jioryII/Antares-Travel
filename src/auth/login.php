<?php
session_start();

$lang = 'es'; 
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $lang = $_GET['lang'];
}

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../funtions/usuarios.php";


$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $usuario = obtenerUsuarioPorEmail($conn, $email);

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        if ($usuario['email_verificado']) {
            $_SESSION['user_id'] = $usuario['id_usuario'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_name']  = $usuario['nombre'];
            $_SESSION['user_picture'] = isset($usuario['avatar_url']) 
              ? "/Antares-Travel/" . $usuario['avatar_url'] 
              : "/Antares-Travel/storage/uploads/avatars/default.png";
            header("Location: ./../../index.php");
            exit;
        } else {
            $error = "Debes verificar tu correo antes de iniciar sesión.";
        }
    } else {
        $error = "❌ Credenciales inválidas.";
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Antares Travel</title>
    
    <link rel="stylesheet" href="../../public/assets/css/styles_landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-H...==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-bg) 0%, #f8f6f3 50%, var(--primary-bg) 100%);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(162, 119, 65, 0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .login-container::after {
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

        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 5px;
            box-shadow: 
                0 25px 60px rgba(162, 119, 65, 0.15),
                0 8px 16px rgba(162, 119, 65, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            padding: 50px 40px;
            max-width: 460px;
            width: 100%;
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(162, 119, 65, 0.1);
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(162, 119, 65, 0.1), transparent);
            transition: left 0.5s;
        }

        .login-card:hover::before {
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

        .login-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .login-title {
            font-size: 2.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
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

        .login-btn {
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

        .login-btn::before {
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

        .login-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .login-btn:hover {
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

        .register-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-light);
            font-size: 14px;
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s;
        }

        .register-link a:hover::after {
            width: 100%;
        }

        .register-link a:hover {
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
    </style>
</head>
<body>
    <div class="login-container">
        <div style="position: absolute; top: 20px; right: 20px; z-index: 10;">
            <div class="lang-switch">
                <button class="lang-btn" data-lang="es">ES</button>
                <button class="lang-btn" data-lang="en">EN</button>
            </div>
        </div>
        <div class="login-wrapper">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">
                        <img src="../../imagenes/antares_logozz2.png" alt="Antares Travel Logo" style="max-width: 150px; height: auto; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;">
                    </div>
                    <h2 class="login-title">Bienvenido de vuelta</h2>
                    <p class="login-subtitle">Inicia sesión en tu cuenta de Antares Travel</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="form-group" style="--i: 1">
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

                    <div class="form-group" style="--i: 2">
                        <div class="input-container">
                            <input 
                                type="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Contraseña" 
                                required
                                autocomplete="current-password"
                                id="passwordInput"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn" style="--i: 3">
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt"></i>
                            Iniciar Sesión
                        </span>
                    </button>
                </form>

                <div class="divider" style="--i: 4">
                    <span>O continúa con</span>
                </div>

                <div class="social-providers" style="--i: 5">
                    <a href="oauth_callback.php?provider=google" class="social-btn" title="Google"><i class="fab fa-google google"></i></a>
                    <a href="oauth_callback.php?provider=apple" class="social-btn" title="Apple"><i class="fab fa-apple apple"></i></a>
                    <a href="oauth_callback.php?provider=microsoft" class="social-btn" title="Microsoft"><i class="fab fa-windows microsoft"></i></a>
                </div>

                <div class="register-link" style="--i: 6">
                    ¿No tienes una cuenta? <a href="register.php">Crear cuenta</a>
                </div>
            </div>
        </div>
    </div>

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
            const formGroups = document.querySelectorAll('.form-group, .divider, .social-providers, .register-link');
            
            formGroups.forEach((group, index) => {
                group.style.animationDelay = `${(index + 1) * 0.1}s`;
            });
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.querySelector('.login-btn');
            const btnText = btn.querySelector('.btn-text');
            
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
            btn.style.pointerEvents = 'none';
            
            setTimeout(() => {
                if (document.querySelector('.error-message')) {
                    btnText.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
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
    </script>
</body>
</html>