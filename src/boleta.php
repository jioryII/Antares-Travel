<?php
// boleta.php
require_once 'config/conexion.php';

$unique_id = $_GET['unique_id'] ?? '';
$ids_reservas = explode(',', $_GET['ids'] ?? $_GET['id_reserva'] ?? ''); // Soporta single o multiple

if (!empty($unique_id)) {
    // Consulta por unique_id
    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE unique_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!$user) die('Unique ID inválido');
    $id_usuario = $user['id_usuario'];

    // Obtener reservas del usuario
    $reserva_query = "SELECT * FROM reservas WHERE id_usuario = ? ORDER BY fecha_reserva DESC";
    $stmt = $conn->prepare($reserva_query);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $reserva_result = $stmt->get_result();
    // Loop para mostrar múltiples
} elseif (!empty($ids_reservas)) {
    // Para múltiples de carrito
    foreach ($ids_reservas as $id) {
        // Consulta individual y muestra en loop
    }
} else {
    // Lógica original single id_reserva
}

// En HTML, loop para mostrar cada reserva/pasajeros
// Agrega botón WhatsApp con unique_id
$wa_message = urlencode("Tu boleta: http://" . $_SERVER['HTTP_HOST'] . "/boleta.php?unique_id=" . $unique_id . " - Total: S/ " . $total_general);
echo '<a href="https://wa.me/[telefono]?text=' . $wa_message . '">Compartir WhatsApp</a>'; // Usa telefono de usuario o primer pasajero

// Para PDF: Usa una librería como mPDF (instala via composer), o botón print existente.
?>
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Reserva - Antares Travel Peru</title>
    <link rel="icon" type="image/png" href="../imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #FFFAF0;
            --primary-color: #A27741;
            --primary-dark: #8B6332;
            --primary-light: #B8926A;
            --secondary-color: #5B797C;
            --text-dark: #2c2c2c;
            --text-light: #666;
            --white: #ffffff;
            --transition: all 0.3s ease;
            --shadow: 0 8px 24px rgba(162, 119, 65, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--primary-bg);
            padding: 20px;
        }

        .boleta-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            padding: 40px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            border: 1px solid var(--primary-light);
        }

        .boleta-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .boleta-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .boleta-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .boleta-info p {
            margin: 0;
            font-size: 1rem;
        }

        .boleta-info strong {
            color: var(--primary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            color: var(--primary-color);
        }

        .total {
            text-align: right;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .observaciones {
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .print-btn {
            display: block;
            width: 200px;
            margin: 20px auto 0;
            padding: 10px;
            background: var(--primary-color);
            color: var(--white);
            text-align: center;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .print-btn:hover {
            background: var(--primary-dark);
        }

        @media print {
            .print-btn {
                display: none;
            }
            .boleta-container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="boleta-container">
        <div class="boleta-header">
            <h1>Boleta de Reserva - Antares Travel Peru</h1>
            <p>ID Reserva: <?php echo $reserva['id_reserva']; ?></p>
        </div>

        <div class="boleta-info">
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($reserva['user_name']); ?></p>
            <p><strong>Tour:</strong> <?php echo htmlspecialchars($reserva['tour_titulo']); ?></p>
            <p><strong>Fecha del Tour:</strong> <?php echo $reserva['fecha_tour']; ?></p>
            <p><strong>Monto Total:</strong> S/ <?php echo number_format($reserva['monto_total'], 2); ?></p>
        </div>

        <h2>Pasajeros</h2>
        <table>
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>DNI/Pasaporte</th>
                    <th>Nacionalidad</th>
                    <th>Teléfono</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pasajeros as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($p['dni_pasaporte']); ?></td>
                        <td><?php echo htmlspecialchars($p['nacionalidad'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($p['telefono'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($p['tipo_pasajero']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="observaciones">
            <strong>Observaciones:</strong> <?php echo htmlspecialchars($reserva['observaciones'] ?? 'Ninguna'); ?>
        </div>

        <a href="javascript:window.print()" class="print-btn">Imprimir Boleta</a>
    </div>
</body>
</html>