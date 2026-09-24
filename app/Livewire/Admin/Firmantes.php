<?php

namespace App\Livewire\Admin;

use App\Models\Firmante;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Firmantes extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $open_modal = false;

    public $editando_id = null;

    public $nombre = '';

    public $cargo = '';

    public $activo = true;

    public $imagen = null;

    public $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'nombre', 'cargo', 'activo', 'imagen']);
        $this->activo = true;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $firmante = Firmante::findOrFail($id);
        $this->editando_id = $firmante->firmante_id;
        $this->nombre = $firmante->nombre;
        $this->cargo = $firmante->cargo ?? '';
        $this->activo = (bool) $firmante->activo;
        $this->imagen = null;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre' => 'required|string|max:100',
            'cargo' => 'nullable|string|max:150',
            'imagen' => 'nullable|image|mimes:png,jpeg|max:10240',
        ]);

        $datos = [
            'nombre' => $this->nombre,
            'cargo' => $this->cargo ?: null,
            'activo' => (bool) $this->activo,
        ];

        if ($this->editando_id) {
            $firmante = Firmante::findOrFail($this->editando_id);
            $firmante->update($datos);
        } else {
            $firmante = Firmante::create($datos);
        }

        if ($this->imagen) {
            $this->reemplazarImagen($firmante);
        }

        $this->dispatch('alert', message: $this->editando_id
            ? 'Firmante actualizado correctamente.'
            : 'Firmante creado correctamente.');

        $this->open_modal = false;
        $this->reset(['editando_id', 'nombre', 'cargo', 'activo', 'imagen']);
    }

    public function eliminar(int $id): void
    {
        $firmante = Firmante::findOrFail($id);

        if ($firmante->imagen_firma_path) {
            Storage::disk('private')->delete($firmante->imagen_firma_path);
        }

        $firmante->delete();

        $this->dispatch('alert', message: 'Firmante eliminado.');
    }

    private function reemplazarImagen(Firmante $firmante): void
    {
        if ($firmante->imagen_firma_path) {
            Storage::disk('private')->delete($firmante->imagen_firma_path);
        }

        $path = $this->imagen->store("firmas/{$firmante->firmante_id}", 'private');

        $firmante->update([
            'imagen_firma_path' => $path,
            'imagen_mime' => $this->imagen->getMimeType(),
        ]);
    }

    public function render()
    {
        $firmantes = Firmante::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nombre', 'like', "%{$this->search}%")
                        ->orWhere('cargo', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.admin.firmantes', compact('firmantes'));
    }
}
