<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participante_indicador', function (Blueprint $table) {
            $table->id('participante_indicador_id');
            $table->unsignedBigInteger('insc_participante_id');
            $table->unsignedBigInteger('indicador_id');
            $table->foreign('insc_participante_id')->references('inscripcion_participante_id')->on('inscripcion_participante')->onDelete('cascade');
            $table->foreign('indicador_id')->references('indicador_id')->on('indicador')->onDelete('cascade');
            $table->unique(['insc_participante_id', 'indicador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participante_indicador');
    }
};
