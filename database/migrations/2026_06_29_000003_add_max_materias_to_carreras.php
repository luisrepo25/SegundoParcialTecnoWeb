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
            $table->integer('max_materias')->default(5)->after('modalidad');
        });

        // Fill from existing periods (first period for that carrera),
        // fallback to modalidad-based defaults
        DB::statement("
            UPDATE carreras c
            SET max_materias = COALESCE(
                (SELECT pd.max_materias
                 FROM periodos_dictado pd
                 WHERE pd.id_carrera = c.id_carrera
                   AND pd.id_nivel IS NULL
                 ORDER BY pd.id_periodo
                 LIMIT 1),
                CASE c.modalidad
                    WHEN 'anual'    THEN 10
                    WHEN 'mensual'  THEN 1
                    ELSE 5
                END
            )
        ");
    }

    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->dropColumn('max_materias');
        });
    }
};
