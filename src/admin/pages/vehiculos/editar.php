<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del vehículo
$id_vehiculo = intval($_GET['id'] ?? 0);

if (!$id_vehiculo) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

try {
    $connection = getConnection();
    
    // Obtener información actual del vehículo
    $vehiculo_sql = "SELECT * FROM vehiculos WHERE id_vehiculo = ?";
    $vehiculo_stmt = $connection->prepare($vehiculo_sql);
    $vehiculo_stmt->execute([$id_vehiculo]);
    $vehiculo = $vehiculo_stmt->fetch();
    
    if (!$vehiculo) {
        header('Location: index.php?error=Vehículo no encontrado');
        exit;
    }
    
    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener y validar datos
        $marca = trim($_POST['marca'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $placa = trim(strtoupper($_POST['placa'] ?? ''));
        $capacidad = intval($_POST['capacidad'] ?? 0);
        $caracteristicas = trim($_POST['caracteristicas'] ?? '');
        $chofer_id = intval($_POST['chofer_id'] ?? 0) ?: null;
        
        // Validaciones
        if (empty($marca)) {
            throw new Exception('La marca es obligatoria');
        }
        
        if (empty($modelo)) {
            throw new Exception('El modelo es obligatorio');
        }
        
        if (empty($placa)) {
            throw new Exception('La placa es obligatoria');
        }
        
        if ($capacidad < 1 || $capacidad > 50) {
            throw new Exception('La capacidad debe estar entre 1 y 50 personas');
        }
        
        // Validar formato de placa (básico)
        if (!preg_match('/^[A-Z0-9\-]{3,10}$/', $placa)) {
            throw new Exception('El formato de la placa no es válido');
        }
        
        // Verificar que la placa no existe (excepto la actual)
        $placa_check = "SELECT id_vehiculo FROM vehiculos WHERE placa = ? AND id_vehiculo != ?";
        $placa_stmt = $connection->prepare($placa_check);
        $placa_stmt->execute([$placa, $id_vehiculo]);
        
        if ($placa_stmt->fetch()) {
            throw new Exception('Ya existe otro vehículo con esa placa');
        }
        
        // Verificar chofer si se especificó y cambió
        if ($chofer_id && $chofer_id != $vehiculo['id_chofer']) {
            $chofer_check = "SELECT c.id_chofer FROM choferes c
                            LEFT JOIN vehiculos v ON c.id_chofer = v.id_chofer
                            WHERE c.id_chofer = ? AND (v.id_chofer IS NULL OR v.id_vehiculo = ?)";
            $chofer_stmt = $connection->prepare($chofer_check);
            $chofer_stmt->execute([$chofer_id, $id_vehiculo]);
            
            if (!$chofer_stmt->fetch()) {
                throw new Exception('El chofer seleccionado no está disponible');
            }
        }
        
        // Actualizar vehículo
        $update_sql = "UPDATE vehiculos 
                      SET marca = ?, modelo = ?, placa = ?, capacidad = ?, caracteristicas = ?, id_chofer = ?
                      WHERE id_vehiculo = ?";
        
        $update_stmt = $connection->prepare($update_sql);
        $updated = $update_stmt->execute([
            $marca, 
            $modelo, 
            $placa, 
            $capacidad, 
            $caracteristicas ?: null, 
            $chofer_id,
            $id_vehiculo
        ]);
        
        if ($updated) {
            $success = "Vehículo actualizado exitosamente";
            
            // Actualizar datos locales para mostrar cambios
            $vehiculo['marca'] = $marca;
            $vehiculo['modelo'] = $modelo;
            $vehiculo['placa'] = $placa;
            $vehiculo['capacidad'] = $capacidad;
            $vehiculo['caracteristicas'] = $caracteristicas;
            $vehiculo['id_chofer'] = $chofer_id;
            
            // Redirigir después de un breve delay para mostrar el mensaje
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'ver.php?id=$id_vehiculo&success=" . urlencode($success) . "';
                }, 2000);
            </script>";
        } else {
            throw new Exception('Error al actualizar el vehículo');
        }
    }
    
    // Obtener lista de choferes disponibles (incluir el actual si lo tiene)
    $choferes_sql = "SELECT c.id_chofer, c.nombre, c.apellido, c.telefono
                     FROM choferes c
                     LEFT JOIN vehiculos v ON c.id_chofer = v.id_chofer
                     WHERE v.id_chofer IS NULL OR v.id_vehiculo = ?
                     ORDER BY c.nombre, c.apellido";
    $choferes_stmt = $connection->prepare($choferes_sql);
    $choferes_stmt->execute([$id_vehiculo]);
    $choferes_disponibles = $choferes_stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $choferes_disponibles = [];
}

$page_title = "Editar Vehículo: " . $vehiculo['marca'] . ' ' . $vehiculo['modelo'];
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
                                    <i class="fas fa-car mr-2"></i>
                                    Vehículos
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                    <a href="ver.php?id=<?php echo $id_vehiculo; ?>" class="text-gray-600 hover:text-blue-600">
                                        <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>
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
                        <i class="fas fa-edit text-green-600 mr-3"></i>Editar Vehículo
                    </h1>
                    <p class="text-gray-600 mt-2">Modifica la información del vehículo <?php echo htmlspecialchars($vehiculo['placa']); ?></p>
                </div>

                <!-- Mensajes -->
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
                                <p class="text-sm text-green-600 mt-1">Serás redirigido automáticamente...</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Información del Vehículo</h2>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Marca -->
                            <div>
                                <label for="marca" class="block text-sm font-medium text-gray-700 mb-2">
                                    Marca *
                                </label>
                                <input type="text" id="marca" name="marca" required
                                       value="<?php echo htmlspecialchars($_POST['marca'] ?? $vehiculo['marca']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: Toyota, Nissan, Hyundai">
                            </div>

                            <!-- Modelo -->
                            <div>
                                <label for="modelo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Modelo *
                                </label>
                                <input type="text" id="modelo" name="modelo" required
                                       value="<?php echo htmlspecialchars($_POST['modelo'] ?? $vehiculo['modelo']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: Hiace, Urvan, H1">
                            </div>

                            <!-- Placa -->
                            <div>
                                <label for="placa" class="block text-sm font-medium text-gray-700 mb-2">
                                    Placa *
                                </label>
                                <input type="text" id="placa" name="placa" required
                                       value="<?php echo htmlspecialchars($_POST['placa'] ?? $vehiculo['placa']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: ABC-123"
                                       style="text-transform: uppercase;"
                                       maxlength="10">
                                <p class="mt-1 text-sm text-gray-500">Formato: ABC-123 (sin espacios)</p>
                            </div>

                            <!-- Capacidad -->
                            <div>
                                <label for="capacidad" class="block text-sm font-medium text-gray-700 mb-2">
                                    Capacidad (personas) *
                                </label>
                                <input type="number" id="capacidad" name="capacidad" min="1" max="50" required
                                       value="<?php echo htmlspecialchars($_POST['capacidad'] ?? $vehiculo['capacidad']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: 15">
                                <p class="mt-1 text-sm text-gray-500">Número máximo de pasajeros</p>
                            </div>
                        </div>

                        <!-- Características -->
                        <div>
                            <label for="caracteristicas" class="block text-sm font-medium text-gray-700 mb-2">
                                Características
                            </label>
                            <textarea id="caracteristicas" name="caracteristicas" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Ej: Aire acondicionado, GPS, WiFi, asientos reclinables..."><?php echo htmlspecialchars($_POST['caracteristicas'] ?? $vehiculo['caracteristicas'] ?? ''); ?></textarea>
                            <p class="mt-1 text-sm text-gray-500">Describe las características especiales del vehículo</p>
                        </div>

                        <!-- Chofer -->
                        <div>
                            <label for="chofer_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Chofer Asignado
                            </label>
                            <select id="chofer_id" name="chofer_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Sin chofer asignado</option>
                                <?php foreach ($choferes_disponibles as $chofer): ?>
                                    <option value="<?php echo $chofer['id_chofer']; ?>"
                                            <?php echo (($_POST['chofer_id'] ?? $vehiculo['id_chofer']) == $chofer['id_chofer']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '')); ?>
                                        <?php if ($chofer['telefono']): ?>
                                            - <?php echo htmlspecialchars($chofer['telefono']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                <?php if (empty($choferes_disponibles)): ?>
                                    No hay choferes disponibles.
                                <?php else: ?>
                                    Solo se muestran choferes disponibles o el actualmente asignado
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Vista previa -->
                        <div id="vista-previa" class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Vista Previa:</h3>
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-car text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900" id="preview-nombre">-</div>
                                    <div class="text-sm text-gray-500" id="preview-placa">-</div>
                                    <div class="text-sm text-gray-500" id="preview-capacidad">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex">
                                <i class="fas fa-info-circle text-blue-600 mr-3 mt-1"></i>
                                <div>
                                    <h3 class="text-sm font-medium text-blue-800">Información</h3>
                                    <div class="text-sm text-blue-700 mt-1">
                                        <p>• Los cambios se aplicarán inmediatamente</p>
                                        <p>• Si cambias el chofer, se actualizarán las asignaciones automáticamente</p>
                                        <p>• La placa debe ser única en el sistema</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex gap-4 pt-6 border-t border-gray-200">
                            <button type="submit" 
                                    class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium">
                                <i class="fas fa-save mr-2"></i>Guardar Cambios
                            </button>
                            <a href="ver.php?id=<?php echo $id_vehiculo; ?>" 
                               class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors font-medium text-center">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para actualizar vista previa
        function actualizarVistaPrevia() {
            const marca = document.getElementById('marca').value;
            const modelo = document.getElementById('modelo').value;
            const placa = document.getElementById('placa').value;
            const capacidad = document.getElementById('capacidad').value;
            
            document.getElementById('preview-nombre').textContent = 
                (marca && modelo) ? `${marca} ${modelo}` : 'Marca y Modelo';
            
            document.getElementById('preview-placa').textContent = 
                placa ? `Placa: ${placa}` : 'Placa: -';
            
            document.getElementById('preview-capacidad').textContent = 
                capacidad ? `Capacidad: ${capacidad} personas` : 'Capacidad: -';
        }

        // Formatear placa en tiempo real
        document.getElementById('placa').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
            actualizarVistaPrevia();
        });

        // Validar capacidad
        document.getElementById('capacidad').addEventListener('input', function(e) {
            const value = parseInt(e.target.value);
            if (value > 50) {
                e.target.value = 50;
            }
            if (value < 1) {
                e.target.value = 1;
            }
            actualizarVistaPrevia();
        });

        // Eventos para vista previa
        document.getElementById('marca').addEventListener('input', actualizarVistaPrevia);
        document.getElementById('modelo').addEventListener('input', actualizarVistaPrevia);

        // Validación de formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const marca = document.getElementById('marca').value.trim();
            const modelo = document.getElementById('modelo').value.trim();
            const placa = document.getElementById('placa').value.trim();
            const capacidad = parseInt(document.getElementById('capacidad').value);

            if (!marca || !modelo || !placa) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios');
                return;
            }

            if (capacidad < 1 || capacidad > 50) {
                e.preventDefault();
                alert('La capacidad debe estar entre 1 y 50 personas');
                return;
            }

            if (placa.length < 3) {
                e.preventDefault();
                alert('La placa debe tener al menos 3 caracteres');
                return;
            }

            // Confirmar cambios
            if (!confirm('¿Estás seguro de que deseas guardar los cambios?')) {
                e.preventDefault();
            }
        });

        // Inicializar vista previa
        actualizarVistaPrevia();
    </script>
</body>
</html>
