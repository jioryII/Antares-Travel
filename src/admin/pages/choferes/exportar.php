<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

try {
    $connection = getConnection();
    
    // Obtener parámetros de filtrado desde la URL
    $buscar = $_GET['buscar'] ?? '';
    
    // Construir consulta con los mismos filtros que index.php
    $where_conditions = [];
    $params = [];
    
    if ($buscar) {
        $where_conditions[] = "(c.nombre LIKE ? OR c.apellido LIKE ? OR c.telefono LIKE ? OR c.licencia LIKE ?)";
        $search_term = "%$buscar%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Obtener choferes con estadísticas para exportar
    $choferes_sql = "SELECT c.*,
                            COALESCE(vehiculos_stats.total_vehiculos, 0) as total_vehiculos,
                            COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados,
                            COALESCE(tours_stats.total_tours, 0) as total_tours,
                            COALESCE(tours_stats.tours_proximos, 0) as tours_proximos
                     FROM choferes c 
                     LEFT JOIN (
                         SELECT id_chofer, COUNT(*) as total_vehiculos
                         FROM vehiculos 
                         WHERE id_chofer IS NOT NULL
                         GROUP BY id_chofer
                     ) vehiculos_stats ON c.id_chofer = vehiculos_stats.id_chofer
                     LEFT JOIN (
                         SELECT v.id_chofer,
                                SUM(CASE WHEN dv.estado = 'Ocupado' AND dv.fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados
                         FROM vehiculos v
                         LEFT JOIN disponibilidad_vehiculos dv ON v.id_vehiculo = dv.id_vehiculo
                         WHERE v.id_chofer IS NOT NULL
                         GROUP BY v.id_chofer
                     ) disponibilidad_stats ON c.id_chofer = disponibilidad_stats.id_chofer
                     LEFT JOIN (
                         SELECT v.id_chofer,
                                COUNT(td.id_tour_diario) as total_tours,
                                SUM(CASE WHEN td.fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos
                         FROM vehiculos v
                         LEFT JOIN tours_diarios td ON v.id_vehiculo = td.id_vehiculo
                         WHERE v.id_chofer IS NOT NULL
                         GROUP BY v.id_chofer
                     ) tours_stats ON c.id_chofer = tours_stats.id_chofer
                     $where_clause 
                     ORDER BY c.nombre, c.apellido";
    
    $choferes_stmt = $connection->prepare($choferes_sql);
    $choferes_stmt->execute($params);
    $choferes = $choferes_stmt->fetchAll();
    
    // Configurar headers para descarga de CSV
    $filename = 'choferes_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    // Crear el archivo CSV
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados del CSV
    $headers = [
        'ID',
        'Nombre',
        'Apellido',
        'Nombre Completo',
        'Teléfono',
        'Licencia',
        'Vehículos Asignados',
        'Tours Totales',
        'Tours Próximos',
        'Días Ocupados',
        'Estado'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Datos de los choferes
    foreach ($choferes as $chofer) {
        $nombre_completo = trim($chofer['nombre'] . ' ' . ($chofer['apellido'] ?? ''));
        $estado = ($chofer['total_vehiculos'] > 0) ? 'Con vehículos' : 'Sin vehículos';
        
        $row = [
            $chofer['id_chofer'],
            $chofer['nombre'],
            $chofer['apellido'] ?? '',
            $nombre_completo,
            $chofer['telefono'] ?? '',
            $chofer['licencia'] ?? '',
            $chofer['total_vehiculos'],
            $chofer['total_tours'],
            $chofer['tours_proximos'],
            $chofer['dias_ocupados'],
            $estado
        ];
        
        fputcsv($output, $row, ';');
    }
    
    // Agregar estadísticas al final
    fputcsv($output, [], ';'); // Línea vacía
    fputcsv($output, ['ESTADÍSTICAS GENERALES'], ';');
    fputcsv($output, ['Total de choferes', count($choferes)], ';');
    fputcsv($output, ['Choferes con vehículos', array_sum(array_map(function($c) { return $c['total_vehiculos'] > 0 ? 1 : 0; }, $choferes))], ';');
    fputcsv($output, ['Total vehículos asignados', array_sum(array_column($choferes, 'total_vehiculos'))], ';');
    fputcsv($output, ['Total tours en sistema', array_sum(array_column($choferes, 'total_tours'))], ';');
    fputcsv($output, ['Tours próximos total', array_sum(array_column($choferes, 'tours_proximos'))], ';');
    fputcsv($output, ['Fecha de exportación', date('Y-m-d H:i:s')], ';');
    fputcsv($output, ['Exportado por', $admin['nombre']], ';');
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode('Error al exportar: ' . $e->getMessage()));
    exit;
}
?>
