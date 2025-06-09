<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Participante;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class QRController extends Controller
{

    public function show($evento_id, $participante_id)
    {
        try {
            $evento = Evento::findOrFail($evento_id);
            $participante = Participante::findOrFail($participante_id);
            $fondo = 'cert_valido.png';
        } catch (ModelNotFoundException $e) {
            $evento = null;
            $participante = null;
            $fondo = 'cert_no_valido.png';
        }

        $path = storage_path("app/private/img_validacion/{$fondo}");

        if (!file_exists($path)) {
            abort(404, "Imagen de fondo no encontrada.");
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return view('livewire.card-validacion', compact('evento', 'participante', 'base64'));
    }
}
