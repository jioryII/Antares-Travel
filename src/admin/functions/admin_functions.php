<?php
// Archivo: src/admin/functions/admin_functions.php
// Funciones específicas para la administración

require_once __DIR__ . '/../config/config.php';

/**
 * Verificar credenciales de administrador
 */
function verificarCredencialesAdmin($email, $password) {
    try {
        $connection = getConnection();
        $sql = "SELECT id_admin, nombre, email, password_hash, rol FROM administradores WHERE email = ? AND activo = 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Actualizar último acceso
            $updateSql = "UPDATE administradores SET ultimo_acceso = NOW() WHERE id_admin = ?";
            $updateStmt = $connection->prepare($updateSql);
            $updateStmt->execute([$admin['id_admin']]);
            
            // Registrar login en auditoría
            logActivity($admin['id_admin'], 'LOGIN', null, null, 'Login exitoso');
            
            return $admin;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error en verificarCredencialesAdmin: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estadísticas completas para el dashboard basado en datos reales
 */
function getDashboardStats() {
    try {
        $connection = getConnection();
        $stats = [];
        
        // Total de tours
        $stmt = $connection->query("SELECT COUNT(*) as total FROM tours");
        $stats['total_tours'] = $stmt->fetch()['total'];
        
        // Total de reservas
        $stmt = $connection->query("SELECT COUNT(*) as total FROM reservas");
        $stats['total_reservas'] = $stmt->fetch()['total'];
        
        // Total de usuarios registrados
        $stmt = $connection->query("SELECT COUNT(*) as total FROM usuarios");
        $stats['total_usuarios'] = $stmt->fetch()['total'];
        
        // Total de guías disponibles
        $stmt = $connection->query("SELECT COUNT(*) as total FROM guias");
        $stats['total_guias'] = $stmt->fetch()['total'];
        
        // Total de vehículos disponibles
        $stmt = $connection->query("SELECT COUNT(*) as total FROM vehiculos");
        $stats['total_vehiculos'] = $stmt->fetch()['total'];
        
        // Total de regiones
        $stmt = $connection->query("SELECT COUNT(*) as total FROM regiones");
        $stats['total_regiones'] = $stmt->fetch()['total'];
        
        // Reservas del mes actual
        $stmt = $connection->query("
            SELECT COUNT(*) as total 
            FROM reservas 
            WHERE MONTH(fecha_reserva) = MONTH(CURRENT_DATE()) 
            AND YEAR(fecha_reserva) = YEAR(CURRENT_DATE())
        ");
        $stats['reservas_mes'] = $stmt->fetch()['total'];
        
        // Ingresos del mes actual (solo reservas confirmadas y finalizadas)
        $stmt = $connection->query("
            SELECT COALESCE(SUM(monto_total), 0) as total 
            FROM reservas 
            WHERE estado IN ('Confirmada', 'Finalizada')
            AND MONTH(fecha_reserva) = MONTH(CURRENT_DATE()) 
            AND YEAR(fecha_reserva) = YEAR(CURRENT_DATE())
        ");
        $stats['ingresos_mes'] = $stmt->fetch()['total'];
        
        // Ingresos totales (confirmadas y finalizadas)
        $stmt = $connection->query("
            SELECT COALESCE(SUM(monto_total), 0) as total 
            FROM reservas 
            WHERE estado IN ('Confirmada', 'Finalizada')
        ");
        $stats['ingresos_totales'] = $stmt->fetch()['total'];
        
        // Reservas por estado
        $stmt = $connection->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'Pendiente'");
        $stats['reservas_pendientes'] = $stmt->fetch()['total'];
        
        $stmt = $connection->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'Confirmada'");
        $stats['reservas_confirmadas'] = $stmt->fetch()['total'];
        
        $stmt = $connection->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'Cancelada'");
        $stats['reservas_canceladas'] = $stmt->fetch()['total'];
        
        $stmt = $connection->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'Finalizada'");
        $stats['reservas_finalizadas'] = $stmt->fetch()['total'];
        
        // Tours más populares (con más reservas)
        $stmt = $connection->query("
            SELECT 
                t.id_tour,
                t.titulo,
                t.precio,
                r.nombre_region,
                COUNT(res.id_reserva) as total_reservas,
                COALESCE(SUM(CASE WHEN res.estado IN ('Confirmada', 'Finalizada') THEN res.monto_total ELSE 0 END), 0) as ingresos_generados
            FROM tours t
            LEFT JOIN regiones r ON t.id_region = r.id_region
            LEFT JOIN reservas res ON t.id_tour = res.id_tour 
            GROUP BY t.id_tour, t.titulo, t.precio, r.nombre_region
            HAVING total_reservas > 0
            ORDER BY total_reservas DESC, ingresos_generados DESC
            LIMIT 8
        ");
        $stats['tours_populares'] = $stmt->fetchAll();
        
        // Ingresos por mes (últimos 12 meses)
        $stmt = $connection->query("
            SELECT 
                DATE_FORMAT(fecha_reserva, '%Y-%m') as mes,
                COALESCE(SUM(CASE WHEN estado IN ('Confirmada', 'Finalizada') THEN monto_total ELSE 0 END), 0) as ingresos,
                COUNT(*) as total_reservas
            FROM reservas 
            WHERE fecha_reserva >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(fecha_reserva, '%Y-%m')
            ORDER BY mes ASC
        ");
        $stats['ingresos_mensuales'] = $stmt->fetchAll();
        
        // Reservas por estado para gráfico
        $stmt = $connection->query("
            SELECT estado, COUNT(*) as cantidad
            FROM reservas
            GROUP BY estado
            ORDER BY cantidad DESC
        ");
        $stats['reservas_por_estado'] = $stmt->fetchAll();
        
        // Próximas reservas (próximos 15 días)
        $stmt = $connection->query("
            SELECT 
                r.id_reserva,
                r.fecha_tour,
                r.monto_total,
                r.estado,
                r.observaciones,
                u.nombre as usuario_nombre,
                u.telefono as usuario_telefono,
                t.titulo as tour_titulo,
                t.lugar_salida,
                t.hora_salida,
                g.nombre as guia_nombre,
                g.apellido as guia_apellido
            FROM reservas r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            INNER JOIN tours t ON r.id_tour = t.id_tour
            LEFT JOIN guias g ON t.id_guia = g.id_guia
            WHERE r.fecha_tour BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
            AND r.estado IN ('Confirmada', 'Pendiente')
            ORDER BY r.fecha_tour ASC, t.hora_salida ASC
            LIMIT 12
        ");
        $stats['proximas_reservas'] = $stmt->fetchAll();
        
        // Estadísticas de guías
        $stmt = $connection->query("
            SELECT 
                g.id_guia,
                g.nombre,
                g.apellido,
                g.experiencia,
                COUNT(t.id_tour) as tours_asignados,
                COUNT(r.id_reserva) as reservas_totales,
                COALESCE(AVG(cg.calificacion), 0) as promedio_calificacion
            FROM guias g
            LEFT JOIN tours t ON g.id_guia = t.id_guia
            LEFT JOIN reservas r ON t.id_tour = r.id_tour AND r.estado IN ('Confirmada', 'Finalizada')
            LEFT JOIN calificaciones_guias cg ON g.id_guia = cg.id_guia
            GROUP BY g.id_guia, g.nombre, g.apellido, g.experiencia
            ORDER BY reservas_totales DESC, promedio_calificacion DESC
            LIMIT 10
        ");
        $stats['guias_top'] = $stmt->fetchAll();
        
        // Regiones más visitadas
        $stmt = $connection->query("
            SELECT 
                r.id_region,
                r.nombre_region,
                COUNT(t.id_tour) as total_tours,
                COUNT(res.id_reserva) as total_reservas,
                COALESCE(SUM(CASE WHEN res.estado IN ('Confirmada', 'Finalizada') THEN res.monto_total ELSE 0 END), 0) as ingresos_region
            FROM regiones r
            LEFT JOIN tours t ON r.id_region = t.id_region
            LEFT JOIN reservas res ON t.id_tour = res.id_tour
            GROUP BY r.id_region, r.nombre_region
            HAVING total_reservas > 0
            ORDER BY total_reservas DESC, ingresos_region DESC
            LIMIT 8
        ");
        $stats['regiones_populares'] = $stmt->fetchAll();
        
        // Usuarios más activos (con más reservas)
        $stmt = $connection->query("
            SELECT 
                u.id_usuario,
                u.nombre,
                u.email,
                COUNT(r.id_reserva) as total_reservas,
                COALESCE(SUM(CASE WHEN r.estado IN ('Confirmada', 'Finalizada') THEN r.monto_total ELSE 0 END), 0) as gasto_total,
                MAX(r.fecha_reserva) as ultima_reserva
            FROM usuarios u
            INNER JOIN reservas r ON u.id_usuario = r.id_usuario
            GROUP BY u.id_usuario, u.nombre, u.email
            ORDER BY total_reservas DESC, gasto_total DESC
            LIMIT 10
        ");
        $stats['usuarios_top'] = $stmt->fetchAll();
        
        // Análisis de conversión (cotizaciones vs reservas)
        $stmt = $connection->query("
            SELECT 
                COUNT(DISTINCT c.id_cotizacion) as total_cotizaciones,
                COUNT(DISTINCT r.id_reserva) as total_reservas_del_mes,
                CASE 
                    WHEN COUNT(DISTINCT c.id_cotizacion) > 0 
                    THEN ROUND((COUNT(DISTINCT r.id_reserva) * 100.0 / COUNT(DISTINCT c.id_cotizacion)), 2)
                    ELSE 0 
                END as tasa_conversion
            FROM cotizaciones c
            LEFT JOIN reservas r ON MONTH(r.fecha_reserva) = MONTH(CURRENT_DATE()) 
                AND YEAR(r.fecha_reserva) = YEAR(CURRENT_DATE())
            WHERE MONTH(c.fecha_creacion) = MONTH(CURRENT_DATE()) 
                AND YEAR(c.fecha_creacion) = YEAR(CURRENT_DATE())
        ");
        $conversion = $stmt->fetch();
        $stats['cotizaciones_mes'] = $conversion['total_cotizaciones'] ?? 0;
        $stats['tasa_conversion'] = $conversion['tasa_conversion'] ?? 0;
        
        // Disponibilidad de recursos hoy
        $stmt = $connection->query("
            SELECT 
                (SELECT COUNT(*) FROM guias) as guias_totales,
                (SELECT COUNT(*) FROM disponibilidad_guias dg 
                 WHERE dg.fecha = CURDATE() AND dg.estado = 'Ocupado') as guias_ocupados_hoy,
                (SELECT COUNT(*) FROM vehiculos) as vehiculos_totales,
                (SELECT COUNT(*) FROM disponibilidad_vehiculos dv 
                 WHERE dv.fecha = CURDATE() AND dv.estado = 'Ocupado') as vehiculos_ocupados_hoy
        ");
        $disponibilidad = $stmt->fetch();
        $stats['guias_disponibles_hoy'] = ($disponibilidad['guias_totales'] ?? 0) - ($disponibilidad['guias_ocupados_hoy'] ?? 0);
        $stats['vehiculos_disponibles_hoy'] = ($disponibilidad['vehiculos_totales'] ?? 0) - ($disponibilidad['vehiculos_ocupados_hoy'] ?? 0);
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error en getDashboardStats: " . $e->getMessage());
        return [
            'total_tours' => 0,
            'total_reservas' => 0,
            'total_usuarios' => 0,
            'total_guias' => 0,
            'total_vehiculos' => 0,
            'total_regiones' => 0,
            'reservas_mes' => 0,
            'ingresos_mes' => 0,
            'ingresos_totales' => 0,
            'reservas_pendientes' => 0,
            'reservas_confirmadas' => 0,
            'reservas_canceladas' => 0,
            'reservas_finalizadas' => 0,
            'tours_populares' => [],
            'ingresos_mensuales' => [],
            'reservas_por_estado' => [],
            'proximas_reservas' => [],
            'guias_top' => [],
            'regiones_populares' => [],
            'usuarios_top' => [],
            'cotizaciones_mes' => 0,
            'tasa_conversion' => 0,
            'guias_disponibles_hoy' => 0,
            'vehiculos_disponibles_hoy' => 0
        ];
    }
}

/**
 * Obtener reservas recientes para el dashboard con información completa
 */
function getReservasRecientes($limite = 8) {
    try {
        $connection = getConnection();
        
        $sql = "SELECT 
                    r.id_reserva,
                    r.fecha_reserva,
                    r.fecha_tour,
                    r.monto_total,
                    r.estado,
                    r.observaciones,
                    r.origen_reserva,
                    t.titulo as tour_titulo,
                    t.duracion,
                    t.lugar_salida,
                    t.hora_salida,
                    u.nombre as usuario_nombre,
                    u.email as usuario_email,
                    u.telefono as usuario_telefono,
                    reg.nombre_region,
                    g.nombre as guia_nombre,
                    g.apellido as guia_apellido,
                    CASE 
                        WHEN r.fecha_tour < CURDATE() THEN 'Pasado'
                        WHEN r.fecha_tour = CURDATE() THEN 'Hoy'
                        WHEN r.fecha_tour BETWEEN DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Esta semana'
                        ELSE 'Próximo'
                    END as tiempo_relativo
                FROM reservas r
                INNER JOIN tours t ON r.id_tour = t.id_tour
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
                LEFT JOIN regiones reg ON t.id_region = reg.id_region
                LEFT JOIN guias g ON t.id_guia = g.id_guia
                ORDER BY r.fecha_reserva DESC
                LIMIT ?";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error en getReservasRecientes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener todos los usuarios para selector
 */
function getUsuariosParaSelector() {
    try {
        $connection = getConnection();
        $sql = "SELECT id_usuario, nombre, email FROM usuarios ORDER BY nombre ASC";
        $stmt = $connection->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error en getUsuariosParaSelector: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener todos los tours activos para selector
 */
function getToursParaSelector() {
    try {
        $connection = getConnection();
        $sql = "SELECT t.id_tour, t.titulo, t.precio, r.nombre_region 
                FROM tours t 
                INNER JOIN regiones r ON t.id_region = r.id_region 
                WHERE t.estado = 'Activo' 
                ORDER BY t.titulo ASC";
        $stmt = $connection->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error en getToursParaSelector: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener actividad reciente para el dashboard
 */
function getActividadReciente($limite = 10) {
    try {
        $connection = getConnection();
        
        $sql = "SELECT 
                    l.accion,
                    l.tabla_afectada,
                    l.registro_id,
                    l.detalles,
                    l.fecha_hora,
                    a.nombre as admin_nombre
                FROM logs_auditoria l
                INNER JOIN administradores a ON l.admin_id = a.id_admin
                ORDER BY l.fecha_hora DESC
                LIMIT ?";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error en getActividadReciente: " . $e->getMessage());
        return [];
    }
}

/**
 * Validar que el admin tiene permisos para una acción específica
 */
function validarPermisoAccion($admin_id, $accion) {
    try {
        $connection = getConnection();
        $sql = "SELECT rol FROM administradores WHERE id_admin = ? AND activo = 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        
        if (!$admin) return false;
        
        $permisos = [
            'moderador' => ['view', 'update_status'],
            'admin' => ['view', 'create', 'update', 'update_status', 'delete'],
            'super_admin' => ['view', 'create', 'update', 'update_status', 'delete', 'manage_admins']
        ];
        
        $rol = $admin['rol'];
        return in_array($accion, $permisos[$rol] ?? []);
        
    } catch (Exception $e) {
        error_log("Error en validarPermisoAccion: " . $e->getMessage());
        return false;
    }
}

/**
 * Cambiar contraseña de administrador
 */
function cambiarPasswordAdmin($admin_id, $password_actual, $password_nueva) {
    try {
        $connection = getConnection();
        
        // Verificar contraseña actual
        $sql = "SELECT password_hash FROM administradores WHERE id_admin = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        
        if (!$admin || !password_verify($password_actual, $admin['password_hash'])) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta'];
        }
        
        // Actualizar contraseña
        $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        $sql = "UPDATE administradores SET password_hash = ?, fecha_modificacion = NOW() WHERE id_admin = ?";
        $stmt = $connection->prepare($sql);
        $result = $stmt->execute([$nuevo_hash, $admin_id]);
        
        if ($result) {
            logActivity($admin_id, 'UPDATE', 'administradores', $admin_id, 'Cambio de contraseña');
            return ['success' => true, 'message' => 'Contraseña actualizada exitosamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la contraseña'];
        }
        
    } catch (Exception $e) {
        error_log("Error en cambiarPasswordAdmin: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor'];
    }
}

/**
 * Obtener configuración del sistema
 */
function getConfiguracionSistema() {
    try {
        $config = [
            'nombre_sitio' => SITE_NAME,
            'url_base' => BASE_URL,
            'max_file_size' => MAX_FILE_SIZE,
            'records_per_page' => RECORDS_PER_PAGE,
            'debug_mode' => DEBUG_MODE
        ];
        
        return $config;
        
    } catch (Exception $e) {
        error_log("Error en getConfiguracionSistema: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener disponibilidad de recursos para una fecha específica
 */
function getDisponibilidadRecursos($fecha) {
    try {
        $connection = getConnection();
        $disponibilidad = [];
        
        // Obtener guías disponibles
        $sql = "SELECT g.id_guia, g.nombre, g.apellido, g.telefono, g.email,
                       CASE WHEN dg.estado = 'Ocupado' THEN 0 ELSE 1 END as disponible
                FROM guias g
                LEFT JOIN disponibilidad_guias dg ON g.id_guia = dg.id_guia AND dg.fecha = ?
                WHERE g.activo = 1
                ORDER BY disponible DESC, g.nombre ASC";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$fecha]);
        $disponibilidad['guias'] = $stmt->fetchAll();
        
        // Obtener choferes disponibles
        $sql = "SELECT c.id_chofer, c.nombre, c.apellido, c.telefono, c.licencia,
                       CASE WHEN cd.estado = 'No Disponible' THEN 0 ELSE 1 END as disponible
                FROM choferes c
                LEFT JOIN chofer_disponibilidad cd ON c.id_chofer = cd.id_chofer AND cd.fecha = ?
                WHERE c.activo = 1
                ORDER BY disponible DESC, c.nombre ASC";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$fecha]);
        $disponibilidad['choferes'] = $stmt->fetchAll();
        
        // Obtener vehículos disponibles
        $sql = "SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, v.capacidad,
                       CASE WHEN dv.estado = 'Ocupado' THEN 0 ELSE 1 END as disponible
                FROM vehiculos v
                LEFT JOIN disponibilidad_vehiculos dv ON v.id_vehiculo = dv.id_vehiculo AND dv.fecha = ?
                WHERE v.activo = 1
                ORDER BY disponible DESC, v.marca ASC";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$fecha]);
        $disponibilidad['vehiculos'] = $stmt->fetchAll();
        
        return $disponibilidad;
        
    } catch (Exception $e) {
        error_log("Error en getDisponibilidadRecursos: " . $e->getMessage());
        return ['guias' => [], 'choferes' => [], 'vehiculos' => []];
    }
}

/**
 * Registrar tour diario con validaciones
 */
function registrarTourDiario($datos) {
    try {
        $connection = getConnection();
        $connection->beginTransaction();
        
        // Validar que los recursos estén disponibles
        $disponibilidad = getDisponibilidadRecursos($datos['fecha']);
        
        // Verificar guía disponible
        $guia_disponible = false;
        foreach ($disponibilidad['guias'] as $guia) {
            if ($guia['id_guia'] == $datos['id_guia'] && $guia['disponible']) {
                $guia_disponible = true;
                break;
            }
        }
        
        if (!$guia_disponible) {
            throw new Exception('El guía seleccionado no está disponible para esta fecha');
        }
        
        // Verificar chofer disponible
        $chofer_disponible = false;
        foreach ($disponibilidad['choferes'] as $chofer) {
            if ($chofer['id_chofer'] == $datos['id_chofer'] && $chofer['disponible']) {
                $chofer_disponible = true;
                break;
            }
        }
        
        if (!$chofer_disponible) {
            throw new Exception('El chofer seleccionado no está disponible para esta fecha');
        }
        
        // Verificar vehículo disponible
        $vehiculo_disponible = false;
        foreach ($disponibilidad['vehiculos'] as $vehiculo) {
            if ($vehiculo['id_vehiculo'] == $datos['id_vehiculo'] && $vehiculo['disponible']) {
                $vehiculo_disponible = true;
                break;
            }
        }
        
        if (!$vehiculo_disponible) {
            throw new Exception('El vehículo seleccionado no está disponible para esta fecha');
        }
        
        // Insertar tour diario
        $sql = "INSERT INTO tours_diarios 
                (fecha, id_tour, id_guia, id_chofer, id_vehiculo, num_adultos, num_ninos, 
                 hora_salida, hora_retorno, observaciones, fecha_creacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            $datos['fecha'],
            $datos['id_tour'],
            $datos['id_guia'],
            $datos['id_chofer'],
            $datos['id_vehiculo'],
            $datos['num_adultos'],
            $datos['num_ninos'],
            $datos['hora_salida'],
            $datos['hora_retorno'],
            $datos['observaciones']
        ]);
        
        $tour_diario_id = $connection->lastInsertId();
        
        // Actualizar disponibilidades
        actualizarDisponibilidadGuia($datos['id_guia'], $datos['fecha'], 'Ocupado');
        actualizarDisponibilidadChofer($datos['id_chofer'], $datos['fecha'], 'No Disponible', $datos['id_tour']);
        actualizarDisponibilidadVehiculo($datos['id_vehiculo'], $datos['fecha'], 'Ocupado');
        
        $connection->commit();
        
        return ['success' => true, 'id' => $tour_diario_id, 'message' => 'Tour diario registrado exitosamente'];
        
    } catch (Exception $e) {
        $connection->rollback();
        error_log("Error en registrarTourDiario: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Actualizar disponibilidad de guía
 */
function actualizarDisponibilidadGuia($id_guia, $fecha, $estado) {
    try {
        $connection = getConnection();
        
        // Verificar si ya existe registro
        $sql = "SELECT id FROM disponibilidad_guias WHERE id_guia = ? AND fecha = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$id_guia, $fecha]);
        
        if ($stmt->fetch()) {
            // Actualizar existente
            $sql = "UPDATE disponibilidad_guias SET estado = ?, fecha_actualizacion = NOW() 
                    WHERE id_guia = ? AND fecha = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$estado, $id_guia, $fecha]);
        } else {
            // Crear nuevo
            $sql = "INSERT INTO disponibilidad_guias (id_guia, fecha, estado, fecha_creacion) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$id_guia, $fecha, $estado]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarDisponibilidadGuia: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualizar disponibilidad de chofer
 */
function actualizarDisponibilidadChofer($id_chofer, $fecha, $estado, $id_tour = null) {
    try {
        $connection = getConnection();
        
        // Verificar si ya existe registro
        $sql = "SELECT id FROM chofer_disponibilidad WHERE id_chofer = ? AND fecha = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$id_chofer, $fecha]);
        
        if ($stmt->fetch()) {
            // Actualizar existente
            $sql = "UPDATE chofer_disponibilidad SET estado = ?, id_tour = ?, fecha_actualizacion = NOW() 
                    WHERE id_chofer = ? AND fecha = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$estado, $id_tour, $id_chofer, $fecha]);
        } else {
            // Crear nuevo
            $sql = "INSERT INTO chofer_disponibilidad (id_chofer, id_tour, fecha, estado, fecha_creacion) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$id_chofer, $id_tour, $fecha, $estado]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarDisponibilidadChofer: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualizar disponibilidad de vehículo
 */
function actualizarDisponibilidadVehiculo($id_vehiculo, $fecha, $estado) {
    try {
        $connection = getConnection();
        
        // Verificar si ya existe registro
        $sql = "SELECT id FROM disponibilidad_vehiculos WHERE id_vehiculo = ? AND fecha = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$id_vehiculo, $fecha]);
        
        if ($stmt->fetch()) {
            // Actualizar existente
            $sql = "UPDATE disponibilidad_vehiculos SET estado = ?, fecha_actualizacion = NOW() 
                    WHERE id_vehiculo = ? AND fecha = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$estado, $id_vehiculo, $fecha]);
        } else {
            // Crear nuevo
            $sql = "INSERT INTO disponibilidad_vehiculos (id_vehiculo, fecha, estado, fecha_creacion) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$id_vehiculo, $fecha, $estado]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarDisponibilidadVehiculo: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener tours diarios por rango de fechas
 */
function getToursDiarios($fecha_inicio = null, $fecha_fin = null, $limite = 20) {
    try {
        $connection = getConnection();
        
        $sql = "SELECT td.*, t.titulo as tour_titulo, 
                       CONCAT(g.nombre, ' ', g.apellido) as guia_nombre,
                       CONCAT(c.nombre, ' ', c.apellido) as chofer_nombre,
                       CONCAT(v.marca, ' ', v.modelo, ' - ', v.placa) as vehiculo_info
                FROM tours_diarios td
                LEFT JOIN tours t ON td.id_tour = t.id_tour
                LEFT JOIN guias g ON td.id_guia = g.id_guia
                LEFT JOIN choferes c ON td.id_chofer = c.id_chofer
                LEFT JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo";
        
        $params = [];
        
        if ($fecha_inicio && $fecha_fin) {
            $sql .= " WHERE td.fecha BETWEEN ? AND ?";
            $params = [$fecha_inicio, $fecha_fin];
        } elseif ($fecha_inicio) {
            $sql .= " WHERE td.fecha >= ?";
            $params = [$fecha_inicio];
        }
        
        $sql .= " ORDER BY td.fecha DESC, td.hora_salida DESC LIMIT ?";
        $params[] = $limite;
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error en getToursDiarios: " . $e->getMessage());
        return [];
    }
}
?>
