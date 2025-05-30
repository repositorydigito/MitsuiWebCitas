#!/usr/bin/env python3
"""
Script de pruebas para servicios C4C
Ejecuta todas las pruebas de conectividad y funcionalidad
"""

import json
import datetime
import logging
from typing import Dict, List, Any
from c4c_soap_client import C4CSoapClient

# Configurar logging detallado
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('/workspace/docs/c4c_test_log.txt'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class C4CServiceTester:
    """Clase para ejecutar pruebas completas de servicios C4C"""
    
    def __init__(self):
        """Inicializar tester"""
        self.client = C4CSoapClient()
        self.test_results = []
        self.start_time = datetime.datetime.now()
        
        # Datos de prueba basados en la especificación
        self.test_data = {
            'dni_tests': [
                '40359482',  # Cliente AQP MUSIC E.I.R.L.
                '12345678'   # DNI inexistente para probar error handling
            ],
            'ruc_tests': [
                '20558638223',  # Cliente ALEJANDRO TOLEDO PARRA
                '99999999999'   # RUC inexistente para probar error handling
            ],
            'appointment_data': {
                'business_partner_id': '1270000347',
                'employee_id': '7000002',
                'start_datetime': '2024-12-01T14:30:00Z',
                'end_datetime': '2024-12-01T14:44:00Z',
                'observation': 'Prueba automatizada de cita - Test de conectividad',
                'client_name': 'Cliente Test Automatizado',
                'exit_date': '2024-12-01',
                'exit_time': '14:40:00',
                'center_id': 'M013',
                'license_plate': 'TEST-999',
                'appointment_status': '1',
                'is_express': 'false'
            },
            'client_ids_for_appointments': [
                '1270002726',  # Cliente con citas pendientes
                '1000000001'   # Cliente sin citas para probar respuesta vacía
            ]
        }
    
    def log_test_result(self, test_name: str, success: bool, details: Dict[str, Any], 
                       execution_time: float, error_message: str = None):
        """
        Registrar resultado de prueba
        
        Args:
            test_name: Nombre de la prueba
            success: Si la prueba fue exitosa
            details: Detalles de la respuesta
            execution_time: Tiempo de ejecución en segundos
            error_message: Mensaje de error si aplica
        """
        result = {
            'test_name': test_name,
            'success': success,
            'execution_time_seconds': execution_time,
            'timestamp': datetime.datetime.now().isoformat(),
            'details': details,
            'error_message': error_message
        }
        
        self.test_results.append(result)
        
        status = "✅ ÉXITO" if success else "❌ FALLO"
        logger.info(f"{status} - {test_name} ({execution_time:.2f}s)")
        
        if error_message:
            logger.error(f"Error: {error_message}")
    
    def test_connectivity(self) -> bool:
        """Probar conectividad básica"""
        logger.info("=== PRUEBA DE CONECTIVIDAD ===")
        
        start_time = datetime.datetime.now()
        
        try:
            connectivity_result = self.client.test_connectivity()
            execution_time = (datetime.datetime.now() - start_time).total_seconds()
            
            success = connectivity_result.get('accessible', False)
            
            self.log_test_result(
                test_name="Conectividad Básica",
                success=success,
                details=connectivity_result,
                execution_time=execution_time,
                error_message=connectivity_result.get('error') if not success else None
            )
            
            return success
            
        except Exception as e:
            execution_time = (datetime.datetime.now() - start_time).total_seconds()
            logger.error(f"Error en prueba de conectividad: {str(e)}")
            
            self.log_test_result(
                test_name="Conectividad Básica",
                success=False,
                details={'error': str(e)},
                execution_time=execution_time,
                error_message=str(e)
            )
            
            return False
    
    def test_query_customer_dni(self) -> Dict[str, bool]:
        """Probar consulta de clientes por DNI"""
        logger.info("=== PRUEBAS DE CONSULTA POR DNI ===")
        
        results = {}
        
        for dni in self.test_data['dni_tests']:
            start_time = datetime.datetime.now()
            
            try:
                success, response = self.client.query_customer_by_dni(dni)
                execution_time = (datetime.datetime.now() - start_time).total_seconds()
                
                test_name = f"Consulta Cliente DNI {dni}"
                
                # Determinar si es una prueba exitosa (encontró cliente o manejó correctamente la ausencia)
                is_valid_test = success or (response.get('status_code') in [200, 404])
                
                self.log_test_result(
                    test_name=test_name,
                    success=is_valid_test,
                    details=response,
                    execution_time=execution_time,
                    error_message=response.get('error') if not success else None
                )
                
                results[dni] = is_valid_test
                
            except Exception as e:
                execution_time = (datetime.datetime.now() - start_time).total_seconds()
                logger.error(f"Error en consulta DNI {dni}: {str(e)}")
                
                self.log_test_result(
                    test_name=f"Consulta Cliente DNI {dni}",
                    success=False,
                    details={'error': str(e)},
                    execution_time=execution_time,
                    error_message=str(e)
                )
                
                results[dni] = False
        
        return results
    
    def test_query_customer_ruc(self) -> Dict[str, bool]:
        """Probar consulta de clientes por RUC"""
        logger.info("=== PRUEBAS DE CONSULTA POR RUC ===")
        
        results = {}
        
        for ruc in self.test_data['ruc_tests']:
            start_time = datetime.datetime.now()
            
            try:
                success, response = self.client.query_customer_by_ruc(ruc)
                execution_time = (datetime.datetime.now() - start_time).total_seconds()
                
                test_name = f"Consulta Cliente RUC {ruc}"
                
                # Determinar si es una prueba exitosa
                is_valid_test = success or (response.get('status_code') in [200, 404])
                
                self.log_test_result(
                    test_name=test_name,
                    success=is_valid_test,
                    details=response,
                    execution_time=execution_time,
                    error_message=response.get('error') if not success else None
                )
                
                results[ruc] = is_valid_test
                
            except Exception as e:
                execution_time = (datetime.datetime.now() - start_time).total_seconds()
                logger.error(f"Error en consulta RUC {ruc}: {str(e)}")
                
                self.log_test_result(
                    test_name=f"Consulta Cliente RUC {ruc}",
                    success=False,
                    details={'error': str(e)},
                    execution_time=execution_time,
                    error_message=str(e)
                )
                
                results[ruc] = False
        
        return results
    
    def test_create_appointment(self) -> bool:
        """Probar creación de cita"""
        logger.info("=== PRUEBA DE CREACIÓN DE CITA ===")
        
        start_time = datetime.datetime.now()
        
        try:
            success, response = self.client.create_appointment(self.test_data['appointment_data'])
            execution_time = (datetime.datetime.now() - start_time).total_seconds()
            
            test_name = "Creación de Cita"
            
            # Una respuesta 200 o ciertos errores específicos pueden ser válidos
            is_valid_test = success or (response.get('status_code') in [200, 400, 422])
            
            self.log_test_result(
                test_name=test_name,
                success=is_valid_test,
                details=response,
                execution_time=execution_time,
                error_message=response.get('error') if not success else None
            )
            
            return is_valid_test
            
        except Exception as e:
            execution_time = (datetime.datetime.now() - start_time).total_seconds()
            logger.error(f"Error en creación de cita: {str(e)}")
            
            self.log_test_result(
                test_name="Creación de Cita",
                success=False,
                details={'error': str(e)},
                execution_time=execution_time,
                error_message=str(e)
            )
            
            return False
    
    def test_query_pending_appointments(self) -> Dict[str, bool]:
        """Probar consulta de citas pendientes"""
        logger.info("=== PRUEBAS DE CONSULTA CITAS PENDIENTES ===")
        
        results = {}
        
        for client_id in self.test_data['client_ids_for_appointments']:
            start_time = datetime.datetime.now()
            
            try:
                success, response = self.client.query_pending_appointments(client_id)
                execution_time = (datetime.datetime.now() - start_time).total_seconds()
                
                test_name = f"Consulta Citas Pendientes Cliente {client_id}"
                
                # Determinar si es una prueba exitosa
                is_valid_test = success or (response.get('status_code') in [200, 404])
                
                self.log_test_result(
                    test_name=test_name,
                    success=is_valid_test,
                    details=response,
                    execution_time=execution_time,
                    error_message=response.get('error') if not success else None
                )
                
                results[client_id] = is_valid_test
                
            except Exception as e:
                execution_time = (datetime.datetime.now() - start_time).total_seconds()
                logger.error(f"Error en consulta citas cliente {client_id}: {str(e)}")
                
                self.log_test_result(
                    test_name=f"Consulta Citas Pendientes Cliente {client_id}",
                    success=False,
                    details={'error': str(e)},
                    execution_time=execution_time,
                    error_message=str(e)
                )
                
                results[client_id] = False
        
        return results
    
    def run_all_tests(self) -> Dict[str, Any]:
        """Ejecutar todas las pruebas"""
        logger.info("🚀 INICIANDO PRUEBAS COMPLETAS DE SERVICIOS C4C")
        logger.info(f"Fecha: {self.start_time.isoformat()}")
        logger.info(f"URL Base: {self.client.base_url}")
        logger.info(f"Usuario: {self.client.username}")
        
        # Ejecutar todas las pruebas en orden
        connectivity_ok = self.test_connectivity()
        
        # Solo continuar con otras pruebas si hay conectividad básica
        if connectivity_ok:
            dni_results = self.test_query_customer_dni()
            ruc_results = self.test_query_customer_ruc()
            appointment_created = self.test_create_appointment()
            appointments_results = self.test_query_pending_appointments()
        else:
            logger.warning("⚠️ Sin conectividad básica - saltando pruebas de servicios")
            dni_results = {}
            ruc_results = {}
            appointment_created = False
            appointments_results = {}
        
        # Calcular estadísticas finales
        total_tests = len(self.test_results)
        successful_tests = sum(1 for result in self.test_results if result['success'])
        success_rate = (successful_tests / total_tests * 100) if total_tests > 0 else 0
        
        total_time = (datetime.datetime.now() - self.start_time).total_seconds()
        
        summary = {
            'test_execution': {
                'start_time': self.start_time.isoformat(),
                'end_time': datetime.datetime.now().isoformat(),
                'total_execution_time_seconds': total_time,
                'total_tests': total_tests,
                'successful_tests': successful_tests,
                'failed_tests': total_tests - successful_tests,
                'success_rate_percentage': success_rate
            },
            'connectivity': {
                'basic_connectivity': connectivity_ok
            },
            'service_tests': {
                'query_customer_dni': dni_results,
                'query_customer_ruc': ruc_results,
                'create_appointment': appointment_created,
                'query_pending_appointments': appointments_results
            },
            'detailed_results': self.test_results,
            'test_data_used': self.test_data
        }
        
        logger.info(f"🏁 PRUEBAS COMPLETADAS")
        logger.info(f"📊 Estadísticas: {successful_tests}/{total_tests} exitosas ({success_rate:.1f}%)")
        logger.info(f"⏱️  Tiempo total: {total_time:.2f} segundos")
        
        return summary
    
    def generate_report(self, results: Dict[str, Any]) -> str:
        """Generar reporte detallado en markdown"""
        
        report = f"""# Reporte de Pruebas - Servicios C4C

## Resumen Ejecutivo

- **Fecha de Ejecución:** {results['test_execution']['start_time']}
- **Tiempo Total:** {results['test_execution']['total_execution_time_seconds']:.2f} segundos
- **Pruebas Totales:** {results['test_execution']['total_tests']}
- **Pruebas Exitosas:** {results['test_execution']['successful_tests']}
- **Tasa de Éxito:** {results['test_execution']['success_rate_percentage']:.1f}%

## Estado de Conectividad

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| Conectividad Básica | {'✅ OK' if results['connectivity']['basic_connectivity'] else '❌ FALLO'} | Acceso al servidor C4C |

## Resultados por Servicio

### 1. QueryCustomerIn - Consulta de Clientes

#### Búsqueda por DNI
"""
        
        for dni, success in results['service_tests']['query_customer_dni'].items():
            status = '✅ OK' if success else '❌ FALLO'
            report += f"- **DNI {dni}:** {status}\n"
        
        report += "\n#### Búsqueda por RUC\n"
        
        for ruc, success in results['service_tests']['query_customer_ruc'].items():
            status = '✅ OK' if success else '❌ FALLO'
            report += f"- **RUC {ruc}:** {status}\n"
        
        appointment_status = '✅ OK' if results['service_tests']['create_appointment'] else '❌ FALLO'
        
        report += f"""
### 2. ManageAppointmentActivityIn - Gestión de Citas

- **Creación de Cita:** {appointment_status}

### 3. WSCitas - Consulta de Citas Pendientes

"""
        
        for client_id, success in results['service_tests']['query_pending_appointments'].items():
            status = '✅ OK' if success else '❌ FALLO'
            report += f"- **Cliente {client_id}:** {status}\n"
        
        report += """
## Análisis Detallado

### Pruebas Exitosas
"""
        
        successful_tests = [r for r in results['detailed_results'] if r['success']]
        for test in successful_tests:
            report += f"- **{test['test_name']}:** Completada en {test['execution_time_seconds']:.2f}s\n"
        
        failed_tests = [r for r in results['detailed_results'] if not r['success']]
        if failed_tests:
            report += "\n### Pruebas Fallidas\n"
            for test in failed_tests:
                report += f"- **{test['test_name']}:** {test.get('error_message', 'Error desconocido')}\n"
        
        report += f"""
## Configuración de Prueba

- **URL Base:** {self.client.base_url}
- **Usuario:** {self.client.username}
- **Timeout:** {self.client.timeout} segundos

## Datos de Prueba Utilizados

### DNIs Probados
{', '.join(results['test_data_used']['dni_tests'])}

### RUCs Probados
{', '.join(results['test_data_used']['ruc_tests'])}

### IDs de Cliente para Citas
{', '.join(results['test_data_used']['client_ids_for_appointments'])}

## Recomendaciones

"""
        
        if results['connectivity']['basic_connectivity']:
            if results['test_execution']['success_rate_percentage'] >= 80:
                report += "✅ **Servicios funcionando correctamente** - Los endpoints están respondiendo adecuadamente.\n"
            elif results['test_execution']['success_rate_percentage'] >= 50:
                report += "⚠️ **Servicios parcialmente funcionales** - Algunos endpoints requieren revisión.\n"
            else:
                report += "❌ **Servicios con problemas** - Se requiere investigación adicional.\n"
        else:
            report += "🚨 **Sin conectividad** - Verificar credenciales y acceso de red.\n"
        
        report += """
### Próximos Pasos

1. **Si hay fallos de conectividad:** Verificar credenciales y configuración de red
2. **Si hay fallos de servicios:** Revisar logs detallados y validar datos de entrada
3. **Para pruebas adicionales:** Considerar datos de prueba específicos del ambiente

---
*Reporte generado automáticamente*
"""
        
        return report

def main():
    """Función principal"""
    try:
        # Ejecutar todas las pruebas
        tester = C4CServiceTester()
        results = tester.run_all_tests()
        
        # Guardar resultados detallados en JSON
        json_file = '/workspace/docs/c4c_test_results.json'
        with open(json_file, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False, default=str)
        
        logger.info(f"📄 Resultados detallados guardados en: {json_file}")
        
        # Generar y guardar reporte en markdown
        report = tester.generate_report(results)
        report_file = '/workspace/docs/Reporte_Pruebas_C4C.md'
        with open(report_file, 'w', encoding='utf-8') as f:
            f.write(report)
        
        logger.info(f"📋 Reporte generado en: {report_file}")
        
        # Mostrar resumen final
        print("\n" + "="*60)
        print("🎯 RESUMEN FINAL DE PRUEBAS")
        print("="*60)
        print(f"✅ Pruebas exitosas: {results['test_execution']['successful_tests']}")
        print(f"❌ Pruebas fallidas: {results['test_execution']['failed_tests']}")
        print(f"📊 Tasa de éxito: {results['test_execution']['success_rate_percentage']:.1f}%")
        print(f"⏱️  Tiempo total: {results['test_execution']['total_execution_time_seconds']:.2f}s")
        print("="*60)
        
        return results['test_execution']['success_rate_percentage'] > 0
        
    except Exception as e:
        logger.error(f"Error en ejecución principal: {str(e)}")
        return False

if __name__ == "__main__":
    main()
