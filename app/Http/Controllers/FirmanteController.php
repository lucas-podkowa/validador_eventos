<?php

namespace App\Http\Controllers;

use App\Models\Firmante;
use App\Support\CertificadoPdfAssets;
use Symfony\Component\HttpFoundation\Response;

class FirmanteController extends Controller
{
    /**
     * Sirve un preview de la firma con marca de agua. El archivo original vive en el
     * disco privado y sólo lo consume Dompdf al componer el PDF.
     */
    public function imagen(Firmante $firmante): Response
    {
        $path = CertificadoPdfAssets::resolvePrivatePath($firmante->imagen_firma_path);

        if (! $path) {
            abort(404, 'Firma no encontrada.');
        }

        $headers = [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ];

        if (! extension_loaded('gd')) {
            return response()->file($path, $headers);
        }

        $info = @getimagesize($path);
        $source = null;

        if (is_array($info)) {
            $source = match ($info[2]) {
                IMAGETYPE_PNG => @imagecreatefrompng($path),
                IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
                default => null,
            };
        }

        if (! $source) {
            return response()->file($path, $headers);
        }

        imagealphablending($source, true);
        imagesavealpha($source, true);

        $width = imagesx($source);
        $height = imagesy($source);

        $color = imagecolorallocatealpha($source, 30, 30, 30, 95);
        $font = CertificadoPdfAssets::fontPath();
        $label = 'PREVIEW';

        $posiciones = [[0.08, 0.35], [0.42, 0.70], [0.68, 0.20]];

        foreach ($posiciones as [$px, $py]) {
            if (is_readable($font) && function_exists('imagettftext')) {
                @imagettftext($source, max(12, (int) ($width * 0.06)), 35, (int) ($width * $px), (int) ($height * $py), $color, $font, $label);
            } else {
                @imagestring($source, 5, (int) ($width * $px), (int) ($height * $py), $label, $color);
            }
        }

        ob_start();
        imagepng($source);
        $data = (string) ob_get_clean();
        imagedestroy($source);

        return response($data, 200, $headers);
    }
}
