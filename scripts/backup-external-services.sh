#!/bin/bash

#############################################
# External Services State Backup
# Purpose: Backup Retell.ai & Cal.com configurations
# Created: 2025-10-25
#############################################

set -euo pipefail

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="/var/www/api-gateway"
BACKUP_DIR="${1:-/var/www/backups/external-services-$(date +%Y%m%d_%H%M%S)}"
LOG_FILE="${BACKUP_DIR}/backup.log"

# Load environment
if [ -f "${APP_ROOT}/.env" ]; then
    export $(grep -v '^#' "${APP_ROOT}/.env" | xargs)
else
    echo -e "${RED}ERROR: .env file not found${NC}"
    exit 1
fi

# Create backup directory
mkdir -p "${BACKUP_DIR}"/{retell,calcom,database-exports}

# Logging
log() {
    echo -e "${GREEN}[$(date +"%H:%M:%S")]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   External Services State Backup         ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
echo ""

# ========================================
# 1. Retell.ai State Backup
# ========================================
log "Backing up Retell.ai configurations..."

# Get current agent configuration
if [ -n "${RETELL_TOKEN:-}" ] && [ -n "${RETELL_AGENT_ID:-}" ]; then
    info "Fetching Retell agent: ${RETELL_AGENT_ID}"

    curl -s -X GET "https://api.retellai.com/get-agent/${RETELL_AGENT_ID}" \
        -H "Authorization: Bearer ${RETELL_TOKEN}" \
        > "${BACKUP_DIR}/retell/agent_${RETELL_AGENT_ID}.json"

    if [ $? -eq 0 ]; then
        log "✓ Retell agent configuration backed up"
    else
        error "Failed to backup Retell agent"
    fi

    # List all agents
    curl -s -X GET "https://api.retellai.com/list-agents" \
        -H "Authorization: Bearer ${RETELL_TOKEN}" \
        > "${BACKUP_DIR}/retell/all_agents.json"

    # Get phone numbers
    curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
        -H "Authorization: Bearer ${RETELL_TOKEN}" \
        > "${BACKUP_DIR}/retell/phone_numbers.json"

    log "✓ Retell.ai state exported"
else
    error "Retell credentials not found in .env"
fi

# ========================================
# 2. Cal.com State Backup
# ========================================
log "Backing up Cal.com configurations..."

if [ -n "${CALCOM_API_KEY:-}" ]; then
    info "Fetching Cal.com event types"

    # Get event types
    curl -s -X GET "${CALCOM_BASE_URL}/event-types" \
        -H "Authorization: Bearer ${CALCOM_API_KEY}" \
        -H "cal-api-version: ${CALCOM_API_VERSION}" \
        > "${BACKUP_DIR}/calcom/event_types.json"

    # Get schedules
    curl -s -X GET "${CALCOM_BASE_URL}/schedules" \
        -H "Authorization: Bearer ${CALCOM_API_KEY}" \
        -H "cal-api-version: ${CALCOM_API_VERSION}" \
        > "${BACKUP_DIR}/calcom/schedules.json"

    # Get team information
    curl -s -X GET "${CALCOM_BASE_URL}/teams/${CALCOM_TEAM_SLUG}" \
        -H "Authorization: Bearer ${CALCOM_API_KEY}" \
        -H "cal-api-version: ${CALCOM_API_VERSION}" \
        > "${BACKUP_DIR}/calcom/team_info.json"

    log "✓ Cal.com state exported"
else
    error "Cal.com credentials not found in .env"
fi

# ========================================
# 3. Database Configuration Exports
# ========================================
log "Exporting critical database configurations..."

mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -D "${DB_DATABASE}" -e "
    SELECT * FROM retell_agents;
" > "${BACKUP_DIR}/database-exports/retell_agents.txt" 2>/dev/null || true

mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -D "${DB_DATABASE}" -e "
    SELECT * FROM phone_numbers;
" > "${BACKUP_DIR}/database-exports/phone_numbers.txt" 2>/dev/null || true

mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -D "${DB_DATABASE}" -e "
    SELECT * FROM calcom_event_mappings;
" > "${BACKUP_DIR}/database-exports/calcom_event_mappings.txt" 2>/dev/null || true

mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -D "${DB_DATABASE}" -e "
    SELECT * FROM calcom_host_mappings;
" > "${BACKUP_DIR}/database-exports/calcom_host_mappings.txt" 2>/dev/null || true

log "✓ Database configurations exported"

# ========================================
# 4. Create metadata
# ========================================
cat > "${BACKUP_DIR}/metadata.json" << EOF
{
    "backup_type": "external-services-state",
    "backup_date": "$(date -Iseconds)",
    "retell_agent_id": "${RETELL_AGENT_ID:-}",
    "calcom_team_slug": "${CALCOM_TEAM_SLUG:-}",
    "components": {
        "retell_agent": $([ -f "${BACKUP_DIR}/retell/agent_${RETELL_AGENT_ID}.json" ] && echo "true" || echo "false"),
        "calcom_event_types": $([ -f "${BACKUP_DIR}/calcom/event_types.json" ] && echo "true" || echo "false"),
        "database_exports": true
    }
}
EOF

# ========================================
# 5. Create restoration guide
# ========================================
cat > "${BACKUP_DIR}/RESTORE_GUIDE.md" << 'EOF'
# External Services State Restoration Guide

## Retell.ai Restoration

### 1. Restore Agent Configuration
```bash
AGENT_ID="your_agent_id"
curl -X POST "https://api.retellai.com/update-agent/${AGENT_ID}" \
    -H "Authorization: Bearer ${RETELL_TOKEN}" \
    -H "Content-Type: application/json" \
    -d @retell/agent_${AGENT_ID}.json
```

### 2. Update Phone Number Mapping
- Review `retell/phone_numbers.json`
- Update phone number assignments via Retell dashboard or API

## Cal.com Restoration

### 1. Event Types
- Event types are usually preserved in Cal.com
- Use `calcom/event_types.json` for reference
- Re-create manually if needed

### 2. Team Configuration
- Review `calcom/team_info.json`
- Verify team slug and memberships

## Database Configuration

### 1. Import Database Mappings
```bash
# Review exports first
cat database-exports/calcom_event_mappings.txt
cat database-exports/calcom_host_mappings.txt

# Import via MySQL if needed
mysql -u user -p database < sql_export.sql
```

## Verification

After restoration:
1. Test Retell agent via phone call
2. Check Cal.com availability queries
3. Verify database mappings are correct
4. Test end-to-end appointment booking

EOF

# Summary
echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   External Services Backup Complete      ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}Backup Location:${NC} ${BACKUP_DIR}"
echo -e "${BLUE}Restore Guide:${NC} ${BACKUP_DIR}/RESTORE_GUIDE.md"
echo ""
log "External services backup completed successfully!"
