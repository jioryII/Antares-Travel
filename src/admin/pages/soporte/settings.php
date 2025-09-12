<?php
require_once __DIR__ . '/../../auth/middleware.php';
verificarSesionAdmin();
$admin = obtenerAdminActual();
$page_title = "Configuración y Ajustes del Sistema";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Antares Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../../../../imagenes/antares_logozz2.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-20 lg:pt-8 min-h-screen">
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-8">
                    <br><br><br>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-cog text-blue-600 mr-3"></i>
                        Configuración del Sistema
                    </h1>
                    <p class="text-gray-600">Administra las configuraciones generales de Antares Travel</p>
                </div>

                <!-- Alertas y Avisos Importantes -->
                <div class="mb-8">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow-sm">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-yellow-800 mb-2">
                                    <i class="fas fa-info-circle mr-2"></i>Aviso Importante sobre el Sistema
                                </h3>
                                <div class="text-yellow-700 space-y-2">
                                    <p><strong>Esta aplicación fue desarrollada en un tiempo muy corto</strong> por lo que no se realizaron todos los flujos de manera adecuada.</p>
                                    <p>Para cualquier consulta, modificación futura o soporte técnico, favor contactar:</p>
                                    <div class="bg-yellow-100 p-3 rounded-md mt-3">
                                        <p class="font-semibold">📞 Soporte Técnico: <span class="text-blue-600">942 287 756</span></p>
                                        <p class="text-sm">Horario de atención: Lunes a Viernes, 8:00 AM - 6:00 PM</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navegación por pestañas -->
                <div class="mb-8">
                    <nav class="flex space-x-8" aria-label="Tabs">
                        <button onclick="showTab('general')" id="tab-general" class="tab-button active py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                            <i class="fas fa-sliders-h mr-2"></i>Configuraciones Generales
                        </button>
                        <button onclick="showTab('soporte')" id="tab-soporte" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            <i class="fas fa-headset mr-2"></i>Soporte Técnico
                        </button>
                        <button onclick="showTab('terminos')" id="tab-terminos" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            <i class="fas fa-file-contract mr-2"></i>Términos y Condiciones
                        </button>
                    </nav>
                </div>

                <!-- Contenido de las pestañas -->
                
                <!-- Pestaña: Configuraciones Generales -->
                <div id="content-general" class="tab-content">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Configuración de la Empresa -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-building text-blue-600 mr-2"></i>Información de la Empresa
                            </h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Empresa</label>
                                    <input type="text" value="Antares Travel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email de Contacto</label>
                                    <input type="email" value="info@antarestravel.com" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                    <input type="tel" value="+51 942 287 756" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                                    <textarea rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">Centro Artesanal Cusco, Stand 39 - Wanchaq, Cusco</textarea>
                                </div>
                                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                                </button>
                            </form>
                        </div>

                        <!-- Configuración de Tours -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-map-marked-alt text-green-600 mr-2"></i>Configuración de Tours
                            </h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Precio Base por Persona</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500">$</span>
                                        <input type="number" value="50" class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacidad Máxima por Tour</label>
                                    <input type="number" value="15" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Días de Anticipación para Reservas</label>
                                    <input type="number" value="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="auto-confirm" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="auto-confirm" class="ml-2 block text-sm text-gray-700">Auto-confirmar reservas</label>
                                </div>
                                <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i>Actualizar Configuración
                                </button>
                            </form>
                        </div>

                        <!-- Configuración de Notificaciones -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-bell text-yellow-600 mr-2"></i>Notificaciones
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Email de nuevas reservas</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">SMS de confirmación</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Recordatorios 24h antes</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración de Seguridad -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-shield-alt text-red-600 mr-2"></i>Seguridad
                            </h3>
                            <div class="space-y-4">
                                <button class="w-full bg-red-100 text-red-700 py-2 px-4 rounded-md hover:bg-red-200 transition-colors border border-red-200">
                                    <i class="fas fa-key mr-2"></i>Cambiar Contraseña de Admin
                                </button>
                                <button class="w-full bg-orange-100 text-orange-700 py-2 px-4 rounded-md hover:bg-orange-200 transition-colors border border-orange-200">
                                    <i class="fas fa-download mr-2"></i>Backup de Base de Datos
                                </button>
                                <button class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-200 transition-colors border border-gray-200">
                                    <i class="fas fa-history mr-2"></i>Ver Logs del Sistema
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña: Soporte Técnico -->
                <div id="content-soporte" class="tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Información de Contacto -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-phone text-blue-600 mr-2"></i>Contacto de Soporte
                            </h3>
                            <div class="space-y-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-phone text-blue-600 mr-2"></i>
                                        <span class="font-semibold text-blue-800">Teléfonos de Soporte</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div>
                                            <p class="text-xl font-bold text-blue-600">942 287 756</p>
                                            <p class="text-xs text-blue-700">Soporte técnico principal</p>
                                        </div>
                                        <div>
                                            <p class="text-xl font-bold text-blue-600">984 423 824</p>
                                            <p class="text-xs text-blue-700">Soporte técnico alternativo</p>
                                        </div>
                                        <div>
                                            <p class="text-xl font-bold text-blue-600">930 173 314</p>
                                            <p class="text-xs text-blue-700">Desarrollo y mantenimiento</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-envelope text-green-600 mr-2"></i>
                                        <span class="font-semibold text-green-800">Email de Soporte</span>
                                    </div>
                                    <p class="text-lg font-semibold text-green-600">soporte@antarestravel.com</p>
                                    <p class="text-sm text-green-700">Para consultas técnicas detalladas</p>
                                </div>

                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-clock text-purple-600 mr-2"></i>
                                        <span class="font-semibold text-purple-800">Horario de Atención</span>
                                    </div>
                                    <p class="text-purple-700">Lunes a Viernes: 8:00 AM - 6:00 PM</p>
                                    <p class="text-purple-700">Sábados: 9:00 AM - 2:00 PM</p>
                                    <p class="text-sm text-purple-600 mt-1">Zona horaria: UTC-5 (Perú)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tipos de Soporte -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-tools text-orange-600 mr-2"></i>Tipos de Soporte Disponible
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-bug text-red-500 mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Reporte de Errores</h4>
                                        <p class="text-sm text-gray-600">Corrección de bugs y problemas técnicos</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-plus-circle text-blue-500 mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Nuevas Funcionalidades</h4>
                                        <p class="text-sm text-gray-600">Desarrollo de características adicionales</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-cogs text-green-500 mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Configuración</h4>
                                        <p class="text-sm text-gray-600">Ajustes y personalización del sistema</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-graduation-cap text-purple-500 mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Capacitación</h4>
                                        <p class="text-sm text-gray-600">Entrenamiento en uso del sistema</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-database text-yellow-500 mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Backup y Restauración</h4>
                                        <p class="text-sm text-gray-600">Respaldo y recuperación de datos</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Proceso de Soporte -->
                        <div class="bg-white rounded-lg shadow-sm p-6 lg:col-span-2">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-list-ol text-indigo-600 mr-2"></i>Proceso de Solicitud de Soporte
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="text-blue-600 font-bold">1</span>
                                    </div>
                                    <h4 class="font-semibold text-gray-800 mb-2">Contacto Inicial</h4>
                                    <p class="text-sm text-gray-600">Llamar al 942 287 756, 984 423 824 o 930 173 314</p>
                                </div>
                                <div class="text-center">
                                    <div class="bg-green-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="text-green-600 font-bold">2</span>
                                    </div>
                                    <h4 class="font-semibold text-gray-800 mb-2">Evaluación</h4>
                                    <p class="text-sm text-gray-600">Análisis del problema o requerimiento</p>
                                </div>
                                <div class="text-center">
                                    <div class="bg-yellow-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="text-yellow-600 font-bold">3</span>
                                    </div>
                                    <h4 class="font-semibold text-gray-800 mb-2">Implementación</h4>
                                    <p class="text-sm text-gray-600">Desarrollo de la solución</p>
                                </div>
                                <div class="text-center">
                                    <div class="bg-purple-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="text-purple-600 font-bold">4</span>
                                    </div>
                                    <h4 class="font-semibold text-gray-800 mb-2">Entrega</h4>
                                    <p class="text-sm text-gray-600">Despliegue y capacitación</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña: Términos y Condiciones -->
                <div id="content-terminos" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-2xl font-semibold text-gray-900 mb-6">
                            <i class="fas fa-file-contract text-blue-600 mr-2"></i>Términos y Condiciones de Uso
                        </h3>
                        
                        <div class="prose max-w-none">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">1. Aceptación de los Términos</h4>
                            <p class="text-gray-700 mb-4">
                                Al utilizar el sistema de gestión de Antares Travel, usted acepta estar sujeto a estos términos y condiciones de uso. 
                                Si no está de acuerdo con alguno de estos términos, no debe utilizar este sistema.
                            </p>

                            <h4 class="text-lg font-semibold text-gray-800 mb-3">2. Descripción del Servicio</h4>
                            <p class="text-gray-700 mb-4">
                                Antares Travel proporciona un sistema de gestión para tours turísticos, incluyendo:
                            </p>
                            <ul class="list-disc pl-6 mb-4 text-gray-700">
                                <li>Gestión de reservas de tours</li>
                                <li>Administración de clientes y usuarios</li>
                                <li>Control de vehículos y personal</li>
                                <li>Reportes y estadísticas</li>
                                <li>Configuración del sistema</li>
                            </ul>

                            <h4 class="text-lg font-semibold text-gray-800 mb-3">3. Responsabilidades del Usuario</h4>
                            <ul class="list-disc pl-6 mb-4 text-gray-700">
                                <li>Mantener la confidencialidad de sus credenciales de acceso</li>
                                <li>Usar el sistema únicamente para fines autorizados</li>
                                <li>No intentar acceder a áreas restringidas del sistema</li>
                                <li>Reportar cualquier problema de seguridad al soporte técnico</li>
                                <li>No compartir información confidencial de clientes</li>
                            </ul>

                            <h4 class="text-lg font-semibold text-gray-800 mb-3">4. Limitaciones de Responsabilidad</h4>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                                <p class="text-yellow-800">
                                    <strong>IMPORTANTE:</strong> Este sistema fue desarrollado en un tiempo muy corto, por lo que 
                                    no se realizaron todos los flujos de validación y seguridad de manera completa. El uso es bajo 
                                    su propia responsabilidad.
                                </p>
                            </div>
                            <ul class="list-disc pl-6 mb-4 text-gray-700">
                                <li>El sistema se proporciona "tal como está" sin garantías expresas o implícitas</li>
                                <li>No garantizamos la disponibilidad ininterrumpida del servicio</li>
                                <li>No somos responsables por pérdida de datos debido a fallas técnicas</li>
                                <li>Se recomienda realizar respaldos regulares de la información</li>
                            </ul>

                            <h4 class="text-lg font-semibold text-gray-800 mb-3">5. Privacidad y Protección de Datos</h4>
                            <ul class="list-disc pl-6 mb-4 text-gray-700">
                                <li>Los datos personales se manejan conforme a la Ley de Protección de Datos Personales</li>
                                <li>La información de clientes debe ser tratada con confidencialidad</li>
                                <li>Solo personal autorizado puede acceder a datos sensibles</li>
                                <li>Se deben reportar inmediatamente cualquier brecha de seguridad</li>
                            </ul>

                            <h4 class="text-lg font-semibold text-gray-800 mb-3">6. Soporte y Mantenimiento</h4>
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                                <p class="text-blue-800">
                                    Para cualquier consulta, modificación futura o soporte técnico:
                                </p>
                                <p class="text-blue-800 font-semibold mt-2">
                                    📞 Contactar al: <span class="text-lg">942 287 756</span>
                                </p>
                                <p class="text-blue-700 text-sm">Equipo de Desarrollo - Antares Travel</p>
                            </div>

                            <h4 class="text-lg font-semibold text-gray-800 mb-3">7. Modificaciones del Sistema</h4>
                            <ul class="list-disc pl-6 mb-4 text-gray-700">
                                <li>Todas las modificaciones deben ser solicitadas al equipo de desarrollo</li>
                                <li>Los cambios serán evaluados en términos de viabilidad y costo</li>
                                <li>Se proporcionará un cronograma para implementación de mejoras</li>
                                <li>Las actualizaciones pueden requerir capacitación adicional</li>
                            </ul>

                            <h4 class="text-lg font-semibold text-gray-800 mb-3">8. Terminación del Servicio</h4>
                            <p class="text-gray-700 mb-4">
                                Nos reservamos el derecho de suspender o terminar el acceso al sistema en caso de:
                            </p>
                            <ul class="list-disc pl-6 mb-4 text-gray-700">
                                <li>Uso inadecuado del sistema</li>
                                <li>Violación de estos términos y condiciones</li>
                                <li>Actividades que comprometan la seguridad del sistema</li>
                                <li>Falta de pago por servicios de soporte (si aplica)</li>
                            </ul>

                            <h4 class="text-lg font-semibold text-gray-800 mb-3">9. Ley Aplicable</h4>
                            <p class="text-gray-700 mb-4">
                                Estos términos se rigen por las leyes de la República del Perú. Cualquier disputa 
                                será resuelta en los tribunales competentes de Cusco, Perú.
                            </p>

                            <div class="bg-gray-50 p-4 rounded-lg mt-6">
                                <p class="text-sm text-gray-600">
                                    <strong>Fecha de última actualización:</strong> <?php echo date('d/m/Y'); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <strong>Versión:</strong> 1.0 - Sistema de Gestión Antares Travel
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Ocultar todo el contenido de las pestañas
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remover clase activa de todos los botones
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar el contenido seleccionado
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Activar el botón seleccionado
            const activeButton = document.getElementById('tab-' + tabName);
            activeButton.classList.add('active', 'border-blue-500', 'text-blue-600');
            activeButton.classList.remove('border-transparent', 'text-gray-500');
        }

        // Efectos de animación para las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.bg-white');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
