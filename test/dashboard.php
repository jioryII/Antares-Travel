<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
</head>
<body>
  <h2>Bienvenido, <?php echo $_SESSION['user_name']; ?> 🎉</h2>
  <p>Tu correo: <?php echo $_SESSION['user_email']; ?></p>
  <a href="logout.php">Cerrar sesión</a>
</body>
</html>
