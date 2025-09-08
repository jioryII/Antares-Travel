<?php
// src/process_add_to_cart.php (completo)
session_start();
require_once __DIR__ . '/config/conexion.php';

if (!isset($_SESSION['user_email'])) {
    header('Location: auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tour = intval($_POST['id_tour']);
    $fecha = $_POST['fecha'];
    $adultos = intval($_POST['adultos']);
    $ninos = intval($_POST['ninos']);
    $infantes = intval($_POST['infantes']);

    if ($adultos < 1) {
        $_SESSION['error'] = 'Debe seleccionar al menos 1 adulto.';
        header('Location: detalles.php?id_tour=' . $id_tour);
        exit;
    }

    $query = "SELECT * FROM tours WHERE id_tour = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_tour);
    $stmt->execute();
    $tour = $stmt->get_result()->fetch_assoc();

    if (!$tour) {
        $_SESSION['error'] = 'Tour no encontrado.';
        header('Location: ../index.php');
        exit;
    }

    $precio_adulto = $tour['precio'];
    $precio_nino = $precio_adulto * 0.5;
    $precio_infante = 0;
    $subtotal = ($precio_adulto * $adultos) + ($precio_nino * $ninos) + ($precio_infante * $infantes);

    $existing_key = -1;
    if (isset($_SESSION['cart']['paquetes'])) {
        foreach ($_SESSION['cart']['paquetes'] as $key => $item) {
            if ($item['id_tour'] == $id_tour && $item['fecha'] == $fecha) {
                $existing_key = $key;
                break;
            }
        }
    }

    $item = [
        'id_tour' => $id_tour,
        'titulo' => $tour['titulo'],
        'precio' => $precio_adulto,
        'imagen' => $tour['imagen_principal'],
        'fecha' => $fecha,
        'adultos' => $adultos,
        'ninos' => $ninos,
        'infantes' => $infantes,
        'subtotal' => $subtotal
    ];

    if ($existing_key !== -1) {
        $_SESSION['cart']['paquetes'][$existing_key]['adultos'] += $adultos;
        $_SESSION['cart']['paquetes'][$existing_key]['ninos'] += $ninos;
        $_SESSION['cart']['paquetes'][$existing_key]['infantes'] += $infantes;
        $_SESSION['cart']['paquetes'][$existing_key]['subtotal'] += $subtotal;
    } else {
        $_SESSION['cart']['paquetes'][] = $item;
    }

    $_SESSION['cart']['total_paquetes'] = count($_SESSION['cart']['paquetes']);
    $_SESSION['success'] = 'Tour agregado al carrito.';

    header('Location: reserva.php');
    exit;
}
?>