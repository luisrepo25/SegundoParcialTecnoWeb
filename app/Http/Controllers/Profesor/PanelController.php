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

        $grupos = DB::table('grupos')
            ->join('materias', 'grupos.id_materia', '=', 'materias.id_materia')
            ->join('aulas', 'grupos.id_aula', '=', 'aulas.id_aula')
            ->join('horarios', 'grupos.id_horario', '=', 'horarios.id_horario')
            ->where('grupos.id_profesor', $profesor->id_profesor)
            ->where('grupos.activo', true)
            ->select(
                'grupos.id_oferta',
                'grupos.codigo_grupo',
                'materias.nombre as materia',
                'aulas.nombre as aula',
                'horarios.dia_semana',
                'horarios.hora_inicio',
                'horarios.hora_fin'
            )
            ->get();

        return Inertia::render('Profesor/Index', [
            'grupos' => $grupos
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
