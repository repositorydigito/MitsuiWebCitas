#!/usr/bin/env python3
"""
Cliente SOAP para servicios C4C
Autor: Script de pruebas automatizado
Fecha: 2025-05-30
"""

import requests
import base64
import json
import datetime
from typing import Dict, Any, Optional, Tuple
import logging

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class C4CSoapClient:
    """Cliente SOAP para interactuar con servicios C4C"""
    
    def __init__(self):
        """Inicializar cliente con configuración base"""
        self.base_url = "https://my317791.crm.ondemand.com"
        self.username = "USCP"
        self.password = "Inicio01"
        self.timeout = 30
        self.session = requests.Session()
        
        # Configurar autenticación básica
        credentials = f"{self.username}:{self.password}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        # Headers comunes para todas las requests SOAP
        self.headers = {
            'Content-Type': 'text/xml; charset=utf-8',
            'SOAPAction': '""',
            'Authorization': f'Basic {encoded_credentials}',
            'User-Agent': 'C4C-Test-Client/1.0'
        }
        
        logger.info(f"Cliente C4C inicializado para {self.base_url}")
    
    def make_soap_request(self, endpoint: str, soap_body: str) -> Tuple[bool, Dict[str, Any]]:
        """
        Hacer request SOAP genérico
        
        Args:
            endpoint: URL endpoint relativo
            soap_body: Cuerpo SOAP XML
            
        Returns:
            Tuple con (éxito, respuesta)
        """
        url = f"{self.base_url}{endpoint}"
        
        try:
            logger.info(f"Enviando request SOAP a: {url}")
            logger.debug(f"SOAP Body: {soap_body[:200]}...")
            
            response = self.session.post(
                url=url,
                data=soap_body,
                headers=self.headers,
                timeout=self.timeout,
                verify=True
            )
            
            result = {
                'url': url,
                'status_code': response.status_code,
                'headers': dict(response.headers),
                'response_text': response.text,
                'request_headers': self.headers,
                'request_body': soap_body,
                'timestamp': datetime.datetime.now().isoformat(),
                'success': response.status_code == 200
            }
            
            if response.status_code == 200:
                logger.info(f"Request exitoso - Status: {response.status_code}")
                return True, result
            else:
                logger.error(f"Request falló - Status: {response.status_code}")
                logger.error(f"Response: {response.text[:500]}")
                return False, result
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error en request: {str(e)}")
            return False, {
                'url': url,
                'error': str(e),
                'timestamp': datetime.datetime.now().isoformat(),
                'success': False
            }
    
    def test_connectivity(self) -> Dict[str, Any]:
        """
        Probar conectividad básica al servidor C4C
        
        Returns:
            Diccionario con resultados de conectividad
        """
        logger.info("Probando conectividad básica...")
        
        # Test básico con HEAD request
        try:
            response = self.session.head(
                self.base_url,
                headers={'Authorization': self.headers['Authorization']},
                timeout=10
            )
            
            connectivity_result = {
                'base_url': self.base_url,
                'accessible': True,
                'status_code': response.status_code,
                'headers': dict(response.headers),
                'timestamp': datetime.datetime.now().isoformat()
            }
            
            logger.info(f"Conectividad exitosa - Status: {response.status_code}")
            return connectivity_result
            
        except requests.exceptions.RequestException as e:
            logger.error(f"Error de conectividad: {str(e)}")
            return {
                'base_url': self.base_url,
                'accessible': False,
                'error': str(e),
                'timestamp': datetime.datetime.now().isoformat()
            }

    def query_customer_by_dni(self, dni: str) -> Tuple[bool, Dict[str, Any]]:
        """
        Consultar cliente por DNI
        
        Args:
            dni: Documento Nacional de Identidad
            
        Returns:
            Tuple con (éxito, respuesta)
        """
        logger.info(f"Consultando cliente por DNI: {dni}")
        
        endpoint = "/sap/bc/srt/scs/sap/querycustomerin1?sap-vhost=my317791.crm.ondemand.com"
        
        soap_body = f"""<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope 
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" 
    xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_">
    <soapenv:Header/>
    <soapenv:Body>
        <glob:CustomerByElementsQuery_sync>
            <CustomerSelectionByElements>
                <y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON>
                    <SelectionByText>
                        <InclusionExclusionCode>I</InclusionExclusionCode>
                        <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                        <LowerBoundaryName>{dni}</LowerBoundaryName>
                        <UpperBoundaryName/>
                    </SelectionByText>
                </y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON>
            </CustomerSelectionByElements>
            <ProcessingConditions>
                <QueryHitsMaximumNumberValue>20</QueryHitsMaximumNumberValue>
                <QueryHitsUnlimitedIndicator>false</QueryHitsUnlimitedIndicator>
            </ProcessingConditions>
        </glob:CustomerByElementsQuery_sync>
    </soapenv:Body>
</soapenv:Envelope>"""
        
        return self.make_soap_request(endpoint, soap_body)
    
    def query_customer_by_ruc(self, ruc: str) -> Tuple[bool, Dict[str, Any]]:
        """
        Consultar cliente por RUC
        
        Args:
            ruc: Registro Único de Contribuyentes
            
        Returns:
            Tuple con (éxito, respuesta)
        """
        logger.info(f"Consultando cliente por RUC: {ruc}")
        
        endpoint = "/sap/bc/srt/scs/sap/querycustomerin1?sap-vhost=my317791.crm.ondemand.com"
        
        soap_body = f"""<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope 
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" 
    xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_">
    <soapenv:Header/>
    <soapenv:Body>
        <glob:CustomerByElementsQuery_sync>
            <CustomerSelectionByElements>
                <y6s:zRuc_EA8AE8AUBVHCSXVYS0FJ1R3ON>
                    <SelectionByText>
                        <InclusionExclusionCode>I</InclusionExclusionCode>
                        <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                        <LowerBoundaryName>{ruc}</LowerBoundaryName>
                        <UpperBoundaryName/>
                    </SelectionByText>
                </y6s:zRuc_EA8AE8AUBVHCSXVYS0FJ1R3ON>
            </CustomerSelectionByElements>
            <ProcessingConditions>
                <QueryHitsMaximumNumberValue>20</QueryHitsMaximumNumberValue>
                <QueryHitsUnlimitedIndicator>false</QueryHitsUnlimitedIndicator>
            </ProcessingConditions>
        </glob:CustomerByElementsQuery_sync>
    </soapenv:Body>
</soapenv:Envelope>"""
        
        return self.make_soap_request(endpoint, soap_body)
    
    def create_appointment(self, appointment_data: Dict[str, Any]) -> Tuple[bool, Dict[str, Any]]:
        """
        Crear una nueva cita
        
        Args:
            appointment_data: Datos de la cita
            
        Returns:
            Tuple con (éxito, respuesta)
        """
        logger.info(f"Creando cita para cliente: {appointment_data.get('business_partner_id')}")
        
        endpoint = "/sap/bc/srt/scs/sap/manageappointmentactivityin1?sap-vhost=my317791.crm.ondemand.com"
        
        soap_body = f"""<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope 
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" 
    xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_" 
    xmlns:a25="http://sap.com/xi/AP/CustomerExtension/BYD/A252F">
    <soapenv:Header/>
    <soapenv:Body>
        <glob:AppointmentActivityBundleMaintainRequest_sync_V1>
            <AppointmentActivity actionCode="01">
                <DocumentTypeCode>0001</DocumentTypeCode>
                <LifeCycleStatusCode>1</LifeCycleStatusCode>
                <MainActivityParty>
                    <BusinessPartnerInternalID>{appointment_data.get('business_partner_id', '1270000347')}</BusinessPartnerInternalID>
                </MainActivityParty>
                <AttendeeParty>
                    <EmployeeID>{appointment_data.get('employee_id', '7000002')}</EmployeeID>
                </AttendeeParty>
                <StartDateTime timeZoneCode="UTC-5">{appointment_data.get('start_datetime', '2024-10-08T13:30:00Z')}</StartDateTime>
                <EndDateTime timeZoneCode="UTC-5">{appointment_data.get('end_datetime', '2024-10-08T13:44:00Z')}</EndDateTime>
                <Text actionCode="01">
                    <TextTypeCode>10002</TextTypeCode>
                    <ContentText>{appointment_data.get('observation', 'Cita de prueba automatizada')}</ContentText>
                </Text>
                <y6s:zClienteComodin>{appointment_data.get('client_name', 'Cliente de Prueba')}</y6s:zClienteComodin>
                <y6s:zFechaHoraProbSalida>{appointment_data.get('exit_date', '2024-10-08')}</y6s:zFechaHoraProbSalida>
                <y6s:zHoraProbSalida>{appointment_data.get('exit_time', '13:40:00')}</y6s:zHoraProbSalida>
                <y6s:zIDCentro>{appointment_data.get('center_id', 'M013')}</y6s:zIDCentro>
                <y6s:zPlaca>{appointment_data.get('license_plate', 'TEST-123')}</y6s:zPlaca>
                <y6s:zEstadoCita>{appointment_data.get('appointment_status', '1')}</y6s:zEstadoCita>
                <y6s:zVieneHCP>X</y6s:zVieneHCP>
                <y6s:zExpress>{appointment_data.get('is_express', 'false')}</y6s:zExpress>
            </AppointmentActivity>
        </glob:AppointmentActivityBundleMaintainRequest_sync_V1>
    </soapenv:Body>
</soapenv:Envelope>"""
        
        return self.make_soap_request(endpoint, soap_body)
    
    def query_pending_appointments(self, client_id: str) -> Tuple[bool, Dict[str, Any]]:
        """
        Consultar citas pendientes de un cliente
        
        Args:
            client_id: ID del cliente en C4C
            
        Returns:
            Tuple con (éxito, respuesta)
        """
        logger.info(f"Consultando citas pendientes para cliente: {client_id}")
        
        endpoint = "/sap/bc/srt/scs/sap/yy6saj0kgy_wscitas?sap-vhost=my317791.crm.ondemand.com"
        
        soap_body = f"""<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope 
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:glob="http://sap.com/xi/SAPGlobal20/Global">
    <soapenv:Header/>
    <soapenv:Body>
        <glob:ActivityBOVNCitasQueryByElementsSimpleByRequest_sync>
            <ActivitySimpleSelectionBy>
                <SelectionByTypeCode>
                    <InclusionExclusionCode>I</InclusionExclusionCode>
                    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                    <LowerBoundaryTypeCode>12</LowerBoundaryTypeCode>
                </SelectionByTypeCode>
                <SelectionByPartyID>
                    <InclusionExclusionCode>I</InclusionExclusionCode>
                    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                    <LowerBoundaryPartyID>{client_id}</LowerBoundaryPartyID>
                    <UpperBoundaryPartyID/>
                </SelectionByPartyID>
                <SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                    <InclusionExclusionCode>I</InclusionExclusionCode>
                    <IntervalBoundaryTypeCode>3</IntervalBoundaryTypeCode>
                    <LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>1</LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                    <UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>2</UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                </SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5>
            </ActivitySimpleSelectionBy>
            <ProcessingConditions>
                <QueryHitsMaximumNumberValue>10000</QueryHitsMaximumNumberValue>
                <QueryHitsUnlimitedIndicator/>
                <LastReturnedObjectID/>
            </ProcessingConditions>
        </glob:ActivityBOVNCitasQueryByElementsSimpleByRequest_sync>
    </soapenv:Body>
</soapenv:Envelope>"""
        
        return self.make_soap_request(endpoint, soap_body)
