# SAP C4C WebService API - Lista de Productos Vinculados

## Descripción General
Este webservice permite consultar la lista de productos vinculados desde SAP Cloud for Customer (C4C) utilizando el protocolo OData v1. Proporciona información detallada sobre productos relacionados con un producto padre específico.

## Información del Endpoint

### URL Base
```
https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/obtenerlistadoproductos/
```

### Endpoint Completo
```
https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/obtenerlistadoproductos/BOListaProductosProductosVinculadosCollection
```

## Parámetros de Consulta

### Filtros Disponibles ($filter)
- **zEstado**: Estado del producto (ej: '02' = Activo)
- **zIDPadre**: ID del producto padre (ej: 'M2275-010')
- **zTipoPosicion**: Tipo de posición del producto
- **zIDProductoVinculado**: ID específico del producto vinculado

### Ejemplo de Consulta
```
GET /BOListaProductosProductosVinculadosCollection?$filter=zEstado eq '02' and zIDPadre eq 'M2275-010'
```

## Estructura de la Respuesta

### Formato de Respuesta
La respuesta viene en formato JSON con la siguiente estructura:

```json
{
    "d": {
        "results": [
            {
                "__metadata": {
                    "uri": "string",
                    "type": "cust.BOListaProductosProductosVinculados"
                },
                // Campos de datos aquí
            }
        ]
    }
}
```

## Campos de Datos

### Campos de Identificación
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `ObjectID` | String | Identificador único del objeto |
| `ParentObjectID` | String | ID del objeto padre |
| `HeaderObjectID` | String | ID del objeto cabecera |
| `RecordNumber` | String | Número de registro |
| `ExternalKey` | String | Clave externa |

### Campos de Producto
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `zIDPadre` | String | ID del producto padre (ej: "M2275-010") |
| `zIDProductoVinculado` | String | ID del producto vinculado |
| `zDescripcionProductoVinculado` | String | Descripción del producto vinculado |
| `zIDPadreProductoVinc` | String | ID completo del producto padre vinculado |
| `zIDModeloPrdVinc` | String | ID del modelo del producto vinculado |
| `zMatnr` | String | Número de material |

### Campos de Cantidad y Medición
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `zMENGE` | Decimal | Cantidad base (formato: 0.00000000000000) |
| `zCantidad` | Decimal | Cantidad del producto |
| `zZMENG` | Decimal | Cantidad alternativa |
| `zTiempoValorTrabajo` | Decimal | Tiempo/valor de trabajo |
| `unitCode` | String | Código de unidad de medida |
| `unitCode1` | String | Código de unidad alternativo 1 |
| `unitCode2` | String | Código de unidad alternativo 2 |

### Campos de Control
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `zPOSNR` | String | Número de posición (ej: "000010", "000020") |
| `zLBRCAT` | String | Categoría de labor/trabajo |
| `zTipoPosicion` | String | Tipo de posición del item |
| `zEstado` | String | Estado del producto |

## Códigos de Estado (zEstado)
- `02`: Activo/Válido

## Tipos de Posición (zTipoPosicion)
- `P001`: Servicio
- `P002`: Material/Parte
- `P009`: Componente
- `P010`: Material específico

## Ejemplos de Uso

### Obtener todos los productos vinculados activos
```http
GET /BOListaProductosProductosVinculadosCollection?$filter=zEstado eq '02'
```

### Obtener productos vinculados por producto padre específico
```http
GET /BOListaProductosProductosVinculadosCollection?$filter=zIDPadre eq 'M2275-010'
```

### Filtro combinado (productos activos de un padre específico)
```http
GET /BOListaProductosProductosVinculadosCollection?$filter=zEstado eq '02' and zIDPadre eq 'M2275-010'
```

### Filtrar por tipo de posición
```http
GET /BOListaProductosProductosVinculadosCollection?$filter=zTipoPosicion eq 'P001'
```

## Ejemplo de Respuesta Completa

```json
{
    "d": {
        "results": [
            {
                "__metadata": {
                    "uri": "https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/obtenerlistadoproductos/BOListaProductosProductosVinculadosCollection('F9BCF6C4B9D11FD090E2585CFCD33D8D')",
                    "type": "cust.BOListaProductosProductosVinculados"
                },
                "ObjectID": "F9BCF6C4B9D11FD090E2585CFCD33D8D",
                "zIDPadre": "M2275-010",
                "zIDProductoVinculado": "Z01_SRV_E_P010",
                "zDescripcionProductoVinculado": "SERVICIO 10,000 KM",
                "zTipoPosicion": "P001",
                "zEstado": "02",
                "zCantidad": "1.00000000000000",
                "zTiempoValorTrabajo": "1.30000000000000"
            }
        ]
    }
}
```

## Notas Importantes

1. **Autenticación**: Este webservice requiere autenticación válida en SAP C4C
2. **Formato de Números**: Los campos decimales utilizan formato extendido con 14 decimales
3. **Filtros OData**: Soporta operadores estándar OData como `eq`, `ne`, `gt`, `lt`, `and`, `or`
4. **Paginación**: Para grandes volúmenes de datos, considerar usar `$top` y `$skip`
5. **Campos Vacíos**: Algunos campos pueden estar vacíos dependiendo del tipo de producto

## Headers HTTP Recomendados

```http
Accept: application/json
Content-Type: application/json
DataServiceVersion: 1.0
MaxDataServiceVersion: 3.0
```

## Manejo de Errores

El servicio retorna códigos de estado HTTP estándar:
- `200 OK`: Consulta exitosa
- `400 Bad Request`: Error en los parámetros de consulta
- `401 Unauthorized`: Error de autenticación
- `404 Not Found`: Recurso no encontrado
- `500 Internal Server Error`: Error interno del servidor