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

try {
    $connection = getConnection();
    
    // Obtener datos de la oferta
    $oferta_sql = "SELECT o.*,
                          CONCAT(a.nombre) as creado_por_nombre,
                          COALESCE(uso_stats.total_usos, 0) as total_usos,
                          COALESCE(uso_stats.total_descuento, 0) as total_descuento,
                          COALESCE(tours_stats.tours_aplicables, 0) as tours_aplicables,
                          COALESCE(usuarios_stats.usuarios_aplicables, 0) as usuarios_aplicables
                   FROM ofertas o 
                   LEFT JOIN administradores a ON o.creado_por = a.id_admin
                   LEFT JOIN (
                       SELECT id_oferta, 
                              COUNT(*) as total_usos,
                              SUM(monto_descuento) as total_descuento
                       FROM historial_uso_ofertas 
                       GROUP BY id_oferta
                   ) uso_stats ON o.id_oferta = uso_stats.id_oferta
                   LEFT JOIN (
                       SELECT id_oferta, COUNT(*) as tours_aplicables
                       FROM ofertas_tours 
                       GROUP BY id_oferta
                   ) tours_stats ON o.id_oferta = tours_stats.id_oferta
                   LEFT JOIN (
                       SELECT id_oferta, COUNT(*) as usuarios_aplicables
                       FROM ofertas_usuarios 
                       GROUP BY id_oferta
                   ) usuarios_stats ON o.id_oferta = usuarios_stats.id_oferta
                   WHERE o.id_oferta = ?";
    
    $stmt = $connection->prepare($oferta_sql);
    $stmt->execute([$id_oferta]);
    $oferta = $stmt->fetch();
    
    if (!$oferta) {
        header("Location: index.php");
        exit;
    }
    
    // Obtener tours específicos si aplica
    $tours_especificos = [];
    if ($oferta['aplicable_a'] === 'Tours_Especificos') {
        $tours_sql = "SELECT t.id_tour, t.titulo, t.precio 
                     FROM ofertas_tours ot 
                     JOIN tours t ON ot.id_tour = t.id_tour 
                     WHERE ot.id_oferta = ? 
                     ORDER BY t.titulo";
        $tours_stmt = $connection->prepare($tours_sql);
        $tours_stmt->execute([$id_oferta]);
        $tours_especificos = $tours_stmt->fetchAll();
    }
    
    // Obtener usuarios específicos si aplica
    $usuarios_especificos = [];
    if ($oferta['aplicable_a'] === 'Usuarios_Especificos') {
        $usuarios_sql = "SELECT u.id_usuario, u.nombre, u.email 
                        FROM ofertas_usuarios ou 
                        JOIN usuarios u ON ou.id_usuario = u.id_usuario 
                        WHERE ou.id_oferta = ? 
                        ORDER BY u.nombre";
        $usuarios_stmt = $connection->prepare($usuarios_sql);
        $usuarios_stmt->execute([$id_oferta]);
        $usuarios_especificos = $usuarios_stmt->fetchAll();
    }
    
    // Obtener historial de uso
    $historial_sql = "SELECT h.*, 
                             u.nombre as usuario_nombre, 
                             u.email as usuario_email,
                             r.id_reserva,
                             t.titulo as tour_titulo
                      FROM historial_uso_ofertas h
                      LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
                      LEFT JOIN reservas r ON h.id_reserva = r.id_reserva
                      LEFT JOIN tours t ON r.id_tour = t.id_tour
                      WHERE h.id_oferta = ?
                      ORDER BY h.fecha_uso DESC
                      LIMIT 10";
    $historial_stmt = $connection->prepare($historial_sql);
    $historial_stmt->execute([$id_oferta]);
    $historial = $historial_stmt->fetchAll();
    
    $page_title = "Ver Oferta: " . $oferta['nombre'];
    
} catch (Exception $e) {
    $error = "Error al cargar la oferta: " . $e->getMessage();
    $page_title = "Error - Ofertas";
}

function formatearFecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

function formatearMonto($monto) {
    return 'S/ ' . number_format($monto, 2);
}

function getEstadoClass($estado) {
    switch($estado) {
        case 'Activa': return 'bg-green-100 text-green-800';
        case 'Pausada': return 'bg-yellow-100 text-yellow-800';
        case 'Finalizada': return 'bg-gray-100 text-gray-800';
        case 'Borrador': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getEstadoIcon($estado) {
    switch($estado) {
        case 'Activa': return 'fas fa-check-circle';
        case 'Pausada': return 'fas fa-pause-circle';
        case 'Finalizada': return 'fas fa-times-circle';
        case 'Borrador': return 'fas fa-edit';
        default: return 'fas fa-question-circle';
    }
}

function getTipoOfertaClass($tipo) {
    switch($tipo) {
        case 'Porcentaje': return 'bg-purple-100 text-purple-800';
        case 'Monto_Fijo': return 'bg-indigo-100 text-indigo-800';
        case 'Precio_Especial': return 'bg-pink-100 text-pink-800';
        case '2x1': return 'bg-orange-100 text-orange-800';
        case 'Combo': return 'bg-teal-100 text-teal-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function estaVigente($fecha_inicio, $fecha_fin, $estado) {
    $ahora = date('Y-m-d H:i:s');
    return $estado === 'Activa' && $fecha_inicio <= $ahora && $fecha_fin >= $ahora;
}
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
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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
                <?php if (isset($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-red-800 font-medium">Error</h3>
                                <p class="text-red-700 mt-1"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                    <a href="index.php" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a Lista
                    </a>
                <?php else: ?>
                    
                    <!-- Encabezado -->
                    <div class="mb-6 lg:mb-8">
                        <br class="hidden lg:block"><br class="hidden lg:block"><br class="hidden lg:block">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <?php if ($oferta['imagen_banner']): ?>
                                        <img src="../../<?php echo htmlspecialchars($oferta['imagen_banner']); ?>" 
                                             alt="Banner" 
                                             class="w-16 h-16 rounded-lg object-cover mr-4">
                                    <?php else: ?>
                                        <div class="w-16 h-16 rounded-lg bg-red-100 flex items-center justify-center mr-4">
                                            <i class="fas fa-tag text-red-600 text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h1 class="text-xl lg:text-2xl font-bold text-gray-900">
                                            <?php echo htmlspecialchars($oferta['nombre']); ?>
                                            <?php if ($oferta['destacada']): ?>
                                                <i class="fas fa-star text-yellow-500 ml-2" title="Oferta destacada"></i>
                                            <?php endif; ?>
                                        </h1>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getEstadoClass($oferta['estado']); ?>">
                                                <i class="<?php echo getEstadoIcon($oferta['estado']); ?> mr-1"></i>
                                                <?php echo $oferta['estado']; ?>
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getTipoOfertaClass($oferta['tipo_oferta']); ?>">
                                                <?php echo str_replace('_', ' ', $oferta['tipo_oferta']); ?>
                                            </span>
                                            <?php if (estaVigente($oferta['fecha_inicio'], $oferta['fecha_fin'], $oferta['estado'])): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-circle text-green-400 mr-1" style="font-size: 6px;"></i>
                                                    Vigente
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($oferta['descripcion']): ?>
                                    <p class="text-gray-600 mt-2"><?php echo nl2br(htmlspecialchars($oferta['descripcion'])); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-2">
                                <a href="index.php" class="inline-flex items-center justify-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver a Lista
                                </a>
                                <a href="editar.php?id=<?php echo $oferta['id_oferta']; ?>" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                    <i class="fas fa-edit mr-2"></i>Editar
                                </a>
                                <?php if ($oferta['estado'] === 'Activa'): ?>
                                    <button onclick="pausarOferta(<?php echo $oferta['id_oferta']; ?>)" class="inline-flex items-center justify-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm">
                                        <i class="fas fa-pause mr-2"></i>Pausar
                                    </button>
                                <?php elseif ($oferta['estado'] === 'Pausada'): ?>
                                    <button onclick="activarOferta(<?php echo $oferta['id_oferta']; ?>)" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                        <i class="fas fa-play mr-2"></i>Activar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas rápidas -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 stat-card">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100">
                                    <i class="fas fa-chart-line text-blue-600 text-lg"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Usos</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($oferta['total_usos']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 stat-card">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100">
                                    <i class="fas fa-coins text-green-600 text-lg"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Ahorrado</p>
                                    <p class="text-2xl font-semibold text-green-600"><?php echo formatearMonto($oferta['total_descuento']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 stat-card">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100">
                                    <i class="fas fa-users text-purple-600 text-lg"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Aplicabilidad</p>
                                    <p class="text-sm font-semibold text-gray-900"><?php echo str_replace('_', ' ', $oferta['aplicable_a']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 stat-card">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-orange-100">
                                    <i class="fas fa-percentage text-orange-600 text-lg"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Descuento</p>
                                    <p class="text-lg font-semibold text-orange-600">
                                        <?php if ($oferta['tipo_oferta'] === 'Porcentaje'): ?>
                                            <?php echo $oferta['valor_descuento']; ?>%
                                        <?php elseif ($oferta['tipo_oferta'] === 'Monto_Fijo'): ?>
                                            -<?php echo formatearMonto($oferta['valor_descuento']); ?>
                                        <?php elseif ($oferta['tipo_oferta'] === 'Precio_Especial'): ?>
                                            <?php echo formatearMonto($oferta['precio_especial']); ?>
                                        <?php else: ?>
                                            <?php echo $oferta['tipo_oferta']; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Información detallada -->
                        <div class="space-y-6">
                            <!-- Detalles de la oferta -->
                            <div class="bg-white rounded-lg shadow-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    Detalles de la Oferta
                                </h3>
                                
                                <div class="space-y-4">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Estado:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getEstadoClass($oferta['estado']); ?>">
                                            <i class="<?php echo getEstadoIcon($oferta['estado']); ?> mr-1"></i>
                                            <?php echo $oferta['estado']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Fecha Inicio:</span>
                                        <span class="font-medium"><?php echo formatearFecha($oferta['fecha_inicio']); ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Fecha Fin:</span>
                                        <span class="font-medium"><?php echo formatearFecha($oferta['fecha_fin']); ?></span>
                                    </div>
                                    
                                    <?php if ($oferta['codigo_promocional']): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Código Promocional:</span>
                                            <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">
                                                <?php echo htmlspecialchars($oferta['codigo_promocional']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($oferta['limite_usos']): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Límite Total:</span>
                                            <span class="font-medium">
                                                <?php echo number_format($oferta['total_usos']); ?> / <?php echo number_format($oferta['limite_usos']); ?>
                                                <div class="w-24 bg-gray-200 rounded-full h-2 ml-2 inline-block">
                                                    <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo min(100, ($oferta['total_usos'] / $oferta['limite_usos']) * 100); ?>%"></div>
                                                </div>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Límite por Usuario:</span>
                                        <span class="font-medium"><?php echo $oferta['limite_por_usuario']; ?></span>
                                    </div>
                                    
                                    <?php if ($oferta['monto_minimo']): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Monto Mínimo:</span>
                                            <span class="font-medium"><?php echo formatearMonto($oferta['monto_minimo']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Visible Públicamente:</span>
                                        <span class="<?php echo $oferta['visible_publica'] ? 'text-green-600' : 'text-red-600'; ?>">
                                            <i class="fas fa-<?php echo $oferta['visible_publica'] ? 'check' : 'times'; ?>"></i>
                                            <?php echo $oferta['visible_publica'] ? 'Sí' : 'No'; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($oferta['creado_por_nombre']): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Creado por:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($oferta['creado_por_nombre']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Creado:</span>
                                        <span class="font-medium"><?php echo formatearFecha($oferta['creado_en']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Mensaje promocional y términos -->
                            <?php if ($oferta['mensaje_promocional'] || $oferta['terminos_condiciones']): ?>
                                <div class="bg-white rounded-lg shadow-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-bullhorn text-green-600 mr-2"></i>
                                        Contenido Promocional
                                    </h3>
                                    
                                    <?php if ($oferta['mensaje_promocional']): ?>
                                        <div class="mb-4">
                                            <h4 class="font-medium text-gray-700 mb-2">Mensaje Promocional</h4>
                                            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                                <p class="text-green-800"><?php echo nl2br(htmlspecialchars($oferta['mensaje_promocional'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($oferta['terminos_condiciones']): ?>
                                        <div>
                                            <h4 class="font-medium text-gray-700 mb-2">Términos y Condiciones</h4>
                                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                                <p class="text-gray-700 text-sm"><?php echo nl2br(htmlspecialchars($oferta['terminos_condiciones'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Panel derecho -->
                        <div class="space-y-6">
                            <!-- Tours específicos -->
                            <?php if ($oferta['aplicable_a'] === 'Tours_Especificos' && !empty($tours_especificos)): ?>
                                <div class="bg-white rounded-lg shadow-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-map-marked-alt text-purple-600 mr-2"></i>
                                        Tours Aplicables (<?php echo count($tours_especificos); ?>)
                                    </h3>
                                    
                                    <div class="space-y-3 max-h-60 overflow-y-auto">
                                        <?php foreach ($tours_especificos as $tour): ?>
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div>
                                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($tour['titulo']); ?></p>
                                                    <p class="text-sm text-gray-600">Precio: <?php echo formatearMonto($tour['precio']); ?></p>
                                                </div>
                                                <a href="../tours/ver.php?id=<?php echo $tour['id_tour']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Usuarios específicos -->
                            <?php if ($oferta['aplicable_a'] === 'Usuarios_Especificos' && !empty($usuarios_especificos)): ?>
                                <div class="bg-white rounded-lg shadow-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-users text-indigo-600 mr-2"></i>
                                        Usuarios Aplicables (<?php echo count($usuarios_especificos); ?>)
                                    </h3>
                                    
                                    <div class="space-y-3 max-h-60 overflow-y-auto">
                                        <?php foreach ($usuarios_especificos as $usuario): ?>
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div>
                                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($usuario['email']); ?></p>
                                                </div>
                                                <a href="../usuarios/ver.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Historial de uso -->
                            <?php if (!empty($historial)): ?>
                                <div class="bg-white rounded-lg shadow-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-history text-gray-600 mr-2"></i>
                                        Historial de Uso Reciente
                                    </h3>
                                    
                                    <div class="space-y-3 max-h-80 overflow-y-auto">
                                        <?php foreach ($historial as $uso): ?>
                                            <div class="border-l-4 border-green-400 pl-4 py-2">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($uso['usuario_nombre']); ?></p>
                                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($uso['usuario_email']); ?></p>
                                                        <?php if ($uso['tour_titulo']): ?>
                                                            <p class="text-xs text-gray-500">Tour: <?php echo htmlspecialchars($uso['tour_titulo']); ?></p>
                                                        <?php endif; ?>
                                                        <p class="text-xs text-gray-400"><?php echo formatearFecha($uso['fecha_uso']); ?></p>
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="font-semibold text-green-600"><?php echo formatearMonto($uso['monto_descuento']); ?></p>
                                                        <p class="text-xs text-gray-500">ahorrado</p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php if (count($historial) >= 10): ?>
                                        <div class="mt-4 pt-4 border-t border-gray-200 text-center">
                                            <button onclick="verHistorialCompleto(<?php echo $oferta['id_oferta']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Ver historial completo
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                                    <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">Esta oferta aún no ha sido utilizada</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function pausarOferta(idOferta) {
            if (confirm('¿Estás seguro de que deseas pausar esta oferta?')) {
                fetch('procesar_oferta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=pausar&id=${idOferta}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al pausar la oferta: ' + error);
                });
            }
        }

        function activarOferta(idOferta) {
            if (confirm('¿Estás seguro de que deseas activar esta oferta?')) {
                fetch('procesar_oferta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=activar&id=${idOferta}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al activar la oferta: ' + error);
                });
            }
        }

        function verHistorialCompleto(idOferta) {
            window.open(`historial.php?id=${idOferta}`, '_blank');
        }
    </script>
</body>
</html>
