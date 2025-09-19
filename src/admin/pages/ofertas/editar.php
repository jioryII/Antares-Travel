<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';

// Verificar sesión de administrador
verificarSesionAdmin();

$id_oferta = intval($_GET['id'] ?? 0);

if ($id_oferta <= 0) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Obtener datos necesarios
try {
    $connection = getConnection();
    
    // Obtener datos de la oferta
    $oferta_sql = "SELECT * FROM ofertas WHERE id_oferta = ?";
    $stmt = $connection->prepare($oferta_sql);
    $stmt->execute([$id_oferta]);
    $oferta = $stmt->fetch();
    
    if (!$oferta) {
        header("Location: index.php");
        exit;
    }
    
    // Obtener tours para el select
    $tours_sql = "SELECT id_tour, titulo FROM tours ORDER BY titulo";
    $tours_stmt = $connection->query($tours_sql);
    $tours = $tours_stmt->fetchAll();
    
    // Obtener usuarios para ofertas específicas
    $usuarios_sql = "SELECT id_usuario, nombre, email FROM usuarios ORDER BY nombre";
    $usuarios_stmt = $connection->query($usuarios_sql);
    $usuarios = $usuarios_stmt->fetchAll();
    
    // Obtener tours ya asignados
    $tours_asignados_sql = "SELECT id_tour FROM ofertas_tours WHERE id_oferta = ?";
    $tours_asignados_stmt = $connection->prepare($tours_asignados_sql);
    $tours_asignados_stmt->execute([$id_oferta]);
    $tours_asignados = array_column($tours_asignados_stmt->fetchAll(), 'id_tour');
    
    // Obtener usuarios ya asignados
    $usuarios_asignados_sql = "SELECT id_usuario FROM ofertas_usuarios WHERE id_oferta = ?";
    $usuarios_asignados_stmt = $connection->prepare($usuarios_asignados_sql);
    $usuarios_asignados_stmt->execute([$id_oferta]);
    $usuarios_asignados = array_column($usuarios_asignados_stmt->fetchAll(), 'id_usuario');
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
    $tours = [];
    $usuarios = [];
    $tours_asignados = [];
    $usuarios_asignados = [];
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $connection->beginTransaction();
        
        // Obtener admin actual
        session_start();
        $admin_id = $_SESSION['admin_id'] ?? null;
        
        // Validar datos requeridos
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tipo_oferta = $_POST['tipo_oferta'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $aplicable_a = $_POST['aplicable_a'] ?? '';
        $estado = $_POST['estado'] ?? 'Borrador';
        
        if (empty($nombre) || empty($tipo_oferta) || empty($fecha_inicio) || empty($fecha_fin)) {
            throw new Exception("Todos los campos requeridos deben ser completados.");
        }
        
        // Validar fechas
        $fecha_inicio_dt = new DateTime($fecha_inicio);
        $fecha_fin_dt = new DateTime($fecha_fin);
        
        if ($fecha_fin_dt <= $fecha_inicio_dt) {
            throw new Exception("La fecha de fin debe ser posterior a la fecha de inicio.");
        }
        
        // Preparar datos para actualización
        $datos_oferta = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'tipo_oferta' => $tipo_oferta,
            'valor_descuento' => !empty($_POST['valor_descuento']) ? floatval($_POST['valor_descuento']) : null,
            'precio_especial' => !empty($_POST['precio_especial']) ? floatval($_POST['precio_especial']) : null,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'limite_usos' => !empty($_POST['limite_usos']) ? intval($_POST['limite_usos']) : null,
            'limite_por_usuario' => intval($_POST['limite_por_usuario'] ?? 1),
            'monto_minimo' => !empty($_POST['monto_minimo']) ? floatval($_POST['monto_minimo']) : null,
            'aplicable_a' => $aplicable_a,
            'codigo_promocional' => trim($_POST['codigo_promocional'] ?? ''),
            'estado' => $estado,
            'visible_publica' => isset($_POST['visible_publica']) ? 1 : 0,
            'destacada' => isset($_POST['destacada']) ? 1 : 0,
            'mensaje_promocional' => trim($_POST['mensaje_promocional'] ?? ''),
            'terminos_condiciones' => trim($_POST['terminos_condiciones'] ?? '')
        ];
        
        // Validar código promocional único si se proporciona y ha cambiado
        if (!empty($datos_oferta['codigo_promocional']) && $datos_oferta['codigo_promocional'] !== $oferta['codigo_promocional']) {
            $check_codigo = "SELECT COUNT(*) FROM ofertas WHERE codigo_promocional = ? AND id_oferta != ?";
            $check_stmt = $connection->prepare($check_codigo);
            $check_stmt->execute([$datos_oferta['codigo_promocional'], $id_oferta]);
            
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("El código promocional ya existe. Elige otro código.");
            }
        }
        
        // Actualizar oferta
        $campos = array_map(function($campo) { return "$campo = ?"; }, array_keys($datos_oferta));
        $sql = "UPDATE ofertas SET " . implode(',', $campos) . ", actualizado_en = CURRENT_TIMESTAMP WHERE id_oferta = ?";
        
        $valores = array_values($datos_oferta);
        $valores[] = $id_oferta;
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($valores);
        
        // Manejar relaciones según el tipo de aplicabilidad
        // Limpiar relaciones existentes
        $delete_tours = "DELETE FROM ofertas_tours WHERE id_oferta = ?";
        $stmt_delete_tours = $connection->prepare($delete_tours);
        $stmt_delete_tours->execute([$id_oferta]);
        
        $delete_usuarios = "DELETE FROM ofertas_usuarios WHERE id_oferta = ?";
        $stmt_delete_usuarios = $connection->prepare($delete_usuarios);
        $stmt_delete_usuarios->execute([$id_oferta]);
        
        // Agregar nuevas relaciones
        if ($aplicable_a === 'Tours_Especificos' && !empty($_POST['tours_seleccionados'])) {
            $sql_tour = "INSERT INTO ofertas_tours (id_oferta, id_tour) VALUES (?, ?)";
            $stmt_tour = $connection->prepare($sql_tour);
            
            foreach ($_POST['tours_seleccionados'] as $tour_id) {
                $stmt_tour->execute([$id_oferta, intval($tour_id)]);
            }
        }
        
        if ($aplicable_a === 'Usuarios_Especificos' && !empty($_POST['usuarios_seleccionados'])) {
            $sql_usuario = "INSERT INTO ofertas_usuarios (id_oferta, id_usuario) VALUES (?, ?)";
            $stmt_usuario = $connection->prepare($sql_usuario);
            
            foreach ($_POST['usuarios_seleccionados'] as $usuario_id) {
                $stmt_usuario->execute([$id_oferta, intval($usuario_id)]);
            }
        }
        
        // Manejar imagen si se subió
        if (isset($_FILES['imagen_banner']) && $_FILES['imagen_banner']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../storage/uploads/ofertas/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Eliminar imagen anterior si existe
            if (!empty($oferta['imagen_banner'])) {
                $ruta_anterior = __DIR__ . '/../../' . $oferta['imagen_banner'];
                if (file_exists($ruta_anterior)) {
                    unlink($ruta_anterior);
                }
            }
            
            $file_info = pathinfo($_FILES['imagen_banner']['name']);
            $extension = strtolower($file_info['extension']);
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($extension, $allowed)) {
                $filename = 'oferta_' . $id_oferta . '_' . time() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['imagen_banner']['tmp_name'], $filepath)) {
                    // Guardar solo la ruta relativa desde la carpeta admin
                    $imagen_url = 'storage/uploads/ofertas/' . $filename;
                    $update_imagen = "UPDATE ofertas SET imagen_banner = ? WHERE id_oferta = ?";
                    $stmt_imagen = $connection->prepare($update_imagen);
                    $stmt_imagen->execute([$imagen_url, $id_oferta]);
                    
                    // Actualizar variable local
                    $oferta['imagen_banner'] = $imagen_url;
                }
            }
        }
        
        $connection->commit();
        
        // Actualizar variable local con nuevos datos
        $oferta = array_merge($oferta, $datos_oferta);
        
        $success = "Oferta actualizada exitosamente.";
        
        // Actualizar arrays de asignación
        $tours_asignados = $_POST['tours_seleccionados'] ?? [];
        $usuarios_asignados = $_POST['usuarios_seleccionados'] ?? [];
        
    } catch (Exception $e) {
        $connection->rollback();
        $error = "Error al actualizar la oferta: " . $e->getMessage();
    }
}

$page_title = "Editar Oferta: " . ($oferta['nombre'] ?? 'Sin nombre');
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
    <style>
        .form-section {
            transition: all 0.3s ease;
        }
        
        .form-section.hidden {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
        }
        
        .form-section.visible {
            max-height: 1000px;
            opacity: 1;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 150px;
            object-fit: cover;
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
                    <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-xl lg:text-3xl font-bold text-gray-900">
                                <i class="fas fa-edit text-blue-600 mr-2 lg:mr-3"></i>Editar Oferta
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600 mt-1">Modifica la configuración de la oferta promocional</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="index.php" class="inline-flex items-center justify-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                                <i class="fas fa-arrow-left mr-2"></i>Volver a Lista
                            </a>
                            <a href="ver.php?id=<?php echo $id_oferta; ?>" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-eye mr-2"></i>Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mostrar mensajes -->
                <?php if (!empty($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-red-800 font-medium">Error</h3>
                                <p class="text-red-700 mt-1"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-green-800 font-medium">Éxito</h3>
                                <p class="text-green-700 mt-1"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <form method="POST" enctype="multipart/form-data" class="space-y-8">
                    <!-- Información básica -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-2 bg-red-100 rounded-full mr-3">
                                <i class="fas fa-info-circle text-red-600"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Información Básica</h2>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre de la Oferta *
                                </label>
                                <input type="text" id="nombre" name="nombre" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       placeholder="ej. Descuento de Verano 2025"
                                       value="<?php echo htmlspecialchars($oferta['nombre']); ?>">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                                    Descripción
                                </label>
                                <textarea id="descripcion" name="descripcion" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                          placeholder="Describe los detalles de la oferta..."><?php echo htmlspecialchars($oferta['descripcion']); ?></textarea>
                            </div>
                            
                            <div>
                                <label for="tipo_oferta" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Oferta *
                                </label>
                                <select id="tipo_oferta" name="tipo_oferta" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="Porcentaje" <?php echo $oferta['tipo_oferta'] === 'Porcentaje' ? 'selected' : ''; ?>>
                                        Descuento por Porcentaje
                                    </option>
                                    <option value="Monto_Fijo" <?php echo $oferta['tipo_oferta'] === 'Monto_Fijo' ? 'selected' : ''; ?>>
                                        Descuento Monto Fijo
                                    </option>
                                    <option value="Precio_Especial" <?php echo $oferta['tipo_oferta'] === 'Precio_Especial' ? 'selected' : ''; ?>>
                                        Precio Especial
                                    </option>
                                    <option value="2x1" <?php echo $oferta['tipo_oferta'] === '2x1' ? 'selected' : ''; ?>>
                                        2x1 - Paga Uno Lleva Dos
                                    </option>
                                    <option value="Combo" <?php echo $oferta['tipo_oferta'] === 'Combo' ? 'selected' : ''; ?>>
                                        Oferta Combo
                                    </option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                    Estado
                                </label>
                                <select id="estado" name="estado"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                                    <option value="Borrador" <?php echo $oferta['estado'] === 'Borrador' ? 'selected' : ''; ?>>
                                        Borrador
                                    </option>
                                    <option value="Activa" <?php echo $oferta['estado'] === 'Activa' ? 'selected' : ''; ?>>
                                        Activa
                                    </option>
                                    <option value="Pausada" <?php echo $oferta['estado'] === 'Pausada' ? 'selected' : ''; ?>>
                                        Pausada
                                    </option>
                                    <option value="Finalizada" <?php echo $oferta['estado'] === 'Finalizada' ? 'selected' : ''; ?>>
                                        Finalizada
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de descuento -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-2 bg-green-100 rounded-full mr-3">
                                <i class="fas fa-percentage text-green-600"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Configuración del Descuento</h2>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div id="campo_valor_descuento" class="form-section">
                                <label for="valor_descuento" class="block text-sm font-medium text-gray-700 mb-2">
                                    Valor del Descuento
                                </label>
                                <div class="relative">
                                    <input type="number" id="valor_descuento" name="valor_descuento" step="0.01" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 pr-12"
                                           placeholder="0.00"
                                           value="<?php echo htmlspecialchars($oferta['valor_descuento']); ?>">
                                    <span id="simbolo_descuento" class="absolute right-3 top-2 text-gray-500">%</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Para porcentaje: 10 = 10%. Para monto fijo: cantidad en soles</p>
                            </div>
                            
                            <div id="campo_precio_especial" class="form-section hidden">
                                <label for="precio_especial" class="block text-sm font-medium text-gray-700 mb-2">
                                    Precio Especial (S/)
                                </label>
                                <input type="number" id="precio_especial" name="precio_especial" step="0.01" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       placeholder="0.00"
                                       value="<?php echo htmlspecialchars($oferta['precio_especial']); ?>">
                            </div>
                            
                            <div>
                                <label for="monto_minimo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Monto Mínimo de Compra (S/)
                                </label>
                                <input type="number" id="monto_minimo" name="monto_minimo" step="0.01" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       placeholder="0.00"
                                       value="<?php echo htmlspecialchars($oferta['monto_minimo']); ?>">
                                <p class="text-xs text-gray-500 mt-1">Opcional: monto mínimo para aplicar la oferta</p>
                            </div>
                        </div>
                    </div>

                    <!-- Vigencia y límites -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-2 bg-blue-100 rounded-full mr-3">
                                <i class="fas fa-calendar-alt text-blue-600"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Vigencia y Límites</h2>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div>
                                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-2">
                                    Fecha de Inicio *
                                </label>
                                <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($oferta['fecha_inicio'])); ?>">
                            </div>
                            
                            <div>
                                <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-2">
                                    Fecha de Fin *
                                </label>
                                <input type="datetime-local" id="fecha_fin" name="fecha_fin" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($oferta['fecha_fin'])); ?>">
                            </div>
                            
                            <div>
                                <label for="limite_usos" class="block text-sm font-medium text-gray-700 mb-2">
                                    Límite Total de Usos
                                </label>
                                <input type="number" id="limite_usos" name="limite_usos" min="1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       placeholder="Sin límite"
                                       value="<?php echo htmlspecialchars($oferta['limite_usos']); ?>">
                            </div>
                            
                            <div>
                                <label for="limite_por_usuario" class="block text-sm font-medium text-gray-700 mb-2">
                                    Límite por Usuario
                                </label>
                                <input type="number" id="limite_por_usuario" name="limite_por_usuario" min="1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       placeholder="1"
                                       value="<?php echo htmlspecialchars($oferta['limite_por_usuario']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Aplicabilidad -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-2 bg-purple-100 rounded-full mr-3">
                                <i class="fas fa-target text-purple-600"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Aplicabilidad</h2>
                        </div>
                        
                        <div class="space-y-6">
                            <div>
                                <label for="aplicable_a" class="block text-sm font-medium text-gray-700 mb-2">
                                    Aplicar oferta a:
                                </label>
                                <select id="aplicable_a" name="aplicable_a" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                                    <option value="Todos" <?php echo $oferta['aplicable_a'] === 'Todos' ? 'selected' : ''; ?>>
                                        Todos los tours
                                    </option>
                                    <option value="Tours_Especificos" <?php echo $oferta['aplicable_a'] === 'Tours_Especificos' ? 'selected' : ''; ?>>
                                        Tours específicos
                                    </option>
                                    <option value="Usuarios_Especificos" <?php echo $oferta['aplicable_a'] === 'Usuarios_Especificos' ? 'selected' : ''; ?>>
                                        Usuarios específicos
                                    </option>
                                    <option value="Nuevos_Usuarios" <?php echo $oferta['aplicable_a'] === 'Nuevos_Usuarios' ? 'selected' : ''; ?>>
                                        Solo usuarios nuevos
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Tours específicos -->
                            <div id="seccion_tours" class="form-section hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Seleccionar Tours
                                </label>
                                <div class="border border-gray-300 rounded-lg p-4 max-h-60 overflow-y-auto">
                                    <?php foreach ($tours as $tour): ?>
                                        <div class="flex items-center mb-2">
                                            <input type="checkbox" id="tour_<?php echo $tour['id_tour']; ?>" 
                                                   name="tours_seleccionados[]" value="<?php echo $tour['id_tour']; ?>"
                                                   class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                                   <?php echo in_array($tour['id_tour'], $tours_asignados) ? 'checked' : ''; ?>>
                                            <label for="tour_<?php echo $tour['id_tour']; ?>" class="ml-2 text-sm text-gray-700">
                                                <?php echo htmlspecialchars($tour['titulo']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Usuarios específicos -->
                            <div id="seccion_usuarios" class="form-section hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Seleccionar Usuarios
                                </label>
                                <div class="border border-gray-300 rounded-lg p-4 max-h-60 overflow-y-auto">
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <div class="flex items-center mb-2">
                                            <input type="checkbox" id="usuario_<?php echo $usuario['id_usuario']; ?>" 
                                                   name="usuarios_seleccionados[]" value="<?php echo $usuario['id_usuario']; ?>"
                                                   class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                                   <?php echo in_array($usuario['id_usuario'], $usuarios_asignados) ? 'checked' : ''; ?>>
                                            <label for="usuario_<?php echo $usuario['id_usuario']; ?>" class="ml-2 text-sm text-gray-700">
                                                <?php echo htmlspecialchars($usuario['nombre']); ?> 
                                                <span class="text-gray-500">(<?php echo htmlspecialchars($usuario['email']); ?>)</span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración adicional -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-2 bg-orange-100 rounded-full mr-3">
                                <i class="fas fa-cog text-orange-600"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Configuración Adicional</h2>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="codigo_promocional" class="block text-sm font-medium text-gray-700 mb-2">
                                    Código Promocional
                                </label>
                                <input type="text" id="codigo_promocional" name="codigo_promocional"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       placeholder="ej. VERANO2025"
                                       value="<?php echo htmlspecialchars($oferta['codigo_promocional']); ?>">
                                <p class="text-xs text-gray-500 mt-1">Opcional: código que deben ingresar los usuarios</p>
                            </div>
                            
                            <div>
                                <label for="imagen_banner" class="block text-sm font-medium text-gray-700 mb-2">
                                    Imagen Banner
                                </label>
                                <input type="file" id="imagen_banner" name="imagen_banner" accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                                <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG, WebP. Máximo 5MB</p>
                                
                                <!-- Mostrar imagen actual -->
                                <div id="imagen_actual" class="mt-2 <?php echo empty($oferta['imagen_banner']) ? 'hidden' : ''; ?>">
                                    <p class="text-xs text-gray-600 mb-2">Imagen actual:</p>
                                    <img id="img_actual" src="../../<?php echo htmlspecialchars($oferta['imagen_banner']); ?>" 
                                         alt="Imagen actual" class="preview-image rounded-lg">
                                </div>
                                
                                <!-- Preview de nueva imagen -->
                                <div id="preview_imagen" class="mt-2 hidden">
                                    <p class="text-xs text-gray-600 mb-2">Nueva imagen:</p>
                                    <img id="img_preview" src="" alt="Preview" class="preview-image rounded-lg">
                                </div>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="mensaje_promocional" class="block text-sm font-medium text-gray-700 mb-2">
                                    Mensaje Promocional
                                </label>
                                <input type="text" id="mensaje_promocional" name="mensaje_promocional" maxlength="500"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                       placeholder="ej. ¡Aprovecha esta oferta especial por tiempo limitado!"
                                       value="<?php echo htmlspecialchars($oferta['mensaje_promocional']); ?>">
                                <p class="text-xs text-gray-500 mt-1">Mensaje que se mostrará al usuario al aplicar la oferta</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="terminos_condiciones" class="block text-sm font-medium text-gray-700 mb-2">
                                    Términos y Condiciones
                                </label>
                                <textarea id="terminos_condiciones" name="terminos_condiciones" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                          placeholder="Especifica los términos y condiciones de la oferta..."><?php echo htmlspecialchars($oferta['terminos_condiciones']); ?></textarea>
                            </div>
                            
                            <div class="md:col-span-2 flex flex-wrap gap-4">
                                <div class="flex items-center">
                                    <input type="checkbox" id="visible_publica" name="visible_publica" 
                                           class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                           <?php echo $oferta['visible_publica'] ? 'checked' : ''; ?>>
                                    <label for="visible_publica" class="ml-2 text-sm text-gray-700">
                                        Visible públicamente
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="destacada" name="destacada" 
                                           class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                           <?php echo $oferta['destacada'] ? 'checked' : ''; ?>>
                                    <label for="destacada" class="ml-2 text-sm text-gray-700">
                                        Oferta destacada
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-end">
                        <a href="ver.php?id=<?php echo $id_oferta; ?>" 
                           class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-center">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Manejar cambios en el tipo de oferta
        document.getElementById('tipo_oferta').addEventListener('change', function() {
            const tipo = this.value;
            const campoValorDescuento = document.getElementById('campo_valor_descuento');
            const campoPrecioEspecial = document.getElementById('campo_precio_especial');
            const simboloDescuento = document.getElementById('simbolo_descuento');
            
            // Resetear visibilidad
            campoValorDescuento.classList.remove('hidden');
            campoPrecioEspecial.classList.add('hidden');
            
            // Ajustar según el tipo
            switch(tipo) {
                case 'Porcentaje':
                    simboloDescuento.textContent = '%';
                    break;
                case 'Monto_Fijo':
                    simboloDescuento.textContent = 'S/';
                    break;
                case 'Precio_Especial':
                    campoValorDescuento.classList.add('hidden');
                    campoPrecioEspecial.classList.remove('hidden');
                    break;
                case '2x1':
                case 'Combo':
                    campoValorDescuento.classList.add('hidden');
                    break;
            }
        });
        
        // Manejar cambios en aplicabilidad
        document.getElementById('aplicable_a').addEventListener('change', function() {
            const valor = this.value;
            const seccionTours = document.getElementById('seccion_tours');
            const seccionUsuarios = document.getElementById('seccion_usuarios');
            
            // Ocultar todas las secciones
            seccionTours.classList.add('hidden');
            seccionUsuarios.classList.add('hidden');
            
            // Mostrar sección correspondiente
            if (valor === 'Tours_Especificos') {
                seccionTours.classList.remove('hidden');
            } else if (valor === 'Usuarios_Especificos') {
                seccionUsuarios.classList.remove('hidden');
            }
        });
        
        // Preview de imagen
        document.getElementById('imagen_banner').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('img_preview').src = e.target.result;
                    document.getElementById('preview_imagen').classList.remove('hidden');
                    // Ocultar imagen actual
                    document.getElementById('imagen_actual').classList.add('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('preview_imagen').classList.add('hidden');
                document.getElementById('imagen_actual').classList.remove('hidden');
            }
        });
        
        // Validación de fechas
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            const fechaInicio = new Date(this.value);
            const fechaFinInput = document.getElementById('fecha_fin');
            
            // Establecer fecha mínima para fecha fin
            fechaFinInput.min = this.value;
            
            // Si fecha fin es anterior a fecha inicio, limpiarla
            if (fechaFinInput.value && new Date(fechaFinInput.value) <= fechaInicio) {
                fechaFinInput.value = '';
            }
        });
        
        // Inicializar formulario
        document.addEventListener('DOMContentLoaded', function() {
            // Disparar eventos para configuración inicial
            document.getElementById('tipo_oferta').dispatchEvent(new Event('change'));
            document.getElementById('aplicable_a').dispatchEvent(new Event('change'));
        });
    </script>
</body>
</html>
