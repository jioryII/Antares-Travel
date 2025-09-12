<?php
/**
 * usuarios.php
 * Funciones reutilizables para la tabla `usuarios`
 */

require_once __DIR__ . "/../config/conexion.php";
 // Ajusta la ruta según tu estructura

/**
 * Inserta un usuario en la tabla `usuarios`
 * 
 * @param mysqli $conn
 * @param string $nombre
 * @param string $email
 * @param string|null $password_hash
 * @param string $proveedor_oauth ('manual', 'google', 'facebook', 'apple', 'microsoft', 'telefono')
 * @param string|null $id_proveedor
 * @param string|null $avatar_url
 * @param string|null $telefono
 * @return int|string|false Devuelve ID insertado, "duplicado" si email existe, o false si error
 */
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

/**
 * Obtiene un usuario por su email
 * 
 * @param mysqli $conn
 * @param string $email
 * @return array|null Devuelve el usuario o null si no existe
 */
function obtenerUsuarioPorEmail($conn, $email) {
    try {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc() ?: null;
    } catch (mysqli_sql_exception $e) {
        echo "❌ Error al obtener usuario: " . $e->getMessage();
        return null;
    }
}

/**
 * Actualiza los datos de un usuario
 * 
 * @param mysqli $conn
 * @param int $id_usuario
 * @param array $campos ['nombre' => 'Nuevo nombre', 'telefono' => '1234']
 * @return bool
 */
function actualizarUsuario($conn, $id_usuario, $campos = []) {
    if (empty($campos)) return false;

    $set = [];
    $valores = [];
    foreach ($campos as $campo => $valor) {
        $set[] = "$campo=?";
        $valores[] = $valor;
    }
    $valores[] = $id_usuario;
    $sql = "UPDATE usuarios SET " . implode(", ", $set) . " WHERE id_usuario=?";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat("s", count($valores)-1) . "i", ...$valores);
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        echo "❌ Error al actualizar usuario: " . $e->getMessage();
        return false;
    }
}

/**
 * Elimina un usuario por ID
 * 
 * @param mysqli $conn
 * @param int $id_usuario
 * @return bool
 */
function eliminarUsuario($conn, $id_usuario) {
    try {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario=?");
        $stmt->bind_param("i", $id_usuario);
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        echo "❌ Error al eliminar usuario: " . $e->getMessage();
        return false;
    }
}
function cerrarSesion() {
    session_destroy();
    header("Location: /");
    exit;
}

?>
