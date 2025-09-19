<?php
require_once '../config/config.php';

try {
    $connection = getConnection();
    
    echo "=== LIMPIEZA DE REGISTROS HUÉRFANOS ===\n\n";
    
    // Encontrar registros con fotos faltantes
    $choferes_sql = "SELECT id_chofer, nombre, apellido, foto_url FROM choferes WHERE foto_url IS NOT NULL AND foto_url != ''";
    $choferes_stmt = $connection->query($choferes_sql);
    $choferes = $choferes_stmt->fetchAll();
    
    $limpiados = 0;
    
    foreach ($choferes as $chofer) {
        $foto_url = $chofer['foto_url'];
        
        // Construir ruta del archivo
        $archivo_path = strpos($foto_url, 'storage/uploads/choferes/') === 0 
            ? "../" . $foto_url 
            : "../storage/uploads/choferes/" . $foto_url;
            
        if (!file_exists($archivo_path)) {
            echo "🧹 Limpiando registro huérfano: {$chofer['nombre']} {$chofer['apellido']} - {$foto_url}\n";
            
            $clean_sql = "UPDATE choferes SET foto_url = NULL WHERE id_chofer = ?";
            $clean_stmt = $connection->prepare($clean_sql);
            $clean_stmt->execute([$chofer['id_chofer']]);
            $limpiados++;
        }
    }
    
    echo "\n✅ Registros limpiados: {$limpiados}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
