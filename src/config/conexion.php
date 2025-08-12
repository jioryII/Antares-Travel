
<?php

class Database {
    private $host = "localhost";
    private $database = "antares_travel";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";
    private $conn = null;

    public function conectar() {
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->database . ";charset=" . $this->charset;
            
            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $opciones);
            return $this->conn;

        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw new Exception("Error al conectar con la base de datos");
        }
    }

    public function desconectar() {
        $this->conn = null;
    }
}

// Uso del singleton para evitar múltiples conexiones
function obtenerConexion() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->conectar();
}