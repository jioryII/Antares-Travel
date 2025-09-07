<?php
session_start();
require_once 'config/conexion.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: auth/login.php");
    exit;
}

// Obtener id_usuario (como arriba)
$user_email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$id_usuario = $user['id_usuario'] ?? 0;

$cart = $_SESSION['cart'] ?? [];
if (empty($cart['tours'])) {
    header("Location: carrito.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Observaciones de POST
    $observaciones = $_POST['observaciones'] ?? '';

    $conn->begin_transaction();
    try {
        $ids_reservas = []; // Array para boletas múltiples
        $monto_total_general = 0;

        foreach ($cart['tours'] as $tour) {
            $num_pasajeros = count($tour['pasajeros_asignados']); // De sesión
            if ($num_pasajeros == 0) throw new Exception('Al menos 1 pasajero por tour');

            $monto_total = $tour['precio'] * $num_pasajeros * $tour['cantidad']; // Ajusta por tipo si descuento
            $monto_total_general += $monto_total;

            // Insert reserva
            $insert_reserva = "INSERT INTO reservas (id_usuario, id_tour, fecha_tour, monto_total, observaciones, estado) VALUES (?, ?, ?, ?, ?, 'Pendiente')";
            $stmt = $conn->prepare($insert_reserva);
            $stmt->bind_param("iisds", $id_usuario, $tour['id_tour'], $tour['fecha_tour'], $monto_total, $observaciones);
            $stmt->execute();
            $id_reserva = $conn->insert_id;
            $ids_reservas[] = $id_reserva;

            // Insert pasajeros asignados (de globales, filtra por asignados)
            foreach ($tour['pasajeros_asignados'] as $p_id) {
                // Obtén datos pasajero de sesión $cart['pasajeros_globales'][$p_id]
                $pasajero = $cart['pasajeros_globales'][$p_id] ?? [];
                if (!empty($pasajero)) {
                    $insert_pasajero = "INSERT INTO pasajeros (id_reserva, nombre, apellido, dni_pasaporte, nacionalidad, telefono, tipo_pasajero) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt2 = $conn->prepare($insert_pasajero);
                    $stmt2->bind_param("issssss", $id_reserva, $pasajero['nombre'], $pasajero['apellido'], $pasajero['dni'], $pasajero['nacionalidad'], $pasajero['telefono'], $pasajero['tipo']);
                    $stmt2->execute();
                }
            }

            // Actualizar disponibilidad (simplificado: inserta en disponibilidad si necesario)
            // Consulta tours_diarios para asignar guia/vehiculo, o set 'Ocupado'
        }

        // Insert pago general si múltiples
        if (!empty($ids_reservas)) {
            $insert_pago = "INSERT INTO pagos (id_reserva, monto, estado_pago) VALUES (?, ?, 'Pendiente')"; // Usa primer id_reserva o crea uno general
            $stmt = $conn->prepare($insert_pago);
            $stmt->bind_param("id", $ids_reservas[0], $monto_total_general); // Ajusta si pagos por reserva
            $stmt->execute();
        }

        $conn->commit();
        unset($_SESSION['cart']); // Limpiar

        // Generar unique_id si no existe
        $unique_id_query = "SELECT unique_id FROM usuarios WHERE id_usuario = ?";
        $stmt = $conn->prepare($unique_id_query);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $unique_result = $stmt->get_result();
        $unique_id = $unique_result->fetch_assoc()['unique_id'] ?? null;
        if (!$unique_id) {
            $unique_id = substr(md5($id_usuario . time() . 'salt'), 0, 16);
            $update_unique = "UPDATE usuarios SET unique_id = ? WHERE id_usuario = ?";
            $stmt = $conn->prepare($update_unique);
            $stmt->bind_param("si", $unique_id, $id_usuario);
            $stmt->execute();
        }

        // Redirigir a boleta con unique_id
        $boleta_url = "boleta.php?unique_id=" . $unique_id . "&ids=" . implode(',', $ids_reservas);
        header("Location: $boleta_url");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// Mostrar resumen (HTML similar a boleta.php, con formulario POST para observaciones y botón confirmar)
?>
<!-- HTML: Lista tours, pasajeros, total, <form method="POST"><textarea name="observaciones"></textarea><button>Confirmar</button></form> -->
<!-- Copia estructura de boleta.php, agrega formulario -->