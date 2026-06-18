<?php

namespace App\Livewire\Admin;

use App\Models\Destinatario;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Destinatarios extends Component
{
    use WithPagination;

    public $open_modal = false;

    public $editando_id = null;

    public $nombre = '';

    public bool $activo = true;

    public $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'nombre', 'activo']);
        $this->activo = true;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $destinatario = Destinatario::findOrFail($id);

        $this->editando_id = $destinatario->destinatario_id;
        $this->nombre = $destinatario->nombre;
        $this->activo = (bool) $destinatario->activo;

        $this->resetValidation();
        $this->open_modal = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                $this->editando_id
                    ? Rule::unique('destinatarios', 'nombre')->ignore($this->editando_id, 'destinatario_id')
                    : Rule::unique('destinatarios', 'nombre'),
            ],
            'activo' => 'boolean',
        ]);

        $datos = [
            'nombre' => mb_strtoupper(trim($this->nombre)),
            'activo' => $this->activo,
        ];

        if ($this->editando_id) {
            Destinatario::findOrFail($this->editando_id)->update($datos);
            $this->dispatch('alert', message: 'Destinatario actualizado correctamente.');
        } else {
            Destinatario::create($datos);
            $this->dispatch('alert', message: 'Destinatario creado correctamente.');
        }

        $this->open_modal = false;
        $this->reset(['editando_id', 'nombre', 'activo']);
    }

    public function eliminar(int $id): void
    {
        $destinatario = Destinatario::withCount('eventos')->findOrFail($id);

        if ($destinatario->eventos_count > 0) {
            $this->dispatch('oops', message: "No se puede eliminar: está asociado a {$destinatario->eventos_count} evento(s).");

            return;
        }

        $destinatario->delete();
        $this->dispatch('alert', message: 'Destinatario eliminado correctamente.');
    }

    public function toggleActivo(int $id): void
    {
        $destinatario = Destinatario::findOrFail($id);
        $destinatario->update(['activo' => ! $destinatario->activo]);
    }

    public function render()
    {
        $destinatarios = Destinatario::withCount('eventos')
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.admin.destinatarios', compact('destinatarios'));
    }
}
