<?php
/**
 * Diagnóstico de Frontend - Panel de Tours
 * Este archivo verifica todas las dependencias y funcionalidades
 */

require_once __DIR__ . '/../../auth/middleware.php';

// Verificar autenticación
try {
    verificarSesionAdmin();
    echo "✅ Autenticación: OK\n";
} catch (Exception $e) {
    echo "❌ Autenticación: FALLO - " . $e->getMessage() . "\n";
    exit;
}

require_once __DIR__ . '/../../functions/tours_functions.php';

echo "\n=== DIAGNÓSTICO DE FRONTEND - TOURS ===\n\n";

// 1. Verificar archivos CSS y JS
$archivos_criticos = [
    '../../assets/css/responsive.css' => 'CSS Responsivo',
    '../../assets/js/responsive.js' => 'JS Responsivo', 
    '../../assets/js/tours.js' => 'JS Tours',
    '../../components/header.php' => 'Header Component',
    '../../components/sidebar.php' => 'Sidebar Component',
    '../../api/tours.php' => 'API Tours'
];

echo "1. VERIFICANDO ARCHIVOS CRÍTICOS:\n";
foreach ($archivos_criticos as $archivo => $descripcion) {
    $ruta_completa = __DIR__ . '/' . $archivo;
    if (file_exists($ruta_completa)) {
        $tamaño = filesize($ruta_completa);
        echo "   ✅ $descripcion: OK ($tamaño bytes)\n";
    } else {
        echo "   ❌ $descripcion: FALTANTE - $ruta_completa\n";
    }
}

// 2. Verificar funciones PHP
echo "\n2. VERIFICANDO FUNCIONES PHP:\n";
$funciones_necesarias = [
    'obtenerTours',
    'obtenerTourPorId',
    'obtenerRegiones',
    'obtenerGuiasDisponibles'
];

foreach ($funciones_necesarias as $funcion) {
    if (function_exists($funcion)) {
        echo "   ✅ Función $funcion: OK\n";
    } else {
        echo "   ❌ Función $funcion: FALTANTE\n";
    }
}

// 3. Probar conexión a base de datos
echo "\n3. PROBANDO CONEXIÓN Y DATOS:\n";
try {
    $resultado = obtenerTours(1, 5);
    if ($resultado['success']) {
        echo "   ✅ Conexión BD: OK\n";
        echo "   ✅ Obtener Tours: OK (" . count($resultado['data']) . " tours encontrados)\n";
    } else {
        echo "   ❌ Obtener Tours: FALLO - " . $resultado['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Conexión BD: FALLO - " . $e->getMessage() . "\n";
}

try {
    $regiones = obtenerRegiones();
    if ($regiones['success']) {
        echo "   ✅ Obtener Regiones: OK (" . count($regiones['data']) . " regiones)\n";
    } else {
        echo "   ❌ Obtener Regiones: FALLO\n";
    }
} catch (Exception $e) {
    echo "   ❌ Obtener Regiones: FALLO - " . $e->getMessage() . "\n";
}

try {
    $guias = obtenerGuiasDisponibles();
    if ($guias['success']) {
        echo "   ✅ Obtener Guías: OK (" . count($guias['data']) . " guías)\n";
    } else {
        echo "   ❌ Obtener Guías: FALLO\n";
    }
} catch (Exception $e) {
    echo "   ❌ Obtener Guías: FALLO - " . $e->getMessage() . "\n";
}

// 4. Verificar estructura de directorios
echo "\n4. VERIFICANDO ESTRUCTURA DE DIRECTORIOS:\n";
$directorios = [
    '../../assets/css' => 'CSS Assets',
    '../../assets/js' => 'JS Assets',
    '../../api' => 'API Directory',
    '../../components' => 'Components',
    '../../functions' => 'Functions',
    '../../config' => 'Config'
];

foreach ($directorios as $dir => $descripcion) {
    $ruta_completa = __DIR__ . '/' . $dir;
    if (is_dir($ruta_completa)) {
        $archivos = count(glob($ruta_completa . '/*'));
        echo "   ✅ $descripcion: OK ($archivos archivos)\n";
    } else {
        echo "   ❌ $descripcion: FALTANTE - $ruta_completa\n";
    }
}

// 5. Verificar permisos
echo "\n5. VERIFICANDO PERMISOS:\n";
$dirs_permisos = [
    '../../storage/uploads' => 'Uploads Directory',
    '../../storage/logs' => 'Logs Directory'
];

foreach ($dirs_permisos as $dir => $descripcion) {
    $ruta_completa = __DIR__ . '/' . $dir;
    if (is_dir($ruta_completa)) {
        if (is_writable($ruta_completa)) {
            echo "   ✅ $descripcion: ESCRIBIBLE\n";
        } else {
            echo "   ⚠️ $descripcion: SOLO LECTURA\n";
        }
    } else {
        echo "   ❌ $descripcion: NO EXISTE\n";
    }
}

// 6. Test de API endpoints
echo "\n6. PROBANDO ENDPOINTS API:\n";

// Simular petición GET para listar
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'listar';
$_GET['pagina'] = 1;

try {
    ob_start();
    include __DIR__ . '/../../api/tours.php';
    $output = ob_get_clean();
    
    $json = json_decode($output, true);
    if ($json && isset($json['success']) && $json['success']) {
        echo "   ✅ API Listar Tours: OK\n";
    } else {
        echo "   ❌ API Listar Tours: FALLO - " . ($json['message'] ?? 'JSON inválido') . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ API Listar Tours: ERROR - " . $e->getMessage() . "\n";
}

// 7. Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "RESUMEN DEL DIAGNÓSTICO:\n";
echo "- ✅ = Funcionando correctamente\n";
echo "- ⚠️ = Funcionando con advertencias\n";
echo "- ❌ = Requiere atención\n";
echo "\n";

// Recomendaciones
echo "RECOMENDACIONES:\n";
echo "1. Verificar que todos los archivos ❌ existan\n";
echo "2. Revisar permisos de directorios ⚠️\n";
echo "3. Probar funcionalidad desde el navegador\n";
echo "4. Verificar consola del navegador (F12) para errores JS\n";
echo "5. Verificar Network tab para errores AJAX\n";

echo "\n" . str_repeat("=", 50) . "\n";
?>
