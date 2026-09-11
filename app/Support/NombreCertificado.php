<?php

namespace App\Support;

class NombreCertificado
{
    /**
     * Cantidad máxima de caracteres de "Apellido, Nombre" que entran en el certificado.
     */
    public const LIMITE = 36;

    /**
     * Texto tal como se muestra en el certificado.
     */
    public static function formatear(?string $apellido, ?string $nombre): string
    {
        return self::normalizar($apellido).', '.self::normalizar($nombre);
    }

    /**
     * Cantidad de caracteres (multibyte) que ocupa "Apellido, Nombre".
     */
    public static function longitud(?string $apellido, ?string $nombre): int
    {
        return mb_strlen(self::formatear($apellido, $nombre));
    }

    /**
     * Indica si el texto supera el límite. Devuelve false si falta alguno de los datos
     * para no interferir con las validaciones "required".
     */
    public static function excede(?string $apellido, ?string $nombre): bool
    {
        if (self::normalizar($apellido) === '' || self::normalizar($nombre) === '') {
            return false;
        }

        return self::longitud($apellido, $nombre) > self::LIMITE;
    }

    /**
     * Mensaje de validación detallado para mostrar al usuario.
     */
    public static function mensaje(?string $apellido, ?string $nombre): string
    {
        $largo = self::longitud($apellido, $nombre);

        return "El apellido y nombre ocupan {$largo} caracteres y el máximo para el certificado es ".self::LIMITE.
            '. Abreviá uno de los nombres con una inicial seguida de punto (por ejemplo, «Cuello» → «C.»).';
    }

    /**
     * Texto listo para el certificado: se devuelve igual si entra, o abreviado si supera el límite.
     */
    public static function paraCertificado(?string $apellido, ?string $nombre): string
    {
        if (! self::excede($apellido, $nombre)) {
            return self::formatear($apellido, $nombre);
        }

        return self::abreviar($apellido, $nombre);
    }

    /**
     * Abrevia nombres y apellidos de forma determinística hasta entrar en el límite.
     * Siempre preserva la primera palabra del apellido y la primera del nombre.
     */
    public static function abreviar(?string $apellido, ?string $nombre): string
    {
        $apellido = self::normalizar($apellido);
        $nombre = self::normalizar($nombre);

        if (self::longitud($apellido, $nombre) <= self::LIMITE) {
            return self::formatear($apellido, $nombre);
        }

        $apellidoTokens = self::tokenizar($apellido);
        $nombreTokens = self::tokenizar($nombre);

        // 1. Abreviar nombres intermedios (ni el primero ni el último), de derecha a izquierda.
        for ($i = count($nombreTokens) - 2; $i >= 1; $i--) {
            $nombreTokens[$i] = self::inicial($nombreTokens[$i]);

            if (self::cabe($apellidoTokens, $nombreTokens)) {
                return self::unir($apellidoTokens, $nombreTokens);
            }
        }

        // 2. Abreviar el resto de los nombres de derecha a izquierda, nunca el primero.
        for ($i = count($nombreTokens) - 1; $i >= 1; $i--) {
            $nombreTokens[$i] = self::inicial($nombreTokens[$i]);

            if (self::cabe($apellidoTokens, $nombreTokens)) {
                return self::unir($apellidoTokens, $nombreTokens);
            }
        }

        // 3. Abreviar apellidos intermedios, nunca el primero.
        for ($i = count($apellidoTokens) - 1; $i >= 1; $i--) {
            $apellidoTokens[$i] = self::inicial($apellidoTokens[$i]);

            if (self::cabe($apellidoTokens, $nombreTokens)) {
                return self::unir($apellidoTokens, $nombreTokens);
            }
        }

        // 4. Último recurso: recortar el resultado.
        return mb_substr(self::unir($apellidoTokens, $nombreTokens), 0, self::LIMITE);
    }

    protected static function cabe(array $apellidoTokens, array $nombreTokens): bool
    {
        return mb_strlen(self::unir($apellidoTokens, $nombreTokens)) <= self::LIMITE;
    }

    protected static function unir(array $apellidoTokens, array $nombreTokens): string
    {
        return implode(' ', $apellidoTokens).', '.implode(' ', $nombreTokens);
    }

    protected static function inicial(string $token): string
    {
        $token = trim($token);

        return $token === '' ? '' : mb_substr($token, 0, 1).'.';
    }

    protected static function tokenizar(string $valor): array
    {
        if ($valor === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $valor), fn ($token) => $token !== ''));
    }

    protected static function normalizar(?string $valor): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $valor));
    }
}
