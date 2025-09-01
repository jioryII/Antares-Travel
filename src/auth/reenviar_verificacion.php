
<?php
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/enviar_correo.php";
require_once __DIR__ . "/../funtions/usuarios.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $usuario = obtenerUsuarioPorEmail($conn, $email);

    if (!$usuario) {
        $mensaje = "❌ No existe una cuenta registrada con ese correo.";
    } elseif ($usuario['email_verificado']) {
        $mensaje = "✅ Tu correo ya está verificado. Puedes iniciar sesión.";
    } else {
        // Buscar token existente y verificar expiración
        $stmt = $conn->prepare("SELECT token, fecha_expiracion FROM email_verificacion WHERE id_usuario = ?");
        $stmt->bind_param("i", $usuario['id_usuario']);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokenData = $result->fetch_assoc();

        if ($tokenData && strtotime($tokenData['fecha_expiracion']) > time()) {
            // Token válido, reutilizar
            $token = $tokenData['token'];
        } else {
            // Generar nuevo token
            $token = bin2hex(random_bytes(32));
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $stmtInsert = $conn->prepare("INSERT INTO email_verificacion (id_usuario, token, fecha_expiracion) VALUES (?, ?, ?)");
            $stmtInsert->bind_param("iss", $usuario['id_usuario'], $token, $fechaExpiracion);
            $stmtInsert->execute();
        }

        $nombre = $usuario['nombre'];
        $link = "https://jiory.opalstacked.com/Antares-Travel/src/auth/verificar_email.php?token=" . $token;
        $resultado = enviarCorreoVerificacion($email, $nombre, $link);

        if ($resultado === true) {
            $mensaje = "✅ Correo de verificación reenviado correctamente. Revisa tu bandeja de entrada.";
        } else {
            $mensaje = "❌ Error al reenviar el correo: $resultado";
        }
    }
} else {
    $mensaje = "❌ Solicitud inválida.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reenviar verificación - Antares Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-400 to-cyan-300 min-h-screen flex items-center justify-center font-sans">
    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md text-center">
        <h2 class="text-2xl font-bold text-blue-600 mb-6">Reenvío de verificación</h2>
        <div class="mb-4">
            <p class="<?php echo strpos($mensaje, '✅') === 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo $mensaje; ?>
            </p>
        </div>
        <button onclick="window.location.href='login.php'" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold">
            Ir al login
        </button>
    </div>