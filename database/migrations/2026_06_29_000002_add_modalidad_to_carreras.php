<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->string('modalidad', 20)->nullable()->after('tipo');
        });

        // Deducir modalidad desde los períodos existentes de cada carrera
        DB::statement("
            UPDATE carreras c
            SET modalidad = (
                SELECT pd.tipo_periodo
                FROM periodos_dictado pd
                WHERE pd.id_carrera = c.id_carrera
                  AND pd.id_nivel IS NULL
                ORDER BY pd.id_periodo
                LIMIT 1
            )
            WHERE EXISTS (
                SELECT 1 FROM periodos_dictado
                WHERE id_carrera = c.id_carrera AND id_nivel IS NULL
            )
        ");
    }

    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }
};
