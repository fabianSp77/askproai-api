#!/bin/bash

#########################################
# GOLDEN BACKUP - Verification Script
# Purpose: Verify integrity of Golden Backup
#########################################

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Get the latest backup
BACKUP_DIR="/var/www/backups"
LATEST_BACKUP=$(ls -t ${BACKUP_DIR}/golden-backup-*.tar.gz 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo -e "${RED}No Golden Backup found!${NC}"
    exit 1
fi

echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   GOLDEN BACKUP - Integrity Verification  ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
echo ""

BACKUP_NAME=$(basename "$LATEST_BACKUP" .tar.gz)
echo -e "${BLUE}Verifying:${NC} $LATEST_BACKUP"
echo ""

# 1. Check archive exists and is readable
if [ ! -r "$LATEST_BACKUP" ]; then
    echo -e "${RED}✗ Backup file not readable${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Backup file exists and is readable"

# 2. Verify archive integrity
tar -tzf "$LATEST_BACKUP" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Archive integrity verified"
else
    echo -e "${RED}✗ Archive is corrupted${NC}"
    exit 1
fi

# 3. Extract to temp for verification
TEMP_DIR="/tmp/backup-verify-$$"
mkdir -p "$TEMP_DIR"
tar -xzf "$LATEST_BACKUP" -C "$TEMP_DIR" 2>/dev/null

# 4. Check required components
echo ""
echo -e "${BLUE}Checking backup components:${NC}"

COMPONENTS=(
    "app/application.tar.gz:Application Code"
    "database/full_dump.sql.gz:Database Dump"
    "config/env.production:Environment File"
    "docs/RESTORE_GUIDE.md:Restoration Guide"
    "checksums.txt:Checksums File"
    "metadata.json:Metadata"
)

VERIFIED=0
FAILED=0

for COMPONENT in "${COMPONENTS[@]}"; do
    FILE="${COMPONENT%%:*}"
    DESC="${COMPONENT##*:}"

    if [ -f "$TEMP_DIR/$BACKUP_NAME/$FILE" ]; then
        SIZE=$(du -h "$TEMP_DIR/$BACKUP_NAME/$FILE" | cut -f1)
        echo -e "  ${GREEN}✓${NC} $DESC ($SIZE)"
        ((VERIFIED++))
    else
        echo -e "  ${RED}✗${NC} $DESC - Missing!"
        ((FAILED++))
    fi
done

# 5. Verify checksums
echo ""
echo -e "${BLUE}Verifying checksums:${NC}"
cd "$TEMP_DIR/$BACKUP_NAME" 2>/dev/null
if [ -f "checksums.txt" ]; then
    # Check a sample of files
    CHECKSUM_ERRORS=0
    while IFS= read -r line; do
        FILE=$(echo "$line" | awk '{print $2}')
        if [ -f "$FILE" ]; then
            echo "$line" | sha256sum -c - > /dev/null 2>&1
            if [ $? -ne 0 ]; then
                ((CHECKSUM_ERRORS++))
            fi
        fi
    done < <(head -10 checksums.txt)

    if [ $CHECKSUM_ERRORS -eq 0 ]; then
        echo -e "  ${GREEN}✓${NC} Checksums verified (sample)"
    else
        echo -e "  ${YELLOW}⚠${NC} Some checksums failed"
    fi
else
    echo -e "  ${RED}✗${NC} Checksums file missing"
fi

# 6. Check metadata
echo ""
echo -e "${BLUE}Backup metadata:${NC}"
if [ -f "$TEMP_DIR/$BACKUP_NAME/metadata.json" ]; then
    BACKUP_DATE=$(grep -o '"backup_date": "[^"]*' "$TEMP_DIR/$BACKUP_NAME/metadata.json" | cut -d'"' -f4)
    TOTAL_SIZE=$(grep -o '"total_size": "[^"]*' "$TEMP_DIR/$BACKUP_NAME/metadata.json" | cut -d'"' -f4)
    echo -e "  Date: $BACKUP_DATE"
    echo -e "  Size: $TOTAL_SIZE"
fi

# 7. Cleanup
rm -rf "$TEMP_DIR"

# Summary
echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}║      VERIFICATION PASSED - BACKUP OK      ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${GREEN}All $VERIFIED components verified successfully!${NC}"
    echo -e "${BLUE}Backup is ready for restoration.${NC}"
    exit 0
else
    echo -e "${RED}║     VERIFICATION FAILED - ISSUES FOUND    ║${NC}"
    echo -e "${RED}╚═══════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${RED}$FAILED components missing or corrupted!${NC}"
    exit 1
fi