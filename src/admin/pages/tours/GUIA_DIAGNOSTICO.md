# 🔍 GUÍA DE DIAGNÓSTICO DE INCONSISTENCIAS FRONTEND

## 🎯 Pasos para Identificar el 80% de Inconsistencias

### 1. **Abrir la Página de Test**
```
http://localhost/Antares-Travel/src/admin/pages/tours/test_frontend.html
```

### 2. **Verificar en el Navegador (F12)**

#### **Console (Consola)**
Busca errores en rojo:
- ❌ `Failed to load resource: net::ERR_FILE_NOT_FOUND`
- ❌ `Uncaught ReferenceError: [función] is not defined`
- ❌ `Uncaught TypeError: Cannot read property`

#### **Network (Red)**
Busca archivos que fallan (status rojo):
- ❌ CSS no cargan (404)
- ❌ JS no cargan (404) 
- ❌ API calls fallan (500, 401, 404)

#### **Elements (Elementos)**
Verifica que los elementos existan:
- ❌ `<div id="modalTour">` no existe
- ❌ Botones sin onclick handlers
- ❌ Forms sin action/method

### 3. **Abrir la Página Real de Tours**
```
http://localhost/Antares-Travel/src/admin/pages/tours/index.php
```

### 4. **Pruebas Manuales Críticas**

#### **Test 1: Autenticación**
- ❌ Redirige a login inesperadamente
- ❌ Session timeout
- ❌ Permisos insuficientes

#### **Test 2: Carga de Datos**
- ❌ Lista de tours vacía cuando debería tener datos
- ❌ Dropdowns vacíos (regiones, guías)
- ❌ Paginación no funciona

#### **Test 3: Modales**
- ❌ Modal no abre al hacer clic en "Nuevo Tour"
- ❌ Modal no cierra
- ❌ Formulario no envía datos
- ❌ Preview de imagen no funciona

#### **Test 4: AJAX**
- ❌ Botones "Ver", "Editar", "Eliminar" no funcionan
- ❌ No aparecen notificaciones de éxito/error
- ❌ Datos no se actualizan sin refresh

#### **Test 5: Responsive**
- ❌ En móvil: Sidebar no se oculta
- ❌ En móvil: Botón hamburguesa no funciona
- ❌ Layout roto en tablet
- ❌ Tabla no scrolleable en móvil

### 5. **Errores Comunes y Soluciones**

#### **Error: "AntaresMobile is not defined"**
```bash
# Verificar que el archivo existe y se carga
http://localhost/Antares-Travel/src/admin/assets/js/responsive.js
```

#### **Error: "abrirModalCrear is not defined"**
```bash
# Verificar que el archivo exists y se carga
http://localhost/Antares-Travel/src/admin/assets/js/tours.js
```

#### **Error: API calls fallan**
```bash
# Verificar endpoint directamente
http://localhost/Antares-Travel/src/admin/api/tours.php?action=listar&pagina=1
```

#### **Error: CSS no aplica**
```bash
# Verificar que el archivo exists
http://localhost/Antares-Travel/src/admin/assets/css/responsive.css
```

### 6. **Checklist de Verificación**

```bash
# Ejecutar desde PowerShell en el directorio del proyecto:
cd c:\xampp\htdocs\Antares-Travel\src\admin\pages\tours
php diagnostico_simple.php
```

#### **Archivos que DEBEN existir:**
- ✅ ../../assets/css/responsive.css
- ✅ ../../assets/js/responsive.js  
- ✅ ../../assets/js/tours.js
- ✅ ../../components/header.php
- ✅ ../../components/sidebar.php
- ✅ ../../api/tours.php

#### **Funciones JS que DEBEN funcionar:**
- ✅ `abrirModalCrear()`
- ✅ `editarTour(id)`
- ✅ `verTour(id)`
- ✅ `eliminarTour(id)`
- ✅ `AntaresMobile.toggleSidebar()`

#### **Endpoints API que DEBEN responder:**
- ✅ `GET /api/tours.php?action=listar`
- ✅ `GET /api/tours.php?action=obtener&id=1`
- ✅ `POST /api/tours.php?action=crear`
- ✅ `PUT /api/tours.php?action=actualizar`
- ✅ `DELETE /api/tours.php?action=eliminar`

### 7. **Reporte de Inconsistencias**

**Formato para reportar:**
```
COMPONENTE: [Modal/API/CSS/JS/etc]
PROBLEMA: [Descripción específica]
ERROR: [Mensaje de error exacto de consola]
NAVEGADOR: [Chrome/Firefox/etc]
DISPOSITIVO: [Desktop/Mobile/Tablet]
PASOS: [Cómo reproducir]
```

### 8. **Solución Rápida de Emergencia**

Si todo falla, crea una página básica sin AJAX:
```php
<!-- Formulario simple sin JavaScript -->
<form method="POST" action="guardar_tour.php" enctype="multipart/form-data">
    <!-- Campos básicos -->
</form>
```

---

## 🚨 **PUNTO CRÍTICO**

**El 80% de inconsistencias frontend son causadas por:**
1. **Rutas incorrectas** (404 en CSS/JS)
2. **Sesiones expiradas** (401 en API)
3. **JavaScript no carga** (función no definida)
4. **CORS/Headers** (API bloquea requests)
5. **Sintaxis PHP** (page breaks)

**¡Empieza siempre por verificar la CONSOLA del navegador!**
