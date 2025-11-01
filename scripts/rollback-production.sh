#!/bin/bash
# ==============================================================================
# Production Rollback Script
# ==============================================================================
# Purpose: Instantly rollback to previous release
# Usage: ./rollback-production.sh [release-name|--auto]
# Modes:
#   --auto              Auto-rollback (no confirmation, for CI/CD)
#   <release-name>      Manual rollback to specific release
#   (no args)           Manual rollback to previous release
# ==============================================================================

set -euo pipefail

BASE_DIR="/var/www/api-gateway"
RELEASES_DIR="$BASE_DIR/releases"
CURRENT_LINK="$BASE_DIR/current"

# Check for auto mode
AUTO_MODE=false
if [[ "${1:-}" == "--auto" ]]; then
    AUTO_MODE=true
    echo "🔄 AUTO-ROLLBACK MODE activated"
fi

# Get current and previous releases
CURRENT_RELEASE=$(readlink "$CURRENT_LINK" | xargs basename)
PREVIOUS_RELEASE=$(ls -1dt "$RELEASES_DIR"/*/ | sed -n '2p' | xargs basename)

# Determine target release
if [ "$AUTO_MODE" = true ]; then
    TARGET_RELEASE="$PREVIOUS_RELEASE"
else
    TARGET_RELEASE="${1:-$PREVIOUS_RELEASE}"
fi

echo "Current:  $CURRENT_RELEASE"
echo "Rolling back to: $TARGET_RELEASE"

# Confirm unless auto mode
if [ "$AUTO_MODE" = false ]; then
    read -p "Continue? (y/N) " -n 1 -r
    echo

    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
else
    echo "⚡ Auto-rollback executing without confirmation..."
fi

# Atomic rollback
ln -sfn "$RELEASES_DIR/$TARGET_RELEASE" "$CURRENT_LINK"
cd "$CURRENT_LINK" && php artisan optimize:clear
sudo systemctl reload php8.3-fpm nginx

echo "✅ Rolled back to: $TARGET_RELEASE"
curl -sf https://api.askproai.de/health && echo "✅ Health check passed"
