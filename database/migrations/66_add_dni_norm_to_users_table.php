<?php

use App\Support\DocumentoIdentidad;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dni_norm', 20)->nullable()->after('dni');
        });

        $vistos = [];

        DB::table('users')->select('id', 'dni')->whereNotNull('dni')->orderBy('id')->chunk(500, function ($filas) use (&$vistos) {
            foreach ($filas as $fila) {
                $normalizado = DocumentoIdentidad::normalizar($fila->dni);

                if ($normalizado !== '' && isset($vistos[$normalizado])) {
                    throw new RuntimeException("Documento duplicado al normalizar en usuarios: {$fila->dni}. Corregilo antes de ejecutar la migración.");
                }

                if ($normalizado !== '') {
                    $vistos[$normalizado] = true;
                }

                DB::table('users')
                    ->where('id', $fila->id)
                    ->update(['dni_norm' => $normalizado === '' ? null : $normalizado]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('dni_norm');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['dni_norm']);
            $table->dropColumn('dni_norm');
        });
    }
};
