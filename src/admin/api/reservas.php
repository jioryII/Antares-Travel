<?php
// API para gestión de reservas
session_start();
require_once '../middleware.php';
require_once '../functions/reservas_functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$admin = getCurrentAdmin();

if (!$admin) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Obtener reserva específica
                $reserva = getReservaById($_GET['id']);
                if ($reserva) {
                    echo json_encode(['success' => true, 'data' => $reserva]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Reserva no encontrada']);
                }
            } elseif (isset($_GET['proximas'])) {
                // Obtener reservas próximas
                $reservas = getReservasProximas();
                echo json_encode(['success' => true, 'data' => $reservas]);
            } elseif (isset($_GET['stats'])) {
                // Obtener estadísticas
                $stats = getReservasStats();
                echo json_encode(['success' => true, 'data' => $stats]);
            } elseif (isset($_GET['disponibilidad'])) {
                // Validar disponibilidad
                $disponibilidad = validarDisponibilidad(
                    $_GET['id_tour'],
                    $_GET['fecha'],
                    intval($_GET['pasajeros'] ?? 1)
                );
                echo json_encode(['success' => true, 'data' => $disponibilidad]);
            } else {
                // Listar reservas con filtros
                $page = intval($_GET['page'] ?? 1);
                $limit = intval($_GET['limit'] ?? 10);
                $search = $_GET['search'] ?? '';
                $estado_filter = $_GET['estado'] ?? '';
                $fecha_filter = $_GET['fecha'] ?? '';
                
                $result = getReservas($page, $limit, $search, $estado_filter, $fecha_filter);
                echo json_encode(['success' => true, 'data' => $result]);
            }
            break;
            
        case 'POST':
            // Crear nueva reserva
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                break;
            }
            
            // Validar campos obligatorios
            $required_fields = ['id_usuario', 'id_tour', 'fecha_tour', 'monto_total', 'estado'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Campo $field es obligatorio"]);
                    exit;
                }
            }
            
            // Validar disponibilidad antes de crear
            $pasajeros_count = count($data['pasajeros'] ?? []);
            $disponibilidad = validarDisponibilidad($data['id_tour'], $data['fecha_tour'], $pasajeros_count);
            
            if (!$disponibilidad['disponible']) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'No hay disponibilidad suficiente',
                    'disponibilidad' => $disponibilidad
                ]);
                break;
            }
            
            $reserva_id = createReserva($data, $admin['id_admin']);
            
            if ($reserva_id) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Reserva creada exitosamente',
                    'id' => $reserva_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear la reserva']);
            }
            break;
            
        case 'PUT':
            // Actualizar estado de reserva
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de reserva requerido']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['estado'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Estado es obligatorio']);
                break;
            }
            
            $valid_states = ['Pendiente', 'Confirmada', 'Cancelada', 'Finalizada'];
            if (!in_array($data['estado'], $valid_states)) {
                http_response_code(400);
                echo json_encode(['error' => 'Estado inválido']);
                break;
            }
            
            $result = updateReservaEstado($_GET['id'], $data['estado'], $admin['id_admin']);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Estado actualizado exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar el estado']);
            }
            break;
            
        case 'DELETE':
            // Eliminar reserva
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de reserva requerido']);
                break;
            }
            
            // Verificar permisos (solo admin y super_admin pueden eliminar)
            if (!hasPermission('admin')) {
                http_response_code(403);
                echo json_encode(['error' => 'Sin permisos para eliminar reservas']);
                break;
            }
            
            $result = deleteReserva($_GET['id'], $admin['id_admin']);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Reserva eliminada exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar la reserva']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en API reservas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
