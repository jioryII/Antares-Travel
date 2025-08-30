<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del chofer
$id_chofer = intval($_GET['id'] ?? 0);

if (!$id_chofer) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

try {
    $connection = getConnection();
    
    // Obtener información actual del chofer
    $chofer_sql = "SELECT * FROM choferes WHERE id_chofer = ?";
    $chofer_stmt = $connection->prepare($chofer_sql);
    $chofer_stmt->execute([$id_chofer]);
    $chofer = $chofer_stmt->fetch();
    
    if (!$chofer) {
        header('Location: index.php?error=Chofer no encontrado');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar datos requeridos
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $licencia = trim($_POST['licencia'] ?? '');
        
        if (empty($nombre)) {
            throw new Exception('El nombre es obligatorio');
        }
        
        // Verificar si la licencia ya existe en otro chofer (si se proporciona)
        if (!empty($licencia)) {
            $check_licencia_sql = "SELECT id_chofer FROM choferes WHERE licencia = ? AND id_chofer != ? AND licencia != ''";
            $check_stmt = $connection->prepare($check_licencia_sql);
            $check_stmt->execute([$licencia, $id_chofer]);
            if ($check_stmt->fetch()) {
                throw new Exception('Ya existe otro chofer con esa licencia');
            }
        }
        
        // Actualizar chofer
        $update_sql = "UPDATE choferes SET nombre = ?, apellido = ?, telefono = ?, licencia = ? WHERE id_chofer = ?";
        $update_stmt = $connection->prepare($update_sql);
        $update_stmt->execute([
            $nombre,
            $apellido ?: null,
            $telefono ?: null,
            $licencia ?: null,
            $id_chofer
        ]);
        
        $success = "Chofer actualizado exitosamente";
        
        // Actualizar datos locales
        $chofer['nombre'] = $nombre;
        $chofer['apellido'] = $apellido;
        $chofer['telefono'] = $telefono;
        $chofer['licencia'] = $licencia;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = "Editar Chofer: " . $chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '');
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
                                    <a href="ver.php?id=<?php echo $id_chofer; ?>" class="text-gray-600 hover:text-blue-600">
                                        <?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                    <span class="text-gray-500">Editar</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                <!-- Encabezado -->
                <div class="mb-8">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                        <i class="fas fa-edit text-green-600 mr-3"></i>Editar Chofer
                    </h1>
                    <p class="text-gray-600 mt-2">Modifica la información del chofer</p>
                </div>

                <!-- Mostrar mensajes -->
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

                <?php if ($success): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-green-800">Éxito</h3>
                                <p class="text-sm text-green-700 mt-1"><?php echo htmlspecialchars($success); ?></p>
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
                                       value="<?php echo htmlspecialchars($chofer['nombre']); ?>"
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
                                       value="<?php echo htmlspecialchars($chofer['apellido'] ?? ''); ?>"
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
                                       value="<?php echo htmlspecialchars($chofer['telefono'] ?? ''); ?>"
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
                                       value="<?php echo htmlspecialchars($chofer['licencia'] ?? ''); ?>"
                                       maxlength="50"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: Q12345678">
                                <p class="text-xs text-gray-500 mt-1">Número de licencia de conducir</p>
                            </div>
                        </div>

                        <!-- Vista previa -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Vista Previa</h3>
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center">
                                    <span class="text-white font-medium" id="preview-initials">
                                        <?php echo strtoupper(substr($chofer['nombre'], 0, 1) . substr($chofer['apellido'] ?? '', 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="font-medium text-gray-900" id="preview-name">
                                        <?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                    </div>
                                    <div class="text-sm text-gray-500" id="preview-info">
                                        <?php 
                                        $info_parts = [];
                                        if ($chofer['telefono']) $info_parts[] = '📞 ' . $chofer['telefono'];
                                        if ($chofer['licencia']) $info_parts[] = '🪪 ' . $chofer['licencia'];
                                        echo $info_parts ? implode(' • ', $info_parts) : 'Información adicional';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de ID -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <i class="fas fa-info-circle text-blue-400 mr-3 mt-1"></i>
                                <div>
                                    <h3 class="text-sm font-medium text-blue-800">Información del registro</h3>
                                    <ul class="text-sm text-blue-700 mt-1 space-y-1">
                                        <li>ID del chofer: #<?php echo $chofer['id_chofer']; ?></li>
                                        <li>Los campos marcados con * son obligatorios</li>
                                        <li>La licencia de conducir debe ser única</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex flex-col sm:flex-row gap-4 sm:justify-end">
                            <a href="ver.php?id=<?php echo $id_chofer; ?>" 
                               class="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-center">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </a>
                            <button type="submit" 
                                    class="w-full sm:w-auto px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Actualizar Chofer
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Enlaces adicionales -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Vehículos -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-car text-blue-600 mr-2"></i>Gestión de Vehículos
                        </h3>
                        <p class="text-gray-600 mb-4">Administra los vehículos asignados a este chofer</p>
                        <div class="flex gap-3">
                            <a href="../vehiculos/crear.php?chofer=<?php echo $id_chofer; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Asignar Vehículo
                            </a>
                            <a href="../vehiculos/index.php?chofer=<?php echo $id_chofer; ?>" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-list mr-2"></i>Ver Vehículos
                            </a>
                        </div>
                    </div>

                    <!-- Tours -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-route text-green-600 mr-2"></i>Tours y Actividad
                        </h3>
                        <p class="text-gray-600 mb-4">Revisa la actividad y tours programados</p>
                        <div class="flex gap-3">
                            <a href="ver.php?id=<?php echo $id_chofer; ?>#tours" 
                               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-calendar mr-2"></i>Ver Tours
                            </a>
                            <a href="../tours/index.php?chofer=<?php echo $id_chofer; ?>" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-search mr-2"></i>Buscar en Tours
                            </a>
                        </div>
                    </div>
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
    </script>
</body>
</html>
