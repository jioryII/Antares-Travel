<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    
    echo "Estructura de la tabla 'tours_diarios':\n";
    $stmt = $pdo->query("DESCRIBE tours_diarios");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columnas as $columna) {
        echo "- {$columna['Field']} ({$columna['Type']}) " . 
             ($columna['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
             ($columna['Default'] ? " DEFAULT '{$columna['Default']}'" : '') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
