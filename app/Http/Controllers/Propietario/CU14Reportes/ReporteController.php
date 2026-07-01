<?php

namespace App\Http\Controllers\Propietario\CU14Reportes;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $filtros = [
            'activo_usuarios' => $request->input('activo_usuarios', 'todos'),
            'activo_aulas'    => $request->input('activo_aulas',    'todos'),
            'activo_horarios' => $request->input('activo_horarios', 'todos'),
            'nombre_periodo'  => $request->input('nombre_periodo')  ?: null,
            'id_carrera'      => $request->input('id_carrera') ? (int) $request->input('id_carrera') : null,
        ];

        // Períodos con su carrera (para filtrado vinculado en frontend).
        // Se incluye id_carrera para que el selector de período se filtre
        // dinámicamente cuando el usuario elige una carrera específica.
        $periodos = DB::table('periodos_dictado')
            ->select('nombre', 'id_carrera', DB::raw('MAX(fecha_inicio) as max_fecha'))
            ->groupBy('nombre', 'id_carrera')
            ->orderByRaw('MAX(fecha_inicio) DESC NULLS LAST')
            ->get();

        // Carreras activas para el selector
        $carreras = DB::table('carreras')
            ->whereRaw('activo IS TRUE')
            ->orderBy('nombre')
            ->get(['id_carrera', 'nombre']);

        $esPropietario = Auth::user()?->id_rol === 1;

        // ── ADMINISTRATIVO ────────────────────────────────────────────────────

        $rolNames = [1 => 'Propietario', 2 => 'Director', 3 => 'Secretaria', 4 => 'Profesor', 5 => 'Estudiante'];

        $qUsuarios = Usuario::select('id_rol', DB::raw('count(*) as total'))->groupBy('id_rol')->orderBy('id_rol');
        if ($filtros['activo_usuarios'] === '1') $qUsuarios->whereRaw('activo IS TRUE');
        if ($filtros['activo_usuarios'] === '0') $qUsuarios->whereRaw('activo IS FALSE');
        $usuariosPorRol = $qUsuarios->get()->map(fn($r) => [
            'label' => $rolNames[$r->id_rol] ?? 'Desconocido',
            'valor' => (int) $r->total,
        ])->values();

        $qAulas = Aula::select('tipo', DB::raw('count(*) as total'))->groupBy('tipo');
        if ($filtros['activo_aulas'] === '1') $qAulas->whereRaw('activo IS TRUE');
        if ($filtros['activo_aulas'] === '0') $qAulas->whereRaw('activo IS FALSE');
        $aulasPorTipo = $qAulas->get()->map(fn($r) => [
            'label' => ucfirst($r->tipo),
            'valor' => (int) $r->total,
        ])->values();

        $diasOrden = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $diasLabel = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'Sábado','domingo'=>'Domingo'];
        $qHorarios = Horario::select('dia_semana', DB::raw('count(*) as total'))->groupBy('dia_semana');
        if ($filtros['activo_horarios'] === '1') $qHorarios->whereRaw('activo IS TRUE');
        if ($filtros['activo_horarios'] === '0') $qHorarios->whereRaw('activo IS FALSE');
        $horarioRaw = $qHorarios->get()->keyBy('dia_semana');
        $horariosPorDia = collect($diasOrden)->map(fn($dia) => [
            'label' => $diasLabel[$dia],
            'valor' => (int) ($horarioRaw->get($dia)?->total ?? 0),
        ])->values();

        // IDs de períodos que coinciden con el nombre seleccionado (usado en 4 queries)
        $idsPeriodoFiltro = $filtros['nombre_periodo']
            ? DB::table('periodos_dictado')
                ->where('nombre', $filtros['nombre_periodo'])
                ->when($filtros['id_carrera'], fn($q) => $q->where('id_carrera', $filtros['id_carrera']))
                ->pluck('id_periodo')
            : null;

        // Inscripciones por carrera (filtrable por período y carrera)
        $qInscCarrera = DB::table('inscripciones as i')
            ->join('grupos as g',      'i.id_oferta',         '=', 'g.id_oferta')
            ->join('estudiantes as e', 'i.id_estudiante',     '=', 'e.id_estudiante')
            ->leftJoin('carreras as c','e.id_carrera_actual', '=', 'c.id_carrera');
        if ($idsPeriodoFiltro)      $qInscCarrera->whereIn('g.id_periodo', $idsPeriodoFiltro);
        if ($filtros['id_carrera']) $qInscCarrera->where('c.id_carrera', $filtros['id_carrera']);
        $inscripcionesPorCarrera = $qInscCarrera
            ->selectRaw("COALESCE(c.nombre, 'Sin carrera') as label, COUNT(*) as valor")
            ->groupBy('c.nombre')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(8)
            ->get()
            ->map(fn($r) => ['label' => $r->label, 'valor' => (int) $r->valor])
            ->values();

        // Carga horaria de profesores (filtrable por período y carrera)
        $qCarga = DB::table('grupos as g')
            ->join('profesores as p',       'g.id_profesor', '=', 'p.id_profesor')
            ->join('usuarios as u',         'p.id_usuario',  '=', 'u.id_usuario')
            ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo');
        if ($idsPeriodoFiltro) {
            $qCarga->whereIn('g.id_periodo', $idsPeriodoFiltro);
        } else {
            $qCarga->whereRaw('g.activo IS TRUE');
        }
        if ($filtros['id_carrera']) $qCarga->where('pd.id_carrera', $filtros['id_carrera']);
        $cargaHoraria = $qCarga
            ->selectRaw("u.nombre || ' ' || u.apellido as label, COUNT(g.id_oferta) as valor")
            ->groupBy('u.id_usuario', 'u.nombre', 'u.apellido')
            ->orderByRaw('COUNT(g.id_oferta) DESC')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['label' => $r->label, 'valor' => (int) $r->valor])
            ->values();

        // Disponibilidad de aulas (grupos activos por aula)
        $disponibilidadAulas = DB::table('aulas as a')
            ->leftJoin('grupos as g', function ($j) {
                $j->on('a.id_aula', '=', 'g.id_aula')->whereRaw('g.activo IS TRUE');
            })
            ->whereRaw('a.activo IS TRUE')
            ->selectRaw('a.nombre as label, a.capacidad, COUNT(g.id_oferta) as grupos_asignados')
            ->groupBy('a.id_aula', 'a.nombre', 'a.capacidad')
            ->orderByRaw('COUNT(g.id_oferta) DESC')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'label'            => $r->label,
                'capacidad'        => (int) $r->capacidad,
                'grupos_asignados' => (int) $r->grupos_asignados,
            ])
            ->values();


        // ── ACADÉMICO ─────────────────────────────────────────────────────────

        // Tasa de aprobación por materia — filtrable por período y carrera
        $qTasa = DB::table('inscripciones as i')
            ->join('grupos as g',      'i.id_oferta',      '=', 'g.id_oferta')
            ->join('materias as m',    'g.id_materia',     '=', 'm.id_materia')
            ->join('estudiantes as est','i.id_estudiante', '=', 'est.id_estudiante')
            ->whereNotNull('i.calificacion_final');
        if ($idsPeriodoFiltro)      $qTasa->whereIn('g.id_periodo', $idsPeriodoFiltro);
        if ($filtros['id_carrera']) $qTasa->where('est.id_carrera_actual', $filtros['id_carrera']);
        $tasaAprobacion = $qTasa
            ->selectRaw("m.nombre as label, COUNT(*) as total, SUM(CASE WHEN i.aprobado IS TRUE THEN 1 ELSE 0 END) as aprobados")
            ->groupBy('m.id_materia', 'm.nombre')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(8)
            ->get()
            ->map(fn($r) => [
                'label'     => $r->label,
                'total'     => (int) $r->total,
                'aprobados' => (int) $r->aprobados,
                'tasa'      => (int)$r->total > 0 ? round((int)$r->aprobados / (int)$r->total * 100, 1) : 0,
            ])
            ->values();

        // Estudiantes en riesgo — filtrable por período y carrera
        $qRiesgo = DB::table('inscripciones as i')
            ->join('grupos as g',        'i.id_oferta',         '=', 'g.id_oferta')
            ->join('estudiantes as e',   'i.id_estudiante',     '=', 'e.id_estudiante')
            ->leftJoin('carreras as c',  'e.id_carrera_actual', '=', 'c.id_carrera')
            ->where('i.estado', '!=', 'abandonado')
            ->where(function ($q) {
                $q->whereRaw('i.calificacion_final < 51')->orWhereNull('i.calificacion_final');
            });
        if ($idsPeriodoFiltro)      $qRiesgo->whereIn('g.id_periodo', $idsPeriodoFiltro);
        if ($filtros['id_carrera']) $qRiesgo->where('e.id_carrera_actual', $filtros['id_carrera']);
        $estudiantesEnRiesgo = $qRiesgo
            ->selectRaw("COALESCE(c.nombre, 'Sin carrera') as label, COUNT(DISTINCT i.id_estudiante) as valor")
            ->groupBy('c.nombre')
            ->orderByRaw('COUNT(DISTINCT i.id_estudiante) DESC')
            ->get()
            ->map(fn($r) => ['label' => $r->label, 'valor' => (int) $r->valor])
            ->values();

        // Ocupación de grupos por periodo (últimos 6). Las vacantes ocupadas se
        // cuentan desde el inicio de la convocatoria de inscripción vigente (no por
        // estado='activo' — ver PanelController): el cupo de este mes no se libera
        // solo porque un alumno ya fue calificado mientras la inscripción sigue abierta.
        $cronogramaInscripcionGlobal = DB::table('cronogramas')
            ->where('tipo_periodo', 'inscripcion')
            ->where('activo', true)
            ->whereRaw('CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin')
            ->orderBy('fecha_inicio', 'desc')
            ->first();
        $ventanaInscripcionDesde = $cronogramaInscripcionGlobal
            ? now()->parse($cronogramaInscripcionGlobal->fecha_inicio)->toDateString()
            : '1900-01-01';

        $ocupacionGrupos = DB::table('grupos as g')
            ->join('periodos_dictado as p', 'g.id_periodo', '=', 'p.id_periodo')
            ->selectRaw("p.nombre as label, SUM(g.vacantes_max) as capacidad, SUM((SELECT COUNT(*) FROM inscripciones ii WHERE ii.id_oferta = g.id_oferta AND ii.estado != 'retirado' AND (ii.estado IN ('activo','pendiente_matricula') OR ii.fecha_inscripcion::date >= COALESCE(p.fecha_inicio_inscripcion, '{$ventanaInscripcionDesde}'::date)))) as ocupadas")
            ->groupBy('p.id_periodo', 'p.nombre', 'p.fecha_inicio')
            ->orderBy('p.fecha_inicio', 'desc')
            ->limit(6)
            ->get()
            ->map(fn($r) => [
                'label'    => $r->label,
                'capacidad'=> (int) $r->capacidad,
                'ocupadas' => (int) $r->ocupadas,
            ])
            ->reverse()
            ->values();

        // ── FINANCIERO ────────────────────────────────────────────────────────

        // Ingresos por matrículas — últimos 12 meses (via pagofacil_transacciones)
        $ingresosMatriculas = DB::table('pagofacil_transacciones')
            ->where('concepto', 'matricula')
            ->where('estado', 'pagado')
            ->whereRaw("fecha_generacion >= CURRENT_DATE - INTERVAL '11 months'")
            ->selectRaw("TO_CHAR(fecha_generacion, 'YYYY-MM') as mes, SUM(monto) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->map(fn($r) => ['label' => $r->mes, 'valor' => (float) $r->total])
            ->values();

        // Ingresos por materias sueltas — últimos 12 meses (via pagofacil_transacciones)
        $ingresosMaterias = DB::table('pagofacil_transacciones')
            ->where('concepto', 'materia')
            ->where('estado', 'pagado')
            ->whereRaw("fecha_generacion >= CURRENT_DATE - INTERVAL '11 months'")
            ->selectRaw("TO_CHAR(fecha_generacion, 'YYYY-MM') as mes, SUM(monto) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->map(fn($r) => ['label' => $r->mes, 'valor' => (float) $r->total])
            ->values();

        // Cuotas pendientes (stats)
        $statCuotas = DB::table('cuotas_carrera')
            ->where('estado', 'pendiente')
            ->selectRaw('COUNT(*) as total_cuotas, COALESCE(SUM(monto), 0) as total_deuda, MIN(fecha_vencimiento) as proxima')
            ->first();

        $cuotasPendientes = [
            'total_cuotas' => (int) ($statCuotas->total_cuotas ?? 0),
            'total_deuda'  => (float) ($statCuotas->total_deuda ?? 0),
            'proxima'      => $statCuotas->proxima ?? null,
        ];

        // Proyección de cobros futuros (cuotas pendientes por mes, próximos 6)
        $proyeccion = DB::table('cuotas_carrera')
            ->where('estado', 'pendiente')
            ->whereRaw('fecha_vencimiento >= CURRENT_DATE')
            ->selectRaw("TO_CHAR(fecha_vencimiento, 'YYYY-MM') as mes, SUM(monto) as proyectado")
            ->groupBy('mes')
            ->orderBy('mes')
            ->limit(6)
            ->get()
            ->map(fn($r) => ['label' => $r->mes, 'valor' => (float) $r->proyectado])
            ->values();

        return Inertia::render('Propietario/CU14Reportes/Index', [
            'esPropietario' => $esPropietario,
            'filtros'       => $filtros,
            'periodos'      => $periodos,
            'carreras'      => $carreras,
            'administrativo' => [
                'usuariosPorRol'          => $usuariosPorRol,
                'aulasPorTipo'            => $aulasPorTipo,
                'aulasActivas'            => Aula::whereRaw('activo IS TRUE')->count(),
                'aulasInactivas'          => Aula::whereRaw('activo IS FALSE')->count(),
                'inscripcionesPorCarrera' => $inscripcionesPorCarrera,
                'cargaHoraria'            => $cargaHoraria,
                'disponibilidadAulas'     => $disponibilidadAulas,
            ],
            'academico' => [
                'carrerasActivas'    => Carrera::whereRaw('activo IS TRUE')->count(),
                'carrerasInactivas'  => Carrera::whereRaw('activo IS FALSE')->count(),
                'materiasActivas'    => Materia::whereRaw('activo IS TRUE')->count(),
                'materiasInactivas'  => Materia::whereRaw('activo IS FALSE')->count(),
                'horariosPorDia'     => $horariosPorDia,
                'tasaAprobacion'     => $tasaAprobacion,
                'estudiantesEnRiesgo'=> $estudiantesEnRiesgo,
                'ocupacionGrupos'    => $ocupacionGrupos,
            ],
            'financiero' => [
                'ingresosMatriculas' => $ingresosMatriculas,
                'ingresosMaterias'   => $ingresosMaterias,
                'cuotasPendientes'   => $cuotasPendientes,
                'proyeccion'         => $proyeccion,
            ],
        ]);
    }
}
