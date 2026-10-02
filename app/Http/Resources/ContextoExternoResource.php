<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContextoExternoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->contexto_id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'denominacion' => $this->denominacion,
            'institucion' => $this->institucion,
            'anio' => $this->anio,
            'resolucion' => $this->resolucion,
            'lugar' => $this->lugar,
            'plantillas' => $this->plantillas->map(fn ($plantilla) => [
                'id' => $plantilla->plantilla_id,
                'codigo' => $plantilla->nombre,
                'tipo' => $plantilla->tipoReconocimiento?->slug,
                'por_defecto' => (bool) $plantilla->por_defecto,
            ])->values(),
        ];
    }
}
