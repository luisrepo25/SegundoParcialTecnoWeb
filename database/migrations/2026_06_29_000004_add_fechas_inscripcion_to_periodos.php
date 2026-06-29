<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodos_dictado', function (Blueprint $table) {
            $table->date('fecha_inicio_inscripcion')->nullable()->after('fecha_fin');
            $table->date('fecha_fin_inscripcion')->nullable()->after('fecha_inicio_inscripcion');
        });
    }

    public function down(): void
    {
        Schema::table('periodos_dictado', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio_inscripcion', 'fecha_fin_inscripcion']);
        });
    }
};
