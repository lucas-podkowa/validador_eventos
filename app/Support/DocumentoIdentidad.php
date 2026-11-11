<?php

namespace App\Support;

class DocumentoIdentidad
{
    public const MIN = 3;

    public const MAX = 20;

    public const REGEX = '/^[A-Za-z0-9][A-Za-z0-9.\-\/]{2,19}$/';

    /**
     * Normaliza un documento para búsquedas y detección de duplicados:
     * mayúsculas y solo letras/números (sin puntos, guiones, barras ni espacios).
     */
    public static function normalizar(?string $valor): string
    {
        $valor = mb_strtoupper(trim((string) $valor));

        return preg_replace('/[^A-Z0-9]/', '', $valor) ?? '';
    }

    public static function esValido(?string $valor): bool
    {
        $valor = trim((string) $valor);

        return $valor !== ''
            && mb_strlen($valor) <= self::MAX
            && preg_match(self::REGEX, $valor) === 1;
    }

    /**
     * @return array<int, string>
     */
    public static function reglas(): array
    {
        return [
            'required',
            'string',
            'min:'.self::MIN,
            'max:'.self::MAX,
            'regex:'.self::REGEX,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function reglasOpcionales(): array
    {
        return [
            'nullable',
            'string',
            'min:'.self::MIN,
            'max:'.self::MAX,
            'regex:'.self::REGEX,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function mensajes(string $atributo = 'dni'): array
    {
        return [
            "{$atributo}.required" => 'El número de documento es obligatorio.',
            "{$atributo}.string" => 'El número de documento debe ser texto.',
            "{$atributo}.min" => 'El número de documento debe tener al menos '.self::MIN.' caracteres.',
            "{$atributo}.max" => 'El número de documento no puede superar los '.self::MAX.' caracteres.',
            "{$atributo}.regex" => 'El número de documento solo puede contener letras, números, puntos, guiones y barras.',
        ];
    }
}
