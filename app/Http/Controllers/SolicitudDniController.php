<?php

namespace App\Http\Controllers;

use App\Models\SolicitudCorreccionDni;
use Illuminate\Support\Facades\Storage;

class SolicitudDniController extends Controller
{
    public function imagen(SolicitudCorreccionDni $solicitud)
    {
        $path = $solicitud->imagen_path;

        if (! $path || ! Storage::disk('private')->exists($path)) {
            abort(404, 'Imagen no encontrada.');
        }

        $response = response()->file(Storage::disk('private')->path($path));
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');

        return $response;
    }
}
