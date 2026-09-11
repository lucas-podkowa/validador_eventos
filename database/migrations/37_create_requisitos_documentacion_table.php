<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requisitos_documentacion', function (Blueprint $table) {
            $table->id('requisito_id');
            $table->uuid('evento_id');
            $table->unsignedBigInteger('destinatario_id');
            $table->string('titulo');
            $table->unsignedInteger('orden')->default(0);

            $table->foreign('evento_id')->references('evento_id')->on('evento')->onDelete('cascade');
            $table->foreign('destinatario_id')->references('destinatario_id')->on('destinatarios')->onDelete('cascade');

            $table->unique(['evento_id', 'destinatario_id', 'titulo'], 'req_doc_eve_dest_tit_unq');
            $table->index(['evento_id', 'destinatario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitos_documentacion');
    }
};
