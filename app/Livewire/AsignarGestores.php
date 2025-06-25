<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\User;
use Livewire\Component;

class AsignarGestores extends Component
{
    public $evento_id;
    public $evento;
    public $gestores = [];
    public $gestoresSeleccionados = [];

    public function mount($evento_id)
    {
        $this->evento_id = $evento_id;
        $this->evento = Evento::findOrFail($evento_id);
        $this->gestores = User::role('gestor')->get();

        $this->gestoresSeleccionados = $this->evento->gestores->pluck('id')->toArray();
    }

    public function guardar()
    {
        $this->evento->gestores()->sync($this->gestoresSeleccionados);
        return redirect()->route('eventos', ['tab' => 'pendientes'])->with('message', 'Gestores asignados correctamente.');
    }
    public function redirectToEventos($tab)
    {
        return redirect()->route('eventos', ['tab' => $tab]);
    }

    public function render()
    {
        return view('livewire.asignar-gestores');
    }
}
