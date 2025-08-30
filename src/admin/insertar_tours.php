<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    
    // Solo insertar tours si no existen
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM tours');
    $resultado = $stmt->fetch();
    
    if ($resultado['total'] > 0) {
        echo "Ya hay {$resultado['total']} tours en la base de datos.\n";
        echo "¿Desea agregar más tours? Los existentes no se duplicarán.\n";
    }
    
    $tours_sql = "INSERT INTO tours (titulo, descripcion, precio, duracion, id_region, lugar_salida, lugar_llegada, hora_salida, hora_llegada) VALUES
('City Tour Lima Histórica', 'Recorrido por el centro histórico de Lima, visitando la Plaza Mayor, Catedral, Palacio de Gobierno y principales atractivos coloniales.', 45.00, '4 horas', 1, 'Plaza Mayor', 'Centro Histórico', '09:00:00', '13:00:00'),
('Tour Gastronómico Lima', 'Experiencia culinaria por los mejores mercados y restaurantes de Lima, degustando platos típicos peruanos.', 80.00, '5 horas', 1, 'Mercado Central', 'Surquillo', '10:00:00', '15:00:00'),
('Machu Picchu Full Day', 'Tour completo a la ciudadela inca de Machu Picchu, una de las maravillas del mundo moderno.', 450.00, '16 horas', 2, 'Estación San Pedro', 'Machu Picchu', '05:00:00', '21:00:00'),
('Salineras de Maras y Moray', 'Visita a las impresionantes salineras de Maras y los andenes circulares de Moray en el Valle Sagrado.', 75.00, '6 horas', 2, 'Cusco Centro', 'Valle Sagrado', '08:00:00', '14:00:00'),
('Valle del Colca', 'Excursión al impresionante Cañón del Colca para observar cóndores y paisajes andinos espectaculares.', 120.00, '12 horas', 3, 'Arequipa', 'Cañón del Colca', '06:00:00', '18:00:00'),
('Islas Ballestas', 'Navegación a las Islas Ballestas para observar lobos marinos, pingüinos y aves marinas en su hábitat natural.', 35.00, '3 horas', 4, 'Puerto de Paracas', 'Islas Ballestas', '08:00:00', '11:00:00'),
('Huacachina y Sandboarding', 'Aventura en el oasis de Huacachina con sandboarding y paseo en buggies por las dunas de Ica.', 65.00, '4 horas', 4, 'Ica Centro', 'Huacachina', '14:00:00', '18:00:00'),
('Chan Chan y Huanchaco', 'Visita a la ciudadela de barro más grande de América precolombina y las playas de Huanchaco.', 55.00, '6 horas', 6, 'Trujillo', 'Huanchaco', '09:00:00', '15:00:00'),
('Cordillera Blanca', 'Trekking de un día en la Cordillera Blanca, visitando las lagunas turquesa de Llanganuco.', 95.00, '10 horas', 7, 'Huaraz', 'Llanganuco', '07:00:00', '17:00:00'),
('Lago Titicaca - Islas Flotantes', 'Navegación en el lago navegable más alto del mundo visitando las islas flotantes de los Uros.', 70.00, '8 horas', 8, 'Puerto Puno', 'Isla Taquile', '07:30:00', '15:30:00')";
    
    echo "Insertando tours...\n";
    $pdo->exec($tours_sql);
    
    // Verificar tours insertados
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM tours');
    $resultado = $stmt->fetch();
    echo "✓ Total de tours en la base de datos: {$resultado['total']}\n\n";
    
    // Mostrar algunos tours
    $stmt = $pdo->query('SELECT id_tour, titulo, precio, duracion FROM tours LIMIT 5');
    $tours = $stmt->fetchAll();
    echo "Tours disponibles (primeros 5):\n";
    foreach ($tours as $tour) {
        echo "- {$tour['titulo']} (S/ {$tour['precio']}, {$tour['duracion']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
