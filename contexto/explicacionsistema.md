Documento de Especificación de Flujo: Proyecto Plataforma de Citas Mitsui
Versión: 1.0
Objetivo: Detallar los requerimientos, flujos, entidades y arquitectura para el desarrollo del "Módulo de Agendamiento de Citas" dentro del ecosistema digital de Mitsui.

1.0 Entidades y Actores del Ecosistema
1.1. Roles / Actores Humanos
id: cliente: Usuario final de la plataforma. Propietario de uno o más vehículos.

id: presentador_bc: Bryann Chuco (Mitsui). Rol: Product Owner / Stakeholder.

id: desarrollador_pa: Pablo Agüero (Proveedor). Rol: Líder Técnico / Arquitecto.

id: desarrollador_mp: Moises Antonio Suarez Perez (Proveedor). Rol: Miembro del equipo técnico.

id: equipo_postventa: Personal interno de Mitsui que administrará el contenido del módulo (ej. campañas).

id: equipo_comercial: Personal interno de Mitsui que recibirá notificaciones de leads (ej. venta de SOAT).

1.2. Sistemas / Plataformas de Software
id: plataforma_principal

Nombre: Plataforma Digital de Mitsui.

Descripción: Contenedor principal o portal web que aloja múltiples módulos para el cliente.

Estado: Ya existente y en producción.

Proveedor: Un proveedor anterior.

id: modulo_estado_compra

Nombre: "Estado de tu compra".

Descripción: Módulo existente dentro de plataforma_principal. Permite el tracking de un vehículo nuevo desde el pago hasta la entrega.

Estado: En producción y en uso por clientes.

id: modulo_citas

Nombre: "Agendamiento de Citas para Postventa".

Descripción: El nuevo módulo a desarrollar. Su objetivo es permitir al cliente agendar citas de taller.

Estado: En fase de planificación/desarrollo.

id: sistema_sap_erp

Nombre: SAP (Sistema principal de Mitsui).

Función: Fuente de verdad para los datos maestros de clientes y vehículos.

Interacción: El modulo_citas consumirá información de este sistema de forma indirecta.

id: sistema_sap_c4c

Nombre: SAP C4C (Cloud for Customer).

Función: Sistema especializado que gestiona la disponibilidad (slots de tiempo, fechas) de los talleres para las citas.

Interacción: El modulo_citas consumirá información de este sistema para mostrar el calendario de citas.

id: api_proveedor_intermedio

Descripción: Un sistema (API/servicio) desarrollado por OTRO proveedor.

Función: Actuar como capa intermedia (middleware) que expone la información de sistema_sap_erp y sistema_sap_c4c para que modulo_citas pueda consumirla.

Implicación: El equipo de desarrollador_pa y desarrollador_mp no se conectará directamente a SAP, sino a esta API.

2.0 Flujo de Proceso Detallado del cliente
2.1. Proceso de Autenticación y Acceso
Acción de Usuario: cliente navega a la web de Mitsui.

Acción de Usuario: cliente ejecuta CLICK en el botón id: "iniciar_sesion".

Respuesta del Sistema: Se muestra la pantalla de login.

Input del Usuario: cliente introduce sus credenciales (usuario, contraseña).

Acción de Usuario: cliente ejecuta CLICK en el botón id: "login_submit".

Respuesta del Sistema: El sistema valida las credenciales. Si son correctas, el cliente es redirigido al dashboard de la plataforma_principal.

Respuesta del Sistema: El dashboard muestra los módulos disponibles, incluyendo id: "modulo_citas".

Acción de Usuario: cliente ejecuta CLICK en id: "modulo_citas".

2.2. Proceso de Creación de Cita
Respuesta del Sistema: Carga la interfaz principal de modulo_citas. Muestra una lista de todos los vehículos asociados a la cuenta del cliente.

Fuente de Datos: api_proveedor_intermedio (que a su vez obtiene los datos de sistema_sap_erp).

Acción de Usuario: cliente selecciona el vehículo para el cual desea agendar la cita.

Respuesta del Sistema: Se muestra el formulario de "Agendamiento Rápido" con los siguientes campos:

Campo: datos_cliente: Pre-poblado.

Campo: seleccion_local: Dropdown para elegir el taller.

Campo: seleccion_fecha_hora: Calendario/Selector de hora.

Fuente de Datos: La disponibilidad (días/horas habilitados) se obtiene en tiempo real desde api_proveedor_intermedio (que a su vez la obtiene de sistema_sap_c4c).

Campo: tipo_mantenimiento: Selector (ej. "10k", "20k", etc.).

Campo: modalidad: Selector (ej. "Exprés", "Regular").

Acción de Usuario: cliente completa todos los campos del formulario.

Respuesta del Sistema: Se muestra un carrusel de "Campañas" administrable por equipo_postventa.

Ejemplos: "Restauración de Faros", "Lavado de Salón".

Acción de Usuario (Opcional): cliente ejecuta CLICK para agregar una o más campañas a su orden de servicio.

Input del Usuario (Opcional): cliente escribe texto en el campo id: "comentario_adicional".

Acción de Usuario: cliente ejecuta CLICK en el botón id: "continuar".

Respuesta del Sistema: La cita es registrada. Se muestra una confirmación.

Respuesta del Sistema (Pop-up): Inmediatamente después de la confirmación, se muestra un pop-up con ofertas de venta cruzada.

Opciones: "Venta de SOAT", "Seguro Toyota".

Acción de Usuario (Opcional): cliente selecciona una de las opciones y presiona "Enviar".

Proceso de Sistema: Si el cliente envía el formulario del pop-up, se genera una notificación que es enviada al equipo_comercial.

2.3. Proceso de Seguimiento de Cita
Acción de Usuario: Desde su panel, el cliente accede a la sección "Mis Citas" o "Detalle de mi Vehículo".

Respuesta del Sistema: Muestra una vista detallada con:

Información del vehículo: Placa, kilometraje, mantenimientos prepagados, fecha último mantenimiento.

Citas Agendadas: Una lista de citas con su estado actual.

Proceso de Sistema (Ciclo de Vida de la Cita): El atributo estado de una cita se actualiza conforme avanza el proceso en el taller. Los estados son:

Estado: Cita Confirmada: Estado inicial.

Estado: En Trabajo: Se activa cuando el auto ingresa al taller.

Evento de Sistema: Al cambiar a este estado, se pueblan nuevos campos con datos de sistema_sap_erp vía api_proveedor_intermedio:

fecha_posible_entrega

hora_posible_entrega

sede

nombre_asesor

correo_asesor

whatsapp_asesor

Estado: Trabajo Concluido

Estado: Vehículo Entregado

3.0 Casos de Uso Adicionales
3.1. Agregar Vehículo Externo
Condición: Un cliente tiene un vehículo que no fue comprado en Mitsui o que nunca ha tenido servicio allí.

Flujo:

El cliente accede a una función id: "agregar_vehiculo".

El cliente introduce los siguientes datos: placa, modelo, año.

El cliente introduce el dato opcional: kilometraje.

Propósito del dato kilometraje: El sistema lo usará para ejecutar una lógica de negocio que recomiende el mantenimiento adecuado para el vehículo.

El sistema crea la nueva entidad "vehículo" y la asocia a la cuenta del cliente.

4.0 Discusión Técnica y Requerimientos No Funcionales
4.1. Problema Central de Implementación
La plataforma_principal fue desarrollada por un proveedor tercero. Se debe decidir la estrategia para integrar el nuevo modulo_citas.

4.2. Opciones de Arquitectura Discutidas
Opción A: Integración Nativa

Descripción: Modificar el código fuente existente de la plataforma_principal para añadir el modulo_citas como parte del mismo proyecto.

Requisito: Acceso total al código fuente y documentación del proveedor anterior.

Riesgo/Costo: Requiere una fase de análisis de código ajeno, lo que puede incrementar el tiempo y la complejidad.

Opción B: Desarrollo Aislado (Micro-frontend)

Descripción: Desarrollar el modulo_citas como una aplicación completamente independiente y autocontenida.

Integración: Desde la plataforma_principal, se colocaría un simple enlace/botón que redirija o cargue esta nueva aplicación.

Ventaja (según desarrollador_pa): Desarrollo más rápido y desacoplado, sin dependencias del código legado.

4.3. Decisiones y Próximos Pasos
Decisión Pendiente: Elegir entre Opción A y Opción B.

Requisito para presentador_bc``: Debe proporcionar al equipo de desarrollo las especificaciones técnicas de la plataforma_principal (lenguajes, frameworks, arquitectura).

Requisito para presentador_bc``: Debe compartir los mockups detallados del modulo_citas.

Acción para desarrollador_pa``: Analizar las especificaciones técnicas para dar una recomendación final y más precisa sobre la viabilidad de Opción A vs. Opción B.

Restricción de Proyecto: El plazo máximo de entrega es de 4 meses.