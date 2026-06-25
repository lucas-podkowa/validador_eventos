<?php

namespace App\Livewire\Admin;

use App\Models\TipoEvento;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TiposEvento extends Component
{
    use WithPagination;

    public $open_modal = false;

    public $editando_id = null;

    public $nombre = '';

    public $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'nombre']);
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $tipo = TipoEvento::findOrFail($id);
        $this->editando_id = $tipo->tipo_evento_id;
        $this->nombre = $tipo->nombre;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                $this->editando_id
                    ? Rule::unique('tipo_evento', 'nombre')->ignore($this->editando_id, 'tipo_evento_id')
                    : Rule::unique('tipo_evento', 'nombre'),
            ],
        ]);

        if ($this->editando_id) {
            TipoEvento::findOrFail($this->editando_id)->update(['nombre' => $this->nombre]);
            $this->dispatch('alert', message: 'Tipo de evento actualizado correctamente.');
        } else {
            TipoEvento::create(['nombre' => $this->nombre]);
            $this->dispatch('alert', message: 'Tipo de evento creado correctamente.');
        }

        $this->open_modal = false;
        $this->reset(['editando_id', 'nombre']);
    }

    public function eliminar(int $id): void
    {
        $tipo = TipoEvento::withCount('eventos')->findOrFail($id);

        if ($tipo->eventos_count > 0) {
            $this->dispatch('oops', message: "No se puede eliminar: existen {$tipo->eventos_count} evento(s) asociados a este tipo.");

            return;
        }

        $tipo->delete();
        $this->dispatch('alert', message: 'Tipo de evento eliminado.');
    }

    public function render()
    {
        $tipos = TipoEvento::withCount('eventos')
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.admin.tipos-evento', compact('tipos'));
    }
}
