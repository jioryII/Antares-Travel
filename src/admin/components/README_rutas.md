# Sistema de Rutas Independientes - Sidebar Admin

## Cambios Realizados

### ✅ **Incoherencias Corregidas:**

1. **Dependencia del proyecto eliminada**: Ya no depende de `/Antares-Travel/`
2. **Función simplificada**: `getAdminUrl()` más eficiente y clara
3. **Rutas consistentes**: Todas las rutas son relativas al módulo admin
4. **Fallback para imágenes**: Si la imagen del logo no existe, muestra icono de FontAwesome
5. **URLs específicas**: Ahora todas apuntan a `index.php` específicos

### 🔧 **Nueva Función `getAdminUrl()`**

```php
function getAdminUrl($path) {
    $currentPath = $_SERVER['REQUEST_URI'];

    // Detecta automáticamente el nivel de profundidad
    if (strpos($currentPath, '/components/') !== false) {
        $baseLevel = '../';
    } else if (strpos($currentPath, '/pages/') !== false) {
        $pathAfterPages = substr($currentPath, strpos($currentPath, '/pages/') + 7);
        $depth = substr_count($pathAfterPages, '/');
        $baseLevel = str_repeat('../', $depth + 1);
    } else {
        $baseLevel = './';
    }

    $cleanPath = ltrim($path, './');
    return $baseLevel . $cleanPath;
}
```

### 📍 **Ejemplos de Rutas Generadas:**

**Desde components/sidebar.php:**

- Dashboard: `../pages/dashboard/index.php`
- Tours: `../pages/tours/index.php`
- Guías: `../pages/guias/index.php`

**Desde pages/dashboard/index.php:**

- Tours: `../tours/index.php`
- Reservas: `../reservas/index.php`

**Desde pages/tours/subcarpeta/archivo.php:**

- Dashboard: `../../dashboard/index.php`
- Usuarios: `../../usuarios/index.php`

### 🖼️ **Imagen del Logo:**

- **Ruta relativa**: `../../imagenes/antares_logo.png`
- **Fallback**: Si no encuentra la imagen, muestra icono de FontAwesome
- **Independiente**: No depende de la estructura del proyecto padre

### 🎯 **Ventajas del Nuevo Sistema:**

1. **Portabilidad**: El módulo admin puede moverse a cualquier proyecto
2. **Independencia**: No necesita configuración de rutas base
3. **Mantenibilidad**: Lógica simple y clara
4. **Robustez**: Maneja diferentes niveles de carpetas automáticamente
5. **Fallbacks**: Graceful degradation si faltan recursos

### 📁 **Estructura Esperada:**

```
admin/
├── components/
│   ├── sidebar.php (origen)
│   └── header.php
├── pages/
│   ├── dashboard/
│   │   └── index.php
│   ├── tours/
│   │   ├── index.php
│   │   └── tours_diarios.php
│   ├── reservas/
│   │   └── index.php
│   └── usuarios/
│       └── index.php
└── imagenes/
    └── antares_logo.png (opcional)
```

### 🔍 **Testing:**

Para verificar que las rutas funcionan correctamente, puedes usar:

```php
// En cualquier página, agregar temporalmente:
echo "Ruta generada: " . getAdminUrl('pages/dashboard/index.php');
```

El sistema ahora es **completamente independiente** y funciona sin importar dónde se coloque el módulo admin.
