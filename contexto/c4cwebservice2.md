# Consumo del Webservice de Lista de Productos de C4C

## 0. Contexto del Sistema de Citas y Servicios

### Resumen General
El video explica el funcionamiento de un sistema para agendar citas de servicio automotriz. En resumen, cada cita de servicio incluye un conjunto de tareas básicas predeterminadas. El cliente puede añadir servicios adicionales (como un servicio "Express" o de "Campaña") al momento de agendar. Técnicamente, esto funciona sumando los códigos de los servicios adicionales a la lista de tareas básicas.

### Desglose del Proceso

#### Paquetes de Servicios Básicos:
Cuando un cliente agenda una cita, ya existe un paquete de aproximadamente 15 actividades de mantenimiento que se realizan por defecto en cada servicio. Estas son las tareas estándar que se hacen sí o sí.

#### Añadir Servicios Adicionales:
Durante el agendamiento en la plataforma web, el cliente tiene la opción de seleccionar servicios extra, como:
- **Mantenimiento periódico**: Puede elegir entre regular o express.
- **Campañas / otros**: Puede seleccionar campañas de servicio especiales que estén activas.

#### ¿Cómo funciona por dentro?
Cada servicio, tanto los básicos como los adicionales, tiene un "Campo clave" (un código único) y una "Denominación" (su descripción). Por ejemplo:
- **XE15**: "SERVICIO EXPRESS 1.5 HORAS"
- **A047**: "CAMB. ACEITE Y FILTRO"

Cuando el cliente elige un servicio adicional (como el "Express"), el código de ese servicio se suma a la lista de las 15 tareas básicas. De esta forma, el taller sabe que debe realizar tanto las tareas estándar como las que el cliente añadió.

#### La Información Técnica (API):
Para obtener la lista completa de todos los servicios posibles con sus códigos y descripciones, se utiliza una herramienta técnica llamada API.
Sin embargo, esta API solo proporciona la información básica: el código del servicio y su descripción.
Toda esta lista de servicios (código y descripción) fue exportada a un archivo de Excel (CSV) para poder consultarla fácilmente.

## 1. Información del Servicio

- **Tipo de servicio**: OData v1
- **Endpoint base**: `https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/dw_listaproducto/BOListaProductosRootCollection`
- **Método HTTP**: GET
- **Autenticación**: Basic Authentication (usuario y contraseña)

## 2. Credenciales de Acceso

```
Usuario: _ODATA
Contraseña: /sap/ap/ui/cloginA!"2
```

## 3. Headers Requeridos

```
Accept: application/json
Authorization: Basic [credenciales codificadas en Base64]
```

## 4. Proceso de Consumo Paso a Paso

### Paso 1: Primera petición

Realiza una petición GET al endpoint base sin parámetros adicionales:

```
GET https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/dw_listaproducto/BOListaProductosRootCollection
```

**Importante**: Este servicio tiene una limitación - solo retorna los primeros 1000 registros en cada petición.

### Paso 2: Paginación

El servicio implementa paginación mediante un token. En la respuesta de cada petición, al final encontrarás una URL con un `skiptoken` para obtener los siguientes registros:

```
https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/dw_listaproducto/BOListaProductosRootCollection?$skiptoken=1001
```

### Paso 3: Proceso completo de extracción

Para obtener todos los registros (en tu caso son 4,639 productos según el CSV), debes:

1. Hacer la primera petición sin skiptoken
2. Guardar los primeros 1000 registros
3. Buscar en la respuesta la URL con el skiptoken
4. Hacer una nueva petición con esa URL
5. Repetir hasta que no haya más skiptoken en la respuesta

## 5. Estructura de la Respuesta

La respuesta contendrá un diccionario con los productos, donde cada producto tiene al menos:
- **ID**: Identificador del producto
- **Descripción**: Descripción del producto

## 6. Contexto de Uso

Según tu documentación, este servicio se utiliza para obtener el diccionario de `zOVPaqueteID`, que es necesario para que la regla de trabajo "Creación Automática de Ofertas desde Cita" funcione correctamente. La condición que no se cumple es "Paquete ID diferente a vacío".

## 7. Ejemplo de Implementación (Pseudocódigo)

```python
función obtenerTodosLosProductos():
    url = "https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/dw_listaproducto/BOListaProductosRootCollection"
    todos_los_productos = []
    
    mientras url no sea vacía:
        respuesta = hacer_peticion_GET(url, usuario="_ODATA", password="/sap/ap/ui/cloginA!"2")
        productos = extraer_productos_de_respuesta(respuesta)
        todos_los_productos.agregar(productos)
        url = extraer_siguiente_url_con_skiptoken(respuesta)
    
    retornar todos_los_productos
```

## 8. Puntos Críticos a Considerar

1. **Autenticación**: Asegúrate de codificar correctamente las credenciales en Base64
2. **Paginación**: No asumas que siempre serán exactamente 1000 registros por página
3. **Manejo de errores**: Implementa reintentos en caso de fallas de red
4. **Límites de rate**: Verifica si hay límites en la cantidad de peticiones por segundo

## 9. Ejemplo de Request con Postman

Según el archivo Postman proporcionado:

```json
{
    "method": "GET",
    "url": "https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/dw_listaproducto/BOListaProductosRootCollection",
    "auth": {
        "type": "basic",
        "username": "_ODATA",
        "password": "/sap/ap/ui/cloginA!\"2"
    },
    "headers": {
        "Accept": "application/json"
    }
}
```

## 10. Notas Adicionales

- El servicio está configurado en el sistema SAP C4C de Mitsui
- El módulo asociado es C4Sales
- Este cambio está relacionado con el ticket RITM0105502 para una mejora en el envío de trama de cierre