<?php

namespace App\Livewire\Admin;

use App\Models\CategoriaEvento;
use App\Models\PlantillaCertificado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Categorias extends Component
{
    use WithFileUploads;
    use WithPagination;

    // Modal principal (crear/editar categoría)
    public $open_modal = false;

    public $editando_id = null;

    public $nombre = '';

    public $descripcion = '';

    // Panel de gestión de plantillas
    public $categoria_activa_id = null;

    public $categoria_activa_nombre = '';

    public $plantillas_de_categoria = [];

    // Formulario de nueva plantilla
    public $nueva_plantilla_nombre = '';

    public $nueva_plantilla_imagen = null;

    public $nueva_plantilla_tipo = 'asistencia';

    public $nueva_plantilla_por_defecto = false;

    // Edición de plantilla existente
    public $open_modal_plantilla_edit = false;

    public $editando_plantilla_id = null;

    public $editando_plantilla_nombre = '';

    public $editando_plantilla_tipo = 'asistencia';

    public $editando_plantilla_por_defecto = false;

    public $editando_plantilla_imagen = null; // optional replacement image

    public $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ─── ABMC Categorías ────────────────────────────────────────────

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'nombre', 'descripcion']);
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $categoria = CategoriaEvento::findOrFail($id);
        $this->editando_id = $categoria->categoria_id;
        $this->nombre = $categoria->nombre;
        $this->descripcion = $categoria->descripcion ?? '';
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

        $datos = ['nombre' => $this->nombre, 'descripcion' => $this->descripcion ?: null];

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
        $categoria = CategoriaEvento::withCount('eventos')->findOrFail($id);

        if ($categoria->eventos_count > 0) {
            $this->dispatch('oops', message: "No se puede eliminar: existen {$categoria->eventos_count} evento(s) en esta categoría.");

            return;
        }

        // Eliminar archivos físicos de todas las plantillas
        foreach ($categoria->plantillas as $plantilla) {
            Storage::disk('public')->delete($plantilla->imagen_path);
        }

        $categoria->delete(); // las plantillas se borran en cascada (FK)

        if ($this->categoria_activa_id === $id) {
            $this->cerrarPlantillas();
        }

        $this->dispatch('alert', message: 'Categoría eliminada.');
    }

    // ─── Gestión de Plantillas ──────────────────────────────────────

    public function abrirPlantillas(int $id): void
    {
        $categoria = CategoriaEvento::with('plantillas')->findOrFail($id);
        $this->categoria_activa_id = $categoria->categoria_id;
        $this->categoria_activa_nombre = $categoria->nombre;
        $this->plantillas_de_categoria = $categoria->plantillas->toArray();
        $this->reset(['nueva_plantilla_nombre', 'nueva_plantilla_imagen', 'nueva_plantilla_tipo', 'nueva_plantilla_por_defecto']);
        $this->resetValidation();
    }

    public function cerrarPlantillas(): void
    {
        $this->reset(['categoria_activa_id', 'categoria_activa_nombre', 'plantillas_de_categoria', 'nueva_plantilla_nombre', 'nueva_plantilla_imagen']);
    }

    public function agregarPlantilla(): void
    {
        $this->validate([
            'nueva_plantilla_nombre' => 'required|string|max:100',
            'nueva_plantilla_imagen' => 'required|image|mimes:jpeg,png|max:30720',
            'nueva_plantilla_tipo' => ['required', Rule::in(PlantillaCertificado::TIPOS)],
        ]);

        $path = $this->nueva_plantilla_imagen->store(
            "plantillas/{$this->categoria_activa_id}",
            'public'
        );

        // Si esta nueva plantilla se marca como por defecto, desmarcamos las otras del mismo tipo y categoría
        if ($this->nueva_plantilla_por_defecto) {
            PlantillaCertificado::where('categoria_id', $this->categoria_activa_id)
                ->where('tipo', $this->nueva_plantilla_tipo)
                ->update(['por_defecto' => false]);
        }

        PlantillaCertificado::create([
            'categoria_id' => $this->categoria_activa_id,
            'nombre' => $this->nueva_plantilla_nombre,
            'imagen_path' => $path,
            'tipo' => $this->nueva_plantilla_tipo,
            'por_defecto' => (bool) $this->nueva_plantilla_por_defecto,
        ]);

        $this->reset(['nueva_plantilla_nombre', 'nueva_plantilla_imagen', 'nueva_plantilla_tipo', 'nueva_plantilla_por_defecto']);
        $this->dispatch('alert', message: 'Plantilla agregada correctamente.');

        // Refrescar lista de plantillas
        $this->abrirPlantillas($this->categoria_activa_id);
    }

    /**
     * Abrir modal de edición para una plantilla existente
     */
    public function abrirEditarPlantilla(int $id): void
    {
        $plantilla = PlantillaCertificado::findOrFail($id);
        $this->editando_plantilla_id = $plantilla->plantilla_id;
        $this->editando_plantilla_nombre = $plantilla->nombre;
        $this->editando_plantilla_tipo = $plantilla->tipo;
        $this->editando_plantilla_por_defecto = (bool) $plantilla->por_defecto;
        $this->editando_plantilla_imagen = null;
        $this->resetValidation();
        $this->open_modal_plantilla_edit = true;
    }

    public function guardarPlantillaEditada(): void
    {
        $this->validate([
            'editando_plantilla_nombre' => 'required|string|max:100',
            'editando_plantilla_tipo' => ['required', Rule::in(PlantillaCertificado::TIPOS)],
            'editando_plantilla_imagen' => 'nullable|image|mimes:jpeg,png|max:30720',
        ]);

        $plantilla = PlantillaCertificado::findOrFail($this->editando_plantilla_id);

        // Si marcó como por_defecto, desmarcar las otras del mismo tipo y categoría
        if ($this->editando_plantilla_por_defecto) {
            PlantillaCertificado::where('categoria_id', $plantilla->categoria_id)
                ->where('tipo', $this->editando_plantilla_tipo)
                ->update(['por_defecto' => false]);
        }

        // Reemplazo de imagen si se subió una nueva
        if ($this->editando_plantilla_imagen) {
            // borrar la anterior
            Storage::disk('public')->delete($plantilla->imagen_path);
            $path = $this->editando_plantilla_imagen->store("plantillas/{$plantilla->categoria_id}", 'public');
            $plantilla->imagen_path = $path;
        }

        $plantilla->nombre = $this->editando_plantilla_nombre;
        $plantilla->tipo = $this->editando_plantilla_tipo;
        $plantilla->por_defecto = (bool) $this->editando_plantilla_por_defecto;
        $plantilla->save();

        $this->dispatch('alert', message: 'Plantilla actualizada correctamente.');

        $this->open_modal_plantilla_edit = false;
        $this->abrirPlantillas($this->categoria_activa_id);
    }

    public function eliminarPlantilla(int $id): void
    {
        $plantilla = PlantillaCertificado::findOrFail($id);
        $categoriaId = $plantilla->categoria_id;
        $tipo = $plantilla->tipo;
        $wasDefault = (bool) $plantilla->por_defecto;

        Storage::disk('public')->delete($plantilla->imagen_path);
        $plantilla->delete();

        // Si era la plantilla por defecto para ese tipo, intentar marcar otra como por_defecto
        if ($wasDefault) {
            $otra = PlantillaCertificado::where('categoria_id', $categoriaId)
                ->where('tipo', $tipo)
                ->orderBy('plantilla_id')
                ->first();
            if ($otra) {
                $otra->update(['por_defecto' => true]);
            }
        }

        $this->dispatch('alert', message: 'Plantilla eliminada.');
        $this->abrirPlantillas($this->categoria_activa_id);
    }

    public function render()
    {
        $categorias = CategoriaEvento::withCount(['eventos', 'plantillas'])
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.admin.categorias', compact('categorias'));
    }
}
