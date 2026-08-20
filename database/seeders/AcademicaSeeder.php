<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AcademicaSeeder extends Seeder
{
    /**
     * Seeder idempotente del módulo Académica (títulos intermedios).
     *
     * Puede ejecutarse en producción sin riesgos porque solo crea datos
     * en tablas nuevas o usa firstOrCreate/updateOrCreate sobre claves únicas:
     *
     *   php artisan db:seed --class=AcademicaSeeder --force
     */
    public function run(): void
    {
        // Rol y permiso del módulo
        $rol = Role::firstOrCreate(['name' => 'Académica']);
        $permiso = Permission::firstOrCreate(['name' => 'academica']);
        if (! $rol->hasPermissionTo('academica')) {
            $rol->givePermissionTo($permiso);
        }
        if (! Permission::where('name', 'academica')->first()->roles()->where('name', 'Administrador')->exists()) {
            $permiso->assignRole('Administrador');
        }

        // Carreras
        $carreras = [
            ['nombre' => 'Ingeniería Civil', 'codigo' => 'CI'],
            ['nombre' => 'Ingeniería Electromecánica', 'codigo' => 'EM'],
            ['nombre' => 'Ingeniería Electrónica', 'codigo' => 'ET'],
            ['nombre' => 'Ingeniería Industrial', 'codigo' => 'IN'],
            ['nombre' => 'Ingeniería en Computación', 'codigo' => 'IC'],
            ['nombre' => 'Ingeniería Mecatrónica', 'codigo' => 'IM'],
            ['nombre' => 'Licenciatura en Higiene y Seguridad en el Trabajo', 'codigo' => 'LHyST'],
        ];

        foreach ($carreras as $carrera) {
            Carrera::updateOrCreate(
                ['codigo' => $carrera['codigo']],
                ['nombre' => $carrera['nombre'], 'activa' => true]
            );
        }
    }
}
