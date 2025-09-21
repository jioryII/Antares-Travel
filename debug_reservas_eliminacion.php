<?php
/**
 * Script de Debug para Eliminación de Reservas
 * Analiza conflictos, verifica dependencias y simula eliminaciones
 * 
 * Autor: Sistema Antares Travel
 * Fecha: 2025-09-21
 */

require_once 'src/config/conexion.php';

// Configuración de debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Función para ejecutar consultas de manera segura
function ejecutarConsulta($conexion, $sql, $params = []) {
    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Función para contar registros
function contarRegistros($conexion, $tabla, $condicion = '', $params = []) {
    try {
        $sql = "SELECT COUNT(*) as total FROM $tabla";
        if ($condicion) {
            $sql .= " WHERE $condicion";
        }
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        return 'Error: ' . $e->getMessage();
    }
}

// Función para verificar si una reserva se puede eliminar
function verificarEliminacionReserva($conexion, $id_reserva) {
    $verificaciones = [
        'puede_eliminar' => true,
        'restricciones' => [],
        'advertencias' => [],
        'datos_asociados' => []
    ];
    
    // Obtener datos de la reserva
    $sql_reserva = "SELECT r.*, u.nombre as cliente_nombre, u.email as cliente_email,
                           t.titulo as tour_titulo
                    FROM reservas r
                    LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                    LEFT JOIN tours t ON r.id_tour = t.id_tour
                    WHERE r.id_reserva = ?";
    $stmt = $conexion->prepare($sql_reserva);
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        $verificaciones['puede_eliminar'] = false;
        $verificaciones['restricciones'][] = "Reserva con ID $id_reserva no encontrada";
        return $verificaciones;
    }
    
    $verificaciones['datos_reserva'] = $reserva;
    
    // VERIFICACIÓN 1: Estado de la reserva
    if ($reserva['estado'] === 'Finalizada') {
        $verificaciones['puede_eliminar'] = false;
        $verificaciones['restricciones'][] = "No se puede eliminar una reserva finalizada (posibles implicaciones fiscales)";
    } elseif ($reserva['estado'] === 'Confirmada') {
        $verificaciones['advertencias'][] = "La reserva está confirmada - considere cancelarla en lugar de eliminarla";
    }
    
    // VERIFICACIÓN 2: Pagos asociados
    $pagos = contarRegistros($conexion, 'pagos', 'id_reserva = ?', [$id_reserva]);
    $verificaciones['datos_asociados']['pagos'] = $pagos;
    
    if ($pagos > 0) {
        // Obtener detalles de pagos
        $sql_pagos = "SELECT estado_pago, monto, fecha_pago FROM pagos WHERE id_reserva = ?";
        $pagos_detalle = ejecutarConsulta($conexion, $sql_pagos, [$id_reserva]);
        $verificaciones['datos_asociados']['detalle_pagos'] = $pagos_detalle;
        
        $tiene_pagos_exitosos = false;
        foreach ($pagos_detalle as $pago) {
            if ($pago['estado_pago'] === 'Pagado') {
                $tiene_pagos_exitosos = true;
                break;
            }
        }
        
        if ($tiene_pagos_exitosos) {
            $verificaciones['puede_eliminar'] = false;
            $verificaciones['restricciones'][] = "Existen $pagos registros de pagos exitosos - Eliminar causaría pérdida de información financiera crítica";
        } else {
            $verificaciones['advertencias'][] = "$pagos registros de pagos (pendientes/fallidos) se eliminarán";
        }
    }
    
    // VERIFICACIÓN 3: Pasajeros asociados
    $pasajeros = contarRegistros($conexion, 'pasajeros', 'id_reserva = ?', [$id_reserva]);
    $verificaciones['datos_asociados']['pasajeros'] = $pasajeros;
    
    if ($pasajeros > 0) {
        $verificaciones['advertencias'][] = "$pasajeros registros de pasajeros se eliminarán permanentemente";
    }
    
    // VERIFICACIÓN 4: Historial de ofertas
    $ofertas_usadas = contarRegistros($conexion, 'historial_uso_ofertas', 'id_reserva = ?', [$id_reserva]);
    $verificaciones['datos_asociados']['ofertas_usadas'] = $ofertas_usadas;
    
    if ($ofertas_usadas > 0) {
        $verificaciones['advertencias'][] = "$ofertas_usadas registros de historial de ofertas se eliminarán";
    }
    
    // VERIFICACIÓN 5: Disponibilidad asociada
    $disponibilidad_guias = contarRegistros($conexion, 'disponibilidad_guias', 'id_reserva = ?', [$id_reserva]);
    $disponibilidad_vehiculos = contarRegistros($conexion, 'disponibilidad_vehiculos', 'id_reserva = ?', [$id_reserva]);
    
    $verificaciones['datos_asociados']['disponibilidad_guias'] = $disponibilidad_guias;
    $verificaciones['datos_asociados']['disponibilidad_vehiculos'] = $disponibilidad_vehiculos;
    
    if ($disponibilidad_guias > 0 || $disponibilidad_vehiculos > 0) {
        $verificaciones['advertencias'][] = "Se liberará disponibilidad asociada (guías: $disponibilidad_guias, vehículos: $disponibilidad_vehiculos)";
    }
    
    // VERIFICACIÓN 6: Fecha del tour
    $fecha_tour = new DateTime($reserva['fecha_tour']);
    $fecha_actual = new DateTime();
    
    if ($fecha_tour < $fecha_actual) {
        $verificaciones['advertencias'][] = "El tour ya se realizó (" . $fecha_tour->format('d/m/Y') . ")";
    } elseif ($fecha_tour <= $fecha_actual->modify('+24 hours')) {
        $verificaciones['advertencias'][] = "El tour es muy próximo (" . $fecha_tour->format('d/m/Y') . ") - posible impacto en cliente";
    }
    
    return $verificaciones;
}

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Debug Reservas - Análisis de Eliminación</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-gray-100'>";

echo "<div class='container mx-auto px-4 py-8'>";
echo "<h1 class='text-3xl font-bold text-gray-900 mb-8 flex items-center'>";
echo "<i class='fas fa-shield-alt text-red-500 mr-3'></i>";
echo "Debug: Análisis de Eliminación de Reservas";
echo "</h1>";

try {
    $connection = getConnection();
    echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-6'>";
    echo "<p class='text-green-700'><i class='fas fa-check-circle mr-2'></i>Conexión a base de datos exitosa</p>";
    echo "</div>";
    
    // 1. ESTADÍSTICAS GENERALES
    echo "<div class='bg-white rounded-lg shadow-lg p-6 mb-6'>";
    echo "<h2 class='text-xl font-semibold text-gray-900 mb-4 flex items-center'>";
    echo "<i class='fas fa-chart-bar text-blue-500 mr-3'></i>Estadísticas de Reservas";
    echo "</h2>";
    
    $stats_reservas = [
        'Total' => contarRegistros($connection, 'reservas'),
        'Pendientes' => contarRegistros($connection, 'reservas', "estado = 'Pendiente'"),
        'Confirmadas' => contarRegistros($connection, 'reservas', "estado = 'Confirmada'"),
        'Canceladas' => contarRegistros($connection, 'reservas', "estado = 'Cancelada'"),
        'Finalizadas' => contarRegistros($connection, 'reservas', "estado = 'Finalizada'")
    ];
    
    echo "<div class='grid grid-cols-2 md:grid-cols-5 gap-4 mb-4'>";
    foreach ($stats_reservas as $estado => $cantidad) {
        $color_class = match($estado) {
            'Total' => 'bg-blue-100 text-blue-800',
            'Pendientes' => 'bg-yellow-100 text-yellow-800',
            'Confirmadas' => 'bg-green-100 text-green-800',
            'Canceladas' => 'bg-red-100 text-red-800',
            'Finalizadas' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800'
        };
        
        echo "<div class='text-center p-4 rounded-lg $color_class'>";
        echo "<p class='text-2xl font-bold'>$cantidad</p>";
        echo "<p class='text-sm font-medium'>$estado</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // Estadísticas de datos relacionados
    echo "<h3 class='text-lg font-semibold text-gray-800 mb-3'>Datos Relacionados</h3>";
    $stats_relacionados = [
        'Pagos totales' => contarRegistros($connection, 'pagos'),
        'Pasajeros registrados' => contarRegistros($connection, 'pasajeros'),
        'Usos de ofertas' => contarRegistros($connection, 'historial_uso_ofertas'),
        'Disponibilidad guías' => contarRegistros($connection, 'disponibilidad_guias', "id_reserva IS NOT NULL"),
        'Disponibilidad vehículos' => contarRegistros($connection, 'disponibilidad_vehiculos', "id_reserva IS NOT NULL")
    ];
    
    echo "<div class='grid grid-cols-2 md:grid-cols-5 gap-3'>";
    foreach ($stats_relacionados as $tipo => $cantidad) {
        echo "<div class='bg-gray-50 p-3 rounded border text-center'>";
        echo "<p class='text-lg font-semibold text-gray-900'>$cantidad</p>";
        echo "<p class='text-xs text-gray-600'>$tipo</p>";
        echo "</div>";
    }
    echo "</div>";
    echo "</div>";
    
    // 2. ANÁLISIS DE RIESGOS DE ELIMINACIÓN
    echo "<div class='bg-white rounded-lg shadow-lg p-6 mb-6'>";
    echo "<h2 class='text-xl font-semibold text-gray-900 mb-4 flex items-center'>";
    echo "<i class='fas fa-exclamation-triangle text-orange-500 mr-3'></i>Análisis de Riesgos";
    echo "</h2>";
    
    // Reservas con pagos exitosos (riesgo alto)
    $reservas_con_pagos = ejecutarConsulta($connection,
        "SELECT r.id_reserva, r.estado, u.nombre as cliente, t.titulo as tour,
                COUNT(p.id_pago) as num_pagos, SUM(p.monto) as total_pagado
         FROM reservas r
         LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
         LEFT JOIN tours t ON r.id_tour = t.id_tour
         INNER JOIN pagos p ON r.id_reserva = p.id_reserva AND p.estado_pago = 'Pagado'
         GROUP BY r.id_reserva
         ORDER BY total_pagado DESC
         LIMIT 10"
    );
    
    if (!empty($reservas_con_pagos) && !isset($reservas_con_pagos['error'])) {
        echo "<div class='mb-6'>";
        echo "<h3 class='text-lg font-semibold text-red-800 mb-3 flex items-center'>";
        echo "<i class='fas fa-ban text-red-600 mr-2'></i>Reservas con Pagos (RIESGO ALTO de eliminación)";
        echo "</h3>";
        
        echo "<div class='overflow-x-auto'>";
        echo "<table class='min-w-full bg-white border border-gray-200 rounded'>";
        echo "<thead class='bg-red-50'>";
        echo "<tr>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-red-700 uppercase'>ID</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-red-700 uppercase'>Cliente</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-red-700 uppercase'>Tour</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-red-700 uppercase'>Estado</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-red-700 uppercase'>Pagos</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-red-700 uppercase'>Total</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-red-700 uppercase'>Simulación</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody class='divide-y divide-red-100'>";
        
        foreach ($reservas_con_pagos as $reserva) {
            echo "<tr class='hover:bg-red-25'>";
            echo "<td class='px-4 py-2 text-sm font-medium text-gray-900'>#{$reserva['id_reserva']}</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$reserva['cliente']}</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$reserva['tour']}</td>";
            echo "<td class='px-4 py-2'><span class='px-2 py-1 text-xs bg-{$reserva['estado']}-100 text-{$reserva['estado']}-800 rounded'>{$reserva['estado']}</span></td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$reserva['num_pagos']} pagos</td>";
            echo "<td class='px-4 py-2 text-sm font-semibold text-green-600'>$" . number_format($reserva['total_pagado'], 2) . "</td>";
            echo "<td class='px-4 py-2'>";
            echo "<a href='?simular={$reserva['id_reserva']}' class='text-blue-600 hover:text-blue-800 text-sm flex items-center'>";
            echo "<i class='fas fa-play-circle mr-1'></i>Simular";
            echo "</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
        echo "</div>";
    }
    
    // Reservas eliminables (riesgo bajo)
    $reservas_eliminables = ejecutarConsulta($connection,
        "SELECT r.id_reserva, r.estado, r.fecha_tour, u.nombre as cliente, t.titulo as tour,
                (SELECT COUNT(*) FROM pagos p WHERE p.id_reserva = r.id_reserva AND p.estado_pago = 'Pagado') as pagos_exitosos
         FROM reservas r
         LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
         LEFT JOIN tours t ON r.id_tour = t.id_tour
         WHERE r.estado IN ('Pendiente', 'Cancelada')
         AND (SELECT COUNT(*) FROM pagos p WHERE p.id_reserva = r.id_reserva AND p.estado_pago = 'Pagado') = 0
         ORDER BY r.fecha_reserva DESC
         LIMIT 10"
    );
    
    if (!empty($reservas_eliminables) && !isset($reservas_eliminables['error'])) {
        echo "<div>";
        echo "<h3 class='text-lg font-semibold text-green-800 mb-3 flex items-center'>";
        echo "<i class='fas fa-check-circle text-green-600 mr-2'></i>Reservas Eliminables (RIESGO BAJO)";
        echo "</h3>";
        
        echo "<div class='overflow-x-auto'>";
        echo "<table class='min-w-full bg-white border border-gray-200 rounded'>";
        echo "<thead class='bg-green-50'>";
        echo "<tr>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-green-700 uppercase'>ID</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-green-700 uppercase'>Cliente</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-green-700 uppercase'>Tour</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-green-700 uppercase'>Estado</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-green-700 uppercase'>Fecha Tour</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-green-700 uppercase'>Simulación</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody class='divide-y divide-green-100'>";
        
        foreach ($reservas_eliminables as $reserva) {
            echo "<tr class='hover:bg-green-25'>";
            echo "<td class='px-4 py-2 text-sm font-medium text-gray-900'>#{$reserva['id_reserva']}</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$reserva['cliente']}</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$reserva['tour']}</td>";
            echo "<td class='px-4 py-2'><span class='px-2 py-1 text-xs bg-green-100 text-green-800 rounded'>{$reserva['estado']}</span></td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>" . date('d/m/Y', strtotime($reserva['fecha_tour'])) . "</td>";
            echo "<td class='px-4 py-2'>";
            echo "<a href='?simular={$reserva['id_reserva']}' class='text-blue-600 hover:text-blue-800 text-sm flex items-center'>";
            echo "<i class='fas fa-play-circle mr-1'></i>Simular";
            echo "</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
        echo "</div>";
    }
    echo "</div>";
    
    // 3. SIMULACIÓN DE ELIMINACIÓN
    if (isset($_GET['simular']) && is_numeric($_GET['simular'])) {
        $id_simular = (int)$_GET['simular'];
        $verificacion = verificarEliminacionReserva($connection, $id_simular);
        
        echo "<div class='bg-white rounded-lg shadow-lg p-6 mb-6'>";
        echo "<h2 class='text-xl font-semibold text-gray-900 mb-4 flex items-center'>";
        echo "<i class='fas fa-microscope text-purple-500 mr-3'></i>Simulación de Eliminación - Reserva #$id_simular";
        echo "</h2>";
        
        if (isset($verificacion['datos_reserva'])) {
            $reserva = $verificacion['datos_reserva'];
            
            echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-6 mb-6'>";
            
            // Información de la reserva
            echo "<div class='bg-blue-50 p-4 rounded border border-blue-200'>";
            echo "<h3 class='font-semibold text-blue-800 mb-3'>Información de la Reserva</h3>";
            echo "<div class='text-sm space-y-1'>";
            echo "<p><strong>ID:</strong> #{$reserva['id_reserva']}</p>";
            echo "<p><strong>Cliente:</strong> {$reserva['cliente_nombre']}</p>";
            echo "<p><strong>Email:</strong> {$reserva['cliente_email']}</p>";
            echo "<p><strong>Tour:</strong> {$reserva['tour_titulo']}</p>";
            echo "<p><strong>Fecha Tour:</strong> " . date('d/m/Y', strtotime($reserva['fecha_tour'])) . "</p>";
            echo "<p><strong>Estado:</strong> <span class='px-2 py-1 rounded text-xs bg-gray-100'>{$reserva['estado']}</span></p>";
            echo "<p><strong>Monto Total:</strong> $" . number_format($reserva['monto_total'], 2) . "</p>";
            echo "</div>";
            echo "</div>";
            
            // Datos asociados que serán afectados
            echo "<div class='bg-orange-50 p-4 rounded border border-orange-200'>";
            echo "<h3 class='font-semibold text-orange-800 mb-3'>Datos Asociados</h3>";
            echo "<div class='text-sm space-y-1'>";
            echo "<p><i class='fas fa-credit-card text-orange-600 w-4'></i> <strong>Pagos:</strong> {$verificacion['datos_asociados']['pagos']}</p>";
            echo "<p><i class='fas fa-users text-orange-600 w-4'></i> <strong>Pasajeros:</strong> {$verificacion['datos_asociados']['pasajeros']}</p>";
            echo "<p><i class='fas fa-gift text-orange-600 w-4'></i> <strong>Ofertas usadas:</strong> {$verificacion['datos_asociados']['ofertas_usadas']}</p>";
            echo "<p><i class='fas fa-user-tie text-orange-600 w-4'></i> <strong>Disponibilidad guías:</strong> {$verificacion['datos_asociados']['disponibilidad_guias']}</p>";
            echo "<p><i class='fas fa-car text-orange-600 w-4'></i> <strong>Disponibilidad vehículos:</strong> {$verificacion['datos_asociados']['disponibilidad_vehiculos']}</p>";
            echo "</div>";
            echo "</div>";
            
            echo "</div>";
            
            // Resultado de la verificación
            if ($verificacion['puede_eliminar']) {
                echo "<div class='bg-green-50 border border-green-200 rounded p-4 mb-4'>";
                echo "<h3 class='font-semibold text-green-800 mb-2 flex items-center'>";
                echo "<i class='fas fa-check-circle text-green-600 mr-2'></i>✅ ELIMINACIÓN PERMITIDA";
                echo "</h3>";
            } else {
                echo "<div class='bg-red-50 border border-red-200 rounded p-4 mb-4'>";
                echo "<h3 class='font-semibold text-red-800 mb-2 flex items-center'>";
                echo "<i class='fas fa-ban text-red-600 mr-2'></i>❌ ELIMINACIÓN BLOQUEADA";
                echo "</h3>";
            }
            
            // Mostrar restricciones
            if (!empty($verificacion['restricciones'])) {
                echo "<div class='mb-3'>";
                echo "<p class='font-medium text-red-700 mb-2'>Restricciones críticas:</p>";
                echo "<ul class='list-disc list-inside text-sm text-red-600 space-y-1'>";
                foreach ($verificacion['restricciones'] as $restriccion) {
                    echo "<li>$restriccion</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
            
            // Mostrar advertencias
            if (!empty($verificacion['advertencias'])) {
                echo "<div>";
                echo "<p class='font-medium text-orange-700 mb-2'>Advertencias:</p>";
                echo "<ul class='list-disc list-inside text-sm text-orange-600 space-y-1'>";
                foreach ($verificacion['advertencias'] as $advertencia) {
                    echo "<li>$advertencia</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
            
            echo "</div>";
            
            // Detalles de pagos si los hay
            if (isset($verificacion['datos_asociados']['detalle_pagos']) && !empty($verificacion['datos_asociados']['detalle_pagos'])) {
                echo "<div class='bg-yellow-50 border border-yellow-200 rounded p-4'>";
                echo "<h3 class='font-semibold text-yellow-800 mb-3'>Detalle de Pagos Asociados</h3>";
                echo "<div class='overflow-x-auto'>";
                echo "<table class='min-w-full bg-white border border-gray-200 rounded text-sm'>";
                echo "<thead><tr class='bg-gray-50'>";
                echo "<th class='px-3 py-2 text-left'>Fecha</th>";
                echo "<th class='px-3 py-2 text-left'>Monto</th>";
                echo "<th class='px-3 py-2 text-left'>Estado</th>";
                echo "</tr></thead><tbody>";
                
                foreach ($verificacion['datos_asociados']['detalle_pagos'] as $pago) {
                    $estado_color = $pago['estado_pago'] === 'Pagado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                    echo "<tr>";
                    echo "<td class='px-3 py-2 border-t'>" . date('d/m/Y', strtotime($pago['fecha_pago'])) . "</td>";
                    echo "<td class='px-3 py-2 border-t'>$" . number_format($pago['monto'], 2) . "</td>";
                    echo "<td class='px-3 py-2 border-t'><span class='px-2 py-1 rounded text-xs $estado_color'>{$pago['estado_pago']}</span></td>";
                    echo "</tr>";
                }
                
                echo "</tbody></table></div></div>";
            }
        } else {
            echo "<div class='bg-red-50 border border-red-200 rounded p-4'>";
            echo "<p class='text-red-700'>Reserva con ID $id_simular no encontrada</p>";
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    // 4. RECOMENDACIONES
    echo "<div class='bg-white rounded-lg shadow-lg p-6'>";
    echo "<h2 class='text-xl font-semibold text-gray-900 mb-4 flex items-center'>";
    echo "<i class='fas fa-lightbulb text-yellow-500 mr-3'></i>Recomendaciones de Implementación";
    echo "</h2>";
    
    echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-6'>";
    
    // Verificaciones recomendadas
    echo "<div class='bg-green-50 p-4 rounded border border-green-200'>";
    echo "<h3 class='font-semibold text-green-800 mb-3'>✅ Verificaciones Implementadas</h3>";
    echo "<ul class='text-sm text-green-700 space-y-1'>";
    echo "<li>• Verificación de estado 'Completada'</li>";
    echo "<li>• Verificación de pagos asociados</li>";
    echo "<li>• Registro en logs de eliminación</li>";
    echo "<li>• Eliminación en cascada de pasajeros</li>";
    echo "<li>• Transacción segura con rollback</li>";
    echo "</ul>";
    echo "</div>";
    
    // Mejoras sugeridas
    echo "<div class='bg-blue-50 p-4 rounded border border-blue-200'>";
    echo "<h3 class='font-semibold text-blue-800 mb-3'>🔧 Mejoras Sugeridas</h3>";
    echo "<ul class='text-sm text-blue-700 space-y-1'>";
    echo "<li>• Verificar fecha del tour (evitar eliminar tours próximos)</li>";
    echo "<li>• Archivar datos críticos antes de eliminar</li>";
    echo "<li>• Notificar a clientes de cancelaciones</li>";
    echo "<li>• Implementar eliminación lógica para auditoría</li>";
    echo "<li>• Verificar disponibilidad asociada</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4'>";
    echo "<p class='text-red-700'><i class='fas fa-exclamation-circle mr-2'></i>Error de conexión: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='mt-8 text-center'>";
echo "<a href='src/admin/pages/reservas/index.php' class='inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors'>";
echo "<i class='fas fa-arrow-left mr-2'></i>";
echo "Volver a Reservas";
echo "</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
