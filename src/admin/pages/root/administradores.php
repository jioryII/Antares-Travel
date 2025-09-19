<?php
require_once '../../config/config.php';
require_once '../../../config/conexion.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar que sea SuperAdmin
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Solo SuperAdmin puede acceder
if (!isset($_SESSION['admin_rol']) || $_SESSION['admin_rol'] !== 'superadmin') {
    header('Location: ../dashboard/?error=acceso_denegado');
    exit;
}

$page_title = "Administradores del Sistema";

// Obtener todos los administradores (usar nombres correctos de columnas)
$administradores = [];
try {
    // Usar los nombres reales de columnas de la BD
    $query = "SELECT 
        id_admin as id, 
        nombre, 
        email, 
        rol, 
        CASE 
            WHEN bloqueado = 1 THEN 'bloqueado'
            WHEN acceso_aprobado = 0 THEN 'pendiente'
            ELSE 'activo'
        END as estado,
        creado_en as fecha_creacion, 
        ultimo_login as ultimo_acceso,
        intentos_fallidos,
        email_verificado,
        acceso_aprobado,
        aprobado_por
    FROM administradores 
    ORDER BY creado_en DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $administradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener administradores: " . $e->getMessage());
}

// Procesar acciones - Solo SuperAdmin puede ejecutar estas acciones
if ($_POST && isset($_POST['accion'])) {
    // Verificación adicional de seguridad
    if ($_SESSION['admin_rol'] !== 'superadmin') {
        header('Location: administradores.php?error=permisos_insuficientes');
        exit;
    }
    
    $admin_actual_id = $_SESSION['admin_id'];
    
    switch ($_POST['accion']) {
        case 'cambiar_estado':
            $admin_id = intval($_POST['admin_id']);
            $accion_estado = $_POST['accion_estado']; // 'activar', 'bloquear', 'aprobar'
            
            // No permitir modificar al SuperAdmin principal
            if ($admin_id <= 2) { // IDs 1 y 2 son protegidos
                header('Location: administradores.php?error=admin_protegido');
                exit;
            }
            
            // No permitir auto-modificación
            if ($admin_id == $admin_actual_id) {
                header('Location: administradores.php?error=auto_modificacion');
                exit;
            }
            
            try {
                switch ($accion_estado) {
                    case 'activar':
                        $stmt = $pdo->prepare("UPDATE administradores SET bloqueado = 0, intentos_fallidos = 0 WHERE id_admin = ?");
                        $mensaje = 'administrador_activado';
                        break;
                    case 'bloquear':
                        $stmt = $pdo->prepare("UPDATE administradores SET bloqueado = 1 WHERE id_admin = ?");
                        $mensaje = 'administrador_bloqueado';
                        break;
                    case 'aprobar':
                        $stmt = $pdo->prepare("UPDATE administradores SET acceso_aprobado = 1, aprobado_por = ?, fecha_aprobacion = NOW() WHERE id_admin = ?");
                        $stmt->execute([$admin_actual_id, $admin_id]);
                        $mensaje = 'administrador_aprobado';
                        break;
                    case 'revocar_aprobacion':
                        $stmt = $pdo->prepare("UPDATE administradores SET acceso_aprobado = 0, aprobado_por = NULL, fecha_aprobacion = NULL WHERE id_admin = ?");
                        $stmt->execute([$admin_id]);
                        $mensaje = 'aprobacion_revocada';
                        break;
                    default:
                        throw new Exception("Acción no válida");
                }
                
                if ($accion_estado !== 'aprobar' && $accion_estado !== 'revocar_aprobacion') {
                    $stmt->execute([$admin_id]);
                }
                
                // Log de auditoría
                $log_stmt = $pdo->prepare("INSERT INTO logs_auditoria (admin_id, accion, tabla_afectada, registro_id, detalles, ip_address) VALUES (?, ?, 'administradores', ?, ?, ?)");
                $log_stmt->execute([$admin_actual_id, "cambio_estado_{$accion_estado}", $admin_id, "Estado cambiado por SuperAdmin", $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                
                header("Location: administradores.php?success={$mensaje}");
                exit;
            } catch (Exception $e) {
                error_log("Error al cambiar estado: " . $e->getMessage());
                header('Location: administradores.php?error=cambio_estado_fallido');
                exit;
            }
            
        case 'cambiar_rol':
            $admin_id = intval($_POST['admin_id']);
            $nuevo_rol = $_POST['nuevo_rol'];
            
            // Validar rol
            $roles_permitidos = ['admin', 'operaciones', 'ventas', 'soporte', 'superadmin'];
            if (!in_array($nuevo_rol, $roles_permitidos)) {
                header('Location: administradores.php?error=rol_invalido');
                exit;
            }
            
            // No permitir modificar SuperAdmins principales
            if ($admin_id <= 2) {
                header('Location: administradores.php?error=admin_protegido');
                exit;
            }
            
            // No permitir auto-modificación de rol
            if ($admin_id == $admin_actual_id) {
                header('Location: administradores.php?error=auto_modificacion_rol');
                exit;
            }
            
            // Verificar que no se esté intentando crear más SuperAdmins sin autorización especial
            if ($nuevo_rol === 'superadmin') {
                // Requerir confirmación especial para crear SuperAdmin
                if (!isset($_POST['confirmar_superadmin']) || $_POST['confirmar_superadmin'] !== 'SI_CONFIRMO') {
                    header('Location: administradores.php?error=confirmacion_superadmin_requerida');
                    exit;
                }
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE administradores SET rol = ? WHERE id_admin = ?");
                $stmt->execute([$nuevo_rol, $admin_id]);
                
                // Log de auditoría crítico para cambios de rol
                $log_stmt = $pdo->prepare("INSERT INTO logs_auditoria (admin_id, accion, tabla_afectada, registro_id, detalles, ip_address) VALUES (?, ?, 'administradores', ?, ?, ?)");
                $log_stmt->execute([$admin_actual_id, 'cambio_rol_critico', $admin_id, "Rol cambiado a: {$nuevo_rol}", $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                
                header('Location: administradores.php?success=rol_actualizado');
                exit;
            } catch (PDOException $e) {
                error_log("Error al cambiar rol: " . $e->getMessage());
                header('Location: administradores.php?error=cambio_rol_fallido');
                exit;
            }
            
        case 'eliminar_admin':
            $admin_id = intval($_POST['admin_id']);
            
            // No permitir eliminar SuperAdmins principales
            if ($admin_id <= 2) {
                header('Location: administradores.php?error=admin_protegido');
                exit;
            }
            
            // No permitir auto-eliminación
            if ($admin_id == $admin_actual_id) {
                header('Location: administradores.php?error=auto_eliminacion');
                exit;
            }
            
            // Requerir confirmación especial
            if (!isset($_POST['confirmar_eliminacion']) || $_POST['confirmar_eliminacion'] !== 'SI_ELIMINAR') {
                header('Location: administradores.php?error=confirmacion_eliminacion_requerida');
                exit;
            }
            
            try {
                // Soft delete - marcar como eliminado en lugar de borrar
                $stmt = $pdo->prepare("UPDATE administradores SET bloqueado = 1, email = CONCAT(email, '_ELIMINADO_', UNIX_TIMESTAMP()), actualizado_en = NOW() WHERE id_admin = ?");
                $stmt->execute([$admin_id]);
                
                // Log de auditoría crítico
                $log_stmt = $pdo->prepare("INSERT INTO logs_auditoria (admin_id, accion, tabla_afectada, registro_id, detalles, ip_address) VALUES (?, ?, 'administradores', ?, ?, ?)");
                $log_stmt->execute([$admin_actual_id, 'eliminacion_admin_critico', $admin_id, "Administrador marcado como eliminado", $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                
                header('Location: administradores.php?success=admin_eliminado');
                exit;
            } catch (PDOException $e) {
                error_log("Error al eliminar admin: " . $e->getMessage());
                header('Location: administradores.php?error=eliminacion_fallida');
                exit;
            }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../../../../imagenes/antares_logozz2.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen bg-gray-100">
            <div class="p-6 lg:p-10">
                <!-- Encabezado -->
                <div class="mb-8">
                    <br><br><br>
                    <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-xl shadow-lg border border-red-700 p-6 lg:p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-3xl lg:text-4xl font-light text-white mb-2 flex items-center">
                                    <i class="fas fa-user-shield mr-4"></i>Administradores del Sistema
                                </h1>
                                <p class="text-red-100 font-medium">Panel de SuperAdmin • Gestión de Usuarios Administrativos</p>
                            </div>
                            <div class="hidden lg:block">
                                <span class="px-4 py-2 bg-red-500 bg-opacity-30 backdrop-blur-sm rounded-full text-red-100 text-sm font-medium">
                                    <i class="fas fa-crown mr-2"></i>SuperAdmin Only
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de estado -->
                <?php if (isset($_GET['success'])): ?>
                <div class="mb-6">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-600 mt-0.5 mr-3"></i>
                            <div class="text-green-800">
                                <?php
                                switch ($_GET['success']) {
                                    case 'administrador_activado':
                                        echo 'Administrador activado correctamente.';
                                        break;
                                    case 'administrador_bloqueado':
                                        echo 'Administrador bloqueado correctamente.';
                                        break;
                                    case 'administrador_aprobado':
                                        echo 'Acceso del administrador aprobado correctamente.';
                                        break;
                                    case 'aprobacion_revocada':
                                        echo 'Aprobación del administrador revocada correctamente.';
                                        break;
                                    case 'rol_actualizado':
                                        echo 'Rol del administrador actualizado correctamente.';
                                        break;
                                    case 'admin_eliminado':
                                        echo 'Administrador eliminado correctamente.';
                                        break;
                                    default:
                                        echo 'Operación completada correctamente.';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="mb-6">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-600 mt-0.5 mr-3"></i>
                            <div class="text-red-800">
                                <?php
                                switch ($_GET['error']) {
                                    case 'permisos_insuficientes':
                                        echo 'No tiene permisos suficientes para realizar esta acción.';
                                        break;
                                    case 'admin_protegido':
                                        echo 'No se puede modificar este administrador protegido.';
                                        break;
                                    case 'auto_modificacion':
                                        echo 'No puede modificar su propio estado.';
                                        break;
                                    case 'auto_modificacion_rol':
                                        echo 'No puede modificar su propio rol.';
                                        break;
                                    case 'auto_eliminacion':
                                        echo 'No puede eliminar su propia cuenta.';
                                        break;
                                    case 'rol_invalido':
                                        echo 'El rol especificado no es válido.';
                                        break;
                                    case 'confirmacion_superadmin_requerida':
                                        echo 'Se requiere confirmación especial para crear un SuperAdmin.';
                                        break;
                                    case 'confirmacion_eliminacion_requerida':
                                        echo 'Se requiere confirmación especial para eliminar un administrador.';
                                        break;
                                    case 'cambio_estado_fallido':
                                        echo 'Error al cambiar el estado del administrador.';
                                        break;
                                    case 'cambio_rol_fallido':
                                        echo 'Error al cambiar el rol del administrador.';
                                        break;
                                    case 'eliminacion_fallida':
                                        echo 'Error al eliminar el administrador.';
                                        break;
                                    default:
                                        echo 'Ha ocurrido un error en la operación.';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg mr-4">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Total Admins</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo count($administradores); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg mr-4">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Activos</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo count(array_filter($administradores, fn($a) => $a['estado'] === 'activo')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg mr-4">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Pendientes</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo count(array_filter($administradores, fn($a) => $a['estado'] === 'pendiente')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-lg mr-4">
                                <i class="fas fa-ban text-red-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Bloqueados</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo count(array_filter($administradores, fn($a) => $a['estado'] === 'bloqueado')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg mr-4">
                                <i class="fas fa-crown text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">SuperAdmins</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo count(array_filter($administradores, fn($a) => $a['rol'] === 'superadmin')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de administradores -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-list text-gray-600 mr-3"></i>Lista de Administradores
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fechas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($administradores as $administrador): ?>
                                <tr class="hover:bg-gray-50 <?php echo $administrador['id'] <= 2 ? 'bg-blue-50 border-blue-200' : ''; ?></tr>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-r <?php 
                                                    echo $administrador['rol'] === 'superadmin' ? 'from-red-500 to-red-600' : 
                                                         ($administrador['id'] <= 2 ? 'from-blue-500 to-blue-600' : 'from-gray-500 to-gray-600'); 
                                                ?> flex items-center justify-center">
                                                    <i class="fas <?php 
                                                        echo $administrador['rol'] === 'superadmin' ? 'fa-crown' : 
                                                             ($administrador['id'] <= 2 ? 'fa-shield-alt' : 'fa-user-cog'); 
                                                    ?> text-white text-sm"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 flex items-center">
                                                    <?php echo htmlspecialchars($administrador['nombre']); ?>
                                                    <?php if ($administrador['id'] <= 2): ?>
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <i class="fas fa-shield-alt mr-1"></i>Protegido
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!$administrador['email_verificado']): ?>
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                            <i class="fas fa-envelope mr-1"></i>Sin Verificar
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">ID: #<?php echo $administrador['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($administrador['email']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            <?php if ($administrador['intentos_fallidos'] > 0): ?>
                                                <span class="text-red-600">⚠️ <?php echo $administrador['intentos_fallidos']; ?> intentos fallidos</span>
                                            <?php else: ?>
                                                <span class="text-green-600">✓ Sin intentos fallidos</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($administrador['id'] <= 2): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                <i class="fas <?php echo $administrador['rol'] === 'superadmin' ? 'fa-crown' : 'fa-shield-alt'; ?> mr-1"></i>
                                                <?php echo ucfirst($administrador['rol']); ?>
                                            </span>
                                        <?php else: ?>
                                            <form method="POST" class="inline" id="form-rol-<?php echo $administrador['id']; ?>">
                                                <input type="hidden" name="accion" value="cambiar_rol">
                                                <input type="hidden" name="admin_id" value="<?php echo $administrador['id']; ?>">
                                                <select name="nuevo_rol" onchange="confirmarCambioRol(<?php echo $administrador['id']; ?>, this)" 
                                                        class="text-sm border-gray-300 rounded-md <?php 
                                                            echo $administrador['rol'] === 'superadmin' ? 'bg-red-50 text-red-800 border-red-300' :
                                                                 ($administrador['rol'] === 'admin' ? 'bg-blue-50 text-blue-800 border-blue-300' :
                                                                  'bg-gray-50 text-gray-800 border-gray-300'); 
                                                        ?>">
                                                    <option value="soporte" <?php echo $administrador['rol'] === 'soporte' ? 'selected' : ''; ?>>Soporte</option>
                                                    <option value="ventas" <?php echo $administrador['rol'] === 'ventas' ? 'selected' : ''; ?>>Ventas</option>
                                                    <option value="operaciones" <?php echo $administrador['rol'] === 'operaciones' ? 'selected' : ''; ?>>Operaciones</option>
                                                    <option value="admin" <?php echo $administrador['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    <option value="superadmin" <?php echo $administrador['rol'] === 'superadmin' ? 'selected' : ''; ?>>⚠️ SuperAdmin</option>
                                                </select>
                                                <input type="hidden" name="confirmar_superadmin" id="confirmar-sa-<?php echo $administrador['id']; ?>">
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            <?php
                                            $estado_color = [
                                                'activo' => 'bg-green-100 text-green-800',
                                                'pendiente' => 'bg-yellow-100 text-yellow-800', 
                                                'bloqueado' => 'bg-red-100 text-red-800'
                                            ][$administrador['estado']] ?? 'bg-gray-100 text-gray-800';
                                            
                                            $estado_icon = [
                                                'activo' => 'fa-check-circle',
                                                'pendiente' => 'fa-clock',
                                                'bloqueado' => 'fa-ban'
                                            ][$administrador['estado']] ?? 'fa-question-circle';
                                            ?>
                                            
                                            <?php if ($administrador['id'] <= 2): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle mr-1"></i>Protegido
                                                </span>
                                            <?php else: ?>
                                                <!-- Botón de estado actual -->
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $estado_color; ?>">
                                                    <i class="fas <?php echo $estado_icon; ?> mr-1"></i><?php echo ucfirst($administrador['estado']); ?>
                                                </span>
                                                
                                                <!-- Botones de acción rápida -->
                                                <div class="flex space-x-1 mt-1">
                                                    <?php if ($administrador['estado'] === 'pendiente'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="accion" value="cambiar_estado">
                                                            <input type="hidden" name="admin_id" value="<?php echo $administrador['id']; ?>">
                                                            <input type="hidden" name="accion_estado" value="aprobar">
                                                            <button type="submit" title="Aprobar acceso"
                                                                    class="text-xs bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($administrador['estado'] === 'bloqueado'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="accion" value="cambiar_estado">
                                                            <input type="hidden" name="admin_id" value="<?php echo $administrador['id']; ?>">
                                                            <input type="hidden" name="accion_estado" value="activar">
                                                            <button type="submit" title="Activar"
                                                                    class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">
                                                                <i class="fas fa-unlock"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($administrador['estado'] === 'activo'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="accion" value="cambiar_estado">
                                                            <input type="hidden" name="admin_id" value="<?php echo $administrador['id']; ?>">
                                                            <input type="hidden" name="accion_estado" value="bloquear">
                                                            <button type="submit" title="Bloquear" 
                                                                    onclick="return confirm('¿Bloquear este administrador?')"
                                                                    class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="space-y-1">
                                            <div>
                                                <span class="text-xs text-gray-400">Creado:</span><br>
                                                <?php echo date('d/m/Y H:i', strtotime($administrador['fecha_creacion'])); ?>
                                            </div>
                                            <div>
                                                <span class="text-xs text-gray-400">Último acceso:</span><br>
                                                <?php echo $administrador['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($administrador['ultimo_acceso'])) : 'Nunca'; ?>
                                            </div>
                                            <?php if ($administrador['aprobado_por']): ?>
                                            <div>
                                                <span class="text-xs text-gray-400">Aprobado por:</span><br>
                                                ID #<?php echo $administrador['aprobado_por']; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-col space-y-2">
                                            <?php if ($administrador['id'] <= 2): ?>
                                                <span class="text-gray-400 text-xs">
                                                    <i class="fas fa-shield-alt mr-1"></i>Protegido
                                                </span>
                                            <?php else: ?>
                                                <!-- Botón Ver Detalles -->
                                                <button onclick="verDetalles(<?php echo $administrador['id']; ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 transition-colors text-xs">
                                                    <i class="fas fa-eye mr-1"></i>Ver
                                                </button>
                                                
                                                <!-- Botón Eliminar con confirmación especial -->
                                                <button onclick="confirmarEliminacion(<?php echo $administrador['id']; ?>, '<?php echo htmlspecialchars($administrador['nombre'], ENT_QUOTES); ?>')" 
                                                        class="text-red-600 hover:text-red-900 transition-colors text-xs">
                                                    <i class="fas fa-trash mr-1"></i>Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function verDetalles(adminId) {
            // Modal básico para mostrar detalles
            alert('Ver detalles del administrador ID: ' + adminId + '\n(Función pendiente de implementar)');
        }
        
        function confirmarCambioRol(adminId, selectElement) {
            const nuevoRol = selectElement.value;
            const form = document.getElementById('form-rol-' + adminId);
            
            if (nuevoRol === 'superadmin') {
                // Requerir confirmación especial para SuperAdmin
                const confirmacion = prompt(
                    '⚠️ ADVERTENCIA CRÍTICA ⚠️\n\n' +
                    'Está a punto de otorgar permisos de SuperAdmin.\n' +
                    'Esta acción otorgará acceso TOTAL al sistema.\n\n' +
                    'Para confirmar, escriba exactamente: SI_CONFIRMO'
                );
                
                if (confirmacion === 'SI_CONFIRMO') {
                    document.getElementById('confirmar-sa-' + adminId).value = 'SI_CONFIRMO';
                    form.submit();
                } else {
                    // Revertir selección
                    selectElement.value = selectElement.dataset.original || 'admin';
                    alert('Operación cancelada. No se modificó el rol.');
                }
            } else {
                // Confirmación normal para otros roles
                if (confirm('¿Confirma el cambio de rol a: ' + nuevoRol + '?')) {
                    form.submit();
                } else {
                    // Revertir selección
                    selectElement.value = selectElement.dataset.original || 'admin';
                }
            }
        }
        
        function confirmarEliminacion(adminId, nombreAdmin) {
            const confirmaciones = [
                confirm(
                    '🚨 ELIMINACIÓN DE ADMINISTRADOR 🚨\n\n' +
                    'Administrador: ' + nombreAdmin + ' (ID: ' + adminId + ')\n\n' +
                    '⚠️ Esta es una acción IRREVERSIBLE ⚠️\n' +
                    'El administrador será bloqueado permanentemente.\n\n' +
                    '¿Está COMPLETAMENTE seguro?'
                ),
                confirm(
                    '🔴 CONFIRMACIÓN FINAL 🔴\n\n' +
                    'Esta es su ÚLTIMA oportunidad para cancelar.\n\n' +
                    'El administrador ' + nombreAdmin + ' será eliminado del sistema.\n' +
                    'Esta acción quedará registrada en los logs de auditoría.\n\n' +
                    '¿Proceder con la eliminación?'
                )
            ];
            
            if (confirmaciones.every(c => c)) {
                const confirmacionTexto = prompt(
                    'Para confirmar la eliminación, escriba exactamente:\n' +
                    'SI_ELIMINAR'
                );
                
                if (confirmacionTexto === 'SI_ELIMINAR') {
                    // Crear y enviar formulario
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const inputs = {
                        'accion': 'eliminar_admin',
                        'admin_id': adminId,
                        'confirmar_eliminacion': 'SI_ELIMINAR'
                    };
                    
                    Object.keys(inputs).forEach(key => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = inputs[key];
                        form.appendChild(input);
                    });
                    
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    alert('Operación cancelada. Texto de confirmación incorrecto.');
                }
            } else {
                alert('Operación cancelada por el usuario.');
            }
        }
        
        // Guardar valores originales de los selects para poder revertir
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select[name="nuevo_rol"]').forEach(select => {
                select.dataset.original = select.value;
            });
        });
        
        // Log de acceso de SuperAdmin
        console.log('Panel de Administradores accedido por SuperAdmin');
        console.log('Usuario:', '<?php echo htmlspecialchars($admin['nombre']); ?>');
        console.log('Timestamp:', '<?php echo date('Y-m-d H:i:s'); ?>');
        console.log('IP:', '<?php echo $_SERVER['REMOTE_ADDR'] ?? 'unknown'; ?>');
        
        // Advertencias adicionales en consola
        console.warn('🚨 ACCESO A PANEL CRÍTICO - Gestión de Administradores');
        console.warn('⚠️ Todas las acciones quedan registradas en auditoría');
    </script>
</body>
</html>
