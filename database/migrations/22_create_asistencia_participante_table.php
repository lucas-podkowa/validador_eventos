<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia_participante', function (Blueprint $table) {
            $table->id('asistencia_participante_id');
            $table->unsignedBigInteger('inscripcion_participante_id');
            $table->unsignedBigInteger('sesion_evento_id');
            $table->boolean('asistio')->default(false);
            $table->foreign('inscripcion_participante_id')
                ->references('inscripcion_participante_id')
                ->on('inscripcion_participante')
                ->onDelete('cascade');
            $table->foreign('sesion_evento_id')->references('sesion_evento_id')->on('sesion_evento')->onDelete('cascade');
            $table->unique(['inscripcion_participante_id', 'sesion_evento_id'], 'asistencia_unica_por_inscripcion_sesion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_participante');
    }
};
