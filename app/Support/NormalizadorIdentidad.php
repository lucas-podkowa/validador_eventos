<?php

namespace App\Support;

use Illuminate\Support\Str;

class NormalizadorIdentidad
{
    /**
     * Normaliza nombres/apellidos: sin acentos, minúsculas, sin signos y con espacios colapsados.
     */
    public static function nombre(?string $valor): string
    {
        $valor = Str::ascii((string) $valor);
        $valor = preg_replace('/[^A-Za-z0-9\s]/', ' ', $valor);
        $valor = preg_replace('/\s+/', ' ', (string) $valor);

        return mb_strtolower(trim((string) $valor));
    }

    /**
     * Normaliza teléfonos: solo dígitos, sin ceros iniciales.
     */
    public static function telefono(?string $valor): string
    {
        $valor = preg_replace('/\D+/', '', (string) $valor);

        return ltrim((string) $valor, '0');
    }

    public static function mail(?string $valor): string
    {
        return mb_strtolower(trim((string) $valor));
    }

    /**
     * Formato de presentación para nombres/apellidos: "López Ricci".
     */
    public static function titulo(?string $valor): string
    {
        return mb_convert_case(mb_strtolower(trim((string) $valor)), MB_CASE_TITLE, 'UTF-8');
    }
}
