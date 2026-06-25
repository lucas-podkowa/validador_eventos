<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmacionInscripcion extends Mailable
{
    use Queueable, SerializesModels;

    public $nombre;

    public $apellido;

    public $evento;

    public $asunto;

    public function __construct($nombre, $apellido, $evento, $asunto)
    {
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->evento = $evento;
        $this->asunto = $asunto;
    }

    public function build()
    {
        return $this->subject('Confirmación de inscripción al evento')
            ->view('emails.confirmacion_inscripcion');
    }
}
