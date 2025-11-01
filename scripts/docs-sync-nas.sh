#!/bin/bash
# ==============================================================================
# Documentation Sync to NAS
# ==============================================================================
# Purpose: Sync documentation files to Synology NAS
# Usage: ./docs-sync-nas.sh
# ==============================================================================

set -euo pipefail

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration (from synology-upload.sh)
SYNOLOGY_HOST="fs-cloud1977.synology.me"
SYNOLOGY_PORT="50222"
SYNOLOGY_USER="AskProAI"
SYNOLOGY_BASE_PATH="/volume1/homes/FSAdmin/Backup/Server AskProAI/docs"
SYNOLOGY_SSH_KEY="/root/.ssh/synology_backup_key"

# Local documentation path
LOCAL_DOCS_PATH="/var/www/api-gateway/storage/docs/backup-system"

echo -e "${GREEN}=== Documentation Sync to NAS ===${NC}"
echo ""

# Test SSH connection
echo "🔌 Testing SSH connection..."
if ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -o ConnectTimeout=10 \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "echo 'Connection successful'" 2>/dev/null; then
    echo "✅ SSH connection successful"
else
    echo "❌ SSH connection failed"
    exit 1
fi

# Create remote directory
echo ""
echo "📁 Creating remote directory..."
ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "mkdir -p \"${SYNOLOGY_BASE_PATH}\""

echo "✅ Remote directory ready"

# Sync documentation files
echo ""
echo "📤 Syncing documentation files..."

# Find all files to sync
FILES_TO_SYNC=$(find "$LOCAL_DOCS_PATH" -type f)

SYNC_SUCCESS=0
SYNC_FAILED=0

for FILE in $FILES_TO_SYNC; do
    RELATIVE_PATH="${FILE#$LOCAL_DOCS_PATH/}"
    REMOTE_FILE="${SYNOLOGY_BASE_PATH}/${RELATIVE_PATH}"
    REMOTE_DIR=$(dirname "$REMOTE_FILE")

    # Create remote directory if needed
    ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "mkdir -p \"${REMOTE_DIR}\"" 2>/dev/null || true

    # Upload file via cat pipe (more reliable than rsync/scp)
    echo "  → $(basename "$FILE")"
    if cat "$FILE" | ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "cat > \"${REMOTE_FILE}\"" 2>/dev/null; then
        ((SYNC_SUCCESS++))
    else
        echo "    ⚠️ Failed to upload $(basename "$FILE")"
        ((SYNC_FAILED++))
    fi
done

# Generate SHA256 manifest
echo ""
echo "🔐 Generating SHA256 manifest..."

MANIFEST_FILE="$LOCAL_DOCS_PATH/SHA256SUMS.txt"
MANIFEST_REMOTE="${SYNOLOGY_BASE_PATH}/SHA256SUMS.txt"

# Create local manifest
cd "$LOCAL_DOCS_PATH"
find . -type f ! -name "SHA256SUMS.txt" ! -name ".htpasswd*" -exec sha256sum {} \; > "$MANIFEST_FILE"

# Upload manifest to NAS
if cat "$MANIFEST_FILE" | ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "cat > \"${MANIFEST_REMOTE}\"" 2>/dev/null; then
    echo "✅ SHA256 manifest uploaded"
    echo "   Local: $MANIFEST_FILE"
    echo "   Remote: $MANIFEST_REMOTE"
    echo ""
    echo "📊 Manifest stats:"
    wc -l "$MANIFEST_FILE" | awk '{print "   " $1 " files checksummed"}'
else
    echo "⚠️ Failed to upload SHA256 manifest"
fi

echo ""
if [ $SYNC_FAILED -eq 0 ]; then
    echo "✅ Documentation sync completed successfully ($SYNC_SUCCESS files)"
    echo ""
    echo "📋 Synced files on NAS:"
    ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "find \"${SYNOLOGY_BASE_PATH}\" -type f -ls" 2>/dev/null | awk '{print $11}' | tail -10
    echo ""
    echo "🔗 Remote path: ${SYNOLOGY_BASE_PATH}"
else
    echo "⚠️ Documentation sync completed with errors"
    echo "   Success: $SYNC_SUCCESS files"
    echo "   Failed: $SYNC_FAILED files"
    exit 1
fi
