<?php
// providers.php

// Configuración de proveedores activos
$providers = [
    "google" => [
        "name" => "Google",
        "auth_url" => "https://accounts.google.com/o/oauth2/auth",
        "client_id" => "454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com",
        "redirect_uri" => "http://localhost/Antares-Travel/login_google.phpp",
        "scope" => "email profile",
    ],
    "facebook" => [
        "name" => "Facebook",
        "auth_url" => "https://www.facebook.com/v12.0/dialog/oauth",
        "client_id" => "TU_CLIENT_ID_FACEBOOK",
        "redirect_uri" => "http://localhost/callback_facebook.php",
        "scope" => "email public_profile",
    ],
    "microsoft" => [
        "name" => "Microsoft",
        "auth_url" => "https://login.microsoftonline.com/common/oauth2/v2.0/authorize",
        "client_id" => "TU_CLIENT_ID_MICROSOFT",
        "redirect_uri" => "http://localhost/callback_microsoft.php",
        "scope" => "openid profile email",
    ],
    "apple" => [
        "name" => "Apple",
        "auth_url" => "https://appleid.apple.com/auth/authorize",
        "client_id" => "TU_CLIENT_ID_APPLE",
        "redirect_uri" => "http://localhost/callback_apple.php",
        "scope" => "name email",
    ],
];

// Mostrar solo los que tienen CLIENT_ID configurado
echo "<h2>Proveedores disponibles para iniciar sesión:</h2>";
foreach ($providers as $key => $provider) {
    if (!empty($provider["client_id"])) {
        // Construcción de URL de login
        $params = http_build_query([
            "client_id" => $provider["client_id"],
            "redirect_uri" => $provider["redirect_uri"],
            "response_type" => "code",
            "scope" => $provider["scope"],
        ]);
        $login_url = $provider["auth_url"] . "?" . $params;

        echo "<p><a href='$login_url'>Iniciar sesión con {$provider['name']}</a></p>";
    }
}
