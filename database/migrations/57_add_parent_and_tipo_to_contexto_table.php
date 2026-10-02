<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contexto', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
        });

        Schema::table('contexto', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable()->change();
            $table->unsignedBigInteger('parent_id')->nullable()->after('categoria_id');
            $table->string('tipo', 30)->nullable()->after('nombre'); // edicion|programa|subprograma

            $table->foreign('categoria_id')->references('categoria_id')->on('categoria_evento')->onDelete('restrict');
            $table->foreign('parent_id')->references('contexto_id')->on('contexto')->onDelete('cascade');
        });

        DB::table('contexto')->whereNull('tipo')->update(['tipo' => 'edicion']);
    }

    public function down(): void
    {
        Schema::table('contexto', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['categoria_id']);
            $table->dropColumn(['parent_id', 'tipo']);
        });

        Schema::table('contexto', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable(false)->change();
            $table->foreign('categoria_id')->references('categoria_id')->on('categoria_evento')->onDelete('restrict');
        });
    }
};
