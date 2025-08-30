<?php
require_once __DIR__ . '/../config/config.php';

$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
$stmt = $pdo->query('SHOW COLUMNS FROM tours LIKE "dificultad"');
$column = $stmt->fetch();
echo "Tipo de columna dificultad: " . $column['Type'] . "\n";
?>
