<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\C4C\CustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateComodinUsersC4CIdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(CustomerService $customerService): void
    {
        // Buscar usuarios comodín con c4c_internal_id = 1200166011
        $comodinUsers = User::where('c4c_internal_id', '1200166011')
            ->where('is_comodin', true)
            ->whereNotNull('document_number')
            ->limit(5)
            ->get();

        Log::info('UpdateComodinUsersC4CIdJob: Procesando ' . $comodinUsers->count() . ' usuarios comodín');

        foreach ($comodinUsers as $user) {
            try {
                // Buscar usuario en C4C por documento
                $c4cCustomer = $customerService->findByDocument(
                    $user->document_type,
                    $user->document_number
                );

                if ($c4cCustomer && isset($c4cCustomer['internal_id'])) {
                    // Usuario encontrado en C4C, actualizar con ID real
                    $user->update([
                        'c4c_internal_id' => $c4cCustomer['internal_id'],
                        'c4c_uuid' => $c4cCustomer['uuid'] ?? null,
                        'is_comodin' => false
                    ]);

                    Log::info("Usuario actualizado: {$user->document_number} -> c4c_internal_id: {$c4cCustomer['internal_id']}");
                } else {
                    Log::info("Usuario no encontrado en C4C: {$user->document_number}");
                }

            } catch (\Exception $e) {
                Log::error("Error actualizando usuario {$user->document_number}: " . $e->getMessage());
            }
        }

        Log::info('UpdateComodinUsersC4CIdJob: Proceso completado');
    }
}
