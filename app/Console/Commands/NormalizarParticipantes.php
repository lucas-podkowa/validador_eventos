<?php

namespace App\Console\Commands;

use App\Models\Participante;
use Illuminate\Console\Command;

class NormalizarParticipantes extends Command
{
    protected $signature = 'participantes:normalizar {--dry-run : Muestra cuántos se normalizarían sin guardar}';

    protected $description = 'Normaliza los nombres/apellidos a Title Case y rellena las columnas normalizadas (nombre_norm, apellido_norm, telefono_norm, mail_norm)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        Participante::query()->orderBy('apellido')->chunk(200, function ($participantes) use ($dryRun, &$total) {
            foreach ($participantes as $participante) {
                $total++;

                if (! $dryRun) {
                    // Re-asignar para disparar el set mutator de Title Case (la hidratación no lo aplica).
                    $participante->nombre = $participante->nombre;
                    $participante->apellido = $participante->apellido;
                    $participante->save();
                }
            }
        });

        $this->info(($dryRun ? 'Se normalizarían ' : 'Se normalizaron ').$total.' participantes.');

        return self::SUCCESS;
    }
}
