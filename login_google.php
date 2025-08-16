<?php
session_start();
require_once 'src/config/conexion.php';
require_once __DIR__ . '/vendor/autoload.php'; // Ruta segura a autoload.php

// ===============================
// CONFIGURACIÓN OAUTH DE GOOGLE
// ===============================
$client = new Google_Client();
$client->setClientId('454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/Antares-Travel/login_google.php'); // Debe coincidir con Google Cloud
$client->addScope("email");
$client->addScope("profile");

// =================================
// 1️⃣ Intercambiar código por token
// =================================
if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);
            $_SESSION['access_token'] = $token['access_token'];

            // Redirigir limpio (sin ?code en la URL)
            header('Location: login_google.php');
            exit;
        } else {
            throw new Exception("Error en el token: " . $token['error']);
        }
    } catch (Exception $e) {
        echo "❌ " . $e->getMessage();
        exit;
    }
}

// =================================
// 2️⃣ Ya hay token → obtener usuario
// =================================
if (!empty($_SESSION['access_token'])) {
    $client->setAccessToken($_SESSION['access_token']);
    $oauth = new Google_Service_Oauth2($client);
    $google_user = $oauth->userinfo->get();

    // Datos del usuario desde Google
    $nombre     = $google_user->name;
    $email      = $google_user->email;
    $foto       = $google_user->picture;
    $google_id  = $google_user->id;

    // =================================
    // 3️⃣ Verificar si ya existe en DB
    // =================================
    $sql = "SELECT id_usuario FROM usuarios 
            WHERE proveedor_oauth='google' AND id_proveedor=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $google_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        // Usuario ya registrado → login
        $row = $result->fetch_assoc();
        $_SESSION['id_usuario'] = $row['id_usuario'];
        $_SESSION['nombre']     = $nombre;

        echo "✅ Bienvenido de nuevo, $nombre!";
    } else {
        // =================================
        // 4️⃣ Usuario nuevo → registrarlo
        // =================================
        $insert = $conn->prepare("INSERT INTO usuarios 
            (nombre, email, avatar_url, proveedor_oauth, id_proveedor) 
            VALUES (?, ?, ?, 'google', ?)");
        $insert->bind_param("ssss", $nombre, $email, $foto, $google_id);

        if ($insert->execute()) {
            $_SESSION['id_usuario'] = $insert->insert_id;
            $_SESSION['nombre']     = $nombre;
            echo "✅ Registro completado, bienvenido $nombre!";
        } else {
            echo "❌ Error al registrar usuario: " . $conn->error;
        }
        $insert->close();
    }

    $stmt->close();

} else {
    // =================================
    // 5️⃣ No hay sesión → mostrar link
    // =================================
    $auth_url = $client->createAuthUrl();
    echo "<a href='" . htmlspecialchars($auth_url) . "'>Iniciar sesión con Google</a>";
}
