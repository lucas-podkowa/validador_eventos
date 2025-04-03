<?php

namespace Database\Seeders;

use App\Models\Evento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class EventoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Evento::create([
                'evento_id' => Str::uuid(),
                'nombre' => "Evento de prueba $i",
                'fecha_inicio' => now()->addDays($i),
                'cupo' => rand(20, 100),
                'lugar' => "Lugar $i",
                'estado' => 'Pendiente',
                'tipo_evento_id' => 1, // Ajusta según los datos que tengas en la tabla tipo_evento
                'certificado_path' => null,
            ]);
        }
    }
}
