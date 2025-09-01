<?php
/**
 * Tests de Funcionalidad - Módulo de Vehículos
 * Antares Travel - Sistema de Gestión Vehicular
 */

// Incluir configuración
require_once '../../../config/config.php';
require_once 'config.php';

class VehiculosTestSuite {
    private $pdo;
    private $tests_passed = 0;
    private $tests_failed = 0;
    private $results = [];

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->log("✅ Conexión a base de datos establecida");
        } catch (PDOException $e) {
            $this->log("❌ Error de conexión: " . $e->getMessage());
            exit(1);
        }
    }

    private function log($message) {
        echo "[" . date('H:i:s') . "] " . $message . "\n";
        $this->results[] = $message;
    }

    private function assert($condition, $message) {
        if ($condition) {
            $this->tests_passed++;
            $this->log("✅ PASS: " . $message);
            return true;
        } else {
            $this->tests_failed++;
            $this->log("❌ FAIL: " . $message);
            return false;
        }
    }

    public function runAllTests() {
        $this->log("🚀 Iniciando pruebas del módulo de vehículos...\n");

        $this->testDatabaseTables();
        $this->testVehiculoValidation();
        $this->testPlacaValidation();
        $this->testConfigConstants();
        $this->testDatabaseOperations();
        $this->testFileStructure();

        $this->log("\n📊 Resumen de pruebas:");
        $this->log("✅ Pruebas exitosas: " . $this->tests_passed);
        $this->log("❌ Pruebas fallidas: " . $this->tests_failed);
        $this->log("📈 Total de pruebas: " . ($this->tests_passed + $this->tests_failed));

        if ($this->tests_failed === 0) {
            $this->log("🎉 ¡Todas las pruebas pasaron exitosamente!");
        } else {
            $this->log("⚠️  Algunas pruebas fallaron. Revisar implementación.");
        }
    }

    private function testDatabaseTables() {
        $this->log("\n🔍 Verificando estructura de base de datos...");

        // Verificar tabla vehiculos
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'vehiculos'");
        $this->assert($stmt->rowCount() > 0, "Tabla 'vehiculos' existe");

        // Verificar tabla choferes
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'choferes'");
        $this->assert($stmt->rowCount() > 0, "Tabla 'choferes' existe");

        // Verificar columnas de vehiculos
        $stmt = $this->pdo->query("DESCRIBE vehiculos");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required_columns = ['id', 'placa', 'marca', 'modelo', 'anio', 'capacidad', 'estado', 'chofer_id'];
        foreach ($required_columns as $column) {
            $this->assert(in_array($column, $columns), "Columna '$column' existe en tabla vehiculos");
        }
    }

    private function testVehiculoValidation() {
        $this->log("\n🔍 Verificando validaciones de vehículos...");

        // Test validación de capacidad
        $valid_capacity = validarCapacidad('bus', 30);
        $this->assert($valid_capacity === true, "Validación de capacidad para bus (30 pasajeros)");

        $invalid_capacity = validarCapacidad('van', 50);
        $this->assert($invalid_capacity === false, "Validación rechaza capacidad inválida para van (50 pasajeros)");

        // Test generación de código
        $codigo = generarCodigoVehiculo('Toyota', 'Hiace', 'ABC-123');
        $this->assert(!empty($codigo), "Generación de código de vehículo");
        $this->assert(strpos($codigo, 'TO') === 0, "Código contiene prefijo de marca");
    }

    private function testPlacaValidation() {
        $this->log("\n🔍 Verificando validación de placas...");

        // Test placas válidas
        $placa_antigua = validarPlaca('AB-1234');
        $this->assert($placa_antigua['valida'] === true, "Validación de placa formato antiguo");

        $placa_nueva = validarPlaca('ABC-123');
        $this->assert($placa_nueva['valida'] === true, "Validación de placa formato nuevo");

        // Test placas inválidas
        $placa_invalida = validarPlaca('123-ABC');
        $this->assert($placa_invalida['valida'] === false, "Rechazo de placa con formato inválido");
    }

    private function testConfigConstants() {
        $this->log("\n🔍 Verificando constantes de configuración...");

        $this->assert(defined('ESTADOS_VEHICULO'), "Constante ESTADOS_VEHICULO definida");
        $this->assert(defined('TIPOS_VEHICULO'), "Constante TIPOS_VEHICULO definida");
        $this->assert(defined('MARCAS_VEHICULO'), "Constante MARCAS_VEHICULO definida");

        // Verificar estructura de estados
        $this->assert(isset(ESTADOS_VEHICULO['activo']), "Estado 'activo' configurado");
        $this->assert(isset(ESTADOS_VEHICULO['mantenimiento']), "Estado 'mantenimiento' configurado");
        $this->assert(isset(ESTADOS_VEHICULO['fuera_servicio']), "Estado 'fuera_servicio' configurado");

        // Verificar tipos de vehículo
        $this->assert(count(TIPOS_VEHICULO) >= 4, "Al menos 4 tipos de vehículo configurados");
    }

    private function testDatabaseOperations() {
        $this->log("\n🔍 Verificando operaciones de base de datos...");

        try {
            // Test consulta de vehículos
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM vehiculos LIMIT 1");
            $this->assert($stmt !== false, "Consulta SELECT en tabla vehiculos");

            // Test consulta con JOIN
            $stmt = $this->pdo->query("
                SELECT v.*, c.nombre as chofer_nombre 
                FROM vehiculos v 
                LEFT JOIN choferes c ON v.chofer_id = c.id 
                LIMIT 1
            ");
            $this->assert($stmt !== false, "Consulta con JOIN vehiculos-choferes");

            // Test consulta de estadísticas
            $stmt = $this->pdo->query("
                SELECT estado, COUNT(*) as total 
                FROM vehiculos 
                GROUP BY estado
            ");
            $this->assert($stmt !== false, "Consulta de estadísticas por estado");

        } catch (PDOException $e) {
            $this->assert(false, "Operaciones de base de datos: " . $e->getMessage());
        }
    }

    private function testFileStructure() {
        $this->log("\n🔍 Verificando estructura de archivos...");

        $required_files = [
            'index.php',
            'ver.php',
            'crear.php',
            'editar.php',
            'eliminar.php',
            'gestionar_chofer.php',
            'exportar.php',
            'config.php',
            'README.md'
        ];

        $base_path = __DIR__ . '/';
        
        foreach ($required_files as $file) {
            $file_path = $base_path . $file;
            $this->assert(file_exists($file_path), "Archivo '$file' existe");
            
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($file_path);
                $this->assert(strpos($content, '<?php') === 0, "Archivo '$file' tiene sintaxis PHP válida");
            }
        }
    }

    public function testPerformance() {
        $this->log("\n⚡ Ejecutando pruebas de rendimiento...");

        $start_time = microtime(true);
        
        // Simular consulta compleja
        $stmt = $this->pdo->query("
            SELECT 
                v.*,
                c.nombre as chofer_nombre,
                COUNT(DISTINCT t.id) as total_tours,
                MAX(t.fecha) as ultimo_tour
            FROM vehiculos v
            LEFT JOIN choferes c ON v.chofer_id = c.id
            LEFT JOIN tours_diarios t ON v.id = t.vehiculo_id
            GROUP BY v.id
            ORDER BY v.placa
            LIMIT 50
        ");
        
        $execution_time = microtime(true) - $start_time;
        $this->assert($execution_time < 1.0, "Consulta compleja ejecuta en menos de 1 segundo");
        $this->log("⏱️  Tiempo de ejecución: " . round($execution_time * 1000, 2) . "ms");
    }

    public function generateReport() {
        $this->log("\n📋 Generando reporte de pruebas...");
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => $this->tests_passed + $this->tests_failed,
            'passed' => $this->tests_passed,
            'failed' => $this->tests_failed,
            'success_rate' => round(($this->tests_passed / ($this->tests_passed + $this->tests_failed)) * 100, 2),
            'results' => $this->results
        ];

        $report_json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents('test_report.json', $report_json);
        
        $this->log("📄 Reporte guardado en test_report.json");
        
        return $report;
    }
}

// Ejecutar pruebas si el archivo se ejecuta directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>Tests Módulo Vehículos</title></head><body><pre>\n";
    
    $test_suite = new VehiculosTestSuite();
    $test_suite->runAllTests();
    $test_suite->testPerformance();
    $test_suite->generateReport();
    
    echo "\n</pre></body></html>";
}
?>
