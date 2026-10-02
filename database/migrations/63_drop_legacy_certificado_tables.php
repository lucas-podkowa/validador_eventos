<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('certificado_emitido');
        Schema::dropIfExists('titulo_intermedio');
        Schema::dropIfExists('certificado_externo');

        // Purga del permiso/rol del módulo Académica eliminado.
        if (Schema::hasTable('permissions')) {
            $permiso = DB::table('permissions')->where('name', 'academica')->first();

            if ($permiso) {
                DB::table('role_has_permissions')->where('permission_id', $permiso->id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $permiso->id)->delete();
                DB::table('permissions')->where('id', $permiso->id)->delete();
            }
        }

        if (Schema::hasTable('roles')) {
            $rol = DB::table('roles')->where('name', 'Académica')->first();

            if ($rol) {
                DB::table('role_has_permissions')->where('role_id', $rol->id)->delete();
                DB::table('model_has_roles')->where('role_id', $rol->id)->delete();
                DB::table('roles')->where('id', $rol->id)->delete();
            }
        }
    }

    public function down(): void
    {
        // Las tablas legacy no se recrean: su reemplazo es `emision`.
    }
};
