<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evento', function (Blueprint $table) {
            $table->uuid('evento_id')->primary();
            //$table->uuid('evento_id')->primary()->default(DB::raw('(UUID())'));

            $table->string('nombre');
            $table->date('fecha_inicio');
            //$table->string('cudap')->unique();
            $table->unsignedInteger('cupo')->nullable();
            $table->string('lugar');
            $table->enum('estado', ['Pendiente', 'En Curso', 'Finalizado'])->default('Pendiente');
            $table->unsignedBigInteger('tipo_evento_id');
            $table->string('certificado_path')->nullable();
            $table->foreign('tipo_evento_id')->references('tipo_evento_id')->on('tipo_evento')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento');
    }
};
