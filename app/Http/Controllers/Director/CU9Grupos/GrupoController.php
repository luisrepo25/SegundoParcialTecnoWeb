<?php

namespace App\Http\Controllers\Director\CU9Grupos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GrupoController extends Controller
{
    public function index()
    {
        // ── Todos los grupos con sus relaciones ──────────────────────────────
        $gruposRaw = DB::table('grupos as g')
            ->join('materias as m',    'g.id_materia',  '=', 'm.id_materia')
            ->join('aulas as a',       'g.id_aula',     '=', 'a.id_aula')
            ->join('profesores as p',  'g.id_profesor', '=', 'p.id_profesor')
            ->join('usuarios as u',    'p.id_usuario',  '=', 'u.id_usuario')
            ->join('horarios as h',    'g.id_horario',  '=', 'h.id_horario')
            ->orderBy('g.id_periodo')
            ->orderBy('m.nombre')
            ->select(
                'g.id_oferta', 'g.id_periodo', 'g.vacantes_max', 'g.vacantes_ocupadas',
                'g.activo', 'g.codigo_grupo',
                'm.id_materia', 'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                'a.id_aula', 'a.nombre as aula_nombre', 'a.capacidad as aula_capacidad',
                'p.id_profesor',
                'u.nombre as prof_nombre', 'u.apellido as prof_apellido',
                'h.id_horario', 'h.dia_semana', 'h.hora_inicio', 'h.hora_fin'
            )
            ->get();

        // ── Todos los períodos (para agrupar y para el select del modal) ─────
        $periodosRaw = DB::table('periodos_dictado as pd')
            ->leftJoin('niveles_carrera as n', 'pd.id_nivel', '=', 'n.id_nivel')
            ->leftJoin('carreras as cn', 'n.id_carrera', '=', 'cn.id_carrera')
            ->leftJoin('carreras as cl', 'pd.id_carrera', '=', 'cl.id_carrera')
            ->orderBy('pd.fecha_inicio', 'desc')
            ->select(
                'pd.id_periodo', 'pd.nombre', 'pd.tipo_periodo',
                'pd.fecha_inicio', 'pd.fecha_fin', 'pd.activo',
                DB::raw("COALESCE(cn.id_carrera, cl.id_carrera) as id_carrera"),
                DB::raw("COALESCE(cn.nombre, cl.nombre) as carrera_nombre"),
                'n.numero_nivel', 'n.nombre as nivel_nombre'
            )
            ->get();

        // ── Agrupar grupos por período ────────────────────────────────────────
        $gruposPorPeriodo = [];
        foreach ($gruposRaw as $g) {
            $gruposPorPeriodo[$g->id_periodo][] = [
                'id_oferta'         => $g->id_oferta,
                'codigo_grupo'      => $g->codigo_grupo,
                'id_materia'        => $g->id_materia,
                'materia_nombre'    => $g->materia_nombre,
                'materia_codigo'    => $g->materia_codigo,
                'id_aula'           => $g->id_aula,
                'aula_nombre'       => $g->aula_nombre,
                'aula_capacidad'    => $g->aula_capacidad,
                'id_profesor'       => $g->id_profesor,
                'profesor_nombre'   => $g->prof_nombre . ' ' . $g->prof_apellido,
                'id_horario'        => $g->id_horario,
                'dia_semana'        => $g->dia_semana,
                'hora_inicio'       => substr($g->hora_inicio, 0, 5),
                'hora_fin'          => substr($g->hora_fin, 0, 5),
                'vacantes_max'      => $g->vacantes_max,
                'vacantes_ocupadas' => $g->vacantes_ocupadas ?? 0,
                'activo'            => $g->activo,
            ];
        }

        $periodos = $periodosRaw->map(fn($p) => [
            'id_periodo'     => $p->id_periodo,
            'nombre'         => $p->nombre,
            'tipo_periodo'   => $p->tipo_periodo,
            'fecha_inicio'   => $p->fecha_inicio,
            'fecha_fin'      => $p->fecha_fin,
            'activo'         => $p->activo,
            'id_carrera'     => $p->id_carrera,
            'carrera_nombre' => $p->carrera_nombre,
            'nivel_nombre'   => $p->nivel_nombre ? ($p->nivel_nombre . ' (Año ' . $p->numero_nivel . ')') : null,
            'grupos'         => $gruposPorPeriodo[$p->id_periodo] ?? [],
        ]);

        // ── Datos para selects del modal ─────────────────────────────────────
        $materias = DB::table('materias')
            ->whereRaw('activo IS TRUE')
            ->orderBy('nombre')
            ->select('id_materia', 'codigo', 'nombre')
            ->get();

        $aulas = DB::table('aulas')
            ->whereRaw('activo IS TRUE')
            ->orderBy('nombre')
            ->select('id_aula', 'nombre', 'capacidad')
            ->get();

        $profesores = DB::table('profesores as p')
            ->join('usuarios as u', 'p.id_usuario', '=', 'u.id_usuario')
            ->whereRaw('u.activo IS TRUE')
            ->orderBy('u.apellido')
            ->select('p.id_profesor', 'u.nombre', 'u.apellido', 'p.especialidad')
            ->get()
            ->map(fn($p) => [
                'id_profesor'  => $p->id_profesor,
                'nombre'       => $p->nombre . ' ' . $p->apellido,
                'especialidad' => $p->especialidad,
            ]);

        $horarios = DB::table('horarios')
            ->whereRaw('activo IS TRUE')
            ->orderByRaw("CASE dia_semana WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2 WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4 WHEN 'viernes' THEN 5 WHEN 'sabado' THEN 6 ELSE 7 END")
            ->orderBy('hora_inicio')
            ->select('id_horario', 'dia_semana', 'hora_inicio', 'hora_fin')
            ->get()
            ->map(fn($h) => [
                'id_horario'  => $h->id_horario,
                'label'       => ucfirst($h->dia_semana) . ' ' . substr($h->hora_inicio, 0, 5) . '–' . substr($h->hora_fin, 0, 5),
                'dia_semana'  => $h->dia_semana,
                'hora_inicio' => substr($h->hora_inicio, 0, 5),
                'hora_fin'    => substr($h->hora_fin, 0, 5),
            ]);

        // ── Malla curricular por carrera (para filtrar materias en modal) ────────
        $mallaRows = DB::table('malla_curricular as mc')
            ->join('materias as m',        'mc.id_materia', '=', 'm.id_materia')
            ->join('niveles_carrera as n',  'mc.id_nivel',   '=', 'n.id_nivel')
            ->join('carreras as c',         'n.id_carrera',  '=', 'c.id_carrera')
            ->whereRaw('m.activo IS TRUE')
            ->orderBy('c.id_carrera')
            ->orderBy('n.numero_nivel')
            ->orderBy('m.nombre')
            ->select(
                'c.id_carrera',
                'n.id_nivel', 'n.numero_nivel',
                'm.id_materia', 'm.codigo', 'm.nombre as materia_nombre'
            )
            ->get();

        $mallaPorCarrera = [];
        foreach ($mallaRows as $r) {
            $mallaPorCarrera[$r->id_carrera][] = [
                'id_materia'   => $r->id_materia,
                'id_nivel'     => $r->id_nivel,
                'numero_nivel' => $r->numero_nivel,
                'codigo'       => $r->codigo,
                'nombre'       => $r->materia_nombre,
            ];
        }

        return Inertia::render('Director/CU9Grupos/Index', [
            'periodos'        => $periodos,
            'materias'        => $materias,
            'aulas'           => $aulas,
            'profesores'      => $profesores,
            'horarios'        => $horarios,
            'mallaPorCarrera' => $mallaPorCarrera,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_materia'   => 'required|integer|exists:materias,id_materia',
            'id_aula'      => 'required|integer|exists:aulas,id_aula',
            'id_periodo'   => 'required|integer|exists:periodos_dictado,id_periodo',
            'id_profesor'  => 'required|integer|exists:profesores,id_profesor',
            'id_horario'   => 'required|integer|exists:horarios,id_horario',
            'vacantes_max' => 'required|integer|min:1|max:500',
            'codigo_grupo' => 'nullable|string|max:20',
        ]);

        // Validar que vacantes_max no supere la capacidad del aula
        $aula = DB::table('aulas')->where('id_aula', $request->id_aula)->first();
        if ($aula && $request->vacantes_max > $aula->capacidad) {
            return redirect()->back()->withErrors([
                'grupo' => "Las vacantes ({$request->vacantes_max}) superan la capacidad del aula ({$aula->capacidad}).",
            ]);
        }

        // Verificar conflicto: misma aula + mismo horario + mismo período
        $conflictoAula = DB::table('grupos')
            ->where('id_aula',    $request->id_aula)
            ->where('id_horario', $request->id_horario)
            ->where('id_periodo', $request->id_periodo)
            ->whereRaw('activo IS TRUE')
            ->exists();

        if ($conflictoAula) {
            return redirect()->back()->withErrors([
                'grupo' => 'Conflicto: esa aula ya tiene un grupo asignado en ese horario y período.',
            ]);
        }

        // Verificar conflicto: mismo profesor + mismo horario + mismo período
        $conflictoProfesor = DB::table('grupos')
            ->where('id_profesor', $request->id_profesor)
            ->where('id_horario',  $request->id_horario)
            ->where('id_periodo',  $request->id_periodo)
            ->whereRaw('activo IS TRUE')
            ->exists();

        if ($conflictoProfesor) {
            return redirect()->back()->withErrors([
                'grupo' => 'Conflicto: ese profesor ya tiene un grupo asignado en ese horario y período.',
            ]);
        }

        $id = DB::table('grupos')->insertGetId([
            'id_materia'        => $request->id_materia,
            'id_aula'           => $request->id_aula,
            'id_periodo'        => $request->id_periodo,
            'id_profesor'       => $request->id_profesor,
            'id_horario'        => $request->id_horario,
            'vacantes_max'      => $request->vacantes_max,
            'vacantes_ocupadas' => 0,
            'activo'            => true,
            'codigo_grupo'      => $request->codigo_grupo ?: null,
        ], 'id_oferta');

        // Si no se proveyó código, usar G-{id}
        if (!$request->codigo_grupo) {
            DB::table('grupos')->where('id_oferta', $id)->update([
                'codigo_grupo' => 'G-' . $id,
            ]);
        }

        return redirect()->route('director.grupos.index')->with('success', 'Grupo registrado.');
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'vacantes_max' => 'required|integer|min:1|max:500',
            'codigo_grupo' => 'nullable|string|max:20',
            'id_aula'      => 'required|integer|exists:aulas,id_aula',
            'id_profesor'  => 'required|integer|exists:profesores,id_profesor',
            'id_horario'   => 'required|integer|exists:horarios,id_horario',
        ]);

        $grupo = DB::table('grupos')->where('id_oferta', $id)->first();
        if (!$grupo) abort(404);

        $ocupadas = $grupo->vacantes_ocupadas ?? 0;
        if ($request->vacantes_max < $ocupadas) {
            return redirect()->back()->withErrors([
                'grupo' => "No se puede reducir vacantes por debajo de las ya ocupadas ({$ocupadas}).",
            ]);
        }

        $aula = DB::table('aulas')->where('id_aula', $request->id_aula)->first();
        if ($aula && $request->vacantes_max > $aula->capacidad) {
            return redirect()->back()->withErrors([
                'grupo' => "Las vacantes ({$request->vacantes_max}) superan la capacidad del aula ({$aula->capacidad}).",
            ]);
        }

        $confAula = DB::table('grupos')
            ->where('id_aula',    $request->id_aula)
            ->where('id_horario', $request->id_horario)
            ->where('id_periodo', $grupo->id_periodo)
            ->where('id_oferta',  '!=', $id)
            ->whereRaw('activo IS TRUE')
            ->exists();

        if ($confAula) {
            return redirect()->back()->withErrors([
                'grupo' => 'Conflicto: esa aula ya tiene un grupo en ese horario y período.',
            ]);
        }

        $confProf = DB::table('grupos')
            ->where('id_profesor', $request->id_profesor)
            ->where('id_horario',  $request->id_horario)
            ->where('id_periodo',  $grupo->id_periodo)
            ->where('id_oferta',   '!=', $id)
            ->whereRaw('activo IS TRUE')
            ->exists();

        if ($confProf) {
            return redirect()->back()->withErrors([
                'grupo' => 'Conflicto: ese profesor ya tiene un grupo en ese horario y período.',
            ]);
        }

        DB::table('grupos')->where('id_oferta', $id)->update([
            'vacantes_max' => $request->vacantes_max,
            'codigo_grupo' => $request->codigo_grupo ?: $grupo->codigo_grupo,
            'id_aula'      => $request->id_aula,
            'id_profesor'  => $request->id_profesor,
            'id_horario'   => $request->id_horario,
        ]);

        return redirect()->route('director.grupos.index')->with('success', 'Grupo actualizado.');
    }

    public function toggleActivo(int $id)
    {
        $grupo = DB::table('grupos')->where('id_oferta', $id)->first();
        if (!$grupo) abort(404);

        DB::table('grupos')->where('id_oferta', $id)
            ->update(['activo' => !$grupo->activo]);

        return redirect()->route('director.grupos.index')
            ->with('success', $grupo->activo ? 'Grupo desactivado.' : 'Grupo activado.');
    }

    public function destroy(int $id)
    {
        $tieneInscritos = DB::table('inscripciones')->where('id_oferta', $id)->exists();
        if ($tieneInscritos) {
            return redirect()->route('director.grupos.index')
                ->withErrors(['grupo' => 'No se puede eliminar: tiene estudiantes inscritos.']);
        }

        DB::table('grupos')->where('id_oferta', $id)->delete();
        return redirect()->route('director.grupos.index')->with('success', 'Grupo eliminado.');
    }

    public function clonar(Request $request)
    {
        $request->validate([
            'id_periodo_origen'  => 'required|integer|exists:periodos_dictado,id_periodo',
            'id_periodo_destino' => 'required|integer|exists:periodos_dictado,id_periodo',
        ]);

        if ($request->id_periodo_origen === $request->id_periodo_destino) {
            return redirect()->back()->withErrors(['grupo' => 'El período origen y destino deben ser distintos.']);
        }

        $grupos = DB::table('grupos')->where('id_periodo', $request->id_periodo_origen)->get();

        $copiados = 0;
        $omitidos = 0;

        foreach ($grupos as $g) {
            // Verificar conflicto aula+horario en destino
            $confAula = DB::table('grupos')
                ->where('id_aula',    $g->id_aula)
                ->where('id_horario', $g->id_horario)
                ->where('id_periodo', $request->id_periodo_destino)
                ->whereRaw('activo IS TRUE')
                ->exists();

            // Verificar conflicto profesor+horario en destino
            $confProf = DB::table('grupos')
                ->where('id_profesor', $g->id_profesor)
                ->where('id_horario',  $g->id_horario)
                ->where('id_periodo',  $request->id_periodo_destino)
                ->whereRaw('activo IS TRUE')
                ->exists();

            if ($confAula || $confProf) {
                $omitidos++;
                continue;
            }

            $id = DB::table('grupos')->insertGetId([
                'id_materia'        => $g->id_materia,
                'id_aula'           => $g->id_aula,
                'id_periodo'        => $request->id_periodo_destino,
                'id_profesor'       => $g->id_profesor,
                'id_horario'        => $g->id_horario,
                'vacantes_max'      => $g->vacantes_max,
                'vacantes_ocupadas' => 0,
                'activo'            => true,
                'codigo_grupo'      => null,
            ], 'id_oferta');
            DB::table('grupos')->where('id_oferta', $id)->update(['codigo_grupo' => 'G-' . $id]);
            $copiados++;
        }

        $msg = "{$copiados} grupo(s) clonados al período destino.";
        if ($omitidos > 0) $msg .= " {$omitidos} omitido(s) por conflicto de horario.";

        return redirect()->route('director.grupos.index')->with('success', $msg);
    }
}
