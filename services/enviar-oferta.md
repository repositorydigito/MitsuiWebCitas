## 🌐 **Configuración del Endpoint SAP C4C**

### **📡 Definición del Servicio**

**Nombre del Servicio:** `customerquoteprocessingmanagec`  
**Operación:** `CustomerQuoteBundleMaintainRequest_sync_V1`  
**Propósito:** Crear cotizaciones/ofertas en SAP C4C para servicios de mantenimiento automotriz

### **🔗 Datos de Conexión**

| Parámetro | Valor | Descripción |
|-----------|-------|-------------|
| **URL Endpoint** | `https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/customerquoteprocessingmanagec` | URL completa del servicio |
| **Usuario** | `_USER_INT` | Usuario de integración para crear ofertas |
| **Contraseña** | `/sap/ap/ui/cloginA!"2` | Contraseña del usuario de servicio |
| **Dominio SAP** | `my317791.crm.ondemand.com` | Instancia SAP C4C de Mitsui |

### **⚙️ Parámetros de Configuración**

| Parámetro | Valor | Descripción |
|-----------|-------|-------------|
| **Protocolo** | SOAP 1.1 | Protocolo de comunicación |
| **Método HTTP** | POST | Envío de datos |
| **Content-Type** | `text/xml; charset=utf-8` | Formato del contenido |
| **SOAPAction** | `http://sap.com/xi/SAPGlobal20/Global/CustomerQuoteBundleMaintainRequest_sync_V1` | Acción SOAP específica |
| **Timeout** | 30 segundos | Tiempo límite de respuesta |
| **Autenticación** | Basic Auth | Usuario y contraseña SAP |

### **📤 Datos Enviados**

**Formato:** XML SOAP Envelope  
**Codificación:** UTF-8  
**Tamaño aproximado:** 2-5 KB dependiendo del número de productos  

**Contenido principal:**
- Datos del cliente (ID interno de SAP)
- Información del vehículo (placa, kilometraje, marca)
- Configuración organizacional (centro, grupo de ventas, canal)
- Lista completa de productos/servicios del paquete
- Referencia a la cita original (UUID)
- Comentarios y observaciones

### **📡 Método de Envío**

**Transporte:** HTTPS  
**Autenticación:** Basic Authentication con credenciales de usuario de servicio SAP  
**Headers requeridos:**
- Content-Type: text/xml; charset=utf-8
- SOAPAction: [URL de la operación]
- Authorization: Basic [credenciales codificadas]

### **📥 Respuesta del Servicio**

#### **✅ Respuesta Exitosa**
**Código HTTP:** 200 OK  
**Formato:** XML SOAP Response  
**Contenido:**
- **ID de Cotización:** Número único asignado por SAP (ej: 20939)
- **UUID:** Identificador único de la cotización en SAP
- **Timestamps:** Fecha/hora de creación y última modificación
- **Log:** Vacío (sin errores)

#### **❌ Respuesta con Error**
**Código HTTP:** 200 OK (pero con errores en el contenido)  
**Formato:** XML SOAP Response con log de errores  
**Contenido:**
- **Log de Errores:** Lista de errores encontrados
- **Códigos de Error:** Identificadores específicos del error
- **Mensajes:** Descripción detallada del problema
- **Severidad:** Nivel de criticidad del error

#### **🚫 Respuesta de Falla de Conexión**
**Códigos HTTP posibles:**
- **401:** Credenciales incorrectas
- **404:** Endpoint no encontrado  
- **500:** Error interno del servidor SAP
- **503:** Servicio no disponible
- **Timeout:** Sin respuesta en el tiempo límite

### **📊 Variables de Entorno Requeridas**

| Variable | Valor | Descripción |
|----------|-------|-------------|
| `SAP_C4C_ENDPOINT` | `https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/customerquoteprocessingmanagec` | URL completa del servicio |
| `SAP_C4C_USERNAME` | `_USER_INT` | Usuario de servicio técnico |
| `SAP_C4C_PASSWORD` | `/sap/ap/ui/cloginA!"2` | Contraseña del usuario |
| `SAP_C4C_TIMEOUT` | `30` | Tiempo límite en segundos |

## 🎯 **Propósito del Método**

Este servicio crea **cotizaciones/ofertas** en SAP C4C para servicios de mantenimiento automotriz de Mitsui. Se ejecuta después de que se crea una cita (`appointment`) y antes de confirmarla.

## 🔄 **Flujo del Proceso**

1. **Cita creada** → `appointments` tabla
2. **Productos asignados** → `products` tabla (por `package_id`)
3. **Generar oferta** → Llamada a SAP C4C
4. **Guardar respuesta** → `appointments.c4c_offer_id`, `offer_created_at`

---

## 📊 **Consultas SQL para Obtener Datos**

### 🔍 **Consulta Principal**
```sql
SELECT 
    a.id,
    a.c4c_uuid,
    a.package_id,
    a.center_code,
    a.vehicle_plate,
    a.service_mode,
    a.comments,
    v.license_plate,
    v.mileage,
    v.brand_code,
    u.c4c_internal_id,
    com.sales_organization_id,
    com.sales_office_id,
    com.sales_group_id,
    com.distribution_channel_code,
    com.division_code
FROM appointments a
JOIN vehicles v ON a.vehicle_id = v.id
JOIN users u ON v.user_id = u.id
JOIN center_organization_mapping com ON com.center_code = a.center_code 
    AND com.brand_code = v.brand_code
WHERE a.id = ?
```

### 🛠️ **Consulta de Productos**
```sql
SELECT 
    c4c_product_id,
    quantity,
    unit_code,
    position_type,
    work_time_value
FROM products 
WHERE appointment_id = ?
ORDER BY position_number
```

---

## 🏗️ **Estructura del Payload SOAP**

### **📋 Encabezado Estático**
```xml
<ProcessingTypeCode>Z300</ProcessingTypeCode>
<Name languageCode="ES">OFERTA</Name>
<DocumentLanguageCode>ES</DocumentLanguageCode>
```

### **👤 Datos del Cliente**
```xml
<BuyerParty>
    <BusinessPartnerInternalID>{users.c4c_internal_id}</BusinessPartnerInternalID>
</BuyerParty>
```

### **🏢 Organización de Ventas**
> **⚠️ IMPORTANTE:** Estos datos se obtienen de `center_organization_mapping` consultando por `center_code` + `brand_code`

```xml
<SalesAndServiceBusinessArea>
    <SalesOrganisationID>{com.sales_organization_id}</SalesOrganisationID>
    <SalesOfficeID>{com.sales_office_id}</SalesOfficeID>
    <SalesGroupID>{com.sales_group_id}</SalesGroupID>
    <DistributionChannelCode>{com.distribution_channel_code}</DistributionChannelCode>
    <DivisionCode>{com.division_code}</DivisionCode>
</SalesAndServiceBusinessArea>
```

### **🔧 Productos/Servicios**
> **⚠️ IMPORTANTE:** Se debe iterar **TODOS** los productos del `appointment_id`. Si hay 30 productos, generar 30 elementos `<Item>`

```xml
<!-- POR CADA PRODUCTO EN products WHERE appointment_id = ? -->
<Item actionCode="01">
    <ProcessingTypeCode>AGN</ProcessingTypeCode>
    <ItemProduct>
        <ProductID>{products.c4c_product_id}</ProductID>
        <ProductInternalID>{products.c4c_product_id}</ProductInternalID>
    </ItemProduct>
    <ItemRequestedScheduleLine>
        <Quantity unitCode="{products.unit_code}">{products.quantity}</Quantity>
    </ItemRequestedScheduleLine>
    <ns2:zOVPosIDTipoPosicion>{products.position_type}</ns2:zOVPosIDTipoPosicion>
    <ns2:zOVPosTipServ>P</ns2:zOVPosTipServ>
    <ns2:zOVPosCantTrab>0</ns2:zOVPosCantTrab>
    <ns2:zID_PAQUETE>{appointments.package_id}</ns2:zID_PAQUETE>
    <ns2:zTIPO_PAQUETE>z1</ns2:zTIPO_PAQUETE>
    <ns2:zOVPosTiempoTeorico>{products.work_time_value}</ns2:zOVPosTiempoTeorico>
</Item>
```

### **📝 Referencia y Datos del Vehículo**
```xml
<BusinessTransactionDocumentReference actionCode="01">
    <UUID>{appointments.c4c_uuid}</UUID>
    <TypeCode>12</TypeCode>
    <RoleCode>1</RoleCode>
</BusinessTransactionDocumentReference>

<Text actionCode="01">
    <TextTypeCode>10024</TextTypeCode>
    <ContentText>{appointments.comments}</ContentText>
</Text>

<ns2:zOVIDCentro>{appointments.center_code}</ns2:zOVIDCentro>
<ns2:zOVPlaca>{vehicles.license_plate}</ns2:zOVPlaca>
<ns2:zOVServExpress>{appointments.service_mode == 'express' ? 'true' : 'false'}</ns2:zOVServExpress>
<ns2:zOVKilometraje>{vehicles.mileage}</ns2:zOVKilometraje>
<ns2:zOVVieneDeHCI>X</ns2:zOVVieneDeHCI>
```

---

## 📄 **Payload Completo de Ejemplo**

```xml
<?xml version='1.0' encoding='utf-8'?>
<ns0:Envelope xmlns:ns0="http://schemas.xmlsoap.org/soap/envelope/" 
               xmlns:ns1="http://sap.com/xi/SAPGlobal20/Global" 
               xmlns:ns2="http://0002961282-one-off.sap.com/Y6SAJ0KGY_">
    <ns0:Header />
    <ns0:Body>
        <ns1:CustomerQuoteBundleMaintainRequest_sync_V1>
            <CustomerQuote ViewObjectIndicator="" 
                          actionCode="01" 
                          approverPartyListCompleteTransmissionIndicator="" 
                          businessTransactionDocumentReferenceListCompleteTransmissionIndicator="" 
                          competitorPartyListCompleteTransmissionIndicator="" 
                          itemListCompleteTransmissionIndicator="" 
                          otherPartyListCompleteTransmissionIndicator="" 
                          salesEmployeePartyListCompleteTransmissionIndicator="" 
                          salesPartnerListCompleteTransmissionIndicator="" 
                          textListCompleteTransimissionIndicator="">
                
                <ProcessingTypeCode>Z300</ProcessingTypeCode>
                <BuyerID schemeAgencyID="" schemeAgencySchemeAgencyID="" schemeID="" />
                <Name languageCode="ES">OFERTA</Name>
                <DocumentLanguageCode>ES</DocumentLanguageCode>
                
                <BuyerParty contactPartyListCompleteTransmissionIndicator="">
                    <BusinessPartnerInternalID>1200191766</BusinessPartnerInternalID>
                </BuyerParty>
                
                <EmployeeResponsibleParty>
                    <EmployeeID>8000000010</EmployeeID>
                </EmployeeResponsibleParty>
                
                <SellerParty>
                    <OrganisationalCentreID>GMIT</OrganisationalCentreID>
                </SellerParty>
                
                <SalesUnitParty>
                    <OrganisationalCentreID>DM08</OrganisationalCentreID>
                </SalesUnitParty>
                
                <SalesAndServiceBusinessArea>
                    <SalesOrganisationID>DM08</SalesOrganisationID>
                    <SalesOfficeID>OVDL01</SalesOfficeID>
                    <SalesGroupID>D03</SalesGroupID>
                    <DistributionChannelCode>D4</DistributionChannelCode>
                    <DivisionCode>D2</DivisionCode>
                </SalesAndServiceBusinessArea>
                
                <!-- Repetir por cada producto -->
                <Item actionCode="01">
                    <ProcessingTypeCode>AGN</ProcessingTypeCode>
                    <ItemProduct>
                        <ProductID>P010</ProductID>
                        <ProductInternalID>P010</ProductInternalID>
                    </ItemProduct>
                    <ItemRequestedScheduleLine>
                        <Quantity unitCode="EA">1.0</Quantity>
                    </ItemRequestedScheduleLine>
                    <ns2:zOVPosIDTipoPosicion>P009</ns2:zOVPosIDTipoPosicion>
                    <ns2:zOVPosTipServ>P</ns2:zOVPosTipServ>
                    <ns2:zOVPosCantTrab>0</ns2:zOVPosCantTrab>
                    <ns2:zID_PAQUETE>M1085-010</ns2:zID_PAQUETE>
                    <ns2:zTIPO_PAQUETE>z1</ns2:zTIPO_PAQUETE>
                    <ns2:zOVPosTiempoTeorico>0</ns2:zOVPosTiempoTeorico>
                </Item>
                
                <BusinessTransactionDocumentReference actionCode="01">
                    <UUID>4158d15e-4b3a-1fd0-91c3-fb78bb69531a</UUID>
                    <TypeCode>12</TypeCode>
                    <RoleCode>1</RoleCode>
                </BusinessTransactionDocumentReference>
                
                <Text actionCode="01">
                    <TextTypeCode>10024</TextTypeCode>
                    <ContentText>Prueba 2</ContentText>
                </Text>
                
                <ns2:zOVGrupoVendedores>D03</ns2:zOVGrupoVendedores>
                <ns2:zOVIDCentro>L013</ns2:zOVIDCentro>
                <ns2:zOVPlaca>CHY-421</ns2:zOVPlaca>
                <ns2:zOVVieneDeHCI>X</ns2:zOVVieneDeHCI>
                <ns2:zOVServExpress>false</ns2:zOVServExpress>
                <ns2:zOVKilometraje>10</ns2:zOVKilometraje>
                <ns2:zOVOrdenDBMV3>3000694890</ns2:zOVOrdenDBMV3>
                
            </CustomerQuote>
        </ns1:CustomerQuoteBundleMaintainRequest_sync_V1>
    </ns0:Body>
</ns0:Envelope>
```

---

## 📥 **Respuesta del Servicio**

### ✅ **Respuesta Exitosa**
```xml
<CustomerQuoteBundleMaintainConfirmation_sync_V1>
    <CustomerQuote>
        <ID>20939</ID>
        <UUID>00163E122C5F1EE4BBC4A8F6AB3F5E5D</UUID>
        <CreationDateTime>2024-06-25T12:15:30Z</CreationDateTime>
        <LastChangeDateTime>2024-06-25T12:15:30Z</LastChangeDateTime>
    </CustomerQuote>
    <Log />
</CustomerQuoteBundleMaintainConfirmation_sync_V1>
```

### ❌ **Respuesta con Error**
```xml
<Log>
    <Item>
        <TypeID>1</TypeID>
        <CategoryCode>Error</CategoryCode>
        <SeverityCode>3</SeverityCode>
        <Note>Error description here</Note>
    </Item>
</Log>
```

---

## 💾 **Actualización de Base de Datos**

### **✅ Si la respuesta es exitosa:**
```sql
UPDATE appointments SET 
    c4c_offer_id = '{ID del response}',
    offer_created_at = NOW(),
    offer_creation_failed = 0,
    offer_creation_error = NULL,
    offer_creation_attempts = offer_creation_attempts + 1
WHERE id = ?
```

### **❌ Si la respuesta tiene error:**
```sql
UPDATE appointments SET 
    offer_creation_failed = 1,
    offer_creation_error = '{error_message}',
    offer_creation_attempts = offer_creation_attempts + 1
WHERE id = ?
```

---


## ⚠️ **Consideraciones Importantes**

### **📋 Validaciones Previas**
1. **Usuario C4C:** Verificar que `users.c4c_internal_id` no sea NULL
2. **Mapeo Organización:** Confirmar registro en `center_organization_mapping`
3. **Productos:** Debe existir al menos 1 producto en `products` tabla
4. **UUID Appointment:** El `c4c_uuid` debe estar presente

### **🔄 Manejo de Errores**
1. **Reintentos:** Implementar máximo 3 intentos
2. **Timeout:** Configurar timeout de 30 segundos
3. **Logging:** Registrar todas las llamadas y respuestas
4. **Notificaciones:** Alertar fallos críticos

### **🚀 Optimizaciones**
1. **Cache:** Cachear datos de `center_organization_mapping`
2. **Batch:** Procesar múltiples appointments si es posible
3. **Async:** Considerar procesamiento asíncrono para UX

### **🔒 Seguridad**
1. **Escape XML:** Usar `htmlspecialchars()` en todos los valores
2. **Validación:** Sanitizar inputs antes de enviar
3. **Credentials:** Usar variables de entorno para credenciales SAP

---

## 📊 **Campos de Monitoreo**

| Campo | Propósito | Valores |
|-------|-----------|---------|
| `c4c_offer_id` | ID de la oferta en SAP | String numérico |
| `offer_created_at` | Timestamp de creación | DateTime |
| `offer_creation_failed` | Indicador de fallo | 0/1 |
| `offer_creation_error` | Mensaje de error | Text |
| `offer_creation_attempts` | Contador de intentos | Integer |

---

## 🔍 **Debugging y Troubleshooting**

### **Errores Comunes**
1. **Usuario no encontrado:** Verificar `c4c_internal_id`
2. **Centro no mapeado:** Revisar `center_organization_mapping`
3. **Productos faltantes:** Confirmar `products` con `appointment_id`
4. **Timeout SAP:** Verificar conectividad de red

### **Logs Recomendados**
```php
Log::info('Creating C4C Offer', [
    'appointment_id' => $appointmentId,
    'package_id' => $packageId,
    'products_count' => count($products)
]);

Log::error('C4C Offer Creation Failed', [
    'appointment_id' => $appointmentId,
    'error' => $errorMessage,
    'attempts' => $attempts
]);
```