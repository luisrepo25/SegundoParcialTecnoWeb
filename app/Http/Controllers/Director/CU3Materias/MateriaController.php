<?php

namespace App\Http\Controllers\Director\CU3Materias;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MateriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Materia::with('requisito')->orderBy('nombre');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('nombre', 'ilike', "%$b%")
                  ->orWhere('codigo', 'ilike', "%$b%");
            });
        }

        if ($request->filled('activo') && $request->activo !== 'todos') {
            $query->whereRaw($request->activo === '1' ? 'activo IS TRUE' : 'activo IS FALSE');
        }

        $materias        = $query->paginate(10)->withQueryString();
        $todasLasMaterias = Materia::orderBy('nombre')->get(['id_materia', 'codigo', 'nombre']);

        return Inertia::render('Director/CU3Materias/Index', [
            'materias'         => $materias,
            'todasLasMaterias' => $todasLasMaterias,
            'filtros'          => $request->only(['buscar', 'activo']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo'               => 'required|string|max:20|unique:materias,codigo',
            'nombre'               => 'required|string|max:150',
            'duracion_meses'       => 'required|integer|min:1',
            'costo_mensual'        => 'required|numeric|min:0',
            'creditos'             => 'nullable|integer|min:0',
            'id_materia_requisito' => 'nullable|integer|exists:materias,id_materia',
        ]);

        Materia::create([
            'codigo'               => strtoupper($request->codigo),
            'nombre'               => $request->nombre,
            'duracion_meses'       => $request->duracion_meses,
            'costo_mensual'        => $request->costo_mensual,
            'creditos'             => $request->creditos,
            'id_materia_requisito' => $request->id_materia_requisito ?: null,
            'activo'               => true,
        ]);

        return redirect()->back()->with('success', 'Materia registrada correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $materia = Materia::findOrFail($id);

        $request->validate([
            'codigo'               => ['required', 'string', 'max:20', Rule::unique('materias', 'codigo')->ignore($id, 'id_materia')],
            'nombre'               => 'required|string|max:150',
            'duracion_meses'       => 'required|integer|min:1',
            'costo_mensual'        => 'required|numeric|min:0',
            'creditos'             => 'nullable|integer|min:0',
            'id_materia_requisito' => ['nullable', 'integer', 'exists:materias,id_materia', Rule::notIn([$id])],
        ]);

        $materia->update([
            'codigo'               => strtoupper($request->codigo),
            'nombre'               => $request->nombre,
            'duracion_meses'       => $request->duracion_meses,
            'costo_mensual'        => $request->costo_mensual,
            'creditos'             => $request->creditos,
            'id_materia_requisito' => $request->id_materia_requisito ?: null,
        ]);

        return redirect()->back()->with('success', 'Materia actualizada correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $materia = Materia::findOrFail($id);
        $materia->update(['activo' => !$materia->activo]);

        $estado = $materia->activo ? 'activada' : 'desactivada';
        return redirect()->back()->with('success', "Materia $estado correctamente.");
    }

    public function porCarrera(int $id)
    {
        $carrera = Carrera::findOrFail($id);

        $materiasDisponibles = Materia::where('activo', true)
            ->orderBy('nombre')
            ->get(['id_materia', 'codigo', 'nombre', 'id_materia_requisito']);

        $selectMalla = [
            'm.id_materia', 'm.codigo', 'm.nombre',
            'm.duracion_meses', 'm.costo_mensual', 'm.creditos',
            'm.id_materia_requisito',
            DB::raw('req.nombre as nombre_requisito'),
            DB::raw('req.codigo as codigo_requisito'),
            'mc.id_malla', 'mc.id_nivel', 'mc.orden_en_nivel', 'mc.obligatoria',
        ];

        // â”€â”€ Curso libre: lista plana sin niveles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ($carrera->tipo === 'curso_libre') {
            $materiasLibres = DB::table('malla_curricular as mc')
                ->join('materias as m', 'mc.id_materia', '=', 'm.id_materia')
                ->leftJoin('materias as req', 'm.id_materia_requisito', '=', 'req.id_materia')
                ->where('mc.id_carrera', $id)
                ->whereNull('mc.id_nivel')
                ->orderByRaw('mc.orden_en_nivel NULLS LAST, mc.id_malla')
                ->select($selectMalla)
                ->get();

            return Inertia::render('Director/CU3Materias/PorCarrera', [
                'carrera'             => $carrera,
                'porNivel'            => [],
                'materiasLibres'      => $materiasLibres,
                'materiasDisponibles' => $materiasDisponibles,
            ]);
        }

        // â”€â”€ Carrera con niveles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $niveles = DB::table('niveles_carrera')
            ->where('id_carrera', $id)
            ->orderBy('numero_nivel')
            ->get();

        $mallaPorNivel = DB::table('malla_curricular as mc')
            ->join('materias as m', 'mc.id_materia', '=', 'm.id_materia')
            ->leftJoin('materias as req', 'm.id_materia_requisito', '=', 'req.id_materia')
            ->where('mc.id_carrera', $id)
            ->whereNotNull('mc.id_nivel')
            ->orderByRaw('mc.orden_en_nivel NULLS LAST, mc.id_malla')
            ->select($selectMalla)
            ->get()
            ->groupBy('id_nivel');

        $porNivel = $niveles->map(function ($nivel) use ($mallaPorNivel) {
            return [
                'id_nivel'     => $nivel->id_nivel,
                'numero_nivel' => $nivel->numero_nivel,
                'nombre_nivel' => $nivel->nombre ?? "Nivel {$nivel->numero_nivel}",
                'descripcion'  => $nivel->descripcion,
                'materias'     => ($mallaPorNivel[$nivel->id_nivel] ?? collect())->values(),
            ];
        })->values();

        return Inertia::render('Director/CU3Materias/PorCarrera', [
            'carrera'             => $carrera,
            'porNivel'            => $porNivel,
            'materiasLibres'      => [],
            'materiasDisponibles' => $materiasDisponibles,
        ]);
    }
}

