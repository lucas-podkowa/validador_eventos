<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolUsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles
        Role::create(['name' => 'Administrador']);
        Role::create(['name' => 'Revisor']);
        Role::create(['name' => 'Asistente']);
        Role::create(['name' => 'Invitado']); // sin permisos

        // Crear permisos y asignar a lor roles
        Permission::create(['name' => 'crear_eventos'])->syncRoles(['Administrador']);
        Permission::create(['name' => 'procesar_aprobaciones'])->syncRoles(['Administrador', 'Revisor']);
        Permission::create(['name' => 'asistencias'])->syncRoles(['Administrador', 'Asistente']);

        // Crear usuarios de ejemplo
        User::factory()->create([
            'name' => 'Administrador del Sistema',
            'email' => 'admin@mail.com',
            'password' => bcrypt('password123')
        ])->assignRole('Administrador');

        User::factory()->create([
            'name' => 'Usuario Revisor',
            'email' => 'revisor@mail.com',
            'password' => bcrypt('password123')
        ])->assignRole('Revisor');

        User::factory()->create([
            'name' => 'Usuario Asistente',
            'email' => 'asistente@mail.com',
            'password' => bcrypt('password123')
        ])->assignRole('Asistente');

        User::factory()->create([
            'name' => 'Usuario Invitado',
            'email' => 'invitado@mail.com',
            'password' => bcrypt('password123')
        ])->assignRole('Invitado');
    }
}
