<?php

namespace App\Http\Controllers\Director\CU5Materias;

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
            $query->where('activo', $request->activo === '1');
        }

        $materias        = $query->paginate(10)->withQueryString();
        $todasLasMaterias = Materia::orderBy('nombre')->get(['id_materia', 'codigo', 'nombre']);

        return Inertia::render('Director/CU5Materias/Index', [
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

        $filas = DB::table('malla_curricular as mc')
            ->join('materias as m', 'mc.id_materia', '=', 'm.id_materia')
            ->leftJoin('niveles_carrera as nc', 'mc.id_nivel', '=', 'nc.id_nivel')
            ->where('mc.id_carrera', $id)
            ->orderBy('nc.numero_nivel')
            ->orderByRaw('mc.orden_en_nivel NULLS LAST')
            ->select(
                'm.id_materia', 'm.codigo', 'm.nombre',
                'm.duracion_meses', 'm.costo_mensual', 'm.creditos', 'm.activo',
                'mc.id_malla', 'mc.orden_en_nivel', 'mc.obligatoria',
                'nc.numero_nivel', 'nc.nombre as nombre_nivel'
            )
            ->get();

        // Agrupar por nivel
        $porNivel = $filas->groupBy('numero_nivel')->map(function ($items, $nivel) {
            return [
                'numero_nivel' => $nivel,
                'nombre_nivel' => $items->first()->nombre_nivel ?? "Nivel $nivel",
                'materias'     => $items->values(),
            ];
        })->values();

        return Inertia::render('Director/CU5Materias/PorCarrera', [
            'carrera'  => $carrera,
            'porNivel' => $porNivel,
        ]);
    }
}
