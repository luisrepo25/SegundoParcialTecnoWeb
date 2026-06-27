<?php

namespace App\Http\Controllers\Director\CU4Carreras;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CarreraController extends Controller
{
    public function index(Request $request)
    {
        $query = Carrera::orderBy('nombre');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('nombre', 'ilike', "%$b%")
                  ->orWhere('codigo', 'ilike', "%$b%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activo') && $request->activo !== 'todos') {
            $query->whereRaw($request->activo === '1' ? 'activo IS TRUE' : 'activo IS FALSE');
        }

        $carreras = $query->paginate(10)->withQueryString();

        return Inertia::render('Director/CU4Carreras/Index', [
            'carreras' => $carreras,
            'filtros'  => $request->only(['buscar', 'tipo', 'activo']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo'                 => 'required|string|max:20|unique:carreras,codigo',
            'nombre'                 => 'required|string|max:150',
            'descripcion'            => 'nullable|string',
            'tipo'                   => 'required|string|in:tecnico,tecnico_superior,curso_libre',
            'duracion_niveles'       => 'required|integer|min:1',
            'costo_carrera_completa' => 'nullable|numeric|min:0',
        ]);

        Carrera::create([
            'codigo'                 => strtoupper($request->codigo),
            'nombre'                 => $request->nombre,
            'descripcion'            => $request->descripcion,
            'tipo'                   => $request->tipo,
            'duracion_niveles'       => $request->duracion_niveles,
            'costo_carrera_completa' => $request->costo_carrera_completa,
            'activo'                 => true,
        ]);

        return redirect()->back()->with('success', 'Carrera registrada correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $carrera = Carrera::findOrFail($id);

        $request->validate([
            'codigo'                 => ['required', 'string', 'max:20', Rule::unique('carreras', 'codigo')->ignore($id, 'id_carrera')],
            'nombre'                 => 'required|string|max:150',
            'descripcion'            => 'nullable|string',
            'tipo'                   => 'required|string|in:tecnico,tecnico_superior,curso_libre',
            'duracion_niveles'       => 'required|integer|min:1',
            'costo_carrera_completa' => 'nullable|numeric|min:0',
        ]);

        $carrera->update([
            'codigo'                 => strtoupper($request->codigo),
            'nombre'                 => $request->nombre,
            'descripcion'            => $request->descripcion,
            'tipo'                   => $request->tipo,
            'duracion_niveles'       => $request->duracion_niveles,
            'costo_carrera_completa' => $request->costo_carrera_completa,
        ]);

        return redirect()->back()->with('success', 'Carrera actualizada correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $carrera = Carrera::findOrFail($id);
        $carrera->update(['activo' => !$carrera->activo]);

        $estado = $carrera->activo ? 'activada' : 'desactivada';
        return redirect()->back()->with('success', "Carrera $estado correctamente.");
    }
}
