<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del chofer
$id_chofer = intval($_GET['id'] ?? 0);

if (!$id_chofer) {
    header('Location: index.php?error=ID de chofer no válido');
    exit;
}

try {
    $connection = getConnection();
    
    // Verificar que el chofer existe
    $chofer_sql = "SELECT nombre, apellido FROM choferes WHERE id_chofer = ?";
    $chofer_stmt = $connection->prepare($chofer_sql);
    $chofer_stmt->execute([$id_chofer]);
    $chofer = $chofer_stmt->fetch();
    
    if (!$chofer) {
        header('Location: index.php?error=Chofer no encontrado');
        exit;
    }
    
    // Verificar dependencias
    $vehiculos_sql = "SELECT COUNT(*) FROM vehiculos WHERE id_chofer = ?";
    $vehiculos_stmt = $connection->prepare($vehiculos_sql);
    $vehiculos_stmt->execute([$id_chofer]);
    $tiene_vehiculos = $vehiculos_stmt->fetchColumn() > 0;
    
    if ($tiene_vehiculos) {
        // Si tiene vehículos, desasignarlos en lugar de impedir la eliminación
        $desasignar_sql = "UPDATE vehiculos SET id_chofer = NULL WHERE id_chofer = ?";
        $desasignar_stmt = $connection->prepare($desasignar_sql);
        $desasignar_stmt->execute([$id_chofer]);
    }
    
    // Eliminar el chofer
    $delete_sql = "DELETE FROM choferes WHERE id_chofer = ?";
    $delete_stmt = $connection->prepare($delete_sql);
    $delete_stmt->execute([$id_chofer]);
    
    $mensaje_exito = "Chofer eliminado exitosamente";
    if ($tiene_vehiculos) {
        $mensaje_exito .= ". Los vehículos asignados han sido desvinculados";
    }
    
    header('Location: index.php?success=' . urlencode($mensaje_exito));
    exit;
    
} catch (Exception $e) {
    $error_msg = "Error al eliminar chofer: " . $e->getMessage();
    header('Location: index.php?error=' . urlencode($error_msg));
    exit;
}
?>
