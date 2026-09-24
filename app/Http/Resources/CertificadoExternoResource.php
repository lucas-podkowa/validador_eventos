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
            'id' => $this->certificado_externo_id,
            'external_ref' => $this->external_ref,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'match_estado' => $this->match_estado,
            'receptor' => [
                'nombre' => $this->receptor_nombre,
                'dni' => $this->receptor_dni,
                'email' => $this->receptor_email,
            ],
            'verificacion_url' => route('verificar.externo', ['codigo' => $this->codigo_verificacion]),
            'emitido_en' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
