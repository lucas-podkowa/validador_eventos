<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categoria_evento', function (Blueprint $table) {
            $table->boolean('disponible_para_eventos')->default(true)->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('categoria_evento', function (Blueprint $table) {
            $table->dropColumn('disponible_para_eventos');
        });
    }
};
