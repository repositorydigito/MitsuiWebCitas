#!/bin/bash

# Define un umbral para considerar una respuesta como "lenta" (en segundos)
SLOW_THRESHOLD=2.0 # Aumentado ligeramente el umbral

# Función para leer un valor del archivo .env de forma segura
get_env_var() {
    local var=$(grep "^$1=" .env | cut -d'=' -f2- | sed 's/^[ 	]*//;s/[ 	]*$//')
    echo "$var" | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

# Función para probar un endpoint
test_endpoint() {
    local name="$1"
    local url="$2"
    local user="$3"
    local pass="$4"
    local type="$5" # WSDL, ODATA, WEB

    if [ -z "$url" ]; then
        printf "% -35s \e[31m[NO CONFIGURADO]\e[0m\n" "Endpoint: $name"
        return
    fi

    local final_url="$url"
    # Para WSDLs, a menudo se necesita ?wsdl para obtener la definición
    if [ "$type" == "WSDL" ] && [[ "$url" != *"?wsdl"* ]]; then
        if [[ "$url" == *"?"* ]]; then
            final_url="${url}&wsdl"
        else
            final_url="${url}?wsdl"
        fi
    fi
    
    # Construir el comando curl
    local curl_cmd="curl --connect-timeout 15 -s -o /dev/null -w '%{http_code} %{time_total}'"
    
    # Añadir autenticación si se proporciona
    if [ -n "$user" ] && [ -n "$pass" ]; then
        # Escapar caracteres especiales en usuario y contraseña para eval
        user_escaped=$(printf '%s\n' "$user" | sed "s/'/'\\''/g")
        pass_escaped=$(printf '%s\n' "$pass" | sed "s/'/'\\''/g")
        curl_cmd="$curl_cmd -u '$user_escaped:$pass_escaped'"
    fi

    # Para OData, es una buena práctica pedir JSON para evitar HTML
    if [ "$type" == "ODATA" ]; then
        curl_cmd="$curl_cmd -H 'Accept: application/json'"
    fi

    # Escapar la URL final para eval
    final_url_escaped=$(printf '%s\n' "$final_url" | sed "s/'/'\\''/g")
    curl_cmd="$curl_cmd '$final_url_escaped'"

    # Ejecutar el comando
    response=$(eval $curl_cmd)
    
    http_code=$(echo $response | cut -d' ' -f1)
    time_total=$(echo $response | cut -d' ' -f2 | sed 's/,/./')

    printf "% -35s" "Endpoint: $name"

    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 400 ]; then
        if (( $(echo "$time_total > $SLOW_THRESHOLD" | bc -l) )); then
            printf "\e[33m[LENTO]\e[0m   - Código: %s, Tiempo: %s\n" "$http_code" "${time_total}s"
        else
            printf "\e[32m[ACTIVO]\e[0m  - Código: %s, Tiempo: %s\n" "$http_code" "${time_total}s"
        fi
    elif [ "$http_code" -eq 000 ]; then
         printf "\e[31m[FALLIDO]\e[0m - No se pudo conectar (Timeout/DNS)\n"
    else
        printf "\e[31m[FALLIDO]\e[0m - Código HTTP: %s, Tiempo: %s\n" "$http_code" "${time_total}s"
    fi
}

echo "Iniciando la validación de endpoints (v2)..."
echo "--------------------------------------------------"

# Web App URL
test_endpoint "APP_URL" "$(get_env_var APP_URL)" "" "" "WEB"

# WSDL Services (credenciales C4C genéricas)
c4c_user=$(get_env_var C4C_USERNAME)
c4c_pass=$(get_env_var C4C_PASSWORD)
test_endpoint "C4C_CUSTOMER_WSDL" "$(get_env_var C4C_CUSTOMER_WSDL)" "$c4c_user" "$c4c_pass" "WSDL"
test_endpoint "C4C_APPOINTMENT_WSDL" "$(get_env_var C4C_APPOINTMENT_WSDL)" "$c4c_user" "$c4c_pass" "WSDL"
test_endpoint "C4C_APPOINTMENT_QUERY_WSDL" "$(get_env_var C4C_APPOINTMENT_QUERY_WSDL)" "$c4c_user" "$c4c_pass" "WSDL"

# WSDL Service (credenciales de Oferta)
offer_user=$(get_env_var C4C_OFFER_USERNAME)
offer_pass=$(get_env_var C4C_OFFER_PASSWORD)
test_endpoint "C4C_OFFER_WSDL" "$(get_env_var C4C_OFFER_WSDL)" "$offer_user" "$offer_pass" "WSDL"

# WSDL Service (credenciales SAP 3P)
sap_user=$(get_env_var SAP_3P_USUARIO)
sap_pass=$(get_env_var SAP_3P_PASSWORD)
test_endpoint "SAP_3P_WSDL_URL" "$(get_env_var SAP_3P_WSDL_URL)" "$sap_user" "$sap_pass" "WSDL"

# OData Services
avail_user=$(get_env_var C4C_AVAILABILITY_USERNAME)
avail_pass=$(get_env_var C4C_AVAILABILITY_PASSWORD)
test_endpoint "C4C_AVAILABILITY_BASE_URL" "$(get_env_var C4C_AVAILABILITY_BASE_URL)" "$avail_user" "$avail_pass" "ODATA"

prod_user=$(get_env_var C4C_PRODUCTS_USERNAME)
prod_pass=$(get_env_var C4C_PRODUCTS_PASSWORD)
test_endpoint "C4C_PRODUCTS_URL" "$(get_env_var C4C_PRODUCTS_URL)" "$prod_user" "$prod_pass" "ODATA"

vehicles_user=$(get_env_var C4C_VEHICLES_USERNAME)
vehicles_pass=$(get_env_var C4C_VEHICLES_PASSWORD)
test_endpoint "C4C_VEHICLES_URL" "$(get_env_var C4C_VEHICLES_URL)" "$vehicles_user" "$vehicles_pass" "ODATA"

echo "--------------------------------------------------"
echo "Validación completada."