#!/bin/bash

# Script wrapper para diagnóstico integral del sistema
# Uso: ./diagnose-system.sh [opciones]

echo "🔍 DIAGNÓSTICO INTEGRAL DEL SISTEMA MITSUI"
echo "=========================================="
echo ""

case "$1" in
    "performance"|"perf"|"p")
        echo "🚀 Ejecutando diagnóstico de RENDIMIENTO"
        php artisan system:diagnose --performance
        ;;
    "validation"|"valid"|"v")
        echo "✅ Ejecutando diagnóstico de VALIDACIÓN..."
        php artisan system:diagnose --validation
        ;;
    "jobs"|"j")
        echo "⚙️  Ejecutando diagnóstico de JOBS..."
        php artisan system:diagnose --jobs
        ;;
    "logic"|"l")
        echo "🧠 Ejecutando diagnóstico de LÓGICA..."
        php artisan system:diagnose --logic
        ;;
    "fix"|"f")
        echo "🔧 Ejecutando diagnóstico COMPLETO con correcciones..."
        php artisan system:diagnose --all --fix
        ;;
    "quick"|"q")
        echo "⚡ Ejecutando diagnóstico RÁPIDO (solo críticos)..."
        php artisan system:diagnose --jobs --validation
        ;;
    "help"|"h"|"-h"|"--help")
        echo "Uso: ./diagnose-system.sh [opciones]"
        echo ""
        echo "Opciones específicas:"
        echo "  performance, perf, p    Analizar rendimiento y lentitud"
        echo "  validation, valid, v    Revisar validación y consistencia"
        echo "  jobs, j                 Analizar jobs y colas"
        echo "  logic, l                Detectar inconsistencias lógicas"
        echo ""
        echo "Opciones combinadas:"
        echo "  fix, f                  Diagnóstico completo + correcciones"
        echo "  quick, q                Diagnóstico rápido (jobs + validación)"
        echo "  help, h                 Mostrar esta ayuda"
        echo ""
        echo "Sin opciones:             Diagnóstico COMPLETO"
        echo ""
        echo "Ejemplos:"
        echo "  ./diagnose-system.sh              # Diagnóstico completo"
        echo "  ./diagnose-system.sh performance  # Solo rendimiento"
        echo "  ./diagnose-system.sh fix          # Completo + reparaciones"
        echo "  ./diagnose-system.sh quick        # Rápido para revisión diaria"
        ;;
    *)
        echo "🌐 Ejecutando diagnóstico COMPLETO..."
        php artisan system:diagnose --all
        ;;
esac

echo ""
echo "✅ Diagnóstico completado!"
echo ""
echo "💡 COMANDOS ÚTILES ADICIONALES:"
echo "  php artisan queue:work            # Procesar jobs en cola"
echo "  php artisan queue:failed          # Ver jobs fallidos"
echo "  php artisan cache:clear           # Limpiar cache"
echo "  ./services-verify.sh              # Verificar servicios C4C/SAP"