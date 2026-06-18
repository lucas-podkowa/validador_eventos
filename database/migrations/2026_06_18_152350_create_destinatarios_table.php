<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('destinatarios', function (Blueprint $table) {
            $table->id('destinatario_id');
            $table->string('nombre');
            $table->boolean('activo')->default(true);

            $table->unique('nombre');
        });

        DB::table('destinatarios')->insertOrIgnore([
            ['nombre' => 'Docente / No Docente de la Facultad de Ingeniería', 'activo' => true],
            ['nombre' => 'Estudiante de la Facultad de Ingeniería', 'activo' => true],
            ['nombre' => 'Graduado de la Facultad de Ingeniería', 'activo' => true],
            ['nombre' => 'Docente / No Docente de la UNaM', 'activo' => true],
            ['nombre' => 'Estudiante de la UNaM', 'activo' => true],
            ['nombre' => 'Graduado de la UNaM', 'activo' => true],
            ['nombre' => 'Profesional Externo', 'activo' => true],
            ['nombre' => 'Público General', 'activo' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinatarios');
    }
};
