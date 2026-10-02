<?php

namespace App\Services;

use App\Models\Emision;
use App\Models\EventoParticipante;
use App\Models\Participante;

class ReemitirCertificadosParticipante
{
    public function __construct(
        private readonly GenerarCertificadoEvento $generadorEvento,
        private readonly GenerarCertificadoEmision $generadorEmision,
    ) {}

    /**
     * Re-emite todos los certificados (eventos legacy y emisiones unificadas) de un
     * participante. Se usa cuando cambia su DNI, porque el DNI va impreso y en el
     * nombre del archivo.
     *
     * @return array{eventos:int, emisiones:int, omitidos:int}
     */
    public function participante(Participante $participante): array
    {
        $resultado = ['eventos' => 0, 'emisiones' => 0, 'omitidos' => 0];

        $participante->eventoParticipantes()
            ->whereNotNull('certificado_path')
            ->with(['evento.tipoEvento', 'rol'])
            ->get()
            ->each(function (EventoParticipante $relacion) use (&$resultado) {
                $this->reemitirEvento($relacion)
                    ? $resultado['eventos']++
                    : $resultado['omitidos']++;
            });

        Emision::query()
            ->where('participante_id', $participante->participante_id)
            ->whereNotNull('certificado_path')
            ->get()
            ->each(function (Emision $emision) use (&$resultado) {
                $this->reemitirEmision($emision)
                    ? $resultado['emisiones']++
                    : $resultado['omitidos']++;
            });

        return $resultado;
    }

    public function reemitirEvento(EventoParticipante $relacion): bool
    {
        return $this->generadorEvento->generar($relacion) !== null;
    }

    public function reemitirEmision(Emision $emision): bool
    {
        try {
            return (bool) $this->generadorEmision->generar($emision);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
