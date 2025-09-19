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
        $foto_url = '';
        
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
            
            // Verificar si la licencia ya existe
            $check_licencia_sql = "SELECT id_chofer FROM choferes WHERE licencia = ? AND licencia != ''";
            $check_stmt = $connection->prepare($check_licencia_sql);
            $check_stmt->execute([$licencia]);
            if ($check_stmt->fetch()) {
                throw new Exception('Ya existe un chofer con esa licencia');
            }
        }
        
        // Manejo de foto si se subió
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
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
            
            // Generar nombre único
            $extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $filename = 'chofer_' . time() . '_' . uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $filename;
            
            // Mover archivo
            if (!move_uploaded_file($foto['tmp_name'], $upload_path)) {
                throw new Exception('Error al subir la imagen');
            }
            
            // Guardar ruta completa como en guías (consistencia)
            $foto_url = 'storage/uploads/choferes/' . $filename;
        }
        
        // Preparar la consulta SQL según si hay campo foto_url o no
        $columns = ['nombre', 'apellido', 'telefono', 'licencia'];
        $placeholders = ['?', '?', '?', '?'];
        $values = [
            $nombre,
            $apellido ?: null,
            $telefono ?: null,
            $licencia ?: null
        ];
        
        // Verificar si existe el campo foto_url en la tabla
        try {
            $check_column_sql = "SHOW COLUMNS FROM choferes LIKE 'foto_url'";
            $check_column_stmt = $connection->prepare($check_column_sql);
            $check_column_stmt->execute();
            $column_exists = $check_column_stmt->fetch();
            
            if ($column_exists && !empty($foto_url)) {
                $columns[] = 'foto_url';
                $placeholders[] = '?';
                $values[] = $foto_url;
            }
        } catch (Exception $e) {
            // Si hay error verificando la columna, continuamos sin foto
            if (!empty($foto_url)) {
                // Eliminar archivo subido si no podemos guardarlo en BD
                @unlink($upload_path);
                $foto_url = '';
            }
        }
        
        // Insertar nuevo chofer
        $insert_sql = "INSERT INTO choferes (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $insert_stmt = $connection->prepare($insert_sql);
        $insert_stmt->execute($values);
        
        $id_chofer = $connection->lastInsertId();
        $success = "Chofer creado exitosamente";
        
        // Redireccionar al ver el chofer creado
        header("Location: ver.php?id=$id_chofer&success=" . urlencode($success));
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        // Eliminar archivo subido si hay error
        if (isset($upload_path) && file_exists($upload_path)) {
            @unlink($upload_path);
        }
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
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-user-plus text-blue-600 mr-2"></i>Información del Chofer
                        </h2>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                        <!-- Foto del chofer -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">
                                <i class="fas fa-camera text-gray-600 mr-2"></i>Foto del Chofer
                            </h3>
                            <div class="flex items-start space-x-6">
                                <div class="flex-shrink-0">
                                    <div id="preview-container" class="relative">
                                        <div id="photo-preview" class="h-24 w-24 rounded-full bg-blue-600 flex items-center justify-center overflow-hidden border-4 border-white shadow-lg">
                                            <span id="preview-initials" class="text-white font-bold text-xl">?</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4">
                                        <label for="foto" class="cursor-pointer bg-white px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                            <i class="fas fa-upload mr-2"></i>Subir Foto
                                        </label>
                                        <button type="button" id="remove-photo" class="hidden px-4 py-2 text-red-600 border border-red-200 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors">
                                            <i class="fas fa-trash mr-2"></i>Quitar
                                        </button>
                                    </div>
                                    <input type="file" id="foto" name="foto" accept="image/*" class="hidden">
                                    <p class="text-xs text-gray-500 mt-2">
                                        JPG, JPEG o PNG. Máximo 5MB. Opcional.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
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
                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
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
                                       value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>"
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
                                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
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
                                       value="<?php echo htmlspecialchars($_POST['licencia'] ?? ''); ?>"
                                       maxlength="50"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
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
        // Variables globales
        let currentPhotoFile = null;
        
        // Actualizar vista previa en tiempo real
        function updatePreview() {
            const nombre = document.getElementById('nombre').value || '';
            const apellido = document.getElementById('apellido').value || '';
            const telefono = document.getElementById('telefono').value || '';
            const licencia = document.getElementById('licencia').value || '';

            // Actualizar nombre completo
            const nombreCompleto = (nombre + ' ' + apellido).trim() || 'Nombre del chofer';
            document.getElementById('preview-name').textContent = nombreCompleto;

            // Actualizar iniciales si no hay foto
            if (!currentPhotoFile) {
                const iniciales = (nombre.charAt(0) + (apellido.charAt(0) || '')).toUpperCase() || '?';
                document.getElementById('preview-initials').textContent = iniciales;
            }

            // Actualizar información adicional
            const infoArray = [];
            if (telefono) infoArray.push(`📞 ${telefono}`);
            if (licencia) infoArray.push(`🪪 ${licencia}`);
            
            const infoText = infoArray.length > 0 ? infoArray.join(' • ') : 'Información adicional';
            document.getElementById('preview-info').textContent = infoText;
        }
        
        // Manejo de contadores de caracteres
        function updateCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
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
            const previewInitials = document.getElementById('preview-initials');
            const removeButton = document.getElementById('remove-photo');
            
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
                        removeButton.classList.remove('hidden');
                        currentPhotoFile = file;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            removeButton.addEventListener('click', function() {
                photoInput.value = '';
                currentPhotoFile = null;
                const iniciales = getInitials();
                photoPreview.innerHTML = `<span id="preview-initials" class="text-white font-bold text-xl">${iniciales}</span>`;
                removeButton.classList.add('hidden');
                updatePreview();
            });
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
                field.addEventListener('input', updatePreview);
                
                // Actualizar iniciales cuando no hay foto
                if (fieldId === 'nombre' || fieldId === 'apellido') {
                    field.addEventListener('input', function() {
                        if (!currentPhotoFile) {
                            document.getElementById('preview-initials').textContent = getInitials();
                        }
                    });
                }
            });
            
            // Contadores
            document.getElementById('nombre').addEventListener('input', function() {
                updateCounter('nombre', 'nombre-counter', 100);
            });
            
            document.getElementById('apellido').addEventListener('input', function() {
                updateCounter('apellido', 'apellido-counter', 100);
            });
            
            // Formatear teléfono
            document.getElementById('telefono').addEventListener('input', function(e) {
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
        
        // Validación del formulario
        function initializeFormValidation() {
            document.querySelector('form').addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                
                if (!nombre) {
                    e.preventDefault();
                    alert('El nombre es obligatorio');
                    document.getElementById('nombre').focus();
                    return;
                }
                
                // Validar foto si se seleccionó
                const photoInput = document.getElementById('foto');
                if (photoInput.files[0]) {
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
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creando...';
                submitButton.disabled = true;
            });
        }
        
        // Inicialización cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            initializePhotoUpload();
            initializeEventListeners();
            initializeFormValidation();
            
            // Actualizar vista previa inicial
            updatePreview();
            updateCounter('nombre', 'nombre-counter', 100);
            updateCounter('apellido', 'apellido-counter', 100);
            
            // Enfocar primer campo
            document.getElementById('nombre').focus();
        });
    </script>
</body>
</html>
