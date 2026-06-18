<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inscripcion_participante', function (Blueprint $table) {
            $table->unsignedBigInteger('destinatario_id')->nullable()->after('rol_id');
            $table->decimal('monto', 10, 2)->nullable()->after('destinatario_id');
            $table->string('comprobante_pago')->nullable()->after('monto');

            $table->foreign('destinatario_id')->references('destinatario_id')->on('destinatarios')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripcion_participante', function (Blueprint $table) {
            $table->dropForeign(['destinatario_id']);
            $table->dropColumn(['destinatario_id', 'monto', 'comprobante_pago']);
        });
    }
};
