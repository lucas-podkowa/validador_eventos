<?php

namespace App\Livewire\Admin;

use App\Models\CategoriaEvento;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Categorias extends Component
{
    use WithPagination;

    // Modal crear/editar categoría
    public $open_modal = false;

    public $editando_id = null;

    public $nombre = '';

    public $descripcion = '';

    public $disponible_para_eventos = true;

    public $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ─── ABMC Categorías ────────────────────────────────────────────

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'nombre', 'descripcion']);
        $this->disponible_para_eventos = true;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $categoria = CategoriaEvento::findOrFail($id);
        $this->editando_id = $categoria->categoria_id;
        $this->nombre = $categoria->nombre;
        $this->descripcion = $categoria->descripcion ?? '';
        $this->disponible_para_eventos = (bool) $categoria->disponible_para_eventos;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                $this->editando_id
                    ? Rule::unique('categoria_evento', 'nombre')->ignore($this->editando_id, 'categoria_id')
                    : Rule::unique('categoria_evento', 'nombre'),
            ],
            'descripcion' => 'nullable|string|max:255',
        ]);

        $datos = ['nombre' => $this->nombre, 'descripcion' => $this->descripcion ?: null, 'disponible_para_eventos' => (bool) $this->disponible_para_eventos];

        if ($this->editando_id) {
            CategoriaEvento::findOrFail($this->editando_id)->update($datos);
            $this->dispatch('alert', message: 'Categoría actualizada correctamente.');
        } else {
            CategoriaEvento::create($datos);
            $this->dispatch('alert', message: 'Categoría creada correctamente.');
        }

        $this->open_modal = false;
        $this->reset(['editando_id', 'nombre', 'descripcion']);
    }

    public function eliminar(int $id): void
    {
        $categoria = CategoriaEvento::withCount(['eventos', 'contextos'])->findOrFail($id);

        if ($categoria->eventos_count > 0) {
            $this->dispatch('oops', message: "No se puede eliminar: existen {$categoria->eventos_count} evento(s) en esta categoría.");

            return;
        }

        if ($categoria->contextos_count > 0) {
            $this->dispatch('oops', message: "No se puede eliminar: existen {$categoria->contextos_count} contexto(s) en esta categoría.");

            return;
        }

        $categoria->delete();

        $this->dispatch('alert', message: 'Categoría eliminada.');
    }

    public function render()
    {
        $categorias = CategoriaEvento::withCount(['eventos', 'contextos'])
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.admin.categorias', compact('categorias'));
    }
}
