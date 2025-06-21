#!/bin/bash

# MCP Setup Script für AskProAI
# Dieses Script richtet die MCP Integration vollständig ein

echo "🚀 AskProAI MCP Setup Script"
echo "============================"
echo ""

# Farben für Output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Prüfe PHP Version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${YELLOW}PHP Version:${NC} $PHP_VERSION"

if [[ ! "$PHP_VERSION" =~ ^8\.3 ]]; then
    echo -e "${RED}❌ PHP 8.3 ist erforderlich für Laravel Loop!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ PHP Version OK${NC}"
echo ""

# Prüfe ob Laravel Loop installiert ist
if composer show kirschbaum-development/laravel-loop &> /dev/null; then
    echo -e "${GREEN}✅ Laravel Loop ist installiert${NC}"
else
    echo -e "${RED}❌ Laravel Loop nicht gefunden${NC}"
    echo "Führen Sie aus: composer require kirschbaum-development/laravel-loop --dev"
    exit 1
fi

echo ""
echo "📋 Verfügbare MCP Funktionen:"
echo "1. Laravel Loop (Artisan Commands)"
echo "2. Custom Database MCP (SQL Queries)"
echo "3. Cal.com MCP (Kalender Integration)"
echo "4. Retell.ai MCP (Telefon AI)"
echo "5. Sentry MCP (Error Tracking)"
echo ""

# Frage nach Admin Email
read -p "Admin Email für MCP Token: " ADMIN_EMAIL

if [ -z "$ADMIN_EMAIL" ]; then
    echo -e "${RED}❌ Email ist erforderlich!${NC}"
    exit 1
fi

# Erstelle MCP Token
echo ""
echo "🔑 Erstelle MCP Access Token..."
php artisan mcp:create-token "$ADMIN_EMAIL" --name="mcp-claude-access"

echo ""
echo -e "${YELLOW}📝 Nächste Schritte:${NC}"
echo ""
echo "1. Kopieren Sie den generierten Token (oben angezeigt)"
echo ""
echo "2. Für Laravel Loop in Claude Code:"
echo "   claude mcp add laravel-loop-mcp php $(pwd)/artisan loop:mcp:start"
echo ""
echo "3. Für HTTP MCP Endpoints:"
echo "   - URL: https://api.askproai.de/api/mcp"
echo "   - Header: Authorization: Bearer YOUR_TOKEN"
echo ""
echo "4. Testen Sie die Verbindung:"
echo "   php artisan mcp:test YOUR_TOKEN"
echo ""
echo -e "${GREEN}✅ Setup abgeschlossen!${NC}"
echo ""
echo "📚 Dokumentation: docs/MCP_SETUP_COMPLETE_GUIDE.md"
echo "📚 Integration Guide: docs/MCP_INTEGRATION_GUIDE.md"