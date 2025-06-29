#!/bin/bash

# Retell.ai Quick Setup Script
# Dieses Skript stellt die Retell-Integration schnell wieder her

echo "=== Retell.ai Quick Setup ==="
echo "Datum: $(date)"
echo ""

# Farben für Output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Health Check
echo -e "${YELLOW}1. Führe Health Check aus...${NC}"
php retell-health-check.php
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Health Check erfolgreich${NC}"
else
    echo -e "${RED}❌ Health Check hat Probleme gefunden${NC}"
fi

# 2. Agent Sync
echo -e "\n${YELLOW}2. Synchronisiere Retell Agent...${NC}"
php sync-retell-agent.php
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Agent synchronisiert${NC}"
else
    echo -e "${RED}❌ Agent-Sync fehlgeschlagen${NC}"
fi

# 3. Import Calls
echo -e "\n${YELLOW}3. Importiere Anrufe...${NC}"
php fetch-retell-calls.php
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Anrufe importiert${NC}"
else
    echo -e "${RED}❌ Call-Import fehlgeschlagen${NC}"
fi

# 4. Check Horizon
echo -e "\n${YELLOW}4. Prüfe Horizon Status...${NC}"
php artisan horizon:status | grep -q "running"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Horizon läuft${NC}"
else
    echo -e "${YELLOW}⚠️ Horizon läuft nicht - starte Horizon...${NC}"
    nohup php artisan horizon > /dev/null 2>&1 &
    sleep 2
    php artisan horizon:status | grep -q "running"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Horizon gestartet${NC}"
    else
        echo -e "${RED}❌ Horizon konnte nicht gestartet werden${NC}"
    fi
fi

# 5. Clear Cache
echo -e "\n${YELLOW}5. Leere Cache...${NC}"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
echo -e "${GREEN}✅ Cache geleert${NC}"

# 6. Final Health Check
echo -e "\n${YELLOW}6. Finaler Health Check...${NC}"
php retell-health-check.php
if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✅ SYSTEM IST BEREIT!${NC}"
    echo -e "${GREEN}Retell.ai Integration ist vollständig funktionsfähig.${NC}"
else
    echo -e "\n${RED}❌ Es gibt noch Probleme - bitte Logs prüfen${NC}"
fi

echo -e "\n=== Setup abgeschlossen ==="