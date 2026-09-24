<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->unsignedBigInteger('contexto_id')->nullable()->after('categoria_id');
            $table->json('layout')->nullable()->after('imagen_path');
            $table->text('texto')->nullable()->after('layout');
            $table->foreign('contexto_id')->references('contexto_id')->on('contexto')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->dropForeign(['contexto_id']);
            $table->dropColumn(['contexto_id', 'layout', 'texto']);
        });
    }
};
