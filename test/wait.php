<?php
// esperar_verificacion.php

// Tomamos nombre y correo desde GET (puedes enviarlos desde registro.php)
$nombre = $_GET['nombre'] ?? 'Usuario';
$email  = $_GET['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Verifica tu correo - Antares Travel</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-400 to-cyan-300 min-h-screen flex items-center justify-center font-sans">
  <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md text-center">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">¡Casi listo, <?= htmlspecialchars($nombre) ?>!</h2>
    
    <p class="text-gray-700 mb-4">
      Hemos enviado un correo a <span class="font-semibold"><?= htmlspecialchars($email) ?></span> para verificar tu cuenta.
    </p>
    
    <p class="text-gray-700 mb-4">
      Por favor, revisa tu bandeja de entrada y haz clic en el enlace de verificación para activar tu cuenta.
    </p>

    <p class="text-gray-500 text-sm mb-6">
      Si no encuentras el correo, revisa tu carpeta de spam o espera unos minutos.
    </p>

    <div class="flex flex-col gap-4">
      <!-- Reenviar correo -->
      <form method="POST" action="reenviar_verificacion.php">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="hidden" name="nombre" value="<?= htmlspecialchars($nombre) ?>">
        <button type="submit"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300 w-full">
          Reenviar correo
        </button>
      </form>

      <!-- Continuar al login -->
      <a href="login.php"
         class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded-lg transition duration-300 block">
        Continuar
      </a>
    </div>
  </div>
</body>
</html>
