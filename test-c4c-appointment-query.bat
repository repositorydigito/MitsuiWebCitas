@echo off
echo ===== Pruebas del servicio de consulta de citas de C4C =====

echo.
echo === Consultando citas pendientes con servicio mock ===
php artisan c4c:test-appointment-query pending --customer_id=1270002726 --mock

echo.
echo === Consultando todas las citas con servicio mock ===
php artisan c4c:test-appointment-query all --customer_id=1270002726 --mock

echo.
echo === Consultando citas por placa de vehículo con servicio mock ===
php artisan c4c:test-appointment-query vehicle --vehicle_plate=APP-001 --mock

echo.
echo === Consultando citas por centro con servicio mock ===
php artisan c4c:test-appointment-query center --center_id=M013 --mock

echo.
echo === Consultando citas por rango de fechas con servicio mock ===
php artisan c4c:test-appointment-query date --start_date=%date:~6,4%-%date:~3,2%-%date:~0,2% --end_date=%date:~6,4%-%date:~3,2%-%date:~0,2% --mock

echo.
echo ===== Pruebas completadas =====
