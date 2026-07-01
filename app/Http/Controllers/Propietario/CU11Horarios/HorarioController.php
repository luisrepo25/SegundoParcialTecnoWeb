<?php

namespace App\Http\Controllers\Propietario\CU11Horarios;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HorarioController extends Controller
{
    const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

    public function index(Request $request)
    {
        $query = Horario::orderByRaw("
            CASE dia_semana
                WHEN 'lunes'     THEN 1
                WHEN 'martes'    THEN 2
                WHEN 'miercoles' THEN 3
                WHEN 'jueves'    THEN 4
                WHEN 'viernes'   THEN 5
                WHEN 'sabado'    THEN 6
                WHEN 'domingo'   THEN 7
                ELSE 8
            END
        ")->orderBy('hora_inicio');

        if ($request->filled('dia')) {
            $query->where('dia_semana', $request->dia);
        }

        if ($request->filled('activo') && $request->activo !== 'todos') {
            $query->whereRaw($request->activo === '1' ? 'activo IS TRUE' : 'activo IS FALSE');
        }

        $horarios = $query->paginate(10)->withQueryString();

        return Inertia::render('Propietario/CU11Horarios/Index', [
            'horarios' => $horarios,
            'dias'     => self::DIAS,
            'filtros'  => $request->only(['dia', 'activo']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dia_semana'  => 'required|string|in:' . implode(',', self::DIAS),
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
        ], [
            'dia_semana.required'       => 'El día de la semana es obligatorio.',
            'dia_semana.in'             => 'El día seleccionado no es válido.',
            'hora_inicio.required'      => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format'   => 'La hora de inicio debe tener formato HH:MM (ej: 08:00).',
            'hora_fin.required'         => 'La hora de fin es obligatoria.',
            'hora_fin.date_format'      => 'La hora de fin debe tener formato HH:MM (ej: 10:00).',
            'hora_fin.after'            => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        // Evitar duplicados exactos
        $existe = Horario::where('dia_semana', $request->dia_semana)
            ->where('hora_inicio', $request->hora_inicio)
            ->where('hora_fin', $request->hora_fin)
            ->exists();

        if ($existe) {
            return redirect()->back()->withErrors(['hora_inicio' => 'Ya existe un horario idéntico para ese día.']);
        }

        Horario::create([
            'dia_semana'  => $request->dia_semana,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin'    => $request->hora_fin,
            'activo'      => true,
        ]);

        return redirect()->back()->with('success', 'Horario registrado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $horario = Horario::findOrFail($id);

        $request->validate([
            'dia_semana'  => 'required|string|in:' . implode(',', self::DIAS),
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
        ], [
            'dia_semana.required'       => 'El día de la semana es obligatorio.',
            'dia_semana.in'             => 'El día seleccionado no es válido.',
            'hora_inicio.required'      => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format'   => 'La hora de inicio debe tener formato HH:MM (ej: 08:00).',
            'hora_fin.required'         => 'La hora de fin es obligatoria.',
            'hora_fin.date_format'      => 'La hora de fin debe tener formato HH:MM (ej: 10:00).',
            'hora_fin.after'            => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        $horario->update([
            'dia_semana'  => $request->dia_semana,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin'    => $request->hora_fin,
        ]);

        return redirect()->back()->with('success', 'Horario actualizado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $horario = Horario::findOrFail($id);
        $horario->update(['activo' => !$horario->activo]);

        $estado = $horario->activo ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Horario $estado correctamente.");
    }
}
