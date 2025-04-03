<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Participante;
use Illuminate\Support\Str;

class ParticipanteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 10 participantes de prueba
        for ($i = 1; $i <= 10; $i++) {
            Participante::create([
                'participante_id' => Str::uuid(),
                'nombre' => "Participante $i",
                'apellido' => "Apellido $i",
                'dni' => rand(99999, 99999999),
                'mail' => "participante$i@example.com",
                'telefono' => '123456789' . $i,
            ]);
        }
    }
}
