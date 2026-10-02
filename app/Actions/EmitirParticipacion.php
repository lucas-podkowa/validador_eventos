<?php

namespace App\Actions;

use App\Models\Emision;
use App\Models\Evento;
use App\Models\Participacion;
use App\Models\PlantillaCertificado;
use App\Services\GenerarCertificadoEmision;
use RuntimeException;

/**
 * Emite el certificado de una única participación (fila de una lista de emisión
 * masiva). Fuente única de verdad usada tanto por el procesamiento síncrono por
 * lotes como por el job de cola.
 */
class EmitirParticipacion
{
    public function __construct(private GenerarCertificadoEmision $emisor) {}

    public function handle(Participacion $participacion, PlantillaCertificado $plantilla, ?int $emitidoPor = null): Emision
    {
        $participacion->loadMissing(['participante', 'tipoReconocimiento', 'origen']);

        if ($participacion->estaEmitida()) {
            throw new RuntimeException('La participación ya fue emitida.');
        }

        if (! $participacion->participante || ! $participacion->tipoReconocimiento) {
            throw new RuntimeException('La participación no tiene participante o tipo de reconocimiento.');
        }

        $origen = $participacion->origen;
        $alcance = $origen instanceof Evento ? 'evento' : 'contexto';

        $emision = $this->emisor->emitir(
            participante: $participacion->participante,
            tipo: $participacion->tipoReconocimiento,
            plantilla: $plantilla,
            origen: $origen,
            alcance: $alcance,
            emitidoPor: $emitidoPor,
        );

        $participacion->update([
            'estado' => Participacion::ESTADO_EMITIDO,
            'emision_id' => $emision->emision_id,
        ]);

        return $emision;
    }
}
