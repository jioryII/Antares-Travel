<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';

// Verificar sesión de administrador
verificarSesionAdmin();
$admin = obtenerAdminActual();

try {
    // Obtener parámetros de filtro (mismos que en index.php)
    $filtro_nombre = $_GET['nombre'] ?? '';
    $filtro_email = $_GET['email'] ?? '';
    $filtro_verificado = $_GET['verificado'] ?? '';
    $filtro_proveedor = $_GET['proveedor'] ?? '';
    $formato = $_GET['formato'] ?? 'csv'; // csv o excel
    
    $connection = getConnection();
    
    // Construir WHERE clause para filtros
    $where_conditions = [];
    $params = [];
    
    if (!empty($filtro_nombre)) {
        $where_conditions[] = "u.nombre LIKE ?";
        $params[] = "%$filtro_nombre%";
    }
    
    if (!empty($filtro_email)) {
        $where_conditions[] = "u.email LIKE ?";
        $params[] = "%$filtro_email%";
    }
    
    if ($filtro_verificado !== '') {
        $where_conditions[] = "u.email_verificado = ?";
        $params[] = $filtro_verificado;
    }
    
    if (!empty($filtro_proveedor)) {
        $where_conditions[] = "u.proveedor_oauth = ?";
        $params[] = $filtro_proveedor;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Obtener usuarios con estadísticas (sin tabla resenas)
    $usuarios_sql = "SELECT u.id_usuario,
                            u.nombre,
                            u.email,
                            u.telefono,
                            u.fecha_nacimiento,
                            u.genero,
                            u.pais,
                            u.proveedor_oauth,
                            u.email_verificado,
                            u.creado_en,
                            u.actualizado_en,
                            u.ultima_actividad,
                            COUNT(r.id_reserva) as total_reservas,
                            COALESCE(SUM(r.monto_total), 0) as total_gastado,
                            0 as calificacion_promedio,
                            0 as total_resenas
                     FROM usuarios u
                     LEFT JOIN reservas r ON u.id_usuario = r.id_usuario
                     $where_clause
                     GROUP BY u.id_usuario
                     ORDER BY u.creado_en DESC";
    
    $usuarios_stmt = $connection->prepare($usuarios_sql);
    $usuarios_stmt->execute($params);
    $usuarios = $usuarios_stmt->fetchAll();
    
    // Generar nombre del archivo
    $fecha_actual = date('Y-m-d_H-i-s');
    $filtros_aplicados = '';
    if (!empty($filtro_nombre) || !empty($filtro_email) || $filtro_verificado !== '' || !empty($filtro_proveedor)) {
        $filtros_aplicados = '_filtrado';
    }
    
    $nombre_archivo = "usuarios_export_{$fecha_actual}{$filtros_aplicados}";
    
    if ($formato === 'excel' || $formato === 'xlsx') {
        exportarExcel($usuarios, $nombre_archivo);
    } else {
        exportarCSV($usuarios, $nombre_archivo);
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode('Error al exportar: ' . $e->getMessage()));
    exit;
}

function exportarCSV($usuarios, $nombre_archivo) {
    // Configurar headers para descarga CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Crear output
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para que Excel abra correctamente los caracteres especiales)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados de columnas
    $headers = [
        'ID Usuario',
        'Nombre',
        'Email',
        'Teléfono',
        'Fecha Nacimiento',
        'Género',
        'País',
        'Tipo Registro',
        'Email Verificado',
        'Total Reservas',
        'Total Gastado',
        'Total Reseñas',
        'Calificación Promedio',
        'Fecha Registro',
        'Última Actualización',
        'Última Actividad'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Datos de usuarios
    foreach ($usuarios as $usuario) {
        $fila = [
            $usuario['id_usuario'],
            $usuario['nombre'] ?? '',
            $usuario['email'],
            $usuario['telefono'] ?? '',
            $usuario['fecha_nacimiento'] ? date('d/m/Y', strtotime($usuario['fecha_nacimiento'])) : '',
            $usuario['genero'] ? ucfirst($usuario['genero']) : '',
            $usuario['pais'] ?? '',
            ucfirst($usuario['proveedor_oauth']),
            $usuario['email_verificado'] ? 'Sí' : 'No',
            $usuario['total_reservas'],
            number_format($usuario['total_gastado'], 2),
            $usuario['total_resenas'],
            $usuario['calificacion_promedio'] > 0 ? number_format($usuario['calificacion_promedio'], 1) : '',
            date('d/m/Y H:i', strtotime($usuario['creado_en'])),
            $usuario['actualizado_en'] ? date('d/m/Y H:i', strtotime($usuario['actualizado_en'])) : '',
            $usuario['ultima_actividad'] ? date('d/m/Y H:i', strtotime($usuario['ultima_actividad'])) : ''
        ];
        
        fputcsv($output, $fila, ';');
    }
    
    fclose($output);
    exit;
}

function exportarExcel($usuarios, $nombre_archivo) {
    // Para Excel necesitaríamos una librería como PhpSpreadsheet
    // Por ahora, exportamos como CSV con formato Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.xls"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // BOM para UTF-8
    echo chr(0xEF).chr(0xBB).chr(0xBF);
    
    // Crear tabla HTML que Excel puede interpretar
    echo "<table border='1'>\n";
    
    // Encabezados
    echo "<tr>\n";
    echo "<th>ID Usuario</th>\n";
    echo "<th>Nombre</th>\n";
    echo "<th>Email</th>\n";
    echo "<th>Teléfono</th>\n";
    echo "<th>Fecha Nacimiento</th>\n";
    echo "<th>Género</th>\n";
    echo "<th>País</th>\n";
    echo "<th>Tipo Registro</th>\n";
    echo "<th>Email Verificado</th>\n";
    echo "<th>Total Reservas</th>\n";
    echo "<th>Total Gastado</th>\n";
    echo "<th>Total Reseñas</th>\n";
    echo "<th>Calificación Promedio</th>\n";
    echo "<th>Fecha Registro</th>\n";
    echo "<th>Última Actualización</th>\n";
    echo "<th>Última Actividad</th>\n";
    echo "</tr>\n";
    
    // Datos
    foreach ($usuarios as $usuario) {
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($usuario['id_usuario']) . "</td>\n";
        echo "<td>" . htmlspecialchars($usuario['nombre'] ?? '') . "</td>\n";
        echo "<td>" . htmlspecialchars($usuario['email']) . "</td>\n";
        echo "<td>" . htmlspecialchars($usuario['telefono'] ?? '') . "</td>\n";
        echo "<td>" . ($usuario['fecha_nacimiento'] ? date('d/m/Y', strtotime($usuario['fecha_nacimiento'])) : '') . "</td>\n";
        echo "<td>" . ($usuario['genero'] ? ucfirst($usuario['genero']) : '') . "</td>\n";
        echo "<td>" . htmlspecialchars($usuario['pais'] ?? '') . "</td>\n";
        echo "<td>" . ucfirst($usuario['proveedor_oauth']) . "</td>\n";
        echo "<td>" . ($usuario['email_verificado'] ? 'Sí' : 'No') . "</td>\n";
        echo "<td>" . $usuario['total_reservas'] . "</td>\n";
        echo "<td>" . number_format($usuario['total_gastado'], 2) . "</td>\n";
        echo "<td>" . $usuario['total_resenas'] . "</td>\n";
        echo "<td>" . ($usuario['calificacion_promedio'] > 0 ? number_format($usuario['calificacion_promedio'], 1) : '') . "</td>\n";
        echo "<td>" . date('d/m/Y H:i', strtotime($usuario['creado_en'])) . "</td>\n";
        echo "<td>" . ($usuario['actualizado_en'] ? date('d/m/Y H:i', strtotime($usuario['actualizado_en'])) : '') . "</td>\n";
        echo "<td>" . ($usuario['ultima_actividad'] ? date('d/m/Y H:i', strtotime($usuario['ultima_actividad'])) : '') . "</td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    exit;
}
?>
