<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_reconocimiento_id')->nullable()->after('rol_id');
            $table->foreign('tipo_reconocimiento_id')->references('tipo_reconocimiento_id')->on('tipo_reconocimiento')->onDelete('set null');
        });

        $roles = DB::table('rol')->get(['rol_id', 'nombre']);

        foreach ($roles as $rol) {
            $slug = match (mb_strtolower(trim((string) $rol->nombre))) {
                'disertante' => 'disertante',
                'colaborador' => 'colaborador',
                default => 'participante',
            };

            $tipoId = DB::table('tipo_reconocimiento')->where('slug', $slug)->value('tipo_reconocimiento_id');

            if ($tipoId) {
                DB::table('evento_participantes')
                    ->where('rol_id', $rol->rol_id)
                    ->whereNull('tipo_reconocimiento_id')
                    ->update(['tipo_reconocimiento_id' => $tipoId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->dropForeign(['tipo_reconocimiento_id']);
            $table->dropColumn('tipo_reconocimiento_id');
        });
    }
};
