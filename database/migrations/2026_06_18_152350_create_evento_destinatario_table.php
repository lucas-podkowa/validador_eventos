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
        Schema::create('evento_destinatario', function (Blueprint $table) {
            $table->uuid('evento_id');
            $table->unsignedBigInteger('destinatario_id');
            $table->decimal('precio', 10, 2);

            $table->foreign('evento_id')->references('evento_id')->on('evento')->onDelete('cascade');
            $table->foreign('destinatario_id')->references('destinatario_id')->on('destinatarios')->onDelete('cascade');

            $table->primary(['evento_id', 'destinatario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_destinatario');
    }
};
