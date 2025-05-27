<?php

namespace Tests\Feature\C4C;

use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    /**
     * Test consulta por DNI.
     */
    public function test_find_customer_by_dni(): void
    {
        // Crear una solicitud POST a la API
        $response = $this->postJson('/api/c4c/customers/find', [
            'document_type' => 'DNI',
            'document_number' => '40359482',
        ]);

        // Verificar que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verificar la estructura de la respuesta
        $response->assertJsonStructure([
            'success',
            'error',
            'data',
            'count',
        ]);

        // Imprimir la respuesta para depuración
        $this->info('Respuesta de consulta por DNI: '.json_encode($response->json(), JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta por RUC.
     */
    public function test_find_customer_by_ruc(): void
    {
        // Crear una solicitud POST a la API
        $response = $this->postJson('/api/c4c/customers/find', [
            'document_type' => 'RUC',
            'document_number' => '20558638223',
        ]);

        // Verificar que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verificar la estructura de la respuesta
        $response->assertJsonStructure([
            'success',
            'error',
            'data',
            'count',
        ]);

        // Imprimir la respuesta para depuración
        $this->info('Respuesta de consulta por RUC: '.json_encode($response->json(), JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta por CE.
     */
    public function test_find_customer_by_ce(): void
    {
        // Crear una solicitud POST a la API
        $response = $this->postJson('/api/c4c/customers/find', [
            'document_type' => 'CE',
            'document_number' => '73532531',
        ]);

        // Verificar que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verificar la estructura de la respuesta
        $response->assertJsonStructure([
            'success',
            'error',
            'data',
            'count',
        ]);

        // Imprimir la respuesta para depuración
        $this->info('Respuesta de consulta por CE: '.json_encode($response->json(), JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta por Pasaporte.
     */
    public function test_find_customer_by_passport(): void
    {
        // Crear una solicitud POST a la API
        $response = $this->postJson('/api/c4c/customers/find', [
            'document_type' => 'PASSPORT',
            'document_number' => '37429823',
        ]);

        // Verificar que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verificar la estructura de la respuesta
        $response->assertJsonStructure([
            'success',
            'error',
            'data',
            'count',
        ]);

        // Imprimir la respuesta para depuración
        $this->info('Respuesta de consulta por Pasaporte: '.json_encode($response->json(), JSON_PRETTY_PRINT));
    }

    /**
     * Test consulta con tipo de documento inválido.
     */
    public function test_find_customer_with_invalid_document_type(): void
    {
        // Crear una solicitud POST a la API con un tipo de documento inválido
        $response = $this->postJson('/api/c4c/customers/find', [
            'document_type' => 'INVALID',
            'document_number' => '12345678',
        ]);

        // Verificar que la respuesta sea un error de validación (código 422)
        $response->assertStatus(422);

        // Verificar que el error sea sobre el tipo de documento
        $response->assertJsonValidationErrors(['document_type']);
    }

    /**
     * Test consulta sin número de documento.
     */
    public function test_find_customer_without_document_number(): void
    {
        // Crear una solicitud POST a la API sin número de documento
        $response = $this->postJson('/api/c4c/customers/find', [
            'document_type' => 'DNI',
        ]);

        // Verificar que la respuesta sea un error de validación (código 422)
        $response->assertStatus(422);

        // Verificar que el error sea sobre el número de documento
        $response->assertJsonValidationErrors(['document_number']);
    }
}
