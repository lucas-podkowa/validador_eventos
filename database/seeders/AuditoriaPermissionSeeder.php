<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuditoriaPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permiso = Permission::findOrCreate('ver_auditoria', 'web');
        $rol = Role::findByName('Administrador', 'web');

        $rol->givePermissionTo($permiso);
    }
}
