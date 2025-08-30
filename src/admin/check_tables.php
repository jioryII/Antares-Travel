<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    echo "Conexión exitosa a la base de datos.\n\n";
    
    // Mostrar tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tablas existentes:\n";
    foreach ($tablas as $tabla) {
        echo "- $tabla\n";
    }
    
    echo "\nVerificando tablas necesarias para tours:\n";
    $tablas_tours = ['guias', 'choferes', 'vehiculos', 'tours_diarios'];
    foreach ($tablas_tours as $tabla) {
        $existe = in_array($tabla, $tablas);
        echo "- $tabla: " . ($existe ? "✓ EXISTE" : "✗ NO EXISTE") . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
