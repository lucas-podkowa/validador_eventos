<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titulo_intermedio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carrera')->onDelete('cascade');
            $table->string('nombre', 120);
            $table->boolean('activo')->default(true);
            $table->string('imagen_plantilla_path')->nullable();
            $table->timestamps();
            $table->unique(['carrera_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titulo_intermedio');
    }
};
