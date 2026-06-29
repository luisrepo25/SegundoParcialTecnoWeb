<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::reconnect();
        DB::statement('ALTER TABLE grupos DROP CONSTRAINT IF EXISTS grupos_codigo_grupo_key');
        DB::statement('ALTER TABLE grupos ADD CONSTRAINT grupos_codigo_grupo_periodo_key UNIQUE (codigo_grupo, id_periodo)');
    }

    public function down(): void
    {
        DB::reconnect();
        DB::statement('ALTER TABLE grupos DROP CONSTRAINT IF EXISTS grupos_codigo_grupo_periodo_key');
        DB::statement('ALTER TABLE grupos ADD CONSTRAINT grupos_codigo_grupo_key UNIQUE (codigo_grupo)');
    }
};
