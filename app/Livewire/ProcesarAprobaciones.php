<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\EventoParticipante;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ProcesarAprobaciones extends Component
{
    public $eventos = [];
    public $eventoSeleccionado = null;
    public $participantes = [];
    public $estadoAprobacion = [];
    protected $listeners = ['finalizarRevision'];

    public function mount()
    {
        $usuario = Auth::user();

        $query = Evento::where('por_aprobacion', true)
            ->where('estado', 'finalizado')
            ->where('revisado', false);

        if ($usuario->hasRole('Revisor')) {
            $query->where('revisor_id', $usuario->id);
        }

        $this->eventos = $query->get();
    }


    public function seleccionarEvento($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);

        if (Auth::user()->hasRole('revisor') && $evento->revisor_id !== Auth::id()) {
            abort(403, 'No autorizado a ver este evento.');
        }

        $this->eventoSeleccionado = $evento;

        $this->participantes = EventoParticipante::with('participante')
            ->where('evento_id', $eventoId)
            ->get();

        foreach ($this->participantes as $p) {
            $this->estadoAprobacion[$p->evento_participantes_id] = $p->aprobado ?? false;
        }
    }



    public function actualizarEstado($index, $valor)
    {
        $this->participantes[$index]['aprobado'] = $valor;
        // Sincronizar con estadoAprobacion
        $eventoParticipantesId = $this->participantes[$index]['evento_participantes_id'];
        $this->estadoAprobacion[$eventoParticipantesId] = $valor;
    }

    public function guardar()
    {
        DB::beginTransaction();

        try {
            //dd($this->estadoAprobacion); // solo para debug
            foreach ($this->participantes as $p) {
                $p->update(['aprobado' => $this->estadoAprobacion[$p->evento_participantes_id]]);
            }
            //$this->eventoSeleccionado->update(['revisado' => true]);

            DB::commit();
            $this->dispatch('alert', message: 'Participantes actualizados correctamente.');
            $this->reset(['eventoSeleccionado', 'participantes']);
            $this->mount(); // recargar eventos
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo Actualizar el listado de Aprobados: ' . $e->getMessage());
        }
    }

    public function finalizarRevision()
    {
        try {
            $this->eventoSeleccionado->update(['revisado' => true]);
            $this->reset(['eventoSeleccionado', 'participantes']);
            $this->mount();
        } catch (\Throwable $e) {
            $this->dispatch('oops', message: 'Error al finalizar la revisión: ' . $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.procesar-aprobaciones');
    }
}
