#!/bin/bash

#################################################################
# AskProAI Documentation Auto-Update Script
# 
# This script automatically updates the documentation by:
# 1. Running the documentation generator
# 2. Building MkDocs
# 3. Deploying to the correct location
# 4. Clearing caches
# 5. Sending notifications
#################################################################

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="/var/log/askproai/docs-update.log"
LOCK_FILE="/tmp/docs-update.lock"
NOTIFICATION_WEBHOOK="${DOCS_UPDATE_WEBHOOK:-}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Error handling
error_exit() {
    echo -e "${RED}Error: $1${NC}" >&2
    log "ERROR: $1"
    cleanup
    send_notification "error" "$1"
    exit 1
}

# Cleanup function
cleanup() {
    rm -f "$LOCK_FILE"
}

# Send notification
send_notification() {
    local status=$1
    local message=$2
    
    if [ -n "$NOTIFICATION_WEBHOOK" ]; then
        curl -s -X POST "$NOTIFICATION_WEBHOOK" \
            -H "Content-Type: application/json" \
            -d "{\"status\":\"$status\",\"message\":\"$message\",\"timestamp\":\"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}" \
            > /dev/null 2>&1 || true
    fi
}

# Check if another instance is running
if [ -f "$LOCK_FILE" ]; then
    if ps -p $(cat "$LOCK_FILE") > /dev/null 2>&1; then
        log "Another instance is already running (PID: $(cat $LOCK_FILE))"
        exit 0
    else
        log "Removing stale lock file"
        rm -f "$LOCK_FILE"
    fi
fi

# Create lock file
echo $$ > "$LOCK_FILE"
trap cleanup EXIT

# Start update process
log "=== Starting documentation auto-update ==="
echo -e "${GREEN}Starting documentation auto-update...${NC}"

# Change to project directory
cd "$PROJECT_ROOT" || error_exit "Failed to change to project directory"

# Step 1: Generate documentation from code
log "Generating documentation from codebase..."
echo -e "${YELLOW}Step 1/5: Generating documentation...${NC}"

if php artisan docs:generate --format=markdown --output=docs_mkdocs 2>&1 | tee -a "$LOG_FILE"; then
    log "Documentation generation completed successfully"
else
    # Non-critical - continue even if generation fails
    log "WARNING: Documentation generation had issues, continuing..."
fi

# Step 2: Check if MkDocs is installed
if ! command -v mkdocs &> /dev/null; then
    log "MkDocs not found, installing..."
    pip install mkdocs-material mkdocs-mermaid2-plugin mkdocs-git-revision-date-localized-plugin mkdocs-minify-plugin 2>&1 | tee -a "$LOG_FILE" || error_exit "Failed to install MkDocs"
fi

# Step 3: Build MkDocs site
log "Building MkDocs site..."
echo -e "${YELLOW}Step 2/5: Building MkDocs...${NC}"

if mkdocs build --strict 2>&1 | tee -a "$LOG_FILE"; then
    log "MkDocs build completed successfully"
else
    error_exit "MkDocs build failed"
fi

# Step 4: Deploy to web directory
log "Deploying documentation..."
echo -e "${YELLOW}Step 3/5: Deploying to web server...${NC}"

# Backup current documentation
if [ -d "public/mkdocs" ]; then
    backup_dir="public/mkdocs.backup.$(date +%Y%m%d%H%M%S)"
    mv public/mkdocs "$backup_dir" || error_exit "Failed to backup current documentation"
    log "Backed up current documentation to $backup_dir"
fi

# Deploy new documentation (clean copy)
rm -rf public/mkdocs 2>/dev/null || true
cp -r site public/mkdocs || error_exit "Failed to deploy documentation"

# Set correct permissions
chown -R www-data:www-data public/mkdocs || log "WARNING: Failed to set ownership (may need sudo)"
chmod -R 755 public/mkdocs || error_exit "Failed to set permissions"

log "Documentation deployed successfully"

# Step 5: Clear caches
log "Clearing caches..."
echo -e "${YELLOW}Step 4/5: Clearing caches...${NC}"

# Clear Laravel caches
php artisan cache:clear 2>&1 | tee -a "$LOG_FILE" || log "WARNING: Failed to clear Laravel cache"

# Clear CDN cache if configured
if [ -n "$CLOUDFLARE_ZONE_ID" ] && [ -n "$CLOUDFLARE_API_TOKEN" ]; then
    log "Purging Cloudflare cache..."
    curl -s -X POST "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID/purge_cache" \
        -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
        -H "Content-Type: application/json" \
        --data '{"purge_everything":false,"files":["https://api.askproai.de/mkdocs/*"]}' \
        > /dev/null 2>&1 || log "WARNING: Failed to purge Cloudflare cache"
fi

# Step 6: Update search index
log "Updating search index..."
echo -e "${YELLOW}Step 5/5: Updating search index...${NC}"

# This would integrate with Algolia or similar if configured
if [ -n "$ALGOLIA_APP_ID" ]; then
    log "TODO: Implement Algolia index update"
fi

# Generate sitemap
if command -v mkdocs &> /dev/null; then
    mkdocs build --config-file mkdocs.yml --site-dir public/mkdocs 2>&1 | tee -a "$LOG_FILE" || log "WARNING: Failed to generate sitemap"
fi

# Clean up old backups (keep last 5)
log "Cleaning up old backups..."
ls -dt public/mkdocs.backup.* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true

# Calculate statistics
TOTAL_FILES=$(find public/mkdocs -type f | wc -l)
TOTAL_SIZE=$(du -sh public/mkdocs | cut -f1)

# Success!
log "=== Documentation update completed successfully ==="
log "Total files: $TOTAL_FILES"
log "Total size: $TOTAL_SIZE"

echo -e "${GREEN}✅ Documentation updated successfully!${NC}"
echo "📊 Stats: $TOTAL_FILES files, $TOTAL_SIZE total"
echo "🌐 View at: https://api.askproai.de/mkdocs/"

# Send success notification
send_notification "success" "Documentation updated: $TOTAL_FILES files, $TOTAL_SIZE"

# Optional: Trigger monitoring check
if [ -n "$MONITORING_ENDPOINT" ]; then
    curl -s "$MONITORING_ENDPOINT/docs-updated" > /dev/null 2>&1 || true
fi

exit 0