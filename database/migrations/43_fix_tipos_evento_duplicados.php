<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El seeder no era idempotente y corrió dos veces, generando filas
     * duplicadas en tipo_evento. Se fusionan los duplicados (se conserva la
     * fila con menor id), se reasignan los eventos y se agrega un índice
     * UNIQUE sobre nombre para evitar que vuelva a ocurrir.
     */
    public function up(): void
    {
        $duplicados = DB::table('tipo_evento')
            ->select('nombre')
            ->groupBy('nombre')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nombre');

        foreach ($duplicados as $nombre) {
            $ids = DB::table('tipo_evento')
                ->where('nombre', $nombre)
                ->orderBy('tipo_evento_id')
                ->pluck('tipo_evento_id');

            $conservar = $ids->first();
            $duplicadosIds = $ids->skip(1)->values();

            DB::table('evento')
                ->whereIn('tipo_evento_id', $duplicadosIds)
                ->update(['tipo_evento_id' => $conservar]);

            DB::table('tipo_evento')
                ->whereIn('tipo_evento_id', $duplicadosIds)
                ->delete();
        }

        Schema::table('tipo_evento', function (Blueprint $table) {
            $table->unique('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('tipo_evento', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });
    }
};
