<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Indicador;
use App\Models\TipoIndicador;

class Indicadores extends Component
{
    public $tipo_indicadores;
    public $indicadores;

    public $showTipoModal = false;
    public $showIndicadorModal = false;

    public $isCreatingTipo = false;
    public $isCreatingIndicador = false;



    // Formulario para TipoIndicador
    public $tipo_nombre, $editingTipoId = null;

    // Formulario para Indicador
    public $indicador_nombre, $tipo_indicador_id, $editingIndicadorId = null;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->tipo_indicadores = TipoIndicador::all();
        $this->indicadores = Indicador::with('tipoIndicador')->get();
    }



    // TIPO INDICADOR
    //----------------------------------------------------
    public function createTipo()
    {
        $this->reset(['tipo_nombre', 'editingTipoId']);
        $this->isCreatingTipo = true;
        $this->showTipoModal = true;
    }

    public function editTipo($id)
    {
        $tipo = TipoIndicador::findOrFail($id);
        $this->tipo_nombre = $tipo->nombre;
        $this->editingTipoId = $tipo->tipo_indicador_id;
        $this->showTipoModal = true;
    }

    public function saveTipo()
    {
        $this->validate(['tipo_nombre' => 'required|string|max:255']);

        if ($this->isCreatingTipo) {
            TipoIndicador::create(['nombre' => $this->tipo_nombre]);
        } else {
            $tipo = TipoIndicador::findOrFail($this->editingTipoId);
            $tipo->update(['nombre' => $this->tipo_nombre]);
        }

        $this->showTipoModal = false;
        $this->isCreatingTipo = false;
        $this->loadData(); // si tenés un método para recargar
    }


    public function deleteTipo($id)
    {
        TipoIndicador::destroy($id);
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


    public function deleteIndicador($id)
    {
        Indicador::destroy($id);
        $this->loadData();
    }



    public function render()
    {
        return view('livewire.indicadores');
    }
}
