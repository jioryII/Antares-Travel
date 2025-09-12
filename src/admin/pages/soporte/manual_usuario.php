<?php
require_once __DIR__ . '/../../auth/middleware.php';
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Manual de Usuario - Antares Travel";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../../../../imagenes/antares_logozz2.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .manual-section { scroll-margin-top: 100px; }
        .code-block { background: #f8f9fa; border-left: 4px solid #007bff; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-20 lg:pt-8 min-h-screen">
          <br><br><br>
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-book text-blue-600 mr-3"></i>
                        Manual de Usuario - Sistema Antares Travel
                    </h1>
                    <p class="text-gray-600">Guía completa para el uso del sistema de gestión de tours</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Índice de navegación -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-list mr-2"></i>Índice
                            </h3>
                            <nav class="space-y-2">
                                <a href="#introduccion" class="block text-sm text-blue-600 hover:text-blue-800 py-1">1. Introducción</a>
                                <a href="#dashboard" class="block text-sm text-blue-600 hover:text-blue-800 py-1">2. Panel de Control</a>
                                <a href="#tours" class="block text-sm text-blue-600 hover:text-blue-800 py-1">3. Gestión de Tours</a>
                                <a href="#reservas" class="block text-sm text-blue-600 hover:text-blue-800 py-1">4. Gestión de Reservas</a>
                                <a href="#usuarios" class="block text-sm text-blue-600 hover:text-blue-800 py-1">5. Gestión de Usuarios</a>
                                <a href="#vehiculos" class="block text-sm text-blue-600 hover:text-blue-800 py-1">6. Gestión de Vehículos</a>
                                <a href="#personal" class="block text-sm text-blue-600 hover:text-blue-800 py-1">7. Gestión de Personal</a>
                                <a href="#reportes" class="block text-sm text-blue-600 hover:text-blue-800 py-1">8. Reportes</a>
                                <a href="#configuracion" class="block text-sm text-blue-600 hover:text-blue-800 py-1">9. Configuración</a>
                                <a href="#soporte" class="block text-sm text-blue-600 hover:text-blue-800 py-1">10. Soporte Técnico</a>
                            </nav>
                        </div>
                    </div>

                    <!-- Contenido del manual -->
                    <div class="lg:col-span-3">
                        <!-- 1. Introducción -->
                        <section id="introduccion" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">1</span>
                                    Introducción al Sistema
                                </h2>
                                
                                <div class="prose max-w-none">
                                    <p class="text-gray-700 mb-4">
                                        Bienvenido al Sistema de Gestión de Antares Travel. Este manual le guiará a través de todas las 
                                        funcionalidades disponibles en el sistema administrativo.
                                    </p>
                                    
                                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                        <div class="flex items-start">
                                            <i class="fas fa-exclamation-triangle text-yellow-400 mr-2 mt-1"></i>
                                            <div>
                                                <h4 class="font-semibold text-yellow-800">Aviso Importante</h4>
                                                <p class="text-yellow-700 text-sm">
                                                    Este sistema fue desarrollado en un tiempo muy corto, por lo que algunos flujos 
                                                    pueden no estar completamente optimizados. Para soporte técnico contactar: 
                                                    <strong>942 287 756</strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Características Principales:</h4>
                                    <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-1">
                                        <li>Panel de control con estadísticas en tiempo real</li>
                                        <li>Gestión completa de tours y paquetes turísticos</li>
                                        <li>Sistema de reservas con estados y seguimiento</li>
                                        <li>Administración de usuarios y clientes</li>
                                        <li>Control de vehículos y mantenimiento</li>
                                        <li>Gestión de personal (choferes, guías)</li>
                                        <li>Reportes detallados y exportación de datos</li>
                                        <li>Configuración flexible del sistema</li>
                                    </ul>

                                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Acceso al Sistema:</h4>
                                    <div class="code-block p-4 rounded-lg mb-4">
                                        <p class="text-sm text-gray-700">
                                            <strong>URL:</strong> https://tu-dominio.com/src/admin/<br>
                                            <strong>Usuario:</strong> [Proporcionado por el administrador]<br>
                                            <strong>Contraseña:</strong> [Proporcionada por el administrador]
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 2. Panel de Control -->
                        <section id="dashboard" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-green-100 text-green-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">2</span>
                                    Panel de Control (Dashboard)
                                </h2>
                                
                                <p class="text-gray-700 mb-4">
                                    El dashboard es la página principal que muestra un resumen general de la actividad del sistema.
                                </p>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Elementos del Dashboard:</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div class="border border-blue-200 rounded-lg p-4">
                                        <h5 class="font-semibold text-blue-800 mb-2">
                                            <i class="fas fa-chart-bar mr-2"></i>Tarjetas de Estadísticas
                                        </h5>
                                        <ul class="text-sm text-gray-700 space-y-1">
                                            <li>• Total de Tours disponibles</li>
                                            <li>• Reservas del mes</li>
                                            <li>• Usuarios registrados</li>
                                            <li>• Ingresos totales</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="border border-green-200 rounded-lg p-4">
                                        <h5 class="font-semibold text-green-800 mb-2">
                                            <i class="fas fa-list mr-2"></i>Reservas Recientes
                                        </h5>
                                        <ul class="text-sm text-gray-700 space-y-1">
                                            <li>• Lista de últimas 5 reservas</li>
                                            <li>• Estado de cada reserva</li>
                                            <li>• Acceso rápido a detalles</li>
                                            <li>• Opciones de acción directa</li>
                                        </ul>
                                    </div>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Navegación:</h4>
                                <p class="text-gray-700 mb-4">
                                    Use el menú lateral izquierdo para navegar entre las diferentes secciones del sistema. 
                                    En dispositivos móviles, use el botón de hamburguesa (☰) para acceder al menú.
                                </p>
                            </div>
                        </section>

                        <!-- 3. Gestión de Tours -->
                        <section id="tours" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-purple-100 text-purple-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">3</span>
                                    Gestión de Tours
                                </h2>
                                
                                <p class="text-gray-700 mb-4">
                                    Administre todos los tours y paquetes turísticos disponibles en su empresa.
                                </p>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Funcionalidades Principales:</h4>
                                
                                <div class="space-y-4 mb-6">
                                    <div class="border-l-4 border-blue-400 pl-4">
                                        <h5 class="font-semibold text-gray-800">
                                            <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Crear Nuevo Tour
                                        </h5>
                                        <p class="text-sm text-gray-700">
                                            Complete el formulario con información del tour: nombre, descripción, precio, 
                                            duración, puntos de interés, hora de salida, etc.
                                        </p>
                                    </div>
                                    
                                    <div class="border-l-4 border-green-400 pl-4">
                                        <h5 class="font-semibold text-gray-800">
                                            <i class="fas fa-edit text-green-600 mr-2"></i>Editar Tours Existentes
                                        </h5>
                                        <p class="text-sm text-gray-700">
                                            Modifique precios, horarios, descripciones o cualquier detalle del tour. 
                                            Los cambios se reflejan inmediatamente en nuevas reservas.
                                        </p>
                                    </div>
                                    
                                    <div class="border-l-4 border-red-400 pl-4">
                                        <h5 class="font-semibold text-gray-800">
                                            <i class="fas fa-trash-alt text-red-600 mr-2"></i>Eliminar Tours
                                        </h5>
                                        <p class="text-sm text-gray-700">
                                            Desactive o elimine tours que ya no se ofrecen. Los tours con reservas 
                                            activas no pueden ser eliminados.
                                        </p>
                                    </div>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Campos Importantes:</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full border border-gray-200 text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="border border-gray-200 px-4 py-2 text-left">Campo</th>
                                                <th class="border border-gray-200 px-4 py-2 text-left">Descripción</th>
                                                <th class="border border-gray-200 px-4 py-2 text-left">Obligatorio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="border border-gray-200 px-4 py-2 font-medium">Nombre</td>
                                                <td class="border border-gray-200 px-4 py-2">Título del tour</td>
                                                <td class="border border-gray-200 px-4 py-2 text-green-600">Sí</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-gray-200 px-4 py-2 font-medium">Precio</td>
                                                <td class="border border-gray-200 px-4 py-2">Costo por persona en USD</td>
                                                <td class="border border-gray-200 px-4 py-2 text-green-600">Sí</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-gray-200 px-4 py-2 font-medium">Duración</td>
                                                <td class="border border-gray-200 px-4 py-2">Tiempo estimado del tour</td>
                                                <td class="border border-gray-200 px-4 py-2 text-green-600">Sí</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-gray-200 px-4 py-2 font-medium">Capacidad</td>
                                                <td class="border border-gray-200 px-4 py-2">Número máximo de participantes</td>
                                                <td class="border border-gray-200 px-4 py-2 text-gray-500">No</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>

                        <!-- 4. Gestión de Reservas -->
                        <section id="reservas" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-yellow-100 text-yellow-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">4</span>
                                    Gestión de Reservas
                                </h2>
                                
                                <p class="text-gray-700 mb-4">
                                    Administre todas las reservas de tours, desde la creación hasta la finalización.
                                </p>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Estados de Reserva:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></span>
                                            <span class="text-sm font-medium">Pendiente</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-green-400 rounded-full mr-2"></span>
                                            <span class="text-sm font-medium">Confirmada</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-blue-400 rounded-full mr-2"></span>
                                            <span class="text-sm font-medium">En Proceso</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-purple-400 rounded-full mr-2"></span>
                                            <span class="text-sm font-medium">Completada</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-red-400 rounded-full mr-2"></span>
                                            <span class="text-sm font-medium">Cancelada</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-gray-400 rounded-full mr-2"></span>
                                            <span class="text-sm font-medium">No Show</span>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Acciones Disponibles:</h4>
                                <div class="space-y-3">
                                    <div class="flex items-start">
                                        <i class="fas fa-eye text-blue-600 mr-3 mt-1"></i>
                                        <div>
                                            <h5 class="font-semibold text-gray-800">Ver Detalles</h5>
                                            <p class="text-sm text-gray-700">Consulte toda la información de la reserva</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-edit text-green-600 mr-3 mt-1"></i>
                                        <div>
                                            <h5 class="font-semibold text-gray-800">Editar Reserva</h5>
                                            <p class="text-sm text-gray-700">Modifique fechas, número de personas, etc.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-sync-alt text-purple-600 mr-3 mt-1"></i>
                                        <div>
                                            <h5 class="font-semibold text-gray-800">Cambiar Estado</h5>
                                            <p class="text-sm text-gray-700">Actualice el estado según el progreso</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 5. Gestión de Usuarios -->
                        <section id="usuarios" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">5</span>
                                    Gestión de Usuarios
                                </h2>
                                
                                <p class="text-gray-700 mb-4">
                                    Administre clientes y usuarios del sistema.
                                </p>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Tipos de Usuario:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div class="border border-blue-200 rounded-lg p-4">
                                        <h5 class="font-semibold text-blue-800 mb-2">
                                            <i class="fas fa-user-tie mr-2"></i>Administradores
                                        </h5>
                                        <p class="text-sm text-gray-700">
                                            Acceso completo al sistema, pueden gestionar todos los módulos
                                        </p>
                                    </div>
                                    
                                    <div class="border border-green-200 rounded-lg p-4">
                                        <h5 class="font-semibold text-green-800 mb-2">
                                            <i class="fas fa-users mr-2"></i>Clientes
                                        </h5>
                                        <p class="text-sm text-gray-700">
                                            Usuarios que realizan reservas, información de contacto y historial
                                        </p>
                                    </div>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Funciones Principales:</h4>
                                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-1">
                                    <li>Crear nuevos usuarios y clientes</li>
                                    <li>Editar información de contacto</li>
                                    <li>Verificar emails y documentos</li>
                                    <li>Exportar listas de usuarios</li>
                                    <li>Gestionar roles y permisos</li>
                                    <li>Ver historial de reservas por usuario</li>
                                </ul>
                            </div>
                        </section>

                        <!-- 6. Gestión de Vehículos -->
                        <section id="vehiculos" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-red-100 text-red-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">6</span>
                                    Gestión de Vehículos
                                </h2>
                                
                                <p class="text-gray-700 mb-4">
                                    Controle la flota de vehículos disponibles para los tours.
                                </p>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Información de Vehículos:</h4>
                                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-1">
                                    <li>Placa y modelo del vehículo</li>
                                    <li>Capacidad de pasajeros</li>
                                    <li>Estado (Disponible, En uso, Mantenimiento)</li>
                                    <li>Fecha de última revisión</li>
                                    <li>Notas de mantenimiento</li>
                                    <li>Asignación a choferes</li>
                                </ul>

                                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-4">
                                    <h5 class="font-semibold text-blue-800 mb-2">Mantenimiento Preventivo</h5>
                                    <p class="text-blue-700 text-sm">
                                        Configure recordatorios automáticos para revisiones técnicas y mantenimientos programados.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- 7. Gestión de Personal -->
                        <section id="personal" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-teal-100 text-teal-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">7</span>
                                    Gestión de Personal
                                </h2>
                                
                                <p class="text-gray-700 mb-4">
                                    Administre el personal involucrado en los tours.
                                </p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="border border-orange-200 rounded-lg p-4">
                                        <h5 class="font-semibold text-orange-800 mb-2">
                                            <i class="fas fa-car mr-2"></i>Choferes
                                        </h5>
                                        <ul class="text-sm text-gray-700 space-y-1">
                                            <li>• Datos personales y licencia</li>
                                            <li>• Vehículos asignados</li>
                                            <li>• Horarios de trabajo</li>
                                            <li>• Evaluaciones de desempeño</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="border border-purple-200 rounded-lg p-4">
                                        <h5 class="font-semibold text-purple-800 mb-2">
                                            <i class="fas fa-microphone mr-2"></i>Guías Turísticos
                                        </h5>
                                        <ul class="text-sm text-gray-700 space-y-1">
                                            <li>• Información de contacto</li>
                                            <li>• Idiomas que habla</li>
                                            <li>• Especialidades en tours</li>
                                            <li>• Certificaciones</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 8. Reportes -->
                        <section id="reportes" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-pink-100 text-pink-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">8</span>
                                    Reportes y Estadísticas
                                </h2>
                                
                                <p class="text-gray-700 mb-4">
                                    Genere reportes detallados sobre el rendimiento del negocio.
                                </p>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Tipos de Reportes:</h4>
                                <div class="space-y-4">
                                    <div class="border-l-4 border-green-400 pl-4">
                                        <h5 class="font-semibold text-gray-800">Reporte de Ventas</h5>
                                        <p class="text-sm text-gray-700">Ingresos por período, tours más vendidos, tendencias</p>
                                    </div>
                                    
                                    <div class="border-l-4 border-blue-400 pl-4">
                                        <h5 class="font-semibold text-gray-800">Reporte de Clientes</h5>
                                        <p class="text-sm text-gray-700">Análisis demográfico, clientes frecuentes, satisfacción</p>
                                    </div>
                                    
                                    <div class="border-l-4 border-purple-400 pl-4">
                                        <h5 class="font-semibold text-gray-800">Reporte Operativo</h5>
                                        <p class="text-sm text-gray-700">Uso de vehículos, desempeño del personal, ocupación</p>
                                    </div>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3 mt-6">Exportación:</h4>
                                <p class="text-gray-700 mb-4">
                                    Los reportes pueden exportarse en formato Excel (XLSX) o PDF para su análisis externo 
                                    o presentación a stakeholders.
                                </p>
                            </div>
                        </section>

                        <!-- 9. Configuración -->
                        <section id="configuracion" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-gray-100 text-gray-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">9</span>
                                    Configuración del Sistema
                                </h2>
                                
                                <p class="text-gray-700 mb-4">
                                    Personalice el comportamiento del sistema según las necesidades de su empresa.
                                </p>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Áreas de Configuración:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-building text-blue-600 mr-2"></i>
                                            <span class="text-sm">Información de la empresa</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                                            <span class="text-sm">Precios y tarifas</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-bell text-yellow-600 mr-2"></i>
                                            <span class="text-sm">Notificaciones automáticas</span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-shield-alt text-red-600 mr-2"></i>
                                            <span class="text-sm">Configuración de seguridad</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-envelope text-purple-600 mr-2"></i>
                                            <span class="text-sm">Plantillas de email</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-database text-gray-600 mr-2"></i>
                                            <span class="text-sm">Backup automático</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 10. Soporte Técnico -->
                        <section id="soporte" class="manual-section mb-12">
                            <div class="bg-white rounded-lg shadow-sm p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                    <span class="bg-orange-100 text-orange-600 rounded-full w-8 h-8 inline-flex items-center justify-center text-sm font-bold mr-3">10</span>
                                    Soporte Técnico
                                </h2>
                                
                                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                                    <div class="flex items-start">
                                        <i class="fas fa-exclamation-triangle text-red-400 mr-2 mt-1"></i>
                                        <div>
                                            <h4 class="font-semibold text-red-800">Aviso Importante</h4>
                                            <p class="text-red-700 text-sm">
                                                Este sistema fue desarrollado en un tiempo muy corto. Para cualquier 
                                                consulta, modificación futura o soporte técnico, contacte al equipo de desarrollo.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                                    <h4 class="text-lg font-semibold text-blue-800 mb-3">Contacto de Soporte</h4>
                                    <div class="space-y-2">
                                        <p class="text-blue-700">
                                            <i class="fas fa-phone mr-2"></i>
                                            <strong>Teléfono:</strong> <span class="text-xl font-bold">942 287 756</span>
                                        </p>
                                        <p class="text-blue-700">
                                            <i class="fas fa-envelope mr-2"></i>
                                            <strong>Email:</strong> soporte@antarestravel.com
                                        </p>
                                        <p class="text-blue-700">
                                            <i class="fas fa-clock mr-2"></i>
                                            <strong>Horario:</strong> Lunes a Viernes, 8:00 AM - 6:00 PM
                                        </p>
                                    </div>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Tipos de Soporte:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <i class="fas fa-bug text-red-500 mr-2"></i>
                                            <span class="text-sm">Reporte de errores</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-question-circle text-blue-500 mr-2"></i>
                                            <span class="text-sm">Consultas de uso</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-graduation-cap text-purple-500 mr-2"></i>
                                            <span class="text-sm">Capacitación</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                                            <span class="text-sm">Nuevas funcionalidades</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-cogs text-orange-500 mr-2"></i>
                                            <span class="text-sm">Configuración avanzada</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-database text-gray-500 mr-2"></i>
                                            <span class="text-sm">Backup y restauración</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                    <h5 class="font-semibold text-gray-800 mb-2">Antes de Contactar Soporte:</h5>
                                    <ul class="text-sm text-gray-700 space-y-1">
                                        <li>1. Describa detalladamente el problema o consulta</li>
                                        <li>2. Indique en qué sección del sistema ocurre</li>
                                        <li>3. Mencione si hay mensajes de error específicos</li>
                                        <li>4. Proporcione capturas de pantalla si es posible</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <!-- Información adicional -->
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg p-6 border border-blue-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Información del Sistema
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                                <div>
                                    <p><strong>Sistema:</strong> Antares Travel Management</p>
                                    <p><strong>Versión:</strong> 1.0</p>
                                    <p><strong>Última actualización:</strong> <?php echo date('d/m/Y'); ?></p>
                                </div>
                                <div>
                                    <p><strong>Desarrollado por:</strong> Equipo Antares Travel</p>
                                    <p><strong>Soporte:</strong> 942 287 756</p>
                                    <p><strong>Manual actualizado:</strong> <?php echo date('d/m/Y'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Smooth scrolling para enlaces del índice
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Highlight de la sección activa en el índice
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.manual-section');
            const navLinks = document.querySelectorAll('nav a[href^="#"]');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 120;
                if (window.pageYOffset >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('font-bold', 'text-blue-800');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('font-bold', 'text-blue-800');
                }
            });
        });

        // Animación de entrada para las secciones
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.manual-section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            sections.forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'all 0.6s ease-out';
                observer.observe(section);
            });
        });
    </script>
</body>
</html>
