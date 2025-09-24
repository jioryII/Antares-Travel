<?php
// Test script to check ofertas table and database connection
require_once 'src/admin/config/config.php';

try {
    $connection = getConnection();
    echo "✅ Database connection successful\n";
    
    // Check if ofertas table exists
    $tables_sql = "SHOW TABLES LIKE 'ofertas'";
    $result = $connection->query($tables_sql);
    
    if ($result->rowCount() > 0) {
        echo "✅ Table 'ofertas' exists\n";
        
        // Check table structure
        $structure_sql = "DESCRIBE ofertas";
        $structure = $connection->query($structure_sql);
        echo "\nTable structure:\n";
        foreach ($structure->fetchAll() as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
        // Check if there are any records
        $count_sql = "SELECT COUNT(*) as total FROM ofertas";
        $count_result = $connection->query($count_sql);
        $count = $count_result->fetch();
        echo "\nTotal records in ofertas: " . $count['total'] . "\n";
        
    } else {
        echo "❌ Table 'ofertas' does not exist\n";
        
        // Show available tables
        $all_tables_sql = "SHOW TABLES";
        $all_tables = $connection->query($all_tables_sql);
        echo "\nAvailable tables:\n";
        foreach ($all_tables->fetchAll() as $table) {
            echo "- " . $table[0] . "\n";
        }
    }
    
    // Check related tables
    $related_tables = ['administradores', 'historial_uso_ofertas', 'ofertas_tours', 'ofertas_usuarios', 'tours'];
    foreach ($related_tables as $table) {
        $check_sql = "SHOW TABLES LIKE '$table'";
        $check_result = $connection->query($check_sql);
        if ($check_result->rowCount() > 0) {
            echo "✅ Related table '$table' exists\n";
        } else {
            echo "❌ Related table '$table' missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
