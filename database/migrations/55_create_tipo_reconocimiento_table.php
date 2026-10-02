<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_reconocimiento', function (Blueprint $table) {
            $table->id('tipo_reconocimiento_id');
            $table->string('nombre', 120);
            $table->string('slug', 120)->unique();
            $table->string('alcance_sugerido', 30)->nullable(); // evento|contexto|programa|sin_origen
            $table->boolean('es_sistema')->default(false); // derivados: asistencia/aprobacion
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
        });

        $tipos = [
            ['nombre' => 'Participante', 'slug' => 'participante', 'alcance_sugerido' => 'evento'],
            ['nombre' => 'Disertante', 'slug' => 'disertante', 'alcance_sugerido' => 'evento'],
            ['nombre' => 'Colaborador', 'slug' => 'colaborador', 'alcance_sugerido' => 'contexto'],
            ['nombre' => 'Organizador', 'slug' => 'organizador', 'alcance_sugerido' => 'contexto'],
            ['nombre' => 'Tutor', 'slug' => 'tutor', 'alcance_sugerido' => 'programa'],
            ['nombre' => 'Evaluador', 'slug' => 'evaluador', 'alcance_sugerido' => 'contexto'],
            ['nombre' => 'Mentor', 'slug' => 'mentor', 'alcance_sugerido' => 'programa'],
            ['nombre' => 'Asistencia', 'slug' => 'asistencia', 'alcance_sugerido' => 'evento', 'es_sistema' => true],
            ['nombre' => 'Aprobación', 'slug' => 'aprobacion', 'alcance_sugerido' => 'evento', 'es_sistema' => true],
        ];

        foreach ($tipos as $i => $tipo) {
            DB::table('tipo_reconocimiento')->updateOrInsert(
                ['slug' => $tipo['slug']],
                [
                    'nombre' => $tipo['nombre'],
                    'alcance_sugerido' => $tipo['alcance_sugerido'],
                    'es_sistema' => $tipo['es_sistema'] ?? false,
                    'activo' => true,
                    'orden' => $i + 1,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_reconocimiento');
    }
};
