/**
 * JavaScript para funcionalidad responsiva del panel de administración
 * Antares Travel Admin Panel
 */

// Variables globales
let sidebarOpen = false;
let isMobile = window.innerWidth < 1024;

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    initializeResponsiveComponents();
    setupEventListeners();
    handleResize();
});

/**
 * Inicializar componentes responsivos
 */
function initializeResponsiveComponents() {
    // Verificar elementos necesarios
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!sidebar || !sidebarToggle) {
        console.warn('Elementos del sidebar no encontrados');
        return;
    }
    
    // Configurar estado inicial
    updateSidebarState();
    
    // Configurar overlay si existe
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Configurar botón toggle
    sidebarToggle.addEventListener('click', toggleSidebar);
    
    console.log('Componentes responsivos inicializados');
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Listener para cambios de tamaño de ventana
    window.addEventListener('resize', debounce(handleResize, 250));
    
    // Listeners para navegación
    setupNavigationListeners();
    
    // Listeners para formularios responsivos
    setupFormListeners();
    
    // Listener para cerrar sidebar con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarOpen && isMobile) {
            closeSidebar();
        }
    });
}

/**
 * Manejar cambios de tamaño de ventana
 */
function handleResize() {
    const newIsMobile = window.innerWidth < 1024;
    
    // Si cambió de móvil a desktop o viceversa
    if (newIsMobile !== isMobile) {
        isMobile = newIsMobile;
        updateSidebarState();
        
        // Reconfigurar charts si existen
        if (typeof Chart !== 'undefined') {
            Chart.helpers.each(Chart.instances, function(instance) {
                instance.resize();
            });
        }
    }
    
    // Actualizar variables CSS custom
    document.documentElement.style.setProperty('--vh', `${window.innerHeight * 0.01}px`);
}

/**
 * Actualizar estado del sidebar según el tamaño de pantalla
 */
function updateSidebarState() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!sidebar) return;
    
    if (isMobile) {
        // En móvil, el sidebar empieza cerrado
        sidebar.classList.add('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
        sidebarOpen = false;
    } else {
        // En desktop, el sidebar está siempre visible
        sidebar.classList.remove('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
        sidebarOpen = true;
    }
}

/**
 * Toggle del sidebar
 */
function toggleSidebar() {
    if (sidebarOpen) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

/**
 * Abrir sidebar
 */
function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!sidebar) return;
    
    sidebar.classList.remove('-translate-x-full');
    if (overlay) overlay.classList.remove('hidden');
    
    sidebarOpen = true;
    
    // Enfocar el primer enlace para accesibilidad
    const firstLink = sidebar.querySelector('a');
    if (firstLink) {
        setTimeout(() => firstLink.focus(), 300);
    }
}

/**
 * Cerrar sidebar
 */
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!sidebar) return;
    
    sidebar.classList.add('-translate-x-full');
    if (overlay) overlay.classList.add('hidden');
    
    sidebarOpen = false;
}

/**
 * Configurar listeners de navegación
 */
function setupNavigationListeners() {
    // Cerrar sidebar al hacer clic en un enlace en móvil
    const navLinks = document.querySelectorAll('#sidebar a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (isMobile) {
                setTimeout(closeSidebar, 150);
            }
        });
    });
}

/**
 * Configurar listeners para formularios
 */
function setupFormListeners() {
    // Mejorar experiencia en formularios móviles
    const inputs = document.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        // Añadir clases responsivas
        if (isMobile) {
            input.classList.add('form-input-mobile');
        }
        
        // Manejar eventos de foco en móvil
        if (isMobile) {
            input.addEventListener('focus', handleMobileFocus);
            input.addEventListener('blur', handleMobileBlur);
        }
    });
}

/**
 * Manejar foco en inputs móviles
 */
function handleMobileFocus(e) {
    // Scroll para asegurar que el input sea visible
    setTimeout(() => {
        e.target.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }, 300);
}

/**
 * Manejar blur en inputs móviles
 */
function handleMobileBlur(e) {
    // Restaurar viewport si es necesario
    setTimeout(() => {
        window.scrollTo(0, 0);
    }, 100);
}

/**
 * Función debounce para optimizar eventos
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Utilidades para notificaciones responsivas
 */
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg transition-all duration-300 ${getNotificationClasses(type)}`;
    notification.textContent = message;
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.classList.add('translate-x-0');
    }, 100);
    
    // Remover después del tiempo especificado
    setTimeout(() => {
        notification.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, duration);
}

/**
 * Obtener clases CSS para notificaciones
 */
function getNotificationClasses(type) {
    const classes = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'warning': 'bg-yellow-500 text-white',
        'info': 'bg-blue-500 text-white'
    };
    
    return classes[type] || classes.info;
}

/**
 * Utilidad para detectar dispositivos táctiles
 */
function isTouchDevice() {
    return (('ontouchstart' in window) ||
            (navigator.maxTouchPoints > 0) ||
            (navigator.msMaxTouchPoints > 0));
}

/**
 * Mejorar experiencia de carga
 */
function showLoadingState(element, text = 'Cargando...') {
    const originalContent = element.innerHTML;
    element.innerHTML = `
        <div class="flex items-center justify-center">
            <div class="loading-spinner mr-2"></div>
            <span>${text}</span>
        </div>
    `;
    
    return () => {
        element.innerHTML = originalContent;
    };
}

/**
 * Validación de formularios mejorada
 */
function validateForm(formElement) {
    const inputs = formElement.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('form-error');
            isValid = false;
        } else {
            input.classList.remove('form-error');
            input.classList.add('form-success');
        }
    });
    
    return isValid;
}

/**
 * Configurar intersección observer para lazy loading
 */
function setupLazyLoading() {
    if ('IntersectionObserver' in window) {
        const lazyElements = document.querySelectorAll('[data-lazy]');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const src = element.dataset.lazy;
                    
                    if (element.tagName === 'IMG') {
                        element.src = src;
                    } else {
                        element.style.backgroundImage = `url(${src})`;
                    }
                    
                    element.removeAttribute('data-lazy');
                    observer.unobserve(element);
                }
            });
        });
        
        lazyElements.forEach(element => {
            observer.observe(element);
        });
    }
}

/**
 * Inicializar componentes cuando se cargan dinámicamente
 */
function initializeDynamicContent(container) {
    // Reconfigurar event listeners para nuevo contenido
    const newInputs = container.querySelectorAll('input, textarea, select');
    newInputs.forEach(input => {
        if (isMobile) {
            input.classList.add('form-input-mobile');
            input.addEventListener('focus', handleMobileFocus);
            input.addEventListener('blur', handleMobileBlur);
        }
    });
    
    // Configurar nuevos elementos lazy
    setupLazyLoading();
}

// Exportar funciones para uso global
window.AntaresMobile = {
    toggleSidebar,
    closeSidebar,
    openSidebar,
    showNotification,
    validateForm,
    showLoadingState,
    initializeDynamicContent,
    isMobile: () => isMobile,
    isTouchDevice
};

console.log('Antares Mobile Utils cargado');
