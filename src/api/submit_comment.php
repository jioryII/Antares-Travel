<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para comentar.']);
    exit;
}

$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$userId = $_SESSION['user_id'];

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'La calificación no es válida.']);
    exit;
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío.']);
    exit;
}

$sql = "INSERT INTO comentarios (id_usuario, calificacion, comentario) VALUES (?, ?, ?)";
try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $userId, $rating, $comment);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to insert comment");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario.']);
}