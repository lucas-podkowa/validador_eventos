<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contexto_firmante', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contexto_id');
            $table->unsignedBigInteger('firmante_id');
            $table->unsignedTinyInteger('orden')->default(1); // Slot: 1, 2, ...
            $table->boolean('mostrar_cargo')->default(true);
            $table->foreign('contexto_id')->references('contexto_id')->on('contexto')->onDelete('cascade');
            $table->foreign('firmante_id')->references('firmante_id')->on('firmante')->onDelete('cascade');
            $table->unique(['contexto_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contexto_firmante');
    }
};
