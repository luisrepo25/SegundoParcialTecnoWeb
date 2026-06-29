<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::reconnect();
        // Permite que grupos del mismo período compartan codigo_grupo
        // (grupos diarios de la misma materia/aula/docente, diferente horario)
        DB::statement('ALTER TABLE grupos DROP CONSTRAINT IF EXISTS grupos_codigo_grupo_periodo_key');
    }

    public function down(): void
    {
        DB::reconnect();
        DB::statement('ALTER TABLE grupos ADD CONSTRAINT grupos_codigo_grupo_periodo_key UNIQUE (codigo_grupo, id_periodo)');
    }
};
