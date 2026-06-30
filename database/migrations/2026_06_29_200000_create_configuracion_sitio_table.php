<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_sitio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_institucion')->default('Instituto San Pablo');
            $table->text('descripcion')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono_1')->nullable();
            $table->string('telefono_2')->nullable();
            $table->string('direccion')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        // Fila inicial (singleton)
        DB::table('configuracion_sitio')->insert([
            'nombre_institucion' => 'Instituto San Pablo',
            'descripcion'        => 'Formación técnica y superior para el mercado laboral del oriente boliviano. Inscripción 100% en línea.',
            'email'              => 'info@sanpablo.edu.bo',
            'telefono_1'         => '+591 3 300-0000',
            'telefono_2'         => '+591 70 000-000',
            'direccion'          => 'Santa Cruz, Bolivia',
            'facebook_url'       => null,
            'instagram_url'      => null,
            'youtube_url'        => null,
            'logo_path'          => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_sitio');
    }
};
