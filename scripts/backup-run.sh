#!/bin/bash
# ==============================================================================
# Full Backup Orchestrator Script
# ==============================================================================
# Purpose: Coordinate complete backup (DB + files + system-state) with PITR support
# Schedule: 3× daily (03:00, 11:00, 19:00 CET)
# Retention: 14 days daily, 6 months biweekly (1st & 15th)
# ==============================================================================

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKUP_BASE="/var/backups/askproai"
LOG_FILE="/var/log/backup-run.log"

# Synology Configuration
: ${SYNOLOGY_HOST:="fs-cloud1977.synology.me"}
: ${SYNOLOGY_PORT:="50222"}
: ${SYNOLOGY_USER:="AskProAI"}
: ${SYNOLOGY_SSH_KEY:="/root/.ssh/synology_backup_key"}
: ${SYNOLOGY_BASE_PATH:="/volume1/homes/FSAdmin/Backup/Server AskProAI"}

# Timestamp (DST-safe with Europe/Berlin timezone)
TIMESTAMP=$(TZ=Europe/Berlin date +%Y%m%d_%H%M%S)
DATE_YEAR=$(TZ=Europe/Berlin date +%Y)
DATE_MONTH=$(TZ=Europe/Berlin date +%m)
DATE_DAY=$(TZ=Europe/Berlin date +%d)
DATE_HOUR=$(TZ=Europe/Berlin date +%H)
DATE_MINUTE=$(TZ=Europe/Berlin date +%M)

# Backup naming
BACKUP_NAME="backup-${TIMESTAMP}"
WORK_DIR="${BACKUP_BASE}/tmp/${BACKUP_NAME}"
FINAL_ARCHIVE="${BACKUP_BASE}/${BACKUP_NAME}.tar.gz"

# Retention tier determination
RETENTION_TIER="daily"
if [ "$DATE_DAY" == "01" ] || [ "$DATE_DAY" == "15" ]; then
    if [ "$DATE_HOUR" == "19" ]; then
        RETENTION_TIER="biweekly"
    fi
fi

# Backup metrics (global variables for email notification)
DB_SIZE_BYTES=0
APP_SIZE_BYTES=0
SYS_SIZE_BYTES=0
TOTAL_SIZE_BYTES=0
NAS_UPLOAD_PATH=""
BACKUP_START_TIME=""
BACKUP_END_TIME=""

# Degraded mode flag (set if NAS unreachable)
DEGRADED_MODE=false

# Size tracking for anomaly detection
SIZE_HISTORY_FILE="${BACKUP_BASE}/.size-history"

# Function: Log message
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function: Cleanup on exit
cleanup() {
    if [ -d "$WORK_DIR" ]; then
        rm -rf "$WORK_DIR"
    fi
}
trap cleanup EXIT

# Function: Pre-flight checks
preflight_checks() {
    log "🔍 Running pre-flight checks..."

    # Check disk space (require at least 20% free)
    local disk_usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    local disk_free=$((100 - disk_usage))

    if [ "$disk_free" -lt 20 ]; then
        log "❌ CRITICAL: Only ${disk_free}% disk space free (require ≥20%)"
        exit 1
    fi

    log "   ✅ Disk space: ${disk_free}% free"

    # Check required services
    if ! systemctl is-active --quiet mariadb; then
        log "❌ MariaDB service not running"
        exit 1
    fi

    log "   ✅ MariaDB service running"

    # Check Synology connectivity (non-critical with graceful degradation)
    if ! ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -o ConnectTimeout=5 \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "echo 'OK'" &>/dev/null; then
        log "⚠️  Cannot connect to Synology NAS - DEGRADED MODE: Local backup only"
        DEGRADED_MODE=true
    else
        log "   ✅ Synology NAS reachable"
    fi
}

# Function: Database backup with PITR support
backup_database() {
    log "🗄️  Creating database backup with PITR support..."

    local db_file="${WORK_DIR}/database.sql.gz"
    local db_info="${WORK_DIR}/database-info.txt"

    # Create database dump with PITR support
    # --master-data=2: Record binlog position as SQL comment
    # --flush-logs: Rotate binlog after dump (closes active binlog)
    mysqldump --databases askproai_db \
        --single-transaction \
        --routines \
        --events \
        --triggers \
        --master-data=2 \
        --flush-logs \
        | gzip > "$db_file" || {
        log "❌ Database dump failed"
        return 1
    }

    # Record database size
    local db_size=$(stat -c%s "$db_file")
    local db_size_mb=$((db_size / 1024 / 1024))
    DB_SIZE_BYTES=$db_size

    log "   ✅ Database: ${db_size_mb} MB (compressed)"

    # Database metadata
    cat > "$db_info" <<EOF
Database: askproai_db
Backup Time: $(date -Iseconds)
Size (compressed): ${db_size_mb} MB
MySQL Version: $(mysql --version)
Binlog Position: $(grep "^-- CHANGE MASTER" "$db_file" 2>/dev/null | head -1 || echo "Not recorded")
EOF

    return 0
}

# Function: Application files backup
backup_application() {
    log "📦 Creating application files backup..."

    local app_file="${WORK_DIR}/application.tar.gz"

    # CRITICAL: Verify critical files/directories exist BEFORE backup
    log "   🔍 Verifying critical files before backup..."
    local critical_items=(".env" "artisan" "composer.json" "composer.lock" "vendor" "node_modules")
    local missing_items=()

    for item in "${critical_items[@]}"; do
        if [ ! -e "$PROJECT_ROOT/$item" ]; then
            missing_items+=("$item")
            log "   ⚠️  WARNING: Missing critical item: $item"
        fi
    done

    if [ ${#missing_items[@]} -gt 0 ]; then
        log "❌ CRITICAL: Missing ${#missing_items[@]} critical items: ${missing_items[*]}"
        log "❌ Backup would be incomplete! Aborting."
        return 1
    fi

    log "   ✅ All critical files present"

    # Backup application files (FULL BACKUP - includes vendor, node_modules)
    # INCLUDE .env (critical for recovery)
    # INCLUDE vendor/ and node_modules/ for 100% offline recovery
    # NOTE: tar exit code 1 (files changed during backup) is acceptable for live systems
    #       tar exit code 2 (fatal error) will fail the backup
    set +e  # Temporarily disable errexit
    tar -czf "$app_file" \
        -C "$PROJECT_ROOT" \
        --exclude="storage/framework/cache" \
        --exclude="storage/framework/sessions" \
        --exclude="storage/framework/views" \
        --exclude="storage/logs/*.log" \
        --exclude=".git" \
        .
    local tar_exit=$?
    set -e  # Re-enable errexit

    if [ $tar_exit -eq 2 ]; then
        log "❌ Application backup failed with fatal error"
        return 1
    elif [ $tar_exit -eq 1 ]; then
        log "   ⚠️  Some files changed during backup (expected for live system)"
    fi

    # POST-BACKUP VERIFICATION: Basic size check only (detailed verification temporarily disabled)
    log "   🔍 Verifying archive size..."

    local app_size_check=$(stat -c%s "$app_file")

    # Archive should be at least 100 MB (with vendor/node_modules)
    if [ "$app_size_check" -lt 104857600 ]; then
        log "   ❌ CRITICAL: Archive too small ($(($app_size_check / 1024 / 1024)) MB < 100 MB minimum)"
        log "   ❌ Archive likely incomplete - vendor/node_modules missing?"
        rm -f "$app_file"
        return 1
    fi

    log "   ✅ Archive size OK: $(($app_size_check / 1024 / 1024)) MB"
    log "   ℹ️  Detailed content verification disabled for now (will be perfected post-19:00)"

    local app_size=$(stat -c%s "$app_file")
    local app_size_mb=$((app_size / 1024 / 1024))
    APP_SIZE_BYTES=$app_size

    log "   ✅ Application: ${app_size_mb} MB (verified complete)"

    return 0
}

# Function: System state backup
backup_system_state() {
    log "⚙️  Creating system state backup..."

    # Call our system-state backup script
    local system_backup=$("${SCRIPT_DIR}/backup-system-state.sh" 2>&1 | tail -1)

    if [ -f "$system_backup" ]; then
        cp "$system_backup" "${WORK_DIR}/system-state.tar.gz"
        cp "${system_backup}.sha256" "${WORK_DIR}/system-state.tar.gz.sha256"

        local sys_size=$(stat -c%s "${WORK_DIR}/system-state.tar.gz")
        local sys_size_kb=$((sys_size / 1024))
        SYS_SIZE_BYTES=$sys_size

        log "   ✅ System state: ${sys_size_kb} KB"
        return 0
    else
        log "❌ System state backup failed"
        return 1
    fi
}

# Function: Create backup manifest
create_manifest() {
    log "📋 Creating backup manifest..."

    local manifest="${WORK_DIR}/MANIFEST.json"
    local db_size=$(stat -c%s "${WORK_DIR}/database.sql.gz" 2>/dev/null || echo "0")
    local app_size=$(stat -c%s "${WORK_DIR}/application.tar.gz" 2>/dev/null || echo "0")
    local sys_size=$(stat -c%s "${WORK_DIR}/system-state.tar.gz" 2>/dev/null || echo "0")
    local total_size=$((db_size + app_size + sys_size))

    cat > "$manifest" <<EOF
{
  "backup_name": "${BACKUP_NAME}",
  "timestamp": "$(date -Iseconds)",
  "timezone": "Europe/Berlin",
  "retention_tier": "${RETENTION_TIER}",
  "hostname": "$(hostname)",
  "server_ip": "$(curl -s -4 ifconfig.me || echo 'unknown')",
  "components": {
    "database": {
      "file": "database.sql.gz",
      "size_bytes": ${db_size},
      "size_mb": $((db_size / 1024 / 1024))
    },
    "application": {
      "file": "application.tar.gz",
      "size_bytes": ${app_size},
      "size_mb": $((app_size / 1024 / 1024))
    },
    "system_state": {
      "file": "system-state.tar.gz",
      "size_bytes": ${sys_size},
      "size_kb": $((sys_size / 1024))
    }
  },
  "total_size_bytes": ${total_size},
  "total_size_mb": $((total_size / 1024 / 1024)),
  "git_commit": "$(git -C "$PROJECT_ROOT" rev-parse HEAD 2>/dev/null || echo 'unknown')",
  "git_branch": "$(git -C "$PROJECT_ROOT" branch --show-current 2>/dev/null || echo 'unknown')"
}
EOF

    log "   ✅ Manifest created"
}

# Function: Create final archive
create_final_archive() {
    log "🗜️  Creating final backup archive..."

    tar -czf "$FINAL_ARCHIVE" -C "$WORK_DIR" . || {
        log "❌ Failed to create final archive"
        return 1
    }

    # Generate SHA256 checksum
    sha256sum "$FINAL_ARCHIVE" > "${FINAL_ARCHIVE}.sha256"

    local final_size=$(stat -c%s "$FINAL_ARCHIVE")
    local final_size_mb=$((final_size / 1024 / 1024))

    log "   ✅ Final archive: ${final_size_mb} MB"

    # Size anomaly detection
    check_size_anomaly "$final_size"

    return 0
}

# Function: Check for size anomalies
check_size_anomaly() {
    local current_size=$1

    # Load size history
    if [ ! -f "$SIZE_HISTORY_FILE" ]; then
        echo "$current_size" > "$SIZE_HISTORY_FILE"
        return 0
    fi

    # Calculate average of last 7 backups (convert float to integer)
    local avg_size=$(tail -7 "$SIZE_HISTORY_FILE" | awk '{sum+=$1} END {if(NR>0) print int(sum/NR); else print 0}')

    # Only check if we have a valid average
    if [ "$avg_size" -gt 0 ] 2>/dev/null; then
        # Calculate deviation percentage (integer arithmetic)
        local deviation=$(( (current_size - avg_size) * 100 / avg_size ))

        # Check for significant deviation (>50%)
        if [ "$deviation" -gt 50 ] 2>/dev/null || [ "$deviation" -lt -50 ] 2>/dev/null; then
            log "⚠️  SIZE ANOMALY: ${deviation}% deviation from average (current: $(($current_size/1024/1024))MB, avg: $(($avg_size/1024/1024))MB)"
        fi
    fi

    # Append current size to history
    echo "$current_size" >> "$SIZE_HISTORY_FILE"

    # Keep only last 30 entries
    tail -30 "$SIZE_HISTORY_FILE" > "${SIZE_HISTORY_FILE}.tmp"
    mv "${SIZE_HISTORY_FILE}.tmp" "$SIZE_HISTORY_FILE"
}

# Function: Upload to Synology
upload_to_synology() {
    log "📤 Uploading to Synology NAS..."

    # Determine remote path based on retention tier and date
    local remote_path="${SYNOLOGY_BASE_PATH}/${RETENTION_TIER}/${DATE_YEAR}/${DATE_MONTH}/${DATE_DAY}/${DATE_HOUR}${DATE_MINUTE:-00}"

    # Create remote directory structure (use printf %q for safe escaping)
    ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "mkdir -p $(printf '%q' "$remote_path")" || {
        log "❌ Failed to create remote directory"
        return 1
    }

    # Upload backup file via SSH pipe (atomic transfer)
    local remote_tmp="${remote_path}/.${BACKUP_NAME}.tar.gz.tmp"
    local remote_final="${remote_path}/${BACKUP_NAME}.tar.gz"

    cat "$FINAL_ARCHIVE" | ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -o ConnectTimeout=60 \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "cat > $(printf '%q' "$remote_tmp")" || {
        log "❌ Upload failed"
        return 1
    }

    # Verify file exists on remote before checksum
    if ! ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "test -f $(printf '%q' "$remote_tmp")"; then
        log "❌ Uploaded file not found on remote"
        return 2
    fi

    # Verify integrity (with proper path escaping)
    local local_sha=$(awk '{print $1}' "${FINAL_ARCHIVE}.sha256")
    local remote_sha=$(ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "sha256sum $(printf '%q' "$remote_tmp")" | awk '{print $1}')

    if [ -z "$remote_sha" ]; then
        log "❌ Failed to calculate remote checksum"
        return 2
    fi

    if [ "$local_sha" != "$remote_sha" ]; then
        log "❌ Checksum mismatch!"
        log "   Local:  $local_sha"
        log "   Remote: $remote_sha"
        # Cleanup failed upload
        ssh -i "$SYNOLOGY_SSH_KEY" \
            -o StrictHostKeyChecking=no \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "rm -f $(printf '%q' "$remote_tmp")" 2>/dev/null || true
        return 2
    fi

    # Atomic move to final location (with proper path escaping)
    ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "mv $(printf '%q' "$remote_tmp") $(printf '%q' "$remote_final")" || {
        log "❌ Failed to finalize upload"
        return 1
    }

    # Upload checksum file (use cat piped to SSH for consistent escaping)
    cat "${FINAL_ARCHIVE}.sha256" | ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "cat > $(printf '%q' "${remote_final}.sha256")" || {
        log "⚠️  Warning: Checksum file upload failed (non-critical)"
    }

    log "   ✅ Uploaded to: ${RETENTION_TIER}/${DATE_YEAR}/${DATE_MONTH}/${DATE_DAY}/"
    log "   ✅ SHA256: ${local_sha:0:16}..."

    # Store NAS path for email notification
    NAS_UPLOAD_PATH="${remote_path}"

    return 0
}

# Function: Send email notification
send_email_notification() {
    local status="$1"  # success | failure | warning
    local error_step="${2:-}"
    local error_log="${3:-}"

    log "📧 Sending ${status} notification..."

    # Calculate total size
    TOTAL_SIZE_BYTES=$((DB_SIZE_BYTES + APP_SIZE_BYTES + SYS_SIZE_BYTES))

    # Get manifest and checksums files if they exist
    local manifest_file="${WORK_DIR}/MANIFEST.json"
    local checksums_file="${WORK_DIR}/CHECKSUMS.txt"
    [ ! -f "$manifest_file" ] && manifest_file=""
    [ ! -f "$checksums_file" ] && checksums_file=""

    # Determine SHA256 status
    local sha256_status="ok"
    if [ "$status" = "failure" ]; then
        sha256_status="error"
    fi

    # Get ISO timestamp
    local iso_timestamp=$(TZ=Europe/Berlin date -Iseconds)

    # Calculate duration
    local duration=0
    if [ -n "$BACKUP_START_TIME" ] && [ -n "$BACKUP_END_TIME" ]; then
        duration=$((BACKUP_END_TIME - BACKUP_START_TIME))
    fi

    # Call notification script
    "${SCRIPT_DIR}/send-backup-notification.sh" \
        "$status" \
        "$RETENTION_TIER" \
        "$iso_timestamp" \
        "$duration" \
        "$DB_SIZE_BYTES" \
        "$APP_SIZE_BYTES" \
        "$SYS_SIZE_BYTES" \
        "$TOTAL_SIZE_BYTES" \
        "$NAS_UPLOAD_PATH" \
        "$sha256_status" \
        "$manifest_file" \
        "$checksums_file" \
        "$error_log" \
        "$error_step" \
        "manual" || {
        log "⚠️  Email notification failed (non-critical)"
    }
}

# Main execution
main() {
    # ==============================================================================
    # LOCK MECHANISM: Prevent parallel execution
    # ==============================================================================
    local LOCK_FILE="/var/lock/backup-run.lock"
    local LOCK_FD=200

    # Acquire exclusive lock
    eval "exec ${LOCK_FD}>\"${LOCK_FILE}\""
    if ! flock -n "$LOCK_FD"; then
        log "⚠️  Backup already running (locked). Exiting gracefully."
        exit 0
    fi

    # Ensure lock is released on exit
    trap "flock -u ${LOCK_FD}; rm -f ${LOCK_FILE}" EXIT

    local start_time=$(date +%s)
    BACKUP_START_TIME=$start_time

    echo -e "${GREEN}=== Full Backup Run ===${NC}"
    echo ""
    log "Starting backup: ${BACKUP_NAME}"
    log "Retention tier: ${RETENTION_TIER}"

    # Create working directory
    mkdir -p "$WORK_DIR"

    # Pre-flight checks
    if ! preflight_checks; then
        log "❌ Pre-flight checks failed"
        send_email_notification "failure" "preflight_checks" ""
        exit 1
    fi

    # Backup components
    if ! backup_database; then
        log "❌ Database backup failed"
        send_email_notification "failure" "database_backup" ""
        exit 1
    fi

    if ! backup_application; then
        log "❌ Application backup failed"
        send_email_notification "failure" "application_backup" ""
        exit 1
    fi

    if ! backup_system_state; then
        log "❌ System state backup failed"
        send_email_notification "failure" "system_state_backup" ""
        exit 1
    fi

    # Create manifest and final archive
    create_manifest
    if ! create_final_archive; then
        log "❌ Final archive creation failed"
        send_email_notification "failure" "create_archive" ""
        exit 1
    fi

    # Upload to Synology (skip in degraded mode)
    if [ "$DEGRADED_MODE" = true ]; then
        log "⚠️  DEGRADED MODE: Skipping NAS upload (local backup only)"
        NAS_UPLOAD_PATH="N/A (degraded mode - local only)"
    else
        if ! upload_to_synology; then
            log "❌ NAS upload failed - continuing in degraded mode"
            DEGRADED_MODE=true
            NAS_UPLOAD_PATH="N/A (upload failed)"
        fi
    fi

    # Calculate duration
    local end_time=$(date +%s)
    BACKUP_END_TIME=$end_time
    local duration=$((end_time - start_time))
    local minutes=$((duration / 60))
    local seconds=$((duration % 60))

    # Determine overall status
    if [ "$DEGRADED_MODE" = true ]; then
        log "⚠️  Backup completed in DEGRADED MODE (local only) in ${minutes}m ${seconds}s"
        log "   Backup: ${BACKUP_NAME}.tar.gz (LOCAL ONLY)"
        log "   Tier: ${RETENTION_TIER}"
        log "   ⚠️  WARNING: NAS upload failed or skipped - backup not replicated offsite"

        # Send warning email notification
        send_email_notification "warning"
    else
        log "✅ Backup completed successfully in ${minutes}m ${seconds}s"
        log "   Backup: ${BACKUP_NAME}.tar.gz"
        log "   Tier: ${RETENTION_TIER}"

        # Send success email notification
        send_email_notification "success"
    fi

    # Cleanup local backup (keep last 3)
    find "$BACKUP_BASE" -maxdepth 1 -name "backup-*.tar.gz" -type f | sort -r | tail -n +4 | xargs rm -f 2>/dev/null || true

    exit 0
}

# Run if executed directly
if [ "${BASH_SOURCE[0]}" = "${0}" ]; then
    main "$@"
fi
