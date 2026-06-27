# FUNCIONALIDADES DEL ROL DE SECRETARIA
## Instituto San Pablo del Oriente — Sistema de Gestión Curricular

Este documento detalla todas las operaciones y comandos que el rol de **Secretaria** (`rolActual = "secretaria"`) puede realizar en el sistema. Se clasifican según su estado de acceso real en el código fuente del proyecto y se detallan las sintaxis de los comandos.

---

### Resumen del Rol
* **Operaciones Administrativas:** Registro de alumnos/profesores, control de inscripciones, registro manual de pagos y cobro de cuotas, y carga/edición de notas académicas.
* **Operaciones Estructurales Bloqueadas:** No puede crear ni modificar carreras, mallas curriculares, periodos semestrales, cronogramas de inscripción, horarios, ni grupos.
* **Brechas en el Código actual:** La secretaria puede crear/modificar aulas y materias, así como registrar abandonos y validar recursantes debido a omisiones de validación de rol en las clases `BAula.java`, `BMateria.java` y `BSeguimiento.java`.

---

### 1. Gestión de Usuarios (`usuario`)
La secretaria puede registrar nuevos usuarios operativos y actualizar perfiles.

| Comando | Descripción | Sintaxis |
| :--- | :--- | :--- |
| `registrar_secretaria` | Registra otra secretaria | `usuario registrar_secretaria(nombre, apellido, DNI, email, telefono, legajo_personal, direccion|null, oficina|null, sueldo|null, observaciones|null)` |
| `registrar_profesor` | Registra un docente | `usuario registrar_profesor(nombre, apellido, DNI, email, telefono, especialidad, titulo, legajo, sueldo, horas_semanales|null, observaciones|null)` |
| `registrar_estudiante` | Registra un estudiante | `usuario registrar_estudiante(nombre, apellido, DNI, email, telefono, codigo_carrera, tutor_nombre|null, tutor_telefono|null, observaciones|null)` |
| `actualizar` | Modifica perfil de usuario | `usuario actualizar(id_usuario, nombre, apellido, telefono, legajo|null)` *(Permite actualizar a cualquier usuario del sistema)* |
| `listar` | Lista todos los usuarios | `usuario listar()` |
| `buscar` | Detalla datos de un usuario | `usuario buscar(id_usuario)` |
| `cambiarpassword` | Cambia contraseña | `usuario cambiarpassword(id_usuario, password_actual, password_nueva)` |

*Nota: Los comandos `usuario registrar_propietario`, `usuario registrar_director`, `usuario desactivar` y `usuario reactivar` se encuentran **restringidos** para el rol de secretaria.*

---

### 2. Aulas y Materias (Módulos Abiertos por Código)
> [!WARNING]
> Las clases de negocio `BAula.java` y `BMateria.java` no verifican el rol actual del usuario. Por lo tanto, la secretaria tiene acceso a los siguientes comandos de administración:

#### Módulo Aulas (`aula`)
* `aula registrar(nombre, capacidad, ubicacion, tipo)`
* `aula actualizar(id_aula, nombre, capacidad, ubicacion, tipo)`
* `aula desactivar(id_aula)`
* `aula listar()`
* `aula buscar(id_aula)`

#### Módulo Materias (`materia`)
* `materia registrar(codigo, nombre, duracion_meses, costo_mensual, creditos, id_materia_requisito|null)`
* `materia actualizar(id_materia, codigo, nombre, duracion_meses, costo_mensual, creditos, id_materia_requisito|null)`
* `materia desactivar(id_materia)`
* `materia listar()`
* `materia buscar(id_materia)`

---

### 3. Gestión de Inscripciones (`inscripcion`)
Permite administrar las inscripciones a grupos y la desvinculación de estudiantes.

| Comando | Descripción | Sintaxis |
| :--- | :--- | :--- |
| `registrar` | Pre-inscribe un estudiante | `inscripcion registrar(id_estudiante, id_oferta)` |
| `activar` | Activa una inscripción pendiente | `inscripcion activar(id_inscripcion)` |
| `retirar` | Retira materia a un alumno | `inscripcion retirar(id_inscripcion)` *(Libera vacante)* |
| `listar` | Lista inscripciones del estudiante | `inscripcion listar(id_estudiante)` |
| `listar_oferta` | Lista alumnos inscritos en un grupo | `inscripcion listar_oferta(id_oferta)` |
| `buscar` | Detalla datos de la inscripción | `inscripcion buscar(id_inscripcion)` |

---

### 4. Cobros y Pagos (`pago`)
La secretaria procesa y registra los pagos manuales en caja.

| Comando | Descripción | Sintaxis |
| :--- | :--- | :--- |
| `registrar_matricula` | Registra pago de Matrícula Única | `pago registrar_matricula(id_estudiante, monto, comprobante|null)` |
| `registrar_carrera` | Registra pago de carrera (Contado/Crédito) | `pago registrar_carrera(id_estudiante, codigo_carrera, monto, comprobante|null)` |
| `pagar_materia` | Registra pago de una materia suelta | `pago pagar_materia(id_inscripcion, monto)` |
| `pagar_cuota` | Registra cobro de cuota mensual | `pago pagar_cuota(id_pago_carrera, numero_cuota)` |
| `listar` | Lista el historial de pagos de un alumno | `pago listar(id_estudiante)` |
| `buscar` | Muestra el estado de un plan contratado | `pago buscar(id_pago_carrera)` |
| `verificar_matricula` | Valida si tiene matrícula pagada | `pago verificar_matricula(id_estudiante)` |

---

### 5. Evaluaciones y Notas (`evaluacion`)
La secretaria posee privilegios para asentar y modificar calificaciones de los estudiantes en representación del docente.

| Comando | Descripción | Sintaxis |
| :--- | :--- | :--- |
| `registrar` | Registra calificación de una evaluación | `evaluacion registrar(id_inscripcion, numero_eval_1_a_4, nota)` |
| `editar` | Rectifica una calificación cargada | `evaluacion editar(id_inscripcion, numero_eval_1_a_4, nueva_nota)` |
| `listar` | Muestra notas de una inscripción | `evaluacion listar(id_inscripcion)` |
| `listar_grupo` | Muestra padrón de notas de un grupo | `evaluacion listar_grupo(id_grupo)` |

---

### 6. Seguimiento Académico (`seguimiento`)
La secretaria puede consultar estadísticas e historiales académicos.

| Comando | Descripción | Sintaxis |
| :--- | :--- | :--- |
| `historial` | Kárdex académico completo del estudiante | `seguimiento historial(id_estudiante)` |
| `resumen` | Resumen de promedios e indicadores | `seguimiento resumen(id_estudiante)` |
| `detalles_evaluaciones` | Notas y ponderaciones por materia | `seguimiento detalles_evaluaciones(id_inscripcion)` |
| `validar_recursar` | Valida si el alumno puede cursar de nuevo | `seguimiento validar_recursar(id_estudiante, id_materia)` *(Sin restricción de rol)* |
| `registrar_abandono` | Registra abandono definitivo de carrera | `seguimiento registrar_abandono(id_estudiante, motivo)` *(Sin restricción de rol)* |

---

### 7. Reportes y Estadísticas (`reporte`)
La secretaria puede generar todos los reportes del sistema con excepción de las auditorías de accesos.

* **Rendimiento Individual:** `reporte desempeño_estudiante(id_estudiante)`
* **Eficiencia Académica:** `reporte tasa_aprobacion(id_materia, id_periodo)`
* **Alumnos Críticos:** `reporte estudiantes_riesgo(id_carrera)`
* **Avance Curricular:** `reporte progreso_carrera(id_estudiante)`
* **Capacidad de Cursos:** `reporte ocupacion_grupos(id_periodo)`
* **Ingresos por Matrícula:** `reporte ingreso_matricula(fecha_inicio, fecha_fin)`
* **Ingresos por Cursos:** `reporte ingreso_pagos_materia(fecha_inicio, fecha_fin)`
* **Control de Cobranza (Mora):** `reporte deudas_pendientes()`
* **Finanzas Futuras:** `reporte proyeccion_ingresos(mes, año)`
* **Demanda Administrativa:** `reporte inscripciones_carrera()`
* **Distribución Docente:** `reporte carga_profesores()`
* **Uso de Espacios:** `reporte disponibilidad_aulas()`

*Nota: El comando `reporte auditoria(fecha_inicio, fecha_fin)` está **restringido** y es exclusivo del Propietario.*

---

### 8. Módulos Estructurales Restringidos (Solo Lectura)
La secretaria no puede alterar la parametrización de las siguientes entidades, pero puede listarlas:
* **Carreras (`carrera`):** Permitido `listar()`, `buscar(id_carrera)` y `listar_materias(codigo_carrera)`.
* **Malla Curricular (`malla`):** Permitido `listar(id_carrera)` y `listar_niveles(id_carrera)`.
* **Periodos Semestrales (`periodo`):** Permitido `listar(id_nivel)` y `buscar(id_periodo)`.
* **Oferta de Grupos (`grupo`):** Permitido `listar(id_periodo)`, `listar_carrera(codigo_carrera, id_periodo)`, `listar_oferta()` y `buscar(id_grupo)`.
* **Cronogramas (`cronograma`):** Permitido `listar()`.
* **Horarios (`horario`):** Permitido `listar()`, `listar_dia(dia_semana)` y `buscar(id_horario)`.
