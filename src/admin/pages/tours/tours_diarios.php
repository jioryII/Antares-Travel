<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

$admin = getCurrentAdmin();
$page_title = "Gestión de Tours Diarios";

// Si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fecha = $_POST['fecha'];
    $id_tour = $_POST['id_tour'];
    $id_guia = $_POST['id_guia'];
    $id_chofer = $_POST['id_chofer'];
    $id_vehiculo = $_POST['id_vehiculo'];
    $num_adultos = $_POST['num_adultos'] ?? 0;
    $num_ninos = $_POST['num_ninos'] ?? 0;
    $hora_salida = $_POST['hora_salida'];
    $hora_retorno = $_POST['hora_retorno'];
    $observaciones = $_POST['observaciones'] ?? '';

    try {
        $connection = getConnection();
        
        // Iniciar transacción
        $connection->beginTransaction();

        // Insertar tour diario
        $sql = "INSERT INTO tours_diarios 
            (fecha, id_tour, id_guia, id_chofer, id_vehiculo, num_adultos, num_ninos, hora_salida, hora_retorno, observaciones) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$fecha, $id_tour, $id_guia, $id_chofer, $id_vehiculo, $num_adultos, $num_ninos, $hora_salida, $hora_retorno, $observaciones]);
        
        $tour_diario_id = $connection->lastInsertId();

        // Actualizar disponibilidad de guías (usando estructura real de la tabla)
        $sql_check_guia = "SELECT id_disponibilidad FROM disponibilidad_guias WHERE id_guia = ? AND fecha = ?";
        $stmt_check = $connection->prepare($sql_check_guia);
        $stmt_check->execute([$id_guia, $fecha]);
        
        if ($stmt_check->fetch()) {
            $sql_update_guia = "UPDATE disponibilidad_guias SET estado = 'Ocupado' WHERE id_guia = ? AND fecha = ?";
            $stmt_update = $connection->prepare($sql_update_guia);
            $stmt_update->execute([$id_guia, $fecha]);
        } else {
            $sql_insert_guia = "INSERT INTO disponibilidad_guias (id_guia, fecha, estado) VALUES (?, ?, 'Ocupado')";
            $stmt_insert = $connection->prepare($sql_insert_guia);
            $stmt_insert->execute([$id_guia, $fecha]);
        }

        // Actualizar disponibilidad de vehículos (usando estructura real de la tabla)
        $sql_check_vehiculo = "SELECT id_disponibilidad FROM disponibilidad_vehiculos WHERE id_vehiculo = ? AND fecha = ?";
        $stmt_check = $connection->prepare($sql_check_vehiculo);
        $stmt_check->execute([$id_vehiculo, $fecha]);
        
        if ($stmt_check->fetch()) {
            $sql_update_vehiculo = "UPDATE disponibilidad_vehiculos SET estado = 'Ocupado' WHERE id_vehiculo = ? AND fecha = ?";
            $stmt_update = $connection->prepare($sql_update_vehiculo);
            $stmt_update->execute([$id_vehiculo, $fecha]);
        } else {
            $sql_insert_vehiculo = "INSERT INTO disponibilidad_vehiculos (id_vehiculo, fecha, estado) VALUES (?, ?, 'Ocupado')";
            $stmt_insert = $connection->prepare($sql_insert_vehiculo);
            $stmt_insert->execute([$id_vehiculo, $fecha]);
        }

        // Nota: No hay tabla separada para disponibilidad de choferes, 
        // la disponibilidad se maneja a través de la tabla tours_diarios

        // Confirmar transacción
        $connection->commit();
        
        // Log de auditoría
        logActivity($admin['id_admin'], 'INSERT', 'tours_diarios', $tour_diario_id, "Tour diario registrado para fecha: $fecha");
        
        setFlashMessage('success', '✅ Tour diario registrado correctamente.');
        
    } catch (Exception $e) {
        $connection->rollback();
        setFlashMessage('error', '❌ Error al registrar el tour diario: ' . $e->getMessage());
    }
}

// Obtener información de tours para cargar horas por defecto
$tours_info = [];
try {
    $connection = getConnection();
    $sql = "SELECT id_tour, titulo, hora_salida, hora_llegada FROM tours ORDER BY titulo";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $tours = $stmt->fetchAll();
    
    foreach ($tours as $tour) {
        $tours_info[$tour['id_tour']] = [
            'titulo' => $tour['titulo'],
            'hora_salida' => $tour['hora_salida'],
            'hora_llegada' => $tour['hora_llegada']
        ];
    }
} catch (Exception $e) {
    $tours = [];
}

// Obtener tours diarios recientes
try {
    $sql = "SELECT td.*, t.titulo as tour_titulo, 
                   CONCAT(g.nombre, ' ', g.apellido) as guia_nombre,
                   CONCAT(c.nombre, ' ', c.apellido) as chofer_nombre,
                   CONCAT(v.marca, ' ', v.modelo, ' - ', v.placa) as vehiculo_info
            FROM tours_diarios td
            LEFT JOIN tours t ON td.id_tour = t.id_tour
            LEFT JOIN guias g ON td.id_guia = g.id_guia
            LEFT JOIN choferes c ON td.id_chofer = c.id_chofer
            LEFT JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo
            ORDER BY td.fecha DESC, td.hora_salida DESC
            LIMIT 10";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $tours_diarios_recientes = $stmt->fetchAll();
} catch (Exception $e) {
    $tours_diarios_recientes = [];
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
        .no-disponible { color: #f59e0b; font-style: italic; }
        .disponible { color: #10b981; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <br><br><br>
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-6 lg:mb-8">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo $page_title; ?></h1>
                    <p class="text-gray-600 mt-1 text-sm lg:text-base">Registra y gestiona tours diarios con asignación de recursos</p>
                </div>

                <!-- Mensajes Flash -->
                <?php if ($success_msg = getFlashMessage('success')): ?>
                    <div class="mb-4 lg:mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_msg = getFlashMessage('error')): ?>
                    <div class="mb-4 lg:mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
                    <!-- Formulario de Registro -->
                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <h2 class="text-lg lg:text-xl font-semibold text-gray-900 mb-4 lg:mb-6">
                            <i class="fas fa-calendar-plus text-blue-600 mr-2"></i>Registrar Nuevo Tour Diario
                        </h2>

                    <form method="POST" class="space-y-4">
                        <!-- Fecha del Tour -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del Tour</label>
                            <input type="date" name="fecha" id="fecha" onchange="cargarDisponibles()" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Seleccionar Tour -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tour</label>
                            <select name="id_tour" id="id_tour" onchange="cargarHorasTour()" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    required>
                                <option value="">Seleccione un tour</option>
                                <?php foreach ($tours as $tour): ?>
                                    <option value="<?php echo $tour['id_tour']; ?>">
                                        <?php echo htmlspecialchars($tour['titulo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Recursos (Guía, Chofer, Vehículo) -->
                        <div class="grid grid-cols-1 gap-4">
                            <!-- Guía -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Guía</label>
                                <select name="id_guia" id="id_guia" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                        required>
                                    <option value="">Seleccione una fecha primero</option>
                                </select>
                            </div>

                            <!-- Chofer -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chofer</label>
                                <select name="id_chofer" id="id_chofer" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                        required>
                                    <option value="">Seleccione una fecha primero</option>
                                </select>
                            </div>

                            <!-- Vehículo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vehículo</label>
                                <select name="id_vehiculo" id="id_vehiculo" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                        required>
                                    <option value="">Seleccione una fecha primero</option>
                                </select>
                            </div>
                        </div>

                        <!-- Número de participantes -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Adultos</label>
                                <input type="number" name="num_adultos" min="0" value="0" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Niños</label>
                                <input type="number" name="num_ninos" min="0" value="0" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                        </div>

                        <!-- Horarios -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Salida</label>
                                <input type="time" name="hora_salida" id="hora_salida" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Retorno</label>
                                <input type="time" name="hora_retorno" id="hora_retorno" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                            <textarea name="observaciones" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                      placeholder="Observaciones adicionales..."></textarea>
                        </div>

                        <!-- Botón de envío -->
                        <div class="pt-4">
                            <button type="submit" 
                                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                                <i class="fas fa-save mr-2"></i>Registrar Tour Diario
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Tours Diarios Recientes -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">
                        <i class="fas fa-history text-green-600 mr-2"></i>Tours Diarios Recientes
                    </h2>

                    <?php if (empty($tours_diarios_recientes)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-calendar-times text-4xl mb-4"></i>
                            <p>No hay tours diarios registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($tours_diarios_recientes as $tour_diario): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($tour_diario['tour_titulo']); ?></h3>
                                        <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                            <?php echo formatDate($tour_diario['fecha'], 'd/m/Y'); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p><i class="fas fa-user-tie w-4"></i> <strong>Guía:</strong> <?php echo htmlspecialchars($tour_diario['guia_nombre']); ?></p>
                                        <p><i class="fas fa-id-card w-4"></i> <strong>Chofer:</strong> <?php echo htmlspecialchars($tour_diario['chofer_nombre']); ?></p>
                                        <p><i class="fas fa-car w-4"></i> <strong>Vehículo:</strong> <?php echo htmlspecialchars($tour_diario['vehiculo_info']); ?></p>
                                        <p><i class="fas fa-users w-4"></i> <strong>Participantes:</strong> <?php echo $tour_diario['num_adultos']; ?> adultos, <?php echo $tour_diario['num_ninos']; ?> niños</p>
                                        <p><i class="fas fa-clock w-4"></i> <strong>Horario:</strong> <?php echo $tour_diario['hora_salida']; ?> - <?php echo $tour_diario['hora_retorno']; ?></p>
                                    </div>
                                    
                                    <?php if (!empty($tour_diario['observaciones'])): ?>
                                        <div class="mt-2 p-2 bg-gray-100 rounded text-xs text-gray-600">
                                            <strong>Observaciones:</strong> <?php echo htmlspecialchars($tour_diario['observaciones']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Datos de tours con sus horarios
        const toursInfo = <?php echo json_encode($tours_info); ?>;
        
        function cargarDisponibles() {
            const fecha = document.getElementById("fecha").value;
            if (!fecha) {
                alert("Por favor, seleccione una fecha primero.");
                return;
            }

            // Mostrar loading en los selects
            const guiaSelect = document.getElementById("id_guia");
            const choferSelect = document.getElementById("id_chofer");
            const vehiculoSelect = document.getElementById("id_vehiculo");
            
            guiaSelect.innerHTML = "<option value=''>Cargando...</option>";
            choferSelect.innerHTML = "<option value=''>Cargando...</option>";
            vehiculoSelect.innerHTML = "<option value=''>Cargando...</option>";

            fetch(`tours_diarios_ajax.php?action=check_availability&fecha=${fecha}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    if (data.redirect_login) {
                        alert(`${data.error}\n\nSe redirigirá al login.`);
                        window.location.href = data.login_url || '../../auth/login.php';
                        return;
                    }
                    if (data.install_required) {
                        alert(`${data.error}\n\nSe redirigirá al instalador.`);
                        window.location.href = '../../install_tours_diarios.php';
                        return;
                    }
                    throw new Error(data.error || 'Error desconocido');
                }
                
                // Cargar guías
                guiaSelect.innerHTML = "";
                if (!data.data.guias || data.data.guias.length === 0) {
                    guiaSelect.innerHTML = "<option value=''>No hay guías disponibles</option>";
                } else {
                    data.data.guias.forEach(g => {
                        const option = document.createElement("option");
                        option.value = g.id_guia;
                        option.textContent = `${g.nombre} ${g.apellido}`;
                        
                        if (g.estado === 'Ocupado') {
                            option.classList.add("no-disponible");
                            option.textContent += " (No disponible)";
                        } else {
                            option.classList.add("disponible");
                        }
                        
                        guiaSelect.appendChild(option);
                    });
                }

                // Cargar choferes
                choferSelect.innerHTML = "";
                if (!data.data.choferes || data.data.choferes.length === 0) {
                    choferSelect.innerHTML = "<option value=''>No hay choferes disponibles</option>";
                } else {
                    data.data.choferes.forEach(c => {
                        const option = document.createElement("option");
                        option.value = c.id_chofer;
                        option.textContent = `${c.nombre} ${c.apellido}`;
                        
                        if (c.estado === 'Ocupado') {
                            option.classList.add("no-disponible");
                            option.textContent += " (No disponible)";
                        } else {
                            option.classList.add("disponible");
                        }
                        
                        choferSelect.appendChild(option);
                    });
                }

                // Cargar vehículos
                vehiculoSelect.innerHTML = "";
                if (!data.data.vehiculos || data.data.vehiculos.length === 0) {
                    vehiculoSelect.innerHTML = "<option value=''>No hay vehículos disponibles</option>";
                } else {
                    data.data.vehiculos.forEach(v => {
                        const option = document.createElement("option");
                        option.value = v.id_vehiculo;
                        option.textContent = `[${v.placa}] ${v.marca} ${v.modelo}`;
                        
                        if (v.estado === 'Ocupado') {
                            option.classList.add("no-disponible");
                            option.textContent += " (No disponible)";
                        } else {
                            option.classList.add("disponible");
                        }
                        
                        vehiculoSelect.appendChild(option);
                    });
                }
                
                // Debug info si está habilitado
                if (data.debug) {
                    console.log('Disponibilidad cargada:', data.debug);
                }
            })
            .catch(error => {
                console.error("Error al cargar disponibilidad:", error);
                
                let errorMessage = "Error al cargar la disponibilidad. ";
                if (error.message.includes('HTTP error')) {
                    errorMessage += "Error de conexión con el servidor.";
                } else if (error.message.includes('tabla')) {
                    errorMessage += "Las tablas necesarias no existen. ¿Desea instalar el módulo?";
                    if (confirm(errorMessage)) {
                        window.location.href = '../../install_tours_diarios.php';
                        return;
                    }
                } else {
                    errorMessage += error.message || "Por favor, intente nuevamente.";
                }
                
                alert(errorMessage);
                
                // Restaurar estado inicial en caso de error
                guiaSelect.innerHTML = "<option value=''>Error al cargar</option>";
                choferSelect.innerHTML = "<option value=''>Error al cargar</option>";
                vehiculoSelect.innerHTML = "<option value=''>Error al cargar</option>";
            });
        }
        
        function cargarHorasTour() {
            const tourId = document.getElementById("id_tour").value;
            if (toursInfo[tourId]) {
                document.getElementById("hora_salida").value = toursInfo[tourId].hora_salida || '';
                document.getElementById("hora_retorno").value = toursInfo[tourId].hora_llegada || '';
            } else {
                document.getElementById("hora_salida").value = '';
                document.getElementById("hora_retorno").value = '';
            }
        }
        
        // Cargar horas al iniciar si ya hay un tour seleccionado
        document.addEventListener("DOMContentLoaded", function() {
            const tourSelect = document.getElementById("id_tour");
            if (tourSelect.value) {
                cargarHorasTour();
            }
        });
    </script>
</body>
</html>
