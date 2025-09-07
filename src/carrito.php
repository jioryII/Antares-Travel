<?php
session_start();
require_once 'config/conexion.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: auth/login.php");
    exit;
}

// Obtener id_usuario
$user_email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$id_usuario = $user['id_usuario'] ?? 0;

if (!$id_usuario) {
    header("Location: auth/login.php");
    exit;
}

// Carrito de sesión
$cart = $_SESSION['cart'] ?? ['tours' => [], 'pasajeros_globales' => [], 'total_paquetes' => 0];
$total_monto = 0;
foreach ($cart['tours'] as $tour) {
    $total_monto += $tour['precio'] * $tour['cantidad'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - Antares Travel Peru</title>
    <!-- Incluye tus CSS de index.php aquí (copia el <style> o link) -->
    <link rel="stylesheet" href="../styles.css"> <!-- Ajusta path -->
</head>
<body>
    <!-- Navbar de index.php aquí (copia para consistencia) -->

    <section class="section">
        <div class="container">
            <h2>Tu Carrito (<?php echo $cart['total_paquetes']; ?> paquetes)</h2>
            <?php if (empty($cart['tours'])): ?>
                <p>Carrito vacío. <a href="../index.php#tours">Agregar tours</a></p>
            <?php else: ?>
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr><th>Tour</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart['tours'] as $index => $tour): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tour['titulo']); ?></td>
                                <td>
                                    <button onclick="updateCantidad(<?php echo $index; ?>, -1)">-</button>
                                    <?php echo $tour['cantidad']; ?>
                                    <button onclick="updateCantidad(<?php echo $index; ?>, 1)">+</button>
                                </td>
                                <td>S/ <?php echo $tour['precio']; ?></td>
                                <td>S/ <?php echo $tour['precio'] * $tour['cantidad']; ?></td>
                                <td>
                                    <button onclick="quitarTour(<?php echo $index; ?>)">Quitar</button>
                                    <!-- Input fecha -->
                                    <input type="date" id="fecha_<?php echo $index; ?>" value="<?php echo $tour['fecha_tour']; ?>" min="<?php echo date('Y-m-d'); ?>" onchange="updateFecha(<?php echo $index; ?>, this.value)">
                                    <!-- Asignar pasajeros (ver abajo) -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>Total: S/ <?php echo $total_monto; ?></p>

                <!-- Sección Pasajeros Globales -->
                <h3>Pasajeros (Globales, asignar por tour abajo)</h3>
                <div id="pasajeros-globales">
                    <!-- Form dinámico con JS para agregar hasta 4+ -->
                    <button onclick="addPasajeroGlobal()">Agregar Pasajero</button>
                    <!-- Ejemplo inicial vacío -->
                </div>

                <!-- Por tour, checkboxes para asignar pasajeros (usa JS para checkboxes dinámicos) -->
                <?php foreach ($cart['tours'] as $index => $tour): ?>
                    <div>
                        <h4>Asignar a <?php echo $tour['titulo']; ?></h4>
                        <!-- Checkboxes: <input type="checkbox" name="pasajero_<?php echo $index; ?>_1"> Pasajero 1, etc. -->
                        <button onclick="asignarTodos(<?php echo $index; ?>)">Asignar Todos</button>
                    </div>
                <?php endforeach; ?>

                <a href="confirmar_reserva.php" class="btn btn-primary">Proceder a Confirmación</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- JS para carrito (agrega al final) -->
    <script>
        let pasajerosCount = 0;
        function addPasajeroGlobal() {
            if (pasajerosCount >= 10) return; // Límite
            pasajerosCount++;
            const div = document.createElement('div');
            div.innerHTML = `
                <input type="text" placeholder="Nombre" id="nombre_p${pasajerosCount}">
                <input type="text" placeholder="Apellido" id="apellido_p${pasajerosCount}">
                <input type="text" placeholder="DNI" id="dni_p${pasajerosCount}">
                <select id="tipo_p${pasajerosCount}"><option>Adulto</option><option>Niño</option><option>Infante</option></select>
                <button onclick="removePasajero(${pasajerosCount})">Quitar</button>
                <input type="hidden" id="id_p${pasajerosCount}" value="${pasajerosCount}">
            `;
            document.getElementById('pasajeros-globales').appendChild(div);
            // Enviar a actualizar_carrito.php vía AJAX al cambiar
        }

        function updateCantidad(index, delta) {
            // AJAX a actualizar_carrito.php con index y delta
            fetch('actualizar_carrito.php', {
                method: 'POST',
                body: `action=update_cantidad&index=${index}&delta=${delta}`
            }).then(() => location.reload());
        }

        function quitarTour(index) {
            if (confirm('Quitar tour?')) {
                fetch('quitar_carrito.php', {
                    method: 'POST',
                    body: `action=quitar_tour&index=${index}`
                }).then(() => location.reload());
            }
        }

        function updateFecha(index, fecha) {
            fetch('actualizar_carrito.php', {
                method: 'POST',
                body: `action=update_fecha&index=${index}&fecha=${fecha}`
            });
        }

        // Funciones similares para asignar pasajeros (envía array de asignados a actualizar_carrito.php)
        function asignarTodos(index) {
            // Lógica para checkboxes checked
        }
    </script>
</body>
</html>