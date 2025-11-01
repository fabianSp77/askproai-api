#!/usr/bin/env bash
set -euo pipefail

#===============================================================================
# Staging Rollback Script
# Reverts staging deployment to previous release
#===============================================================================

STAGING_BASE="/var/www/api-gateway-staging"
RELEASES_DIR="$STAGING_BASE/releases"
CURRENT_LINK="$STAGING_BASE/current"

AUTO_MODE=false

# Parse arguments
while [[ $# -gt 0 ]]; then
    case $1 in
        --auto)
            AUTO_MODE=true
            shift
            ;;
        *)
            echo "❌ Unknown option: $1"
            echo "Usage: $0 [--auto]"
            exit 1
            ;;
    esac
done

# Verify we're running as deploy user or root
if [[ "$EUID" -ne 0 && "$USER" != "deploy" ]]; then
    echo "❌ This script must be run as deploy user or root"
    exit 1
fi

# Get current release
if [[ ! -L "$CURRENT_LINK" ]]; then
    echo "❌ No current deployment found at $CURRENT_LINK"
    exit 1
fi

CURRENT_RELEASE=$(readlink -f "$CURRENT_LINK")
CURRENT_RELEASE_NAME=$(basename "$CURRENT_RELEASE")

# Find previous release
PREVIOUS_RELEASE=$(ls -1dt "$RELEASES_DIR"/* | grep -v "$CURRENT_RELEASE_NAME" | head -n1)

if [[ -z "$PREVIOUS_RELEASE" || ! -d "$PREVIOUS_RELEASE" ]]; then
    echo "❌ No previous release found to rollback to"
    echo "Available releases:"
    ls -1t "$RELEASES_DIR" || echo "  (none)"
    exit 1
fi

PREVIOUS_RELEASE_NAME=$(basename "$PREVIOUS_RELEASE")

# Show rollback plan
echo "📋 Rollback Plan:"
echo "  From: $CURRENT_RELEASE_NAME"
echo "  To:   $PREVIOUS_RELEASE_NAME"
echo ""

# Confirm unless --auto
if [[ "$AUTO_MODE" != "true" ]]; then
    read -p "Continue with rollback? (yes/no): " CONFIRM
    if [[ "$CONFIRM" != "yes" ]]; then
        echo "❌ Rollback cancelled"
        exit 0
    fi
fi

# Perform rollback
echo "🔄 Performing rollback..."

# Update symlink
ln -snf "$PREVIOUS_RELEASE" "$CURRENT_LINK"
echo "✅ Updated symlink: $CURRENT_LINK -> $PREVIOUS_RELEASE_NAME"

# Reload services
if [[ "$EUID" -eq 0 ]]; then
    systemctl reload php8.3-fpm 2>/dev/null || true
    nginx -t && systemctl reload nginx || true
    echo "✅ Reloaded PHP-FPM and NGINX"
else
    echo "⚠️  Not running as root - skipping service reload"
    echo "   Run: sudo systemctl reload php8.3-fpm nginx"
fi

echo ""
echo "✅ Rollback completed successfully!"
echo "   Current release: $PREVIOUS_RELEASE_NAME"
echo ""
echo "Verify deployment:"
echo "  curl -H 'Authorization: Bearer YOUR_TOKEN' https://staging.askproai.de/healthcheck.php"
