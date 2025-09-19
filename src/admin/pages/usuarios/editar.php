<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del usuario
$id_usuario = intval($_GET['id'] ?? 0);

if (!$id_usuario) {
    header('Location: index.php');
    exit;
}

$success_message = '';
$error_message = '';

try {
    $connection = getConnection();
    
    // Obtener datos actuales del usuario
    $usuario_sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
    $usuario_stmt = $connection->prepare($usuario_sql);
    $usuario_stmt->execute([$id_usuario]);
    $usuario = $usuario_stmt->fetch();
    
    if (!$usuario) {
        header('Location: index.php?error=Usuario no encontrado');
        exit;
    }
    
    // Procesar la URL del avatar para corregir las rutas
    if (!empty($usuario['avatar_url'])) {
        // Si es una URL completa (HTTP/HTTPS), dejarla como está
        if (preg_match('/^https?:\/\//i', $usuario['avatar_url'])) {
            // URL externa (Google, Facebook, etc.) - no hacer nada
        }
        // Si es una ruta local que no comienza con /, añadir /
        elseif (!preg_match('/^\//', $usuario['avatar_url'])) {
            $usuario['avatar_url'] = '/' . $usuario['avatar_url'];
        }
    }
    
    $page_title = "Editar Usuario: " . ($usuario['nombre'] ?? $usuario['email']);
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}

function subirAvatar($archivo, $id_usuario) {
    $directorio_destino = __DIR__ . '/../../../../storage/uploads/avatars/';
    $max_size = 5 * 1024 * 1024; // 5MB
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Verificar si se subió el archivo
    if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }
    
    // Verificar tamaño
    if ($archivo['size'] > $max_size) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB.');
    }
    
    // Verificar tipo MIME
    $tipo_mime = mime_content_type($archivo['tmp_name']);
    if (!in_array($tipo_mime, $tipos_permitidos)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF, WEBP).');
    }
    
    // Crear directorio si no existe
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }
    
    // Generar nombre único
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'avatar_' . uniqid() . '.' . strtolower($extension);
    $ruta_completa = $directorio_destino . $nombre_archivo;
    
    // Mover archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Retornar ruta relativa para la base de datos
    return 'storage/uploads/avatars/' . $nombre_archivo;
}

function eliminarAvatarAnterior($avatar_url) {
    if (!empty($avatar_url) && !preg_match('/^https?:\/\//i', $avatar_url)) {
        $ruta_archivo = __DIR__ . '/../../../../' . ltrim($avatar_url, '/');
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email_verificado = isset($_POST['email_verificado']) ? 1 : 0;
        $nueva_password = $_POST['nueva_password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        $accion = $_POST['accion'] ?? 'actualizar';
        
        // Manejar subida de avatar si se proporciona
        $nueva_avatar_url = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $nueva_avatar_url = subirAvatar($_FILES['avatar'], $id_usuario);
        }
        
        // Manejar diferentes acciones
        if ($accion === 'toggle_verification') {
            // Solo cambiar estado de verificación
            $nuevo_estado = $usuario['email_verificado'] ? 0 : 1;
            $toggle_sql = "UPDATE usuarios SET email_verificado = ? WHERE id_usuario = ?";
            $toggle_stmt = $connection->prepare($toggle_sql);
            $toggle_stmt->execute([$nuevo_estado, $id_usuario]);
            
            $mensaje = $nuevo_estado ? 'Usuario verificado exitosamente' : 'Verificación removida del usuario';
            $success_message = $mensaje;
            
            // Actualizar datos en memoria
            $usuario['email_verificado'] = $nuevo_estado;
            
        } elseif ($accion === 'reset_password') {
            // Solo resetear contraseña
            if (empty($nueva_password)) {
                throw new Exception("Debe proporcionar una nueva contraseña");
            }
            
            if (strlen($nueva_password) < 6) {
                throw new Exception("La nueva contraseña debe tener al menos 6 caracteres");
            }
            
            if ($nueva_password !== $confirmar_password) {
                throw new Exception("Las contraseñas no coinciden");
            }
            
            $password_hash = password_hash($nueva_password, PASSWORD_BCRYPT);
            $password_sql = "UPDATE usuarios SET password_hash = ?, actualizado_en = NOW() WHERE id_usuario = ?";
            $password_stmt = $connection->prepare($password_sql);
            $password_stmt->execute([$password_hash, $id_usuario]);
            
            $success_message = "Contraseña actualizada exitosamente";
            
        } elseif ($accion === 'actualizar') {
            // Actualización completa de perfil
            
            // Validaciones básicas
            if (empty($nombre)) {
                throw new Exception("El nombre es obligatorio");
            }
            
            if (empty($email)) {
                throw new Exception("El email es obligatorio");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El formato del email no es válido");
            }
            
            // Validar longitudes
            if (strlen($nombre) > 100) {
                throw new Exception("El nombre no puede tener más de 100 caracteres");
            }
            
            if (strlen($email) > 100) {
                throw new Exception("El email no puede tener más de 100 caracteres");
            }
            
            // Validar teléfono si se proporciona
            if (!empty($telefono)) {
                if (strlen($telefono) > 15) {
                    throw new Exception("El teléfono no puede tener más de 15 caracteres");
                }
                
                if (!preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $telefono)) {
                    throw new Exception("El formato del teléfono no es válido");
                }
            }
            
            // Verificar que el email no exista en otro usuario
            if ($email !== $usuario['email']) {
                $check_sql = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?";
                $check_stmt = $connection->prepare($check_sql);
                $check_stmt->execute([$email, $id_usuario]);
                
                if ($check_stmt->fetch()) {
                    throw new Exception("Ya existe otro usuario con este email");
                }
            }
            
            // Preparar datos para actualizar
            $datos_actualizacion = [
                $nombre,
                $email,
                $telefono ?: null,
                $email_verificado,
                $id_usuario
            ];
            
            $update_sql = "UPDATE usuarios SET 
                          nombre = ?, 
                          email = ?, 
                          telefono = ?, 
                          email_verificado = ?,
                          actualizado_en = NOW()
                          WHERE id_usuario = ?";
            
            // Si se subió un nuevo avatar
            if ($nueva_avatar_url) {
                // Eliminar avatar anterior si existe y es local
                eliminarAvatarAnterior($usuario['avatar_url']);
                
                // Agregar avatar_url a la consulta
                $update_sql = "UPDATE usuarios SET 
                              nombre = ?, 
                              email = ?, 
                              telefono = ?, 
                              email_verificado = ?,
                              avatar_url = ?,
                              actualizado_en = NOW()
                              WHERE id_usuario = ?";
                
                // Insertar avatar_url antes del id_usuario
                array_splice($datos_actualizacion, -1, 0, $nueva_avatar_url);
            }
            
            // Si también se cambió la contraseña
            if (!empty($nueva_password)) {
                if (strlen($nueva_password) < 6) {
                    throw new Exception("La nueva contraseña debe tener al menos 6 caracteres");
                }
                
                if ($nueva_password !== $confirmar_password) {
                    throw new Exception("Las contraseñas no coinciden");
                }
                
                $password_hash = password_hash($nueva_password, PASSWORD_BCRYPT);
                
                if ($nueva_avatar_url) {
                    // Con avatar y password
                    $update_sql = "UPDATE usuarios SET 
                                  nombre = ?, 
                                  email = ?, 
                                  telefono = ?, 
                                  email_verificado = ?,
                                  avatar_url = ?,
                                  password_hash = ?,
                                  actualizado_en = NOW()
                                  WHERE id_usuario = ?";
                    
                    // Insertar password_hash antes del id_usuario
                    array_splice($datos_actualizacion, -1, 0, $password_hash);
                } else {
                    // Solo con password
                    $update_sql = "UPDATE usuarios SET 
                                  nombre = ?, 
                                  email = ?, 
                                  telefono = ?, 
                                  email_verificado = ?,
                                  password_hash = ?,
                                  actualizado_en = NOW()
                                  WHERE id_usuario = ?";
                    
                    // Insertar password_hash antes del id_usuario
                    array_splice($datos_actualizacion, -1, 0, $password_hash);
                }
            }
            
            $update_stmt = $connection->prepare($update_sql);
            $update_stmt->execute($datos_actualizacion);
            
            // Actualizar datos en memoria para mostrar los cambios
            $usuario['nombre'] = $nombre;
            $usuario['email'] = $email;
            $usuario['telefono'] = $telefono;
            $usuario['email_verificado'] = $email_verificado;
            if ($nueva_avatar_url) {
                $usuario['avatar_url'] = $nueva_avatar_url;
            }
            
            $cambios = [];
            if (!empty($nueva_password)) $cambios[] = "contraseña";
            if ($email !== $usuario['email']) $cambios[] = "email";
            
            $success_message = "Perfil actualizado exitosamente";
            if (!empty($cambios)) {
                $success_message .= " (Cambios: " . implode(', ', $cambios) . ")";
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

function getProveedorClass($proveedor) {
    $classes = [
        'manual' => 'bg-blue-100 text-blue-800',
        'google' => 'bg-red-100 text-red-800',
        'apple' => 'bg-gray-100 text-gray-800',
        'microsoft' => 'bg-green-100 text-green-800'
    ];
    return $classes[$proveedor] ?? 'bg-gray-100 text-gray-800';
}

function getProveedorIcon($proveedor) {
    $icons = [
        'google' => 'fab fa-google',
        'apple' => 'fab fa-apple',
        'microsoft' => 'fab fa-microsoft',
        'manual' => 'fas fa-user'
    ];
    return $icons[$proveedor] ?? 'fas fa-user';
}

function timeAgo($date) {
    if (!$date) return 'N/A';
    
    $dateObj = new DateTime($date);
    $now = new DateTime();
    $diff = $now->diff($dateObj);
    
    if ($diff->days > 30) {
        return $dateObj->format('d/m/Y');
    } elseif ($diff->days > 0) {
        return $diff->days . ' días atrás';
    } elseif ($diff->h > 0) {
        return $diff->h . ' horas atrás';
    } else {
        return $diff->i . ' minutos atrás';
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
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="/imagenes/antares_logozz2.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-section {
            transition: all 0.3s ease;
        }
        .form-section:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-6 lg:mb-8">
                    <br><br><br>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <div class="flex items-center mb-2">
                                <a href="index.php" class="text-blue-600 hover:text-blue-800 mr-2">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                                    <i class="fas fa-user-edit text-green-600 mr-3"></i>Editar Usuario
                                </h1>
                            </div>
                            <p class="text-sm lg:text-base text-gray-600">Modifica los datos del usuario seleccionado</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="ver.php?id=<?php echo $usuario['id_usuario']; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-eye mr-2"></i>Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mostrar mensajes -->
                <?php if ($success_message): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-green-800">Éxito</h3>
                                <p class="text-sm text-green-700 mt-1"><?php echo htmlspecialchars($success_message); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <p class="text-sm text-red-700 mt-1"><?php echo htmlspecialchars($error_message); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Información del Usuario Actual -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Información Actual
                            </h2>
                            
                            <div class="text-center mb-6">
                                <?php if ($usuario['avatar_url']): ?>
                                    <?php 
                                    // Función para mostrar avatar con ruta correcta
                                    $avatar_display_url = $usuario['avatar_url'];
                                    if (!preg_match('/^https?:\/\//i', $avatar_display_url)) {
                                        $avatar_display_url = '../../../../' . ltrim($avatar_display_url, '/');
                                    }
                                    ?>
                                    <img class="h-20 w-20 rounded-full mx-auto mb-4" src="<?php echo htmlspecialchars($avatar_display_url); ?>" alt="">
                                <?php else: ?>
                                    <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center mx-auto mb-4">
                                        <span class="text-white font-bold text-xl">
                                            <?php echo strtoupper(substr($usuario['nombre'] ?? $usuario['email'], 0, 1)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($usuario['nombre'] ?? 'Sin nombre'); ?>
                                </h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($usuario['email']); ?></p>
                            </div>

                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">ID:</span>
                                    <span class="font-medium">#<?php echo $usuario['id_usuario']; ?></span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Tipo:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getProveedorClass($usuario['proveedor_oauth']); ?>">
                                        <i class="<?php echo getProveedorIcon($usuario['proveedor_oauth']); ?> mr-1"></i>
                                        <?php echo ucfirst($usuario['proveedor_oauth']); ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Estado:</span>
                                    <?php if ($usuario['email_verificado']): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Verificado
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>Pendiente
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Registrado:</span>
                                    <span class="font-medium" title="<?php echo formatDate($usuario['creado_en'], 'd/m/Y H:i:s'); ?>">
                                        <?php echo timeAgo($usuario['creado_en']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($usuario['actualizado_en'] && $usuario['actualizado_en'] !== $usuario['creado_en']): ?>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Actualizado:</span>
                                    <span class="font-medium" title="<?php echo formatDate($usuario['actualizado_en'], 'd/m/Y H:i:s'); ?>">
                                        <?php echo timeAgo($usuario['actualizado_en']); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($usuario['id_proveedor'] && $usuario['proveedor_oauth'] !== 'manual'): ?>
                                <div class="flex items-center justify-between border-t pt-2 mt-2">
                                    <span class="text-gray-500">ID Proveedor:</span>
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                        <?php echo htmlspecialchars(substr($usuario['id_proveedor'], 0, 20)) . (strlen($usuario['id_proveedor']) > 20 ? '...' : ''); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Alertas importantes -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                                <div>
                                    <h3 class="text-sm font-medium text-yellow-800">Importante</h3>
                                    <div class="text-sm text-yellow-700 mt-1 space-y-1">
                                        <p>• Los cambios se aplicarán inmediatamente</p>
                                        <p>• Si cambias el email, se requerirá nueva verificación</p>
                                        <p>• La contraseña solo se cambia si ingresas una nueva</p>
                                        <p>• El usuario será notificado de cambios importantes</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Edición -->
                    <div class="lg:col-span-2">
                        <form method="POST" id="editForm" enctype="multipart/form-data">
                            <div class="space-y-6">
                                <!-- Información Personal -->
                                <div class="form-section bg-white rounded-lg shadow-lg p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-user text-blue-600 mr-2"></i>Información Personal
                                    </h2>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                                                Nombre completo <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="nombre" name="nombre" required
                                                   value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="Ingrese el nombre completo">
                                        </div>

                                        <div class="md:col-span-2">
                                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                                Email <span class="text-red-500">*</span>
                                            </label>
                                            <input type="email" id="email" name="email" required
                                                   value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="usuario@ejemplo.com">
                                            <?php if ($usuario['email'] !== ($_POST['email'] ?? $usuario['email'])): ?>
                                                <p class="text-xs text-amber-600 mt-1">
                                                    <i class="fas fa-warning mr-1"></i>
                                                    Si cambias el email, el usuario deberá verificarlo nuevamente.
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                                                Teléfono
                                            </label>
                                            <input type="tel" id="telefono" name="telefono"
                                                   value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="+51 999 999 999">
                                        </div>

                                        <!-- Campo adicional para mostrar información del proveedor OAuth -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Tipo de Registro
                                            </label>
                                            <div class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg">
                                                <span class="inline-flex items-center">
                                                    <i class="<?php echo getProveedorIcon($usuario['proveedor_oauth']); ?> mr-2"></i>
                                                    <?php echo ucfirst($usuario['proveedor_oauth']); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Mostrar ID único si existe -->
                                        <?php if ($usuario['unique_id']): ?>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                ID Único
                                            </label>
                                            <div class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg font-mono text-sm">
                                                <?php echo htmlspecialchars($usuario['unique_id']); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Avatar -->
                                <div class="form-section bg-white rounded-lg shadow-lg p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-image text-purple-600 mr-2"></i>Avatar
                                    </h2>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Avatar Actual
                                            </label>
                                            <div class="flex items-center space-x-4">
                                                <?php if ($usuario['avatar_url']): ?>
                                                    <?php 
                                                    // Función para mostrar avatar con ruta correcta
                                                    $avatar_url = $usuario['avatar_url'];
                                                    if (!preg_match('/^https?:\/\//i', $avatar_url)) {
                                                        $avatar_url = '../../../../' . ltrim($avatar_url, '/');
                                                    }
                                                    ?>
                                                    <img id="currentAvatar" class="h-16 w-16 rounded-full object-cover border-2 border-gray-200" 
                                                         src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar actual">
                                                <?php else: ?>
                                                    <div id="currentAvatar" class="h-16 w-16 rounded-full bg-blue-600 flex items-center justify-center border-2 border-gray-200">
                                                        <span class="text-white font-bold text-xl">
                                                            <?php echo strtoupper(substr($usuario['nombre'] ?? $usuario['email'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <p class="text-sm text-gray-600">
                                                        <?php if ($usuario['avatar_url'] && preg_match('/^https?:\/\//i', $usuario['avatar_url'])): ?>
                                                            Avatar de OAuth (<?php echo ucfirst($usuario['proveedor_oauth']); ?>)
                                                        <?php elseif ($usuario['avatar_url']): ?>
                                                            Avatar personalizado subido
                                                        <?php else: ?>
                                                            Avatar por defecto (inicial del nombre)
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2">
                                                Subir Nuevo Avatar
                                            </label>
                                            <div class="flex items-center space-x-4">
                                                <input type="file" id="avatar" name="avatar" accept="image/*" 
                                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                                <button type="button" id="clearAvatar" class="text-sm text-red-600 hover:text-red-800">
                                                    <i class="fas fa-trash mr-1"></i>Limpiar
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Formatos permitidos: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB
                                            </p>
                                            
                                            <!-- Vista previa del nuevo avatar -->
                                            <div id="avatarPreview" class="hidden mt-3">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Vista Previa</label>
                                                <img id="previewImage" class="h-16 w-16 rounded-full object-cover border-2 border-blue-200" alt="Vista previa">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configuración de Cuenta -->
                                <div class="form-section bg-white rounded-lg shadow-lg p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-cog text-green-600 mr-2"></i>Configuración de Cuenta
                                    </h2>
                                    
                                    <div class="space-y-6">
                                        <!-- Gestión de Contraseña -->
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <h3 class="text-sm font-medium text-gray-900 mb-3">
                                                <i class="fas fa-key text-blue-600 mr-2"></i>Gestión de Contraseña
                                            </h3>
                                            
                                            <div class="space-y-3">
                                                <div>
                                                    <label for="nueva_password" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Nueva Contraseña
                                                    </label>
                                                    <div class="relative">
                                                        <input type="password" id="nueva_password" name="nueva_password"
                                                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                               placeholder="Mínimo 6 caracteres">
                                                        <button type="button" onclick="togglePassword('nueva_password')" 
                                                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                            <i class="fas fa-eye text-gray-400" id="nueva_password-icon"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <label for="confirmar_password" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Confirmar Nueva Contraseña
                                                    </label>
                                                    <div class="relative">
                                                        <input type="password" id="confirmar_password" name="confirmar_password"
                                                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                               placeholder="Repetir la contraseña">
                                                        <button type="button" onclick="togglePassword('confirmar_password')" 
                                                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                            <i class="fas fa-eye text-gray-400" id="confirmar_password-icon"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <p class="text-xs text-blue-600 bg-blue-50 p-2 rounded">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Deja estos campos vacíos si no quieres cambiar la contraseña actual
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Estado de Verificación -->
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <h3 class="text-sm font-medium text-gray-900 mb-3">
                                                <i class="fas fa-shield-alt text-green-600 mr-2"></i>Estado de Verificación
                                            </h3>
                                            
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <input type="checkbox" id="email_verificado" name="email_verificado" 
                                                           <?php echo $usuario['email_verificado'] ? 'checked' : ''; ?>
                                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                    <label for="email_verificado" class="ml-2 block text-sm text-gray-700">
                                                        Email verificado
                                                    </label>
                                                </div>
                                                
                                                <div>
                                                    <?php if ($usuario['email_verificado']): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <i class="fas fa-check-circle mr-1"></i>Verificado
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            <i class="fas fa-clock mr-1"></i>Pendiente
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Acciones rápidas de verificación -->
                                            <div class="mt-3 pt-3 border-t border-gray-200">
                                                <p class="text-xs text-gray-500 mb-2">Acciones rápidas:</p>
                                                <div class="flex gap-2">
                                                    <?php if ($usuario['email_verificado']): ?>
                                                        <button type="button" onclick="submitAction('toggle_verification')" 
                                                                class="text-xs px-3 py-1 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition-colors">
                                                            <i class="fas fa-times-circle mr-1"></i>Remover verificación
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" onclick="submitAction('toggle_verification')" 
                                                                class="text-xs px-3 py-1 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                                                            <i class="fas fa-check-circle mr-1"></i>Marcar como verificado
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Información adicional -->
                                        <?php if ($usuario['proveedor_oauth'] === 'manual'): ?>
                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-blue-900 mb-2">
                                                <i class="fas fa-info-circle mr-2"></i>Cuenta Manual
                                            </h4>
                                            <p class="text-xs text-blue-700">
                                                Esta cuenta fue creada manualmente, por lo que puedes cambiar todos los datos incluyendo la contraseña.
                                            </p>
                                        </div>
                                        <?php else: ?>
                                        <div class="bg-orange-50 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-orange-900 mb-2">
                                                <i class="fab fa-<?php echo $usuario['proveedor_oauth']; ?> mr-2"></i>Cuenta OAuth (<?php echo ucfirst($usuario['proveedor_oauth']); ?>)
                                            </h4>
                                            <p class="text-xs text-orange-700">
                                                Esta cuenta se creó mediante <?php echo ucfirst($usuario['proveedor_oauth']); ?>. 
                                                Los cambios de contraseña no afectarán el login con <?php echo ucfirst($usuario['proveedor_oauth']); ?>.
                                            </p>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($usuario['proveedor_oauth'] !== 'manual'): ?>
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                <div class="flex">
                                                    <i class="fas fa-info-circle text-blue-600 mr-3 mt-1"></i>
                                                    <div>
                                                        <h3 class="text-sm font-medium text-blue-800">Cuenta Social</h3>
                                                        <p class="text-sm text-blue-700 mt-1">
                                                            Este usuario se registró usando <?php echo ucfirst($usuario['proveedor_oauth']); ?>. 
                                                            Algunos datos pueden estar sincronizados automáticamente.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Botones de acción -->
                                <div class="bg-white rounded-lg shadow-lg p-6">
                                    <div class="flex flex-col space-y-4">
                                        <!-- Botones principales -->
                                        <div class="flex flex-col sm:flex-row gap-4">
                                            <button type="submit" name="accion" value="actualizar"
                                                    class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                                <i class="fas fa-save mr-2"></i>Actualizar Perfil Completo
                                            </button>
                                            
                                            <button type="button" onclick="resetearFormulario()"
                                                    class="inline-flex items-center justify-center px-6 py-3 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                                <i class="fas fa-undo mr-2"></i>Restablecer
                                            </button>
                                        </div>
                                        
                                        <!-- Botones de acciones específicas -->
                                        <div class="border-t pt-4">
                                            <p class="text-sm text-gray-600 mb-3">Acciones específicas:</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <button type="button" onclick="confirmarResetPassword()" 
                                                        class="inline-flex items-center justify-center px-4 py-2 bg-blue-100 text-blue-800 text-sm font-medium rounded-lg hover:bg-blue-200 transition-colors">
                                                    <i class="fas fa-key mr-2"></i>Solo Cambiar Contraseña
                                                </button>
                                                
                                                <button type="button" onclick="confirmarToggleVerification()" 
                                                        class="inline-flex items-center justify-center px-4 py-2 <?php echo $usuario['email_verificado'] ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?> text-sm font-medium rounded-lg transition-colors">
                                                    <?php if ($usuario['email_verificado']): ?>
                                                        <i class="fas fa-times-circle mr-2"></i>Desverificar Email
                                                    <?php else: ?>
                                                        <i class="fas fa-check-circle mr-2"></i>Verificar Email
                                                    <?php endif; ?>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Botón volver -->
                                        <div class="border-t pt-4">
                                            <a href="index.php" 
                                               class="inline-flex items-center justify-center w-full px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                                <i class="fas fa-arrow-left mr-2"></i>Volver a Lista de Usuarios
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Input oculto para acciones -->
                            <input type="hidden" name="accion" id="accion_form" value="actualizar">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function resetearFormulario() {
            if (confirm('¿Estás seguro de que deseas restablecer el formulario a los valores originales?')) {
                location.reload();
            }
        }
        
        // Función para enviar acciones específicas
        function submitAction(accion) {
            document.getElementById('accion_form').value = accion;
            document.getElementById('editForm').submit();
        }
        
        // Confirmar cambio de contraseña únicamente
        function confirmarResetPassword() {
            const nuevaPassword = document.getElementById('nueva_password').value;
            const confirmarPassword = document.getElementById('confirmar_password').value;
            
            if (!nuevaPassword || !confirmarPassword) {
                alert('Por favor, completa ambos campos de contraseña.');
                document.getElementById('nueva_password').focus();
                return;
            }
            
            if (nuevaPassword.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres.');
                document.getElementById('nueva_password').focus();
                return;
            }
            
            if (nuevaPassword !== confirmarPassword) {
                alert('Las contraseñas no coinciden.');
                document.getElementById('confirmar_password').focus();
                return;
            }
            
            if (confirm('¿Estás seguro de que deseas cambiar solo la contraseña de este usuario?')) {
                submitAction('reset_password');
            }
        }
        
        // Confirmar cambio de estado de verificación
        function confirmarToggleVerification() {
            const estadoActual = <?php echo $usuario['email_verificado'] ? 'true' : 'false'; ?>;
            const mensaje = estadoActual 
                ? '¿Estás seguro de que deseas REMOVER la verificación de este usuario?' 
                : '¿Estás seguro de que deseas VERIFICAR este usuario?';
            
            if (confirm(mensaje)) {
                submitAction('toggle_verification');
            }
        }

        // Validación de email en tiempo real
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const originalEmail = "<?php echo $usuario['email']; ?>";
            
            if (email && email !== originalEmail) {
                // Verificar formato
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    this.setCustomValidity('Formato de email inválido');
                    this.classList.add('border-red-500');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('border-red-500');
                    this.classList.add('border-amber-500');
                }
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-500', 'border-amber-500');
            }
        });

        // Validación de contraseña
        document.getElementById('nueva_password').addEventListener('input', function() {
            const password = this.value;
            
            if (password.length > 0 && password.length < 6) {
                this.setCustomValidity('La contraseña debe tener al menos 6 caracteres');
                this.classList.add('border-red-500');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-500');
                if (password.length >= 6) {
                    this.classList.add('border-green-500');
                } else {
                    this.classList.remove('border-green-500');
                }
            }
        });

        // Detectar cambios en el formulario
        let formChanged = false;
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', function() {
                formChanged = true;
            });
        });

        // Advertir antes de salir si hay cambios
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Marcar formulario como guardado al enviar
        document.querySelector('form').addEventListener('submit', function() {
            formChanged = false;
        });

        // Funciones para manejo de avatar
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('avatarPreview');
            const previewImage = document.getElementById('previewImage');
            
            if (file) {
                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Por favor selecciona un archivo de imagen válido (JPG, PNG, GIF, WEBP)');
                    this.value = '';
                    previewContainer.classList.add('hidden');
                    return;
                }
                
                // Validar tamaño (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('El archivo es demasiado grande. El tamaño máximo es 5MB.');
                    this.value = '';
                    previewContainer.classList.add('hidden');
                    return;
                }
                
                // Mostrar vista previa
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
                
                formChanged = true;
            } else {
                previewContainer.classList.add('hidden');
            }
        });

        // Limpiar selección de avatar
        document.getElementById('clearAvatar').addEventListener('click', function() {
            document.getElementById('avatar').value = '';
            document.getElementById('avatarPreview').classList.add('hidden');
        });
    </script>
</body>
</html>
