<?php
session_start();

// Incluir conexión y funciones de usuario
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../funtions/usuarios.php";
require_once __DIR__ . "/enviar_correo.php"; // Asegúrate de incluir PHPMailer y tu función

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = $_POST['nombre'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // Verificar si el correo ya existe
    $usuarioExistente = obtenerUsuarioPorEmail($conn, $email);
    if ($usuarioExistente) {
        $error = "Ya existe una cuenta registrada con ese correo. Puedes iniciar sesión o usar otro correo.";
    } else {
        // Manejar avatar subido
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = uniqid('avatar_') . "." . $ext;
            $rutaDestino = __DIR__ . "/../../storage/uploads/avatars/" . $nombreArchivo;

            // Crear carpeta si no existe
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

        // Insertar usuario en la base de datos
        $idUsuario = insertarUsuario($conn, $nombre, $email, $password_hash, 'manual', null, $avatar, null);

        // Reemplazar la sección de envío de correo con:
        if ($idUsuario && is_numeric($idUsuario)) {
            // Generar y guardar token
            $token = bin2hex(random_bytes(32)); // 32 bytes = 64 caracteres hex
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $sqlToken = "INSERT INTO email_verificacion (id_usuario, token, fecha_expiracion) 
                         VALUES (?, ?, ?)";
            try {
                $stmt = $conn->prepare($sqlToken);
                $stmt->bind_param("iss", $idUsuario, $token, $fechaExpiracion);

                if ($stmt->execute()) {
                    $link = "http://localhost/Antares-Travel/src/auth/verificar_email.php?token=" . $token;
                    if (enviarCorreoVerificacion($email, $nombre, $link)) {
                        // Mostrar popup en vez de redirigir
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
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro - Antares Travel</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-400 to-cyan-300 min-h-screen flex items-center justify-center font-sans">
  <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Registro Manual</h2>

    <?php if (!empty($error)): ?>
      <p class="text-red-600 text-center mb-4"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if (isset($popup) && $popup): ?>
      <div id="popup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50">
        <div class="bg-white rounded-xl shadow-xl p-8 max-w-sm w-full text-center">
          <h3 class="text-xl font-bold mb-4 text-blue-600">¡Revisa tu correo!</h3>
          <p class="mb-2">Te hemos enviado un enlace de verificación a <span class="font-semibold"><?php echo htmlspecialchars($email); ?></span>.</p>
          <p class="mb-4">Por favor, verifica tu cuenta antes de iniciar sesión.</p>
          <div class="flex justify-center mb-6">
            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
          </div>
          <div class="flex flex-col gap-3">
            <form action="reenviar_verificacion.php" method="POST">
              <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
              <button type="submit" class="bg-blue-400 hover:bg-blue-600 text-white px-4 py-2 rounded-lg w-full font-semibold">
                Reenviar correo de verificación
              </button>
            </form>
            <button onclick="window.location.href='login.php'" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg w-full font-semibold">
              Continuar al login
            </button>
          </div>
        </div>
      </div>
      <script>
        setTimeout(function() {
          window.location.href = "login.php";
        }, 300000); // 5 minutos = 300,000 ms
      </script>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
      <input type="text" name="nombre" placeholder="Nombre" required
             class="border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-400">
      <input type="email" name="email" placeholder="Correo" required
             class="border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-400">
      <input type="password" name="password" placeholder="Contraseña" required
             class="border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-400">
      <input type="file" name="avatar" accept="image/*"
             class="border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-400">

      <button type="submit"
              class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg transition duration-300">
        Registrarse
      </button>
    </form>

    <p class="text-center text-gray-600 mt-4">
      ¿Ya tienes cuenta? 
      <a href="login.php" class="text-blue-500 hover:underline font-semibold">Inicia sesión</a>
    </p>
  </div>
</body>
</html>
