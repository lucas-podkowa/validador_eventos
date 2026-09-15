<?php

namespace App\Console\Commands;

use App\Models\Participante;
use App\Services\ReemitirCertificadosParticipante;
use Illuminate\Console\Command;

class ReemitirCertificadosParticipanteCommand extends Command
{
    protected $signature = 'participantes:reemitir-certificados {dni* : Uno o más DNI de participantes}';

    protected $description = 'Re-emite los certificados de eventos y títulos de uno o más participantes (útil tras corregir el DNI o fusionar duplicados)';

    public function handle(): int
    {
        foreach ($this->argument('dni') as $dni) {
            $participante = Participante::where('dni', $dni)->first();

            if (! $participante) {
                $this->warn("No se encontró un participante con DNI {$dni}.");

                continue;
            }

            $resultado = app(ReemitirCertificadosParticipante::class)->participante($participante);

            $this->info("DNI {$dni}: eventos reemitidos {$resultado['eventos']}, títulos {$resultado['titulos']}, omitidos {$resultado['omitidos']}.");
        }

        return self::SUCCESS;
    }
}
