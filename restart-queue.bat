@echo off
echo 🔄 Reiniciando Laravel Queue Worker...
echo.

echo 🛑 Deteniendo workers existentes...
taskkill /F /IM php.exe 2>nul
timeout /t 2 /nobreak >nul

echo 🧹 Limpiando failed jobs...
php artisan queue:flush

echo 🚀 Iniciando nuevo worker...
php artisan queue:start

echo.
echo ✅ Queue worker reiniciado correctamente
pause 