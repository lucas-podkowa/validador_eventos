<?php

namespace App\Livewire\Academica;

use App\Models\Carrera;
use App\Models\TituloIntermedio;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TitulosIntermedios extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $open_modal = false;

    public $editando_id = null;

    public $carrera_id = null;

    public $nombre = '';

    public $activo = true;

    public $plantilla_imagen = null;

    public $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'carrera_id', 'nombre', 'plantilla_imagen']);
        $this->activo = true;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $titulo = TituloIntermedio::findOrFail($id);
        $this->editando_id = $titulo->id;
        $this->carrera_id = $titulo->carrera_id;
        $this->nombre = $titulo->nombre;
        $this->activo = (bool) $titulo->activo;
        $this->plantilla_imagen = null;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function guardar(): void
    {
        $ruleNombre = Rule::unique('titulo_intermedio', 'nombre')
            ->where(fn ($q) => $q->where('carrera_id', $this->carrera_id));
        if ($this->editando_id) {
            $ruleNombre->ignore($this->editando_id);
        }

        $this->validate([
            'carrera_id' => 'required|exists:carrera,id',
            'nombre' => ['required', 'string', 'max:120', $ruleNombre],
            'activo' => 'boolean',
            'plantilla_imagen' => 'nullable|image|mimes:jpeg,png|max:30720',
        ]);

        $datos = [
            'carrera_id' => $this->carrera_id,
            'nombre' => $this->nombre,
            'activo' => (bool) $this->activo,
        ];

        if ($this->editando_id && $this->plantilla_imagen) {
            $tituloActual = TituloIntermedio::find($this->editando_id);
            if ($tituloActual && $tituloActual->imagen_plantilla_path) {
                Storage::disk('public')->delete($tituloActual->imagen_plantilla_path);
            }
        }

        if ($this->plantilla_imagen) {
            $datos['imagen_plantilla_path'] = $this->plantilla_imagen->store('plantillas/titulos/tmp', 'public');
        }

        if ($this->editando_id) {
            TituloIntermedio::findOrFail($this->editando_id)->update($datos);
            $this->dispatch('alert', message: 'Título intermedio actualizado correctamente.');
        } else {
            $titulo = TituloIntermedio::create($datos);

            // Reubicar la plantilla recién subida a la carpeta definitiva del título
            if (isset($datos['imagen_plantilla_path'])) {
                $origen = $datos['imagen_plantilla_path'];
                $destino = "plantillas/titulos/{$titulo->id}/".basename($origen);
                Storage::disk('public')->move($origen, $destino);
                $titulo->update(['imagen_plantilla_path' => $destino]);
            }

            $this->dispatch('alert', message: 'Título intermedio creado correctamente.');
        }

        $this->open_modal = false;
        $this->reset(['editando_id', 'carrera_id', 'nombre', 'activo', 'plantilla_imagen']);
    }

    public function eliminar(int $id): void
    {
        $titulo = TituloIntermedio::withCount('certificadosEmitidos')->findOrFail($id);

        if ($titulo->certificadosEmitidos_count > 0) {
            $this->dispatch('oops', message: "No se puede eliminar: existen {$titulo->certificadosEmitidos_count} certificado(s) emitido(s) para este título.");

            return;
        }

        if ($titulo->imagen_plantilla_path) {
            Storage::disk('public')->delete($titulo->imagen_plantilla_path);
        }

        $titulo->delete();
        $this->dispatch('alert', message: 'Título intermedio eliminado.');
    }

    public function render()
    {
        $carreras = Carrera::orderBy('nombre')->get();

        $titulos = TituloIntermedio::with('carrera')
            ->when($this->search, function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                    ->orWhereHas('carrera', fn ($c) => $c->where('nombre', 'like', "%{$this->search}%"));
            })
            ->orderBy('carrera_id')
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.academica.titulos-intermedios', compact('carreras', 'titulos'));
    }
}
