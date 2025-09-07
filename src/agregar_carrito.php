<?php
session_start();
require_once 'config/conexion.php';

if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$id_tour = intval($_POST['id_tour'] ?? 0);
$titulo = $_POST['titulo'] ?? '';
$precio = floatval($_POST['precio'] ?? 0);

if ($id_tour <= 0 || empty($titulo) || $precio <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Obtener id_usuario de sesión
$user_email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$id_usuario = $user['id_usuario'] ?? 0;

if (!$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// Inicializar carrito si no existe
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = ['tours' => [], 'total_paquetes' => 0];
}

// Verificar si tour ya existe, incrementar cantidad
$tour_exists = false;
foreach ($_SESSION['cart']['tours'] as &$tour) {
    if ($tour['id_tour'] == $id_tour) {
        $tour['cantidad']++;
        $tour_exists = true;
        break;
    }
}

if (!$tour_exists) {
    // Agregar nuevo tour
    $_SESSION['cart']['tours'][] = [
        'id_tour' => $id_tour,
        'titulo' => $titulo,
        'precio' => $precio,
        'cantidad' => 1,
        'pasajeros_asignados' => [],  // Array de IDs de pasajeros globales
        'fecha_tour' => ''  // Se asigna en carrito
    ];
}

$_SESSION['cart']['total_paquetes'] = count($_SESSION['cart']['tours']);  // Actual, pero si incrementas cantidad, ajusta a sum(cantidad)

echo json_encode([
    'success' => true,
    'total_paquetes' => $_SESSION['cart']['total_paquetes'],
    'message' => 'Agregado correctamente'
]);
?>