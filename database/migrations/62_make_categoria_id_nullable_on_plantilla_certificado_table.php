<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
        });

        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable()->change();
            $table->foreign('categoria_id')->references('categoria_id')->on('categoria_evento')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
        });

        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable(false)->change();
            $table->foreign('categoria_id')->references('categoria_id')->on('categoria_evento')->onDelete('cascade');
        });
    }
};
