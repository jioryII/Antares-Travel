<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Gestión de Usuarios";

try {
    $connection = getConnection();
    
    // Obtener todos los usuarios sin paginación (para filtros en tiempo real)
    $usuarios_sql = "SELECT u.*, 
                            COUNT(r.id_reserva) as total_reservas,
                            COALESCE(SUM(r.monto_total), 0) as total_gastado
                     FROM usuarios u
                     LEFT JOIN reservas r ON u.id_usuario = r.id_usuario
                     GROUP BY u.id_usuario
                     ORDER BY u.creado_en DESC";
    
    $usuarios_stmt = $connection->prepare($usuarios_sql);
    $usuarios_stmt->execute();
    $usuarios = $usuarios_stmt->fetchAll();
    
    // Procesar las URLs de avatar para corregir las rutas
    foreach ($usuarios as &$usuario) {
        if (!empty($usuario['avatar_url'])) {
            // Si es una URL completa (HTTP/HTTPS), dejarla como está
            if (preg_match('/^https?:\/\//i', $usuario['avatar_url'])) {
                // URL externa (Google, Facebook, etc.) - no hacer nada
                continue;
            }
            // Si es una ruta local, ajustar la ruta relativa
            elseif (!preg_match('/^https?:\/\//i', $usuario['avatar_url'])) {
                // Ruta local: convertir a ruta relativa desde la ubicación actual
                $usuario['avatar_url'] = '../../../../' . ltrim($usuario['avatar_url'], '/');
            }
        }
    }
    unset($usuario); // Romper la referencia
    
    $total_usuarios = count($usuarios);
    
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

// Manejar mensajes de éxito/error
$mensaje_success = $_GET['success'] ?? null;
$mensaje_error = $_GET['error'] ?? null;

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
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="/imagenes/antares_logozz2.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .desktop-table {
                display: none;
            }
            .mobile-cards {
                display: block;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-form {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .filter-actions {
                flex-direction: column;
                width: 100%;
            }
            .filter-actions button,
            .filter-actions a {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (min-width: 769px) {
            .desktop-table {
                display: block;
            }
            .mobile-cards {
                display: none;
            }
            .stats-grid {
                grid-template-columns: repeat(5, 1fr);
            }
            .filter-form {
                grid-template-columns: repeat(5, 1fr);
                gap: 1rem;
            }
            .filter-actions {
                flex-direction: row;
                gap: 0.5rem;
            }
        }
        
        /* Configuración de scroll para la tabla desktop */
        .desktop-table {
            border: 2px solid #d1d5db;
            border-radius: 0.5rem;
            background: white;
            max-height: 600px;
            overflow: hidden;
            position: relative;
        }
        
        .desktop-table .table-container {
            max-height: 600px;
            overflow-y: auto;
            overflow-x: auto;
        }
        
        .desktop-table table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        /* Header sticky con mejor configuración */
        .desktop-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #f9fafb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }
        
        .desktop-table thead th {
            background: #f9fafb;
            position: sticky;
            top: 0;
            z-index: 15;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .desktop-table tbody {
            background: white;
        }
        
        /* Scroll personalizado mejorado */
        .desktop-table .table-container::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        
        .desktop-table .table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .desktop-table .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .desktop-table .table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
            
            /* Mejorar separación visual en scroll */
            .desktop-table tbody tr {
                border-bottom: 1px solid #e5e7eb;
                transition: background-color 0.2s ease;
            }
            
            .desktop-table tbody tr:hover {
                background-color: #f8fafc;
            }
            
            /* Optimizar contenido de celdas para scroll */
            .desktop-table td {
                padding: 12px 16px;
                vertical-align: middle;
            }
            
            .desktop-table th {
                padding: 12px 16px;
                white-space: nowrap;
                font-weight: 600;
            }
            
            /* Anchos específicos para columnas críticas */
            .desktop-table th:nth-child(1),
            .desktop-table td:nth-child(1) { 
                min-width: 250px;
                max-width: 300px;
            }
            
            .desktop-table th:nth-child(2),
            .desktop-table td:nth-child(2) { 
                min-width: 160px;
                max-width: 180px;
                text-align: center;
            }
            
            .desktop-table th:nth-child(3),
            .desktop-table td:nth-child(3) { 
                min-width: 120px;
                max-width: 140px;
                text-align: center;
            }
            
            .desktop-table th:nth-child(4),
            .desktop-table td:nth-child(4) { 
                min-width: 150px;
                max-width: 180px;
                text-align: center;
            }
            
            .desktop-table th:nth-child(5),
            .desktop-table td:nth-child(5) { 
                min-width: 120px;
                max-width: 140px;
                text-align: center;
            }
            
            .desktop-table th:nth-child(6),
            .desktop-table td:nth-child(6) { 
                min-width: 180px;
                max-width: 200px;
                text-align: center;
            }
        }
        
        /* Estilos para las tarjetas móviles */
        .user-card {
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease-in-out;
        }
        
        .user-card:hover {
            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }
        
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <br>
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
                        </div>
                    </div>
                </div>

                <!-- Notificaciones de éxito/error -->
                <?php if ($mensaje_success): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700 font-medium">
                                    <?php echo htmlspecialchars($mensaje_success); ?>
                                </p>
                            </div>
                            <div class="ml-auto">
                                <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                                        class="text-green-400 hover:text-green-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($mensaje_error): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700 font-medium">
                                    <?php echo htmlspecialchars($mensaje_error); ?>
                                </p>
                            </div>
                            <div class="ml-auto">
                                <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                                        class="text-red-400 hover:text-red-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

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
                <div class="stats-grid grid gap-3 lg:gap-6 mb-6">
                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-blue-100 bg-opacity-75">
                                <i class="fas fa-users text-blue-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Total Usuarios</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-green-100 bg-opacity-75">
                                <i class="fas fa-check-circle text-green-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Verificados</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['verificados'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-purple-100 bg-opacity-75">
                                <i class="fas fa-user-plus text-purple-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Registro Manual</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['registro_manual'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-orange-100 bg-opacity-75">
                                <i class="fab fa-google text-orange-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Social Login</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['registro_social'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card bg-white rounded-lg shadow-lg p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-indigo-100 bg-opacity-75">
                                <i class="fas fa-calendar text-indigo-600 text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4">
                                <p class="text-xs lg:text-sm font-medium text-gray-500">Nuevos (30d)</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['nuevos_mes'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 mb-6">
                    <div class="filter-form grid gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por nombre</label>
                            <div class="relative">
                                <input type="text" id="filtro-nombre" 
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full text-sm pr-8"
                                       placeholder="Nombre del usuario">
                                <button type="button" id="limpiar-nombre" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por email</label>
                            <div class="relative">
                                <input type="email" id="filtro-email" 
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full text-sm pr-8"
                                       placeholder="Email del usuario">
                                <button type="button" id="limpiar-email" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado de verificación</label>
                            <select id="filtro-verificado" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full text-sm">
                                <option value="">Todos</option>
                                <option value="1">Verificados</option>
                                <option value="0">No verificados</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de registro</label>
                            <select id="filtro-proveedor" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full text-sm">
                                <option value="">Todos</option>
                                <option value="manual">Manual</option>
                                <option value="google">Google</option>
                                <option value="apple">Apple</option>
                                <option value="microsoft">Microsoft</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions flex items-center gap-3">
                            <span id="contador-resultados" class="text-sm text-gray-600">
                                <i class="fas fa-list mr-1"></i>
                                <span id="total-resultados"><?php echo count($usuarios); ?></span> usuarios
                            </span>
                            <button type="button" onclick="limpiarFiltros()" 
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm border border-gray-300 transition-colors">
                                <i class="fas fa-eraser mr-1"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de usuarios - Vista Desktop -->
                                <!-- Vista Desktop - Tabla con Scroll -->
                <div class="desktop-table bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="table-container">
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
                </div>

                <!-- Vista Mobile - Tarjetas -->
                <div class="mobile-cards space-y-4">
                    <?php if (empty($usuarios)): ?>
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-900 mb-2">No se encontraron usuarios</p>
                            <p class="text-sm text-gray-500">Intenta ajustar los filtros de búsqueda</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <div class="user-card bg-white p-4 border border-gray-200">
                                <!-- Header de la tarjeta -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <?php if ($usuario['avatar_url']): ?>
                                                <img class="h-12 w-12 rounded-full" src="<?php echo htmlspecialchars($usuario['avatar_url']); ?>" alt="">
                                            <?php else: ?>
                                                <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center">
                                                    <span class="text-white font-medium text-sm">
                                                        <?php echo strtoupper(substr($usuario['nombre'] ?? $usuario['email'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="font-semibold text-gray-900 text-sm">
                                                <?php echo htmlspecialchars($usuario['nombre'] ?? 'Sin nombre'); ?>
                                            </h3>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($usuario['email']); ?></p>
                                            <?php if ($usuario['telefono']): ?>
                                                <p class="text-xs text-gray-400">
                                                    <i class="fas fa-phone mr-1"></i>
                                                    <?php echo htmlspecialchars($usuario['telefono']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($usuario['email_verificado']): ?>
                                        <span class="status-badge bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Verificado
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>Pendiente
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Tipo de registro -->
                                <div class="mb-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getProveedorClass($usuario['proveedor_oauth']); ?>">
                                        <i class="<?php echo getProveedorIcon($usuario['proveedor_oauth']); ?> mr-1"></i>
                                        Registro <?php echo ucfirst($usuario['proveedor_oauth']); ?>
                                    </span>
                                </div>

                                <!-- Estadísticas -->
                                <div class="grid grid-cols-2 gap-3 mb-4 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-blue-600"><?php echo number_format($usuario['total_reservas']); ?></div>
                                        <div class="text-xs text-gray-500">Reservas</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-green-600"><?php echo formatCurrency($usuario['total_gastado']); ?></div>
                                        <div class="text-xs text-gray-500">Total gastado</div>
                                    </div>
                                </div>

                                <!-- Footer con fecha de registro y acciones -->
                                <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                    <div class="flex space-x-4">
                                        <a href="ver.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 transition-colors">
                                            <i class="fas fa-eye text-sm"></i>
                                            <span class="ml-1 text-xs">Ver</span>
                                        </a>
                                        <a href="editar.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                           class="text-green-600 hover:text-green-800 transition-colors">
                                            <i class="fas fa-edit text-sm"></i>
                                            <span class="ml-1 text-xs">Editar</span>
                                        </a>
                                        <?php if (!$usuario['email_verificado']): ?>
                                            <button onclick="verificarEmail(<?php echo $usuario['id_usuario']; ?>)" 
                                                    class="text-purple-600 hover:text-purple-800 transition-colors">
                                                <i class="fas fa-check text-sm"></i>
                                                <span class="ml-1 text-xs">Verificar</span>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="eliminarUsuario(<?php echo $usuario['id_usuario']; ?>)" 
                                                class="text-red-600 hover:text-red-800 transition-colors">
                                            <i class="fas fa-trash text-sm"></i>
                                            <span class="ml-1 text-xs">Eliminar</span>
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo formatDate($usuario['creado_en'], 'd/m/Y'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div id="modalEliminar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[450px] shadow-xl rounded-lg bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Eliminar Usuario</h3>
                <div class="mt-2 px-4 py-3">
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        ¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.
                    </p>
                    
                    <!-- Advertencia de datos relacionados -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-0.5"></i>
                            <div class="text-xs text-yellow-700">
                                <strong>Advertencia:</strong> Se eliminarán todos los datos del usuario, pero las reservas históricas se mantendrán de forma anónima para conservar registros contables.
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-left">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-clipboard-list mr-1"></i>Motivo de eliminación (opcional):
                        </label>
                        <textarea name="motivo" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm resize-none"
                                  placeholder="Ej: Solicitud del usuario, cuenta duplicada, violación de términos..."></textarea>
                        <div class="text-xs text-gray-400 mt-1">
                            Este motivo quedará registrado en el historial administrativo
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-3 px-4 py-4">
                    <button id="btnConfirmarEliminar" 
                            class="px-6 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Eliminar Usuario
                    </button>
                    <button onclick="cerrarModalEliminar()" 
                            class="px-6 py-2 bg-gray-200 text-gray-800 text-sm font-semibold rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let usuarioAEliminar = null;

        function eliminarUsuario(id) {
            usuarioAEliminar = id;
            
            // Buscar información del usuario en la tabla para mostrarla en el modal
            const filaUsuario = document.querySelector(`button[onclick="eliminarUsuario(${id})"]`)?.closest('tr');
            let nombreUsuario = 'este usuario';
            let emailUsuario = '';
            
            if (filaUsuario) {
                const celdaUsuario = filaUsuario.cells[0];
                if (celdaUsuario) {
                    const nombreDiv = celdaUsuario.querySelector('.text-sm.font-medium');
                    const emailDiv = celdaUsuario.querySelector('.text-sm.text-gray-500');
                    if (nombreDiv) nombreUsuario = nombreDiv.textContent.trim();
                    if (emailDiv) emailUsuario = emailDiv.textContent.trim();
                }
            } else {
                // Buscar en tarjetas móviles
                const tarjetaUsuario = document.querySelector(`button[onclick="eliminarUsuario(${id})"]`)?.closest('.user-card');
                if (tarjetaUsuario) {
                    const nombreH3 = tarjetaUsuario.querySelector('h3.font-semibold');
                    const emailP = tarjetaUsuario.querySelector('p.text-xs.text-gray-500');
                    if (nombreH3) nombreUsuario = nombreH3.textContent.trim();
                    if (emailP) emailUsuario = emailP.textContent.trim();
                }
            }
            
            // Actualizar el contenido del modal
            const modalTitulo = document.querySelector('#modalEliminar h3');
            const modalTexto = document.querySelector('#modalEliminar .text-sm.text-gray-500');
            
            if (modalTitulo) {
                modalTitulo.textContent = `Eliminar Usuario: ${nombreUsuario}`;
            }
            
            if (modalTexto) {
                modalTexto.innerHTML = `
                    ¿Estás seguro de que deseas eliminar a <strong>${nombreUsuario}</strong> (${emailUsuario})?<br>
                    Esta acción no se puede deshacer y se eliminarán todos los datos asociados.
                `;
            }
            
            // Limpiar el textarea
            const textareaMotivo = document.querySelector('textarea[name="motivo"]');
            if (textareaMotivo) {
                textareaMotivo.value = '';
            }
            
            document.getElementById('modalEliminar').classList.remove('hidden');
        }

        function cerrarModalEliminar() {
            // Ocultar modal
            document.getElementById('modalEliminar').classList.add('hidden');
            
            // Resetear variables
            usuarioAEliminar = null;
            
            // Restaurar estado del botón eliminar
            const btnEliminar = document.getElementById('btnConfirmarEliminar');
            const btnCancelar = document.querySelector('button[onclick="cerrarModalEliminar()"]');
            
            if (btnEliminar) {
                btnEliminar.disabled = false;
                btnEliminar.innerHTML = '<i class="fas fa-trash mr-2"></i>Eliminar Usuario';
                btnEliminar.className = 'px-6 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors';
            }
            
            if (btnCancelar) {
                btnCancelar.disabled = false;
            }
            
            // Restaurar título y texto del modal
            const modalTitulo = document.querySelector('#modalEliminar h3');
            const modalTexto = document.querySelector('#modalEliminar .text-sm.text-gray-500');
            
            if (modalTitulo) {
                modalTitulo.textContent = 'Eliminar Usuario';
            }
            
            if (modalTexto) {
                modalTexto.innerHTML = '¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.';
            }
            
            // Limpiar textarea
            const textareaMotivo = document.querySelector('textarea[name="motivo"]');
            if (textareaMotivo) {
                textareaMotivo.value = '';
            }
        }
        
        // Cerrar modal al hacer clic fuera de él
        document.getElementById('modalEliminar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEliminar();
            }
        });
        
        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('modalEliminar').classList.contains('hidden')) {
                cerrarModalEliminar();
            }
        });

        function verificarEmail(id) {
            // Mostrar confirmación
            const confirmacion = confirm('¿Deseas marcar el email de este usuario como verificado?\n\nEsta acción enviará una notificación al usuario.');
            
            if (!confirmacion) return;
            
            // Buscar el botón que se presionó para mostrar estado de carga
            const botonVerificar = document.querySelector(`button[onclick="verificarEmail(${id})"]`);
            let iconoOriginal = '';
            let textoOriginal = '';
            
            if (botonVerificar) {
                iconoOriginal = botonVerificar.innerHTML;
                botonVerificar.disabled = true;
                botonVerificar.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i><span class="ml-1 text-xs">Verificando...</span>';
                botonVerificar.className = 'text-gray-400 cursor-not-allowed transition-colors';
            }
            
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
                    // Mostrar mensaje de éxito
                    mostrarNotificacion('Email verificado exitosamente', 'success');
                    
                    // Recargar la página después de un momento para mostrar los cambios
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    // Mostrar error
                    mostrarNotificacion('Error al verificar email: ' + data.message, 'error');
                    
                    // Restaurar botón
                    if (botonVerificar) {
                        botonVerificar.disabled = false;
                        botonVerificar.innerHTML = iconoOriginal;
                        botonVerificar.className = 'text-purple-600 hover:text-purple-900';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión al verificar email', 'error');
                
                // Restaurar botón
                if (botonVerificar) {
                    botonVerificar.disabled = false;
                    botonVerificar.innerHTML = iconoOriginal;
                    botonVerificar.className = 'text-purple-600 hover:text-purple-900';
                }
            });
        }
        
        // Función para mostrar notificaciones
        function mostrarNotificacion(mensaje, tipo = 'info') {
            // Crear elemento de notificación
            const notificacion = document.createElement('div');
            notificacion.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full opacity-0`;
            
            // Estilos según tipo
            if (tipo === 'success') {
                notificacion.className += ' bg-green-500 text-white';
                notificacion.innerHTML = `<i class="fas fa-check-circle mr-2"></i>${mensaje}`;
            } else if (tipo === 'error') {
                notificacion.className += ' bg-red-500 text-white';
                notificacion.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${mensaje}`;
            } else {
                notificacion.className += ' bg-blue-500 text-white';
                notificacion.innerHTML = `<i class="fas fa-info-circle mr-2"></i>${mensaje}`;
            }
            
            // Agregar al DOM
            document.body.appendChild(notificacion);
            
            // Animar entrada
            setTimeout(() => {
                notificacion.className = notificacion.className.replace('translate-x-full opacity-0', 'translate-x-0 opacity-100');
            }, 100);
            
            // Remover después de 4 segundos
            setTimeout(() => {
                notificacion.className = notificacion.className.replace('translate-x-0 opacity-100', 'translate-x-full opacity-0');
                setTimeout(() => {
                    if (notificacion.parentNode) {
                        notificacion.parentNode.removeChild(notificacion);
                    }
                }, 300);
            }, 4000);
        }

        document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
            if (usuarioAEliminar) {
                const motivo = document.querySelector('textarea[name="motivo"]')?.value || '';
                const btnEliminar = document.getElementById('btnConfirmarEliminar');
                const btnCancelar = document.querySelector('button[onclick="cerrarModalEliminar()"]');
                
                // Cambiar estado del botón a loading
                btnEliminar.disabled = true;
                btnEliminar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Eliminando...';
                btnEliminar.className = 'px-4 py-2 bg-gray-400 text-white text-base font-medium rounded-md w-auto cursor-not-allowed mr-2';
                btnCancelar.disabled = true;
                
                // Crear formulario y enviar
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

        // ============================================
        // FILTROS EN TIEMPO REAL
        // ============================================
        
        let filtroTimeout;
        
        // Función para filtrar usuarios
        function filtrarUsuarios() {
            const filtroNombre = document.getElementById('filtro-nombre')?.value.toLowerCase() || '';
            const filtroEmail = document.getElementById('filtro-email')?.value.toLowerCase() || '';
            const filtroVerificado = document.getElementById('filtro-verificado')?.value || '';
            const filtroProveedor = document.getElementById('filtro-proveedor')?.value || '';
            
            // Filtrar tabla desktop
            const filasTabla = document.querySelectorAll('.desktop-table tbody tr');
            let visiblesTabla = 0;
            
            filasTabla.forEach(function(fila) {
                // Verificar si es la fila de "no hay resultados"
                if (fila.querySelector('td[colspan]')) {
                    fila.style.display = 'none';
                    return;
                }
                
                let visible = true;
                
                // Filtro por nombre (incluyendo email en el campo de usuario)
                if (filtroNombre) {
                    const celdaUsuario = fila.cells[0];
                    const textoUsuario = celdaUsuario ? celdaUsuario.textContent.toLowerCase() : '';
                    if (!textoUsuario.includes(filtroNombre)) {
                        visible = false;
                    }
                }
                
                // Filtro por email específico
                if (filtroEmail) {
                    const celdaUsuario = fila.cells[0];
                    const textoEmail = celdaUsuario ? celdaUsuario.textContent.toLowerCase() : '';
                    if (!textoEmail.includes(filtroEmail)) {
                        visible = false;
                    }
                }
                
                // Filtro por verificación
                if (filtroVerificado) {
                    const celdaVerificado = fila.cells[2];
                    if (celdaVerificado) {
                        const textoEstado = celdaVerificado.textContent.toLowerCase();
                        const esVerificado = textoEstado.includes('verificado') && !textoEstado.includes('pendiente');
                        
                        if ((filtroVerificado === '1' && !esVerificado) || 
                            (filtroVerificado === '0' && esVerificado)) {
                            visible = false;
                        }
                    }
                }
                
                // Filtro por proveedor OAuth
                if (filtroProveedor) {
                    const celdaProveedor = fila.cells[1];
                    const textoProveedor = celdaProveedor ? celdaProveedor.textContent.toLowerCase() : '';
                    if (!textoProveedor.includes(filtroProveedor.toLowerCase())) {
                        visible = false;
                    }
                }
                
                fila.style.display = visible ? '' : 'none';
                if (visible) visiblesTabla++;
            });
            
            // Filtrar tarjetas móviles
            const tarjetas = document.querySelectorAll('.user-card');
            let visiblesTarjetas = 0;
            
            tarjetas.forEach(function(tarjeta) {
                let visible = true;
                
                // Filtro por nombre
                if (filtroNombre) {
                    const textoTarjeta = tarjeta.textContent.toLowerCase();
                    if (!textoTarjeta.includes(filtroNombre)) {
                        visible = false;
                    }
                }
                
                // Filtro por email
                if (filtroEmail) {
                    const textoTarjeta = tarjeta.textContent.toLowerCase();
                    if (!textoTarjeta.includes(filtroEmail)) {
                        visible = false;
                    }
                }
                
                // Filtro por verificación
                if (filtroVerificado) {
                    const badgeVerificado = tarjeta.querySelector('.status-badge');
                    if (badgeVerificado) {
                        const textoEstado = badgeVerificado.textContent.toLowerCase();
                        const esVerificado = textoEstado.includes('verificado') && !textoEstado.includes('pendiente');
                        
                        if ((filtroVerificado === '1' && !esVerificado) || 
                            (filtroVerificado === '0' && esVerificado)) {
                            visible = false;
                        }
                    }
                }
                
                // Filtro por proveedor
                if (filtroProveedor) {
                    const spanProveedor = tarjeta.querySelector('span[class*="rounded-full"]');
                    const textoProveedor = spanProveedor ? spanProveedor.textContent.toLowerCase() : '';
                    if (!textoProveedor.includes(filtroProveedor.toLowerCase())) {
                        visible = false;
                    }
                }
                
                tarjeta.style.display = visible ? '' : 'none';
                if (visible) visiblesTarjetas++;
            });
            
            // Mostrar mensaje si no hay resultados
            mostrarMensajeNoResultados(visiblesTabla, visiblesTarjetas);
        }
        
        // Función para mostrar mensaje de no resultados
        function mostrarMensajeNoResultados(visiblesTabla, visiblesTarjetas) {
            // Para tabla desktop
            const tablaBody = document.querySelector('.desktop-table tbody');
            if (tablaBody) {
                let filaSinResultados = tablaBody.querySelector('tr[data-no-results]');
                
                if (visiblesTabla === 0) {
                    if (!filaSinResultados) {
                        filaSinResultados = document.createElement('tr');
                        filaSinResultados.setAttribute('data-no-results', 'true');
                        filaSinResultados.innerHTML = `
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium">No se encontraron usuarios</p>
                                <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                            </td>
                        `;
                        tablaBody.appendChild(filaSinResultados);
                    }
                    filaSinResultados.style.display = '';
                } else if (filaSinResultados) {
                    filaSinResultados.style.display = 'none';
                }
            }
            
            // Para tarjetas móviles
            const contenedorTarjetas = document.querySelector('.mobile-cards');
            if (contenedorTarjetas) {
                let tarjetaSinResultados = contenedorTarjetas.querySelector('[data-no-results]');
                
                if (visiblesTarjetas === 0) {
                    if (!tarjetaSinResultados) {
                        tarjetaSinResultados = document.createElement('div');
                        tarjetaSinResultados.setAttribute('data-no-results', 'true');
                        tarjetaSinResultados.className = 'bg-white rounded-lg shadow p-6 text-center';
                        tarjetaSinResultados.innerHTML = `
                            <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-900 mb-2">No se encontraron usuarios</p>
                            <p class="text-sm text-gray-500">Intenta ajustar los filtros de búsqueda</p>
                        `;
                        contenedorTarjetas.appendChild(tarjetaSinResultados);
                    }
                    tarjetaSinResultados.style.display = '';
                } else if (tarjetaSinResultados) {
                    tarjetaSinResultados.style.display = 'none';
                }
            }
        }
        
        // Función con debounce para optimizar rendimiento
        function filtrarConDebounce() {
            clearTimeout(filtroTimeout);
            filtroTimeout = setTimeout(filtrarUsuarios, 300);
        }
        
        // Event listeners para los campos de filtro
        document.addEventListener('DOMContentLoaded', function() {
            const filtroNombre = document.getElementById('filtro-nombre');
            const filtroEmail = document.getElementById('filtro-email');
            const filtroVerificado = document.getElementById('filtro-verificado');
            const filtroProveedor = document.getElementById('filtro-proveedor');
            
            if (filtroNombre) {
                filtroNombre.addEventListener('input', filtrarConDebounce);
            }
            
            if (filtroEmail) {
                filtroEmail.addEventListener('input', filtrarConDebounce);
            }
            
            if (filtroVerificado) {
                filtroVerificado.addEventListener('change', filtrarUsuarios);
            }
            
            if (filtroProveedor) {
                filtroProveedor.addEventListener('change', filtrarUsuarios);
            }
        });
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            document.getElementById('filtro-nombre').value = '';
            document.getElementById('filtro-email').value = '';
            document.getElementById('filtro-verificado').value = '';
            document.getElementById('filtro-proveedor').value = '';
            filtrarUsuarios();
        }
    </script>
</body>
</html>
