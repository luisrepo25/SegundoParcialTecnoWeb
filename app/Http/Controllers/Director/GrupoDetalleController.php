<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class GrupoDetalleController extends Controller
{
    public function show($idGrupo)
    {
        $grupo = DB::table('grupos')
            ->join('materias', 'grupos.id_materia', '=', 'materias.id_materia')
            ->where('grupos.id_oferta', $idGrupo)
            ->select('grupos.*', 'materias.nombre as materia_nombre')
            ->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado.');
        }

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

        $cronograma = DB::table('cronogramas')
            ->where('id_periodo', $grupo->id_periodo)
            ->where('tipo_periodo', 'clases')
            ->first();

        $actaCerrada = $cronograma && $cronograma->fecha_fin < now()->toDateString();

        return Inertia::render('Director/GrupoDetalle', [
            'grupo'       => $grupo,
            'estudiantes' => $estudiantes,
            'cronograma'  => $cronograma,
            'actaCerrada' => $actaCerrada,
            'hoy'         => now()->toDateString(),
        ]);
    }
}
