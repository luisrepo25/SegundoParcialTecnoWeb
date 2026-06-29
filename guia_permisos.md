# Permisos por Rol — Los 14 CUs

> Basado en `PermissionUtil.java`, `HandleReporte.java`, `HandlePago.java`, `BPago.java`, `HandleSeguimiento.java`.
>
> Jerarquía de permisos (nivel interno Java, no es el id_rol de la BD):
> `propietario` > `director` > `secretaria` > `profesor` > `estudiante`

---

## Tabla Resumen

Leyenda: `✓` acceso total · `~` parcial/solo lo propio · `✗` sin acceso · `★` exclusivo

| CU | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| CU1 — Usuarios | ✓ | ~ | ~ | ~ | ~ |
| CU2 — Aulas | ✓ | ✓ | solo ver | solo ver | solo ver |
| CU3 — Materias | ✓ | ✓ | solo ver | solo ver | solo ver |
| CU4 — Carreras | ✓ | ✓ | solo ver | solo ver | solo ver |
| CU5 — Malla Curricular | ✓ | ✓ | solo ver | solo ver | solo ver |
| CU6 — Inscripciones | ✓ | ✓ | ✓ | solo ver | ~ |
| CU7 — Pagos | ✓ | ✓ | ✓ | solo ver | ~ (QR) |
| CU8 — Periodos | ✓ | ✓ | solo ver | solo ver | solo ver |
| CU9 — Grupos | ✓ | ✓ | solo ver | solo ver | solo ver |
| CU10 — Cronogramas | ✓ | ✓ | solo ver | solo ver | solo ver |
| CU11 — Horarios | ✓ | ✓ | solo ver | solo ver | solo ver |
| CU12 — Evaluaciones | ✓ | ✓ | ✓ | ✓ | solo ver |
| CU13 — Seguimiento | ✓ | ✓ | ✓ | ✓ | ~ |
| CU14 — Reportes | ✓ ★ | ~ | ~ | ~ | ✗ |

---

## CU1 — Gestión de Usuarios

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar propietario | ✓ | ✗ | ✗ | ✗ | ✗ |
| Registrar director | ✓ | ✓ | ✗ | ✗ | ✗ |
| Registrar secretaria | ✓ | ✓ | ✓ | ✗ | ✗ |
| Registrar profesor | ✓ | ✓ | ✓ | ✗ | ✗ |
| Registrar estudiante | ✓ | ✓ | ✓ | ✗ | ✗ |
| Auto-registro (desconocido) | — | — | — | — | ✓ ¹ |
| Listar usuarios | ✓ | ✓ | ✓ | ✓ | ✓ |
| Buscar usuario por ID | ✓ | ✓ | ✓ | ✓ | ✓ |
| Actualizar datos de cualquier usuario | ✓ | ✓ | ✓ | ✗ | ✗ |
| Actualizar datos propios | ✓ | ✓ | ✓ | ✓ | ✓ |
| Desactivar usuario | ✓ | ✓ | ✗ | ✗ | ✗ |
| Reactivar usuario | ✓ | ✓ | ✗ | ✗ | ✗ |
| Cambiar contraseña (cualquiera) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Cambiar contraseña propia | ✓ | ✓ | ✓ | ✓ | ✓ |

> ¹ `registrar_estudiante` no tiene guardia de permisos — el rol "desconocido" puede ejecutarlo para auto-registrarse.

---

## CU2 — Gestión de Aulas

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar aula | ✓ | ✓ | ✗ | ✗ | ✗ |
| Actualizar aula | ✓ | ✓ | ✗ | ✗ | ✗ |
| Desactivar aula | ✓ | ✓ | ✗ | ✗ | ✗ |
| Listar aulas | ✓ | ✓ | ✓ | ✓ | ✓ |
| Buscar aula por ID | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## CU3 — Gestión de Materias

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar materia | ✓ | ✓ | ✗ | ✗ | ✗ |
| Actualizar materia | ✓ | ✓ | ✗ | ✗ | ✗ |
| Desactivar materia | ✓ | ✓ | ✗ | ✗ | ✗ |
| Listar materias | ✓ | ✓ | ✓ | ✓ | ✓ |
| Buscar materia por ID | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## CU4 — Gestión de Carreras

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar carrera | ✓ | ✓ | ✗ | ✗ | ✗ |
| Actualizar carrera | ✓ | ✓ | ✗ | ✗ | ✗ |
| Desactivar carrera | ✓ | ✓ | ✗ | ✗ | ✗ |
| Listar carreras (público) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Buscar carrera / ver costos | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## CU5 — Gestión de Malla Curricular

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Agregar materia a nivel de malla | ✓ | ✓ | ✗ | ✗ | ✗ |
| Actualizar materia en malla | ✓ | ✓ | ✗ | ✗ | ✗ |
| Quitar materia de nivel | ✓ | ✓ | ✗ | ✗ | ✗ |
| Listar malla completa | ✓ | ✓ | ✓ | ✓ | ✓ |
| Listar niveles de carrera | ✓ | ✓ | ✓ | ✓ | ✓ |

> Los niveles de la malla se crean automáticamente al registrar la carrera (trigger DB).

---

## CU6 — Inscripciones

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar inscripción (directa, sin QR) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Inscribirse a materia (vía QR PagoFácil) | ✗ | ✗ | ✗ | ✗ | ✓ |
| Activar inscripción sin cobro | ✓ | ✓ | ✓ | ✗ | ✗ |
| Retirar estudiante de inscripción | ✓ | ✓ | ✓ | ✗ | ✓ ¹ |
| Ver inscripciones (de cualquier estudiante) | ✓ | ✓ | ✓ | ✓ | ✗ |
| Ver inscripciones propias | ✓ | ✓ | ✓ | ✓ | ✓ |
| Buscar inscripción por ID | ✓ | ✓ | ✓ | ✓ | ✓ |

> ¹ Estudiante solo puede retirar su propia inscripción.

---

## CU7 — Gestión de Pagos

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar matrícula (directo, sin QR) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Pagar matrícula (QR PagoFácil) | ✗ | ✗ | ✗ | ✗ | ✓ |
| Registrar pago de carrera (directo) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Pagar carrera (QR PagoFácil) | ✗ | ✗ | ✗ | ✗ | ✓ |
| Registrar pago de materia suelta (directo) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Pagar materia (QR PagoFácil) | ✗ | ✗ | ✗ | ✗ | ✓ |
| Pagar cuota de carrera (directo) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Pagar cuota (QR PagoFácil, cuota automática) | ✗ | ✗ | ✗ | ✗ | ✓ |
| Ver pagos (de cualquier estudiante) | ✓ | ✓ | ✓ | ✓ | ✗ |
| Ver pagos propios | ✓ | ✓ | ✓ | ✓ | ✓ |
| Confirmar pago manualmente | ✓ | ✓ | ✓ | ✗ | ✗ |
| Anular pago | ✓ | ✓ | ✗ | ✗ | ✗ |
| Buscar plan de pago por ID | ✓ | ✓ | ✓ | ✓ | ✓ |
| Verificar estado de matrícula (de cualquiera) | ✓ | ✓ | ✓ | ✓ | ✗ |
| Verificar matrícula propia | ✓ | ✓ | ✓ | ✓ | ✓ |

> Los roles con acceso directo (admin) insertan en DB sin QR y con comprobante manual.
> El estudiante siempre genera un QR de PagoFácil que expira en 15 minutos.

---

## CU8 — Gestión de Periodos

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar periodo | ✓ | ✓ | ✗ | ✗ | ✗ |
| Actualizar periodo | ✓ | ✓ | ✗ | ✗ | ✗ |
| Desactivar periodo | ✓ | ✓ | ✗ | ✗ | ✗ |
| Listar periodos | ✓ | ✓ | ✓ | ✓ | ✓ |
| Buscar periodo por ID | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## CU9 — Gestión de Grupos

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar grupo | ✓ | ✓ | ✗ | ✗ | ✗ |
| Actualizar grupo (vacantes) | ✓ | ✓ | ✗ | ✗ | ✗ |
| Desactivar grupo | ✓ | ✓ | ✗ | ✗ | ✗ |
| Listar oferta completa | ✓ | ✓ | ✓ | ✓ | ✓ |
| Listar grupos disponibles para el estudiante | ✓ | ✓ | ✓ | ✓ | ✓ |
| Listar grupos por carrera y periodo | ✓ | ✓ | ✓ | ✓ | ✓ |
| Buscar grupo por ID | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## CU10 — Cronogramas / Ventanas de Inscripción

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar cronograma (global o por carrera) | ✓ | ✓ | ✗ | ✗ | ✗ |
| Listar cronogramas | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ver estado apertura/cierre | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## CU11 — Gestión de Horarios

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar horario | ✓ | ✓ | ✗ | ✗ | ✗ |
| Actualizar horario | ✓ | ✓ | ✗ | ✗ | ✗ |
| Desactivar horario | ✓ | ✓ | ✗ | ✗ | ✗ |
| Listar horarios | ✓ | ✓ | ✓ | ✓ | ✓ |
| Listar horarios por día | ✓ | ✓ | ✓ | ✓ | ✓ |
| Buscar horario por ID | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## CU12 — Evaluaciones y Notas

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Registrar evaluación (nota individual) | ✓ | ✓ | ✓ | ✓ ¹ | ✗ |
| Registrar 4 evaluaciones masivas | ✓ | ✓ | ✓ | ✓ | ✗ |
| Editar nota ya cargada | ✓ | ✓ | ✓ | ✓ | ✗ |
| Ver evaluaciones de una inscripción | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ver padrón completo de un grupo | ✓ | ✓ | ✓ | ✓ | ✗ |

> ¹ El profesor registra solo en materias que le pertenecen (validado en capa de negocio).
> Admins pueden cargar notas en nombre de cualquier profesor.

---

## CU13 — Seguimiento Académico

| Acción | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Ver historial (de cualquier estudiante) | ✓ | ✓ | ✓ | ✓ | ✗ |
| Ver historial propio | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ver resumen con indicadores (de cualquiera) | ✓ | ✓ | ✓ | ✓ | ✗ |
| Ver resumen propio | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ver detalles de evaluaciones | ✓ | ✓ | ✓ | ✓ | ✓ |
| Validar si puede recursar | ✓ | ✓ | ✓ | ✓ | ✗ |
| Registrar abandono de carrera | ✓ | ✓ | ✓ | ✓ | ✗ |
| Eliminar registros de seguimiento | ✓ | ✗ | ✗ | ✗ | ✗ |

> El acceso admin (historial de otro estudiante) queda registrado automáticamente en auditoría.

---

## CU14 — Reportes y Estadísticas

### Reportes Académicos

| Reporte | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Desempeño individual de un estudiante | ✓ | ✓ | ✓ | ✓ ¹ | ✗ ² |
| Tasa de aprobación por materia y periodo | ✓ | ✓ | ✓ | ✓ | ✗ |
| Estudiantes en riesgo por carrera | ✓ | ✓ | ✓ | ✗ | ✗ |
| Progreso de carrera de un estudiante | ✓ | ✓ | ✓ | ✓ | ✗ ³ |
| Ocupación de grupos por periodo | ✓ | ✓ | ✓ | ✓ | ✗ |

### Reportes Financieros

| Reporte | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Ingresos por matrículas (rango fechas) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Ingresos por pagos de materia (rango fechas) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Deudas y cuotas pendientes | ✓ | ✓ | ✓ | ✗ | ✗ |
| Proyección de ingresos (mes, año) | ✓ | ✓ | ✓ | ✗ | ✗ |

### Reportes Administrativos

| Reporte | Propietario | Director | Secretaria | Profesor | Estudiante |
|---|:---:|:---:|:---:|:---:|:---:|
| Inscripciones por carrera | ✓ | ✓ | ✓ | ✗ | ✗ |
| Carga horaria de profesores | ✓ | ✓ | ✓ | ✗ | ✗ |
| Disponibilidad de aulas | ✓ | ✓ | ✓ | ✗ | ✗ |
| **Auditoría del sistema** | ✓ ★ | ✗ | ✗ | ✗ | ✗ |

> ¹ Profesor ve solo estudiantes de sus materias (validado en business layer).
> ² Estudiante bloqueado explícitamente — se le indica usar su propio ID si desea verse.
> ³ Estudiante bloqueado — se le indica usar su propio ID si desea verse.
> ★ Auditoría: exclusivo del propietario, verificación doble en HandleReporte.

---

## Resumen de Exclusividades por Rol

| Acción exclusiva | Rol |
|---|---|
| Registrar otro propietario | Propietario |
| Ver reporte de auditoría del sistema | Propietario |
| Eliminar registros de seguimiento | Propietario |
| Anular pagos | Propietario y Director |
| Desactivar / reactivar usuarios | Propietario y Director |
| Reportes financieros completos | Propietario, Director, Secretaria |
| Reportes administrativos | Propietario, Director, Secretaria |
| Ver reporte de estudiantes en riesgo | Propietario, Director, Secretaria |
| Pagar vía QR PagoFácil | Solo Estudiante |
| Auto-registro sin autenticación | Solo "desconocido" (registrar_estudiante) |
