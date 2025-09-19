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
    
    // Verificar si existe el campo foto_url
    $has_foto_column = false;
    try {
        $check_column_sql = "SHOW COLUMNS FROM choferes LIKE 'foto_url'";
        $check_column_stmt = $connection->prepare($check_column_sql);
        $check_column_stmt->execute();
        $has_foto_column = ($check_column_stmt->fetch() !== false);
    } catch (Exception $e) {
        $has_foto_column = false;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar datos requeridos
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $licencia = trim($_POST['licencia'] ?? '');
        
        // Validaciones básicas
        if (empty($nombre)) {
            throw new Exception('El nombre es obligatorio');
        }
        
        if (strlen($nombre) > 100) {
            throw new Exception('El nombre no puede exceder 100 caracteres');
        }
        
        if (!empty($apellido) && strlen($apellido) > 100) {
            throw new Exception('El apellido no puede exceder 100 caracteres');
        }
        
        if (!empty($telefono) && strlen($telefono) > 20) {
            throw new Exception('El teléfono no puede exceder 20 caracteres');
        }
        
        if (!empty($licencia)) {
            if (strlen($licencia) > 50) {
                throw new Exception('La licencia no puede exceder 50 caracteres');
            }
            
            // Verificar si la licencia ya existe en otro chofer
            $check_licencia_sql = "SELECT id_chofer FROM choferes WHERE licencia = ? AND id_chofer != ? AND licencia != ''";
            $check_stmt = $connection->prepare($check_licencia_sql);
            $check_stmt->execute([$licencia, $id_chofer]);
            if ($check_stmt->fetch()) {
                throw new Exception('Ya existe otro chofer con esa licencia');
            }
        }
        
        $foto_updated = false;
        $old_photo = null;
        
        // Manejo de eliminación de foto
        if (isset($_POST['eliminar_foto']) && $_POST['eliminar_foto'] === '1' && $has_foto_column) {
            $old_photo = $chofer['foto_url'] ?? null;
            if ($old_photo) {
                // Construir ruta completa - manejar tanto rutas nuevas como legacy
                $photo_path = strpos($old_photo, 'storage/uploads/choferes/') === 0 
                    ? "../../../../" . $old_photo 
                    : "../../../../storage/uploads/choferes/" . $old_photo;
                    
                if (file_exists($photo_path)) {
                    @unlink($photo_path);
                }
            }
            $chofer['foto_url'] = null;
            $foto_updated = true;
        }
        
        // Manejo de nueva foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $has_foto_column) {
            $foto = $_FILES['foto'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Validar tipo de archivo
            if (!in_array($foto['type'], $allowed_types)) {
                throw new Exception('Solo se permiten archivos JPG, JPEG y PNG');
            }
            
            // Validar tamaño
            if ($foto['size'] > $max_size) {
                throw new Exception('La imagen no puede superar 5MB');
            }
            
            // Crear directorio si no existe
            $upload_dir = '../../../../storage/uploads/choferes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Eliminar foto anterior
            $old_photo = $chofer['foto_url'] ?? null;
            if ($old_photo) {
                // Construir ruta completa - manejar tanto rutas nuevas como legacy
                $photo_path = strpos($old_photo, 'storage/uploads/choferes/') === 0 
                    ? "../../../../" . $old_photo 
                    : "../../../../storage/uploads/choferes/" . $old_photo;
                    
                if (file_exists($photo_path)) {
                    @unlink($photo_path);
                }
            }
            
            // Generar nombre único
            $extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $filename = 'chofer_' . time() . '_' . uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $filename;
            
            // Mover archivo
            if (!move_uploaded_file($foto['tmp_name'], $upload_path)) {
                throw new Exception('Error al subir la imagen');
            }
            
            // Guardar ruta completa como en guías (consistencia)
            $chofer['foto_url'] = 'storage/uploads/choferes/' . $filename;
            $foto_updated = true;
        }
        
        // Preparar la consulta SQL
        $update_fields = ['nombre = ?', 'apellido = ?', 'telefono = ?', 'licencia = ?'];
        $update_values = [
            $nombre,
            $apellido ?: null,
            $telefono ?: null,
            $licencia ?: null
        ];
        
        // Agregar foto si el campo existe y se actualizó
        if ($has_foto_column && $foto_updated) {
            $update_fields[] = 'foto_url = ?';
            $update_values[] = $chofer['foto_url'];
        }
        
        $update_values[] = $id_chofer;
        
        // Actualizar chofer
        $update_sql = "UPDATE choferes SET " . implode(', ', $update_fields) . " WHERE id_chofer = ?";
        $update_stmt = $connection->prepare($update_sql);
        $update_stmt->execute($update_values);
        
        $success = "Chofer actualizado exitosamente";
        
        // Actualizar datos locales
        $chofer['nombre'] = $nombre;
        $chofer['apellido'] = $apellido;
        $chofer['telefono'] = $telefono;
        $chofer['licencia'] = $licencia;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    
    // Eliminar archivo subido si hay error
    if (isset($upload_path) && file_exists($upload_path)) {
        @unlink($upload_path);
    }
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
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../../../../imagenes/antares_logozz2.png">
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
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-user-edit text-green-600 mr-2"></i>Información del Chofer
                        </h2>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                        <!-- Foto del chofer -->
                        <?php if ($has_foto_column): ?>
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">
                                <i class="fas fa-camera text-gray-600 mr-2"></i>Foto del Chofer
                            </h3>
                            <div class="flex items-start space-x-6">
                                <div class="flex-shrink-0">
                                    <div id="preview-container" class="relative">
                                        <div id="photo-preview" class="h-24 w-24 rounded-full bg-blue-600 flex items-center justify-center overflow-hidden border-4 border-white shadow-lg">
                                            <?php 
                                            $foto_url = $chofer['foto_url'] ?? '';
                                            $mostrar_foto = false;
                                            $foto_src = '';
                                            
                                            if (!empty($foto_url)) {
                                                // Manejar rutas tanto nuevas (completas) como legacy (solo nombre)
                                                $foto_path = strpos($foto_url, 'storage/uploads/choferes/') === 0 
                                                    ? "../../../../" . $foto_url 
                                                    : "../../../../storage/uploads/choferes/" . $foto_url;
                                                $foto_src = strpos($foto_url, 'storage/uploads/choferes/') === 0 
                                                    ? "../../../../" . $foto_url 
                                                    : "../../../../storage/uploads/choferes/" . $foto_url;
                                                
                                                $mostrar_foto = file_exists($foto_path);
                                            }
                                            
                                            if ($mostrar_foto): ?>
                                                <img src="<?php echo htmlspecialchars($foto_src); ?>" 
                                                     alt="Foto del chofer" 
                                                     class="h-full w-full object-cover rounded-full">
                                            <?php else: ?>
                                                <span id="preview-initials" class="text-white font-bold text-xl">
                                                    <?php echo strtoupper(substr($chofer['nombre'], 0, 1) . substr($chofer['apellido'] ?? '', 0, 1)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4">
                                        <label for="foto" class="cursor-pointer bg-white px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                            <i class="fas fa-upload mr-2"></i><?php echo ($mostrar_foto) ? 'Cambiar Foto' : 'Subir Foto'; ?>
                                        </label>
                                        <?php if ($mostrar_foto): ?>
                                        <button type="button" id="remove-photo" class="px-4 py-2 text-red-600 border border-red-200 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors">
                                            <i class="fas fa-trash mr-2"></i>Eliminar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" id="foto" name="foto" accept="image/*" class="hidden">
                                    <input type="hidden" id="eliminar_foto" name="eliminar_foto" value="0">
                                    <p class="text-xs text-gray-500 mt-2">
                                        JPG, JPEG o PNG. Máximo 5MB. 
                                        <?php echo ($foto_url) ? 'Foto actual: ' . htmlspecialchars($foto_url) : 'Sin foto actual.'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Información básica -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nombre -->
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user text-gray-400 mr-1"></i>
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       id="nombre" 
                                       value="<?php echo htmlspecialchars($chofer['nombre']); ?>"
                                       required 
                                       maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Ingresa el nombre del chofer">
                                <div class="text-xs text-gray-500 mt-1" id="nombre-counter">0/100 caracteres</div>
                            </div>

                            <!-- Apellido -->
                            <div>
                                <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user text-gray-400 mr-1"></i>
                                    Apellido
                                </label>
                                <input type="text" 
                                       name="apellido" 
                                       id="apellido" 
                                       value="<?php echo htmlspecialchars($chofer['apellido'] ?? ''); ?>"
                                       maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Ingresa el apellido del chofer">
                                <div class="text-xs text-gray-500 mt-1" id="apellido-counter">0/100 caracteres</div>
                            </div>
                        </div>

                        <!-- Información de contacto -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Teléfono -->
                            <div>
                                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone text-gray-400 mr-1"></i>
                                    Teléfono
                                </label>
                                <input type="tel" 
                                       name="telefono" 
                                       id="telefono" 
                                       value="<?php echo htmlspecialchars($chofer['telefono'] ?? ''); ?>"
                                       maxlength="20"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Ej: +51 987 654 321">
                                <p class="text-xs text-gray-500 mt-1">Número de contacto del chofer</p>
                            </div>

                            <!-- Licencia de conducir -->
                            <div>
                                <label for="licencia" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-id-badge text-gray-400 mr-1"></i>
                                    Licencia de Conducir
                                </label>
                                <input type="text" 
                                       name="licencia" 
                                       id="licencia" 
                                       value="<?php echo htmlspecialchars($chofer['licencia'] ?? ''); ?>"
                                       maxlength="50"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Ej: Q12345678">
                                <p class="text-xs text-gray-500 mt-1">Número de licencia de conducir (opcional)</p>
                            </div>
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
        // Variables globales
        let currentPhotoFile = null;
        let hasCurrentPhoto = <?php echo ($has_foto_column && !empty($chofer['foto_url'])) ? 'true' : 'false'; ?>;
        
        // Actualizar vista previa en tiempo real
        function updatePreview() {
            const nombre = document.getElementById('nombre').value || '';
            const apellido = document.getElementById('apellido').value || '';
            const telefono = document.getElementById('telefono').value || '';
            const licencia = document.getElementById('licencia').value || '';

            // Actualizar nombre completo
            const nombreCompleto = (nombre + ' ' + apellido).trim() || 'Nombre del chofer';
            const previewNameElement = document.getElementById('preview-name');
            if (previewNameElement) {
                previewNameElement.textContent = nombreCompleto;
            }

            // Actualizar iniciales si no hay foto
            if (!currentPhotoFile && !hasCurrentPhoto) {
                const iniciales = (nombre.charAt(0) + (apellido.charAt(0) || '')).toUpperCase() || '?';
                const previewInitialsElement = document.getElementById('preview-initials');
                if (previewInitialsElement) {
                    previewInitialsElement.textContent = iniciales;
                }
            }

            // Actualizar información adicional
            const infoArray = [];
            if (telefono) infoArray.push(`📞 ${telefono}`);
            if (licencia) infoArray.push(`🪪 ${licencia}`);
            
            const infoText = infoArray.length > 0 ? infoArray.join(' • ') : 'Información adicional';
            const previewInfoElement = document.getElementById('preview-info');
            if (previewInfoElement) {
                previewInfoElement.textContent = infoText;
            }
        }
        
        // Manejo de contadores de caracteres
        function updateCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            if (!input || !counter) return;
            
            const currentLength = input.value.length;
            
            counter.textContent = `${currentLength}/${maxLength} caracteres`;
            
            if (currentLength > maxLength * 0.8) {
                counter.classList.add('text-orange-600');
                counter.classList.remove('text-gray-500');
            } else {
                counter.classList.add('text-gray-500');
                counter.classList.remove('text-orange-600');
            }
            
            if (currentLength === maxLength) {
                counter.classList.add('text-red-600');
                counter.classList.remove('text-orange-600', 'text-gray-500');
            }
        }
        
        // Manejo de subida de fotos
        function initializePhotoUpload() {
            const photoInput = document.getElementById('foto');
            const photoPreview = document.getElementById('photo-preview');
            const removeButton = document.getElementById('remove-photo');
            const eliminarFotoInput = document.getElementById('eliminar_foto');
            
            if (!photoInput || !photoPreview) return;
            
            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validar tipo de archivo
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Solo se permiten archivos JPG, JPEG y PNG');
                        e.target.value = '';
                        return;
                    }
                    
                    // Validar tamaño (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('La imagen no puede superar 5MB');
                        e.target.value = '';
                        return;
                    }
                    
                    // Mostrar preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        photoPreview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="h-full w-full object-cover rounded-full">`;
                        if (removeButton) {
                            removeButton.classList.remove('hidden');
                            removeButton.innerHTML = '<i class="fas fa-trash mr-2"></i>Quitar Nueva';
                        }
                        currentPhotoFile = file;
                        hasCurrentPhoto = true;
                        if (eliminarFotoInput) eliminarFotoInput.value = '0';
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    if (currentPhotoFile) {
                        // Quitar nueva foto subida
                        photoInput.value = '';
                        currentPhotoFile = null;
                        
                        // Restaurar foto original si existe
                        <?php if ($has_foto_column && !empty($chofer['foto_url'])): ?>
                        photoPreview.innerHTML = '<img src="../../../../storage/uploads/choferes/<?php echo htmlspecialchars($chofer['foto_url']); ?>" alt="Foto del chofer" class="h-full w-full object-cover rounded-full">';
                        removeButton.innerHTML = '<i class="fas fa-trash mr-2"></i>Eliminar';
                        hasCurrentPhoto = true;
                        <?php else: ?>
                        const iniciales = getInitials();
                        photoPreview.innerHTML = `<span id="preview-initials" class="text-white font-bold text-xl">${iniciales}</span>`;
                        removeButton.classList.add('hidden');
                        hasCurrentPhoto = false;
                        <?php endif; ?>
                        if (eliminarFotoInput) eliminarFotoInput.value = '0';
                    } else {
                        // Eliminar foto original
                        if (confirm('¿Estás seguro de que quieres eliminar la foto actual?')) {
                            const iniciales = getInitials();
                            photoPreview.innerHTML = `<span id="preview-initials" class="text-white font-bold text-xl">${iniciales}</span>`;
                            removeButton.classList.add('hidden');
                            hasCurrentPhoto = false;
                            if (eliminarFotoInput) eliminarFotoInput.value = '1';
                        }
                    }
                    updatePreview();
                });
            }
        }
        
        // Obtener iniciales
        function getInitials() {
            const nombre = document.getElementById('nombre').value || '';
            const apellido = document.getElementById('apellido').value || '';
            return (nombre.charAt(0) + (apellido.charAt(0) || '')).toUpperCase() || '?';
        }
        
        // Event listeners para campos de texto
        function initializeEventListeners() {
            // Actualización de preview
            ['nombre', 'apellido', 'telefono', 'licencia'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', updatePreview);
                    
                    // Actualizar iniciales cuando no hay foto
                    if (fieldId === 'nombre' || fieldId === 'apellido') {
                        field.addEventListener('input', function() {
                            if (!currentPhotoFile && !hasCurrentPhoto) {
                                const previewInitialsElement = document.getElementById('preview-initials');
                                if (previewInitialsElement) {
                                    previewInitialsElement.textContent = getInitials();
                                }
                            }
                        });
                    }
                }
            });
            
            // Contadores
            const nombreInput = document.getElementById('nombre');
            const apellidoInput = document.getElementById('apellido');
            
            if (nombreInput) {
                nombreInput.addEventListener('input', function() {
                    updateCounter('nombre', 'nombre-counter', 100);
                });
            }
            
            if (apellidoInput) {
                apellidoInput.addEventListener('input', function() {
                    updateCounter('apellido', 'apellido-counter', 100);
                });
            }
            
            // Formatear teléfono
            const telefonoInput = document.getElementById('telefono');
            if (telefonoInput) {
                telefonoInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0 && !value.startsWith('51')) {
                        if (value.length <= 9) {
                            value = '51' + value;
                        }
                    }
                    if (value.length > 0) {
                        value = '+' + value;
                    }
                    e.target.value = value.substring(0, 20);
                });
            }
        }
        
        // Validación del formulario
        function initializeFormValidation() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const nombre = document.getElementById('nombre').value.trim();
                    
                    if (!nombre) {
                        e.preventDefault();
                        alert('El nombre es obligatorio');
                        document.getElementById('nombre').focus();
                        return;
                    }
                    
                    // Validar foto si se seleccionó una nueva
                    const photoInput = document.getElementById('foto');
                    if (photoInput && photoInput.files[0]) {
                        const file = photoInput.files[0];
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                        if (!allowedTypes.includes(file.type)) {
                            e.preventDefault();
                            alert('Solo se permiten archivos JPG, JPEG y PNG');
                            return;
                        }
                        
                        if (file.size > 5 * 1024 * 1024) {
                            e.preventDefault();
                            alert('La imagen no puede superar 5MB');
                            return;
                        }
                    }
                    
                    // Mostrar indicador de carga
                    const submitButton = this.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Actualizando...';
                        submitButton.disabled = true;
                    }
                });
            }
        }
        
        // Inicialización cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            initializePhotoUpload();
            initializeEventListeners();
            initializeFormValidation();
            
            // Actualizar contadores iniciales
            updateCounter('nombre', 'nombre-counter', 100);
            updateCounter('apellido', 'apellido-counter', 100);
            
            // Enfocar primer campo
            const nombreInput = document.getElementById('nombre');
            if (nombreInput) {
                nombreInput.focus();
            }
        });
    </script>
</body>
</html>
