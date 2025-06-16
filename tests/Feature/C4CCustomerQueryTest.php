<?php

namespace Tests\Feature;

use App\Services\C4C\CustomerService;
use Tests\TestCase;

class C4CCustomerQueryTest extends TestCase
{
    protected CustomerService $customerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerService = app(CustomerService::class);
    }

    /** @test */
    public function it_can_find_customer_by_valid_dni()
    {
        // DNI validado del ejemplo Python
        $result = $this->customerService->findByDNI('40359482');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']);
        $this->assertEquals('ALEJANDRO TOLEDO PARRA', $result['data'][0]['organisation']['first_line_name']);
        $this->assertEquals('1000140', $result['data'][0]['internal_id']);
    }

    /** @test */
    public function it_returns_empty_for_invalid_dni()
    {
        $result = $this->customerService->findByDNI('99999999');

        // Puede ser success=true con data=[] o success=false
        $this->assertTrue($result['success'] === false || empty($result['data']));
    }

    /** @test */
    public function it_can_find_customer_with_fallback()
    {
        // Test del Ejemplo 1 Python
        $result = $this->customerService->findWithFallback('40359482', '20558638223');

        $this->assertTrue($result['success']);
        $this->assertEquals('DNI', $result['search_type']);
        $this->assertEquals('40359482', $result['document_used']);
        $this->assertFalse($result['fallback_used']);
    }

    /** @test */
    public function it_uses_fallback_when_dni_not_found()
    {
        $result = $this->customerService->findWithFallback('99999999', '20558638223');

        if ($result['success']) {
            $this->assertEquals('RUC', $result['search_type']);
            $this->assertEquals('20558638223', $result['document_used']);
            $this->assertTrue($result['fallback_used']);
        } else {
            // Si ambos fallan
            $this->assertFalse($result['success']);
            $this->assertTrue($result['fallback_used']);
        }
    }

    /** @test */
    public function it_can_find_customer_with_multiple_documents()
    {
        // Test del Ejemplo 3 Python
        $documents = ['12345678', '40359482', '99999999999'];
        $result = $this->customerService->findMultiple($documents);

        $this->assertTrue($result['success']);
        $this->assertContains($result['document_used'], $documents);
        $this->assertLessThanOrEqual(count($documents), $result['attempt_number']);
    }

    /** @test */
    public function it_validates_dni_format()
    {
        // DNI muy corto
        $result = $this->customerService->findByDNI('123');
        $this->assertFalse($result['success']);

        // DNI con caracteres
        $result = $this->customerService->findByDNI('abc12345');
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_validates_ruc_format()
    {
        // RUC muy corto
        $result = $this->customerService->findByRUC('123');
        $this->assertFalse($result['success']);

        // RUC muy largo
        $result = $this->customerService->findByRUC('123456789012345');
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function api_can_find_customer_by_document()
    {
        $response = $this->postJson('/api/c4c/customers/find', [
            'document_type' => 'DNI',
            'document_number' => '40359482',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uuid',
                        'internal_id',
                        'external_id',
                        'organisation' => [
                            'first_line_name',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function api_can_find_customer_with_fallback()
    {
        $response = $this->postJson('/api/c4c/customers/find-with-fallback', [
            'dni' => '40359482',
            'ruc' => '20558638223',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'search_type',
                'document_used',
                'fallback_used',
                'data',
            ]);
    }

    /** @test */
    public function api_can_find_customer_with_multiple_documents()
    {
        $response = $this->postJson('/api/c4c/customers/find-multiple', [
            'documents' => ['12345678', '40359482', '99999999999'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'search_type',
                'document_used',
                'attempt_number',
                'total_attempts',
                'data',
            ]);
    }
}
