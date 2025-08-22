<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetUrl;
    public $documentType;
    public $documentNumber;

    /**
     * Create a new message instance.
     */
    public function __construct(string $resetUrl, string $documentType, string $documentNumber)
    {
        $this->resetUrl = $resetUrl;
        $this->documentType = $documentType;
        $this->documentNumber = $documentNumber;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Restablece tu contraseña - Mitsui',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'resetUrl' => $this->resetUrl,
                'documentType' => $this->documentType,
                'documentNumber' => $this->documentNumber,
                'expiresInMinutes' => 30, // Token expires in 30 minutes
            ]
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