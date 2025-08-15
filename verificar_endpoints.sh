#!/bin/bash

# Define un umbral para considerar una respuesta como "lenta" (en segundos)
SLOW_THRESHOLD=1.0

# Función para leer un valor del archivo .env de forma segura
get_env_var() {
    # Lee la variable, quita comentarios y espacios en blanco
    local var=$(grep "^$1=" .env | cut -d'=' -f2- | sed 's/^[ 	]*//;s/[ 	]*$//')
    # Quita las comillas que puedan rodear el valor
    echo "$var" | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'\]//"
}

# Array de nombres de variables de entorno a verificar
ENDPOINT_VARS=(
    "APP_URL"
    "C4C_CUSTOMER_WSDL"
    "C4C_APPOINTMENT_WSDL"
    "C4C_APPOINTMENT_QUERY_WSDL"
    "SAP_3P_WSDL_URL"
    "C4C_AVAILABILITY_BASE_URL"
    "C4C_PRODUCTS_URL"
    "C4C_VEHICLES_URL"
    "C4C_OFFER_WSDL"
)

echo "Iniciando la validación de endpoints..."
echo "----------------------------------------"

for var_name in "${ENDPOINT_VARS[@]}"; do
    url=$(get_env_var "$var_name")

    if [ -z "$url" ]; then
        echo -e "Endpoint: $var_name 	[NO CONFIGURADO]"
        continue
    fi

    # Usar curl para obtener el código de estado HTTP y el tiempo de respuesta
    # Se añade un timeout de 10 segundos para no esperar indefinidamente
    response=$(curl --connect-timeout 10 -s -o /dev/null -w "%{http_code} %{time_total}" "$url")
    
    http_code=$(echo $response | cut -d' ' -f1)
    time_total=$(echo $response | cut -d' ' -f2 | sed 's/,/./') # Asegura punto decimal para 'bc'

    printf "%-35s" "Endpoint: $var_name"

    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 400 ]; then
        if (( $(echo "$time_total > $SLOW_THRESHOLD" | bc -l) )); then
            printf "\e[33m[LENTO]\e[0m 	- Tiempo: %s\n" "${time_total}s"
        else
            printf "\e[32m[ACTIVO]\e[0m  	- Tiempo: %s\n" "${time_total}s"
        fi
    elif [ "$http_code" -eq 000 ]; then
         printf "\e[31m[FALLIDO]\e[0m - No se pudo conectar (Timeout o DNS error)\n"
    else
        printf "\e[31m[FALLIDO]\e[0m - Código HTTP: %s, Tiempo: %s\n" "$http_code" "${time_total}s"
    fi
done

echo "----------------------------------------"
echo "Validación completada."
