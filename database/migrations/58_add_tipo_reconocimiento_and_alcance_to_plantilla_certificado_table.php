<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_reconocimiento_id')->nullable()->after('tipo');
            $table->string('alcance', 30)->nullable()->after('tipo_reconocimiento_id'); // evento|contexto|programa|sin_origen
            $table->foreign('tipo_reconocimiento_id')->references('tipo_reconocimiento_id')->on('tipo_reconocimiento')->onDelete('set null');
        });

        // Backfill: mapear el enum legado `tipo` al vocabulario unificado.
        $mapa = [
            'asistencia' => 'asistencia',
            'aprobacion' => 'aprobacion',
            'disertante' => 'disertante',
            'colaborador' => 'colaborador',
            'tutor_academico' => 'tutor',
        ];

        foreach ($mapa as $tipoLegacy => $slug) {
            $id = DB::table('tipo_reconocimiento')->where('slug', $slug)->value('tipo_reconocimiento_id');

            if ($id) {
                DB::table('plantilla_certificado')
                    ->where('tipo', $tipoLegacy)
                    ->whereNull('tipo_reconocimiento_id')
                    ->update(['tipo_reconocimiento_id' => $id]);
            }
        }

        // Alcance por defecto: las plantillas de contexto/legacy apuntan a evento salvo colaborador/tutor.
        DB::table('plantilla_certificado')->whereNull('alcance')->update(['alcance' => 'evento']);
        DB::table('plantilla_certificado')
            ->whereIn('tipo', ['colaborador'])
            ->update(['alcance' => 'contexto']);
        DB::table('plantilla_certificado')
            ->whereIn('tipo', ['tutor_academico'])
            ->update(['alcance' => 'programa']);
    }

    public function down(): void
    {
        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->dropForeign(['tipo_reconocimiento_id']);
            $table->dropColumn(['tipo_reconocimiento_id', 'alcance']);
        });
    }
};
