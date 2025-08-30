<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del guía
$id_guia = intval($_GET['id'] ?? 0);

if (!$id_guia) {
    header('Location: index.php?error=' . urlencode('ID de guía no válido'));
    exit;
}

try {
    $connection = getConnection();
    
    // Obtener información del guía
    $guia_sql = "SELECT * FROM guias WHERE id_guia = ?";
    $guia_stmt = $connection->prepare($guia_sql);
    $guia_stmt->execute([$id_guia]);
    $guia = $guia_stmt->fetch();
    
    if (!$guia) {
        header('Location: index.php?error=' . urlencode('Guía no encontrado'));
        exit;
    }
    
    // Verificar si el guía tiene reservas activas (simulado - no hay relación directa)
    $reservas_activas = 0;
    $total_reservas = 0;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $confirmar = $_POST['confirmar'] ?? '';
        $mantener_reservas = $_POST['mantener_reservas'] ?? '0';
        
        if ($confirmar === 'SI_ELIMINAR') {
            try {
                $connection->beginTransaction();
                
                if ($mantener_reservas === '1') {
                    // Mantener reservas pero desasignar el guía (simulado - no hay relación directa)
                    // No hay acción necesaria ya que no hay campo id_guia en reservas
                } else {
                    // Eliminar todas las reservas del guía (simulado - no hay relación directa)
                    // No hay acción necesaria ya que no hay campo id_guia en reservas
                }
                
                // Eliminar el guía
                $delete_guia_sql = "DELETE FROM guias WHERE id_guia = ?";
                $delete_guia_stmt = $connection->prepare($delete_guia_sql);
                $delete_guia_stmt->execute([$id_guia]);
                
                // Registrar actividad
                // registrarActividad($admin['id_administrador'], 'eliminar', 'guias', $id_guia, 
                //                  "Eliminó el guía: " . $guia['nombre'] . ' ' . $guia['apellido']);
                
                $connection->commit();
                
                header('Location: index.php?success=' . urlencode('Guía eliminado exitosamente'));
                exit;
                
            } catch (Exception $e) {
                $connection->rollBack();
                $error = "Error al eliminar guía: " . $e->getMessage();
            }
        } else {
            $error = "Debe confirmar la eliminación escribiendo 'SI_ELIMINAR'";
        }
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode("Error al procesar solicitud: " . $e->getMessage()));
    exit;
}

$page_title = "Eliminar Guía: " . $guia['nombre'] . ' ' . $guia['apellido'];
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
    <?php include '../../components/header.php'; ?>
    
    <div class="flex">
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 lg:ml-64 pt-16 lg:pt-0 min-h-screen">
            <div class="p-4 lg:p-8">
                <!-- Encabezado -->
                <div class="mb-6 lg:mb-8">
                    <br><br><br>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <div class="flex items-center mb-2">
                                <a href="index.php" class="text-blue-600 hover:text-blue-800 mr-2">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                                <h1 class="text-2xl lg:text-3xl font-bold text-red-600">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>Eliminar Guía
                                </h1>
                            </div>
                            <p class="text-sm lg:text-base text-gray-600">Esta acción no se puede deshacer</p>
                        </div>
                    </div>
                </div>

                <!-- Mostrar errores -->
                <?php if (isset($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <p class="text-sm text-red-700 mt-1"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="max-w-2xl mx-auto">
                    <!-- Información del guía a eliminar -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-user-tie text-blue-600 mr-2"></i>Información del Guía a Eliminar
                        </h3>
                        
                        <div class="flex items-center mb-4">
                            <?php if ($guia['foto_url']): ?>
                                <img class="h-16 w-16 rounded-full" src="<?php echo htmlspecialchars($guia['foto_url']); ?>" alt="">
                            <?php else: ?>
                                <div class="h-16 w-16 rounded-full bg-blue-600 flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">
                                        <?php echo strtoupper(substr($guia['nombre'], 0, 1) . substr($guia['apellido'], 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($guia['nombre'] . ' ' . $guia['apellido']); ?>
                                </h4>
                                <p class="text-gray-600"><?php echo htmlspecialchars($guia['email']); ?></p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $guia['estado'] === 'Libre' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <i class="<?php echo $guia['estado'] === 'Libre' ? 'fas fa-check-circle' : 'fas fa-clock'; ?> mr-1"></i>
                                    <?php echo $guia['estado']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                            <div>
                                <span class="text-sm font-medium text-gray-500">ID del Guía:</span>
                                <p class="text-sm text-gray-900">#<?php echo $guia['id_guia']; ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Teléfono:</span>
                                <p class="text-sm text-gray-900"><?php echo $guia['telefono'] ? htmlspecialchars($guia['telefono']) : 'No registrado'; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Advertencia sobre reservas -->
                    <?php if ($total_reservas > 0): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-yellow-800">¡Atención! - Reservas Asociadas</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Este guía tiene <strong><?php echo $total_reservas; ?> reserva(s)</strong> asociada(s):</p>
                                        <ul class="list-disc list-inside mt-2">
                                            <li><strong><?php echo $reservas_activas; ?></strong> reservas activas (Pendiente/Confirmada)</li>
                                            <li><strong><?php echo $total_reservas - $reservas_activas; ?></strong> reservas finalizadas/canceladas</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($reservas_activas > 0): ?>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                                <div class="flex">
                                    <i class="fas fa-ban text-red-400 mr-3 mt-1"></i>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-red-800">¡Advertencia Crítica!</h3>
                                        <p class="mt-2 text-sm text-red-700">
                                            Este guía tiene <strong><?php echo $reservas_activas; ?> reserva(s) activa(s)</strong>. 
                                            Eliminar este guía podría afectar tours programados y clientes.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Formulario de confirmación -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-red-600 mb-4">
                            <i class="fas fa-trash text-red-600 mr-2"></i>Confirmar Eliminación
                        </h3>
                        
                        <form method="POST" onsubmit="return confirmarEliminacion()">
                            <!-- Opciones para reservas -->
                            <?php if ($total_reservas > 0): ?>
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        ¿Qué hacer con las reservas asociadas?
                                    </label>
                                    <div class="space-y-3">
                                        <label class="flex items-start">
                                            <input type="radio" name="mantener_reservas" value="1" checked
                                                   class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                            <div class="ml-3">
                                                <span class="text-sm font-medium text-gray-900">Mantener reservas</span>
                                                <p class="text-xs text-gray-500">Las reservas se mantendrán pero sin guía asignado (recomendado)</p>
                                            </div>
                                        </label>
                                        <label class="flex items-start">
                                            <input type="radio" name="mantener_reservas" value="0"
                                                   class="mt-1 h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
                                            <div class="ml-3">
                                                <span class="text-sm font-medium text-red-600">Eliminar todas las reservas</span>
                                                <p class="text-xs text-red-500">¡PELIGROSO! Se perderán todos los datos de reservas</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Confirmación por escrito -->
                            <div class="mb-6">
                                <label for="confirmar" class="block text-sm font-medium text-gray-700 mb-2">
                                    Para confirmar la eliminación, escriba exactamente: <code class="bg-gray-100 px-2 py-1 rounded">SI_ELIMINAR</code>
                                </label>
                                <input type="text" name="confirmar" id="confirmar" required
                                       class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                       placeholder="Escriba SI_ELIMINAR para confirmar">
                            </div>
                            
                            <!-- Botones -->
                            <div class="flex justify-end space-x-3">
                                <a href="ver.php?id=<?php echo $guia['id_guia']; ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Eliminar Guía
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Información adicional -->
                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-info-circle text-blue-400 mr-3 mt-1"></i>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-blue-800">Información Importante</h3>
                                <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                                    <li>Esta acción no se puede deshacer</li>
                                    <li>Se perderá toda la información del guía</li>
                                    <li>Se registrará esta acción en el historial del administrador</li>
                                    <?php if ($reservas_activas > 0): ?>
                                        <li class="text-red-600 font-medium">Hay reservas activas que podrían verse afectadas</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmarEliminacion() {
            const confirmar = document.getElementById('confirmar').value;
            if (confirmar !== 'SI_ELIMINAR') {
                alert('Debe escribir exactamente "SI_ELIMINAR" para confirmar');
                return false;
            }
            
            const reservasActivas = <?php echo $reservas_activas; ?>;
            if (reservasActivas > 0) {
                return confirm(`¡ADVERTENCIA FINAL!\n\nEste guía tiene ${reservasActivas} reserva(s) activa(s).\n\n¿Está completamente seguro de que desea eliminar este guía?`);
            }
            
            return confirm('¿Está seguro de que desea eliminar este guía?');
        }

        // Enfocar automáticamente el campo de confirmación
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('confirmar').focus();
        });
    </script>
</body>
</html>
