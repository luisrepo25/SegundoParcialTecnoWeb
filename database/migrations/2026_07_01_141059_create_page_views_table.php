<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->string('pagina', 200)->primary();
            $table->unsignedBigInteger('visitas')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
