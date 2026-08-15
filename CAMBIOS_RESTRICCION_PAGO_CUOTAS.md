# Resumen de Cambios: Restricción de Selección y Pago Secuencial de Cuotas (Exclusivo para Contratos Individuales)

**Fecha:** 15 de Agosto de 2026  
**Módulo:** Cobranzas / Pagos  
**Objetivo:** Restringir el proceso de cobranza para que únicamente en **contratos individuales** las cuotas se paguen en orden correlativo estricto. En **contratos de tipo Grupo**, los clientes pueden seleccionar y pagar cualquier cuota sin restricción secuencial.

---

## 📋 Descripción del Requerimiento

1. **Contratos Individuales:**
   - Únicamente la **siguiente cuota correlativa a pagar** (la primera cuota pendiente) permanece activa y seleccionable.
   - Las cuotas superiores se muestran en el listado deshabilitadas (`disabled`), con la leyenda `(Bloqueada: Pagar cuota anterior)`.
   - El servidor valida en `processIndividualPayment` que no existan cuotas anteriores impagadas antes de registrar el pago.

2. **Contratos Grupales:**
   - **Sin restricción secuencial:** El usuario puede seleccionar y abonar/pagar cualquier cuota disponible sin importar el estado de cuotas anteriores.
   - Todas las cuotas figuran habilitadas para su libre selección.

---

## 🛠️ Detalle de Archivos Modificados

### 1. Frontend / Interfaz de Usuario
**Archivo:** `resources/views/payments/index.blade.php`

- **Función JavaScript:** `getQuotas(contract_id)`
- **Cambios realizados:**
  - Si `currentContract.client_type == 'Grupo'`: Se renderizan todas las cuotas impagadas de forma activa/seleccionable sin deshabilitar ninguna ni agregar leyendas de bloqueo.
  - Si el contrato es **Individual**: Se mantiene la lógica donde solo la primera cuota impagada (`index === 0`) está activa, deshabilitando (`disabled`) las cuotas siguientes (`index > 0`).

---

### 2. Backend / Controlador de Pagos
**Archivo:** `app/Http/Controllers/PaymentController.php`

- **Métodos modificados:**
  - `processIndividualPayment`: Mantiene la validación en base de datos para garantizar el orden correlativo de pago.
  - `processUnifiedGroupPayment` y `processSeparatedGroupPayment`: Se les removió la validación de cuotas anteriores impagadas, permitiendo procesar pagos para cualquier cuota seleccionada del grupo.

---

## 🧪 Casos de Prueba y Verificación

1. **Ingreso al Formulario de Pago:**
   - Navegar a **Cobranzas ➔ Pagos** y presionar el botón **"Crear nuevo"**.
   - Seleccionar un contrato (individual o grupal) con múltiples cuotas pendientes (ej. Cuota 4, Cuota 5 y Cuota 6).

2. **Verificación Visual:**
   - Desplegar el selector **Cuota**.
   - Confirmar que la **Cuota 4** es la única seleccionable.
   - Confirmar que la **Cuota 5** y la **Cuota 6** aparecen grises / inactivas con la etiqueta `(Bloqueada: Pagar cuota anterior)`.

3. **Verificación de Transacción:**
   - Procesar el pago de la Cuota 4.
   - Volver a consultar el cliente en el modal de pagos; ahora la **Cuota 5** se habrá activado como la siguiente cuota seleccionable y la **Cuota 6** seguirá bloqueada.

---

## 📝 Mensajes de Commit Recomendados

- `feat(payments): restringir seleccion y pago de cuotas a orden correlativo`
- `feat(cobranzas): bloquear cuotas posteriores y permitir solo la siguiente cuota a pagar`
