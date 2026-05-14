<?php

namespace App\Livewire;

use App\Models\Indicador;
use App\Models\TipoIndicador;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Indicadores extends Component
{
    public $tipoSeleccionadoId = null;
    public $showIndicadorModal = false;

    public $sortField = 'nombre';
    public $sortDirection = 'asc';

    public $modoTipo = 'idle';
    public $modoIndicador = 'idle';

    public $tipo_nombre = '';
    public $tipo_selector = 'Selección Única';
    public $editingTipoId = null;

    public $indicador_nombre = '';
    public $editingIndicadorId = null;

    public function mount()
    {
        $this->syncTipoSeleccionado();
    }

    protected function syncTipoSeleccionado(?int $preferredId = null): void
    {
        $currentId = $preferredId ?? $this->tipoSeleccionadoId;

        if ($currentId && TipoIndicador::whereKey($currentId)->exists()) {
            $this->tipoSeleccionadoId = $currentId;

            return;
        }

        $this->tipoSeleccionadoId = TipoIndicador::query()
            ->orderBy('nombre', 'asc')
            ->value('tipo_indicador_id');
    }

    public function sortBy($field)
    {
        if (!in_array($field, ['nombre', 'indicador_id'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function selectTipo($id)
    {
        $this->tipoSeleccionadoId = (int) $id;
        $this->cancelTipoForm();
        $this->cancelIndicadorForm();
    }

    #[Computed]
    public function tiposIndicadores()
    {
        return TipoIndicador::query()
            ->withCount('indicadores')
            ->orderBy('nombre', 'asc')
            ->get();
    }

    #[Computed]
    public function tipoActivo()
    {
        if (!$this->tipoSeleccionadoId) {
            return null;
        }

        return TipoIndicador::query()
            ->withCount('indicadores')
            ->find($this->tipoSeleccionadoId);
    }

    #[Computed]
    public function indicadores()
    {
        if (!$this->tipoSeleccionadoId) {
            return collect();
        }

        return Indicador::query()
            ->where('tipo_indicador_id', $this->tipoSeleccionadoId)
            ->orderBy($this->resolveSortField(), $this->sortDirection)
            ->get();
    }

    public function createTipo()
    {
        $this->resetValidation();
        $this->cancelIndicadorForm();

        $this->modoTipo = 'create';
        $this->editingTipoId = null;
        $this->tipo_nombre = '';
        $this->tipo_selector = 'Selección Única';
    }

    public function editTipo($id = null)
    {
        $tipoId = $id ? (int) $id : (int) $this->tipoSeleccionadoId;

        if (!$tipoId) {
            $this->dispatch('oops', message: 'Seleccione un tipo antes de editarlo.');

            return;
        }

        $tipo = TipoIndicador::findOrFail($tipoId);

        $this->resetValidation();
        $this->cancelIndicadorForm();

        $this->tipoSeleccionadoId = $tipo->tipo_indicador_id;
        $this->modoTipo = 'edit';
        $this->tipo_nombre = $tipo->nombre;
        $this->tipo_selector = $tipo->selector ?: 'Selección Única';
        $this->editingTipoId = $tipo->tipo_indicador_id;
    }

    public function saveTipo()
    {
        $this->tipo_nombre = trim((string) $this->tipo_nombre);

        $this->validate([
            'tipo_nombre' => 'required|string|max:255',
            'tipo_selector' => 'required|in:Selección Única,Selección Múltiple,Texto Libre',
        ], [], [
            'tipo_nombre' => 'nombre del tipo',
            'tipo_selector' => 'tipo de selección',
        ]);

        if ($this->modoTipo === 'create') {
            $tipo = TipoIndicador::create([
                'nombre' => $this->tipo_nombre,
                'selector' => $this->tipo_selector,
            ]);

            $this->syncTipoSeleccionado($tipo->tipo_indicador_id);
            $this->dispatch('alert', message: 'Tipo de indicador creado correctamente.');
        } else {
            $tipo = TipoIndicador::findOrFail($this->editingTipoId);
            $tipo->update([
                'nombre' => $this->tipo_nombre,
                'selector' => $this->tipo_selector,
            ]);

            $this->syncTipoSeleccionado($tipo->tipo_indicador_id);
            $this->dispatch('alert', message: 'Tipo de indicador actualizado correctamente.');
        }

        $this->cancelTipoForm();
    }

    #[On('deleteTipo')]
    public function deleteTipo($tipo_indicador_id)
    {
        $tipoIndicador = TipoIndicador::findOrFail($tipo_indicador_id);

        if ($tipoIndicador->indicadores()->exists()) {
            $this->dispatch('oops', message: 'No se puede eliminar el tipo de indicador porque tiene indicadores asociados.');

            return;
        }

        $nombreTipo = $tipoIndicador->nombre;
        $tipoIndicador->delete();

        $this->syncTipoSeleccionado();
        $this->cancelTipoForm();
        $this->cancelIndicadorForm();

        $this->dispatch('alert', message: 'Se eliminó el tipo de indicador ' . $nombreTipo . '.');
    }

    public function createIndicador()
    {
        if (!$this->tipoSeleccionadoId) {
            $this->dispatch('oops', message: 'Primero debe crear o seleccionar un tipo de indicador.');

            return;
        }

        $this->resetValidation();
        $this->cancelTipoForm();

        $this->modoIndicador = 'create';
        $this->editingIndicadorId = null;
        $this->indicador_nombre = '';
        $this->showIndicadorModal = true;
    }

    public function editIndicador($id)
    {
        $indicador = Indicador::findOrFail($id);

        $this->resetValidation();
        $this->cancelTipoForm();

        $this->tipoSeleccionadoId = $indicador->tipo_indicador_id;
        $this->modoIndicador = 'edit';
        $this->indicador_nombre = $indicador->nombre;
        $this->editingIndicadorId = $indicador->indicador_id;
        $this->showIndicadorModal = true;
    }

    public function saveIndicador()
    {
        if (!$this->tipoSeleccionadoId) {
            $this->dispatch('oops', message: 'Seleccione un tipo de indicador antes de guardar.');

            return;
        }

        $this->indicador_nombre = trim((string) $this->indicador_nombre);

        $this->validate([
            'indicador_nombre' => 'required|string|max:255',
        ], [], [
            'indicador_nombre' => 'nombre del indicador',
        ]);

        if ($this->modoIndicador === 'create') {
            Indicador::create([
                'nombre' => $this->indicador_nombre,
                'tipo_indicador_id' => $this->tipoSeleccionadoId,
            ]);

            $this->dispatch('alert', message: 'Indicador creado correctamente.');
        } else {
            $indicador = Indicador::findOrFail($this->editingIndicadorId);
            $indicador->update([
                'nombre' => $this->indicador_nombre,
                'tipo_indicador_id' => $this->tipoSeleccionadoId,
            ]);

            $this->dispatch('alert', message: 'Indicador actualizado correctamente.');
        }

        $this->cancelIndicadorForm();
    }

    #[On('deleteIndicador')]
    public function deleteIndicador($indicador_id)
    {
        $indicador = Indicador::findOrFail($indicador_id);

        if ($indicador->hasInscripciones()) {
            $this->dispatch('oops', message: 'No se puede eliminar el indicador porque tiene inscripciones asociadas.');

            return;
        }

        $nombreIndicador = $indicador->nombre;
        $indicador->delete();

        if ((int) $this->editingIndicadorId === (int) $indicador_id) {
            $this->cancelIndicadorForm();
        }

        $this->dispatch('alert', message: 'Se eliminó el indicador ' . $nombreIndicador . '.');
    }

    public function cancelTipoForm()
    {
        $this->resetValidation();

        $this->modoTipo = 'idle';
        $this->editingTipoId = null;
        $this->tipo_nombre = '';
        $this->tipo_selector = 'Selección Única';
    }

    public function cancelIndicadorForm()
    {
        $this->resetValidation();

        $this->showIndicadorModal = false;
        $this->modoIndicador = 'idle';
        $this->editingIndicadorId = null;
        $this->indicador_nombre = '';
    }

    public function selectorOptions(): array
    {
        return ['Selección Única', 'Selección Múltiple', 'Texto Libre'];
    }

    public function selectorBadgeClass(?string $selector): string
    {
        return match ($selector) {
            'Selección Múltiple' => 'bg-amber-100 text-amber-800 border border-amber-200',
            'Texto Libre' => 'bg-sky-100 text-sky-800 border border-sky-200',
            default => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        };
    }

    protected function resolveSortField(): string
    {
        return in_array($this->sortField, ['nombre', 'indicador_id'], true)
            ? $this->sortField
            : 'nombre';
    }

    public function render()
    {
        return view('livewire.indicadores', [
            'tiposIndicadores' => $this->tiposIndicadores,
            'tipoActivo' => $this->tipoActivo,
            'indicadores' => $this->indicadores,
        ]);
    }
}