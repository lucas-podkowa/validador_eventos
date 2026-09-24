<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmante', function (Blueprint $table) {
            $table->id('firmante_id');
            $table->string('nombre');
            $table->string('cargo')->nullable(); // Ej: "Decana Facultad de Ingeniería UNaM"
            $table->string('imagen_firma_path')->nullable(); // Disco private: firmas/{firmante_id}/...
            $table->string('imagen_mime')->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmante');
    }
};
