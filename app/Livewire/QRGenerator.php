<?php

namespace App\Livewire;

use Livewire\Component;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;
use App\Models\Evento;
use App\Models\Participante;


class QRGenerator extends Component
{

    public $evento_id;
    public $participante_id;
    public $qrCode;


    public function mount($evento_id, $participante_id)
    {
        $this->evento_id = $evento_id;
        $this->participante_id = $participante_id;


        // // Generar el código QR
        // $this->qrCode = QrCode::size(200)->generate($url);

        // Generar la URL
        $url = "http://localhost:8080/validate/{$this->evento_id}/{$this->participante_id}";

        // Crear el QR usando BaconQrCode
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        // Generar el código QR como SVG
        $this->qrCode = $writer->writeString($url);
    }


    public function render()
    {
        $evento = Evento::findOrFail($this->evento_id);
        $participante = Participante::findOrFail($this->participante_id);

        return view('livewire.qr-generator', [
            'evento' => $evento,
            'participante' => $participante,
            'qrCode' => $this->qrCode,
        ]);
    }
}
