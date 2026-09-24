<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contexto', function (Blueprint $table) {
            $table->id('contexto_id');
            $table->unsignedBigInteger('categoria_id');
            $table->string('nombre'); // Ej: "XVI JIDeTEV"
            $table->string('denominacion')->nullable(); // Ej: "Jornadas de Investigación, Desarrollo Tecnológico..."
            $table->string('institucion')->nullable(); // Ej: "Facultad de Ingeniería UNaM"
            $table->unsignedSmallInteger('anio')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('lugar')->nullable(); // Ej: "Oberá, Misiones"
            $table->string('resolucion')->nullable(); // Ej: "CD 089/26"
            $table->boolean('activo')->default(true);
            $table->foreign('categoria_id')->references('categoria_id')->on('categoria_evento')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contexto');
    }
};
