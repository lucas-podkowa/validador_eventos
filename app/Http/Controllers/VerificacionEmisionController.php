<?php

namespace App\Http\Controllers;

use App\Models\Emision;

class VerificacionEmisionController extends Controller
{
    public function show(string $codigo)
    {
        $emision = Emision::query()
            ->with(['participante', 'tipoReconocimiento'])
            ->where('codigo_verificacion', $codigo)
            ->first();

        $valido = $emision && $emision->esValida();
        $anulado = $emision && $emision->estaAnulada();

        $fondo = $valido ? 'cert_valido.png' : 'cert_no_valido.png';
        $path = storage_path("app/private/img_validacion/{$fondo}");

        if (! file_exists($path)) {
            abort(404, 'Imagen de validación no encontrada.');
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $base64 = 'data:image/'.$type.';base64,'.base64_encode(file_get_contents($path));

        return view('verificacion-emision', compact('emision', 'valido', 'anulado', 'base64'));
    }
}
