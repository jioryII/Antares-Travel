<?php
/**
 * conexion.php
 * Conexión a MySQL usando mysqli con manejo de excepciones
 * Usuario: root
 * Contraseña: admin942
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "opal18.opalstack.com";
$username   = "jiory";
$password   = "3fwPqEHLOwWT680";
$dbname     = "db_antares";

try {
    // Crear conexión
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4"); // Soporte para acentos y emojis
    // echo "✅ Conexión exitosa a la base de datos MySQL"; // opcional para debug
} catch (mysqli_sql_exception $e) {
    // Captura errores de conexión
    echo "❌ Error de conexión MySQL: " . $e->getMessage();
    exit(); // Termina el script si no se puede conectar
}

/**
 * Función de ayuda para ejecutar consultas SELECT
 */
function ejecutarConsulta($conn, $sql) {
    try {
        $result = $conn->query($sql);
        return $result;
    } catch (mysqli_sql_exception $e) {
        echo "❌ Error en la consulta: " . $e->getMessage();
        return false;
    }
}

/**
 * Función de ayuda para ejecutar INSERT, UPDATE, DELETE
 */
function ejecutarAccion($conn, $sql) {
    try {
        $conn->query($sql);
        return true;
    } catch (mysqli_sql_exception $e) {
        echo "❌ Error al ejecutar acción: " . $e->getMessage();
        return false;
    }
}
?>
