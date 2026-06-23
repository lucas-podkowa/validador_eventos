<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('inscripcion_participante', function (Blueprint $table) {
            if (! Schema::hasColumn('inscripcion_participante', 'metodo_pago')) {
                $table->json('metodo_pago')->nullable()->after('comprobante_pago');
            }
        });
    }

    public function down()
    {
        Schema::table('inscripcion_participante', function (Blueprint $table) {
            if (Schema::hasColumn('inscripcion_participante', 'metodo_pago')) {
                $table->dropColumn('metodo_pago');
            }
        });
    }
};
