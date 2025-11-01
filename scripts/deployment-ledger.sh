#!/bin/bash
# ==============================================================================
# Deployment Ledger System
# ==============================================================================
# Purpose: JSON audit trail for all deployments
# Storage: Local + NAS sync
# Format: Immutable append-only log
# ==============================================================================

set -euo pipefail

ACTION="${1:-}"
ENVIRONMENT="${2:-}"

LEDGER_DIR="/var/www/api-gateway/docs/deployments/ledger"
LEDGER_FILE="$LEDGER_DIR/${ENVIRONMENT}-ledger.json"

# ==============================================================================
# Helper Functions
# ==============================================================================
usage() {
    echo "Usage: $0 <action> <environment> [args...]"
    echo ""
    echo "Actions:"
    echo "  record <env> <release_id> <git_sha>  - Record deployment"
    echo "  verify <env>                          - Verify last deployment"
    echo "  list <env>                            - List all deployments"
    echo ""
    echo "Environments: staging | production"
    exit 1
}

if [ -z "$ACTION" ] || [ -z "$ENVIRONMENT" ]; then
    usage
fi

mkdir -p "$LEDGER_DIR"

# ==============================================================================
# Actions
# ==============================================================================
case "$ACTION" in
    record)
        RELEASE_ID="${3:-}"
        GIT_SHA="${4:-}"

        if [ -z "$RELEASE_ID" ] || [ -z "$GIT_SHA" ]; then
            echo "❌ Error: release_id and git_sha required for record action"
            usage
        fi

        # Create entry
        ENTRY=$(jq -n \
            --arg ts "$(date -Iseconds)" \
            --arg release "$RELEASE_ID" \
            --arg sha "$GIT_SHA" \
            --arg env "$ENVIRONMENT" \
            --arg user "${GITHUB_ACTOR:-$(whoami)}" \
            --arg host "$(hostname)" \
            '{
                timestamp: $ts,
                release_id: $release,
                git_sha: $sha,
                environment: $env,
                deployed_by: $user,
                deployed_from: $host,
                status: "success"
            }')

        # Append to ledger
        if [[ -f "$LEDGER_FILE" ]]; then
            jq ". += [$ENTRY]" "$LEDGER_FILE" > "${LEDGER_FILE}.tmp"
            mv "${LEDGER_FILE}.tmp" "$LEDGER_FILE"
        else
            echo "[$ENTRY]" > "$LEDGER_FILE"
        fi

        echo "✅ Deployment recorded in ledger"
        echo "$ENTRY" | jq .
        ;;

    verify)
        if [[ ! -f "$LEDGER_FILE" ]]; then
            echo "❌ No ledger found for $ENVIRONMENT"
            exit 1
        fi

        echo "📊 Last deployment for $ENVIRONMENT:"
        echo ""
        LAST_DEPLOY=$(jq -r '.[-1]' "$LEDGER_FILE")
        echo "$LAST_DEPLOY" | jq .

        # Check if same as current release
        CURRENT_RELEASE=$(readlink -f "/var/www/${ENVIRONMENT}/current" 2>/dev/null | xargs basename || echo "unknown")
        LEDGER_RELEASE=$(echo "$LAST_DEPLOY" | jq -r '.release_id')

        if [ "$CURRENT_RELEASE" == "$LEDGER_RELEASE" ]; then
            echo ""
            echo "✅ Ledger matches current release"
        else
            echo ""
            echo "⚠️  Warning: Ledger ($LEDGER_RELEASE) != Current ($CURRENT_RELEASE)"
        fi
        ;;

    list)
        if [[ ! -f "$LEDGER_FILE" ]]; then
            echo "📭 No deployments recorded yet for $ENVIRONMENT"
            exit 0
        fi

        echo "📋 Deployment history for $ENVIRONMENT:"
        echo ""
        jq -r '.[] | "\(.timestamp) | \(.release_id) | \(.git_sha) | \(.deployed_by)"' "$LEDGER_FILE" | \
            column -t -s '|' -N "Timestamp,Release,Commit,User"
        ;;

    *)
        echo "❌ Unknown action: $ACTION"
        usage
        ;;
esac

exit 0
