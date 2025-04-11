# Variables de Entorno para WebService de Vehículos

Para configurar el sistema de fallback del WebService SOAP, se deben agregar las siguientes variables al archivo `.env` de la aplicación:

```
# Habilitar/deshabilitar WebService (true/false)
VEHICULOS_WEBSERVICE_ENABLED=true

# Tiempo de espera en segundos para las peticiones SOAP
VEHICULOS_WEBSERVICE_TIMEOUT=5

# Número de intentos fallidos permitidos antes de activar modo de prueba
VEHICULOS_WEBSERVICE_RETRY_ATTEMPTS=2

# Intervalo de tiempo en segundos para verificar el estado del servicio
VEHICULOS_WEBSERVICE_HEALTH_CHECK_INTERVAL=300
```

## Uso y Configuración

- **VEHICULOS_WEBSERVICE_ENABLED**: Si se establece en `false`, siempre usará los datos de prueba configurados.
- **VEHICULOS_WEBSERVICE_TIMEOUT**: Tiempo máximo en segundos para esperar la respuesta del servicio.
- **VEHICULOS_WEBSERVICE_RETRY_ATTEMPTS**: Después de este número de intentos fallidos, se activará el "circuit breaker" para evitar sobrecarga del servicio.
- **VEHICULOS_WEBSERVICE_HEALTH_CHECK_INTERVAL**: Tiempo en segundos que se almacena en caché el estado del servicio.

El sistema verificará automáticamente la disponibilidad del servicio y fallará a datos de prueba cuando sea necesario.
