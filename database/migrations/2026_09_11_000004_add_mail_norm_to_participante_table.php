<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columna normalizada del correo para la detección de duplicados.
     * Migración aditiva: se rellena con el comando participantes:normalizar.
     */
    public function up(): void
    {
        Schema::table('participante', function (Blueprint $table) {
            $table->string('mail_norm', 255)->nullable()->after('mail');
            $table->index('mail_norm');
        });
    }

    public function down(): void
    {
        Schema::table('participante', function (Blueprint $table) {
            $table->dropIndex(['mail_norm']);
            $table->dropColumn('mail_norm');
        });
    }
};
