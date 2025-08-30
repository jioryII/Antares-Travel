<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['REQUEST_URI'];

// Función para determinar rutas activas
function isActiveRoute($route) {
    global $current_page, $current_path;
    
    if (strpos($current_path, $route) !== false) {
        return true;
    }
    
    return $current_page === $route;
}

// Función para generar URL relativa desde la ubicación actual
function getRelativeUrl($target) {
    $current_path = $_SERVER['REQUEST_URI'];
    
    // Si estamos en la raíz del admin
    if (strpos($current_path, '/pages/') === false) {
        return $target;
    }
    
    // Si estamos en una subcarpeta de pages
    $depth = substr_count(str_replace('/Antares-Travel/src/admin/', '', $current_path), '/');
    $prefix = str_repeat('../', $depth - 1);
    
    return $prefix . $target;
}
?>

<!-- Overlay para móvil -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"></div>

<aside id="sidebar" class="bg-gradient-to-b from-gray-900 via-gray-900 to-gray-800 text-white w-16 lg:w-64 h-screen flex flex-col fixed left-0 top-0 z-40 transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out shadow-2xl border-r border-gray-700/50 overflow-hidden">
    <!-- Logo -->
    <div class="relative p-4 lg:p-4 border-b border-gray-700 mt-16 lg:mt-0 flex-shrink-0">
        <!-- Gradient background -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/10 to-purple-600/10"></div>
        
        <div class="relative flex items-center group cursor-pointer hover:scale-105 transition-transform duration-300">
            <!-- Logo con animación -->
            <div class="w-12 h-12 lg:w-12 lg:h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-3 lg:mr-3 shadow-lg group-hover:shadow-blue-500/25 transition-all duration-300 overflow-hidden">
                <img src="/Antares-Travel/imagenes/antares_logo.png" 
                     alt="Antares Travel Logo" 
                     class="w-10 h-10 lg:w-10 lg:h-10 object-contain group-hover:scale-110 transition-transform duration-300 filter brightness-0 invert">
            </div>
            
            <!-- Texto del logo -->
            <div class="flex-1 block lg:block">
                <h2 class="text-xl lg:text-xl font-bold bg-gradient-to-r from-white to-blue-200 bg-clip-text text-transparent">
                    Antares
                </h2>
                <p class="text-sm text-blue-300/80 font-medium tracking-wide lg:text-xs">Travel Admin</p>
            </div>
            
            <!-- Indicador de versión -->
            <div class="block lg:block">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-300 border border-blue-500/30">
                    v1.0
                </span>
            </div>
        </div>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 px-3 lg:px-4 py-3 lg:py-4 space-y-2 lg:space-y-1 overflow-y-auto custom-scrollbar min-h-0">
        <!-- Dashboard -->
        <a href="<?php echo getRelativeUrl('../pages/dashboard/'); ?>" 
           class="<?php echo isActiveRoute('dashboard') ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg scale-105' : 'text-gray-300 hover:bg-gray-800/70 hover:text-white'; ?> group flex items-center px-4 lg:px-4 py-3 lg:py-2.5 rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-md">
            <div class="<?php echo isActiveRoute('dashboard') ? 'bg-white/20' : 'bg-gray-700/50 group-hover:bg-blue-600/50'; ?> p-2 lg:p-2 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                <i class="fas fa-tachometer-alt w-4 h-4 lg:w-4 lg:h-4 text-center"></i>
            </div>
            <span class="font-medium text-base lg:text-sm">Dashboard</span>
            <?php if (isActiveRoute('dashboard')): ?>
                <div class="ml-auto">
                    <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                </div>
            <?php endif; ?>
        </a>

        <!-- Divider -->
        <div class="h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent my-3 lg:my-3"></div>

        <!-- Tours Section -->
        <div class="space-y-2 lg:space-y-1">
            <div class="flex items-center px-4 lg:px-4 py-2 lg:py-2">
                <div class="w-6 lg:w-6 h-px bg-gradient-to-r from-blue-500 to-purple-500 mr-3 lg:mr-2"></div>
                <div class="text-sm font-bold text-blue-400 uppercase tracking-wider flex-1 lg:text-xs">Tours</div>
                <i class="fas fa-map-marked-alt text-blue-400/60 text-sm lg:text-xs"></i>
            </div>
            
            <a href="<?php echo getRelativeUrl('../pages/tours/'); ?>" 
               class="<?php echo isActiveRoute('tours') && !isActiveRoute('tours_diarios') ? 'bg-gradient-to-r from-blue-600/80 to-blue-700/80 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800/50 hover:text-blue-200'; ?> group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
                <div class="<?php echo isActiveRoute('tours') && !isActiveRoute('tours_diarios') ? 'bg-white/20' : 'bg-gray-700/50 group-hover:bg-blue-600/50'; ?> p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                    <i class="fas fa-map-marked-alt text-sm lg:text-sm"></i>
                </div>
                <span class="text-base lg:text-sm font-medium">Gestión de Tours</span>
            </a>
            
            <a href="<?php echo getRelativeUrl('../pages/tours/tours_diarios.php'); ?>" 
               class="<?php echo isActiveRoute('tours_diarios') ? 'bg-gradient-to-r from-green-600/80 to-green-700/80 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800/50 hover:text-green-200'; ?> group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
                <div class="<?php echo isActiveRoute('tours_diarios') ? 'bg-white/20' : 'bg-gray-700/50 group-hover:bg-green-600/50'; ?> p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                    <i class="fas fa-calendar-day text-sm lg:text-sm"></i>
                </div>
                <span class="text-base lg:text-sm font-medium">Tours Diarios</span>
            </a>
        </div>

        <!-- Divider -->
        <div class="h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent my-3 lg:my-3"></div>

        <!-- Reservas -->
        <a href="<?php echo getRelativeUrl('../pages/reservas/'); ?>" 
           class="<?php echo isActiveRoute('reservas') ? 'bg-gradient-to-r from-purple-600/80 to-purple-700/80 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800/50 hover:text-purple-200'; ?> group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
            <div class="<?php echo isActiveRoute('reservas') ? 'bg-white/20' : 'bg-gray-700/50 group-hover:bg-purple-600/50'; ?> p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                <i class="fas fa-calendar-check text-sm lg:text-sm"></i>
            </div>
            <span class="text-base lg:text-sm font-medium">Reservas</span>
        </a>

        <!-- Usuarios -->
        <a href="<?php echo getRelativeUrl('../pages/usuarios/'); ?>" 
           class="<?php echo isActiveRoute('usuarios') ? 'bg-gradient-to-r from-indigo-600/80 to-indigo-700/80 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800/50 hover:text-indigo-200'; ?> group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
            <div class="<?php echo isActiveRoute('usuarios') ? 'bg-white/20' : 'bg-gray-700/50 group-hover:bg-indigo-600/50'; ?> p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                <i class="fas fa-users text-sm lg:text-sm"></i>
            </div>
            <span class="text-base lg:text-sm font-medium">Usuarios</span>
        </a>

        <!-- Divider -->
        <div class="h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent my-3 lg:my-3"></div>

        <!-- Personal Section -->
        <div class="space-y-2 lg:space-y-1">
            <div class="flex items-center px-4 lg:px-4 py-2 lg:py-2">
                <div class="w-6 lg:w-6 h-px bg-gradient-to-r from-orange-500 to-red-500 mr-3 lg:mr-2"></div>
                <div class="text-sm font-bold text-orange-400 uppercase tracking-wider flex-1 lg:text-xs">Personal</div>
                <i class="fas fa-user-tie text-orange-400/60 text-sm lg:text-xs"></i>
            </div>
            
            <a href="<?php echo getRelativeUrl('../pages/guias/'); ?>" 
               class="<?php echo isActiveRoute('guias') ? 'bg-gradient-to-r from-orange-600/80 to-orange-700/80 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800/50 hover:text-orange-200'; ?> group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
                <div class="<?php echo isActiveRoute('guias') ? 'bg-white/20' : 'bg-gray-700/50 group-hover:bg-orange-600/50'; ?> p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                    <i class="fas fa-user-tie text-sm lg:text-sm"></i>
                </div>
                <span class="text-base lg:text-sm font-medium">Guías</span>
            </a>
            
            <a href="<?php echo getRelativeUrl('../pages/choferes/'); ?>" 
               class="<?php echo isActiveRoute('choferes') ? 'bg-gradient-to-r from-amber-600/80 to-amber-700/80 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800/50 hover:text-amber-200'; ?> group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
                <div class="<?php echo isActiveRoute('choferes') ? 'bg-white/20' : 'bg-gray-700/50 group-hover:bg-amber-600/50'; ?> p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                    <i class="fas fa-user-cog text-sm lg:text-sm"></i>
                </div>
                <span class="text-base lg:text-sm font-medium">Choferes</span>
            </a>
            
            <a href="<?php echo getRelativeUrl('../pages/vehiculos/'); ?>" 
               class="<?php echo isActiveRoute('vehiculos') ? 'bg-gradient-to-r from-teal-600/80 to-teal-700/80 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800/50 hover:text-teal-200'; ?> group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
                <div class="<?php echo isActiveRoute('vehiculos') ? 'bg-white/20' : 'bg-gray-700/50 group-hover:bg-teal-600/50'; ?> p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                    <i class="fas fa-bus text-sm lg:text-sm"></i>
                </div>
                <span class="text-base lg:text-sm font-medium">Vehículos</span>
            </a>
        </div>

        <!-- Divider -->
        <!-- <div class="h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent my-3 lg:my-3"></div> -->

        <!-- Gestión Section - MÓDULO COMENTADO TEMPORALMENTE 
        <div class="space-y-2 lg:space-y-1">
            <div class="flex items-center px-4 lg:px-4 py-2 lg:py-2">
                <div class="w-6 lg:w-6 h-px bg-gradient-to-r from-cyan-500 to-blue-500 mr-3 lg:mr-2"></div>
                <div class="text-sm font-bold text-cyan-400 uppercase tracking-wider flex-1 lg:text-xs">Gestión</div>
                <i class="fas fa-cogs text-cyan-400/60 text-sm lg:text-xs"></i>
            </div>
            
            <a href="pages/calendario/index.php" 
               class="text-gray-300 hover:bg-gray-800/50 hover:text-cyan-200 group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
                <div class="bg-gray-700/50 group-hover:bg-cyan-600/50 p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                    <i class="fas fa-calendar-alt text-sm lg:text-sm"></i>
                </div>
                <span class="text-base lg:text-sm font-medium">Calendario</span>
            </a>

            <a href="pages/experiencias/index.php" 
               class="text-gray-300 hover:bg-gray-800/50 hover:text-pink-200 group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
                <div class="bg-gray-700/50 group-hover:bg-pink-600/50 p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                    <i class="fas fa-camera text-sm lg:text-sm"></i>
                </div>
                <span class="text-base lg:text-sm font-medium">Muro de Fotos</span>
            </a>
        </div>
        -->

        <!-- Divider -->
        <!-- <div class="h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent my-3 lg:my-3"></div> -->

        <!-- Reportes - MÓDULO COMENTADO TEMPORALMENTE
        <a href="pages/reportes/index.php" 
           class="text-gray-300 hover:bg-gray-800/50 hover:text-emerald-200 group flex items-center px-4 lg:px-4 py-3 lg:py-2 rounded-lg transition-all duration-300 hover:translate-x-1">
            <div class="bg-gray-700/50 group-hover:bg-emerald-600/50 p-2 lg:p-1.5 rounded-lg mr-4 lg:mr-3 transition-all duration-300 flex-shrink-0">
                <i class="fas fa-chart-bar text-sm lg:text-sm"></i>
            </div>
            <span class="text-base lg:text-sm font-medium">Reportes</span>
            <div class="ml-auto lg:block">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <i class="fas fa-chart-line mr-1"></i>
                    <span class="hidden lg:inline">Analytics</span>
                </span>
            </div>
        </a>
        -->
    </nav>

    <!-- Footer del sidebar -->
    <div class="relative p-4 lg:p-3 border-t border-gray-700/50 flex-shrink-0">
        <!-- Gradient background -->
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent"></div>
        
        <div class="relative">
            <!-- Status indicator -->
            <div class="flex items-center justify-center lg:justify-between mb-2 lg:mb-2">
                <div class="flex items-center lg:flex">
                    <div class="w-2 h-2 lg:w-1.5 lg:h-1.5 bg-green-400 rounded-full animate-pulse mr-3 lg:mr-2"></div>
                    <span class="text-sm lg:text-xs text-green-400 font-medium">Online</span>
                </div>
                
                <!-- Quick actions -->
                <div class="flex lg:flex space-x-2 lg:space-x-1">
                    <button class="p-2 lg:p-1 text-gray-400 hover:text-white hover:bg-gray-700/50 rounded transition-all duration-300 group" title="Configuración">
                        <i class="fas fa-cog text-sm lg:text-xs group-hover:rotate-90 transition-transform duration-300"></i>
                    </button>
                    <button class="p-2 lg:p-1 text-gray-400 hover:text-white hover:bg-gray-700/50 rounded transition-all duration-300" title="Ayuda">
                        <i class="fas fa-question-circle text-sm lg:text-xs"></i>
                    </button>
                </div>
            </div>
            
            <!-- Copyright info -->
            <div class="text-center">
                <div class="text-sm lg:text-xs text-gray-400/80 space-y-1 lg:space-y-0.5">
                    <p class="font-medium">
                        <span>&copy; 2025 Antares Travel</span>
                    </p>
                    <p class="text-gray-500 flex items-center justify-center">
                        <span>v1.0</span>
                        <span class="mx-2 lg:mx-1">•</span>
                        <span class="w-1 h-1 bg-blue-400 rounded-full"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Estilos personalizados para el sidebar -->
<style>
    /* Custom scrollbar */
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: #3b82f6 rgba(55, 65, 81, 0.1);
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(55, 65, 81, 0.1);
        border-radius: 2px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #3b82f6, #1d4ed8);
        border-radius: 2px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #2563eb, #1e40af);
    }
    
    /* Ensure proper height calculations */
    #sidebar {
        height: 100vh;
        max-height: 100vh;
        opacity: 1;
    }
    
    #sidebar nav {
        min-height: 0;
        max-height: calc(100vh - 160px); /* Ajusta según header y footer */
    }
    
    /* Responsive sidebar adjustments */
    @media (max-width: 1023px) {
        #sidebar {
            transform: translateX(-100%);
            width: 320px;
            z-index: 40;
            transition: transform 0.3s ease-in-out;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        #sidebar.show {
            transform: translateX(0) !important;
        }
        
        #sidebar nav {
            max-height: calc(100vh - 180px);
        }
        
        /* Asegurar que el overlay funcione correctamente */
        #sidebarOverlay {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: all 0.3s ease-in-out;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        
        #sidebarOverlay.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        
        /* Efectos de entrada más suaves en móviles */
        #sidebar a {
            transform: translateX(0);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        #sidebar a:active {
            transform: scale(0.96) translateX(4px);
            transition-duration: 0.1s;
        }
        
        /* Mejor feedback táctil */
        #sidebar a:hover {
            transform: translateX(6px) scale(1.02);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
    }
    
    /* Mobile optimizations */
    @media (max-width: 640px) {
        #sidebar {
            width: 320px;
            max-width: 90vw;
        }
        
        #sidebar.show {
            transform: translateX(0) !important;
            width: 320px;
            max-width: 90vw;
        }
        
        #sidebar nav {
            max-height: calc(100vh - 180px);
            padding-bottom: 1rem;
        }
        
        /* Mejorar la visibilidad del texto en móviles */
        #sidebar a span {
            font-size: 1rem;
            font-weight: 500;
            letter-spacing: 0.025em;
        }
        
        /* Iconos más grandes en móviles */
        #sidebar .fas {
            font-size: 1.125rem;
        }
        
        /* Mejores espacios entre elementos */
        #sidebar .space-y-2 > * + * {
            margin-top: 0.75rem;
        }
        
        /* Sombras más pronunciadas para móviles */
        #sidebar a:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(8px) scale(1.02);
        }
        
        /* Divisores más visibles */
        #sidebar .h-px {
            height: 2px;
            border-radius: 1px;
        }
    }
    
    /* Altura mínima para pantallas muy pequeñas */
    @media (max-height: 600px) {
        #sidebar nav {
            max-height: calc(100vh - 100px);
        }
        
        #sidebar .logo-section {
            padding: 0.5rem;
        }
        
        #sidebar .footer-section {
            padding: 0.5rem;
        }
    }
    
    /* Extra small screens */
    @media (max-height: 500px) {
        #sidebar nav {
            max-height: calc(100vh - 80px);
        }
        
        .divider {
            margin: 0.25rem 0;
        }
        
        .nav-item {
            padding: 0.375rem 0.5rem;
        }
    }
    
    /* Logo animation */
    .logo-rocket {
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
    }
    
    /* Navigation item hover effects */
    .nav-item {
        position: relative;
        overflow: hidden;
        will-change: transform;
        backface-visibility: hidden;
    }
    
    .nav-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s ease-out;
        pointer-events: none;
    }
    
    .nav-item:hover::before {
        left: 100%;
    }
    
    /* Animación de entrada para elementos del sidebar en móviles */
    @media (max-width: 1023px) {
        #sidebar.show nav a {
            animation: slideInFromLeft 0.4s ease-out forwards;
            opacity: 0;
            transform: translateX(-20px);
        }
        
        #sidebar.show nav a:nth-child(1) { animation-delay: 0.1s; }
        #sidebar.show nav a:nth-child(2) { animation-delay: 0.15s; }
        #sidebar.show nav a:nth-child(3) { animation-delay: 0.2s; }
        #sidebar.show nav a:nth-child(4) { animation-delay: 0.25s; }
        #sidebar.show nav a:nth-child(5) { animation-delay: 0.3s; }
        #sidebar.show nav a:nth-child(6) { animation-delay: 0.35s; }
        #sidebar.show nav a:nth-child(7) { animation-delay: 0.4s; }
        #sidebar.show nav a:nth-child(8) { animation-delay: 0.45s; }
        #sidebar.show nav a:nth-child(9) { animation-delay: 0.5s; }
        #sidebar.show nav a:nth-child(10) { animation-delay: 0.55s; }
        
        #sidebar.show nav > div {
            animation: slideInFromLeft 0.4s ease-out forwards;
            opacity: 0;
            transform: translateX(-20px);
        }
        
        #sidebar.show nav > div:nth-child(1) { animation-delay: 0.05s; }
        #sidebar.show nav > div:nth-child(2) { animation-delay: 0.1s; }
        #sidebar.show nav > div:nth-child(3) { animation-delay: 0.15s; }
        #sidebar.show nav > div:nth-child(4) { animation-delay: 0.2s; }
        #sidebar.show nav > div:nth-child(5) { animation-delay: 0.25s; }
        #sidebar.show nav > div:nth-child(6) { animation-delay: 0.3s; }
        #sidebar.show nav > div:nth-child(7) { animation-delay: 0.35s; }
        #sidebar.show nav > div:nth-child(8) { animation-delay: 0.4s; }
        #sidebar.show nav > div:nth-child(9) { animation-delay: 0.45s; }
        #sidebar.show nav > div:nth-child(10) { animation-delay: 0.5s; }
    }
    
    @keyframes slideInFromLeft {
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    /* Ripple effect styles */
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        pointer-events: none;
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        z-index: 0;
    }
    
    @keyframes ripple-animation {
        0% {
            transform: scale(0);
            opacity: 1;
        }
        100% {
            transform: scale(2);
            opacity: 0;
        }
    }
    
    /* Mobile sidebar overlay */
    @media (max-width: 1023px) {
        #sidebarOverlay {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: all 0.3s ease-in-out;
        }
        
        #sidebarOverlay.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
    }
    
    @media (min-width: 1024px) {
        #sidebarOverlay {
            display: none !important;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Performance optimizations */
    .nav-link, #sidebar a {
        will-change: transform, background-color;
        backface-visibility: hidden;
        transform-style: preserve-3d;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 1;
    }
    
    /* Prevent layout shifts during interactions */
    #sidebar a:active {
        transform: scale(0.98);
        transition-duration: 0.1s;
    }
    
    #sidebar a:not(:active):hover {
        transform: translateX(4px) scale(1.02);
    }
    
    #sidebar a:not(:hover):not(:active) {
        transform: translateX(0) scale(1);
    }
    
    /* Smooth scroll behavior */
    #sidebar nav {
        scroll-behavior: smooth;
    }
    
    /* Focus states for accessibility */
    #sidebar a:focus {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }
    
    /* Improved contrast for better readability */
    @media (prefers-reduced-motion: reduce) {
        #sidebar * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>

<!-- JavaScript para mejorar la responsividad -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Función para mostrar/ocultar sidebar en móvil
    function toggleSidebar() {
        if (window.innerWidth < 1024) {
            const isVisible = sidebar.classList.contains('show');
            
            if (isVisible) {
                // Ocultar sidebar
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = 'auto';
            } else {
                // Mostrar sidebar
                sidebar.classList.add('show');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden'; // Prevenir scroll del body
            }
        }
    }
    
    // Cerrar sidebar al hacer clic en overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        });
    }
    
    // Cerrar sidebar al redimensionar a desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Hacer la función disponible globalmente para el botón hamburguesa
    window.toggleSidebar = toggleSidebar;
    
    // Efecto de navegación suave
    const navLinks = document.querySelectorAll('#sidebar a');
    navLinks.forEach(link => {
        // Hacer que el contenido mantenga su posición
        link.style.position = 'relative';
        link.style.zIndex = '1';
        
        link.addEventListener('click', function(e) {
            // Prevenir múltiples ripples
            const existingRipple = this.querySelector('.ripple');
            if (existingRipple) {
                existingRipple.remove();
            }
            
            // Efecto de ripple mejorado
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height) * 1.2;
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.position = 'absolute';
            ripple.style.zIndex = '0';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            // Remover el ripple sin afectar el layout
            setTimeout(() => {
                if (ripple && ripple.parentNode) {
                    ripple.remove();
                }
            }, 600);
        });
    });
    
    // Lazy loading para iconos
    const icons = document.querySelectorAll('#sidebar i[class*="fas"]');
    icons.forEach(icon => {
        icon.style.willChange = 'transform';
    });
    
    // Optimización de scroll
    let ticking = false;
    const nav = document.querySelector('#sidebar nav');
    
    if (nav) {
        nav.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    // Aquí puedes agregar efectos de scroll si los necesitas
                    ticking = false;
                });
                ticking = true;
            }
        });
    }
    
    // Preload hover states con mejores transiciones
    const navItems = document.querySelectorAll('#sidebar .group');
    navItems.forEach(item => {
        // Configurar estilos iniciales para transiciones suaves
        item.style.transition = 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)';
        item.style.transformOrigin = 'left center';
        
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px) scale(1.02)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0) scale(1)';
        });
        
        // Prevenir cambios de tamaño durante el clic
        item.addEventListener('mousedown', function() {
            this.style.transform = 'translateX(2px) scale(0.98)';
        });
        
        item.addEventListener('mouseup', function() {
            this.style.transform = 'translateX(4px) scale(1.02)';
        });
        
        // Restablecer en caso de que el mouse salga durante el clic
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0) scale(1)';
        });
    });
    
    // Accesibilidad mejorada
    document.addEventListener('keydown', function(e) {
        // Cerrar sidebar con Escape
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Inicialización del sidebar
    function initSidebar() {
        // Asegurar estado inicial correcto según el tamaño de pantalla
        if (window.innerWidth < 1024) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        } else {
            // En desktop, asegurar que esté visible
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        // Debug: verificar que los elementos existen
        console.log('Sidebar element:', sidebar);
        console.log('Overlay element:', overlay);
        console.log('Window width:', window.innerWidth);
        
        // Hacer visible con animación suave
        setTimeout(() => {
            sidebar.style.opacity = '1';
        }, 100);
    }
    
    // Inicializar
    initSidebar();
    
    // Debug: función para verificar el estado
    window.debugSidebar = function() {
        console.log('Sidebar classes:', sidebar.className);
        console.log('Overlay classes:', overlay.className);
        console.log('Body overflow:', document.body.style.overflow);
        console.log('Window width:', window.innerWidth);
    };
});
</script>
