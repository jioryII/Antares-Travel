<?php
/**
 * API para validación de ofertas en el módulo de reservas
 * Endpoint: /src/admin/pages/reservas/validar_ofertas.php
 * 
 * Valida códigos promocionales y ofertas para aplicar descuentos en reservas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir POST y GET
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'])) {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once '../../../config/conexion.php';

try {
    $connection = getConnection();
    
    // Obtener datos de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Verificar parámetros requeridos
    $required_params = ['codigo_promocional', 'tour_id', 'precio_original', 'num_pasajeros'];
    foreach ($required_params as $param) {
        if (!isset($input[$param]) || $input[$param] === '') {
            throw new Exception("Parámetro requerido faltante: $param");
        }
    }
    
    $codigo_promocional = trim($input['codigo_promocional']);
    $tour_id = (int)$input['tour_id'];
    $precio_original = (float)$input['precio_original'];
    $num_pasajeros = (int)$input['num_pasajeros'];
    $user_id = isset($input['user_id']) ? (int)$input['user_id'] : null;
    
    // Validar que los valores sean válidos
    if ($precio_original <= 0) {
        throw new Exception("El precio original debe ser mayor a 0");
    }
    
    if ($num_pasajeros <= 0) {
        throw new Exception("El número de pasajeros debe ser mayor a 0");
    }
    
    // Buscar la oferta por código promocional
    $sql = "SELECT o.*, 
                   (SELECT COUNT(*) FROM historial_uso_ofertas WHERE id_oferta = o.id_oferta) as total_usos_reales
            FROM ofertas o 
            WHERE o.codigo_promocional = ? 
            AND o.estado = 'Activa' 
            AND o.fecha_inicio <= NOW() 
            AND o.fecha_fin >= NOW()";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$codigo_promocional]);
    $oferta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oferta) {
        echo json_encode([
            'valida' => false,
            'mensaje' => 'Código promocional no válido o expirado',
            'codigo' => 'CODIGO_INVALIDO'
        ]);
        exit;
    }
    
    // Verificar límite de usos (usar el campo de la DB y el conteo real como respaldo)
    $total_usos = max($oferta['usos_actuales'], $oferta['total_usos_reales']);
    if ($oferta['limite_usos'] > 0 && $total_usos >= $oferta['limite_usos']) {
        echo json_encode([
            'valida' => false,
            'mensaje' => 'Este código promocional ha alcanzado el límite de usos',
            'codigo' => 'LIMITE_ALCANZADO'
        ]);
        exit;
    }
    
    // Verificar elegibilidad del tour (si hay restricciones)
    $tour_eligible = true;
    $tour_check_sql = "SELECT COUNT(*) FROM ofertas_tours WHERE id_oferta = ? AND id_tour = ?";
    $tour_check_stmt = $connection->prepare($tour_check_sql);
    $tour_check_stmt->execute([$oferta['id_oferta'], $tour_id]);
    $tour_relations = $tour_check_stmt->fetchColumn();
    
    // Si hay relaciones específicas de tours, verificar que este tour esté incluido
    $all_tours_sql = "SELECT COUNT(*) FROM ofertas_tours WHERE id_oferta = ?";
    $all_tours_stmt = $connection->prepare($all_tours_sql);
    $all_tours_stmt->execute([$oferta['id_oferta']]);
    $total_tour_relations = $all_tours_stmt->fetchColumn();
    
    if ($total_tour_relations > 0 && $tour_relations === 0) {
        echo json_encode([
            'valida' => false,
            'mensaje' => 'Esta oferta no es válida para el tour seleccionado',
            'codigo' => 'TOUR_NO_ELEGIBLE'
        ]);
        exit;
    }
    
    // Verificar elegibilidad del usuario (si hay restricciones)
    if ($user_id) {
        $user_eligible = true;
        $user_check_sql = "SELECT COUNT(*) FROM ofertas_usuarios WHERE id_oferta = ? AND id_usuario = ?";
        $user_check_stmt = $connection->prepare($user_check_sql);
        $user_check_stmt->execute([$oferta['id_oferta'], $user_id]);
        $user_relations = $user_check_stmt->fetchColumn();
        
        $all_users_sql = "SELECT COUNT(*) FROM ofertas_usuarios WHERE id_oferta = ?";
        $all_users_stmt = $connection->prepare($all_users_sql);
        $all_users_stmt->execute([$oferta['id_oferta']]);
        $total_user_relations = $all_users_stmt->fetchColumn();
        
        if ($total_user_relations > 0 && $user_relations === 0) {
            echo json_encode([
                'valida' => false,
                'mensaje' => 'Esta oferta no está disponible para su perfil',
                'codigo' => 'USUARIO_NO_ELEGIBLE'
            ]);
            exit;
        }
    }
    
    // Calcular el descuento y precio final
    $subtotal = $precio_original * $num_pasajeros;
    $descuento_aplicado = 0;
    $precio_final = $subtotal;
    
    switch ($oferta['tipo_oferta']) {
        case 'Porcentaje':
            $descuento_aplicado = $subtotal * ($oferta['valor_descuento'] / 100);
            $precio_final = $subtotal - $descuento_aplicado;
            break;
            
        case 'Monto_Fijo':
            $descuento_aplicado = min($oferta['valor_descuento'], $subtotal);
            $precio_final = max(0, $subtotal - $descuento_aplicado);
            break;
            
        case 'Precio_Especial':
            $precio_final = $oferta['precio_especial'] * $num_pasajeros;
            $descuento_aplicado = $subtotal - $precio_final;
            break;
            
        case '2x1':
            // En 2x1, se paga por la mitad de personas (redondeando hacia arriba)
            $pasajeros_a_pagar = ceil($num_pasajeros / 2);
            $precio_final = $precio_original * $pasajeros_a_pagar;
            $descuento_aplicado = $subtotal - $precio_final;
            break;
            
        case 'Combo':
            // Para combos, aplicar el precio especial
            if ($oferta['precio_especial'] > 0) {
                $precio_final = $oferta['precio_especial'] * $num_pasajeros;
                $descuento_aplicado = $subtotal - $precio_final;
            }
            break;
            
        default:
            throw new Exception("Tipo de oferta no válido: " . $oferta['tipo_oferta']);
    }
    
    // Asegurar que el precio final no sea negativo
    $precio_final = max(0, $precio_final);
    $descuento_aplicado = $subtotal - $precio_final;
    
    // Preparar mensaje descriptivo
    $mensaje = "Código promocional válido: " . $oferta['nombre'];
    
    switch ($oferta['tipo_oferta']) {
        case 'Porcentaje':
            $mensaje .= " ({$oferta['valor_descuento']}% de descuento)";
            break;
        case 'Monto_Fijo':
            $mensaje .= " (S/ {$oferta['valor_descuento']} de descuento)";
            break;
        case 'Precio_Especial':
            $mensaje .= " (Precio especial: S/ {$oferta['precio_especial']} por persona)";
            break;
        case '2x1':
            $mensaje .= " (Promoción 2x1: paga {$pasajeros_a_pagar} de {$num_pasajeros} pasajeros)";
            break;
        case 'Combo':
            $mensaje .= " (Precio combo especial)";
            break;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'valida' => true,
        'mensaje' => $mensaje,
        'oferta_id' => $oferta['id_oferta'],
        'oferta_nombre' => $oferta['nombre'],
        'tipo_oferta' => $oferta['tipo_oferta'],
        'codigo_promocional' => $codigo_promocional,
        'precio_original' => $precio_original,
        'num_pasajeros' => $num_pasajeros,
        'subtotal' => $subtotal,
        'descuento_aplicado' => round($descuento_aplicado, 2),
        'precio_final' => round($precio_final, 2),
        'porcentaje_descuento' => $subtotal > 0 ? round(($descuento_aplicado / $subtotal) * 100, 1) : 0,
        'ahorro' => round($descuento_aplicado, 2),
        'limite_usos' => $oferta['limite_usos'],
        'usos_restantes' => $oferta['limite_usos'] > 0 ? max(0, $oferta['limite_usos'] - $total_usos) : null,
        'vigente_hasta' => $oferta['fecha_fin']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'valida' => false,
        'mensaje' => 'Error interno: ' . $e->getMessage(),
        'codigo' => 'ERROR_INTERNO'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'valida' => false,
        'mensaje' => 'Error de base de datos',
        'codigo' => 'ERROR_BD'
    ]);
}
?>
