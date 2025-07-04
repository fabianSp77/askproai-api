#!/bin/bash

# Retell.ai Integration Backup & Restore Script
# Sichert und stellt kritische Dateien wieder her

BACKUP_DIR="/var/www/api-gateway/backups/retell"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Farben für Output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Kritische Dateien
CRITICAL_FILES=(
    "app/Http/Controllers/Api/RetellWebhookWorkingController.php"
    "app/Helpers/RetellDataExtractor.php"
    "routes/api.php"
    "manual-retell-import.php"
    "cleanup-stale-calls.php"
)

function show_help {
    echo "Retell.ai Integration Backup & Restore"
    echo ""
    echo "Usage: $0 [backup|restore|list|test]"
    echo ""
    echo "Commands:"
    echo "  backup   - Create backup of all critical files"
    echo "  restore  - Restore from a backup"
    echo "  list     - List available backups"
    echo "  test     - Test current integration"
    echo ""
}

function create_backup {
    echo -e "${GREEN}Creating backup...${NC}"
    
    # Erstelle Backup-Verzeichnis
    BACKUP_PATH="$BACKUP_DIR/$TIMESTAMP"
    mkdir -p "$BACKUP_PATH"
    
    # Kopiere alle kritischen Dateien
    for file in "${CRITICAL_FILES[@]}"; do
        if [ -f "$file" ]; then
            # Erstelle Verzeichnisstruktur im Backup
            dir=$(dirname "$file")
            mkdir -p "$BACKUP_PATH/$dir"
            
            # Kopiere Datei
            cp "$file" "$BACKUP_PATH/$file"
            echo -e "  ✓ Backed up: $file"
        else
            echo -e "  ${YELLOW}⚠ File not found: $file${NC}"
        fi
    done
    
    # Speichere auch .env Variablen (nur Retell-bezogen)
    grep "RETELL_" .env > "$BACKUP_PATH/retell.env"
    echo -e "  ✓ Backed up: Retell environment variables"
    
    # Erstelle Info-Datei
    cat > "$BACKUP_PATH/backup_info.txt" << EOF
Backup created: $TIMESTAMP
Laravel version: $(php artisan --version)
PHP version: $(php -v | head -n 1)
Status: Working configuration as of $(date)
EOF
    
    echo -e "${GREEN}✅ Backup created successfully at: $BACKUP_PATH${NC}"
}

function list_backups {
    echo -e "${GREEN}Available backups:${NC}"
    
    if [ ! -d "$BACKUP_DIR" ]; then
        echo "No backups found."
        return
    fi
    
    for backup in $(ls -1 "$BACKUP_DIR" | sort -r); do
        if [ -f "$BACKUP_DIR/$backup/backup_info.txt" ]; then
            echo ""
            echo "📁 Backup: $backup"
            cat "$BACKUP_DIR/$backup/backup_info.txt" | sed 's/^/   /'
        fi
    done
}

function restore_backup {
    if [ -z "$1" ]; then
        echo -e "${RED}Error: Please specify backup timestamp${NC}"
        echo "Usage: $0 restore TIMESTAMP"
        echo ""
        list_backups
        return 1
    fi
    
    BACKUP_PATH="$BACKUP_DIR/$1"
    
    if [ ! -d "$BACKUP_PATH" ]; then
        echo -e "${RED}Error: Backup not found: $1${NC}"
        return 1
    fi
    
    echo -e "${YELLOW}⚠️  WARNING: This will restore files from backup: $1${NC}"
    echo "Current files will be backed up with .before_restore suffix"
    echo ""
    read -p "Continue? (y/N) " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Restore cancelled."
        return
    fi
    
    echo -e "${GREEN}Restoring from backup...${NC}"
    
    # Sichere aktuelle Dateien
    for file in "${CRITICAL_FILES[@]}"; do
        if [ -f "$file" ]; then
            cp "$file" "$file.before_restore"
            echo -e "  ✓ Current version saved: $file.before_restore"
        fi
    done
    
    # Restore Dateien
    for file in "${CRITICAL_FILES[@]}"; do
        if [ -f "$BACKUP_PATH/$file" ]; then
            cp "$BACKUP_PATH/$file" "$file"
            echo -e "  ✓ Restored: $file"
        fi
    done
    
    echo ""
    echo -e "${GREEN}✅ Restore completed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Clear Laravel cache: php artisan optimize:clear"
    echo "2. Restart services: sudo systemctl restart php8.3-fpm"
    echo "3. Test integration: php test-retell-real-data.php"
}

function test_integration {
    echo -e "${GREEN}Testing Retell.ai Integration...${NC}"
    echo ""
    
    # Test 1: Check Route
    echo "1. Checking route..."
    if grep -q "webhook-simple" routes/api.php; then
        echo -e "  ✓ Route found"
    else
        echo -e "  ${RED}✗ Route missing!${NC}"
    fi
    
    # Test 2: Check Controller
    echo "2. Checking controller..."
    if [ -f "app/Http/Controllers/Api/RetellWebhookWorkingController.php" ]; then
        echo -e "  ✓ Controller exists"
    else
        echo -e "  ${RED}✗ Controller missing!${NC}"
    fi
    
    # Test 3: Check Helper
    echo "3. Checking data extractor..."
    if [ -f "app/Helpers/RetellDataExtractor.php" ]; then
        echo -e "  ✓ Helper exists"
    else
        echo -e "  ${RED}✗ Helper missing!${NC}"
    fi
    
    # Test 4: Run actual test
    echo "4. Running integration test..."
    php test-retell-real-data.php
}

# Main script logic
case "$1" in
    backup)
        create_backup
        ;;
    restore)
        restore_backup "$2"
        ;;
    list)
        list_backups
        ;;
    test)
        test_integration
        ;;
    *)
        show_help
        ;;
esac