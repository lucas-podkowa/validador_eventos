<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->unsignedBigInteger('contexto_id')->nullable()->after('categoria_id');
            $table->foreign('contexto_id')->references('contexto_id')->on('contexto')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->dropForeign(['contexto_id']);
            $table->dropColumn('contexto_id');
        });
    }
};
