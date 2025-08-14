<?php
session_start();
require_once 'conexion.php';
require_once 'vendor/autoload.php'; // Google Client

// Configuración OAuth
$client = new Google_Client();
$client->setClientId('TU_CLIENT_ID.apps.googleusercontent.com');
$client->setClientSecret('TU_CLIENT_SECRET');
$client->setRedirectUri('http://localhost/Antares-Travel/login_google.php'); // Cambia según tu URL
$client->addScope("email");
$client->addScope("profile");

// 1️⃣ Si viene el código de Google, intercambiarlo por token
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $_SESSION['access_token'] = $token['access_token'];
        header('Location: login_google.php'); // redirige limpio
        exit;
    } else {
        echo "❌ Error en el token: " . $token['error'];
        exit;
    }
}

// 2️⃣ Si hay token en sesión, obtener info del usuario
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
    $oauth = new Google_Service_Oauth2($client);
    $google_user = $oauth->userinfo->get();

    // Datos del usuario
    $nombre = $google_user->name;
    $email = $google_user->email;
    $foto = $google_user->picture;
    $google_id = $google_user->id;

    // 3️⃣ Verificar si el usuario ya existe en DB
    $sql = "SELECT id_usuario FROM usuarios WHERE proveedor_login='google' AND id_proveedor='$google_id'";
    $result = ejecutarConsulta($conn, $sql);

    if ($result && $result->num_rows > 0) {
        // Usuario existe → inicio sesión automático
        $row = $result->fetch_assoc();
        $_SESSION['id_usuario'] = $row['id_usuario'];
        $_SESSION['nombre'] = $nombre;
        echo "✅ Bienvenido de nuevo, $nombre!";
    } else {
        // Usuario no existe → crear registro
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, foto_perfil, proveedor_login, id_proveedor) VALUES (?, ?, ?, 'google', ?)");
        $stmt->bind_param("ssss", $nombre, $email, $foto, $google_id);
        if ($stmt->execute()) {
            $_SESSION['id_usuario'] = $stmt->insert_id;
            $_SESSION['nombre'] = $nombre;
            echo "✅ Registro completado, bienvenido $nombre!";
        } else {
            echo "❌ Error al registrar usuario.";
        }
        $stmt->close();
    }

} else {
    // 4️⃣ No hay sesión → mostrar link de login Google
    $auth_url = $client->createAuthUrl();
    echo "<a href='$auth_url'>Iniciar sesión con Google</a>";
}
?>
