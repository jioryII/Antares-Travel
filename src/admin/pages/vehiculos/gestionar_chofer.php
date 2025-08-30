<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';

// Verificar sesión de administrador
verificarSesionAdmin();

header('Content-Type: application/json');

try {
    $connection = getConnection();
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_disponibles':
            // Obtener choferes sin vehículo asignado
            $sql = "SELECT c.id_chofer, c.nombre, c.apellido, c.telefono, c.licencia
                    FROM choferes c
                    LEFT JOIN vehiculos v ON c.id_chofer = v.id_chofer
                    WHERE v.id_chofer IS NULL
                    ORDER BY c.nombre, c.apellido";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $choferes = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'choferes' => $choferes
            ]);
            break;
            
        case 'asignar':
            // Asignar chofer a vehículo
            $vehiculo_id = intval($input['vehiculo_id'] ?? 0);
            $chofer_id = intval($input['chofer_id'] ?? 0);
            
            if (!$vehiculo_id || !$chofer_id) {
                throw new Exception('IDs inválidos');
            }
            
            // Verificar que el vehículo existe y no tiene chofer
            $check_vehiculo = "SELECT id_vehiculo FROM vehiculos WHERE id_vehiculo = ? AND id_chofer IS NULL";
            $vehiculo_stmt = $connection->prepare($check_vehiculo);
            $vehiculo_stmt->execute([$vehiculo_id]);
            
            if (!$vehiculo_stmt->fetch()) {
                throw new Exception('El vehículo no está disponible o ya tiene chofer asignado');
            }
            
            // Verificar que el chofer existe y no tiene vehículo asignado
            $check_chofer = "SELECT c.id_chofer FROM choferes c
                            LEFT JOIN vehiculos v ON c.id_chofer = v.id_chofer
                            WHERE c.id_chofer = ? AND v.id_chofer IS NULL";
            $chofer_stmt = $connection->prepare($check_chofer);
            $chofer_stmt->execute([$chofer_id]);
            
            if (!$chofer_stmt->fetch()) {
                throw new Exception('El chofer no está disponible o ya tiene vehículo asignado');
            }
            
            // Asignar chofer al vehículo
            $update_sql = "UPDATE vehiculos SET id_chofer = ? WHERE id_vehiculo = ?";
            $update_stmt = $connection->prepare($update_sql);
            $update_stmt->execute([$chofer_id, $vehiculo_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Chofer asignado exitosamente'
            ]);
            break;
            
        case 'desasignar':
            // Desasignar chofer del vehículo
            $vehiculo_id = intval($input['vehiculo_id'] ?? 0);
            
            if (!$vehiculo_id) {
                throw new Exception('ID de vehículo inválido');
            }
            
            // Verificar que el vehículo existe y tiene chofer asignado
            $check_sql = "SELECT id_vehiculo, id_chofer FROM vehiculos WHERE id_vehiculo = ? AND id_chofer IS NOT NULL";
            $check_stmt = $connection->prepare($check_sql);
            $check_stmt->execute([$vehiculo_id]);
            
            if (!$check_stmt->fetch()) {
                throw new Exception('El vehículo no tiene chofer asignado o no existe');
            }
            
            // Verificar que no tenga tours próximos
            $tours_check = "SELECT COUNT(*) as total FROM tours_diarios 
                           WHERE id_vehiculo = ? AND fecha >= CURDATE()";
            $tours_stmt = $connection->prepare($tours_check);
            $tours_stmt->execute([$vehiculo_id]);
            $tours_result = $tours_stmt->fetch();
            
            if ($tours_result['total'] > 0) {
                throw new Exception('No se puede desasignar: el vehículo tiene tours próximos programados');
            }
            
            // Desasignar chofer
            $update_sql = "UPDATE vehiculos SET id_chofer = NULL WHERE id_vehiculo = ?";
            $update_stmt = $connection->prepare($update_sql);
            $update_stmt->execute([$vehiculo_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Chofer desasignado exitosamente'
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
