<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificado_externo', function (Blueprint $table) {
            $table->uuid('certificado_externo_id')->primary();
            $table->unsignedBigInteger('api_cliente_id');
            $table->string('external_ref');
            $table->string('tipo')->default('tutor_academico');
            $table->unsignedBigInteger('plantilla_certificado_id')->nullable();
            $table->unsignedBigInteger('contexto_id')->nullable();
            $table->uuid('participante_id')->nullable();
            $table->json('datos');
            $table->string('receptor_nombre')->nullable();
            $table->string('receptor_dni')->nullable();
            $table->string('receptor_email')->nullable();
            $table->string('match_estado', 20)->default('auto');
            $table->json('match_detalle')->nullable();
            $table->string('certificado_path')->nullable();
            $table->longText('qrcode')->nullable();
            $table->string('codigo_verificacion', 64)->unique();
            $table->string('estado', 20)->default('emitido');
            $table->timestamps();

            $table->foreign('api_cliente_id')->references('api_cliente_id')->on('api_cliente')->onDelete('cascade');
            $table->foreign('plantilla_certificado_id')->references('plantilla_id')->on('plantilla_certificado')->onDelete('set null');
            $table->foreign('contexto_id')->references('contexto_id')->on('contexto')->onDelete('set null');
            $table->foreign('participante_id')->references('participante_id')->on('participante')->onDelete('set null');

            $table->unique(['api_cliente_id', 'external_ref']);
            $table->index('match_estado');
            $table->index('receptor_dni');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificado_externo');
    }
};
