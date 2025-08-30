<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query('SHOW TABLES');
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Todas las tablas disponibles:\n";
    foreach ($tablas as $tabla) {
        echo "- $tabla\n";
    }
    
    echo "\nTablas relacionadas con disponibilidad:\n";
    $disponibilidad_tables = array_filter($tablas, function($tabla) {
        return strpos($tabla, 'disponibilidad') !== false || strpos($tabla, 'chofer') !== false;
    });
    
    if (empty($disponibilidad_tables)) {
        echo "❌ No hay tablas de disponibilidad específicas.\n";
    } else {
        foreach ($disponibilidad_tables as $tabla) {
            echo "✓ $tabla\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
