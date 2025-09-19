<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';

// Verificar sesión de administrador
verificarSesionAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);

if (empty($action) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Acción o ID inválido']);
    exit;
}

try {
    $connection = getConnection();
    $connection->beginTransaction();
    
    // Verificar que la oferta existe
    $check_sql = "SELECT id_oferta, nombre, estado FROM ofertas WHERE id_oferta = ?";
    $check_stmt = $connection->prepare($check_sql);
    $check_stmt->execute([$id]);
    $oferta = $check_stmt->fetch();
    
    if (!$oferta) {
        throw new Exception("Oferta no encontrada");
    }
    
    switch ($action) {
        case 'activar':
            if ($oferta['estado'] === 'Activa') {
                throw new Exception("La oferta ya está activa");
            }
            
            // Verificar que las fechas sean válidas
            $fecha_check = "SELECT fecha_inicio, fecha_fin FROM ofertas WHERE id_oferta = ?";
            $fecha_stmt = $connection->prepare($fecha_check);
            $fecha_stmt->execute([$id]);
            $fechas = $fecha_stmt->fetch();
            
            $ahora = date('Y-m-d H:i:s');
            if ($fechas['fecha_fin'] < $ahora) {
                throw new Exception("No se puede activar una oferta que ya ha vencido");
            }
            
            $sql = "UPDATE ofertas SET estado = 'Activa', actualizado_en = CURRENT_TIMESTAMP WHERE id_oferta = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$id]);
            
            $message = "Oferta '{$oferta['nombre']}' activada correctamente";
            break;
            
        case 'pausar':
            if ($oferta['estado'] !== 'Activa') {
                throw new Exception("Solo se pueden pausar ofertas activas");
            }
            
            $sql = "UPDATE ofertas SET estado = 'Pausada', actualizado_en = CURRENT_TIMESTAMP WHERE id_oferta = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$id]);
            
            $message = "Oferta '{$oferta['nombre']}' pausada correctamente";
            break;
            
        case 'eliminar':
            // Verificar si la oferta tiene usos
            $uso_check = "SELECT COUNT(*) FROM historial_uso_ofertas WHERE id_oferta = ?";
            $uso_stmt = $connection->prepare($uso_check);
            $uso_stmt->execute([$id]);
            $tiene_usos = $uso_stmt->fetchColumn() > 0;
            
            if ($tiene_usos) {
                // Si tiene usos, solo cambiar estado a finalizada
                $sql = "UPDATE ofertas SET estado = 'Finalizada', actualizado_en = CURRENT_TIMESTAMP WHERE id_oferta = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$id]);
                $message = "Oferta finalizada (tenía usos registrados)";
            } else {
                // Si no tiene usos, eliminar completamente
                // Primero eliminar relaciones
                $delete_tours = "DELETE FROM ofertas_tours WHERE id_oferta = ?";
                $stmt_tours = $connection->prepare($delete_tours);
                $stmt_tours->execute([$id]);
                
                $delete_usuarios = "DELETE FROM ofertas_usuarios WHERE id_oferta = ?";
                $stmt_usuarios = $connection->prepare($delete_usuarios);
                $stmt_usuarios->execute([$id]);
                
                // Luego eliminar la oferta
                $sql = "DELETE FROM ofertas WHERE id_oferta = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$id]);
                
                $message = "Oferta '{$oferta['nombre']}' eliminada correctamente";
            }
            break;
            
        case 'finalizar':
            $sql = "UPDATE ofertas SET estado = 'Finalizada', actualizado_en = CURRENT_TIMESTAMP WHERE id_oferta = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$id]);
            
            $message = "Oferta '{$oferta['nombre']}' finalizada correctamente";
            break;
            
        default:
            throw new Exception("Acción no válida");
    }
    
    // Log de auditoría (si existe función de logging)
    session_start();
    if (isset($_SESSION['admin_id'])) {
        try {
            $log_sql = "INSERT INTO logs_admin (id_admin, accion, tabla, registro_id, descripcion, fecha) 
                       VALUES (?, ?, 'ofertas', ?, ?, NOW())";
            $log_stmt = $connection->prepare($log_sql);
            $log_stmt->execute([
                $_SESSION['admin_id'],
                strtoupper($action),
                $id,
                $message
            ]);
        } catch (Exception $e) {
            // Ignore logging errors
        }
    }
    
    $connection->commit();
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    $connection->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
