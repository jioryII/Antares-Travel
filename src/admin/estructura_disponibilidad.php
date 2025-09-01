<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    
    $tablas = ['disponibilidad_guias', 'disponibilidad_vehiculos'];
    
    foreach ($tablas as $tabla) {
        echo "Estructura de la tabla '$tabla':\n";
        $stmt = $pdo->query("DESCRIBE $tabla");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columnas as $columna) {
            echo "- {$columna['Field']} ({$columna['Type']}) " . 
                 ($columna['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
                 ($columna['Default'] ? " DEFAULT '{$columna['Default']}'" : '') . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
