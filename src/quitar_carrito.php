<?php
session_start();
if (!isset($_SESSION['user_email']) || $_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$action = $_POST['action'] ?? '';
if ($action === 'quitar_tour') {
    $index = intval($_POST['index'] ?? -1);
    if ($index >= 0 && isset($_SESSION['cart']['tours'][$index])) {
        unset($_SESSION['cart']['tours'][$index]);
        $_SESSION['cart']['tours'] = array_values($_SESSION['cart']['tours']);
        $_SESSION['cart']['total_paquetes'] = count($_SESSION['cart']['tours']);
    }
}
// Similar para quitar pasajero global
header('Location: carrito.php');
exit;
?>