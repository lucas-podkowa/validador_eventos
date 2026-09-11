<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class Texto
{
    private const MAPA = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n', 'ç' => 'c',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        'Ü' => 'u', 'Ñ' => 'n', 'Ç' => 'c',
        'À' => 'a', 'È' => 'e', 'Ì' => 'i', 'Ò' => 'o', 'Ù' => 'u',
        'Â' => 'a', 'Ê' => 'e', 'Î' => 'i', 'Ô' => 'o', 'Û' => 'u',
        'Ä' => 'a', 'Ë' => 'e', 'Ï' => 'i', 'Ö' => 'o',
    ];

    private const MIN_CARACTERES = 3;

    /**
     * Normaliza un texto: minúsculas, sin tildes ni diacríticos.
     */
    public static function normalizar(string $valor): string
    {
        return mb_strtolower(strtr(trim($valor), self::MAPA), 'UTF-8');
    }

    /**
     * Construye la expresión SQL que normaliza una columna (minúsculas y sin
     * tildes) para poder compararla contra un término normalizado en PHP.
     * Compatible con MySQL y SQLite.
     */
    public static function expresionNormalizada(string $columna): string
    {
        $expresion = $columna;

        foreach (self::MAPA as $de => $a) {
            $de = str_replace("'", "''", $de);
            $expresion = "REPLACE({$expresion}, '{$de}', '{$a}')";
        }

        return "LOWER({$expresion})";
    }

    /**
     * Aplica un filtro LIKE normalizado (insensible a mayúsculas y tildes)
     * sobre una o varias columnas (combinadas con OR).
     * Solo filtra cuando el término tiene al menos MIN_CARACTERES caracteres.
     * Devuelve true si el filtro fue aplicado.
     */
    public static function aplicarFiltroLike(Builder $query, array|string $columnas, ?string $termino): bool
    {
        $columnas = (array) $columnas;
        $termino = trim((string) $termino);

        if (mb_strlen($termino, 'UTF-8') < self::MIN_CARACTERES || $columnas === []) {
            return false;
        }

        $terminoSql = '%'.self::normalizar($termino).'%';

        $esMysql = in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb']);

        $query->where(function (Builder $q) use ($columnas, $terminoSql, $esMysql) {
            foreach ($columnas as $i => $columna) {
                // MySQL: el collation utf8mb4_unicode_ci ya ignora mayúsculas y tildes.
                $expresion = $esMysql ? $columna : self::expresionNormalizada($columna);

                if ($i === 0) {
                    $q->whereRaw("{$expresion} LIKE ?", [$terminoSql]);
                } else {
                    $q->orWhereRaw("{$expresion} LIKE ?", [$terminoSql]);
                }
            }
        });

        return true;
    }
}
