#!/bin/bash
#
# Meine persönlichen Shortcuts für AskProAI
# Usage: source scripts/my-shortcuts.sh
#

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

# === SHORTCUTS ===

# Quick MCP Discovery
mcp() {
    echo -e "${BLUE}🤖 MCP Discovery: $1${NC}"
    php artisan mcp:discover "$1" "${@:2}"
}

# Impact Check
impact() {
    echo -e "${BLUE}🔍 Running Impact Analysis...${NC}"
    php artisan analyze:impact --git
}

# Quality Check
quality() {
    echo -e "${BLUE}✨ Running Quality Checks...${NC}"
    composer quality
}

# Quick DB Access
db() {
    mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db
}

# Fix Common Issues
fix-db() {
    echo -e "${GREEN}🔧 Fixing DB Access...${NC}"
    rm -f bootstrap/cache/config.php
    php artisan config:cache
    sudo systemctl restart php8.3-fpm
}

# Update Claude Context
claude-update() {
    echo -e "${BLUE}🧠 Updating Claude Context...${NC}"
    echo "Claude, please read:"
    echo "- CLAUDE_CONTEXT_SUMMARY.md"
    echo "- BEST_PRACTICES_IMPLEMENTATION.md" 
    echo "- Any files modified today"
}

# Show Data Flows
flows() {
    php artisan dataflow:list "${@}"
}

# Import Retell Calls
import-calls() {
    echo -e "${GREEN}📞 Importing Retell Calls...${NC}"
    php artisan horizon &
    sleep 2
    echo "Now click 'Anrufe abrufen' in admin panel"
}

# Full System Check
check-all() {
    echo -e "${BLUE}🏥 Full System Health Check...${NC}"
    php artisan health:check
    php artisan mcp:health
    php artisan docs:health
    composer quality
}

# === ALIASES ===
alias art='php artisan'
alias tinker='php artisan tinker'
alias test='php artisan test --parallel'
alias horizon='php artisan horizon'
alias fresh='php artisan migrate:fresh --seed'
alias docs='php artisan docs:check-updates'

# === HELP ===
my-help() {
    echo "🚀 AskProAI Shortcuts:"
    echo "  mcp 'task'     - Find best MCP server"
    echo "  impact         - Run impact analysis"
    echo "  quality        - Run all quality checks"
    echo "  db             - Quick database access"
    echo "  fix-db         - Fix database access issues"
    echo "  claude-update  - Update Claude's context"
    echo "  flows          - Show data flows"
    echo "  import-calls   - Import Retell calls"
    echo "  check-all      - Full system check"
    echo ""
    echo "Aliases: art, tinker, test, horizon, fresh, docs"
}

echo -e "${GREEN}✅ Shortcuts loaded! Type 'my-help' for commands${NC}"