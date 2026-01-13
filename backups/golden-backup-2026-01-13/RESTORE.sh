#!/bin/bash

#################################################
# Golden Backup Restore Script
# Datum: 2026-01-13
# Backup-ID: 02abf1262
#################################################

set -e

BACKUP_DIR="$(dirname "$(readlink -f "$0")")"
PROJECT_DIR="/var/www/api-gateway"
DB_NAME="askproai_db"
DB_USER="askproai_user"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║           GOLDEN BACKUP RESTORE - 2026-01-13               ║"
echo "║                   Commit: 02abf1262                        ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running from correct directory
if [ ! -f "$BACKUP_DIR/database-complete.sql.gz" ]; then
    echo -e "${RED}ERROR: database-complete.sql.gz not found!${NC}"
    echo "Please run this script from the backup directory."
    exit 1
fi

show_menu() {
    echo ""
    echo -e "${YELLOW}Wähle eine Option:${NC}"
    echo ""
    echo "  1) Full Restore (Datenbank + Code + Dependencies)"
    echo "  2) Code Only (tar.gz entpacken)"
    echo "  3) Database Only (MySQL restore)"
    echo "  4) Documentation Only (claudedocs)"
    echo "  5) Show Backup Info"
    echo "  6) Verify Backup Integrity"
    echo "  0) Exit"
    echo ""
    read -p "Option [0-6]: " choice
}

restore_code() {
    echo -e "${BLUE}► Restoring Code...${NC}"
    cd "$PROJECT_DIR"

    # Backup current state
    echo "  Creating safety backup of current state..."
    git stash -m "Pre-restore safety stash $(date +%Y%m%d-%H%M%S)" 2>/dev/null || true

    # Extract code
    echo "  Extracting code-complete.tar.gz..."
    tar -xzf "$BACKUP_DIR/code-complete.tar.gz"

    echo -e "${GREEN}✓ Code restored${NC}"
}

restore_database() {
    echo -e "${BLUE}► Restoring Database...${NC}"

    read -sp "MySQL Password for $DB_USER: " DB_PASS
    echo ""

    echo "  Importing database (this may take a while)..."
    gunzip -c "$BACKUP_DIR/database-complete.sql.gz" | mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"

    echo -e "${GREEN}✓ Database restored${NC}"
}

restore_docs() {
    echo -e "${BLUE}► Restoring Documentation...${NC}"
    cd "$PROJECT_DIR"

    tar -xzf "$BACKUP_DIR/claudedocs.tar.gz"

    echo -e "${GREEN}✓ Documentation restored${NC}"
}

install_dependencies() {
    echo -e "${BLUE}► Installing Dependencies...${NC}"
    cd "$PROJECT_DIR"

    echo "  Running composer install..."
    composer install --no-interaction

    echo "  Running npm install..."
    npm install

    echo "  Clearing caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    php artisan route:clear

    echo "  Running migrations..."
    php artisan migrate --force

    echo -e "${GREEN}✓ Dependencies installed${NC}"
}

full_restore() {
    echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}           FULL RESTORE STARTING                       ${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"

    restore_code
    restore_database
    restore_docs
    install_dependencies

    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}           FULL RESTORE COMPLETE!                      ${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
}

show_info() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}           BACKUP INFORMATION                          ${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo ""
    echo "Backup Directory: $BACKUP_DIR"
    echo ""
    echo "Files:"
    ls -lh "$BACKUP_DIR"/*.gz "$BACKUP_DIR"/*.txt 2>/dev/null || true
    echo ""
    echo "Git Commit at backup: 02abf126282e7a7569c9123594becc12ed9cf800"
    echo "Branch: main"
    echo ""
}

verify_integrity() {
    echo -e "${BLUE}► Verifying Backup Integrity...${NC}"

    ERRORS=0

    # Check gzip files
    for gz in "$BACKUP_DIR"/*.gz; do
        if gzip -t "$gz" 2>/dev/null; then
            echo -e "  ${GREEN}✓${NC} $(basename "$gz")"
        else
            echo -e "  ${RED}✗${NC} $(basename "$gz") - CORRUPT!"
            ERRORS=$((ERRORS + 1))
        fi
    done

    # Check required files exist
    for file in "database-complete.sql.gz" "code-complete.tar.gz" "claudedocs.tar.gz"; do
        if [ -f "$BACKUP_DIR/$file" ]; then
            echo -e "  ${GREEN}✓${NC} $file exists"
        else
            echo -e "  ${RED}✗${NC} $file MISSING!"
            ERRORS=$((ERRORS + 1))
        fi
    done

    echo ""
    if [ $ERRORS -eq 0 ]; then
        echo -e "${GREEN}All files verified successfully!${NC}"
    else
        echo -e "${RED}$ERRORS error(s) found!${NC}"
    fi
}

# Handle command line argument
if [ "$1" == "full" ]; then
    full_restore
    exit 0
fi

# Interactive menu
while true; do
    show_menu

    case $choice in
        1)
            full_restore
            ;;
        2)
            restore_code
            ;;
        3)
            restore_database
            ;;
        4)
            restore_docs
            ;;
        5)
            show_info
            ;;
        6)
            verify_integrity
            ;;
        0)
            echo "Bye!"
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid option${NC}"
            ;;
    esac
done
