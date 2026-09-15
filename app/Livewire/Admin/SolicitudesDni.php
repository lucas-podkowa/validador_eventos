<?php

namespace App\Livewire\Admin;

use App\Models\Participante;
use App\Models\SolicitudCorreccionDni;
use App\Services\ReemitirCertificadosParticipante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class SolicitudesDni extends Component
{
    use WithPagination;

    public string $estado = 'pendiente';

    public $solicitud_selected = null;

    public bool $open_modal = false;

    public $observacion = '';

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    public function ver($id): void
    {
        $this->resetValidation();
        $this->solicitud_selected = SolicitudCorreccionDni::with(['participante', 'user'])
            ->findOrFail($id);
        $this->observacion = '';
        $this->open_modal = true;
    }

    public function aprobar(): void
    {
        $solicitud = $this->solicitud_selected;

        if (! $solicitud || $solicitud->estado !== SolicitudCorreccionDni::ESTADO_PENDIENTE) {
            return;
        }

        $ocupado = Participante::where('dni', $solicitud->dni_solicitado)
            ->where('participante_id', '!=', $solicitud->participante_id)
            ->exists();

        if ($ocupado) {
            $this->addError('observacion', 'El DNI solicitado ya pertenece a otro participante. Revisá y fusioná manualmente.');

            return;
        }

        $participante = $solicitud->participante;
        $dniAnterior = $participante->dni;

        DB::transaction(function () use ($solicitud, $participante, $dniAnterior) {
            $participante->dni = $solicitud->dni_solicitado;
            $participante->save();

            if ($participante->user_id) {
                $user = $participante->user;
                $ocupadoPorOtro = $user && $user->id !== null
                    ? \App\Models\User::where('dni', $solicitud->dni_solicitado)->where('id', '!=', $user->id)->exists()
                    : false;

                if ($user && ! $ocupadoPorOtro && ($user->dni === null || (string) $user->dni === (string) $dniAnterior)) {
                    $user->dni = $solicitud->dni_solicitado;
                    $user->save();
                }
            }

            $solicitud->update([
                'estado' => SolicitudCorreccionDni::ESTADO_APROBADA,
                'revisado_por' => auth()->id(),
                'revisado_en' => now(),
                'observacion' => $this->observacion ?: null,
            ]);
        });

        try {
            app(ReemitirCertificadosParticipante::class)->participante($participante->fresh());
        } catch (\Throwable $e) {
            Log::error('Error al re-emitir certificados tras corrección de DNI.', [
                'participante_id' => $participante->participante_id,
                'message' => $e->getMessage(),
            ]);
            $this->dispatch('oops', message: 'El DNI se actualizó, pero falló la re-emisión de certificados. Reemitilos manualmente.');
            $this->open_modal = false;

            return;
        }

        $this->open_modal = false;
        $this->solicitud_selected = null;
        $this->dispatch('alert', message: 'DNI actualizado y certificados re-emitidos.');
    }

    public function rechazar(): void
    {
        $solicitud = $this->solicitud_selected;

        if (! $solicitud || $solicitud->estado !== SolicitudCorreccionDni::ESTADO_PENDIENTE) {
            return;
        }

        $solicitud->update([
            'estado' => SolicitudCorreccionDni::ESTADO_RECHAZADA,
            'revisado_por' => auth()->id(),
            'revisado_en' => now(),
            'observacion' => $this->observacion ?: null,
        ]);

        $this->open_modal = false;
        $this->solicitud_selected = null;
        $this->dispatch('alert', message: 'Solicitud rechazada.');
    }

    public function render()
    {
        $solicitudes = SolicitudCorreccionDni::with(['participante', 'user'])
            ->when($this->estado !== 'todas', fn ($query) => $query->where('estado', $this->estado))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.solicitudes-dni', compact('solicitudes'));
    }
}
