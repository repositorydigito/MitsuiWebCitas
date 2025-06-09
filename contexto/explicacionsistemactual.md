Resumen del Sistema de Citas Mitsui
🏗️ Arquitectura General
El sistema es una aplicación web para la gestión de citas de servicio automotriz de Mitsui.

Backend: Laravel 12

Panel de Administración: Filament 3.3

Frontend Interactivo: Livewire 3.6

Integraciones: Servicios SOAP para comunicación con sistemas externos (SAP/C4C).

Base de Datos: MySQL

📊 Modelos de Datos Principales
User: Gestiona los usuarios, roles y permisos dentro del sistema.

Vehicle: Almacena la información de los vehículos de los clientes.

Appointment: Contiene todos los datos de las citas agendadas.

Local: Administra las sucursales y locales de servicio de Mitsui.

Campana: Define las campañas promocionales y sus reglas.

Bloqueo: Gestiona los bloqueos de horarios en la agenda.

Основные модули системы
🚗 Módulo de Gestión de Vehículos
Función: Permite visualizar, agregar y gestionar la información de los vehículos de los clientes.

Páginas Clave:

Vehiculos.php: Lista y filtra todos los vehículos.

DetalleVehiculo.php: Muestra la información completa y el historial de un vehículo.

AgregarVehiculo.php: Formulario para registrar nuevos vehículos, con integración SOAP para validación.

📅 Módulo de Agendamiento de Citas
Función: Centraliza el proceso de creación y gestión de citas.

Páginas Clave:

AgendarCita.php: Asistente de 3 pasos para crear una nueva cita, seleccionando vehículo, local, fecha, hora y servicios.

ProgramacionCitasServicio.php: Vista de calendario para visualizar y gestionar la disponibilidad y las citas existentes.

ProgramarBloqueo.php: Permite bloquear horarios específicos en la agenda de un local.

🎯 Módulo de Gestión de Campañas
Función: Administra las campañas promocionales del sistema.

Páginas Clave:

Campanas.php: Lista y gestiona todas las campañas activas.

CargaCampanasPage.php: Herramienta para la carga masiva de campañas desde un archivo Excel.

CrearCampana.php: Formulario para la creación manual de campañas individuales.

GestionPopUp.php: Administra los pop-ups promocionales que aparecen en la aplicación.

⚙️ Módulo de Administración
Función: Configuración general del sistema.

Páginas Clave:

AdministrarLocales.php: CRUD para la gestión de sucursales, horarios y ubicaciones.

AdministrarModelos.php: CRUD para los modelos de vehículos y los años disponibles.

UserResource.php y RoleResource.php: Gestión de usuarios, roles y permisos.

📈 Módulo de Reportes y KPIs
Función: Ofrece métricas y análisis sobre el rendimiento del sistema.

Páginas Clave:

Kpis.php: Muestra indicadores clave de rendimiento (KPIs) sobre citas y servicios.

DashboardKpi.php: Un dashboard ejecutivo con gráficos y métricas consolidadas en tiempo real.

🔄 Flujos de Datos e Integraciones
Flujo Principal de Usuario: El recorrido típico de un usuario es:

Dashboard: Punto de partida.

Gestión de Vehículos: Seleccionar o agregar un vehículo.

Agendar Cita: Completar el proceso de agendamiento.

Calendario: Visualizar la cita creada.

Flujo de Administración: Configuración de locales, modelos, usuarios y campañas.

Integraciones Clave:

VehiculoSoapService: Se comunica con SAP para obtener y validar la información de los vehículos.

CitasSoapService: Sincroniza las citas creadas en el sistema con SAP.

El sistema cruza datos constantemente entre Campañas, Bloqueos y Locales para asegurar que la disponibilidad y las promociones mostradas en el módulo de Agendar Cita sean siempre correctas.