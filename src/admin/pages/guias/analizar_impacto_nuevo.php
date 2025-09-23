<?php
session_start();

// Configurar headers antes que cualquier output
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación básica
if (!isset($_SESSION['usuario_admin']) || empty($_SESSION['usuario_admin'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Incluir archivos después de verificar auth
require_once '../../../config/conexion.php';

if (!isset($_POST['id_guia']) || empty($_POST['id_guia'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de guía no proporcionado'
    ]);
    exit;
}

$id_guia = (int)$_POST['id_guia'];

// Verificar conexión de base de datos
if (!isset($conn) || !$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ]);
    exit;
}

try {
    // Verificar que el guía existe
    $stmt = $conn->prepare("SELECT nombre, apellidos FROM guias WHERE id_guia = ? AND activo = 1");
    $stmt->bind_param("i", $id_guia);
    $stmt->execute();
    $result = $stmt->get_result();
    $guia = $result->fetch_assoc();
    
    if (!$guia) {
        echo json_encode([
            'success' => false,
            'message' => 'Guía no encontrado o inactivo'
        ]);
        exit;
    }

    $analisis = [
        'tours' => [],
        'tours_diarios' => [],
        'disponibilidad' => [],
        'calificaciones' => []
    ];

    // 1. Analizar tours que tienen este guía asignado
    $stmt = $conn->prepare("
        SELECT DISTINCT t.id_tour, t.nombre, t.descripcion
        FROM tours t 
        WHERE t.id_guia = ?
        ORDER BY t.nombre
    ");
    $stmt->bind_param("i", $id_guia);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $analisis['tours'][] = $row;
    }

    // 2. Analizar tours diarios programados con este guía
    $stmt = $conn->prepare("
        SELECT td.id_tour_diario, td.fecha_tour, td.estado,
               t.nombre as tour_nombre,
               COUNT(r.id_reserva) as reservas_asociadas
        FROM tours_diarios td
        JOIN tours t ON td.id_tour = t.id_tour
        LEFT JOIN reservas r ON td.id_tour_diario = r.id_tour_diario
        WHERE td.id_guia = ?
        AND td.fecha_tour >= CURDATE()
        GROUP BY td.id_tour_diario, td.fecha_tour, td.estado, t.nombre
        ORDER BY td.fecha_tour ASC
        LIMIT 20
    ");
    $stmt->bind_param("i", $id_guia);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $analisis['tours_diarios'][] = $row;
    }

    // 3. Analizar disponibilidad del guía
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_registros
        FROM disponibilidad_guias 
        WHERE id_guia = ?
        AND fecha >= CURDATE()
    ");
    $stmt->bind_param("i", $id_guia);
    $stmt->execute();
    $result = $stmt->get_result();
    $disponibilidad_row = $result->fetch_assoc();
    $disponibilidad_count = $disponibilidad_row['total_registros'];
    
    if ($disponibilidad_count > 0) {
        $analisis['disponibilidad'] = [
            ['total' => $disponibilidad_count]
        ];
    }

    // 4. Analizar calificaciones y comentarios
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_calificaciones,
               AVG(calificacion) as promedio_calificacion
        FROM calificaciones 
        WHERE id_guia = ?
    ");
    $stmt->bind_param("i", $id_guia);
    $stmt->execute();
    $result = $stmt->get_result();
    $calificaciones_data = $result->fetch_assoc();
    
    if ($calificaciones_data && $calificaciones_data['total_calificaciones'] > 0) {
        $analisis['calificaciones'] = [
            [
                'total' => $calificaciones_data['total_calificaciones'],
                'promedio' => round($calificaciones_data['promedio_calificacion'], 2)
            ]
        ];
    }

    // 5. Verificar reservas futuras que podrían verse afectadas
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT r.id_reserva) as reservas_futuras
        FROM reservas r
        JOIN tours_diarios td ON r.id_tour_diario = td.id_tour_diario
        WHERE td.id_guia = ?
        AND td.fecha_tour > CURDATE()
        AND r.estado IN ('confirmada', 'pendiente')
    ");
    $stmt->bind_param("i", $id_guia);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservas_row = $result->fetch_assoc();
    $reservas_futuras = $reservas_row['reservas_futuras'];

    // Agregar información adicional al análisis
    $analisis['resumen'] = [
        'nombre_guia' => $guia['nombre'] . ' ' . $guia['apellidos'],
        'tours_afectados' => count($analisis['tours']),
        'tours_diarios_programados' => count($analisis['tours_diarios']),
        'disponibilidad_registros' => $disponibilidad_count,
        'calificaciones_total' => $calificaciones_data['total_calificaciones'] ?? 0,
        'reservas_futuras' => $reservas_futuras,
        'fecha_analisis' => date('Y-m-d H:i:s')
    ];

    // Log del análisis realizado
    error_log("Análisis de eliminación realizado para guía ID: $id_guia por usuario: " . ($_SESSION['usuario_admin']['nombre'] ?? 'Desconocido'));

    echo json_encode([
        'success' => true,
        'analisis' => $analisis
    ]);

} catch (Exception $e) {
    error_log("Error en análisis de impacto: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
