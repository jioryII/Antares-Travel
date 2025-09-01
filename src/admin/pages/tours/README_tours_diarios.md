# Módulo de Tours Diarios - Antares Travel Admin

## Descripción
El módulo de Tours Diarios permite la gestión completa de tours programados por día, incluyendo la asignación automática de recursos (guías, choferes y vehículos) y el control de disponibilidad.

## Características Principales

### ✅ Gestión de Tours Diarios
- Registro de tours por fecha específica
- Asignación automática de guías, choferes y vehículos
- Control de horarios de salida y retorno
- Registro de número de participantes (adultos y niños)
- Campo de observaciones para notas adicionales

### ✅ Control de Disponibilidad
- **Guías**: Control automático de disponibilidad por fecha
- **Choferes**: Gestión de disponibilidad con asignación de tours
- **Vehículos**: Control de ocupación y mantenimiento
- Estados visuales para identificar recursos disponibles/ocupados

### ✅ Validaciones Automáticas
- Verificación de disponibilidad antes del registro
- Prevención de conflictos de recursos
- Transacciones seguras en base de datos
- Logs de auditoría integrados

### ✅ Interfaz Moderna
- Diseño responsive con TailwindCSS
- Carga dinámica de disponibilidad via AJAX
- Autocompletado de horarios basado en tours
- Mensajes flash para feedback del usuario

## Estructura de Archivos

```
src/admin/
├── pages/tours/
│   ├── tours_diarios.php          # Página principal del módulo
│   └── tours_diarios_ajax.php     # API AJAX para disponibilidad
├── sql/
│   └── tours_diarios_schema.sql   # Script de creación de tablas
├── install_tours_diarios.php      # Instalador del módulo
└── functions/admin_functions.php  # Funciones actualizadas
```

## Instalación

### Paso 1: Acceder al Instalador
1. Navegar a: `http://localhost/Antares-Travel/src/admin/install_tours_diarios.php`
2. Solo usuarios con rol `super_admin` pueden acceder al instalador

### Paso 2: Ejecutar Instalación
1. Leer las advertencias y funcionalidades incluidas
2. Marcar la casilla de confirmación
3. Hacer clic en "Instalar Módulo"

### Paso 3: Verificar Instalación
- Se crearán automáticamente todas las tablas necesarias
- Se insertarán datos de ejemplo para pruebas
- Se habilitará el enlace en el sidebar

## Tablas Creadas

### `tours_diarios`
Registro principal de tours programados por día
- Campos: fecha, tour, guía, chofer, vehículo, participantes, horarios, observaciones

### `disponibilidad_guias`
Control de disponibilidad de guías por fecha
- Estados: Disponible, Ocupado, No_Disponible

### `chofer_disponibilidad`
Control de disponibilidad de choferes por fecha
- Estados: Disponible, No Disponible
- Incluye asignación de tour

### `disponibilidad_vehiculos`
Control de disponibilidad de vehículos por fecha
- Estados: Disponible, Ocupado, Mantenimiento, No_Disponible

### Tablas de Recursos (con datos de ejemplo)
- `guias`: Información de guías turísticos
- `choferes`: Información de conductores
- `vehiculos`: Flota de vehículos

## Uso del Módulo

### Registrar Nuevo Tour Diario
1. Acceder a **Tours Diarios** desde el sidebar
2. Seleccionar fecha del tour
3. Elegir tour de la lista disponible
4. Seleccionar recursos disponibles (auto-carga tras elegir fecha)
5. Configurar participantes y horarios
6. Agregar observaciones si es necesario
7. Hacer clic en "Registrar Tour Diario"

### Características Especiales
- **Auto-carga de horarios**: Al seleccionar un tour, se cargan automáticamente los horarios predefinidos
- **Verificación de disponibilidad**: Solo se muestran recursos disponibles para la fecha seleccionada
- **Indicadores visuales**: Recursos ocupados aparecen marcados en color naranja
- **Validación previa**: El sistema verifica disponibilidad antes de permitir el registro

## API AJAX

### Endpoint: `tours_diarios_ajax.php`
**Parámetro**: `fecha` (formato: YYYY-MM-DD)

**Respuesta**:
```json
{
    "success": true,
    "guias": [
        {
            "id_guia": 1,
            "nombre": "Carlos",
            "apellido": "Mendoza",
            "disponible": true
        }
    ],
    "choferes": [...],
    "vehiculos": [...]
}
```

## Funciones Principales

### Backend (`admin_functions.php`)
- `getDisponibilidadRecursos($fecha)`: Obtiene disponibilidad de todos los recursos
- `registrarTourDiario($datos)`: Registra nuevo tour con validaciones
- `actualizarDisponibilidadGuia()`: Actualiza estado de guía
- `actualizarDisponibilidadChofer()`: Actualiza estado de chofer
- `actualizarDisponibilidadVehiculo()`: Actualiza estado de vehículo
- `getToursDiarios()`: Obtiene lista de tours diarios

### Frontend (JavaScript)
- `cargarDisponibles()`: Carga recursos disponibles via AJAX
- `cargarHorasTour()`: Autocompleta horarios del tour seleccionado

## Seguridad
- ✅ Validación de permisos de usuario
- ✅ Sanitización de datos de entrada
- ✅ Transacciones de base de datos
- ✅ Logs de auditoría
- ✅ Protección CSRF (heredada del sistema)

## Integración
- ✅ Totalmente integrado con el sistema de administración existente
- ✅ Usa las mismas librerías y estilos
- ✅ Aprovecha el sistema de autenticación y permisos
- ✅ Compatible con el sistema de logs y auditoría

## Datos de Ejemplo Incluidos

### Guías
- Carlos Mendoza (Español, Inglés)
- María González (Español, Inglés, Francés)
- José Ramírez (Español, Inglés)
- Ana Torres (Español, Inglés, Portugués)

### Choferes
- Pedro Silva (Licencia A1)
- Juan Vargas (Licencia A2)
- Luis Castillo (Licencia A1)
- Roberto Herrera (Licencia A2)

### Vehículos
- Toyota Hiace (12 pasajeros)
- Hyundai H1 (15 pasajeros)
- Mercedes-Benz Sprinter (20 pasajeros)
- Ford Transit (14 pasajeros)
- Chevrolet N300 (18 pasajeros)

## Soporte y Mantenimiento
- Logs de errores en archivos del sistema
- Modo debug para desarrollo
- Respaldos automáticos recomendados
- Monitoreo de rendimiento incluido

---

**Desarrollado para Antares Travel Admin System**  
**Versión**: 1.0  
**Compatibilidad**: PHP 8.x, MySQL 5.7+, TailwindCSS 3.x
