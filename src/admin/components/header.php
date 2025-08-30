<?php
require_once __DIR__ . '/../auth/middleware.php';
$admin = obtenerAdminActual();
?>

<header class="bg-gradient-to-r from-gray-50/95 via-white/95 to-blue-50/95 shadow-xl border-b border-blue-100/50 fixed top-0 left-0 right-0 z-30 backdrop-blur-lg bg-white/90">
    <div class="flex items-center justify-between px-4 lg:px-6 py-3 lg:py-4">
        <!-- Espacio en blanco para que el navbar no tape el contenido -->
        <div class="hidden lg:block" style="width: 240px;"></div>

        <!-- Botón hamburguesa para móvil -->
        <button id="sidebarToggle" class="lg:hidden p-2 text-gray-600 hover:text-blue-700 hover:bg-blue-50 rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-400/50 shadow-sm">
            <i class="fas fa-bars text-lg"></i>
        </button>

        <!-- Logo y título -->
        <div class="flex items-center">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 via-purple-600 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110">
                    <i class="fas fa-compass text-white text-sm drop-shadow-sm"></i>
                </div>
                <div class="hidden sm:block">
                    <h1 class="text-lg lg:text-xl font-bold bg-gradient-to-r from-blue-700 via-purple-600 to-blue-800 bg-clip-text text-transparent drop-shadow-sm">
                        Antares Travel
                    </h1>
                    <p class="text-xs text-gray-600/80 font-medium">Panel de Administración</p>
                </div>
                <div class="sm:hidden">
                    <h1 class="text-lg font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">AT</h1>
                </div>
            </div>
        </div>

        <!-- Información del usuario y acciones -->
        <div class="flex items-center space-x-2 lg:space-x-4">
            <!-- Notificaciones -->
            <div class="relative">
                <button class="p-2 text-gray-500 hover:text-blue-700 hover:bg-blue-50 rounded-xl transition-all duration-300 relative group shadow-sm hover:shadow-md">
                    <i class="fas fa-bell text-lg"></i>
                    <span class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-full min-w-5 h-5 flex items-center justify-center font-medium shadow-lg animate-pulse">3</span>
                </button>
                <div class="absolute top-full right-0 mt-2 w-2 h-2 bg-red-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </div>

            <!-- Información del admin -->
            <div class="flex items-center space-x-2 lg:space-x-3 bg-gradient-to-r from-blue-50/80 to-purple-50/80 rounded-xl px-3 py-2 border border-blue-200/50 hover:border-blue-300/70 transition-all duration-300 shadow-sm hover:shadow-md backdrop-blur-sm">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-9 h-9 bg-gradient-to-br from-blue-500 via-purple-600 to-cyan-500 rounded-full flex items-center justify-center shadow-lg ring-2 ring-white/50 hover:ring-blue-200 transition-all duration-300">
                            <i class="fas fa-user text-white text-sm drop-shadow-sm"></i>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-white shadow-sm animate-pulse"></div>
                    </div>
                    <div class="hidden lg:block">
                        <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($admin['nombre']); ?></p>
                        <div class="flex items-center space-x-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-blue-100 to-purple-100 text-blue-800 border border-blue-200/50">
                                <i class="fas fa-crown mr-1 text-xs text-blue-600"></i>
                                <?php echo htmlspecialchars($admin['rol'] ?? 'Administrador'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Dropdown menu -->
                <div class="relative" id="userDropdown">
                    <button onclick="toggleDropdown()" class="p-1 text-gray-500 hover:text-blue-700 transition-colors duration-300 hover:bg-blue-50 rounded-lg">
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                    
                    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-56 bg-white/95 backdrop-blur-lg rounded-xl shadow-2xl ring-1 ring-blue-200/50 z-50 border border-blue-100/50 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50/90 to-purple-50/90 px-4 py-3 border-b border-blue-200/50">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 via-purple-600 to-cyan-500 rounded-full flex items-center justify-center shadow-lg">
                                    <i class="fas fa-user text-white drop-shadow-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($admin['nombre']); ?></p>
                                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($admin['email'] ?? 'admin@antares.com'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="py-2">
                            <a href="../mi_perfil/index.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50/80 hover:text-blue-700 transition-all duration-300 group">
                                <div class="w-8 h-8 bg-blue-50 group-hover:bg-blue-100 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                    <i class="fas fa-user-circle text-blue-600 group-hover:text-blue-700"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Mi Perfil</p>
                                    <p class="text-xs text-gray-500">Configurar información personal</p>
                                </div>
                            </a>
                            <a href="../../config/settings.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50/80 hover:text-gray-800 transition-all duration-300 group">
                                <div class="w-8 h-8 bg-gray-50 group-hover:bg-gray-100 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                    <i class="fas fa-cog text-gray-600 group-hover:text-gray-700"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Configuración</p>
                                    <p class="text-xs text-gray-500">Ajustes del sistema</p>
                                </div>
                            </a>
                            <div class="border-t border-blue-100/50 my-2"></div>
                            <a href="../../auth/logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50/80 hover:text-red-700 transition-all duration-300 group">
                                <div class="w-8 h-8 bg-red-50 group-hover:bg-red-100 rounded-lg flex items-center justify-center mr-3 transition-colors">
                                    <i class="fas fa-sign-out-alt text-red-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Cerrar Sesión</p>
                                    <p class="text-xs text-red-500">Salir del panel de control</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('dropdownMenu');
    const isHidden = dropdown.classList.contains('hidden');
    
    if (isHidden) {
        dropdown.classList.remove('hidden');
        // Animación de entrada
        setTimeout(() => {
            dropdown.style.transform = 'translateY(0)';
            dropdown.style.opacity = '1';
        }, 10);
    } else {
        // Animación de salida
        dropdown.style.transform = 'translateY(-10px)';
        dropdown.style.opacity = '0';
        setTimeout(() => {
            dropdown.classList.add('hidden');
        }, 200);
    }
}

// Cerrar dropdown cuando se hace clic fuera
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const menu = document.getElementById('dropdownMenu');
    
    if (!dropdown.contains(event.target)) {
        if (!menu.classList.contains('hidden')) {
            menu.style.transform = 'translateY(-10px)';
            menu.style.opacity = '0';
            setTimeout(() => {
                menu.classList.add('hidden');
            }, 200);
        }
    }
});

// Toggle sidebar para móvil
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Inicializar estilos del dropdown
    const dropdownMenu = document.getElementById('dropdownMenu');
    if (dropdownMenu) {
        dropdownMenu.style.transform = 'translateY(-10px)';
        dropdownMenu.style.opacity = '0';
        dropdownMenu.style.transition = 'all 0.2s ease-in-out';
    }
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            // Usar la función global toggleSidebar del sidebar
            if (typeof window.toggleSidebar === 'function') {
                window.toggleSidebar();
            } else {
                // Fallback si la función no está disponible aún
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
    
    // Cerrar sidebar en móvil cuando se hace clic en el overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        });
    }
    
    // Efecto de scroll en el header
    let lastScroll = 0;
    const header = document.querySelector('header');
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll <= 0) {
            header.classList.remove('shadow-lg');
            header.classList.add('shadow-sm');
        } else {
            header.classList.remove('shadow-sm');
            header.classList.add('shadow-lg');
        }
        
        // Ocultar/mostrar header al hacer scroll
        if (currentScroll > lastScroll && currentScroll > 100) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }
        
        lastScroll = currentScroll;
    });
    
    // Añadir transición al header
    header.style.transition = 'transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out';
    
    // Efecto hover en las notificaciones
    const notificationBtn = document.querySelector('[data-notification]') || document.querySelector('.fa-bell').parentElement;
    if (notificationBtn) {
        notificationBtn.addEventListener('mouseenter', function() {
            const badge = this.querySelector('span');
            if (badge) {
                badge.style.transform = 'scale(1.2)';
                badge.style.transition = 'transform 0.2s ease-in-out';
            }
        });
        
        notificationBtn.addEventListener('mouseleave', function() {
            const badge = this.querySelector('span');
            if (badge) {
                badge.style.transform = 'scale(1)';
            }
        });
    }
    
    // Efecto de typing en el título (opcional)
    const titleElement = document.querySelector('h1');
    if (titleElement && titleElement.textContent === 'Antares Travel') {
        const originalText = titleElement.textContent;
        titleElement.textContent = '';
        
        let i = 0;
        const typeEffect = setInterval(() => {
            if (i < originalText.length) {
                titleElement.textContent += originalText.charAt(i);
                i++;
            } else {
                clearInterval(typeEffect);
            }
        }, 100);
    }
});

// Función para mostrar notificaciones toast (opcional)
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-20 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' :
                type === 'error' ? 'fa-exclamation-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                'fa-info-circle'
            }"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>
