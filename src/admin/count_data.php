<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    
    $tablas = ['guias', 'choferes', 'vehiculos'];
    
    foreach ($tablas as $tabla) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
        $resultado = $stmt->fetch();
        echo "Tabla '$tabla': {$resultado['total']} registros\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
