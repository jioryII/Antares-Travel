<?php
// Archivo: src/admin/functions/reservas_functions.php
// Funciones para gestión de reservas

require_once __DIR__ . '/../config/config.php';

/**
 * Obtener todas las reservas con paginación y filtros
 */
function getReservas($page = 1, $limit = 10, $search = '', $estado_filter = '', $fecha_filter = '') {
    try {
        $connection = getConnection();
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        // Búsqueda por nombre de usuario o ID de reserva
        if (!empty($search)) {
            $whereConditions[] = "(r.id_reserva LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Filtro por estado
        if (!empty($estado_filter)) {
            $whereConditions[] = "r.estado = ?";
            $params[] = $estado_filter;
        }
        
        // Filtro por fecha
        if (!empty($fecha_filter)) {
            $whereConditions[] = "DATE(r.fecha_tour) = ?";
            $params[] = $fecha_filter;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total 
                     FROM reservas r 
                     INNER JOIN usuarios u ON r.id_usuario = u.id_usuario 
                     INNER JOIN tours t ON r.id_tour = t.id_tour 
                     $whereClause";
        
        $countStmt = $connection->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Obtener reservas
        $sql = "SELECT r.*, 
                       u.nombre as usuario_nombre, u.email as usuario_email,
                       t.titulo as tour_titulo, t.duracion as tour_duracion,
                       COUNT(p.id_pasajero) as total_pasajeros,
                       reg.nombre_region
                FROM reservas r 
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario 
                INNER JOIN tours t ON r.id_tour = t.id_tour 
                INNER JOIN regiones reg ON t.id_region = reg.id_region
                LEFT JOIN pasajeros p ON r.id_reserva = p.id_reserva
                $whereClause
                GROUP BY r.id_reserva
                ORDER BY r.fecha_reserva DESC 
                LIMIT $limit OFFSET $offset";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $reservas = $stmt->fetchAll();
        
        return [
            'reservas' => $reservas,
            'total' => $total,
            'total_pages' => ceil($total / $limit),
            'current_page' => $page
        ];
        
    } catch (Exception $e) {
        error_log("Error en getReservas: " . $e->getMessage());
        return ['reservas' => [], 'total' => 0, 'total_pages' => 0, 'current_page' => 1];
    }
}

/**
 * Obtener reserva por ID con todos los detalles
 */
function getReservaById($id) {
    try {
        $connection = getConnection();
        
        $sql = "SELECT r.*, 
                       u.nombre as usuario_nombre, u.email as usuario_email, u.telefono as usuario_telefono,
                       t.titulo as tour_titulo, t.descripcion as tour_descripcion, t.precio as tour_precio,
                       t.duracion as tour_duracion, t.lugar_salida, t.lugar_llegada,
                       t.hora_salida, t.hora_llegada,
                       reg.nombre_region,
                       g.nombre as guia_nombre, g.apellido as guia_apellido,
                       v.marca as vehiculo_marca, v.modelo as vehiculo_modelo, v.placa as vehiculo_placa,
                       ch.nombre as chofer_nombre, ch.apellido as chofer_apellido
                FROM reservas r 
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario 
                INNER JOIN tours t ON r.id_tour = t.id_tour 
                INNER JOIN regiones reg ON t.id_region = reg.id_region
                LEFT JOIN guias g ON t.id_guia = g.id_guia
                LEFT JOIN vehiculos v ON t.id_vehiculo = v.id_vehiculo
                LEFT JOIN choferes ch ON v.id_chofer = ch.id_chofer
                WHERE r.id_reserva = ?";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$id]);
        $reserva = $stmt->fetch();
        
        if ($reserva) {
            // Obtener pasajeros
            $pasajerosSql = "SELECT * FROM pasajeros WHERE id_reserva = ? ORDER BY id_pasajero";
            $pasajerosStmt = $connection->prepare($pasajerosSql);
            $pasajerosStmt->execute([$id]);
            $reserva['pasajeros'] = $pasajerosStmt->fetchAll();
        }
        
        return $reserva;
        
    } catch (Exception $e) {
        error_log("Error en getReservaById: " . $e->getMessage());
        return null;
    }
}

/**
 * Actualizar estado de reserva
 */
function updateReservaEstado($id, $nuevo_estado, $admin_id) {
    try {
        $connection = getConnection();
        
        $sql = "UPDATE reservas SET estado = ?, fecha_modificacion = NOW() WHERE id_reserva = ?";
        $stmt = $connection->prepare($sql);
        $result = $stmt->execute([$nuevo_estado, $id]);
        
        if ($result) {
            // Registrar log de auditoría
            logActivity($admin_id, 'UPDATE', 'reservas', $id, "Estado cambiado a: $nuevo_estado");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error en updateReservaEstado: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estadísticas de reservas
 */
function getReservasStats() {
    try {
        $connection = getConnection();
        
        // Estadísticas generales
        $stats = [];
        
        // Total de reservas
        $sql = "SELECT COUNT(*) as total FROM reservas";
        $stmt = $connection->query($sql);
        $stats['total_reservas'] = $stmt->fetch()['total'];
        
        // Reservas por estado
        $sql = "SELECT estado, COUNT(*) as cantidad FROM reservas GROUP BY estado";
        $stmt = $connection->query($sql);
        $stats['por_estado'] = $stmt->fetchAll();
        
        // Reservas del mes actual
        $sql = "SELECT COUNT(*) as total FROM reservas 
                WHERE MONTH(fecha_reserva) = MONTH(CURRENT_DATE()) 
                AND YEAR(fecha_reserva) = YEAR(CURRENT_DATE())";
        $stmt = $connection->query($sql);
        $stats['mes_actual'] = $stmt->fetch()['total'];
        
        // Ingresos del mes
        $sql = "SELECT COALESCE(SUM(monto_total), 0) as total FROM reservas 
                WHERE estado IN ('Confirmada', 'Finalizada')
                AND MONTH(fecha_reserva) = MONTH(CURRENT_DATE()) 
                AND YEAR(fecha_reserva) = YEAR(CURRENT_DATE())";
        $stmt = $connection->query($sql);
        $stats['ingresos_mes'] = $stmt->fetch()['total'];
        
        // Tours más reservados
        $sql = "SELECT t.titulo, COUNT(r.id_reserva) as total_reservas
                FROM tours t
                INNER JOIN reservas r ON t.id_tour = r.id_tour
                GROUP BY t.id_tour, t.titulo
                ORDER BY total_reservas DESC
                LIMIT 5";
        $stmt = $connection->query($sql);
        $stats['tours_populares'] = $stmt->fetchAll();
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error en getReservasStats: " . $e->getMessage());
        return [];
    }
}

/**
 * Crear nueva reserva desde admin
 */
function createReserva($data, $admin_id) {
    try {
        $connection = getConnection();
        $connection->beginTransaction();
        
        // Insertar reserva
        $sql = "INSERT INTO reservas (id_usuario, id_tour, fecha_tour, monto_total, estado, origen_reserva, notas_admin) 
                VALUES (?, ?, ?, ?, ?, 'Presencial', ?)";
        
        $stmt = $connection->prepare($sql);
        $result = $stmt->execute([
            $data['id_usuario'],
            $data['id_tour'],
            $data['fecha_tour'],
            $data['monto_total'],
            $data['estado'],
            $data['notas_admin'] ?? null
        ]);
        
        if (!$result) {
            throw new Exception("Error al crear la reserva");
        }
        
        $reserva_id = $connection->lastInsertId();
        
        // Insertar pasajeros
        if (!empty($data['pasajeros'])) {
            $pasajeroSql = "INSERT INTO pasajeros (id_reserva, nombre, apellido, dni_pasaporte, nacionalidad, telefono, tipo_pasajero) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pasajeroStmt = $connection->prepare($pasajeroSql);
            
            foreach ($data['pasajeros'] as $pasajero) {
                $pasajeroStmt->execute([
                    $reserva_id,
                    $pasajero['nombre'],
                    $pasajero['apellido'],
                    $pasajero['dni_pasaporte'],
                    $pasajero['nacionalidad'],
                    $pasajero['telefono'],
                    $pasajero['tipo_pasajero']
                ]);
            }
        }
        
        $connection->commit();
        
        // Registrar log de auditoría
        logActivity($admin_id, 'CREATE', 'reservas', $reserva_id, "Reserva creada desde admin");
        
        return $reserva_id;
        
    } catch (Exception $e) {
        $connection->rollBack();
        error_log("Error en createReserva: " . $e->getMessage());
        return false;
    }
}

/**
 * Eliminar reserva
 */
function deleteReserva($id, $admin_id) {
    try {
        $connection = getConnection();
        $connection->beginTransaction();
        
        // Eliminar pasajeros primero
        $sql = "DELETE FROM pasajeros WHERE id_reserva = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$id]);
        
        // Eliminar reserva
        $sql = "DELETE FROM reservas WHERE id_reserva = ?";
        $stmt = $connection->prepare($sql);
        $result = $stmt->execute([$id]);
        
        $connection->commit();
        
        if ($result) {
            // Registrar log de auditoría
            logActivity($admin_id, 'DELETE', 'reservas', $id, "Reserva eliminada");
        }
        
        return $result;
        
    } catch (Exception $e) {
        $connection->rollBack();
        error_log("Error en deleteReserva: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener reservas próximas (próximos 7 días)
 */
function getReservasProximas() {
    try {
        $connection = getConnection();
        
        $sql = "SELECT r.*, 
                       u.nombre as usuario_nombre, u.telefono as usuario_telefono,
                       t.titulo as tour_titulo, t.lugar_salida, t.hora_salida,
                       COUNT(p.id_pasajero) as total_pasajeros
                FROM reservas r 
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario 
                INNER JOIN tours t ON r.id_tour = t.id_tour 
                LEFT JOIN pasajeros p ON r.id_reserva = p.id_reserva
                WHERE r.fecha_tour BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND r.estado IN ('Confirmada', 'Pendiente')
                GROUP BY r.id_reserva
                ORDER BY r.fecha_tour ASC, t.hora_salida ASC";
        
        $stmt = $connection->query($sql);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error en getReservasProximas: " . $e->getMessage());
        return [];
    }
}

/**
 * Validar disponibilidad de tour para una fecha
 */
function validarDisponibilidad($id_tour, $fecha, $cantidad_pasajeros) {
    try {
        $connection = getConnection();
        
        // Obtener capacidad máxima del tour (basado en el vehículo asignado)
        $sql = "SELECT v.capacidad
                FROM tours t
                LEFT JOIN vehiculos v ON t.id_vehiculo = v.id_vehiculo
                WHERE t.id_tour = ?";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$id_tour]);
        $tour = $stmt->fetch();
        
        $capacidad_maxima = $tour['capacidad'] ?? 50; // Default 50 si no hay vehículo asignado
        
        // Contar pasajeros ya reservados para esa fecha
        $sql = "SELECT COALESCE(SUM(
                    (SELECT COUNT(*) FROM pasajeros p WHERE p.id_reserva = r.id_reserva)
                ), 0) as total_reservados
                FROM reservas r
                WHERE r.id_tour = ? 
                AND r.fecha_tour = ?
                AND r.estado IN ('Confirmada', 'Pendiente')";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$id_tour, $fecha]);
        $total_reservados = $stmt->fetch()['total_reservados'];
        
        $disponibles = $capacidad_maxima - $total_reservados;
        
        return [
            'disponible' => ($disponibles >= $cantidad_pasajeros),
            'capacidad_maxima' => $capacidad_maxima,
            'reservados' => $total_reservados,
            'disponibles' => $disponibles
        ];
        
    } catch (Exception $e) {
        error_log("Error en validarDisponibilidad: " . $e->getMessage());
        return ['disponible' => false, 'capacidad_maxima' => 0, 'reservados' => 0, 'disponibles' => 0];
    }
}
?>
