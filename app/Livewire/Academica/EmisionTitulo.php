<?php

namespace App\Livewire\Academica;

use App\Models\Carrera;
use App\Models\CertificadoEmitido;
use App\Models\Participante;
use App\Models\TituloIntermedio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EmisionTitulo extends Component
{
    public $carrera_id = null;

    public $titulo_id = null;

    public $dni = '';

    public $nombre = '';

    public $apellido = '';

    public $mail = '';

    public $telefono = '';

    public $busqueda_realizada = false;

    public $participante_encontrado = false;

    public $certificado_emitido_id = null;

    protected $rules = [
        'carrera_id' => 'required|exists:carrera,id',
        'titulo_id' => 'required|exists:titulo_intermedio,id',
        'dni' => 'required|numeric|min:1000000|max:999999999',
        'nombre' => 'required|string|max:100',
        'apellido' => 'required|string|max:100',
        'mail' => 'required|email|max:100',
        'telefono' => 'required|string|max:20',
    ];

    public function updatedCarreraId(): void
    {
        $this->titulo_id = null;
        $this->reset(['certificado_emitido_id']);
    }

    public function updatedTituloId(): void
    {
        $this->reset(['certificado_emitido_id']);
    }

    public function buscarParticipante(): void
    {
        $this->reset(['certificado_emitido_id']);
        $this->busqueda_realizada = false;
        $this->participante_encontrado = false;

        if (empty($this->dni)) {
            $this->reset(['nombre', 'apellido', 'mail', 'telefono']);

            return;
        }

        $participante = Participante::where('dni', $this->dni)->first();

        $this->busqueda_realizada = true;

        if ($participante) {
            $this->participante_encontrado = true;
            $this->nombre = $participante->nombre;
            $this->apellido = $participante->apellido;
            $this->mail = $participante->mail ?? '';
            $this->telefono = $participante->telefono ?? '';
        } else {
            $this->participante_encontrado = false;
            $this->reset(['nombre', 'apellido', 'mail', 'telefono']);
        }
    }

    public function emitir(): void
    {
        $this->reset(['certificado_emitido_id']);
        $this->validate();

        $titulo = TituloIntermedio::with('carrera')->findOrFail($this->titulo_id);

        if ($titulo->carrera_id != $this->carrera_id) {
            $this->dispatch('oops', message: 'El título intermedio no pertenece a la carrera seleccionada.');

            return;
        }

        if (! $titulo->activo) {
            $this->dispatch('oops', message: 'El título intermedio no se encuentra activo.');

            return;
        }

        if (! $titulo->imagen_plantilla_path) {
            $this->dispatch('oops', message: 'Primero cargá una plantilla para este título intermedio desde el submenú "Plantillas".');

            return;
        }

        DB::beginTransaction();
        try {
            // Normalizar campos
            $this->nombre = ucfirst(mb_strtolower(trim($this->nombre)));
            $this->apellido = ucfirst(mb_strtolower(trim($this->apellido)));

            $participante = Participante::where('dni', (int) $this->dni)->first();

            if (! $participante) {
                $this->validate([
                    'mail' => ['required', 'email', 'max:100', Rule::unique('participante', 'mail')],
                ]);

                $participante = Participante::create([
                    'nombre' => $this->nombre,
                    'apellido' => $this->apellido,
                    'dni' => (int) $this->dni,
                    'mail' => $this->mail,
                    'telefono' => $this->telefono,
                ]);
            }

            // Anular certificados vigentes previos del mismo (participante, título)
            CertificadoEmitido::where('participante_id', $participante->participante_id)
                ->where('titulo_intermedio_id', $titulo->id)
                ->where('anulado', false)
                ->update(['anulado' => true]);

            $filename = $this->generarCertificado($participante, $titulo);

            $certificado = CertificadoEmitido::create([
                'participante_id' => $participante->participante_id,
                'titulo_intermedio_id' => $titulo->id,
                'certificado_path' => $filename,
                'anulado' => false,
                'emitido_por' => auth()->id(),
            ]);

            DB::commit();

            $this->certificado_emitido_id = $certificado->id;
            $this->dispatch('alert', message: 'Certificado emitido correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Error: '.$e->getMessage());
        }
    }

    private function generarCertificado(Participante $participante, TituloIntermedio $titulo): string
    {
        // Resolver ruta absoluta del fondo (soporta storage público y privado)
        $backgroundAbsPath = null;
        if ($titulo->imagen_plantilla_path) {
            if (Storage::disk('public')->exists($titulo->imagen_plantilla_path)) {
                $backgroundAbsPath = Storage::disk('public')->path($titulo->imagen_plantilla_path);
            } else {
                $backgroundAbsPath = Storage::path($titulo->imagen_plantilla_path);
            }
        }

        $pdf = Pdf::loadView('certificado_titulo', [
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'titulo' => $titulo->nombre,
            'carrera' => $titulo->carrera->nombre,
            'fecha' => now()->format('d/m/Y'),
            'background' => $backgroundAbsPath,
        ])->setPaper('a4', 'landscape');

        $year = now()->year;
        $folder = "certificados_titulos/{$year}/{$titulo->carrera->codigo}/{$titulo->id}";
        $filename = "{$folder}/{$participante->apellido}_{$participante->nombre}_{$participante->dni}.pdf";

        Storage::put($filename, $pdf->output());

        return $filename;
    }

    public function render()
    {
        $carreras = Carrera::where('activa', true)->orderBy('nombre')->get();

        $titulos = $this->carrera_id
            ? TituloIntermedio::where('carrera_id', $this->carrera_id)->where('activo', true)->orderBy('nombre')->get()
            : collect();

        return view('livewire.academica.emision-titulo', compact('carreras', 'titulos'));
    }
}
