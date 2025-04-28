@echo off
echo ===== Pruebas completas de C4C =====

echo.
echo === Generando datos de prueba ===
php artisan c4c:generate-test-data 5

echo.
echo === Probando todos los tipos de documentos con mock ===
php artisan c4c:test-all-documents --mock

echo.
echo === Probando todos los tipos de documentos con servicio real ===
php artisan c4c:test-all-documents --real

echo.
echo === Prueba específica de RUC 20605414410 con mock ===
php artisan c4c:find-customer RUC 20605414410 --mock

echo.
echo === Prueba específica de RUC 20605414410 con servicio real ===
php artisan c4c:find-customer RUC 20605414410 --real

echo.
echo === Prueba específica de RUC 20558638223 con mock ===
php artisan c4c:find-customer RUC 20558638223 --mock

echo.
echo === Prueba específica de RUC 20558638223 con servicio real ===
php artisan c4c:find-customer RUC 20558638223 --real

echo.
echo ===== Pruebas completadas =====
