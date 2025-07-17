# MCP (Model Context Protocol) Dokumentation

## 🚀 Übersicht

Das Model Context Protocol (MCP) ist das zentrale System für die Kommunikation zwischen AI-Assistenten, Services und Tools in AskProAI. Es bietet eine standardisierte Schnittstelle für alle Integrationen und automatisiert viele Entwicklungsprozesse.

### Kernfunktionen
- 🔌 **Einheitliche API** für alle Services
- 🤖 **AI-Assistant Integration** (Claude, GitHub Copilot)
- 📊 **Automatisches Monitoring** und Health Checks
- 🛡️ **Built-in Security** mit Rate Limiting und Circuit Breakers
- 📝 **Automatische Dokumentation** und Tool-Discovery

### Vorteile für Entwickler
- Schnellere Entwicklung durch vorgefertigte Tools
- Konsistente Error-Handling und Logging
- Automatische Metriken und Performance-Tracking
- Einfache Integration neuer Services

## Quick Links
- [Architektur](#architektur)
- [Verfügbare Server](#verfügbare-server)
- [Shortcuts & Befehle](#shortcuts-befehle)
- [Beispiele](#beispiele)
- [Troubleshooting](#troubleshooting)

## Zugriff auf MCP Dashboard

**URL**: https://api.askproai.de/admin/mcp-dashboard
**Berechtigung**: Admin oder Developer Role

### Dashboard Features
- **System Health**: Echtzeit-Status aller MCP Server
- **Performance Metrics**: Request/Response Zeiten, Error Rates
- **Connection Pool**: Datenbankverbindungen und Auslastung
- **Queue Status**: Horizon und Job-Queue Übersicht
- **Recent Errors**: Letzte Fehler mit Details
- **Service Metrics**: Statistiken pro MCP Server

## MCP Server Typen

### 1. Interne Server
Direkt in Laravel implementierte Server für Core-Funktionalität:
- CalcomMCPServer - Kalenderverwaltung
- RetellMCPServer - Telefonie-Integration
- DatabaseMCPServer - Sichere Datenbankzugriffe
- AppointmentMCPServer - Terminbuchungen
- CustomerMCPServer - Kundenverwaltung
- Und viele mehr...

### 2. Externe Server
NPM-basierte Server für erweiterte Funktionen:
- sequential_thinking - Schrittweise Problemlösung
- postgres - Datenbankzugriff (MySQL mapping)
- notion - Notion Integration
- github - GitHub Integration
- memory_bank - Persistente Speicherung

## Schnellstart

### CLI Befehle
```bash
# MCP Status anzeigen
php artisan mcp:status

# Server Health Check
php artisan mcp:health

# Tool ausführen
php artisan mcp:execute <server> <tool> --arg=value

# Shortcuts nutzen
php artisan mcp book  # Termin buchen
php artisan mcp find  # Kunde suchen
```

### In Code verwenden
```php
use App\Services\MCP\MCPOrchestrator;

$mcp = app(MCPOrchestrator::class);
$result = $mcp->executeForTenant(
    tenantId: 1,
    service: 'appointment',
    tool: 'create_appointment',
    arguments: [
        'customer_phone' => '+49 123 456789',
        'service_id' => 'massage-60min',
        'date' => '2025-01-20',
        'time' => '14:00'
    ]
);
```

## Team Workflow

1. **Feature Development**: Nutze MCP Tools für schnelle Implementierung
2. **Testing**: Automatische Tests mit MCP Test Suite
3. **Monitoring**: Dashboard für Performance und Fehler
4. **Documentation**: Automatisch generiert aus Tool-Definitionen

## Support & Hilfe

- **Team Chat**: #mcp-support
- **Wiki**: wiki.askproai.de/mcp
- **Dashboard**: https://api.askproai.de/admin/mcp-dashboard
- **Logs**: /storage/logs/mcp.log