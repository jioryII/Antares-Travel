<?php
session_start();
if (!isset($_SESSION['user_email']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$action = $_POST['action'] ?? '';
$index = intval($_POST['index'] ?? -1);

if ($index < 0 || !isset($_SESSION['cart']['tours'][$index])) {
    echo json_encode(['success' => false]);
    exit;
}

switch ($action) {
    case 'update_cantidad':
        $delta = intval($_POST['delta'] ?? 0);
        $_SESSION['cart']['tours'][$index]['cantidad'] += $delta;
        if ($_SESSION['cart']['tours'][$index]['cantidad'] <= 0) {
            unset($_SESSION['cart']['tours'][$index]);
            $_SESSION['cart']['tours'] = array_values($_SESSION['cart']['tours']); // Reindex
        }
        break;
    case 'update_fecha':
        $_SESSION['cart']['tours'][$index]['fecha_tour'] = $_POST['fecha'] ?? '';
        break;
    // Case para pasajeros: e.g., 'asignar_pasajeros' con array POST
    default:
        echo json_encode(['success' => false]);
        exit;
}

$_SESSION['cart']['total_paquetes'] = count($_SESSION['cart']['tours']);
echo json_encode(['success' => true]);
?>