#!/usr/bin/env python3
"""
Ejemplos prácticos de uso de los servicios C4C
Casos de uso comunes para integración
"""

import datetime
import logging
from c4c_soap_client import C4CSoapClient

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class C4CExamples:
    """Ejemplos prácticos de uso de servicios C4C"""
    
    def __init__(self):
        self.client = C4CSoapClient()
    
    def example_customer_registration_flow(self, dni: str, ruc: str = None):
        """
        Ejemplo: Flujo de registro de cliente
        1. Buscar por DNI
        2. Si no existe, buscar por RUC
        3. Registrar resultado
        """
        logger.info(f"🔍 EJEMPLO: Flujo de registro para DNI {dni}")
        
        # Paso 1: Buscar por DNI
        success, response = self.client.query_customer_by_dni(dni)
        
        if success and 'Customer' in response['response_text']:
            logger.info("✅ Cliente encontrado por DNI")
            return {
                'found': True,
                'search_type': 'DNI',
                'document': dni,
                'client_data': self._extract_basic_client_info(response['response_text'])
            }
        
        # Paso 2: Si se proporciona RUC, buscar por RUC
        if ruc:
            logger.info(f"🔍 Cliente no encontrado por DNI, buscando por RUC {ruc}")
            success, response = self.client.query_customer_by_ruc(ruc)
            
            if success and 'Customer' in response['response_text']:
                logger.info("✅ Cliente encontrado por RUC")
                return {
                    'found': True,
                    'search_type': 'RUC',
                    'document': ruc,
                    'client_data': self._extract_basic_client_info(response['response_text'])
                }
        
        logger.info("❌ Cliente no encontrado en C4C")
        return {
            'found': False,
            'search_type': None,
            'document': dni,
            'client_data': None
        }
    
    def example_appointment_booking_flow(self, client_internal_id: str, 
                                       appointment_datetime: datetime.datetime,
                                       duration_minutes: int = 30):
        """
        Ejemplo: Flujo completo de reserva de cita
        1. Verificar citas pendientes del cliente
        2. Crear nueva cita
        3. Verificar que la cita fue creada
        """
        logger.info(f"📅 EJEMPLO: Reserva de cita para cliente {client_internal_id}")
        
        # Paso 1: Verificar citas pendientes
        logger.info("🔍 Verificando citas pendientes existentes...")
        success, response = self.client.query_pending_appointments(client_internal_id)
        
        pending_appointments = 0
        if success and 'Activity' in response['response_text']:
            # Contar citas pendientes (simplificado)
            pending_appointments = response['response_text'].count('<Activity>')
        
        logger.info(f"📊 Cliente tiene {pending_appointments} cita(s) pendiente(s)")
        
        # Paso 2: Crear nueva cita
        end_datetime = appointment_datetime + datetime.timedelta(minutes=duration_minutes)
        
        appointment_data = {
            'business_partner_id': client_internal_id,
            'employee_id': '7000002',  # Empleado por defecto
            'start_datetime': appointment_datetime.strftime('%Y-%m-%dT%H:%M:%SZ'),
            'end_datetime': end_datetime.strftime('%Y-%m-%dT%H:%M:%SZ'),
            'observation': f'Cita reservada via sistema web - {datetime.datetime.now().strftime("%Y-%m-%d %H:%M")}',
            'client_name': 'Cliente Sistema Web',
            'exit_date': appointment_datetime.strftime('%Y-%m-%d'),
            'exit_time': end_datetime.strftime('%H:%M:%S'),
            'center_id': 'M013',
            'license_plate': 'WEB-001',
            'appointment_status': '1',  # Generada
            'is_express': 'false'
        }
        
        logger.info(f"📝 Creando cita para {appointment_datetime.strftime('%Y-%m-%d %H:%M')}")
        success, response = self.client.create_appointment(appointment_data)
        
        if success:
            logger.info("✅ Cita creada exitosamente")
            
            # Paso 3: Verificar creación (consultar nuevamente)
            logger.info("🔍 Verificando que la cita fue creada...")
            success, verify_response = self.client.query_pending_appointments(client_internal_id)
            
            new_pending_count = 0
            if success and 'Activity' in verify_response['response_text']:
                new_pending_count = verify_response['response_text'].count('<Activity>')
            
            return {
                'appointment_created': True,
                'previous_appointments': pending_appointments,
                'current_appointments': new_pending_count,
                'appointment_data': appointment_data,
                'creation_response': response
            }
        else:
            logger.error("❌ Error al crear la cita")
            return {
                'appointment_created': False,
                'error': response.get('error', 'Unknown error'),
                'appointment_data': appointment_data
            }
    
    def example_customer_search_with_fallback(self, documents: list):
        """
        Ejemplo: Búsqueda de cliente con múltiples documentos
        Útil cuando se tienen varios documentos posibles
        """
        logger.info(f"🔍 EJEMPLO: Búsqueda con fallback para documentos {documents}")
        
        for i, doc in enumerate(documents):
            # Determinar si es DNI o RUC (simplificado por longitud)
            if len(doc) == 8:
                search_type = 'DNI'
                success, response = self.client.query_customer_by_dni(doc)
            elif len(doc) == 11:
                search_type = 'RUC'
                success, response = self.client.query_customer_by_ruc(doc)
            else:
                logger.warning(f"⚠️ Documento {doc} no tiene formato reconocido")
                continue
            
            if success and 'Customer' in response['response_text']:
                logger.info(f"✅ Cliente encontrado con {search_type}: {doc}")
                return {
                    'found': True,
                    'search_type': search_type,
                    'document_used': doc,
                    'attempt_number': i + 1,
                    'total_attempts': len(documents),
                    'client_data': self._extract_basic_client_info(response['response_text'])
                }
            else:
                logger.info(f"❌ No encontrado con {search_type}: {doc}")
        
        logger.info("❌ Cliente no encontrado con ningún documento")
        return {
            'found': False,
            'documents_tried': documents,
            'total_attempts': len(documents)
        }
    
    def example_bulk_appointment_check(self, client_ids: list):
        """
        Ejemplo: Verificación masiva de citas pendientes
        Útil para dashboards o reportes
        """
        logger.info(f"📊 EJEMPLO: Verificación masiva para {len(client_ids)} clientes")
        
        results = []
        
        for client_id in client_ids:
            logger.info(f"🔍 Verificando cliente {client_id}")
            
            success, response = self.client.query_pending_appointments(client_id)
            
            if success:
                # Contar citas (método simplificado)
                appointment_count = response['response_text'].count('<Activity>')
                
                results.append({
                    'client_id': client_id,
                    'success': True,
                    'pending_appointments': appointment_count,
                    'has_appointments': appointment_count > 0
                })
                
                logger.info(f"✅ Cliente {client_id}: {appointment_count} cita(s)")
            else:
                results.append({
                    'client_id': client_id,
                    'success': False,
                    'error': response.get('error', 'Unknown error')
                })
                
                logger.error(f"❌ Error consultando cliente {client_id}")
        
        # Estadísticas finales
        successful_checks = sum(1 for r in results if r['success'])
        total_appointments = sum(r.get('pending_appointments', 0) for r in results if r['success'])
        clients_with_appointments = sum(1 for r in results if r.get('has_appointments', False))
        
        summary = {
            'total_clients_checked': len(client_ids),
            'successful_checks': successful_checks,
            'failed_checks': len(client_ids) - successful_checks,
            'total_pending_appointments': total_appointments,
            'clients_with_appointments': clients_with_appointments,
            'clients_without_appointments': successful_checks - clients_with_appointments,
            'detailed_results': results
        }
        
        logger.info(f"📊 Resumen: {successful_checks}/{len(client_ids)} exitosos, {total_appointments} citas total")
        
        return summary
    
    def _extract_basic_client_info(self, soap_response: str) -> dict:
        """Extraer información básica del cliente de la respuesta SOAP"""
        try:
            # Extractores simples con regex para demo
            import re
            
            internal_id_match = re.search(r'<InternalID>(.*?)</InternalID>', soap_response)
            external_id_match = re.search(r'<ExternalID>(.*?)</ExternalID>', soap_response)
            name_match = re.search(r'<FirstLineName>(.*?)</FirstLineName>', soap_response)
            
            return {
                'internal_id': internal_id_match.group(1) if internal_id_match else None,
                'external_id': external_id_match.group(1) if external_id_match else None,
                'name': name_match.group(1) if name_match else None
            }
        except Exception as e:
            logger.error(f"Error extrayendo info del cliente: {str(e)}")
            return {}

def main():
    """Ejecutar ejemplos prácticos"""
    logger.info("🚀 EJECUTANDO EJEMPLOS PRÁCTICOS DE USO")
    
    examples = C4CExamples()
    
    # Ejemplo 1: Flujo de registro
    print("\n" + "="*60)
    print("📝 EJEMPLO 1: FLUJO DE REGISTRO DE CLIENTE")
    print("="*60)
    
    result1 = examples.example_customer_registration_flow('40359482', '20558638223')
    print(f"Resultado: {result1}")
    
    # Ejemplo 2: Reserva de cita
    print("\n" + "="*60)
    print("📅 EJEMPLO 2: FLUJO DE RESERVA DE CITA")
    print("="*60)
    
    # Cita para mañana a las 3 PM
    tomorrow_3pm = datetime.datetime.now() + datetime.timedelta(days=1)
    tomorrow_3pm = tomorrow_3pm.replace(hour=15, minute=0, second=0, microsecond=0)
    
    result2 = examples.example_appointment_booking_flow('1270000347', tomorrow_3pm, 45)
    print(f"Resultado: {result2}")
    
    # Ejemplo 3: Búsqueda con fallback
    print("\n" + "="*60)
    print("🔍 EJEMPLO 3: BÚSQUEDA CON FALLBACK")
    print("="*60)
    
    result3 = examples.example_customer_search_with_fallback(['12345678', '40359482', '99999999999'])
    print(f"Resultado: {result3}")
    
    # Ejemplo 4: Verificación masiva
    print("\n" + "="*60)
    print("📊 EJEMPLO 4: VERIFICACIÓN MASIVA DE CITAS")
    print("="*60)
    
    result4 = examples.example_bulk_appointment_check(['1270002726', '1000000001', '1270000347'])
    print(f"Resultado resumen: {result4}")
    
    logger.info("🏁 EJEMPLOS COMPLETADOS")

if __name__ == "__main__":
    main()
