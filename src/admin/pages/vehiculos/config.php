<?php
/**
 * Configuración del Módulo de Vehículos
 * Antares Travel - Sistema de Gestión Vehicular
 */

// Estados de vehículos disponibles
const ESTADOS_VEHICULO = [
    'activo' => [
        'label' => 'Activo',
        'color' => 'green',
        'icon' => 'fas fa-check-circle',
        'description' => 'Vehículo operativo y disponible'
    ],
    'mantenimiento' => [
        'label' => 'En Mantenimiento',
        'color' => 'yellow',
        'icon' => 'fas fa-tools',
        'description' => 'Vehículo en proceso de mantenimiento'
    ],
    'fuera_servicio' => [
        'label' => 'Fuera de Servicio',
        'color' => 'red',
        'icon' => 'fas fa-times-circle',
        'description' => 'Vehículo no disponible para tours'
    ]
];

// Tipos de vehículos
const TIPOS_VEHICULO = [
    'bus' => [
        'label' => 'Bus',
        'icon' => 'fas fa-bus',
        'capacidad_min' => 20,
        'capacidad_max' => 50
    ],
    'minibus' => [
        'label' => 'Minibus',
        'icon' => 'fas fa-shuttle-van',
        'capacidad_min' => 8,
        'capacidad_max' => 19
    ],
    'van' => [
        'label' => 'Van',
        'icon' => 'fas fa-car',
        'capacidad_min' => 4,
        'capacidad_max' => 12
    ],
    'automovil' => [
        'label' => 'Automóvil',
        'icon' => 'fas fa-car-side',
        'capacidad_min' => 1,
        'capacidad_max' => 5
    ]
];

// Marcas de vehículos populares en Perú
const MARCAS_VEHICULO = [
    'Toyota', 'Hyundai', 'Chevrolet', 'Nissan', 'Kia', 
    'Volkswagen', 'Ford', 'Honda', 'Mitsubishi', 'Mazda',
    'Iveco', 'Mercedes-Benz', 'Volvo', 'Scania', 'Isuzu'
];

// Configuración de validación de placas peruanas
const PLACA_PATTERNS = [
    'antigua' => '/^[A-Z]{2}-\d{4}$/',  // Ejemplo: AB-1234
    'nueva' => '/^[A-Z]{3}-\d{3}$/',    // Ejemplo: ABC-123
    'especial' => '/^[A-Z]{2}-\d{3}[A-Z]$/', // Ejemplo: AB-123C
];

// Configuración de exportación
const EXPORT_CONFIG = [
    'csv' => [
        'delimiter' => ',',
        'enclosure' => '"',
        'escape' => '\\',
        'encoding' => 'UTF-8',
        'bom' => true // Para compatibilidad con Excel
    ],
    'max_records' => 10000,
    'timeout' => 300 // 5 minutos
];

// Configuración de paginación
const PAGINATION_CONFIG = [
    'items_per_page' => 20,
    'max_items_per_page' => 100,
    'show_pagination_info' => true
];

// Configuración de mantenimiento
const MANTENIMIENTO_CONFIG = [
    'kilometraje_revision' => 10000,
    'dias_revision' => 90,
    'alertas_anticipadas' => 7, // días antes
    'tipos_mantenimiento' => [
        'preventivo' => 'Mantenimiento Preventivo',
        'correctivo' => 'Mantenimiento Correctivo',
        'emergencia' => 'Reparación de Emergencia'
    ]
];

// Configuración de disponibilidad
const DISPONIBILIDAD_CONFIG = [
    'horas_trabajo' => [
        'inicio' => '06:00',
        'fin' => '22:00'
    ],
    'dias_trabajo' => ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
    'tiempo_limpieza' => 30, // minutos entre tours
    'tiempo_preparacion' => 15 // minutos antes del tour
];

/**
 * Función para obtener el color CSS basado en el estado
 */
function getEstadoColor($estado) {
    $colores = [
        'activo' => 'text-green-600 bg-green-100',
        'mantenimiento' => 'text-yellow-600 bg-yellow-100',
        'fuera_servicio' => 'text-red-600 bg-red-100'
    ];
    
    return $colores[$estado] ?? 'text-gray-600 bg-gray-100';
}

/**
 * Función para validar formato de placa peruana
 */
function validarPlaca($placa) {
    foreach (PLACA_PATTERNS as $tipo => $pattern) {
        if (preg_match($pattern, $placa)) {
            return [
                'valida' => true,
                'tipo' => $tipo,
                'formato' => $pattern
            ];
        }
    }
    
    return [
        'valida' => false,
        'error' => 'Formato de placa no válido para Perú'
    ];
}

/**
 * Función para obtener el ícono del tipo de vehículo
 */
function getTipoVehiculoIcon($tipo) {
    return TIPOS_VEHICULO[$tipo]['icon'] ?? 'fas fa-car';
}

/**
 * Función para validar capacidad según tipo de vehículo
 */
function validarCapacidad($tipo, $capacidad) {
    if (!isset(TIPOS_VEHICULO[$tipo])) {
        return false;
    }
    
    $config = TIPOS_VEHICULO[$tipo];
    return $capacidad >= $config['capacidad_min'] && $capacidad <= $config['capacidad_max'];
}

/**
 * Función para formatear kilometraje
 */
function formatearKilometraje($km) {
    if ($km >= 1000000) {
        return number_format($km / 1000000, 1) . 'M km';
    } elseif ($km >= 1000) {
        return number_format($km / 1000, 1) . 'K km';
    }
    return number_format($km) . ' km';
}

/**
 * Función para calcular próximo mantenimiento
 */
function calcularProximoMantenimiento($ultimo_km, $ultimo_fecha, $km_actual) {
    $km_siguiente = $ultimo_km + MANTENIMIENTO_CONFIG['kilometraje_revision'];
    $fecha_siguiente = date('Y-m-d', strtotime($ultimo_fecha . ' + ' . MANTENIMIENTO_CONFIG['dias_revision'] . ' days'));
    
    return [
        'por_kilometraje' => $km_siguiente,
        'por_fecha' => $fecha_siguiente,
        'km_restantes' => max(0, $km_siguiente - $km_actual),
        'dias_restantes' => max(0, ceil((strtotime($fecha_siguiente) - time()) / 86400))
    ];
}

/**
 * Función para generar código de vehículo único
 */
function generarCodigoVehiculo($marca, $modelo, $placa) {
    $codigo_marca = strtoupper(substr($marca, 0, 2));
    $codigo_modelo = strtoupper(substr($modelo, 0, 2));
    $codigo_placa = str_replace('-', '', $placa);
    
    return $codigo_marca . $codigo_modelo . '-' . $codigo_placa;
}

/**
 * Configuración de alertas y notificaciones
 */
const ALERTAS_CONFIG = [
    'mantenimiento_vencido' => [
        'tipo' => 'error',
        'mensaje' => 'Mantenimiento vencido',
        'accion' => 'Programar mantenimiento inmediato'
    ],
    'mantenimiento_proximo' => [
        'tipo' => 'warning',
        'mensaje' => 'Mantenimiento próximo',
        'accion' => 'Programar mantenimiento'
    ],
    'sin_chofer' => [
        'tipo' => 'info',
        'mensaje' => 'Sin chofer asignado',
        'accion' => 'Asignar chofer'
    ],
    'capacidad_excedida' => [
        'tipo' => 'error',
        'mensaje' => 'Capacidad excedida en tour',
        'accion' => 'Verificar asignación'
    ]
];

/**
 * Configuración de reportes
 */
const REPORTES_CONFIG = [
    'formatos' => ['csv', 'pdf', 'excel'],
    'periodo_maximo' => 365, // días
    'incluir_fotos' => false,
    'templates' => [
        'resumen_flota' => 'Resumen General de Flota',
        'mantenimiento' => 'Reporte de Mantenimiento',
        'utilization' => 'Reporte de Utilización',
        'costos' => 'Reporte de Costos Operativos'
    ]
];
?>
