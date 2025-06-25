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
        Schema::create('planilla_inscripcion', function (Blueprint $table) {
            $table->uuid('planilla_inscripcion_id')->primary();
            $table->datetime('apertura');
            $table->datetime('cierre');
            $table->string('header')->nullable();
            $table->string('footer')->nullable();
            $table->string('disposicion')->nullable();
            $table->uuid('evento_id');
            $table->foreign('evento_id')->references('evento_id')->on('evento')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_inscripcion');
    }
};
