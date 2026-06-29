<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Para cada período con id_nivel, resolver su id_carrera ─────────
        // Agrupamos por (id_carrera, nombre): si hay varios períodos con el mismo
        // nombre para la misma carrera (uno por nivel), conservamos el de menor id.

        $nivelPeriodos = DB::table('periodos_dictado as pd')
            ->join('niveles_carrera as n', 'pd.id_nivel', '=', 'n.id_nivel')
            ->whereNotNull('pd.id_nivel')
            ->orderBy('pd.id_periodo')
            ->select('pd.id_periodo', 'pd.nombre', 'n.id_carrera')
            ->get();

        // Map: (id_carrera, nombre) → survivor id_periodo
        $survivorMap = [];  // key = "carrera|nombre" → survivor_id
        $duplicates  = []; // id_periodo → survivor_id (for grupos re-link)

        foreach ($nivelPeriodos as $row) {
            $key = $row->id_carrera . '|' . $row->nombre;
            if (!isset($survivorMap[$key])) {
                $survivorMap[$key] = $row->id_periodo;
            } else {
                $duplicates[$row->id_periodo] = $survivorMap[$key];
            }
        }

        // ── 2. Re-linkar grupos de períodos duplicados al survivor ────────────
        foreach ($duplicates as $oldId => $survivorId) {
            DB::table('grupos')->where('id_periodo', $oldId)->update(['id_periodo' => $survivorId]);
        }

        // ── 3. Re-linkar cronogramas de períodos duplicados al survivor ────────
        foreach ($duplicates as $oldId => $survivorId) {
            DB::table('cronogramas')->where('id_periodo', $oldId)->update(['id_periodo' => $survivorId]);
        }

        // ── 4. Eliminar períodos duplicados (no-survivor) ─────────────────────
        if (!empty($duplicates)) {
            DB::table('periodos_dictado')
                ->whereIn('id_periodo', array_keys($duplicates))
                ->delete();
        }

        // ── 5. Actualizar survivors: set id_carrera desde su nivel, null id_nivel
        foreach ($survivorMap as $key => $survivorId) {
            [$idCarrera] = explode('|', $key, 2);
            DB::table('periodos_dictado')->where('id_periodo', $survivorId)->update([
                'id_carrera' => (int) $idCarrera,
                'id_nivel'   => null,
            ]);
        }
    }

    public function down(): void
    {
        // No revertimos: restaurar los períodos por nivel desde cero
        // requeriría guardar un snapshot, lo cual excede el scope de esta migración.
    }
};
