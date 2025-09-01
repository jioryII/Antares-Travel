<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Configurar respuesta JSON
header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

$id_reserva = isset($data['id_reserva']) ? intval($data['id_reserva']) : 0;
$nuevo_estado = isset($data['nuevo_estado']) ? trim($data['nuevo_estado']) : '';
$comentario = isset($data['observaciones']) ? trim($data['observaciones']) : '';

// Validar datos
if (!$id_reserva || empty($nuevo_estado)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Estados válidos según la base de datos
$estados_validos = ['Pendiente', 'Confirmada', 'Cancelada', 'Finalizada'];
if (!in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

try {
    $connection = getConnection();
    $connection->beginTransaction();
    
    // Verificar que la reserva existe y obtener estado actual
    $reserva_sql = "SELECT estado, id_usuario, monto_total FROM reservas WHERE id_reserva = ?";
    $reserva_stmt = $connection->prepare($reserva_sql);
    $reserva_stmt->execute([$id_reserva]);
    $reserva = $reserva_stmt->fetch();
    
    if (!$reserva) {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }
    
    $estado_anterior = $reserva['estado'];
    
    // No actualizar si el estado es el mismo
    if ($estado_anterior === $nuevo_estado) {
        echo json_encode(['success' => false, 'message' => 'El estado ya es el mismo']);
        exit;
    }
    
    // Actualizar estado de la reserva
    $update_sql = "UPDATE reservas SET estado = ? WHERE id_reserva = ?";
    $update_stmt = $connection->prepare($update_sql);
    $update_stmt->execute([$nuevo_estado, $id_reserva]);
    
    // Registrar el cambio de estado en un log (si existe la tabla)
    $log_sql = "INSERT INTO logs_reservas (id_reserva, estado_anterior, estado_nuevo, comentario, id_admin, fecha_cambio) 
                VALUES (?, ?, ?, ?, ?, NOW())";
    try {
        $log_stmt = $connection->prepare($log_sql);
        $log_stmt->execute([$id_reserva, $estado_anterior, $nuevo_estado, $comentario, $admin['id_admin']]);
    } catch (PDOException $e) {
        // Si la tabla de logs no existe, continuamos sin error
        // En una implementación real, podrías crear la tabla automáticamente
    }
    
    // Lógica adicional según el estado
    switch ($nuevo_estado) {
        case 'Confirmada':
            // Aquí podrías enviar email de confirmación al cliente
            // También podrías actualizar inventario o disponibilidad
            break;
            
        case 'Cancelada':
            // Aquí podrías liberar espacios reservados
            // También podrías procesar reembolsos automáticamente
            break;
            
        case 'Completada':
            // Aquí podrías enviar encuesta de satisfacción
            // También podrías actualizar estadísticas
            break;
    }
    
    $connection->commit();
    
    // Respuesta JSON de éxito
    echo json_encode([
        'success' => true, 
        'message' => "Estado cambiado de '$estado_anterior' a '$nuevo_estado' exitosamente"
    ]);
    exit;
    
} catch (Exception $e) {
    $connection->rollback();
    echo json_encode([
        'success' => false, 
        'message' => "Error al cambiar estado: " . $e->getMessage()
    ]);
    exit;
}
?>
