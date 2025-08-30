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
    
    // Verificar que la reserva existe y obtener información
    $reserva_sql = "SELECT r.*, u.nombre as cliente_nombre, u.email as cliente_email,
                           t.titulo as tour_titulo
                    FROM reservas r
                    LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                    LEFT JOIN tours t ON r.id_tour = t.id_tour
                    WHERE r.id_reserva = ?";
    $reserva_stmt = $connection->prepare($reserva_sql);
    $reserva_stmt->execute([$id_reserva]);
    $reserva = $reserva_stmt->fetch();
    
    if (!$reserva) {
        throw new Exception("Reserva no encontrada");
    }
    
    // Verificar que la reserva pueda ser eliminada
    if ($reserva['estado'] === 'Completada') {
        throw new Exception("No se puede eliminar una reserva completada");
    }
    
    // Verificar si hay pagos asociados
    $pagos_sql = "SELECT COUNT(*) as total_pagos FROM pagos WHERE id_reserva = ?";
    $pagos_stmt = $connection->prepare($pagos_sql);
    $pagos_stmt->execute([$id_reserva]);
    $pagos_result = $pagos_stmt->fetch();
    
    if ($pagos_result['total_pagos'] > 0) {
        throw new Exception("No se puede eliminar una reserva que tiene pagos registrados. Considere cancelarla en su lugar.");
    }
    
    // Registrar eliminación en log (antes de eliminar)
    $log_sql = "INSERT INTO logs_eliminaciones (tipo_registro, id_registro, datos_eliminados, motivo, id_admin, fecha_eliminacion) 
                VALUES ('reserva', ?, ?, ?, ?, NOW())";
    try {
        $datos_eliminados = json_encode([
            'id_reserva' => $reserva['id_reserva'],
            'cliente' => $reserva['cliente_nombre'],
            'email_cliente' => $reserva['cliente_email'],
            'tour' => $reserva['tour_titulo'],
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
    
    // Eliminar pasajeros asociados
    $delete_pasajeros_sql = "DELETE FROM pasajeros WHERE id_reserva = ?";
    $delete_pasajeros_stmt = $connection->prepare($delete_pasajeros_sql);
    $delete_pasajeros_stmt->execute([$id_reserva]);
    
    // Eliminar la reserva
    $delete_reserva_sql = "DELETE FROM reservas WHERE id_reserva = ?";
    $delete_reserva_stmt = $connection->prepare($delete_reserva_sql);
    $delete_reserva_stmt->execute([$id_reserva]);
    
    $connection->commit();
    
    // Redireccionar con mensaje de éxito
    $mensaje = urlencode("Reserva #$id_reserva eliminada exitosamente");
    header("Location: index.php?success=reserva_eliminada&mensaje=$mensaje");
    exit;
    
} catch (Exception $e) {
    $connection->rollback();
    $error = urlencode("Error al eliminar reserva: " . $e->getMessage());
    header("Location: index.php?error=eliminar_reserva&mensaje=$error");
    exit;
}
?>
