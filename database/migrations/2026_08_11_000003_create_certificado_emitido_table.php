<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificado_emitido', function (Blueprint $table) {
            $table->id();
            $table->uuid('participante_id');
            $table->foreignId('titulo_intermedio_id')->constrained('titulo_intermedio')->onDelete('restrict');
            $table->string('certificado_path');
            $table->boolean('anulado')->default(false);
            $table->foreignId('emitido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('participante_id')->references('participante_id')->on('participante')->onDelete('cascade');
            $table->index(['participante_id', 'titulo_intermedio_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificado_emitido');
    }
};
