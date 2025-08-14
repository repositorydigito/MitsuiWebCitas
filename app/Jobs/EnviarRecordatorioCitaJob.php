<?php

namespace App\Jobs;

use App\Mail\RecordatorioCita;
use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorioCitaJob implements ShouldQueue
{
    use Queueable;

    public $appointment;

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('📧 [RecordatorioCita] Iniciando envío de recordatorio', [
                'appointment_id' => $this->appointment->id,
                'appointment_number' => $this->appointment->appointment_number,
                'customer_email' => $this->appointment->customer_email,
                'appointment_date' => $this->appointment->appointment_date,
            ]);

            // Verificar que la cita aún esté activa
            if ($this->appointment->status === 'cancelled') {
                Log::info('📧 [RecordatorioCita] Cita cancelada, no se envía recordatorio', [
                    'appointment_id' => $this->appointment->id,
                ]);
                return;
            }

            // Preparar datos del cliente
            $datosCliente = [
                'nombres' => $this->appointment->customer_name,
                'apellidos' => $this->appointment->customer_last_name,
                'email' => $this->appointment->customer_email,
                'celular' => $this->appointment->customer_phone,
            ];

            // Preparar datos del vehículo
            $datosVehiculo = [
                'marca' => $this->appointment->vehicle_brand ?? 'No especificado',
                'modelo' => $this->appointment->vehicle_model ?? 'No especificado',
                'placa' => $this->appointment->vehicle_license_plate ?? 'No especificado',
            ];

            // Enviar el correo
            Mail::to($this->appointment->customer_email)
                ->send(new RecordatorioCita($this->appointment, $datosCliente, $datosVehiculo));

            Log::info('📧 [RecordatorioCita] Recordatorio enviado exitosamente', [
                'appointment_id' => $this->appointment->id,
                'customer_email' => $this->appointment->customer_email,
            ]);

        } catch (\Exception $e) {
            Log::error('📧 [RecordatorioCita] Error enviando recordatorio', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lanzar la excepción para que el job se marque como fallido
            throw $e;
        }
    }
}
