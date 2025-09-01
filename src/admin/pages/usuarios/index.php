<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Gestión de Usuarios";

// Parámetros de filtrado y paginación
$filtro_nombre = $_GET['nombre'] ?? '';
$filtro_email = $_GET['email'] ?? '';
$filtro_verificado = $_GET['verificado'] ?? '';
$filtro_proveedor = $_GET['proveedor'] ?? '';
$pagina_actual = intval($_GET['pagina'] ?? 1);
$registros_por_pagina = 20;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

try {
    $connection = getConnection();
    
    // Construir WHERE clause para filtros
    $where_conditions = [];
    $params = [];
    
    if (!empty($filtro_nombre)) {
        $where_conditions[] = "u.nombre LIKE ?";
        $params[] = "%$filtro_nombre%";
    }
    
    if (!empty($filtro_email)) {
        $where_conditions[] = "u.email LIKE ?";
        $params[] = "%$filtro_email%";
    }
    
    if ($filtro_verificado !== '') {
        $where_conditions[] = "u.email_verificado = ?";
        $params[] = $filtro_verificado;
    }
    
    if (!empty($filtro_proveedor)) {
        $where_conditions[] = "u.proveedor_oauth = ?";
        $params[] = $filtro_proveedor;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Obtener usuarios con filtros y paginación
    $usuarios_sql = "SELECT u.*, 
                            COUNT(r.id_reserva) as total_reservas,
                            COALESCE(SUM(r.monto_total), 0) as total_gastado
                     FROM usuarios u
                     LEFT JOIN reservas r ON u.id_usuario = r.id_usuario
                     $where_clause
                     GROUP BY u.id_usuario
                     ORDER BY u.creado_en DESC
                     LIMIT $registros_por_pagina OFFSET $offset";
    
    $usuarios_stmt = $connection->prepare($usuarios_sql);
    $usuarios_stmt->execute($params);
    $usuarios = $usuarios_stmt->fetchAll();
    
    // Contar total de registros para paginación
    $count_sql = "SELECT COUNT(DISTINCT u.id_usuario) as total 
                  FROM usuarios u
                  $where_clause";
    
    $count_stmt = $connection->prepare($count_sql);
    $count_stmt->execute($params);
    $total_registros = $count_stmt->fetch()['total'];
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    // Obtener estadísticas generales
    $stats_sql = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN email_verificado = 1 THEN 1 ELSE 0 END) as verificados,
                    SUM(CASE WHEN proveedor_oauth = 'manual' THEN 1 ELSE 0 END) as registro_manual,
                    SUM(CASE WHEN proveedor_oauth != 'manual' THEN 1 ELSE 0 END) as registro_social,
                    SUM(CASE WHEN creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as nuevos_mes
                  FROM usuarios";
    
    $stats = $connection->query($stats_sql)->fetch();
    
} catch (Exception $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
    $usuarios = [];
    $total_registros = 0;
    $total_paginas = 0;
    $stats = [];
}

// Función para obtener clase CSS del proveedor
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
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-users text-blue-600 mr-3"></i>Gestión de Usuarios
                            </h1>
                            <p class="text-sm lg:text-base text-gray-600">Administra todos los usuarios registrados en la plataforma</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="crear.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Nuevo Usuario
                            </a>
                            <button onclick="exportarUsuarios()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Exportar
                            </button>
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

                <!-- Estadísticas Rápidas -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-6 mb-6">
                    <div class="stats-card bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 bg-opacity-75">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Usuarios</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 bg-opacity-75">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Verificados</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['verificados'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 bg-opacity-75">
                                <i class="fas fa-user-plus text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Registro Manual</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['registro_manual'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-orange-100 bg-opacity-75">
                                <i class="fab fa-google text-orange-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Social Login</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['registro_social'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-indigo-100 bg-opacity-75">
                                <i class="fas fa-calendar text-indigo-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Nuevos (30d)</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['nuevos_mes'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por nombre</label>
                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($filtro_nombre); ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full"
                                   placeholder="Nombre del usuario">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($filtro_email); ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full"
                                   placeholder="Email del usuario">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado de verificación</label>
                            <select name="verificado" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full">
                                <option value="">Todos</option>
                                <option value="1" <?php echo $filtro_verificado === '1' ? 'selected' : ''; ?>>Verificados</option>
                                <option value="0" <?php echo $filtro_verificado === '0' ? 'selected' : ''; ?>>No verificados</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de registro</label>
                            <select name="proveedor" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full">
                                <option value="">Todos</option>
                                <option value="manual" <?php echo $filtro_proveedor === 'manual' ? 'selected' : ''; ?>>Manual</option>
                                <option value="google" <?php echo $filtro_proveedor === 'google' ? 'selected' : ''; ?>>Google</option>
                                <option value="apple" <?php echo $filtro_proveedor === 'apple' ? 'selected' : ''; ?>>Apple</option>
                                <option value="microsoft" <?php echo $filtro_proveedor === 'microsoft' ? 'selected' : ''; ?>>Microsoft</option>
                            </select>
                        </div>
                        
                        <div class="flex items-end gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-filter mr-1"></i>Filtrar
                            </button>
                            <a href="index.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                <i class="fas fa-times mr-1"></i>Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabla de usuarios -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Registro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estadísticas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-lg font-medium">No se encontraron usuarios</p>
                                            <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0">
                                                        <?php if ($usuario['avatar_url']): ?>
                                                            <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($usuario['avatar_url']); ?>" alt="">
                                                        <?php else: ?>
                                                            <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center">
                                                                <span class="text-white font-medium text-sm">
                                                                    <?php echo strtoupper(substr($usuario['nombre'] ?? $usuario['email'], 0, 1)); ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($usuario['nombre'] ?? 'Sin nombre'); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($usuario['email']); ?>
                                                        </div>
                                                        <?php if ($usuario['telefono']): ?>
                                                            <div class="text-xs text-gray-400">
                                                                <i class="fas fa-phone mr-1"></i>
                                                                <?php echo htmlspecialchars($usuario['telefono']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getProveedorClass($usuario['proveedor_oauth']); ?>">
                                                    <i class="<?php echo getProveedorIcon($usuario['proveedor_oauth']); ?> mr-1"></i>
                                                    <?php echo ucfirst($usuario['proveedor_oauth']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($usuario['email_verificado']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i>Verificado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-clock mr-1"></i>Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="space-y-1">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-calendar-check text-blue-500 mr-2 text-xs"></i>
                                                        <span class="text-xs"><?php echo number_format($usuario['total_reservas']); ?> reservas</span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-dollar-sign text-green-500 mr-2 text-xs"></i>
                                                        <span class="text-xs"><?php echo formatCurrency($usuario['total_gastado']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo formatDate($usuario['creado_en'], 'd/m/Y'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <a href="ver.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                                       class="text-green-600 hover:text-green-900" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (!$usuario['email_verificado']): ?>
                                                        <button onclick="verificarEmail(<?php echo $usuario['id_usuario']; ?>)" 
                                                                class="text-purple-600 hover:text-purple-900" title="Verificar email">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button onclick="eliminarUsuario(<?php echo $usuario['id_usuario']; ?>)" 
                                                            class="text-red-600 hover:text-red-900" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($pagina_actual > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Anterior
                                    </a>
                                <?php endif; ?>
                                <?php if ($pagina_actual < $total_paginas): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>" 
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Siguiente
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando 
                                        <span class="font-medium"><?php echo ($offset + 1); ?></span>
                                        a 
                                        <span class="font-medium"><?php echo min($offset + $registros_por_pagina, $total_registros); ?></span>
                                        de 
                                        <span class="font-medium"><?php echo $total_registros; ?></span>
                                        resultados
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                                      <?php echo $i === $pagina_actual 
                                                          ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' 
                                                          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div id="modalEliminar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-2">Eliminar Usuario</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 mb-4">
                        ¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.
                    </p>
                    <div class="text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de eliminación (opcional):</label>
                        <textarea name="motivo" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                                  placeholder="Ingrese el motivo de la eliminación..."></textarea>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="btnConfirmarEliminar" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-auto hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300 mr-2">
                        Eliminar
                    </button>
                    <button onclick="cerrarModalEliminar()" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-auto hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let usuarioAEliminar = null;

        function eliminarUsuario(id) {
            usuarioAEliminar = id;
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModalEliminar() {
            document.getElementById('modalEliminar').classList.add('hidden');
            usuarioAEliminar = null;
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

        document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
            if (usuarioAEliminar) {
                const motivo = document.querySelector('textarea[name="motivo"]')?.value || '';
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar.php';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_usuario';
                inputId.value = usuarioAEliminar;
                form.appendChild(inputId);
                
                const inputMotivo = document.createElement('input');
                inputMotivo.type = 'hidden';
                inputMotivo.name = 'motivo';
                inputMotivo.value = motivo;
                form.appendChild(inputMotivo);
                
                document.body.appendChild(form);
                form.submit();
            }
        });

        function exportarUsuarios() {
            const params = new URLSearchParams(window.location.search);
            params.set('exportar', '1');
            window.location.href = 'exportar.php?' + params.toString();
        }
    </script>
</body>
</html>
