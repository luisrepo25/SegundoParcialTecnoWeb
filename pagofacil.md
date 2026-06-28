# PagoFácil — Guía de Implementación Web

> Documento de referencia basado en el código fuente del sistema de email.
> Cubre todos los archivos: `PagoFacilService`, `PagoFacilVerificationThread`, `DPagoFacil`, `BPago`, `HandlePago`.

---

## 1. Credenciales y Endpoints

### Variables de entorno requeridas

```env
TOKEN_SERVICE=<valor en PagoFacilService.TOKEN_SERVICE>
TOKEN_SECRET=<valor en PagoFacilService.TOKEN_SECRET>
```

### URLs de la API PagoFácil

| Acción | Método | URL |
|---|---|---|
| Autenticación | POST | `https://masterqr.pagofacil.com.bo/api/services/v2/login` |
| Generar QR | POST | `https://masterqr.pagofacil.com.bo/api/services/v2/generate-qr` |
| Consultar transacción | POST | `https://masterqr.pagofacil.com.bo/api/services/v2/query-transaction` |

### Callback (tu servidor)

```
POST https://grupo07sc.ngrok.app/callback
```

PagoFácil hace un POST a esta URL cuando el pago es confirmado por la entidad bancaria.

---

## 2. Autenticación

El token se obtiene una vez y se reutiliza durante 50 minutos (caché en memoria).

### Request de login

```http
POST /api/services/v2/login
Headers:
  tcTokenService: <TOKEN_SERVICE>
  tcTokenSecret:  <TOKEN_SECRET>
  Content-Type: application/json
```

Sin body. El token viene en la respuesta JSON.

### Lógica de caché (replicar en web)

```
si (tokenCacheado existe Y tiempo_actual < tokenFechaObtencion + 50min):
    usar token cacheado
sino:
    obtener nuevo token → guardar con timestamp
```

---

## 3. Tabla en Base de Datos

Tabla: `pagofacil_transacciones`

| Campo | Tipo | Descripción |
|---|---|---|
| `id_transaccion_pf` | int PK | ID local auto-incremental |
| `transaction_id_api` | bigint | ID devuelto por PagoFácil al generar el QR |
| `payment_number` | varchar | Número de pago único generado por el sistema |
| `id_estudiante` | int FK | Estudiante que paga |
| `monto` | double | Monto real del sistema (no el monto de prueba) |
| `concepto` | varchar | `matricula` / `materia` / `carrera` / `cuota` |
| `codigo_grupo` | varchar NULLABLE | Uso diferente según concepto (ver tabla abajo) |
| `id_inscripcion` | int NULLABLE | Solo para concepto `materia` y `cuota` |
| `estado` | varchar | `pendiente` / `pagado` / `expirado` |
| `fecha_generacion` | timestamp | Momento en que se generó el QR |

### Significado de `codigo_grupo` por concepto

| `concepto` | `codigo_grupo` | `id_inscripcion` |
|---|---|---|
| `matricula` | `null` | `null` |
| `carrera` | código de carrera (ej: `ING-SIS`) | `null` |
| `materia` | código del grupo (ej: `PROG101-A`) | id_inscripcion |
| `cuota` | número de cuota como string (ej: `"3"`) | id_pago_carrera |

---

## 4. Generar QR de Pago

### Request

```http
POST /api/services/v2/generate-qr
Authorization: Bearer <token>
Content-Type: application/json

{
  "paymentMethod": 34,
  "documentType": 1,
  "currency": 2,
  "clientName": "Juan Pérez",
  "documentId": "12345678",
  "phoneNumber": "70000000",
  "email": "juan@example.com",
  "paymentNumber": "MAT-5-1719432000000",
  "amount": 0.01,
  "clientCode": "5",
  "callbackUrl": "https://grupo07sc.ngrok.app/callback"
}
```

Valores fijos:
- `paymentMethod: 34` (siempre)
- `documentType: 1` (siempre)
- `currency: 2` = BOB bolivianos

### Response esperada

```json
{
  "transactionId": 987654321,
  "qrImage": "data:image/png;base64,iVBORw0KGgo..."
}
```

- `transactionId`: guardar en `pagofacil_transacciones.transaction_id_api`
- `qrImage`: imagen base64 del QR para mostrar al usuario

### Nota sobre montos en pruebas

El sistema actualmente envía **0.01 BOB** a la pasarela para pruebas reales,
independientemente del monto del sistema. El monto real se guarda en la DB local.
Para producción, cambiar a usar el monto real del sistema.

---

## 5. Los 4 Conceptos de Pago

### 5.1 Matrícula Única

- Costo fijo: **500 BOB**
- Solo si el estudiante NO tiene matrícula previa
- Solo si la ventana de inscripción está abierta (validado en BPago)
- `paymentNumber`: `MAT-{idEstudiante}-{timestamp}`

**Flujo post-pago (PagoFacilVerificationThread):**
Al confirmar → marcar matrícula como pagada → enviar email con lista de grupos disponibles y siguiente paso (`pago pagar_materia`)

### 5.2 Pago de Carrera Completa

- Mínimo: 30% del costo total de la carrera (sin descuento)
- Descuento del 20% si se paga el 100% de contado
- `paymentNumber`: `CARRERA-{idEstudiante}-{timestamp}`
- `codigo_grupo`: código de la carrera

**Modalidades:**
| Monto pagado | Modalidad | Estado |
|---|---|---|
| ≥ costo con 20% descuento | Contado | `pagado` |
| < costo total pero ≥ 30% | Crédito | `parcial` |

**Plan de crédito:** Un trigger de BD calcula `materias_cubiertas` y `cuotas_total` automáticamente después del INSERT.

**Flujo post-pago:** Al confirmar → registrar `pago_carrera_completa` en DB → enviar email de confirmación con el plan de pago

### 5.3 Pago de Materia

- Precio por materia calculado de la carrera del estudiante
- Si el estudiante ya tiene carrera pagada (contado), la inscripción queda activa sin cobro
- `paymentNumber`: `MATERIA-{idEstudiante}-{timestamp}`
- `codigo_grupo`: código del grupo (ej: `PROG101-A`)
- `id_inscripcion`: el ID generado al pre-registrar la inscripción

**Flujo:** Inscripción se crea en estado `pendiente` → QR generado → al confirmar pago → inscripción pasa a `activo`

### 5.4 Pago de Cuota

- Detecta automáticamente la próxima cuota pendiente del plan de carrera del estudiante
- `paymentNumber`: `CUOTA-{idPagoCarrera}-{numeroCuota}-{timestamp}`
- `codigo_grupo`: número de cuota como string
- `id_inscripcion`: id_pago_carrera

**Flujo post-pago:** Al confirmar → marcar cuota como `pagada` → actualizar estado del pago de carrera → enviar email

---

## 6. Trigger de Base de Datos — `trg_pagofacil_confirmacion`

Este es el componente más importante. La lógica de negocio post-pago **la ejecuta la propia BD**, no Java ni la web.

### Definición

```sql
CREATE TRIGGER trg_pagofacil_confirmacion
    BEFORE UPDATE ON pagofacil_transacciones
    FOR EACH ROW
    EXECUTE FUNCTION fn_confirmar_pago_qr();
```

Se dispara cuando se hace:

```sql
UPDATE pagofacil_transacciones SET estado = 'pagado' WHERE transaction_id_api = ?;
```

### Qué hace el trigger según `concepto`

#### `concepto = 'matricula'`
```sql
INSERT INTO matricula_unica (id_estudiante, monto_pagado, comprobante, estado)
VALUES (NEW.id_estudiante, NEW.monto, NEW.payment_number, 'pagado')
ON CONFLICT (id_estudiante) DO NOTHING;
```

#### `concepto = 'materia'`
```sql
-- 1. Registrar el pago (o actualizar si ya existía en 'pendiente')
INSERT INTO pago_materia_suelta (id_inscripcion, monto_pagado, estado)
VALUES (id_inscripcion, NEW.monto, 'pagado')
ON CONFLICT (id_inscripcion) DO UPDATE SET estado = 'pagado', fecha_pago = CURRENT_DATE;

-- 2. Activar la inscripción
UPDATE inscripciones SET estado = 'activo' WHERE id_inscripcion = ?;
```
Si `id_inscripcion` es NULL, el trigger la busca en `inscripciones` por `id_estudiante + codigo_grupo`.

#### `concepto = 'carrera'`
```sql
-- codigo_grupo = código de la carrera (ej: 'ING-SIS')
-- Aplica 20% descuento si monto >= costo_total * 0.80

INSERT INTO pago_carrera_completa
    (id_estudiante, id_carrera, monto_total, monto_pagado, forma_pago, estado, fecha_contrato)
VALUES (...)
ON CONFLICT (id_estudiante, id_carrera) DO NOTHING;
```
- Si `monto >= costo * 0.80` → `forma_pago='contado'`, `estado='pagado'`
- Si no → `forma_pago='credito'`, `estado='parcial'`

Otro trigger (`crear_cuotas_credito`) crea el plan de cuotas automáticamente cuando se inserta en `pago_carrera_completa` con `forma_pago='credito'`.

#### `concepto = 'cuota'`
```sql
-- id_inscripcion reutilizado como id_pago_carrera
-- codigo_grupo reutilizado como numero_cuota (string)

UPDATE cuotas_carrera
SET estado = 'pagado', fecha_pago = CURRENT_DATE
WHERE id_pago_carrera = NEW.id_inscripcion
  AND numero_cuota = NEW.codigo_grupo::INT
  AND estado = 'pendiente';

-- Si no quedan cuotas pendientes → el plan queda 'pagado'
UPDATE pago_carrera_completa
SET estado = CASE
    WHEN (SELECT COUNT(*) FROM cuotas_carrera WHERE id_pago_carrera = ? AND estado = 'pendiente') = 0
         THEN 'pagado'
    ELSE 'parcial'
END
WHERE id_pago_carrera = NEW.id_inscripcion;
```

### También sets automáticos del trigger
```sql
NEW.fecha_pago := CURRENT_TIMESTAMP;   -- se guarda cuando se confirma
```

### Conclusión para la web

La web (y el callback) solo necesita **un UPDATE**:
```sql
UPDATE pagofacil_transacciones
SET estado = 'pagado'
WHERE transaction_id_api = :transactionId
  AND estado = 'pendiente';   -- condición de idempotencia
```

El trigger hace todo lo demás. **No necesitas código adicional para crear la matrícula, activar la inscripción, ni registrar el pago de carrera.** La BD lo hace sola.

---

## 7. Consultar Estado de Transacción (para el polling)

```http
POST /api/services/v2/query-transaction
Authorization: Bearer <token>
Content-Type: application/json

{
  "transactionId": 987654321
}
```

### Lógica de confirmación (cualquiera de estas condiciones)

```
pagado = (paymentDate != null) OR (paymentStatus IN [2, 5])
```

- `paymentStatus: 1` = pendiente
- `paymentStatus: 2` = confirmado
- `paymentStatus: 5` = confirmado (variante)

---

## 7. Estados del QR y Expiración

| Estado | Significado |
|---|---|
| `pendiente` | QR generado, esperando pago |
| `pagado` | Pago confirmado por PagoFácil |
| `expirado` | Han pasado más de 15 minutos sin pago |

**Regla de expiración:** Si `fecha_generacion + 15 minutos < ahora` → marcar como `expirado` y notificar al usuario por email.

---

## 8. Thread de Verificación (Polling)

En el sistema de email existe `PagoFacilVerificationThread` que corre en segundo plano.

### Comportamiento

- Intervalo de polling: **cada 15 segundos**
- Consulta todas las transacciones con `estado = 'pendiente'`
- Para cada una:
  1. Si `fecha_generacion + 15 min < ahora` → marcar `expirado` + email de aviso
  2. Si no expiró → llamar a `queryTransaction(transactionIdApi)`
     - Si confirmado → llamar a `procesarPagoConfirmado()` (ver abajo) + marcar `pagado` + email de confirmación

### En la web: dos alternativas equivalentes

**Opción A — Polling activo (replicar el thread):**
- Un proceso/worker en background hace lo mismo cada 15 segundos
- Consulta `pagofacil_transacciones WHERE estado='pendiente'`
- Llama a `/query-transaction` por cada una

**Opción B — Callback (recomendado para web):**
- PagoFácil hace POST a tu `/callback` cuando el pago se confirma
- Más eficiente, no necesita polling
- El polling sirve como respaldo en caso de que el callback falle

---

## 9. Endpoint Callback

```
POST https://grupo07sc.ngrok.app/callback
```

PagoFácil envía los datos de la transacción confirmada. El servidor debe:

1. Leer `transactionId` del body
2. Buscar la fila en `pagofacil_transacciones` con ese `transaction_id_api`
3. Verificar que `estado = 'pendiente'` (idempotencia — no procesar dos veces)
4. Ejecutar la acción según `concepto`:
   - `matricula` → insertar en `matricula_unica` + activar estado
   - `carrera` → insertar en `pago_carrera_completa` (o disparar trigger)
   - `materia` → actualizar `inscripcion.estado = 'activo'` + pagar `pago_materia_suelta`
   - `cuota` → marcar cuota como pagada + actualizar estado del plan de carrera
5. Actualizar `estado = 'pagado'` en `pagofacil_transacciones`
6. Enviar email de confirmación al estudiante
7. Responder `200 OK` a PagoFácil

---

## 10. Flujo Completo en la Web (por concepto)

```
[Estudiante en web]
        │
        ▼
  POST /api/pago/matricula
  { monto: 500 }
        │
        ▼
  Validar: ¿tiene matrícula? ¿ventana abierta?
        │
        ▼
  PagoFácilService.generateQR(...)
  → { transactionId, qrImage }
        │
        ▼
  INSERT pagofacil_transacciones (estado='pendiente')
        │
        ▼
  Devolver QR al frontend (imagen base64 o PNG)
        │
        ▼
  [Usuario escanea QR en banca móvil]
        │
    ┌───┴────────────────────────────┐
    │ Callback (instantáneo)         │ Polling cada 15s (respaldo)
    ▼                                ▼
  POST /callback                    queryTransaction(transactionId)
    │                                │
    └──────────────┬─────────────────┘
                   ▼
         Verificar estado en DB
         (no procesar si ya es 'pagado')
                   │
                   ▼
         Ejecutar lógica de negocio
         según concepto
                   │
                   ▼
         UPDATE estado='pagado'
                   │
                   ▼
         Enviar email de confirmación
```

---

## 11. Parámetros del Estudiante para el QR

Estos datos se toman de la tabla `usuarios` al momento de generar el QR:

| Campo API | Fuente en DB | Fallback |
|---|---|---|
| `clientName` | `usuarios.nombre + apellido` | — |
| `documentId` | `usuarios.dni` | — |
| `phoneNumber` | `usuarios.telefono` | `"70000000"` |
| `email` | `emailFrom` (autenticado) | — |
| `clientCode` | `String.valueOf(idEstudiante)` | — |

---

## 12. Comportamiento según Rol

| Rol | Comportamiento |
|---|---|
| `estudiante` | Siempre genera QR de PagoFácil. El ID de estudiante se resuelve desde su email. |
| `secretaria`, `director`, `propietario` | Registro directo en DB sin QR. Usa comprobante manual. |
| `desconocido` | Bloqueado. Debe registrarse primero. |

---

## 13. Comandos del Sistema de Email (referencia)

| Comando | Rol | Descripción |
|---|---|---|
| `pago registrar_matricula(monto)` | estudiante | Genera QR de matrícula |
| `pago registrar_matricula(id_est, monto)` | admin | Registro directo en DB |
| `pago registrar_carrera(cod_carrera, monto)` | estudiante | QR pago de carrera |
| `pago registrar_carrera(id_est, cod_carrera, monto)` | admin | Directo en DB |
| `pago pagar_materia(codigo_grupo)` | estudiante | QR inscripción + pago materia |
| `pago pagar_materia(id_inscripcion, monto)` | admin | Pago directo materia |
| `pago pagar_cuota()` | estudiante | QR próxima cuota automática |
| `pago pagar_cuota(id_pago_carrera, num_cuota)` | admin | Cuota directa |
| `pago listar()` | estudiante | Ver todos sus pagos |
| `pago listar(id_estudiante)` | admin | Ver pagos de un estudiante |
| `pago buscar(id_pago_carrera)` | todos | Detalle del plan de pago |
| `pago verificar_matricula()` | estudiante | Estado de su matrícula |
| `pago verificar_matricula(id_estudiante)` | admin | Estado de matrícula de cualquiera |

---

## 14. Archivos de Referencia

| Archivo | Capa | Responsabilidad |
|---|---|---|
| [HandlePago.java](../src/main/java/com/tecnoweb/grupo07sc/command/HandlePago.java) | Command | Routing de comandos y diferenciación estudiante/admin |
| [BPago.java](../src/main/java/com/tecnoweb/grupo07sc/business/BPago.java) | Business | Validaciones, resolución de IDs, orquestación del QR |
| [PagoFacilService.java](../src/main/java/com/tecnoweb/grupo07sc/utils/PagoFacilService.java) | Utils | Llamadas HTTP a la API PagoFácil (login, generateQR, queryTransaction) |
| [DPagoFacil.java](../src/main/java/com/tecnoweb/grupo07sc/data/DPagoFacil.java) | Data | CRUD de `pagofacil_transacciones` |
| [PagoFacilVerificationThread.java](../src/main/java/com/tecnoweb/grupo07sc/communication/PagoFacilVerificationThread.java) | Communication | Polling cada 15s: expiración y confirmación de pagos |
