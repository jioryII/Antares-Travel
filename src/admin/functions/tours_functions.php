<?php
/**
 * Sistema de Gestión de Tours - Antares Travel
 * Versión: 2.0.0 - Reestructurada según schema oficial
 * Fecha: Agosto 2025
 */

// Configuración de base de datos específica para tours
require_once __DIR__ . '/../config/config.php';

/**
 * Obtener conexión a la base de datos
 */
function getToursDatabase() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error de conexión en tours: " . $e->getMessage());
        throw new Exception("Error de conexión a la base de datos");
    }
}

/**
 * Obtener lista de tours con filtros y paginación
 * Función compatible con el index.php actual
 */
function obtenerTours($pagina = 1, $por_pagina = 10, $filtros = []) {
    try {
        $pdo = getToursDatabase();
        
        // Variables para construir la consulta
        $where_conditions = [];
        $params = [];
        $join_clauses = [];
        
        // JOINs necesarios
        $join_clauses[] = "LEFT JOIN regiones r ON t.id_region = r.id_region";
        $join_clauses[] = "LEFT JOIN guias g ON t.id_guia = g.id_guia";
        $join_clauses[] = "LEFT JOIN reservas res ON t.id_tour = res.id_tour";
        $join_clauses[] = "LEFT JOIN reservas res_conf ON t.id_tour = res_conf.id_tour AND res_conf.estado = 'Confirmada'";
        
        // Filtro de búsqueda por título y descripción
        if (!empty($filtros['busqueda'])) {
            $busqueda = trim($filtros['busqueda']);
            $where_conditions[] = "(LOWER(t.titulo) LIKE LOWER(:busqueda) OR LOWER(t.descripcion) LIKE LOWER(:busqueda))";
            $params['busqueda'] = "%" . $busqueda . "%";
        }
        
        // Filtro por región
        if (!empty($filtros['region']) && is_numeric($filtros['region'])) {
            $where_conditions[] = "t.id_region = :region";
            $params['region'] = intval($filtros['region']);
        }
        
        // Filtro por estado de guía
        if (!empty($filtros['guia_estado'])) {
            if ($filtros['guia_estado'] === 'con_guia') {
                $where_conditions[] = "t.id_guia IS NOT NULL";
            } elseif ($filtros['guia_estado'] === 'sin_guia') {
                $where_conditions[] = "t.id_guia IS NULL";
            }
        }
        
        // Filtro por rango de precio
        if (!empty($filtros['precio_min']) && is_numeric($filtros['precio_min'])) {
            $where_conditions[] = "t.precio >= :precio_min";
            $params['precio_min'] = floatval($filtros['precio_min']);
        }
        
        if (!empty($filtros['precio_max']) && is_numeric($filtros['precio_max'])) {
            $where_conditions[] = "t.precio <= :precio_max";
            $params['precio_max'] = floatval($filtros['precio_max']);
        }
        
        // Construir consulta principal
        $sql = "
            SELECT 
                t.id_tour,
                t.titulo,
                t.descripcion,
                t.precio,
                t.duracion,
                t.lugar_salida,
                t.lugar_llegada,
                t.hora_salida,
                t.hora_llegada,
                t.imagen_principal,
                
                -- Datos de región
                r.id_region,
                r.nombre_region,
                
                -- Datos de guía
                g.id_guia,
                g.nombre AS guia_nombre,
                g.apellido AS guia_apellido,
                g.estado AS estado_guia,
                
                -- Estadísticas agregadas
                COUNT(DISTINCT res.id_reserva) AS total_reservas,
                COUNT(DISTINCT res_conf.id_reserva) AS reservas_confirmadas,
                COALESCE(SUM(res_conf.monto_total), 0) AS ingresos_generados
                
            FROM tours t
            " . implode(" ", $join_clauses);
        
        // Agregar condiciones WHERE
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $sql .= " GROUP BY t.id_tour, t.titulo, t.descripcion, t.precio, t.duracion, 
                  t.lugar_salida, t.lugar_llegada, t.hora_salida, t.hora_llegada, 
                  t.imagen_principal, r.id_region, r.nombre_region, g.id_guia, 
                  g.nombre, g.apellido, g.estado
                  ORDER BY t.id_tour DESC";
        
        // Consulta para contar el total (sin paginación)
        $count_sql = "
            SELECT COUNT(DISTINCT t.id_tour) as total 
            FROM tours t
            " . implode(" ", $join_clauses);
        
        if (!empty($where_conditions)) {
            $count_sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Obtener total de registros
        $stmt_count = $pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $stmt_count->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt_count->execute();
        $total = $stmt_count->fetch()['total'];
        
        // Aplicar paginación a la consulta principal
        $offset = ($pagina - 1) * $por_pagina;
        $sql .= " LIMIT :limite OFFSET :offset";
        
        // Preparar y ejecutar consulta principal
        $stmt = $pdo->prepare($sql);
        
        // Bind de parámetros de filtros
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        // Bind de parámetros de paginación
        $stmt->bindValue(':limite', $por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $tours = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => $tours,
            'total' => (int)$total,
            'pagina_actual' => $pagina,
            'limite' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
            'filtros_aplicados' => $filtros,
            'debug_sql' => $sql, // Para debug si es necesario
            'debug_params' => $params
        ];
        
    } catch (Exception $e) {
        error_log("Error obteniendo tours: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error al obtener tours: ' . $e->getMessage(),
            'data' => [],
            'total' => 0,
            'total_paginas' => 1,
            'error_detalle' => $e->getMessage()
        ];
    }
}

/**
 * Obtener todas las regiones
 */
function obtenerRegiones() {
    try {
        $pdo = getToursDatabase();
        $stmt = $pdo->query("SELECT id_region, nombre_region FROM regiones ORDER BY nombre_region");
        $regiones = $stmt->fetchAll();
        
        return ['success' => true, 'data' => $regiones];
    } catch (Exception $e) {
        error_log("Error obteniendo regiones: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al obtener regiones', 'data' => []];
    }
}

/**
 * Obtener guías disponibles (compatible con el index.php)
 */
function obtenerGuiasDisponibles() {
    try {
        $pdo = getToursDatabase();
        $stmt = $pdo->query("SELECT id_guia, nombre, apellido, telefono, email, estado FROM guias ORDER BY nombre, apellido");
        $guias = $stmt->fetchAll();
        
        return ['success' => true, 'data' => $guias];
    } catch (Exception $e) {
        error_log("Error obteniendo guías: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al obtener guías', 'data' => []];
    }
}

/**
 * Obtener un tour específico por ID
 */
function obtenerTourPorId($id_tour) {
    try {
        $pdo = getToursDatabase();
        
        $sql = "
            SELECT 
                t.*,
                r.nombre_region,
                g.nombre AS nombre_guia,
                g.apellido AS apellido_guia,
                g.telefono AS telefono_guia,
                g.email AS email_guia,
                g.estado AS estado_guia,
                COUNT(DISTINCT res.id_reserva) AS total_reservas,
                COUNT(DISTINCT res_conf.id_reserva) AS reservas_confirmadas,
                COALESCE(SUM(res_conf.monto_total), 0) AS ingresos_totales
            FROM tours t
            LEFT JOIN regiones r ON t.id_region = r.id_region
            LEFT JOIN guias g ON t.id_guia = g.id_guia
            LEFT JOIN reservas res ON t.id_tour = res.id_tour
            LEFT JOIN reservas res_conf ON t.id_tour = res_conf.id_tour AND res_conf.estado = 'Confirmada'
            WHERE t.id_tour = :id_tour
            GROUP BY t.id_tour
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_tour', $id_tour, PDO::PARAM_INT);
        $stmt->execute();
        $tour = $stmt->fetch();
        
        if (!$tour) {
            return ['success' => false, 'message' => 'Tour no encontrado'];
        }
        
        return ['success' => true, 'data' => $tour];
        
    } catch (Exception $e) {
        error_log("Error obteniendo tour por ID: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al obtener tour: ' . $e->getMessage()];
    }
}

/**
 * Crear nuevo tour
 */
function crearTour($datos) {
    try {
        $pdo = getToursDatabase();
        
        // Validaciones básicas
        if (empty($datos['titulo']) || empty($datos['descripcion']) || empty($datos['precio'])) {
            return ['success' => false, 'message' => 'Faltan campos obligatorios'];
        }
        
        // Verificar título único
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tours WHERE titulo = :titulo");
        $stmt_check->bindParam(':titulo', $datos['titulo']);
        $stmt_check->execute();
        
        if ($stmt_check->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Ya existe un tour con ese título'];
        }
        
        $pdo->beginTransaction();
        
        $sql = "
            INSERT INTO tours (
                titulo, descripcion, precio, duracion, 
                id_region, lugar_salida, lugar_llegada, 
                hora_salida, hora_llegada, imagen_principal, id_guia
            ) VALUES (
                :titulo, :descripcion, :precio, :duracion,
                :id_region, :lugar_salida, :lugar_llegada,
                :hora_salida, :hora_llegada, :imagen_principal, :id_guia
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $params = [
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'precio' => $datos['precio'],
            'duracion' => $datos['duracion'],
            'id_region' => null, // Campo eliminado del formulario
            'lugar_salida' => null, // Campo eliminado del formulario
            'lugar_llegada' => null, // Campo eliminado del formulario
            'hora_salida' => !empty($datos['hora_salida']) ? $datos['hora_salida'] : null,
            'hora_llegada' => !empty($datos['hora_llegada']) ? $datos['hora_llegada'] : null,
            'imagen_principal' => $datos['imagen_principal'] ?? null,
            'id_guia' => null // Campo eliminado del formulario
        ];
        
        $stmt->execute($params);
        $id_tour = $pdo->lastInsertId();
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Tour creado exitosamente',
            'id_tour' => $id_tour
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error creando tour: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al crear tour: ' . $e->getMessage()];
    }
}

/**
 * Actualizar tour existente
 */
function actualizarTour($id_tour, $datos) {
    try {
        $pdo = getToursDatabase();
        
        // Validaciones básicas
        if (empty($datos['titulo']) || empty($datos['descripcion']) || empty($datos['precio'])) {
            return ['success' => false, 'message' => 'Faltan campos obligatorios'];
        }
        
        // Verificar que el tour existe
        $tour_actual = obtenerTourPorId($id_tour);
        if (!$tour_actual['success']) {
            return ['success' => false, 'message' => 'Tour no encontrado'];
        }
        
        // Verificar título único (excluyendo el tour actual)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tours WHERE titulo = :titulo AND id_tour != :id_tour");
        $stmt_check->execute([
            'titulo' => $datos['titulo'],
            'id_tour' => $id_tour
        ]);
        
        if ($stmt_check->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Ya existe otro tour con ese título'];
        }
        
        $pdo->beginTransaction();
        
        $sql = "
            UPDATE tours SET
                titulo = :titulo,
                descripcion = :descripcion,
                precio = :precio,
                duracion = :duracion,
                id_region = :id_region,
                lugar_salida = :lugar_salida,
                lugar_llegada = :lugar_llegada,
                hora_salida = :hora_salida,
                hora_llegada = :hora_llegada,
                id_guia = :id_guia
        ";
        
        $params = [
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'precio' => $datos['precio'],
            'duracion' => $datos['duracion'],
            'id_region' => null, // Campo eliminado del formulario
            'lugar_salida' => null, // Campo eliminado del formulario
            'lugar_llegada' => null, // Campo eliminado del formulario
            'hora_salida' => !empty($datos['hora_salida']) ? $datos['hora_salida'] : null,
            'hora_llegada' => !empty($datos['hora_llegada']) ? $datos['hora_llegada'] : null,
            'id_guia' => null, // Campo eliminado del formulario
            'id_tour' => $id_tour
        ];
        
        // Actualizar imagen solo si se proporciona una nueva
        if (isset($datos['imagen_principal'])) {
            $sql .= ", imagen_principal = :imagen_principal";
            $params['imagen_principal'] = $datos['imagen_principal'];
        }
        
        $sql .= " WHERE id_tour = :id_tour";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Tour actualizado exitosamente'
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error actualizando tour: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al actualizar tour: ' . $e->getMessage()];
    }
}

/**
 * Eliminar tour
 */
function eliminarTour($id_tour) {
    try {
        $pdo = getToursDatabase();
        
        // Verificar que el tour existe
        $tour = obtenerTourPorId($id_tour);
        if (!$tour['success']) {
            return ['success' => false, 'message' => 'Tour no encontrado'];
        }
        
        // Verificar que no tenga reservas confirmadas
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*) FROM reservas 
            WHERE id_tour = :id_tour AND estado IN ('Confirmada', 'Pendiente')
        ");
        $stmt_check->bindParam(':id_tour', $id_tour, PDO::PARAM_INT);
        $stmt_check->execute();
        
        if ($stmt_check->fetchColumn() > 0) {
            return [
                'success' => false, 
                'message' => 'No se puede eliminar el tour porque tiene reservas activas'
            ];
        }
        
        $pdo->beginTransaction();
        
        // Eliminar imagen si existe
        if (!empty($tour['data']['imagen_principal'])) {
            $ruta_imagen = __DIR__ . '/../../..' . $tour['data']['imagen_principal'];
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
        
        // Eliminar tour
        $stmt = $pdo->prepare("DELETE FROM tours WHERE id_tour = :id_tour");
        $stmt->bindParam(':id_tour', $id_tour, PDO::PARAM_INT);
        $stmt->execute();
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Tour eliminado exitosamente'
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error eliminando tour: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al eliminar tour: ' . $e->getMessage()];
    }
}

/**
 * Obtener estadísticas generales de tours
 */
function obtenerEstadisticasTours() {
    try {
        $pdo = getToursDatabase();
        
        $estadisticas = [];
        
        // Total de tours
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tours");
        $estadisticas['total_tours'] = (int)$stmt->fetchColumn();
        
        // Tours con guía asignado
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tours WHERE id_guia IS NOT NULL");
        $estadisticas['tours_con_guia'] = (int)$stmt->fetchColumn();
        
        // Total de reservas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas");
        $estadisticas['total_reservas'] = (int)$stmt->fetchColumn();
        
        // Reservas confirmadas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'Confirmada'");
        $estadisticas['reservas_confirmadas'] = (int)$stmt->fetchColumn();
        
        // Ingresos totales
        $stmt = $pdo->query("SELECT COALESCE(SUM(monto_total), 0) as total FROM reservas WHERE estado = 'Confirmada'");
        $estadisticas['ingresos_totales'] = (float)$stmt->fetchColumn();
        
        // Precio promedio de tours
        $stmt = $pdo->query("SELECT AVG(precio) as promedio FROM tours");
        $estadisticas['precio_promedio'] = round((float)$stmt->fetchColumn(), 2);
        
        // Tour más popular
        $stmt = $pdo->query("
            SELECT t.titulo, COUNT(r.id_reserva) as total_reservas
            FROM tours t
            LEFT JOIN reservas r ON t.id_tour = r.id_tour
            GROUP BY t.id_tour
            ORDER BY total_reservas DESC
            LIMIT 1
        ");
        $popular = $stmt->fetch();
        $estadisticas['tour_popular'] = $popular ? $popular['titulo'] : 'N/A';
        $estadisticas['reservas_tour_popular'] = $popular ? (int)$popular['total_reservas'] : 0;
        
        return ['success' => true, 'data' => $estadisticas];
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error al obtener estadísticas',
            'data' => []
        ];
    }
}

/**
 * Obtener estadísticas detalladas
 */
function obtenerEstadisticasDetalladas() {
    try {
        $pdo = getToursDatabase();
        $estadisticas = [];
        
        // Distribución por región
        $stmt = $pdo->query("
            SELECT r.nombre_region, COUNT(t.id_tour) as cantidad
            FROM regiones r
            LEFT JOIN tours t ON r.id_region = t.id_region
            GROUP BY r.id_region
            ORDER BY cantidad DESC
            LIMIT 10
        ");
        $estadisticas['por_region'] = $stmt->fetchAll();
        
        // Tours por rango de precio
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN precio < 50 THEN 'Menos de S/50'
                    WHEN precio < 100 THEN 'S/50 - S/99'
                    WHEN precio < 200 THEN 'S/100 - S/199'
                    WHEN precio < 500 THEN 'S/200 - S/499'
                    ELSE 'S/500 o más'
                END as rango_precio,
                COUNT(*) as cantidad
            FROM tours
            GROUP BY rango_precio
            ORDER BY MIN(precio)
        ");
        $estadisticas['por_precio'] = $stmt->fetchAll();
        
        // Estado de los guías
        $stmt = $pdo->query("
            SELECT estado, COUNT(*) as cantidad
            FROM guias
            GROUP BY estado
        ");
        $estadisticas['estado_guias'] = $stmt->fetchAll();
        
        // Reservas por mes (últimos 6 meses)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(fecha_reserva, '%Y-%m') as mes,
                COUNT(*) as cantidad,
                SUM(monto_total) as ingresos
            FROM reservas
            WHERE fecha_reserva >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY mes
            ORDER BY mes DESC
        ");
        $estadisticas['reservas_mensuales'] = $stmt->fetchAll();
        
        // Top 5 tours por ingresos
        $stmt = $pdo->query("
            SELECT 
                t.titulo,
                COUNT(r.id_reserva) as total_reservas,
                SUM(r.monto_total) as ingresos_totales
            FROM tours t
            LEFT JOIN reservas r ON t.id_tour = r.id_tour AND r.estado = 'Confirmada'
            GROUP BY t.id_tour
            ORDER BY ingresos_totales DESC
            LIMIT 5
        ");
        $estadisticas['top_tours_ingresos'] = $stmt->fetchAll();
        
        return $estadisticas;
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas detalladas: " . $e->getMessage());
        return [];
    }
}

/**
 * Procesar imagen de tour
 */
function procesarImagenTour($archivo, $id_tour = null) {
    try {
        // Validaciones básicas
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error al subir archivo'];
        }
        
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($archivo['type'], $tipos_permitidos)) {
            return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
        }
        
        $tamano_maximo = 5 * 1024 * 1024; // 5MB
        if ($archivo['size'] > $tamano_maximo) {
            return ['success' => false, 'message' => 'El archivo es demasiado grande'];
        }
        
        // Crear directorio si no existe
        $directorio_upload = __DIR__ . '/../../../uploads/tours/';
        if (!is_dir($directorio_upload)) {
            mkdir($directorio_upload, 0755, true);
        }
        
        // Generar nombre único
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'tour_' . ($id_tour ?: 'new') . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio_upload . $nombre_archivo;
        $ruta_relativa = 'uploads/tours/' . $nombre_archivo;
        
        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            return ['success' => false, 'message' => 'Error al guardar archivo'];
        }
        
        return [
            'success' => true, 
            'message' => 'Imagen procesada exitosamente',
            'ruta' => $ruta_relativa
        ];
        
    } catch (Exception $e) {
        error_log("Error procesando imagen: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al procesar imagen'];
    }
}

/**
 * Buscar tours con filtros (para el API)
 */
function buscarTours($filtros = []) {
    try {
        $pdo = getToursDatabase();
        
        $pagina = isset($filtros['pagina']) ? max(1, intval($filtros['pagina'])) : 1;
        $limite = isset($filtros['limite']) ? max(1, intval($filtros['limite'])) : 10;
        $busqueda = isset($filtros['busqueda']) ? trim($filtros['busqueda']) : '';
        
        // Usar la función existente
        return obtenerTours($pagina, $limite, $busqueda);
        
    } catch (Exception $e) {
        error_log("Error buscando tours: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error al buscar tours',
            'data' => [],
            'total' => 0
        ];
    }
}
?>
