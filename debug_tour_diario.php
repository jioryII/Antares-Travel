<?php
/**
 * Script de Debug para Tours Diarios
 * Verifica relaciones, restricciones y estado de los datos
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

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Debug Tours Diarios - Antares Travel</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-gray-100'>";

echo "<div class='container mx-auto px-4 py-8'>";
echo "<h1 class='text-3xl font-bold text-gray-900 mb-8 flex items-center'>";
echo "<i class='fas fa-bug text-red-500 mr-3'></i>";
echo "Debug: Tours Diarios - Análisis de Base de Datos";
echo "</h1>";

try {
    $connection = getConnection();
    echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-6'>";
    echo "<p class='text-green-700'><i class='fas fa-check-circle mr-2'></i>Conexión a base de datos exitosa</p>";
    echo "</div>";
    
    // 1. ANÁLISIS DE TABLAS PRINCIPALES
    echo "<div class='bg-white rounded-lg shadow-lg p-6 mb-6'>";
    echo "<h2 class='text-xl font-semibold text-gray-900 mb-4 flex items-center'>";
    echo "<i class='fas fa-table text-blue-500 mr-3'></i>Análisis de Tablas Principales";
    echo "</h2>";
    
    $tablas = [
        'tours_diarios' => 'Tours Diarios',
        'tours' => 'Tours',
        'guias' => 'Guías', 
        'choferes' => 'Choferes',
        'vehiculos' => 'Vehículos',
        'disponibilidad_guias' => 'Disponibilidad Guías',
        'disponibilidad_vehiculos' => 'Disponibilidad Vehículos',
        'reservas' => 'Reservas'
    ];
    
    echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6'>";
    foreach ($tablas as $tabla => $nombre) {
        $total = contarRegistros($connection, $tabla);
        $icono = match($tabla) {
            'tours_diarios' => 'fa-calendar-check',
            'tours' => 'fa-route',
            'guias' => 'fa-user-tie',
            'choferes' => 'fa-id-card',
            'vehiculos' => 'fa-car',
            'disponibilidad_guias', 'disponibilidad_vehiculos' => 'fa-calendar',
            'reservas' => 'fa-bookmark',
            default => 'fa-table'
        };
        
        echo "<div class='bg-gray-50 p-4 rounded-lg border'>";
        echo "<div class='flex items-center justify-between'>";
        echo "<div>";
        echo "<p class='text-sm font-medium text-gray-600'>$nombre</p>";
        echo "<p class='text-2xl font-bold text-gray-900'>$total</p>";
        echo "</div>";
        echo "<i class='fas $icono text-2xl text-gray-400'></i>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    
    // 2. ANÁLISIS DE TOURS DIARIOS DETALLADO
    echo "<h3 class='text-lg font-semibold text-gray-800 mb-4'>Tours Diarios - Últimos 10 registros</h3>";
    $tours_diarios = ejecutarConsulta($connection, 
        "SELECT td.*, t.titulo as tour_titulo, 
         CONCAT(g.nombre, ' ', g.apellido) as guia_nombre,
         CONCAT(c.nombre, ' ', c.apellido) as chofer_nombre,
         CONCAT(v.marca, ' ', v.modelo, ' - ', v.placa) as vehiculo_info
         FROM tours_diarios td
         LEFT JOIN tours t ON td.id_tour = t.id_tour
         LEFT JOIN guias g ON td.id_guia = g.id_guia  
         LEFT JOIN choferes c ON td.id_chofer = c.id_chofer
         LEFT JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo
         ORDER BY td.fecha DESC, td.hora_salida DESC
         LIMIT 10"
    );
    
    if (!empty($tours_diarios) && !isset($tours_diarios['error'])) {
        echo "<div class='overflow-x-auto'>";
        echo "<table class='min-w-full bg-white border border-gray-200 rounded-lg'>";
        echo "<thead class='bg-gray-50'>";
        echo "<tr>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>ID</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Fecha</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Tour</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Guía</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Vehículo</th>";
        echo "<th class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Pasajeros</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody class='divide-y divide-gray-200'>";
        
        foreach ($tours_diarios as $tour) {
            echo "<tr class='hover:bg-gray-50'>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$tour['id_tour_diario']}</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>" . date('d/m/Y', strtotime($tour['fecha'])) . "</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$tour['tour_titulo']}</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$tour['guia_nombre']}</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>{$tour['vehiculo_info']}</td>";
            echo "<td class='px-4 py-2 text-sm text-gray-900'>A: {$tour['num_adultos']} | N: {$tour['num_ninos']}</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='bg-yellow-50 border border-yellow-200 rounded p-4'>";
        echo "<p class='text-yellow-700'>No se encontraron tours diarios o error en la consulta.</p>";
        if (isset($tours_diarios['error'])) {
            echo "<p class='text-red-600 text-sm mt-2'>Error: {$tours_diarios['error']}</p>";
        }
        echo "</div>";
    }
    echo "</div>";
    
    // 3. VERIFICACIÓN DE RESTRICCIONES DE ELIMINACIÓN
    echo "<div class='bg-white rounded-lg shadow-lg p-6 mb-6'>";
    echo "<h2 class='text-xl font-semibold text-gray-900 mb-4 flex items-center'>";
    echo "<i class='fas fa-shield-alt text-green-500 mr-3'></i>Verificación de Restricciones de Eliminación";
    echo "</h2>";
    
    // Verificar si hay foreign keys que impidan eliminar
    $foreign_keys = ejecutarConsulta($connection,
        "SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE 
         WHERE REFERENCED_TABLE_NAME = 'tours_diarios' 
         AND TABLE_SCHEMA = DATABASE()"
    );
    
    if (empty($foreign_keys) || isset($foreign_keys['error'])) {
        echo "<div class='bg-green-50 border border-green-200 rounded p-4'>";
        echo "<p class='text-green-700 flex items-center'>";
        echo "<i class='fas fa-check-circle mr-2'></i>";
        echo "✅ <strong>ELIMINACIÓN SEGURA:</strong> No hay foreign keys que referencien la tabla tours_diarios.";
        echo "</p>";
        echo "<p class='text-green-600 text-sm mt-2'>Los registros de tours_diarios pueden eliminarse sin restricciones de integridad referencial.</p>";
        echo "</div>";
    } else {
        echo "<div class='bg-red-50 border border-red-200 rounded p-4'>";
        echo "<p class='text-red-700'><i class='fas fa-exclamation-triangle mr-2'></i>Se encontraron restricciones:</p>";
        foreach ($foreign_keys as $fk) {
            echo "<p class='text-sm text-red-600 ml-6'>• {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} → tours_diarios.{$fk['REFERENCED_COLUMN_NAME']}</p>";
        }
        echo "</div>";
    }
    
    // 4. ANÁLISIS DE DISPONIBILIDAD
    echo "<h3 class='text-lg font-semibold text-gray-800 mb-4 mt-6'>Estado de Disponibilidad</h3>";
    
    $disponibilidad_stats = [
        'guias_libres' => contarRegistros($connection, 'disponibilidad_guias', "estado = 'Libre'"),
        'guias_ocupados' => contarRegistros($connection, 'disponibilidad_guias', "estado = 'Ocupado'"),
        'vehiculos_libres' => contarRegistros($connection, 'disponibilidad_vehiculos', "estado = 'Libre'"),
        'vehiculos_ocupados' => contarRegistros($connection, 'disponibilidad_vehiculos', "estado = 'Ocupado'")
    ];
    
    echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
    
    // Guías
    echo "<div class='bg-blue-50 border border-blue-200 rounded p-4'>";
    echo "<h4 class='font-medium text-blue-800 mb-2'>Estado de Guías</h4>";
    echo "<div class='flex justify-between items-center'>";
    echo "<span class='text-green-600'>Libres: {$disponibilidad_stats['guias_libres']}</span>";
    echo "<span class='text-red-600'>Ocupados: {$disponibilidad_stats['guias_ocupados']}</span>";
    echo "</div>";
    echo "</div>";
    
    // Vehículos
    echo "<div class='bg-green-50 border border-green-200 rounded p-4'>";
    echo "<h4 class='font-medium text-green-800 mb-2'>Estado de Vehículos</h4>";
    echo "<div class='flex justify-between items-center'>";
    echo "<span class='text-green-600'>Libres: {$disponibilidad_stats['vehiculos_libres']}</span>";
    echo "<span class='text-red-600'>Ocupados: {$disponibilidad_stats['vehiculos_ocupados']}</span>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // 5. SIMULACIÓN DE ELIMINACIÓN
    if (isset($_GET['simular_id']) && is_numeric($_GET['simular_id'])) {
        $id_simular = (int)$_GET['simular_id'];
        
        echo "<div class='bg-white rounded-lg shadow-lg p-6 mb-6'>";
        echo "<h2 class='text-xl font-semibold text-gray-900 mb-4 flex items-center'>";
        echo "<i class='fas fa-play-circle text-purple-500 mr-3'></i>Simulación de Eliminación - ID: $id_simular";
        echo "</h2>";
        
        // Obtener datos del tour a simular
        $tour_simular = ejecutarConsulta($connection,
            "SELECT td.*, t.titulo as tour_titulo, 
             CONCAT(g.nombre, ' ', g.apellido) as guia_nombre,
             CONCAT(c.nombre, ' ', c.apellido) as chofer_nombre,
             CONCAT(v.marca, ' ', v.modelo, ' - ', v.placa) as vehiculo_info
             FROM tours_diarios td
             LEFT JOIN tours t ON td.id_tour = t.id_tour
             LEFT JOIN guias g ON td.id_guia = g.id_guia  
             LEFT JOIN choferes c ON td.id_chofer = c.id_chofer
             LEFT JOIN vehiculos v ON td.id_vehiculo = v.id_vehiculo
             WHERE td.id_tour_diario = ?",
            [$id_simular]
        );
        
        if (!empty($tour_simular) && !isset($tour_simular['error'])) {
            $tour = $tour_simular[0];
            
            echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-6'>";
            
            // Datos del tour
            echo "<div class='bg-gray-50 p-4 rounded border'>";
            echo "<h4 class='font-medium text-gray-800 mb-3'>Datos del Tour</h4>";
            echo "<ul class='text-sm text-gray-600 space-y-1'>";
            echo "<li><strong>ID:</strong> {$tour['id_tour_diario']}</li>";
            echo "<li><strong>Tour:</strong> {$tour['tour_titulo']}</li>";
            echo "<li><strong>Fecha:</strong> " . date('d/m/Y', strtotime($tour['fecha'])) . "</li>";
            echo "<li><strong>Guía:</strong> {$tour['guia_nombre']}</li>";
            echo "<li><strong>Chofer:</strong> {$tour['chofer_nombre']}</li>";
            echo "<li><strong>Vehículo:</strong> {$tour['vehiculo_info']}</li>";
            echo "</ul>";
            echo "</div>";
            
            // Verificaciones
            echo "<div class='bg-yellow-50 p-4 rounded border border-yellow-200'>";
            echo "<h4 class='font-medium text-yellow-800 mb-3'>Verificaciones</h4>";
            
            // Verificar reservas
            $reservas_asociadas = contarRegistros($connection, 'reservas', 
                'fecha_tour = ? AND id_tour = ?', 
                [$tour['fecha'], $tour['id_tour']]
            );
            
            echo "<ul class='text-sm space-y-2'>";
            
            if ($reservas_asociadas > 0) {
                echo "<li class='text-red-600'><i class='fas fa-times-circle mr-2'></i>❌ $reservas_asociadas reservas asociadas - NO SE PUEDE ELIMINAR</li>";
            } else {
                echo "<li class='text-green-600'><i class='fas fa-check-circle mr-2'></i>✅ Sin reservas asociadas - SE PUEDE ELIMINAR</li>";
            }
            
            // Verificar fecha
            $fecha_tour = new DateTime($tour['fecha']);
            $fecha_actual = new DateTime();
            
            if ($fecha_tour < $fecha_actual) {
                echo "<li class='text-yellow-600'><i class='fas fa-exclamation-triangle mr-2'></i>⚠️ Fecha ya pasada - Advertir al usuario</li>";
            } else {
                echo "<li class='text-blue-600'><i class='fas fa-info-circle mr-2'></i>ℹ️ Fecha futura - Eliminación normal</li>";
            }
            
            echo "</ul>";
            
            // Acciones que se realizarían
            echo "<div class='mt-4 p-3 bg-blue-50 border border-blue-200 rounded'>";
            echo "<p class='font-medium text-blue-800 mb-2'>Acciones que se realizarían:</p>";
            echo "<ul class='text-sm text-blue-700 space-y-1'>";
            echo "<li>1. Eliminar registro de tours_diarios</li>";
            echo "<li>2. Liberar disponibilidad del guía (ID: {$tour['id_guia']})</li>";
            echo "<li>3. Liberar disponibilidad del vehículo (ID: {$tour['id_vehiculo']})</li>";
            echo "<li>4. Registrar actividad en log de auditoría</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "</div>";
            echo "</div>";
        } else {
            echo "<div class='bg-red-50 border border-red-200 rounded p-4'>";
            echo "<p class='text-red-700'>Tour diario con ID $id_simular no encontrado.</p>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Lista de tours para simular
    if (!empty($tours_diarios) && !isset($tours_diarios['error'])) {
        echo "<div class='bg-white rounded-lg shadow-lg p-6'>";
        echo "<h2 class='text-xl font-semibold text-gray-900 mb-4 flex items-center'>";
        echo "<i class='fas fa-cogs text-orange-500 mr-3'></i>Herramientas de Simulación";
        echo "</h2>";
        
        echo "<p class='text-gray-600 mb-4'>Selecciona un tour diario para simular su eliminación:</p>";
        echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3'>";
        
        foreach (array_slice($tours_diarios, 0, 6) as $tour) {
            $url_simular = $_SERVER['PHP_SELF'] . '?simular_id=' . $tour['id_tour_diario'];
            echo "<a href='$url_simular' class='block p-3 border border-gray-200 rounded hover:bg-gray-50 transition-colors'>";
            echo "<div class='text-sm'>";
            echo "<p class='font-medium text-gray-900'>#{$tour['id_tour_diario']} - {$tour['tour_titulo']}</p>";
            echo "<p class='text-gray-500'>" . date('d/m/Y', strtotime($tour['fecha'])) . "</p>";
            echo "<p class='text-gray-500'>{$tour['guia_nombre']}</p>";
            echo "</div>";
            echo "</a>";
        }
        
        echo "</div>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4'>";
    echo "<p class='text-red-700'><i class='fas fa-exclamation-circle mr-2'></i>Error de conexión: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='mt-8 text-center'>";
echo "<a href='src/admin/pages/tours/tours_diarios.php?debug=1' class='inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors'>";
echo "<i class='fas fa-arrow-left mr-2'></i>";
echo "Volver a Tours Diarios";
echo "</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
