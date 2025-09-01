<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $connection = getConnection();
        
        // Validar datos requeridos
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $licencia = trim($_POST['licencia'] ?? '');
        
        if (empty($nombre)) {
            throw new Exception('El nombre es obligatorio');
        }
        
        // Verificar si la licencia ya existe (si se proporciona)
        if (!empty($licencia)) {
            $check_licencia_sql = "SELECT id_chofer FROM choferes WHERE licencia = ? AND licencia != ''";
            $check_stmt = $connection->prepare($check_licencia_sql);
            $check_stmt->execute([$licencia]);
            if ($check_stmt->fetch()) {
                throw new Exception('Ya existe un chofer con esa licencia');
            }
        }
        
        // Insertar nuevo chofer
        $insert_sql = "INSERT INTO choferes (nombre, apellido, telefono, licencia) VALUES (?, ?, ?, ?)";
        $insert_stmt = $connection->prepare($insert_sql);
        $insert_stmt->execute([
            $nombre,
            $apellido ?: null,
            $telefono ?: null,
            $licencia ?: null
        ]);
        
        $id_chofer = $connection->lastInsertId();
        $success = "Chofer creado exitosamente";
        
        // Redireccionar al ver el chofer creado
        header("Location: ver.php?id=$id_chofer&success=" . urlencode($success));
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Crear Nuevo Chofer";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <div class="p-4 lg:p-8">
                <!-- Navegación -->
                <div class="mb-6">
                    <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="index.php" class="text-gray-600 hover:text-blue-600 inline-flex items-center">
                                    <i class="fas fa-id-card mr-2"></i>
                                    Choferes
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                    <span class="text-gray-500">Crear Chofer</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                <!-- Encabezado -->
                <div class="mb-8">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                        <i class="fas fa-plus text-blue-600 mr-3"></i>Crear Nuevo Chofer
                    </h1>
                    <p class="text-gray-600 mt-2">Completa la información básica del nuevo chofer</p>
                </div>

                <!-- Mostrar errores -->
                <?php if ($error): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <p class="text-sm text-red-700 mt-1"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Información del Chofer</h2>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6">
                        <!-- Información básica -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nombre -->
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       id="nombre" 
                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                       required 
                                       maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ingresa el nombre del chofer">
                            </div>

                            <!-- Apellido -->
                            <div>
                                <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2">
                                    Apellido
                                </label>
                                <input type="text" 
                                       name="apellido" 
                                       id="apellido" 
                                       value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>"
                                       maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ingresa el apellido del chofer">
                            </div>
                        </div>

                        <!-- Información de contacto -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Teléfono -->
                            <div>
                                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                                    Teléfono
                                </label>
                                <input type="tel" 
                                       name="telefono" 
                                       id="telefono" 
                                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                                       maxlength="20"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: +51 987 654 321">
                                <p class="text-xs text-gray-500 mt-1">Número de contacto del chofer</p>
                            </div>

                            <!-- Licencia de conducir -->
                            <div>
                                <label for="licencia" class="block text-sm font-medium text-gray-700 mb-2">
                                    Licencia de Conducir
                                </label>
                                <input type="text" 
                                       name="licencia" 
                                       id="licencia" 
                                       value="<?php echo htmlspecialchars($_POST['licencia'] ?? ''); ?>"
                                       maxlength="50"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: Q12345678">
                                <p class="text-xs text-gray-500 mt-1">Número de licencia de conducir (opcional)</p>
                            </div>
                        </div>

                        <!-- Vista previa -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Vista Previa</h3>
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center">
                                    <span class="text-white font-medium" id="preview-initials">?</span>
                                </div>
                                <div class="ml-4">
                                    <div class="font-medium text-gray-900" id="preview-name">Nombre del chofer</div>
                                    <div class="text-sm text-gray-500" id="preview-info">Información adicional</div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos requeridos -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <i class="fas fa-info-circle text-blue-400 mr-3 mt-1"></i>
                                <div>
                                    <h3 class="text-sm font-medium text-blue-800">Información importante</h3>
                                    <ul class="text-sm text-blue-700 mt-1 list-disc list-inside space-y-1">
                                        <li>Los campos marcados con * son obligatorios</li>
                                        <li>La licencia de conducir debe ser única si se proporciona</li>
                                        <li>Puedes asignar vehículos después de crear el chofer</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex flex-col sm:flex-row gap-4 sm:justify-end">
                            <a href="index.php" 
                               class="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-center">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </a>
                            <button type="submit" 
                                    class="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Crear Chofer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Actualizar vista previa en tiempo real
        function updatePreview() {
            const nombre = document.getElementById('nombre').value || '';
            const apellido = document.getElementById('apellido').value || '';
            const telefono = document.getElementById('telefono').value || '';
            const licencia = document.getElementById('licencia').value || '';

            // Actualizar nombre completo
            const nombreCompleto = (nombre + ' ' + apellido).trim() || 'Nombre del chofer';
            document.getElementById('preview-name').textContent = nombreCompleto;

            // Actualizar iniciales
            const iniciales = (nombre.charAt(0) + (apellido.charAt(0) || '')).toUpperCase() || '?';
            document.getElementById('preview-initials').textContent = iniciales;

            // Actualizar información adicional
            const infoArray = [];
            if (telefono) infoArray.push(`📞 ${telefono}`);
            if (licencia) infoArray.push(`🪪 ${licencia}`);
            
            const infoText = infoArray.length > 0 ? infoArray.join(' • ') : 'Información adicional';
            document.getElementById('preview-info').textContent = infoText;
        }

        // Agregar event listeners
        document.getElementById('nombre').addEventListener('input', updatePreview);
        document.getElementById('apellido').addEventListener('input', updatePreview);
        document.getElementById('telefono').addEventListener('input', updatePreview);
        document.getElementById('licencia').addEventListener('input', updatePreview);

        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            
            if (!nombre) {
                e.preventDefault();
                alert('El nombre es obligatorio');
                document.getElementById('nombre').focus();
                return;
            }
        });

        // Formatear teléfono automáticamente
        document.getElementById('telefono').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.startsWith('51')) {
                    value = '+' + value;
                } else if (!value.startsWith('+')) {
                    value = '+51' + value;
                }
            }
            e.target.value = value;
        });

        // Actualizar vista previa inicial
        updatePreview();
    </script>
</body>
</html>
