<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';
require_once '../../functions/admin_functions.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

// Parámetros de filtrado (los mismos que en index.php)
$buscar = $_GET['buscar'] ?? '';
$estado = $_GET['estado'] ?? '';
$formato = $_GET['formato'] ?? 'csv';

try {
    $connection = getConnection();
    
    // Construir consulta con los mismos filtros que index.php
    $where_conditions = [];
    $params = [];
    
    if ($buscar) {
        $where_conditions[] = "(g.nombre LIKE ? OR g.apellido LIKE ? OR g.email LIKE ? OR g.telefono LIKE ?)";
        $search_term = "%$buscar%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($estado) {
        $where_conditions[] = "g.estado = ?";
        $params[] = $estado;
    }
    
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Obtener datos de guías (sin estadísticas de reservas)
    $guias_sql = "SELECT g.*,
                         0 as total_tours,
                         0 as tours_confirmados,
                         0 as tours_completados,
                         0 as ingresos_generados
                  FROM guias g 
                  $where_clause 
                  ORDER BY g.nombre, g.apellido";
    
    $guias_stmt = $connection->prepare($guias_sql);
    $guias_stmt->execute($params);
    $guias = $guias_stmt->fetchAll();
    
    // Registrar actividad de exportación
    // registrarActividad($admin['id_administrador'], 'exportar', 'guias', null, 
    //                  "Exportó datos de guías (" . count($guias) . " registros)");
    
    if ($formato === 'csv') {
        // Exportar como CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="guias_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($output, [
            'ID',
            'Nombre',
            'Apellido',
            'Email',
            'Teléfono',
            'Estado',
            'Total Tours',
            'Tours Confirmados',
            'Tours Completados',
            'Ingresos Generados',
            'Experiencia',
            'Foto URL'
        ]);
        
        // Datos
        foreach ($guias as $guia) {
            fputcsv($output, [
                $guia['id_guia'],
                $guia['nombre'],
                $guia['apellido'],
                $guia['email'],
                $guia['telefono'] ?? '',
                $guia['estado'],
                $guia['total_tours'],
                $guia['tours_confirmados'],
                $guia['tours_completados'],
                number_format($guia['ingresos_generados'], 2),
                $guia['experiencia'] ?? '',
                $guia['foto_url'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
        
    } elseif ($formato === 'excel') {
        // Exportar como Excel (HTML table que Excel puede leer)
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="guias_' . date('Y-m-d_H-i-s') . '.xls"');
        
        echo '<table border="1">';
        echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
        echo '<th>ID</th>';
        echo '<th>Nombre</th>';
        echo '<th>Apellido</th>';
        echo '<th>Email</th>';
        echo '<th>Teléfono</th>';
        echo '<th>Estado</th>';
        echo '<th>Total Tours</th>';
        echo '<th>Tours Confirmados</th>';
        echo '<th>Tours Completados</th>';
        echo '<th>Ingresos Generados</th>';
        echo '<th>Experiencia</th>';
        echo '<th>Foto URL</th>';
        echo '</tr>';
        
        foreach ($guias as $guia) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($guia['id_guia']) . '</td>';
            echo '<td>' . htmlspecialchars($guia['nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($guia['apellido']) . '</td>';
            echo '<td>' . htmlspecialchars($guia['email']) . '</td>';
            echo '<td>' . htmlspecialchars($guia['telefono'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($guia['estado']) . '</td>';
            echo '<td>' . htmlspecialchars($guia['total_tours']) . '</td>';
            echo '<td>' . htmlspecialchars($guia['tours_confirmados']) . '</td>';
            echo '<td>' . htmlspecialchars($guia['tours_completados']) . '</td>';
            echo '<td>' . number_format($guia['ingresos_generados'], 2) . '</td>';
            echo '<td>' . htmlspecialchars($guia['experiencia'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($guia['foto_url'] ?? '') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
        
    } else {
        throw new Exception('Formato de exportación no válido');
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode("Error al exportar: " . $e->getMessage()));
    exit;
}
?>
