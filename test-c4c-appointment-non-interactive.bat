@echo off
echo ===== Pruebas no interactivas del servicio de citas de C4C =====

echo.
echo === Creando cita con servicio mock ===
php artisan c4c:test-appointment create --mock < test-appointment-input.txt

echo.
echo ===== Pruebas completadas =====
