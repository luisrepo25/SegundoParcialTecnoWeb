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
            ->leftJoin('carreras as c', 'pd.id_carrera', '=', 'c.id_carrera')
            ->orderBy('pd.fecha_inicio', 'desc')
            ->select(
                'pd.id_periodo', 'pd.nombre', 'pd.tipo_periodo',
                'pd.fecha_inicio', 'pd.fecha_fin', 'pd.activo',
                'pd.fecha_inicio_inscripcion', 'pd.fecha_fin_inscripcion',
                'c.id_carrera', 'c.nombre as carrera_nombre'
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
            'id_periodo'               => $p->id_periodo,
            'nombre'                   => $p->nombre,
            'tipo_periodo'             => $p->tipo_periodo,
            'fecha_inicio'             => $p->fecha_inicio,
            'fecha_fin'                => $p->fecha_fin,
            'fecha_inicio_inscripcion' => $p->fecha_inicio_inscripcion ?? null,
            'fecha_fin_inscripcion'    => $p->fecha_fin_inscripcion ?? null,
            'activo'                   => $p->activo,
            'id_carrera'               => $p->id_carrera,
            'carrera_nombre'           => $p->carrera_nombre,
            'nivel_nombre'             => null,
            'grupos'                   => $gruposPorPeriodo[$p->id_periodo] ?? [],
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

        // ── Slots ocupados por período (para filtrado anti-conflicto client-side) ──
        $ocupadosPorPeriodo = DB::table('grupos')
            ->whereRaw('activo IS TRUE')
            ->select('id_oferta', 'id_periodo', 'id_horario', 'id_aula', 'id_profesor')
            ->get()
            ->groupBy('id_periodo')
            ->map(fn($rows) => $rows->values()->toArray());

        return Inertia::render('Director/CU9Grupos/Index', [
            'periodos'           => $periodos,
            'materias'           => $materias,
            'aulas'              => $aulas,
            'profesores'         => $profesores,
            'horarios'           => $horarios,
            'mallaPorCarrera'    => $mallaPorCarrera,
            'ocupadosPorPeriodo' => $ocupadosPorPeriodo,
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
            'dias_extra'   => 'nullable|array',
            'dias_extra.*' => 'integer|exists:horarios,id_horario',
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

        // Verificar duplicado de código en el mismo período
        if ($request->codigo_grupo) {
            $codigoUsado = DB::table('grupos')
                ->where('codigo_grupo', $request->codigo_grupo)
                ->where('id_periodo',   $request->id_periodo)
                ->exists();
            if ($codigoUsado) {
                return redirect()->back()->withErrors([
                    'grupo' => "El código '{$request->codigo_grupo}' ya está en uso en este período. Usa otro código o déjalo vacío para generar uno automático.",
                ]);
            }
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

        // Código definitivo del grupo principal (para compartir con los extras)
        $codigoPrincipal = $request->codigo_grupo ?: ('G-' . $id);

        // Grupos adicionales por días de la semana (misma hora, diferente día)
        // Todos comparten el mismo codigo_grupo que el grupo principal.
        $diasExtra    = array_filter((array)($request->dias_extra ?? []),
            fn($h) => (int)$h !== (int)$request->id_horario);
        $extraCreados  = 0;
        $extraOmitidos = 0;

        foreach ($diasExtra as $idHorarioExtra) {
            $cAula = DB::table('grupos')
                ->where('id_aula',    $request->id_aula)
                ->where('id_horario', $idHorarioExtra)
                ->where('id_periodo', $request->id_periodo)
                ->whereRaw('activo IS TRUE')->exists();

            $cProf = DB::table('grupos')
                ->where('id_profesor', $request->id_profesor)
                ->where('id_horario',  $idHorarioExtra)
                ->where('id_periodo',  $request->id_periodo)
                ->whereRaw('activo IS TRUE')->exists();

            if ($cAula || $cProf) { $extraOmitidos++; continue; }

            DB::table('grupos')->insert([
                'id_materia'        => $request->id_materia,
                'id_aula'           => $request->id_aula,
                'id_periodo'        => $request->id_periodo,
                'id_profesor'       => $request->id_profesor,
                'id_horario'        => $idHorarioExtra,
                'vacantes_max'      => $request->vacantes_max,
                'vacantes_ocupadas' => 0,
                'activo'            => true,
                'codigo_grupo'      => $codigoPrincipal,
            ]);
            $extraCreados++;
        }

        $msg = 'Grupo registrado.';
        if ($extraCreados > 0) $msg = ($extraCreados + 1) . ' grupos creados (' . $extraCreados . ' día(s) adicional(es)).';
        if ($extraOmitidos > 0) $msg .= " {$extraOmitidos} día(s) omitido(s) por conflicto.";

        return redirect()->route('director.grupos.index')->with('success', $msg);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'vacantes_max'    => 'required|integer|min:1|max:500',
            'codigo_grupo'    => 'nullable|string|max:20',
            'id_aula'         => 'required|integer|exists:aulas,id_aula',
            'id_profesor'     => 'required|integer|exists:profesores,id_profesor',
            'dias_mantener'   => 'nullable|array',
            'dias_mantener.*' => 'integer',
            'dias_agregar'    => 'nullable|array',
            'dias_agregar.*'  => 'integer|exists:horarios,id_horario',
        ]);

        $grupo = DB::table('grupos')->where('id_oferta', $id)->first();
        if (!$grupo) abort(404);

        // Todos los hermanos del bloque (mismo código, mismo período)
        $hermanos    = DB::table('grupos')
            ->where('id_periodo',   $grupo->id_periodo)
            ->where('codigo_grupo', $grupo->codigo_grupo)
            ->get();
        $idsHermanos = $hermanos->pluck('id_oferta')->toArray();

        // Validar vacantes vs ocupadas
        $maxOcupadas = $hermanos->max('vacantes_ocupadas') ?? 0;
        if ($request->vacantes_max < $maxOcupadas) {
            return redirect()->back()->withErrors([
                'grupo' => "No se puede reducir vacantes por debajo de las ya ocupadas ({$maxOcupadas}).",
            ]);
        }

        // Validar capacidad del aula
        $aula = DB::table('aulas')->where('id_aula', $request->id_aula)->first();
        if ($aula && $request->vacantes_max > $aula->capacidad) {
            return redirect()->back()->withErrors([
                'grupo' => "Las vacantes ({$request->vacantes_max}) superan la capacidad del aula ({$aula->capacidad}).",
            ]);
        }

        // Días a mantener y a eliminar
        $diasMantener = !is_null($request->dias_mantener)
            ? array_values(array_intersect($idsHermanos, (array)$request->dias_mantener))
            : $idsHermanos;

        if (empty($diasMantener)) {
            return redirect()->back()->withErrors(['grupo' => 'Debe mantener al menos un día.']);
        }

        $diasEliminar = array_diff($idsHermanos, $diasMantener);

        if (!empty($diasEliminar)) {
            $tieneInscritos = DB::table('inscripciones')->whereIn('id_oferta', $diasEliminar)->exists();
            if ($tieneInscritos) {
                return redirect()->back()->withErrors([
                    'grupo' => 'No se puede quitar un día que tiene estudiantes inscritos.',
                ]);
            }
        }

        // Verificar conflictos de aula/profesor en cada día que se mantiene
        foreach ($hermanos->whereIn('id_oferta', $diasMantener) as $h) {
            $confAula = DB::table('grupos')
                ->where('id_aula',    $request->id_aula)
                ->where('id_horario', $h->id_horario)
                ->where('id_periodo', $grupo->id_periodo)
                ->whereNotIn('id_oferta', $idsHermanos)
                ->whereRaw('activo IS TRUE')->exists();
            if ($confAula) {
                return redirect()->back()->withErrors([
                    'grupo' => "Conflicto: aula ya ocupada el " . ucfirst($h->dia_semana ?? '') . ".",
                ]);
            }
            $confProf = DB::table('grupos')
                ->where('id_profesor', $request->id_profesor)
                ->where('id_horario',  $h->id_horario)
                ->where('id_periodo',  $grupo->id_periodo)
                ->whereNotIn('id_oferta', $idsHermanos)
                ->whereRaw('activo IS TRUE')->exists();
            if ($confProf) {
                return redirect()->back()->withErrors([
                    'grupo' => "Conflicto: el profesor ya tiene grupo el " . ucfirst($h->dia_semana ?? '') . ".",
                ]);
            }
        }

        $nuevoCodigo = $request->codigo_grupo ?: $grupo->codigo_grupo;

        // Actualizar todos los días que se mantienen
        DB::table('grupos')->whereIn('id_oferta', $diasMantener)->update([
            'vacantes_max' => $request->vacantes_max,
            'codigo_grupo' => $nuevoCodigo,
            'id_aula'      => $request->id_aula,
            'id_profesor'  => $request->id_profesor,
        ]);

        // Eliminar días removidos
        if (!empty($diasEliminar)) {
            DB::table('grupos')->whereIn('id_oferta', $diasEliminar)->delete();
        }

        // Agregar nuevos días
        $diasAgregar   = $request->dias_agregar ?? [];
        $extraCreados  = 0;
        $extraOmitidos = 0;

        foreach ($diasAgregar as $idHorarioNuevo) {
            $cAula = DB::table('grupos')
                ->where('id_aula',    $request->id_aula)
                ->where('id_horario', $idHorarioNuevo)
                ->where('id_periodo', $grupo->id_periodo)
                ->whereRaw('activo IS TRUE')->exists();
            $cProf = DB::table('grupos')
                ->where('id_profesor', $request->id_profesor)
                ->where('id_horario',  $idHorarioNuevo)
                ->where('id_periodo',  $grupo->id_periodo)
                ->whereRaw('activo IS TRUE')->exists();
            if ($cAula || $cProf) { $extraOmitidos++; continue; }

            DB::table('grupos')->insert([
                'id_materia'        => $grupo->id_materia,
                'id_aula'           => $request->id_aula,
                'id_periodo'        => $grupo->id_periodo,
                'id_profesor'       => $request->id_profesor,
                'id_horario'        => $idHorarioNuevo,
                'vacantes_max'      => $request->vacantes_max,
                'vacantes_ocupadas' => 0,
                'activo'            => $grupo->activo,
                'codigo_grupo'      => $nuevoCodigo,
            ]);
            $extraCreados++;
        }

        $msg = 'Grupo actualizado.';
        if (!empty($diasEliminar))  $msg .= ' ' . count($diasEliminar) . ' día(s) eliminado(s).';
        if ($extraCreados  > 0)     $msg .= " {$extraCreados} día(s) agregado(s).";
        if ($extraOmitidos > 0)     $msg .= " {$extraOmitidos} día(s) con conflicto omitido(s).";

        return redirect()->route('director.grupos.index')->with('success', $msg);
    }

    public function toggleActivo(int $id)
    {
        $grupo = DB::table('grupos')->where('id_oferta', $id)->first();
        if (!$grupo) abort(404);

        // Sincroniza todos los días del mismo bloque (mismo código en mismo período)
        $hermanos = DB::table('grupos')
            ->where('id_periodo',   $grupo->id_periodo)
            ->where('codigo_grupo', $grupo->codigo_grupo)
            ->pluck('id_oferta');

        DB::table('grupos')->whereIn('id_oferta', $hermanos)
            ->update(['activo' => !$grupo->activo]);

        return redirect()->route('director.grupos.index')
            ->with('success', $grupo->activo ? 'Grupo(s) desactivado(s).' : 'Grupo(s) activado(s).');
    }

    public function destroy(int $id)
    {
        $grupo = DB::table('grupos')->where('id_oferta', $id)->first();
        if (!$grupo) abort(404);

        // Elimina todos los días del mismo bloque (mismo código en mismo período)
        $hermanos = DB::table('grupos')
            ->where('id_periodo',   $grupo->id_periodo)
            ->where('codigo_grupo', $grupo->codigo_grupo)
            ->pluck('id_oferta');

        $tieneInscritos = DB::table('inscripciones')->whereIn('id_oferta', $hermanos)->exists();
        if ($tieneInscritos) {
            return redirect()->route('director.grupos.index')
                ->withErrors(['grupo' => 'No se puede eliminar: tiene estudiantes inscritos.']);
        }

        DB::table('grupos')->whereIn('id_oferta', $hermanos)->delete();

        $n = $hermanos->count();
        return redirect()->route('director.grupos.index')
            ->with('success', $n > 1 ? "{$n} grupos (días) eliminados." : 'Grupo eliminado.');
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

        // UNA sola query: estado completo del período destino
        $destinoRows = DB::table('grupos')
            ->where('id_periodo', $request->id_periodo_destino)
            ->get(['id_aula', 'id_horario', 'id_profesor', 'codigo_grupo']);

        $aulasOcupadas  = [];
        $profesOcupados = [];
        $codigosDestino = [];

        foreach ($destinoRows as $r) {
            $aulasOcupadas[$r->id_aula . ':' . $r->id_horario]       = true;
            $profesOcupados[$r->id_profesor . ':' . $r->id_horario]   = true;
            if ($r->codigo_grupo) $codigosDestino[$r->codigo_grupo]   = true;
        }

        // Agrupar filas origen por codigo_grupo para preservar bloques multi-día
        $bloques = [];
        foreach ($grupos as $g) {
            $key = $g->codigo_grupo ?? ('__solo_' . $g->id_oferta);
            $bloques[$key][] = $g;
        }

        $rowsBatch   = [];
        $sinCodigo   = [];
        $logicosOk   = 0;
        $logicosOmit = 0;

        foreach ($bloques as $codigoOrigen => $hermanos) {
            // Determinar UN código para todo el bloque (compartido entre días)
            $codigoFinal = null;
            $esCodigoReal = !str_starts_with($codigoOrigen, '__solo_');

            if ($esCodigoReal) {
                if (!isset($codigosDestino[$codigoOrigen])) {
                    $codigoFinal = $codigoOrigen;
                } else {
                    $base = substr($codigoOrigen, 0, 17);
                    for ($i = 2; $i <= 99; $i++) {
                        $c = $base . '-' . $i;
                        if (!isset($codigosDestino[$c])) { $codigoFinal = $c; break; }
                    }
                }
            }

            $diasValidos = 0;
            foreach ($hermanos as $g) {
                if (isset($aulasOcupadas[$g->id_aula . ':' . $g->id_horario]) ||
                    isset($profesOcupados[$g->id_profesor . ':' . $g->id_horario])) {
                    continue;  // este día tiene conflicto → omitir solo ese día
                }

                $row = [
                    'id_materia'        => $g->id_materia,
                    'id_aula'           => $g->id_aula,
                    'id_periodo'        => $request->id_periodo_destino,
                    'id_profesor'       => $g->id_profesor,
                    'id_horario'        => $g->id_horario,
                    'vacantes_max'      => $g->vacantes_max,
                    'vacantes_ocupadas' => 0,
                    'activo'            => true,
                    'codigo_grupo'      => $codigoFinal,
                ];

                // Marcar slot ocupado para detectar conflictos dentro del mismo lote
                $aulasOcupadas[$g->id_aula . ':' . $g->id_horario]       = true;
                $profesOcupados[$g->id_profesor . ':' . $g->id_horario]   = true;

                if ($codigoFinal) {
                    $rowsBatch[] = $row;
                } else {
                    $sinCodigo[] = $row;
                }
                $diasValidos++;
            }

            if ($diasValidos > 0) {
                if ($codigoFinal) $codigosDestino[$codigoFinal] = true;
                $logicosOk++;
            } else {
                $logicosOmit++;
            }
        }

        if (!empty($rowsBatch)) {
            DB::table('grupos')->insert($rowsBatch);
        }

        foreach ($sinCodigo as $row) {
            $id = DB::table('grupos')->insertGetId($row, 'id_oferta');
            DB::table('grupos')->where('id_oferta', $id)->update(['codigo_grupo' => 'G-' . $id]);
        }

        $msg = "{$logicosOk} grupo(s) clonados al período destino.";
        if ($logicosOmit > 0) $msg .= " {$logicosOmit} omitido(s) por conflicto en todos sus días.";

        return redirect()->route('director.grupos.index')->with('success', $msg);
    }

    public function destroyPeriodo(int $id)
    {
        $tieneInscritos = DB::table('inscripciones')
            ->join('grupos', 'inscripciones.id_oferta', '=', 'grupos.id_oferta')
            ->where('grupos.id_periodo', $id)
            ->exists();

        if ($tieneInscritos) {
            return redirect()->route('director.grupos.index')
                ->withErrors(['grupo' => 'No se puede vaciar: hay estudiantes inscritos en grupos de este período.']);
        }

        $eliminados = DB::table('grupos')->where('id_periodo', $id)->delete();

        return redirect()->route('director.grupos.index')
            ->with('success', "{$eliminados} grupo(s) eliminados del período.");
    }

    // ── CU9: Ver inscritos en un grupo específico ──────────────────────────────
    public function inscritos(int $idOferta)
    {
        $grupo = DB::table('grupos as g')
            ->join('materias as m',         'g.id_materia',  '=', 'm.id_materia')
            ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo')
            ->join('aulas as a',            'g.id_aula',     '=', 'a.id_aula')
            ->join('profesores as pr',      'g.id_profesor', '=', 'pr.id_profesor')
            ->join('usuarios as up',        'pr.id_usuario', '=', 'up.id_usuario')
            ->join('horarios as h',         'g.id_horario',  '=', 'h.id_horario')
            ->where('g.id_oferta', $idOferta)
            ->select(
                'g.id_oferta', 'g.id_periodo', 'g.codigo_grupo', 'g.vacantes_max', 'g.vacantes_ocupadas', 'g.activo',
                'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                'pd.nombre as periodo_nombre', 'pd.fecha_inicio', 'pd.fecha_fin',
                'a.nombre as aula_nombre',
                DB::raw("up.nombre || ' ' || up.apellido as profesor_nombre"),
                'h.dia_semana', 'h.hora_inicio', 'h.hora_fin'
            )
            ->first();

        if (!$grupo) abort(404);

        $inscritos = DB::table('inscripciones as i')
            ->join('estudiantes as e',  'i.id_estudiante', '=', 'e.id_estudiante')
            ->join('usuarios as u',     'e.id_usuario',    '=', 'u.id_usuario')
            ->leftJoin('carreras as c', 'e.id_carrera_actual', '=', 'c.id_carrera')
            ->where('i.id_oferta', $idOferta)
            ->whereIn('i.estado', ['activo', 'aprobado', 'pendiente_matricula', 'pendiente_pago'])
            ->select(
                'i.id_inscripcion', 'i.estado', 'i.calificacion_final', 'i.aprobado', 'i.fecha_inscripcion',
                'e.id_estudiante', 'e.legajo',
                'u.nombre', 'u.apellido', 'u.email', 'u.dni',
                DB::raw("u.nombre || ' ' || u.apellido as estudiante_nombre"),
                'c.nombre as carrera_nombre'
            )
            ->orderBy('u.apellido')
            ->orderBy('u.nombre')
            ->get();

        $idInscripciones = $inscritos->pluck('id_inscripcion');
        $evalsPorInscripcion = DB::table('evaluaciones')
            ->whereIn('id_inscripcion', $idInscripciones)
            ->orderBy('tipo')
            ->get()
            ->groupBy('id_inscripcion');

        $inscritos = $inscritos->map(function ($ins) use ($evalsPorInscripcion) {
            $arr = (array) $ins;
            $arr['evaluaciones'] = array_values(
                $evalsPorInscripcion->get($ins->id_inscripcion)?->toArray() ?? []
            );
            return $arr;
        })->toArray();

        $cronograma = DB::table('cronogramas')
            ->where('id_periodo', $grupo->id_periodo)
            ->where('tipo_periodo', 'clases')
            ->first();

        $actaCerrada = $cronograma && $cronograma->fecha_fin < now()->toDateString();

        return Inertia::render('Director/CU9Grupos/Inscritos', [
            'grupo'       => (array) $grupo,
            'inscritos'   => $inscritos,
            'cronograma'  => $cronograma,
            'actaCerrada' => $actaCerrada,
            'hoy'         => now()->toDateString(),
        ]);
    }
}
