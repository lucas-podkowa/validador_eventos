<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas normalizadas para detectar duplicados por nombre y teléfono.
     * Migración aditiva: los valores existentes se rellenan con el comando participantes:normalizar.
     */
    public function up(): void
    {
        Schema::table('participante', function (Blueprint $table) {
            $table->string('nombre_norm', 255)->nullable()->after('nombre');
            $table->string('apellido_norm', 255)->nullable()->after('apellido');
            $table->string('telefono_norm', 20)->nullable()->after('telefono');
            $table->index(['apellido_norm', 'nombre_norm', 'telefono_norm'], 'participante_norm_index');
        });
    }

    public function down(): void
    {
        Schema::table('participante', function (Blueprint $table) {
            $table->dropIndex('participante_norm_index');
            $table->dropColumn(['nombre_norm', 'apellido_norm', 'telefono_norm']);
        });
    }
};
