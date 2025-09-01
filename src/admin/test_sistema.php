<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    
    echo "=== PRUEBA DEL SISTEMA DE TOURS DIARIOS ===\n\n";
    
    // 1. Verificar datos disponibles
    echo "1. Verificando datos disponibles:\n";
    $resources = ['tours', 'guias', 'choferes', 'vehiculos'];
    foreach ($resources as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
        $result = $stmt->fetch();
        echo "   - $table: {$result['total']} registros\n";
    }
    
    // 2. Verificar estructura de tours_diarios
    echo "\n2. Estructura de tours_diarios:\n";
    $stmt = $pdo->query("DESCRIBE tours_diarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    
    // 3. Verificar tours diarios existentes
    echo "\n3. Tours diarios registrados:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tours_diarios");
    $result = $stmt->fetch();
    echo "   Total: {$result['total']} tours diarios\n";
    
    if ($result['total'] > 0) {
        $stmt = $pdo->query("
            SELECT td.fecha, t.titulo, g.nombre as guia, c.nombre as chofer, v.placa
            FROM tours_diarios td
            JOIN tours t ON td.id_tour = t.id_tour
            JOIN guias g ON td.id_guia = g.id_guia
            JOIN choferes c ON td.id_chofer = c.id_chofer
            JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo
            ORDER BY td.fecha DESC LIMIT 5
        ");
        $tours = $stmt->fetchAll();
        foreach ($tours as $tour) {
            echo "   - {$tour['fecha']}: {$tour['titulo']} (Guía: {$tour['guia']}, Chofer: {$tour['chofer']}, Vehículo: {$tour['placa']})\n";
        }
    }
    
    // 4. Simular inserción de tour diario
    echo "\n4. Simulando inserción de nuevo tour diario...\n";
    
    $fecha_test = '2025-08-30';
    $id_tour = 1; // City Tour Lima
    $id_guia = 1; // Juan Carlos
    $id_chofer = 1; // Miguel
    $id_vehiculo = 1; // Mercedes Sprinter
    
    // Verificar si ya existe para esta fecha
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM tours_diarios 
        WHERE fecha = ? AND (id_guia = ? OR id_chofer = ? OR id_vehiculo = ?)
    ");
    $stmt->execute([$fecha_test, $id_guia, $id_chofer, $id_vehiculo]);
    $exists = $stmt->fetch();
    
    if ($exists['total'] > 0) {
        echo "   ⚠️  Ya hay conflictos para la fecha $fecha_test\n";
    } else {
        echo "   ✅ No hay conflictos para la fecha $fecha_test\n";
        echo "   📋 Recursos disponibles para usar:\n";
        echo "      - Tour: City Tour Lima Histórica\n";
        echo "      - Guía: Juan Carlos Pérez López\n";
        echo "      - Chofer: Miguel Torres Vega\n";
        echo "      - Vehículo: Mercedes-Benz Sprinter (ABC-123)\n";
    }
    
    echo "\n✅ Sistema listo para funcionar!\n";
    echo "🔗 Accede a: pages/tours/tours_diarios.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
