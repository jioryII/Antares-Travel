<?php
// verify.php
require_once __DIR__ . '/vendor/autoload.php'; // composer autoload
// Config
$expected_audience = '454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com'; // reemplaza con tu Client ID
$dbFile = __DIR__ . '/ratings.db';

header('Content-Type: text/plain; charset=utf-8');

$raw = file_get_contents('php://input');
if (!$raw) { http_response_code(400); echo "Solicitud vacía"; exit; }
$data = json_decode($raw, true);
if (!$data) { http_response_code(400); echo "JSON inválido"; exit; }

if (!isset($data['id_token'])) { http_response_code(400); echo "Falta id_token"; exit; }

$idToken = $data['id_token'];
$target_person = trim($data['target_person'] ?? '');
$stars = intval($data['stars'] ?? 0);
$comment = trim($data['comment'] ?? '');

if (!$target_person || $stars < 1 || $stars > 5) { http_response_code(400); echo "Datos de calificación inválidos"; exit; }

$client = new Google_Client(['client_id' => $expected_audience]);
try {
    $payload = $client->verifyIdToken($idToken);
    if (!$payload) {
        http_response_code(401);
        echo "Token inválido o expirado";
        exit;
    }
    // payload contiene campos como: sub (id único), email, name, picture, aud, exp...
    $sub = $payload['sub'];
    $email = $payload['email'] ?? null;
    $name = $payload['name'] ?? null;

    // Conexión a SQLite y guardado
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Guardar (puedes decidir si permitir múltiples calificaciones por usuario para la misma persona)
    $stmt = $db->prepare('INSERT INTO ratings (google_sub, name, email, target_person, stars, comment) VALUES (:sub,:name,:email,:target_person,:stars,:comment)');
    $stmt->execute([
        ':sub' => $sub,
        ':name' => $name,
        ':email' => $email,
        ':target_person' => $target_person,
        ':stars' => $stars,
        ':comment' => $comment
    ]);

    http_response_code(200);
    echo "OK";
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Error verifying token: " . $e->getMessage();
    exit;
}
