<?php

namespace App\Http\Controllers\Propietario\CU2Aulas;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AulaController extends Controller
{
    public function index(Request $request)
    {
        $query = Aula::orderBy('nombre');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('nombre', 'ilike', "%$b%")
                  ->orWhere('ubicacion', 'ilike', "%$b%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activo') && $request->activo !== 'todos') {
            $query->whereRaw($request->activo === '1' ? 'activo IS TRUE' : 'activo IS FALSE');
        }

        $aulas = $query->paginate(10)->withQueryString();

        return Inertia::render('Propietario/CU2Aulas/Index', [
            'aulas'   => $aulas,
            'filtros' => $request->only(['buscar', 'tipo', 'activo']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'    => 'required|string|max:50|unique:aulas,nombre',
            'capacidad' => 'required|integer|min:1',
            'ubicacion' => 'nullable|string|max:100',
            'tipo'      => 'required|string|in:aula,laboratorio,sala,auditorio',
        ]);

        Aula::create([
            'nombre'    => $request->nombre,
            'capacidad' => $request->capacidad,
            'ubicacion' => $request->ubicacion,
            'tipo'      => $request->tipo,
            'activo'    => true,
        ]);

        return redirect()->back()->with('success', 'Aula registrada correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $aula = Aula::findOrFail($id);

        $request->validate([
            'nombre'    => ['required', 'string', 'max:50', Rule::unique('aulas', 'nombre')->ignore($id, 'id_aula')],
            'capacidad' => 'required|integer|min:1',
            'ubicacion' => 'nullable|string|max:100',
            'tipo'      => 'required|string|in:aula,laboratorio,sala,auditorio',
        ]);

        $aula->update([
            'nombre'    => $request->nombre,
            'capacidad' => $request->capacidad,
            'ubicacion' => $request->ubicacion,
            'tipo'      => $request->tipo,
        ]);

        return redirect()->back()->with('success', 'Aula actualizada correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $aula = Aula::findOrFail($id);
        $aula->update(['activo' => !$aula->activo]);

        $estado = $aula->activo ? 'activada' : 'desactivada';
        return redirect()->back()->with('success', "Aula $estado correctamente.");
    }
}
