# 📘 Documentación del Proyecto: Antares-Travel

---

## 📅 2025-08-16 — Estructura de Archivos

Se definió una **estructura modular y escalable** para el proyecto **Antares-Travel**, organizada de la siguiente manera:

```plaintext
Antares_travel/        # Carpeta raíz del proyecto
│
├── db/                # Versiones de la base de datos (migraciones, seeds)
│
├── docs/              # Documentación del código y detalles técnicos
│
├── public/            # Archivos públicos (accesibles directamente por el navegador)
│
├── src/               # Lógica principal de la aplicación
│   ├── admin/         # Módulo de administrador (login, endpoints, vistas)
│   ├── auth/          # Autenticación (login social, manual, validaciones)
│   ├── config/        # Configuración del sistema
│   │   ├── conexion.php   # Archivo de conexión a la base de datos
│   │   └── routes.php     # Definición de rutas del sistema
│   ├── modules/       # Módulos específicos (ej: reservas, pagos)
│   ├── usuarios/      # Administración y gestión de usuarios
│   └── views/         # Vistas HTML reutilizables
│
├── storage/           # Archivos dinámicos (perfiles de usuario, imágenes subidas)
│
├── vendor/            # Dependencias externas de PHP (composer)
│
├── index.php          # Punto de entrada del sistema (incluye landing page)
├── .gitignore         # Exclusiones para el control de versiones (Git)
└── composer.json      # Configuración de dependencias (Composer)
```

> **Nota:** Los archivos o módulos que no aparecen documentados aún se encuentran en **fase de pruebas**.


---
*###################################*


## 📅 2025-08-17 — Inicio de Sesión y Registro de Usuarios

Se implementó un **sistema híbrido de autenticación** que soporta tanto el **registro manual** como el **login social**.

### 🔑 Métodos de autenticación disponibles:

- **Correo y contraseña** (registro manual con verificación por correo electrónico).
- **Google OAuth / One Tap** (inicio de sesión instantáneo con cuenta de Google).
- **Microsoft OAuth**.
- **Apple OAuth**.

### 🌀 Flujo de Autenticación

1. **Inicio de sesión social**

   - Si el usuario se conecta con Google/Microsoft/Apple y no tiene cuenta, se le **crea automáticamente**.
   - Si ya existe una cuenta vinculada al mismo correo, se **actualizan los datos del usuario**.

2. **Inicio de sesión manual (correo y contraseña)**

   - El usuario se registra con su correo.
   - Se envía un **correo de verificación**.
   - El usuario **no podrá iniciar sesión** hasta validar su correo electrónico.

3. **Google One Tap**
   - Implementado en `index.php`.
   - Permite al usuario iniciar sesión con un solo clic si ya tiene sesión activa en Google.


## ✅ Beneficios de la Implementación

- Experiencia de usuario fluida con **inicio automático**.
- Base de datos optimizada para manejar **múltiples proveedores de autenticación**.
- Seguridad reforzada mediante **verificación de correo** y **password hashing**.
- Sistema escalable, preparado para añadir nuevos proveedores en el futuro.

---
*###################################*








> **Nota:** para visualizar el ouput de este archivo, se debe instalar el plugin de **Markdown Preview** en Visual Studio Code y usarlo con ctrl+shift+v.
