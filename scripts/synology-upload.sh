#!/bin/bash
# ==============================================================================
# Synology NAS Upload Script
# ==============================================================================
# Purpose: Upload backups to Synology NAS via SFTP/rsync
# Usage: ./synology-upload.sh <backup-file> [retention-type]
# Example: ./synology-upload.sh backup-20251029.tar.gz daily
# ==============================================================================

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="/var/log/synology-upload.log"

# Synology Configuration (CUSTOMIZE THESE)
# ============================================================================
# Option 1: Direct SSH/SFTP (Recommended if available)
# Requires: Port forwarding or DynDNS (e.g., askpro.synology.me)
: ${SYNOLOGY_HOST:="fs-cloud1977.synology.me"}
: ${SYNOLOGY_PORT:="50222"}
: ${SYNOLOGY_USER:="AskProAI"}
: ${SYNOLOGY_BASE_PATH:="/volume1/homes/FSAdmin/Backup/Server AskProAI"}

# Option 2: SSH Key Authentication (Recommended)
: ${SYNOLOGY_SSH_KEY:="/root/.ssh/synology_backup_key"}

# Option 3: Password Authentication (Less secure, use SSH key instead)
: ${SYNOLOGY_PASSWORD:="Qwe421as1!11"}

# Retention paths
RETENTION_PATHS=(
    "daily"
    "weekly"
    "monthly"
    "pre-deploy"
)

# Function: Log message
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function: Check if Synology host is configured
check_configuration() {
    if [ -z "$SYNOLOGY_HOST" ]; then
        echo -e "${RED}ERROR: SYNOLOGY_HOST not configured${NC}"
        echo ""
        echo "Please configure Synology connection:"
        echo "1. Set SYNOLOGY_HOST environment variable, OR"
        echo "2. Edit this script and set SYNOLOGY_HOST="
        echo ""
        echo "Examples:"
        echo "  SYNOLOGY_HOST=\"askpro.synology.me\""
        echo "  SYNOLOGY_HOST=\"192.168.1.100\""
        echo "  SYNOLOGY_HOST=\"your-public-ip\""
        echo ""
        echo "For QuickConnect users:"
        echo "  - Setup Port Forwarding (SSH port 22) in your router"
        echo "  - OR use Synology DynDNS (*.synology.me)"
        echo "  - OR use VPN tunnel"
        exit 1
    fi
}

# Function: Setup SSH key if not exists
setup_ssh_key() {
    if [ ! -f "$SYNOLOGY_SSH_KEY" ]; then
        log "⚠️  SSH key not found at $SYNOLOGY_SSH_KEY"
        log "Creating new SSH key pair..."

        mkdir -p "$(dirname "$SYNOLOGY_SSH_KEY")"
        ssh-keygen -t ed25519 -f "$SYNOLOGY_SSH_KEY" -N "" -C "backup@askproai"

        log "✅ SSH key created: $SYNOLOGY_SSH_KEY"
        log ""
        log "📝 IMPORTANT: Copy public key to Synology:"
        echo -e "${YELLOW}=========================${NC}"
        cat "${SYNOLOGY_SSH_KEY}.pub"
        echo -e "${YELLOW}=========================${NC}"
        log ""
        log "On Synology:"
        log "  1. SSH to Synology as ${SYNOLOGY_USER}"
        log "  2. mkdir -p ~/.ssh && chmod 700 ~/.ssh"
        log "  3. echo '[PUBLIC_KEY]' >> ~/.ssh/authorized_keys"
        log "  4. chmod 600 ~/.ssh/authorized_keys"
        log ""
        read -p "Press Enter after copying public key to Synology..."
    fi
}

# Function: Test SSH connection
test_ssh_connection() {
    log "🔌 Testing SSH connection to $SYNOLOGY_USER@$SYNOLOGY_HOST:$SYNOLOGY_PORT..."

    # Try SSH key first
    if [ -f "$SYNOLOGY_SSH_KEY" ]; then
        if ssh -i "$SYNOLOGY_SSH_KEY" \
            -o StrictHostKeyChecking=no \
            -o ConnectTimeout=10 \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "echo 'SSH connection successful'" 2>/dev/null; then
            log "✅ SSH key authentication successful"
            return 0
        fi
    fi

    # Try password authentication (using sshpass if available)
    if command -v sshpass &> /dev/null; then
        if sshpass -p "$SYNOLOGY_PASSWORD" \
            ssh -o StrictHostKeyChecking=no \
            -o ConnectTimeout=10 \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "echo 'SSH connection successful'" 2>/dev/null; then
            log "✅ Password authentication successful (consider using SSH key)"
            return 0
        fi
    fi

    log "❌ SSH connection failed"
    return 1
}

# Function: Create retention directories on Synology
create_retention_dirs() {
    log "📁 Creating retention directories on Synology..."

    for retention in "${RETENTION_PATHS[@]}"; do
        local remote_path="${SYNOLOGY_BASE_PATH}/${retention}"

        if [ -f "$SYNOLOGY_SSH_KEY" ]; then
            ssh -i "$SYNOLOGY_SSH_KEY" \
                -o StrictHostKeyChecking=no \
                -p "$SYNOLOGY_PORT" \
                "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
                "mkdir -p \"$remote_path\"" 2>/dev/null || true
        else
            sshpass -p "$SYNOLOGY_PASSWORD" \
                ssh -o StrictHostKeyChecking=no \
                -p "$SYNOLOGY_PORT" \
                "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
                "mkdir -p \"$remote_path\"" 2>/dev/null || true
        fi
    done

    log "✅ Retention directories created"
}

# Function: Upload file to Synology via rsync
upload_file_rsync() {
    local local_file="$1"
    local retention="$2"
    local remote_path="${SYNOLOGY_BASE_PATH}/${retention}/"

    if [ ! -f "$local_file" ]; then
        log "❌ File not found: $local_file"
        return 1
    fi

    local filename=$(basename "$local_file")
    log "📤 Uploading $filename to Synology ($retention)..."

    # Calculate file size
    local size=$(stat -c%s "$local_file")
    local size_mb=$((size / 1024 / 1024))
    log "   File size: ${size_mb} MB"

    # Upload using rsync with progress
    if [ -f "$SYNOLOGY_SSH_KEY" ]; then
        rsync -avz --progress \
            -e "ssh -i $SYNOLOGY_SSH_KEY -o StrictHostKeyChecking=no -p $SYNOLOGY_PORT" \
            "$local_file" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}:${remote_path}" 2>&1 | \
            grep -E "(sending|sent|total)" | tail -3
    else
        # Fallback to scp with password
        sshpass -p "$SYNOLOGY_PASSWORD" \
            scp -o StrictHostKeyChecking=no \
            -P "$SYNOLOGY_PORT" \
            "$local_file" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}:${remote_path}"
    fi

    if [ $? -eq 0 ]; then
        log "✅ Upload successful: $filename"
        return 0
    else
        log "❌ Upload failed: $filename"
        return 1
    fi
}

# Function: Upload SHA256 checksum
upload_checksum() {
    local local_file="$1"
    local retention="$2"

    if [ -f "${local_file}.sha256" ]; then
        log "🔐 Uploading checksum..."
        upload_file_rsync "${local_file}.sha256" "$retention"
    fi
}

# Function: Apply retention policy (keep last N backups)
apply_retention_policy() {
    local retention="$1"
    local keep_count="$2"
    local remote_path="${SYNOLOGY_BASE_PATH}/${retention}"

    log "🧹 Applying retention policy: keep last $keep_count in $retention/"

    local cleanup_cmd="cd \"$remote_path\" && ls -t backup-*.tar.gz 2>/dev/null | tail -n +$((keep_count + 1)) | xargs -r rm -f"

    if [ -f "$SYNOLOGY_SSH_KEY" ]; then
        ssh -i "$SYNOLOGY_SSH_KEY" \
            -o StrictHostKeyChecking=no \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "$cleanup_cmd" 2>/dev/null || true
    else
        sshpass -p "$SYNOLOGY_PASSWORD" \
            ssh -o StrictHostKeyChecking=no \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "$cleanup_cmd" 2>/dev/null || true
    fi

    log "✅ Retention policy applied"
}

# Function: List backups on Synology
list_backups() {
    local retention="$1"
    local remote_path="${SYNOLOGY_BASE_PATH}/${retention}"

    log "📋 Backups in $retention/:"

    if [ -f "$SYNOLOGY_SSH_KEY" ]; then
        ssh -i "$SYNOLOGY_SSH_KEY" \
            -o StrictHostKeyChecking=no \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "ls -lh \"$remote_path\"/backup-*.tar.gz 2>/dev/null | tail -5" || echo "No backups found"
    else
        sshpass -p "$SYNOLOGY_PASSWORD" \
            ssh -o StrictHostKeyChecking=no \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "ls -lh \"$remote_path\"/backup-*.tar.gz 2>/dev/null | tail -5" || echo "No backups found"
    fi
}

# Main execution
main() {
    echo -e "${GREEN}=== Synology NAS Upload ===${NC}"
    echo ""

    check_configuration

    # Install sshpass if not available (for password auth fallback)
    if ! command -v sshpass &> /dev/null && [ ! -f "$SYNOLOGY_SSH_KEY" ]; then
        log "Installing sshpass for password authentication..."
        sudo apt-get update -qq && sudo apt-get install -y sshpass -qq
    fi

    # Parse arguments
    if [ $# -lt 1 ]; then
        echo "Usage: $0 <backup-file> [retention-type]"
        echo ""
        echo "Retention types:"
        echo "  daily      - Keep last 7"
        echo "  weekly     - Keep last 4"
        echo "  monthly    - Keep last 12"
        echo "  pre-deploy - Keep last 10"
        echo ""
        exit 1
    fi

    local backup_file="$1"
    local retention="${2:-daily}"

    # Validate retention type
    if [[ ! " ${RETENTION_PATHS[@]} " =~ " ${retention} " ]]; then
        log "❌ Invalid retention type: $retention"
        exit 1
    fi

    # Setup SSH key if needed
    if [ ! -f "$SYNOLOGY_SSH_KEY" ] && [ -z "$SYNOLOGY_PASSWORD" ]; then
        setup_ssh_key
    fi

    # Test connection
    if ! test_ssh_connection; then
        log "❌ Cannot connect to Synology. Please check:"
        log "   1. SYNOLOGY_HOST is correct and reachable"
        log "   2. Port forwarding is configured (if behind NAT)"
        log "   3. SSH key is properly installed on Synology"
        log "   4. Firewall allows SSH connections"
        exit 1
    fi

    # Create retention directories
    create_retention_dirs

    # Upload backup file
    if upload_file_rsync "$backup_file" "$retention"; then
        # Upload checksum
        upload_checksum "$backup_file" "$retention"

        # Apply retention policy
        case "$retention" in
            daily)      apply_retention_policy "$retention" 7 ;;
            weekly)     apply_retention_policy "$retention" 4 ;;
            monthly)    apply_retention_policy "$retention" 12 ;;
            pre-deploy) apply_retention_policy "$retention" 10 ;;
        esac

        # List current backups
        list_backups "$retention"

        log "✅ Upload completed successfully"
        exit 0
    else
        log "❌ Upload failed"
        exit 1
    fi
}

# Run if executed directly
if [ "${BASH_SOURCE[0]}" = "${0}" ]; then
    main "$@"
fi
