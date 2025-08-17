<?php
require_once __DIR__ . "/../config/conexion.php";
session_start();

$token = $_GET['token'] ?? '';
$mensaje = '';

if (!empty($token)) {
    // Buscar el token en la tabla y verificar que no esté expirado
    $sql = "SELECT u.* FROM email_verificacion v JOIN usuarios u ON v.id_usuario = u.id_usuario WHERE v.token = ? AND v.fecha_expiracion > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $id_usuario = $row['id_usuario'];

        // Marcar usuario como verificado
        $sqlUpdate = "UPDATE usuarios SET email_verificado = 1 WHERE id_usuario = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $id_usuario);
        if ($stmtUpdate->execute()) {
            // Eliminar el token usado
            $sqlDelete = "DELETE FROM email_verificacion WHERE id_usuario = ?";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bind_param("i", $id_usuario);
            $stmtDelete->execute();

            // Iniciar sesión automáticamente
            $_SESSION['id_usuario']   = $row['id_usuario'];
            $_SESSION['nombre']       = $row['nombre'];
            $_SESSION['email']        = $row['email'];
            $_SESSION['avatar_url']   = $row['avatar_url'];
            $_SESSION['email_verificado'] = 1;
            $_SESSION['user_email']   = $row['email'];
            $_SESSION['user_name']    = $row['nombre'];
            $_SESSION['user_picture'] = isset($row['avatar_url']) && $row['avatar_url'] 
                ? "http://localhost/Antares-Travel/" . $row['avatar_url'] 
                : "http://localhost/Antares-Travel/uploads/avatars/default.png";

            header("Location: ./../../index.php");
            exit;
        } else {
            $mensaje = [
                'tipo' => 'error',
                'texto' => '❌ Error al verificar el correo.'
            ];
        }
    } else {
        $mensaje = [
            'tipo' => 'error',
            'texto' => '❌ Token inválido o expirado.'
        ];
    }
} else {
    $mensaje = [
        'tipo' => 'error',
        'texto' => '❌ Parámetros inválidos.'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Email - Antares Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-400 to-cyan-300 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-md w-full">
        <h2 class="text-2xl font-bold text-center mb-6">Verificación de Email</h2>
        <?php if ($mensaje): ?>
            <div class="p-4 rounded-lg mb-6 text-center <?php 
                echo $mensaje['tipo'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; 
            ?>">
                <?php echo $mensaje['texto']; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>