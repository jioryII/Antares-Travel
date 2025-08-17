<?php
require_once __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/Antares-Travel/login_google.php');
$client->addScope("email");
$client->addScope("profile");

if (!isset($_GET['code'])) {
    // Paso 1: mostrar el link de login
    $authUrl = $client->createAuthUrl();
    echo "<a href='" . htmlspecialchars($authUrl) . "'>Iniciar sesión con Google</a>";
} else {
    // Paso 2: recibir el 'code' y obtener el token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        echo "Error al obtener el token: " . $token['error_description'];
        exit;
    }

    $client->setAccessToken($token);

    // Obtener datos del usuario
    $oauth2 = new Google\Service\Oauth2($client);
    $userinfo = $oauth2->userinfo->get();

    echo "<h1>Bienvenido, " . $userinfo->name . "</h1>";
    echo "<p>Email: " . $userinfo->email . "</p>";
    echo "<img src='" . $userinfo->picture . "'>";
}
?>
