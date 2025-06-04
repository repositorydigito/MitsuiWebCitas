# 🚗 Sistema de Citas Mitsui - Guía de Instalación

## 📋 Requisitos Previos

- **PHP 8.1+**
- **MySQL 8.0+** o **MariaDB 10.3+**
- **Composer**
- **Node.js 16+** (opcional, para assets)

## 🚀 Instalación Rápida

### 1. Configurar Base de Datos

Crea una base de datos MySQL:

```sql
CREATE DATABASE mitsui_citas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Configurar Variables de Entorno

Copia el archivo de configuración de ejemplo:

```bash
cp .env.database.example .env
```

Edita el archivo `.env` con tus datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mitsui_citas
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 3. Instalar Dependencias

```bash
composer install
```

### 4. Generar Clave de Aplicación

```bash
php artisan key:generate
```

### 5. Ejecutar Instalación de Base de Datos

**En Windows:**
```bash
install-database.bat
```

**En Linux/Mac:**
```bash
chmod +x install-database.sh
./install-database.sh
```

**O manualmente:**
```bash
php artisan migrate:fresh --seed
```

### 6. Iniciar Servidor

```bash
php artisan serve
```

## 🎯 Acceso al Sistema

### Panel de Administración
- **URL:** http://localhost:8000/admin
- **Usuario:** admin@mitsui.com
- **Contraseña:** password

### Páginas Públicas
- **Búsqueda de Vehículos:** http://localhost:8000/vehiculos
- **Campañas:** http://localhost:8000/campanas
- **Agendamiento:** http://localhost:8000/agendar-cita

## 📊 Datos Iniciales Incluidos

El sistema viene con datos de prueba:

### 🏢 Locales (3)
- **Mitsui La Molina** - Con URLs de Waze y Maps
- **Mitsui San Borja** - Con URLs de Waze y Maps  
- **Mitsui Surco** - Sin URLs (para probar funcionalidad)

### 🚙 Modelos de Vehículos (3)
- **Outlander** (2018-2024)
- **Lancer** (2018-2024)
- **Montero** (2018-2024)

### 🔧 Tipos de Mantenimiento (3)
- **5,000 Km** - Mantenimiento básico
- **10,000 Km** - Mantenimiento intermedio
- **20,000 Km** - Mantenimiento mayor

### 🎯 Campañas Activas (2)
- **Campaña de Verano 2024** - Para todos los modelos y locales
- **Promoción Outlander** - Específica para Outlander 2022-2024

### 🚗 Vehículos de Ejemplo (2)
- **ABC-123** - Outlander 2022 (Juan Pérez)
- **DEF-456** - Lancer 2021 (María García)

### ⚡ Servicios Express (3)
- Configuraciones para diferentes combinaciones modelo/año/local

## 🛠️ Configuración Adicional

### Configurar Storage para Imágenes

```bash
php artisan storage:link
```

### Configurar Permisos (Linux/Mac)

```bash
chmod -R 775 storage bootstrap/cache
```

### Configurar Cron Jobs (Opcional)

Agregar al crontab para tareas programadas:

```bash
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## 🔧 Comandos Útiles

### Base de Datos
```bash
# Resetear base de datos completamente
php artisan migrate:fresh --seed

# Solo ejecutar seeders
php artisan db:seed

# Ver estado de migraciones
php artisan migrate:status
```

### Cache
```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Logs
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

## 🐛 Solución de Problemas

### Error de Conexión a Base de Datos
1. Verificar que MySQL esté ejecutándose
2. Confirmar credenciales en `.env`
3. Verificar que la base de datos existe

### Error de Permisos
```bash
# Linux/Mac
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Error de Clave de Aplicación
```bash
php artisan key:generate
```

### Problemas con Migraciones
```bash
# Rollback y volver a ejecutar
php artisan migrate:rollback
php artisan migrate
```

## 📁 Estructura del Proyecto

```
mitsui-citas/
├── app/
│   ├── Filament/Pages/     # Páginas de la aplicación
│   ├── Models/             # Modelos de base de datos
│   └── Http/Controllers/   # Controladores
├── database/
│   ├── migrations/         # Migraciones de BD
│   └── seeders/           # Datos iniciales
├── resources/views/        # Vistas Blade
└── routes/                # Rutas de la aplicación
```

## 🔄 Actualizaciones

Para actualizar el sistema:

1. Hacer backup de la base de datos
2. Ejecutar `composer update`
3. Ejecutar `php artisan migrate`
4. Limpiar cache: `php artisan cache:clear`

## 📞 Soporte

Para problemas o dudas:
1. Revisar los logs en `storage/logs/laravel.log`
2. Verificar la documentación en `database/README.md`
3. Contactar al equipo de desarrollo

---

**¡El sistema está listo para usar! 🎉**
