#!/bin/bash
#
# AskProAI Power Commands - Die Top 10 als Quick Access Script
# Usage: source scripts/askpro-power.sh
# Oder: ./scripts/askpro-power.sh [command]
#

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
PURPLE='\033[0;35m'
NC='\033[0m'

# === TOP 10 POWER COMMANDS ===

# 1. Quick Setup - Neuen Kunden in 5 Minuten
ask-setup() {
    echo -e "${GREEN}🚀 5-Minuten Komplett-Onboarding${NC}"
    
    if [ $# -lt 3 ]; then
        echo "Usage: ask-setup \"Company Name\" \"+49 30 12345678\" \"email@company.de\" [\"Branch Name\"]"
        return 1
    fi
    
    local company="$1"
    local phone="$2"
    local email="$3"
    local branch="${4:-Hauptfiliale}"
    
    php artisan askpro:quick-setup \
        --company="$company" \
        --phone="$phone" \
        --email="$email" \
        --branch="$branch"
}

# 2. Phone Resolution Test
ask-phone() {
    echo -e "${CYAN}📞 Testing Phone Resolution${NC}"
    local phone="${1:-+49 30 12345678}"
    php artisan phone:test-resolution "$phone" --webhook
}

# 3. KPI Dashboard
ask-kpi() {
    echo -e "${PURPLE}📊 Business KPI Dashboard${NC}"
    local company_id="${1:-1}"
    php artisan kpi:dashboard --company-id="$company_id" --format=pretty
}

# 4. Impact Analysis
ask-impact() {
    echo -e "${YELLOW}🔍 Running Impact Analysis${NC}"
    php artisan analyze:impact --git
}

# 5. MCP Discovery
ask-mcp() {
    echo -e "${BLUE}🤖 MCP Auto-Discovery${NC}"
    if [ -z "$1" ]; then
        echo "Usage: ask-mcp \"your task description\""
        return 1
    fi
    php artisan mcp:discover "$1" ${2:+--execute}
}

# 6. Webhook Monitor
ask-webhook() {
    echo -e "${YELLOW}🏥 Webhook Health Monitor${NC}"
    if [ -f "./monitor-webhooks.sh" ]; then
        ./monitor-webhooks.sh
    else
        php artisan retell:analyze-webhooks --last-hour
    fi
}

# 7. Emergency Fix
ask-fix() {
    echo -e "${RED}🚨 Running Emergency Fix Combo${NC}"
    echo "This will:"
    echo "  - Clear config cache"
    echo "  - Restart Horizon"
    echo "  - Clear all caches"
    echo ""
    read -p "Continue? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -f bootstrap/cache/config.php
        php artisan config:cache
        php artisan horizon:terminate
        php artisan horizon &
        php artisan optimize:clear
        echo -e "${GREEN}✅ Emergency fix completed!${NC}"
    fi
}

# 8. Health Check
ask-health() {
    echo -e "${GREEN}✅ Complete System Health Check${NC}"
    php artisan health:check --all
    php artisan mcp:health
    php artisan performance:analyze
    php artisan circuit-breaker:status
}

# 9. Data Flow Debug
ask-flow() {
    case "$1" in
        start)
            echo -e "${BLUE}🔄 Starting Data Flow Tracking${NC}"
            php artisan dataflow:start
            ;;
        list)
            echo -e "${BLUE}🔄 Today's Data Flows${NC}"
            php artisan dataflow:list --today
            ;;
        diagram)
            if [ -z "$2" ]; then
                echo "Usage: ask-flow diagram <correlation-id>"
                return 1
            fi
            php artisan dataflow:diagram "$2"
            ;;
        *)
            echo -e "${BLUE}🔄 Recent Data Flows${NC}"
            php artisan dataflow:list --limit=10
            ;;
    esac
}

# 10. Safe Deploy
ask-deploy() {
    echo -e "${GREEN}🚀 Production-Ready Deployment Check${NC}"
    
    # Run all checks
    echo -e "${YELLOW}Running production readiness test...${NC}"
    php artisan test:production-readiness || { echo -e "${RED}❌ Production test failed!${NC}"; return 1; }
    
    echo -e "${YELLOW}Running impact analysis...${NC}"
    php artisan analyze:impact --git || { echo -e "${RED}❌ Impact analysis failed!${NC}"; return 1; }
    
    echo -e "${YELLOW}Running quality checks...${NC}"
    composer quality || { echo -e "${RED}❌ Quality check failed!${NC}"; return 1; }
    
    echo -e "${GREEN}✅ All checks passed! Ready for deployment.${NC}"
    
    if [ "$1" == "--deploy" ]; then
        echo -e "${YELLOW}Creating backup...${NC}"
        php artisan backup:run --only-db
        
        echo -e "${GREEN}Deploying to production...${NC}"
        ./deploy.sh production --safety-check
    else
        echo "Run 'ask-deploy --deploy' to actually deploy"
    fi
}

# === BONUS COMMANDS ===

# Database quick access
ask-db() {
    echo -e "${CYAN}🗄️ Database Access${NC}"
    mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db
}

# Show recent calls
ask-calls() {
    echo -e "${PURPLE}📞 Recent Calls${NC}"
    mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
    SELECT 
        c.id,
        DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i') as time,
        c.duration_minutes as min,
        cu.name,
        cu.phone,
        co.name as company
    FROM calls c 
    LEFT JOIN customers cu ON c.customer_id = cu.id 
    LEFT JOIN companies co ON c.company_id = co.id 
    WHERE c.created_at > NOW() - INTERVAL 24 HOUR 
    ORDER BY c.created_at DESC 
    LIMIT 20;"
}

# === HELP MENU ===
ask-help() {
    echo -e "${GREEN}🏆 AskProAI Top 10 Power Commands${NC}"
    echo ""
    echo -e "${CYAN}Setup & Onboarding:${NC}"
    echo "  ask-setup \"Company\" \"+49...\" \"email@...\"  - 5-min onboarding"
    echo ""
    echo -e "${CYAN}Debugging & Testing:${NC}"
    echo "  ask-phone [+49...]             - Test phone resolution"
    echo "  ask-flow [start|list|diagram]  - Data flow debugging"
    echo "  ask-webhook                    - Monitor webhooks"
    echo ""
    echo -e "${CYAN}Analytics & Monitoring:${NC}"
    echo "  ask-kpi [company-id]           - Business KPI dashboard"
    echo "  ask-health                     - Complete health check"
    echo "  ask-calls                      - Show recent calls"
    echo ""
    echo -e "${CYAN}Development & Deployment:${NC}"
    echo "  ask-mcp \"task description\"     - MCP auto-discovery"
    echo "  ask-impact                     - Impact analysis"
    echo "  ask-deploy [--deploy]          - Deployment checks"
    echo ""
    echo -e "${CYAN}Emergency & Fixes:${NC}"
    echo "  ask-fix                        - Emergency fix combo"
    echo "  ask-db                         - Database access"
    echo ""
    echo -e "${YELLOW}Type any command to start!${NC}"
}

# === AUTO-COMPLETION ===
if [ -n "$BASH_VERSION" ]; then
    complete -W "ask-setup ask-phone ask-kpi ask-impact ask-mcp ask-webhook ask-fix ask-health ask-flow ask-deploy ask-db ask-calls ask-help" ask
fi

# === MAIN ===
if [ "$0" = "${BASH_SOURCE[0]}" ]; then
    # Script is being executed (not sourced)
    if [ $# -eq 0 ]; then
        ask-help
    else
        # Execute the requested command
        "$@"
    fi
else
    # Script is being sourced
    echo -e "${GREEN}✅ AskProAI Power Commands loaded!${NC}"
    echo -e "Type ${CYAN}ask-help${NC} to see all commands"
fi

# === ALIASES FOR EVEN QUICKER ACCESS ===
alias asetup='ask-setup'
alias aphone='ask-phone'
alias akpi='ask-kpi'
alias aimpact='ask-impact'
alias amcp='ask-mcp'
alias awebhook='ask-webhook'
alias afix='ask-fix'
alias ahealth='ask-health'
alias aflow='ask-flow'
alias adeploy='ask-deploy'
alias adb='ask-db'
alias acalls='ask-calls'