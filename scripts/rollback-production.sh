#!/bin/bash
# ==============================================================================
# Production Rollback Script
# ==============================================================================
# Purpose: Instantly rollback to previous release
# Usage: ./rollback-production.sh [release-name]
# ==============================================================================

set -euo pipefail

BASE_DIR="/var/www/api-gateway"
RELEASES_DIR="$BASE_DIR/releases"
CURRENT_LINK="$BASE_DIR/current"

# Get current and previous releases
CURRENT_RELEASE=$(readlink "$CURRENT_LINK" | xargs basename)
PREVIOUS_RELEASE=$(ls -1dt "$RELEASES_DIR"/*/ | sed -n '2p' | xargs basename)

# Use argument if provided
TARGET_RELEASE="${1:-$PREVIOUS_RELEASE}"

echo "Current:  $CURRENT_RELEASE"
echo "Rolling back to: $TARGET_RELEASE"
read -p "Continue? (y/N) " -n 1 -r
echo

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Atomic rollback
ln -sfn "$RELEASES_DIR/$TARGET_RELEASE" "$CURRENT_LINK"
cd "$CURRENT_LINK" && php artisan optimize:clear
sudo systemctl reload php8.3-fpm nginx

echo "✅ Rolled back to: $TARGET_RELEASE"
curl -sf https://api.askproai.de/health && echo "✅ Health check passed"
