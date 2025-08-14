<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class C4CAppointmentDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:appointment-demo
                            {customer_id : ID del cliente en C4C}
                            {--license_plate= : Placa del vehículo (si no se especifica, se genera automáticamente)}
                            {--customer_name= : Nombre del cliente (por defecto: Cliente Demo Laravel)}
                            {--real : Usar servicio real en lugar de mock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demostración completa de gestión de citas C4C (Crear, Actualizar, Eliminar, Consultar)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->argument('customer_id');

        $this->info('🎯 DEMOSTRACIÓN COMPLETA DE GESTIÓN DE CITAS C4C');
        $this->info("Cliente: {$customerId}");
        $this->info(str_repeat('=', 70));

        if ($this->option('real')) {
            $this->info('🔴 Usando servicio REAL - Las operaciones afectarán datos reales');
            if (! $this->confirm('¿Está seguro de continuar con el servicio real?')) {
                $this->info('Operación cancelada.');

                return 0;
            }
        } else {
            $this->info('🟡 Usando servicio mock (usar --real para servicio real)');
        }

        try {
            $appointmentService = new AppointmentService;

            // PASO 1: Consultar citas existentes
            $this->info("\n📋 PASO 1: Consultando citas existentes...");
            $existingAppointments = $appointmentService->queryPendingAppointments($customerId);

            if ($existingAppointments['success']) {
                $count = $existingAppointments['count'] ?? 0;
                $this->info("✅ Cliente tiene {$count} cita(s) pendiente(s)");
            } else {
                $this->warn('⚠️ No se pudieron consultar las citas existentes');
            }

            // PASO 2: Crear nueva cita
            $this->info("\n🆕 PASO 2: Creando nueva cita...");

            // Determinar placa a usar
            $licensePlate = $this->option('license_plate');
            if (empty($licensePlate)) {
                $licensePlate = 'DEMO-'.rand(100, 999);
                $this->info("🔢 Placa generada automáticamente: {$licensePlate}");
            } else {
                $this->info("🚗 Usando placa especificada: {$licensePlate}");
            }

            // Determinar nombre del cliente
            $customerName = $this->option('customer_name') ?: 'Cliente Demo Laravel';

            $appointmentData = [
                'customer_id' => $customerId,
                'start_date' => Carbon::now()->addDay()->setHour(14)->setMinute(0)->format('Y-m-d H:i:s'),
                'end_date' => Carbon::now()->addDay()->setHour(15)->setMinute(0)->format('Y-m-d H:i:s'),
                'center_id' => 'M013',
                'vehicle_plate' => $licensePlate,
                'customer_name' => $customerName,
                'notes' => 'Cita de demostración creada desde Laravel - '.now()->format('Y-m-d H:i:s'),
                'express' => 'false',
            ];

            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Customer ID', $appointmentData['customer_id']],
                    ['Fecha Inicio', $appointmentData['start_date']],
                    ['Fecha Fin', $appointmentData['end_date']],
                    ['Centro', $appointmentData['center_id']],
                    ['Placa', $appointmentData['vehicle_plate']],
                    ['Cliente', $appointmentData['customer_name']],
                    ['Observaciones', $appointmentData['notes']],
                ]
            );

            $createResult = $appointmentService->create($appointmentData);

            if ($createResult['success']) {
                $this->info('✅ Cita creada exitosamente');
                $appointmentUuid = $createResult['data']['uuid'] ?? null;
                $appointmentId = $createResult['data']['id'] ?? null;

                if ($appointmentUuid) {
                    $this->info("UUID: {$appointmentUuid}");
                    $this->info("ID: {$appointmentId}");

                    // PASO 3: Actualizar la cita recién creada
                    $this->info("\n📝 PASO 3: Actualizando la cita recién creada...");
                    $updateData = [
                        'appointment_status' => '2', // Confirmada
                        'customer_name' => 'Cliente Demo Laravel (ACTUALIZADO)',
                        'notes' => 'Cita actualizada desde Laravel - '.now()->format('Y-m-d H:i:s'),
                    ];

                    $updateResult = $appointmentService->update($appointmentUuid, $updateData);

                    if ($updateResult['success']) {
                        $this->info('✅ Cita actualizada exitosamente');

                        // Mostrar warnings si existen
                        if (isset($updateResult['warnings']) && ! empty($updateResult['warnings'])) {
                            $this->info("\n⚠️ Advertencias:");
                            foreach ($updateResult['warnings'] as $warning) {
                                $this->warn('  - '.$warning);
                            }
                        }
                    } else {
                        $this->error('❌ Error al actualizar la cita: '.($updateResult['error'] ?? 'Error desconocido'));
                    }

                    // PASO 4: Consultar citas nuevamente para ver los cambios
                    $this->info("\n🔍 PASO 4: Consultando citas después de la actualización...");
                    $updatedAppointments = $appointmentService->queryPendingAppointments($customerId);

                    if ($updatedAppointments['success']) {
                        $count = $updatedAppointments['count'] ?? 0;
                        $this->info("✅ Cliente ahora tiene {$count} cita(s) pendiente(s)");
                    }

                    // PASO 5: Preguntar si eliminar la cita
                    if ($this->confirm("\n🗑️ ¿Desea eliminar la cita de demostración?")) {
                        $this->info('PASO 5: Eliminando la cita de demostración...');

                        $deleteResult = $appointmentService->delete($appointmentUuid);

                        if ($deleteResult['success']) {
                            $this->info('✅ Cita eliminada exitosamente');

                            // Mostrar warnings si existen
                            if (isset($deleteResult['warnings']) && ! empty($deleteResult['warnings'])) {
                                $this->info("\n⚠️ Advertencias:");
                                foreach ($deleteResult['warnings'] as $warning) {
                                    $this->warn('  - '.$warning);
                                }
                            }
                        } else {
                            $this->error('❌ Error al eliminar la cita: '.($deleteResult['error'] ?? 'Error desconocido'));
                        }
                    } else {
                        $this->info("ℹ️ Cita conservada. UUID: {$appointmentUuid}");
                    }

                } else {
                    $this->warn('⚠️ No se obtuvo UUID de la cita creada, no se pueden realizar operaciones adicionales');
                }

                // Mostrar warnings de creación si existen
                if (isset($createResult['warnings']) && ! empty($createResult['warnings'])) {
                    $this->info("\n⚠️ Advertencias de creación:");
                    foreach ($createResult['warnings'] as $warning) {
                        $this->warn('  - '.$warning);
                    }
                }

            } else {
                $this->error('❌ Error al crear la cita: '.($createResult['error'] ?? 'Error desconocido'));

                return 1;
            }

            // PASO FINAL: Consulta final
            $this->info("\n📊 CONSULTA FINAL: Estado actual de las citas...");
            $finalAppointments = $appointmentService->queryPendingAppointments($customerId);

            if ($finalAppointments['success']) {
                $count = $finalAppointments['count'] ?? 0;
                $this->info("✅ Cliente tiene {$count} cita(s) pendiente(s) al final");
            }

            $this->info("\n🎉 ¡Demostración completada exitosamente!");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Excepción durante la demostración: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return 1;
        }
    }
}
