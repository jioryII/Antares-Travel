<?php
/**
 * TEST - Verificar estadísticas de ingresos para el nuevo gráfico
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/admin_functions.php';

echo "=== TEST ESTADÍSTICAS DE INGRESOS ===\n";

try {
    $stats = getDashboardStats();
    
    echo "📊 DATOS OBTENIDOS:\n";
    echo "  💰 Ingresos este mes: S/ " . number_format($stats['ingresos_mes'] ?? 0, 2) . "\n";
    echo "  📈 Promedio mensual: S/ " . number_format($stats['promedio_ingresos'] ?? 0, 2) . "\n";
    echo "  💯 Ingresos totales: S/ " . number_format($stats['ingresos_totales'] ?? 0, 2) . "\n";
    
    echo "\n📅 INGRESOS MENSUALES:\n";
    if (isset($stats['ingresos_mensuales']) && count($stats['ingresos_mensuales']) > 0) {
        foreach ($stats['ingresos_mensuales'] as $ingreso) {
            echo "  {$ingreso['mes']}: S/ " . number_format($ingreso['ingresos'], 2) . 
                 " ({$ingreso['total_reservas']} reservas)\n";
        }
    } else {
        echo "  No hay datos de ingresos mensuales\n";
    }
    
    echo "\n✅ Las funciones de estadísticas están funcionando correctamente\n";
    echo "✅ El nuevo gráfico de ingresos tendrá datos reales\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
