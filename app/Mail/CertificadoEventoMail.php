<?php

namespace App\Mail;

use App\Models\Evento;
use App\Models\Participante;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;

class CertificadoEventoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $evento;
    public $participante;
    public $certificadoPath;

    public function __construct(Evento $evento, Participante $participante, string $certificadoPath)
    {
        $this->evento = $evento;
        $this->participante = $participante;
        $this->certificadoPath = $certificadoPath;
    }


    public function envelope()
    {
        return new Envelope(
            subject: 'Tu Certificado del Evento: ' . $this->evento->nombre,
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.certificado-evento',
        );
    }

    public function attachments()
    {
        // Adjuntamos el certificado en PDF desde el disco privado
        return [
            Attachment::fromPath(Storage::disk('private')->path($this->certificadoPath))
                ->as(basename($this->certificadoPath))
                ->withMime('application/pdf'),
        ];
    }
}
