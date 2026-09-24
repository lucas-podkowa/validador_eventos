<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_evento', function (Blueprint $table) {
            $table->string('formula')->nullable()->after('nombre'); // Ej: "a la", "al"
        });
    }

    public function down(): void
    {
        Schema::table('tipo_evento', function (Blueprint $table) {
            $table->dropColumn('formula');
        });
    }
};
