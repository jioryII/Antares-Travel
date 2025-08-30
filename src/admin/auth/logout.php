<?php
session_start();
require_once __DIR__ . '/middleware.php';

// Cerrar sesión admin
cerrarSesionAdmin();

// Redirigir al login
header('Location: login.php');
exit();
?>
