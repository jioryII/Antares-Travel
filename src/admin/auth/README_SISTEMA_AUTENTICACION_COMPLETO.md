# Sistema de Autenticación y Aprobación de Administradores - Antares Travel

## 📋 Resumen del Sistema

Sistema completo de autenticación de administradores con verificación de correo electrónico, aprobación por superadministradores y flujo automático de gestión de solicitudes.

## 🏗️ Arquitectura del Sistema

### Roles de Usuario

- **`admin`**: Administrador regular con acceso básico al panel
- **`superadmin`**: Superadministrador con capacidad de aprobar/rechazar nuevos administradores

### Estados de Cuenta

- **`email_verificado`**: Correo electrónico confirmado por el usuario
- **`acceso_aprobado`**: Cuenta aprobada por un superadministrador
- **`bloqueado`**: Cuenta bloqueada por intentos fallidos o administrativamente

## 🔄 Flujo Completo del Sistema

### 1. 📝 Registro de Nuevo Administrador

```
Usuario completa formulario → Validación de datos → Inserción en BD → Envío de correo de verificación
```

**Archivos involucrados:**

- `register.php` - Formulario de registro
- `functions.php::registrarAdmin()` - Lógica de registro
- `enviar_correo_admin.php::enviarCorreoVerificacionAdmin()` - Envío de correo

**Estado inicial:**

- `email_verificado = FALSE`
- `acceso_aprobado = FALSE`
- `token_verificacion = generado (32 bytes hex)`
- `token_expira = +24 horas`

### 2. ✅ Verificación de Correo Electrónico

```
Usuario recibe correo → Clic en enlace → Verificación de token → Marca email como verificado → Notificación a superadministradores
```

**URL de verificación:**

```
https://antarestravelperu.com/src/admin/auth/verificar_email_admin.php?token=<token_32_hex>
```

**Archivos involucrados:**

- `verificar_email_admin.php` - Procesamiento de verificación
- `functions.php::notificarSuperadministradores()` - Notificación automática

**Proceso:**

1. Validación del token (existencia, expiración)
2. Actualización: `email_verificado = TRUE`
3. Generación de tokens de aprobación/rechazo
4. Envío de correos a todos los superadministradores activos

### 3. 📧 Solicitud de Aprobación

Los superadministradores reciben correo con dos opciones:

**Estructura del correo:**

```html
Nuevo Administrador Solicita Acceso 👤 Nombre: [Nombre del solicitante] 📧 Email: [email@domain.com]
[APROBAR ACCESO] [RECHAZAR SOLICITUD]
```

**URLs generadas:**

- **Aprobar**: `aprobar_admin.php?token=<token_aprobacion>&accion=aprobar`
- **Rechazar**: `aprobar_admin.php?token=<token_rechazo>&accion=rechazar`

**Tokens generados:**

- `token_aprobacion` - 32 bytes hex, expira en 72 horas
- `token_rechazo` - 32 bytes hex, expira en 72 horas

### 4. 🔐 Flujo de Aprobación Automática

#### Caso A: Superadministrador ya logueado

```
Clic en enlace → Verificación de sesión → Procesamiento automático → Confirmación
```

#### Caso B: Superadministrador no logueado

```
Clic en enlace → Página de aprobación → "Iniciar Sesión" (preservando token) → Login → Procesamiento automático → Confirmación
```

**Archivos involucrados:**

- `aprobar_admin.php` - Página principal de aprobación
- `login.php` - Login con preservación de parámetros
- `functions.php::autenticarAdmin()` - Autenticación

**Características del flujo automático:**

- **Preservación de parámetros**: Token y acción se mantienen durante toda la navegación
- **Campos ocultos**: Los parámetros GET se convierten en campos POST para seguridad
- **Redirección automática**: Después del login exitoso, procesamiento sin intervención adicional
- **Validación de roles**: Solo superadministradores pueden aprobar

### 5. ✅ Procesamiento de Aprobación

#### Aprobación (`accion=aprobar`):

```php
UPDATE administradores
SET acceso_aprobado = true,
    aprobado_por = [id_superadmin],
    fecha_aprobacion = NOW()
WHERE id_admin = [id_solicitante]
```

**Acciones posteriores:**

1. Marcar token como procesado: `procesado = TRUE`
2. Enviar correo de confirmación al solicitante
3. Mostrar página de éxito al superadministrador

#### Rechazo (`accion=rechazar`):

```php
DELETE FROM administradores WHERE id_admin = [id_solicitante]
```

**Acciones posteriores:**

1. Marcar token como procesado: `procesado = TRUE`
2. Enviar correo de notificación de rechazo
3. Mostrar página de confirmación al superadministrador

## 📊 Base de Datos

### Tabla `administradores`

```sql
CREATE TABLE administradores (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'superadmin') DEFAULT 'admin',
    email_verificado BOOLEAN DEFAULT FALSE,
    acceso_aprobado BOOLEAN DEFAULT FALSE,
    bloqueado BOOLEAN DEFAULT FALSE,
    token_verificacion VARCHAR(64),
    token_expira DATETIME,
    ultimo_login DATETIME,
    intentos_fallidos INT DEFAULT 0,
    aprobado_por INT,
    fecha_aprobacion DATETIME,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabla `tokens_aprobacion`

```sql
CREATE TABLE tokens_aprobacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_admin_solicitante INT NOT NULL,
    token_aprobacion VARCHAR(64) NOT NULL,
    token_rechazo VARCHAR(64) NOT NULL,
    fecha_expiracion DATETIME NOT NULL,
    procesado BOOLEAN DEFAULT FALSE,
    fecha_procesado DATETIME,
    FOREIGN KEY (id_admin_solicitante) REFERENCES administradores(id_admin)
);
```

## 🎯 Estados de Acceso

### Acceso Denegado

- ❌ Email no verificado
- ❌ Acceso no aprobado
- ❌ Cuenta bloqueada
- ❌ Credenciales incorrectas

### Acceso Permitido

- ✅ Email verificado
- ✅ Acceso aprobado
- ✅ Cuenta no bloqueada
- ✅ Credenciales correctas

## 🔧 Funciones Principales

### `functions.php`

#### `autenticarAdmin($email, $password)`

- Validación completa de credenciales y estados
- Control de intentos fallidos (bloqueo automático)
- Creación de sesión PHP
- Actualización de último login

#### `registrarAdmin($nombre, $email, $password)`

- Validación de email único
- Hash seguro de contraseña (PASSWORD_DEFAULT)
- Generación de token de verificación
- Envío automático de correo

#### `notificarSuperadministradores($id_admin_solicitante)`

- Búsqueda de superadministradores activos
- Generación de tokens de aprobación/rechazo
- Envío masivo de correos de notificación

### `enviar_correo_admin.php`

#### `enviarCorreoVerificacionAdmin($email, $nombre, $link)`

- Plantilla HTML personalizada para administradores
- Diseño corporativo (colores naranja/amarillo)
- Instrucciones claras para verificación

#### `enviarCorreoSolicitudAprobacion($email_super, $nombre_super, $nombre_solicitante, $email_solicitante, $token_aprobacion, $token_rechazo)`

- Notificación a superadministradores
- Enlaces directos para aprobar/rechazar
- Información completa del solicitante

#### `enviarCorreoAccesoAprobado($email, $nombre)`

- Confirmación de aprobación
- Enlaces al panel de administración
- Información de bienvenida

#### `enviarCorreoAccesoRechazado($email, $nombre)`

- Notificación de rechazo
- Información sobre el proceso
- Contacto para consultas

## 🎨 Interfaz de Usuario

### Páginas Principales

#### `login.php`

- **Formulario dual**: Login y registro en pestañas
- **Diseño glassmorphism**: Efectos de vidrio y gradientes
- **Mensajes contextuales**: Información sobre verificación y aprobación
- **Preservación de parámetros**: Mantiene tokens durante el login
- **Responsive**: Optimizado para móviles y desktop

#### `aprobar_admin.php`

- **Información del solicitante**: Datos completos del admin a aprobar
- **Estado de la sesión**: Información del superadministrador activo
- **Confirmación visual**: Iconos y colores para éxito/error
- **Redirección inteligente**: Botones hacia dashboard o login según estado

#### `verificar_email_admin.php`

- **Proceso automático**: Verificación sin intervención del usuario
- **Feedback visual**: Confirmación clara del estado
- **Redirección automática**: Hacia login después de verificación

### Características de Diseño

#### Colores Corporativos

- **Primario**: Azul (#1e40af, #3730a3)
- **Secundario**: Naranja (#d97706)
- **Acentos**: Amarillo, Verde, Rojo para estados

#### Efectos Visuales

- **Glassmorphism**: Fondos translúcidos con desenfoque
- **Gradientes**: Transiciones suaves de color
- **Animaciones**: Efectos hover y transiciones
- **Iconografía**: Font Awesome para consistencia

## ⚠️ Consideraciones de Seguridad

### Tokens

- **Longitud**: 32 bytes hexadecimales (64 caracteres)
- **Expiración**: 24h verificación, 72h aprobación
- **Uso único**: Tokens se marcan como procesados
- **Validación**: Existencia, expiración, estado de procesamiento

### Sesiones

- **PHP Sessions**: Manejo seguro de estado de login
- **Variables de sesión**: admin_id, admin_rol, admin_logged_in
- **Validación**: Verificación en cada página protegida

### Contraseñas

- **Hashing**: PASSWORD_DEFAULT (bcrypt)
- **Validación**: Requisitos de complejidad
- **Intentos fallidos**: Bloqueo automático tras múltiples fallos

### Base de Datos

- **Prepared Statements**: Prevención de SQL injection
- **Conexiones**: PDO y MySQLi para compatibilidad
- **Validación**: Sanitización de entradas

## 📈 Métricas y Monitoreo

### Logs del Sistema

- **Intentos de login**: Éxito y fallos
- **Verificaciones**: Tokens utilizados y expirados
- **Aprobaciones**: Registro de decisiones de superadministradores

### Estados Monitoreables

- Administradores pendientes de verificación
- Administradores pendientes de aprobación
- Tokens expirados sin usar
- Cuentas bloqueadas

## 🚀 Mejoras Futuras

### Funcionalidades Adicionales

- **Dashboard de aprobaciones**: Panel para gestionar solicitudes pendientes
- **Historial de actividad**: Log detallado de acciones administrativas
- **Notificaciones en tiempo real**: WebSockets para notificaciones instantáneas
- **Gestión de roles avanzada**: Permisos granulares por módulo

### Optimizaciones

- **Cache de sesiones**: Redis para mejor rendimiento
- **Rate limiting**: Control de solicitudes por IP
- **2FA opcional**: Autenticación de dos factores para superadministradores

## 📞 Contacto y Mantenimiento

**Desarrollador**: Anderson Quispe  
**Email**: andiquispe9422@gmail.com  
**Proyecto**: Antares Travel - Sistema de Administración  
**Fecha**: Septiembre 2025

---

## 🔍 Debugging y Troubleshooting

### Comandos de Verificación

#### Estado de un administrador específico:

```php
php -r "
require_once 'src/config/conexion.php';
\$email = 'admin@example.com';
\$stmt = \$pdo->prepare('SELECT * FROM administradores WHERE email = ?');
\$stmt->execute([\$email]);
\$admin = \$stmt->fetch(PDO::FETCH_ASSOC);
print_r(\$admin);
"
```

#### Tokens de aprobación pendientes:

```php
php -r "
require_once 'src/config/conexion.php';
\$stmt = \$pdo->prepare('SELECT ta.*, a.nombre, a.email FROM tokens_aprobacion ta JOIN administradores a ON ta.id_admin_solicitante = a.id_admin WHERE ta.procesado = FALSE');
\$stmt->execute();
\$tokens = \$stmt->fetchAll(PDO::FETCH_ASSOC);
print_r(\$tokens);
"
```

### Problemas Comunes

1. **Correos no llegan**: Verificar configuración SMTP en `enviar_correo_admin.php`
2. **Tokens expirados**: Regenerar token con `reenviar_verificacion_admin.php`
3. **Redirección incorrecta**: Verificar rutas en `getAdminUrl()` function
4. **Sesiones no persisten**: Verificar `session_start()` en cada página

---

_Este documento refleja el estado actual del sistema implementado hasta septiembre 2025._
