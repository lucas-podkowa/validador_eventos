<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documentos_presentados', function (Blueprint $table) {
            $table->id('documento_id');
            $table->unsignedBigInteger('inscripcion_participante_id');
            $table->unsignedBigInteger('requisito_id');
            $table->string('path');

            $table->foreign('inscripcion_participante_id')
                ->references('inscripcion_participante_id')
                ->on('inscripcion_participante')
                ->onDelete('cascade');
            $table->foreign('requisito_id')
                ->references('requisito_id')
                ->on('requisitos_documentacion')
                ->onDelete('restrict');

            $table->unique(['inscripcion_participante_id', 'requisito_id'], 'doc_presentados_ip_req_unique');
            $table->index('requisito_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_presentados');
    }
};
