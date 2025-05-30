<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_indicador', function (Blueprint $table) {
            $table->id('tipo_indicador_id');
            $table->string('nombre')->unique();
            $table->enum('selector', ['Selección Única', 'Selección Múltiple', 'Texto Libre'])->default('Selección Única');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_indicador');
    }
};
