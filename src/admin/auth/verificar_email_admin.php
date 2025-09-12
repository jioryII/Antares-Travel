<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/enviar_correo_admin.php';

session_start();

$token = $_GET['token'] ?? '';
$mensaje = null;

if (!empty($token)) {
    try {
        // Buscar administrador con el token válido y no expirado
        $sql = "SELECT id_admin, nombre, email, email_verificado 
                FROM administradores 
                WHERE token_verificacion = ? 
                AND token_expira > NOW() 
                AND email_verificado = FALSE 
                LIMIT 1";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            // Verificar email del administrador
            $sql_update = "UPDATE administradores 
                          SET email_verificado = TRUE, 
                              token_verificacion = NULL, 
                              token_expira = NULL 
                          WHERE id_admin = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$admin['id_admin']]);
            
            if ($stmt_update->rowCount() > 0) {
                // Notificar a superadministradores sobre nueva solicitud
                require_once __DIR__ . '/functions.php';
                $resultado_notificacion = notificarSuperadministradores($admin['id_admin']);
                
                if ($resultado_notificacion['success']) {
                    $mensaje = [
                        'tipo' => 'success',
                        'texto' => 'Tu correo electrónico ha sido verificado exitosamente. Se ha notificado a los superadministradores para que aprueben tu acceso al panel de administración.',
                        'admin_id' => $admin['id_admin'],
                        'admin_nombre' => $admin['nombre'],
                        'admin_email' => $admin['email'],
                        'correos_enviados' => $resultado_notificacion['correos_enviados'] ?? 0
                    ];
                } else {
                    $mensaje = [
                        'tipo' => 'warning',
                        'texto' => 'Tu correo electrónico ha sido verificado, pero hubo un problema al notificar a los superadministradores. Tu solicitud será procesada manualmente.',
                        'admin_id' => $admin['id_admin'],
                        'admin_nombre' => $admin['nombre'],
                        'admin_email' => $admin['email']
                    ];
                }
            } else {
                throw new Exception('Error al actualizar el estado de verificación');
            }
        } else {
            // Verificar si el token existe pero ya está verificado
            $sql_check = "SELECT id_admin, nombre, email, email_verificado 
                         FROM administradores 
                         WHERE token_verificacion = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$token]);
            $admin_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($admin_check && $admin_check['email_verificado']) {
                $mensaje = [
                    'tipo' => 'info',
                    'texto' => 'Este correo electrónico ya ha sido verificado anteriormente. Tu cuenta está pendiente de aprobación por un superadministrador.',
                    'admin_nombre' => $admin_check['nombre'],
                    'admin_email' => $admin_check['email']
                ];
            } else {
                $mensaje = [
                    'tipo' => 'error',
                    'texto' => 'Token de verificación inválido o expirado. Si necesitas un nuevo token, solicita el reenvío del correo de verificación desde la página de login.'
                ];
            }
        }
        
    } catch (Exception $e) {
        $mensaje = [
            'tipo' => 'error',
            'texto' => 'Error interno del servidor. Por favor, inténtalo más tarde o contacta al soporte técnico.'
        ];
        error_log("Error en verificación de email admin: " . $e->getMessage());
    }
} else {
    $mensaje = [
        'tipo' => 'error',
        'texto' => 'Token de verificación no proporcionado. Por favor, utiliza el enlace completo del correo de verificación.'
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Email - Admin Antares Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <img src="../../../imagenes/antares_logo.png" alt="Antares Travel" class="mx-auto mb-4 h-16 w-auto">
            <h1 class="text-4xl font-bold text-white mb-2">Verificación de Email</h1>
            <p class="text-blue-100">Sistema de Administración - Antares Travel</p>
        </div>

        <!-- Tarjeta principal -->
        <div class="bg-white rounded-2xl p-8 card-shadow">
            <!-- Debug info -->
            <div class="mb-4 p-4 bg-gray-100 rounded-lg text-sm">
                <p><strong>🔍 Debug Info:</strong></p>
                <p><strong>Token recibido:</strong> <?php echo $token ? substr($token, 0, 20) . '...' : 'No token'; ?></p>
                <p><strong>Fecha actual:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>

            <!-- Mensaje principal -->
            <div class="text-center mb-6">
                <?php if ($mensaje): ?>
                    <div class="p-6 rounded-lg mb-6 <?php 
                        echo $mensaje['tipo'] === 'success' ? 'bg-green-50 border border-green-200' : 
                             ($mensaje['tipo'] === 'warning' ? 'bg-yellow-50 border border-yellow-200' : 
                             ($mensaje['tipo'] === 'info' ? 'bg-blue-50 border border-blue-200' : 'bg-red-50 border border-red-200')); 
                    ?>">
                        <div class="flex items-center justify-center mb-4">
                            <?php if ($mensaje['tipo'] === 'success'): ?>
                                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                                </div>
                            <?php elseif ($mensaje['tipo'] === 'warning'): ?>
                                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-exclamation-triangle text-3xl text-yellow-500"></i>
                                </div>
                            <?php elseif ($mensaje['tipo'] === 'info'): ?>
                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-info-circle text-3xl text-blue-500"></i>
                                </div>
                            <?php else: ?>
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-times-circle text-3xl text-red-500"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="text-lg font-semibold <?php 
                            echo $mensaje['tipo'] === 'success' ? 'text-green-800' : 
                                 ($mensaje['tipo'] === 'warning' ? 'text-yellow-800' : 
                                 ($mensaje['tipo'] === 'info' ? 'text-blue-800' : 'text-red-800')); 
                        ?> mb-2">
                            <?php if ($mensaje['tipo'] === 'success'): ?>
                                ✅ Email Verificado con Éxito
                            <?php elseif ($mensaje['tipo'] === 'warning'): ?>
                                ⚠️ Verificado con Advertencia  
                            <?php elseif ($mensaje['tipo'] === 'info'): ?>
                                ℹ️ Email Ya Verificado
                            <?php else: ?>
                                ❌ Error de Verificación
                            <?php endif; ?>
                        </h3>
                        
                        <p class="<?php 
                            echo $mensaje['tipo'] === 'success' ? 'text-green-700' : 
                                 ($mensaje['tipo'] === 'warning' ? 'text-yellow-700' : 
                                 ($mensaje['tipo'] === 'info' ? 'text-blue-700' : 'text-red-700')); 
                        ?>">
                            <?php echo htmlspecialchars($mensaje['texto']); ?>
                        </p>
                    </div>

                    <!-- Información del admin si es exitoso -->
                    <?php if (($mensaje['tipo'] === 'success' || $mensaje['tipo'] === 'info') && isset($mensaje['admin_nombre'])): ?>
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h3 class="font-semibold text-gray-700 mb-2">Detalles de la cuenta:</h3>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><span class="font-medium">👤 Nombre:</span> <?php echo htmlspecialchars($mensaje['admin_nombre']); ?></p>
                                <p><span class="font-medium">📧 Email:</span> <?php echo htmlspecialchars($mensaje['admin_email']); ?></p>
                                <?php if ($mensaje['tipo'] === 'success'): ?>
                                    <p><span class="font-medium">✅ Verificado:</span> <?php echo date('d/m/Y H:i'); ?></p>
                                    <p><span class="font-medium">⏳ Estado:</span> Pendiente de aprobación</p>
                                    <?php if (isset($mensaje['correos_enviados']) && $mensaje['correos_enviados'] > 0): ?>
                                        <p><span class="font-medium">📨 Notificaciones:</span> Enviadas a <?php echo $mensaje['correos_enviados']; ?> superadministrador(es)</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($mensaje['tipo'] === 'success' || $mensaje['tipo'] === 'info'): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-yellow-500 mr-3"></i>
                                    <div>
                                        <p class="text-sm font-medium text-yellow-800">Siguiente paso:</p>
                                        <p class="text-sm text-yellow-700">Un superadministrador revisará tu solicitud y te notificará por correo cuando sea aprobada.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Botones de acción -->
                    <div class="text-center space-y-3">
                        <?php if ($mensaje['tipo'] === 'success' || $mensaje['tipo'] === 'info'): ?>
                            <a href="login.php" class="block w-full bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                                <i class="fas fa-hourglass-half mr-2"></i>
                                Ir a Login (Pendiente de Aprobación)
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="block w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Volver al Login
                            </a>
                        <?php endif; ?>
                        
                        <a href="reenviar_verificacion_admin.php" class="block w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-200">
                            <i class="fas fa-envelope mr-2"></i>
                            ¿Necesitas reenviar verificación?
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Procesando verificación...</p>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <div class="flex items-center justify-center text-gray-500 text-sm">
                    <i class="fas fa-shield-alt mr-2"></i>
                    <span>Sistema seguro de verificación de email</span>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    © <?php echo date('Y'); ?> Antares Travel. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
