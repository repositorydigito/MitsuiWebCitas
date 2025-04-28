@echo off
echo ===== Pruebas del servicio de citas de C4C =====

echo.
echo === Creando cita con servicio mock ===
php artisan c4c:test-appointment create --mock

echo.
echo === Actualizando cita con servicio mock ===
php artisan c4c:test-appointment update --mock

echo.
echo === Eliminando cita con servicio mock ===
php artisan c4c:test-appointment delete --mock

echo.
echo === Creando cita con servicio real ===
php artisan c4c:test-appointment create

echo.
echo === Actualizando cita con servicio real ===
php artisan c4c:test-appointment update

echo.
echo === Eliminando cita con servicio real ===
php artisan c4c:test-appointment delete

echo.
echo ===== Pruebas completadas =====
