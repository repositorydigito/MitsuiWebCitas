<?php

namespace App\Console\Commands;

use App\Services\C4C\C4CClient;
use Illuminate\Console\Command;

class TestSoapGeneration extends Command
{
    protected $signature = 'c4c:test-soap-generation';
    protected $description = 'Test SOAP XML generation directly';

    public function handle()
    {
        $this->info("🧪 Testing SOAP XML generation directly");
        
        // Simular parámetros básicos como los que genera OfferService
        $params = [
            'CustomerQuote' => [
                'BuyerParty' => [
                    'BusinessPartnerInternalID' => '1200191766'
                ],
                'EmployeeResponsibleParty' => [
                    'EmployeeID' => '8000000010'
                ],
                'SalesAndServiceBusinessArea' => [
                    'SalesOrganisationID' => 'DM07',
                    'SalesOfficeID' => 'OVDM01',
                    'SalesGroupID' => 'D03',
                    'DistributionChannelCode' => ['_' => 'D4'],
                    'DivisionCode' => ['_' => 'D2']
                ],
                'BusinessTransactionDocumentReference' => [
                    'UUID' => ['_' => 'f9bcf6c4-b9d1-1fd0-94ab-af57f9a104c6']
                ],
                'Text' => [
                    'ContentText' => 'Test offer'
                ],
                'y6s:zOVIDCentro' => 'M013',
                'y6s:zOVPlaca' => 'BJD-733',
                'y6s:zOVKilometraje' => '0',
                'y6s:zOVServExpress' => 'false',
                'Item' => [
                    [
                        'ItemProduct' => [
                            'ProductID' => ['_' => 'P010'],
                            'ProductInternalID' => ['_' => 'P010']
                        ],
                        'ItemRequestedScheduleLine' => [
                            'Quantity' => ['_' => '1.0', 'unitCode' => 'EA']
                        ],
                        'y6s:zOVPosIDTipoPosicion' => ['_' => 'P009'],
                        'y6s:zID_PAQUETE' => 'M2275-010',
                        'y6s:zOVPosTiempoTeorico' => '0'
                    ],
                    [
                        'ItemProduct' => [
                            'ProductID' => ['_' => 'Z01_SRV_E_P010'],
                            'ProductInternalID' => ['_' => 'Z01_SRV_E_P010']
                        ],
                        'ItemRequestedScheduleLine' => [
                            'Quantity' => ['_' => '1.0', 'unitCode' => 'EA']
                        ],
                        'y6s:zOVPosIDTipoPosicion' => ['_' => 'P001'],
                        'y6s:zID_PAQUETE' => 'M2275-010',
                        'y6s:zOVPosTiempoTeorico' => '1.3'
                    ]
                ]
            ]
        ];
        
        $this->info("📋 Parámetros preparados:");
        $this->line("Total Items: " . count($params['CustomerQuote']['Item']));
        
        // Usar reflexión para acceder al método privado
        try {
            $reflection = new \ReflectionClass(C4CClient::class);
            $method = $reflection->getMethod('buildOfferCreateSoapBodySimple');
            $method->setAccessible(true);
            
            $soapBody = $method->invoke(null, $params);
            
            $this->info("\n🔍 SOAP Body generado:");
            $this->line("Longitud: " . strlen($soapBody));
            
            // Verificar si contiene los productos
            $containsP010 = strpos($soapBody, 'P010') !== false;
            $containsZ01 = strpos($soapBody, 'Z01_SRV_E_P010') !== false;
            $containsCustomerQuote = strpos($soapBody, '<CustomerQuote') !== false;
            $containsItems = strpos($soapBody, '<Item') !== false;
            
            $this->info("\n📊 Verificación de contenido:");
            $this->line("¿Contiene CustomerQuote? " . ($containsCustomerQuote ? 'SÍ' : 'NO'));
            $this->line("¿Contiene Items? " . ($containsItems ? 'SÍ' : 'NO'));
            $this->line("¿Contiene P010? " . ($containsP010 ? 'SÍ' : 'NO'));
            $this->line("¿Contiene Z01_SRV_E_P010? " . ($containsZ01 ? 'SÍ' : 'NO'));
            
            if ($containsCustomerQuote && $containsItems) {
                $this->info("\n✅ El SOAP Body parece correcto!");
                
                // Mostrar preview
                $this->info("\nPreview (primeros 500 caracteres):");
                $this->line(substr($soapBody, 0, 500) . "...");
                
            } else {
                $this->error("\n❌ El SOAP Body está incompleto!");
                $this->line("Contenido completo:");
                $this->line($soapBody);
            }
            
        } catch (\Exception $e) {
            $this->error("💥 Error: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}