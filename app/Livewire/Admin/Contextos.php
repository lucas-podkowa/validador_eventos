<?php

namespace App\Livewire\Admin;

use App\Models\CategoriaEvento;
use App\Models\Contexto;
use App\Models\Firmante;
use Livewire\Component;
use Livewire\WithPagination;

class Contextos extends Component
{
    use WithPagination;

    // Modal crear/editar contexto
    public $open_modal = false;

    public $editando_id = null;

    public $categoria_id = null;

    public $nombre = '';

    public $denominacion = '';

    public $institucion = '';

    public $anio = '';

    public $fecha_inicio = '';

    public $fecha_fin = '';

    public $lugar = '';

    public $resolucion = '';

    public $activo = true;

    // Filtros
    public $search = '';

    public $searchCategoria = '';

    // Panel del contexto activo
    public $contexto_activo_id = null;

    public $contexto_activo_nombre = '';

    public $firmantes_asignados = [];

    public $nuevo_firmante_id = null;

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSearchCategoria(): void
    {
        $this->resetPage();
    }

    // ─── ABMC Contextos ─────────────────────────────────────────────

    public function abrirCrear(): void
    {
        $this->reset([
            'editando_id', 'categoria_id', 'nombre', 'denominacion', 'institucion',
            'anio', 'fecha_inicio', 'fecha_fin', 'lugar', 'resolucion',
        ]);
        $this->activo = true;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $contexto = Contexto::findOrFail($id);

        $this->editando_id = $contexto->contexto_id;
        $this->categoria_id = $contexto->categoria_id;
        $this->nombre = $contexto->nombre;
        $this->denominacion = $contexto->denominacion ?? '';
        $this->institucion = $contexto->institucion ?? '';
        $this->anio = $contexto->anio ?? '';
        $this->fecha_inicio = $contexto->fecha_inicio?->format('Y-m-d') ?? '';
        $this->fecha_fin = $contexto->fecha_fin?->format('Y-m-d') ?? '';
        $this->lugar = $contexto->lugar ?? '';
        $this->resolucion = $contexto->resolucion ?? '';
        $this->activo = (bool) $contexto->activo;

        $this->resetValidation();
        $this->open_modal = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'categoria_id' => 'required|exists:categoria_evento,categoria_id',
            'nombre' => 'required|string|max:150',
            'denominacion' => 'nullable|string|max:255',
            'institucion' => 'nullable|string|max:255',
            'anio' => 'nullable|integer|min:1900|max:2100',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'lugar' => 'nullable|string|max:150',
            'resolucion' => 'nullable|string|max:100',
        ]);

        $datos = [
            'categoria_id' => $this->categoria_id,
            'nombre' => $this->nombre,
            'denominacion' => $this->denominacion ?: null,
            'institucion' => $this->institucion ?: null,
            'anio' => $this->anio !== '' ? (int) $this->anio : null,
            'fecha_inicio' => $this->fecha_inicio ?: null,
            'fecha_fin' => $this->fecha_fin ?: null,
            'lugar' => $this->lugar ?: null,
            'resolucion' => $this->resolucion ?: null,
            'activo' => (bool) $this->activo,
        ];

        if ($this->editando_id) {
            Contexto::findOrFail($this->editando_id)->update($datos);
            $this->dispatch('alert', message: 'Contexto actualizado correctamente.');
        } else {
            Contexto::create($datos);
            $this->dispatch('alert', message: 'Contexto creado correctamente.');
        }

        $this->open_modal = false;
        $this->reset([
            'editando_id', 'categoria_id', 'nombre', 'denominacion', 'institucion',
            'anio', 'fecha_inicio', 'fecha_fin', 'lugar', 'resolucion',
        ]);
    }

    public function eliminar(int $id): void
    {
        $contexto = Contexto::withCount('eventos')->findOrFail($id);

        if ($contexto->eventos_count > 0) {
            $this->dispatch('oops', message: "No se puede eliminar: hay {$contexto->eventos_count} evento(s) asociados a este contexto.");

            return;
        }

        $contexto->delete();

        if ($this->contexto_activo_id === $id) {
            $this->cerrarContexto();
        }

        $this->dispatch('alert', message: 'Contexto eliminado.');
    }

    // ─── Panel del contexto activo ──────────────────────────────────

    public function abrirContexto(int $id): void
    {
        $contexto = Contexto::with('firmantes')->findOrFail($id);

        $this->contexto_activo_id = $contexto->contexto_id;
        $this->contexto_activo_nombre = $contexto->nombre;
        $this->firmantes_asignados = $contexto->firmantes
            ->map(fn ($f) => [
                'firmante_id' => $f->firmante_id,
                'orden' => (int) $f->pivot->orden,
                'mostrar_cargo' => (bool) $f->pivot->mostrar_cargo,
            ])
            ->values()
            ->all();
        $this->nuevo_firmante_id = null;
        $this->resetValidation();
    }

    public function cerrarContexto(): void
    {
        $this->reset(['contexto_activo_id', 'contexto_activo_nombre', 'firmantes_asignados', 'nuevo_firmante_id']);
    }

    public function agregarFirmante(): void
    {
        $this->validate([
            'nuevo_firmante_id' => 'required|exists:firmante,firmante_id',
        ], [
            'nuevo_firmante_id.required' => 'Seleccioná un firmante.',
        ]);

        $yaAsignado = collect($this->firmantes_asignados)->contains(
            fn ($f) => (int) $f['firmante_id'] === (int) $this->nuevo_firmante_id
        );

        if ($yaAsignado) {
            $this->dispatch('oops', message: 'Ese firmante ya está asignado a este contexto.');

            return;
        }

        $this->firmantes_asignados[] = [
            'firmante_id' => (int) $this->nuevo_firmante_id,
            'orden' => count($this->firmantes_asignados) + 1,
            'mostrar_cargo' => true,
        ];

        $this->nuevo_firmante_id = null;
        $this->persistirFirmantes();
    }

    public function quitarFirmante(int $index): void
    {
        if (! isset($this->firmantes_asignados[$index])) {
            return;
        }

        unset($this->firmantes_asignados[$index]);
        $this->firmantes_asignados = array_values($this->firmantes_asignados);

        foreach ($this->firmantes_asignados as $i => &$firmante) {
            $firmante['orden'] = $i + 1;
        }
        unset($firmante);

        $this->persistirFirmantes();
    }

    public function moverFirmante(int $index, int $direccion): void
    {
        $destino = $index + $direccion;

        if (! isset($this->firmantes_asignados[$index], $this->firmantes_asignados[$destino])) {
            return;
        }

        [$this->firmantes_asignados[$index], $this->firmantes_asignados[$destino]] =
            [$this->firmantes_asignados[$destino], $this->firmantes_asignados[$index]];

        foreach ($this->firmantes_asignados as $i => &$firmante) {
            $firmante['orden'] = $i + 1;
        }
        unset($firmante);

        $this->persistirFirmantes();
    }

    public function actualizarMostrarCargo(int $index): void
    {
        if (isset($this->firmantes_asignados[$index])) {
            $this->firmantes_asignados[$index]['mostrar_cargo'] = (bool) $this->firmantes_asignados[$index]['mostrar_cargo'];
            $this->persistirFirmantes();
        }
    }

    private function persistirFirmantes(): void
    {
        if (! $this->contexto_activo_id) {
            return;
        }

        $contexto = Contexto::find($this->contexto_activo_id);

        if (! $contexto) {
            return;
        }

        $sync = [];
        foreach ($this->firmantes_asignados as $i => $firmante) {
            $sync[(int) $firmante['firmante_id']] = [
                'orden' => $i + 1,
                'mostrar_cargo' => (bool) ($firmante['mostrar_cargo'] ?? true),
            ];
        }

        $contexto->firmantes()->sync($sync);
    }

    public function render()
    {
        $categorias = CategoriaEvento::orderBy('nombre')->get();

        $contextos = Contexto::with('categoria')
            ->withCount(['eventos', 'plantillas', 'firmantes'])
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->when($this->searchCategoria, fn ($q) => $q->where('categoria_id', $this->searchCategoria))
            ->orderBy('nombre')
            ->paginate(10);

        $firmantes = Firmante::where('activo', true)->orderBy('nombre')->get();

        return view('livewire.admin.contextos', compact('categorias', 'contextos', 'firmantes'));
    }
}
