#!/bin/bash
#
# backup-run.sh - Wrapper for automated backups via cron
#
# Created: 2026-01-15 after discovering this script was missing
# Incident: Production database was deleted by PHPUnit tests
#
# Cron schedule: 0 3,11,19 * * * (3x daily at 03:00, 11:00, 19:00)
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_PREFIX="[$(date '+%Y-%m-%d %H:%M:%S')] backup-run.sh:"

echo "$LOG_PREFIX Starting automated backup..."

# Use the ultimate backup script
if [ -f "$SCRIPT_DIR/golden-backup-v2-ultimate.sh" ]; then
    echo "$LOG_PREFIX Running golden-backup-v2-ultimate.sh"
    bash "$SCRIPT_DIR/golden-backup-v2-ultimate.sh"
    EXIT_CODE=$?

    if [ $EXIT_CODE -eq 0 ]; then
        echo "$LOG_PREFIX Backup completed successfully"
    else
        echo "$LOG_PREFIX ERROR: Backup failed with exit code $EXIT_CODE"
        exit $EXIT_CODE
    fi
else
    echo "$LOG_PREFIX ERROR: golden-backup-v2-ultimate.sh not found!"
    echo "$LOG_PREFIX Looking for alternative backup scripts..."

    # Fallback to comprehensive-backup.sh if available
    if [ -f "$SCRIPT_DIR/comprehensive-backup.sh" ]; then
        echo "$LOG_PREFIX Running comprehensive-backup.sh as fallback"
        bash "$SCRIPT_DIR/comprehensive-backup.sh"
    else
        echo "$LOG_PREFIX ERROR: No backup script found!"
        exit 1
    fi
fi

echo "$LOG_PREFIX Backup run completed"
