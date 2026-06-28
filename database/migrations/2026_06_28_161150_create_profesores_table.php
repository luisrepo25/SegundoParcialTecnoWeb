<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // La tabla profesores ya existe en la BD. Solo agrega archivo_cv si no está.
        if (!Schema::hasColumn('profesores', 'archivo_cv')) {
            Schema::table('profesores', function (Blueprint $table) {
                $table->string('archivo_cv')->nullable()->after('observaciones');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('profesores', 'archivo_cv')) {
            Schema::table('profesores', function (Blueprint $table) {
                $table->dropColumn('archivo_cv');
            });
        }
    }
};
