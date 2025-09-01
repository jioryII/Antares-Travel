# Módulo de Vehículos - Antares Travel

## 📋 Descripción General
El módulo de vehículos es un sistema completo de gestión de flota vehicular para la empresa de turismo Antares Travel. Permite administrar vehículos, asignar choferes, controlar mantenimiento y generar reportes detallados.

## 🚗 Características Principales

### ✅ Gestión Completa de Vehículos
- **Registro de vehículos** con información detallada (placa, marca, modelo, año, etc.)
- **Validación automática** de placas peruanas
- **Gestión de estado** (activo, mantenimiento, fuera de servicio)
- **Control de capacidad** de pasajeros

### 👨‍💼 Gestión de Choferes
- **Asignación dinámica** de choferes a vehículos
- **Control de disponibilidad** de choferes
- **Validación de conflictos** de horarios
- **Historial de asignaciones**

### 📊 Panel de Estadísticas
- **Resumen ejecutivo** con métricas clave
- **Estadísticas por estado** de vehículos
- **Indicadores de asignación** de choferes
- **Métricas de tours** por vehículo

### 📱 Diseño Responsivo
- **Adaptación automática** a dispositivos móviles
- **Vista de tabla** para escritorio
- **Vista de tarjetas** para móviles
- **Navegación optimizada** para touch

## 🗂️ Estructura de Archivos

```
src/admin/pages/vehiculos/
├── index.php              # Lista principal con estadísticas
├── ver.php                # Vista detallada del vehículo
├── crear.php              # Formulario de creación
├── editar.php             # Formulario de edición
├── eliminar.php           # Eliminación segura
├── gestionar_chofer.php   # API para gestión de choferes
└── exportar.php           # Exportación CSV
```

## 🎯 Funcionalidades Detalladas

### 1. Lista Principal (index.php)
- **Panel de estadísticas** con 6 métricas clave
- **Buscador avanzado** por placa, marca o modelo
- **Filtros dinámicos** por chofer y estado
- **Tabla responsiva** con información completa
- **Acciones rápidas** (ver, editar, eliminar, exportar)

### 2. Vista Detallada (ver.php)
- **Información completa** del vehículo
- **Panel de estadísticas** específico del vehículo
- **Gestión de chofer asignado** con modales
- **Historial de tours** realizados
- **Estado en tiempo real**

### 3. Gestión de Choferes (gestionar_chofer.php)
**API AJAX con 3 endpoints:**
- `GET /get_disponibles` - Lista choferes disponibles
- `POST /asignar` - Asigna chofer al vehículo
- `POST /desasignar` - Desasigna chofer del vehículo

### 4. Formularios (crear.php / editar.php)
- **Validación en tiempo real** de campos
- **Preview dinámico** de información
- **Validación de placa** peruana
- **Selección de chofer** con estado
- **Mensajes de error** detallados

### 5. Eliminación Segura (eliminar.php)
- **Verificación de dependencias** (tours activos)
- **Eliminación transaccional** para integridad
- **Mensajes informativos** sobre restricciones

### 6. Exportación (exportar.php)
- **Exportación CSV** con datos completos
- **Codificación UTF-8** para caracteres especiales
- **Estadísticas incluidas** en el archivo
- **Descarga automática**

## 🛠️ Instalación y Configuración

### 1. Prerequisitos
- **PHP 8.0+** con extensiones PDO y MySQL
- **MySQL/MariaDB** con base de datos `db_antares`
- **Servidor web** (Apache/Nginx)

### 2. Configuración de Base de Datos
Asegúrese de que las siguientes tablas existan:
- `vehiculos` - Tabla principal de vehículos
- `choferes` - Tabla de choferes
- `tours_diarios` - Tabla de tours programados
- `disponibilidad_vehiculos` - Tabla de disponibilidad

### 3. Configuración de Archivos
Verifique la configuración en `src/admin/config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_antares');
define('DB_USER', 'root');
define('DB_PASS', 'admin942');
```

## 🔄 Flujo de Trabajo

### Gestión de Vehículos
1. **Acceder** al módulo desde el sidebar
2. **Revisar estadísticas** en el panel principal
3. **Crear nuevo vehículo** con el botón "Agregar"
4. **Asignar chofer** desde la vista detallada
5. **Monitorear estado** y realizar mantenimiento

### Asignación de Choferes
1. **Entrar** a la vista detallada del vehículo
2. **Verificar** estado actual del chofer
3. **Cambiar asignación** usando el modal
4. **Confirmar** la nueva asignación

### Generación de Reportes
1. **Aplicar filtros** necesarios en la lista
2. **Hacer clic** en "Exportar CSV"
3. **Descargar** archivo con datos filtrados

## 🎨 Características de UI/UX

### Diseño Visual
- **Gradientes modernos** en elementos clave
- **Iconografía consistente** Font Awesome
- **Colores temáticos** por estado
- **Animaciones suaves** en interacciones

### Responsividad
- **Breakpoints** en 768px y 1024px
- **Ocultación progresiva** de columnas
- **Adaptación de controles** touch-friendly
- **Redimensionamiento** de modales

### Accesibilidad
- **Contrastes adecuados** WCAG 2.1
- **Navegación por teclado** completa
- **Labels descriptivos** en formularios
- **Mensajes de estado** claros

## 🔧 Personalización

### Estilos CSS
Los estilos utilizan **Tailwind CSS** con clases utilitarias. Para personalizar:
- Modificar clases de color en los archivos PHP
- Ajustar breakpoints en las clases responsivas
- Personalizar animaciones en las transiciones

### Funcionalidades
Para agregar nuevas funcionalidades:
- Extender la API en `gestionar_chofer.php`
- Agregar nuevos campos en los formularios
- Implementar validaciones adicionales
- Crear nuevos endpoints de exportación

## 📞 Soporte

### Resolución de Problemas
- **Verificar conexión** a base de datos
- **Revisar permisos** de archivos
- **Comprobar extensiones** PHP requeridas
- **Validar estructura** de tablas

### Logs y Debugging
- Errores PHP en logs del servidor
- Errores JavaScript en consola del navegador
- Consultas SQL en logs de MySQL
- Validaciones AJAX en Network tab

## 🚀 Próximas Mejoras
- **Notificaciones push** para mantenimiento
- **Integración GPS** para tracking
- **App móvil** nativa
- **Dashboard analytics** avanzado
- **Integración** con sistemas externos

---

**Desarrollado para Antares Travel** - Sistema de gestión vehicular empresarial
*Versión 1.0 - Agosto 2025*
