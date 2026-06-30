<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PanelController extends Controller
{
    /**
     * Muestra las materias asignadas al docente.
     */
    public function index()
    {
        $idUsuario = Auth::user()->id_usuario;
        $profesor = DB::table('profesores')->where('id_usuario', $idUsuario)->first();

        if (!$profesor) {
            return Inertia::render('Profesor/Index', [
                'grupos' => []
            ]);
        }

        $hoy = now()->toDateString();

        // IDs de periodos donde el profesor tiene grupos activos
        $periodoIds = DB::table('grupos')
            ->where('id_profesor', $profesor->id_profesor)
            ->where('activo', true)
            ->pluck('id_periodo')
            ->unique();

        if ($periodoIds->isEmpty()) {
            return Inertia::render('Profesor/Index', [
                'grupos'        => [],
                'periodoActual' => null,
                'esFallback'    => false,
            ]);
        }

        // Períodos de esos grupos que están vigentes hoy según sus propias fechas
        $periodosActivos = DB::table('periodos_dictado')
            ->whereIn('id_periodo', $periodoIds)
            ->whereNotNull('fecha_inicio')
            ->whereNotNull('fecha_fin')
            ->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin',    '>=', $hoy)
            ->pluck('id_periodo');

        $esFallback = false;

        // Fallback 1: período con fecha_inicio más reciente (pasada)
        if ($periodosActivos->isEmpty()) {
            $esFallback      = true;
            $periodosActivos = DB::table('periodos_dictado')
                ->whereIn('id_periodo', $periodoIds)
                ->whereNotNull('fecha_inicio')
                ->where('fecha_inicio', '<=', $hoy)
                ->orderBy('fecha_inicio', 'desc')
                ->limit(1)
                ->pluck('id_periodo');
        }

        // Fallback 2: si no hay fechas, el MAX id_periodo
        if ($periodosActivos->isEmpty()) {
            $esFallback      = true;
            $periodosActivos = collect([$periodoIds->max()]);
        }

        $raw = DB::table('grupos')
            ->join('materias',        'grupos.id_materia',  '=', 'materias.id_materia')
            ->join('aulas',           'grupos.id_aula',     '=', 'aulas.id_aula')
            ->join('horarios',        'grupos.id_horario',  '=', 'horarios.id_horario')
            ->join('periodos_dictado','grupos.id_periodo',  '=', 'periodos_dictado.id_periodo')
            ->where('grupos.id_profesor', $profesor->id_profesor)
            ->where('grupos.activo', true)
            ->whereIn('grupos.id_periodo', $periodosActivos)
            ->select(
                'grupos.id_oferta',
                'grupos.codigo_grupo',
                'grupos.id_periodo',
                'materias.nombre as materia',
                'aulas.nombre as aula',
                'horarios.dia_semana',
                'horarios.hora_inicio',
                'horarios.hora_fin',
                'periodos_dictado.nombre as periodo_nombre'
            )
            ->orderBy('grupos.codigo_grupo')
            ->get();

        // Paso 1: deduplicar por (codigo_grupo, id_periodo), acumulando días
        $porGrupo = $raw
            ->groupBy(fn($g) => $g->id_periodo . '|' . $g->codigo_grupo)
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'id_oferta'    => $first->id_oferta,
                    'codigo_grupo' => $first->codigo_grupo,
                    'materia'      => $first->materia,
                    'aula'         => $first->aula,
                    'hora_inicio'  => $first->hora_inicio,
                    'hora_fin'     => $first->hora_fin,
                    'dias'         => $rows->pluck('dia_semana')->unique()->sort()->values()->toArray(),
                ];
            })
            ->values();

        // Paso 2: agrupar por (materia, aula) → una tarjeta con varios horarios
        $materias = $porGrupo
            ->groupBy(fn($g) => $g['materia'] . '||' . $g['aula'])
            ->map(function ($grupos) {
                $first = $grupos->first();
                return [
                    'materia' => $first['materia'],
                    'aula'    => $first['aula'],
                    'dias'    => $first['dias'],
                    'grupos'  => $grupos
                        ->sortBy('hora_inicio')
                        ->map(fn($g) => [
                            'id_oferta'    => $g['id_oferta'],
                            'codigo_grupo' => $g['codigo_grupo'],
                            'hora_inicio'  => $g['hora_inicio'],
                            'hora_fin'     => $g['hora_fin'],
                        ])
                        ->values()
                        ->toArray(),
                ];
            })
            ->values();

        $periodoActual = DB::table('periodos_dictado')
            ->whereIn('id_periodo', $periodosActivos)
            ->pluck('nombre')
            ->unique()
            ->implode(' · ');

        return Inertia::render('Profesor/Index', [
            'materias'      => $materias,
            'periodoActual' => $periodoActual,
            'esFallback'    => $esFallback,
        ]);
    }

    /**
     * Muestra los estudiantes inscritos en un grupo.
     */
    public function grupoDetalle($idGrupo)
    {
        $idUsuario = Auth::user()->id_usuario;
        $profesor = DB::table('profesores')->where('id_usuario', $idUsuario)->first();

        if (!$profesor) {
            abort(403, 'Acceso denegado. No eres profesor.');
        }

        // Verificar que el grupo pertenece al profesor
        $grupo = DB::table('grupos')
            ->join('materias', 'grupos.id_materia', '=', 'materias.id_materia')
            ->where('id_oferta', $idGrupo)
            ->where('id_profesor', $profesor->id_profesor)
            ->select('grupos.*', 'materias.nombre as materia_nombre')
            ->first();

        if (!$grupo) {
            abort(403, 'Acceso denegado o grupo no encontrado.');
        }

        // Obtener estudiantes
        $estudiantes = DB::table('inscripciones')
            ->join('estudiantes', 'inscripciones.id_estudiante', '=', 'estudiantes.id_estudiante')
            ->join('usuarios', 'estudiantes.id_usuario', '=', 'usuarios.id_usuario')
            ->where('inscripciones.id_oferta', $idGrupo)
            ->select(
                'usuarios.id_usuario',
                'usuarios.nombre',
                'usuarios.apellido',
                'usuarios.email',
                'usuarios.foto_perfil',
                'estudiantes.legajo',
                'inscripciones.id_inscripcion',
                'inscripciones.estado',
                'inscripciones.calificacion_final'
            )
            ->orderBy('usuarios.apellido')
            ->get();

        // Cargar evaluaciones por inscripcion
        $idInscripciones = $estudiantes->pluck('id_inscripcion');
        $evalsPorInscripcion = DB::table('evaluaciones')
            ->whereIn('id_inscripcion', $idInscripciones)
            ->orderBy('tipo')
            ->get()
            ->groupBy('id_inscripcion');

        $estudiantes = $estudiantes->map(function ($est) use ($evalsPorInscripcion) {
            $est->evaluaciones = array_values(
                $evalsPorInscripcion->get($est->id_inscripcion)?->toArray() ?? []
            );
            return $est;
        });

        // Cronograma de clases del período (controla si el acta está abierta o cerrada)
        $cronograma = DB::table('cronogramas')
            ->where('id_periodo', $grupo->id_periodo)
            ->where('tipo_periodo', 'clases')
            ->first();

        $actaCerrada = $cronograma && $cronograma->fecha_fin < now()->toDateString();

        return Inertia::render('Profesor/GrupoDetalle', [
            'grupo'       => $grupo,
            'estudiantes' => $estudiantes,
            'cronograma'  => $cronograma,
            'actaCerrada' => $actaCerrada,
            'hoy'         => now()->toDateString(),
        ]);
    }
}
