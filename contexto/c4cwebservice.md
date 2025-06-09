
```markdown
# Documento de Especificación Técnica - Servicios C4C App Citas Web

## Información del Documento
- **Fecha:** 19/07/2024
- **Versión:** 1.0
- **Propósito:** Especificación de servicios C4C para flujo de creación de citas

## Historial de Revisiones

| Ítem | Fecha | Versión | Descripción | Realizado por |
|------|-------|---------|-------------|---------------|
| 1 | 18/07/2024 | 1.0 | Creación de documento | David Moreno |
| 2 | 19/07/2024 | 2.0 | Especificación de servicios y diccionario de datos | David Moreno |

## Configuración de Autenticación
- **Ambiente:** QA
- **Username:** USCP
- **Password:** Inicio01

---

## 1. Servicio de Consulta de Clientes

### Información General
- **Descripción:** Consultar cuentas
- **Nombre:** QueryCustomerIn
- **Tipo:** SOAP
- **URL:** [https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/querycustomerin1?sap-vhost=my317791.crm.ondemand.com](https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/querycustomerin1?sap-vhost=my317791.crm.ondemand.com)
- **Autenticación:** Básica

### Casos de Uso

#### Caso 1: Consulta por DNI

**Request XML:**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_">
   <soapenv:Header/>
   <soapenv:Body>
    <glob:CustomerByElementsQuery_sync>
    <CustomerSelectionByElements>
    <y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON>
    <SelectionByText>
    <InclusionExclusionCode>I</InclusionExclusionCode>
    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
    <LowerBoundaryName>40359482</LowerBoundaryName>
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
</soapenv:Envelope>
```

#### Caso 2: Consulta por RUC

**Request XML:**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_">
   <soapenv:Header/>
   <soapenv:Body>
    <glob:CustomerByElementsQuery_sync>
    <CustomerSelectionByElements>
    <y6s:zRuc_EA8AE8AUBVHCSXVYS0FJ1R3ON>
    <SelectionByText>
    <InclusionExclusionCode>I</InclusionExclusionCode>
    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
    <LowerBoundaryName>20558638223</LowerBoundaryName>
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
</soapenv:Envelope>
```

#### Caso 3: Consulta por Carnet de Extranjería

**Request XML:**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_">
   <soapenv:Header/>
   <soapenv:Body>
    <glob:CustomerByElementsQuery_sync>
    <CustomerSelectionByElements>
    <y6s:zCE_EA8AE8AUBVHCSXVYS0FJ1R3ON>
    <SelectionByText>
    <InclusionExclusionCode>I</InclusionExclusionCode>
    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
    <LowerBoundaryName>73532531</LowerBoundaryName>
    <UpperBoundaryName/>
    </SelectionByText>
    </y6s:zCE_EA8AE8AUBVHCSXVYS0FJ1R3ON>
    </CustomerSelectionByElements>
    <ProcessingConditions>
    <QueryHitsMaximumNumberValue>20</QueryHitsMaximumNumberValue>
    <QueryHitsUnlimitedIndicator>false</QueryHitsUnlimitedIndicator>
    </ProcessingConditions>
    </glob:CustomerByElementsQuery_sync>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Caso 4: Consulta por Pasaporte

**Request XML:**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_">
   <soapenv:Header/>
   <soapenv:Body>
    <glob:CustomerByElementsQuery_sync>
    <CustomerSelectionByElements>
    <y6s:zPasaporte_EA8AE8AUBVHCSXVYS0FJ1R3ON>
    <SelectionByText>
    <InclusionExclusionCode>I</InclusionExclusionCode>
    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
    <LowerBoundaryName>37429823</LowerBoundaryName>
    <UpperBoundaryName/>
    </SelectionByText>
    </y6s:zPasaporte_EA8AE8AUBVHCSXVYS0FJ1R3ON>
    </CustomerSelectionByElements>
    <ProcessingConditions>
    <QueryHitsMaximumNumberValue>20</QueryHitsMaximumNumberValue>
    <QueryHitsUnlimitedIndicator>false</QueryHitsUnlimitedIndicator>
    </ProcessingConditions>
    </glob:CustomerByElementsQuery_sync>
   </soapenv:Body>
</soapenv:Envelope>
```

### Diccionario de Datos - Consulta Clientes

#### Request

| Campo | Descripción | Tipo | Longitud | Valores | Observaciones |
|-------|-------------|------|----------|---------|---------------|
| InclusionExclusionCode | - | String | 1 | I | Valor fijo |
| IntervalBoundaryTypeCode | - | String | 1 | 1 | Valor fijo |
| LowerBoundaryName | Documento de identidad | String | Variable | 4444 | - |
| QueryHitsMaximumNumberValue | - | Numeric | 10 | 20 | Valor fijo |
| QueryHitsUnlimitedIndicator | - | Indicator | - | false | Valor fijo |

#### Response

| Campo | Descripción | Tipo | Longitud | Valores | Observaciones |
|-------|-------------|------|----------|---------|---------------|
| FirstLineName | Nombre Cliente | Text | 255 | Raul Porras Flores | - |
| InternalID | Id Interno | ID | 40 | 200001212 | - |
| UUID | UUID | UUID | 36 | fb859c15-e812-1edf-91ce-d7eb22acc871 | Formato: dddd-dddd-dddd-dddd-dddd |

---

## 2. Servicio de Gestión de Citas

### Información General
- **Descripción:** Gestionar actividades de cita
- **Nombre:** manageappointmentactivityin
- **Tipo:** SOAP
- **URL:** [https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/manageappointmentactivityin1?sap-vhost=my317791.crm.ondemand.com](https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/manageappointmentactivityin1?sap-vhost=my317791.crm.ondemand.com)
- **Autenticación:** Básica

### Casos de Uso

#### Caso 1: Registrar Citas

**Request XML:**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_" xmlns:a25="http://sap.com/xi/AP/CustomerExtension/BYD/A252F">
   <soapenv:Header/>
   <soapenv:Body>
    <glob:AppointmentActivityBundleMaintainRequest_sync_V1>
    <AppointmentActivity actionCode="01">
    <DocumentTypeCode>0001</DocumentTypeCode>
    <LifeCycleStatusCode>1</LifeCycleStatusCode>
    <MainActivityParty>
    <BusinessPartnerInternalID>1270000347</BusinessPartnerInternalID>
    </MainActivityParty>
    <AttendeeParty>
    <EmployeeID>700002</EmployeeID>
    </AttendeeParty>
    <StartDateTime timeZoneCode="UTC-5">2024-10-08T13:30:00Z</StartDateTime>
    <EndDateTime timeZoneCode="UTC-5">2024-10-08T13:44:00Z</EndDateTime>
    <Text actionCode="01">
    <TextTypeCode>10002</TextTypeCode>
    <ContentText>oParam.sObservacion</ContentText>
    </Text>
    <y6s:zClienteComodin>oParam.sNomClienteComodin</y6s:zClienteComodin>
    <y6s:zFechaHoraProbSalida>2024-10-08</y6s:zFechaHoraProbSalida>
    <y6s:zHoraProbSalida>13:40:00</y6s:zHoraProbSalida>
    <y6s:zIDCentro>M013</y6s:zIDCentro>
    <y6s:zPlaca>XXX-123</y6s:zPlaca>
    <y6s:zEstadoCita>1</y6s:zEstadoCita>
    <y6s:zVieneHCP>X</y6s:zVieneHCP>
    <y6s:zExpress>false</y6s:zExpress>
    </AppointmentActivity>
    </glob:AppointmentActivityBundleMaintainRequest_sync_V1>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Caso 2: Actualizar Citas (Diferido)

**Request XML:**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:yax="http://0002961282-one-off.sap.com/yaxAJ0KGY_" xmlns:a25="http://sap.com/xi/AP/CustomerExtension/BYD/A252F">
   <soapenv:Header/>
   <soapenv:Body>
    <glob:AppointmentActivityBundleMaintainRequest_sync_V1>
    <AppointmentActivity actionCode="04">
    <UUID>b59c62b2-26ee-1edf-91cb-c2c37396da32</UUID>
    <LifeCycleStatusCode>2</LifeCycleStatusCode>
    <yax:zEstadoCita>4</yax:zEstadoCita>
    <yax:zVieneHCP>X</yax:zVieneHCP>
    </AppointmentActivity>
    </glob:AppointmentActivityBundleMaintainRequest_sync_V1>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Caso 3: Eliminar Citas

**Request XML:**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_" xmlns:a25="http://sap.com/xi/AP/CustomerExtension/BYD/A252F">
   <soapenv:Header/>
   <soapenv:Body>
    <glob:AppointmentActivityBundleMaintainRequest_sync_V1>
    <AppointmentActivity actionCode="06">
    <UUID>00163e7d-2d22-1eea-b6a6-b244edf519e3</UUID>
    <LifeCycleStatusCode>4</LifeCycleStatusCode>
    <y6s:zEstadoCita>6</y6s:zEstadoCita>
    <y6s:zVieneHCP>X</y6s:zVieneHCP>
    </AppointmentActivity>
    </glob:AppointmentActivityBundleMaintainRequest_sync_V1>
   </soapenv:Body>
</soapenv:Envelope>
```

### Diccionario de Datos - Gestión de Citas

#### Request

| Nodo | Campo | Descripción | Tipo | Longitud | Valores | Observaciones |
|------|-------|-------------|------|----------|---------|---------------|
| - | DocumentTypeCode | - | String | 4 | 0001 | Valor fijo |
| - | LifeCycleStatusCode | - | Numeric | 1 | 1 | Valor fijo |
| MainActivityParty | BusinessPartnerInternalID | IdClienteC4C | Numeric | 10 | 1270000347 | - |
| AttendeeParty | EmployeeID | iIdParticipante | Numeric | 20 | 700002 | - |
| - | StartDateTime | FechaInicioCita | DateTime | - | 2024-10-08T13:30:00Z | CCYY-MM-DDThh:mm:ss(.sss)(Z) |
| - | EndDateTime | FechaFinCita | DateTime | - | 2024-10-08T13:44:00Z | CCYY-MM-DDThh:mm:ss(.sss)(Z) |
| Text | TextTypeCode | - | Numeric | 80 | 10002 | Valor fijo, campo opcional |
| Text | ContentText | Observaciones | Text | 255 | Observaciones | Campo opcional |
| - | zClienteComodin | - | Text | 255 | Cliente Comodin | Campo opcional |
| - | zFechaHoraProbSalida | Fecha de salida | Date | - | 2024-10-08 | YYYY-MM-dd |
| - | zHoraProbSalida | Hora de salida | Time | - | 13:40:00 | hh:mm:ss |
| - | zIDCentro | Código de taller | ID | 60 | M013 | - |
| - | zPlaca | Placa | ID | 60 | XXX-333 | - |
| - | zEstadoCita | Estado | Código Lista | (1,20) | 1 | Code List Estado de Cita |
| - | zVieneHCP | - | Texto | 255 | X | Valor fijo |
| - | zExpress | Express | Indicator | - | false | - |

#### Response

| Campo | Descripción | Tipo | Longitud | Valores | Observaciones |
|-------|-------------|------|----------|---------|---------------|
| UUID | Id cita | UUID | 36 | fb859c15-e812-1edf-91ce-d7eb22acc871 | Formato: dddd-dddd-dddd-dddd-dddd |

---

## 3. Servicio de Consulta de Citas Pendientes

### Información General
- **Descripción:** Consulta Citas pendientes
- **Nombre:** WSCitas
- **Tipo:** SOAP
- **URL:** [https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/yy6saj0kgy_wscitas?sap-vhost=my317791.crm.ondemand.com](https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/yy6saj0kgy_wscitas?sap-vhost=my317791.crm.ondemand.com)
- **Autenticación:** Básica

### Request XML

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global">
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
    <LowerBoundaryPartyID>1270002726</LowerBoundaryPartyID>
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
</soapenv:Envelope>
```

### Diccionario de Datos - Citas Pendientes

#### Request

| Nodo | SubNodo | Campo | Descripción | Tipo | Longitud | Valores | Observaciones |
|------|---------|-------|-------------|------|----------|---------|---------------|
| ActivitySimpleSelectionBy | SelectionByTypeCode | LowerBoundaryTypeCode | - | ID | 80 | 12 | Valor fijo (Appointment) |
| ActivitySimpleSelectionBy | SelectionByPartyID | LowerBoundaryPartyID | Id cliente | ID | 10 | 1270002726 | - |
| ActivitySimpleSelectionBy | SelectionByzEstadoCita | LowerBoundaryzEstadoCita | - | Código | 1 | 1 | Generada (valor fijo) |
| ActivitySimpleSelectionBy | SelectionByzEstadoCita | UpperBoundaryzEstadoCita | - | Código | 1 | 2 | Confirmada (valor fijo) |
| ProcessingConditions | - | QueryHitsMaximumNumberValue | - | Numeric | 1000 | 10000 | Valor fijo |

---

## Manejo de Errores

### Consulta sin Resultados

```xml
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/">
   <soap-env:Header/>
   <soap-env:Body>
    <n0:CustomerByElementsResponse_sync>
    <ProcessingConditions>
    <ReturnedQueryHitsNumberValue>0</ReturnedQueryHitsNumberValue>
    <MoreHitsAvailableIndicator>false</MoreHitsAvailableIndicator>
    </ProcessingConditions>
    </n0:CustomerByElementsResponse_sync>
   </soap-env:Body>
</soap-env:Envelope>
```

### Error en Creación de Cita

```xml
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/">
   <soap-env:Header/>
   <soap-env:Body>
    <n0:AppointmentActivityBundleMaintainConfirmation_sync_V1>
    <Log>
    <MaximumLogItemSeverityCode>3</MaximumLogItemSeverityCode>
    <Item>
    <SeverityCode>2</SeverityCode>
    <Note>No se encontró la placa.</Note>
    </Item>
    <Item>
    <SeverityCode>3</SeverityCode>
    <Note>El vehículo ya tiene cita(s) abierta(s) en el local MOLINA SERVICIO</Note>
    </Item>
    </Log>
    </n0:AppointmentActivityBundleMaintainConfirmation_sync_V1>
   </soap-env:Body>
</soap-env:Envelope>
```

---

## Códigos de Estado y Listas

### Estados de Cita
- **1:** Generada
- **2:** Confirmada
- **4:** Diferida
- **6:** Cancelada

### Códigos de Acción
- **01:** Crear
- **04:** Actualizar
- **06:** Eliminar
```