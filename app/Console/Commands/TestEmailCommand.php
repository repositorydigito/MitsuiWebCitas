<?php

namespace App\Console\Commands;

use App\Mail\SolicitudInformacionPopup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Enviando correo de prueba a: {$email}");

        try {
            $datosUsuario = [
                'nombres' => 'Usuario',
                'apellidos' => 'De Prueba',
                'email' => 'usuario@test.com',
                'celular' => '999123456',
                'dni' => '12345678',
                'placa' => 'ABC-123',
            ];

            Mail::to($email)->send(new SolicitudInformacionPopup($datosUsuario, 'Servicio de Prueba'));

            $this->info('✅ Correo enviado exitosamente!');

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar correo: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());
        }
    }
}
