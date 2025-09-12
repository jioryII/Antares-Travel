<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/enviar_correo_admin.php';

session_start();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    try {
        // Buscar administrador por email
        $stmt = $conn->prepare("SELECT id_admin, nombre, email, email_verificado, token_verificacion, token_expira FROM administradores WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$admin = $result->fetch_assoc()) {
            $mensaje = "❌ No existe una cuenta de administrador registrada con ese correo.";
            $tipo_mensaje = "error";
        } elseif ($admin['email_verificado']) {
            $mensaje = "✅ Tu correo ya está verificado. Puedes iniciar sesión normalmente.";
            $tipo_mensaje = "info";
        } else {
            // Verificar si tiene un token válido vigente
            $tieneTokenValido = false;
            if ($admin['token_verificacion'] && $admin['token_expira']) {
                $tieneTokenValido = strtotime($admin['token_expira']) > time();
            }

            $token = $admin['token_verificacion'];
            
            if (!$tieneTokenValido) {
                // Generar nuevo token
                $token = bin2hex(random_bytes(32));
                $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                $stmtUpdate = $conn->prepare("UPDATE administradores SET token_verificacion = ?, token_expira = ? WHERE id_admin = ?");
                $stmtUpdate->bind_param("ssi", $token, $fechaExpiracion, $admin['id_admin']);
                $stmtUpdate->execute();
            }

            // Enviar correo de verificación
            $nombre = $admin['nombre'];
            $link = "https://antarestravelperu.com/src/admin/auth/verificar_email_admin.php?token=" . $token;
            $resultado = enviarCorreoVerificacionAdmin($email, $nombre, $link);

            if ($resultado === true) {
                $mensaje = "✅ Correo de verificación enviado correctamente. Revisa tu bandeja de entrada y spam.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "❌ Error al enviar el correo: " . $resultado;
                $tipo_mensaje = "error";
            }
        }
    } catch (Exception $e) {
        error_log("Error al reenviar verificación admin: " . $e->getMessage());
        $mensaje = "❌ Error interno del servidor. Por favor, intenta más tarde.";
        $tipo_mensaje = "error";
    }
} elseif ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['email'])) {
    // Prellenar el email si viene por GET
    $email = htmlspecialchars($_GET['email']);
} else {
    $email = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reenviar Verificación - Panel Admin | Antares Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 via-white to-orange-100 min-h-screen flex items-center justify-center p-4">
    
    <!-- Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-10 left-10 w-72 h-72 bg-orange-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-yellow-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
    </div>

    <div class="relative z-10 w-full max-w-md">
        <!-- Logo y Header -->
        <div class="text-center mb-8">
            <img src="../../../imagenes/antares_logozz2.png" alt="Antares Travel" class="mx-auto h-16 w-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Panel de Administración</h1>
            <p class="text-gray-600">Reenviar Verificación de Correo</p>
        </div>

        <!-- Card Principal -->
        <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl border border-white/20 p-8">
            
            <!-- Icono y Descripción -->
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-envelope-open-text text-2xl text-orange-600"></i>
                </div>
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Reenviar Verificación</h2>
                <p class="text-gray-600 text-sm">Ingresa tu email para recibir un nuevo enlace de verificación</p>
            </div>

            <!-- Mensaje de resultado -->
            <?php if (!empty($mensaje)): ?>
                <div class="mb-6 p-4 rounded-lg <?php 
                    echo $tipo_mensaje === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 
                        ($tipo_mensaje === 'error' ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-blue-50 border border-blue-200 text-blue-800'); 
                ?>">
                    <div class="flex items-center">
                        <i class="fas <?php 
                            echo $tipo_mensaje === 'success' ? 'fa-check-circle text-green-500' : 
                                ($tipo_mensaje === 'error' ? 'fa-times-circle text-red-500' : 'fa-info-circle text-blue-500'); 
                        ?> mr-3"></i>
                        <p class="text-sm font-medium"><?php echo $mensaje; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-orange-500"></i>
                        Email de Administrador
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 bg-white/50 backdrop-blur-sm"
                        placeholder="admin@antares.com"
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    Reenviar Verificación
                </button>
            </form>

            <!-- Enlaces adicionales -->
            <div class="mt-8 text-center space-y-3">
                <a href="login.php" class="block text-orange-600 hover:text-orange-700 font-medium transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver al Login
                </a>
                
                <div class="text-gray-400 text-sm">
                    <p>¿Problemas con la verificación?</p>
                    <a href="mailto:soporte@antares.com" class="text-orange-500 hover:underline">Contactar Soporte</a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-xs">
            <p>&copy; <?php echo date('Y'); ?> Antares Travel - Sistema de Administración</p>
        </div>
    </div>

    <script>
        // Auto-focus en el campo email si está vacío
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (!emailInput.value) {
                emailInput.focus();
            }
        });

        // Animación del botón al enviar
        document.querySelector('form').addEventListener('submit', function() {
            const button = document.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
            button.disabled = true;
        });
    </script>
</body>
</html>
