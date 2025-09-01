<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_rol'] = 'admin';
$_SESSION['admin_id'] = 1;
$_GET['action'] = 'check_availability';
$_GET['fecha'] = '2025-08-27';

// Cambiar al directorio correcto
chdir(__DIR__ . '/pages/tours/');
include 'tours_diarios_ajax.php';
?>
