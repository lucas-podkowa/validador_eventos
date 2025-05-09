<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmacionInscripcion extends Mailable
{
    use Queueable, SerializesModels;
    public $nombre;
    public $apellido;
    public $evento;

    /**
     * Create a new message instance.
     */
    public function __construct($nombre, $apellido, $evento)
    {
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->evento = $evento;
    }

    public function build()
    {
        return $this->subject('Confirmación de inscripción al evento')
            ->view('emails.confirmacion_inscripcion');
    }

    // /**
    //  * Get the message envelope.
    //  */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Confirmacion Inscripcion',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    // /**
    //  * Get the attachments for the message.
    //  *
    //  * @return array<int, \Illuminate\Mail\Mailables\Attachment>
    //  */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
