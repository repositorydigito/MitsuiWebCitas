# Base de Datos - Sistema Mitsui de Citas

## Descripción General

Este sistema maneja el agendamiento de citas para servicios de mantenimiento de vehículos Mitsubishi. La base de datos está completamente estructurada en inglés para mantener consistencia y facilitar el mantenimiento.

## Estructura de la Base de Datos

### Tablas Principales

#### 1. **premises** (Locales/Sucursales)
- `id`: ID único
- `code`: Código del local (ej: 'local1', 'local2')
- `name`: Nombre del local (ej: 'Mitsui La Molina')
- `address`: Dirección completa
- `location`: Información de ubicación/teléfono
- `is_active`: Estado activo (boolean)
- `waze_url`: URL de Waze (opcional)
- `maps_url`: URL de Google Maps (opcional)

#### 2. **models** (Modelos de Vehículos)
- `id`: ID único
- `code`: Código del modelo (ej: 'OUTLANDER')
- `name`: Nombre del modelo (ej: 'Outlander')
- `brand`: Marca del vehículo
- `description`: Descripción del modelo
- `is_active`: Estado activo

#### 3. **campaigns** (Campañas Promocionales)
- `id`: ID único
- `title`: Título de la campaña
- `description`: Descripción detallada
- `city`: Ciudad donde aplica
- `status`: Estado ('active' o 'inactive')
- `start_date`: Fecha de inicio
- `end_date`: Fecha de fin
- `is_active`: Estado activo

#### 4. **vehicles** (Vehículos de Clientes)
- `id`: ID único
- `vehicle_id`: ID del vehículo en sistema externo
- `license_plate`: Placa del vehículo
- `model`: Modelo del vehículo
- `year`: Año del vehículo
- `brand_name`: Nombre de la marca
- `customer_name`: Nombre del propietario
- `customer_phone`: Teléfono del propietario
- `customer_email`: Email del propietario

#### 5. **appointments** (Citas)
- `id`: ID único
- `appointment_number`: Número de cita único
- `vehicle_id`: FK a vehicles
- `premise_id`: FK a premises
- `customer_ruc`: RUC del cliente
- `appointment_date`: Fecha de la cita
- `appointment_time`: Hora de la cita
- `customer_name`: Nombre del cliente
- `customer_last_name`: Apellido del cliente
- `customer_phone`: Teléfono del cliente
- `customer_email`: Email del cliente
- `service_mode`: Tipo de servicio seleccionado
- `maintenance_type`: Tipo de mantenimiento
- `comments`: Comentarios adicionales
- `status`: Estado de la cita ('pending', 'confirmed', 'completed', 'cancelled')

### Tablas de Relación

#### **campaign_models** (Campañas ↔ Modelos)
- Relaciona qué modelos de vehículos aplican para cada campaña

#### **campaign_premises** (Campañas ↔ Locales)
- Relaciona en qué locales está disponible cada campaña
- Usa `premise_code` en lugar de FK para flexibilidad

#### **campaign_years** (Campañas ↔ Años)
- Relaciona qué años de vehículos aplican para cada campaña
- Almacena el año directamente, no como FK

### Tablas de Configuración

#### **vehicles_express** (Servicios Express)
- `model`: Modelo del vehículo
- `brand`: Marca del vehículo
- `year`: Año del vehículo
- `premises`: Código del local
- `maintenance`: JSON con tipos de mantenimiento disponibles
- `is_active`: Estado activo

#### **maintenance_types** (Tipos de Mantenimiento)
- `name`: Nombre (ej: '5,000 Km')
- `code`: Código único
- `description`: Descripción
- `kilometers`: Kilómetros del mantenimiento
- `is_active`: Estado activo

#### **additional_services** (Servicios Adicionales)
- `name`: Nombre del servicio
- `code`: Código único
- `description`: Descripción
- `price`: Precio del servicio
- `duration_minutes`: Duración en minutos
- `is_active`: Estado activo

#### **blockades** (Bloqueos de Horarios)
- `premises`: Código del local
- `start_date`: Fecha de inicio del bloqueo
- `end_date`: Fecha de fin del bloqueo
- `start_time`: Hora de inicio (opcional)
- `end_time`: Hora de fin (opcional)
- `all_day`: Si es bloqueo de todo el día
- `comments`: Comentarios del bloqueo

#### **pop_ups** (Pop-ups Promocionales)
- `name`: Nombre del pop-up
- `image_path`: Ruta de la imagen
- `sizes`: Tamaño de la imagen
- `format`: Formato de la imagen
- `url_wp`: URL de WhatsApp
- `is_active`: Estado activo

## Instalación y Configuración

### 1. Ejecutar Migraciones

```bash
php artisan migrate:fresh
```

### 2. Ejecutar Seeders (Datos Iniciales)

```bash
php artisan db:seed
```

Esto creará:
- 3 locales de ejemplo (La Molina, San Borja, Surco)
- 3 modelos de vehículos (Outlander, Lancer, Montero)
- Años 2018-2024 para cada modelo
- 3 tipos de mantenimiento (5K, 10K, 20K km)
- 3 servicios adicionales
- 2 vehículos de ejemplo
- 3 configuraciones de servicios express
- 2 campañas activas
- 1 pop-up promocional
- Usuario administrador (admin@mitsui.com)

### 3. Verificar Instalación

Puedes verificar que todo esté funcionando accediendo a:
- `/admin` - Panel de administración Filament
- `/vehiculos` - Página de búsqueda de vehículos
- `/campanas` - Página de campañas

## Cambios Importantes

### Migración de Español a Inglés

La base de datos fue migrada completamente de español a inglés:

**Antes (Español):**
- `titulo` → `title`
- `estado` → `status`
- `codigo` → eliminado (se usa `id`)
- `ruta` → `image_path`
- `activo` → `is_active`

**Después (Inglés):**
- Todas las columnas en inglés
- Estructura más consistente
- Mejor mantenibilidad

### Estructura de Campañas

Las campañas ahora usan:
- `status`: 'active' o 'inactive'
- Relaciones flexibles con códigos en lugar de FKs estrictas
- Años almacenados directamente en `campaign_years`

## Notas para Desarrolladores

1. **Consistencia**: Todas las columnas están en inglés
2. **Flexibilidad**: Las relaciones de campañas usan códigos para mayor flexibilidad
3. **Datos de Prueba**: El seeder incluye datos realistas para pruebas
4. **Migraciones Limpias**: Una sola migración principal con toda la estructura
5. **Documentación**: Cada tabla y columna está documentada

## Comandos Útiles

```bash
# Resetear base de datos completamente
php artisan migrate:fresh --seed

# Solo ejecutar seeders
php artisan db:seed

# Ejecutar seeder específico
php artisan db:seed --class=MitsuiInitialDataSeeder

# Ver estado de migraciones
php artisan migrate:status
```
