<?php
/**
 * Script para insertar tours de ejemplo
 * Antares Travel - Módulo de Tours
 */

require_once __DIR__ . '/../config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "🚀 Insertando tours de ejemplo...\n\n";

    $tours_ejemplo = [
        [
            'titulo' => 'Machu Picchu Full Day',
            'descripcion' => 'Descubre la majestuosa ciudadela inca de Machu Picchu en un tour completo de un día. Incluye transporte, guía especializado y entrada al santuario histórico.',
            'precio' => 180.00,
            'duracion' => '12 horas',
            'id_region' => 1, // Cusco
            'dificultad' => 'Moderado',
            'capacidad_maxima' => 15,
            'incluye' => "• Transporte turístico\n• Guía oficial certificado\n• Entrada a Machu Picchu\n• Almuerzo buffet\n• Seguro de viaje",
            'no_incluye' => "• Bebidas adicionales\n• Propinas\n• Gastos personales",
            'lugar_salida' => 'Plaza de Armas del Cusco',
            'ubicacion' => 'Machu Picchu, Cusco',
            'id_guia' => 1,
            'lugar_llegada' => 'Plaza de Armas del Cusco',
            'hora_salida' => '05:30:00',
            'hora_llegada' => '18:00:00',
            'recomendaciones' => 'Llevar ropa cómoda y bloqueador solar',
            'que_llevar' => 'Documento de identidad, cámara fotográfica, agua',
            'politicas' => 'Cancelación gratuita hasta 24 horas antes'
        ],
        [
            'titulo' => 'Valle Sagrado de los Incas',
            'descripcion' => 'Explora los pueblos mágicos del Valle Sagrado: Pisac, Ollantaytambo y Chinchero. Una experiencia cultural única.',
            'precio' => 120.00,
            'duracion' => '10 horas',
            'id_region' => 1, // Cusco
            'dificultad' => 'Fácil',
            'capacidad_maxima' => 20,
            'incluye' => "• Transporte privado\n• Guía bilingüe\n• Entradas a sitios arqueológicos\n• Almuerzo típico\n• Degustación de chicha",
            'no_incluye' => "• Bebidas alcohólicas\n• Souvenirs\n• Propinas opcionales",
            'lugar_salida' => 'Hotel en Cusco',
            'ubicacion' => 'Valle Sagrado, Cusco',
            'id_guia' => 2,
            'lugar_llegada' => 'Hotel en Cusco',
            'hora_salida' => '08:00:00',
            'hora_llegada' => '18:00:00',
            'recomendaciones' => 'Ideal para toda la familia',
            'que_llevar' => 'Sombrero, protector solar, cámara',
            'politicas' => 'Reembolso 50% por cancelación con 48h de anticipación'
        ],
        [
            'titulo' => 'Islas Ballestas y Oasis de Huacachina',
            'descripcion' => 'Combina naturaleza y aventura visitando las Islas Ballestas y el impresionante oasis de Huacachina en Ica.',
            'precio' => 95.00,
            'duracion' => '8 horas',
            'id_region' => 4, // Ica
            'dificultad' => 'Fácil',
            'capacidad_maxima' => 25,
            'incluye' => "• Transporte en bus turístico\n• Paseo en lancha a Islas Ballestas\n• Sandboarding en Huacachina\n• Guía especializado\n• Almuerzo",
            'no_incluye' => "• Entrada al paracas (S/11)\n• Bebidas\n• Propinas",
            'lugar_salida' => 'Terminal Cruz del Sur Lima',
            'ubicacion' => 'Paracas e Ica',
            'id_guia' => 3,
            'lugar_llegada' => 'Terminal Cruz del Sur Lima',
            'hora_salida' => '04:00:00',
            'hora_llegada' => '22:00:00',
            'recomendaciones' => 'Llevar ropa ligera y lentes de sol',
            'que_llevar' => 'Bloqueador solar, agua, ropa de cambio',
            'politicas' => 'No reembolsable por mal tiempo'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO tours (
            titulo, descripcion, precio, duracion, id_region, dificultad,
            capacidad_maxima, incluye, no_incluye, lugar_salida,
            id_guia, lugar_llegada, hora_salida, hora_llegada,
            recomendaciones
        ) VALUES (
            :titulo, :descripcion, :precio, :duracion, :id_region, :dificultad,
            :capacidad_maxima, :incluye, :no_incluye, :lugar_salida,
            :id_guia, :lugar_llegada, :hora_salida, :hora_llegada,
            :recomendaciones
        )
    ");

    foreach ($tours_ejemplo as $tour) {
        // Remover los campos que no están en la tabla para esta inserción
        $tour_insert = [
            'titulo' => $tour['titulo'],
            'descripcion' => $tour['descripcion'],
            'precio' => $tour['precio'],
            'duracion' => $tour['duracion'],
            'id_region' => $tour['id_region'],
            'dificultad' => $tour['dificultad'],
            'capacidad_maxima' => $tour['capacidad_maxima'],
            'incluye' => $tour['incluye'],
            'no_incluye' => $tour['no_incluye'],
            'lugar_salida' => $tour['lugar_salida'],
            'id_guia' => $tour['id_guia'],
            'lugar_llegada' => $tour['lugar_llegada'],
            'hora_salida' => $tour['hora_salida'],
            'hora_llegada' => $tour['hora_llegada'],
            'recomendaciones' => $tour['recomendaciones']
        ];
        
        $stmt->execute($tour_insert);
        echo "✅ Tour '{$tour['titulo']}' insertado\n";
    }

    echo "\n🎉 Tours de ejemplo insertados correctamente\n";
    
    // Mostrar estadísticas actualizadas
    $total_tours = $pdo->query("SELECT COUNT(*) as total FROM tours WHERE estado = 'Activo'")->fetch()['total'];
    echo "📊 Total de tours activos: {$total_tours}\n";

} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
