<?php
// Datos de ejemplo para el dashboard (sin base de datos)
$usuario = [
    'nombre' => 'Administrador',
    'email' => 'admin@antares.com',
    'avatar_url' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face'
];

// Estadísticas de ejemplo
$stats = [
    'reservas' => 47,
    'tours' => 12,
    'usuarios' => 156,
    'ventas_mes' => 15750.50
];

// Tours de ejemplo
$tours_ejemplo = [
    ['id' => 1, 'nombre' => 'City Tour Lima', 'precio' => 50.00, 'hora_salida' => '09:00', 'hora_retorno' => '17:00'],
    ['id' => 2, 'nombre' => 'Islas Ballestas', 'precio' => 120.00, 'hora_salida' => '07:30', 'hora_retorno' => '16:00'],
    ['id' => 3, 'nombre' => 'Líneas de Nazca', 'precio' => 250.00, 'hora_salida' => '08:00', 'hora_retorno' => '18:30'],
    ['id' => 4, 'nombre' => 'Machu Picchu', 'precio' => 350.00, 'hora_salida' => '06:00', 'hora_retorno' => '20:00'],
    ['id' => 5, 'nombre' => 'Cañón del Colca', 'precio' => 180.00, 'hora_salida' => '07:00', 'hora_retorno' => '19:00']
];

// Guías de ejemplo
$guias_ejemplo = [
    ['id' => 1, 'nombre' => 'Juan Pérez'],
    ['id' => 2, 'nombre' => 'María García'],
    ['id' => 3, 'nombre' => 'Carlos López'],
    ['id' => 4, 'nombre' => 'Ana Rodríguez'],
    ['id' => 5, 'nombre' => 'Pedro Martínez']
];

// Choferes de ejemplo
$choferes_ejemplo = [
    ['id' => 1, 'nombre' => 'Roberto Silva'],
    ['id' => 2, 'nombre' => 'Luis Mendoza'],
    ['id' => 3, 'nombre' => 'José Ramírez'],
    ['id' => 4, 'nombre' => 'Miguel Torres'],
    ['id' => 5, 'nombre' => 'Fernando Vega']
];

// Vehículos de ejemplo
$vehiculos_ejemplo = [
    ['id' => 1, 'descripcion' => 'Bus ABC-123', 'capacidad' => 45],
    ['id' => 2, 'descripcion' => 'Van XYZ-456', 'capacidad' => 15],
    ['id' => 3, 'descripcion' => 'Minibus DEF-789', 'capacidad' => 25],
    ['id' => 4, 'descripcion' => 'Bus GHI-012', 'capacidad' => 50],
    ['id' => 5, 'descripcion' => 'Van JKL-345', 'capacidad' => 12]
];

// Tours del día de ejemplo
$tours_hoy = [
    [
        'tour' => 'City Tour Lima',
        'guia' => 'Juan Pérez',
        'chofer' => 'Roberto Silva',
        'vehiculo' => 'ABC-123',
        'adultos' => 15,
        'ninos' => 3,
        'hora_salida' => '09:00',
        'hora_retorno' => '17:00',
        'estado' => 'Programado'
    ],
    [
        'tour' => 'Islas Ballestas',
        'guia' => 'María García',
        'chofer' => 'Luis Mendoza',
        'vehiculo' => 'XYZ-456',
        'adultos' => 8,
        'ninos' => 2,
        'hora_salida' => '07:30',
        'hora_retorno' => '16:00',
        'estado' => 'En progreso'
    ],
    [
        'tour' => 'Machu Picchu',
        'guia' => 'Carlos López',
        'chofer' => 'José Ramírez',
        'vehiculo' => 'DEF-789',
        'adultos' => 20,
        'ninos' => 5,
        'hora_salida' => '06:00',
        'hora_retorno' => '20:00',
        'estado' => 'Completado'
    ]
];

// Reservas recientes
$reservas_recientes = [
    [
        'id' => 'RES-001',
        'cliente' => 'María González',
        'tour' => 'City Tour Lima',
        'fecha' => '2025-08-25',
        'pasajeros' => 2,
        'monto' => 100.00,
        'estado' => 'Confirmada'
    ],
    [
        'id' => 'RES-002',
        'cliente' => 'Carlos Mendez',
        'tour' => 'Líneas de Nazca',
        'fecha' => '2025-08-26',
        'pasajeros' => 4,
        'monto' => 1000.00,
        'estado' => 'Pendiente'
    ],
    [
        'id' => 'RES-003',
        'cliente' => 'Ana Silva',
        'tour' => 'Islas Ballestas',
        'fecha' => '2025-08-24',
        'pasajeros' => 3,
        'monto' => 360.00,
        'estado' => 'Completada'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Antares Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar-item:hover { background-color: rgba(59, 130, 246, 0.1); }
        .active { background-color: rgba(59, 130, 246, 0.2); border-right: 3px solid #3b82f6; }
        
        /* Mejoras para responsividad móvil */
        @media (max-width: 768px) {
            .sidebar-fixed { 
                position: fixed; 
                top: 0; 
                left: 0; 
                height: 100vh; 
                z-index: 50; 
                transform: translateX(-100%); 
                transition: transform 0.3s ease-in-out; 
                width: 16rem !important; /* Forzar ancho completo en móvil */
            }
            .sidebar-open { transform: translateX(0); }
            .overlay { 
                position: fixed; 
                inset: 0; 
                background-color: rgba(0, 0, 0, 0.5); 
                z-index: 40; 
            }
            .mobile-header { 
                position: sticky; 
                top: 0; 
                z-index: 30; 
                background: white; 
            }
            .grid-responsive { 
                grid-template-columns: 1fr; 
            }
            .grid-responsive-2 { 
                grid-template-columns: repeat(2, 1fr); 
            }
            .text-responsive { 
                font-size: 0.875rem; 
            }
            .card-mobile { 
                padding: 1rem; 
            }
            .btn-mobile { 
                padding: 0.75rem 1rem; 
                font-size: 0.875rem; 
            }
            .form-mobile { 
                padding: 1rem; 
            }
            .table-mobile { 
                font-size: 0.75rem; 
            }
            .overflow-x-scroll { 
                overflow-x: auto; 
                -webkit-overflow-scrolling: touch; 
            }
        }
        
        /* Estilos específicos para desktop - sidebar retraíble */
        @media (min-width: 769px) {
            .sidebar-fixed {
                position: relative;
                transform: translateX(0) !important;
                transition: width 0.3s ease-in-out;
            }
        }
        
        @media (max-width: 480px) {
            .grid-responsive { 
                grid-template-columns: 1fr; 
            }
            .grid-responsive-2 { 
                grid-template-columns: 1fr; 
            }
            .text-xs-mobile { 
                font-size: 0.75rem; 
            }
            .p-xs-mobile { 
                padding: 0.75rem; 
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen relative" x-data="{ sidebarOpen: false, currentModule: 'dashboard' }">
        
        <!-- Overlay para móvil -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="overlay md:hidden"
             style="display: none;"></div>
        
        <!-- Sidebar -->
        <div class="bg-white shadow-lg sidebar-fixed md:relative md:translate-x-0 w-64"
             :class="{ 
                'sidebar-open': sidebarOpen, 
                'md:w-16': !sidebarOpen && window.innerWidth >= 768,
                'md:w-64': sidebarOpen || window.innerWidth < 768
             }"
             x-transition:enter="transition-transform ease-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition-transform ease-in duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">>
            <div class="p-4">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-lg font-bold text-blue-600 truncate">Antares Travel</h1>
                </div>
            </div>
            
            <nav class="mt-8 px-2">
                <!-- Dashboard -->
                <a href="#" @click="currentModule = 'dashboard'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'dashboard' ? 'active' : ''">
                    <i class="fas fa-tachometer-alt text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Dashboard</span>
                </a>
                
                <!-- Módulo de Reservas -->
                <a href="#" @click="currentModule = 'reservas'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'reservas' ? 'active' : ''">
                    <i class="fas fa-calendar-check text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Reservas</span>
                </a>
                
                <!-- Tours Diarios -->
                <a href="#" @click="currentModule = 'tours-diarios'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'tours-diarios' ? 'active' : ''">
                    <i class="fas fa-map-marked-alt text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Tours Diarios</span>
                </a>
                
                <!-- Gestión de Tours -->
                <a href="#" @click="currentModule = 'tours'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'tours' ? 'active' : ''">
                    <i class="fas fa-route text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Gestión Tours</span>
                </a>
                
                <!-- Gestión de Usuarios -->
                <a href="#" @click="currentModule = 'usuarios'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'usuarios' ? 'active' : ''">
                    <i class="fas fa-users text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Usuarios</span>
                </a>
                
                <!-- Personal -->
                <a href="#" @click="currentModule = 'personal'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'personal' ? 'active' : ''">
                    <i class="fas fa-id-badge text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Personal</span>
                </a>
                
                <!-- Vehículos -->
                <a href="#" @click="currentModule = 'vehiculos'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'vehiculos' ? 'active' : ''">
                    <i class="fas fa-car text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Vehículos</span>
                </a>
                
                <!-- Reportes -->
                <a href="#" @click="currentModule = 'reportes'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'reportes' ? 'active' : ''">
                    <i class="fas fa-chart-bar text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Reportes</span>
                </a>
                
                <!-- Configuración -->
                <a href="#" @click="currentModule = 'configuracion'; if(window.innerWidth < 768) sidebarOpen = false" 
                   class="sidebar-item flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 transition-colors rounded-lg mb-1"
                   :class="currentModule === 'configuracion' ? 'active' : ''">
                    <i class="fas fa-cog text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Configuración</span>
                </a>
            </nav>
            
            <!-- Logout -->
            <div class="absolute bottom-4 left-0 right-0 px-4">
                <a href="../auth/logout.php" class="sidebar-item flex items-center px-4 py-3 text-red-600 hover:text-red-800 transition-colors rounded-lg hover:bg-red-50">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                    <span x-show="sidebarOpen || window.innerWidth < 768" class="ml-3 text-sm md:text-base">Cerrar Sesión</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b mobile-header">
                <div class="flex items-center justify-between px-4 md:px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-lg md:text-xl font-semibold text-gray-800 truncate" x-text="
                            currentModule === 'dashboard' ? 'Panel de Control' :
                            currentModule === 'reservas' ? 'Módulo de Reservas' :
                            currentModule === 'tours-diarios' ? 'Tours Diarios' :
                            currentModule === 'tours' ? 'Gestión de Tours' :
                            currentModule === 'usuarios' ? 'Gestión de Usuarios' :
                            currentModule === 'personal' ? 'Gestión de Personal' :
                            currentModule === 'vehiculos' ? 'Gestión de Vehículos' :
                            currentModule === 'reportes' ? 'Reportes y Estadísticas' :
                            currentModule === 'configuracion' ? 'Configuración del Sistema' : 'Panel de Control'
                        "></h2>
                    </div>
                    <div class="flex items-center space-x-2 md:space-x-4">
                        <span class="text-gray-600 text-sm md:text-base hidden sm:inline">Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?></span>
                        <img src="<?php echo isset($usuario['avatar_url']) && $usuario['avatar_url'] ? $usuario['avatar_url'] : '/assets/default-avatar.png'; ?>" 
                             alt="Avatar" class="w-8 h-8 md:w-10 md:h-10 rounded-full">
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                
                <!-- Dashboard Principal -->
                <div x-show="currentModule === 'dashboard'">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
                        <!-- Card Reservas -->
                        <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border card-mobile">
                            <div class="flex items-center">
                                <div class="p-2 md:p-3 bg-blue-100 rounded-full">
                                    <i class="fas fa-calendar-check text-blue-600 text-lg md:text-xl"></i>
                                </div>
                                <div class="ml-3 md:ml-4 min-w-0">
                                    <h3 class="text-gray-500 text-xs md:text-sm">Total Reservas</h3>
                                    <p class="text-lg md:text-2xl font-bold text-gray-800 truncate"><?php echo $stats['reservas']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Tours -->
                        <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border card-mobile">
                            <div class="flex items-center">
                                <div class="p-2 md:p-3 bg-green-100 rounded-full">
                                    <i class="fas fa-route text-green-600 text-lg md:text-xl"></i>
                                </div>
                                <div class="ml-3 md:ml-4 min-w-0">
                                    <h3 class="text-gray-500 text-xs md:text-sm">Tours Activos</h3>
                                    <p class="text-lg md:text-2xl font-bold text-gray-800 truncate"><?php echo $stats['tours']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Usuarios -->
                        <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border card-mobile">
                            <div class="flex items-center">
                                <div class="p-2 md:p-3 bg-purple-100 rounded-full">
                                    <i class="fas fa-users text-purple-600 text-lg md:text-xl"></i>
                                </div>
                                <div class="ml-3 md:ml-4 min-w-0">
                                    <h3 class="text-gray-500 text-xs md:text-sm">Usuarios Activos</h3>
                                    <p class="text-lg md:text-2xl font-bold text-gray-800 truncate"><?php echo $stats['usuarios']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Ventas -->
                        <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border card-mobile">
                            <div class="flex items-center">
                                <div class="p-2 md:p-3 bg-yellow-100 rounded-full">
                                    <i class="fas fa-dollar-sign text-yellow-600 text-lg md:text-xl"></i>
                                </div>
                                <div class="ml-3 md:ml-4 min-w-0">
                                    <h3 class="text-gray-500 text-xs md:text-sm">Ventas del Mes</h3>
                                    <p class="text-lg md:text-2xl font-bold text-gray-800 truncate">S/. <?php echo number_format($stats['ventas_mes'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Accesos Rápidos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                        <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                            <h3 class="text-base md:text-lg font-semibold mb-3 md:mb-4 text-gray-800">
                                <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                                Nueva Reserva
                            </h3>
                            <p class="text-gray-600 mb-3 md:mb-4 text-sm md:text-base">Crear una nueva reserva para un cliente</p>
                            <button @click="currentModule = 'reservas'" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors btn-mobile w-full md:w-auto">
                                Crear Reserva
                            </button>
                        </div>
                        
                        <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                            <h3 class="text-base md:text-lg font-semibold mb-3 md:mb-4 text-gray-800">
                                <i class="fas fa-calendar-plus text-green-600 mr-2"></i>
                                Tour Diario
                            </h3>
                            <p class="text-gray-600 mb-3 md:mb-4 text-sm md:text-base">Programar tour para el día de hoy</p>
                            <button @click="currentModule = 'tours-diarios'" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors btn-mobile w-full md:w-auto">
                                Programar Tour
                            </button>
                        </div>
                        
                        <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border md:col-span-2 lg:col-span-1">
                            <h3 class="text-base md:text-lg font-semibold mb-3 md:mb-4 text-gray-800">
                                <i class="fas fa-chart-line text-purple-600 mr-2"></i>
                                Ver Reportes
                            </h3>
                            <p class="text-gray-600 mb-3 md:mb-4 text-sm md:text-base">Consultar estadísticas y reportes</p>
                            <button @click="currentModule = 'reportes'" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors btn-mobile w-full md:w-auto">
                                Ver Reportes
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Reservas -->
                <div x-show="currentModule === 'reservas'" class="space-y-4 md:space-y-6">
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border form-mobile">
                        <h3 class="text-base md:text-lg font-semibold mb-4">Crear Nueva Reserva (Administrador)</h3>
                        <form class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tour</label>
                                    <select id="tour-select" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base" onchange="actualizarDatosTour()">
                                        <option value="">Seleccionar tour...</option>
                                        <?php foreach($tours_ejemplo as $tour): ?>
                                            <option value="<?php echo $tour['id']; ?>" 
                                                    data-precio="<?php echo $tour['precio']; ?>"
                                                    data-salida="<?php echo $tour['hora_salida']; ?>"
                                                    data-retorno="<?php echo $tour['hora_retorno']; ?>">
                                                <?php echo $tour['nombre']; ?> - S/. <?php echo number_format($tour['precio'], 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha del Tour</label>
                                    <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Hora de Salida</label>
                                    <input type="time" id="hora-salida" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-sm md:text-base" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Hora de Retorno</label>
                                    <input type="time" id="hora-retorno" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-sm md:text-base" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Precio por Persona</label>
                                    <input type="text" id="precio-persona" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-sm md:text-base" readonly placeholder="S/. 0.00">
                                </div>
                            </div>
                            
                            <!-- Sección de Pasajeros -->
                            <div class="border-t pt-4">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-2">
                                    <h4 class="text-sm md:text-base font-semibold text-gray-800">Datos de Pasajeros</h4>
                                    <button type="button" class="bg-green-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-green-700 btn-mobile">
                                        <i class="fas fa-plus mr-1"></i> Agregar Pasajero
                                    </button>
                                </div>
                                
                                <div class="space-y-4" id="pasajeros-container">
                                    <div class="bg-gray-50 p-3 md:p-4 rounded-lg">
                                        <h5 class="font-medium text-gray-700 mb-3 text-sm md:text-base">Pasajero 1</h5>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                            <input type="text" placeholder="Nombre" class="border border-gray-300 rounded-lg px-3 py-2 text-sm md:text-base">
                                            <input type="text" placeholder="Apellido" class="border border-gray-300 rounded-lg px-3 py-2 text-sm md:text-base">
                                            <input type="text" placeholder="DNI/Pasaporte" class="border border-gray-300 rounded-lg px-3 py-2 text-sm md:text-base">
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                                            <input type="text" placeholder="Nacionalidad" class="border border-gray-300 rounded-lg px-3 py-2 text-sm md:text-base">
                                            <input type="tel" placeholder="Teléfono" class="border border-gray-300 rounded-lg px-3 py-2 text-sm md:text-base">
                                            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm md:text-base">
                                                <option value="adulto">Adulto</option>
                                                <option value="niño">Niño</option>
                                                <option value="senior">Senior</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Observaciones y Monto Extra (Solo Admin) -->
                            <div class="border-t pt-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                        <textarea rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base" placeholder="Observaciones adicionales..."></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Monto Extra</label>
                                        <input type="number" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base" placeholder="0.00">
                                        <p class="text-xs md:text-sm text-gray-500 mt-1">Recargo o descuento adicional</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tipo de Pago y Total -->
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Pago</label>
                                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                            <option value="efectivo">Efectivo</option>
                                            <option value="tarjeta">Tarjeta</option>
                                            <option value="transferencia">Transferencia</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Monto Total</label>
                                        <div class="text-xl md:text-2xl font-bold text-blue-600">S/. 0.00</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors btn-mobile">
                                    <i class="fas fa-save mr-2"></i>Guardar Reserva
                                </button>
                                <button type="button" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition-colors btn-mobile">
                                    <i class="fas fa-times mr-2"></i>Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tours Diarios -->
                <div x-show="currentModule === 'tours-diarios'" class="space-y-4 md:space-y-6">
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border form-mobile">
                        <h3 class="text-base md:text-lg font-semibold mb-4">Registrar Tour Diario</h3>
                        <form class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha del Tour</label>
                                    <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tour</label>
                                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                        <option value="">Seleccionar tour...</option>
                                        <option value="1">City Tour Lima</option>
                                        <option value="2">Islas Ballestas</option>
                                        <option value="3">Líneas de Nazca</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Guía Asignado</label>
                                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                        <option value="">Seleccionar guía...</option>
                                        <option value="1">Juan Pérez</option>
                                        <option value="2">María García</option>
                                        <option value="3">Carlos López</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Chofer Asignado</label>
                                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                        <option value="">Seleccionar chofer...</option>
                                        <option value="1">Roberto Silva</option>
                                        <option value="2">Luis Mendoza</option>
                                        <option value="3">José Ramírez</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Vehículo Asignado</label>
                                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                        <option value="">Seleccionar vehículo...</option>
                                        <option value="1">Bus ABC-123</option>
                                        <option value="2">Van XYZ-456</option>
                                        <option value="3">Minibus DEF-789</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de Adultos</label>
                                    <input type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de Niños</label>
                                    <input type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Hora de Salida</label>
                                    <input type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Hora de Retorno</label>
                                    <input type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                <textarea rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm md:text-base" placeholder="Observaciones del tour..."></textarea>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                                <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors btn-mobile">
                                    <i class="fas fa-calendar-plus mr-2"></i>Registrar Tour Diario
                                </button>
                                <button type="button" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition-colors btn-mobile">
                                    <i class="fas fa-times mr-2"></i>Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de Tours del Día -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                        <h3 class="text-base md:text-lg font-semibold mb-4">Tours Programados para Hoy</h3>
                        <div class="overflow-x-scroll">
                            <table class="min-w-full table-auto table-mobile">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-2 md:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tour</th>
                                        <th class="px-2 md:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Guía</th>
                                        <th class="px-2 md:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Chofer</th>
                                        <th class="px-2 md:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Vehículo</th>
                                        <th class="px-2 md:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pasajeros</th>
                                        <th class="px-2 md:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Hora</th>
                                        <th class="px-2 md:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-2 md:px-4 py-2 text-xs md:text-sm text-gray-900">City Tour Lima</td>
                                        <td class="px-2 md:px-4 py-2 text-xs md:text-sm text-gray-600">Juan Pérez</td>
                                        <td class="px-2 md:px-4 py-2 text-xs md:text-sm text-gray-600 hidden sm:table-cell">Roberto Silva</td>
                                        <td class="px-2 md:px-4 py-2 text-xs md:text-sm text-gray-600 hidden sm:table-cell">ABC-123</td>
                                        <td class="px-2 md:px-4 py-2 text-xs md:text-sm text-gray-600">15A, 3N</td>
                                        <td class="px-2 md:px-4 py-2 text-xs md:text-sm text-gray-600 hidden md:table-cell">09:00-17:00</td>
                                        <td class="px-2 md:px-4 py-2">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Programado</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Otros módulos se cargarían aquí de manera similar -->
                <div x-show="currentModule === 'tours'" class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                    <h3 class="text-base md:text-lg font-semibold mb-4">Gestión de Tours</h3>
                    <p class="text-gray-600 text-sm md:text-base">Módulo para gestionar tours, crear nuevos, editar existentes, etc.</p>
                </div>
                
                <div x-show="currentModule === 'usuarios'" class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                    <h3 class="text-base md:text-lg font-semibold mb-4">Gestión de Usuarios</h3>
                    <p class="text-gray-600 text-sm md:text-base">Módulo para gestionar usuarios registrados en la plataforma.</p>
                </div>
                
                <div x-show="currentModule === 'personal'" class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                    <h3 class="text-base md:text-lg font-semibold mb-4">Gestión de Personal</h3>
                    <p class="text-gray-600 text-sm md:text-base">Módulo para gestionar guías, choferes y personal de la empresa.</p>
                </div>
                
                <div x-show="currentModule === 'vehiculos'" class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                    <h3 class="text-base md:text-lg font-semibold mb-4">Gestión de Vehículos</h3>
                    <p class="text-gray-600 text-sm md:text-base">Módulo para gestionar la flota de vehículos de la empresa.</p>
                </div>
                
                <div x-show="currentModule === 'reportes'" class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                    <h3 class="text-base md:text-lg font-semibold mb-4">Reportes y Estadísticas</h3>
                    <p class="text-gray-600 text-sm md:text-base">Módulo para generar reportes de ventas, reservas, etc.</p>
                </div>
                
                <div x-show="currentModule === 'configuracion'" class="bg-white p-4 md:p-6 rounded-lg shadow-sm border">
                    <h3 class="text-base md:text-lg font-semibold mb-4">Configuración del Sistema</h3>
                    <p class="text-gray-600 text-sm md:text-base">Módulo para configurar parámetros del sistema.</p>
                </div>
                
            </main>
        </div>
    </div>
    
    <script>
        // Update tour details when selection changes
        function updateTourDetails() {
            const tourSelect = document.getElementById('tour-select');
            const selectedTourId = tourSelect.value;
            
            if (selectedTourId) {
                const tours = <?php echo json_encode($tours_ejemplo); ?>;
                const selectedTour = tours.find(tour => tour.id == selectedTourId);
                
                if (selectedTour) {
                    document.getElementById('hora-salida').value = selectedTour.hora_salida;
                    document.getElementById('hora-retorno').value = selectedTour.hora_retorno;
                    document.getElementById('precio-persona').value = 'S/. ' + selectedTour.precio;
                }
            } else {
                document.getElementById('hora-salida').value = '';
                document.getElementById('hora-retorno').value = '';
                document.getElementById('precio-persona').value = '';
            }
        }
        
        // Add event listener for tour selection
        document.addEventListener('DOMContentLoaded', function() {
            const tourSelect = document.getElementById('tour-select');
            if (tourSelect) {
                tourSelect.addEventListener('change', updateTourDetails);
            }
            
            // Function to handle desktop/mobile sidebar behavior
            function handleResponsiveSidebar() {
                // No need to auto-close sidebar on desktop resize
                // Desktop sidebar should remain retractable
                // Mobile behavior is handled by Alpine.js conditions
            }
            
            // Listen for window resize events
            window.addEventListener('resize', handleResponsiveSidebar);
            
            // Initial call
            handleResponsiveSidebar();
        });
        
        // Function to ensure proper mobile compatibility
        function actualizarDatosTour() {
            updateTourDetails();
        }
    </script>
</body>
</html>
