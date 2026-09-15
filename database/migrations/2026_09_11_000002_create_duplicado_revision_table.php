<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de posibles duplicados detectados al crear/inscribir participantes.
     */
    public function up(): void
    {
        Schema::create('duplicado_revision', function (Blueprint $table) {
            $table->id();
            $table->uuid('participante_id');
            $table->uuid('candidato_id')->nullable();
            $table->string('origen', 30);
            $table->string('decision', 30);
            $table->text('detalle')->nullable();
            $table->boolean('revisado')->default(false);
            $table->timestamps();

            $table->foreign('participante_id')->references('participante_id')->on('participante')->onDelete('cascade');
            $table->foreign('candidato_id')->references('participante_id')->on('participante')->onDelete('set null');
            $table->index(['revisado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicado_revision');
    }
};
