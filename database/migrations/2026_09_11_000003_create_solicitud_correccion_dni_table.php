<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes de corrección de DNI hechas por el participante, con imagen del documento.
     */
    public function up(): void
    {
        Schema::create('solicitud_correccion_dni', function (Blueprint $table) {
            $table->id();
            $table->uuid('participante_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dni_actual', 20)->nullable();
            $table->string('dni_solicitado', 20);
            $table->text('motivo')->nullable();
            $table->string('imagen_path');
            $table->string('imagen_mime', 100)->nullable();
            $table->string('imagen_original')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('participante_id')->references('participante_id')->on('participante')->onDelete('cascade');
            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_correccion_dni');
    }
};
