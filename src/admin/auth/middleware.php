<?php
/**
 * Middleware de autenticación para administradores
 * Verificar sesión y permisos antes de acceder a páginas del admin
 */

session_start();

function verificarSesionAdmin() {
    // Verificar si existe sesión de admin
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Redirigir a login
        header('Location: ' . '/Antares-Travel/src/admin/auth/login.php');
        exit();
    }
    
    // Verificar rol de admin
    if (!isset($_SESSION['admin_rol']) || $_SESSION['admin_rol'] !== 'admin') {
        // Sin permisos suficientes
        header('Location: ' . '/Antares-Travel/src/admin/auth/login.php?error=insufficient_permissions');
        exit();
    }
    
    return true;
}

function obtenerAdminActual() {
    if (verificarSesionAdmin()) {
        return [
            'id' => $_SESSION['admin_id'],
            'nombre' => $_SESSION['admin_nombre'],
            'email' => $_SESSION['admin_email'],
            'rol' => $_SESSION['admin_rol']
        ];
    }
    return null;
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

// Verificar sesión automáticamente al incluir este archivo
verificarSesionAdmin();
?>
