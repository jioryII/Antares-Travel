<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php'; 

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'recent';

$orderBy = "c.fecha_creacion DESC"; 

if ($filter === 'highest') {
    $orderBy = "c.calificacion DESC, c.fecha_creacion DESC";
} elseif ($filter === 'lowest') {
    $orderBy = "c.calificacion ASC, c.fecha_creacion DESC";
}

$sql = "SELECT c.calificacion, c.comentario, u.nombre, u.avatar_url 
        FROM comentarios c
        JOIN usuarios u ON c.id_usuario = u.id_usuario
        ORDER BY $orderBy
        LIMIT 20"; 

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($comments as &$comment) {
        if ($comment['avatar_url'] && !filter_var($comment['avatar_url'], FILTER_VALIDATE_URL)) {
             $comment['avatar_url'] = 'http://localhost/Antares-Travel/' . $comment['avatar_url'];
        }
    }

    echo json_encode($comments);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed']);
}