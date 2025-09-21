<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Obtener ID de la reserva
$id_reserva = isset($_POST['id_reserva']) ? intval($_POST['id_reserva']) : 0;
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

// Validar datos
if (!$id_reserva) {
    header("Location: index.php?error=datos_invalidos");
    exit;
}

try {
    $connection = getConnection();
    $connection->beginTransaction();
    
    // Verificar que la reserva existe
    $reserva_sql = "SELECT * FROM reservas WHERE id_reserva = ?";
    $reserva_stmt = $connection->prepare($reserva_sql);
    $reserva_stmt->execute([$id_reserva]);
    $reserva = $reserva_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        $connection->rollBack();
        header("Location: index.php?error=reserva_no_encontrada");
        exit;
    }
    
    // Registrar eliminación en log (antes de eliminar)
    $log_sql = "INSERT INTO logs_eliminaciones (tipo_registro, id_registro, datos_eliminados, motivo, id_admin, fecha_eliminacion) 
                VALUES ('reserva', ?, ?, ?, ?, NOW())";
    try {
        $datos_eliminados = json_encode([
            'id_reserva' => $reserva['id_reserva'],
            'fecha_tour' => $reserva['fecha_tour'],
            'monto_total' => $reserva['monto_total'],
            'estado' => $reserva['estado'],
            'fecha_creacion' => $reserva['fecha_creacion']
        ]);
        
        $log_stmt = $connection->prepare($log_sql);
        $log_stmt->execute([$id_reserva, $datos_eliminados, $motivo, $admin['id_admin']]);
    } catch (PDOException $e) {
        // Si la tabla de logs no existe, continuamos sin error
    }
    
    // Eliminar pasajeros asociados (para mantener integridad referencial)
    $delete_pasajeros_sql = "DELETE FROM pasajeros WHERE id_reserva = ?";
    $delete_pasajeros_stmt = $connection->prepare($delete_pasajeros_sql);
    $delete_pasajeros_stmt->execute([$id_reserva]);
    
    // Eliminar la reserva
    $delete_reserva_sql = "DELETE FROM reservas WHERE id_reserva = ?";
    $delete_reserva_stmt = $connection->prepare($delete_reserva_sql);
    $delete_reserva_stmt->execute([$id_reserva]);
    
    $connection->commit();
    
    // Redireccionar con mensaje de éxito
    header("Location: index.php?success=reserva_eliminada");
    exit;
    
} catch (Exception $e) {
    $connection->rollback();
    header("Location: index.php?error=eliminar_reserva&mensaje=" . urlencode($e->getMessage()));
    exit;
}
?>
