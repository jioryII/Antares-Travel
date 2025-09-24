<?php
require_once __DIR__ . '/../auth/middleware.php';
$admin = obtenerAdminActual();
?>

<header class="bg-gradient-to-r from-slate-50/95 via-white/95 to-gray-50/95 backdrop-blur-lg shadow-lg border-b border-gray-200/60 fixed top-0 left-0 right-0 z-20 before:absolute before:inset-0 before:bg-gradient-to-r before:from-blue-500/5 before:via-transparent before:to-purple-500/5 before:pointer-events-none">
    <div class="relative flex items-center justify-between px-4 lg:px-8 py-4">
        <!-- Espacio para el sidebar -->
        <div class="hidden lg:block" style="width: 256px;"></div>

        <!-- Botón hamburguesa para móvil -->
        <button id="sidebarToggle" class="lg:hidden p-2 text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
            <i class="fas fa-bars text-lg"></i>
        </button>

        <!-- Logo y título corporativo -->
        <div class="flex items-center">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-slate-900 rounded-lg flex items-center justify-center shadow-sm">
                    <i class="fas fa-map-marked-alt text-white text-lg"></i>
                </div>
                <!-- Título completo para desktop -->
                <div class="hidden lg:block">
                    <h1 class="text-xl font-light text-gray-900 tracking-tight">
                        Antares Travel
                    </h1>
                    <p class="text-sm text-gray-600 font-medium">Sistema de Administración</p>
                </div>
                <!-- Título simplificado para móvil y tablet -->
                <div class="lg:hidden">
                    <h1 class="text-lg font-semibold text-gray-900">Antares Travel</h1>
                </div>
            </div>
        </div>

        <!-- Panel de usuario profesional -->
        <div class="flex items-center space-x-3 lg:space-x-4">
            <!-- Información del administrador - Completa para desktop -->
            <div class="hidden lg:flex items-center space-x-3 bg-gray-50 rounded-lg px-4 py-2.5 border border-gray-200 hover:border-gray-300 transition-all duration-200">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-9 h-9 bg-slate-900 rounded-lg flex items-center justify-center shadow-sm">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($admin['nombre']); ?></p>
                        <div class="flex items-center space-x-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                <i class="fas fa-shield-alt mr-1 text-xs"></i>
                                <?php echo htmlspecialchars($admin['rol'] ?? 'Administrador'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Menú desplegable para desktop -->
                <div class="relative" id="userDropdownDesktop">
                    <button onclick="toggleDropdown('Desktop')" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all duration-200">
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                    
                    <div id="dropdownMenuDesktop" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg ring-1 ring-gray-200 z-50 border border-gray-100 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-slate-900 rounded-lg flex items-center justify-center shadow-sm">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($admin['nombre']); ?></p>
                                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($admin['email'] ?? 'admin@antares.com'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="py-1">
                            <a href="../mi_perfil/index.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors group">
                                <div class="w-8 h-8 bg-gray-100 group-hover:bg-gray-200 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                    <i class="fas fa-user-circle text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Mi Perfil</p>
                                    <p class="text-xs text-gray-500">Configuración personal</p>
                                </div>
                            </a>
                            <a href="../soporte/settings.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors group">
                                <div class="w-8 h-8 bg-gray-100 group-hover:bg-gray-200 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                    <i class="fas fa-cog text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Configuración</p>
                                    <p class="text-xs text-gray-500">Ajustes del sistema</p>
                                </div>
                            </a>
                            <a href="../soporte/manual_usuario.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors group">
                                <div class="w-8 h-8 bg-gray-100 group-hover:bg-gray-200 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                    <i class="fas fa-book text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Manual de Usuario</p>
                                    <p class="text-xs text-gray-500">Documentación</p>
                                </div>
                            </a>
                            <a href="https://antarestravelperu.com/" target="_blank" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors group">
                                <div class="w-8 h-8 bg-gray-100 group-hover:bg-gray-200 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                    <i class="fas fa-external-link-alt text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Visitar Sitio Web</p>
                                    <p class="text-xs text-gray-500">Ver página pública</p>
                                </div>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="../../auth/logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors group">
                                <div class="w-8 h-8 bg-red-50 group-hover:bg-red-100 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                    <i class="fas fa-sign-out-alt text-red-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Cerrar Sesión</p>
                                    <p class="text-xs text-red-500">Salir del sistema</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Avatar simple para móvil -->
            <div class="lg:hidden relative" id="userDropdownMobile">
                <button onclick="toggleDropdown('Mobile')" class="relative p-1 focus:outline-none">
                    <div class="w-9 h-9 bg-slate-900 rounded-lg flex items-center justify-center shadow-sm hover:shadow-md transition-all duration-200">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                </button>
                
                <div id="dropdownMenuMobile" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg ring-1 ring-gray-200 z-50 border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-slate-900 rounded-lg flex items-center justify-center shadow-sm">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($admin['nombre']); ?></p>
                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars($admin['email'] ?? 'admin@antares.com'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <a href="../mi_perfil/index.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors group">
                            <div class="w-8 h-8 bg-gray-100 group-hover:bg-gray-200 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                <i class="fas fa-user-circle text-gray-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">Mi Perfil</p>
                                <p class="text-xs text-gray-500">Configuración personal</p>
                            </div>
                        </a>
                        <a href="../soporte/settings.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors group">
                            <div class="w-8 h-8 bg-gray-100 group-hover:bg-gray-200 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                <i class="fas fa-cog text-gray-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">Configuración</p>
                                <p class="text-xs text-gray-500">Ajustes del sistema</p>
                            </div>
                        </a>
                        <a href="../soporte/manual_usuario.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors group">
                            <div class="w-8 h-8 bg-gray-100 group-hover:bg-gray-200 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                <i class="fas fa-book text-gray-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">Manual de Usuario</p>
                                <p class="text-xs text-gray-500">Documentación</p>
                            </div>
                        </a>
                        <a href="https://antarestravelperu.com/" target="_blank" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors group">
                            <div class="w-8 h-8 bg-gray-100 group-hover:bg-gray-200 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                <i class="fas fa-external-link-alt text-gray-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">Visitar Sitio Web</p>
                                <p class="text-xs text-gray-500">Ver página pública</p>
                            </div>
                        </a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a href="../../auth/logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors group">
                            <div class="w-8 h-8 bg-red-50 group-hover:bg-red-100 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                <i class="fas fa-sign-out-alt text-red-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">Cerrar Sesión</p>
                                <p class="text-xs text-red-500">Salir del sistema</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Función para alternar el dropdown del usuario
function toggleDropdown(device = '') {
    const dropdown = document.getElementById(`dropdownMenu${device}`);
    const isHidden = dropdown.classList.contains('hidden');
    
    // Cerrar el otro dropdown si está abierto
    const otherDevice = device === 'Mobile' ? 'Desktop' : 'Mobile';
    const otherDropdown = document.getElementById(`dropdownMenu${otherDevice}`);
    if (otherDropdown && !otherDropdown.classList.contains('hidden')) {
        otherDropdown.style.transform = 'translateY(-8px)';
        otherDropdown.style.opacity = '0';
        setTimeout(() => {
            otherDropdown.classList.add('hidden');
        }, 150);
    }
    
    if (isHidden) {
        dropdown.classList.remove('hidden');
        setTimeout(() => {
            dropdown.style.transform = 'translateY(0)';
            dropdown.style.opacity = '1';
        }, 10);
    } else {
        dropdown.style.transform = 'translateY(-8px)';
        dropdown.style.opacity = '0';
        setTimeout(() => {
            dropdown.classList.add('hidden');
        }, 150);
    }
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(event) {
    const dropdownDesktop = document.getElementById('userDropdownDesktop');
    const dropdownMobile = document.getElementById('userDropdownMobile');
    const menuDesktop = document.getElementById('dropdownMenuDesktop');
    const menuMobile = document.getElementById('dropdownMenuMobile');
    
    // Verificar si el clic fue fuera de ambos dropdowns
    const isOutsideDesktop = dropdownDesktop && !dropdownDesktop.contains(event.target);
    const isOutsideMobile = dropdownMobile && !dropdownMobile.contains(event.target);
    
    if (isOutsideDesktop && menuDesktop && !menuDesktop.classList.contains('hidden')) {
        menuDesktop.style.transform = 'translateY(-8px)';
        menuDesktop.style.opacity = '0';
        setTimeout(() => {
            menuDesktop.classList.add('hidden');
        }, 150);
    }
    
    if (isOutsideMobile && menuMobile && !menuMobile.classList.contains('hidden')) {
        menuMobile.style.transform = 'translateY(-8px)';
        menuMobile.style.opacity = '0';
        setTimeout(() => {
            menuMobile.classList.add('hidden');
        }, 150);
    }
});

// Inicialización del header
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Configurar estilos iniciales de los dropdowns
    const dropdownMenuDesktop = document.getElementById('dropdownMenuDesktop');
    const dropdownMenuMobile = document.getElementById('dropdownMenuMobile');
    
    [dropdownMenuDesktop, dropdownMenuMobile].forEach(dropdown => {
        if (dropdown) {
            dropdown.style.transform = 'translateY(-8px)';
            dropdown.style.opacity = '0';
            dropdown.style.transition = 'all 0.15s ease-in-out';
        }
    });
    
    // Agregar estilos elegantes al header
    const header = document.querySelector('header');
    if (header) {
        // Crear el efecto glass elegante
        header.style.backgroundImage = `
            linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.95) 50%, rgba(241,245,249,0.98) 100%),
            radial-gradient(ellipse at top, rgba(59,130,246,0.1) 0%, transparent 50%),
            radial-gradient(ellipse at bottom right, rgba(147,51,234,0.08) 0%, transparent 50%)
        `;
        header.style.backdropFilter = 'blur(16px) saturate(180%)';
        header.style.borderImage = 'linear-gradient(90deg, rgba(59,130,246,0.3), rgba(147,51,234,0.2), rgba(59,130,246,0.3)) 1';
    }
    
    // Funcionalidad del toggle del sidebar
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            if (typeof window.toggleSidebar === 'function') {
                window.toggleSidebar();
            } else {
                // Fallback básico
                const isVisible = sidebar.classList.contains('show');
                if (isVisible) {
                    sidebar.classList.remove('show');
                    if (overlay) overlay.classList.remove('show');
                    document.body.style.overflow = 'auto';
                } else {
                    sidebar.classList.add('show');
                    if (overlay) overlay.classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            }
        });
    }
    
    // Cerrar sidebar en móvil al hacer clic en overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        });
    }
    
    // Efectos de scroll en el header con retracción
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        // Cambiar sombra y efectos según el scroll
        if (currentScroll <= 0) {
            header.classList.remove('shadow-2xl');
            header.classList.add('shadow-lg');
            // Efecto más sutil en la parte superior
            header.style.backgroundImage = `
                linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.95) 50%, rgba(241,245,249,0.98) 100%),
                radial-gradient(ellipse at top, rgba(59,130,246,0.1) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(147,51,234,0.08) 0%, transparent 50%)
            `;
        } else {
            header.classList.remove('shadow-lg');
            header.classList.add('shadow-2xl');
            // Efecto más intenso al hacer scroll
            header.style.backgroundImage = `
                linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 50%, rgba(241,245,249,0.98) 100%),
                radial-gradient(ellipse at top, rgba(59,130,246,0.15) 0%, transparent 60%),
                radial-gradient(ellipse at bottom right, rgba(147,51,234,0.12) 0%, transparent 60%)
            `;
        }
        
        // Ocultar/mostrar header al hacer scroll
        if (currentScroll > lastScroll && currentScroll > 100) {
            // Scrolling hacia abajo - ocultar header
            header.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling hacia arriba - mostrar header
            header.style.transform = 'translateY(0)';
        }
        
        lastScroll = currentScroll;
    });
    
    // Transición suave para el header
    header.style.transition = 'transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out, background-image 0.3s ease-in-out';
});

// Función para mostrar notificaciones (simplificada)
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-200 ${
        type === 'success' ? 'bg-green-600 text-white' :
        type === 'error' ? 'bg-red-600 text-white' :
        type === 'warning' ? 'bg-yellow-600 text-white' :
        'bg-blue-600 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' :
                type === 'error' ? 'fa-exclamation-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                'fa-info-circle'
            }"></i>
            <span class="text-sm font-medium">${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 50);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 200);
    }, 3000);
}
</script>
