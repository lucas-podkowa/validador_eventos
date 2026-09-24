<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstalarApi extends Command
{
    protected $signature = 'api:instalar';

    protected $description = 'Crea el permiso administrar_api (sin recrear la base) y lo asigna al rol Administrador';

    public function handle(): int
    {
        $permiso = Permission::firstOrCreate(['name' => 'administrar_api']);

        $rol = Role::where('name', 'Administrador')->first();

        if ($rol) {
            $rol->givePermissionTo($permiso);
            $this->info('Permiso administrar_api asignado al rol Administrador.');
        } else {
            $this->warn('No existe el rol Administrador; el permiso quedó creado sin asignar.');
        }

        return self::SUCCESS;
    }
}
