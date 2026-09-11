<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->index('estado');
            $table->index('fecha_inicio');
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['fecha_inicio']);
            $table->dropIndex(['nombre']);
        });
    }
};
