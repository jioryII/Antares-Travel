<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username   = "root";
$password   = "admin942";
$dbname     = "db_antares";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    echo "❌ Error de conexión MySQL: " . $e->getMessage();
    exit();
}

function insertarUsuario($conn, $nombre, $email, $password_hash = null, $proveedor_oauth = 'manual', $id_proveedor = null, $avatar_url = null, $telefono = null) {
    try {
        $stmt = $conn->prepare("INSERT INTO usuarios 
            (nombre, email, email_verificado, password_hash, proveedor_oauth, id_proveedor, avatar_url, telefono) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $email_verificado = ($proveedor_oauth !== 'manual') ? 1 : 0;

        $stmt->bind_param(
            "ssisssss",
            $nombre,
            $email,
            $email_verificado,
            $password_hash,
            $proveedor_oauth,
            $id_proveedor,
            $avatar_url,
            $telefono
        );

        $stmt->execute();
        return $stmt->insert_id;
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            return "duplicado";
        }
        echo "❌ Error al insertar usuario: " . $e->getMessage();
        return false;
    }
}

function obtenerUsuarioPorEmail($conn, $email) {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
