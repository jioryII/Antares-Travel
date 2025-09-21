# 📊 ANÁLISIS DE CONFLICTOS: Eliminación de Reservas

## 🔍 **Análisis de Relaciones de la Tabla `reservas`**

### **📥 Relaciones Entrantes (Tablas que referencian a reservas)**

La tabla `reservas` tiene **5 tablas hijas** que la referencian:

#### **1. `disponibilidad_guias`**

```sql
CONSTRAINT disponibilidad_guias_ibfk_2
FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva)
ON DELETE SET NULL
```

- **Comportamiento**: Al eliminar reserva → Campo `id_reserva` se pone `NULL`
- **Conflicto**: ❌ **NO HAY CONFLICTO** - Se actualiza automáticamente
- **Implicación**: La disponibilidad del guía queda sin reserva asociada

#### **2. `disponibilidad_vehiculos`**

```sql
CONSTRAINT disponibilidad_vehiculos_ibfk_2
FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva)
ON DELETE SET NULL
```

- **Comportamiento**: Al eliminar reserva → Campo `id_reserva` se pone `NULL`
- **Conflicto**: ❌ **NO HAY CONFLICTO** - Se actualiza automáticamente
- **Implicación**: La disponibilidad del vehículo queda sin reserva asociada

#### **3. `historial_uso_ofertas`**

```sql
CONSTRAINT historial_uso_ofertas_ibfk_3
FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva)
ON DELETE CASCADE
```

- **Comportamiento**: Al eliminar reserva → Registros del historial se **ELIMINAN**
- **Conflicto**: ❌ **NO HAY CONFLICTO** - Se elimina automáticamente
- **Implicación**: Se pierde el historial de uso de ofertas para esa reserva

#### **4. `pagos`**

```sql
CONSTRAINT pagos_ibfk_1
FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva)
ON DELETE CASCADE
```

- **Comportamiento**: Al eliminar reserva → Todos los pagos se **ELIMINAN**
- **Conflicto**: ⚠️ **POSIBLE CONFLICTO DE NEGOCIO** - Se eliminan registros financieros
- **Implicación**: Se pierden registros de transacciones y pagos

#### **5. `pasajeros`**

```sql
CONSTRAINT pasajeros_ibfk_1
FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva)
ON DELETE CASCADE
```

- **Comportamiento**: Al eliminar reserva → Todos los pasajeros se **ELIMINAN**
- **Conflicto**: ❌ **NO HAY CONFLICTO TÉCNICO** - Se elimina automáticamente
- **Implicación**: Se pierden datos de pasajeros asociados

### **📤 Relaciones Salientes (reservas referencia a otras tablas)**

#### **1. `usuarios`**

```sql
CONSTRAINT reservas_ibfk_1
FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
ON DELETE CASCADE
```

- **Implicación**: Si se elimina el usuario → todas sus reservas se eliminan
- **No afecta**: La eliminación de reservas

#### **2. `tours`**

```sql
CONSTRAINT reservas_ibfk_2
FOREIGN KEY (id_tour) REFERENCES tours(id_tour)
ON DELETE CASCADE
```

- **Implicación**: Si se elimina el tour → todas sus reservas se eliminan
- **No afecta**: La eliminación de reservas

#### **3. `ofertas`**

```sql
CONSTRAINT reservas_ofertas_ibfk
FOREIGN KEY (id_oferta_aplicada) REFERENCES ofertas(id_oferta)
ON DELETE SET NULL
```

- **Implicación**: Si se elimina la oferta → campo se pone NULL en reservas
- **No afecta**: La eliminación de reservas

## 🚨 **CONFLICTOS IDENTIFICADOS**

### **❌ CONFLICTOS TÉCNICOS**

**NINGUNO** - Todas las relaciones están configuradas con `ON DELETE CASCADE` o `ON DELETE SET NULL`

### **⚠️ CONFLICTOS DE NEGOCIO**

#### **1. PÉRDIDA DE INFORMACIÓN FINANCIERA**

```
Tabla: pagos
Problema: Al eliminar reserva se eliminan todos los registros de pagos
Impacto: Pérdida de información contable y fiscal
Severidad: ALTA
```

#### **2. PÉRDIDA DE DATOS PERSONALES**

```
Tabla: pasajeros
Problema: Se eliminan datos de pasajeros
Impacto: Pérdida de información de clientes
Severidad: MEDIA
```

#### **3. PÉRDIDA DE HISTORIAL DE OFERTAS**

```
Tabla: historial_uso_ofertas
Problema: Se elimina el registro de uso de promociones
Impacto: Pérdida de información para análisis de marketing
Severidad: BAJA
```

#### **4. DISPONIBILIDAD HUÉRFANA**

```
Tablas: disponibilidad_guias, disponibilidad_vehiculos
Problema: Quedan registros de disponibilidad sin reserva asociada
Impacto: Datos inconsistentes en el sistema
Severidad: MEDIA
```

## 📋 **RECOMENDACIONES**

### **🚀 ELIMINACIÓN DIRECTA (Actual)**

**Pros:**

- ✅ Sin conflictos técnicos
- ✅ Eliminación rápida y automática
- ✅ No requiere verificaciones complejas

**Contras:**

- ❌ Pérdida de información financiera crítica
- ❌ Pérdida de datos de pasajeros
- ❌ Sin posibilidad de recuperación

### **🛡️ ELIMINACIÓN SEGURA (Recomendada)**

#### **Verificaciones Previas:**

1. **Verificar pagos realizados** - No eliminar si hay pagos > 0
2. **Verificar estado** - Solo permitir eliminar reservas en estado 'Cancelada' o 'Pendiente'
3. **Verificar fecha** - Advertir si es una reserva futura

#### **Proceso de Eliminación Segura:**

```php
// 1. Verificar estado de la reserva
if ($reserva['estado'] !== 'Cancelada' && $reserva['estado'] !== 'Pendiente') {
    throw new Exception('Solo se pueden eliminar reservas Canceladas o Pendientes');
}

// 2. Verificar pagos
if ($total_pagos > 0) {
    throw new Exception('No se puede eliminar reserva con pagos realizados. Debe proceder con reembolso.');
}

// 3. Archivar información crítica antes de eliminar
// Mover datos a tabla de auditoria
// Luego proceder con eliminación
```

### **📊 ALTERNATIVA: ELIMINACIÓN LÓGICA**

En lugar de eliminar físicamente, marcar como eliminada:

```sql
ALTER TABLE reservas ADD COLUMN eliminada BOOLEAN DEFAULT FALSE;
ALTER TABLE reservas ADD COLUMN fecha_eliminacion TIMESTAMP NULL;
ALTER TABLE reservas ADD COLUMN eliminada_por INT UNSIGNED NULL;
```

## 🎯 **CONCLUSIÓN**

**Estado Actual:** ✅ **TÉCNICAMENTE SEGURO** para eliminar

- No hay restricciones de foreign key que impidan la eliminación
- Todas las relaciones están configuradas correctamente

**Riesgo de Negocio:** ⚠️ **ALTO**

- Pérdida de información financiera crítica
- Pérdida de datos de clientes
- Sin posibilidad de auditoría posterior

**Recomendación:** 🛡️ **IMPLEMENTAR VERIFICACIONES** antes de eliminar reservas con:

- Pagos realizados
- Estados que no sean 'Cancelada' o 'Pendiente'
- Datos críticos de negocio
