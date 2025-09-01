// JavaScript para gestión de tours - Versión actualizada
// Antares Travel Admin

let tourEditando = null;
let estadisticasTours = {};

// Configuración global
const CONFIG = {
    API_BASE: '../../api/tours.php',
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    ALLOWED_TYPES: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    TOAST_DURATION: 5000
};

// Inicializar página
document.addEventListener('DOMContentLoaded', function() {
    cargarEstadisticas();
    configurarEventos();
});

// Configurar eventos de la página
function configurarEventos() {
    // Preview de imagen
    const inputImagen = document.getElementById('imagen');
    if (inputImagen) {
        inputImagen.addEventListener('change', manejarPreviewImagen);
    }
    
    // Validación en tiempo real del formulario
    const formTour = document.getElementById('formTour');
    if (formTour) {
        formTour.addEventListener('submit', manejarEnvioFormulario);
        
        // Validaciones en tiempo real
        document.getElementById('titulo')?.addEventListener('input', validarTitulo);
        document.getElementById('precio')?.addEventListener('input', validarPrecio);
        document.getElementById('hora_salida')?.addEventListener('change', validarHoras);
        document.getElementById('hora_llegada')?.addEventListener('change', validarHoras);
    }
}

// Cargar estadísticas generales
async function cargarEstadisticas() {
    try {
        const response = await fetch(CONFIG.API_BASE + '?action=estadisticas');
        const resultado = await response.json();
        
        if (resultado.success) {
            estadisticasTours = resultado.data;
            actualizarPanelEstadisticas();
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

// Actualizar panel de estadísticas en la UI
function actualizarPanelEstadisticas() {
    console.log('Estadísticas actualizadas:', estadisticasTours);
}

// Abrir modal para crear tour
function abrirModalCrear() {
    tourEditando = null;
    document.getElementById('modalTitle').textContent = 'Nuevo Tour';
    document.getElementById('btnGuardar').textContent = 'Crear Tour';
    
    // Limpiar formulario
    const form = document.getElementById('formTour');
    form.reset();
    document.getElementById('tour_id').value = '';
    
    // Ocultar preview de imagen
    ocultarPreviewImagen();
    
    // Limpiar validaciones
    limpiarValidaciones();
    
    // Mostrar modal
    document.getElementById('modalTour').classList.remove('hidden');
    
    // Enfocar primer campo
    setTimeout(() => {
        document.getElementById('titulo')?.focus();
    }, 100);
}

// Abrir modal para editar tour
async function editarTour(idTour) {
    try {
        mostrarCargando(true);
        
        const response = await fetch(CONFIG.API_BASE + '?action=obtener&id=' + idTour);
        const resultado = await response.json();
        
        if (!resultado.success) {
            mostrarError(resultado.message);
            return;
        }
        
        const tour = resultado.data;
        tourEditando = idTour;
        
        // Configurar modal
        document.getElementById('modalTitle').textContent = 'Editar Tour';
        document.getElementById('btnGuardar').textContent = 'Actualizar Tour';
        
        // Llenar formulario con datos del tour
        rellenarFormulario(tour);
        
        // Mostrar imagen actual si existe
        if (tour.imagen_principal) {
            mostrarPreviewImagen('../../../' + tour.imagen_principal);
        } else {
            ocultarPreviewImagen();
        }
        
        // Limpiar validaciones
        limpiarValidaciones();
        
        // Mostrar modal
        document.getElementById('modalTour').classList.remove('hidden');
        
    } catch (error) {
        console.error('Error cargando tour:', error);
        mostrarError('Error al cargar los datos del tour');
    } finally {
        mostrarCargando(false);
    }
}

// Rellenar formulario con datos del tour
function rellenarFormulario(tour) {
    const campos = [
        'id_tour', 'titulo', 'descripcion', 'precio', 'duracion',
        'id_region', 'id_guia', 'lugar_salida', 'lugar_llegada',
        'hora_salida', 'hora_llegada'
    ];
    
    campos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.value = tour[campo] || '';
        }
    });
}

// Ver detalles del tour
async function verTour(idTour) {
    try {
        mostrarCargando(true);
        
        const response = await fetch(CONFIG.API_BASE + '?action=obtener&id=' + idTour);
        const resultado = await response.json();
        
        if (!resultado.success) {
            mostrarError(resultado.message);
            return;
        }
        
        const tour = resultado.data;
        
        // Generar HTML para detalles
        const detallesHTML = generarHTMLDetalles(tour);
        
        // Mostrar en modal
        document.getElementById('detallesTour').innerHTML = detallesHTML;
        document.getElementById('modalVerTour').classList.remove('hidden');
        
    } catch (error) {
        console.error('Error cargando detalles:', error);
        mostrarError('Error al cargar los detalles del tour');
    } finally {
        mostrarCargando(false);
    }
}

// Generar HTML para detalles del tour
function generarHTMLDetalles(tour) {
    const imagenHTML = tour.imagen_principal 
        ? '<img src="../../../' + tour.imagen_principal + '" alt="' + escapeHtml(tour.titulo) + '" class="w-full h-64 object-cover rounded-lg shadow-md">'
        : '<div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center"><div class="text-center text-gray-400"><i class="fas fa-image text-4xl mb-2"></i><p>Sin imagen</p></div></div>';
    
    const guiaHTML = tour.guia_nombre 
        ? '<div class="flex items-center"><div class="w-10 h-10 bg-purple-200 rounded-full flex items-center justify-center mr-3"><i class="fas fa-user text-purple-600"></i></div><div><p class="font-medium">' + escapeHtml(tour.guia_nombre + ' ' + tour.guia_apellido) + '</p><p class="text-sm text-gray-600">Estado: ' + escapeHtml(tour.guia_estado || 'N/A') + '</p></div></div>'
        : '<div class="text-center text-gray-500 py-2"><i class="fas fa-user-slash text-2xl mb-2"></i><p>Sin guía asignado</p></div>';
    
    return '<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">' +
           '<div class="space-y-4">' +
           '<div class="relative">' + imagenHTML + '</div>' +
           '<div class="bg-gray-50 p-4 rounded-lg">' +
           '<h4 class="font-semibold text-gray-700 mb-3">Información Básica</h4>' +
           '<div class="space-y-2 text-sm">' +
           '<div class="flex justify-between"><span class="text-gray-600">Precio:</span><span class="font-semibold text-green-600">S/. ' + parseFloat(tour.precio).toFixed(2) + '</span></div>' +
           '<div class="flex justify-between"><span class="text-gray-600">Duración:</span><span>' + escapeHtml(tour.duracion) + '</span></div>' +
           '<div class="flex justify-between"><span class="text-gray-600">Región:</span><span>' + escapeHtml(tour.nombre_region || 'Sin región') + '</span></div>' +
           '</div></div></div>' +
           '<div class="space-y-4">' +
           '<div><h3 class="text-xl font-bold text-gray-900 mb-3">' + escapeHtml(tour.titulo) + '</h3>' +
           '<p class="text-gray-600 leading-relaxed">' + escapeHtml(tour.descripcion) + '</p></div>' +
           '<div class="bg-blue-50 p-4 rounded-lg"><h4 class="font-semibold text-blue-800 mb-3"><i class="fas fa-route mr-2"></i>Itinerario</h4>' +
           '<div class="space-y-2 text-sm">' +
           '<div class="flex items-center"><i class="fas fa-play text-green-500 mr-2"></i><span class="text-gray-600">Salida:</span><span class="ml-2 font-medium">' + escapeHtml(tour.lugar_salida) + '</span>' + (tour.hora_salida ? '<span class="ml-2 text-blue-600">(' + tour.hora_salida + ')</span>' : '') + '</div>' +
           '<div class="flex items-center"><i class="fas fa-flag-checkered text-red-500 mr-2"></i><span class="text-gray-600">Llegada:</span><span class="ml-2 font-medium">' + escapeHtml(tour.lugar_llegada) + '</span>' + (tour.hora_llegada ? '<span class="ml-2 text-blue-600">(' + tour.hora_llegada + ')</span>' : '') + '</div>' +
           '</div></div>' +
           '<div class="bg-purple-50 p-4 rounded-lg"><h4 class="font-semibold text-purple-800 mb-3"><i class="fas fa-user-tie mr-2"></i>Guía Asignado</h4>' + guiaHTML + '</div>' +
           '<div class="bg-gray-50 p-4 rounded-lg"><h4 class="font-semibold text-gray-700 mb-3"><i class="fas fa-chart-bar mr-2"></i>Estadísticas</h4>' +
           '<div class="grid grid-cols-2 gap-4 text-center">' +
           '<div class="bg-white p-3 rounded-lg"><div class="text-2xl font-bold text-blue-600">' + (tour.total_reservas || 0) + '</div><div class="text-xs text-gray-600">Reservas</div></div>' +
           '<div class="bg-white p-3 rounded-lg"><div class="text-2xl font-bold text-green-600">S/. ' + parseFloat(tour.ingresos_totales || 0).toFixed(0) + '</div><div class="text-xs text-gray-600">Ingresos</div></div>' +
           '<div class="bg-white p-3 rounded-lg"><div class="text-2xl font-bold text-purple-600">' + (tour.tours_programados || 0) + '</div><div class="text-xs text-gray-600">Programados</div></div>' +
           '<div class="bg-white p-3 rounded-lg"><div class="text-2xl font-bold text-yellow-600">' + parseFloat(tour.puntuacion_promedio || 0).toFixed(1) + '</div><div class="text-xs text-gray-600">Puntuación</div></div>' +
           '</div></div></div></div>';
}

// Eliminar tour
async function eliminarTour(idTour) {
    if (!confirm('¿Estás seguro de que quieres eliminar este tour? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        mostrarCargando(true);
        
        const response = await fetch(CONFIG.API_BASE + '?action=eliminar&id=' + idTour, {
            method: 'DELETE'
        });
        
        const resultado = await response.json();
        
        if (resultado.success) {
            mostrarExito(resultado.message);
            // Recargar página después de un breve delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            mostrarError(resultado.message);
        }
        
    } catch (error) {
        console.error('Error eliminando tour:', error);
        mostrarError('Error al eliminar el tour');
    } finally {
        mostrarCargando(false);
    }
}

// Manejar envío del formulario
async function manejarEnvioFormulario(e) {
    e.preventDefault();
    
    try {
        mostrarCargando(true);
        
        // Validar formulario
        if (!validarFormulario()) {
            mostrarError('Por favor corrige los errores del formulario');
            return;
        }
        
        const formData = new FormData(e.target);
        
        let url, method;
        if (tourEditando) {
            url = CONFIG.API_BASE + '?action=actualizar&id=' + tourEditando;
            method = 'PUT';
            
            // Para PUT, convertir FormData a JSON
            const datos = {};
            for (let [key, value] of formData.entries()) {
                if (key !== 'imagen') { // Excluir archivo de imagen para PUT
                    datos[key] = value;
                }
            }
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
            });
            
            const resultado = await response.json();
            
            if (resultado.success) {
                mostrarExito('Tour actualizado exitosamente');
                cerrarModal();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                mostrarError(resultado.message);
            }
            
        } else {
            url = CONFIG.API_BASE + '?action=crear';
            method = 'POST';
            
            const response = await fetch(url, {
                method: method,
                body: formData
            });
            
            const resultado = await response.json();
            
            if (resultado.success) {
                mostrarExito('Tour creado exitosamente');
                cerrarModal();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                mostrarError(resultado.message);
            }
        }
        
    } catch (error) {
        console.error('Error enviando formulario:', error);
        mostrarError('Error al procesar el formulario');
    } finally {
        mostrarCargando(false);
    }
}

// Validar formulario
function validarFormulario() {
    let esValido = true;
    
    // Limpiar errores previos
    limpiarValidaciones();
    
    // Validar campos requeridos
    const camposRequeridos = ['titulo', 'descripcion', 'precio', 'duracion', 'lugar_salida', 'lugar_llegada'];
    
    camposRequeridos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (!elemento.value.trim()) {
            mostrarErrorCampo(campo, 'Este campo es obligatorio');
            esValido = false;
        }
    });
    
    // Validaciones específicas
    const precio = document.getElementById('precio');
    if (precio.value && (isNaN(precio.value) || parseFloat(precio.value) <= 0)) {
        mostrarErrorCampo('precio', 'El precio debe ser un número mayor a 0');
        esValido = false;
    }
    
    return esValido;
}

// Manejar preview de imagen
function manejarPreviewImagen(e) {
    const file = e.target.files[0];
    if (!file) {
        ocultarPreviewImagen();
        return;
    }
    
    // Validar tipo de archivo
    if (!CONFIG.ALLOWED_TYPES.includes(file.type)) {
        mostrarError('Tipo de archivo no permitido. Use JPG, PNG, WEBP o GIF');
        e.target.value = '';
        ocultarPreviewImagen();
        return;
    }
    
    // Validar tamaño
    if (file.size > CONFIG.MAX_FILE_SIZE) {
        mostrarError('El archivo es demasiado grande. Máximo 5MB');
        e.target.value = '';
        ocultarPreviewImagen();
        return;
    }
    
    // Mostrar preview
    const reader = new FileReader();
    reader.onload = function(e) {
        mostrarPreviewImagen(e.target.result);
    };
    reader.readAsDataURL(file);
}

// Mostrar preview de imagen
function mostrarPreviewImagen(src) {
    const preview = document.getElementById('imagenPreview');
    const img = document.getElementById('imagenPreviewImg');
    
    if (preview && img) {
        img.src = src;
        preview.classList.remove('hidden');
    }
}

// Ocultar preview de imagen
function ocultarPreviewImagen() {
    const preview = document.getElementById('imagenPreview');
    if (preview) {
        preview.classList.add('hidden');
    }
}

// Validaciones en tiempo real
function validarTitulo() {
    const titulo = document.getElementById('titulo');
    const valor = titulo.value.trim();
    
    if (valor.length < 3) {
        mostrarErrorCampo('titulo', 'El título debe tener al menos 3 caracteres');
    } else if (valor.length > 200) {
        mostrarErrorCampo('titulo', 'El título no puede exceder 200 caracteres');
    } else {
        limpiarErrorCampo('titulo');
    }
}

function validarPrecio() {
    const precio = document.getElementById('precio');
    const valor = parseFloat(precio.value);
    
    if (isNaN(valor) || valor <= 0) {
        mostrarErrorCampo('precio', 'El precio debe ser un número mayor a 0');
    } else if (valor > 99999.99) {
        mostrarErrorCampo('precio', 'El precio no puede exceder S/. 99,999.99');
    } else {
        limpiarErrorCampo('precio');
    }
}

function validarHoras() {
    const horaSalida = document.getElementById('hora_salida');
    const horaLlegada = document.getElementById('hora_llegada');
    
    if (horaSalida.value && horaLlegada.value) {
        if (horaSalida.value >= horaLlegada.value) {
            mostrarErrorCampo('hora_llegada', 'La hora de llegada debe ser posterior a la de salida');
        } else {
            limpiarErrorCampo('hora_llegada');
        }
    }
}

// Funciones de utilidad
function mostrarErrorCampo(campo, mensaje) {
    const elemento = document.getElementById(campo);
    if (elemento) {
        elemento.classList.add('border-red-500');
        
        // Crear o actualizar mensaje de error
        let errorDiv = elemento.parentNode.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message text-red-500 text-sm mt-1';
            elemento.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = mensaje;
    }
}

function limpiarErrorCampo(campo) {
    const elemento = document.getElementById(campo);
    if (elemento) {
        elemento.classList.remove('border-red-500');
        const errorDiv = elemento.parentNode.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
}

function limpiarValidaciones() {
    const errores = document.querySelectorAll('.error-message');
    errores.forEach(error => error.remove());
    
    const campos = document.querySelectorAll('.border-red-500');
    campos.forEach(campo => campo.classList.remove('border-red-500'));
}

function mostrarCargando(mostrar) {
    const btn = document.getElementById('btnGuardar');
    if (btn) {
        if (mostrar) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';
        } else {
            btn.disabled = false;
            btn.innerHTML = tourEditando ? 'Actualizar Tour' : 'Crear Tour';
        }
    }
}

function cerrarModal() {
    document.getElementById('modalTour').classList.add('hidden');
    limpiarValidaciones();
}

function cerrarModalVer() {
    document.getElementById('modalVerTour').classList.add('hidden');
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Funciones de notificación (definidas en index.php)
function mostrarError(mensaje) {
    if (typeof window.mostrarError === 'function') {
        window.mostrarError(mensaje);
    } else {
        alert('Error: ' + mensaje);
    }
}

function mostrarExito(mensaje) {
    if (typeof window.mostrarExito === 'function') {
        window.mostrarExito(mensaje);
    } else {
        alert('Éxito: ' + mensaje);
    }
}
