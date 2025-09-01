<?php
/**
 * Archivo de prueba para verificar que el filtro de búsqueda funciona
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../functions/tours_functions.php';

echo "<h1>Prueba del Filtro de Búsqueda</h1>";

// Prueba 1: Obtener todos los tours
echo "<h2>1. Todos los tours:</h2>";
$resultado_todos = obtenerTours(1, 50, []);
if ($resultado_todos['success']) {
    echo "<p>Total tours: " . $resultado_todos['total'] . "</p>";
    foreach ($resultado_todos['data'] as $tour) {
        echo "<p>- " . htmlspecialchars($tour['titulo']) . " | " . htmlspecialchars(substr($tour['descripcion'], 0, 50)) . "...</p>";
    }
} else {
    echo "<p style='color: red;'>Error: " . $resultado_todos['message'] . "</p>";
}

// Prueba 2: Buscar con un término específico
echo "<h2>2. Búsqueda con término 'tour':</h2>";
$resultado_busqueda = obtenerTours(1, 50, ['busqueda' => 'tour']);
if ($resultado_busqueda['success']) {
    echo "<p>Tours encontrados: " . $resultado_busqueda['total'] . "</p>";
    foreach ($resultado_busqueda['data'] as $tour) {
        echo "<p>- " . htmlspecialchars($tour['titulo']) . " | " . htmlspecialchars(substr($tour['descripcion'], 0, 50)) . "...</p>";
    }
} else {
    echo "<p style='color: red;'>Error: " . $resultado_busqueda['message'] . "</p>";
}

// Prueba 3: Buscar con un término que probablemente no existe
echo "<h2>3. Búsqueda con término 'xyz123':</h2>";
$resultado_no_existe = obtenerTours(1, 50, ['busqueda' => 'xyz123']);
if ($resultado_no_existe['success']) {
    echo "<p>Tours encontrados: " . $resultado_no_existe['total'] . "</p>";
    if ($resultado_no_existe['total'] > 0) {
        foreach ($resultado_no_existe['data'] as $tour) {
            echo "<p>- " . htmlspecialchars($tour['titulo']) . " | " . htmlspecialchars(substr($tour['descripcion'], 0, 50)) . "...</p>";
        }
    } else {
        echo "<p>No se encontraron tours (esto es correcto)</p>";
    }
} else {
    echo "<p style='color: red;'>Error: " . $resultado_no_existe['message'] . "</p>";
}

// Prueba 4: Información de debug
echo "<h2>4. Debug de la última consulta:</h2>";
if (isset($resultado_busqueda['debug_sql'])) {
    echo "<pre>SQL: " . htmlspecialchars($resultado_busqueda['debug_sql']) . "</pre>";
    echo "<pre>Parámetros: " . print_r($resultado_busqueda['debug_params'], true) . "</pre>";
}

echo "<p><a href='index.php'>Volver al panel principal</a></p>";
?>
