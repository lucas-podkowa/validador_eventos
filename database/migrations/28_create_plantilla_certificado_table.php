<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantilla_certificado', function (Blueprint $table) {
            $table->id('plantilla_id');
            $table->unsignedBigInteger('categoria_id');
            $table->string('nombre'); // Ej: "Asistente", "Aprobado", "Disertante"
            $table->string('imagen_path');
            $table->foreign('categoria_id')->references('categoria_id')->on('categoria_evento')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantilla_certificado');
    }
};
