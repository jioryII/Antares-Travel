<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

$sql = "SELECT e.comentario, u.nombre, u.avatar_url
        FROM experiencias e
        JOIN usuarios u ON e.id_usuario = u.id_usuario
        ORDER BY e.fecha_publicacion DESC
        LIMIT 6";

$result = $conn->query($sql);
$experiencias = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['avatar_url'] = !empty($row['avatar_url']) ? "/Antares-Travel/" . $row['avatar_url'] : "/Antares-Travel/storage/uploads/avatars/default.png";
        $experiencias[] = $row;
    }
}

echo json_encode($experiencias);

$conn->close();
?>