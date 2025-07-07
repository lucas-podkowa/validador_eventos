<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\Participante;
use App\Models\EventoParticipante;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;


class EmisorCertificados extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $modal_abierto = false;
    public $evento_id;
    public $nombre, $apellido, $dni, $telefono, $mail;
    public ?array $participanteExistente = null;
    public $background_image;

    public $eventoParticipantes = [];
    public $eventos = [];

    protected $rules = [
        'evento_id' => 'required|exists:evento,evento_id',
        'nombre' => 'required|string|max:100',
        'apellido' => 'required|string|max:100',
        'dni' => 'required|string|max:15',
        'telefono' => 'required|string|min:6|max:15',
        'mail' => 'required|email|max:100',
        'background_image' => 'required|image|mimes:jpeg,png|max:2048',
    ];

    public function mount()
    {

        $this->eventos = Evento::where('estado', 'Finalizado')->get();
        $this->eventoParticipantes = EventoParticipante::with(['participante', 'evento'])
            ->where('emision_directa', true)
            ->whereHas('evento', function ($query) {
                $query->where('estado', 'Finalizado');
            })
            ->get();
    }

    public function abrirModal()
    {
        $this->reset(['evento_id', 'nombre', 'apellido', 'dni', 'telefono', 'mail', 'participanteExistente']);
        $this->modal_abierto = true;
    }

    public function buscarParticipante()
    {
        if ($this->dni) {
            $this->participanteExistente = Participante::where('dni', $this->dni)->first()?->toArray();

            if ($this->participanteExistente) {
                $this->nombre = $this->participanteExistente['nombre'];
                $this->apellido = $this->participanteExistente['apellido'];
                $this->telefono = $this->participanteExistente['telefono'];
                $this->mail = $this->participanteExistente['mail'];
            } else {
                $this->reset('nombre', 'apellido', 'telefono', 'mail');
            }
        }
    }

    public function guardar()
    {
        $this->validate();
        $backgroundPath = $this->background_image ? $this->background_image->store('images', 'public') : null;


        DB::beginTransaction();
        try {
            // Normalizar campos
            $this->nombre = ucfirst(mb_strtolower(trim($this->nombre)));
            $this->apellido = ucfirst(mb_strtolower(trim($this->apellido)));

            $participante = Participante::where('dni', $this->dni)->first();

            if (!$participante) {
                $participante = Participante::create([
                    'nombre' => $this->nombre,
                    'apellido' => $this->apellido,
                    'dni' => $this->dni,
                    'telefono' => $this->telefono,
                    'mail' => $this->mail,
                ]);
            }

            // Evitar duplicados
            $yaExiste = EventoParticipante::where('evento_id', $this->evento_id)
                ->where('participante_id', $participante->participante_id)
                ->exists();

            if ($yaExiste) {
                DB::rollBack();
                $this->dispatch('oops', message: 'Este participante ya está registrado en el evento.');
                return;
            }

            // Generar URL y QR
            $url = route('validar.participante', [
                'evento_id' => $this->evento_id,
                'participante_id' => $participante->participante_id
            ]);

            $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
            $writer = new Writer($renderer);
            $qrcode = $writer->writeString($url);

            EventoParticipante::create([
                'evento_id' => $this->evento_id,
                'participante_id' => $participante->participante_id,
                'url' => $url,
                'qrcode' => $qrcode,
                'emision_directa' => true,
            ]);

            // Generar certificado
            $evento = Evento::with('tipoEvento')->find($this->evento_id);
            $this->generarCertificadoIndividual($participante, $evento, $backgroundPath);

            DB::commit();
            $this->dispatch('alert', message: 'Participante registrado correctamente.');
            $this->modal_abierto = false;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Error: ' . $e->getMessage());
        }
    }

    private function generarCertificadoIndividual(Participante $participante, Evento $evento, ?string $backgroundPath)
    {
        $year = now()->year;
        $tipoEvento = $evento->tipoEvento->nombre;
        $nombreEvento = $evento->nombre;

        $pivot = $evento->participantes()
            ->where('evento_participantes.participante_id', $participante->participante_id)
            ->first()
            ?->pivot;


        if (!$pivot) {
            throw new \Exception("No se encontró el vínculo entre evento y participante.");
        }

        $pdf = Pdf::loadView('certificado', [
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'qr' => 'data:image/svg+xml;base64,' . base64_encode($pivot->qrcode),
            'background' => $backgroundPath
        ])->setPaper('a4', 'landscape');

        $folderPath = "certificados/{$year}/{$tipoEvento}/{$nombreEvento}";
        $filename = "{$folderPath}/{$participante->apellido}_{$participante->nombre} ({$participante->dni}).pdf";

        Storage::put($filename, $pdf->output());
        EventoParticipante::where('evento_id', $evento->evento_id)
            ->where('participante_id', $participante->participante_id)
            ->update(['certificado_path' => $filename]);

        // (opcionalmente también podés guardar esa ruta en el modelo Evento si querés mantenerlo como está)


        $evento->update(['certificado_path' => $folderPath]);
    }

    // private function generarCertificadoIndividual(Participante $participante, Evento $evento, string $backgroundPath)
    // {
    //     $year = now()->year;
    //     $tipoEvento = $evento->tipoEvento->nombre;
    //     $nombreEvento = $evento->nombre;

    //     $pdf = Pdf::loadView('certificado', [
    //         'nombre' => $participante->nombre,
    //         'apellido' => $participante->apellido,
    //         'dni' => $participante->dni,
    //         'qr' => 'data:image/svg+xml;base64,' . base64_encode(
    //             $evento->participantes()->where('participante_id', $participante->participante_id)->first()->pivot->qrcode
    //         ),
    //         'background' => $backgroundPath
    //     ])->setPaper('a4', 'landscape');

    //     $folderPath = "certificados/{$year}/{$tipoEvento}/{$nombreEvento}";
    //     $filename = "{$folderPath}/{$participante->apellido}_{$participante->nombre} ({$participante->dni}).pdf";

    //     Storage::put($filename, $pdf->output());

    //     $evento->update(['certificado_path' => $folderPath]);
    // }


    public function render()
    {
        return view('livewire.emisor-certificados');
    }
}
