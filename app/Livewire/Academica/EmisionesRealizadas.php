<?php

namespace App\Livewire\Academica;

use App\Models\Carrera;
use App\Models\CertificadoEmitido;
use App\Models\TituloIntermedio;
use Livewire\Component;
use Livewire\WithPagination;

class EmisionesRealizadas extends Component
{
    use WithPagination;

    public $carrera_id = null;

    public $titulo_id = null;

    public $buscar_nombre = '';

    public $buscar_dni = '';

    public $fecha_desde = '';

    public $fecha_hasta = '';

    public $solo_vigentes = false;

    public function updatedCarreraId(): void
    {
        $this->titulo_id = null;
        $this->resetPage();
    }

    public function updatedBuscarNombre(): void
    {
        $this->resetPage();
    }

    public function updatedBuscarDni(): void
    {
        $this->resetPage();
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatedSoloVigentes(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $carreras = Carrera::orderBy('nombre')->get();

        $titulos = $this->carrera_id
            ? TituloIntermedio::where('carrera_id', $this->carrera_id)->orderBy('nombre')->get()
            : collect();

        $emisiones = CertificadoEmitido::with(['participante', 'tituloIntermedio.carrera'])
            ->when($this->carrera_id, fn ($q) => $q->whereHas('tituloIntermedio', fn ($t) => $t->where('carrera_id', $this->carrera_id)))
            ->when($this->titulo_id, fn ($q) => $q->where('titulo_intermedio_id', $this->titulo_id))
            ->when($this->buscar_nombre, function ($q) {
                $q->whereHas('participante', function ($p) {
                    $p->where('nombre', 'like', "%{$this->buscar_nombre}%")
                        ->orWhere('apellido', 'like', "%{$this->buscar_nombre}%");
                });
            })
            ->when($this->buscar_dni, fn ($q) => $q->whereHas('participante', fn ($p) => $p->where('dni', $this->buscar_dni)))
            ->when($this->fecha_desde, fn ($q) => $q->whereDate('created_at', '>=', $this->fecha_desde))
            ->when($this->fecha_hasta, fn ($q) => $q->whereDate('created_at', '<=', $this->fecha_hasta))
            ->when($this->solo_vigentes, fn ($q) => $q->where('anulado', false))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.academica.emisiones-realizadas', compact('carreras', 'titulos', 'emisiones'));
    }
}
