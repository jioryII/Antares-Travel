<?php
/**
 * Procesador de formularios de tours
 * Maneja creación, edición y eliminación de tours
 */

// Configurar headers para AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar autenticación
require_once __DIR__ . '/../../auth/middleware.php';
verificarSesionAdmin();

require_once __DIR__ . '/../../functions/tours_functions.php';

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener acción
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            $resultado = crearTourCompleto($_POST);
            break;
            
        case 'editar':
            if (empty($_POST['id_tour'])) {
                throw new Exception('ID de tour requerido para editar');
            }
            $resultado = editarTour($_POST['id_tour'], $_POST);
            break;
            
        case 'eliminar':
            if (empty($_POST['id_tour'])) {
                throw new Exception('ID de tour requerido para eliminar');
            }
            $resultado = eliminarTour($_POST['id_tour']);
            break;
            
        case 'cambiar_estado':
            if (empty($_POST['id_tour'])) {
                throw new Exception('ID de tour requerido');
            }
            $resultado = cambiarEstadoTour($_POST['id_tour'], $_POST['estado'] ?? 1);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    // Respuesta exitosa
    echo json_encode($resultado);
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    // Log del error
    error_log("Error en procesar_tour.php: " . $e->getMessage());
}
?>
