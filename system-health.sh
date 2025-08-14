#!/bin/bash

# Script de monitoreo de salud del sistema
# Para uso diario - combina verificación de servicios y diagnóstico rápido

echo "🏥 MONITOREO DE SALUD DEL SISTEMA MITSUI"
echo "========================================"
echo ""

echo "🔧 1. VERIFICANDO CONFIGURACIÓN DE SERVICIOS..."
echo "------------------------------------------------"
php artisan services:verify --quick

echo ""
echo "🔍 2. DIAGNÓSTICO RÁPIDO DE PROBLEMAS CRÍTICOS..."
echo "-------------------------------------------------"
php artisan system:diagnose --validation --jobs

echo ""
echo "📊 3. ESTADO ACTUAL DE LA COLA..."
echo "--------------------------------"
echo "Jobs pendientes: $(php artisan tinker --execute='echo DB::table(\"jobs\")->count();')"
echo "Jobs fallidos (24h): $(php artisan tinker --execute='echo DB::table(\"failed_jobs\")->where(\"failed_at\", \">=\", now()->subDay())->count();')"

echo ""
echo "💡 COMANDOS ÚTILES:"
echo "  ./diagnose-system.sh fix    # Reparar problemas automáticamente"
echo "  php artisan queue:work      # Procesar cola de jobs"
echo "  php artisan queue:restart   # Reiniciar workers"
echo ""
echo "✅ Monitoreo completado!"