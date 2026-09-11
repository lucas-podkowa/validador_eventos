<?php

namespace App\Http\Controllers;

use App\Models\CertificadoEmitido;
use App\Models\EventoParticipante;
use Illuminate\Support\Facades\Storage;

class CertificadoDescargaController extends Controller
{
    public function evento(EventoParticipante $eventoParticipante)
    {
        $this->autorizarEvento($eventoParticipante);

        $path = $eventoParticipante->certificado_path;

        if (! $path || ! Storage::disk('private')->exists($path)) {
            abort(404, 'Certificado no encontrado.');
        }

        return $this->servir($path);
    }

    public function titulo(CertificadoEmitido $certificadoEmitido)
    {
        $this->autorizarTitulo($certificadoEmitido);

        $path = $certificadoEmitido->certificado_path;

        if (! $path || ! Storage::disk('private')->exists($path)) {
            abort(404, 'Certificado no encontrado.');
        }

        return $this->servir($path);
    }

    protected function autorizarEvento(EventoParticipante $eventoParticipante): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $esDuenio = $eventoParticipante->participante
            && (int) $eventoParticipante->participante->user_id === (int) $user->id;

        abort_unless(
            $esDuenio || $user->hasAnyRole(['Administrador', 'Gestor']),
            403,
            'No autorizado para ver este certificado.'
        );
    }

    protected function autorizarTitulo(CertificadoEmitido $certificadoEmitido): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $esDuenio = $certificadoEmitido->participante
            && (int) $certificadoEmitido->participante->user_id === (int) $user->id;

        abort_unless(
            $esDuenio || $user->hasAnyRole(['Administrador', 'Académica']),
            403,
            'No autorizado para ver este certificado.'
        );
    }

    protected function servir(string $path)
    {
        $response = response()->file(Storage::disk('private')->path($path));

        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
