<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/usuarios.php';
require_once __DIR__ . '/../../vendor/autoload.php';

function getGoogleClient(): Google_Client {
    $client = new Google_Client();
    $client->setClientId('454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com');
    return $client;
}

function procesarGoogleCredential($credential, $conn, $client): bool {
    $payload = $client->verifyIdToken($credential);
    if (!$payload) return false;

    $google_id = $payload['sub'];
    $name      = $payload['name'] ?? '';
    $email     = $payload['email'] ?? '';
    $picture   = $payload['picture'] ?? '';

    $_SESSION['user_name']    = $name;
    $_SESSION['user_email']   = $email;
    $_SESSION['user_picture'] = $picture;

    $idUsuario = insertarUsuario($conn, $name, $email, null, 'google', $google_id, $picture);

    if ($idUsuario === "duplicado") {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, avatar_url=? WHERE email=?");
        $stmt->bind_param("sss", $name, $picture, $email);
        $stmt->execute();
    }

    return true;
}
