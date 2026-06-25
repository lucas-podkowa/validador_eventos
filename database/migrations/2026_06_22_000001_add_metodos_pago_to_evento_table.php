<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('evento', function (Blueprint $table) {
            if (! Schema::hasColumn('evento', 'metodos_pago')) {
                $table->json('metodos_pago')->nullable()->after('link_pago');
            }
        });

        // Migrar valores existentes de link_pago a metodos_pago (si aplica)
        if (Schema::hasColumn('evento', 'link_pago')) {
            $events = DB::table('evento')->select('evento_id', 'link_pago')->get();
            foreach ($events as $e) {
                if (! empty($e->link_pago)) {
                    DB::table('evento')->where('evento_id', $e->evento_id)->update([
                        'metodos_pago' => json_encode([
                            [
                                'tipo' => 'url',
                                'valor' => $e->link_pago,
                                'principal' => true,
                                'activo' => true,
                            ],
                        ]),
                    ]);
                }
            }
        }
    }

    public function down()
    {
        Schema::table('evento', function (Blueprint $table) {
            if (Schema::hasColumn('evento', 'metodos_pago')) {
                $table->dropColumn('metodos_pago');
            }
        });
    }
};
