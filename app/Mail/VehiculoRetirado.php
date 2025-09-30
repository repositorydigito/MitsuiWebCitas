<?php

namespace App\Mail;

use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehiculoRetirado extends Mailable
{
    use Queueable, SerializesModels;

    public $vehiculo;
    public $cliente;
    public $fechaRetiro;

    /**
     * Create a new message instance.
     */
    public function __construct(Vehicle $vehiculo, User $cliente)
    {
        $this->vehiculo = $vehiculo;
        $this->cliente = $cliente;
        $this->fechaRetiro = now()->subHours(5)->format('d/m/Y H:i:s');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: ['bchuco@digito.pe'],
            subject: '🚗 Alerta: Vehículo Retirado del Sistema',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.vehiculo-retirado',
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