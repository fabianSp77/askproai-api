#!/bin/bash

# Retell Webhook Monitoring Script

echo "=== RETELL WEBHOOK MONITORING ==="
echo "================================="
echo ""

# Configuration
LOG_FILE="/var/www/api-gateway/storage/logs/laravel.log"
DB_HOST="localhost"
DB_NAME="askproai_db"
DB_USER="askproai_user"
DB_PASS="lkZ57Dju9EDjrMxn"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check database stats
check_database_stats() {
    echo -e "${YELLOW}📊 Database Statistics:${NC}"
    
    # Recent calls
    mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e "
    SELECT 
        COUNT(*) as total_calls,
        COUNT(CASE WHEN created_at >= NOW() - INTERVAL 1 HOUR THEN 1 END) as last_hour,
        COUNT(CASE WHEN created_at >= NOW() - INTERVAL 24 HOUR THEN 1 END) as last_24h,
        COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as with_appointments
    FROM calls;
    " 2>/dev/null
    
    echo ""
    
    # Recent webhooks
    echo -e "${YELLOW}🔔 Recent Webhook Activity:${NC}"
    mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e "
    SELECT 
        provider,
        status,
        COUNT(*) as count,
        MAX(created_at) as last_received
    FROM webhook_logs
    WHERE created_at >= NOW() - INTERVAL 24 HOUR
    GROUP BY provider, status;
    " 2>/dev/null
    
    echo ""
}

# Function to show recent calls
show_recent_calls() {
    echo -e "${YELLOW}📞 Last 5 Calls:${NC}"
    mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e "
    SELECT 
        id,
        retell_call_id,
        from_number,
        extracted_name,
        branch_id,
        created_at
    FROM calls 
    ORDER BY id DESC 
    LIMIT 5;
    " 2>/dev/null
    
    echo ""
}

# Function to check webhook endpoints
check_webhook_endpoints() {
    echo -e "${YELLOW}🌐 Webhook Endpoints Status:${NC}"
    
    # Test each endpoint
    endpoints=(
        "https://api.askproai.de/api/retell/webhook"
        "https://api.askproai.de/api/retell/debug-webhook"
        "https://api.askproai.de/api/retell/enhanced-webhook"
        "https://api.askproai.de/api/test/webhook"
    )
    
    for endpoint in "${endpoints[@]}"; do
        response=$(curl -s -o /dev/null -w "%{http_code}" -X GET "$endpoint" 2>/dev/null)
        if [ "$response" == "405" ] || [ "$response" == "200" ]; then
            echo -e "✅ $endpoint - ${GREEN}Active${NC} (HTTP $response)"
        else
            echo -e "❌ $endpoint - ${RED}Issue${NC} (HTTP $response)"
        fi
    done
    
    echo ""
}

# Function to monitor log file
monitor_logs() {
    echo -e "${YELLOW}📋 Live Log Monitoring (Ctrl+C to stop):${NC}"
    echo "Watching for Retell webhooks..."
    echo ""
    
    tail -f $LOG_FILE | grep -E "(Retell|webhook|ENHANCED|DEBUG)" --color=always
}

# Main menu
while true; do
    echo -e "${YELLOW}Select an option:${NC}"
    echo "1) View database statistics"
    echo "2) Show recent calls"
    echo "3) Check webhook endpoints"
    echo "4) Monitor live logs"
    echo "5) Full system check"
    echo "6) Exit"
    
    read -p "Enter your choice (1-6): " choice
    
    case $choice in
        1)
            check_database_stats
            ;;
        2)
            show_recent_calls
            ;;
        3)
            check_webhook_endpoints
            ;;
        4)
            monitor_logs
            ;;
        5)
            check_database_stats
            show_recent_calls
            check_webhook_endpoints
            ;;
        6)
            echo "Exiting..."
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid choice. Please try again.${NC}"
            ;;
    esac
    
    echo ""
    read -p "Press Enter to continue..."
    clear
done