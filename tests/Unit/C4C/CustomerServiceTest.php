<?php

namespace Tests\Unit\C4C;

use App\Services\C4C\CustomerService;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    /**
     * @var CustomerService
     */
    protected $customerService;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->customerService = app(CustomerService::class);
    }

    /**
     * Test consulta por DNI.
     */
    public function test_find_by_dni(): void
    {
        $result = $this->customerService->findByDNI('40359482');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('data', $result);

        // Imprimir el resultado para depuración
        $this->info('Resultado de findByDNI: '.json_encode($result, JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta por RUC.
     */
    public function test_find_by_ruc(): void
    {
        $result = $this->customerService->findByRUC('20558638223');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('data', $result);

        // Imprimir el resultado para depuración
        $this->info('Resultado de findByRUC: '.json_encode($result, JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta por CE.
     */
    public function test_find_by_ce(): void
    {
        $result = $this->customerService->findByCE('73532531');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('data', $result);

        // Imprimir el resultado para depuración
        $this->info('Resultado de findByCE: '.json_encode($result, JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta por Pasaporte.
     */
    public function test_find_by_passport(): void
    {
        $result = $this->customerService->findByPassport('37429823');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('data', $result);

        // Imprimir el resultado para depuración
        $this->info('Resultado de findByPassport: '.json_encode($result, JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta por DNI con número inválido.
     */
    public function test_find_by_dni_with_invalid_number(): void
    {
        $result = $this->customerService->findByDNI('99999999');

        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertNull($result['data']);

        // Imprimir el resultado para depuración
        $this->info('Resultado de findByDNI con número inválido: '.json_encode($result, JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta por RUC con número inválido.
     */
    public function test_find_by_ruc_with_invalid_number(): void
    {
        $result = $this->customerService->findByRUC('99999999999');

        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertNull($result['data']);

        // Imprimir el resultado para depuración
        $this->info('Resultado de findByRUC con número inválido: '.json_encode($result, JSON_PRETTY_PRINT));
    }
}
