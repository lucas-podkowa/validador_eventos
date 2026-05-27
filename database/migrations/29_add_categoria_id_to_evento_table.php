<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar la columna como nullable para poder migrar datos existentes
        Schema::table('evento', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable()->after('tipo_evento_id');
        });

        // 2. Insertar categoría "General" por defecto
        $categoriaId = DB::table('categoria_evento')->insertGetId([
            'nombre'      => 'General',
            'descripcion' => 'Categoría por defecto para eventos existentes',
        ], 'categoria_id');

        // 3. Asignar todos los eventos existentes a la categoría "General"
        DB::table('evento')->update(['categoria_id' => $categoriaId]);

        // 4. Hacer la columna NOT NULL y agregar la FK
        Schema::table('evento', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable(false)->change();
            $table->foreign('categoria_id')->references('categoria_id')->on('categoria_evento')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });

        DB::table('categoria_evento')->where('nombre', 'General')->delete();
    }
};
