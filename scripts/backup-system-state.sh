#!/bin/bash
###############################################################################
# System State Backup Script
# Purpose: Capture system configuration and state for disaster recovery
# Output: Creates a tar.gz archive with system state information
###############################################################################

set -euo pipefail

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
WORK_DIR="/tmp/system-state-${TIMESTAMP}"
OUTPUT_FILE="/tmp/system-state-${TIMESTAMP}.tar.gz"

# Create working directory
mkdir -p "${WORK_DIR}"

# Function: Log message
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >&2
}

# Function: Capture cron jobs
capture_cron() {
    log "📋 Capturing cron jobs..."
    crontab -l > "${WORK_DIR}/root-crontab.txt" 2>/dev/null || echo "# No crontab for root" > "${WORK_DIR}/root-crontab.txt"

    if id -u deploy >/dev/null 2>&1; then
        crontab -u deploy -l > "${WORK_DIR}/deploy-crontab.txt" 2>/dev/null || echo "# No crontab for deploy" > "${WORK_DIR}/deploy-crontab.txt"
    fi
}

# Function: Capture NGINX configuration
capture_nginx() {
    log "🌐 Capturing NGINX configuration..."
    if [ -d /etc/nginx/sites-available ]; then
        mkdir -p "${WORK_DIR}/nginx"
        cp -r /etc/nginx/sites-available "${WORK_DIR}/nginx/" 2>/dev/null || true
        cp -r /etc/nginx/sites-enabled "${WORK_DIR}/nginx/" 2>/dev/null || true
        cp /etc/nginx/nginx.conf "${WORK_DIR}/nginx/" 2>/dev/null || true
    fi
}

# Function: Capture systemd services
capture_services() {
    log "⚙️  Capturing systemd services..."
    systemctl list-units --type=service --all > "${WORK_DIR}/systemd-services.txt" 2>/dev/null || true
    systemctl list-timers --all > "${WORK_DIR}/systemd-timers.txt" 2>/dev/null || true
}

# Function: Capture installed packages
capture_packages() {
    log "📦 Capturing installed packages..."
    dpkg -l > "${WORK_DIR}/installed-packages.txt" 2>/dev/null || true
    apt-mark showmanual > "${WORK_DIR}/manually-installed-packages.txt" 2>/dev/null || true
}

# Function: Capture PHP-FPM configuration
capture_php() {
    log "🐘 Capturing PHP configuration..."
    if [ -d /etc/php ]; then
        mkdir -p "${WORK_DIR}/php"
        cp -r /etc/php/*/fpm/pool.d "${WORK_DIR}/php/" 2>/dev/null || true
        cp /etc/php/*/fpm/php-fpm.conf "${WORK_DIR}/php/" 2>/dev/null || true
        php -v > "${WORK_DIR}/php/php-version.txt" 2>/dev/null || true
        php -m > "${WORK_DIR}/php/php-modules.txt" 2>/dev/null || true
    fi
}

# Function: Capture database configuration
capture_database() {
    log "🗄️  Capturing database configuration..."
    if [ -f /etc/mysql/my.cnf ]; then
        mkdir -p "${WORK_DIR}/mysql"
        cp /etc/mysql/my.cnf "${WORK_DIR}/mysql/" 2>/dev/null || true
        cp -r /etc/mysql/conf.d "${WORK_DIR}/mysql/" 2>/dev/null || true
        cp -r /etc/mysql/mariadb.conf.d "${WORK_DIR}/mysql/" 2>/dev/null || true
    fi
}

# Function: Capture system information
capture_system_info() {
    log "💻 Capturing system information..."
    uname -a > "${WORK_DIR}/system-info.txt"
    df -h > "${WORK_DIR}/disk-usage.txt"
    free -h > "${WORK_DIR}/memory-info.txt"
    ip addr > "${WORK_DIR}/network-interfaces.txt" 2>/dev/null || true
    hostnamectl > "${WORK_DIR}/hostname-info.txt" 2>/dev/null || true
}

# Function: Capture environment variables (sanitized)
capture_environment() {
    log "🌍 Capturing environment variables..."
    env | grep -v -E '(PASSWORD|SECRET|TOKEN|KEY)' > "${WORK_DIR}/environment.txt" 2>/dev/null || echo "# No env vars" > "${WORK_DIR}/environment.txt"
}

# Function: Create manifest
create_manifest() {
    log "📋 Creating manifest..."
    cat > "${WORK_DIR}/MANIFEST.txt" << EOF
System State Backup
===================
Timestamp: $(date -Iseconds)
Hostname: $(hostname)
System: $(uname -a)

Included Files:
$(find "${WORK_DIR}" -type f | sed "s|${WORK_DIR}/||" | sort)

Archive Created: ${TIMESTAMP}
EOF
}

# Main execution
main() {
    log "🏁 Starting system state backup..."

    # Capture all components
    capture_cron
    capture_nginx
    capture_services
    capture_packages
    capture_php
    capture_database
    capture_system_info
    capture_environment
    create_manifest

    # Create tarball
    log "📦 Creating archive..."
    tar -czf "${OUTPUT_FILE}" -C "$(dirname "${WORK_DIR}")" "$(basename "${WORK_DIR}")" 2>/dev/null

    # Create checksum
    sha256sum "${OUTPUT_FILE}" > "${OUTPUT_FILE}.sha256"

    # Cleanup working directory
    rm -rf "${WORK_DIR}"

    # Log completion
    local size=$(stat -c%s "${OUTPUT_FILE}")
    local size_kb=$((size / 1024))
    log "✅ System state backup complete: ${size_kb} KB"

    # Output the file path as LAST LINE (this is what backup-run.sh expects via tail -1)
    echo "${OUTPUT_FILE}"
}

# Run main function
main
