<?php
// init_db.php
$dbFile = __DIR__ . '/ratings.db';
if (file_exists($dbFile)) {
    echo "La DB ya existe en $dbFile\n";
    exit;
}

$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
CREATE TABLE ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    google_sub TEXT NOT NULL,       -- id único del usuario Google (sub)
    name TEXT,
    email TEXT,
    target_person TEXT NOT NULL,    -- la persona que se califica
    stars INTEGER NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");
echo "Base de datos creada en $dbFile\n";
