#!/bin/bash
# Setup-Skript für Dokumentations-Auto-Update-System

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}===============================================${NC}"
echo -e "${BLUE}📚 AskProAI Dokumentations-Auto-Update Setup${NC}"
echo -e "${BLUE}===============================================${NC}"
echo ""

# Prüfe ob im richtigen Verzeichnis
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Fehler: Bitte führe dieses Skript im Projekt-Root aus${NC}"
    exit 1
fi

echo -e "${YELLOW}1. Installiere Git Hooks...${NC}"

# Erstelle Hooks-Verzeichnis falls nicht vorhanden
mkdir -p .githooks

# Mache alle Hooks ausführbar
chmod +x .githooks/*

# Setze Git Config
git config core.hooksPath .githooks

echo -e "${GREEN}✅ Git Hooks installiert${NC}"
echo ""

echo -e "${YELLOW}2. Prüfe Laravel Command...${NC}"

# Teste ob Command funktioniert
if php artisan docs:check-updates --help > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Laravel Command funktioniert${NC}"
else
    echo -e "${RED}❌ Laravel Command nicht gefunden${NC}"
    echo -e "${YELLOW}   Stelle sicher dass DocsCheckUpdates.php existiert${NC}"
fi
echo ""

echo -e "${YELLOW}3. Prüfe Abhängigkeiten...${NC}"

# Prüfe jq für JSON parsing
if command -v jq &> /dev/null; then
    echo -e "${GREEN}✅ jq installiert${NC}"
else
    echo -e "${YELLOW}⚠️  jq nicht installiert (optional)${NC}"
    echo -e "   Installiere mit: sudo apt-get install jq"
fi

# Prüfe git
if command -v git &> /dev/null; then
    echo -e "${GREEN}✅ git installiert${NC}"
else
    echo -e "${RED}❌ git nicht installiert${NC}"
fi
echo ""

echo -e "${YELLOW}4. Initiale Dokumentations-Analyse...${NC}"

# Führe initiale Analyse aus
php artisan docs:check-updates

echo ""
echo -e "${BLUE}===============================================${NC}"
echo -e "${GREEN}✅ Setup abgeschlossen!${NC}"
echo -e "${BLUE}===============================================${NC}"
echo ""
echo -e "${YELLOW}Nächste Schritte:${NC}"
echo -e "1. Teste mit einem Commit:"
echo -e "   ${BLUE}git add . && git commit -m \"test: documentation hooks\"${NC}"
echo -e ""
echo -e "2. Konfiguriere Umgebungsvariablen in .env:"
echo -e "   ${BLUE}DOCS_AUTO_UPDATE=true${NC}"
echo -e "   ${BLUE}DOCS_FRESHNESS_THRESHOLD=30${NC}"
echo -e "   ${BLUE}DOCS_MIN_HEALTH_SCORE=50${NC}"
echo -e ""
echo -e "3. Aktiviere automatische Checks (optional):"
echo -e "   Füge zu app/Console/Kernel.php hinzu:"
echo -e "   ${BLUE}\$schedule->command('docs:check-updates')->daily();${NC}"
echo -e ""
echo -e "${GREEN}Happy Documenting! 📚${NC}"