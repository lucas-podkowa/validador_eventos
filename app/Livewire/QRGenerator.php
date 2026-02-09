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
        try {
            $this->evento_id = $evento_id;
            $this->participante_id = $participante_id;

            // Validar que los IDs sean enteros positivos
            if (!is_numeric($evento_id) || !is_numeric($participante_id) || $evento_id <= 0 || $participante_id <= 0) {
                throw new \InvalidArgumentException('IDs inválidos para evento o participante.');
            }

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
        } catch (\Throwable $e) {
            $this->qrCode = null;
            $this->dispatch('oops', message: 'Error generando QR: ' . $e->getMessage());
        }
    }


    public function render()
    {
        try {
            $evento = Evento::findOrFail($this->evento_id);
            $participante = Participante::findOrFail($this->participante_id);

            return view('livewire.qr-generator', [
                'evento' => $evento,
                'participante' => $participante,
                'qrCode' => $this->qrCode,
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('oops', message: 'Error cargando datos: ' . $e->getMessage());
            return view('livewire.qr-generator', [
                'evento' => null,
                'participante' => null,
                'qrCode' => null,
            ]);
        }
    }
}
