<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

try {
    $connection = getConnection();
    
    // Obtener todos los vehículos con información completa
    $vehiculos_sql = "SELECT v.*,
                             CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellido, '')) as chofer_nombre,
                             c.telefono as chofer_telefono,
                             c.licencia as chofer_licencia,
                             COALESCE(tours_stats.total_tours, 0) as total_tours,
                             COALESCE(tours_stats.tours_proximos, 0) as tours_proximos,
                             COALESCE(tours_stats.tours_completados, 0) as tours_completados,
                             COALESCE(tours_stats.ultimo_tour, 'Nunca') as ultimo_tour,
                             COALESCE(disponibilidad_stats.dias_ocupados, 0) as dias_ocupados,
                             COALESCE(disponibilidad_stats.dias_libres, 0) as dias_libres
                      FROM vehiculos v
                      LEFT JOIN choferes c ON v.id_chofer = c.id_chofer
                      LEFT JOIN (
                          SELECT id_vehiculo,
                                 COUNT(*) as total_tours,
                                 SUM(CASE WHEN fecha >= CURDATE() THEN 1 ELSE 0 END) as tours_proximos,
                                 SUM(CASE WHEN fecha < CURDATE() THEN 1 ELSE 0 END) as tours_completados,
                                 MAX(fecha) as ultimo_tour
                          FROM tours_diarios
                          GROUP BY id_vehiculo
                      ) tours_stats ON v.id_vehiculo = tours_stats.id_vehiculo
                      LEFT JOIN (
                          SELECT id_vehiculo,
                                 SUM(CASE WHEN estado = 'Ocupado' AND fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_ocupados,
                                 SUM(CASE WHEN estado = 'Libre' AND fecha >= CURDATE() THEN 1 ELSE 0 END) as dias_libres
                          FROM disponibilidad_vehiculos
                          GROUP BY id_vehiculo
                      ) disponibilidad_stats ON v.id_vehiculo = disponibilidad_stats.id_vehiculo
                      ORDER BY v.marca, v.modelo";
    
    $vehiculos_stmt = $connection->prepare($vehiculos_sql);
    $vehiculos_stmt->execute();
    $vehiculos = $vehiculos_stmt->fetchAll();
    
    // Configurar headers para descarga CSV
    $filename = 'vehiculos_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Crear archivo CSV
    $output = fopen('php://output', 'w');
    
    // Agregar BOM para UTF-8 (para que Excel reconozca caracteres especiales)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados del CSV
    $headers = [
        'ID',
        'Marca',
        'Modelo', 
        'Placa',
        'Capacidad',
        'Características',
        'Chofer Asignado',
        'Teléfono Chofer',
        'Licencia Chofer',
        'Total Tours',
        'Tours Próximos',
        'Tours Completados',
        'Último Tour',
        'Días Ocupados',
        'Días Libres',
        'Estado'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Datos de los vehículos
    foreach ($vehiculos as $vehiculo) {
        // Determinar estado
        $estado = 'Sin chofer';
        if ($vehiculo['id_chofer']) {
            if ($vehiculo['tours_proximos'] > 0) {
                $estado = 'En uso';
            } else {
                $estado = 'Disponible';
            }
        }
        
        $row = [
            $vehiculo['id_vehiculo'],
            $vehiculo['marca'],
            $vehiculo['modelo'],
            $vehiculo['placa'],
            $vehiculo['capacidad'],
            $vehiculo['caracteristicas'] ?: '',
            trim($vehiculo['chofer_nombre']) ?: 'Sin asignar',
            $vehiculo['chofer_telefono'] ?: '',
            $vehiculo['chofer_licencia'] ?: '',
            $vehiculo['total_tours'],
            $vehiculo['tours_proximos'],
            $vehiculo['tours_completados'],
            $vehiculo['ultimo_tour'],
            $vehiculo['dias_ocupados'],
            $vehiculo['dias_libres'],
            $estado
        ];
        
        fputcsv($output, $row, ';');
    }
    
    // Agregar estadísticas al final
    fputcsv($output, [], ';'); // Línea vacía
    fputcsv($output, ['ESTADÍSTICAS GENERALES'], ';');
    fputcsv($output, ['Total de vehículos:', count($vehiculos)], ';');
    fputcsv($output, ['Con chofer asignado:', array_sum(array_map(function($v) { return $v['id_chofer'] ? 1 : 0; }, $vehiculos))], ';');
    fputcsv($output, ['Sin chofer:', array_sum(array_map(function($v) { return !$v['id_chofer'] ? 1 : 0; }, $vehiculos))], ';');
    fputcsv($output, ['Capacidad total:', array_sum(array_column($vehiculos, 'capacidad'))], ';');
    fputcsv($output, ['Tours activos hoy:', array_sum(array_column($vehiculos, 'tours_proximos'))], ';');
    fputcsv($output, [], ';'); // Línea vacía
    fputcsv($output, ['Exportado el:', date('d/m/Y H:i:s')], ';');
    fputcsv($output, ['Por:', $admin['nombre'] ?? 'Admin'], ';');
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    // En caso de error, redirigir al index con mensaje
    header('Location: index.php?error=' . urlencode('Error al exportar: ' . $e->getMessage()));
    exit;
}
?>
