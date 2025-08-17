<?php
session_start();
require_once __DIR__ . '/src/funtions/google_auth.php';

$client = getGoogleClient();

// Procesar Google One Tap
if (isset($_POST['credential'])) {
    if (procesarGoogleCredential($_POST['credential'], $conn, $client)) {
        header("Location: index.php");
        exit;
    } else {
        echo "❌ Token inválido";
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    cerrarSesion();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Antares Travel</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-400 to-cyan-400 min-h-screen flex flex-col">

<header class="fixed top-0 w-full bg-white bg-opacity-90 shadow-md flex justify-end items-center p-4 z-50">
    <?php if (!isset($_SESSION['user_email'])): ?>
        <a href="./src/auth/login.php" class="ml-3 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">Iniciar sesión</a>
        <a href="./src/auth/register.php" class="ml-3 px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700">Registrarse</a>
    <?php else: ?>
        <div class="flex items-center gap-3">
            <img src="<?php echo $_SESSION['user_picture']; ?>" alt="Avatar" class="w-10 h-10 rounded-full">
            <span class="font-semibold"><?php echo $_SESSION['user_name']; ?></span>
            <a href="index.php?logout=1" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">Cerrar sesión</a>
        </div>
    <?php endif; ?>
</header>

<main class="flex-grow flex flex-col justify-center items-center text-center pt-24 px-4">
    <h1 class="text-5xl font-bold text-white drop-shadow-lg mb-4">Bienvenido a Antares Travel</h1>
    <p class="text-white text-lg mb-8 drop-shadow-md">Explora los mejores destinos y paquetes turísticos del mundo</p>

    <?php if (!isset($_SESSION['user_email'])): ?>
        <div id="g_id_onload"
             data-client_id="454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com"
             data-context="signin"
             data-ux_mode="popup"
             data-auto_prompt="true"
             data-callback="handleCredentialResponse">
        </div>
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
        </script>
    <?php endif; ?>

    <a href="destinos.php" class="px-8 py-3 bg-orange-500 text-white rounded-full font-bold hover:bg-orange-600 transition">Ver Destinos</a>
</main>
</body>
</html>
