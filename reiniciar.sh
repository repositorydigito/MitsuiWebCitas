#!/bin/bash

# Colores para mejorar la salida
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # Sin color

# Buscar todos los servicios que coincidan con el patrón "laravel-queue@*.service"
SERVICES=$(systemctl list-units --type=service | grep -E 'laravel-queue@.*\.service' | awk '{print $1}')

# Verificar si hay servicios activos
if [ -z "$SERVICES" ]; then
  echo -e "${YELLOW}No se encontraron workers de Laravel activos.${NC}"
  exit 0
fi

echo -e "${GREEN}Reiniciando workers de Laravel...${NC}"

# Reiniciar cada servicio encontrado
for SERVICE in $SERVICES; do
  echo -e "${YELLOW}Reiniciando: $SERVICE${NC}"
  sudo systemctl restart "$SERVICE"
  
  # Verificar si el reinicio fue exitoso
  if systemctl is-active --quiet "$SERVICE"; then
    echo -e "${GREEN}✔ $SERVICE reiniciado correctamente.${NC}"
  else
    echo -e "${RED}✗ Error al reiniciar $SERVICE.${NC}"
  fi
done

echo -e "${GREEN}Proceso completado.${NC}"
