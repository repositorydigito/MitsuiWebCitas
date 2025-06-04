@echo off
echo ===============================================
echo    INSTALACION DE BASE DE DATOS - MITSUI
echo ===============================================
echo.

echo [1/4] Verificando configuracion de base de datos...
echo.
echo Asegurate de que tu archivo .env tenga la configuracion correcta:
echo DB_CONNECTION=mysql
echo DB_HOST=127.0.0.1
echo DB_PORT=3306
echo DB_DATABASE=mitsui_citas
echo DB_USERNAME=tu_usuario
echo DB_PASSWORD=tu_password
echo.
pause

echo [2/4] Ejecutando migraciones (creando tablas)...
php artisan migrate:fresh
if %errorlevel% neq 0 (
    echo ERROR: Las migraciones fallaron. Verifica tu configuracion de base de datos.
    pause
    exit /b 1
)
echo ✓ Migraciones ejecutadas exitosamente
echo.

echo [3/4] Ejecutando seeders (insertando datos iniciales)...
php artisan db:seed
if %errorlevel% neq 0 (
    echo ERROR: Los seeders fallaron.
    pause
    exit /b 1
)
echo ✓ Datos iniciales insertados exitosamente
echo.

echo [4/4] Verificando instalacion...
echo.
echo ===============================================
echo           INSTALACION COMPLETADA
echo ===============================================
echo.
echo Datos creados:
echo - 3 locales (La Molina, San Borja, Surco)
echo - 3 modelos de vehiculos (Outlander, Lancer, Montero)
echo - Anos 2018-2024 para cada modelo
echo - 3 tipos de mantenimiento
echo - 3 servicios adicionales
echo - 2 vehiculos de ejemplo
echo - 3 servicios express
echo - 2 campanas activas
echo - 1 pop-up promocional
echo - Usuario administrador: admin@mitsui.com
echo.
echo Puedes acceder a:
echo - Panel admin: http://localhost:8000/admin
echo - Vehiculos: http://localhost:8000/vehiculos
echo - Campanas: http://localhost:8000/campanas
echo.
echo ===============================================
pause
