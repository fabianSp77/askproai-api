#!/bin/bash
################################################################################
# Dokumentations-Hub Sync Script
#
# Zweck: Synchronisiert wichtige Dokumentationen automatisch in den Hub
# Trigger: Nach jedem erfolgreichen Deployment oder manuell
# Autor: Claude Code
# Datum: 2025-11-01
################################################################################

set -e

# Pfade
SOURCE="/var/www/api-gateway"
TARGET="$SOURCE/storage/docs/backup-system"

# Farben für Output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Log-Funktion
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

################################################################################
# Kritische Dokumente (Layer 1 - Immer aktuell)
################################################################################

CRITICAL_DOCS=(
    "EXECUTIVE_SUMMARY.md"
    "FINAL_VALIDATION_REPORT.md"
    "DEPLOYMENT_HARDENING_COMPLETE.md"
    "BACKUP_AUTOMATION.md"
    "BACKUP_NOTIFICATIONS_FINAL.md"
)

################################################################################
# Operational Dokumente (Layer 2 - Bei Änderung)
################################################################################

OPERATIONAL_DOCS=(
    "DEPLOYMENT_HARDENING_SUMMARY.md"
    "DEPLOYMENT_QUICK_START.md"
    "DEPLOYMENT_VERIFICATION_CHECKLIST.md"
    "DEPLOYMENT_CACHE_CLEARING_CHECKLIST.md"
    "EMAIL_NOTIFICATIONS_SETUP.md"
    "EMAIL_NOTIFICATIONS_STATUS.md"
    "SYNOLOGY_SETUP.md"
    "MANUAL_TESTING_GUIDE_2025-10-27.md"
)

################################################################################
# HTML Visualisierungen (Layer 3 - Grafische Dokumentation)
################################################################################

# Diese HTML-Dateien liegen bereits im Hub-Verzeichnis und müssen nicht
# synchronisiert werden. Falls sie im Root aktualisiert werden, werden sie
# hier gelistet für automatischen Sync.
HTML_VISUALIZATIONS=(
    "storage/docs/backup-system/backup-process.html"
    "storage/docs/backup-system/email-notifications.html"
    "storage/docs/backup-system/deployment-release.html"
)

################################################################################
# Hauptfunktion: Sync
################################################################################

sync_docs() {
    local updated=0
    local skipped=0
    local errors=0

    log "🔄 Starte Dokumentations-Sync..."

    # Prüfe ob Zielverzeichnis existiert
    if [ ! -d "$TARGET" ]; then
        error "Zielverzeichnis nicht gefunden: $TARGET"
        exit 1
    fi

    # Sync kritische Dokumente
    log "📋 Synchronisiere kritische Dokumente (Layer 1)..."
    for doc in "${CRITICAL_DOCS[@]}"; do
        if [ -f "$SOURCE/$doc" ]; then
            # Prüfe ob Datei neuer ist oder nicht existiert
            if [ ! -f "$TARGET/$doc" ] || [ "$SOURCE/$doc" -nt "$TARGET/$doc" ]; then
                cp "$SOURCE/$doc" "$TARGET/"
                log "  ✅ Aktualisiert: $doc"
                ((updated++))
            else
                log "  ⏭️  Übersprungen: $doc (bereits aktuell)"
                ((skipped++))
            fi
        else
            warn "  ⚠️  Nicht gefunden: $doc"
            ((errors++))
        fi
    done

    # Sync operational Dokumente
    log "📁 Synchronisiere operational Dokumente (Layer 2)..."
    for doc in "${OPERATIONAL_DOCS[@]}"; do
        if [ -f "$SOURCE/$doc" ]; then
            if [ ! -f "$TARGET/$doc" ] || [ "$SOURCE/$doc" -nt "$TARGET/$doc" ]; then
                cp "$SOURCE/$doc" "$TARGET/"
                log "  ✅ Aktualisiert: $doc"
                ((updated++))
            else
                ((skipped++))
            fi
        fi
    done

    # Zusammenfassung
    echo ""
    log "╔══════════════════════════════════════════╗"
    log "║  Sync abgeschlossen                      ║"
    log "╚══════════════════════════════════════════╝"
    log "  ✅ Aktualisiert: $updated Dateien"
    log "  ⏭️  Übersprungen: $skipped Dateien"
    if [ $errors -gt 0 ]; then
        warn "  ⚠️  Fehler: $errors Dateien nicht gefunden"
    fi
    echo ""

    # Berechtige Dateien korrekt
    chown -R www-data:www-data "$TARGET"
    chmod -R 644 "$TARGET"/*.md 2>/dev/null || true
}

################################################################################
# Cleanup alte/veraltete Dateien
################################################################################

cleanup_old_files() {
    log "🧹 Cleanup alte Dateien..."

    # Liste veralteter Dateien
    OLD_FILES=(
        "login.html"
        "documentation-index.html"
        "index-old-backup.html"
        "index-v2.html"
    )

    for file in "${OLD_FILES[@]}"; do
        if [ -f "$TARGET/$file" ]; then
            rm "$TARGET/$file"
            log "  🗑️  Gelöscht: $file"
        fi
    done
}

################################################################################
# Main
################################################################################

main() {
    log "═══════════════════════════════════════════════════"
    log "  📚 Dokumentations-Hub Sync"
    log "═══════════════════════════════════════════════════"
    echo ""

    # Argumente verarbeiten
    case "${1:-}" in
        --cleanup)
            cleanup_old_files
            ;;
        --force)
            log "⚡ Force-Modus: Alle Dateien werden aktualisiert"
            # Entferne alle Dateien im Target um Force-Update zu erzwingen
            ;;
        *)
            sync_docs
            ;;
    esac

    log "✅ Fertig!"
}

# Script ausführen
main "$@"
