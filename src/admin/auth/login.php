<?php
session_start();

// Función para generar URLs absolutas desde la raíz del dominio
function getAdminUrl($path) {
    // Generar URL absoluta desde la raíz del dominio
    $cleanPath = ltrim($path, './');
    return '/src/admin/' . $cleanPath;
}

// Si ya está logueado, verificar si hay parámetros de aprobación pendientes
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Verificar si hay parámetros de aprobación en la URL
    if (isset($_GET['token']) && isset($_GET['accion']) && $_SESSION['admin_rol'] === 'superadmin') {
        // Redirigir automáticamente a aprobar_admin.php con los parámetros
        $token = urlencode($_GET['token']);
        $accion = urlencode($_GET['accion']);
        header('Location: ' . getAdminUrl('auth/aprobar_admin.php?token=' . $token . '&accion=' . $accion));
        exit();
    }
    header('Location: ' . getAdminUrl('pages/dashboard/'));
    exit();
}

require_once __DIR__ . '/functions.php';

$error = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        $resultado = autenticarAdmin($email, $password);
        if ($resultado['success']) {
            // Verificar si hay parámetros de aprobación después del login exitoso
            if (isset($_POST['approval_token']) && isset($_POST['approval_action']) && $resultado['admin']['rol'] === 'superadmin') {
                // Redirigir automáticamente a aprobar_admin.php con los parámetros
                $token = urlencode($_POST['approval_token']);
                $accion = urlencode($_POST['approval_action']);
                header('Location: ' . getAdminUrl('auth/aprobar_admin.php?token=' . $token . '&accion=' . $accion));
                exit();
            }
            header('Location: ' . getAdminUrl('pages/dashboard/'));
            exit();
        } else {
            // Si requiere verificación, mostrar mensaje especial
            if (isset($resultado['require_verification']) && $resultado['require_verification']) {
                $error = $resultado['message'] . ' <a href="reenviar_verificacion_admin.php?email=' . urlencode($resultado['email']) . '" class="text-orange-500 hover:underline font-semibold">Reenviar correo de verificación</a>';
            } elseif (isset($resultado['pending_approval']) && $resultado['pending_approval']) {
                // Cuenta verificada pero pendiente de aprobación
                $error = '⏳ <strong>Cuenta Pendiente de Aprobación</strong><br>' . $resultado['message'] . '<br><small class="text-gray-600 mt-2 block">Un superadministrador revisará tu solicitud y te notificará por correo cuando sea aprobada.</small>';
            } else {
                $error = $resultado['message'];
            }
        }
    }
}

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    $errores = validarDatosRegistro($nombre, $email, $password, $confirmar_password);
    
    if (empty($errores)) {
        $resultado = registrarAdmin($nombre, $email, $password);
        if ($resultado['success']) {
            if (isset($resultado['require_verification']) && $resultado['require_verification']) {
                $success = '✅ <strong>¡Registro Exitoso!</strong><br>' . $resultado['message'] . '<br><br>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-3">
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium mb-1">📋 Proceso de activación:</p>
                        <p class="mb-1">1️⃣ Verifica tu correo electrónico (revisa spam)</p>
                        <p class="mb-1">2️⃣ Un superadministrador aprobará tu solicitud</p>
                        <p>3️⃣ Recibirás confirmación para acceder al sistema</p>
                    </div>
                </div>
                <small class="text-gray-600 mt-2 block">¿No recibiste el correo? <a href="reenviar_verificacion_admin.php?email=' . urlencode($email) . '" class="text-orange-500 hover:underline">Reenviar verificación</a></small>';
            } else {
                $success = $resultado['message'];
            }
        } else {
            $error = $resultado['message'];
        }
    } else {
        $error = implode('<br>', $errores);
    }
}

// Obtener mensaje de error de URL si existe
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'insufficient_permissions':
            $error = 'No tienes permisos suficientes para acceder';
            break;
        case 'session_expired':
            $error = 'Tu sesión ha expirado';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Antares Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .floating-animation {
            animation: floating 6s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(1deg); }
            66% { transform: translateY(-10px) rotate(-1deg); }
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), 0 0 20px rgba(99, 102, 241, 0.2);
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);
            position: relative;
            overflow: hidden;
        }
        
        .btn-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-gradient:hover::before {
            left: 100%;
        }
        
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .bg-animated {
            background: linear-gradient(-45deg, #1e293b, #334155, #475569, #64748b);
            background-size: 400% 400%;
            animation: gradientBG 20s ease infinite;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .tab-active {
            background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.4);
        }
        
        .tab-inactive {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
        }
        
        .form-slide-in {
            animation: slideIn 0.5s ease-out;
        }
        
        .form-slide-out-left {
            animation: slideOutLeft 0.3s ease-in forwards;
        }
        
        .form-slide-out-right {
            animation: slideOutRight 0.3s ease-in forwards;
        }
        
        .form-slide-in-left {
            animation: slideInFromLeft 0.5s ease-out;
        }
        
        .form-slide-in-right {
            animation: slideInFromRight 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutLeft {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(-100px);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
        
        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .form-container {
            position: relative;
            min-height: 500px;
        }
        
        .form-wrapper {
            position: absolute;
            width: 100%;
            top: 0;
            left: 0;
        }
        
        /* Responsividad mejorada */
        @media (max-width: 640px) {
            .form-container {
                min-height: 450px;
            }
            
            .floating-animation {
                animation-duration: 8s;
            }
            
            .particle {
                display: none; /* Ocultar partículas en móvil para mejor performance */
            }
            
            .glass-effect {
                backdrop-filter: blur(15px);
            }
        }
        
        @media (max-width: 480px) {
            .form-container {
                min-height: 500px;
            }
            
            .bg-animated {
                animation-duration: 25s; /* Animación más lenta en móvil */
            }
        }
        
        @media (min-width: 768px) {
            .form-container {
                min-height: 520px;
            }
        }
        
        @media (min-width: 1024px) {
            .form-container {
                min-height: 550px;
            }
            
            .glass-effect {
                backdrop-filter: blur(25px);
            }
        }
        
        @media (min-width: 1280px) {
            .form-container {
                min-height: 500px;
            }
        }
        
        /* Configuración de scroll específica para PC */
        .no-scroll-mobile {
            overflow: hidden;
        }
        
        @media (min-width: 1024px) {
            .no-scroll-mobile {
                overflow: auto;
            }
            
            body {
                min-height: 100vh;
            }
        }
    </style>
</head>
<body class="bg-animated min-h-screen relative no-scroll-mobile">
    <!-- Partículas flotantes de fondo -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="particle floating-animation" style="width: 4px; height: 4px; top: 20%; left: 10%; animation-delay: 0s;"></div>
        <div class="particle floating-animation" style="width: 6px; height: 6px; top: 60%; left: 20%; animation-delay: 2s;"></div>
        <div class="particle floating-animation" style="width: 3px; height: 3px; top: 40%; left: 80%; animation-delay: 4s;"></div>
        <div class="particle floating-animation" style="width: 5px; height: 5px; top: 80%; left: 70%; animation-delay: 1s;"></div>
        <div class="particle floating-animation" style="width: 4px; height: 4px; top: 30%; left: 60%; animation-delay: 3s;"></div>
        <div class="particle floating-animation" style="width: 7px; height: 7px; top: 70%; left: 40%; animation-delay: 5s;"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center py-4 sm:py-6 lg:py-8 px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-md w-full space-y-4 sm:space-y-6">
            <!-- Logo y título -->
            <div class="text-center">
                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-1 sm:mb-2">
                    Antares Travel
                </h2>
                <p class="text-sm sm:text-base text-white/80 font-light">
                    Panel de Administración
                </p>
                <div class="w-12 sm:w-16 h-1 bg-gradient-to-r from-blue-400 to-purple-400 mx-auto mt-2 sm:mt-3 rounded-full"></div>
            </div>

            <!-- Contenedor de formularios -->
            <div class="glass-effect rounded-xl sm:rounded-2xl shadow-2xl p-5 sm:p-6 lg:p-8 relative">
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-white/5 rounded-xl sm:rounded-2xl"></div>
                <div class="relative z-10">
                    <!-- Tabs -->
                    <div class="flex mb-5 sm:mb-6 p-1 glass-effect rounded-lg sm:rounded-xl">
                        <button onclick="showLogin()" id="loginTab" class="flex-1 py-2 sm:py-3 px-3 sm:px-4 text-center font-semibold rounded-lg transition-all duration-300 tab-active text-sm sm:text-base">
                            <i class="fas fa-sign-in-alt mr-1 sm:mr-2 text-sm sm:text-base"></i>
                            <span class="hidden xs:inline">Iniciar Sesión</span>
                            <span class="xs:hidden">Login</span>
                        </button>
                        <button onclick="showRegister()" id="registerTab" class="flex-1 py-2 sm:py-3 px-3 sm:px-4 text-center font-semibold rounded-lg transition-all duration-300 tab-inactive text-sm sm:text-base">
                            <i class="fas fa-user-plus mr-1 sm:mr-2 text-sm sm:text-base"></i>
                            <span class="hidden xs:inline">Registrarse</span>
                            <span class="xs:hidden">Registro</span>
                        </button>
                    </div>

                <!-- Mensajes -->
                <?php if ($error): ?>
                    <div class="mb-4 sm:mb-6 bg-red-500/20 backdrop-blur-sm border border-red-400/30 text-red-100 px-3 sm:px-4 py-2 sm:py-3 rounded-lg sm:rounded-xl animate-pulse text-sm sm:text-base">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-4 sm:mb-6 bg-green-500/20 backdrop-blur-sm border border-green-400/30 text-green-100 px-3 sm:px-4 py-2 sm:py-3 rounded-lg sm:rounded-xl animate-pulse text-sm sm:text-base">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- Mensaje especial para aprobaciones pendientes -->
                <?php if (isset($_GET['token']) && isset($_GET['accion'])): ?>
                    <div class="mb-4 sm:mb-6 bg-blue-500/20 backdrop-blur-sm border border-blue-400/30 text-blue-100 px-3 sm:px-4 py-2 sm:py-3 rounded-lg sm:rounded-xl text-sm sm:text-base">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>🔐 Proceso de Aprobación Detectado</strong><br>
                        <small class="text-blue-200 mt-1 block">Después de iniciar sesión, se procesará automáticamente la <?php echo $_GET['accion'] === 'aprobar' ? 'aprobación' : 'denegación'; ?> del administrador solicitante.</small>
                    </div>
                <?php endif; ?>

                <!-- Formularios con contenedor animado -->
                <div class="form-container">
                    <!-- Formulario de Login -->
                    <div id="loginFormWrapper" class="form-wrapper">
                        <form id="loginForm" method="POST" class="space-y-4 sm:space-y-6">
                            <?php 
                            // Preservar parámetros GET en campos ocultos
                            if (isset($_GET['token']) && isset($_GET['accion'])) {
                                echo '<input type="hidden" name="approval_token" value="' . htmlspecialchars($_GET['token']) . '">';
                                echo '<input type="hidden" name="approval_action" value="' . htmlspecialchars($_GET['accion']) . '">';
                            }
                            ?>
                            <div class="space-y-1 sm:space-y-2">
                                <label for="email" class="block text-xs sm:text-sm font-semibold text-white/90">
                                    <i class="fas fa-envelope mr-2 text-blue-300 text-xs sm:text-sm"></i>Correo Electrónico
                                </label>
                                <div class="relative">
                                    <input id="email" name="email" type="email" required 
                                           class="w-full px-3 sm:px-4 py-2 sm:py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg sm:rounded-xl text-white placeholder-white/60 focus:outline-none focus:border-blue-400 input-glow transition-all duration-300 text-sm sm:text-base"
                                           placeholder="admin@antarestravel.com">
                                    <div class="absolute inset-0 rounded-lg sm:rounded-xl bg-gradient-to-r from-blue-400/20 to-purple-400/20 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                                </div>
                            </div>

                            <div class="space-y-1 sm:space-y-2">
                                <label for="password" class="block text-xs sm:text-sm font-semibold text-white/90">
                                    <i class="fas fa-lock mr-2 text-purple-300 text-xs sm:text-sm"></i>Contraseña
                                </label>
                                <div class="relative">
                                    <input id="password" name="password" type="password" required 
                                           class="w-full px-3 sm:px-4 py-2 sm:py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg sm:rounded-xl text-white placeholder-white/60 focus:outline-none focus:border-purple-400 input-glow transition-all duration-300 text-sm sm:text-base"
                                           placeholder="••••••••">
                                    <div class="absolute inset-0 rounded-lg sm:rounded-xl bg-gradient-to-r from-blue-400/20 to-purple-400/20 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                                </div>
                            </div>

                            <button type="submit" name="login" 
                                    class="w-full py-2 sm:py-3 px-3 sm:px-4 btn-gradient rounded-lg sm:rounded-xl text-white font-semibold hover:shadow-lg hover:shadow-blue-500/25 transform hover:scale-105 transition-all duration-300 mt-6 sm:mt-8 text-sm sm:text-base">
                                <i class="fas fa-sign-in-alt mr-2 text-xs sm:text-sm"></i>
                                Iniciar Sesión
                            </button>
                        </form>
                    </div>

                    <!-- Formulario de Registro -->
                    <div id="registerFormWrapper" class="form-wrapper hidden">
                        <form id="registerForm" method="POST" class="space-y-3 sm:space-y-6">
                            <div class="space-y-1 sm:space-y-2">
                                <label for="nombre" class="block text-xs sm:text-sm font-semibold text-white/90">
                                    <i class="fas fa-user mr-2 text-green-300 text-xs sm:text-sm"></i>Nombre Completo
                                </label>
                                <div class="relative">
                                    <input id="nombre" name="nombre" type="text" required 
                                           class="w-full px-3 sm:px-4 py-2 sm:py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg sm:rounded-xl text-white placeholder-white/60 focus:outline-none focus:border-green-400 input-glow transition-all duration-300 text-sm sm:text-base"
                                           placeholder="Juan Pérez">
                                    <div class="absolute inset-0 rounded-lg sm:rounded-xl bg-gradient-to-r from-green-400/20 to-blue-400/20 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                                </div>
                            </div>

                            <div class="space-y-1 sm:space-y-2">
                                <label for="reg_email" class="block text-xs sm:text-sm font-semibold text-white/90">
                                    <i class="fas fa-envelope mr-2 text-blue-300 text-xs sm:text-sm"></i>Correo Electrónico
                                </label>
                                <div class="relative">
                                    <input id="reg_email" name="email" type="email" required 
                                           class="w-full px-3 sm:px-4 py-2 sm:py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg sm:rounded-xl text-white placeholder-white/60 focus:outline-none focus:border-blue-400 input-glow transition-all duration-300 text-sm sm:text-base"
                                           placeholder="admin@antarestravel.com">
                                    <div class="absolute inset-0 rounded-lg sm:rounded-xl bg-gradient-to-r from-green-400/20 to-blue-400/20 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                                </div>
                            </div>

                            <div class="space-y-1 sm:space-y-2">
                                <label for="reg_password" class="block text-xs sm:text-sm font-semibold text-white/90">
                                    <i class="fas fa-lock mr-2 text-purple-300 text-xs sm:text-sm"></i>Contraseña
                                </label>
                                <div class="relative">
                                    <input id="reg_password" name="password" type="password" required 
                                           class="w-full px-3 sm:px-4 py-2 sm:py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg sm:rounded-xl text-white placeholder-white/60 focus:outline-none focus:border-purple-400 input-glow transition-all duration-300 text-sm sm:text-base"
                                           placeholder="••••••••">
                                    <div class="absolute inset-0 rounded-lg sm:rounded-xl bg-gradient-to-r from-green-400/20 to-blue-400/20 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                                </div>
                            </div>

                            <div class="space-y-1 sm:space-y-2">
                                <label for="confirmar_password" class="block text-xs sm:text-sm font-semibold text-white/90">
                                    <i class="fas fa-lock mr-2 text-pink-300 text-xs sm:text-sm"></i>Confirmar Contraseña
                                </label>
                                <div class="relative">
                                    <input id="confirmar_password" name="confirmar_password" type="password" required 
                                           class="w-full px-3 sm:px-4 py-2 sm:py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg sm:rounded-xl text-white placeholder-white/60 focus:outline-none focus:border-pink-400 input-glow transition-all duration-300 text-sm sm:text-base"
                                           placeholder="••••••••">
                                    <div class="absolute inset-0 rounded-lg sm:rounded-xl bg-gradient-to-r from-green-400/20 to-blue-400/20 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                                </div>
                            </div>

                            <button type="submit" name="register" 
                                    class="w-full py-2 sm:py-3 px-3 sm:px-4 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg sm:rounded-xl text-white font-semibold hover:shadow-lg hover:shadow-green-500/25 transform hover:scale-105 transition-all duration-300 mt-4 sm:mt-8 relative overflow-hidden text-sm sm:text-base">
                                <span class="relative z-10">
                                    <i class="fas fa-user-plus mr-2 text-xs sm:text-sm"></i>
                                    Crear Cuenta
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Crear partículas dinámicas
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle floating-animation';
            particle.style.width = Math.random() * 8 + 3 + 'px';
            particle.style.height = particle.style.width;
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 6 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 5) + 's';
            
            document.body.appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
            }, 15000);
        }
        
        // Crear partículas cada 3 segundos (solo en desktop)
        if (window.innerWidth > 640) {
            setInterval(createParticle, 3000);
        }
        
        // Optimizar para touch devices
        if ('ontouchstart' in window) {
            // Reducir efectos hover en touch devices
            document.documentElement.style.setProperty('--hover-scale', '1');
        }
        
        // Variables para controlar el estado actual
        let currentForm = 'login';
        let isTransitioning = false;
        
        function showLogin() {
            if (currentForm === 'login' || isTransitioning) return;
            
            isTransitioning = true;
            const loginWrapper = document.getElementById('loginFormWrapper');
            const registerWrapper = document.getElementById('registerFormWrapper');
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            
            // Animar salida del formulario de registro hacia la derecha
            registerWrapper.classList.add('form-slide-out-right');
            
            // Actualizar tabs inmediatamente
            loginTab.className = 'flex-1 py-2 sm:py-3 px-3 sm:px-4 text-center font-semibold rounded-lg transition-all duration-300 tab-active text-sm sm:text-base';
            registerTab.className = 'flex-1 py-2 sm:py-3 px-3 sm:px-4 text-center font-semibold rounded-lg transition-all duration-300 tab-inactive text-sm sm:text-base';
            
            setTimeout(() => {
                // Ocultar registro y mostrar login
                registerWrapper.classList.add('hidden');
                registerWrapper.classList.remove('form-slide-out-right');
                
                loginWrapper.classList.remove('hidden');
                loginWrapper.classList.add('form-slide-in-left');
                
                currentForm = 'login';
                
                // Limpiar animación después de completarse
                setTimeout(() => {
                    loginWrapper.classList.remove('form-slide-in-left');
                    isTransitioning = false;
                }, 500);
            }, 300);
        }

        function showRegister() {
            if (currentForm === 'register' || isTransitioning) return;
            
            isTransitioning = true;
            const loginWrapper = document.getElementById('loginFormWrapper');
            const registerWrapper = document.getElementById('registerFormWrapper');
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            
            // Animar salida del formulario de login hacia la izquierda
            loginWrapper.classList.add('form-slide-out-left');
            
            // Actualizar tabs inmediatamente
            registerTab.className = 'flex-1 py-2 sm:py-3 px-3 sm:px-4 text-center font-semibold rounded-lg transition-all duration-300 bg-gradient-to-r from-green-500 to-emerald-600 text-white transform translateY(-2px) shadow-lg shadow-green-500/25 text-sm sm:text-base';
            loginTab.className = 'flex-1 py-2 sm:py-3 px-3 sm:px-4 text-center font-semibold rounded-lg transition-all duration-300 tab-inactive text-sm sm:text-base';
            
            setTimeout(() => {
                // Ocultar login y mostrar registro
                loginWrapper.classList.add('hidden');
                loginWrapper.classList.remove('form-slide-out-left');
                
                registerWrapper.classList.remove('hidden');
                registerWrapper.classList.add('form-slide-in-right');
                
                currentForm = 'register';
                
                // Limpiar animación después de completarse
                setTimeout(() => {
                    registerWrapper.classList.remove('form-slide-in-right');
                    isTransitioning = false;
                }, 500);
            }, 300);
        }
        
        // Efecto de clic en los inputs
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Efecto de hover en los botones
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05) translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) translateY(0)';
            });
        });
        
        // Agregar efectos de sonido (opcional)
        function playTransitionSound() {
            // Crear un sonido sutil usando Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(400, audioContext.currentTime + 0.1);
            
            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        }
        
        // Agregar sonido a las transiciones (comentado por defecto)
        // document.getElementById('loginTab').addEventListener('click', playTransitionSound);
        // document.getElementById('registerTab').addEventListener('click', playTransitionSound);
    </script>
</body>
</html>
