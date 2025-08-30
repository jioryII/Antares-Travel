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
    
    $page_title = "Editar Usuario: " . ($usuario['nombre'] ?? $usuario['email']);
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $genero = $_POST['genero'] ?? null;
        $pais = trim($_POST['pais'] ?? '');
        $email_verificado = isset($_POST['email_verificado']) ? 1 : 0;
        $nueva_password = $_POST['nueva_password'] ?? '';
        
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
            $fecha_nacimiento ?: null,
            $genero ?: null,
            $pais ?: null,
            $email_verificado,
            $id_usuario
        ];
        
        $update_sql = "UPDATE usuarios SET 
                      nombre = ?, 
                      email = ?, 
                      telefono = ?, 
                      fecha_nacimiento = ?, 
                      genero = ?, 
                      pais = ?, 
                      email_verificado = ?,
                      actualizado_en = NOW()
                      WHERE id_usuario = ?";
        
        // Si se proporcionó nueva contraseña, incluirla en la actualización
        if (!empty($nueva_password)) {
            if (strlen($nueva_password) < 6) {
                throw new Exception("La nueva contraseña debe tener al menos 6 caracteres");
            }
            
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE usuarios SET 
                          nombre = ?, 
                          email = ?, 
                          telefono = ?, 
                          fecha_nacimiento = ?, 
                          genero = ?, 
                          pais = ?, 
                          email_verificado = ?,
                          password_hash = ?,
                          actualizado_en = NOW()
                          WHERE id_usuario = ?";
            
            // Insertar password_hash antes del id_usuario
            array_splice($datos_actualizacion, -1, 0, $password_hash);
        }
        
        $update_stmt = $connection->prepare($update_sql);
        $update_stmt->execute($datos_actualizacion);
        
        // Actualizar datos en memoria para mostrar los cambios
        $usuario['nombre'] = $nombre;
        $usuario['email'] = $email;
        $usuario['telefono'] = $telefono;
        $usuario['fecha_nacimiento'] = $fecha_nacimiento;
        $usuario['genero'] = $genero;
        $usuario['pais'] = $pais;
        $usuario['email_verificado'] = $email_verificado;
        
        $success_message = "Usuario actualizado exitosamente.";
        
        // Registrar actividad del administrador
        $cambios = [];
        if (!empty($nueva_password)) $cambios[] = "contraseña";
        if ($email !== $usuario['email']) $cambios[] = "email";
        if ($email_verificado !== $usuario['email_verificado']) $cambios[] = "verificación";
        
        $descripcion = "Editó el usuario: {$nombre} ({$email})";
        if (!empty($cambios)) {
            $descripcion .= " - Cambios: " . implode(', ', $cambios);
        }
        
        registrarActividadAdmin($admin['id_admin'], 'editar_usuario', $descripcion, $id_usuario);
        
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
        'manual' => 'fas fa-user',
        'google' => 'fab fa-google',
        'apple' => 'fab fa-apple',
        'microsoft' => 'fab fa-microsoft'
    ];
    return $icons[$proveedor] ?? 'fas fa-user';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                                    <img class="h-20 w-20 rounded-full mx-auto mb-4" src="<?php echo htmlspecialchars($usuario['avatar_url']); ?>" alt="">
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
                                    <span class="font-medium"><?php echo formatDate($usuario['creado_en'], 'd/m/Y'); ?></span>
                                </div>
                                
                                <?php if ($usuario['actualizado_en']): ?>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Actualizado:</span>
                                    <span class="font-medium"><?php echo formatDate($usuario['actualizado_en'], 'd/m/Y'); ?></span>
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
                        <form method="POST">
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

                                        <div>
                                            <label for="pais" class="block text-sm font-medium text-gray-700 mb-1">
                                                País
                                            </label>
                                            <input type="text" id="pais" name="pais"
                                                   value="<?php echo htmlspecialchars($usuario['pais'] ?? ''); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="Perú">
                                        </div>

                                        <div>
                                            <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">
                                                Fecha de Nacimiento
                                            </label>
                                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                                                   value="<?php echo htmlspecialchars($usuario['fecha_nacimiento'] ?? ''); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>

                                        <div>
                                            <label for="genero" class="block text-sm font-medium text-gray-700 mb-1">
                                                Género
                                            </label>
                                            <select id="genero" name="genero"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">Seleccionar género</option>
                                                <option value="masculino" <?php echo ($usuario['genero'] ?? '') === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                                <option value="femenino" <?php echo ($usuario['genero'] ?? '') === 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                                                <option value="otro" <?php echo ($usuario['genero'] ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                                <option value="no_especificar" <?php echo ($usuario['genero'] ?? '') === 'no_especificar' ? 'selected' : ''; ?>>Prefiero no especificar</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configuración de Cuenta -->
                                <div class="form-section bg-white rounded-lg shadow-lg p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-cog text-green-600 mr-2"></i>Configuración de Cuenta
                                    </h2>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label for="nueva_password" class="block text-sm font-medium text-gray-700 mb-1">
                                                Nueva Contraseña (opcional)
                                            </label>
                                            <div class="relative">
                                                <input type="password" id="nueva_password" name="nueva_password"
                                                       class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                       placeholder="Dejar en blanco para mantener la actual">
                                                <button type="button" onclick="togglePassword('nueva_password')" 
                                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <i class="fas fa-eye text-gray-400" id="nueva_password-icon"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Solo se cambiará si ingresas una nueva contraseña (mínimo 6 caracteres)</p>
                                        </div>

                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <h3 class="text-sm font-medium text-gray-900 mb-3">Estado de la Cuenta</h3>
                                            
                                            <div class="flex items-center">
                                                <input type="checkbox" id="email_verificado" name="email_verificado" 
                                                       <?php echo $usuario['email_verificado'] ? 'checked' : ''; ?>
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <label for="email_verificado" class="ml-2 block text-sm text-gray-700">
                                                    Email verificado
                                                    <?php if (!$usuario['email_verificado']): ?>
                                                        <span class="text-amber-600">(actualmente pendiente)</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>

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
                                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                    <button type="submit"
                                            class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                                    </button>
                                    
                                    <button type="button" onclick="resetearFormulario()"
                                            class="inline-flex items-center px-6 py-3 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                        <i class="fas fa-undo mr-2"></i>Restablecer
                                    </button>
                                    
                                    <a href="index.php"
                                       class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                                        <i class="fas fa-times mr-2"></i>Cancelar
                                    </a>
                                </div>
                            </div>
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
    </script>
</body>
</html>
