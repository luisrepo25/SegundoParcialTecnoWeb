# Rol Propietario — Guía de Funcionalidades

> Nivel jerárquico máximo. Tiene acceso total al sistema sin restricciones.

---

## CU1 — Gestión de Usuarios

- Registrar propietario, director, secretaria, profesor y estudiante
- Listar todos los usuarios activos
- Buscar usuario por ID
- Actualizar datos de cualquier usuario
- Desactivar y reactivar cualquier usuario
- Cambiar contraseña (propia o de otro usuario)

---

## CU2 — Gestión de Aulas

- Registrar aula (nombre, capacidad, ubicación, tipo)
- Actualizar datos de aula
- Desactivar aula
- Listar todas las aulas
- Buscar aula por ID

---

## CU3 — Gestión de Materias

- Registrar materia (con o sin prerequisito)
- Actualizar materia
- Desactivar materia
- Listar todas las materias
- Buscar materia por ID

---

## CU4 — Gestión de Carreras

- Registrar carrera (código, nombre, duración, tipo, costo)
- Actualizar carrera
- Desactivar carrera
- Listar carreras (público)
- Buscar carrera por ID
- Ver malla y costos de carrera (público)

---

## CU5 — Gestión de Malla Curricular

- Los niveles se crean automáticamente al registrar carrera
- Agregar materia a un nivel de la malla
- Actualizar materia en la malla (orden, obligatoriedad)
- Quitar materia de un nivel
- Listar malla completa de una carrera
- Listar niveles de una carrera

---

## CU6 — Inscripciones

- Registrar inscripción manualmente (admin directo)
- Activar inscripción sin cobro
- Retirar estudiante de un curso
- Listar inscripciones de cualquier estudiante
- Buscar inscripción por ID

---

## CU7 — Gestión de Pagos

- Registrar matrícula directa (sin QR, pago ya confirmado)
- Registrar plan de carrera completa para un estudiante
- Pagar cuota de carrera a crédito
- Pagar materia suelta para un estudiante
- Listar pagos de cualquier estudiante
- Buscar plan de pago por ID
- Verificar estado de matrícula de cualquier estudiante

---

## CU8 — Gestión de Periodos

- Registrar periodo académico (fechas inicio/fin, tipo, nivel)
- Actualizar periodo
- Desactivar periodo
- Listar periodos de un nivel
- Buscar periodo por ID

---

## CU9 — Gestión de Grupos

- Registrar grupo (materia + aula + periodo + profesor + horario + vacantes)
- Actualizar grupo (vacantes)
- Desactivar grupo
- Listar grupos de un periodo
- Listar oferta completa del semestre
- Listar grupos por carrera y periodo
- Buscar grupo por ID

---

## CU10 — Cronogramas / Ventanas de Inscripción

- Registrar cronograma (global o por carrera)
- Listar cronogramas y ver cuáles están abiertos/cerrados

---

## CU11 — Gestión de Horarios

- Registrar horario (día, hora inicio, hora fin)
- Actualizar horario
- Desactivar horario
- Listar todos los horarios
- Listar horarios por día
- Buscar horario por ID

---

## CU12 — Evaluaciones y Notas

- Registrar notas para cualquier inscripción (en nombre de profesor)
- Registrar las 4 evaluaciones en un solo comando (masivo)
- Editar nota ya cargada
- Listar evaluaciones de una inscripción
- Listar padrón completo de un grupo

---

## CU13 — Seguimiento Académico

- Ver historial académico de cualquier estudiante
- Ver resumen con indicadores (promedios, tasa de aprobación, progreso)
- Ver detalles de evaluaciones de cualquier inscripción
- Validar si un estudiante puede recursar una materia
- Registrar abandono de carrera

---

## CU14 — Reportes y Estadísticas

### Académicos
- Desempeño individual de un estudiante (con gráfico)
- Tasa de aprobación por materia y periodo (con gráfico)
- Estudiantes en riesgo por carrera (con gráfico)
- Progreso de carrera de un estudiante (con gráfico)
- Ocupación de grupos por periodo (con gráfico)

### Financieros
- Ingresos por matrículas en rango de fechas (con gráfico)
- Ingresos por pagos de materia en rango de fechas (con gráfico)
- Deudas y cuotas pendientes (con gráfico)
- Proyección de ingresos por mes y año (con gráfico)

### Administrativos
- Inscripciones por carrera (con gráfico)
- Carga horaria de profesores (con gráfico)
- Disponibilidad de aulas (con gráfico)
- **Auditoría del sistema** — exclusivo del propietario, sin gráfico

---

## Accesos exclusivos del Propietario

| Acción | Otros roles |
|---|---|
| Registrar otro propietario | Nadie más puede |
| Desactivar/reactivar cualquier usuario | Solo Director comparte este permiso |
| Ver reporte de auditoría del sistema | Nadie más puede |
| Eliminar registros de seguimiento | Nadie más puede |



--------------
listar_mios — ver grupos propios (más útil para profesor, pero propietario también puede)
listar_disponibles — ver oferta filtrada para inscribirse   , esto es estudiante
En inscripciones faltó:

listar_oferta(id_oferta) — ver todos los inscriptos en un grupo específico