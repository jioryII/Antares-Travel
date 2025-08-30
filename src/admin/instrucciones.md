antes de iniciar considera que este modulo se debe desarollar solo dentro la carpeta de admin/, y no debe afectar el modulo de usuarios, ya que son modulos independientes.

 objetivo:


# FLUJO DETALLADO Y CONSIDERACIONES LÓGICAS (PARA APROBACIÓN)

A continuación se describe, a nivel lógico y paso a paso, todo el flujo que se propone implementar en el módulo de administración. Revisar cuidadosamente y aprobar antes de comenzar con la implementación en código.

## 1. Resumen de objetivos
- Implementar un módulo de administración autónomo dentro de `src/admin/`.
- Gestionar: administradores (login), tours, reservas, usuarios (moderación), guías, choferes, vehículos, experiencias (muro de fotos) y tours diarios (calendario).
- Almacenamiento de archivos local en `storage/uploads/` con subcarpetas por tipo.
- Usar TailwindCSS vía CDN para diseño y Chart.js para gráficas básicas.

## 2. Arquitectura lógica (capas)
- Capa de presentación (views/HTML + Tailwind): páginas y componentes reutilizables.
- Capa de controladores (endpoints en `src/admin/api/`): reciben peticiones AJAX/POST y validan datos.
- Capa de servicios/funciones (`src/admin/functions/`): lógica de negocio, validaciones complejas, transacciones.
- Capa de datos: `src/config/conexion.php` (reutilizar conexión y prepared statements).

## 3. Convenciones y reglas generales
- Todos los queries deben usar prepared statements para evitar SQL injection.
- Todas las operaciones que involucran múltiples tablas (p.ej. crear reserva + pasajeros + actualizar disponibilidad) usarán transacciones (BEGIN / COMMIT / ROLLBACK).
- Mensajes de error y éxito deben devolver un formato JSON estándar para llamadas AJAX:
  - { success: bool, message: string, data: object|null, errors: array|null }
- Rutas y archivos dentro de `src/admin/` deben usar namespaces o prefijos para evitar colisiones con otros módulos.
- Variables de sesión de administradores con prefijo `admin_`.

## 4. Autenticación y autorización (lógica)
- Registro de administradores: formulario protegido (solo accesible por superadmin si se habilita). En esta fase usar solo login/registro básico.
- Login admin: validar email + password. Comparar password con `password_hash` en tabla `administradores`.
- Manejo de intentos fallidos: incrementar `intentos_fallidos` y bloquear (`bloqueado = true`) después de X intentos (p.ej. 5). Mantener `ultimo_login` y registro de auditoría.
- Sesión: al autenticar correctamente, setear:
  - `$_SESSION['admin_id']`, `$_SESSION['admin_nombre']`, `$_SESSION['admin_email']`, `$_SESSION['admin_rol']`, `$_SESSION['admin_logged_in']=true`.
- Middleware: todas las páginas del admin verificarán `$_SESSION['admin_logged_in'] === true` y `$_SESSION['admin_rol'] === 'admin'`.
- Logout: destruir solo las variables prefijadas `admin_` o usar session_regenerate_id + limpiar.

## 5. Modelado lógico mapeado a la BD (resumen)
- administradores (ya definida): usar campo `password_hash` y `salt` (opcional).
- usuarios: lectura y moderación.
- tours: CRUD principal, relación con `regiones` y `guias`.
- reservas + pasajeros: creación manual por admin y gestión de estados.
- guias + guia_idiomas: CRUD, disponibilidad reflejada en `disponibilidad_guias`.
- choferes + vehiculos + disponibilidad_vehiculos: CRUD y disponibilidad.
- experiencias: moderación y eliminación.
- tours_diarios: programación por fecha con referencias a tour, guia, chofer, vehiculo.

## 6. Flujos operativos (detallados)

6.1. Flujo: Login admin
- Usuario accede a `/src/admin/auth/login.php`.
- Formulario POST -> validar campos.
- Buscar admin por email.
- Si no existe -> error.
- Verificar `bloqueado` y `intentos_fallidos`.
- Verificar `password_hash` (password_verify).
- Si ok -> setear sesión y `ultimo_login` (UPDATE), resetear `intentos_fallidos` a 0.
- Si falla -> incrementar `intentos_fallidos`, si >= límite -> `bloqueado = true`.

6.2. Flujo: Crear/editar tour
- Formulario recoge: titulo, descripcion, precio, duracion, id_region, lugar_salida, lugar_llegada, hora_salida, hora_llegada, id_guia, imagen_principal.
- Validaciones: campos obligatorios, precio numérico >=0, region/guia existentes.
- Si hay imagen: validar tipo/size, guardar en `storage/uploads/tours/`, generar nombre único.
- Insert/Update en tabla `tours` dentro de transacción.

6.3. Flujo: Reserva manual desde admin
- Admin crea reserva: seleccionar tour, fecha_tour, cantidad pasajeros, datos de pasajeros.
- Calcular `monto_total` (precio_clase * cantidad) o tomar precio guardado en `tours`.
- Iniciar transacción:
  - Insertar en `reservas` con `id_administrador` = admin_id.
  - Insertar cada pasajero en `pasajeros` con id_reserva.
  - (Opcional) Crear registro de pago en `pagos` si se registra pago inmediato.
  - Confirmar disponibilidad: marcar `disponibilidad_guias` y `disponibilidad_vehiculos` según corresponda (si se asigna un tour_diario en la misma fecha).
- Commit.

6.4. Flujo: Programar tour diario (calendario)
- Desde vista calendario, admin selecciona fecha y crea evento.
- Validar:
  - que `id_tour` exista.
  - que `id_guia`, `id_chofer`, `id_vehiculo` estén libres en `disponibilidad_guias` y `disponibilidad_vehiculos` para esa fecha (o crear registro si no existe y marcar Ocupado).
- Si disponibles -> insertar en `tours_diarios`.
- Actualizar/insertar registros en tablas de disponibilidad con `estado = 'Ocupado'` y `id_reserva` = NULL (hasta que se asigne reserva si aplica).
- Si hay conflicto -> devolver error indicando recursos ocupados y sugerir alternativas (otras guías/vehículos o días alternos).

6.5. Flujo: Moderación de experiencias (muro de fotos)
- Consulta paginada de `experiencias`.
- Ver imagen y comentario; admin puede Aprobar/Eliminar/Editar.
- Si se elimina, borrar archivo físico de `storage/uploads/experiencias/` y registro DB.

6.6. Flujo: Gestión de disponibilidad
- Cada vez que una reserva se confirma que usa recursos (guía/vehículo), se debe:
  - Insertar o actualizar `disponibilidad_guias` y `disponibilidad_vehiculos` con `estado = 'Ocupado'` y `id_reserva` vinculado.
- Al cancelar o finalizar reservar -> liberar disponibilidad (set estado = 'Libre' o eliminar el registro, según política).

## 7. Reglas de validación y negocio (importante)
- No permitir duplicidad en campos únicos (email de admin, email de usuario, placa de vehículo, licencia de chofer).
- No permitir reservas para fechas pasadas.
- Para tours_diarios la hora_salida/hora_retorno deben ser coherentes (hora_retorno > hora_salida).
- Al borrar un recurso (guía/chofer/vehículo) comprobar integridad referencial: si tiene tours o tours_diarios asignados, denegar eliminación o forzar reasignación.

## 8. API interna: contratos y endpoints (resumen)
- `GET /src/admin/api/stats.php` -> devuelve métricas del dashboard.
- `GET/POST/PUT/DELETE /src/admin/api/tours.php` -> gestión de tours.
- `GET/POST/PUT/DELETE /src/admin/api/reservas.php` -> gestión reservas y pasajeros.
- `GET/POST/PUT/DELETE /src/admin/api/guias.php` -> gestión guías e idiomas.
- `GET/POST/PUT/DELETE /src/admin/api/choferes.php` -> gestión choferes.
- `GET/POST/PUT/DELETE /src/admin/api/vehiculos.php` -> gestión vehículos.
- `GET/POST/PUT/DELETE /src/admin/api/experiencias.php` -> moderación muro.
- `GET/POST /src/admin/api/calendar.php` -> datos y operaciones de `tours_diarios` (para integrarse con un componente de calendario JS).
- `POST /src/admin/api/upload.php` -> subir archivos (retorna URL y nombre guardado).

Formato común de petición/respuesta:
- Peticiones con JSON o form-data (para uploads).
- Respuestas: JSON con structure { success, message, data, errors }.


## 9. Lógica del calendario y verificación de conflictos
- Al pedir recursos disponibles para una fecha:
  - Consultar `disponibilidad_guias` WHERE fecha = X AND estado = 'Libre' OR registro no existente para esa guia.
  - Similar para `disponibilidad_vehiculos`.
- Para evitar race conditions (dos admins asignando simultáneamente): usar transacciones + bloqueo optimista (verificar al commit) o marcar provisionalmente con token de sesión y tiempo de expiración si la UI reserva temporalmente el recurso.

## 10. Gestión de archivos (detalles)
- Validar mime-type y extensión contra lista blanca (`image/jpeg`, `image/png`, `image/webp`).
- Limitar tamaño máximo (p.ej. 5MB por imagen).
- Almacenar en `storage/uploads/<tipo>/` con nombre: `<tipo>_<timestamp>_<random>.<ext>`.
- Generar thumbnail si aplica (p.ej. 300x200) y guardar versión optimizada.
- Si se elimina el registro DB, eliminar también los archivos físicos.

## 11. Seguridad y protección adicional
- CSRF token en formularios HTML (especialmente en formularios críticos).
- Escapar/sanitizar outputs en vistas para prevenir XSS.
- Protección contra path traversal en uploads.
- Forzar HTTPS en producción.
- Registros de auditoría para acciones críticas (crear/eliminar tours, cambios en reservas, bloqueos de admins).

## 12. Manejo de errores y mensajes al usuario
- Errores en backend deben registrarse en un log (error_log o almacenamiento en `storage/logs/`).
- Para la UI devolver mensajes claros y códigos HTTP correctos (400, 401, 403, 404, 500).
- En operaciones masivas, retornar lista de items con estado por item si procede.

## 13. Pruebas y QA (plan lógico)
- Pruebas manuales de flujo: login, crear tour, crear reserva, programar tour diario, conflicto de disponibilidad, upload de imagenes.
- Casos de borde: duplicados, fechas pasadas, archivos inválidos.
- Checklist antes de despliegue: backups DB, permiso de carpetas (storage/uploads) y variables de sesión funcionando.

## 14. Consideraciones de despliegue y ambiente
- Entorno local: XAMPP (Windows) ya usado por el proyecto.
- Asegurar `storage/uploads/` con permisos de escritura.
- Variables sensibles (DB credentials) se mantienen en `src/config/conexion.php` — en producción considerar mover a variables de entorno o archivo fuera del repo.

## 15. Tareas prioritarias para la primera iteración (MVP)
1. Implementar sistema de login/logout admin y middleware de protección.
2. CRUD básico de Tours con upload de imagen y validaciones.
3. CRUD de Reservas (creación manual + pasajeros) sin integraciones de pago.
4. Vista de Dashboard con métricas básicas (usuarios, reservas, tours) y gráficos simples.
5. Calendario básico con creación de `tours_diarios` y comprobación de disponibilidad simple.

## 16. Puntos abiertos / decisiones a confirmar (antes de comenzar a codificar)
- ¿Habilitar registro de administradores desde UI o se crearán manualmente en BD por ahora?
desde ui
- ¿Límite de intentos fallidos antes de bloqueo (recomiendo 5)?ok
- ¿Deseas thumbnails automáticos para todas las imágenes o solo mostrar la imagen original redimensionada en el front? solo mostrar la imagen original redimensionada
- ¿Política al eliminar recursos que están referenciados (soft delete vs denegar eliminación)?
antes de considerar esta opcion analiza como esta estructurado la base de datos y las respectivas tablas
---

Termina aquí la propuesta de flujo detallado. Por favor revisa y aprueba o marca cambios. Una vez aprobado pasaré a crear la estructura de carpetas y a implementar la primera iteración (MVP) en código.