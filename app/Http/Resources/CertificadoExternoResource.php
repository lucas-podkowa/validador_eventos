<?php

namespace App\Http\Resources;

use App\Models\Contexto;
use App\Support\NombreCertificado;
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
            'alcance' => $this->alcance,
            'estado' => $this->estado,
            'match_estado' => $this->match_estado,
            'receptor' => [
                'nombre' => NombreCertificado::paraCertificado(
                    $this->participante?->apellido,
                    $this->participante?->nombre,
                ),
                'dni' => $this->participante?->dni,
                'email' => $this->participante?->mail,
            ],
            'contexto' => $this->when(
                $this->origen instanceof Contexto,
                fn () => [
                    'id' => $this->origen->contexto_id,
                    'nombre' => $this->origen->nombre,
                ],
            ),
            'plantilla' => $this->when(
                $this->relationLoaded('plantilla') && $this->plantilla,
                fn () => [
                    'id' => $this->plantilla->plantilla_id,
                    'codigo' => $this->plantilla->nombre,
                ],
            ),
            'verificacion_url' => route('verificar', ['codigo' => $this->codigo_verificacion]),
            'emitido_en' => optional($this->emitida_en ?? $this->created_at)->toIso8601String(),
        ];
    }
}
