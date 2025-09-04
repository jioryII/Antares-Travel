<?php
// src/add_experiencia.php
session_start();
require_once 'config/conexion.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: auth/login.php");
    exit;
}

$user_email = $_SESSION['user_email'];
$user_query = "SELECT id_usuario FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$id_usuario = $user['id_usuario'] ?? null;

$comentario = $_POST['comentario'] ?? '';
$imagen_url = '';

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $file_type = $_FILES['foto']['type'];
    $file_size = $_FILES['foto']['size'];

    // Validate file type and size
    if (!in_array($file_type, $allowed_types)) {
        header("Location: ../index.php#experiencias?error=invalid_type");
        exit;
    }
    if ($file_size > $max_size) {
        header("Location: ../index.php#experiencias?error=too_large");
        exit;
    }

    $target_dir = "../Uploads/experiencias/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Sanitize file name
    $file_name = preg_replace("/[^A-Za-z0-9._-]/", '', basename($_FILES["foto"]["name"]));
    $target_file = $target_dir . time() . '_' . $file_name;

    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
        $imagen_url = "Uploads/experiencias/" . time() . '_' . $file_name;
    } else {
        header("Location: ../index.php#experiencias?error=upload_failed");
        exit;
    }
} elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Handle other upload errors
    header("Location: ../index.php#experiencias?error=upload_error");
    exit;
}

if ($id_usuario && ($comentario || $imagen_url)) {
    $insert = "INSERT INTO experiencias (imagen_url, comentario, id_usuario, fecha_publicacion) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert);
    $stmt->bind_param("ssi", $imagen_url, $comentario, $id_usuario);
    if ($stmt->execute()) {
        header("Location: ../index.php#experiencias?success=1");
    } else {
        header("Location: ../index.php#experiencias?error=db");
    }
} else {
    header("Location: ../index.php#experiencias?error=invalid");
}
exit;
