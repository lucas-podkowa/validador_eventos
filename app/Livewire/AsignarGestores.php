<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\User;
use Livewire\Component;

class AsignarGestores extends Component
{
    public $evento_id;
    public $evento;
    public $gestoresSeleccionados = [];
    public $searchGestor = '';

    public function mount($evento_id)
    {
        $this->evento_id = $evento_id;
        $this->evento = Evento::findOrFail($evento_id);
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
        $query = User::role('gestor');

        // Si hay algo en el buscador, aplica el filtro
        if (!empty($this->searchGestor)) {
            $searchTerm = '%' . $this->searchGestor . '%';
            // Asumiendo que el nombre y apellido están en la columna 'name'
            $query->where('name', 'like', $searchTerm);
        }

        // Obtiene los gestores filtrados (o todos si el buscador está vacío)
        $gestores = $query->orderBy('name')->get();

        return view('livewire.asignar-gestores', [
            'gestores' => $gestores,
        ]);
    }
}
