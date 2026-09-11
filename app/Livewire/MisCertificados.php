<?php

namespace App\Livewire;

use App\Models\CertificadoEmitido;
use App\Models\EventoParticipante;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class MisCertificados extends Component
{
    public function render()
    {
        $user = auth()->user();
        $participante = $user?->participante;

        $certificadosEventos = collect();
        $certificadosTitulos = collect();

        if ($participante) {
            $certificadosEventos = EventoParticipante::query()
                ->with(['evento.tipoEvento', 'rol'])
                ->where('participante_id', $participante->participante_id)
                ->whereNotNull('certificado_path')
                ->get()
                ->filter(fn (EventoParticipante $ep) => Storage::disk('private')->exists($ep->certificado_path))
                ->sortByDesc(fn (EventoParticipante $ep) => optional($ep->evento)->fecha_inicio);

            $certificadosTitulos = CertificadoEmitido::query()
                ->with('tituloIntermedio.carrera')
                ->where('participante_id', $participante->participante_id)
                ->where('anulado', false)
                ->orderByDesc('created_at')
                ->get()
                ->filter(fn (CertificadoEmitido $certificado) => $certificado->certificado_path
                    && Storage::disk('private')->exists($certificado->certificado_path));
        }

        return view('livewire.mis-certificados', [
            'participante' => $participante,
            'certificadosEventos' => $certificadosEventos,
            'certificadosTitulos' => $certificadosTitulos,
        ]);
    }
}
