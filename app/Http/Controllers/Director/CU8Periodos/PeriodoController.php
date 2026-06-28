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
        $carreraMap = [];

        // ── Períodos de carreras con niveles ─────────────────────────────────
        $rows = DB::table('periodos_dictado as pd')
            ->leftJoin('niveles_carrera as n', 'pd.id_nivel', '=', 'n.id_nivel')
            ->leftJoin('carreras as c', 'n.id_carrera', '=', 'c.id_carrera')
            ->whereNotNull('pd.id_nivel')
            ->orderBy('c.nombre')
            ->orderBy('n.numero_nivel')
            ->orderBy('pd.fecha_inicio')
            ->select(
                'c.id_carrera', 'c.nombre as carrera_nombre', 'c.tipo as carrera_tipo',
                'n.id_nivel', 'n.numero_nivel', 'n.nombre as nivel_nombre',
                'pd.id_periodo', 'pd.nombre', 'pd.tipo_periodo',
                'pd.fecha_inicio', 'pd.fecha_fin', 'pd.max_materias', 'pd.activo'
            )
            ->get();

        foreach ($rows as $r) {
            $cid = $r->id_carrera;
            if (!isset($carreraMap[$cid])) {
                $carreraMap[$cid] = [
                    'id_carrera'       => $cid,
                    'nombre'           => $r->carrera_nombre,
                    'tipo'             => $r->carrera_tipo,
                    'niveles'          => [],
                    'periodos_directos' => [],
                ];
            }
            $nid = $r->id_nivel;
            if ($nid && !isset($carreraMap[$cid]['niveles'][$nid])) {
                $carreraMap[$cid]['niveles'][$nid] = [
                    'id_nivel'     => $nid,
                    'numero_nivel' => $r->numero_nivel,
                    'nombre_nivel' => $r->nivel_nombre ?? "Año {$r->numero_nivel}",
                    'periodos'     => [],
                ];
            }
            if ($nid && $r->id_periodo) {
                $carreraMap[$cid]['niveles'][$nid]['periodos'][] = $this->mapPeriodo($r);
            }
        }

        // ── Períodos de cursos libres (id_carrera directo, sin nivel) ────────
        $cursoLibreRows = DB::table('periodos_dictado as pd')
            ->join('carreras as c', 'pd.id_carrera', '=', 'c.id_carrera')
            ->whereNull('pd.id_nivel')
            ->whereNotNull('pd.id_carrera')
            ->orderBy('c.nombre')
            ->orderBy('pd.fecha_inicio')
            ->select(
                'c.id_carrera', 'c.nombre as carrera_nombre', 'c.tipo as carrera_tipo',
                'pd.id_periodo', 'pd.nombre', 'pd.tipo_periodo',
                'pd.fecha_inicio', 'pd.fecha_fin', 'pd.max_materias', 'pd.activo'
            )
            ->get();

        foreach ($cursoLibreRows as $r) {
            $cid = $r->id_carrera;
            if (!isset($carreraMap[$cid])) {
                $carreraMap[$cid] = [
                    'id_carrera'       => $cid,
                    'nombre'           => $r->carrera_nombre,
                    'tipo'             => $r->carrera_tipo,
                    'niveles'          => [],
                    'periodos_directos' => [],
                ];
            }
            $carreraMap[$cid]['periodos_directos'][] = $this->mapPeriodo($r);
        }

        // ── Todas las carreras con niveles (para mostrar vacías) ─────────────
        $todasCarreras = DB::table('carreras as c')
            ->join('niveles_carrera as n', 'c.id_carrera', '=', 'n.id_carrera')
            ->orderBy('c.nombre')
            ->orderBy('n.numero_nivel')
            ->select('c.id_carrera', 'c.nombre as carrera_nombre', 'c.tipo as carrera_tipo',
                     'n.id_nivel', 'n.numero_nivel', 'n.nombre as nivel_nombre')
            ->get();

        foreach ($todasCarreras as $r) {
            $cid = $r->id_carrera;
            if (!isset($carreraMap[$cid])) {
                $carreraMap[$cid] = [
                    'id_carrera'       => $cid,
                    'nombre'           => $r->carrera_nombre,
                    'tipo'             => $r->carrera_tipo,
                    'niveles'          => [],
                    'periodos_directos' => [],
                ];
            }
            $nid = $r->id_nivel;
            if (!isset($carreraMap[$cid]['niveles'][$nid])) {
                $carreraMap[$cid]['niveles'][$nid] = [
                    'id_nivel'     => $nid,
                    'numero_nivel' => $r->numero_nivel,
                    'nombre_nivel' => $r->nivel_nombre ?? "Año {$r->numero_nivel}",
                    'periodos'     => [],
                ];
            }
        }

        // ── Cursos libres sin períodos aún ───────────────────────────────────
        $cursosLibres = DB::table('carreras')
            ->where('tipo', 'curso_libre')
            ->orderBy('nombre')
            ->get();

        foreach ($cursosLibres as $c) {
            if (!isset($carreraMap[$c->id_carrera])) {
                $carreraMap[$c->id_carrera] = [
                    'id_carrera'       => $c->id_carrera,
                    'nombre'           => $c->nombre,
                    'tipo'             => $c->tipo,
                    'niveles'          => [],
                    'periodos_directos' => [],
                ];
            }
        }

        // Flatten
        $carreras = array_values(array_map(function ($c) {
            $c['niveles'] = array_values($c['niveles']);
            return $c;
        }, $carreraMap));

        // Niveles para el selector del modal (solo carreras con niveles)
        $nivelesSelect = DB::table('niveles_carrera as n')
            ->join('carreras as c', 'n.id_carrera', '=', 'c.id_carrera')
            ->where('c.tipo', '!=', 'curso_libre')
            ->orderBy('c.nombre')
            ->orderBy('n.numero_nivel')
            ->select('n.id_nivel', 'n.numero_nivel', 'n.nombre',
                     'c.id_carrera', 'c.nombre as carrera_nombre')
            ->get();

        // ── Plantillas para "clonar año" ─────────────────────────────────────
        // Períodos existentes con sus id_nivel, para auto-rellenar el modal lote
        $periodosExistentes = DB::table('periodos_dictado as pd')
            ->join('niveles_carrera as n', 'pd.id_nivel', '=', 'n.id_nivel')
            ->join('carreras as c', 'n.id_carrera', '=', 'c.id_carrera')
            ->whereNotNull('pd.id_nivel')
            ->orderBy('pd.fecha_inicio', 'desc')
            ->select('pd.id_periodo', 'pd.nombre', 'pd.tipo_periodo', 'pd.max_materias',
                     'pd.fecha_inicio', 'pd.fecha_fin', 'pd.id_nivel',
                     'n.numero_nivel', 'c.nombre as carrera_nombre')
            ->get();

        // Agrupar por nombre + tipo + max para obtener "plantillas"
        $plantillasMap = [];
        foreach ($periodosExistentes as $r) {
            $key = $r->nombre . '||' . $r->tipo_periodo . '||' . $r->max_materias;
            if (!isset($plantillasMap[$key])) {
                $plantillasMap[$key] = [
                    'label'        => $r->nombre . ' (' . $r->tipo_periodo . ')',
                    'nombre'       => $r->nombre,
                    'tipo_periodo' => $r->tipo_periodo,
                    'max_materias' => $r->max_materias,
                    'fecha_inicio' => $r->fecha_inicio,
                    'fecha_fin'    => $r->fecha_fin,
                    'id_niveles'   => [],
                ];
            }
            $plantillasMap[$key]['id_niveles'][] = $r->id_nivel;
        }

        // ── Cronogramas de clases para auto-rellenar fechas ─────────────────────
        $cronogramasClases = DB::table('cronogramas')
            ->where('tipo_periodo', 'clases')
            ->where('activo', true)
            ->orderBy('fecha_inicio', 'desc')
            ->select('id_cronograma', 'nombre', 'modalidad', 'fecha_inicio', 'fecha_fin')
            ->get();

        return Inertia::render('Director/CU8Periodos/Index', [
            'carreras'          => $carreras,
            'nivelesSelect'     => $nivelesSelect,
            'plantillas'        => array_values($plantillasMap),
            'cronogramasClases' => $cronogramasClases,
        ]);
    }

    private function mapPeriodo($r): array
    {
        return [
            'id_periodo'   => $r->id_periodo,
            'nombre'       => $r->nombre,
            'tipo_periodo' => $r->tipo_periodo,
            'fecha_inicio' => $r->fecha_inicio,
            'fecha_fin'    => $r->fecha_fin,
            'max_materias' => $r->max_materias,
            'activo'       => $r->activo,
        ];
    }

    public function store(Request $request)
    {
        $esCursoLibre = $request->boolean('es_curso_libre');

        if ($esCursoLibre) {
            $request->validate([
                'id_carrera'   => 'required|integer|exists:carreras,id_carrera',
                'nombre'       => 'required|string|max:50',
                'tipo_periodo' => 'required|in:mensual,semestral,anual,intensivo',
                'fecha_inicio' => 'required|date',
                'fecha_fin'    => 'required|date|after:fecha_inicio',
                'max_materias' => 'required|integer|min:1|max:30',
            ]);

            // Cursos libres: máx 1 período activo
            $yaExiste = DB::table('periodos_dictado')
                ->where('id_carrera', $request->id_carrera)
                ->whereNull('id_nivel')
                ->exists();

            if ($yaExiste) {
                return redirect()->back()->withErrors([
                    'periodo' => 'Este curso libre ya tiene un período registrado.',
                ]);
            }

            DB::table('periodos_dictado')->insert([
                'id_carrera'   => $request->id_carrera,
                'id_nivel'     => null,
                'nombre'       => $request->nombre,
                'tipo_periodo' => $request->tipo_periodo,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'max_materias' => $request->max_materias,
                'activo'       => true,
            ]);
        } else {
            $request->validate([
                'id_nivel'     => 'required|integer|exists:niveles_carrera,id_nivel',
                'nombre'       => 'required|string|max:50',
                'tipo_periodo' => 'required|in:mensual,semestral,anual,intensivo',
                'fecha_inicio' => 'required|date',
                'fecha_fin'    => 'required|date|after:fecha_inicio',
                'max_materias' => 'required|integer|min:1|max:30',
            ]);

            DB::table('periodos_dictado')->insert([
                'id_nivel'     => $request->id_nivel,
                'id_carrera'   => null,
                'nombre'       => $request->nombre,
                'tipo_periodo' => $request->tipo_periodo,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'max_materias' => $request->max_materias,
                'activo'       => true,
            ]);
        }

        return redirect()->back()->with('success', 'Período registrado.');
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nombre'       => 'required|string|max:50',
            'tipo_periodo' => 'required|in:mensual,semestral,anual,intensivo',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
            'max_materias' => 'required|integer|min:1|max:30',
        ]);

        DB::table('periodos_dictado')->where('id_periodo', $id)->update([
            'nombre'       => $request->nombre,
            'tipo_periodo' => $request->tipo_periodo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'max_materias' => $request->max_materias,
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
            'nombre'                 => 'required|string|max:50',
            'fecha_inicio'           => 'required|date',
            'fecha_fin'              => 'required|date|after:fecha_inicio',
            'max_materias'           => 'required|integer|min:1|max:30',
            'niveles'                => 'required|array|min:1',
            'niveles.*.id_nivel'     => 'required|integer|exists:niveles_carrera,id_nivel',
            'niveles.*.tipo_periodo' => 'required|in:mensual,semestral,anual,intensivo',
            'niveles.*.fecha_inicio' => 'nullable|date',
            'niveles.*.fecha_fin'    => 'nullable|date',
        ]);

        $rows = [];
        foreach ($request->niveles as $item) {
            $fechaInicio = !empty($item['fecha_inicio']) ? $item['fecha_inicio'] : $request->fecha_inicio;
            $fechaFin    = !empty($item['fecha_fin'])    ? $item['fecha_fin']    : $request->fecha_fin;
            $rows[] = [
                'id_nivel'     => (int) $item['id_nivel'],
                'id_carrera'   => null,
                'nombre'       => $request->nombre,
                'tipo_periodo' => $item['tipo_periodo'],
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
                'max_materias' => $request->max_materias,
                'activo'       => true,
            ];
        }

        DB::table('periodos_dictado')->insert($rows);
        $n = count($rows);

        return redirect()->route('director.periodos.index')
            ->with('success', "{$n} período(s) creados correctamente.");
    }

    public function clonarSiguienteAnio()
    {
        $anoActual    = (int) date('Y');
        $anoSiguiente = $anoActual + 1;

        $periodos = DB::table('periodos_dictado')
            ->whereNotNull('id_nivel')
            ->where('activo', true)
            ->whereYear('fecha_inicio', $anoActual)
            ->get();

        if ($periodos->isEmpty()) {
            return redirect()->back()
                ->withErrors(['periodo' => "No hay períodos activos del año {$anoActual} para clonar."]);
        }

        $rows     = [];
        $omitidos = 0;

        foreach ($periodos as $p) {
            $yaExiste = DB::table('periodos_dictado')
                ->where('id_nivel', $p->id_nivel)
                ->whereYear('fecha_inicio', $anoSiguiente)
                ->exists();

            if ($yaExiste) {
                $omitidos++;
                continue;
            }

            $nuevoNombre = preg_replace('/\b' . $anoActual . '\b/', (string) $anoSiguiente, $p->nombre);
            $nuevaInicio = date('Y-m-d', strtotime($p->fecha_inicio . ' +1 year'));
            $nuevaFin    = date('Y-m-d', strtotime($p->fecha_fin    . ' +1 year'));

            $rows[] = [
                'id_nivel'     => $p->id_nivel,
                'id_carrera'   => null,
                'nombre'       => $nuevoNombre,
                'tipo_periodo' => $p->tipo_periodo,
                'fecha_inicio' => $nuevaInicio,
                'fecha_fin'    => $nuevaFin,
                'max_materias' => $p->max_materias,
                'activo'       => true,
            ];
        }

        if (empty($rows) && $omitidos > 0) {
            return redirect()->back()
                ->with('success', "Los períodos del año {$anoSiguiente} ya existen ({$omitidos} omitidos).");
        }

        if (!empty($rows)) {
            DB::table('periodos_dictado')->insert($rows);
        }

        $creados = count($rows);
        $msg = "{$creados} período(s) clonados para {$anoSiguiente}.";
        if ($omitidos > 0) {
            $msg .= " {$omitidos} omitido(s) (ya existían).";
        }

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
