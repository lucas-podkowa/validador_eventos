<?php

namespace App\Mail;

use App\Models\Emision;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CertificadoTutorMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Emision $certificado) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu certificado de Tutoría Académica',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.certificado-tutor',
        );
    }

    public function attachments(): array
    {
        if (! $this->certificado->certificado_path) {
            return [];
        }

        return [
            Attachment::fromPath(Storage::disk('private')->path($this->certificado->certificado_path))
                ->as(basename($this->certificado->certificado_path))
                ->withMime('application/pdf'),
        ];
    }
}
