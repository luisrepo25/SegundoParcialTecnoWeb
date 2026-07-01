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
        $esCurso = $request->tipo === 'curso_libre';

        $request->validate([
            'codigo'                 => ['required','string','max:20','unique:carreras,codigo','regex:/^[a-zA-Z0-9\-_]+$/'],
            'nombre'                 => 'required|string|max:150',
            'descripcion'            => 'nullable|string',
            'tipo'                   => 'required|string|in:tecnico,tecnico_superior,curso_libre',
            'modalidad'              => $esCurso ? 'nullable' : 'required|string|in:anual,semestral,mensual',
            'duracion_unidad'        => 'nullable|string|in:anos,meses',
            'max_materias'           => 'required|integer|min:1|max:30',
            'duracion_niveles'       => 'required|integer|min:1|max:' . ($esCurso ? '60' : '20'),
            'costo_carrera_completa' => 'nullable|numeric|min:0',
        ], [
            'codigo.required'              => 'El código es obligatorio.',
            'codigo.unique'                => 'Ya existe una carrera con ese código.',
            'codigo.regex'                 => 'El código solo debe contener letras, números, guiones y guiones bajos.',
            'nombre.required'              => 'El nombre de la carrera es obligatorio.',
            'modalidad.required'           => 'La modalidad es obligatoria.',
            'max_materias.required'        => 'Las materias por período son obligatorias.',
            'max_materias.min'             => 'Debe haber al menos 1 materia por período.',
            'max_materias.max'             => 'No puede superar 30 materias por período.',
            'duracion_niveles.required'    => 'La duración es obligatoria.',
            'duracion_niveles.integer'     => 'La duración debe ser un número entero.',
            'duracion_niveles.min'         => 'La duración debe ser al menos 1.',
            'costo_carrera_completa.numeric' => 'El costo debe ser un número válido (ej: 15000 o 15000.50).',
            'costo_carrera_completa.min'     => 'El costo no puede ser negativo.',
        ]);

        Carrera::create([
            'codigo'                 => strtoupper($request->codigo),
            'nombre'                 => $request->nombre,
            'descripcion'            => $request->descripcion,
            'tipo'                   => $request->tipo,
            'modalidad'              => $esCurso ? null : ($request->modalidad ?: null),
            'duracion_unidad'        => $esCurso ? 'meses' : 'anos',
            'max_materias'           => $request->max_materias,
            'duracion_niveles'       => $request->duracion_niveles,
            'costo_carrera_completa' => $request->costo_carrera_completa,
            'activo'                 => true,
        ]);

        return redirect()->back()->with('success', 'Carrera registrada correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $carrera  = Carrera::findOrFail($id);
        $esCurso  = $request->tipo === 'curso_libre';

        $request->validate([
            'codigo'                 => ['required','string','max:20',Rule::unique('carreras','codigo')->ignore($id,'id_carrera'),'regex:/^[a-zA-Z0-9\-_]+$/'],
            'nombre'                 => 'required|string|max:150',
            'descripcion'            => 'nullable|string',
            'tipo'                   => 'required|string|in:tecnico,tecnico_superior,curso_libre',
            'modalidad'              => $esCurso ? 'nullable' : 'required|string|in:anual,semestral,mensual',
            'duracion_unidad'        => 'nullable|string|in:anos,meses',
            'max_materias'           => 'required|integer|min:1|max:30',
            'duracion_niveles'       => 'required|integer|min:1|max:' . ($esCurso ? '60' : '20'),
            'costo_carrera_completa' => 'nullable|numeric|min:0',
        ], [
            'codigo.required'              => 'El código es obligatorio.',
            'codigo.unique'                => 'Ya existe una carrera con ese código.',
            'codigo.regex'                 => 'El código solo debe contener letras, números, guiones y guiones bajos.',
            'nombre.required'              => 'El nombre de la carrera es obligatorio.',
            'modalidad.required'           => 'La modalidad es obligatoria.',
            'max_materias.required'        => 'Las materias por período son obligatorias.',
            'max_materias.min'             => 'Debe haber al menos 1 materia por período.',
            'max_materias.max'             => 'No puede superar 30 materias por período.',
            'duracion_niveles.required'    => 'La duración es obligatoria.',
            'duracion_niveles.integer'     => 'La duración debe ser un número entero.',
            'duracion_niveles.min'         => 'La duración debe ser al menos 1.',
            'costo_carrera_completa.numeric' => 'El costo debe ser un número válido (ej: 15000 o 15000.50).',
            'costo_carrera_completa.min'     => 'El costo no puede ser negativo.',
        ]);

        $carrera->update([
            'codigo'                 => strtoupper($request->codigo),
            'nombre'                 => $request->nombre,
            'descripcion'            => $request->descripcion,
            'tipo'                   => $request->tipo,
            'modalidad'              => $esCurso ? null : ($request->modalidad ?: null),
            'duracion_unidad'        => $esCurso ? 'meses' : 'anos',
            'max_materias'           => $request->max_materias,
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
