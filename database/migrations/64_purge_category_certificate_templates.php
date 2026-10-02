<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Purga las plantillas a nivel Categoría (contexto_id NULL). La emisión de
     * certificados pasa a depender exclusivamente de las plantillas del contexto.
     */
    public function up(): void
    {
        $plantillas = DB::table('plantilla_certificado')
            ->whereNull('contexto_id')
            ->get(['plantilla_id', 'imagen_path']);

        foreach ($plantillas as $plantilla) {
            if (! empty($plantilla->imagen_path)) {
                Storage::disk('public')->delete($plantilla->imagen_path);
            }
        }

        DB::table('plantilla_certificado')->whereNull('contexto_id')->delete();
    }

    public function down(): void
    {
        // Purga irreversible: las plantillas de categoría ya no se usan.
    }
};
