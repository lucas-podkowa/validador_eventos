<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_participantes', function (Blueprint $table) {

            $table->uuid('evento_participantes_id')->primary();
            $table->uuid('evento_id');
            $table->uuid('participante_id');
            $table->unsignedBigInteger('rol_id');
            $table->string('url')->nullable();  // Para almacenar la URL de la validacion
            $table->longText('qrcode')->nullable();  // Para almacenar el código QR en formato SVG de la URL
            $table->boolean('aprobado')->default(null)->nullable(); // Para almacenar el estado de aprobación del participante
            $table->boolean('emision_directa')->default(false); // Para almacenar el estado de aprobación del participante
            $table->string('certificado_path')->nullable();
            $table->foreign('evento_id')->references('evento_id')->on('evento')->onDelete('cascade');
            $table->foreign('participante_id')->references('participante_id')->on('participante')->onDelete('cascade');
            $table->foreign('rol_id')->references('rol_id')->on('rol')->onDelete('restrict');
            $table->unique(['evento_id', 'participante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_participantes');
    }
};
