<?php

namespace App\Http\Controllers\Propietario\CU13Seguimiento;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                'id'              => $c->id_carrera,
                'nombre'          => $c->nombre,
                'duracion_niveles'=> $c->duracion_niveles,
            ] : null;
        }

        $historial  = [];
        $resumen    = [
            'total_materias_cursadas' => 0,
            'materias_aprobadas'      => 0,
            'materias_reprobadas'     => 0,
            'promedio_general'        => null,
            'tasa_aprobacion'         => null,
            'progreso_carrera'        => null,
        ];

        // ── Requiere CU6 (inscripciones) + CU9 (grupos) + CU12 (evaluaciones) ─
        $tieneInscripciones = Schema::hasTable('inscripciones');
        $tieneEvaluaciones  = Schema::hasTable('evaluaciones');

        if ($tieneInscripciones && $estudiante) {
            /*
             * TODO: completar cuando CU6/CU9/CU12 estén listos.
             *
             * $historial = DB::table('inscripciones as i')
             *     ->join('grupos as g',   'i.id_grupo',   '=', 'g.id_grupo')
             *     ->join('materias as m', 'g.id_materia', '=', 'm.id_materia')
             *     ->join('periodos as p', 'g.id_periodo', '=', 'p.id_periodo')
             *     ->where('i.id_estudiante', $estudiante->id_estudiante)
             *     ->select(
             *         'i.id_inscripcion', 'i.estado', 'i.fecha_inscripcion',
             *         'm.nombre as materia', 'm.id_materia',
             *         'p.nombre as periodo', 'p.tipo as periodo_tipo',
             *         'g.id_grupo',
             *     )
             *     ->orderByDesc('p.fecha_inicio')
             *     ->get();
             *
             * if ($tieneEvaluaciones) {
             *     foreach ($historial as &$ins) {
             *         $ins->evaluaciones = DB::table('evaluaciones')
             *             ->where('id_inscripcion', $ins->id_inscripcion)
             *             ->orderBy('numero_evaluacion')
             *             ->get();
             *         $notas = $ins->evaluaciones->pluck('nota')->filter()->values();
             *         $ins->promedio = $notas->count() ? round($notas->avg(), 2) : null;
             *         $ins->aprobado = $ins->promedio !== null && $ins->promedio >= 51;
             *     }
             *
             *     $cursadas  = $historial->count();
             *     $aprobadas = $historial->where('aprobado', true)->count();
             *     $promedios = $historial->pluck('promedio')->filter();
             *     $resumen = [
             *         'total_materias_cursadas' => $cursadas,
             *         'materias_aprobadas'      => $aprobadas,
             *         'materias_reprobadas'     => $cursadas - $aprobadas,
             *         'promedio_general'        => $promedios->count() ? round($promedios->avg(), 2) : null,
             *         'tasa_aprobacion'         => $cursadas > 0 ? round($aprobadas / $cursadas * 100, 1) : null,
             *         'progreso_carrera'        => $carrera
             *             ? round($aprobadas / max($carrera['duracion_niveles'] * 6, 1) * 100, 1)
             *             : null,
             *     ];
             * }
             */
        }

        return Inertia::render('Propietario/CU13Seguimiento/Show', [
            'estudiante' => [
                'id_usuario'    => $usuario->id_usuario,
                'nombre'        => $usuario->nombre,
                'apellido'      => $usuario->apellido,
                'email'         => $usuario->email,
                'dni'           => $usuario->dni,
                'telefono'      => $usuario->telefono,
                'activo'        => $usuario->activo,
                'legajo'        => $estudiante?->legajo,
                'tutor_nombre'  => $estudiante?->tutor_nombre,
                'tutor_telefono'=> $estudiante?->tutor_telefono,
                'observaciones' => $estudiante?->observaciones,
                'fecha_inicio'  => $estudiante?->fecha_inscripcion_inicial,
            ],
            'carrera'    => $carrera,
            'historial'  => $historial,
            'resumen'    => $resumen,
            'pendiente'  => [
                'inscripciones' => !$tieneInscripciones,
                'evaluaciones'  => !$tieneEvaluaciones,
            ],
        ]);
    }

    // ── CU13.3 — Registrar abandono de carrera ────────────────────────────────
    public function registrarAbandono(Request $request, int $id)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $estudiante = Estudiante::where('id_usuario', $id)->firstOrFail();

        /*
         * TODO: cuando exista tabla 'abandonos', insertar registro formal.
         * Por ahora se registra en observaciones como historial temporal.
         */
        $nota = '[ABANDONO ' . now()->format('d/m/Y') . '] ' . $request->motivo;
        $prev = $estudiante->observaciones ? $estudiante->observaciones . "\n" : '';
        $estudiante->update(['observaciones' => $prev . $nota]);

        return back()->with('success', 'Abandono de carrera registrado.');
    }

    // ── CU13.4 — Validar si puede recursar una materia ───────────────────────
    public function validarRecurso(int $idUsuario, int $idMateria)
    {
        /*
         * TODO: completar cuando CU6/CU12 estén listos.
         * Lógica: estudiante puede recursar si cursó la materia al menos una vez
         * y obtuvo nota < 51 o tiene estado 'reprobado'.
         */
        return response()->json([
            'puede_recursar' => null,
            'mensaje'        => 'Validación disponible cuando CU6 y CU12 estén implementados.',
        ]);
    }
}
