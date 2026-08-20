<?php

namespace App\Livewire\Academica;

use App\Models\Carrera;
use App\Models\TituloIntermedio;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Plantillas extends Component
{
    use WithFileUploads;

    public $carrera_id = null;

    public $nueva_plantilla_titulo_id = null;

    public $nueva_plantilla_imagen = null;

    public function updatedCarreraId(): void
    {
        $this->reset(['nueva_plantilla_titulo_id', 'nueva_plantilla_imagen']);
        $this->resetValidation();
    }

    public function selectTituloParaPlantilla(int $tituloId): void
    {
        $this->nueva_plantilla_titulo_id = $tituloId;
        $this->nueva_plantilla_imagen = null;
        $this->resetValidation();
    }

    public function cancelarSeleccionPlantilla(): void
    {
        $this->reset(['nueva_plantilla_titulo_id', 'nueva_plantilla_imagen']);
        $this->resetValidation();
    }

    public function guardarPlantilla(): void
    {
        $this->validate([
            'nueva_plantilla_titulo_id' => 'required|exists:titulo_intermedio,id',
            'nueva_plantilla_imagen' => 'required|image|mimes:jpeg,png|max:30720',
        ]);

        $titulo = TituloIntermedio::findOrFail($this->nueva_plantilla_titulo_id);

        // Reemplazo: eliminar la plantilla anterior
        if ($titulo->imagen_plantilla_path) {
            Storage::disk('public')->delete($titulo->imagen_plantilla_path);
        }

        $path = $this->nueva_plantilla_imagen->store("plantillas/titulos/{$titulo->id}", 'public');
        $titulo->update(['imagen_plantilla_path' => $path]);

        $this->dispatch('alert', message: 'Plantilla guardada correctamente.');
        $this->reset(['nueva_plantilla_titulo_id', 'nueva_plantilla_imagen']);
    }

    public function render()
    {
        $carreras = Carrera::orderBy('nombre')->get();

        $titulos = $this->carrera_id
            ? TituloIntermedio::where('carrera_id', $this->carrera_id)->orderBy('nombre')->get()
            : collect();

        return view('livewire.academica.plantillas', compact('carreras', 'titulos'));
    }
}
