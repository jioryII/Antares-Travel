<?php
session_start();

// Conexión y funciones de usuario
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../funtions/usuarios.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $usuario = obtenerUsuarioPorEmail($conn, $email);

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        if ($usuario['email_verificado']) {
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_name']  = $usuario['nombre'];
            $_SESSION['user_picture'] = isset($usuario['avatar_url']) 
              ? "http://localhost/Antares-Travel/" . $usuario['avatar_url'] 
              : "http://localhost/Antares-Travel/storage/uploads/avatars/default.png";
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Antares Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Iconos de FontAwesome para logitos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-H...==" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>
<body class="bg-gradient-to-r from-blue-400 to-cyan-400 min-h-screen flex items-center justify-center px-4">
<div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full">
    <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">Iniciar Sesión</h2>

    <?php if (!empty($error)): ?>
        <p class="text-red-600 font-semibold mb-4 text-center"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" class="flex flex-col gap-4">
        <input type="email" name="email" placeholder="Correo" required
               class="p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        <input type="password" name="password" placeholder="Contraseña" required
               class="p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        <button type="submit"
                class="bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Iniciar sesión</button>
    </form>

    <div class="text-center my-4 text-gray-500">O usa un proveedor</div>
    <div class="flex justify-center gap-4 flex-wrap">
        <a href="oauth_callback.php?provider=google"
           class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow hover:shadow-lg transition">
            <i class="fab fa-google text-red-500 text-2xl"></i>
        </a>
        <a href="oauth_callback.php?provider=facebook"
           class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow hover:shadow-lg transition">
            <i class="fab fa-facebook-f text-blue-700 text-2xl"></i>
        </a>
        <a href="oauth_callback.php?provider=apple"
           class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow hover:shadow-lg transition">
            <i class="fab fa-apple text-gray-800 text-2xl"></i>
        </a>
        <a href="oauth_callback.php?provider=microsoft"
           class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow hover:shadow-lg transition">
            <i class="fab fa-windows text-green-600 text-2xl"></i>
        </a>
        <a href="oauth_callback.php?provider=telefono"
           class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow hover:shadow-lg transition">
            <i class="fas fa-phone text-yellow-500 text-2xl"></i>
        </a>
    </div>

    <p class="text-center mt-6 text-gray-600">
        ¿No tienes cuenta? <a href="register.php" class="text-blue-600 font-semibold hover:underline">Regístrate</a>
    </p>
</div>
</body>
</html>
