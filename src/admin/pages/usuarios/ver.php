<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del usuario
$id_usuario = intval($_GET['id'] ?? 0);

if (!$id_usuario) {
    header('Location: index.php');
    exit;
}

try {
    $connection = getConnection();
    
    // Obtener datos básicos del usuario (sin tabla resenas que no existe)
    $usuario_sql = "SELECT u.*, 
                           COUNT(DISTINCT r.id_reserva) as total_reservas,
                           COALESCE(SUM(r.monto_total), 0) as total_gastado
                    FROM usuarios u
                    LEFT JOIN reservas r ON u.id_usuario = r.id_usuario
                    WHERE u.id_usuario = ?
                    GROUP BY u.id_usuario";
    
    $usuario_stmt = $connection->prepare($usuario_sql);
    $usuario_stmt->execute([$id_usuario]);
    $usuario = $usuario_stmt->fetch();
    
    if (!$usuario) {
        header('Location: index.php?error=Usuario no encontrado');
        exit;
    }
    
    // Agregar campos por defecto que no están en la tabla resenas
    $usuario['calificacion_promedio'] = 0;
    $usuario['total_resenas'] = 0;
    
    // Obtener historial de reservas
    $reservas_sql = "SELECT r.*, 
                            CONCAT('Tour ID: ', r.id_tour) as paquete_nombre,
                            NULL as imagen_principal,
                            1 as numero_personas,
                            CASE 
                                WHEN LOWER(r.estado) = 'pendiente' THEN 'bg-yellow-100 text-yellow-800'
                                WHEN LOWER(r.estado) = 'confirmada' THEN 'bg-green-100 text-green-800'
                                WHEN LOWER(r.estado) = 'cancelada' THEN 'bg-red-100 text-red-800'
                                WHEN LOWER(r.estado) = 'finalizada' THEN 'bg-blue-100 text-blue-800'
                                ELSE 'bg-gray-100 text-gray-800'
                            END as estado_clase,
                            r.fecha_tour as fecha_inicio
                     FROM reservas r
                     WHERE r.id_usuario = ?
                     ORDER BY r.fecha_reserva DESC
                     LIMIT 10";
    
    $reservas_stmt = $connection->prepare($reservas_sql);
    $reservas_stmt->execute([$id_usuario]);
    $reservas = $reservas_stmt->fetchAll();
    
    // Inicializar reseñas como array vacío (tabla no existe)
    $resenas = [];
    
    // Obtener actividad reciente 
    $actividad_sql = "SELECT 'reserva' as tipo, 
                             r.fecha_reserva as fecha,
                             CONCAT('Reserva #', r.id_reserva, ' - Tour ID: ', r.id_tour) as descripcion,
                             r.estado,
                             r.monto_total as monto
                      FROM reservas r
                      WHERE r.id_usuario = ?
                      ORDER BY fecha DESC
                      LIMIT 20";
    
    $actividad_stmt = $connection->prepare($actividad_sql);
    $actividad_stmt->execute([$id_usuario]);
    $actividad = $actividad_stmt->fetchAll();
    
    $page_title = "Usuario: " . ($usuario['nombre'] ?? $usuario['email']);
    
} catch (Exception $e) {
    $error = "Error al cargar datos del usuario: " . $e->getMessage();
    // Inicializar variables por defecto para evitar errores
    $usuario = [
        'id_usuario' => $id_usuario,
        'nombre' => '',
        'email' => '',
        'telefono' => '',
        'proveedor_oauth' => 'manual',
        'email_verificado' => 0,
        'creado_en' => date('Y-m-d H:i:s'),
        'actualizado_en' => '',
        'avatar_url' => '',
        'total_reservas' => 0,
        'total_gastado' => 0,
        'calificacion_promedio' => 0,
        'total_resenas' => 0
    ];
    $reservas = [];
    $resenas = [];
    $actividad = [];
    $page_title = "Error - Usuario";
}

function getProveedorClass($proveedor) {
    $classes = [
        'manual' => 'bg-blue-100 text-blue-800',
        'google' => 'bg-red-100 text-red-800',
        'apple' => 'bg-gray-100 text-gray-800',
        'microsoft' => 'bg-green-100 text-green-800'
    ];
    return $classes[$proveedor] ?? 'bg-gray-100 text-gray-800';
}

function getProveedorIcon($proveedor) {
    $icons = [
        'manual' => 'fas fa-user',
        'google' => 'fab fa-google',
        'apple' => 'fab fa-apple',
        'microsoft' => 'fab fa-microsoft'
    ];
    return $icons[$proveedor] ?? 'fas fa-user';
}

function getEstadoIcon($estado) {
    $estado_lower = strtolower($estado);
    $icons = [
        'pendiente' => 'fas fa-clock',
        'confirmada' => 'fas fa-check',
        'cancelada' => 'fas fa-times',
        'finalizada' => 'fas fa-check-circle'
    ];
    return $icons[$estado_lower] ?? 'fas fa-question';
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
        .timeline-item {
            position: relative;
            padding-left: 3rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-item:last-child::before {
            bottom: 1rem;
        }
        .timeline-dot {
            position: absolute;
            left: 0.75rem;
            top: 0.5rem;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background: #3b82f6;
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
                                    <i class="fas fa-user text-blue-600 mr-3"></i>Detalles del Usuario
                                </h1>
                            </div>
                            <p class="text-sm lg:text-base text-gray-600">Información completa y actividad del usuario</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="editar.php?id=<?php echo $usuario['id_usuario']; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-edit mr-2"></i>Editar Usuario
                            </a>
                            <?php if (!$usuario['email_verificado']): ?>
                                <button onclick="verificarEmail(<?php echo $usuario['id_usuario']; ?>)" 
                                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-check mr-2"></i>Verificar Email
                                </button>
                            <?php endif; ?>
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

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Información del Usuario -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                            <div class="text-center mb-6">
                                <?php if ($usuario['avatar_url']): ?>
                                    <img class="h-24 w-24 rounded-full mx-auto mb-4" src="<?php echo htmlspecialchars($usuario['avatar_url']); ?>" alt="">
                                <?php else: ?>
                                    <div class="h-24 w-24 rounded-full bg-blue-600 flex items-center justify-center mx-auto mb-4">
                                        <span class="text-white font-bold text-2xl">
                                            <?php echo strtoupper(substr($usuario['nombre'] ?? $usuario['email'], 0, 1)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <h2 class="text-xl font-bold text-gray-900">
                                    <?php echo htmlspecialchars($usuario['nombre'] ?? 'Sin nombre'); ?>
                                </h2>
                                <p class="text-gray-600"><?php echo htmlspecialchars($usuario['email']); ?></p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">ID Usuario:</span>
                                    <span class="text-sm text-gray-900">#<?php echo $usuario['id_usuario']; ?></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Teléfono:</span>
                                    <span class="text-sm text-gray-900">
                                        <?php echo $usuario['telefono'] ? htmlspecialchars($usuario['telefono']) : 'No registrado'; ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Fecha de Nacimiento:</span>
                                    <span class="text-sm text-gray-900">
                                        <?php echo isset($usuario['fecha_nacimiento']) && $usuario['fecha_nacimiento'] ? formatDate($usuario['fecha_nacimiento'], 'd/m/Y') : 'No registrada'; ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Género:</span>
                                    <span class="text-sm text-gray-900">
                                        <?php echo isset($usuario['genero']) && $usuario['genero'] ? ucfirst($usuario['genero']) : 'No especificado'; ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">País:</span>
                                    <span class="text-sm text-gray-900">
                                        <?php echo isset($usuario['pais']) && $usuario['pais'] ? htmlspecialchars($usuario['pais']) : 'No especificado'; ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Tipo de registro:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getProveedorClass($usuario['proveedor_oauth']); ?>">
                                        <i class="<?php echo getProveedorIcon($usuario['proveedor_oauth']); ?> mr-1"></i>
                                        <?php echo ucfirst($usuario['proveedor_oauth']); ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Estado del email:</span>
                                    <?php if ($usuario['email_verificado']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Verificado
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>Pendiente
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Registrado:</span>
                                    <span class="text-sm text-gray-900"><?php echo formatDate($usuario['creado_en'], 'd/m/Y H:i'); ?></span>
                                </div>

                                <?php if (isset($usuario['ultima_actividad']) && $usuario['ultima_actividad']): ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-500">Última actividad:</span>
                                        <span class="text-sm text-gray-900"><?php echo formatDate($usuario['ultima_actividad'], 'd/m/Y H:i'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Estadísticas del Usuario -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Estadísticas
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600"><?php echo number_format($usuario['total_reservas']); ?></div>
                                    <div class="text-sm text-gray-600">Reservas</div>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600"><?php echo formatCurrency($usuario['total_gastado']); ?></div>
                                    <div class="text-sm text-gray-600">Total gastado</div>
                                </div>
                                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                    <div class="text-2xl font-bold text-yellow-600"><?php echo number_format($usuario['total_resenas']); ?></div>
                                    <div class="text-sm text-gray-600">Reseñas</div>
                                </div>
                                <div class="text-center p-4 bg-purple-50 rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600">
                                        <?php echo $usuario['calificacion_promedio'] > 0 ? number_format($usuario['calificacion_promedio'], 1) : '—'; ?>
                                    </div>
                                    <div class="text-sm text-gray-600">Rating promedio</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad y Detalles -->
                    <div class="lg:col-span-2">
                        <!-- Pestañas -->
                        <div class="mb-6">
                            <nav class="flex space-x-8" aria-label="Tabs">
                                <button onclick="mostrarTab('reservas')" 
                                        class="tab-button active whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                        data-tab="reservas">
                                    <i class="fas fa-calendar-check mr-2"></i>Reservas
                                </button>
                                <button onclick="mostrarTab('resenas')" 
                                        class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                        data-tab="resenas">
                                    <i class="fas fa-star mr-2"></i>Reseñas
                                </button>
                                <button onclick="mostrarTab('actividad')" 
                                        class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                        data-tab="actividad">
                                    <i class="fas fa-history mr-2"></i>Actividad
                                </button>
                            </nav>
                        </div>

                        <!-- Contenido de Reservas -->
                        <div id="tab-reservas" class="tab-content">
                            <div class="bg-white rounded-lg shadow-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-calendar-check text-blue-600 mr-2"></i>Historial de Reservas
                                </h3>
                                <?php if (empty($reservas)): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500">No hay reservas registradas</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($reservas as $reserva): ?>
                                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex items-start space-x-4">
                                                        <?php if ($reserva['imagen_principal']): ?>
                                                            <img src="<?php echo htmlspecialchars($reserva['imagen_principal']); ?>" 
                                                                 alt="" class="h-16 w-16 rounded-lg object-cover">
                                                        <?php else: ?>
                                                            <div class="h-16 w-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                                                <i class="fas fa-image text-gray-400"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <h4 class="font-medium text-gray-900">
                                                                Reserva #<?php echo $reserva['id_reserva']; ?>
                                                            </h4>
                                                            <p class="text-sm text-gray-600">
                                                                <?php echo htmlspecialchars($reserva['paquete_nombre'] ?? 'Paquete eliminado'); ?>
                                                            </p>
                                                            <div class="flex items-center mt-2 space-x-4 text-sm text-gray-500">
                                                                <span>
                                                                    <i class="fas fa-calendar mr-1"></i>
                                                                    <?php echo formatDate($reserva['fecha_inicio'], 'd/m/Y'); ?>
                                                                </span>
                                                                <span>
                                                                    <i class="fas fa-users mr-1"></i>
                                                                    <?php echo $reserva['numero_personas']; ?> personas
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $reserva['estado_clase']; ?>">
                                                            <i class="<?php echo getEstadoIcon($reserva['estado']); ?> mr-1"></i>
                                                            <?php echo ucfirst(strtolower($reserva['estado'])); ?>
                                                        </span>
                                                        <p class="text-lg font-semibold text-gray-900 mt-1">
                                                            <?php echo formatCurrency($reserva['monto_total']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($reservas) >= 10): ?>
                                        <div class="text-center mt-4">
                                            <a href="../reservas/index.php?usuario=<?php echo $usuario['id_usuario']; ?>" 
                                               class="text-blue-600 hover:text-blue-800 text-sm">
                                                Ver todas las reservas →
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contenido de Reseñas -->
                        <div id="tab-resenas" class="tab-content hidden">
                            <div class="bg-white rounded-lg shadow-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-star text-yellow-500 mr-2"></i>Reseñas del Usuario
                                </h3>
                                <?php if (empty($resenas)): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500">No hay reseñas registradas</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($resenas as $resena): ?>
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <div class="flex items-start justify-between mb-2">
                                                    <h4 class="font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($resena['paquete_nombre'] ?? 'Paquete eliminado'); ?>
                                                    </h4>
                                                    <div class="flex items-center">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star text-sm <?php echo $i <= $resena['calificacion'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                                        <?php endfor; ?>
                                                        <span class="ml-2 text-sm text-gray-600"><?php echo $resena['calificacion']; ?>/5</span>
                                                    </div>
                                                </div>
                                                <p class="text-gray-700 text-sm mb-2">
                                                    <?php echo htmlspecialchars($resena['comentario']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    <?php echo formatDate($resena['fecha_resena'], 'd/m/Y H:i'); ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contenido de Actividad -->
                        <div id="tab-actividad" class="tab-content hidden">
                            <div class="bg-white rounded-lg shadow-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-history text-gray-600 mr-2"></i>Actividad Reciente
                                </h3>
                                <?php if (empty($actividad)): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500">No hay actividad registrada</p>
                                    </div>
                                <?php else: ?>
                                    <div class="flow-root">
                                        <ul class="-mb-8">
                                            <?php foreach ($actividad as $index => $item): ?>
                                                <li class="timeline-item">
                                                    <div class="timeline-dot"></div>
                                                    <div class="min-w-0 flex-1">
                                                        <div>
                                                            <div class="text-sm">
                                                                <span class="font-medium text-gray-900">
                                                                    <?php echo htmlspecialchars($item['descripcion']); ?>
                                                                </span>
                                                            </div>
                                                            <div class="mt-1 text-xs text-gray-500">
                                                                <i class="fas fa-clock mr-1"></i>
                                                                <?php echo formatDate($item['fecha'], 'd/m/Y H:i'); ?>
                                                                <?php if ($item['monto']): ?>
                                                                    <span class="ml-2 font-medium">
                                                                        <?php echo formatCurrency($item['monto']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mostrarTab(tabName) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remover clase activa de todos los botones
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            });
            
            // Mostrar contenido seleccionado
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            // Activar botón seleccionado
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            activeButton.classList.add('active', 'border-blue-500', 'text-blue-600');
            activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        }

        function verificarEmail(id) {
            if (confirm('¿Deseas marcar el email de este usuario como verificado?')) {
                fetch('verificar_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id_usuario: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al verificar email: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al verificar email');
                });
            }
        }

        // Inicializar primera pestaña
        document.addEventListener('DOMContentLoaded', function() {
            mostrarTab('reservas');
        });
    </script>
</body>
</html>
