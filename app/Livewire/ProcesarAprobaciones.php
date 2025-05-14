<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\EventoParticipante;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ProcesarAprobaciones extends Component
{
    public $eventos = [];
    public $eventoSeleccionado = null;
    public $participantes = [];
    public $estadoAprobacion = [];

    public function mount()
    {
        $this->eventos = Evento::where('por_aprobacion', true)
            ->where('estado', 'finalizado')
            // ->whereHas('participantes', function ($query) {
            //     $query->whereNull('evento_participantes.aprobado');
            // })
            ->get();
    }

    public function seleccionarEvento($eventoId)
    {
        $this->eventoSeleccionado = Evento::find($eventoId);

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

            DB::commit();
            $this->dispatch('success', message: 'Participantes actualizados correctamente.');
            $this->reset(['eventoSeleccionado', 'participantes']);
            $this->mount(); // recargar eventos
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo Actualizar el listado de Aprobados: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.procesar-aprobaciones');
    }
}
