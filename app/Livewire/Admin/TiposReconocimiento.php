<?php

namespace App\Livewire\Admin;

use App\Models\TipoReconocimiento;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TiposReconocimiento extends Component
{
    use WithPagination;

    public $open_modal = false;

    public $editando_id = null;

    public $nombre = '';

    public $slug = '';

    public $alcance_sugerido = '';

    public $orden = 0;

    public $activo = true;

    public $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'nombre', 'slug', 'alcance_sugerido', 'orden']);
        $this->activo = true;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $tipo = TipoReconocimiento::findOrFail($id);

        $this->editando_id = $tipo->tipo_reconocimiento_id;
        $this->nombre = $tipo->nombre;
        $this->slug = $tipo->slug;
        $this->alcance_sugerido = $tipo->alcance_sugerido ?? '';
        $this->orden = $tipo->orden;
        $this->activo = (bool) $tipo->activo;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre' => 'required|string|max:120',
            'slug' => ['required', 'string', 'max:120', Rule::unique('tipo_reconocimiento', 'slug')->ignore($this->editando_id, 'tipo_reconocimiento_id')],
            'alcance_sugerido' => ['nullable', Rule::in(TipoReconocimiento::ALCANCES)],
            'orden' => 'integer|min:0|max:9999',
        ]);

        $datos = [
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'alcance_sugerido' => $this->alcance_sugerido ?: null,
            'orden' => (int) $this->orden,
            'activo' => (bool) $this->activo,
        ];

        if ($this->editando_id) {
            TipoReconocimiento::findOrFail($this->editando_id)->update($datos);
        } else {
            TipoReconocimiento::create($datos);
        }

        $this->open_modal = false;
        $this->dispatch('alert', message: 'Tipo de reconocimiento guardado.');
    }

    public function eliminar(int $id): void
    {
        $tipo = TipoReconocimiento::withCount(['emisiones'])->findOrFail($id);

        if ($tipo->es_sistema) {
            $this->dispatch('oops', message: 'Los tipos de sistema no se pueden eliminar.');

            return;
        }

        if ($tipo->emisiones_count > 0) {
            $this->dispatch('oops', message: 'No se puede eliminar: hay emisiones asociadas. Desactivalo en su lugar.');

            return;
        }

        $tipo->delete();
        $this->dispatch('alert', message: 'Tipo de reconocimiento eliminado.');
    }

    public function render()
    {
        $tipos = TipoReconocimiento::query()
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%")->orWhere('slug', 'like', "%{$this->search}%"))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->paginate(12);

        return view('livewire.admin.tipos-reconocimiento', compact('tipos'));
    }
}
