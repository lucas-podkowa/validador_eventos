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

        $path_fondo = asset('storage/images/' . $fondo);
        return view('livewire.card-validacion', compact('evento', 'participante', 'path_fondo'));
    }
}
