<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Indicador;
use App\Models\TipoIndicador;
use Livewire\Attributes\On;

class Indicadores extends Component
{
    public $tipo_indicadores;
    public $indicadores;

    public $showTipoModal = false;
    public $showIndicadorModal = false;

    public $isCreatingTipo = false;
    public $isCreatingIndicador = false;

    public $sortField = 'nombre';
    public $sortDirection = 'asc';


    // Formulario para TipoIndicador
    public $tipo_nombre, $editingTipoId = null;
    public $tipo_selector = 'Selección Única'; // valor por defecto

    // Formulario para Indicador
    public $indicador_nombre, $tipo_indicador_id, $editingIndicadorId = null;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->tipo_indicadores = TipoIndicador::orderBy('nombre', 'asc')->get(); // sigue como antes

        $this->indicadores = Indicador::with('tipoIndicador')
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadData();
    }



    // TIPO INDICADOR
    //----------------------------------------------------
    public function createTipo()
    {
        $this->reset(['tipo_nombre', 'tipo_selector', 'editingTipoId']);
        $this->tipo_selector = 'Selección Única'; // por defecto
        $this->isCreatingTipo = true;
        $this->showTipoModal = true;
    }

    public function editTipo($id)
    {
        $tipo = TipoIndicador::findOrFail($id);
        $this->tipo_nombre = $tipo->nombre;
        $this->tipo_selector = $tipo->selector;
        $this->editingTipoId = $tipo->tipo_indicador_id;
        $this->showTipoModal = true;
    }

    public function saveTipo()
    {

        $this->validate([
            'tipo_nombre' => 'required|string|max:255',
            'tipo_selector' => 'required|in:Selección Única,Selección Múltiple,Texto Libre',
        ]);

        if ($this->isCreatingTipo) {
            TipoIndicador::create(['nombre' => $this->tipo_nombre]);
        } else {
            $tipo = TipoIndicador::findOrFail($this->editingTipoId);
            $tipo->update([
                'nombre' => $this->tipo_nombre,
                'selector' => $this->tipo_selector,
            ]);
        }

        $this->showTipoModal = false;
        $this->isCreatingTipo = false;
        $this->loadData(); // si tenés un método para recargar
    }

    #[On('deleteTipo')]
    public function deleteTipo($tipo_indicador_id)
    {
        // Verificar si el tipo de indicador tiene indicadores asociados
        $tipoIndicador = TipoIndicador::findOrFail($tipo_indicador_id);
        if ($tipoIndicador->indicadores()->exists()) {
            $this->dispatch('oops', message: 'No se puede eliminar el tipo de indicador porque tiene indicadores asociados.');
            return;
        }
        // Si no tiene indicadores asociados, proceder a eliminar
        $tipoIndicador->delete();
        $this->loadData();
    }


    // INDICADOR
    //----------------------------------------------------
    public function createIndicador()
    {
        $this->reset(['indicador_nombre', 'tipo_indicador_id', 'editingIndicadorId']);
        $this->isCreatingIndicador = true;
        $this->showIndicadorModal = true;
    }

    public function editIndicador($id)
    {
        $indicador = Indicador::findOrFail($id);
        $this->indicador_nombre = $indicador->nombre;
        $this->tipo_indicador_id = $indicador->tipo_indicador_id;
        $this->editingIndicadorId = $indicador->indicador_id;
        $this->showIndicadorModal = true;
    }

    public function saveIndicador()
    {
        $this->validate([
            'indicador_nombre' => 'required|string|max:255',
            'tipo_indicador_id' => 'required|exists:tipo_indicador,tipo_indicador_id',
        ]);

        if ($this->isCreatingIndicador) {
            Indicador::create([
                'nombre' => $this->indicador_nombre,
                'tipo_indicador_id' => $this->tipo_indicador_id,
            ]);
        } else {
            $indicador = Indicador::findOrFail($this->editingIndicadorId);
            $indicador->update([
                'nombre' => $this->indicador_nombre,
                'tipo_indicador_id' => $this->tipo_indicador_id,
            ]);
        }

        $this->showIndicadorModal = false;
        $this->isCreatingIndicador = false;
        $this->loadData(); // o reload de la data
    }


    #[On('deleteIndicador')]
    public function deleteIndicador($indicador_id)
    {
        // Verificar si el indicador tiene inscripciones asociadas
        $indicador = Indicador::findOrFail($indicador_id);
        if ($indicador->hasInscripciones()) {
            $this->dispatch('oops', message: 'No se puede eliminar el indicador porque tiene inscripciones asociadas.');
            return;
        }

        // Si no tiene inscripciones asociadas, proceder a eliminar
        $indicador->delete();
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.indicadores');
    }
}
