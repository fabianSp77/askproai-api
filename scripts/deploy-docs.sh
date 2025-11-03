#!/usr/bin/env bash
set -euo pipefail

# Deploy E2E Documentation to Public Directory
# This script syncs docs/e2e/ to public/docs/e2e/
# Run this after pulling new changes or during deployment

echo "═══════════════════════════════════════════════════"
echo "Deploying E2E Documentation to Public Directory"
echo "═══════════════════════════════════════════════════"
echo ""

SOURCE_DIR="docs/e2e"
TARGET_DIR="public/docs/e2e"

# Check if source exists
if [ ! -d "$SOURCE_DIR" ]; then
  echo "❌ Source directory $SOURCE_DIR not found"
  exit 1
fi

# Create target directory if it doesn't exist
mkdir -p "$TARGET_DIR"

# Sync files (preserves structure, deletes removed files)
echo "📦 Syncing $SOURCE_DIR → $TARGET_DIR..."
rsync -av --delete \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='*.bak' \
  "$SOURCE_DIR/" "$TARGET_DIR/"

# Set correct permissions
if [ -n "${SUDO_USER:-}" ]; then
  # Running via sudo, set to www-data
  echo "🔒 Setting ownership to www-data:www-data..."
  chown -R www-data:www-data "$TARGET_DIR"
else
  echo "⚠️  Not running as sudo, skipping ownership change"
fi

echo ""
echo "✅ E2E Documentation deployed successfully"
echo "🌐 URL: https://api.askproai.de/docs/e2e/index.html"
echo ""
