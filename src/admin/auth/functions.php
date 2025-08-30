<?php
/**
 * Funciones de autenticación para administradores
 */

require_once __DIR__ . '/../../config/conexion.php';

function autenticarAdmin($email, $password) {
    global $conn;
    
    try {
        // Buscar administrador por email
        $stmt = $conn->prepare("SELECT id_admin, nombre, email, password_hash, rol, bloqueado, intentos_fallidos FROM administradores WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
        
        $admin = $result->fetch_assoc();
        
        // Verificar si está bloqueado
        if ($admin['bloqueado']) {
            return ['success' => false, 'message' => 'Cuenta bloqueada. Contacte al administrador'];
        }
        
        // Verificar intentos fallidos (máximo 5)
        if ($admin['intentos_fallidos'] >= 5) {
            // Bloquear cuenta
            $stmt_block = $conn->prepare("UPDATE administradores SET bloqueado = TRUE WHERE id_admin = ?");
            $stmt_block->bind_param("i", $admin['id_admin']);
            $stmt_block->execute();
            
            return ['success' => false, 'message' => 'Cuenta bloqueada por múltiples intentos fallidos'];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $admin['password_hash'])) {
            // Incrementar intentos fallidos
            $stmt_fail = $conn->prepare("UPDATE administradores SET intentos_fallidos = intentos_fallidos + 1 WHERE id_admin = ?");
            $stmt_fail->bind_param("i", $admin['id_admin']);
            $stmt_fail->execute();
            
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
        
        // Autenticación exitosa - actualizar último login y resetear intentos
        $stmt_success = $conn->prepare("UPDATE administradores SET ultimo_login = NOW(), intentos_fallidos = 0 WHERE id_admin = ?");
        $stmt_success->bind_param("i", $admin['id_admin']);
        $stmt_success->execute();
        
        // Crear sesión
        $_SESSION['admin_id'] = $admin['id_admin'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_rol'] = $admin['rol'];
        $_SESSION['admin_logged_in'] = true;
        
        return [
            'success' => true, 
            'message' => 'Login exitoso',
            'admin' => [
                'id' => $admin['id_admin'],
                'nombre' => $admin['nombre'],
                'email' => $admin['email'],
                'rol' => $admin['rol']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error en autenticación admin: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor'];
    }
}

function registrarAdmin($nombre, $email, $password) {
    global $conn;
    
    try {
        // Verificar si el email ya existe
        $stmt_check = $conn->prepare("SELECT id_admin FROM administradores WHERE email = ? LIMIT 1");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar nuevo administrador
        $stmt = $conn->prepare("INSERT INTO administradores (nombre, email, password_hash, rol, email_verificado) VALUES (?, ?, ?, 'admin', TRUE)");
        $stmt->bind_param("sss", $nombre, $email, $password_hash);
        
        if ($stmt->execute()) {
            return [
                'success' => true, 
                'message' => 'Administrador registrado exitosamente',
                'admin_id' => $stmt->insert_id
            ];
        } else {
            return ['success' => false, 'message' => 'Error al registrar administrador'];
        }
        
    } catch (Exception $e) {
        error_log("Error en registro admin: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor'];
    }
}

function validarDatosRegistro($nombre, $email, $password, $confirmar_password) {
    $errores = [];
    
    // Validar nombre
    if (empty(trim($nombre))) {
        $errores[] = "El nombre es obligatorio";
    } elseif (strlen(trim($nombre)) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres";
    }
    
    // Validar email
    if (empty(trim($email))) {
        $errores[] = "El email es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del email no es válido";
    }
    
    // Validar contraseña
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Validar confirmación
    if ($password !== $confirmar_password) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    return $errores;
}
?>
