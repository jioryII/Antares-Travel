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
 * Obtener lista de tours con filtros avanzados y paginación
 */
function obtenerToursConFiltros($pagina = 1, $por_pagina = 10, $filtros = []) {
    try {
        $pdo = getToursDatabase();
        
        // Construir WHERE dinámico
        $where_conditions = [];
        $params = [];
        
        // Búsqueda por texto
        if (!empty($filtros['busqueda'])) {
            $where_conditions[] = "(t.titulo LIKE :busqueda OR t.descripcion LIKE :busqueda OR t.ubicacion LIKE :busqueda)";
            $params[':busqueda'] = '%' . $filtros['busqueda'] . '%';
        }
        
        // Filtro por dificultad
        if (!empty($filtros['dificultad'])) {
            $where_conditions[] = "t.dificultad = :dificultad";
            $params[':dificultad'] = $filtros['dificultad'];
        }
        
        // Filtro por capacidad mínima
        if (!empty($filtros['capacidad_min']) && $filtros['capacidad_min'] > 0) {
            $where_conditions[] = "t.capacidad_maxima >= :capacidad_min";
            $params[':capacidad_min'] = $filtros['capacidad_min'];
        }
        
        // Filtro por imagen
        if (!empty($filtros['tiene_imagen'])) {
            if ($filtros['tiene_imagen'] === 'si') {
                $where_conditions[] = "t.imagen_principal IS NOT NULL AND t.imagen_principal != ''";
            } elseif ($filtros['tiene_imagen'] === 'no') {
                $where_conditions[] = "(t.imagen_principal IS NULL OR t.imagen_principal = '')";
            }
        }
        
        // Filtros por precio
        if (!empty($filtros['precio_min']) && $filtros['precio_min'] > 0) {
            $where_conditions[] = "t.precio >= :precio_min";
            $params[':precio_min'] = $filtros['precio_min'];
        }
        
        if (!empty($filtros['precio_max']) && $filtros['precio_max'] > 0) {
            $where_conditions[] = "t.precio <= :precio_max";
            $params[':precio_max'] = $filtros['precio_max'];
        }
        
        // Construir WHERE clause
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Contar total de registros
        $count_sql = "SELECT COUNT(*) as total FROM tours t $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Calcular offset y total de páginas
        $offset = ($pagina - 1) * $por_pagina;
        $total_paginas = ceil($total / $por_pagina);
        
        // Consulta principal con los campos requeridos
        $sql = "
            SELECT 
                t.id_tour,
                t.titulo,
                t.precio,
                t.duracion,
                t.imagen_principal,
                t.incluye,
                t.dificultad,
                t.capacidad_maxima,
                t.fecha_creacion,
                t.activo,
                r.nombre as region_nombre
            FROM tours t
            LEFT JOIN regiones r ON t.region_id = r.id_region
            $where_clause
            ORDER BY t.fecha_creacion DESC
            LIMIT :offset, :limit
        ";
        
        $stmt = $pdo->prepare($sql);
        
        // Agregar parámetros de paginación
        $params[':offset'] = $offset;
        $params[':limit'] = $por_pagina;
        
        $stmt->execute($params);
        $tours = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => $tours,
            'total' => $total,
            'total_paginas' => $total_paginas,
            'pagina_actual' => $pagina
        ];
        
    } catch (Exception $e) {
        error_log("Error en obtenerToursConFiltros: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al obtener los tours: ' . $e->getMessage(),
            'data' => [],
            'total' => 0,
            'total_paginas' => 1,
            'pagina_actual' => 1
        ];
    }
}

/**
 * Obtener lista de tours con filtros y paginación
 * Función compatible con el index.php actual
 */
function obtenerTours($pagina = 1, $por_pagina = 10, $busqueda = '') {
    try {
        $pdo = getToursDatabase();
        
        // Construir filtros basados en los parámetros
        $filtros = [];
        if (!empty($busqueda)) {
            $filtros['busqueda'] = $busqueda;
        }
        
        // Construir consulta base
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
            LEFT JOIN regiones r ON t.id_region = r.id_region
            LEFT JOIN guias g ON t.id_guia = g.id_guia
            LEFT JOIN reservas res ON t.id_tour = res.id_tour
            LEFT JOIN reservas res_conf ON t.id_tour = res_conf.id_tour AND res_conf.estado = 'Confirmada'
        ";
        
        $where_conditions = [];
        $params = [];
        
        // Aplicar filtro de búsqueda
        if (!empty($busqueda)) {
            $where_conditions[] = "(t.titulo LIKE :busqueda OR t.descripcion LIKE :busqueda OR t.lugar_salida LIKE :busqueda OR t.lugar_llegada LIKE :busqueda)";
            $params['busqueda'] = "%" . $busqueda . "%";
        }
        
        // Agregar WHERE si hay condiciones
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $sql .= " GROUP BY t.id_tour ORDER BY t.id_tour DESC";
        
        // Contar total para paginación
        $count_sql = "SELECT COUNT(DISTINCT t.id_tour) as total FROM tours t";
        if (!empty($where_conditions)) {
            $count_sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $stmt_count = $pdo->prepare($count_sql);
        $stmt_count->execute($params);
        $total = $stmt_count->fetch()['total'];
        
        // Aplicar paginación
        $offset = ($pagina - 1) * $por_pagina;
        $sql .= " LIMIT :limite OFFSET :offset";
        $params['limite'] = $por_pagina;
        $params['offset'] = $offset;
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $tours = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => $tours,
            'total' => (int)$total,
            'pagina_actual' => $pagina,
            'limite' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
            'filtros_aplicados' => $filtros
        ];
        
    } catch (Exception $e) {
        error_log("Error obteniendo tours: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error al obtener tours: ' . $e->getMessage(),
            'data' => [],
            'total' => 0,
            'total_paginas' => 1
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
            'id_region' => !empty($datos['id_region']) ? $datos['id_region'] : null,
            'lugar_salida' => $datos['lugar_salida'],
            'lugar_llegada' => $datos['lugar_llegada'],
            'hora_salida' => !empty($datos['hora_salida']) ? $datos['hora_salida'] : null,
            'hora_llegada' => !empty($datos['hora_llegada']) ? $datos['hora_llegada'] : null,
            'imagen_principal' => $datos['imagen_principal'] ?? null,
            'id_guia' => !empty($datos['id_guia']) ? $datos['id_guia'] : null
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
            'id_region' => !empty($datos['id_region']) ? $datos['id_region'] : null,
            'lugar_salida' => $datos['lugar_salida'],
            'lugar_llegada' => $datos['lugar_llegada'],
            'hora_salida' => !empty($datos['hora_salida']) ? $datos['hora_salida'] : null,
            'hora_llegada' => !empty($datos['hora_llegada']) ? $datos['hora_llegada'] : null,
            'id_guia' => !empty($datos['id_guia']) ? $datos['id_guia'] : null,
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
 * Crear un nuevo tour con todos los campos obligatorios
 */
function crearTourCompleto($datos) {
    try {
        $pdo = getToursDatabase();
        $pdo->beginTransaction();
        
        // Validar campos obligatorios
        $campos_obligatorios = [
            'titulo' => 'Título',
            'descripcion' => 'Descripción',
            'precio' => 'Precio',
            'duracion' => 'Duración',
            'region_id' => 'Región',
            'dificultad' => 'Dificultad',
            'capacidad_maxima' => 'Capacidad máxima',
            'incluye' => 'Incluye',
            'no_incluye' => 'No incluye',
            'lugar_salida' => 'Lugar de salida',
            'ubicacion' => 'Ubicación'
        ];
        
        foreach ($campos_obligatorios as $campo => $nombre) {
            if (empty($datos[$campo])) {
                throw new Exception("El campo '$nombre' es obligatorio");
            }
        }
        
        // Validar datos específicos
        if (!is_numeric($datos['precio']) || $datos['precio'] <= 0) {
            throw new Exception("El precio debe ser un número positivo");
        }
        
        if (!is_numeric($datos['capacidad_maxima']) || $datos['capacidad_maxima'] <= 0) {
            throw new Exception("La capacidad máxima debe ser un número positivo");
        }
        
        // Procesar imagen si existe
        $imagen_principal = null;
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
            $resultado_imagen = procesarImagenTour($_FILES['imagen_principal']);
            if ($resultado_imagen['success']) {
                $imagen_principal = $resultado_imagen['ruta'];
            } else {
                throw new Exception($resultado_imagen['message']);
            }
        }
        
        // Preparar datos para inserción
        $sql = "
            INSERT INTO tours (
                titulo, descripcion, precio, duracion, region_id, dificultad,
                capacidad_maxima, incluye, no_incluye, lugar_salida, ubicacion,
                imagen_principal, guia_id, lugar_llegada, hora_salida, hora_llegada,
                recomendaciones, que_llevar, politicas, activo, fecha_creacion
            ) VALUES (
                :titulo, :descripcion, :precio, :duracion, :region_id, :dificultad,
                :capacidad_maxima, :incluye, :no_incluye, :lugar_salida, :ubicacion,
                :imagen_principal, :guia_id, :lugar_llegada, :hora_salida, :hora_llegada,
                :recomendaciones, :que_llevar, :politicas, 1, NOW()
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        
        $params = [
            ':titulo' => trim($datos['titulo']),
            ':descripcion' => trim($datos['descripcion']),
            ':precio' => floatval($datos['precio']),
            ':duracion' => trim($datos['duracion']),
            ':region_id' => intval($datos['region_id']),
            ':dificultad' => trim($datos['dificultad']),
            ':capacidad_maxima' => intval($datos['capacidad_maxima']),
            ':incluye' => trim($datos['incluye']),
            ':no_incluye' => trim($datos['no_incluye']),
            ':lugar_salida' => trim($datos['lugar_salida']),
            ':ubicacion' => trim($datos['ubicacion']),
            ':imagen_principal' => $imagen_principal,
            ':guia_id' => !empty($datos['guia_id']) ? intval($datos['guia_id']) : null,
            ':lugar_llegada' => !empty($datos['lugar_llegada']) ? trim($datos['lugar_llegada']) : null,
            ':hora_salida' => !empty($datos['hora_salida']) ? trim($datos['hora_salida']) : null,
            ':hora_llegada' => !empty($datos['hora_llegada']) ? trim($datos['hora_llegada']) : null,
            ':recomendaciones' => !empty($datos['recomendaciones']) ? trim($datos['recomendaciones']) : null,
            ':que_llevar' => !empty($datos['que_llevar']) ? trim($datos['que_llevar']) : null,
            ':politicas' => !empty($datos['politicas']) ? trim($datos['politicas']) : null
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
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error creando tour: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Editar un tour existente
 */
function editarTour($id_tour, $datos) {
    try {
        $pdo = getToursDatabase();
        $pdo->beginTransaction();
        
        // Validar que el tour existe
        $tour_existente = obtenerTourPorId($id_tour);
        if (!$tour_existente['success']) {
            throw new Exception('Tour no encontrado');
        }
        
        // Validar campos obligatorios
        $campos_obligatorios = [
            'titulo' => 'Título',
            'descripcion' => 'Descripción',
            'precio' => 'Precio',
            'duracion' => 'Duración',
            'region_id' => 'Región',
            'dificultad' => 'Dificultad',
            'capacidad_maxima' => 'Capacidad máxima',
            'incluye' => 'Incluye',
            'lugar_salida' => 'Lugar de salida',
            'ubicacion' => 'Ubicación'
        ];
        
        foreach ($campos_obligatorios as $campo => $nombre) {
            if (empty($datos[$campo])) {
                throw new Exception("El campo '$nombre' es obligatorio");
            }
        }
        
        // Validar datos específicos
        if (!is_numeric($datos['precio']) || $datos['precio'] <= 0) {
            throw new Exception("El precio debe ser un número positivo");
        }
        
        if (!is_numeric($datos['capacidad_maxima']) || $datos['capacidad_maxima'] <= 0) {
            throw new Exception("La capacidad máxima debe ser un número positivo");
        }
        
        // Procesar imagen si existe
        $imagen_principal = $tour_existente['data']['imagen_principal']; // Mantener la existente
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
            $resultado_imagen = procesarImagenTour($_FILES['imagen_principal'], $id_tour);
            if ($resultado_imagen['success']) {
                $imagen_principal = $resultado_imagen['ruta'];
            } else {
                throw new Exception($resultado_imagen['message']);
            }
        }
        
        // Preparar datos para actualización
        $sql = "
            UPDATE tours SET
                titulo = :titulo,
                descripcion = :descripcion,
                precio = :precio,
                duracion = :duracion,
                region_id = :region_id,
                dificultad = :dificultad,
                capacidad_maxima = :capacidad_maxima,
                incluye = :incluye,
                no_incluye = :no_incluye,
                lugar_salida = :lugar_salida,
                ubicacion = :ubicacion,
                imagen_principal = :imagen_principal,
                guia_id = :guia_id,
                lugar_llegada = :lugar_llegada,
                hora_salida = :hora_salida,
                hora_llegada = :hora_llegada,
                recomendaciones = :recomendaciones,
                que_llevar = :que_llevar,
                politicas = :politicas,
                fecha_modificacion = NOW()
            WHERE id_tour = :id_tour
        ";
        
        $stmt = $pdo->prepare($sql);
        
        $params = [
            ':id_tour' => intval($id_tour),
            ':titulo' => trim($datos['titulo']),
            ':descripcion' => trim($datos['descripcion']),
            ':precio' => floatval($datos['precio']),
            ':duracion' => trim($datos['duracion']),
            ':region_id' => intval($datos['region_id']),
            ':dificultad' => trim($datos['dificultad']),
            ':capacidad_maxima' => intval($datos['capacidad_maxima']),
            ':incluye' => trim($datos['incluye']),
            ':no_incluye' => !empty($datos['no_incluye']) ? trim($datos['no_incluye']) : null,
            ':lugar_salida' => trim($datos['lugar_salida']),
            ':ubicacion' => trim($datos['ubicacion']),
            ':imagen_principal' => $imagen_principal,
            ':guia_id' => !empty($datos['guia_id']) ? intval($datos['guia_id']) : null,
            ':lugar_llegada' => !empty($datos['lugar_llegada']) ? trim($datos['lugar_llegada']) : null,
            ':hora_salida' => !empty($datos['hora_salida']) ? trim($datos['hora_salida']) : null,
            ':hora_llegada' => !empty($datos['hora_llegada']) ? trim($datos['hora_llegada']) : null,
            ':recomendaciones' => !empty($datos['recomendaciones']) ? trim($datos['recomendaciones']) : null,
            ':que_llevar' => !empty($datos['que_llevar']) ? trim($datos['que_llevar']) : null,
            ':politicas' => !empty($datos['politicas']) ? trim($datos['politicas']) : null
        ];
        
        $stmt->execute($params);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Tour actualizado exitosamente'
        ];
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error editando tour: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Cambiar estado de un tour (activo/inactivo)
 */
function cambiarEstadoTour($id_tour, $estado = 1) {
    try {
        $pdo = getToursDatabase();
        
        $sql = "UPDATE tours SET activo = :estado WHERE id_tour = :id_tour";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':estado' => intval($estado),
            ':id_tour' => intval($id_tour)
        ]);
        
        $estado_texto = $estado ? 'activado' : 'desactivado';
        
        return [
            'success' => true,
            'message' => "Tour $estado_texto exitosamente"
        ];
        
    } catch (Exception $e) {
        error_log("Error cambiando estado de tour: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al cambiar estado del tour'
        ];
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
