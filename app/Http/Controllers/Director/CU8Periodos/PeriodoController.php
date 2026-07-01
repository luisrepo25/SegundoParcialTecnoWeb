<?php

namespace App\Http\Controllers\Director\CU8Periodos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PeriodoController extends Controller
{
    public function index()
    {
        // ── Todos los períodos agrupados por carrera ──────────────────────────
        $rows = DB::table('periodos_dictado as pd')
            ->join('carreras as c', 'pd.id_carrera', '=', 'c.id_carrera')
            ->whereNotNull('pd.id_carrera')
            ->orderBy('c.nombre')
            ->orderBy('pd.fecha_inicio')
            ->select(
                'c.id_carrera', 'c.nombre as carrera_nombre', 'c.tipo as carrera_tipo',
                'pd.id_periodo', 'pd.nombre', 'pd.tipo_periodo',
                'pd.fecha_inicio', 'pd.fecha_fin',
                'pd.fecha_inicio_inscripcion', 'pd.fecha_fin_inscripcion',
                'pd.max_materias', 'pd.activo'
            )
            ->get();

        $carreraMap = [];
        foreach ($rows as $r) {
            $cid = $r->id_carrera;
            if (!isset($carreraMap[$cid])) {
                $carreraMap[$cid] = [
                    'id_carrera' => $cid,
                    'nombre'     => $r->carrera_nombre,
                    'tipo'       => $r->carrera_tipo,
                    'periodos'   => [],
                ];
            }
            $carreraMap[$cid]['periodos'][] = $this->mapPeriodo($r);
        }

        // Incluir carreras sin períodos
        $todasCarreras = DB::table('carreras')->orderBy('nombre')->get();
        foreach ($todasCarreras as $c) {
            if (!isset($carreraMap[$c->id_carrera])) {
                $carreraMap[$c->id_carrera] = [
                    'id_carrera' => $c->id_carrera,
                    'nombre'     => $c->nombre,
                    'tipo'       => $c->tipo,
                    'periodos'   => [],
                ];
            }
        }

        $carreras = array_values($carreraMap);

        // ── Carreras para selector del modal ─────────────────────────────────
        $carrerasSelect = DB::table('carreras')
            ->orderBy('nombre')
            ->select('id_carrera', 'nombre', 'tipo', 'modalidad')
            ->get();

        // ── Plantillas (períodos existentes para clonar configuración) ────────
        $anioActual = now()->year;
        $periodosExistentes = DB::table('periodos_dictado as pd')
            ->join('carreras as c', 'pd.id_carrera', '=', 'c.id_carrera')
            ->whereNotNull('pd.id_carrera')
            ->whereYear('pd.fecha_inicio', $anioActual)
            ->orderBy('pd.fecha_inicio', 'desc')
            ->select('pd.id_periodo', 'pd.nombre', 'pd.tipo_periodo',
                     'pd.fecha_inicio', 'pd.fecha_fin', 'c.id_carrera', 'c.nombre as carrera_nombre')
            ->get();

        $plantillasMap = [];
        foreach ($periodosExistentes as $r) {
            $key = strtolower(trim($r->nombre)) . '||' . $r->tipo_periodo;
            if (!isset($plantillasMap[$key])) {
                $plantillasMap[$key] = [
                    'label'        => $r->nombre . ' (' . $r->tipo_periodo . ')',
                    'nombre'       => $r->nombre,
                    'tipo_periodo' => $r->tipo_periodo,
                    'fecha_inicio' => $r->fecha_inicio,
                    'fecha_fin'    => $r->fecha_fin,
                    'id_carreras'  => [],
                ];
            }
            $plantillasMap[$key]['id_carreras'][] = $r->id_carrera;
        }

        // Envía todos los cronogramas activos (sin filtro de año);
        // la Vue filtra por el año del período que se está editando/creando.
        $cronogramasClases = DB::table('cronogramas')
            ->where('tipo_periodo', 'clases')
            ->where('activo', true)
            ->orderBy('fecha_inicio', 'desc')
            ->select('id_cronograma', 'nombre', 'modalidad', 'fecha_inicio', 'fecha_fin')
            ->get();

        $cronogramasInscripcion = DB::table('cronogramas')
            ->where('tipo_periodo', 'inscripcion')
            ->where('activo', true)
            ->orderBy('fecha_inicio', 'desc')
            ->select('id_cronograma', 'nombre', 'modalidad', 'fecha_inicio', 'fecha_fin')
            ->get();

        return Inertia::render('Director/CU8Periodos/Index', [
            'carreras'               => $carreras,
            'carrerasSelect'         => $carrerasSelect,
            'plantillas'             => array_values($plantillasMap),
            'cronogramasClases'      => $cronogramasClases,
            'cronogramasInscripcion' => $cronogramasInscripcion,
        ]);
    }

    private function mapPeriodo($r): array
    {
        return [
            'id_periodo'               => $r->id_periodo,
            'nombre'                   => $r->nombre,
            'tipo_periodo'             => $r->tipo_periodo,
            'fecha_inicio'             => $r->fecha_inicio,
            'fecha_fin'                => $r->fecha_fin,
            'fecha_inicio_inscripcion' => $r->fecha_inicio_inscripcion ?? null,
            'fecha_fin_inscripcion'    => $r->fecha_fin_inscripcion ?? null,
            'activo'                   => $r->activo,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_carrera'               => 'required|integer|exists:carreras,id_carrera',
            'nombre'                   => 'required|string|max:50',
            'tipo_periodo'             => 'required|in:mensual,semestral,anual,intensivo',
            'fecha_inicio'             => 'required|date',
            'fecha_fin'                => 'required|date|after:fecha_inicio',
            'fecha_inicio_inscripcion' => 'nullable|date',
            'fecha_fin_inscripcion'    => 'nullable|date|after_or_equal:fecha_inicio_inscripcion',
        ], [
            'nombre.required'                   => 'El nombre del período es obligatorio.',
            'fecha_inicio.required'             => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'                 => 'La fecha de inicio no es válida.',
            'fecha_fin.required'                => 'La fecha de fin es obligatoria.',
            'fecha_fin.date'                    => 'La fecha de fin no es válida.',
            'fecha_fin.after'                   => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'fecha_fin_inscripcion.after_or_equal' => 'La fecha de cierre de inscripciones no puede ser anterior a la de inicio.',
        ]);

        $yaExiste = DB::table('periodos_dictado')
            ->where('id_carrera', $request->id_carrera)
            ->where('nombre', $request->nombre)
            ->whereNull('id_nivel')
            ->exists();

        if ($yaExiste) {
            return redirect()->back()->withErrors([
                'periodo' => 'Ya existe un período con ese nombre para esta carrera.',
            ]);
        }

        $maxMaterias = DB::table('carreras')
            ->where('id_carrera', $request->id_carrera)
            ->value('max_materias') ?? 5;

        DB::table('periodos_dictado')->insert([
            'id_carrera'               => $request->id_carrera,
            'id_nivel'                 => null,
            'nombre'                   => $request->nombre,
            'tipo_periodo'             => $request->tipo_periodo,
            'fecha_inicio'             => $request->fecha_inicio,
            'fecha_fin'                => $request->fecha_fin,
            'fecha_inicio_inscripcion' => $request->fecha_inicio_inscripcion ?: null,
            'fecha_fin_inscripcion'    => $request->fecha_fin_inscripcion ?: null,
            'max_materias'             => $maxMaterias,
            'activo'                   => true,
        ]);

        return redirect()->back()->with('success', 'Período registrado.');
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nombre'                   => 'required|string|max:50',
            'tipo_periodo'             => 'required|in:mensual,semestral,anual,intensivo',
            'fecha_inicio'             => 'required|date',
            'fecha_fin'                => 'required|date|after:fecha_inicio',
            'fecha_inicio_inscripcion' => 'nullable|date',
            'fecha_fin_inscripcion'    => 'nullable|date|after_or_equal:fecha_inicio_inscripcion',
        ], [
            'nombre.required'                   => 'El nombre del período es obligatorio.',
            'fecha_inicio.required'             => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'                 => 'La fecha de inicio no es válida.',
            'fecha_fin.required'                => 'La fecha de fin es obligatoria.',
            'fecha_fin.date'                    => 'La fecha de fin no es válida.',
            'fecha_fin.after'                   => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'fecha_fin_inscripcion.after_or_equal' => 'La fecha de cierre de inscripciones no puede ser anterior a la de inicio.',
        ]);

        DB::table('periodos_dictado')->where('id_periodo', $id)->update([
            'nombre'                   => $request->nombre,
            'tipo_periodo'             => $request->tipo_periodo,
            'fecha_inicio'             => $request->fecha_inicio,
            'fecha_fin'                => $request->fecha_fin,
            'fecha_inicio_inscripcion' => $request->fecha_inicio_inscripcion ?: null,
            'fecha_fin_inscripcion'    => $request->fecha_fin_inscripcion ?: null,
        ]);

        return redirect()->route('director.periodos.index')->with('success', 'Período actualizado.');
    }

    public function toggleActivo(int $id)
    {
        $periodo = DB::table('periodos_dictado')->where('id_periodo', $id)->first();
        if (!$periodo) abort(404);

        DB::table('periodos_dictado')->where('id_periodo', $id)
            ->update(['activo' => !$periodo->activo]);

        return redirect()->route('director.periodos.index')
            ->with('success', $periodo->activo ? 'Período desactivado.' : 'Período activado.');
    }

    public function storeLote(Request $request)
    {
        $request->validate([
            'nombre'                   => 'required|string|max:50',
            'fecha_inicio'             => 'required|date',
            'fecha_fin'                => 'required|date|after:fecha_inicio',
            'fecha_inicio_inscripcion' => 'nullable|date',
            'fecha_fin_inscripcion'    => 'nullable|date|after_or_equal:fecha_inicio_inscripcion',
            'carreras'                 => 'required|array|min:1',
            'carreras.*.id_carrera'    => 'required|integer|exists:carreras,id_carrera',
            'carreras.*.tipo_periodo'  => 'required|in:mensual,semestral,anual,intensivo',
            'carreras.*.fecha_inicio'  => 'nullable|date',
            'carreras.*.fecha_fin'     => 'nullable|date',
        ]);

        // Precarga max_materias de todas las carreras involucradas
        $ids = array_column($request->carreras, 'id_carrera');
        $maxPorCarrera = DB::table('carreras')
            ->whereIn('id_carrera', $ids)
            ->pluck('max_materias', 'id_carrera');

        $rows    = [];
        $omitidos = 0;

        foreach ($request->carreras as $item) {
            $fechaInicio = !empty($item['fecha_inicio']) ? $item['fecha_inicio'] : $request->fecha_inicio;
            $fechaFin    = !empty($item['fecha_fin'])    ? $item['fecha_fin']    : $request->fecha_fin;

            $yaExiste = DB::table('periodos_dictado')
                ->where('id_carrera', (int) $item['id_carrera'])
                ->where('nombre', $request->nombre)
                ->whereNull('id_nivel')
                ->exists();

            if ($yaExiste) {
                $omitidos++;
                continue;
            }

            $rows[] = [
                'id_carrera'               => (int) $item['id_carrera'],
                'id_nivel'                 => null,
                'nombre'                   => $request->nombre,
                'tipo_periodo'             => $item['tipo_periodo'],
                'fecha_inicio'             => $fechaInicio,
                'fecha_fin'                => $fechaFin,
                'fecha_inicio_inscripcion' => $request->fecha_inicio_inscripcion ?: null,
                'fecha_fin_inscripcion'    => $request->fecha_fin_inscripcion ?: null,
                'max_materias'             => $maxPorCarrera[(int) $item['id_carrera']] ?? 5,
                'activo'                   => true,
            ];
        }

        if (!empty($rows)) {
            DB::table('periodos_dictado')->insert($rows);
        }

        $n   = count($rows);
        $msg = "{$n} período(s) creados correctamente.";
        if ($omitidos > 0) $msg .= " {$omitidos} omitido(s) por duplicado.";

        return redirect()->route('director.periodos.index')->with('success', $msg);
    }

    public function clonarSiguienteAnio()
    {
        $anoActual    = (int) date('Y');
        $anoSiguiente = $anoActual + 1;

        $periodos = DB::table('periodos_dictado')
            ->whereNotNull('id_carrera')
            ->whereNull('id_nivel')
            ->where('activo', true)
            ->whereYear('fecha_inicio', $anoActual)
            ->select('id_periodo', 'id_carrera', 'nombre', 'tipo_periodo',
                     'fecha_inicio', 'fecha_fin', 'fecha_inicio_inscripcion', 'fecha_fin_inscripcion')
            ->get();

        if ($periodos->isEmpty()) {
            return redirect()->back()
                ->withErrors(['periodo' => "No hay períodos activos del año {$anoActual} para clonar."]);
        }

        // Precarga max_materias de carreras involucradas
        $carreraIds = $periodos->pluck('id_carrera')->unique()->values()->toArray();
        $maxPorCarrera = DB::table('carreras')
            ->whereIn('id_carrera', $carreraIds)
            ->pluck('max_materias', 'id_carrera');

        $rows        = [];
        $omitidos    = 0;
        $yaAgregados = [];

        foreach ($periodos as $p) {
            $nuevoNombre = preg_replace('/\b' . $anoActual . '\b/', (string) $anoSiguiente, $p->nombre);
            $clave       = $p->id_carrera . '|' . $nuevoNombre;

            if (isset($yaAgregados[$clave])) {
                $omitidos++;
                continue;
            }

            $yaExiste = DB::table('periodos_dictado')
                ->where('id_carrera', $p->id_carrera)
                ->where('nombre', $nuevoNombre)
                ->whereNull('id_nivel')
                ->exists();

            if ($yaExiste) {
                $omitidos++;
                continue;
            }

            $nuevaInicio = date('Y-m-d', strtotime($p->fecha_inicio . ' +1 year'));
            $nuevaFin    = date('Y-m-d', strtotime($p->fecha_fin    . ' +1 year'));

            $rows[] = [
                'id_carrera'               => $p->id_carrera,
                'id_nivel'                 => null,
                'nombre'                   => $nuevoNombre,
                'tipo_periodo'             => $p->tipo_periodo,
                'fecha_inicio'             => $nuevaInicio,
                'fecha_fin'                => $nuevaFin,
                'fecha_inicio_inscripcion' => null, // se define al crear el cronograma del año siguiente
                'fecha_fin_inscripcion'    => null,
                'max_materias'             => $maxPorCarrera[$p->id_carrera] ?? 5,
                'activo'                   => true,
            ];
            $yaAgregados[$clave] = true;
        }

        if (empty($rows) && $omitidos > 0) {
            return redirect()->back()
                ->with('success', "Los períodos del año {$anoSiguiente} ya existen ({$omitidos} omitidos).");
        }

        if (!empty($rows)) {
            DB::table('periodos_dictado')->insert($rows);
        }

        $creados = count($rows);
        $msg     = "{$creados} período(s) clonados para {$anoSiguiente}.";
        if ($omitidos > 0) $msg .= " {$omitidos} omitido(s) (ya existían).";

        return redirect()->route('director.periodos.index')->with('success', $msg);
    }

    public function destroy(int $id)
    {
        $tieneGrupos = DB::table('grupos')->where('id_periodo', $id)->exists();
        if ($tieneGrupos) {
            return redirect()->route('director.periodos.index')
                ->withErrors(['periodo' => 'No se puede eliminar: tiene grupos/oferta académica asociada.']);
        }

        $tieneCronogramas = DB::table('cronogramas')->where('id_periodo', $id)->exists();
        if ($tieneCronogramas) {
            return redirect()->route('director.periodos.index')
                ->withErrors(['periodo' => 'No se puede eliminar: tiene cronogramas asociados.']);
        }

        DB::table('periodos_dictado')->where('id_periodo', $id)->delete();
        return redirect()->route('director.periodos.index')->with('success', 'Período eliminado.');
    }
}
