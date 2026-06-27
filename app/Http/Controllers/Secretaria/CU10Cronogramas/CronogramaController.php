<?php

namespace App\Http\Controllers\Secretaria\CU10Cronogramas;

use App\Http\Controllers\Controller;
use App\Models\Cronograma;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CronogramaController extends Controller
{
    public function index(Request $request)
    {
        $query = Cronograma::with('carrera')->orderBy('fecha_inicio', 'desc');

        if ($request->filled('buscar')) {
            $query->where('nombre', 'ilike', '%' . $request->buscar . '%');
        }

        if ($request->filled('fase') && $request->fase !== 'todas') {
            $now = now()->startOfDay()->toDateString();
            
            switch ($request->fase) {
                case 'abierta':
                    $query->where('activo', 'true')
                          ->whereDate('fecha_inicio', '<=', $now)
                          ->whereDate('fecha_fin', '>=', $now);
                    break;
                case 'proxima':
                    $query->where('activo', 'true')
                          ->whereDate('fecha_inicio', '>', $now);
                    break;
                case 'cerrada':
                    $query->where('activo', 'true')
                          ->whereDate('fecha_fin', '<', $now);
                    break;
                case 'inactiva':
                    $query->where('activo', 'false');
                    break;
            }
        }

        $cronogramas = $query->paginate(10)->withQueryString();
        $carreras = Carrera::where('activo', 'true')->get();

        return Inertia::render('Secretaria/CU10Cronogramas/Index', [
            'cronogramas' => $cronogramas,
            'carreras'    => $carreras,
            'filtros'     => $request->only(['buscar', 'fase']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'       => 'required|string|max:50',
            'tipo_periodo' => 'required|string|max:20',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'id_carrera'   => 'nullable|exists:carreras,id_carrera',
            'activo'       => 'true',
        ]);

        Cronograma::create([
            'nombre'       => $request->nombre,
            'tipo_periodo' => $request->tipo_periodo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'id_carrera'   => $request->id_carrera,
            'activo'       => 'true', // Usar string para PgBouncer
        ]);

        return redirect()->back()->with('success', 'Cronograma registrado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $cronograma = Cronograma::findOrFail($id);
        
        // Bypass Eloquent $casts usando DB::table para mandar el string 'true'/'false' puro a Postgres
        $nuevoEstado = !$cronograma->activo ? 'true' : 'false';
        \Illuminate\Support\Facades\DB::table('cronogramas')
            ->where('id_cronograma', $id)
            ->update(['activo' => $nuevoEstado]);

        $estado = $cronograma->activo ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Cronograma $estado correctamente.");
    }
}
