<?php

namespace App\Http\Controllers;

use App\Models\CertificadoExterno;

class VerificacionExternaController extends Controller
{
    public function show(string $codigo)
    {
        $certificado = CertificadoExterno::query()
            ->where('codigo_verificacion', $codigo)
            ->first();

        $valido = $certificado && $certificado->estado === CertificadoExterno::ESTADO_EMITIDO;

        $fondo = $valido ? 'cert_valido.png' : 'cert_no_valido.png';
        $path = storage_path("app/private/img_validacion/{$fondo}");

        if (! file_exists($path)) {
            abort(404, 'Imagen de validación no encontrada.');
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $base64 = 'data:image/'.$type.';base64,'.base64_encode(file_get_contents($path));

        return view('verificacion-externa', compact('certificado', 'valido', 'base64'));
    }
}
