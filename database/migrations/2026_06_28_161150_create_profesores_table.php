<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profesores', function (Blueprint $table) {
            $table->bigIncrements('id_profesor');
            $table->unsignedBigInteger('id_usuario');
            $table->string('legajo_profesor');
            $table->string('especialidad')->nullable();
            $table->string('titulo_maximo')->nullable();
            $table->date('fecha_contratacion')->nullable();
            $table->decimal('sueldo_base', 10, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('archivo_cv')->nullable();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profesores');
    }
};
?>
