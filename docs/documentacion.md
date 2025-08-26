<!-- filepath: c:\xampp\htdocs\Antares-Travel\docs\documentacion.md -->
# 📘 Documentación del Proyecto: Antares-Travel

---

## 📅 2025-08-16 — Estructura de Archivos

Se definió una **estructura modular y escalable** para el proyecto **Antares-Travel**, organizada de la siguiente manera:

```plaintext
Antares_travel/        # Carpeta raíz del proyecto
│
├── db/                # Versiones de la base de datos (migraciones, seeds)
├── docs/              # Documentación del código y detalles técnicos
├── public/            # Archivos públicos (accesibles directamente por el navegador)
├── src/               # Lógica principal de la aplicación
│   ├── admin/         # Módulo de administrador (login, endpoints, vistas)
│   ├── auth/          # Autenticación (login social, manual, validaciones)
│   ├── config/        # Configuración del sistema
│   ├── modules/       # Módulos específicos (ej: reservas, pagos)
│   ├── usuarios/      # Administración y gestión de usuarios
│   └── views/         # Vistas HTML reutilizables
├── storage/           # Archivos dinámicos (perfiles de usuario, imágenes subidas)
├── vendor/            # Dependencias externas de PHP (composer)
├── index.php          # Punto de entrada del sistema (incluye landing page)
├── .gitignore         # Exclusiones para el control de versiones (Git)
└── composer.json      # Configuración de dependencias (Composer)
```

---

## 📅 2025-08-17 — Sistema de Autenticación Híbrida

Implementación de **autenticación múltiple** que soporta:

### 🔑 Métodos disponibles:
- **Manual**: Correo + contraseña con verificación email
- **OAuth Social**: Google, Microsoft, Apple
- **Google One Tap**: Inicio automático

### 🌀 Flujo de autenticación:
1. **Social**: Creación automática de cuenta si no existe
2. **Manual**: Registro → Verificación email → Acceso
3. **One Tap**: Inicio instantáneo con sesión Google activa

**Beneficios**: Experiencia fluida, seguridad reforzada, escalabilidad para nuevos proveedores.

---

## 📅 2025-08-25 — Sistema de Configuraciones Multinivel

Implementación de **configuraciones personalizables** en 3 niveles jerárquicos:

### 🗂️ Estructura de tablas:

| Tabla | Propósito | Acceso | Ejemplos |
|-------|-----------|---------|----------|
| `configuraciones_sistema` | **Globales** | Superadmin | SMTP, moneda, límites |
| `configuraciones_admin` | **Por admin** | Cada admin | Dashboard, timezone |
| `preferencias_usuario` | **Por usuario** | Cada usuario | Tema, idioma, notificaciones |

### 🎯 Características principales:
- **🔒 Seguridad**: Aislamiento por roles y acceso controlado
- **⚡ Performance**: Índices únicos, separación de datos
- **🔄 Auditoría**: Timestamps automáticos, categorización
- **🌟 Escalabilidad**: Soporte JSON, gestión centralizada

**Resultado**: Sistema robusto para personalización total de experiencia de usuario.

---

> **Nota:** Para visualizar este archivo, usar **Markdown Preview** en VS Code con `Ctrl+Shift+V`.
