<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emision', function (Blueprint $table) {
            $table->uuid('emision_id')->primary();
            $table->uuid('participante_id');
            $table->unsignedBigInteger('tipo_reconocimiento_id')->nullable();
            $table->string('origen_type', 191)->nullable(); // App\Models\Evento | App\Models\Contexto
            $table->string('origen_id', 191)->nullable();
            $table->string('alcance', 30)->default('sin_origen'); // evento|contexto|programa|sin_origen
            $table->unsignedBigInteger('plantilla_id')->nullable();
            $table->string('codigo_verificacion', 64)->unique();
            $table->longText('qr')->nullable();
            $table->string('certificado_path')->nullable();
            $table->string('estado', 20)->default('emitido'); // emitido|anulado
            $table->json('datos')->nullable();
            $table->json('origen_snapshot')->nullable();
            $table->text('texto_snapshot')->nullable();

            // Origen API (certificado externo)
            $table->unsignedBigInteger('api_cliente_id')->nullable();
            $table->string('external_ref', 191)->nullable();
            $table->string('match_estado', 20)->nullable();
            $table->json('match_detalle')->nullable();

            $table->foreignId('emitido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('emitida_en')->nullable();
            $table->timestamps();

            $table->foreign('participante_id')->references('participante_id')->on('participante')->onDelete('cascade');
            $table->foreign('tipo_reconocimiento_id')->references('tipo_reconocimiento_id')->on('tipo_reconocimiento')->onDelete('set null');
            $table->foreign('plantilla_id')->references('plantilla_id')->on('plantilla_certificado')->onDelete('set null');
            $table->foreign('api_cliente_id')->references('api_cliente_id')->on('api_cliente')->onDelete('set null');

            $table->index(['participante_id', 'estado']);
            $table->index(['origen_type', 'origen_id']);
            $table->index('tipo_reconocimiento_id');
            $table->index('estado');
            $table->index('emitida_en');
            $table->unique(['api_cliente_id', 'external_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emision');
    }
};
