@echo off
echo ===== Ejecutando tests para C4C Customer Service =====

echo.
echo === Tests unitarios con mock ===
php artisan test --filter=CustomerServiceMockTest

echo.
echo === Tests unitarios con llamadas reales ===
php artisan test --filter=Tests\Unit\C4C\CustomerServiceTest

echo.
echo === Tests de integración con API ===
php artisan test --filter=Tests\Feature\C4C\CustomerServiceTest

echo.
echo === Prueba con comando Artisan (DNI) ===
php artisan c4c:find-customer DNI 40359482

echo.
echo === Prueba con comando Artisan (RUC) ===
php artisan c4c:find-customer RUC 20558638223

echo.
echo === Prueba con comando Artisan (CE) ===
php artisan c4c:find-customer CE 73532531

echo.
echo === Prueba con comando Artisan (PASSPORT) ===
php artisan c4c:find-customer PASSPORT 37429823

echo.
echo ===== Pruebas completadas =====
