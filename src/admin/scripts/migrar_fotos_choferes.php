<?php
/**
 * Script para migrar fotos de choferes al formato consistente con guías
 * 
 * Este script actualiza las rutas de fotos en la base de datos para que:
 * - Las rutas que solo tienen el nombre del archivo se conviertan a ruta completa
 * - Se mantenga la consistencia con el módulo de guías
 */

require_once '../config/config.php';

try {
    $connection = getConnection();
    
    echo "=== MIGRACIÓN DE FOTOS DE CHOFERES ===\n\n";
    
    // 1. Verificar campo foto_url
    echo "🔍 Verificando estructura de la tabla...\n";
    $check_column_sql = "SHOW COLUMNS FROM choferes LIKE 'foto_url'";
    $check_column_stmt = $connection->prepare($check_column_sql);
    $check_column_stmt->execute();
    $column_exists = $check_column_stmt->fetch();
    
    if (!$column_exists) {
        throw new Exception("El campo 'foto_url' no existe en la tabla choferes");
    }
    echo "✅ Campo 'foto_url' encontrado\n\n";
    
    // 2. Obtener choferes con fotos que necesitan migrar
    echo "📋 Buscando choferes con fotos para migrar...\n";
    $choferes_sql = "SELECT id_chofer, nombre, apellido, foto_url 
                     FROM choferes 
                     WHERE foto_url IS NOT NULL 
                     AND foto_url != ''
                     AND foto_url NOT LIKE 'storage/uploads/choferes/%'";
    $choferes_stmt = $connection->query($choferes_sql);
    $choferes_a_migrar = $choferes_stmt->fetchAll();
    
    echo "📊 Choferes encontrados para migrar: " . count($choferes_a_migrar) . "\n\n";
    
    if (count($choferes_a_migrar) === 0) {
        echo "✅ No hay choferes que necesiten migración. Todas las fotos ya tienen el formato correcto.\n";
        exit(0);
    }
    
    // 3. Procesar cada chofer
    $migrados = 0;
    $errores = 0;
    
    echo "🔄 Iniciando migración...\n\n";
    
    foreach ($choferes_a_migrar as $chofer) {
        $id = $chofer['id_chofer'];
        $nombre = $chofer['nombre'] . ' ' . ($chofer['apellido'] ?? '');
        $foto_actual = $chofer['foto_url'];
        
        // Verificar si el archivo físico existe
        $ruta_archivo_actual = "../../../storage/uploads/choferes/" . $foto_actual;
        
        if (file_exists($ruta_archivo_actual)) {
            // Construir nueva ruta
            $nueva_foto_url = 'storage/uploads/choferes/' . $foto_actual;
            
            // Actualizar en base de datos
            $update_sql = "UPDATE choferes SET foto_url = ? WHERE id_chofer = ?";
            $update_stmt = $connection->prepare($update_sql);
            
            if ($update_stmt->execute([$nueva_foto_url, $id])) {
                echo "✅ {$nombre} (ID: {$id}): {$foto_actual} → {$nueva_foto_url}\n";
                $migrados++;
            } else {
                echo "❌ {$nombre} (ID: {$id}): Error al actualizar base de datos\n";
                $errores++;
            }
        } else {
            echo "⚠️  {$nombre} (ID: {$id}): Archivo no encontrado - {$ruta_archivo_actual}\n";
            
            // Opcionalmente, limpiar registro sin archivo
            $clean_sql = "UPDATE choferes SET foto_url = NULL WHERE id_chofer = ?";
            $clean_stmt = $connection->prepare($clean_sql);
            $clean_stmt->execute([$id]);
            echo "   📝 Registro limpiado (foto_url = NULL)\n";
            $migrados++;
        }
    }
    
    echo "\n=== RESUMEN DE MIGRACIÓN ===\n";
    echo "📊 Total procesados: " . count($choferes_a_migrar) . "\n";
    echo "✅ Migrados exitosamente: {$migrados}\n";
    echo "❌ Errores: {$errores}\n\n";
    
    // 4. Verificar resultado final
    echo "🔍 Verificando resultado final...\n";
    $verificacion_sql = "SELECT 
                           COUNT(*) as total_con_foto,
                           SUM(CASE WHEN foto_url LIKE 'storage/uploads/choferes/%' THEN 1 ELSE 0 END) as formato_correcto,
                           SUM(CASE WHEN foto_url NOT LIKE 'storage/uploads/choferes/%' THEN 1 ELSE 0 END) as formato_legacy
                         FROM choferes 
                         WHERE foto_url IS NOT NULL AND foto_url != ''";
    $verificacion_stmt = $connection->query($verificacion_sql);
    $stats = $verificacion_stmt->fetch();
    
    echo "📈 Estadísticas finales:\n";
    echo "   - Total con foto: {$stats['total_con_foto']}\n";
    echo "   - Formato correcto: {$stats['formato_correcto']}\n";
    echo "   - Formato legacy: {$stats['formato_legacy']}\n\n";
    
    if ($stats['formato_legacy'] == 0) {
        echo "🎉 ¡MIGRACIÓN COMPLETADA! Todas las fotos ahora usan el formato consistente.\n";
        echo "📝 Los choferes ahora guardan fotos igual que los guías: 'storage/uploads/choferes/archivo.jpg'\n";
    } else {
        echo "⚠️  Aún quedan {$stats['formato_legacy']} registros con formato legacy.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Script finalizado.\n";
?>
