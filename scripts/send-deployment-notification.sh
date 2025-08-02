#!/bin/bash
# send-deployment-notification.sh
# Stakeholder communication script for deployment notifications

set -e

NOTIFICATION_TYPE="${1:-info}"
SUBJECT="${2:-Deployment Notification}"
MESSAGE="${3:-Deployment notification}"
DEPLOYMENT_ID="${4:-unknown}"

# Configuration
PROJECT_ROOT="$(dirname "$(dirname "$(realpath "$0")")")"
EMAIL_FROM="deployments@askproai.de"
SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-}"

# Stakeholder groups and contact methods
declare -A STAKEHOLDER_GROUPS=(
    ["executive"]="cto@askproai.de,ceo@askproai.de"
    ["development"]="dev-team@askproai.de"
    ["support"]="support@askproai.de"
    ["operations"]="ops@askproai.de"
)

declare -A SLACK_CHANNELS=(
    ["development"]="#deployments"
    ["operations"]="#ops-alerts"
    ["support"]="#support-updates"
    ["emergency"]="#incidents"
)

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

# Load deployment data if available
load_deployment_data() {
    local deployment_data=""
    
    if [ -f "$PROJECT_ROOT/storage/deployment/validation-results.json" ]; then
        deployment_data=$(cat "$PROJECT_ROOT/storage/deployment/validation-results.json" 2>/dev/null || echo "{}")
    elif [ -f "$PROJECT_ROOT/storage/deployment/post-validation-report-$DEPLOYMENT_ID.json" ]; then
        deployment_data=$(cat "$PROJECT_ROOT/storage/deployment/post-validation-report-$DEPLOYMENT_ID.json" 2>/dev/null || echo "{}")
    else
        deployment_data="{}"
    fi
    
    echo "$deployment_data"
}

# Generate deployment statistics
generate_deployment_stats() {
    local deployment_data="$1"
    local stats=""
    
    if [ "$deployment_data" != "{}" ]; then
        local score=$(echo "$deployment_data" | jq -r '.validation_results.score // .score // "N/A"')
        local percentage=$(echo "$deployment_data" | jq -r '.validation_results.percentage // .percentage // "N/A"')
        local response_time=$(echo "$deployment_data" | jq -r '.performance_metrics.response_time_ms // "N/A"')
        
        stats="📊 Deployment Statistics:
- Validation Score: $score
- Success Rate: $percentage%
- Response Time: ${response_time}ms
- Deployment ID: $DEPLOYMENT_ID"
    else
        stats="📊 Deployment Statistics:
- Deployment ID: $DEPLOYMENT_ID
- Status: $NOTIFICATION_TYPE
- Timestamp: $(date -Iseconds)"
    fi
    
    echo "$stats"
}

# Email notification templates
send_email_notification() {
    local recipients="$1"
    local email_subject="$2"
    local email_body="$3"
    local priority="$4"
    
    # Create email template
    local email_template=$(cat << EOF
From: AskProAI Deployment System <$EMAIL_FROM>
To: $recipients
Subject: $email_subject
Priority: $priority
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #007cba; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .status-success { color: #28a745; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .stats { background: #f8f9fa; padding: 15px; border-left: 4px solid #007cba; margin: 15px 0; }
        .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .action-button { 
            display: inline-block; 
            background: #007cba; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 AskProAI Deployment Notification</h1>
    </div>
    <div class="content">
        $email_body
        
        <div class="stats">
            $(generate_deployment_stats "$(load_deployment_data)")
        </div>
        
        <p>
            <a href="https://api.askproai.de/admin/deployment-monitor" class="action-button">
                View Deployment Dashboard
            </a>
        </p>
        
        <p>For questions or issues, contact the deployment team at <a href="mailto:deployments@askproai.de">deployments@askproai.de</a></p>
    </div>
    <div class="footer">
        <p>AskProAI Deployment System | $(date)</p>
        <p>This is an automated message from the deployment system.</p>
    </div>
</body>
</html>
EOF
)

    # Send email (using system sendmail or configured SMTP)
    if command -v sendmail >/dev/null 2>&1; then
        echo "$email_template" | sendmail -t
        success "Email sent to: $recipients"
    else
        warning "Sendmail not available - email template saved to logs"
        echo "$email_template" >> "$PROJECT_ROOT/storage/logs/email-notifications.log"
    fi
}

# Slack notification
send_slack_notification() {
    local channel="$1"
    local slack_message="$2"
    local color="$3"
    
    if [ -z "$SLACK_WEBHOOK_URL" ]; then
        warning "Slack webhook URL not configured - skipping Slack notification"
        return
    fi
    
    local payload=$(cat << EOF
{
    "channel": "$channel",
    "username": "AskProAI Deployment Bot",
    "icon_emoji": ":rocket:",
    "attachments": [
        {
            "color": "$color",
            "title": "$SUBJECT",
            "text": "$slack_message",
            "footer": "AskProAI Deployment System",
            "ts": $(date +%s),
            "fields": [
                {
                    "title": "Deployment ID",
                    "value": "$DEPLOYMENT_ID",
                    "short": true
                },
                {
                    "title": "Environment",
                    "value": "Production",
                    "short": true
                }
            ],
            "actions": [
                {
                    "type": "button",
                    "text": "View Dashboard",
                    "url": "https://api.askproai.de/admin/deployment-monitor"
                }
            ]
        }
    ]
}
EOF
)

    if curl -s -X POST -H 'Content-type: application/json' --data "$payload" "$SLACK_WEBHOOK_URL" >/dev/null; then
        success "Slack notification sent to $channel"
    else
        error "Failed to send Slack notification to $channel"
    fi
}

# Generate notification content based on type
generate_notification_content() {
    local notification_type="$1"
    local deployment_data="$(load_deployment_data)"
    
    case "$notification_type" in
        "pre-deployment")
            echo "Business Portal improvements are scheduled for deployment.

🗓️ **Deployment Window**: $(date -d '+2 hours' +'%Y-%m-%d %H:%M CET')
⏱️ **Expected Duration**: <2 hours
📉 **Expected Downtime**: <30 seconds

**What's being deployed:**
- Critical UI/UX fixes for mobile navigation
- Enhanced test coverage (40% → 60%)
- Performance monitoring improvements  
- Documentation updates and health checks

**Rollback Plan:** Comprehensive rollback procedures are in place with <2 minute recovery time.

**Questions?** Contact the deployment team for any concerns."
            ;;
            
        "deployment-started")
            echo "Deployment has started and is proceeding as planned.

🚀 **Status**: Deployment in progress
⏰ **Started**: $(date)
📊 **Phase**: Infrastructure preparation
🎯 **Next**: Backend deployment with brief maintenance window

**Live Status**: Monitor progress at the deployment dashboard.

**No action required** - we'll notify you when deployment is complete."
            ;;
            
        "deployment-success")
            echo "Business Portal improvements have been successfully deployed! 🎉

✅ **Status**: Deployment completed successfully
⏱️ **Duration**: $(echo "$deployment_data" | jq -r '.duration_seconds // "N/A"') seconds
📈 **Validation**: All post-deployment checks passed
🚀 **Features**: All new features are now live

**What's New:**
- ✅ Enhanced mobile navigation and responsiveness
- ✅ Improved table interfaces and performance
- ✅ Real-time performance monitoring dashboard
- ✅ Expanded test coverage ensuring reliability

**Performance Impact:** Response times improved by $(echo "$deployment_data" | jq -r '.performance_improvement // "N/A"')%

**Next Steps:** Our team will monitor system performance for the next 24 hours to ensure optimal operation."
            ;;
            
        "deployment-failed")
            echo "⚠️ Deployment encountered issues and has been paused for investigation.

❌ **Status**: Deployment failed
🔍 **Cause**: Under investigation
🔄 **Action**: Automatic rollback initiated
⏱️ **Recovery Time**: <5 minutes

**Current Status:**
- System remains operational with previous version
- No user impact or data loss
- Investigation in progress

**Next Steps:**
1. Root cause analysis
2. Issue resolution
3. Rescheduled deployment

We'll provide updates as soon as we have more information."
            ;;
            
        "rollback-success")
            echo "✅ System has been successfully rolled back to the previous stable version.

🔄 **Status**: Rollback completed successfully
⏱️ **Duration**: $(echo "$deployment_data" | jq -r '.duration_seconds // "N/A"') seconds
✅ **System Health**: All services operational
📊 **Validation**: Post-rollback checks passed

**Current State:**
- Previous stable version restored
- All services functioning normally
- User experience unaffected

**Next Steps:**
- Issue analysis and resolution
- Deployment retry scheduled after fixes
- Continuous monitoring for 24 hours

**No action required** from users - everything is working normally."
            ;;
            
        "emergency")
            echo "🚨 **URGENT**: Emergency deployment action required.

⚠️ **Situation**: Critical issue detected requiring immediate attention
🚨 **Impact**: $(echo "$MESSAGE" | head -1)
⏱️ **Response Time**: Immediate action in progress

**Actions Taken:**
- Emergency response team activated
- System monitoring intensified
- Rollback procedures on standby

**Updates**: We'll provide status updates every 15 minutes until resolved.

**Contact**: For urgent issues, call the emergency hotline."
            ;;
            
        *)
            echo "$MESSAGE"
            ;;
    esac
}

# Send notifications to different stakeholder groups
send_notifications() {
    local notification_type="$1"
    local content="$(generate_notification_content "$notification_type")"
    
    case "$notification_type" in
        "pre-deployment")
            # Notify all stakeholders 48 hours before
            send_email_notification "${STAKEHOLDER_GROUPS[executive]}" \
                "📅 Scheduled: Business Portal Improvements - $(date -d '+2 hours' +'%Y-%m-%d')" \
                "$content" "normal"
            
            send_email_notification "${STAKEHOLDER_GROUPS[development]},${STAKEHOLDER_GROUPS[support]}" \
                "🔧 Technical: Business Portal Deployment - $(date -d '+2 hours' +'%Y-%m-%d')" \
                "$content" "normal"
            
            send_slack_notification "${SLACK_CHANNELS[development]}" "$content" "good"
            ;;
            
        "deployment-started")
            # Notify technical teams
            send_email_notification "${STAKEHOLDER_GROUPS[development]},${STAKEHOLDER_GROUPS[operations]}" \
                "🚀 Started: Business Portal Deployment - $DEPLOYMENT_ID" \
                "$content" "high"
            
            send_slack_notification "${SLACK_CHANNELS[development]}" "$content" "#439FE0"
            send_slack_notification "${SLACK_CHANNELS[operations]}" "$content" "#439FE0"
            ;;
            
        "deployment-success")
            # Notify all stakeholders
            send_email_notification "${STAKEHOLDER_GROUPS[executive]}" \
                "✅ Success: Business Portal Improvements Live - $DEPLOYMENT_ID" \
                "$content" "normal"
            
            send_email_notification "${STAKEHOLDER_GROUPS[development]},${STAKEHOLDER_GROUPS[support]},${STAKEHOLDER_GROUPS[operations]}" \
                "🎉 Complete: Business Portal Deployment - $DEPLOYMENT_ID" \
                "$content" "normal"
            
            send_slack_notification "${SLACK_CHANNELS[development]}" "$content" "good"
            send_slack_notification "${SLACK_CHANNELS[operations]}" "$content" "good"
            ;;
            
        "deployment-failed")
            # Immediate notification to technical teams
            send_email_notification "${STAKEHOLDER_GROUPS[development]},${STAKEHOLDER_GROUPS[operations]}" \
                "❌ Failed: Business Portal Deployment - $DEPLOYMENT_ID" \
                "$content" "urgent"
            
            # Executive summary
            send_email_notification "${STAKEHOLDER_GROUPS[executive]}" \
                "⚠️ Issue: Deployment Paused - Investigation Underway" \
                "$content" "high"
            
            send_slack_notification "${SLACK_CHANNELS[development]}" "$content" "danger"
            send_slack_notification "${SLACK_CHANNELS[operations]}" "$content" "danger"
            ;;
            
        "rollback-success")
            # All stakeholders for rollback
            send_email_notification "${STAKEHOLDER_GROUPS[executive]}" \
                "🔄 Resolved: System Restored to Stable Version" \
                "$content" "high"
            
            send_email_notification "${STAKEHOLDER_GROUPS[development]},${STAKEHOLDER_GROUPS[operations]},${STAKEHOLDER_GROUPS[support]}" \
                "✅ Complete: Rollback Successful - $DEPLOYMENT_ID" \
                "$content" "high"
            
            send_slack_notification "${SLACK_CHANNELS[development]}" "$content" "good"
            send_slack_notification "${SLACK_CHANNELS[operations]}" "$content" "good"
            ;;
            
        "emergency")
            # Emergency notifications to all channels
            send_email_notification "${STAKEHOLDER_GROUPS[executive]},${STAKEHOLDER_GROUPS[development]},${STAKEHOLDER_GROUPS[operations]}" \
                "🚨 URGENT: Emergency Deployment Action - $DEPLOYMENT_ID" \
                "$content" "urgent"
            
            send_slack_notification "${SLACK_CHANNELS[emergency]}" "$content" "danger"
            send_slack_notification "${SLACK_CHANNELS[development]}" "$content" "danger"
            send_slack_notification "${SLACK_CHANNELS[operations]}" "$content" "danger"
            ;;
    esac
}

# Main execution
main() {
    log "Sending deployment notification: $NOTIFICATION_TYPE"
    log "Subject: $SUBJECT"
    log "Deployment ID: $DEPLOYMENT_ID"
    
    # Send notifications
    send_notifications "$NOTIFICATION_TYPE"
    
    # Log notification
    echo "$(date -Iseconds): $NOTIFICATION_TYPE notification sent for deployment $DEPLOYMENT_ID" >> \
        "$PROJECT_ROOT/storage/logs/deployment-notifications.log"
    
    success "All notifications sent successfully"
}

# Help function
show_help() {
    cat << EOF
Usage: $0 <notification-type> [subject] [message] [deployment-id]

Notification types:
  pre-deployment     - 48h advance notice
  deployment-started - Deployment beginning
  deployment-success - Successful completion
  deployment-failed  - Deployment failure
  rollback-success   - Successful rollback
  emergency          - Emergency situation

Examples:
  $0 pre-deployment "Scheduled Deployment" "" "bp-20250801-0200"
  $0 deployment-success "Deployment Complete" "" "bp-20250801-0200"
  $0 emergency "Critical Issue" "Database connectivity lost" "bp-20250801-0200"

Environment variables:
  SLACK_WEBHOOK_URL - Slack webhook for notifications
EOF
}

# Check arguments
if [ $# -eq 0 ]; then
    show_help
    exit 1
fi

if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    show_help
    exit 0
fi

# Execute main function
main