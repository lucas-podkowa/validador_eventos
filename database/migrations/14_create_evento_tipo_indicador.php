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
        Schema::create('evento_tipo_indicador', function (Blueprint $table) {
            $table->id('evento_tipo_indicador_id');
            $table->uuid('evento_id');
            $table->unsignedBigInteger('tipo_indicador_id');
            $table->foreign('evento_id')->references('evento_id')->on('evento')->onDelete('cascade');
            $table->foreign('tipo_indicador_id')->references('tipo_indicador_id')->on('tipo_indicador')->onDelete('cascade');
            $table->unique(['evento_id', 'tipo_indicador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_tipo_indicador');
    }
};
