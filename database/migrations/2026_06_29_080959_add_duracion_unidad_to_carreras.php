<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::reconnect();
        DB::statement("ALTER TABLE carreras ADD COLUMN IF NOT EXISTS duracion_unidad VARCHAR(5) NOT NULL DEFAULT 'anos'");
        DB::statement("UPDATE carreras SET duracion_unidad = 'meses' WHERE tipo = 'curso_libre'");
    }

    public function down(): void
    {
        DB::reconnect();
        DB::statement('ALTER TABLE carreras DROP COLUMN IF EXISTS duracion_unidad');
    }
};
