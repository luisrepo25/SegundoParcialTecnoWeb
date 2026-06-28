<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Sin transacción — PgBouncer en modo transacción no soporta
    // DDL + DML mixtos en una misma conexión pooled.
    public $withinTransaction = false;

    public function up(): void
    {
        DB::reconnect();

        // 1. Agregar columna modalidad si no existe
        DB::statement('ALTER TABLE cronogramas ADD COLUMN IF NOT EXISTS modalidad VARCHAR(20) NULL');

        // 2. CHECK constraint (idempotente: primero la elimina si existe, luego la agrega)
        DB::statement('ALTER TABLE cronogramas DROP CONSTRAINT IF EXISTS cronogramas_modalidad_check');
        DB::statement("
            ALTER TABLE cronogramas
            ADD CONSTRAINT cronogramas_modalidad_check
            CHECK (modalidad IS NULL OR modalidad IN ('mensual','semestral','anual','intensivo'))
        ");

        // 3. Quitar FK y columna id_carrera si todavía existe
        DB::statement('ALTER TABLE cronogramas DROP CONSTRAINT IF EXISTS cronogramas_id_carrera_fkey');
        DB::statement('ALTER TABLE cronogramas DROP COLUMN IF EXISTS id_carrera');
    }

    public function down(): void
    {
        DB::reconnect();

        DB::statement('ALTER TABLE cronogramas DROP CONSTRAINT IF EXISTS cronogramas_modalidad_check');
        DB::statement('ALTER TABLE cronogramas DROP COLUMN IF EXISTS modalidad');
        DB::statement('ALTER TABLE cronogramas ADD COLUMN IF NOT EXISTS id_carrera INTEGER NULL');
        DB::statement('ALTER TABLE cronogramas ADD CONSTRAINT cronogramas_id_carrera_fkey FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera)');
    }
};
