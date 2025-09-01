<?php
/**
 * Diagnóstico Simple de Frontend
 */

echo "=== DIAGNÓSTICO SIMPLE DE FRONTEND ===\n\n";

// 1. Verificar archivos críticos
$base_path = __DIR__ . '/../../';
$archivos = [
    'assets/css/responsive.css',
    'assets/js/responsive.js',
    'assets/js/tours.js',
    'components/header.php',
    'components/sidebar.php',
    'api/tours.php',
    'functions/tours_functions.php'
];

echo "ARCHIVOS CRÍTICOS:\n";
foreach ($archivos as $archivo) {
    $ruta = $base_path . $archivo;
    if (file_exists($ruta)) {
        $size = filesize($ruta);
        echo "✅ $archivo ($size bytes)\n";
    } else {
        echo "❌ $archivo (FALTANTE)\n";
    }
}

// 2. Verificar directorios
echo "\nDIRECTORIOS:\n";
$dirs = [
    'assets/css',
    'assets/js', 
    'api',
    'components',
    'functions',
    'config'
];

foreach ($dirs as $dir) {
    $ruta = $base_path . $dir;
    if (is_dir($ruta)) {
        $files = count(glob($ruta . '/*'));
        echo "✅ $dir ($files archivos)\n";
    } else {
        echo "❌ $dir (NO EXISTE)\n";
    }
}

// 3. Verificar contenido de archivos clave
echo "\nCONTENIDO DE ARCHIVOS:\n";

// Verificar tours.js
$tours_js = $base_path . 'assets/js/tours.js';
if (file_exists($tours_js)) {
    $content = file_get_contents($tours_js);
    $functions = ['abrirModalCrear', 'editarTour', 'verTour', 'eliminarTour'];
    foreach ($functions as $func) {
        if (strpos($content, $func) !== false) {
            echo "✅ Función $func encontrada en tours.js\n";
        } else {
            echo "❌ Función $func FALTANTE en tours.js\n";
        }
    }
} else {
    echo "❌ tours.js no existe\n";
}

// Verificar responsive.js
$responsive_js = $base_path . 'assets/js/responsive.js';
if (file_exists($responsive_js)) {
    $content = file_get_contents($responsive_js);
    if (strpos($content, 'AntaresMobile') !== false) {
        echo "✅ AntaresMobile encontrado en responsive.js\n";
    } else {
        echo "❌ AntaresMobile FALTANTE en responsive.js\n";
    }
} else {
    echo "❌ responsive.js no existe\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>
