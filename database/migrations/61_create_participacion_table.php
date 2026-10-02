<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participacion', function (Blueprint $table) {
            $table->uuid('participacion_id')->primary();
            $table->uuid('participante_id');
            $table->string('origen_type', 191);
            $table->string('origen_id', 191);
            $table->unsignedBigInteger('tipo_reconocimiento_id');
            $table->boolean('aprobado')->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente|emitido|anulado
            $table->uuid('emision_id')->nullable();
            $table->timestamps();

            $table->foreign('participante_id')->references('participante_id')->on('participante')->onDelete('cascade');
            $table->foreign('tipo_reconocimiento_id')->references('tipo_reconocimiento_id')->on('tipo_reconocimiento')->onDelete('restrict');
            $table->foreign('emision_id')->references('emision_id')->on('emision')->onDelete('set null');

            $table->unique(['participante_id', 'origen_type', 'origen_id', 'tipo_reconocimiento_id'], 'participacion_unica');
            $table->index(['origen_type', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participacion');
    }
};
