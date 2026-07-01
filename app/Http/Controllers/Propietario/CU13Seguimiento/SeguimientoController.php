<?php

namespace App\Http\Controllers\Propietario\CU13Seguimiento;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Usuario;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SeguimientoController extends Controller
{
    // ── CU13.1 — Listado de estudiantes con búsqueda ──────────────────────────
    public function index(Request $request)
    {
        $buscar = $request->get('buscar', '');

        $query = Usuario::where('id_rol', 5)
            ->with('estudiante')
            ->orderBy('apellido')
            ->orderBy('nombre');

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre',   'ilike', "%{$buscar}%")
                  ->orWhere('apellido', 'ilike', "%{$buscar}%")
                  ->orWhere('dni',      'ilike', "%{$buscar}%")
                  ->orWhere('email',    'ilike', "%{$buscar}%");
            });
        }

        $estudiantes = $query->get()->map(function ($u) {
            $carrera = null;
            if ($u->estudiante?->id_carrera_actual) {
                $c = Carrera::find($u->estudiante->id_carrera_actual);
                $carrera = $c ? ['id' => $c->id_carrera, 'nombre' => $c->nombre] : null;
            }

            return [
                'id_usuario' => $u->id_usuario,
                'nombre'     => $u->nombre,
                'apellido'   => $u->apellido,
                'email'      => $u->email,
                'dni'        => $u->dni,
                'activo'     => $u->activo,
                'legajo'     => $u->estudiante?->legajo,
                'carrera'    => $carrera,
            ];
        });

        return Inertia::render('Propietario/CU13Seguimiento/Index', [
            'estudiantes' => $estudiantes,
            'filtros'     => ['buscar' => $buscar],
        ]);
    }

    // ── CU13.2 — Historial académico completo de un estudiante ────────────────
    public function show(int $id)
    {
        $usuario    = Usuario::where('id_usuario', $id)->where('id_rol', 5)->firstOrFail();
        $estudiante = Estudiante::where('id_usuario', $id)->first();

        $carrera = null;
        if ($estudiante?->id_carrera_actual) {
            $c = Carrera::find($estudiante->id_carrera_actual);
            $carrera = $c ? [
                'id'               => $c->id_carrera,
                'nombre'           => $c->nombre,
                'duracion_niveles' => $c->duracion_niveles,
            ] : null;
        }

        $historial = collect();
        $resumen   = [
            'total_materias_cursadas' => 0,
            'materias_aprobadas'      => 0,
            'materias_reprobadas'     => 0,
            'promedio_general'        => null,
            'tasa_aprobacion'         => null,
            'progreso_carrera'        => null,
        ];

        if ($estudiante) {
            $historial = DB::table('inscripciones as i')
                ->join('grupos as g',           'i.id_oferta',   '=', 'g.id_oferta')
                ->join('materias as m',          'g.id_materia',  '=', 'm.id_materia')
                ->join('periodos_dictado as p',  'g.id_periodo',  '=', 'p.id_periodo')
                ->where('i.id_estudiante', $estudiante->id_estudiante)
                ->select(
                    'i.id_inscripcion', 'i.estado', 'i.fecha_inscripcion',
                    'i.calificacion_final', 'i.aprobado', 'i.observaciones',
                    'm.nombre as materia', 'm.codigo as materia_codigo', 'm.id_materia',
                    'p.nombre as periodo', 'p.tipo_periodo', 'p.fecha_inicio', 'p.fecha_fin',
                    'g.id_oferta', 'g.codigo_grupo'
                )
                ->orderByDesc('p.fecha_inicio')
                ->get();

            // Cargar evaluaciones agrupadas por inscripción
            $idInscripciones     = $historial->pluck('id_inscripcion');
            $evalsPorInscripcion = DB::table('evaluaciones')
                ->whereIn('id_inscripcion', $idInscripciones)
                ->orderByRaw("CASE tipo WHEN 'parcial1' THEN 1 WHEN 'parcial2' THEN 2 WHEN 'final' THEN 3 ELSE 4 END")
                ->get()
                ->groupBy('id_inscripcion');

            $historial = $historial->map(function ($ins) use ($evalsPorInscripcion) {
                $arr = (array) $ins;
                $arr['aprobado']      = (bool) ($ins->aprobado ?? false);
                $arr['evaluaciones']  = array_values(
                    $evalsPorInscripcion->get($ins->id_inscripcion)?->toArray() ?? []
                );
                return $arr;
            });

            // Calcular indicadores
            $finalizadas = $historial->filter(fn($i) => $i['calificacion_final'] !== null);
            $aprobadas   = $historial->filter(fn($i) => $i['aprobado'] === true)->count();
            $reprobadas  = $finalizadas->filter(fn($i) => !$i['aprobado'])->count();
            $notas       = $finalizadas->pluck('calificacion_final')->filter();

            $totalMalla = $carrera
                ? DB::table('malla_curricular')->where('id_carrera', $carrera['id'])->count()
                : 0;

            $resumen = [
                'total_materias_cursadas' => $historial->count(),
                'materias_aprobadas'      => $aprobadas,
                'materias_reprobadas'     => $reprobadas,
                'promedio_general'        => $notas->count() ? round($notas->avg(), 2) : null,
                'tasa_aprobacion'         => $finalizadas->count() > 0
                    ? round($aprobadas / $finalizadas->count() * 100, 1)
                    : null,
                'progreso_carrera'        => ($totalMalla > 0 && $aprobadas > 0)
                    ? round($aprobadas / $totalMalla * 100, 1)
                    : null,
            ];
        }

        return Inertia::render('Propietario/CU13Seguimiento/Show', [
            'estudiante' => [
                'id_usuario'     => $usuario->id_usuario,
                'nombre'         => $usuario->nombre,
                'apellido'       => $usuario->apellido,
                'email'          => $usuario->email,
                'dni'            => $usuario->dni,
                'telefono'       => $usuario->telefono,
                'activo'         => $usuario->activo,
                'legajo'         => $estudiante?->legajo,
                'tutor_nombre'   => $estudiante?->tutor_nombre,
                'tutor_telefono' => $estudiante?->tutor_telefono,
                'observaciones'  => $estudiante?->observaciones,
                'fecha_inicio'   => $estudiante?->fecha_inscripcion_inicial,
            ],
            'carrera'  => $carrera,
            'historial' => $historial->values()->toArray(),
            'resumen'   => $resumen,
        ]);
    }

    // ── CU13.3 — Registrar abandono de carrera ────────────────────────────────
    public function registrarAbandono(Request $request, int $id)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $estudiante = Estudiante::where('id_usuario', $id)->firstOrFail();

        $nota = '[ABANDONO ' . now()->format('d/m/Y') . '] ' . $request->motivo;
        $prev = $estudiante->observaciones ? $estudiante->observaciones . "\n" : '';
        $estudiante->update(['observaciones' => $prev . $nota]);

        BitacoraService::registrar('abandono_carrera', "Abandono registrado para estudiante #{$estudiante->id_estudiante}. Motivo: {$request->motivo}");
        return back()->with('success', 'Abandono de carrera registrado.');
    }

    // ── CU13.4 — Validar si puede recursar una materia ───────────────────────
    public function validarRecurso(int $idUsuario, int $idMateria)
    {
        $estudiante = Estudiante::where('id_usuario', $idUsuario)->first();

        if (!$estudiante) {
            return response()->json(['puede_recursar' => false, 'mensaje' => 'Estudiante no encontrado.']);
        }

        $intentos = DB::table('inscripciones as i')
            ->join('grupos as g', 'i.id_oferta', '=', 'g.id_oferta')
            ->where('i.id_estudiante', $estudiante->id_estudiante)
            ->where('g.id_materia', $idMateria)
            ->select('i.id_inscripcion', 'i.estado', 'i.aprobado', 'i.calificacion_final')
            ->get();

        if ($intentos->isEmpty()) {
            return response()->json(['puede_recursar' => false, 'mensaje' => 'No cursó esta materia anteriormente.']);
        }

        $yaAprobada = $intentos->contains(fn($i) => (bool) $i->aprobado === true);
        if ($yaAprobada) {
            return response()->json(['puede_recursar' => false, 'mensaje' => 'La materia ya fue aprobada.']);
        }

        $cursandoAhora = $intentos->contains(fn($i) => $i->estado === 'activo');
        if ($cursandoAhora) {
            return response()->json(['puede_recursar' => false, 'mensaje' => 'Actualmente está cursando esta materia.']);
        }

        return response()->json([
            'puede_recursar' => true,
            'intentos'       => $intentos->count(),
            'mensaje'        => "Puede recursar. Intentos previos: {$intentos->count()}.",
        ]);
    }
}
