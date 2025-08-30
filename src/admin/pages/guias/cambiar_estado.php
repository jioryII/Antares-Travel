<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id_guia = intval($input['id_guia'] ?? 0);
    $nuevo_estado = $input['nuevo_estado'] ?? '';
    
    if (!$id_guia) {
        throw new Exception('ID de guía no válido');
    }
    
    if (!in_array($nuevo_estado, ['Libre', 'Ocupado'])) {
        throw new Exception('Estado no válido');
    }
    
    $connection = getConnection();
    
    // Verificar que el guía existe
    $check_sql = "SELECT nombre, apellido, estado FROM guias WHERE id_guia = ?";
    $check_stmt = $connection->prepare($check_sql);
    $check_stmt->execute([$id_guia]);
    $guia = $check_stmt->fetch();
    
    if (!$guia) {
        throw new Exception('Guía no encontrado');
    }
    
    if ($guia['estado'] === $nuevo_estado) {
        echo json_encode(['success' => true, 'message' => 'El guía ya tiene este estado']);
        exit;
    }
    
    // Actualizar estado
    $update_sql = "UPDATE guias SET estado = ? WHERE id_guia = ?";
    $update_stmt = $connection->prepare($update_sql);
    $update_stmt->execute([$nuevo_estado, $id_guia]);
    
    // Registrar actividad
    // registrarActividad($admin['id_administrador'], 'editar', 'guias', $id_guia, 
    //                  "Cambió estado del guía {$guia['nombre']} {$guia['apellido']} de {$guia['estado']} a $nuevo_estado");
    
    echo json_encode([
        'success' => true, 
        'message' => "Estado cambiado a $nuevo_estado exitosamente",
        'nuevo_estado' => $nuevo_estado
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
