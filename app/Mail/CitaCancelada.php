<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CitaCancelada extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $datosCliente;
    public $datosVehiculo;
    public $motivoCancelacion;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, array $datosCliente, array $datosVehiculo, string $motivoCancelacion = '')
    {
        // Cargar la relación de servicios adicionales
        $this->appointment = $appointment->load('additionalServices.additionalService');
        $this->datosCliente = $datosCliente;
        $this->datosVehiculo = $datosVehiculo;
        $this->motivoCancelacion = $motivoCancelacion;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Tu cita ha sido cancelada',
            cc: ['citasmantenimiento@mitsuiautomotriz.com'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.cita-cancelada',
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