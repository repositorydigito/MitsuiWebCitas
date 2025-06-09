Documentación Completa del Web Service de Integración para Gestión de Citas
1. INTRODUCCIÓN
1.1 Propósito
El propósito de este documento es dejar una descripción textual para el consumo y envío de parámetros que se requieren en cada operación que contiene el proyecto de web service.
1.2 Alcance
El servicio web se elabora en base al ERP SAP de Mitsui, en dicho servicio se encuentran las operaciones requeridas para el proceso de integración con el Portal de Gestión de Citas.
1.3 Definiciones, Acrónimos y abreviaturas

ERP: Sistema principal de la empresa

2. DETALLE DEL SERVICIO
Información de Conexión
ParámetroValorLenguajeAbapServidor de aplicaciónSAPWSDLhttp://mitdesqafo.mitsuiautomotriz.com:8002/sap/bc/srt/wsdl/flv_10002A111AD1/bndg_url/sap/bc/srt/rfc/sap/zws_ges_cit/400/zws_gescit/zws_gescit?sap-client=400UsuarioUSR_TRKClaveSrv0103$MrvTk%
El desarrollo del servicio se realizó utilizando el protocolo SOAP de SAP. A continuación, se detalla la funcionalidad y parámetros (entrada y salida) de cada uno de los servicios.
2.1 Z3PF_GETDATOSCLIENTE
Este servicio retorna los datos del cliente, requeridos para el flujo operativo.
2.1.1 PARÁMETROS ENTRADA
ParámetroDescripciónLongTipoValoresObservaciónPI_NUMDOCCLINro Documento Cliente11CHAR
2.1.2 PARÁMETROS SALIDA
ParámetroDescripciónLongTipoValoresObservaciónPE_NOMCLINombre del Cliente100CHARPE_CORCLICorreo del Cliente100CHARPE_TELCLITeléfono del Cliente15CHAR
2.2 Z3PF_GETLISTAVEHICULOS
Este servicio retorna una lista de vehículos relacionados al cliente.
2.2.1 PARÁMETROS ENTRADA
ParámetroDescripciónLongTipoValoresObservaciónPI_NUMDOCCLINro Documento Cliente11CHARPI_MARCACodigo de Marca3CHAR
2.2.2 PARÁMETROS SALIDA
Se devuelve la lista con la estructura definida:
ParámetroDescripciónLongTipoValoresObservaciónNUMPLANro de Placa10CHARANIOMODAño del Modelo4NUMMODVERModelo Versión20CHARVHCLEId VehiculoNo ObligatorioUso Interno
2.3 Z3PF_GETLISTASERVICIOS
Este servicio lista los mantenimientos realizados por una placa de vehículo.
2.3.1 PARÁMETROS ENTRADA
ParámetroDescripciónLongTipoValoresObservaciónPI_PLACANro de Placa10CHAR
2.3.2 PARÁMETROS SALIDA
Se devuelve la lista con la estructura definida:
ParámetroDescripciónLongTipoValoresObservaciónPE_PLACANro de Placa10CHARPE_KILOMETRAJEKM Ultimo Servicio4NUMPE_ULT_SERVICIOUltimo Servicio30CHARPE_ULT_FEC_SERVICIOFecha de Ultimo Servicio10DATEPE_ULT_FEC_PREPAGOFecha de Vencimiento Prepago10DATETT_LISSRVTabla Datos de Servicios
Estructura de TT_LISSRV:
ParámetroDescripciónLongTipoValoresObservaciónFECSRVFecha del Servicio10CHARDESSRVDescripción del Servicio25CHARASESRVAsesor del Servicio60CHARSEDSRVLocal del Servicio60CHARTIPPAGSRVTipo de Pago25CHAR
2.4 Z3PF_GETDATOSASESORPROCESO
Este servicio lista los datos del asesor asignado al servicio en proceso.
2.4.1 PARÁMETROS ENTRADA
ParámetroDescripciónLongTipoValoresObservaciónPI_PLACANro de Placa10CHAR
2.4.2 PARÁMETROS SALIDA
Se devuelve la lista con la estructura definida:
ParámetroDescripciónLongTipoValoresObservaciónPE_NOM_ASENombre del Asesor100CHARPE_TEL_ASERTeléfono del Asesor10CHARPE_COR_ASECorreo de Asesor100CHARPE_FEC_ENTREGAFecha de Entrega10DATEPE_HOR_ENTREGAHora de Entrega8TIMEPE_LOCALLocal del servicio30CHAR
2.5 Z3PF_GETLISTAPREPAGOPEN
Este servicio lista todos los prepagos que están pendientes de uso.
2.5.1 PARÁMETROS ENTRADA
ParámetroDescripciónLongTipoValoresObservaciónPI_PLACANro de Placa10CHARPI_PENDIndicador internoNo obligatorioUso Interno
2.5.2 PARÁMETROS SALIDA
Se devuelve la lista con la estructura definida:
ParámetroDescripciónLongTipoValoresObservaciónMAKTXTexto del Prepago40CHARZZTIPOValor internoNo usarZZMAT1Valor internoNo usarKUNNRValor internoNo usar
3. DICCIONARIO DE DATOS
Se detalla los datos requeridos para la integración.
3.1 Código de marcas
CódigoDescripciónZ01TOYOTAZ02LEXUSZ03HINO
4. PRUEBAS DE CONSUMO
4.1 Z3PF_GETDATOSCLIENTE
Solicitud SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" 
               xmlns:urn="urn:sap-com:document:sap:rfc:functions">
  <soap:Header/>
  <soap:Body>
    <urn:Z3PF_GETDATOSCLIENTE>
      <PI_NUMDOCCLI>20601641410</PI_NUMDOCCLI>
    </urn:Z3PF_GETDATOSCLIENTE>
  </soap:Body>
</soap:Envelope>
Respuesta SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
  <soap:Body>
    <urn:Z3PF_GETDATOSCLIENTEResponse xmlns:urn="urn:sap-com:document:sap:rfc:functions">
      <PE_CORCLI>SantiacoEmail.com</PE_CORCLI>
      <PE_NOMCLI>SENTINEL S.A.</PE_NOMCLI>
      <PE_TELCLI>967700</PE_TELCLI>
    </urn:Z3PF_GETDATOSCLIENTEResponse>
  </soap:Body>
</soap:Envelope>
4.2 Z3PF_GETLISTAVEHICULOS
Solicitud SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
               xmlns:urn="urn:sap-com:document:sap:rfc:functions">
  <soap:Header/>
  <soap:Body>
    <urn:Z3PF_GETLISTAVEHICULOS>
      <PI_MARCA>Z01</PI_MARCA>
      <PI_NUMDOCCLI>20601641410</PI_NUMDOCCLI>
    </urn:Z3PF_GETLISTAVEHICULOS>
  </soap:Body>
</soap:Envelope>
Respuesta SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
  <soap:Body>
    <urn:Z3PF_GETLISTAVEHICULOSResponse xmlns:urn="urn:sap-com:document:sap:rfc:functions">
      <TT_LISVEH>
        <item>
          <VHCLE>0000439144</VHCLE>
          <NUMPLA>AYW-716</NUMPLA>
          <ANIOMOD>2017</ANIOMOD>
          <MODVER>404 D/C 1GD EKV/RUD9TE</MODVER>
        </item>
        <item>
          <VHCLE>0000444125</VHCLE>
          <NUMPLA>ATR-657</NUMPLA>
          <ANIOMOD>2017</ANIOMOD>
          <MODVER>404 D/C 1GD EKV/RUD9TE</MODVER>
        </item>
        <item>
          <VHCLE>0000439762</VHCLE>
          <NUMPLA>AYT-626</NUMPLA>
          <ANIOMOD>2017</ANIOMOD>
          <MODVER>K ADVENTURE 404/RUD9TE</MODVER>
        </item>
      </TT_LISVEH>
    </urn:Z3PF_GETLISTAVEHICULOSResponse>
  </soap:Body>
</soap:Envelope>
4.3 Z3PF_GETLISTASERVICIOS
Solicitud SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
               xmlns:urn="urn:sap-com:document:sap:rfc:functions">
  <soap:Header/>
  <soap:Body>
    <urn:Z3PF_GETLISTASERVICIOS>
      <PI_PLACA>ATR-657</PI_PLACA>
    </urn:Z3PF_GETLISTASERVICIOS>
  </soap:Body>
</soap:Envelope>
Respuesta SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
  <soap:Body>
    <urn:Z3PF_GETLISTASERVICIOSResponse xmlns:urn="urn:sap-com:document:sap:rfc:functions">
      <PE_KILOMETRAJE>0</PE_KILOMETRAJE>
      <PE_PLACA>ATR-657</PE_PLACA>
      <PE_ULT_FEC_PREPAGO>0000-00-00</PE_ULT_FEC_PREPAGO>
      <PE_ULT_FEC_SERVICIO>0000-00-00</PE_ULT_FEC_SERVICIO>
      <PE_ULT_SERVICIO></PE_ULT_SERVICIO>
      <TT_LISSRV>
        <!-- Lista vacía en este ejemplo -->
      </TT_LISSRV>
    </urn:Z3PF_GETLISTASERVICIOSResponse>
  </soap:Body>
</soap:Envelope>
4.4 Z3PF_GETDATOSASESORPROCESO
Solicitud SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
               xmlns:urn="urn:sap-com:document:sap:rfc:functions">
  <soap:Header/>
  <soap:Body>
    <urn:Z3PF_GETDATOSASESORPROCESO>
      <PI_PLACA>ATR-657</PI_PLACA>
    </urn:Z3PF_GETDATOSASESORPROCESO>
  </soap:Body>
</soap:Envelope>
Respuesta SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
  <soap:Body>
    <urn:Z3PF_GETDATOSASESORPROCESOResponse xmlns:urn="urn:sap-com:document:sap:rfc:functions">
      <PE_COR_ASE></PE_COR_ASE>
      <PE_FEC_ENTREGA>0000-00-00</PE_FEC_ENTREGA>
      <PE_HOR_ENTREGA>00:00:00</PE_HOR_ENTREGA>
      <PE_LOCAL></PE_LOCAL>
      <PE_NOM_ASE></PE_NOM_ASE>
      <PE_TEL_ASER></PE_TEL_ASER>
    </urn:Z3PF_GETDATOSASESORPROCESOResponse>
  </soap:Body>
</soap:Envelope>
4.5 Z3PF_GETLISTAPREPAGOPEN
Solicitud SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
               xmlns:urn="urn:sap-com:document:sap:rfc:functions">
  <soap:Header/>
  <soap:Body>
    <urn:Z3PF_GETLISTAPREPAGOPEN>
      <PI_PEND></PI_PEND>
      <PI_PLACA>ATR-657</PI_PLACA>
    </urn:Z3PF_GETLISTAPREPAGOPEN>
  </soap:Body>
</soap:Envelope>
Respuesta SOAP:
xml<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
  <soap:Body>
    <urn:Z3PF_GETLISTAPREPAGOPENResponse xmlns:urn="urn:sap-com:document:sap:rfc:functions">
      <TT_LISPREPAGO>
        <!-- Lista vacía en este ejemplo -->
      </TT_LISPREPAGO>
    </urn:Z3PF_GETLISTAPREPAGOPENResponse>
  </soap:Body>
</soap:Envelope>
5. CONSIDERACIONES TÉCNICAS
5.1 Formatos de Datos

Fechas: Formato DATE (YYYY-MM-DD). Cuando no hay valor, retorna "0000-00-00"
Horas: Formato TIME (HH:MM:SS). Cuando no hay valor, retorna "00:00:00"
Strings: Cuando no hay valor, retorna cadena vacía
Números: Cuando no hay valor, retorna 0

5.2 Namespace
Todos los servicios utilizan el namespace: urn:sap-com:document:sap:rfc:functions
5.3 Autenticación

La autenticación se realiza mediante usuario y contraseña en el header HTTP Basic
Es obligatorio incluir el parámetro sap-client=400 en la URL

5.4 Manejo de Errores
El sistema SAP retornará mensajes de error SOAP estándar en caso de fallas:

Errores de autenticación
Parámetros inválidos
Errores de sistema

5.5 Campos de Uso Interno
Los siguientes campos NO deben ser utilizados por sistemas externos:

VHCLE (en Z3PF_GETLISTAVEHICULOS)
PI_PEND (en Z3PF_GETLISTAPREPAGOPEN)
ZZTIPO, ZZMAT1, KUNNR (en Z3PF_GETLISTAPREPAGOPEN)

6. FLUJO TÍPICO DE INTEGRACIÓN

Identificación del Cliente

Llamar a Z3PF_GETDATOSCLIENTE con el número de documento
Obtener datos básicos del cliente


Listado de Vehículos

Llamar a Z3PF_GETLISTAVEHICULOS con documento y marca
Obtener lista de vehículos del cliente


Historial de Servicios

Llamar a Z3PF_GETLISTASERVICIOS con la placa del vehículo
Obtener historial completo de mantenimientos


Verificar Prepagos

Llamar a Z3PF_GETLISTAPREPAGOPEN con la placa
Obtener lista de prepagos disponibles


Consultar Servicio en Proceso

Llamar a Z3PF_GETDATOSASESORPROCESO con la placa
Obtener información del asesor y tiempos estimados