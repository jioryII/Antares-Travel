<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Crear Usuario";

$success_message = '';
$error_message = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $genero = $_POST['genero'] ?? null;
        $pais = trim($_POST['pais'] ?? '');
        $password = $_POST['password'] ?? '';
        $email_verificado = isset($_POST['email_verificado']) ? 1 : 0;
        $enviar_email = isset($_POST['enviar_email']) ? 1 : 0;
        
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
        
        if (empty($password)) {
            throw new Exception("La contraseña es obligatoria");
        }
        
        if (strlen($password) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres");
        }
        
        $connection = getConnection();
        
        // Verificar que el email no exista
        $check_sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
        $check_stmt = $connection->prepare($check_sql);
        $check_stmt->execute([$email]);
        
        if ($check_stmt->fetch()) {
            throw new Exception("Ya existe un usuario con este email");
        }
        
        // Preparar datos para insertar
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $token_verificacion = $email_verificado ? null : bin2hex(random_bytes(32));
        
        // Insertar usuario
        $insert_sql = "INSERT INTO usuarios (
                        nombre, email, telefono, fecha_nacimiento, genero, pais,
                        password_hash, email_verificado, token_verificacion,
                        proveedor_oauth, creado_en
                       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'manual', NOW())";
        
        $insert_stmt = $connection->prepare($insert_sql);
        $insert_stmt->execute([
            $nombre,
            $email,
            $telefono ?: null,
            $fecha_nacimiento ?: null,
            $genero ?: null,
            $pais ?: null,
            $password_hash,
            $email_verificado,
            $token_verificacion
        ]);
        
        $usuario_id = $connection->lastInsertId();
        
        // Enviar email de bienvenida si está marcada la opción
        if ($enviar_email) {
            $email_enviado = enviarEmailBienvenida($email, $nombre, $password, $token_verificacion);
            if (!$email_enviado) {
                $success_message = "Usuario creado exitosamente, pero no se pudo enviar el email de bienvenida.";
            } else {
                $success_message = "Usuario creado exitosamente. Se ha enviado un email de bienvenida.";
            }
        } else {
            $success_message = "Usuario creado exitosamente.";
        }
        
        // Registrar actividad del administrador
        registrarActividadAdmin($admin['id_admin'], 'crear_usuario', 
            "Creó el usuario: {$nombre} ({$email})", $usuario_id);
        
        // Limpiar formulario
        $_POST = [];
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Función para enviar email de bienvenida
function enviarEmailBienvenida($email, $nombre, $password, $token_verificacion) {
    // Esta función debe implementarse según el sistema de email configurado
    // Por ahora retornamos true como simulación
    return true;
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
    <link rel="icon" type="image/png" href="../../../../imagenes/antares_logozz2.png">
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
                                    <i class="fas fa-user-plus text-blue-600 mr-3"></i>Crear Nuevo Usuario
                                </h1>
                            </div>
                            <p class="text-sm lg:text-base text-gray-600">Registra un nuevo usuario en la plataforma</p>
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

                <!-- Formulario -->
                <form method="POST" class="max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Información Personal -->
                        <div class="form-section bg-white rounded-lg shadow-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-user text-blue-600 mr-2"></i>Información Personal
                            </h2>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nombre completo <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="nombre" name="nombre" required
                                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Ingrese el nombre completo">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" id="email" name="email" required
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="usuario@ejemplo.com">
                                </div>

                                <div>
                                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                                        Teléfono
                                    </label>
                                    <input type="tel" id="telefono" name="telefono"
                                           value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="+51 999 999 999">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">
                                            Fecha de Nacimiento
                                        </label>
                                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                                               value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label for="genero" class="block text-sm font-medium text-gray-700 mb-1">
                                            Género
                                        </label>
                                        <select id="genero" name="genero"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Seleccionar género</option>
                                            <option value="masculino" <?php echo ($_POST['genero'] ?? '') === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="femenino" <?php echo ($_POST['genero'] ?? '') === 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                                            <option value="otro" <?php echo ($_POST['genero'] ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                            <option value="no_especificar" <?php echo ($_POST['genero'] ?? '') === 'no_especificar' ? 'selected' : ''; ?>>Prefiero no especificar</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="pais" class="block text-sm font-medium text-gray-700 mb-1">
                                        País
                                    </label>
                                    <input type="text" id="pais" name="pais"
                                           value="<?php echo htmlspecialchars($_POST['pais'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Perú">
                                </div>
                            </div>
                        </div>

                        <!-- Información de Cuenta -->
                        <div class="form-section bg-white rounded-lg shadow-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-key text-green-600 mr-2"></i>Información de Cuenta
                            </h2>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                        Contraseña <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password" required
                                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Mínimo 6 caracteres">
                                        <button type="button" onclick="togglePassword('password')" 
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <i class="fas fa-eye text-gray-400" id="password-icon"></i>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">La contraseña debe tener al menos 6 caracteres</p>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-gray-900 mb-3">Configuración de Cuenta</h3>
                                    
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="email_verificado" name="email_verificado" 
                                                   <?php echo isset($_POST['email_verificado']) ? 'checked' : ''; ?>
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="email_verificado" class="ml-2 block text-sm text-gray-700">
                                                Marcar email como verificado
                                            </label>
                                        </div>

                                        <div class="flex items-center">
                                            <input type="checkbox" id="enviar_email" name="enviar_email" checked
                                                   <?php echo isset($_POST['enviar_email']) || !isset($_POST['submit']) ? 'checked' : ''; ?>
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="enviar_email" class="ml-2 block text-sm text-gray-700">
                                                Enviar email de bienvenida
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información adicional -->
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex">
                                        <i class="fas fa-info-circle text-blue-600 mr-3 mt-1"></i>
                                        <div>
                                            <h3 class="text-sm font-medium text-blue-800">Información importante</h3>
                                            <div class="text-sm text-blue-700 mt-1 space-y-1">
                                                <p>• El usuario será registrado con tipo de acceso "manual"</p>
                                                <p>• Si marca "email verificado", el usuario podrá acceder inmediatamente</p>
                                                <p>• Si envía email de bienvenida, incluirá las credenciales de acceso</p>
                                                <p>• El usuario podrá cambiar su contraseña desde su perfil</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                        <button type="submit" name="submit"
                                class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-save mr-2"></i>Crear Usuario
                        </button>
                        
                        <button type="button" onclick="limpiarFormulario()"
                                class="inline-flex items-center px-6 py-3 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-undo mr-2"></i>Limpiar
                        </button>
                        
                        <a href="index.php"
                           class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </a>
                    </div>
                </form>

                <!-- Consejos adicionales -->
                <div class="mt-8 max-w-4xl mx-auto">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-yellow-800 mb-3">
                            <i class="fas fa-lightbulb mr-2"></i>Consejos para crear usuarios
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-yellow-700">
                            <div>
                                <h4 class="font-medium mb-2">Datos obligatorios:</h4>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Nombre completo del usuario</li>
                                    <li>Email válido y único</li>
                                    <li>Contraseña segura (mín. 6 caracteres)</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-medium mb-2">Recomendaciones:</h4>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Verificar el email si es confiable</li>
                                    <li>Incluir teléfono para comunicación</li>
                                    <li>Enviar email de bienvenida por defecto</li>
                                </ul>
                            </div>
                        </div>
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

        function limpiarFormulario() {
            if (confirm('¿Estás seguro de que deseas limpiar el formulario?')) {
                document.querySelectorAll('input, select, textarea').forEach(element => {
                    if (element.type === 'checkbox') {
                        element.checked = element.id === 'enviar_email'; // Solo mantener marcado enviar_email
                    } else {
                        element.value = '';
                    }
                });
                document.getElementById('nombre').focus();
            }
        }

        // Validación de email en tiempo real
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            if (email) {
                // Verificar formato
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    this.setCustomValidity('Formato de email inválido');
                    this.classList.add('border-red-500');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('border-red-500');
                }
            }
        });

        // Validación de contraseña
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('password-strength');
            
            if (password.length < 6) {
                this.setCustomValidity('La contraseña debe tener al menos 6 caracteres');
                this.classList.add('border-red-500');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Auto-focus en el primer campo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nombre').focus();
        });
    </script>
</body>
</html>
