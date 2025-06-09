<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Evento;
use App\Models\PlanillaInscripcion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


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
    public $modo = 'crear';


    protected function rules()
    {
        $rules = [
            'apertura' => 'required|date_format:Y-m-d H:i|before:cierre',
            'cierre' => 'required|date_format:Y-m-d H:i|after:apertura',
        ];

        if ($this->modo === 'crear' || $this->disposicion instanceof \Illuminate\Http\UploadedFile) {
            $rules['disposicion'] = 'required|file|mimes:pdf|max:10240';
        }

        return $rules;
    }



    public function mount($evento_id = null)
    {
        $this->evento_id = $evento_id;
        $this->evento = Evento::findOrFail($evento_id);

        $planilla = PlanillaInscripcion::where('evento_id', $evento_id)->first();
        if ($planilla) {
            $this->modo = 'editar';
            $this->apertura = Carbon::parse($planilla->apertura)->format('Y-m-d H:i');
            $this->cierre = Carbon::parse($planilla->cierre)->format('Y-m-d H:i');
            $this->header = $planilla->header;
            $this->footer = $planilla->footer;
            $this->disposicion = $planilla->disposicion ?? null;
        }
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

    public function cerrarGaleria()
    {
        $this->showHeaderModal = false;
        $this->showFooterModal = false;
        $this->tipoModalActivo = null;
        $this->nuevaImagen = null;
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
        if (!$this->tipoModalActivo) {
            $this->addError('nuevaImagen', 'No se pudo determinar el tipo de imagen (header o footer).');
            return;
        }

        $this->validate([
            'nuevaImagen' => 'required|image|max:10240',
        ]);

        try {
            //$path = $this->nuevaImagen->store('images', 'public');
            $path = $this->nuevaImagen->store('images/' . $this->tipoModalActivo, 'public');
            $this->imagenesDisponibles[] = $path;
            // Refrescar galería
            $this->abrirGaleria($this->tipoModalActivo);
            $this->nuevaImagen = null;
            $this->dispatch('image-uploaded');
        } catch (\Exception $e) {
            $this->addError('nuevaImagen', 'Error al subir la imagen.');
        }
    }

    public function guardar_planilla()
    {
        $this->validate();

        if ($this->header instanceof \Illuminate\Http\UploadedFile) {
            $this->header = $this->header->store('images/header', 'public');
        }
        if ($this->footer instanceof \Illuminate\Http\UploadedFile) {
            $this->footer = $this->footer->store('images/footer', 'public');
        }

        // Verifica si la disposición es un archivo cargado o un archivo ya existente
        if ($this->disposicion instanceof \Illuminate\Http\UploadedFile) {
            $anio = Carbon::parse($this->evento->fecha_inicio)->year;
            $tipo = Str::slug($this->evento->tipoEvento->nombre);
            $nombreEvento = Str::slug($this->evento->nombre);
            $fechaEvento = Carbon::parse($this->evento->fecha_inicio)->format('d-m');
            $nombreArchivo = $nombreEvento . '_' . $fechaEvento . '.pdf';

            $ruta = "disposiciones/{$anio}/{$tipo}";
            $this->disposicion = $this->disposicion->storeAs($ruta, $nombreArchivo, 'private');
        } elseif (!$this->disposicion) {
            // Si no hay disposición cargada y no está definida, no guardamos nada
            $this->disposicion = null;
        }

        $apertura = Carbon::createFromFormat('Y-m-d H:i', $this->apertura);
        $cierre = Carbon::createFromFormat('Y-m-d H:i', $this->cierre);

        if ($apertura->gte(Carbon::parse($this->evento->fecha_inicio))) {
            $fechaInicioFormateada = Carbon::parse($this->evento->fecha_inicio)->format('d/m/Y H:i');
            $this->dispatch('oops', message: 'La fecha de apertura debe ser menor a la fecha de inicio del evento (' . $fechaInicioFormateada . ').');
            return;
        }

        DB::beginTransaction();
        try {

            PlanillaInscripcion::updateOrCreate(
                ['evento_id' => $this->evento->evento_id],
                [
                    'apertura' => $apertura,
                    'cierre' => $cierre,
                    'header' => $this->header,
                    'footer' => $this->footer,
                    'disposicion' => $this->disposicion,
                ]
            );

            Evento::where('evento_id', $this->evento->evento_id)->update(['estado' => 'En Curso']);
            DB::commit();

            $this->reset(['apertura', 'cierre', 'header', 'footer', 'disposicion', 'evento', 'modo']);
            $this->redirectToEventos('en_curso');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Error al guardar la planilla: ' . $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.habilitar-planilla');
    }
}
