#!/usr/bin/env bash

# -------------------------------------------------------------
# Script: limpiar-laravel.sh
# Descripción: Limpia todas las cachés de Laravel en un solo paso
# Uso: ./limpiar-laravel.sh [ruta/al/proyecto] (opcional)
# -------------------------------------------------------------

# Si se pasa como argumento, usamos la ruta indicada; si no, asumimos el directorio actual
PROJECT_PATH="${1:-$(pwd)}"

echo "➡️  Entrando en el proyecto: $PROJECT_PATH"
cd "$PROJECT_PATH" || { echo "❌ No existe la ruta $PROJECT_PATH"; exit 1; }

echo "🧹 Limpiando caché de aplicación..."
php artisan cache:clear

echo "🧹 Limpiando caché de rutas..."
php artisan route:clear

echo "🧹 Limpiando caché de configuración..."
php artisan config:clear

echo "🧹 Limpiando caché de vistas..."
php artisan view:clear

echo "🚀 Regenerando optimizaciones (opcional pero recomendado)…"
php artisan optimize:clear

echo "✅ ¡Cachés limpias! 👌"
