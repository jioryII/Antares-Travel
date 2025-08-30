<?php
require_once '../config/config.php';
require_once '../auth/middleware.php';

$admin = getCurrentAdmin();
$page_title = "Instalación de Tours Diarios";

// Solo super_admin puede ejecutar instalaciones
if (!hasPermission('manage_admins')) {
    redirect('../dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_POST && isset($_POST['install'])) {
    try {
        $connection = getConnection();
        
        // Leer el archivo SQL
        $sql_file = __DIR__ . '/sql/tours_diarios_schema.sql';
        if (!file_exists($sql_file)) {
            throw new Exception('Archivo SQL no encontrado');
        }
        
        $sql = file_get_contents($sql_file);
        
        // Dividir las consultas por punto y coma
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        $connection->beginTransaction();
        
        foreach ($queries as $query) {
            if (!empty($query) && !str_starts_with(trim($query), '--')) {
                $connection->exec($query);
            }
        }
        
        $connection->commit();
        
        // Log de auditoría
        logActivity($admin['id_admin'], 'INSTALL', 'sistema', null, 'Instalación de módulo Tours Diarios');
        
        $message = '✅ Módulo de Tours Diarios instalado correctamente. Las tablas y datos de ejemplo han sido creados.';
        
    } catch (Exception $e) {
        $connection->rollback();
        $error = '❌ Error durante la instalación: ' . $e->getMessage();
    }
}

// Verificar si ya está instalado
$is_installed = false;
try {
    $connection = getConnection();
    $result = $connection->query("SHOW TABLES LIKE 'tours_diarios'");
    $is_installed = $result->rowCount() > 0;
} catch (Exception $e) {
    // Error al verificar
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 ml-64 p-8">
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900"><?php echo $page_title; ?></h1>
                <p class="text-gray-600 mt-1">Instalar módulo de gestión de tours diarios</p>
            </div>

            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
                <?php if ($is_installed): ?>
                    <div class="text-center">
                        <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Módulo Ya Instalado</h2>
                        <p class="text-gray-600 mb-6">El módulo de Tours Diarios ya está instalado y listo para usar.</p>
                        <a href="pages/tours/tours_diarios.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-calendar-day mr-2"></i>Ir a Tours Diarios
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <i class="fas fa-download text-blue-500 text-6xl mb-4"></i>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Instalar Módulo de Tours Diarios</h2>
                        <p class="text-gray-600 mb-6">Este módulo creará las siguientes tablas y funcionalidades:</p>
                        
                        <div class="text-left bg-gray-50 p-4 rounded-lg mb-6">
                            <h3 class="font-semibold text-gray-800 mb-3">Funcionalidades incluidas:</h3>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Registro de tours diarios con asignación de recursos</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Gestión de disponibilidad de guías, choferes y vehículos</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Control automático de disponibilidad por fechas</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Historial y seguimiento de tours programados</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Integración con el sistema de auditoría</li>
                            </ul>
                        </div>
                        
                        <div class="text-left bg-yellow-50 p-4 rounded-lg mb-6">
                            <h3 class="font-semibold text-yellow-800 mb-3">Tablas que se crearán:</h3>
                            <ul class="space-y-1 text-sm text-yellow-700">
                                <li>• tours_diarios</li>
                                <li>• disponibilidad_guias</li>
                                <li>• chofer_disponibilidad</li>
                                <li>• disponibilidad_vehiculos</li>
                                <li>• guias (con datos de ejemplo)</li>
                                <li>• choferes (con datos de ejemplo)</li>
                                <li>• vehiculos (con datos de ejemplo)</li>
                            </ul>
                        </div>

                        <form method="POST" class="space-y-4">
                            <div class="bg-red-50 border border-red-200 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                                    <div>
                                        <p class="text-red-800 font-semibold">Advertencia</p>
                                        <p class="text-red-700 text-sm">Esta operación modificará la base de datos. Se recomienda hacer un respaldo antes de continuar.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="confirm" name="confirm" required class="mr-2">
                                <label for="confirm" class="text-sm text-gray-700">He leído la advertencia y deseo continuar con la instalación</label>
                            </div>
                            
                            <button type="submit" name="install" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                <i class="fas fa-download mr-2"></i>Instalar Módulo
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
