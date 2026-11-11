<?php

namespace App\Models\Concerns;

use App\Support\DocumentoIdentidad;
use Illuminate\Database\Eloquent\Builder;

trait MantieneDocumentoNormalizado
{
    public static function bootMantieneDocumentoNormalizado(): void
    {
        static::saving(function ($model) {
            $documento = trim((string) $model->dni);

            $model->dni = $model->dni === null ? null : $documento;
            $model->dni_norm = DocumentoIdentidad::normalizar($documento) ?: null;
        });
    }

    public function scopePorDocumento(Builder $query, ?string $documento): Builder
    {
        $normalizado = DocumentoIdentidad::normalizar($documento);

        if ($normalizado === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($this->getTable().'.dni_norm', $normalizado);
    }
}
