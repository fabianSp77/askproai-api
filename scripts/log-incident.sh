#!/bin/bash
# ==============================================================================
# Incident Logger for Backup System
# ==============================================================================
# Purpose: Log and track backup system incidents with severity levels
# Usage: ./log-incident.sh <severity> <category> <title> <description> [resolution] [verification]
# Example: ./log-incident.sh critical backup "Backup failed" "Cron job not running" "Reinstalled cron jobs" "Run: sudo crontab -l | grep backup"
# ==============================================================================

set -euo pipefail

# Configuration
INCIDENT_DB="/var/backups/askproai/incidents.json"
INCIDENT_LOCK="/var/backups/askproai/.incidents.lock"
MAX_INCIDENTS=100  # Keep last 100 incidents

# Parameters
SEVERITY="${1:-unknown}"     # critical | high | medium | low | info
CATEGORY="${2:-general}"     # backup | monitoring | storage | database | automation | email
TITLE="${3:-Untitled}"
DESCRIPTION="${4:-No description}"
RESOLUTION="${5:-}"          # Optional: How it was resolved
VERIFICATION="${6:-}"        # Optional: How to verify the fix works

# Validate severity
case "$SEVERITY" in
    critical|high|medium|low|info) ;;
    *)
        echo "❌ Invalid severity: $SEVERITY (use: critical|high|medium|low|info)"
        exit 1
        ;;
esac

# Validate category
case "$CATEGORY" in
    backup|monitoring|storage|database|automation|email|general) ;;
    *)
        echo "❌ Invalid category: $CATEGORY"
        exit 1
        ;;
esac

# Generate unique ID
INCIDENT_ID="INC-$(date +%Y%m%d%H%M%S)-$(head /dev/urandom | tr -dc A-Za-z0-9 | head -c 6)"
TIMESTAMP=$(date -Iseconds)

# Lock handling
exec 200>"$INCIDENT_LOCK"
flock -x 200

# Initialize incident database if not exists
if [ ! -f "$INCIDENT_DB" ]; then
    echo '{"incidents": [], "stats": {"total": 0, "critical": 0, "high": 0, "medium": 0, "low": 0, "info": 0}}' > "$INCIDENT_DB"
fi

# Create incident JSON
INCIDENT_JSON=$(cat <<EOF
{
  "id": "$INCIDENT_ID",
  "timestamp": "$TIMESTAMP",
  "severity": "$SEVERITY",
  "category": "$CATEGORY",
  "title": "$TITLE",
  "description": "$DESCRIPTION",
  "status": "$([ -z "$RESOLUTION" ] && echo "open" || echo "resolved")",
  "resolution": "$RESOLUTION",
  "verification": "$VERIFICATION",
  "resolved_at": "$([ -z "$RESOLUTION" ] && echo "null" || date -Iseconds)"
}
EOF
)

# Add incident to database using Python for JSON manipulation
python3 <<PYTHON
import json
import sys

try:
    with open('$INCIDENT_DB', 'r') as f:
        data = json.load(f)

    # Add new incident at beginning (newest first)
    new_incident = $INCIDENT_JSON
    data['incidents'].insert(0, new_incident)

    # Update stats
    data['stats']['total'] = len(data['incidents'])
    data['stats']['$SEVERITY'] = data['stats'].get('$SEVERITY', 0) + 1

    # Trim to max incidents
    if len(data['incidents']) > $MAX_INCIDENTS:
        removed = data['incidents'][$MAX_INCIDENTS:]
        data['incidents'] = data['incidents'][:$MAX_INCIDENTS]
        # Adjust stats for removed incidents
        for inc in removed:
            if inc['severity'] in data['stats']:
                data['stats'][inc['severity']] = max(0, data['stats'][inc['severity']] - 1)
            data['stats']['total'] = len(data['incidents'])

    with open('$INCIDENT_DB', 'w') as f:
        json.dump(data, f, indent=2)

    print("✅ Incident logged: $INCIDENT_ID")
    sys.exit(0)
except Exception as e:
    print(f"❌ Failed to log incident: {e}", file=sys.stderr)
    sys.exit(1)
PYTHON

RESULT=$?

# Release lock
flock -u 200

# Create Markdown documentation file for the incident
if [ "$RESULT" -eq 0 ]; then
    MARKDOWN_DIR="/var/www/api-gateway/storage/docs/backup-system/incidents"
    MARKDOWN_FILE="$MARKDOWN_DIR/$INCIDENT_ID.md"

    # Create incidents directory if it doesn't exist
    mkdir -p "$MARKDOWN_DIR"

    # Determine status badge
    if [ -z "$RESOLUTION" ]; then
        STATUS_BADGE="🔄 OPEN"
        STATUS_TEXT="Not yet resolved"
    else
        STATUS_BADGE="✅ RESOLVED"
        STATUS_TEXT="$TIMESTAMP"
    fi

    # Determine severity badge
    case "$SEVERITY" in
        critical) SEVERITY_BADGE="🔴 CRITICAL" ;;
        high) SEVERITY_BADGE="🟠 HIGH" ;;
        medium) SEVERITY_BADGE="🟡 MEDIUM" ;;
        low) SEVERITY_BADGE="🔵 LOW" ;;
        info) SEVERITY_BADGE="🟢 INFO" ;;
    esac

    # Create markdown content
    cat > "$MARKDOWN_FILE" <<MARKDOWN
# Incident Report: $INCIDENT_ID

**Status**: $STATUS_BADGE
**Severity**: $SEVERITY_BADGE
**Category**: $CATEGORY
**Created**: $(date -d "$TIMESTAMP" "+%Y-%m-%d %H:%M:%S %Z" 2>/dev/null || echo "$TIMESTAMP")
**Resolved**: $STATUS_TEXT

---

## Problem Description

**Title**: $TITLE

**Details**:
$DESCRIPTION

---

## Impact Assessment

- **Duration**: $([ -z "$RESOLUTION" ] && echo "Ongoing" || echo "Resolved")
- **Risk Level**: $(echo "$SEVERITY" | awk '{print toupper(substr($0,1,1)) tolower(substr($0,2))}')
- **Systems Affected**: $CATEGORY system
- **Category**: $CATEGORY

---

$(if [ -n "$RESOLUTION" ]; then
cat <<RESOLUTION_SECTION
## Resolution

**Actions Taken**:
$RESOLUTION

---

RESOLUTION_SECTION
fi)

$(if [ -n "$VERIFICATION" ]; then
cat <<VERIFICATION_SECTION
## Verification Steps

Run the following to verify the fix:

\`\`\`bash
$VERIFICATION
\`\`\`

---

VERIFICATION_SECTION
fi)

## Investigation Steps

For AI analysis, consider investigating:

1. **System Logs**:
   \`\`\`bash
   tail -100 /var/log/backup-health-check.log
   tail -100 /var/log/backup-run.log
   \`\`\`

2. **Current System Status**:
   \`\`\`bash
   /var/www/api-gateway/scripts/backup-health-check.sh
   \`\`\`

3. **Related Files**:
   - Incident Database: \`/var/backups/askproai/incidents.json\`
   - Status File: \`/var/www/api-gateway/storage/docs/backup-system/status.json\`

---

## Related Documentation

- **Backup Dashboard**: https://api.askproai.de/docs/backup-system
- **Backup Monitoring System**: \`/var/www/api-gateway/storage/docs/backup-system/BACKUP_MONITORING_SYSTEM_2025-11-02.md\`
- **Health Check Script**: \`/var/www/api-gateway/scripts/backup-health-check.sh\`
- **Incident Logger**: \`/var/www/api-gateway/scripts/log-incident.sh\`

---

## Timeline

| Time | Event |
|------|-------|
| $TIMESTAMP | Incident $([ -z "$RESOLUTION" ] && echo "created" || echo "created and resolved") |

---

**For AI Analysis**:
- Category: $CATEGORY
- Severity: $SEVERITY
- Status: $([ -z "$RESOLUTION" ] && echo "Open - requires investigation" || echo "Resolved - can be used as reference")
- This markdown file can be shared with AI assistants for detailed analysis and troubleshooting recommendations.
MARKDOWN

    echo "📝 Markdown documentation created: $MARKDOWN_FILE"
fi

# Send email for critical/high severity incidents
if [ "$RESULT" -eq 0 ] && [ "$SEVERITY" = "critical" -o "$SEVERITY" = "high" ]; then
    echo "📧 Sending alert email for $SEVERITY incident..."

    # Send email using Laravel
    php /var/www/api-gateway/artisan tinker --execute="
        use Illuminate\Support\Facades\Mail;
        Mail::raw(
            '🚨 BACKUP SYSTEM ALERT\n\n' .
            'Severity: $SEVERITY\n' .
            'Category: $CATEGORY\n' .
            'Title: $TITLE\n' .
            'Description: $DESCRIPTION\n' .
            'Incident ID: $INCIDENT_ID\n' .
            'Timestamp: $TIMESTAMP\n\n' .
            'Please check the backup system immediately.\n' .
            'View details: https://api.askproai.de/docs/backup-system',
            function(\$message) {
                \$message->to(['fabian@askproai.de', 'fabianspitzer@icloud.com'])
                        ->subject('🚨 Backup System Alert - $SEVERITY: $TITLE');
            }
        );
        echo '✅ Alert email sent';
    " 2>&1 | grep -v "Warning:" || echo "⚠️ Failed to send email alert"
fi

exit $RESULT
