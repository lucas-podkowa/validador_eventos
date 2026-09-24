<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class FechaCertificado
{
    /**
     * Formatea un rango de fechas en lenguaje natural para el certificado.
     * Ej.: "del 25 al 28 de agosto de 2026" / "del 25 de agosto de 2026".
     */
    public static function rango(CarbonInterface|string|null $inicio, CarbonInterface|string|null $fin = null): string
    {
        if (! $inicio) {
            return '';
        }

        $inicio = self::normalizar($inicio);

        if (! $fin) {
            return 'del '.$inicio->isoFormat('D [de] MMMM [de] YYYY');
        }

        $fin = self::normalizar($fin);

        if ($inicio->isSameDay($fin)) {
            return 'del '.$inicio->isoFormat('D [de] MMMM [de] YYYY');
        }

        if ($inicio->year === $fin->year && $inicio->month === $fin->month) {
            return 'del '.$inicio->isoFormat('D').' al '.$fin->isoFormat('D [de] MMMM [de] YYYY');
        }

        if ($inicio->year === $fin->year) {
            return 'del '.$inicio->isoFormat('D [de] MMMM').' al '.$fin->isoFormat('D [de] MMMM [de] YYYY');
        }

        return 'del '.$inicio->isoFormat('D [de] MMMM [de] YYYY').' al '.$fin->isoFormat('D [de] MMMM [de] YYYY');
    }

    private static function normalizar(CarbonInterface|string $fecha): CarbonInterface
    {
        $carbon = $fecha instanceof CarbonInterface ? $fecha->copy() : Carbon::parse($fecha);

        return $carbon->locale('es');
    }
}
