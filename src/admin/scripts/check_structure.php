<?php
require_once __DIR__ . '/../config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "📋 Estructura de la tabla 'tours':\n\n";
    
    $stmt = $pdo->query("DESCRIBE tours");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default']
        );
    }
    
    echo "\n📋 Estructura de la tabla 'regiones':\n\n";
    
    $stmt = $pdo->query("DESCRIBE regiones");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default']
        );
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
