<?php

namespace App\Http\Controllers;

use App\Models\InscripcionParticipante;
use Illuminate\Support\Facades\Storage;

class ComprobantePagoController extends Controller
{
    public function show(InscripcionParticipante $inscripcion)
    {
        abort_if(! $inscripcion->comprobante_pago, 404);
        abort_if(! Storage::disk('private')->exists($inscripcion->comprobante_pago), 404);

        return Storage::disk('private')->download($inscripcion->comprobante_pago);
    }
}
