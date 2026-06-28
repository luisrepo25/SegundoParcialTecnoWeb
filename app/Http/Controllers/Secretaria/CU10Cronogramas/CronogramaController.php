<?php

namespace App\Http\Controllers\Secretaria\CU10Cronogramas;

use App\Http\Controllers\Controller;
use App\Models\Cronograma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CronogramaController extends Controller
{
    private const MODALIDADES = ['mensual', 'semestral', 'anual', 'intensivo'];

    public function index(Request $request)
    {
        $query = Cronograma::orderBy('fecha_inicio', 'desc');

        if ($request->filled('buscar')) {
            $query->where('nombre', 'ilike', '%' . $request->buscar . '%');
        }

        if ($request->filled('fase') && $request->fase !== 'todas') {
            $now = now()->startOfDay()->toDateString();
            match ($request->fase) {
                'abierta'  => $query->where('activo', true)->whereDate('fecha_inicio', '<=', $now)->whereDate('fecha_fin', '>=', $now),
                'proxima'  => $query->where('activo', true)->whereDate('fecha_inicio', '>', $now),
                'cerrada'  => $query->where('activo', true)->whereDate('fecha_fin', '<', $now),
                'inactiva' => $query->where('activo', false),
                default    => null,
            };
        }

        if ($request->filled('modalidad') && $request->modalidad !== 'todas') {
            if ($request->modalidad === 'global') {
                $query->whereNull('modalidad');
            } else {
                $query->where('modalidad', $request->modalidad);
            }
        }

        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->where('tipo_periodo', $request->tipo);
        }

        return Inertia::render('Secretaria/CU10Cronogramas/Index', [
            'cronogramas' => $query->get(),
            'filtros'     => $request->only(['buscar', 'fase', 'modalidad', 'tipo']),
        ]);
    }

    public function store(Request $request)
    {
        // Reconectar antes de validate para evitar colgar con PgBouncer en estado abortado
        DB::reconnect();

        $request->validate([
            'nombre'       => 'required|string|max:100',
            'tipo_periodo' => 'required|in:inscripcion,clases,examenes,receso',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'modalidad'    => 'nullable|in:mensual,semestral,anual,intensivo',
        ], [
            'tipo_periodo.in' => 'El tipo de período no es válido.',
            'modalidad.in'    => 'La modalidad seleccionada no es válida.',
        ]);

        DB::table('cronogramas')->insert([
            'nombre'       => $request->nombre,
            'tipo_periodo' => $request->tipo_periodo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'modalidad'    => $request->modalidad ?: null,
            'activo'       => true,
        ]);

        return redirect()->back()->with('success', 'Cronograma creado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        DB::reconnect();

        $request->validate([
            'nombre'       => 'required|string|max:100',
            'tipo_periodo' => 'required|in:inscripcion,clases,examenes,receso',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'modalidad'    => 'nullable|in:mensual,semestral,anual,intensivo',
        ], [
            'tipo_periodo.in' => 'El tipo de período no es válido.',
            'modalidad.in'    => 'La modalidad seleccionada no es válida.',
        ]);

        Cronograma::findOrFail($id);

        DB::reconnect();
        DB::table('cronogramas')
            ->where('id_cronograma', $id)
            ->update([
                'nombre'       => $request->nombre,
                'tipo_periodo' => $request->tipo_periodo,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'modalidad'    => $request->modalidad ?: null,
            ]);

        return redirect()->back()->with('success', 'Cronograma actualizado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $cronograma  = Cronograma::findOrFail($id);
        $nuevoEstado = $cronograma->activo ? 'false' : 'true';

        DB::reconnect();
        DB::table('cronogramas')
            ->where('id_cronograma', $id)
            ->update(['activo' => $nuevoEstado]);

        $estado = $cronograma->activo ? 'desactivado' : 'activado';
        return redirect()->back()->with('success', "Cronograma $estado correctamente.");
    }

    public function destroy(int $id)
    {
        Cronograma::findOrFail($id);
        DB::reconnect();
        DB::table('cronogramas')->where('id_cronograma', $id)->delete();

        return redirect()->back()->with('success', 'Cronograma eliminado correctamente.');
    }
}
