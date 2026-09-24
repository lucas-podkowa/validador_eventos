<?php

namespace App\Support;

class CertificadoLayout
{
    /**
     * Indica si un campo del layout se renderiza como imagen.
     */
    public static function esImagen(?string $campo): bool
    {
        $campo = (string) $campo;

        return $campo === 'qr' || str_ends_with($campo, '_imagen');
    }

    /**
     * Devuelve el número de slot (1..N) de un campo de firma, o null si no aplica.
     */
    public static function slotFirma(?string $campo): ?int
    {
        if (preg_match('/^firma_(\d+)_(imagen|nombre|cargo)$/', (string) $campo, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Resuelve el HTML de un bloque de texto, con los tokens ya reemplazados
     * y los valores del participante escapados.
     */
    public static function textoBloque(array $bloque, ?string $texto, array $variables): string
    {
        $campo = (string) ($bloque['campo'] ?? 'literal');

        if ($campo === 'literal') {
            return CertificadoVariables::reemplazar($bloque['contenido'] ?? '', $variables);
        }

        if ($campo === 'texto_cuerpo') {
            return CertificadoVariables::reemplazar($texto, $variables);
        }

        if (preg_match('/^firma_(\d+)_(nombre|cargo)$/', $campo, $m)) {
            return e($variables["firmante_{$m[1]}_{$m[2]}"] ?? '');
        }

        return e($variables[$campo] ?? '');
    }

    /**
     * Devuelve el estilo inline CSS de un bloque.
     */
    public static function estilo(array $bloque): string
    {
        $campo = (string) ($bloque['campo'] ?? 'literal');
        $esImagen = self::esImagen($campo);

        $x = (float) ($bloque['x'] ?? 0);
        $y = (float) ($bloque['y'] ?? 0);
        $w = (float) ($bloque['w'] ?? 0);
        $h = (float) ($bloque['h'] ?? 0);

        $style = "left:{$x}%;top:{$y}%;";

        if ($w > 0) {
            $style .= "width:{$w}%;";
        }

        if ($h > 0) {
            $style .= "height:{$h}%;";
        }

        if (! $esImagen) {
            $size = (float) ($bloque['size'] ?? 14);
            $align = (string) ($bloque['align'] ?? 'left');
            $color = (string) ($bloque['color'] ?? '#0A1B3A');

            $style .= "text-align:{$align};color:{$color};font-size:{$size}px;line-height:1.3;";

            if (! empty($bloque['bold'])) {
                $style .= 'font-weight:700;';
            }

            if (! empty($bloque['italic'])) {
                $style .= 'font-style:italic;';
            }
        }

        return $style;
    }
}
