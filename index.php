<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/Antares-Travel/login_google.php');
$client->addScope("email");
$client->addScope("profile");

$login_url = $client->createAuthUrl();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login con Google - Antares Travel</title>
</head>
<body>
  <h2>Bienvenido a Antares Travel</h2>
  <?php if (!isset($_SESSION['user_email'])): ?>
    <a href="<?php echo htmlspecialchars($login_url); ?>">
      <img src="https://developers.google.com/identity/images/btn_google_signin_dark_normal_web.png" 
           alt="Iniciar sesión con Google">
    </a>
  <?php else: ?>
    <p>Hola, <?php echo $_SESSION['user_name']; ?> (<?php echo $_SESSION['user_email']; ?>)</p>
    <a href="logout.php">Cerrar sesión</a>
  <?php endif; ?>
</body>
</html>
