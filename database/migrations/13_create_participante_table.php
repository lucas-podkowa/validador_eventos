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
        Schema::create('participante', function (Blueprint $table) {
            $table->uuid('participante_id')->primary();
            $table->string('apellido');
            $table->string('nombre');
            $table->integer('dni')->unique();
            $table->string('mail')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participante');
    }
};
