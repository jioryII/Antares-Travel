# 📋 Resumen Ejecutivo - Sistema de Autenticación de Administradores

## 🎯 Resumen del Flujo Completo

### 1. 🚀 **Registro de Nuevo Administrador**

```
📝 Formulario → ✅ Validación → 💾 BD → 📧 Email Verificación
```

- **Estado inicial**: `email_verificado = FALSE`, `acceso_aprobado = FALSE`
- **Token**: 32 bytes hex, expira en 24 horas
- **Archivo**: `functions.php::registrarAdmin()`

### 2. 🔐 **Verificación de Correo**

```
📧 Correo → 🖱️ Clic enlace → ✅ Token válido → 📧 Notificación Superadmins
```

- **URL**: `verificar_email_admin.php?token=<32_hex>`
- **Acción**: `email_verificado = TRUE`
- **Archivo**: `verificar_email_admin.php`

### 3. 📬 **Notificación a Superadministradores**

```
🔍 Buscar Superadmins → 🎫 Generar Tokens → 📧 Enviar Correos
```

- **Tokens**: `token_aprobacion` y `token_rechazo` (72h expira)
- **Archivo**: `functions.php::notificarSuperadministradores()`

### 4. ⚡ **Flujo de Aprobación Automática**

#### **Caso A: Ya logueado**

```
🖱️ Clic enlace → ✅ Sesión activa → 🤖 Procesamiento automático → ✅ Resultado
```

#### **Caso B: No logueado**

```
🖱️ Clic enlace → 🔐 Página login → 🔑 Credenciales → 🤖 Procesamiento automático → ✅ Resultado
```

**Características clave:**

- ✅ Preservación de parámetros durante navegación
- ✅ Campos ocultos para seguridad (GET → POST)
- ✅ Redirección automática post-login
- ✅ Sin clics adicionales requeridos

### 5. 🎯 **Procesamiento de Decisión**

#### **✅ Aprobación**:

- `acceso_aprobado = TRUE`
- `aprobado_por = id_superadmin`
- `fecha_aprobacion = NOW()`
- 📧 Email de bienvenida

#### **❌ Rechazo**:

- `DELETE FROM administradores`
- 📧 Email de notificación
- Cuenta completamente eliminada

## 🗂️ **Archivos Clave del Sistema**

### **Core de Autenticación**

- `functions.php` - Lógica principal de autenticación
- `login.php` - Formulario con preservación de parámetros
- `aprobar_admin.php` - Procesamiento de aprobaciones
- `verificar_email_admin.php` - Verificación de tokens

### **Sistema de Correos**

- `enviar_correo_admin.php` - Todas las funciones de email
  - `enviarCorreoVerificacionAdmin()`
  - `enviarCorreoSolicitudAprobacion()`
  - `enviarCorreoAccesoAprobado()`
  - `enviarCorreoAccesoRechazado()`

### **Configuración**

- `conexion.php` - BD con PDO y MySQLi
- `routes.php` - Rutas del sistema

## 🎨 **URLs del Sistema**

### **Para Usuarios**

- **Registro**: `/src/admin/auth/login.php` (tab registro)
- **Login**: `/src/admin/auth/login.php`
- **Verificación**: `/src/admin/auth/verificar_email_admin.php?token=<token>`

### **Para Superadministradores**

- **Dashboard**: `/src/admin/pages/dashboard/`
- **Aprobación**: `/src/admin/auth/aprobar_admin.php?token=<token>&accion=<aprobar|rechazar>`

## 🔧 **Estados de Sistema**

### **Acceso Permitido** ✅

```
email_verificado = TRUE
acceso_aprobado = TRUE
bloqueado = FALSE
credenciales_correctas = TRUE
```

### **Acceso Denegado** ❌

```
email_verificado = FALSE  → "Verificar email"
acceso_aprobado = FALSE   → "Pendiente aprobación"
bloqueado = TRUE          → "Cuenta bloqueada"
credenciales_incorrectas  → "Login inválido"
```

## 📊 **Base de Datos - Campos Críticos**

### **Tabla `administradores`**

```sql
id_admin, nombre, email, password_hash, rol,
email_verificado, acceso_aprobado, bloqueado,
token_verificacion, token_expira, aprobado_por,
fecha_aprobacion, ultimo_login, intentos_fallidos
```

### **Tabla `tokens_aprobacion`**

```sql
id, id_admin_solicitante, token_aprobacion,
token_rechazo, fecha_expiracion, procesado
```

## 🚨 **Puntos Críticos de Seguridad**

### **Tokens**

- ✅ 32 bytes hexadecimales (64 caracteres)
- ✅ Expiración: 24h verificación, 72h aprobación
- ✅ Uso único (marcado como procesado)
- ✅ Validación completa (existencia + expiración + estado)

### **Sesiones**

- ✅ PHP sessions con regeneración de ID
- ✅ Variables: `admin_id`, `admin_rol`, `admin_logged_in`
- ✅ Validación en cada página protegida

### **Contraseñas**

- ✅ `PASSWORD_DEFAULT` (bcrypt)
- ✅ Intentos fallidos → bloqueo automático
- ✅ Nunca almacenadas en texto plano

## 🎯 **Flujo de Usuario Final**

### **Para el Administrador Solicitante:**

1. 📝 **Registro** → Formulario completado
2. 📧 **Email** → "Verificar tu correo electrónico"
3. 🖱️ **Clic** → Email verificado automáticamente
4. ⏳ **Espera** → "Pendiente de aprobación por superadministrador"
5. 📧 **Email** → "¡Tu cuenta ha sido aprobada!" o "Solicitud rechazada"
6. 🔑 **Login** → Acceso completo al panel administrativo

### **Para el Superadministrador:**

1. 📧 **Email** → "Nuevo administrador solicita acceso"
2. 🖱️ **Clic** → En "APROBAR ACCESO" o "RECHAZAR SOLICITUD"
3. 🔐 **Login** → Si no está logueado (automático)
4. ✅ **Confirmación** → "Administrador aprobado exitosamente"
5. 📊 **Dashboard** → Vuelta a la gestión normal

---

## 🔍 **Testing Rápido**

### **Crear Admin de Prueba:**

```php
php -r "
require_once 'src/config/conexion.php';
\$stmt = \$pdo->prepare('INSERT INTO administradores (nombre, email, password_hash, rol, email_verificado, acceso_aprobado) VALUES (?, ?, ?, ?, TRUE, FALSE)');
\$stmt->execute(['Test Admin', 'test@test.com', password_hash('123456', PASSWORD_DEFAULT), 'admin']);
echo 'Admin creado con ID: ' . \$pdo->lastInsertId();
"
```

### **Verificar Estado:**

```php
php -r "
require_once 'src/config/conexion.php';
\$stmt = \$pdo->prepare('SELECT * FROM administradores WHERE email = ?');
\$stmt->execute(['test@test.com']);
print_r(\$stmt->fetch(PDO::FETCH_ASSOC));
"
```

---

**📅 Actualizado**: Septiembre 2025  
**👨‍💻 Desarrollador**: Anderson Quispe  
**🏢 Proyecto**: Antares Travel - Sistema de Administración
