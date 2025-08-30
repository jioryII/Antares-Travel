<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar autenticación
session_start();
require_once __DIR__ . '/../auth/middleware.php';

try {
    verificarSesionAdmin();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../functions/tours_functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'obtener') {
                $id_tour = intval($_GET['id'] ?? 0);
                if ($id_tour <= 0) {
                    throw new Exception('ID de tour inválido');
                }
                
                $resultado = obtenerTourPorId($id_tour);
                echo json_encode($resultado);
                
            } elseif ($action === 'listar') {
                $pagina = max(1, intval($_GET['pagina'] ?? 1));
                $busqueda = $_GET['busqueda'] ?? '';
                $por_pagina = max(1, min(50, intval($_GET['por_pagina'] ?? 10)));
                
                // Filtros adicionales
                $filtros = [
                    'region' => $_GET['region'] ?? '',
                    'guia' => $_GET['guia'] ?? '',
                    'precio_min' => $_GET['precio_min'] ?? '',
                    'precio_max' => $_GET['precio_max'] ?? ''
                ];
                
                $resultado = obtenerTours($pagina, $por_pagina, $busqueda, $filtros);
                echo json_encode($resultado);
                
            } elseif ($action === 'estadisticas') {
                $resultado = obtenerEstadisticasTours();
                echo json_encode($resultado);
                
            } elseif ($action === 'buscar') {
                $filtros = [
                    'busqueda' => $_GET['q'] ?? '',
                    'region' => $_GET['region'] ?? '',
                    'precio_min' => $_GET['precio_min'] ?? '',
                    'precio_max' => $_GET['precio_max'] ?? '',
                    'con_guia' => $_GET['con_guia'] ?? ''
                ];
                
                $resultado = buscarTours($filtros);
                echo json_encode($resultado);
                
            } else {
                throw new Exception('Acción no válida');
            }
            break;

        case 'POST':
            if ($action === 'crear') {
                // Procesar datos del formulario
                $datos = [];
                
                // Si es multipart/form-data (con imagen)
                if (isset($_POST['titulo'])) {
                    $datos = $_POST;
                    
                    // Procesar imagen si se envió
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        $resultado_imagen = procesarImagenTour($_FILES['imagen']);
                        if ($resultado_imagen['success']) {
                            $datos['imagen_principal'] = $resultado_imagen['ruta'];
                        } else {
                            throw new Exception('Error procesando imagen: ' . $resultado_imagen['message']);
                        }
                    }
                } else {
                    // Datos JSON
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input) {
                        throw new Exception('Datos inválidos');
                    }
                    $datos = $input;
                }
                
                $resultado = crearTour($datos);
                echo json_encode($resultado);
                
            } elseif ($action === 'upload_imagen') {
                if (!isset($_FILES['imagen'])) {
                    throw new Exception('No se recibió archivo de imagen');
                }
                
                $tour_id = $_POST['tour_id'] ?? null;
                $resultado = procesarImagenTour($_FILES['imagen'], $tour_id);
                echo json_encode($resultado);
                
            } else {
                throw new Exception('Acción no válida');
            }
            break;

        case 'PUT':
            if ($action === 'actualizar') {
                $id_tour = intval($_GET['id'] ?? 0);
                if ($id_tour <= 0) {
                    throw new Exception('ID de tour inválido');
                }
                
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    throw new Exception('Datos inválidos');
                }
                
                $resultado = actualizarTour($id_tour, $input);
                echo json_encode($resultado);
                
            } else {
                throw new Exception('Acción no válida');
            }
            break;

        case 'DELETE':
            if ($action === 'eliminar') {
                $id_tour = intval($_GET['id'] ?? 0);
                if ($id_tour <= 0) {
                    throw new Exception('ID de tour inválido');
                }
                
                $resultado = eliminarTour($id_tour);
                echo json_encode($resultado);
                
            } else {
                throw new Exception('Acción no válida');
            }
            break;

        case 'OPTIONS':
            // Preflight request para CORS
            http_response_code(200);
            exit;

        default:
            throw new Exception('Método no permitido');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    
    error_log("Error en API tours: " . $e->getMessage() . " - Línea: " . $e->getLine());
}
                if ($id_tour <= 0) {
                    throw new Exception('ID de tour inválido');
                }
                
                // Validar datos
                $errores = validarDatosTour($input, true);
                if (!empty($errores)) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Datos inválidos',
                        'errors' => $errores
                    ]);
                    break;
                }
                
                $resultado = actualizarTour($id_tour, $input);
                
                // Registrar log de auditoría
                if ($resultado['success']) {
                    $admin = obtenerAdminActual();
                    registrarLogAuditoria(
                        $admin['id'], 
                        'UPDATE', 
                        'tours', 
                        $id_tour, 
                        'Actualizó tour: ' . $input['titulo']
                    );
                }
                
                echo json_encode($resultado);
                
            } else {
                throw new Exception('Acción no válida');
            }
            break;

        case 'DELETE':
            if ($action === 'eliminar') {
                $id_tour = intval($_GET['id'] ?? 0);
                if ($id_tour <= 0) {
                    throw new Exception('ID de tour inválido');
                }
                
                // Obtener datos del tour antes de eliminar para el log
                $tour_data = obtenerTourPorId($id_tour);
                $titulo_tour = $tour_data['success'] ? $tour_data['data']['titulo'] : 'Tour desconocido';
                
                $resultado = eliminarTour($id_tour);
                
                // Registrar log de auditoría
                if ($resultado['success']) {
                    $admin = obtenerAdminActual();
                    registrarLogAuditoria(
                        $admin['id'], 
                        'DELETE', 
                        'tours', 
                        $id_tour, 
                        'Eliminó tour: ' . $titulo_tour
                    );
                }
                
                echo json_encode($resultado);
                
            } else {
                throw new Exception('Acción no válida');
            }
            break;

        default:
            throw new Exception('Método no permitido');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
