<?php

namespace App\Livewire;

use App\Models\Emision;
use App\Models\Evento;
use App\Models\EventoParticipante;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class MisCertificados extends Component
{
    public function render()
    {
        $user = auth()->user();
        $participante = $user?->participante;

        $emisiones = collect();
        $legacyEventos = collect();

        if ($participante) {
            $emisiones = Emision::query()
                ->with('tipoReconocimiento')
                ->where('participante_id', $participante->participante_id)
                ->orderByDesc('emitida_en')
                ->get()
                ->filter(fn (Emision $emision) => $emision->certificado_path
                    && Storage::disk('private')->exists($emision->certificado_path));

            $legacyEventos = EventoParticipante::query()
                ->with(['evento.tipoEvento', 'rol'])
                ->where('participante_id', $participante->participante_id)
                ->whereNotNull('certificado_path')
                ->get()
                ->filter(fn (EventoParticipante $ep) => Storage::disk('private')->exists($ep->certificado_path))
                ->filter(fn (EventoParticipante $ep) => ! $this->tieneEmision($emisiones, $ep))
                ->sortByDesc(fn (EventoParticipante $ep) => optional($ep->evento)->fecha_inicio);
        }

        return view('livewire.mis-certificados', [
            'participante' => $participante,
            'emisiones' => $emisiones,
            'legacyEventos' => $legacyEventos,
        ]);
    }

    private function tieneEmision($emisiones, EventoParticipante $ep): bool
    {
        return $emisiones->contains(
            fn (Emision $emision) => $emision->origen_type === Evento::class
                && (string) $emision->origen_id === (string) $ep->evento_id
                && (string) $emision->participante_id === (string) $ep->participante_id
        );
    }
}
