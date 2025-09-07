<?php
/**
 * Punto de entrada principal del panel de administración
 * Redirige automáticamente al dashboard
 */

// Función para redireccionar al dashboard
function redirectToDashboard() {
    // Verificar si ya estamos en el dashboard para evitar bucles infinitos
    $currentPath = $_SERVER['REQUEST_URI'];
    
    if (strpos($currentPath, 'dashboard') === false) {
        // URL absoluta del dashboard desde la raíz del dominio
        $dashboardUrl = '/src/admin/pages/dashboard/';
        
        // Verificar que el directorio del dashboard existe
        if (is_dir(__DIR__ . '/pages/dashboard')) {
            // Redirección con código 302 (redirección temporal)
            header("Location: $dashboardUrl", true, 302);
            exit();
        } else {
            // Si no existe el dashboard, mostrar una alerta visual
            echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl border border-red-200">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Panel No Disponible</h3>
                        <p class="text-sm text-gray-600">Error de configuración del sistema</p>
                    </div>
                </div>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800">
                                <strong>Advertencia:</strong> Su panel de administrador no está disponible en este momento.
                            </p>
                            <p class="text-sm text-red-700 mt-2">
                                Por favor, póngase en contacto con el soporte técnico para resolver este problema.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="window.location.reload()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-redo mr-2"></i>
                        Intentar de Nuevo
                    </button>
                    <button onclick="window.history.back()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver
                    </button>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">
                        Si el problema persiste, contacte al administrador del sistema
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
            exit();
        }
    }
}

// Ejecutar la redirección
redirectToDashboard();
?>
