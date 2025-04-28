<?php

namespace Tests\Unit\C4C;

use App\Services\C4C\C4CClient;
use App\Services\C4C\CustomerService;
use Mockery;
use Tests\TestCase;

class CustomerServiceMockTest extends TestCase
{
    /**
     * Test consulta por DNI con mock.
     */
    public function test_find_by_dni_with_mock(): void
    {
        // Crear un mock de C4CClient
        $mock = Mockery::mock('alias:App\Services\C4C\C4CClient');
        
        // Configurar el comportamiento esperado
        $mock->shouldReceive('call')
            ->once()
            ->with(
                config('c4c.services.customer.wsdl'),
                config('c4c.services.customer.method'),
                Mockery::on(function ($params) {
                    // Verificar que los parámetros incluyan el DNI correcto
                    return isset($params['CustomerSelectionByElements']['y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON']['SelectionByText']['LowerBoundaryName']) 
                        && $params['CustomerSelectionByElements']['y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON']['SelectionByText']['LowerBoundaryName'] === '40359482';
                })
            )
            ->andReturn([
                'success' => true,
                'error' => null,
                'data' => (object) [
                    'Customer' => (object) [
                        'UUID' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
                        'InternalID' => '80019',
                        'ExternalID' => '0000080019',
                        'Organisation' => (object) [
                            'FirstLineName' => 'JUAN PEREZ'
                        ],
                        'CategoryCode' => '1',
                        'LifeCycleStatusCode' => '2',
                        'AddressInformation' => (object) [
                            'Address' => (object) [
                                'CountryCode' => 'PE',
                                'CityName' => 'LIMA',
                                'StreetName' => 'AV. JAVIER PRADO ESTE 123',
                                'StreetPostalCode' => '15024'
                            ]
                        ]
                    ]
                ]
            ]);
        
        // Crear una instancia del servicio
        $customerService = new CustomerService();
        
        // Llamar al método que queremos probar
        $result = $customerService->findByDNI('40359482');
        
        // Verificar el resultado
        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('JUAN PEREZ', $result['data'][0]['name']);
    }

    /**
     * Test consulta por RUC con mock.
     */
    public function test_find_by_ruc_with_mock(): void
    {
        // Crear un mock de C4CClient
        $mock = Mockery::mock('alias:App\Services\C4C\C4CClient');
        
        // Configurar el comportamiento esperado
        $mock->shouldReceive('call')
            ->once()
            ->with(
                config('c4c.services.customer.wsdl'),
                config('c4c.services.customer.method'),
                Mockery::on(function ($params) {
                    // Verificar que los parámetros incluyan el RUC correcto
                    return isset($params['CustomerSelectionByElements']['y6s:zRuc_EA8AE8AUBVHCSXVYS0FJ1R3ON']['SelectionByText']['LowerBoundaryName']) 
                        && $params['CustomerSelectionByElements']['y6s:zRuc_EA8AE8AUBVHCSXVYS0FJ1R3ON']['SelectionByText']['LowerBoundaryName'] === '20558638223';
                })
            )
            ->andReturn([
                'success' => true,
                'error' => null,
                'data' => (object) [
                    'Customer' => (object) [
                        'UUID' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
                        'InternalID' => '80019',
                        'ExternalID' => '0000080019',
                        'Organisation' => (object) [
                            'FirstLineName' => 'AQP MUSIC E.I.R.L.'
                        ],
                        'CategoryCode' => '2',
                        'LifeCycleStatusCode' => '2',
                        'AddressInformation' => (object) [
                            'Address' => (object) [
                                'CountryCode' => 'PE',
                                'CityName' => 'LIMA',
                                'StreetName' => 'AV. JAVIER PRADO ESTE 6042',
                                'StreetPostalCode' => '15024'
                            ]
                        ]
                    ]
                ]
            ]);
        
        // Crear una instancia del servicio
        $customerService = new CustomerService();
        
        // Llamar al método que queremos probar
        $result = $customerService->findByRUC('20558638223');
        
        // Verificar el resultado
        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('AQP MUSIC E.I.R.L.', $result['data'][0]['name']);
    }

    /**
     * Test consulta por CE con mock.
     */
    public function test_find_by_ce_with_mock(): void
    {
        // Crear un mock de C4CClient
        $mock = Mockery::mock('alias:App\Services\C4C\C4CClient');
        
        // Configurar el comportamiento esperado
        $mock->shouldReceive('call')
            ->once()
            ->andReturn([
                'success' => true,
                'error' => null,
                'data' => (object) [
                    'Customer' => (object) [
                        'UUID' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
                        'InternalID' => '80020',
                        'ExternalID' => '0000080020',
                        'Organisation' => (object) [
                            'FirstLineName' => 'MARIA RODRIGUEZ'
                        ],
                        'CategoryCode' => '1',
                        'LifeCycleStatusCode' => '2',
                    ]
                ]
            ]);
        
        // Crear una instancia del servicio
        $customerService = new CustomerService();
        
        // Llamar al método que queremos probar
        $result = $customerService->findByCE('73532531');
        
        // Verificar el resultado
        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('MARIA RODRIGUEZ', $result['data'][0]['name']);
    }

    /**
     * Test consulta por Pasaporte con mock.
     */
    public function test_find_by_passport_with_mock(): void
    {
        // Crear un mock de C4CClient
        $mock = Mockery::mock('alias:App\Services\C4C\C4CClient');
        
        // Configurar el comportamiento esperado
        $mock->shouldReceive('call')
            ->once()
            ->andReturn([
                'success' => true,
                'error' => null,
                'data' => (object) [
                    'Customer' => (object) [
                        'UUID' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
                        'InternalID' => '80021',
                        'ExternalID' => '0000080021',
                        'Organisation' => (object) [
                            'FirstLineName' => 'JOHN SMITH'
                        ],
                        'CategoryCode' => '1',
                        'LifeCycleStatusCode' => '2',
                    ]
                ]
            ]);
        
        // Crear una instancia del servicio
        $customerService = new CustomerService();
        
        // Llamar al método que queremos probar
        $result = $customerService->findByPassport('37429823');
        
        // Verificar el resultado
        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('JOHN SMITH', $result['data'][0]['name']);
    }

    /**
     * Test consulta por DNI sin resultados con mock.
     */
    public function test_find_by_dni_no_results_with_mock(): void
    {
        // Crear un mock de C4CClient
        $mock = Mockery::mock('alias:App\Services\C4C\C4CClient');
        
        // Configurar el comportamiento esperado
        $mock->shouldReceive('call')
            ->once()
            ->andReturn([
                'success' => true,
                'error' => null,
                'data' => (object) [
                    'ProcessingConditions' => (object) [
                        'ReturnedQueryHitsNumberValue' => '0',
                        'MoreHitsAvailableIndicator' => 'false',
                    ]
                ]
            ]);
        
        // Crear una instancia del servicio
        $customerService = new CustomerService();
        
        // Llamar al método que queremos probar
        $result = $customerService->findByDNI('99999999');
        
        // Verificar el resultado
        $this->assertFalse($result['success']);
        $this->assertEquals('Customer not found', $result['error']);
        $this->assertNull($result['data']);
    }

    /**
     * Test error en la llamada SOAP con mock.
     */
    public function test_soap_error_with_mock(): void
    {
        // Crear un mock de C4CClient
        $mock = Mockery::mock('alias:App\Services\C4C\C4CClient');
        
        // Configurar el comportamiento esperado
        $mock->shouldReceive('call')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'SOAP fault: Authentication failed',
                'data' => null
            ]);
        
        // Crear una instancia del servicio
        $customerService = new CustomerService();
        
        // Llamar al método que queremos probar
        $result = $customerService->findByDNI('40359482');
        
        // Verificar el resultado
        $this->assertFalse($result['success']);
        $this->assertEquals('SOAP fault: Authentication failed', $result['error']);
        $this->assertNull($result['data']);
    }
}
