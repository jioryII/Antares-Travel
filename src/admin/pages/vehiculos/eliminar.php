<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Obtener ID del vehículo
$id_vehiculo = intval($_GET['id'] ?? 0);

if (!$id_vehiculo) {
    header('Location: index.php?error=ID de vehículo inválido');
    exit;
}

try {
    $connection = getConnection();
    $connection->beginTransaction();
    
    // Verificar que el vehículo existe
    $vehiculo_sql = "SELECT v.*, 
                            CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellido, '')) as chofer_nombre
                     FROM vehiculos v
                     LEFT JOIN choferes c ON v.id_chofer = c.id_chofer
                     WHERE v.id_vehiculo = ?";
    $vehiculo_stmt = $connection->prepare($vehiculo_sql);
    $vehiculo_stmt->execute([$id_vehiculo]);
    $vehiculo = $vehiculo_stmt->fetch();
    
    if (!$vehiculo) {
        throw new Exception('Vehículo no encontrado');
    }
    
    // Verificar que no tenga tours próximos
    $tours_check = "SELECT COUNT(*) as total FROM tours_diarios 
                   WHERE id_vehiculo = ? AND fecha >= CURDATE()";
    $tours_stmt = $connection->prepare($tours_check);
    $tours_stmt->execute([$id_vehiculo]);
    $tours_result = $tours_stmt->fetch();
    
    if ($tours_result['total'] > 0) {
        throw new Exception('No se puede eliminar: el vehículo tiene tours próximos programados. Cancela primero los tours o espera a que se completen.');
    }
    
    // Verificar si el chofer asignado tiene tours próximos con este vehículo
    if ($vehiculo['id_chofer']) {
        $chofer_tours_check = "SELECT COUNT(*) as total FROM tours_diarios td
                               INNER JOIN choferes c ON td.id_chofer = c.id_chofer
                               WHERE c.id_chofer = ? AND td.fecha >= CURDATE() AND td.id_vehiculo = ?";
        $chofer_tours_stmt = $connection->prepare($chofer_tours_check);
        $chofer_tours_stmt->execute([$vehiculo['id_chofer'], $id_vehiculo]);
        $chofer_tours_result = $chofer_tours_stmt->fetch();
        
        if ($chofer_tours_result['total'] > 0) {
            throw new Exception('No se puede eliminar: el chofer asignado a este vehículo tiene tours próximos programados.');
        }
    }
    
    // Eliminar registros relacionados en orden correcto
    
    // 1. Eliminar disponibilidad del vehículo
    $delete_disponibilidad = "DELETE FROM disponibilidad_vehiculos WHERE id_vehiculo = ?";
    $disponibilidad_stmt = $connection->prepare($delete_disponibilidad);
    $disponibilidad_stmt->execute([$id_vehiculo]);
    
    // 2. Actualizar tours históricos para mantener integridad (opcional - comentar si se desea eliminar completamente)
    $update_tours = "UPDATE tours_diarios SET id_vehiculo = NULL WHERE id_vehiculo = ? AND fecha < CURDATE()";
    $tours_update_stmt = $connection->prepare($update_tours);
    $tours_update_stmt->execute([$id_vehiculo]);
    
    // 3. Eliminar el vehículo (esto también desasignará el chofer automáticamente por CASCADE)
    $delete_vehiculo = "DELETE FROM vehiculos WHERE id_vehiculo = ?";
    $vehiculo_delete_stmt = $connection->prepare($delete_vehiculo);
    $vehiculo_delete_stmt->execute([$id_vehiculo]);
    
    if ($vehiculo_delete_stmt->rowCount() === 0) {
        throw new Exception('Error al eliminar el vehículo');
    }
    
    $connection->commit();
    
    // Log detallado de éxito
    $log_detalles = "VEHÍCULOS - Eliminación exitosa: ID $id_vehiculo - {$vehiculo['marca']} {$vehiculo['modelo']} (Placa: {$vehiculo['placa']})";
    if ($vehiculo['chofer_nombre']) {
        $log_detalles .= " - Chofer asignado: {$vehiculo['chofer_nombre']}";
    }
    $log_detalles .= " - Admin: {$admin['nombre_usuario']} - " . date('Y-m-d H:i:s');
    error_log($log_detalles);
    
    // Redirigir con mensaje de éxito
    $mensaje = "Vehículo '{$vehiculo['marca']} {$vehiculo['modelo']}' (Placa: {$vehiculo['placa']}) eliminado exitosamente";
    header('Location: index.php?success=' . urlencode($mensaje));
    exit;
    
} catch (Exception $e) {
    if ($connection->inTransaction()) {
        $connection->rollback();
    }
    
    // Log detallado del error
    error_log("VEHÍCULOS - Error en eliminación: " . $e->getMessage() . " - ID: $id_vehiculo - Admin: " . ($admin['nombre_usuario'] ?? 'desconocido'));
    
    // Redirigir con mensaje de error
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>
