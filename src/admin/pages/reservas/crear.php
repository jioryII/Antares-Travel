<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Nueva Reserva";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $connection = getConnection();
        $connection->beginTransaction();
        
        // Datos de la reserva
        $id_usuario = intval($_POST['id_usuario']);
        $id_tour = intval($_POST['id_tour']);
        $fecha_tour = $_POST['fecha_tour'];
        $observaciones = $_POST['observaciones'] ?? '';
        $origen_reserva = $_POST['origen_reserva'] ?? 'Presencial';
        
        // Obtener precio del tour
        $tour_sql = "SELECT precio FROM tours WHERE id_tour = ?";
        $tour_stmt = $connection->prepare($tour_sql);
        $tour_stmt->execute([$id_tour]);
        $tour = $tour_stmt->fetch();
        
        if (!$tour) {
            throw new Exception("Tour no encontrado");
        }
        
        // Calcular monto total basado en número de pasajeros
        $pasajeros = $_POST['pasajeros'] ?? [];
        $num_pasajeros = count($pasajeros);
        $monto_total = $tour['precio'] * $num_pasajeros;
        
        // Insertar reserva
        $reserva_sql = "INSERT INTO reservas (id_usuario, id_tour, fecha_tour, monto_total, observaciones, origen_reserva, estado) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')";
        $reserva_stmt = $connection->prepare($reserva_sql);
        $reserva_stmt->execute([$id_usuario, $id_tour, $fecha_tour, $monto_total, $observaciones, $origen_reserva]);
        
        $id_reserva = $connection->lastInsertId();
        
        // Insertar pasajeros
        $pasajero_sql = "INSERT INTO pasajeros (id_reserva, nombre, apellido, dni_pasaporte, nacionalidad, telefono, tipo_pasajero) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pasajero_stmt = $connection->prepare($pasajero_sql);
        
        foreach ($pasajeros as $pasajero) {
            if (!empty($pasajero['nombre']) && !empty($pasajero['apellido']) && !empty($pasajero['dni_pasaporte'])) {
                $pasajero_stmt->execute([
                    $id_reserva,
                    $pasajero['nombre'],
                    $pasajero['apellido'],
                    $pasajero['dni_pasaporte'],
                    $pasajero['nacionalidad'] ?? '',
                    $pasajero['telefono'] ?? '',
                    $pasajero['tipo_pasajero'] ?? 'Adulto'
                ]);
            }
        }
        
        $connection->commit();
        
        // Redireccionar a la página de la reserva creada
        header("Location: ver.php?id=$id_reserva&success=1");
        exit;
        
    } catch (Exception $e) {
        $connection->rollback();
        $error = "Error al crear la reserva: " . $e->getMessage();
    }
}

// Obtener datos para los formularios
try {
    $connection = getConnection();
    
    // Obtener usuarios
    $usuarios_sql = "SELECT id_usuario, nombre, email FROM usuarios ORDER BY nombre ASC";
    $usuarios = $connection->query($usuarios_sql)->fetchAll();
    
    // Obtener tours activos
    $tours_sql = "SELECT t.id_tour, t.titulo, t.precio, t.duracion, r.nombre_region 
                  FROM tours t 
                  LEFT JOIN regiones r ON t.id_region = r.id_region 
                  ORDER BY t.titulo ASC";
    $tours = $connection->query($tours_sql)->fetchAll();
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
    $usuarios = [];
    $tours = [];
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
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .pasajero-card {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        .pasajero-card.filled {
            border: 2px solid #3b82f6;
            background-color: #eff6ff;
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
                            <nav class="flex mb-3" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    <li><a href="index.php" class="text-blue-600 hover:text-blue-800">Reservas</a></li>
                                    <li><span class="text-gray-500">/</span></li>
                                    <li><span class="text-gray-500">Nueva Reserva</span></li>
                                </ol>
                            </nav>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-plus-circle text-blue-600 mr-3"></i>Nueva Reserva
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600">Crear una nueva reserva para un cliente</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="index.php" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mostrar errores -->
                <?php if (isset($error)): ?>
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
                <form method="POST" id="formNuevaReserva" class="space-y-6">
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <!-- Columna Principal -->
                        <div class="xl:col-span-2 space-y-6">
                            <!-- Información Básica -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-info-circle mr-3"></i>Información Básica
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Cliente *</label>
                                            <select name="id_usuario" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">Seleccionar cliente...</option>
                                                <?php foreach ($usuarios as $usuario): ?>
                                                    <option value="<?php echo $usuario['id_usuario']; ?>">
                                                        <?php echo htmlspecialchars($usuario['nombre'] . ' (' . $usuario['email'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tour *</label>
                                            <select name="id_tour" id="tourSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">Seleccionar tour...</option>
                                                <?php foreach ($tours as $tour): ?>
                                                    <option value="<?php echo $tour['id_tour']; ?>" 
                                                            data-precio="<?php echo $tour['precio']; ?>"
                                                            data-duracion="<?php echo htmlspecialchars($tour['duracion']); ?>"
                                                            data-region="<?php echo htmlspecialchars($tour['nombre_region']); ?>">
                                                        <?php echo htmlspecialchars($tour['titulo']); ?> - <?php echo formatCurrency($tour['precio']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha del Tour *</label>
                                            <input type="date" name="fecha_tour" required 
                                                   min="<?php echo date('Y-m-d'); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Origen de Reserva</label>
                                            <select name="origen_reserva" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="Presencial">Presencial</option>
                                                <option value="Web">Web</option>
                                                <option value="Llamada">Llamada</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                        <textarea name="observaciones" rows="3" 
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                  placeholder="Notas adicionales sobre la reserva..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Tour Seleccionado -->
                            <div id="tourInfo" class="form-section bg-white rounded-lg shadow-lg overflow-hidden hidden">
                                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-map-marked-alt mr-3"></i>Información del Tour
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Precio por Persona</label>
                                            <p id="tourPrecio" class="mt-1 text-lg font-semibold text-green-600">-</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Duración</label>
                                            <p id="tourDuracion" class="mt-1 text-gray-900">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pasajeros -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-white flex items-center">
                                            <i class="fas fa-users mr-3"></i>Pasajeros
                                        </h3>
                                        <button type="button" onclick="agregarPasajero()" 
                                                class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-3 py-1 rounded-lg text-sm transition-colors">
                                            <i class="fas fa-plus mr-1"></i>Agregar Pasajero
                                        </button>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div id="pasajerosContainer">
                                        <!-- Los pasajeros se agregarán aquí dinámicamente -->
                                    </div>
                                    <div class="mt-4">
                                        <button type="button" onclick="agregarPasajero()" 
                                                class="w-full pasajero-card rounded-lg p-4 text-center text-gray-500 hover:text-blue-600 hover:border-blue-300 transition-colors">
                                            <i class="fas fa-plus text-2xl mb-2"></i>
                                            <p>Agregar Pasajero</p>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Lateral - Resumen -->
                        <div class="space-y-6">
                            <!-- Resumen de la Reserva -->
                            <div class="form-section bg-white rounded-lg shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-white flex items-center">
                                        <i class="fas fa-calculator mr-3"></i>Resumen
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Precio por Persona:</span>
                                            <span id="resumenPrecio" class="font-medium">S/ 0.00</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Número de Pasajeros:</span>
                                            <span id="resumenPasajeros" class="font-medium">0</span>
                                        </div>
                                        <div class="border-t pt-4">
                                            <div class="flex justify-between items-center">
                                                <span class="text-lg font-semibold text-gray-900">Total:</span>
                                                <span id="resumenTotal" class="text-2xl font-bold text-orange-600">S/ 0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-6 pt-6 border-t">
                                        <button type="submit" 
                                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                            <i class="fas fa-save mr-2"></i>Crear Reserva
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Ayuda -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <i class="fas fa-info-circle text-blue-400 mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-800">Información</h4>
                                        <ul class="text-sm text-blue-700 mt-2 space-y-1">
                                            <li>• Todos los campos marcados con * son obligatorios</li>
                                            <li>• Debe agregar al menos un pasajero</li>
                                            <li>• El precio total se calcula automáticamente</li>
                                            <li>• La reserva se creará en estado "Pendiente"</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let pasajeroCount = 0;
        let precioPorPersona = 0;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            agregarPasajero(); // Agregar el primer pasajero por defecto
        });

        // Manejar selección de tour
        document.getElementById('tourSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const tourInfo = document.getElementById('tourInfo');
            
            if (selectedOption.value) {
                precioPorPersona = parseFloat(selectedOption.dataset.precio);
                
                document.getElementById('tourPrecio').textContent = formatCurrency(precioPorPersona);
                document.getElementById('tourDuracion').textContent = selectedOption.dataset.duracion || '-';
                
                tourInfo.classList.remove('hidden');
            } else {
                tourInfo.classList.add('hidden');
                precioPorPersona = 0;
            }
            
            actualizarResumen();
        });

        function agregarPasajero() {
            pasajeroCount++;
            const container = document.getElementById('pasajerosContainer');
            
            const pasajeroDiv = document.createElement('div');
            pasajeroDiv.className = 'pasajero-card border rounded-lg p-4 mb-4';
            pasajeroDiv.id = `pasajero-${pasajeroCount}`;
            
            pasajeroDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-900">Pasajero #${pasajeroCount}</h4>
                    <button type="button" onclick="eliminarPasajero(${pasajeroCount})" 
                            class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][nombre]" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][apellido]" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">DNI/Pasaporte *</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][dni_pasaporte]" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onchange="validarPasajero(${pasajeroCount})">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="pasajeros[${pasajeroCount}][tipo_pasajero]" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Adulto">Adulto</option>
                            <option value="Niño">Niño</option>
                            <option value="Infante">Infante</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nacionalidad</label>
                        <input type="text" name="pasajeros[${pasajeroCount}][nacionalidad]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ej: Peruana">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="tel" name="pasajeros[${pasajeroCount}][telefono]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ej: +51 999 888 777">
                    </div>
                </div>
            `;
            
            container.appendChild(pasajeroDiv);
            actualizarResumen();
        }

        function eliminarPasajero(id) {
            const pasajeroDiv = document.getElementById(`pasajero-${id}`);
            if (pasajeroDiv) {
                pasajeroDiv.remove();
                actualizarResumen();
            }
        }

        function validarPasajero(id) {
            const pasajeroDiv = document.getElementById(`pasajero-${id}`);
            const inputs = pasajeroDiv.querySelectorAll('input[required]');
            let filled = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    filled = false;
                }
            });
            
            if (filled) {
                pasajeroDiv.classList.add('filled');
            } else {
                pasajeroDiv.classList.remove('filled');
            }
        }

        function actualizarResumen() {
            const numPasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card').length;
            const total = precioPorPersona * numPasajeros;
            
            document.getElementById('resumenPrecio').textContent = formatCurrency(precioPorPersona);
            document.getElementById('resumenPasajeros').textContent = numPasajeros;
            document.getElementById('resumenTotal').textContent = formatCurrency(total);
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN',
                minimumFractionDigits: 2
            }).format(amount);
        }

        // Validación del formulario
        document.getElementById('formNuevaReserva').addEventListener('submit', function(e) {
            const pasajeros = document.querySelectorAll('#pasajerosContainer .pasajero-card');
            
            if (pasajeros.length === 0) {
                e.preventDefault();
                alert('Debe agregar al menos un pasajero');
                return false;
            }

            // Validar que todos los pasajeros tengan datos básicos
            let valid = true;
            pasajeros.forEach(pasajero => {
                const inputs = pasajero.querySelectorAll('input[required]');
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.classList.add('border-red-500');
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });
            });

            if (!valid) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios de los pasajeros');
                return false;
            }
        });
    </script>
</body>
</html>
