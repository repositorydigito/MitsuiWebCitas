#!/bin/bash

# Script wrapper para verificar servicios C4C y SAP
# Uso: ./check-services.sh [quick|detailed]

echo "🔍 VERIFICADOR DE SERVICIOS MITSUI"
echo "=================================="
echo ""

case "$1" in
    "quick"|"q")
        echo "⚡ Ejecutando verificación rápida..."
        php artisan services:verify --quick
        ;;
    "detailed"|"d")
        echo "🔍 Ejecutando verificación detallada..."
        php artisan services:verify --detailed
        ;;
    "help"|"h"|"-h"|"--help")
        echo "Uso: ./check-services.sh [opciones]"
        echo ""
        echo "Opciones:"
        echo "  quick, q      Verificación rápida (solo configuración y WSDL locales)"
        echo "  detailed, d   Verificación detallada (incluye URLs y Content-Types)"
        echo "  help, h       Mostrar esta ayuda"
        echo ""
        echo "Sin opciones:   Verificación completa con pruebas de conectividad"
        echo ""
        echo "Ejemplos:"
        echo "  ./check-services.sh          # Verificación completa"
        echo "  ./check-services.sh quick    # Solo configuración"
        echo "  ./check-services.sh detailed # Información completa"
        ;;
    *)
        echo "🌐 Ejecutando verificación completa con pruebas de conectividad..."
        php artisan services:verify
        ;;
esac

echo ""
echo "✅ Verificación completada!"