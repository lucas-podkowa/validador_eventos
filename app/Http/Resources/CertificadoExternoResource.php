<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificadoExternoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->emision_id,
            'external_ref' => $this->external_ref,
            'tipo' => $this->tipoReconocimiento?->slug,
            'estado' => $this->estado,
            'match_estado' => $this->match_estado,
            'receptor' => [
                'nombre' => trim(($this->participante?->nombre ?? '').' '.($this->participante?->apellido ?? '')),
                'dni' => $this->participante?->dni,
                'email' => $this->participante?->mail,
            ],
            'verificacion_url' => route('verificar', ['codigo' => $this->codigo_verificacion]),
            'emitido_en' => optional($this->emitida_en ?? $this->created_at)->toIso8601String(),
        ];
    }
}
