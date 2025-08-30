// JavaScript para gestión de reservas
document.addEventListener('DOMContentLoaded', function() {
    console.log('Módulo de reservas cargado');
});

// Ver detalles de una reserva
async function viewReserva(id) {
    try {
        showLoading('viewModalContent');
        document.getElementById('viewModal').classList.remove('hidden');
        
        const response = await fetch(`api/reservas.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const reserva = data.data;
            
            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información General -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Información General</h4>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">ID Reserva</label>
                                <p class="text-sm text-gray-900">#${reserva.id_reserva}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Estado</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getEstadoClass(reserva.estado)}">
                                    ${reserva.estado}
                                </span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Fecha de Reserva</label>
                                <p class="text-sm text-gray-900">${formatDate(reserva.fecha_reserva)}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Origen</label>
                                <p class="text-sm text-gray-900">${reserva.origen_reserva}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Monto Total</label>
                                <p class="text-lg font-semibold text-green-600">S/ ${parseFloat(reserva.monto_total).toFixed(2)}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Usuario -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Cliente</h4>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Nombre</label>
                                <p class="text-sm text-gray-900">${reserva.usuario_nombre}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="text-sm text-gray-900">${reserva.usuario_email}</p>
                            </div>
                            
                            ${reserva.usuario_telefono ? `
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Teléfono</label>
                                <p class="text-sm text-gray-900">${reserva.usuario_telefono}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                
                <!-- Información del Tour -->
                <div class="mt-6 space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Detalles del Tour</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Tour</label>
                                <p class="text-sm text-gray-900 font-medium">${reserva.tour_titulo}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Región</label>
                                <p class="text-sm text-gray-900">${reserva.nombre_region}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Duración</label>
                                <p class="text-sm text-gray-900">${reserva.tour_duracion}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Fecha del Tour</label>
                                <p class="text-sm text-gray-900 font-medium">${formatDate(reserva.fecha_tour)}</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Lugar de Salida</label>
                                <p class="text-sm text-gray-900">${reserva.lugar_salida}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Hora de Salida</label>
                                <p class="text-sm text-gray-900">${reserva.hora_salida}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Lugar de Llegada</label>
                                <p class="text-sm text-gray-900">${reserva.lugar_llegada}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Hora de Llegada</label>
                                <p class="text-sm text-gray-900">${reserva.hora_llegada}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Personal Asignado -->
                ${reserva.guia_nombre ? `
                <div class="mt-6 space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Personal Asignado</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Guía</label>
                            <p class="text-sm text-gray-900">${reserva.guia_nombre} ${reserva.guia_apellido}</p>
                        </div>
                        
                        ${reserva.chofer_nombre ? `
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Chofer</label>
                            <p class="text-sm text-gray-900">${reserva.chofer_nombre} ${reserva.chofer_apellido}</p>
                        </div>
                        ` : ''}
                    </div>
                    
                    ${reserva.vehiculo_marca ? `
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-500">Vehículo</label>
                        <p class="text-sm text-gray-900">${reserva.vehiculo_marca} ${reserva.vehiculo_modelo} - ${reserva.vehiculo_placa}</p>
                    </div>
                    ` : ''}
                </div>
                ` : ''}
                
                <!-- Lista de Pasajeros -->
                ${reserva.pasajeros && reserva.pasajeros.length > 0 ? `
                <div class="mt-6 space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Pasajeros (${reserva.pasajeros.length})</h4>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">DNI/Pasaporte</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nacionalidad</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${reserva.pasajeros.map(pasajero => `
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">${pasajero.nombre} ${pasajero.apellido}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${pasajero.dni_pasaporte}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${pasajero.nacionalidad}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${pasajero.tipo_pasajero}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${pasajero.telefono || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                ` : ''}
                
                <!-- Notas del Admin -->
                ${reserva.notas_admin ? `
                <div class="mt-6 space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Notas del Administrador</h4>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <p class="text-sm text-yellow-800">${reserva.notas_admin}</p>
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('viewModalContent').innerHTML = content;
        } else {
            showError('Error al cargar los detalles de la reserva');
            closeModal('viewModal');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showError('Error al cargar los detalles de la reserva');
        closeModal('viewModal');
    }
}

// Mostrar modal para editar estado
function editEstado(id, estadoActual) {
    document.getElementById('editReservaId').value = id;
    document.getElementById('editEstado').value = estadoActual;
    document.getElementById('editModal').classList.remove('hidden');
}

// Actualizar estado de reserva
async function updateEstado(event) {
    event.preventDefault();
    
    const id = document.getElementById('editReservaId').value;
    const nuevoEstado = document.getElementById('editEstado').value;
    
    try {
        const response = await fetch(`api/reservas.php?id=${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                estado: nuevoEstado
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Estado actualizado exitosamente');
            closeModal('editModal');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showError(data.error || 'Error al actualizar el estado');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showError('Error al actualizar el estado');
    }
}

// Eliminar reserva
async function deleteReserva(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta reserva? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        const response = await fetch(`api/reservas.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Reserva eliminada exitosamente');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showError(data.error || 'Error al eliminar la reserva');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showError('Error al eliminar la reserva');
    }
}

// Obtener clase CSS para el estado
function getEstadoClass(estado) {
    const classes = {
        'Pendiente': 'bg-yellow-100 text-yellow-800',
        'Confirmada': 'bg-green-100 text-green-800',
        'Cancelada': 'bg-red-100 text-red-800',
        'Finalizada': 'bg-blue-100 text-blue-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
}

// Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Cerrar modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Mostrar loading
function showLoading(elementId) {
    document.getElementById(elementId).innerHTML = `
        <div class="flex justify-center items-center h-32">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Cargando...</span>
        </div>
    `;
}

// Mostrar notificaciones
function showSuccess(message) {
    showNotification(message, 'success');
}

function showError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type) {
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-transform duration-300 translate-x-full`;
    notification.innerHTML = `
        <div class="flex items-center">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remover después de 5 segundos
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}
