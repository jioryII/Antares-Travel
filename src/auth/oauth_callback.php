<?php
session_start();
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . '/../../vendor/autoload.php';

// Conexión a MySQLi
$mysqli = $conn; // Usamos la conexión de conexion.php

$provider = $_GET['provider'] ?? null;

if (!$provider) {
    die("❌ Proveedor no especificado.");
}

switch ($provider) {

    // ---------------- GOOGLE ----------------
    case "google":
        $client = new Google_Client();
        $client->setClientId("454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com");
        $client->setClientSecret("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");
        $client->setRedirectUri("https://antarestravelperu.com/src/auth/oauth_callback.php?provider=google");


        $client->addScope("email");
        $client->addScope("profile");

        // Si es flujo OAuth tradicional (code)
        if (isset($_GET['code'])) {
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

            if (isset($token['error'])) {
                die("❌ Error al obtener token: " . $token['error_description']);
            }

            $client->setAccessToken($token);

            $oauth2 = new Google_Service_Oauth2($client);
            $googleUser = $oauth2->userinfo->get();

            $email  = $mysqli->real_escape_string($googleUser->email);
            $nombre = $mysqli->real_escape_string($googleUser->name);
            $id_google = $googleUser->id;
            $avatar = $googleUser->picture ?? null;

            // Insertar o actualizar usuario
            $res = $mysqli->query("SELECT * FROM usuarios WHERE email='$email'");
            if ($res->num_rows === 0) {
                // Usuario nuevo: insertar todos los datos
                $mysqli->query("INSERT INTO usuarios (nombre, email, proveedor_oauth, id_proveedor, avatar_url, email_verificado) VALUES ('$nombre', '$email', 'google', '$id_google', '$avatar', 1)");
            } else {
                // Usuario existente: solo actualiza avatar si no existe
                $row = $res->fetch_assoc();
                $avatar_actual = $row['avatar_url'];
                if (empty($avatar_actual)) {
                    $mysqli->query("UPDATE usuarios SET nombre='$nombre', avatar_url='$avatar', proveedor_oauth='google', id_proveedor='$id_google', email_verificado=1 WHERE email='$email'");
                } else {
                    $mysqli->query("UPDATE usuarios SET nombre='$nombre', proveedor_oauth='google', id_proveedor='$id_google', email_verificado=1 WHERE email='$email'");
                }
            }

            // Guardar sesión
            $_SESSION['user_email']  = $email;
            $_SESSION['user_name']   = $nombre;
            $_SESSION['user_picture']= $avatar;

            header("Location: ./../../");
            exit;

        } else {
            // Redirigir a Google para solicitar código
            header("Location: " . $client->createAuthUrl());
            exit;
        }

    // ---------------- FACEBOOK ----------------
    case "facebook":
        echo "📘 Login con Facebook (pendiente de implementación)";
        break;

    // ---------------- APPLE ----------------
    case "apple":
        echo "🍎 Login con Apple (pendiente de implementación)";
        break;

    // ---------------- MICROSOFT ----------------
    case "microsoft":
        echo "🪟 Login con Microsoft (pendiente de implementación)";
        break;

    // ---------------- TELÉFONO ----------------
    case "telefono":
        echo "📱 Login con Teléfono (pendiente de implementación)";
        break;

    default:
        echo "❌ Proveedor no válido";
        exit;
}
