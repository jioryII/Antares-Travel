<?php
/**
 * Versión modificada del middleware para desarrollo
 * Evita el redirect automático que causa Error 500
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function verificarSesionAdmin() {
    // Verificar si existe sesión de admin
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // En lugar de redirect, mostrar mensaje de error
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Acceso Denegado</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; }
                .error { color: #d32f2f; background: #ffebee; padding: 20px; border-radius: 5px; }
                .info { color: #1976d2; background: #e3f2fd; padding: 20px; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="error">
                <h2>🚫 Acceso Denegado</h2>
                <p>Necesitas iniciar sesión como administrador para acceder a esta página.</p>
            </div>
            <div class="info">
                <h3>💡 Para solucionar este problema:</h3>
                <ol>
                    <li>Ve a <a href="/src/admin/auth/login.php">Página de Login</a></li>
                    <li>Inicia sesión con credenciales de administrador</li>
                    <li>Regresa a esta página</li>
                </ol>
            </div>
        </body>
        </html>';
        exit();
    }
    
    // Verificar rol de admin o superadmin
    if (!isset($_SESSION['admin_rol']) || !in_array($_SESSION['admin_rol'], ['admin', 'superadmin'])) {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Permisos Insuficientes</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; }
                .error { color: #d32f2f; background: #ffebee; padding: 20px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="error">
                <h2>⚠️ Permisos Insuficientes</h2>
                <p>No tienes permisos de administrador suficientes.</p>
                <p><a href="/src/admin/auth/login.php">Iniciar sesión con otra cuenta</a></p>
            </div>
        </body>
        </html>';
        exit();
    }
    
    return true;
}

function obtenerAdminActual() {
    // Crear datos de administrador ficticios para desarrollo
    if (!isset($_SESSION['admin_logged_in'])) {
        return [
            'id_admin' => 1,
            'nombre' => 'Admin de Desarrollo',
            'email' => 'admin@test.com',
            'rol' => 'admin'
        ];
    }
    
    return [
        'id_admin' => $_SESSION['admin_id'] ?? 1,
        'nombre' => $_SESSION['admin_nombre'] ?? 'Admin',
        'email' => $_SESSION['admin_email'] ?? 'admin@test.com',
        'rol' => $_SESSION['admin_rol'] ?? 'admin'
    ];
}

function cerrarSesionAdmin() {
    // Limpiar solo variables de sesión de admin
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_nombre']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_rol']);
    unset($_SESSION['admin_logged_in']);
    
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
}

// NO verificar sesión automáticamente para evitar Error 500
// verificarSesionAdmin();
?>
