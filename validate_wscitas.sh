#!/bin/bash

# Comprehensive WSCitas Service Validation Script
# Author: Augment Agent
# Date: 2025-08-20
# Description: Complete validation of WSCitas SOAP service functionality

set -euo pipefail  # Exit on error, undefined vars, pipe failures

echo "🔍 COMPREHENSIVE WSCITAS SERVICE VALIDATION"
echo "============================================="

# Color definitions for output formatting
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly NC='\033[0m' # No Color

# Global counters for summary
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0
WARNING_CHECKS=0

# Test client ID and expected plate for validation
readonly TEST_CLIENT_ID="1200041395"
readonly TEST_PLATE="F2W-066"

# Function to display test results with counters
show_result() {
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
    else
        echo -e "${RED}❌ $2${NC}"
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
    fi
}

# Function to display warnings
show_warning() {
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    echo -e "${YELLOW}⚠️  $1${NC}"
    WARNING_CHECKS=$((WARNING_CHECKS + 1))
}

# Function to display informational messages
show_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Function to display section headers
show_section() {
    echo ""
    echo -e "${PURPLE}$1${NC}"
    echo -e "${PURPLE}$(echo "$1" | sed 's/./─/g')${NC}"
}

# Function to validate environment configuration
validate_environment() {
    show_section "1️⃣  SERVICE CONFIGURATION VALIDATION"

    # Check .env file existence
    if [ -f ".env" ]; then
        show_result 0 ".env file found"

        # Validate critical C4C credentials
        if grep -q "^C4C_USERNAME=" .env; then
            local c4c_user
            c4c_user=$(grep "^C4C_USERNAME=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
            if [ -n "$c4c_user" ]; then
                show_result 0 "C4C_USERNAME configured: $c4c_user"
            else
                show_result 1 "C4C_USERNAME is empty"
            fi
        else
            show_result 1 "C4C_USERNAME not found in .env"
        fi

        if grep -q "^C4C_PASSWORD=" .env; then
            local c4c_pass
            c4c_pass=$(grep "^C4C_PASSWORD=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
            if [ -n "$c4c_pass" ]; then
                show_result 0 "C4C_PASSWORD configured (length: ${#c4c_pass})"
            else
                show_result 1 "C4C_PASSWORD is empty"
            fi
        else
            show_result 1 "C4C_PASSWORD not found in .env"
        fi

        # Check XML logging configuration
        if grep -q "^VEHICULOS_WEBSERVICE_LOG_XML=true" .env; then
            show_result 0 "XML logging enabled"
        else
            show_warning "XML logging disabled - enable for debugging: VEHICULOS_WEBSERVICE_LOG_XML=true"
        fi

        # Check Laravel environment
        if grep -q "^APP_ENV=" .env; then
            local app_env
            app_env=$(grep "^APP_ENV=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
            show_info "Laravel environment: $app_env"
        fi

    else
        show_result 1 ".env file not found"
        echo -e "${RED}Critical error: Cannot proceed without .env file${NC}"
        exit 1
    fi
}

# Function to validate WSDL files
validate_wsdl() {
    show_section "2️⃣  WSDL FILE VALIDATION"

    local wsdl_local="storage/wsdl/wscitas.wsdl"

    if [ -f "$wsdl_local" ]; then
        show_result 0 "Local WSDL file found: $wsdl_local"

        # Extract SOAP endpoint from WSDL
        local endpoint
        endpoint=$(grep -o 'location="[^"]*' "$wsdl_local" | cut -d'"' -f2 | head -1)

        if [ -n "$endpoint" ]; then
            show_result 0 "SOAP endpoint extracted: $endpoint"

            # Verify production environment
            if [[ "$endpoint" == *"my330968.crm.ondemand.com"* ]]; then
                show_result 0 "Endpoint points to PRODUCTION (my330968)"
            else
                show_warning "Endpoint does NOT point to production: $endpoint"
            fi

            # Verify correct service
            if [[ "$endpoint" == *"wscitas"* ]]; then
                show_result 0 "Endpoint is WSCitas service"
            else
                show_result 1 "Endpoint is NOT WSCitas service"
            fi

            # Store endpoint for later use
            SOAP_ENDPOINT="$endpoint"
        else
            show_result 1 "Could not extract endpoint from WSDL"
            SOAP_ENDPOINT=""
        fi

        # Validate WSDL file integrity
        local wsdl_size
        wsdl_size=$(wc -c < "$wsdl_local" 2>/dev/null || echo "0")

        if [ "$wsdl_size" -gt 5000 ]; then
            show_result 0 "WSDL file size valid: $wsdl_size bytes"
        elif [ "$wsdl_size" -gt 1000 ]; then
            show_warning "WSDL file size small: $wsdl_size bytes"
        else
            show_result 1 "WSDL file appears corrupted or empty: $wsdl_size bytes"
        fi

        # Validate WSDL XML structure
        if grep -q "soap:address" "$wsdl_local" && grep -q "wsdl:definitions" "$wsdl_local"; then
            show_result 0 "WSDL XML structure valid"
        else
            show_result 1 "WSDL XML structure invalid or corrupted"
        fi

        # Check for WSCitas specific elements
        if grep -q "ActivityBOVNCitasQuery" "$wsdl_local"; then
            show_result 0 "WSCitas service definition found in WSDL"
        else
            show_result 1 "WSCitas service definition NOT found in WSDL"
        fi

    else
        show_result 1 "Local WSDL file not found: $wsdl_local"
        SOAP_ENDPOINT=""
    fi
}

# Function to test network connectivity
validate_connectivity() {
    show_section "3️⃣  NETWORK CONNECTIVITY TESTS"

    if [ -n "${SOAP_ENDPOINT:-}" ]; then
        show_info "Testing connectivity to: $SOAP_ENDPOINT"

        # Extract hostname from endpoint
        local hostname
        hostname=$(echo "$SOAP_ENDPOINT" | sed 's|https\?://||' | cut -d'/' -f1)

        # DNS resolution test
        if nslookup "$hostname" > /dev/null 2>&1; then
            show_result 0 "DNS resolution successful for: $hostname"
        else
            show_result 1 "DNS resolution failed for: $hostname"
        fi

        # Network connectivity test (ping)
        if ping -c 2 -W 5 "$hostname" > /dev/null 2>&1; then
            show_result 0 "Network connectivity to host: $hostname"
        else
            show_result 1 "Network connectivity failed to host: $hostname"
        fi

        # HTTP connectivity test
        local http_code
        http_code=$(curl -s -o /dev/null -w "%{http_code}" "$SOAP_ENDPOINT" \
                   --max-time 15 --connect-timeout 10 2>/dev/null || echo "000")

        case "$http_code" in
            "200"|"405"|"500")
                show_result 0 "HTTP endpoint responds: $http_code"
                ;;
            "000")
                show_result 1 "HTTP endpoint unreachable (timeout/connection error)"
                ;;
            *)
                show_warning "HTTP endpoint responds with unexpected code: $http_code"
                ;;
        esac

        # SSL certificate test (if HTTPS)
        if [[ "$SOAP_ENDPOINT" == https* ]]; then
            if echo | openssl s_client -connect "$hostname:443" -servername "$hostname" \
               > /dev/null 2>&1; then
                show_result 0 "SSL certificate valid"
            else
                show_result 1 "SSL certificate invalid or connection failed"
            fi
        fi

    else
        show_warning "No SOAP endpoint available for connectivity testing"
    fi
}

# Function to validate Laravel configuration
validate_laravel() {
    show_section "4️⃣  LARAVEL CONFIGURATION VALIDATION"

    # Check if Laravel is functional
    if php artisan --version > /dev/null 2>&1; then
        local laravel_version
        laravel_version=$(php artisan --version 2>/dev/null | head -1)
        show_result 0 "Laravel functional: $laravel_version"

        # Test database connectivity
        if php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_OK';" 2>/dev/null | grep -q "DB_OK"; then
            show_result 0 "Database connection successful"
        else
            show_warning "Database connection failed or not configured"
        fi

        # Validate C4C service configuration
        local c4c_config
        c4c_config=$(php artisan tinker --execute="echo config('c4c.services.appointment.query_wsdl') ?? 'NULL';" 2>/dev/null | tail -1)

        if [ "$c4c_config" != "NULL" ] && [ -n "$c4c_config" ]; then
            show_result 0 "C4C appointment service configured: $c4c_config"
        else
            show_result 1 "C4C appointment service configuration missing"
        fi

        # Check if AppointmentService class exists
        if php artisan tinker --execute="class_exists('App\\Services\\C4C\\AppointmentService') ? print('EXISTS') : print('MISSING');" 2>/dev/null | grep -q "EXISTS"; then
            show_result 0 "AppointmentService class found"
        else
            show_result 1 "AppointmentService class missing"
        fi

        # Validate C4C client configuration
        local c4c_base_url
        c4c_base_url=$(php artisan tinker --execute="echo config('c4c.base_url') ?? 'NULL';" 2>/dev/null | tail -1)

        if [ "$c4c_base_url" != "NULL" ] && [ -n "$c4c_base_url" ]; then
            show_result 0 "C4C base URL configured: $c4c_base_url"
        else
            show_result 1 "C4C base URL configuration missing"
        fi

    else
        show_result 1 "Laravel not functional or PHP not available"
        echo -e "${RED}Critical error: Cannot proceed without functional Laravel${NC}"
        return 1
    fi
}

# Function to test WSCitas service functionality
validate_service_functionality() {
    show_section "5️⃣  SERVICE FUNCTIONALITY TESTS"

    show_info "Executing WSCitas service test with client ID: $TEST_CLIENT_ID"

    # Clear logs before test to get clean results
    local log_file="storage/logs/laravel.log"
    if [ -f "$log_file" ]; then
        # Backup current log and create fresh one for test
        cp "$log_file" "${log_file}.backup.$(date +%s)" 2>/dev/null || true
        > "$log_file" 2>/dev/null || true
    fi

    # Execute the Laravel command
    local command_output
    local command_exit_code

    command_output=$(timeout 60 php artisan c4c:query-appointments "$TEST_CLIENT_ID" --real 2>&1)
    command_exit_code=$?

    if [ $command_exit_code -eq 0 ]; then
        show_result 0 "Laravel command executed successfully"

        # Parse appointment count from output
        local appointments_count=0
        if echo "$command_output" | grep -q "Se encontraron.*citas"; then
            appointments_count=$(echo "$command_output" | grep -o "Se encontraron [0-9]*" | grep -o "[0-9]*" | head -1)
            show_result 0 "Appointments found: $appointments_count"
        elif echo "$command_output" | grep -q "Found.*appointments"; then
            appointments_count=$(echo "$command_output" | grep -o "Found [0-9]*" | grep -o "[0-9]*" | head -1)
            show_result 0 "Appointments found: $appointments_count"
        else
            show_warning "No appointments found or unexpected response format"
        fi

        # Check for specific test vehicle plate
        if echo "$command_output" | grep -q "$TEST_PLATE"; then
            show_result 0 "Test vehicle plate ($TEST_PLATE) found in results"
        else
            show_warning "Test vehicle plate ($TEST_PLATE) NOT found in results"
        fi

        # Validate appointment data structure
        if echo "$command_output" | grep -q "appointment_status\|Estado\|Status"; then
            show_result 0 "Appointment data contains status information"
        else
            show_warning "Appointment data missing status information"
        fi

        if echo "$command_output" | grep -q "scheduled_start_date\|Fecha\|Date"; then
            show_result 0 "Appointment data contains date information"
        else
            show_warning "Appointment data missing date information"
        fi

    elif [ $command_exit_code -eq 124 ]; then
        show_result 1 "Command timed out after 60 seconds"
    else
        show_result 1 "Command failed with exit code: $command_exit_code"
        show_info "Command output:"
        echo -e "${CYAN}$command_output${NC}"
    fi

    # Store command output for later analysis
    LAST_COMMAND_OUTPUT="$command_output"
}

# Function to validate XML responses and detect errors
validate_xml_and_errors() {
    show_section "6️⃣  XML RESPONSE VALIDATION & ERROR DETECTION"

    local log_file="storage/logs/laravel.log"

    if [ -f "$log_file" ]; then
        show_result 0 "Laravel log file found"

        # Check for recent WSCitas activity
        local recent_wscitas_logs
        recent_wscitas_logs=$(tail -200 "$log_file" | grep -c "WSCitas\|AppointmentService\|ActivityBOVNCitasQuery" 2>/dev/null || echo "0")

        if [ "$recent_wscitas_logs" -gt 0 ]; then
            show_result 0 "Recent WSCitas activity found: $recent_wscitas_logs log entries"
        else
            show_warning "No recent WSCitas activity found in logs"
        fi

        # Validate SOAP XML responses
        local soap_responses
        soap_responses=$(tail -300 "$log_file" | grep -c "soap-env:Envelope\|soap:Envelope" 2>/dev/null || echo "0")

        if [ "$soap_responses" -gt 0 ]; then
            show_result 0 "SOAP XML responses found: $soap_responses"

            # Check for WSCitas specific XML structure
            if tail -300 "$log_file" | grep -q "ActivityBOVNCitasQuery"; then
                show_result 0 "WSCitas XML structure validated (ActivityBOVNCitasQuery found)"
            else
                show_warning "WSCitas XML structure not confirmed in recent responses"
            fi
        else
            show_warning "No SOAP XML responses found in recent logs"
        fi

        # Error detection - HTTP errors
        local http_errors
        http_errors=$(tail -200 "$log_file" | grep -c "HTTP Error\|HTTP.*[45][0-9][0-9]" 2>/dev/null || echo "0")

        if [ "$http_errors" -eq 0 ]; then
            show_result 0 "No HTTP errors detected"
        else
            show_result 1 "HTTP errors detected: $http_errors"
        fi

        # Error detection - SOAP faults
        local soap_faults
        soap_faults=$(tail -200 "$log_file" | grep -c "soap:Fault\|SoapFault" 2>/dev/null || echo "0")

        if [ "$soap_faults" -eq 0 ]; then
            show_result 0 "No SOAP faults detected"
        else
            show_result 1 "SOAP faults detected: $soap_faults"
        fi

        # Error detection - Authentication failures
        local auth_errors
        auth_errors=$(tail -200 "$log_file" | grep -c -i "authentication\|unauthorized\|401\|403" 2>/dev/null || echo "0")

        if [ "$auth_errors" -eq 0 ]; then
            show_result 0 "No authentication errors detected"
        else
            show_result 1 "Authentication errors detected: $auth_errors"
        fi

        # Error detection - cURL errors
        local curl_errors
        curl_errors=$(tail -200 "$log_file" | grep -c "cURL Error\|CURL.*error" 2>/dev/null || echo "0")

        if [ "$curl_errors" -eq 0 ]; then
            show_result 0 "No cURL errors detected"
        else
            show_result 1 "cURL errors detected: $curl_errors"
        fi

        # Error detection - Timeout issues
        local timeout_errors
        timeout_errors=$(tail -200 "$log_file" | grep -c -i "timeout\|timed out" 2>/dev/null || echo "0")

        if [ "$timeout_errors" -eq 0 ]; then
            show_result 0 "No timeout errors detected"
        else
            show_result 1 "Timeout errors detected: $timeout_errors"
        fi

        # XML parsing errors
        local xml_errors
        xml_errors=$(tail -200 "$log_file" | grep -c -i "xml.*error\|parse.*error\|malformed" 2>/dev/null || echo "0")

        if [ "$xml_errors" -eq 0 ]; then
            show_result 0 "No XML parsing errors detected"
        else
            show_result 1 "XML parsing errors detected: $xml_errors"
        fi

    else
        show_result 1 "Laravel log file not found"
    fi
}

# Function to generate comprehensive summary report
generate_summary() {
    show_section "📊 COMPREHENSIVE VALIDATION SUMMARY"

    # Calculate percentages
    local success_rate=0
    if [ $TOTAL_CHECKS -gt 0 ]; then
        success_rate=$(( (PASSED_CHECKS * 100) / TOTAL_CHECKS ))
    fi

    # Display statistics
    echo -e "${CYAN}╔══════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           VALIDATION RESULTS         ║${NC}"
    echo -e "${CYAN}╠══════════════════════════════════════╣${NC}"
    echo -e "${CYAN}║${NC} Total Checks:      ${BLUE}$(printf "%3d" $TOTAL_CHECKS)${NC}              ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} Passed:           ${GREEN}$(printf "%3d" $PASSED_CHECKS)${NC} (${GREEN}$(printf "%3d" $success_rate)%%${NC})      ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} Failed:           ${RED}$(printf "%3d" $FAILED_CHECKS)${NC}              ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} Warnings:         ${YELLOW}$(printf "%3d" $WARNING_CHECKS)${NC}              ${CYAN}║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════╝${NC}"

    echo ""

    # Overall status determination
    local overall_status
    local status_color
    local status_icon

    if [ $FAILED_CHECKS -eq 0 ] && [ $WARNING_CHECKS -eq 0 ]; then
        overall_status="EXCELLENT - All systems operational"
        status_color="$GREEN"
        status_icon="🎉"
    elif [ $FAILED_CHECKS -eq 0 ] && [ $WARNING_CHECKS -le 3 ]; then
        overall_status="GOOD - Minor warnings detected"
        status_color="$GREEN"
        status_icon="✅"
    elif [ $FAILED_CHECKS -le 2 ] && [ $success_rate -ge 80 ]; then
        overall_status="ACCEPTABLE - Some issues need attention"
        status_color="$YELLOW"
        status_icon="⚠️"
    elif [ $success_rate -ge 60 ]; then
        overall_status="PROBLEMATIC - Multiple issues detected"
        status_color="$YELLOW"
        status_icon="🔧"
    else
        overall_status="CRITICAL - Service may not function properly"
        status_color="$RED"
        status_icon="🚨"
    fi

    echo -e "${status_color}${status_icon} WSCitas Service Status: ${overall_status}${NC}"

    # Recommendations based on results
    echo ""
    show_info "Recommendations:"

    if [ $FAILED_CHECKS -gt 0 ]; then
        echo -e "${RED}  • Address failed checks immediately${NC}"
    fi

    if [ $WARNING_CHECKS -gt 0 ]; then
        echo -e "${YELLOW}  • Review warnings for potential improvements${NC}"
    fi

    if [ $FAILED_CHECKS -eq 0 ] && [ $WARNING_CHECKS -eq 0 ]; then
        echo -e "${GREEN}  • Service is operating optimally${NC}"
    fi

    # Log file information
    local log_file="storage/logs/laravel.log"
    if [ -f "$log_file" ]; then
        local log_size
        log_size=$(du -h "$log_file" 2>/dev/null | cut -f1)
        echo -e "${BLUE}  • Log file: $log_file (${log_size})${NC}"
    fi

    echo ""
    echo -e "${PURPLE}Validation completed at: $(date)${NC}"
    echo -e "${PURPLE}Script version: WSCitas Validator v1.0${NC}"
}

# Main execution function
main() {
    # Check if we're in a Laravel project
    if [ ! -f "artisan" ]; then
        echo -e "${RED}Error: This script must be run from the Laravel project root directory${NC}"
        exit 1
    fi

    # Execute all validation functions
    validate_environment
    validate_wsdl
    validate_connectivity
    validate_laravel
    validate_service_functionality
    validate_xml_and_errors
    generate_summary

    # Exit with appropriate code
    if [ $FAILED_CHECKS -eq 0 ]; then
        exit 0
    else
        exit 1
    fi
}

# Execute main function
main "$@"
