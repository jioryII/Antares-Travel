<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../funtions/usuarios.php';
require_once __DIR__ . '/enviar_correo_admin.php';

$mensaje = null;
$admin_info = null;
$accion = null;
$admin_logueado = null;

// Verificar que el usuario logueado sea un superadministrador
if (!isset($_SESSION['admin_id'])) {
    $mensaje = [
        'tipo' => 'error',
        'texto' => 'Debe estar logueado como administrador para realizar esta acción.'
    ];
} else {
    // Verificar que sea superadministrador
    $stmt = $pdo->prepare("
        SELECT id_admin, nombre, email, rol, acceso_aprobado 
        FROM administradores 
        WHERE id_admin = ? AND rol = 'superadmin' AND acceso_aprobado = true
    ");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin_logueado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin_logueado) {
        $mensaje = [
            'tipo' => 'error',
            'texto' => 'Solo los superadministradores pueden aprobar o rechazar nuevos administradores.'
        ];
    }
}

// Procesar la acción si todo está correcto
if (!$mensaje && isset($_GET['token']) && isset($_GET['accion'])) {
    $token = $_GET['token'];
    $accion = $_GET['accion'];
    
    try {
        // Buscar el token de aprobación
        $stmt = $pdo->prepare("
            SELECT ta.*, a.nombre, a.email, a.rol
            FROM tokens_aprobacion ta
            JOIN administradores a ON ta.id_admin_solicitante = a.id_admin
            WHERE (ta.token_aprobacion = ? OR ta.token_rechazo = ?) 
              AND ta.procesado = false 
              AND ta.fecha_expiracion > NOW()
        ");
        $stmt->execute([$token, $token]);
        $token_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$token_info) {
            throw new Exception('Token inválido, expirado o ya utilizado.');
        }
        
        $admin_info = [
            'id' => $token_info['id_admin_solicitante'],
            'nombre' => $token_info['nombre'],
            'email' => $token_info['email'],
            'rol' => $token_info['rol']
        ];
        
        // Procesar según la acción
        if ($accion === 'aprobar') {
            // Aprobar al administrador
            $stmt = $pdo->prepare("
                UPDATE administradores 
                SET acceso_aprobado = true, 
                    aprobado_por = ?, 
                    fecha_aprobacion = NOW()
                WHERE id_admin = ?
            ");
            $stmt->execute([$admin_logueado['id_admin'], $token_info['id_admin_solicitante']]);
            
            // Marcar el token como usado
            $stmt = $pdo->prepare("UPDATE tokens_aprobacion SET procesado = true WHERE token_aprobacion = ? OR token_rechazo = ?");
            $stmt->execute([$token, $token]);
            
            // Enviar correo de aprobación
            if (enviarCorreoAccesoAprobado($token_info['email'], $token_info['nombre'])) {
                $mensaje = [
                    'tipo' => 'success',
                    'texto' => 'Administrador aprobado exitosamente. Se ha enviado un correo de confirmación.',
                    'admin_aprobado' => $token_info['nombre'],
                    'accion_realizada' => 'aprobado'
                ];
            } else {
                $mensaje = [
                    'tipo' => 'warning',
                    'texto' => 'Administrador aprobado pero hubo un error al enviar el correo de confirmación.',
                    'admin_aprobado' => $token_info['nombre'],
                    'accion_realizada' => 'aprobado'
                ];
            }
            
        } elseif ($accion === 'rechazar') {
            // Rechazar al administrador (eliminar cuenta)
            $stmt = $pdo->prepare("DELETE FROM administradores WHERE id_admin = ?");
            $stmt->execute([$token_info['id_admin_solicitante']]);
            
            // Marcar el token como usado
            $stmt = $pdo->prepare("UPDATE tokens_aprobacion SET procesado = true WHERE token_aprobacion = ? OR token_rechazo = ?");
            $stmt->execute([$token, $token]);
            
            // Enviar correo de rechazo
            if (enviarCorreoAccesoRechazado($token_info['email'], $token_info['nombre'])) {
                $mensaje = [
                    'tipo' => 'success',
                    'texto' => 'Solicitud rechazada y cuenta eliminada. Se ha notificado al usuario.',
                    'admin_rechazado' => $token_info['nombre'],
                    'accion_realizada' => 'rechazado'
                ];
            } else {
                $mensaje = [
                    'tipo' => 'warning',
                    'texto' => 'Solicitud rechazada pero hubo un error al enviar el correo de notificación.',
                    'admin_rechazado' => $token_info['nombre'],
                    'accion_realizada' => 'rechazado'
                ];
            }
        } else {
            throw new Exception('Acción no válida.');
        }
        
    } catch (Exception $e) {
        $mensaje = [
            'tipo' => 'error',
            'texto' => 'Error: ' . $e->getMessage()
        ];
    }
} elseif (!$mensaje && (!isset($_GET['token']) || !isset($_GET['accion']))) {
    $mensaje = [
        'tipo' => 'error',
        'texto' => 'Parámetros faltantes en la URL.'
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Aprobaciones - Admin Antares Travel</title>
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
            <h1 class="text-4xl font-bold text-white mb-2">Gestión de Aprobaciones</h1>
            <p class="text-blue-100">Sistema de Administración - Antares Travel</p>
        </div>

        <!-- Tarjeta principal -->
        <div class="bg-white rounded-2xl p-8 card-shadow">
            <!-- Información del admin logueado -->
            <?php if ($admin_logueado): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-user-shield text-blue-500 text-lg mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-blue-800">Sesión activa:</p>
                            <p class="text-sm text-blue-700"><?php echo htmlspecialchars($admin_logueado['nombre']); ?> (<?php echo htmlspecialchars($admin_logueado['email']); ?>)</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Mensaje principal -->
            <div class="text-center mb-6">
                <?php if ($mensaje): ?>
                    <div class="p-6 rounded-lg mb-6 <?php 
                        echo $mensaje['tipo'] === 'success' ? 'bg-green-50 border border-green-200' : 
                             ($mensaje['tipo'] === 'warning' ? 'bg-yellow-50 border border-yellow-200' : 'bg-red-50 border border-red-200'); 
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
                            <?php else: ?>
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-times-circle text-3xl text-red-500"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="text-lg font-semibold <?php 
                            echo $mensaje['tipo'] === 'success' ? 'text-green-800' : 
                                 ($mensaje['tipo'] === 'warning' ? 'text-yellow-800' : 'text-red-800'); 
                        ?> mb-2">
                            <?php if ($mensaje['tipo'] === 'success'): ?>
                                <?php if (isset($mensaje['accion_realizada']) && $mensaje['accion_realizada'] === 'aprobado'): ?>
                                    ✅ Administrador Aprobado
                                <?php elseif (isset($mensaje['accion_realizada']) && $mensaje['accion_realizada'] === 'rechazado'): ?>
                                    ❌ Solicitud Rechazada
                                <?php else: ?>
                                    Operación Completada
                                <?php endif; ?>
                            <?php elseif ($mensaje['tipo'] === 'warning'): ?>
                                ⚠️ Operación Completada con Advertencia
                            <?php else: ?>
                                ❌ Error en la Operación
                            <?php endif; ?>
                        </h3>
                        
                        <p class="<?php 
                            echo $mensaje['tipo'] === 'success' ? 'text-green-700' : 
                                 ($mensaje['tipo'] === 'warning' ? 'text-yellow-700' : 'text-red-700'); 
                        ?>">
                            <?php echo htmlspecialchars($mensaje['texto']); ?>
                        </p>
                    </div>

                    <!-- Información adicional si es exitoso -->
                    <?php if ($mensaje['tipo'] === 'success' && $admin_info): ?>
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h3 class="font-semibold text-gray-700 mb-3">Detalles de la operación:</h3>
                            <div class="text-sm text-gray-600 space-y-2">
                                <div class="flex justify-between">
                                    <span class="font-medium">👤 Administrador:</span>
                                    <span><?php echo htmlspecialchars($admin_info['nombre']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">📧 Email:</span>
                                    <span><?php echo htmlspecialchars($admin_info['email']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">⚡ Acción:</span>
                                    <span class="<?php echo $mensaje['accion_realizada'] === 'aprobado' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $mensaje['accion_realizada'] === 'aprobado' ? 'APROBADO' : 'RECHAZADO'; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">🕒 Fecha:</span>
                                    <span><?php echo date('d/m/Y H:i:s'); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">👨‍💼 Procesado por:</span>
                                    <span><?php echo htmlspecialchars($admin_logueado['nombre']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Botones de acción -->
            <div class="text-center space-y-3">
                <?php if ($admin_logueado): ?>
                    <a href="../pages/dashboard/" class="block w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Volver al Dashboard
                    </a>
                    
                    <a href="login.php" class="block w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Cerrar Sesión
                    </a>
                <?php else: ?>
                    <?php 
                    // Construir URL de login preservando los parámetros GET
                    $login_url = 'login.php';
                    if (isset($_GET['token']) && isset($_GET['accion'])) {
                        $login_url .= '?token=' . urlencode($_GET['token']) . '&accion=' . urlencode($_GET['accion']);
                    }
                    ?>
                    <a href="<?php echo $login_url; ?>" class="block w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <div class="flex items-center justify-center text-gray-500 text-sm">
                    <i class="fas fa-shield-alt mr-2"></i>
                    <span>Sistema seguro de gestión de administradores</span>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    © <?php echo date('Y'); ?> Antares Travel. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
