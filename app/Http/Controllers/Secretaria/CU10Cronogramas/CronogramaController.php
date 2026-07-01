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
            'nombre.required'          => 'El nombre del cronograma es obligatorio.',
            'tipo_periodo.in'          => 'El tipo de período no es válido.',
            'fecha_inicio.required'    => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'        => 'La fecha de inicio no es válida.',
            'fecha_fin.required'       => 'La fecha de fin es obligatoria.',
            'fecha_fin.date'           => 'La fecha de fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'modalidad.in'             => 'La modalidad seleccionada no es válida.',
        ]);

        DB::table('cronogramas')->insert([
            'nombre'       => $request->nombre,
            'tipo_periodo' => $request->tipo_periodo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'modalidad'    => $request->modalidad ?: null,
            'activo'       => true,
        ]);

        $this->sincronizarPeriodos($request->tipo_periodo, $request->fecha_inicio, $request->fecha_fin, $request->modalidad);

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
            'nombre.required'          => 'El nombre del cronograma es obligatorio.',
            'tipo_periodo.in'          => 'El tipo de período no es válido.',
            'fecha_inicio.required'    => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'        => 'La fecha de inicio no es válida.',
            'fecha_fin.required'       => 'La fecha de fin es obligatoria.',
            'fecha_fin.date'           => 'La fecha de fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'modalidad.in'             => 'La modalidad seleccionada no es válida.',
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

        $this->sincronizarPeriodos($request->tipo_periodo, $request->fecha_inicio, $request->fecha_fin, $request->modalidad);

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

    /**
     * Al crear/actualizar un cronograma de clases o inscripción,
     * auto-rellena los periodos_dictado del mismo año que aún tienen NULL en esos campos.
     * Solo afecta períodos cuya carrera tenga la misma modalidad (o si el cronograma es global).
     */
    /**
     * Al crear/actualizar un cronograma de clases o inscripción,
     * sincroniza automáticamente los periodos_dictado del mismo año y modalidad.
     *
     * Clases: actualiza períodos cuya fecha_inicio actual cae dentro del rango del cronograma
     *   (±14 días en el inicio para tolerar diferencias de clone vs fechas reales).
     *   Identifica correctamente Sem 1 vs Sem 2 porque los clones tienen fechas aproximadas.
     *
     * Inscripción: solo actualiza períodos que aún tienen NULL en fecha_inicio_inscripcion,
     *   cuyo año coincide. Se identifica el semestre porque Sem 1 empieza en la 1ra mitad
     *   del año y Sem 2 en la 2da; se usa el rango del cronograma para filtrar.
     */
    private function sincronizarPeriodos(string $tipo, string $fechaInicio, string $fechaFin, ?string $modalidad): void
    {
        $carrerasQuery = DB::table('carreras')->whereNotNull('id_carrera');
        if ($modalidad) {
            $carrerasQuery->where('modalidad', $modalidad);
        }
        $idCarreras = $carrerasQuery->pluck('id_carrera');

        if ($idCarreras->isEmpty()) return;

        if ($tipo === 'clases') {
            // Actualiza períodos cuya fecha_inicio (clonada o previa) cae dentro del rango
            // del cronograma (con tolerancia de 14 días al inicio para manejar desfases de clone).
            // Esto identifica Sem 1 vs Sem 2 sin ambigüedad.
            DB::table('periodos_dictado')
                ->whereNotNull('id_carrera')
                ->whereNull('id_nivel')
                ->whereIn('id_carrera', $idCarreras)
                ->whereNotNull('fecha_inicio')
                ->whereRaw(
                    "fecha_inicio BETWEEN (? ::date - INTERVAL '14 days') AND ? ::date",
                    [$fechaInicio, $fechaFin]
                )
                ->update([
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin'    => $fechaFin,
                ]);

        } elseif ($tipo === 'inscripcion') {
            $anio = (int) date('Y', strtotime($fechaInicio));

            // Actualiza períodos del año que aún no tienen fecha de inscripción.
            // Para distinguir Sem 1 vs Sem 2, filtra por períodos cuya fecha_inicio
            // caiga en el mismo rango del cronograma (con tolerancia de 60 días post-fin
            // para cubrir ventanas de inscripción que terminan antes que inicien clases).
            DB::table('periodos_dictado')
                ->whereNotNull('id_carrera')
                ->whereNull('id_nivel')
                ->whereIn('id_carrera', $idCarreras)
                ->whereNull('fecha_inicio_inscripcion')
                ->whereNotNull('fecha_inicio')
                ->whereRaw(
                    "EXTRACT(YEAR FROM fecha_inicio) = ?
                     AND fecha_inicio BETWEEN (? ::date - INTERVAL '14 days') AND (? ::date + INTERVAL '60 days')",
                    [$anio, $fechaInicio, $fechaFin]
                )
                ->update([
                    'fecha_inicio_inscripcion' => $fechaInicio,
                    'fecha_fin_inscripcion'    => $fechaFin,
                ]);
        }
    }

    public function destroy(int $id)
    {
        Cronograma::findOrFail($id);
        DB::reconnect();
        DB::table('cronogramas')->where('id_cronograma', $id)->delete();

        return redirect()->back()->with('success', 'Cronograma eliminado correctamente.');
    }
}
