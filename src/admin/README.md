# 🌟 Antares Travel - Sistema de Gestión Turística

<div align="center">

![Antares Travel Logo](imagenes/antares_logozz2.png)

**Descubre el mundo con experiencias extraordinarias**

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://javascript.info/)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.0+-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC34A?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev/)

</div>

---

## 📋 Descripción del Proyecto

**Antares Travel** es un sistema integral de gestión turística que permite a las agencias de viajes administrar tours, reservas, personal y clientes de manera eficiente. El sistema cuenta con una landing page atractiva para clientes y un panel administrativo completo para la gestión interna.

### 🎯 **Características Principales**

- 🌐 **Landing Page Moderna** - Diseño responsivo con animaciones y efectos visuales
- 🔐 **Sistema de Autenticación Híbrida** - Login manual y OAuth (Google, Microsoft, Apple)
- 📊 **Panel Administrativo Completo** - Dashboard con estadísticas en tiempo real
- 🗓️ **Gestión de Tours** - Programación y administración de tours diarios
- 📝 **Sistema de Reservas** - Gestión completa de reservas y clientes
- 👥 **Administración de Personal** - Gestión de guías, choferes y vehículos
- 📈 **Reportes y Analytics** - Estadísticas detalladas de ventas y operaciones
- 🎨 **UI/UX Moderna** - Interfaz intuitiva con Tailwind CSS y Alpine.js

---

## 🏗️ Arquitectura del Sistema

### 📁 **Estructura de Directorios**

```
Antares-Travel/
├── 📂 db/                    # Base de datos y migraciones
├── 📂 docs/                  # Documentación técnica
├── 📂 public/                # Archivos públicos accesibles
│   ├── 📂 assets/           # CSS, JS, imágenes estáticas
│   └── 📄 example.php       # Páginas de ejemplo
├── 📂 src/                   # Lógica principal del sistema
│   ├── 📂 admin/            # Panel administrativo
│   │   ├── 📂 components/   # Componentes reutilizables
│   │   ├── 📂 pages/        # Páginas del admin
│   │   └── 📄 dashboard.php # Dashboard principal
│   ├── 📂 auth/             # Sistema de autenticación
│   ├── 📂 config/           # Configuraciones del sistema
│   ├── 📂 functions/        # Funciones auxiliares
│   └── 📂 modules/          # Módulos específicos
├── 📂 storage/              # Archivos dinámicos y uploads
├── 📂 vendor/               # Dependencias de Composer
├── 📄 index.php            # Landing page principal
├── 📄 composer.json        # Configuración de dependencias
└── 📄 setup.php            # Script de instalación
```

### 🔧 **Stack Tecnológico**

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **PHP** | 8.0+ | Backend y lógica del servidor |
| **MySQL** | 8.0+ | Base de datos principal |
| **TailwindCSS** | 3.x | Framework CSS utilitario |
| **Alpine.js** | 3.x | Reactividad en el frontend |
| **Font Awesome** | 6.x | Iconografía |
| **Google OAuth** | 2.0 | Autenticación social |
| **XAMPP** | 8.x | Entorno de desarrollo |

---

## 🚀 Instalación y Configuración

### ⚡ **Instalación Rápida**

```bash
# 1. Clonar el repositorio
git clone https://github.com/jioryII/Antares-Travel.git
cd Antares-Travel

# 2. Instalar dependencias de PHP
composer install

# 3. Configurar base de datos
# Importar setup_completo.sql en phpMyAdmin

# 4. Configurar variables de entorno
cp .env.example .env
# Editar .env con tus credenciales

# 5. Ejecutar configuración inicial
php setup.php
```

### 🗄️ **Configuración de Base de Datos**

1. **Crear base de datos** en phpMyAdmin:
   ```sql
   CREATE DATABASE antares_travel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Importar esquema**:
   - Ejecutar `setup_completo.sql` en phpMyAdmin
   - O usar: `mysql -u root -p antares_travel < setup_completo.sql`

3. **Configurar conexión** en `src/config/database.php`:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $database = "antares_travel";
   ```

### 🔑 **Configuración OAuth (Google)**

1. **Crear proyecto** en [Google Cloud Console](https://console.cloud.google.com/)
2. **Habilitar Google+ API**
3. **Crear credenciales OAuth 2.0**
4. **Configurar** en `src/functions/google_auth.php`:
   ```php
   $client->setClientId('tu-client-id.googleusercontent.com');
   $client->setClientSecret('tu-client-secret');
   $client->setRedirectUri('http://localhost/Antares-Travel/');
   ```

---

## 🎮 Uso del Sistema

### 🌐 **Landing Page**

**Acceso:** `http://localhost/Antares-Travel/`

**Características:**
- ✨ Diseño moderno y responsivo
- 🏖️ Galería de destinos interactiva
- 📱 Formulario de reservas integrado
- 🌍 Soporte multiidioma (ES/EN)
- 🔐 Login social y manual

### 👨‍💼 **Panel Administrativo**

**Acceso:** `http://localhost/Antares-Travel/src/admin/`

#### 📊 **Dashboard Principal**
- **Estadísticas en tiempo real**: Reservas, tours, usuarios, ventas
- **Accesos rápidos**: Crear reservas, programar tours, ver reportes
- **Vista responsiva**: Optimizada para móvil y desktop

#### 🗂️ **Módulos Disponibles**

| Módulo | Funcionalidad |
|--------|---------------|
| **📅 Reservas** | Gestión completa de reservas y clientes |
| **🗺️ Tours Diarios** | Programación de tours con asignación de personal |
| **🎯 Gestión de Tours** | Catálogo de tours disponibles |
| **👥 Usuarios** | Administración de clientes registrados |
| **👨‍🏫 Personal** | Gestión de guías y choferes |
| **🚐 Vehículos** | Administración de flota de transporte |
| **📈 Reportes** | Estadísticas y analytics detallados |
| **⚙️ Configuración** | Parámetros del sistema |

---

## 🎨 Características de Diseño

### 🎭 **Sistema de Temas**

- **🌙 Modo Oscuro/Claro**: Alternancia automática
- **🎨 Paleta de Colores**: Gradientes modernos azul-púrpura
- **📱 Diseño Responsivo**: Mobile-first approach
- **✨ Animaciones Suaves**: Transiciones CSS optimizadas

### 🎯 **UX/UI Destacadas**

- **🔥 Hover Effects**: Efectos visuales en botones y cards
- **📏 Grid System**: Layout adaptativo con CSS Grid/Flexbox
- **🎪 Micro-interacciones**: Feedback visual inmediato
- **♿ Accesibilidad**: Contraste optimizado y navegación por teclado

---

## 🔐 Seguridad

### 🛡️ **Medidas Implementadas**

- **🔒 Autenticación Segura**: Hash de contraseñas con `password_hash()`
- **🚫 Prevención SQL Injection**: Prepared statements
- **🛡️ Validación de Datos**: Sanitización de inputs
- **🔐 Sesiones Seguras**: Manejo correcto de sesiones PHP
- **🌐 OAuth Integrado**: Google, Microsoft, Apple

### 🎯 **Buenas Prácticas**

- Validación tanto en cliente como servidor
- Escape de datos antes de mostrar
- Uso de HTTPS en producción
- Tokens CSRF para formularios críticos

---

## 📊 Base de Datos

### 🗃️ **Tablas Principales**

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Información de usuarios y clientes |
| `tours` | Catálogo de tours disponibles |
| `reservas` | Reservas realizadas por clientes |
| `guias` | Personal guía de la empresa |
| `choferes` | Conductores de vehículos |
| `vehiculos` | Flota de transporte |
| `tours_diarios` | Programación diaria de tours |
| `configuraciones_sistema` | Configuraciones globales |

### 🔗 **Relaciones Clave**

```sql
-- Ejemplo de relación entre reservas y tours
reservas (id_tour) → tours (id)
tours_diarios (id_guia) → guias (id)
tours_diarios (id_chofer) → choferes (id)
tours_diarios (id_vehiculo) → vehiculos (id)
```

---

## 🛠️ Desarrollo

### 🏃‍♂️ **Comandos de Desarrollo**

```bash
# Iniciar servidor local
php -S localhost:8000

# Actualizar dependencias
composer update

# Ejecutar migraciones
php ejecutar_datos_base.php

# Limpiar cache (si aplica)
rm -rf storage/cache/*
```

### 🐛 **Debugging**

- **Logs de Error**: Revisar `error_log` en directorio raíz
- **Console del Navegador**: Para errores JavaScript
- **Network Tab**: Para verificar requests AJAX
- **phpMyAdmin**: Para consultas directas a BD

---

## 📈 Roadmap

### ✅ **Completado**

- [x] Landing page responsiva
- [x] Sistema de autenticación híbrida
- [x] Panel administrativo funcional
- [x] Gestión básica de tours y reservas
- [x] Dashboard con estadísticas

### 🚧 **En Desarrollo**

- [ ] Sistema de notificaciones en tiempo real
- [ ] Integración con pasarelas de pago
- [ ] API REST para aplicaciones móviles
- [ ] Sistema de reviews y calificaciones
- [ ] Chat en vivo para soporte

### 🔮 **Futuro**

- [ ] Aplicación móvil nativa
- [ ] Integración con redes sociales
- [ ] Sistema de fidelización de clientes
- [ ] Multi-tenancy para múltiples agencias
- [ ] Inteligencia artificial para recomendaciones

---

## 🤝 Contribución

### 👥 **Equipo de Desarrollo**

- **🚀 Lead Developer**: [jioryII](https://github.com/jioryII)
- **🎨 UI/UX Designer**: Anderson Quispe
- **📊 Database Architect**: Diane Rojas
- **🔧 DevOps**: Andi

### 📝 **Cómo Contribuir**

1. **Fork** el repositorio
2. **Crear rama** para tu feature: `git checkout -b feature/nueva-caracteristica`
3. **Commit** tus cambios: `git commit -m 'Agregar nueva característica'`
4. **Push** a la rama: `git push origin feature/nueva-caracteristica`
5. **Crear Pull Request**

### 📋 **Guidelines**

- Seguir estándares PSR-12 para PHP
- Usar convención de commits semánticos
- Agregar tests para nuevas funcionalidades
- Documentar cambios en el changelog

---

## 📞 Soporte

### 💬 **Canales de Comunicación**

- **📧 Email**: soporte@antarestravel.com
- **💬 Discord**: [Servidor de Desarrollo](https://discord.gg/antares-travel)
- **🐛 Issues**: [GitHub Issues](https://github.com/jioryII/Antares-Travel/issues)
- **📱 WhatsApp**: +51 999 999 999

### 📚 **Documentación**

- **🔧 Documentación Técnica**: `/docs/documentacion.md`
- **📝 Tareas Pendientes**: `/docs/pendientes.md`
- **🎯 API Reference**: `/docs/api.md` (próximamente)

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

## 🙏 Agradecimientos

**Antares Travel** ha sido posible gracias a:

- **🌟 Comunidad PHP**: Por las librerías y frameworks utilizados
- **🎨 TailwindCSS Team**: Por el framework CSS excepcional
- **⚡ Alpine.js Community**: Por la reactividad simple y poderosa
- **🔗 Google**: Por las APIs de autenticación
- **👥 Beta Testers**: Por el feedback invaluable

---

<div align="center">

**Hecho con ❤️ para revolucionar la industria turística**

**[⭐ Star este proyecto](https://github.com/jioryII/Antares-Travel)** si te ha sido útil

---

*"El mundo es un libro y aquellos que no viajan leen solo una página."* - San Agustín

</div>
