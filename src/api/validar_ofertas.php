<?php
header('Content-Type: application/json');
require_once '../config/conexion.php';
require_once '../auth/auth_check.php';

// Función para validar y calcular ofertas
function validarOferta($codigo_promo = null, $id_tour = null, $id_usuario = null, $monto_base = 0) {
    global $conn;
    
    try {
        $where_conditions = ["o.estado = 'Activa'", "NOW() BETWEEN o.fecha_inicio AND o.fecha_fin"];
        $params = [];
        
        // Si hay código promocional
        if ($codigo_promo) {
            $where_conditions[] = "o.codigo_promocional = ?";
            $params[] = $codigo_promo;
        }
        
        // Construir consulta base
        $sql = "SELECT o.*, 
                       CASE 
                           WHEN o.limite_usos IS NOT NULL AND o.usos_actuales >= o.limite_usos THEN 0
                           ELSE 1 
                       END as disponible
                FROM ofertas o 
                WHERE " . implode(' AND ', $where_conditions) . "
                ORDER BY 
                    CASE o.tipo_oferta 
                        WHEN 'Precio_Especial' THEN 1
                        WHEN 'Porcentaje' THEN 2
                        WHEN 'Monto_Fijo' THEN 3
                        WHEN '2x1' THEN 4
                        WHEN 'Combo' THEN 5
                    END";
        
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $ofertas = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $ofertas_aplicables = [];
        
        foreach ($ofertas as $oferta) {
            if (!$oferta['disponible']) continue;
            
            // Verificar monto mínimo
            if ($oferta['monto_minimo'] && $monto_base < $oferta['monto_minimo']) {
                continue;
            }
            
            // Verificar aplicabilidad
            $es_aplicable = false;
            
            switch ($oferta['aplicable_a']) {
                case 'Todos':
                    $es_aplicable = true;
                    break;
                    
                case 'Tours_Especificos':
                    if ($id_tour) {
                        $check_tour = "SELECT 1 FROM ofertas_tours WHERE id_oferta = ? AND id_tour = ?";
                        $check_stmt = $conn->prepare($check_tour);
                        $check_stmt->bind_param("ii", $oferta['id_oferta'], $id_tour);
                        $check_stmt->execute();
                        $es_aplicable = $check_stmt->get_result()->num_rows > 0;
                        $check_stmt->close();
                    }
                    break;
                    
                case 'Usuarios_Especificos':
                    if ($id_usuario) {
                        $check_user = "SELECT 1 FROM ofertas_usuarios WHERE id_oferta = ? AND id_usuario = ?";
                        $check_stmt = $conn->prepare($check_user);
                        $check_stmt->bind_param("ii", $oferta['id_oferta'], $id_usuario);
                        $check_stmt->execute();
                        $es_aplicable = $check_stmt->get_result()->num_rows > 0;
                        $check_stmt->close();
                    }
                    break;
                    
                case 'Nuevos_Usuarios':
                    if ($id_usuario) {
                        $check_new = "SELECT COUNT(*) as reservas FROM reservas WHERE id_usuario = ?";
                        $check_stmt = $conn->prepare($check_new);
                        $check_stmt->bind_param("i", $id_usuario);
                        $check_stmt->execute();
                        $result = $check_stmt->get_result()->fetch_assoc();
                        $es_aplicable = $result['reservas'] == 0;
                        $check_stmt->close();
                    }
                    break;
            }
            
            if (!$es_aplicable) continue;
            
            // Verificar límite por usuario
            if ($id_usuario && $oferta['limite_por_usuario']) {
                $usage_check = "SELECT COUNT(*) as usos FROM historial_uso_ofertas WHERE id_oferta = ? AND id_usuario = ?";
                $usage_stmt = $conn->prepare($usage_check);
                $usage_stmt->bind_param("ii", $oferta['id_oferta'], $id_usuario);
                $usage_stmt->execute();
                $usage_result = $usage_stmt->get_result()->fetch_assoc();
                if ($usage_result['usos'] >= $oferta['limite_por_usuario']) {
                    $usage_stmt->close();
                    continue;
                }
                $usage_stmt->close();
            }
            
            // Calcular descuento
            $descuento = calcularDescuento($oferta, $monto_base);
            
            if ($descuento > 0) {
                $ofertas_aplicables[] = [
                    'id_oferta' => $oferta['id_oferta'],
                    'nombre' => $oferta['nombre'],
                    'tipo_oferta' => $oferta['tipo_oferta'],
                    'descuento' => $descuento,
                    'precio_final' => max(0, $monto_base - $descuento),
                    'mensaje' => $oferta['mensaje_promocional'] ?: "Descuento aplicado: S/ " . number_format($descuento, 2),
                    'codigo' => $oferta['codigo_promocional']
                ];
            }
        }
        
        return $ofertas_aplicables;
        
    } catch (Exception $e) {
        error_log("Error en validarOferta: " . $e->getMessage());
        return [];
    }
}

function calcularDescuento($oferta, $monto_base) {
    switch ($oferta['tipo_oferta']) {
        case 'Porcentaje':
            return ($monto_base * $oferta['valor_descuento']) / 100;
            
        case 'Monto_Fijo':
            return min($oferta['valor_descuento'], $monto_base);
            
        case 'Precio_Especial':
            return max(0, $monto_base - $oferta['precio_especial']);
            
        case '2x1':
            return $monto_base * 0.5; // 50% de descuento
            
        case 'Combo':
            // Lógica personalizable para combos
            return ($monto_base * ($oferta['valor_descuento'] ?: 15)) / 100;
            
        default:
            return 0;
    }
}

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $codigo_promo = $input['codigo'] ?? null;
    $id_tour = $input['id_tour'] ?? null;
    $id_usuario = $_SESSION['user_id'] ?? null;
    $monto_base = floatval($input['monto'] ?? 0);
    
    if ($monto_base <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Monto base inválido'
        ]);
        exit;
    }
    
    $ofertas = validarOferta($codigo_promo, $id_tour, $id_usuario, $monto_base);
    
    if (empty($ofertas)) {
        echo json_encode([
            'success' => false,
            'message' => $codigo_promo ? 'Código promocional no válido o expirado' : 'No hay ofertas aplicables'
        ]);
    } else {
        // Retornar la mejor oferta (mayor descuento)
        usort($ofertas, function($a, $b) {
            return $b['descuento'] <=> $a['descuento'];
        });
        
        echo json_encode([
            'success' => true,
            'oferta' => $ofertas[0],
            'todas_ofertas' => $ofertas
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener ofertas públicas activas
    $ofertas_publicas = "SELECT id_oferta, nombre, descripcion, tipo_oferta, mensaje_promocional, imagen_banner
                         FROM ofertas 
                         WHERE estado = 'Activa' 
                         AND visible_publica = 1 
                         AND NOW() BETWEEN fecha_inicio AND fecha_fin 
                         ORDER BY destacada DESC, creado_en DESC 
                         LIMIT 10";
    
    $result = $conn->query($ofertas_publicas);
    $ofertas = [];
    
    while ($row = $result->fetch_assoc()) {
        $ofertas[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'ofertas_destacadas' => $ofertas
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>
