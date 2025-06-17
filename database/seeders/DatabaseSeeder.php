<?php

namespace Database\Seeders;

use App\Models\Indicador;
use App\Models\TipoEvento;
use App\Models\TipoIndicador;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        TipoEvento::insert([
            ['nombre' => 'Curso'],
            ['nombre' => 'Conferencia'],
            ['nombre' => 'Seminario'],
            ['nombre' => 'Taller'],
            ['nombre' => 'Charla'],
            ['nombre' => 'Webinar'],
        ]);

        TipoIndicador::insert([
            ['nombre' => 'Relación con la Institución'],
            ['nombre' => 'Origen de Información'],
            ['nombre' => 'Carrera'],
        ]);

        Indicador::insert([
            ['nombre' => 'Estudiante UNaM', 'tipo_indicador_id' => 1],
            ['nombre' => 'Docente UNaM', 'tipo_indicador_id' => 1],
            ['nombre' => 'Graduado UNaM', 'tipo_indicador_id' => 1],
            ['nombre' => 'No Docente UNaM', 'tipo_indicador_id' => 1],
            ['nombre' => 'Por la web www.fio.unam.edu.ar', 'tipo_indicador_id' => 2],
            ['nombre' => 'Docente o No Docentes de la FI UNaM', 'tipo_indicador_id' => 2],
            ['nombre' => 'Instagram', 'tipo_indicador_id' => 2],
            ['nombre' => 'Facebook', 'tipo_indicador_id' => 2],
            ['nombre' => 'Whatsapp', 'tipo_indicador_id' => 2],
            ['nombre' => 'Otro', 'tipo_indicador_id' => 2],
            ['nombre' => 'Ingeniería Civil', 'tipo_indicador_id' => 3],
            ['nombre' => 'Ingeniería Electromecánica', 'tipo_indicador_id' => 3],
            ['nombre' => 'Ingeniería Electrónica', 'tipo_indicador_id' => 3],
            ['nombre' => 'Ingeniería Industrial', 'tipo_indicador_id' => 3],
            ['nombre' => 'Ingeniería en Computación', 'tipo_indicador_id' => 3],
            ['nombre' => 'Ingeniería Mecatrónica', 'tipo_indicador_id' => 3],
            ['nombre' => 'Licenciatura en Higiene y Seguridad en el Trabajo', 'tipo_indicador_id' => 3],
        ]);


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
            'email' => 'sistemas@fio.unam.edu.ar',
            'password' => bcrypt('hh1y32gg')
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

        $this->call([
            //EventoSeeder::class,
            //ParticipanteSeeder::class,
        ]);
    }
}
