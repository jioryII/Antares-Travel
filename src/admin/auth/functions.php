<?php
/**
 * Funciones de autenticación para administradores
 */

require_once __DIR__ . '/../../config/conexion.php';

function autenticarAdmin($email, $password) {
    global $conn;
    
    try {
        // Buscar administrador por email
        $stmt = $conn->prepare("SELECT id_admin, nombre, email, password_hash, rol, bloqueado, intentos_fallidos, email_verificado, acceso_aprobado FROM administradores WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
        
        $admin = $result->fetch_assoc();
        
        // Verificar si el email está verificado
        if (!$admin['email_verificado']) {
            return [
                'success' => false, 
                'message' => 'Debes verificar tu correo electrónico antes de iniciar sesión.',
                'require_verification' => true,
                'email' => $admin['email']
            ];
        }

        // Verificar si el acceso está aprobado
        if (!$admin['acceso_aprobado']) {
            return [
                'success' => false, 
                'message' => 'Tu cuenta está pendiente de aprobación por un superadministrador. Te notificaremos cuando sea aprobada.',
                'pending_approval' => true
            ];
        }
        
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
        
        // Generar token de verificación
        $token_verificacion = bin2hex(random_bytes(32));
        $token_expira = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Insertar nuevo administrador (email_verificado = FALSE, acceso_aprobado = FALSE por defecto)
        $stmt = $conn->prepare("INSERT INTO administradores (nombre, email, password_hash, rol, email_verificado, acceso_aprobado, token_verificacion, token_expira) VALUES (?, ?, ?, 'admin', FALSE, FALSE, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $email, $password_hash, $token_verificacion, $token_expira);
        
        if ($stmt->execute()) {
            $admin_id = $stmt->insert_id;
            
            // Enviar correo de verificación
            require_once __DIR__ . '/enviar_correo_admin.php';
            $link = "https://antarestravelperu.com/src/admin/auth/verificar_email_admin.php?token=" . $token_verificacion;
            $resultado_correo = enviarCorreoVerificacionAdmin($email, $nombre, $link);
            
            if ($resultado_correo === true) {
                return [
                    'success' => true, 
                    'message' => 'Administrador registrado exitosamente. Se ha enviado un correo de verificación a tu email.',
                    'admin_id' => $admin_id,
                    'require_verification' => true
                ];
            } else {
                // Si falla el correo, eliminar el admin creado para mantener consistencia
                $stmt_delete = $conn->prepare("DELETE FROM administradores WHERE id_admin = ?");
                $stmt_delete->bind_param("i", $admin_id);
                $stmt_delete->execute();
                
                return [
                    'success' => false, 
                    'message' => 'Error al enviar el correo de verificación: ' . $resultado_correo
                ];
            }
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

/**
 * Reenvía correo de verificación para administrador
 */
function reenviarVerificacionAdmin($email) {
    global $conn;
    
    try {
        // Buscar administrador por email
        $stmt = $conn->prepare("SELECT id_admin, nombre, email, email_verificado, token_verificacion, token_expira FROM administradores WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'No existe una cuenta de administrador con ese correo'];
        }

        $admin = $result->fetch_assoc();

        if ($admin['email_verificado']) {
            return ['success' => false, 'message' => 'El correo ya está verificado'];
        }

        // Verificar si tiene un token válido vigente
        $tieneTokenValido = false;
        if ($admin['token_verificacion'] && $admin['token_expira']) {
            $tieneTokenValido = strtotime($admin['token_expira']) > time();
        }

        $token = $admin['token_verificacion'];
        
        if (!$tieneTokenValido) {
            // Generar nuevo token
            $token = bin2hex(random_bytes(32));
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmtUpdate = $conn->prepare("UPDATE administradores SET token_verificacion = ?, token_expira = ? WHERE id_admin = ?");
            $stmtUpdate->bind_param("ssi", $token, $fechaExpiracion, $admin['id_admin']);
            $stmtUpdate->execute();
        }

        // Enviar correo de verificación
        require_once __DIR__ . '/enviar_correo_admin.php';
        $nombre = $admin['nombre'];
        $link = "https://antarestravelperu.com/src/admin/auth/verificar_email_admin.php?token=" . $token;
        $resultado = enviarCorreoVerificacionAdmin($email, $nombre, $link);

        if ($resultado === true) {
            return ['success' => true, 'message' => 'Correo de verificación enviado correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al enviar el correo: ' . $resultado];
        }

    } catch (Exception $e) {
        error_log("Error al reenviar verificación admin: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor'];
    }
}

/**
 * Notifica a superadministradores sobre nueva solicitud de acceso
 */
function notificarSuperadministradores($id_admin_solicitante) {
    global $conn;
    
    try {
        // Obtener datos del administrador solicitante
        $stmt = $conn->prepare("SELECT nombre, email FROM administradores WHERE id_admin = ? LIMIT 1");
        $stmt->bind_param("i", $id_admin_solicitante);
        $stmt->execute();
        $result = $stmt->get_result();
        $solicitante = $result->fetch_assoc();
        
        if (!$solicitante) {
            return ['success' => false, 'message' => 'Administrador solicitante no encontrado'];
        }

        // Generar tokens de aprobación y rechazo
        $token_aprobacion = bin2hex(random_bytes(32));
        $token_rechazo = bin2hex(random_bytes(32));
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+72 hours'));

        // Insertar tokens en tabla de aprobación
        $stmt_token = $conn->prepare("INSERT INTO tokens_aprobacion (id_admin_solicitante, token_aprobacion, token_rechazo, fecha_expiracion) VALUES (?, ?, ?, ?)");
        $stmt_token->bind_param("isss", $id_admin_solicitante, $token_aprobacion, $token_rechazo, $fecha_expiracion);
        $stmt_token->execute();

        // Buscar todos los superadministradores
        $stmt_superadmins = $conn->prepare("SELECT nombre, email FROM administradores WHERE rol = 'superadmin' AND email_verificado = TRUE AND acceso_aprobado = TRUE");
        $stmt_superadmins->execute();
        $result_superadmins = $stmt_superadmins->get_result();

        $correos_enviados = 0;
        require_once __DIR__ . '/enviar_correo_admin.php';

        while ($superadmin = $result_superadmins->fetch_assoc()) {
            $resultado = enviarCorreoSolicitudAprobacion(
                $superadmin['email'], 
                $superadmin['nombre'], 
                $solicitante['nombre'], 
                $solicitante['email'],
                $token_aprobacion,
                $token_rechazo
            );
            
            if ($resultado === true) {
                $correos_enviados++;
            }
        }

        return [
            'success' => true, 
            'message' => "Notificación enviada a $correos_enviados superadministradores",
            'correos_enviados' => $correos_enviados
        ];

    } catch (Exception $e) {
        error_log("Error al notificar superadministradores: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor'];
    }
}

/**
 * Procesa aprobación o rechazo de administrador
 */
function procesarAprobacionAdmin($token, $accion, $admin_aprobador_id) {
    global $conn;
    
    try {
        // Determinar qué token usar según la acción
        $campo_token = ($accion === 'aprobar') ? 'token_aprobacion' : 'token_rechazo';
        
        // Buscar token válido no procesado
        $stmt = $conn->prepare("
            SELECT ta.*, a.nombre, a.email 
            FROM tokens_aprobacion ta 
            JOIN administradores a ON ta.id_admin_solicitante = a.id_admin 
            WHERE ta.$campo_token = ? 
            AND ta.fecha_expiracion > NOW() 
            AND ta.procesado = FALSE 
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Token inválido o expirado'];
        }

        $datos = $result->fetch_assoc();
        $id_solicitante = $datos['id_admin_solicitante'];
        $nombre_solicitante = $datos['nombre'];
        $email_solicitante = $datos['email'];

        if ($accion === 'aprobar') {
            // Aprobar administrador
            $stmt_aprobar = $conn->prepare("
                UPDATE administradores 
                SET acceso_aprobado = TRUE, 
                    aprobado_por = ?, 
                    fecha_aprobacion = NOW() 
                WHERE id_admin = ?
            ");
            $stmt_aprobar->bind_param("ii", $admin_aprobador_id, $id_solicitante);
            $stmt_aprobar->execute();

            // Enviar correo de aprobación
            require_once __DIR__ . '/enviar_correo_admin.php';
            enviarCorreoAccesoAprobado($email_solicitante, $nombre_solicitante);

            $mensaje = "Administrador $nombre_solicitante aprobado exitosamente";
        } else {
            // Rechazar administrador - eliminar registro
            $stmt_rechazar = $conn->prepare("DELETE FROM administradores WHERE id_admin = ?");
            $stmt_rechazar->bind_param("i", $id_solicitante);
            $stmt_rechazar->execute();

            // Enviar correo de rechazo (opcional)
            require_once __DIR__ . '/enviar_correo_admin.php';
            enviarCorreoAccesoRechazado($email_solicitante, $nombre_solicitante);

            $mensaje = "Solicitud de $nombre_solicitante rechazada";
        }

        // Marcar token como procesado
        $stmt_procesado = $conn->prepare("UPDATE tokens_aprobacion SET procesado = TRUE WHERE id = ?");
        $stmt_procesado->bind_param("i", $datos['id']);
        $stmt_procesado->execute();

        return ['success' => true, 'message' => $mensaje];

    } catch (Exception $e) {
        error_log("Error al procesar aprobación admin: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor'];
    }
}
?>
