<?php
require_once '../../config/config.php';
require_once '../../auth/middleware.php';

// Verificar sesión de administrador
verificarSesionAdmin();

// Parámetros de filtrado (heredados de la página principal)
$buscar = $_GET['buscar'] ?? '';
$estado = $_GET['estado'] ?? '';
$tipo_oferta = $_GET['tipo_oferta'] ?? '';

try {
    $connection = getConnection();
    
    // Construir consulta con filtros
    $where_conditions = [];
    $params = [];
    
    if ($buscar) {
        $where_conditions[] = "(o.nombre LIKE ? OR o.descripcion LIKE ? OR o.codigo_promocional LIKE ?)";
        $search_term = "%$buscar%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($estado) {
        $where_conditions[] = "o.estado = ?";
        $params[] = $estado;
    }
    
    if ($tipo_oferta) {
        $where_conditions[] = "o.tipo_oferta = ?";
        $params[] = $tipo_oferta;
    }
    
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Obtener ofertas para exportar
    $ofertas_sql = "SELECT o.*,
                           CONCAT(a.nombre) as creado_por_nombre,
                           COALESCE(uso_stats.total_usos, 0) as total_usos,
                           COALESCE(uso_stats.total_descuento, 0) as total_descuento,
                           COALESCE(tours_stats.tours_aplicables, 0) as tours_aplicables,
                           COALESCE(usuarios_stats.usuarios_aplicables, 0) as usuarios_aplicables
                    FROM ofertas o 
                    LEFT JOIN administradores a ON o.creado_por = a.id_admin
                    LEFT JOIN (
                        SELECT id_oferta, 
                               COUNT(*) as total_usos,
                               SUM(monto_descuento) as total_descuento
                        FROM historial_uso_ofertas 
                        GROUP BY id_oferta
                    ) uso_stats ON o.id_oferta = uso_stats.id_oferta
                    LEFT JOIN (
                        SELECT id_oferta, COUNT(*) as tours_aplicables
                        FROM ofertas_tours 
                        GROUP BY id_oferta
                    ) tours_stats ON o.id_oferta = tours_stats.id_oferta
                    LEFT JOIN (
                        SELECT id_oferta, COUNT(*) as usuarios_aplicables
                        FROM ofertas_usuarios 
                        GROUP BY id_oferta
                    ) usuarios_stats ON o.id_oferta = usuarios_stats.id_oferta
                    $where_clause 
                    ORDER BY o.fecha_inicio DESC";
    
    $ofertas_stmt = $connection->prepare($ofertas_sql);
    $ofertas_stmt->execute($params);
    $ofertas = $ofertas_stmt->fetchAll();
    
    // Configurar headers para descarga CSV
    $filename = 'ofertas_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Crear el archivo CSV
    $output = fopen('php://output', 'w');
    
    // Escribir BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers del CSV
    $headers = [
        'ID',
        'Nombre',
        'Descripción',
        'Tipo de Oferta',
        'Estado',
        'Descuento/Valor',
        'Precio Especial',
        'Fecha Inicio',
        'Fecha Fin',
        'Código Promocional',
        'Aplicable A',
        'Límite Total',
        'Límite por Usuario',
        'Monto Mínimo',
        'Visible Públicamente',
        'Destacada',
        'Total Usos',
        'Total Ahorrado',
        'Tours Aplicables',
        'Usuarios Específicos',
        'Mensaje Promocional',
        'Términos y Condiciones',
        'Creado Por',
        'Fecha Creación',
        'Última Actualización'
    ];
    
    fputcsv($output, $headers);
    
    // Escribir datos
    foreach ($ofertas as $oferta) {
        $row = [
            $oferta['id_oferta'],
            $oferta['nombre'],
            $oferta['descripcion'],
            str_replace('_', ' ', $oferta['tipo_oferta']),
            $oferta['estado'],
            $oferta['valor_descuento'] ?? '',
            $oferta['precio_especial'] ?? '',
            date('d/m/Y H:i', strtotime($oferta['fecha_inicio'])),
            date('d/m/Y H:i', strtotime($oferta['fecha_fin'])),
            $oferta['codigo_promocional'] ?? '',
            str_replace('_', ' ', $oferta['aplicable_a']),
            $oferta['limite_usos'] ?? 'Sin límite',
            $oferta['limite_por_usuario'],
            $oferta['monto_minimo'] ? 'S/ ' . number_format($oferta['monto_minimo'], 2) : '',
            $oferta['visible_publica'] ? 'Sí' : 'No',
            $oferta['destacada'] ? 'Sí' : 'No',
            $oferta['total_usos'],
            'S/ ' . number_format($oferta['total_descuento'], 2),
            $oferta['tours_aplicables'],
            $oferta['usuarios_aplicables'],
            $oferta['mensaje_promocional'] ?? '',
            $oferta['terminos_condiciones'] ?? '',
            $oferta['creado_por_nombre'] ?? '',
            date('d/m/Y H:i', strtotime($oferta['creado_en'])),
            date('d/m/Y H:i', strtotime($oferta['actualizado_en']))
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    // En caso de error, redirigir de vuelta
    header("Location: index.php?error=" . urlencode("Error al exportar: " . $e->getMessage()));
    exit;
}
?>
