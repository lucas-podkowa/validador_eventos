<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsable', function (Blueprint $table) {
            $table->id('responsable_id');
            $table->string('nombre');
            $table->string('apellido');
            $table->integer('dni')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsable');
    }
};
