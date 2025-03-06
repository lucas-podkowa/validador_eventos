<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicador', function (Blueprint $table) {
            $table->id('indicador_id');
            $table->string('nombre');
            $table->unsignedBigInteger('tipo_indicador_id');
            $table->foreign('tipo_indicador_id')->references('tipo_indicador_id')->on('tipo_indicador')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicador');
    }
};
