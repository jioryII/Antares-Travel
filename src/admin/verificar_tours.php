<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    
    // Verificar tours existentes
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM tours');
    $resultado = $stmt->fetch();
    echo "Tours existentes: {$resultado['total']}\n\n";
    
    if ($resultado['total'] > 0) {
        $stmt = $pdo->query('SELECT id_tour, nombre_tour, duracion, precio FROM tours LIMIT 10');
        $tours = $stmt->fetchAll();
        echo "Tours disponibles:\n";
        foreach ($tours as $tour) {
            echo "- {$tour['nombre_tour']} (Duración: {$tour['duracion']}, Precio: S/ {$tour['precio']})\n";
        }
    } else {
        echo "❌ No hay tours base en el sistema.\n";
        echo "🔧 Necesitamos crear tours base para poder programar tours diarios.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
