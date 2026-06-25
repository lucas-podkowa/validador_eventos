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
            $table->string('tipo')->nullable()->after('imagen_path');
            $table->boolean('por_defecto')->default(false)->after('tipo');
        });

        // Asignar `tipo = 'asistencia'` a plantillas existentes para evitar bloqueos
        DB::table('plantilla_certificado')->whereNull('tipo')->update(['tipo' => 'asistencia']);

        // Marcar la primera plantilla por categoria como por_defecto para cada tipo
        $categories = DB::table('plantilla_certificado')->select('categoria_id')->distinct()->get();
        foreach ($categories as $cat) {
            $first = DB::table('plantilla_certificado')
                ->where('categoria_id', $cat->categoria_id)
                ->orderBy('plantilla_id')
                ->value('plantilla_id');
            if ($first) {
                DB::table('plantilla_certificado')->where('plantilla_id', $first)->update(['por_defecto' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('plantilla_certificado', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'por_defecto']);
        });
    }
};
