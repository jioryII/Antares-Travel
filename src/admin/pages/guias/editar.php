<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del guía
$id_guia = intval($_GET['id'] ?? 0);

if (!$id_guia) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';

try {
    $connection = getConnection();
    
    // Obtener datos actuales del guía
    $guia_sql = "SELECT * FROM guias WHERE id_guia = ?";
    $guia_stmt = $connection->prepare($guia_sql);
    $guia_stmt->execute([$id_guia]);
    $guia = $guia_stmt->fetch();
    
    if (!$guia) {
        header('Location: index.php?error=Guía no encontrado');
        exit;
    }
    
    // Obtener todos los idiomas disponibles
    $idiomas_sql = "SELECT * FROM idiomas ORDER BY nombre_idioma";
    $idiomas_stmt = $connection->query($idiomas_sql);
    $todos_idiomas = $idiomas_stmt->fetchAll();
    
    // Obtener idiomas actuales del guía
    $idiomas_guia_sql = "SELECT id_idioma FROM guia_idiomas WHERE id_guia = ?";
    $idiomas_guia_stmt = $connection->prepare($idiomas_guia_sql);
    $idiomas_guia_stmt->execute([$id_guia]);
    $idiomas_guia = $idiomas_guia_stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode("Error al cargar guía: " . $e->getMessage()));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $experiencia = trim($_POST['experiencia'] ?? '');
    $estado = $_POST['estado'] ?? 'Libre';
    $foto_url = trim($_POST['foto_url'] ?? '');
    $idiomas_seleccionados = $_POST['idiomas'] ?? [];
    
    // Validaciones
    if (empty($nombre)) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($apellido)) {
        $errors[] = "El apellido es obligatorio";
    }
    
    if (empty($email)) {
        $errors[] = "El email es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no tiene un formato válido";
    }
    
    if (!in_array($estado, ['Libre', 'Ocupado'])) {
        $errors[] = "Estado no válido";
    }
    
    // Validar idiomas seleccionados
    if (!empty($idiomas_seleccionados)) {
        $idiomas_validos_sql = "SELECT id_idioma FROM idiomas WHERE id_idioma IN (" . 
                               str_repeat('?,', count($idiomas_seleccionados) - 1) . "?)";
        $idiomas_validos_stmt = $connection->prepare($idiomas_validos_sql);
        $idiomas_validos_stmt->execute($idiomas_seleccionados);
        $idiomas_validos = $idiomas_validos_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($idiomas_validos) !== count($idiomas_seleccionados)) {
            $errors[] = "Algunos idiomas seleccionados no son válidos";
        }
    }
    
    // Verificar si el email ya existe (excluyendo el guía actual)
    if (empty($errors)) {
        try {
            $check_email_sql = "SELECT COUNT(*) FROM guias WHERE email = ? AND id_guia != ?";
            $check_stmt = $connection->prepare($check_email_sql);
            $check_stmt->execute([$email, $id_guia]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $errors[] = "Ya existe otro guía registrado con este email";
            }
        } catch (Exception $e) {
            $errors[] = "Error al verificar email: " . $e->getMessage();
        }
    }
    
    // Si no hay errores, actualizar el guía
    if (empty($errors)) {
        try {
            // Iniciar transacción
            $connection->beginTransaction();
            
            // Actualizar datos básicos del guía
            $update_sql = "UPDATE guias SET 
                          nombre = ?, apellido = ?, telefono = ?, email = ?, 
                          experiencia = ?, estado = ?, foto_url = ?
                          WHERE id_guia = ?";
            $update_stmt = $connection->prepare($update_sql);
            $update_stmt->execute([
                $nombre,
                $apellido,
                $telefono ?: null,
                $email,
                $experiencia ?: null,
                $estado,
                $foto_url ?: null,
                $id_guia
            ]);
            
            // Eliminar idiomas actuales del guía
            $delete_idiomas_sql = "DELETE FROM guia_idiomas WHERE id_guia = ?";
            $delete_idiomas_stmt = $connection->prepare($delete_idiomas_sql);
            $delete_idiomas_stmt->execute([$id_guia]);
            
            // Insertar nuevos idiomas
            if (!empty($idiomas_seleccionados)) {
                $insert_idioma_sql = "INSERT INTO guia_idiomas (id_guia, id_idioma) VALUES (?, ?)";
                $insert_idioma_stmt = $connection->prepare($insert_idioma_sql);
                
                foreach ($idiomas_seleccionados as $id_idioma) {
                    $insert_idioma_stmt->execute([$id_guia, $id_idioma]);
                }
            }
            
            // Confirmar transacción
            $connection->commit();
            
            // Registrar actividad del administrador
            // registrarActividad($admin['id_administrador'], 'editar', 'guias', $id_guia, 
            //                  "Editó el guía: $nombre $apellido");
            
            $success = "Guía actualizado exitosamente";
            
            // Actualizar datos para mostrar en el formulario
            $guia = array_merge($guia, [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono,
                'email' => $email,
                'experiencia' => $experiencia,
                'estado' => $estado,
                'foto_url' => $foto_url
            ]);
            
            // Actualizar idiomas del guía para la vista
            $idiomas_guia = $idiomas_seleccionados;
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $connection->rollback();
            $errors[] = "Error al actualizar guía: " . $e->getMessage();
        }
    }
}

$page_title = "Editar Guía: " . $guia['nombre'] . ' ' . $guia['apellido'];
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
                                    <i class="fas fa-user-edit text-blue-600 mr-3"></i>Editar Guía
                                </h1>
                            </div>
                            <p class="text-sm lg:text-base text-gray-600">Modifica la información del guía turístico</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="ver.php?id=<?php echo $guia['id_guia']; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-eye mr-2"></i>Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mostrar errores -->
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-red-800">Se encontraron errores:</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Mostrar éxito -->
                <?php if ($success): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-green-800">¡Éxito!</h3>
                                <p class="text-sm text-green-700 mt-1"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Formulario -->
                    <div class="lg:col-span-2">
                        <form method="POST" class="bg-white rounded-lg shadow-lg p-6">
                            <div class="space-y-6">
                                <!-- Información Personal -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-user text-blue-600 mr-2"></i>Información Personal
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                                Nombre <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="nombre" id="nombre" required
                                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? $guia['nombre']); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Nombre del guía">
                                        </div>
                                        
                                        <div>
                                            <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2">
                                                Apellido <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="apellido" id="apellido" required
                                                   value="<?php echo htmlspecialchars($_POST['apellido'] ?? $guia['apellido']); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Apellido del guía">
                                        </div>
                                    </div>
                                </div>

                                <!-- Información de Contacto -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-envelope text-blue-600 mr-2"></i>Información de Contacto
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                                Email <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="email" name="email" id="email" required
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? $guia['email']); ?>"
                                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                       placeholder="email@ejemplo.com">
                                                <i class="fas fa-envelope absolute left-3 top-3 text-gray-400"></i>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Debe ser único en el sistema</p>
                                        </div>
                                        
                                        <div>
                                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                                                Teléfono
                                            </label>
                                            <div class="relative">
                                                <input type="tel" name="telefono" id="telefono"
                                                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? $guia['telefono'] ?? ''); ?>"
                                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                       placeholder="+51 999 999 999">
                                                <i class="fas fa-phone absolute left-3 top-3 text-gray-400"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado y Foto -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-cog text-blue-600 mr-2"></i>Configuración
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                                Estado
                                            </label>
                                            <select name="estado" id="estado" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="Libre" <?php echo ($_POST['estado'] ?? $guia['estado']) === 'Libre' ? 'selected' : ''; ?>>
                                                    Libre
                                                </option>
                                                <option value="Ocupado" <?php echo ($_POST['estado'] ?? $guia['estado']) === 'Ocupado' ? 'selected' : ''; ?>>
                                                    Ocupado
                                                </option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="foto_url" class="block text-sm font-medium text-gray-700 mb-2">
                                                URL de Foto
                                            </label>
                                            <div class="relative">
                                                <input type="url" name="foto_url" id="foto_url"
                                                       value="<?php echo htmlspecialchars($_POST['foto_url'] ?? $guia['foto_url'] ?? ''); ?>"
                                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                       placeholder="https://ejemplo.com/foto.jpg">
                                                <i class="fas fa-image absolute left-3 top-3 text-gray-400"></i>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Opcional. URL de la foto del guía</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Experiencia -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-user-graduate text-blue-600 mr-2"></i>Experiencia
                                    </h3>
                                    <div>
                                        <label for="experiencia" class="block text-sm font-medium text-gray-700 mb-2">
                                            Descripción de Experiencia
                                        </label>
                                        <textarea name="experiencia" id="experiencia" rows="4"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Describe la experiencia, especialidades, certificaciones, años de trabajo, etc."><?php echo htmlspecialchars($_POST['experiencia'] ?? $guia['experiencia'] ?? ''); ?></textarea>
                                        <p class="text-xs text-gray-500 mt-1">Información sobre la experiencia y especialidades del guía</p>
                                    </div>
                                </div>

                                <!-- Idiomas -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-globe text-blue-600 mr-2"></i>Idiomas
                                    </h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            Selecciona los idiomas que maneja el guía
                                        </label>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-4">
                                            <?php foreach ($todos_idiomas as $idioma): ?>
                                                <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                                    <input type="checkbox" 
                                                           name="idiomas[]" 
                                                           value="<?php echo $idioma['id_idioma']; ?>"
                                                           <?php echo in_array($idioma['id_idioma'], $_POST['idiomas'] ?? $idiomas_guia) ? 'checked' : ''; ?>
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700">
                                                        <?php echo htmlspecialchars($idioma['nombre_idioma']); ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">
                                            Selecciona todos los idiomas que el guía puede manejar durante los tours
                                        </p>
                                        <div class="mt-2">
                                            <span class="text-sm font-medium text-gray-700">Idiomas seleccionados: </span>
                                            <span id="idiomas-count" class="text-sm text-blue-600 font-medium">
                                                <?php echo count($_POST['idiomas'] ?? $idiomas_guia); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                    <a href="ver.php?id=<?php echo $guia['id_guia']; ?>" 
                                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                        Cancelar
                                    </a>
                                    <button type="submit" 
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Panel de información -->
                    <div class="lg:col-span-1">
                        <!-- Vista previa -->
                        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-eye text-blue-600 mr-2"></i>Vista Previa
                            </h3>
                            
                            <div class="text-center" id="preview">
                                <div class="h-16 w-16 rounded-full bg-blue-600 flex items-center justify-center mx-auto mb-3">
                                    <span class="text-white font-bold text-lg" id="preview-initials">
                                        <?php echo strtoupper(substr($guia['nombre'], 0, 1) . substr($guia['apellido'], 0, 1)); ?>
                                    </span>
                                </div>
                                <h4 class="font-medium text-gray-900" id="preview-name">
                                    <?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>
                                </h4>
                                <p class="text-sm text-gray-600" id="preview-email">
                                    <?php echo htmlspecialchars($guia['email']); ?>
                                </p>
                                <div class="flex flex-col items-center space-y-2 mt-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $guia['estado'] === 'Libre' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <i class="<?php echo $guia['estado'] === 'Libre' ? 'fas fa-check-circle' : 'fas fa-clock'; ?> mr-1"></i>
                                        <span id="preview-estado"><?php echo $guia['estado']; ?></span>
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <i class="fas fa-globe mr-1"></i>
                                        <span id="preview-idiomas"><?php echo count($idiomas_guia); ?></span> idioma<?php echo count($idiomas_guia) != 1 ? 's' : ''; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Información del guía -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Información del Guía
                            </h3>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-500">ID:</span>
                                    <span class="text-sm text-gray-900">#<?php echo $guia['id_guia']; ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-500">Estado Original:</span>
                                    <span class="text-sm text-gray-900"><?php echo $guia['estado']; ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-500">Email Original:</span>
                                    <span class="text-sm text-gray-900 break-all"><?php echo htmlspecialchars($guia['email']); ?></span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-500">Idiomas Actuales:</span>
                                    <span class="text-sm text-gray-900"><?php echo count($idiomas_guia); ?> idioma<?php echo count($idiomas_guia) != 1 ? 's' : ''; ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <h4 class="font-medium text-gray-900 mb-2">Acciones Rápidas</h4>
                                <div class="space-y-2">
                                    <a href="ver.php?id=<?php echo $guia['id_guia']; ?>" 
                                       class="block w-full text-center px-3 py-2 text-sm bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                                        <i class="fas fa-eye mr-1"></i>Ver Detalles
                                    </a>
                                    <button onclick="cambiarEstado(<?php echo $guia['id_guia']; ?>, '<?php echo $guia['estado']; ?>')" 
                                            class="block w-full text-center px-3 py-2 text-sm bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors">
                                        <i class="fas fa-sync mr-1"></i>Cambiar Estado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Vista previa en tiempo real
        function updatePreview() {
            const nombre = document.getElementById('nombre').value || 'Nuevo';
            const apellido = document.getElementById('apellido').value || 'Guía';
            const email = document.getElementById('email').value || 'email@ejemplo.com';
            const estado = document.getElementById('estado').value;
            
            // Contar idiomas seleccionados
            const idiomasSeleccionados = document.querySelectorAll('input[name="idiomas[]"]:checked').length;
            
            // Actualizar nombre
            document.getElementById('preview-name').textContent = `${nombre} ${apellido}`;
            
            // Actualizar email
            document.getElementById('preview-email').textContent = email;
            
            // Actualizar iniciales
            const initials = (nombre.charAt(0) + apellido.charAt(0)).toUpperCase();
            document.getElementById('preview-initials').textContent = initials;
            
            // Actualizar estado
            const estadoSpan = document.getElementById('preview-estado');
            estadoSpan.textContent = estado;
            
            // Actualizar clase del estado
            const estadoContainer = estadoSpan.parentElement;
            estadoContainer.className = estado === 'Libre' 
                ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'
                : 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
            
            // Actualizar contador de idiomas
            document.getElementById('preview-idiomas').textContent = idiomasSeleccionados;
            document.getElementById('idiomas-count').textContent = idiomasSeleccionados;
        }

        function cambiarEstado(id, estadoActual) {
            const nuevoEstado = estadoActual === 'Libre' ? 'Ocupado' : 'Libre';
            if (confirm(`¿Deseas cambiar el estado del guía a "${nuevoEstado}"?`)) {
                fetch('cambiar_estado.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        id_guia: id,
                        nuevo_estado: nuevoEstado
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al cambiar estado: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar estado');
                });
            }
        }

        // Agregar eventos a los campos
        document.getElementById('nombre').addEventListener('input', updatePreview);
        document.getElementById('apellido').addEventListener('input', updatePreview);
        document.getElementById('email').addEventListener('input', updatePreview);
        document.getElementById('estado').addEventListener('change', updatePreview);
        
        // Agregar eventos a los checkboxes de idiomas
        document.querySelectorAll('input[name="idiomas[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', updatePreview);
        });
        
        // Inicializar vista previa
        updatePreview();
    </script>
</body>
</html>
