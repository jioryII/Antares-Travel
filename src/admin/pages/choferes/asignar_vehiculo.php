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
            // Obtener vehículos sin chofer asignado
            $sql = "SELECT id_vehiculo, marca, modelo, placa, capacidad, caracteristicas 
                    FROM vehiculos 
                    WHERE id_chofer IS NULL 
                    ORDER BY marca, modelo";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $vehiculos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'vehiculos' => $vehiculos
            ]);
            break;
            
        case 'asignar':
            // Asignar vehículo existente a chofer
            $vehiculo_id = intval($input['vehiculo_id'] ?? 0);
            $chofer_id = intval($input['chofer_id'] ?? 0);
            
            if (!$vehiculo_id || !$chofer_id) {
                throw new Exception('IDs inválidos');
            }
            
            // Verificar que el vehículo existe y no tiene chofer
            $check_sql = "SELECT id_vehiculo FROM vehiculos WHERE id_vehiculo = ? AND id_chofer IS NULL";
            $check_stmt = $connection->prepare($check_sql);
            $check_stmt->execute([$vehiculo_id]);
            
            if (!$check_stmt->fetch()) {
                throw new Exception('El vehículo no está disponible');
            }
            
            // Verificar que el chofer existe
            $chofer_check = "SELECT id_chofer FROM choferes WHERE id_chofer = ?";
            $chofer_stmt = $connection->prepare($chofer_check);
            $chofer_stmt->execute([$chofer_id]);
            
            if (!$chofer_stmt->fetch()) {
                throw new Exception('Chofer no encontrado');
            }
            
            // Asignar vehículo al chofer
            $update_sql = "UPDATE vehiculos SET id_chofer = ? WHERE id_vehiculo = ?";
            $update_stmt = $connection->prepare($update_sql);
            $update_stmt->execute([$chofer_id, $vehiculo_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Vehículo asignado exitosamente'
            ]);
            break;
            
        case 'desasignar':
            // Desasignar vehículo de chofer
            $vehiculo_id = intval($input['vehiculo_id'] ?? 0);
            
            if (!$vehiculo_id) {
                throw new Exception('ID de vehículo inválido');
            }
            
            // Verificar que el vehículo existe y tiene chofer asignado
            $check_sql = "SELECT id_vehiculo, id_chofer FROM vehiculos WHERE id_vehiculo = ? AND id_chofer IS NOT NULL";
            $check_stmt = $connection->prepare($check_sql);
            $check_stmt->execute([$vehiculo_id]);
            
            if (!$check_stmt->fetch()) {
                throw new Exception('El vehículo no está asignado o no existe');
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
            
            // Desasignar vehículo
            $update_sql = "UPDATE vehiculos SET id_chofer = NULL WHERE id_vehiculo = ?";
            $update_stmt = $connection->prepare($update_sql);
            $update_stmt->execute([$vehiculo_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Vehículo desasignado exitosamente'
            ]);
            break;
            
        case 'crear_y_asignar':
            // Crear nuevo vehículo y asignarlo al chofer
            $chofer_id = intval($input['chofer_id'] ?? 0);
            $marca = trim($input['marca'] ?? '');
            $modelo = trim($input['modelo'] ?? '');
            $placa = trim(strtoupper($input['placa'] ?? ''));
            $capacidad = intval($input['capacidad'] ?? 0);
            $caracteristicas = trim($input['caracteristicas'] ?? '');
            
            // Validaciones
            if (!$chofer_id) {
                throw new Exception('ID de chofer inválido');
            }
            
            if (empty($marca) || empty($modelo) || empty($placa)) {
                throw new Exception('Marca, modelo y placa son obligatorios');
            }
            
            if ($capacidad < 1 || $capacidad > 50) {
                throw new Exception('La capacidad debe estar entre 1 y 50 personas');
            }
            
            // Verificar que el chofer existe
            $chofer_check = "SELECT id_chofer FROM choferes WHERE id_chofer = ?";
            $chofer_stmt = $connection->prepare($chofer_check);
            $chofer_stmt->execute([$chofer_id]);
            
            if (!$chofer_stmt->fetch()) {
                throw new Exception('Chofer no encontrado');
            }
            
            // Verificar que la placa no existe
            $placa_check = "SELECT id_vehiculo FROM vehiculos WHERE placa = ?";
            $placa_stmt = $connection->prepare($placa_check);
            $placa_stmt->execute([$placa]);
            
            if ($placa_stmt->fetch()) {
                throw new Exception('Ya existe un vehículo con esa placa');
            }
            
            // Insertar nuevo vehículo
            $insert_sql = "INSERT INTO vehiculos (marca, modelo, placa, capacidad, caracteristicas, id_chofer) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $connection->prepare($insert_sql);
            $success = $insert_stmt->execute([
                $marca, 
                $modelo, 
                $placa, 
                $capacidad, 
                $caracteristicas ?: null, 
                $chofer_id
            ]);
            
            if (!$success) {
                throw new Exception('Error al crear el vehículo');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Vehículo creado y asignado exitosamente',
                'vehiculo_id' => $connection->lastInsertId()
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
