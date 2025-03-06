<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripcion_participante', function (Blueprint $table) {
            $table->id('inscripcion_participante_id');
            $table->uuid('planilla_id');
            $table->uuid('participante_id');
            $table->timestamp('fecha_inscripcion');
            $table->boolean('asistencia')->default(false);

            $table->foreign('planilla_id')->references('planilla_inscripcion_id')->on('planilla_inscripcion')->onDelete('cascade');
            $table->foreign('participante_id')->references('participante_id')->on('participante')->onDelete('cascade');

            $table->unique(['planilla_id', 'participante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripcion_participante');
    }
};
