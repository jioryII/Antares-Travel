<?php
session_start();

// Función para generar URLs absolutas desde la raíz del dominio
function getAdminUrl($path) {
    // Generar URL absoluta desde la raíz del dominio
    $cleanPath = ltrim($path, './');
    return '/src/admin/' . $cleanPath;
}

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
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
            header('Location: ' . getAdminUrl('pages/dashboard/'));
            exit();
        } else {
            $error = $resultado['message'];
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
            $success = $resultado['message'];
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
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-purple-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo y título -->
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-white">
                    Antares Travel
                </h2>
                <p class="mt-2 text-sm text-blue-200">
                    Panel de Administración
                </p>
            </div>

            <!-- Contenedor de formularios -->
            <div class="bg-white rounded-lg shadow-xl p-8">
                <!-- Tabs -->
                <div class="flex mb-6">
                    <button onclick="showLogin()" id="loginTab" class="flex-1 py-2 px-4 text-center font-medium rounded-l-lg bg-blue-600 text-white">
                        Iniciar Sesión
                    </button>
                    <button onclick="showRegister()" id="registerTab" class="flex-1 py-2 px-4 text-center font-medium rounded-r-lg bg-gray-200 text-gray-700">
                        Registrarse
                    </button>
                </div>

                <!-- Mensajes -->
                <?php if ($error): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Login -->
                <form id="loginForm" method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input id="email" name="email" type="email" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="admin@antarestravel.com">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-lock mr-2"></i>Contraseña
                        </label>
                        <input id="password" name="password" type="password" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="••••••••">
                    </div>

                    <button type="submit" name="login" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Iniciar Sesión
                    </button>
                </form>

                <!-- Formulario de Registro -->
                <form id="registerForm" method="POST" class="space-y-6 hidden">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-user mr-2"></i>Nombre Completo
                        </label>
                        <input id="nombre" name="nombre" type="text" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Juan Pérez">
                    </div>

                    <div>
                        <label for="reg_email" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input id="reg_email" name="email" type="email" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="admin@antarestravel.com">
                    </div>

                    <div>
                        <label for="reg_password" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-lock mr-2"></i>Contraseña
                        </label>
                        <input id="reg_password" name="password" type="password" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="••••••••">
                    </div>

                    <div>
                        <label for="confirmar_password" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-lock mr-2"></i>Confirmar Contraseña
                        </label>
                        <input id="confirmar_password" name="confirmar_password" type="password" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="••••••••">
                    </div>

                    <button type="submit" name="register" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                        <i class="fas fa-user-plus mr-2"></i>
                        Registrarse
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showLogin() {
            document.getElementById('loginForm').classList.remove('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('loginTab').classList.add('bg-blue-600', 'text-white');
            document.getElementById('loginTab').classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById('registerTab').classList.add('bg-gray-200', 'text-gray-700');
            document.getElementById('registerTab').classList.remove('bg-blue-600', 'text-white');
        }

        function showRegister() {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.remove('hidden');
            document.getElementById('registerTab').classList.add('bg-green-600', 'text-white');
            document.getElementById('registerTab').classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById('loginTab').classList.add('bg-gray-200', 'text-gray-700');
            document.getElementById('loginTab').classList.remove('bg-blue-600', 'text-white');
        }
    </script>
</body>
</html>
