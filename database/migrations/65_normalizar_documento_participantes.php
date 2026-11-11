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
        Schema::table('participante', function (Blueprint $table) {
            $table->string('dni', 20)->nullable(false)->change();
        });

        Schema::table('participante', function (Blueprint $table) {
            $table->string('dni_norm', 20)->nullable()->after('dni');
        });

        $vistos = [];

        DB::table('participante')->select('participante_id', 'dni')->orderBy('participante_id')->chunk(500, function ($filas) use (&$vistos) {
            foreach ($filas as $fila) {
                $normalizado = DocumentoIdentidad::normalizar($fila->dni);

                if ($normalizado !== '' && isset($vistos[$normalizado])) {
                    throw new RuntimeException("Documento duplicado al normalizar: {$fila->dni}. Corregilo antes de ejecutar la migración.");
                }

                if ($normalizado !== '') {
                    $vistos[$normalizado] = true;
                }

                DB::table('participante')
                    ->where('participante_id', $fila->participante_id)
                    ->update(['dni_norm' => $normalizado === '' ? null : $normalizado]);
            }
        });

        Schema::table('participante', function (Blueprint $table) {
            $table->unique('dni_norm');
        });
    }

    public function down(): void
    {
        foreach (DB::table('participante')->whereNotNull('dni')->cursor() as $fila) {
            if (! ctype_digit((string) $fila->dni)) {
                throw new RuntimeException('No se puede revertir: existen documentos no numéricos.');
            }
        }

        Schema::table('participante', function (Blueprint $table) {
            $table->dropUnique(['dni_norm']);
            $table->dropColumn('dni_norm');
        });

        Schema::table('participante', function (Blueprint $table) {
            $table->integer('dni')->nullable(false)->change();
        });
    }
};
