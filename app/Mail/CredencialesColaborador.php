<?php

namespace App\Mail;

use App\Models\Evento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredencialesColaborador extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombre;
    public string $apellido;
    public string $email;
    public string $password;
    public Evento $evento;
    public bool $usuarioNuevo;

    /**
     * @param string $nombre       Nombre del colaborador
     * @param string $apellido     Apellido del colaborador
     * @param string $email        Email / usuario para iniciar sesión
     * @param string $password     Contraseña en texto plano (DNI) — solo para mostrar en el correo
     * @param Evento $evento       Evento al que fue asignado
     * @param bool   $usuarioNuevo Indica si la cuenta fue creada ahora o ya existía
     */
    public function __construct(
        string $nombre,
        string $apellido,
        string $email,
        string $password,
        Evento $evento,
        bool $usuarioNuevo = true
    ) {
        $this->nombre      = $nombre;
        $this->apellido    = $apellido;
        $this->email       = $email;
        $this->password    = $password;
        $this->evento      = $evento;
        $this->usuarioNuevo = $usuarioNuevo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tus credenciales de acceso al sistema',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciales_colaborador',
        );
    }
}
