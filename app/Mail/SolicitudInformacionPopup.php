<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudInformacionPopup extends Mailable
{
    use Queueable, SerializesModels;

    public $datosUsuario;

    public $nombrePopup;

    /**
     * Create a new message instance.
     */
    public function __construct(array $datosUsuario, string $nombrePopup)
    {
        $this->datosUsuario = $datosUsuario;
        $this->nombrePopup = $nombrePopup;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de Información - '.$this->nombrePopup,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-informacion-popup',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
