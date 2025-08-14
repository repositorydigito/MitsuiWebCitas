#!/bin/bash

# Script para validar flujo completo de appointments y ofertas
# Uso: ./validate-appointments.sh [opciones]

echo "🔍 VALIDADOR DE FLUJO DE CITAS Y OFERTAS MITSUI"
echo "============================================="
echo ""

case "$1" in
    "specific"|"s")
        if [ -z "$2" ]; then
            echo "❌ Error: Proporciona el ID del appointment"
            echo "Uso: ./validate-appointments.sh specific 123"
            exit 1
        fi
        echo "🎯 Validando appointment específico ID: $2"
        php artisan appointment:validate --id="$2" --detailed
        ;;
    "recent"|"r")
        hours=${2:-6}
        echo "⏰ Validando appointments de las últimas $hours horas"
        php artisan appointment:validate --hours="$hours"
        ;;
    "detailed"|"d")
        hours=${2:-12}
        echo "🔍 Análisis detallado de las últimas $hours horas"
        php artisan appointment:validate --hours="$hours" --detailed
        ;;
    "fix"|"f")
        hours=${2:-24}
        echo "🔧 Validación con correcciones automáticas ($hours horas)"
        php artisan appointment:validate --hours="$hours" --fix --detailed
        ;;
    "critical"|"c")
        echo "⚠️ Validando solo problemas críticos (últimas 48h)"
        php artisan appointment:validate --hours=48
        ;;
    "help"|"h"|"-h"|"--help")
        echo "Uso: ./validate-appointments.sh [opción] [parámetro]"
        echo ""
        echo "Opciones disponibles:"
        echo "  specific, s [ID]     Validar appointment específico por ID"
        echo "  recent, r [horas]    Validar appointments recientes (default: 6h)"
        echo "  detailed, d [horas]  Análisis detallado (default: 12h)"
        echo "  fix, f [horas]       Validar + correcciones automáticas (default: 24h)"
        echo "  critical, c          Solo problemas críticos (48h)"
        echo "  help, h              Mostrar esta ayuda"
        echo ""
        echo "Sin opciones:          Análisis estándar (últimas 24h)"
        echo ""
        echo "Ejemplos:"
        echo "  ./validate-appointments.sh                    # Análisis estándar"
        echo "  ./validate-appointments.sh specific 125       # Appointment ID 125"
        echo "  ./validate-appointments.sh recent 8           # Últimas 8 horas"
        echo "  ./validate-appointments.sh fix 12             # Corregir últimas 12h"
        echo "  ./validate-appointments.sh detailed           # Análisis detallado"
        ;;
    *)
        echo "📊 Validación estándar (últimas 24 horas)"
        php artisan appointment:validate --hours=24
        ;;
esac

echo ""
echo "✅ Validación completada!"
echo ""
echo "💡 COMANDOS ÚTILES ADICIONALES:"
echo "  php artisan queue:work            # Procesar jobs pendientes"
echo "  php artisan queue:failed          # Ver jobs fallidos"
echo "  php artisan queue:restart         # Reiniciar workers"
echo "  php artisan system:diagnose       # Diagnóstico del sistema"
echo "  php artisan services:verify       # Verificar servicios C4C/SAP"