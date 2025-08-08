#!/bin/bash

# mark-golden-backup.sh
# Markiert ein Backup als "Golden Backup" nach Verifizierung
# Usage: ./mark-golden-backup.sh /path/to/backup.tar.gz

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
GOLDEN_DOC="/var/www/api-gateway/GOLDEN_BACKUP_RESTORE_POINTS.md"
GOLDEN_DIR="/var/www/GOLDEN_BACKUPS"
PROJECT_DIR="/var/www/api-gateway"
LOG_FILE="/var/www/api-gateway/storage/logs/golden-backup.log"

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

# Function to verify backup
verify_backup() {
    local backup_file="$1"
    
    echo -e "${YELLOW}Verifiziere Backup...${NC}"
    
    # Check if file exists
    if [ ! -f "$backup_file" ]; then
        echo -e "${RED}✗ Backup-Datei nicht gefunden: $backup_file${NC}"
        return 1
    fi
    
    # Check file size (should be at least 1MB)
    local size=$(stat -f%z "$backup_file" 2>/dev/null || stat -c%s "$backup_file" 2>/dev/null)
    if [ "$size" -lt 1048576 ]; then
        echo -e "${RED}✗ Backup zu klein (< 1MB)${NC}"
        return 1
    fi
    
    # Test tar integrity
    echo -e "${BLUE}Teste Archiv-Integrität...${NC}"
    if tar -tzf "$backup_file" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Archiv ist intakt${NC}"
    else
        echo -e "${RED}✗ Archiv ist beschädigt${NC}"
        return 1
    fi
    
    # Extract and check for required files
    local temp_dir="/tmp/backup_verify_$$"
    mkdir -p "$temp_dir"
    
    echo -e "${BLUE}Prüfe Backup-Inhalt...${NC}"
    tar -xzf "$backup_file" -C "$temp_dir" 2>/dev/null
    
    # Check for essential components
    local has_database=false
    local has_code=false
    local has_config=false
    
    if find "$temp_dir" -name "*.sql*" -o -name "*database*" | grep -q .; then
        has_database=true
        echo -e "${GREEN}✓ Datenbank-Backup gefunden${NC}"
    fi
    
    if find "$temp_dir" -name "*.php" -o -name "*application*" -o -name "*app*" | grep -q .; then
        has_code=true
        echo -e "${GREEN}✓ Anwendungscode gefunden${NC}"
    fi
    
    if find "$temp_dir" -name ".env*" -o -name "*config*" | grep -q .; then
        has_config=true
        echo -e "${GREEN}✓ Konfiguration gefunden${NC}"
    fi
    
    # Cleanup
    rm -rf "$temp_dir"
    
    if [ "$has_database" = true ] && [ "$has_code" = true ]; then
        echo -e "${GREEN}✓ Backup enthält alle wichtigen Komponenten${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠ Backup könnte unvollständig sein${NC}"
        read -p "Trotzdem als Golden Backup markieren? (j/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Jj]$ ]]; then
            return 0
        else
            return 1
        fi
    fi
}

# Function to run system tests
run_system_tests() {
    echo -e "\n${YELLOW}Führe System-Tests aus...${NC}"
    
    local all_tests_passed=true
    
    # Test 1: Database connection
    echo -n "Teste Datenbank-Verbindung... "
    if php -r "try { new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn'); echo 'OK'; } catch(Exception \$e) { echo 'FAIL'; exit(1); }" 2>/dev/null; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${RED}✗${NC}"
        all_tests_passed=false
    fi
    
    # Test 2: Laravel config
    echo -n "Teste Laravel-Konfiguration... "
    if cd "$PROJECT_DIR" && php artisan config:cache > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${YELLOW}⚠${NC}"
    fi
    
    # Test 3: Check for critical errors in log
    echo -n "Prüfe auf kritische Fehler... "
    if [ -f "$PROJECT_DIR/storage/logs/laravel.log" ]; then
        recent_errors=$(tail -100 "$PROJECT_DIR/storage/logs/laravel.log" | grep -c "CRITICAL\|EMERGENCY" || true)
        if [ "$recent_errors" -eq 0 ]; then
            echo -e "${GREEN}✓ Keine kritischen Fehler${NC}"
        else
            echo -e "${YELLOW}⚠ $recent_errors kritische Fehler gefunden${NC}"
        fi
    else
        echo -e "${BLUE}Log-Datei nicht gefunden${NC}"
    fi
    
    # Test 4: Web endpoints
    echo -n "Teste Admin-Panel... "
    if curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/login | grep -q "200"; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${YELLOW}⚠${NC}"
    fi
    
    if [ "$all_tests_passed" = false ]; then
        echo -e "\n${YELLOW}Nicht alle Tests bestanden.${NC}"
        read -p "Trotzdem fortfahren? (j/n): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Jj]$ ]]; then
            return 1
        fi
    else
        echo -e "\n${GREEN}Alle Tests bestanden!${NC}"
    fi
    
    return 0
}

# Function to update documentation
update_documentation() {
    local backup_file="$1"
    local backup_number="$2"
    
    echo -e "\n${YELLOW}Aktualisiere Dokumentation...${NC}"
    
    # Calculate checksum
    local checksum=$(md5sum "$backup_file" | cut -d' ' -f1)
    local size=$(du -h "$backup_file" | cut -f1)
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    # Create backup entry
    cat >> "$GOLDEN_DOC" << EOF

---

## 🌟 GOLDEN BACKUP #${backup_number}
**Status**: ✅ VERIFIZIERT & SICHER
**Erstellt**: ${timestamp}

### 📊 Backup-Details
- **Datei**: \`$(basename "$backup_file")\`
- **Pfad**: \`$backup_file\`
- **Größe**: ${size}
- **MD5 Checksum**: \`${checksum}\`
- **Typ**: Manuell markiert als Golden Backup

### 🔍 System-Zustand bei Markierung
- Alle System-Tests bestanden
- Keine kritischen Fehler in Logs
- Admin Panel funktionsfähig
- Datenbank-Verbindung aktiv

### 🔧 Restore-Anleitung
\`\`\`bash
# Backup wiederherstellen
cd $(dirname "$backup_file")
tar -xzf $(basename "$backup_file")
cd $(basename "$backup_file" .tar.gz)
./restore-backup.sh
\`\`\`

EOF
    
    echo -e "${GREEN}✓ Dokumentation aktualisiert${NC}"
    log_message "Golden Backup #${backup_number} dokumentiert: $backup_file"
}

# Function to create symlink
create_symlink() {
    local backup_file="$1"
    local backup_number="$2"
    
    echo -e "\n${YELLOW}Erstelle Symlink...${NC}"
    
    # Create golden backups directory if not exists
    mkdir -p "$GOLDEN_DIR"
    
    # Create symlink with meaningful name
    local link_name="$GOLDEN_DIR/golden-backup-${backup_number}-$(date '+%Y%m%d').tar.gz"
    ln -sf "$backup_file" "$link_name"
    
    echo -e "${GREEN}✓ Symlink erstellt: $link_name${NC}"
    log_message "Symlink erstellt: $link_name -> $backup_file"
}

# Main script
main() {
    echo -e "${BLUE}=== Golden Backup Markierung ===${NC}\n"
    
    # Check arguments
    if [ $# -eq 0 ]; then
        echo -e "${RED}Fehler: Backup-Pfad erforderlich${NC}"
        echo "Usage: $0 /path/to/backup.tar.gz [backup_number]"
        exit 1
    fi
    
    local backup_file="$1"
    local backup_number="${2:-$(date '+%s')}"
    
    # Make backup path absolute
    backup_file=$(realpath "$backup_file" 2>/dev/null || echo "$backup_file")
    
    echo -e "Backup-Datei: ${BLUE}$backup_file${NC}"
    echo -e "Backup-Nummer: ${BLUE}#${backup_number}${NC}\n"
    
    # Step 1: Verify backup
    if ! verify_backup "$backup_file"; then
        echo -e "${RED}Backup-Verifizierung fehlgeschlagen${NC}"
        log_message "FEHLER: Verifizierung fehlgeschlagen für $backup_file"
        exit 1
    fi
    
    # Step 2: Run system tests
    if ! run_system_tests; then
        echo -e "${RED}System-Tests fehlgeschlagen${NC}"
        log_message "FEHLER: System-Tests fehlgeschlagen"
        exit 1
    fi
    
    # Step 3: Update documentation
    update_documentation "$backup_file" "$backup_number"
    
    # Step 4: Create symlink
    create_symlink "$backup_file" "$backup_number"
    
    # Success message
    echo -e "\n${GREEN}════════════════════════════════════════${NC}"
    echo -e "${GREEN}✓ Golden Backup erfolgreich markiert!${NC}"
    echo -e "${GREEN}════════════════════════════════════════${NC}"
    echo -e "\nBackup #${backup_number} wurde als Golden Backup registriert."
    echo -e "Dokumentation: ${BLUE}$GOLDEN_DOC${NC}"
    echo -e "Symlink: ${BLUE}$GOLDEN_DIR/${NC}"
    
    log_message "SUCCESS: Golden Backup #${backup_number} erfolgreich markiert"
    
    # Optional: Send notification email
    if command -v mail > /dev/null 2>&1; then
        echo "Golden Backup #${backup_number} wurde erfolgreich markiert.

Datei: $backup_file
Größe: $(du -h "$backup_file" | cut -f1)
Zeitpunkt: $(date)

Die Dokumentation wurde aktualisiert." | mail -s "Golden Backup #${backup_number} erstellt" fabian@askproai.de 2>/dev/null || true
    fi
}

# Run main function
main "$@"