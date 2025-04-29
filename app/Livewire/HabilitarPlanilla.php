<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Evento;
use App\Models\PlanillaInscripcion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HabilitarPlanilla extends Component
{
    use WithFileUploads;

    public $evento_id;
    public $evento;
    public $disposicion;
    public $header = null;
    public $footer = null;
    public $apertura;
    public $cierre;
    public $imagenesDisponibles = [];
    public $showHeaderModal = false;
    public $showFooterModal = false;
    public $nuevaImagen;
    public $tipoModalActivo;




    protected $rules = [
        'apertura' => 'required|date_format:Y-m-d H:i',
        'cierre' => 'required|date_format:Y-m-d H:i|after:apertura',
        'disposicion' => 'required|file|mimes:pdf|max:10240', // 10MB
    ];



    public function mount($evento_id = null)
    {
        $this->evento_id = $evento_id;
        $this->evento = Evento::findOrFail($evento_id);
    }

    public function updatedApertura($value)
    {
        $this->apertura = Carbon::parse($value)->format('Y-m-d H:i');
    }
    public function updatedCierre($value)
    {
        $this->cierre = Carbon::parse($value)->format('Y-m-d H:i');
    }


    public function redirectToEventos($tab)
    {
        return redirect()->route('eventos', ['tab' => $tab]);
    }


    public function abrirGaleria($tipo)
    {
        $this->tipoModalActivo = $tipo;

        $path = 'images/' . $tipo;
        $files = Storage::disk('public')->files($path);
        $this->imagenesDisponibles = collect($files)->filter(function ($file) {
            return collect(['.jpg', '.jpeg', '.png'])->contains(fn($ext) => str_ends_with($file, $ext));
        })->values()->all();


        if ($tipo === 'header') {
            $this->showHeaderModal = true;
        } elseif ($tipo === 'footer') {
            $this->showFooterModal = true;
        }
    }



    public function seleccionarImagen($path, $tipo)
    {
        if ($tipo === 'header') {
            $this->header = $path;
            $this->showHeaderModal = false;
        } elseif ($tipo === 'footer') {
            $this->footer = $path;
            $this->showFooterModal = false;
        }
    }



    public function guardarNuevaImagen()
    {
        $this->validate([
            'nuevaImagen' => 'required|image|max:10240',
        ]);

        $path = $this->nuevaImagen->store('images/' . $this->tipoModalActivo, 'public');

        // Refrescar galería
        $this->abrirGaleria($this->tipoModalActivo);

        // Limpiar input
        $this->nuevaImagen = null;
    }



    public function habilitar_planilla()
    {

        $this->validate();

        if ($this->header instanceof \Illuminate\Http\UploadedFile) {
            $this->header = $this->header->store('images/header', 'public');
        }
        if ($this->footer instanceof \Illuminate\Http\UploadedFile) {
            $this->footer = $this->footer->store('images/footer', 'public');
        }

        // Formatear fechas correctamente antes de la validación
        $apertura = Carbon::createFromFormat('Y-m-d H:i', $this->apertura);
        $cierre = Carbon::createFromFormat('Y-m-d H:i', $this->cierre);

        // Verificar que la fecha de apertura sea menor a la fecha de inicio del evento
        if ($apertura->gte(Carbon::parse($this->evento->fecha_inicio))) {
            $fechaInicioFormateada = Carbon::parse($this->evento->fecha_inicio)->format('d/m/Y H:i');
            $this->dispatch('oops', message: 'La fecha de apertura debe ser menor a la fecha de inicio del evento (' . $fechaInicioFormateada . ').');
            return;
        }

        DB::beginTransaction();
        try {
            // Validar y cargar las imágenes y el archivo de la disposición
            //$headerPath = $this->header ? $this->header->store('images', 'public') : null;
            //$footerPath = $this->footer ? $this->footer->store('images', 'public') : null;
            $headerPath = $this->header;
            $footerPath = $this->footer;
            $dispoPath = $this->disposicion ? $this->disposicion->store('disposiciones', 'private') : null;

            // Consulta si ya existe la planilla de inscripción para el evento
            $planilla = PlanillaInscripcion::where('evento_id', $this->evento->evento_id)->first();

            //Crea la planilla de inscripción para el evento
            if (!$planilla) {
                $planilla = PlanillaInscripcion::create([
                    'evento_id' => $this->evento->evento_id,
                    'apertura' => $apertura,
                    'cierre' => $cierre,
                    'header' => $headerPath,
                    'footer' => $footerPath,
                    'disposicion' => $dispoPath,
                ]);
            }

            // Actualizar el estado del evento a "en curso"
            Evento::where('evento_id', $this->evento->evento_id)->update(['estado' => 'En Curso']);

            DB::commit();
            $this->reset([
                'apertura',
                'cierre',
                'header',
                'footer',
                'disposicion',
                'evento',
            ]);
            $this->redirectToEventos('en_curso');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo habilitar la planilla de inscripción: ' . $e->getMessage());
            return;
        }
    }

    public function render()
    {
        return view('livewire.habilitar-planilla');
    }
}
