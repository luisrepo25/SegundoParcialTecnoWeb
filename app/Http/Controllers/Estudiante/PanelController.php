<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PanelController extends Controller
{
    // ── Dashboard (resumen rápido) ───────────────────────────────────────────────
    public function index()
    {
        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();

        if (!$est) {
            return Inertia::render('Dashboard/Estudiante', ['estudiante' => null]);
        }

        $carrera  = $est->id_carrera_actual
            ? DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->first()
            : null;

        $matricula = DB::table('matricula_unica')->where('id_estudiante', $est->id_estudiante)->first();

        $afiliacion = DB::table('afiliaciones_estudiante')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('estado', 'activo')
            ->orderByDesc('fecha_inicio')
            ->first();

        $pagoCarrera = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->whereIn('estado', ['pagado', 'parcial'])
            ->orderByDesc('fecha_contrato')
            ->first();

        $resumen = $this->resumenAcademico($est, $carrera);

        $totalInscripciones = DB::table('inscripciones')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('estado', '!=', 'pendiente_matricula')
            ->count();

        return Inertia::render('Dashboard/Estudiante', [
            'estudiante' => [
                'id_estudiante'   => $est->id_estudiante,
                'legajo'          => $est->legajo,
                'tiene_matricula' => $matricula !== null,
                'matricula'       => $matricula ? [
                    'fecha_pago'   => $matricula->fecha_pago,
                    'monto_pagado' => (float) $matricula->monto_pagado,
                ] : null,
                'carrera' => $carrera ? [
                    'id_carrera' => $carrera->id_carrera,
                    'nombre'     => $carrera->nombre,
                    'tipo'       => $carrera->tipo,
                    'codigo'     => $carrera->codigo,
                ] : null,
            ],
            'afiliacion'  => $afiliacion ? [
                'tipo_programa' => $afiliacion->tipo_programa,
                'fecha_inicio'  => $afiliacion->fecha_inicio,
                'estado'        => $afiliacion->estado,
            ] : null,
            'pagoCarrera' => $pagoCarrera ? [
                'forma_pago'   => $pagoCarrera->forma_pago,
                'monto_total'  => (float) $pagoCarrera->monto_total,
                'monto_pagado' => (float) ($pagoCarrera->monto_pagado ?? 0),
                'estado'       => $pagoCarrera->estado,
            ] : null,
            'materiaEnCurso'            => $resumen['materiaEnCursoInfo'],
            'materiaReprobadaEsperando' => $resumen['materiaReprobadaEsperandoInfo'],
            'proximaMateria'            => $resumen['proximaMateriaInfo'],
            'totalInscripciones'        => $totalInscripciones,
        ]);
    }

    // ── Mis Materias: Plan de Carrera / Oferta del Semestre / Mis Grupos / Inscripciones
    public function materias()
    {
        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();

        if (!$est) {
            return Inertia::render('Estudiante/MisMaterias', [
                'estudiante' => null, 'inscripciones' => [],
                'gruposDisponibles' => [], 'afiliacion' => null, 'pagoCarrera' => null, 'planOpciones' => [],
            ]);
        }

        $carrera  = $est->id_carrera_actual
            ? DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->first()
            : null;

        $matricula = DB::table('matricula_unica')->where('id_estudiante', $est->id_estudiante)->first();

        // Afiliación activa — necesaria para inscribirse
        $afiliacion = DB::table('afiliaciones_estudiante')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('estado', 'activo')
            ->orderByDesc('fecha_inicio')
            ->first();

        // Plan de pago de carrera (creado por trigger al pagar)
        $pagoCarrera = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->whereIn('estado', ['pagado', 'parcial'])
            ->orderByDesc('fecha_contrato')
            ->first();

        // Opciones de plan — valores válidos según constraints del trigger
        $planOpciones = [];
        if ($carrera) {
            $costoTotal    = (float) ($carrera->costo_carrera_completa ?? 0);
            $totalMaterias = max(DB::table('malla_curricular')->where('id_carrera', $est->id_carrera_actual)->count(), 1);

            $planOpciones = [
                'contado' => [
                    'tipo'           => 'contado',
                    'titulo'         => 'Pago Total',
                    'descripcion'    => '20% de descuento. Cubre todas las materias.',
                    'monto_inicial'  => round($costoTotal * 0.80, 2),
                    'monto_original' => $costoTotal,
                    'ahorro'         => round($costoTotal * 0.20, 2),
                    'por_materia'    => 0,
                ],
                'credito' => [
                    'tipo'           => 'credito',
                    'titulo'         => 'Enganche + Materias',
                    'descripcion'    => 'Paga 30% ahora y el resto al inscribirte en cada materia.',
                    'monto_inicial'  => round($costoTotal * 0.30, 2),
                    'monto_original' => $costoTotal,
                    'ahorro'         => 0,
                    'por_materia'    => round($costoTotal * 0.70 / $totalMaterias, 2),
                ],
                'materia' => [
                    'tipo'           => 'materia',
                    'titulo'         => 'Pago por Materia',
                    'descripcion'    => 'Sin enganche. Pagas solo cuando te inscribes en cada materia.',
                    'monto_inicial'  => 0,
                    'monto_original' => $costoTotal,
                    'ahorro'         => 0,
                    'por_materia'    => round($costoTotal / $totalMaterias, 2),
                ],
            ];
        }

        // Mis inscripciones (excluir las que están en pendiente de pago)
        $inscripciones = DB::table('inscripciones as i')
            ->join('grupos as g',           'i.id_oferta',   '=', 'g.id_oferta')
            ->join('materias as m',          'g.id_materia',  '=', 'm.id_materia')
            ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo')
            ->join('horarios as h',          'g.id_horario',  '=', 'h.id_horario')
            ->join('aulas as a',             'g.id_aula',     '=', 'a.id_aula')
            ->join('profesores as p',        'g.id_profesor', '=', 'p.id_profesor')
            ->join('usuarios as u',          'p.id_usuario',  '=', 'u.id_usuario')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.estado', '!=', 'pendiente_matricula')
            ->select(
                'i.id_inscripcion', 'i.estado', 'i.calificacion_final', 'i.aprobado', 'i.fecha_inscripcion',
                'g.id_oferta', 'g.codigo_grupo',
                'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                'pd.nombre as periodo_nombre', 'pd.fecha_inicio as periodo_inicio', 'pd.fecha_fin as periodo_fin',
                'h.dia_semana', 'h.hora_inicio', 'h.hora_fin',
                'a.nombre as aula_nombre',
                DB::raw("u.nombre || ' ' || u.apellido as profesor_nombre"),
                DB::raw("p.archivo_cv /* v2 */ as profesor_cv")
            )
            ->orderBy('pd.fecha_inicio', 'desc')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $gruposInscritos = array_column($inscripciones, 'id_oferta');

        $resumen = $this->resumenAcademico($est, $carrera);
        $cronogramaInscripcionGlobal     = $resumen['cronogramaInscripcionGlobal'];
        $sqlOcupadasGrupo                = $resumen['sqlOcupadasGrupo'];
        $idsPeriodoConInscripcionAbierta = $resumen['idsPeriodoConInscripcionAbierta'];
        $inscripcionesAbiertas           = $resumen['inscripcionesAbiertas'];
        $materiaEnCursoInfo              = $resumen['materiaEnCursoInfo'];
        $materiaReprobadaEsperandoInfo   = $resumen['materiaReprobadaEsperandoInfo'];
        $proximaMateriaInfo              = $resumen['proximaMateriaInfo'];
        $cronogramaInscripcion           = $cronogramaInscripcionGlobal; // para vista (compat)

        // Grupos disponibles — solo si hay ventana de inscripción abierta y una próxima materia
        $gruposDisponibles = [];
        if ($idsPeriodoConInscripcionAbierta->isNotEmpty() && $proximaMateriaInfo) {
            $proximaMateria = $proximaMateriaInfo['id_materia'];
            $query = DB::table('grupos as g')
                ->join('materias as m',          'g.id_materia',  '=', 'm.id_materia')
                ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo')
                ->join('horarios as h',          'g.id_horario',  '=', 'h.id_horario')
                ->join('aulas as a',             'g.id_aula',     '=', 'a.id_aula')
                ->join('profesores as p',        'g.id_profesor', '=', 'p.id_profesor')
                ->join('usuarios as u',          'p.id_usuario',  '=', 'u.id_usuario')
                ->whereIn('g.id_periodo', $idsPeriodoConInscripcionAbierta)
                ->where('g.id_materia', $proximaMateria)
                ->whereRaw('g.activo IS TRUE')
                ->whereRaw("{$sqlOcupadasGrupo} < g.vacantes_max");

            if (!empty($gruposInscritos)) {
                $query->whereNotIn('g.id_oferta', $gruposInscritos);
            }

            $gruposDisponibles = $query
                ->select(
                    'g.id_oferta', 'g.codigo_grupo', 'g.vacantes_max',
                    DB::raw("{$sqlOcupadasGrupo} as vacantes_ocupadas"),
                    'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                    'pd.id_periodo', 'pd.nombre as periodo_nombre',
                    'pd.fecha_inicio as periodo_inicio', 'pd.fecha_fin as periodo_fin',
                    'h.dia_semana', 'h.hora_inicio', 'h.hora_fin',
                    'a.nombre as aula_nombre',
                    DB::raw("u.nombre || ' ' || u.apellido as profesor_nombre"),
                    DB::raw("p.archivo_cv /* v2 */ as profesor_cv")
                )
                ->orderByRaw("CASE h.dia_semana WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2 WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4 WHEN 'viernes' THEN 5 WHEN 'sabado' THEN 6 WHEN 'domingo' THEN 7 ELSE 8 END")
                ->orderBy('h.hora_inicio')
                ->get()
                ->map(fn($r) => (array) $r)
                ->toArray();
        }

        // Maestro de oferta general — todos los grupos de la carrera en el período con inscripción abierta
        // Solo visible cuando las inscripciones están abiertas
        $ofertaGeneral = [];
        if ($carrera && $inscripcionesAbiertas && $idsPeriodoConInscripcionAbierta->isNotEmpty()) {
            $idsPeriodo = $idsPeriodoConInscripcionAbierta;

            $ofertaGeneral = DB::table('grupos as g')
                ->join('materias as m',          'g.id_materia',  '=', 'm.id_materia')
                ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo')
                ->join('horarios as h',          'g.id_horario',  '=', 'h.id_horario')
                ->join('aulas as a',             'g.id_aula',     '=', 'a.id_aula')
                ->join('profesores as p',        'g.id_profesor', '=', 'p.id_profesor')
                ->join('usuarios as u',          'p.id_usuario',  '=', 'u.id_usuario')
                ->leftJoin('malla_curricular as mc', function ($join) use ($est) {
                    $join->on('g.id_materia', '=', 'mc.id_materia')
                         ->where('mc.id_carrera', $est->id_carrera_actual);
                })
                ->leftJoin('niveles_carrera as n', 'mc.id_nivel', '=', 'n.id_nivel')
                ->whereIn('g.id_periodo', $idsPeriodo)
                ->whereRaw('g.activo IS TRUE')
                ->select(
                    'g.id_oferta', 'g.codigo_grupo', 'g.vacantes_max',
                    DB::raw("{$sqlOcupadasGrupo} as vacantes_ocupadas"),
                    'm.id_materia', 'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                    'pd.nombre as periodo_nombre',
                    'h.dia_semana', 'h.hora_inicio', 'h.hora_fin',
                    'a.nombre as aula_nombre',
                    DB::raw("u.nombre || ' ' || u.apellido as profesor_nombre"),
                    DB::raw('p.archivo_cv as profesor_cv'),
                    'n.numero_nivel', 'n.nombre as nivel_nombre',
                    'mc.orden_en_nivel'
                )
                ->orderByRaw('COALESCE(n.numero_nivel, 0)')
                ->orderByRaw('COALESCE(mc.orden_en_nivel, 0)')
                ->orderBy('m.nombre')
                ->orderBy('g.codigo_grupo')
                ->orderByRaw("CASE h.dia_semana WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2 WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4 WHEN 'viernes' THEN 5 WHEN 'sabado' THEN 6 WHEN 'domingo' THEN 7 ELSE 8 END")
                ->get()
                ->map(fn($r) => (array) $r)
                ->toArray();
        }

        return Inertia::render('Estudiante/MisMaterias', [
            'estudiante' => [
                'id_estudiante'   => $est->id_estudiante,
                'legajo'          => $est->legajo,
                'tiene_matricula' => $matricula !== null,
                'matricula'       => $matricula ? [
                    'fecha_pago'   => $matricula->fecha_pago,
                    'monto_pagado' => (float) $matricula->monto_pagado,
                ] : null,
                'carrera' => $carrera ? [
                    'id_carrera'             => $carrera->id_carrera,
                    'nombre'                 => $carrera->nombre,
                    'tipo'                   => $carrera->tipo,
                    'codigo'                 => $carrera->codigo,
                    'costo_carrera_completa' => (float) $carrera->costo_carrera_completa,
                ] : null,
            ],
            'afiliacion'  => $afiliacion ? [
                'tipo_programa' => $afiliacion->tipo_programa,
                'fecha_inicio'  => $afiliacion->fecha_inicio,
                'estado'        => $afiliacion->estado,
            ] : null,
            'pagoCarrera' => $pagoCarrera ? [
                'forma_pago'   => $pagoCarrera->forma_pago,
                'monto_total'  => (float) $pagoCarrera->monto_total,
                'monto_pagado' => (float) ($pagoCarrera->monto_pagado ?? 0),
                'estado'       => $pagoCarrera->estado,
                'fecha_contrato' => $pagoCarrera->fecha_contrato,
            ] : null,
            'planOpciones'           => $planOpciones,
            'inscripciones'          => $inscripciones,
            'gruposDisponibles'      => $gruposDisponibles,
            'ofertaGeneral'          => $ofertaGeneral,
            'proximaMateria'         => $proximaMateriaInfo,
            'materiaEnCurso'         => $materiaEnCursoInfo,
            'materiaReprobadaEsperando' => $materiaReprobadaEsperandoInfo,
            'cronogramaInscripcion'  => $cronogramaInscripcion ? [
                'nombre'      => $cronogramaInscripcion->nombre,
                'fecha_inicio' => $cronogramaInscripcion->fecha_inicio,
                'fecha_fin'    => $cronogramaInscripcion->fecha_fin,
            ] : null,
        ]);
    }

    // ── Resumen académico compartido: materia en curso/reprobada/próxima ────────
    // Usado tanto por el Dashboard (resumen rápido) como por Mis Materias (oferta completa).
    private function resumenAcademico($est, $carrera): array
    {
        // Cronograma de inscripción global (fallback cuando el período no tiene fechas propias)
        $cronogramaInscripcionGlobal = DB::table('cronogramas')
            ->where('tipo_periodo', 'inscripcion')
            ->where('activo', true)
            ->whereRaw('CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin')
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        // Inicio de la convocatoria de inscripción vigente: las vacantes ocupadas de
        // un grupo se cuentan desde esta fecha (no por estado='activo'), porque el
        // cupo de este mes NO debe liberarse solo porque un alumno ya fue calificado
        // (reprobado/aprobado) mientras la inscripción de este mismo mes sigue abierta
        // — otros estudiantes todavía pueden inscribirse en ese cupo. El contador
        // recién vuelve a cero cuando abre la convocatoria del siguiente mes (otra
        // fecha de inicio), no antes.
        $ventanaInscripcionDesde = $cronogramaInscripcionGlobal
            ? now()->parse($cronogramaInscripcionGlobal->fecha_inicio)->toDateString()
            : '1900-01-01';
        $sqlOcupadasGrupo = "(SELECT COUNT(*) FROM inscripciones ii WHERE ii.id_oferta = g.id_oferta AND ii.estado != 'retirado' AND ii.fecha_inscripcion::date >= COALESCE(pd.fecha_inicio_inscripcion, '{$ventanaInscripcionDesde}'::date))";

        // Períodos de la carrera del estudiante con ventana de inscripción abierta.
        // Lógica: si el período tiene fecha_inicio_inscripcion → usa esas fechas propias
        // (la inscripción suele abrir ANTES de que el período de clases empiece, por eso
        // no se filtra aquí por fecha_inicio/fecha_fin del dictado).
        // Si no tiene fechas propias → acepta si el cronograma global de inscripción está
        // activo Y el período de clases está vigente.
        $periodosCarrera = [];
        if ($carrera) {
            $periodosCarrera = DB::table('periodos_dictado as pd')
                ->where('pd.id_carrera', $est->id_carrera_actual)
                ->whereNull('pd.id_nivel')
                ->whereRaw('pd.activo IS TRUE')
                ->select('pd.id_periodo', 'pd.fecha_inicio', 'pd.fecha_fin', 'pd.fecha_inicio_inscripcion', 'pd.fecha_fin_inscripcion')
                ->get();
        }

        // Filtrar períodos con inscripción abierta
        $idsPeriodoConInscripcionAbierta = collect($periodosCarrera)->filter(function ($p) use ($cronogramaInscripcionGlobal) {
            if ($p->fecha_inicio_inscripcion && $p->fecha_fin_inscripcion) {
                // Período tiene fechas propias → usar esas
                return now()->toDateString() >= $p->fecha_inicio_inscripcion
                    && now()->toDateString() <= $p->fecha_fin_inscripcion;
            }
            // Sin fechas propias → depende del cronograma global y de que el período esté vigente
            return $cronogramaInscripcionGlobal !== null
                && now()->toDateString() >= $p->fecha_inicio
                && now()->toDateString() <= $p->fecha_fin;
        })->pluck('id_periodo');

        $inscripcionesAbiertas = $idsPeriodoConInscripcionAbierta->isNotEmpty();

        // Las materias son mensuales y secuenciales: si el estudiante ya tiene una
        // inscripción 'activo' (materia en curso, aún no aprobada), no se le debe
        // ofrecer ningún grupo nuevo hasta que la complete — coincide con el guard
        // de inscribir().
        $materiaEnCurso = DB::table('inscripciones as i')
            ->join('grupos as g2',    'i.id_oferta',  '=', 'g2.id_oferta')
            ->join('materias as m2',  'g2.id_materia', '=', 'm2.id_materia')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.estado', 'activo')
            ->select('m2.id_materia', 'm2.nombre', 'm2.codigo')
            ->first();
        $materiaEnCursoInfo = $materiaEnCurso ? (array) $materiaEnCurso : null;

        // Las materias son modulares (1 mes = 1 materia). El "mes" de una materia
        // NO está delimitado por periodos_dictado.fecha_fin (eso es la ventana larga
        // de dictado de la carrera, puede durar varios meses) sino por el ciclo de
        // inscripción mensual: si el grupo donde reprobó pertenece a un período que
        // SIGUE siendo el que tiene inscripción abierta ahora mismo, sigue dentro de
        // ese mismo mes y debe esperar a que se lance la convocatoria del siguiente
        // mes (nuevo período con inscripción abierta) para reintentarla.
        $materiaReprobadaEsperando = DB::table('inscripciones as i')
            ->join('grupos as g2',   'i.id_oferta',   '=', 'g2.id_oferta')
            ->join('materias as m2', 'g2.id_materia', '=', 'm2.id_materia')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.estado', 'reprobado')
            ->whereIn('g2.id_periodo', $idsPeriodoConInscripcionAbierta)
            ->select('m2.id_materia', 'm2.nombre', 'm2.codigo')
            ->first();

        $materiaReprobadaEsperandoInfo = null;
        if ($materiaReprobadaEsperando) {
            $materiaReprobadaEsperandoInfo = (array) $materiaReprobadaEsperando;

            // Hint informativo: próxima convocatoria de inscripción ya programada (si existe).
            // Solo cronogramas 'mensual' — uno 'semestral'/'anual' no es la convocatoria
            // del siguiente mes, es la ventana larga de otro ciclo y daría una fecha falsa.
            $proximaConvocatoria = DB::table('cronogramas')
                ->where('tipo_periodo', 'inscripcion')
                ->where('modalidad', 'mensual')
                ->where('activo', true)
                ->where('fecha_inicio', '>', now()->toDateString())
                ->orderBy('fecha_inicio')
                ->first();

            if ($proximaConvocatoria) {
                $materiaReprobadaEsperandoInfo['proxima_convocatoria'] = $proximaConvocatoria->fecha_inicio;
            }
        }

        $proximaMateriaInfo = null;
        if ($carrera && $inscripcionesAbiertas && !$materiaEnCurso && !$materiaReprobadaEsperando) {
            // ── Calcular la próxima materia del estudiante en su malla ────────
            $mallaOrdenada = DB::table('malla_curricular as mc')
                ->leftJoin('niveles_carrera as n', 'mc.id_nivel', '=', 'n.id_nivel')
                ->where('mc.id_carrera', $est->id_carrera_actual)
                ->where('mc.obligatoria', true)
                ->orderByRaw('COALESCE(n.numero_nivel, 0)')
                ->orderByRaw('COALESCE(mc.orden_en_nivel, 0)')
                ->pluck('mc.id_materia')
                ->toArray();

            $materiasYaHechas = DB::table('inscripciones as i')
                ->join('grupos as g2', 'i.id_oferta', '=', 'g2.id_oferta')
                ->where('i.id_estudiante', $est->id_estudiante)
                ->where('i.estado', 'aprobado')
                ->pluck('g2.id_materia')
                ->toArray();

            $proximaMateria = null;
            foreach ($mallaOrdenada as $mId) {
                if (!in_array($mId, $materiasYaHechas)) {
                    $proximaMateria = $mId;
                    break;
                }
            }

            if ($proximaMateria) {
                $mat = DB::table('materias')->where('id_materia', $proximaMateria)
                    ->select('id_materia', 'nombre', 'codigo')->first();
                $proximaMateriaInfo = $mat ? (array) $mat : null;
            }
        }

        return [
            'cronogramaInscripcionGlobal'      => $cronogramaInscripcionGlobal,
            'ventanaInscripcionDesde'          => $ventanaInscripcionDesde,
            'sqlOcupadasGrupo'                 => $sqlOcupadasGrupo,
            'idsPeriodoConInscripcionAbierta'  => $idsPeriodoConInscripcionAbierta,
            'inscripcionesAbiertas'            => $inscripcionesAbiertas,
            'materiaEnCursoInfo'               => $materiaEnCursoInfo,
            'materiaReprobadaEsperandoInfo'    => $materiaReprobadaEsperandoInfo,
            'proximaMateriaInfo'               => $proximaMateriaInfo,
        ];
    }

    // ── Mi Malla Académica ──────────────────────────────────────────────────────
    public function malla()
    {
        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();

        if (!$est || !$est->id_carrera_actual) {
            return Inertia::render('Estudiante/Malla', ['carrera' => null, 'niveles' => []]);
        }

        $carrera = DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->first();

        $mallaRows = DB::table('malla_curricular as mc')
            ->leftJoin('niveles_carrera as n', 'mc.id_nivel', '=', 'n.id_nivel')
            ->join('materias as m', 'mc.id_materia', '=', 'm.id_materia')
            ->where('mc.id_carrera', $est->id_carrera_actual)
            ->where('mc.obligatoria', true)
            ->select(
                'm.id_materia', 'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                'n.numero_nivel', 'n.nombre as nivel_nombre'
            )
            ->orderByRaw('COALESCE(n.numero_nivel, 0)')
            ->orderByRaw('COALESCE(mc.orden_en_nivel, 0)')
            ->get();

        // Último intento por materia (más reciente primero → unique() se queda con ese)
        $ultimoIntentoPorMateria = DB::table('inscripciones as i')
            ->join('grupos as g', 'i.id_oferta', '=', 'g.id_oferta')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->whereIn('i.estado', ['activo', 'aprobado', 'reprobado'])
            ->select('g.id_materia', 'i.estado', 'i.calificacion_final', 'i.fecha_inscripcion', 'i.fecha_aprobacion')
            ->orderByDesc('i.fecha_inscripcion')
            ->get()
            ->unique('id_materia')
            ->keyBy('id_materia');

        $niveles = [];
        foreach ($mallaRows as $row) {
            $key = $row->numero_nivel ?? 0;
            if (!isset($niveles[$key])) {
                $niveles[$key] = [
                    'numero_nivel' => $row->numero_nivel,
                    'nivel_nombre' => $row->nivel_nombre ?? ($row->numero_nivel ? "Nivel {$row->numero_nivel}" : 'Módulos'),
                    'materias'     => [],
                ];
            }
            $h = $ultimoIntentoPorMateria->get($row->id_materia);
            $niveles[$key]['materias'][] = [
                'id_materia'         => $row->id_materia,
                'nombre'             => $row->materia_nombre,
                'codigo'             => $row->materia_codigo,
                'estado'             => $h ? $h->estado : 'pendiente', // activo | aprobado | reprobado | pendiente
                'calificacion_final' => $h && $h->calificacion_final !== null ? (float) $h->calificacion_final : null,
                'fecha'              => $h ? ($h->fecha_aprobacion ?? $h->fecha_inscripcion) : null,
            ];
        }

        return Inertia::render('Estudiante/Malla', [
            'carrera' => $carrera ? ['nombre' => $carrera->nombre, 'codigo' => $carrera->codigo] : null,
            'niveles' => array_values($niveles),
        ]);
    }

    // ── Historial de Notas ──────────────────────────────────────────────────────
    public function notas()
    {
        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();

        if (!$est) {
            return Inertia::render('Estudiante/Notas', ['notas' => []]);
        }

        $notas = DB::table('inscripciones as i')
            ->join('grupos as g',           'i.id_oferta',  '=', 'g.id_oferta')
            ->join('materias as m',         'g.id_materia', '=', 'm.id_materia')
            ->join('periodos_dictado as pd','g.id_periodo', '=', 'pd.id_periodo')
            ->leftJoin('malla_curricular as mc', function ($join) use ($est) {
                $join->on('m.id_materia', '=', 'mc.id_materia')
                     ->where('mc.id_carrera', $est->id_carrera_actual);
            })
            ->leftJoin('niveles_carrera as n', 'mc.id_nivel', '=', 'n.id_nivel')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->whereIn('i.estado', ['aprobado', 'reprobado'])
            ->select(
                'i.id_inscripcion', 'i.estado', 'i.calificacion_final', 'i.fecha_aprobacion', 'i.fecha_inscripcion',
                'm.id_materia', 'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                'pd.nombre as periodo_nombre',
                'n.numero_nivel', 'n.nombre as nivel_nombre'
            )
            ->orderByDesc('i.fecha_aprobacion')
            ->orderByDesc('i.fecha_inscripcion')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        return Inertia::render('Estudiante/Notas', ['notas' => $notas]);
    }

    // ── Historial de Pagos ──────────────────────────────────────────────────────
    public function pagos()
    {
        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();

        if (!$est) {
            return Inertia::render('Estudiante/Pagos', ['matricula' => null, 'planCarrera' => null, 'pagosMateria' => []]);
        }

        $matricula = DB::table('matricula_unica')->where('id_estudiante', $est->id_estudiante)->first();

        $planCarrera = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->orderByDesc('fecha_contrato')
            ->first();

        $pagosMateria = DB::table('pago_materia_suelta as pms')
            ->join('inscripciones as i', 'pms.id_inscripcion', '=', 'i.id_inscripcion')
            ->join('grupos as g',        'i.id_oferta',        '=', 'g.id_oferta')
            ->join('materias as m',      'g.id_materia',       '=', 'm.id_materia')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->select(
                'pms.id_pago_materia', 'pms.monto_pagado', 'pms.fecha_pago', 'pms.estado',
                'm.nombre as materia_nombre', 'm.codigo as materia_codigo'
            )
            ->orderByDesc('pms.fecha_pago')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        return Inertia::render('Estudiante/Pagos', [
            'matricula' => $matricula ? [
                'fecha_pago'   => $matricula->fecha_pago,
                'monto_pagado' => (float) $matricula->monto_pagado,
                'estado'       => $matricula->estado,
                'comprobante'  => $matricula->comprobante,
            ] : null,
            'planCarrera' => $planCarrera ? [
                'forma_pago'         => $planCarrera->forma_pago,
                'monto_total'        => (float) $planCarrera->monto_total,
                'monto_pagado'       => (float) ($planCarrera->monto_pagado ?? 0),
                'estado'             => $planCarrera->estado,
                'fecha_contrato'     => $planCarrera->fecha_contrato,
                'materias_cubiertas' => $planCarrera->materias_cubiertas,
            ] : null,
            'pagosMateria' => $pagosMateria,
        ]);
    }

    // ── Elegir plan de pago de carrera ────────────────────────────────────────
    // tipos: 'contado' (20% desc) | 'credito' (30% enganche) | 'materia' (sin enganche)
    public function elegirPlan(Request $request, string $tipo)
    {
        if (!in_array($tipo, ['contado', 'credito', 'materia'])) abort(404);

        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if (!$est || !$est->id_carrera_actual) abort(403);

        if (DB::table('afiliaciones_estudiante')->where('id_estudiante', $est->id_estudiante)->where('estado', 'activo')->exists()) {
            return back()->withErrors(['plan' => 'Ya tienes un plan de carrera activo.']);
        }

        $carrera    = DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->firstOrFail();
        $costoTotal = (float) $carrera->costo_carrera_completa;

        // Expirar cualquier QR de carrera anterior pendiente (permite reintentar sin bloqueo)
        DB::table('pagofacil_transacciones')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('concepto', 'carrera')
            ->where('estado', 'pendiente')
            ->update(['estado' => 'expirado']);

        // Plan POR MATERIA: sin QR, crear afiliación directamente (sin pago_carrera_completa)
        if ($tipo === 'materia') {
            DB::table('afiliaciones_estudiante')->insert([
                'id_estudiante' => $est->id_estudiante,
                'id_carrera'    => $est->id_carrera_actual,
                'tipo_programa' => 'carrera',
                'fecha_inicio'  => now()->toDateString(),
                'estado'        => 'activo',
            ]);
            return redirect()->route('estudiante.materias')->with('success', '¡Plan activado! Ya puedes inscribirte en materias. Pagas cada materia al inscribirte.');
        }

        // CONTADO (≥80% = con 20% descuento) o CRÉDITO (≥30% = enganche)
        // El trigger detecta contado vs credito según monto vs costo*0.80
        $monto = match ($tipo) {
            'contado' => round($costoTotal * 0.80, 2),
            'credito' => round($costoTotal * 0.30, 2),
        };

        $user          = DB::table('usuarios')->where('id_usuario', $userId)->first();
        $paymentNumber = 'CARRERA-' . $est->id_estudiante . '-' . now()->timestamp;

        // Generar QR
        try {
            $pf       = app(PagoFacilService::class);
            $qrResult = $pf->generarQR([
                'clientName'    => $user->nombre . ' ' . $user->apellido,
                'documentId'    => $user->dni ?? '00000000',
                'phoneNumber'   => $user->telefono ?: '70000000',
                'email'         => $user->email,
                'paymentNumber' => $paymentNumber,
                'clientCode'    => (string) $est->id_estudiante,
                'concepto'      => 'Carrera: ' . $carrera->nombre,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['plan' => 'Error al conectar con el sistema de pago: ' . $e->getMessage()]);
        }

        $apiTransId = $qrResult['values']['transactionId'] ?? $qrResult['transactionId'] ?? null;
        $qrRaw      = $qrResult['values']['qrBase64'] ?? $qrResult['values']['qrImage'] ?? null;
        $qrImage    = $qrRaw ? (str_starts_with($qrRaw, 'data:') ? $qrRaw : 'data:image/png;base64,' . $qrRaw) : null;

        if (!$apiTransId) {
            return back()->withErrors(['plan' => 'PagoFácil no devolvió ID de transacción.']);
        }

        // El trigger fn_confirmar_pago_qr (concepto='carrera') crea pago_carrera_completa automáticamente.
        // Solo insertamos en pagofacil_transacciones. codigo_grupo = código de la carrera.
        $transId = DB::table('pagofacil_transacciones')->insertGetId([
            'transaction_id_api' => $apiTransId,
            'payment_number'     => $paymentNumber,
            'id_estudiante'      => $est->id_estudiante,
            'monto'              => $monto,
            'concepto'           => 'carrera',
            'codigo_grupo'       => $carrera->codigo,
            'estado'             => 'pendiente',
        ], 'id_transaccion_pf');

        session(['pf_qr_pc_' . $transId => $qrImage]);

        return redirect()->route('estudiante.pago.carrera', $transId);
    }

    // ── Página de pago del plan de carrera ────────────────────────────────────
    public function pagoCarrera(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')->where('id_transaccion_pf', $transId)->first();
        if (!$trans) abort(404);

        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if (!$est || (int) $trans->id_estudiante !== (int) $est->id_estudiante) abort(403);

        $costoTotal  = (float) DB::table('carreras')->where('codigo', $trans->codigo_grupo)->value('costo_carrera_completa');
        $esContado   = $costoTotal > 0 && $trans->monto >= round($costoTotal * 0.80, 2);
        $planNombre  = $esContado ? 'Pago Total (20% descuento)' : 'Enganche 30% + materias';

        return Inertia::render('Estudiante/PagoCarrera', [
            'transId'    => $transId,
            'qrImage'    => session('pf_qr_pc_' . $transId),
            'estado'     => $trans->estado,
            'planNombre' => $planNombre,
            'monto'      => (float) $trans->monto,
        ]);
    }

    // ── Polling estado del plan de carrera ────────────────────────────────────
    public function estadoPlan(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')->where('id_transaccion_pf', $transId)->first();
        if (!$trans) return response()->json(['estado' => 'error'], 404);

        if ($trans->estado === 'pagado') return response()->json(['estado' => 'pagado']);

        if ($trans->fecha_generacion && now()->diffInMinutes($trans->fecha_generacion) >= 15) {
            DB::table('pagofacil_transacciones')
                ->where('id_transaccion_pf', $transId)->where('estado', 'pendiente')
                ->update(['estado' => 'expirado']);
            return response()->json(['estado' => 'expirado']);
        }

        if ($trans->transaction_id_api) {
            try {
                $result = app(PagoFacilService::class)->consultarTransaccion((int) $trans->transaction_id_api);
                if (PagoFacilService::esPagado($result)) {
                    // El UPDATE dispara el trigger que crea pago_carrera_completa
                    DB::table('pagofacil_transacciones')
                        ->where('id_transaccion_pf', $transId)->where('estado', 'pendiente')
                        ->update(['estado' => 'pagado']);
                    $this->crearAfiliacion($trans);
                    return response()->json(['estado' => 'pagado']);
                }
            } catch (\Throwable) {}
        }

        return response()->json(['estado' => $trans->estado ?? 'pendiente']);
    }

    // ── Inscribirse en un grupo ────────────────────────────────────────────────
    public function inscribir(Request $request, int $idOferta)
    {
        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if (!$est) abort(403);

        // Verificar cronograma de inscripción activo
        $inscripcionAbierta = DB::table('cronogramas')
            ->where('tipo_periodo', 'inscripcion')
            ->where('activo', true)
            ->whereRaw('CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin')
            ->exists();

        if (!$inscripcionAbierta) {
            return back()->withErrors(['general' => 'El período de inscripciones está cerrado. Consulta el cronograma académico.']);
        }

        if (!DB::table('matricula_unica')->where('id_estudiante', $est->id_estudiante)->exists()) {
            return back()->withErrors(['general' => 'Debes pagar la matrícula antes de inscribirte.']);
        }

        if (!DB::table('afiliaciones_estudiante')->where('id_estudiante', $est->id_estudiante)->where('estado', 'activo')->exists()) {
            return back()->withErrors(['general' => 'Debes elegir un plan de pago de carrera antes de inscribirte en materias.']);
        }

        $grupo = DB::table('grupos as g')
            ->join('materias as m',          'g.id_materia', '=', 'm.id_materia')
            ->join('periodos_dictado as pd', 'g.id_periodo', '=', 'pd.id_periodo')
            ->where('g.id_oferta', $idOferta)
            ->whereRaw('g.activo IS TRUE')
            ->select('g.*', 'm.nombre as materia_nombre', 'pd.nombre as periodo_nombre', 'pd.fecha_inicio_inscripcion')
            ->first();

        if (!$grupo) abort(404);

        // Ocupación en vivo desde el inicio de la convocatoria vigente (no por
        // estado='activo'): el cupo de este mes no se libera solo porque un alumno
        // ya fue calificado mientras la inscripción de este mismo mes sigue abierta.
        $cronogramaInscripcionGlobal = DB::table('cronogramas')
            ->where('tipo_periodo', 'inscripcion')
            ->where('activo', true)
            ->whereRaw('CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin')
            ->orderBy('fecha_inicio', 'desc')
            ->first();
        $ventanaInscripcionDesde = $grupo->fecha_inicio_inscripcion
            ?? ($cronogramaInscripcionGlobal ? now()->parse($cronogramaInscripcionGlobal->fecha_inicio)->toDateString() : '1900-01-01');

        $ocupadasActuales = DB::table('inscripciones')
            ->where('id_oferta', $idOferta)
            ->where('estado', '!=', 'retirado')
            ->whereRaw('fecha_inscripcion::date >= ?', [$ventanaInscripcionDesde])
            ->count();

        if (($grupo->vacantes_max - $ocupadasActuales) <= 0) {
            return back()->withErrors(['general' => 'No hay vacantes disponibles en este grupo.']);
        }

        // ── Validar progresión secuencial de malla ────────────────────────────
        // La malla es recursiva: solo puede inscribirse en la PRIMERA materia
        // pendiente de su carrera (orden por nivel → orden_en_nivel).
        if ($est->id_carrera_actual) {
            // Malla ordenada secuencialmente
            $mallaOrdenada = DB::table('malla_curricular as mc')
                ->leftJoin('niveles_carrera as n', 'mc.id_nivel', '=', 'n.id_nivel')
                ->where('mc.id_carrera', $est->id_carrera_actual)
                ->where('mc.obligatoria', true)
                ->orderByRaw('COALESCE(n.numero_nivel, 0)')
                ->orderByRaw('COALESCE(mc.orden_en_nivel, 0)')
                ->pluck('mc.id_materia')
                ->toArray();

            // Materias ya aprobadas o con inscripción activa
            $materiasEnCurso = DB::table('inscripciones as i')
                ->join('grupos as g2', 'i.id_oferta', '=', 'g2.id_oferta')
                ->where('i.id_estudiante', $est->id_estudiante)
                ->whereIn('i.estado', ['activo', 'aprobado'])
                ->pluck('g2.id_materia')
                ->toArray();

            // Primera materia pendiente
            $proximaMateria = null;
            foreach ($mallaOrdenada as $mId) {
                if (!in_array($mId, $materiasEnCurso)) {
                    $proximaMateria = $mId;
                    break;
                }
            }

            if ($proximaMateria !== null && (int) $grupo->id_materia !== (int) $proximaMateria) {
                $nombreProxima = DB::table('materias')->where('id_materia', $proximaMateria)->value('nombre');
                return back()->withErrors([
                    'general' => "La malla es secuencial. Tu próxima materia es: {$nombreProxima}.",
                ]);
            }
        }

        // ── Solo UNA materia activa globalmente (progresión mensual) ─────────
        // Las materias son mensuales y secuenciales. El estudiante debe completar
        // la materia actual antes de inscribirse en la siguiente.
        $materiaEnCurso = DB::table('inscripciones as i')
            ->join('grupos as g2', 'i.id_oferta', '=', 'g2.id_oferta')
            ->join('materias as m2', 'g2.id_materia', '=', 'm2.id_materia')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.estado', 'activo')
            ->where('i.id_oferta', '!=', $idOferta)
            ->select('m2.nombre as materia_nombre')
            ->first();

        if ($materiaEnCurso) {
            return back()->withErrors([
                'general' => "Ya tienes '{$materiaEnCurso->materia_nombre}' en curso. Debes completarla antes de inscribirte en otra materia.",
            ]);
        }

        // Las materias son modulares (1 mes = 1 materia): si reprobó una materia cuyo
        // grupo pertenece a un período que SIGUE teniendo inscripción abierta ahora
        // mismo, sigue dentro de ese mismo mes — debe esperar a la convocatoria del
        // siguiente mes para reintentarla (no se mide por la fecha_fin larga del
        // período de dictado).
        if ($est->id_carrera_actual) {
            $idsPeriodoAbierto = $this->periodosConInscripcionAbierta($est->id_carrera_actual);

            $materiaReprobadaEsperando = DB::table('inscripciones as i')
                ->join('grupos as g2',   'i.id_oferta',   '=', 'g2.id_oferta')
                ->join('materias as m2', 'g2.id_materia', '=', 'm2.id_materia')
                ->where('i.id_estudiante', $est->id_estudiante)
                ->where('i.estado', 'reprobado')
                ->whereIn('g2.id_periodo', $idsPeriodoAbierto)
                ->select('m2.nombre as materia_nombre')
                ->first();

            if ($materiaReprobadaEsperando) {
                return back()->withErrors([
                    'general' => "Reprobaste '{$materiaReprobadaEsperando->materia_nombre}'. Debes esperar a la convocatoria de inscripción del siguiente mes para reintentarla.",
                ]);
            }
        }

        // Caso 1: inscripción en pendiente_matricula pero ya tiene pago_materia_suelta
        // (trigger corrió pero no actualizó el estado — activar manualmente)
        $inscConPagoExistente = DB::table('inscripciones as i')
            ->join('pago_materia_suelta as pms', 'i.id_inscripcion', '=', 'pms.id_inscripcion')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.id_oferta', $idOferta)
            ->where('i.estado', 'pendiente_matricula')
            ->value('i.id_inscripcion');

        if ($inscConPagoExistente) {
            DB::table('inscripciones')
                ->where('id_inscripcion', $inscConPagoExistente)
                ->update(['estado' => 'activo']);
            return redirect()->route('estudiante.materias')
                ->with('success', 'Tu pago ya estaba registrado. ¡Inscripción activada correctamente!');
        }

        // Caso 2: inscripción en pendiente_matricula con QR expirado y sin pago → limpiar
        $idsPendientesExpiradas = DB::table('inscripciones as i')
            ->join('pagofacil_transacciones as t', 'i.id_inscripcion', '=', 't.id_inscripcion')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.id_oferta', $idOferta)
            ->whereIn('i.estado', ['pendiente_matricula', 'pendiente_pago'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('pago_materia_suelta')
                  ->whereColumn('pago_materia_suelta.id_inscripcion', 'i.id_inscripcion');
            })
            ->where(function ($q) {
                $q->where('t.estado', 'expirado')
                  ->orWhere(function ($q2) {
                      $q2->where('t.estado', 'pendiente')
                         ->where('t.fecha_generacion', '<', now()->subMinutes(15));
                  });
            })
            ->pluck('i.id_inscripcion');

        if ($idsPendientesExpiradas->isNotEmpty()) {
            DB::table('pagofacil_transacciones')
                ->whereIn('id_inscripcion', $idsPendientesExpiradas)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'expirado']);
            DB::table('inscripciones')->whereIn('id_inscripcion', $idsPendientesExpiradas)->delete();
        }

        // Bloquear solo si hay inscripción activa o QR genuinamente en curso (<15 min)
        if (DB::table('inscripciones')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('id_oferta', $idOferta)
            ->whereIn('estado', ['pendiente_matricula', 'pendiente_pago', 'activo'])
            ->exists()) {
            return back()->withErrors(['general' => 'Ya tienes una inscripción activa o en proceso para este grupo.']);
        }

        // Verificar si tiene plan CONTADO → inscripción directa sin QR
        $tieneContado = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('forma_pago', 'contado')
            ->where('estado', 'pagado')
            ->exists();

        if ($tieneContado) {
            try {
                DB::table('inscripciones')->insert([
                    'id_estudiante'    => $est->id_estudiante,
                    'id_oferta'        => $idOferta,
                    'estado'           => 'activo',
                    'fecha_inscripcion' => now(),
                ]);
                return redirect()->route('estudiante.materias')->with('success', '¡Inscripción exitosa en ' . $grupo->materia_nombre . '!');
            } catch (\Throwable $e) {
                return back()->withErrors(['general' => 'Error al inscribirse: ' . $e->getMessage()]);
            }
        }

        // Plan CRÉDITO: si quedan materias cubiertas por el adelanto y NO es reintento → inscripción directa
        $pagoCredito = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('forma_pago', 'credito')
            ->whereIn('estado', ['parcial', 'pagado'])
            ->first();

        if ($pagoCredito) {
            $idMateria = DB::table('grupos')->where('id_oferta', $idOferta)->value('id_materia');

            // Si ya cursó esta materia antes (aprobó o reprobó) → siempre paga el reintento
            $esReintento = DB::table('consumo_materias_carrera')
                ->where('id_pago_carrera', $pagoCredito->id_pago_carrera)
                ->where('id_materia', $idMateria)
                ->exists();

            if (!$esReintento) {
                // Materias DISTINTAS ya consumidas (cada materia cuenta una sola vez)
                $materiasDistintasUsadas = DB::table('consumo_materias_carrera')
                    ->where('id_pago_carrera', $pagoCredito->id_pago_carrera)
                    ->distinct()
                    ->count('id_materia');

                if ($materiasDistintasUsadas < (int) $pagoCredito->materias_cubiertas) {
                    // Todavía cubierta por el adelanto → inscripción directa sin QR
                    try {
                        DB::table('inscripciones')->insert([
                            'id_estudiante'    => $est->id_estudiante,
                            'id_oferta'        => $idOferta,
                            'estado'           => 'activo',
                            'fecha_inscripcion' => now(),
                        ]);
                        $restantes = (int) $pagoCredito->materias_cubiertas - $materiasDistintasUsadas - 1;
                        $msg = '¡Inscripción exitosa en ' . $grupo->materia_nombre . '! (cubierta por tu adelanto'
                            . ($restantes > 0 ? ", te quedan $restantes materia(s) sin costo adicional" : '') . ')';
                        return redirect()->route('estudiante.materias')->with('success', $msg);
                    } catch (\Throwable $e) {
                        return back()->withErrors(['general' => 'Error al inscribirse: ' . $e->getMessage()]);
                    }
                }
            }
        }

        // Plan CRÉDITO (slots agotados o reintento) o MATERIA → requiere QR por materia
        $carrera    = DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->first();
        $costoTotal = (float) ($carrera?->costo_carrera_completa ?? 0);
        $totalMaterias = max(DB::table('malla_curricular')->where('id_carrera', $est->id_carrera_actual)->count(), 1);

        // Para crédito: cobrar el restante distribuido; para materia: precio estándar por materia
        $pagoC = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->whereIn('estado', ['parcial', 'pagado'])
            ->first();

        $montoMateria = $pagoC && $pagoC->forma_pago === 'credito'
            ? round(((float) $pagoC->monto_total - (float) $pagoC->monto_pagado) / $totalMaterias, 2)
            : round($costoTotal / $totalMaterias, 2);

        $montoMateria = max($montoMateria, 0.01);

        $user          = DB::table('usuarios')->where('id_usuario', $userId)->first();
        $paymentNumber = 'MATERIA-' . $est->id_estudiante . '-' . now()->timestamp;

        // Pre-crear inscripción en estado pendiente_matricula
        // El trigger actualiza a 'activo' al confirmar pago (concepto='materia')
        $idInscripcion = DB::table('inscripciones')->insertGetId([
            'id_estudiante'    => $est->id_estudiante,
            'id_oferta'        => $idOferta,
            'estado'           => 'pendiente_matricula',
            'fecha_inscripcion' => now(),
        ], 'id_inscripcion');

        // Generar QR
        try {
            $pf       = app(PagoFacilService::class);
            $qrResult = $pf->generarQR([
                'clientName'    => $user->nombre . ' ' . $user->apellido,
                'documentId'    => $user->dni ?? '00000000',
                'phoneNumber'   => $user->telefono ?: '70000000',
                'email'         => $user->email,
                'paymentNumber' => $paymentNumber,
                'clientCode'    => (string) $est->id_estudiante,
                'concepto'      => 'Materia: ' . $grupo->materia_nombre,
            ]);
        } catch (\Throwable $e) {
            DB::table('inscripciones')->where('id_inscripcion', $idInscripcion)->delete();
            return back()->withErrors(['general' => 'Error al conectar con el sistema de pago: ' . $e->getMessage()]);
        }

        $apiTransId = $qrResult['values']['transactionId'] ?? $qrResult['transactionId'] ?? null;
        $qrRaw      = $qrResult['values']['qrBase64'] ?? $qrResult['values']['qrImage'] ?? null;
        $qrImage    = $qrRaw ? (str_starts_with($qrRaw, 'data:') ? $qrRaw : 'data:image/png;base64,' . $qrRaw) : null;

        if (!$apiTransId) {
            DB::table('inscripciones')->where('id_inscripcion', $idInscripcion)->delete();
            return back()->withErrors(['general' => 'PagoFácil no devolvió ID de transacción.']);
        }

        // concepto='materia', codigo_grupo=código del grupo, id_inscripcion=FK
        // El trigger fn_confirmar_pago_qr activa la inscripción y crea pago_materia_suelta automáticamente
        $transId = DB::table('pagofacil_transacciones')->insertGetId([
            'transaction_id_api' => $apiTransId,
            'payment_number'     => $paymentNumber,
            'id_estudiante'      => $est->id_estudiante,
            'monto'              => $montoMateria,
            'concepto'           => 'materia',
            'codigo_grupo'       => $grupo->codigo_grupo,
            'id_inscripcion'     => $idInscripcion,
            'estado'             => 'pendiente',
        ], 'id_transaccion_pf');

        session(['pf_qr_ins_' . $transId => $qrImage]);

        return redirect()->route('estudiante.pago', $transId);
    }

    // ── Página de pago de materia ─────────────────────────────────────────────
    public function pagoInscripcion(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')->where('id_transaccion_pf', $transId)->first();
        if (!$trans) abort(404);

        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if (!$est || (int) $trans->id_estudiante !== (int) $est->id_estudiante) abort(403);

        $concepto = '';
        if ($trans->id_inscripcion) {
            $row = DB::table('inscripciones as i')
                ->join('grupos as g',           'i.id_oferta',   '=', 'g.id_oferta')
                ->join('materias as m',          'g.id_materia',  '=', 'm.id_materia')
                ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo')
                ->where('i.id_inscripcion', $trans->id_inscripcion)
                ->select('m.nombre as materia_nombre', 'pd.nombre as periodo_nombre')
                ->first();
            if ($row) $concepto = $row->materia_nombre . ' — ' . $row->periodo_nombre;
        }

        return Inertia::render('Estudiante/PagoInscripcion', [
            'transId'  => $transId,
            'qrImage'  => session('pf_qr_ins_' . $transId),
            'estado'   => $trans->estado,
            'concepto' => $concepto,
            'monto'    => (float) $trans->monto,
        ]);
    }

    // ── Polling estado de pago de materia ─────────────────────────────────────
    public function estadoInscripcion(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')->where('id_transaccion_pf', $transId)->first();
        if (!$trans) return response()->json(['estado' => 'error'], 404);

        if ($trans->estado === 'pagado') return response()->json(['estado' => 'pagado']);

        if ($trans->fecha_generacion && now()->diffInMinutes($trans->fecha_generacion) >= 15) {
            DB::table('pagofacil_transacciones')
                ->where('id_transaccion_pf', $transId)->where('estado', 'pendiente')
                ->update(['estado' => 'expirado']);
            // Limpiar inscripción pendiente
            if ($trans->id_inscripcion) {
                DB::table('inscripciones')
                    ->where('id_inscripcion', $trans->id_inscripcion)
                    ->where('estado', 'pendiente_matricula')
                    ->delete();
            }
            return response()->json(['estado' => 'expirado']);
        }

        if ($trans->transaction_id_api) {
            try {
                $result = app(PagoFacilService::class)->consultarTransaccion((int) $trans->transaction_id_api);
                if (PagoFacilService::esPagado($result)) {
                    // UPDATE dispara trigger fn_confirmar_pago_qr (concepto='materia')
                    // El trigger activa la inscripción y crea pago_materia_suelta automáticamente
                    DB::table('pagofacil_transacciones')
                        ->where('id_transaccion_pf', $transId)->where('estado', 'pendiente')
                        ->update(['estado' => 'pagado']);
                    return response()->json(['estado' => 'pagado']);
                }
            } catch (\Throwable) {}
        }

        return response()->json(['estado' => $trans->estado ?? 'pendiente']);
    }

    // ── Helper: crear afiliación tras confirmar pago de carrera ───────────────
    private function crearAfiliacion(object $trans): void
    {
        if (DB::table('afiliaciones_estudiante')->where('id_estudiante', $trans->id_estudiante)->where('estado', 'activo')->exists()) {
            return;
        }
        // codigo_grupo contiene el código de la carrera
        $idCarrera = DB::table('carreras')->where('codigo', $trans->codigo_grupo)->value('id_carrera');
        if (!$idCarrera) return;

        DB::table('afiliaciones_estudiante')->insert([
            'id_estudiante' => $trans->id_estudiante,
            'id_carrera'    => $idCarrera,
            'tipo_programa' => 'carrera',
            'fecha_inicio'  => now()->toDateString(),
            'estado'        => 'activo',
        ]);
    }

    // ── Helper: períodos de dictado de una carrera con ventana de inscripción
    // abierta ahora mismo. Misma lógica usada en index() — usa fechas propias del
    // período si las tiene, o cae al cronograma global de inscripción.
    private function periodosConInscripcionAbierta(int $idCarrera): \Illuminate\Support\Collection
    {
        $cronogramaGlobal = DB::table('cronogramas')
            ->where('tipo_periodo', 'inscripcion')
            ->where('activo', true)
            ->whereRaw('CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin')
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        $periodos = DB::table('periodos_dictado as pd')
            ->where('pd.id_carrera', $idCarrera)
            ->whereNull('pd.id_nivel')
            ->whereRaw('pd.activo IS TRUE')
            ->select('pd.id_periodo', 'pd.fecha_inicio', 'pd.fecha_fin', 'pd.fecha_inicio_inscripcion', 'pd.fecha_fin_inscripcion')
            ->get();

        return collect($periodos)->filter(function ($p) use ($cronogramaGlobal) {
            if ($p->fecha_inicio_inscripcion && $p->fecha_fin_inscripcion) {
                return now()->toDateString() >= $p->fecha_inicio_inscripcion
                    && now()->toDateString() <= $p->fecha_fin_inscripcion;
            }
            return $cronogramaGlobal !== null
                && now()->toDateString() >= $p->fecha_inicio
                && now()->toDateString() <= $p->fecha_fin;
        })->pluck('id_periodo');
    }
}
