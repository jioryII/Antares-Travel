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
        const response = await fetch(`${CONFIG.API_BASE}?action=estadisticas`);
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
    // Esta función se puede expandir para mostrar estadísticas en tiempo real
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
        
        const response = await fetch(`${CONFIG.API_BASE}?action=obtener&id=${idTour}`);
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
            mostrarPreviewImagen(`../../../${tour.imagen_principal}`);
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

// Ver detalles del tour con información ampliada
async function verTour(idTour) {
    try {
        mostrarCargando(true);
        
        const response = await fetch(`${CONFIG.API_BASE}?action=obtener&id=${idTour}`);
        const resultado = await response.json();
        
        if (!resultado.success) {
            mostrarError(resultado.message);
            return;
        }
        
        const tour = resultado.data;
        
        // Generar HTML mejorado para detalles
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
    return `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Imagen y información básica -->
            <div class="space-y-4">
                <div class="relative">
                    ${tour.imagen_principal ? 
                        `<img src="../../../${tour.imagen_principal}" alt="${escapeHtml(tour.titulo)}" 
                             class="w-full h-64 object-cover rounded-lg shadow-md">` :
                        `<div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                            <div class="text-center text-gray-400">
                                <i class="fas fa-image text-4xl mb-2"></i>
                                <p>Sin imagen</p>
                            </div>
                         </div>`
                    }
                </div>
                
                <!-- Información básica -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-700 mb-3">Información Básica</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Precio:</span>
                            <span class="font-semibold text-green-600">S/. ${parseFloat(tour.precio).toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Duración:</span>
                            <span>${escapeHtml(tour.duracion)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Región:</span>
                            <span>${escapeHtml(tour.nombre_region || 'Sin región')}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detalles y estadísticas -->
            <div class="space-y-4">
                <!-- Título y descripción -->
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">${escapeHtml(tour.titulo)}</h3>
                    <p class="text-gray-600 leading-relaxed">${escapeHtml(tour.descripcion)}</p>
                </div>
                
                <!-- Itinerario -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-3">
                        <i class="fas fa-route mr-2"></i>Itinerario
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center">
                            <i class="fas fa-play text-green-500 mr-2"></i>
                            <span class="text-gray-600">Salida:</span>
                            <span class="ml-2 font-medium">${escapeHtml(tour.lugar_salida)}</span>
                            ${tour.hora_salida ? `<span class="ml-2 text-blue-600">(${tour.hora_salida})</span>` : ''}
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-flag-checkered text-red-500 mr-2"></i>
                            <span class="text-gray-600">Llegada:</span>
                            <span class="ml-2 font-medium">${escapeHtml(tour.lugar_llegada)}</span>
                            ${tour.hora_llegada ? `<span class="ml-2 text-blue-600">(${tour.hora_llegada})</span>` : ''}
                        </div>
                    </div>
                </div>
                
                <!-- Guía asignado -->
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-purple-800 mb-3">
                        <i class="fas fa-user-tie mr-2"></i>Guía Asignado
                    </h4>
                    ${tour.guia_nombre ? `
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-purple-200 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-purple-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">${escapeHtml(tour.guia_nombre + ' ' + tour.guia_apellido)}</p>
                                <p class="text-sm text-gray-600">Estado: ${escapeHtml(tour.guia_estado || 'N/A')}</p>
                            </div>
                        </div>
                    ` : `
                        <div class="text-center text-gray-500 py-2">
                            <i class="fas fa-user-slash text-2xl mb-2"></i>
                            <p>Sin guía asignado</p>
                        </div>
                    `}
                </div>
                
                <!-- Estadísticas -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-700 mb-3">
                        <i class="fas fa-chart-bar mr-2"></i>Estadísticas
                    </h4>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-white p-3 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">${tour.total_reservas || 0}</div>
                            <div class="text-xs text-gray-600">Reservas</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">S/. ${parseFloat(tour.ingresos_totales || 0).toFixed(0)}</div>
                            <div class="text-xs text-gray-600">Ingresos</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">${tour.tours_programados || 0}</div>
                            <div class="text-xs text-gray-600">Programados</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">${parseFloat(tour.puntuacion_promedio || 0).toFixed(1)}</div>
                            <div class="text-xs text-gray-600">Puntuación</div>
                        </div>
                    </div>
                </div>
                
                <!-- Próximos tours si existen -->
                ${tour.proximos_tours && tour.proximos_tours.length > 0 ? `
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-green-800 mb-3">
                            <i class="fas fa-calendar-alt mr-2"></i>Próximos Tours
                        </h4>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            ${tour.proximos_tours.map(pt => `
                                <div class="flex justify-between items-center text-sm bg-white p-2 rounded">
                                    <span>${pt.fecha}</span>
                                    <span class="text-blue-600">${pt.hora_salida || 'N/A'}</span>
                                    ${pt.placa ? `<span class="text-gray-600">${pt.placa}</span>` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}
                        <p class="text-gray-900">${tour.guia_nombre ? escapeHtml(tour.guia_nombre + ' ' + tour.guia_apellido) : 'Sin asignar'}</p>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <h4 class="font-semibold text-gray-700 mb-2">Descripción</h4>
                    <p class="text-gray-900">${escapeHtml(tour.descripcion)}</p>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-700">Lugar de Salida</h4>
                    <p class="text-gray-900">${escapeHtml(tour.lugar_salida)}</p>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-700">Lugar de Llegada</h4>
                    <p class="text-gray-900">${escapeHtml(tour.lugar_llegada)}</p>
                </div>
                ${tour.hora_salida ? `
                <div>
                    <h4 class="font-semibold text-gray-700">Hora de Salida</h4>
                    <p class="text-gray-900">${tour.hora_salida}</p>
                </div>
                ` : ''}
                ${tour.hora_llegada ? `
                <div>
                    <h4 class="font-semibold text-gray-700">Hora de Llegada</h4>
                    <p class="text-gray-900">${tour.hora_llegada}</p>
                </div>
                ` : ''}
            </div>
        `;
        
        document.getElementById('detallesTour').innerHTML = detallesHTML;
        document.getElementById('modalVerTour').classList.remove('hidden');
        
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar los detalles del tour');
    }
}

// Eliminar tour
async function eliminarTour(idTour) {
    if (!confirm('¿Estás seguro de que deseas eliminar este tour? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        const response = await fetch(`../../api/tours.php?action=eliminar&id=${idTour}`, {
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
        console.error('Error:', error);
        mostrarError('Error al eliminar el tour');
    }
}

// Cerrar modal
function cerrarModal() {
    document.getElementById('modalTour').classList.add('hidden');
}

function cerrarModalVer() {
    document.getElementById('modalVerTour').classList.add('hidden');
}

// Manejar envío del formulario
document.getElementById('formTour').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const datos = {};
    
    // Convertir FormData a objeto, excluyendo la imagen por ahora
    for (let [key, value] of formData.entries()) {
        if (key !== 'imagen') {
            datos[key] = value;
        }
    }
    
    // Convertir valores numéricos
    if (datos.precio) datos.precio = parseFloat(datos.precio);
    if (datos.id_region && datos.id_region !== '') datos.id_region = parseInt(datos.id_region);
    if (datos.id_guia && datos.id_guia !== '') datos.id_guia = parseInt(datos.id_guia);
    
    // Limpiar valores vacíos
    Object.keys(datos).forEach(key => {
        if (datos[key] === '') {
            datos[key] = null;
        }
    });
    
    try {
        let imagenUrl = null;
        
        // Subir imagen si se seleccionó una nueva
        const archivoImagen = formData.get('imagen');
        if (archivoImagen && archivoImagen.size > 0) {
            const imagenFormData = new FormData();
            imagenFormData.append('imagen', archivoImagen);
            
            const responseImagen = await fetch('../../api/tours.php?action=upload_imagen', {
                method: 'POST',
                body: imagenFormData
            });
            
            const resultadoImagen = await responseImagen.json();
            
            if (resultadoImagen.success) {
                imagenUrl = resultadoImagen.ruta_relativa;
            } else {
                mostrarError('Error al subir la imagen: ' + resultadoImagen.message);
                return;
            }
        }
        
        // Incluir URL de imagen si se subió una nueva
        if (imagenUrl) {
            datos.imagen_principal = imagenUrl;
        }
        
        let url, method;
        
        if (tourEditando) {
            // Actualizar tour existente
            url = `../../api/tours.php?action=actualizar&id=${tourEditando}`;
            method = 'PUT';
        } else {
            // Crear nuevo tour
            url = '../../api/tours.php?action=crear';
            method = 'POST';
        }
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datos)
        });
        
        const resultado = await response.json();
        
        if (resultado.success) {
            mostrarExito(resultado.message);
            cerrarModal();
            // Recargar página después de un breve delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            if (resultado.errors) {
                mostrarError('Errores de validación:\n' + resultado.errors.join('\n'));
            } else {
                mostrarError(resultado.message);
            }
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al guardar el tour');
    }
});

// Preview de imagen
document.getElementById('imagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagenPreviewImg').src = e.target.result;
            document.getElementById('imagenPreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagenPreview').classList.add('hidden');
    }
});

// Cerrar modales con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarModalVer();
    }
});

// Cerrar modales al hacer clic fuera
document.getElementById('modalTour').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

document.getElementById('modalVerTour').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalVer();
    }
});

// Funciones de utilidad
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function mostrarError(mensaje) {
    // Crear elemento de notificación
    const div = document.createElement('div');
    div.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    div.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span>${escapeHtml(mensaje)}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(div);
    
    // Eliminar después de 5 segundos
    setTimeout(() => {
        if (div.parentNode) {
            div.remove();
        }
    }, 5000);
}

function mostrarExito(mensaje) {
    // Crear elemento de notificación
    const div = document.createElement('div');
    div.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    div.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>${escapeHtml(mensaje)}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(div);
    
    // Eliminar después de 3 segundos
    setTimeout(() => {
        if (div.parentNode) {
            div.remove();
        }
    }, 3000);
}
