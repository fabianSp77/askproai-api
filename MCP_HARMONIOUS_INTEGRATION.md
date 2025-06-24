# MCP Harmonische Integration - Vollständige Übersicht

## Überblick

Die AskProAI-Plattform verfügt jetzt über eine vollständig integrierte MCP-Architektur mit zwei Ebenen:

### 1. **Interne MCP-Server** (Laravel-basiert)
Diese Server sind Teil der Anwendung und bieten Geschäftslogik-Funktionen:
- Webhook-Verarbeitung
- Cal.com Integration
- Retell.ai Integration
- Datenbank-Operationen
- Queue-Management
- Payment-Processing
- Und weitere...

### 2. **Externe MCP-Server** (Node.js-basiert)
Diese Server erweitern Claudes Fähigkeiten:
- **sequential-thinking**: Schrittweises Denken und Problemlösung
- **postgres**: Direkte Datenbankzugriffe (für MySQL/MariaDB konfiguriert)
- **effect-docs**: Dokumentationsgenerierung
- **taskmaster-ai**: Erweiterte Aufgabenverwaltung

## Architektur-Diagramm

```
┌─────────────────────────────────────────────────────────────────┐
│                        Claude Desktop                             │
├─────────────────────────────────────────────────────────────────┤
│                      MCP Client Layer                             │
├─────────────┬───────────────────────────────┬───────────────────┤
│             │                               │                     │
│  Externe    │                               │    Interne         │
│  MCP Server │                               │    MCP Server      │
│  (Node.js)  │                               │    (Laravel)       │
│             │                               │                     │
│ ┌─────────┐ │                               │ ┌───────────────┐ │
│ │Sequential│ │                               │ │Webhook Server │ │
│ │Thinking │ │                               │ └───────┬───────┘ │
│ └─────────┘ │                               │         │         │
│             │                               │ ┌───────▼───────┐ │
│ ┌─────────┐ │                               │ │MCP Orchestrator│ │
│ │Postgres │ │                               │ └───────┬───────┘ │
│ │(MySQL)  │ │                               │         │         │
│ └─────────┘ │                               │ ┌───────▼───────┐ │
│             │                               │ │Service Layer  │ │
│ ┌─────────┐ │                               │ │- CalcomMCP    │ │
│ │Effect   │ │                               │ │- RetellMCP    │ │
│ │Docs     │ │                               │ │- DatabaseMCP  │ │
│ └─────────┘ │                               │ │- QueueMCP     │ │
│             │                               │ │- StripeMCP    │ │
│ ┌─────────┐ │                               │ └───────────────┘ │
│ │Taskmaster│ │                               │                   │
│ │AI       │ │                               │                   │
│ └─────────┘ │                               │                   │
└─────────────┴───────────────────────────────┴───────────────────┘
```

## Integration Details

### 1. Keine Konflikte
- Externe und interne MCP-Server arbeiten völlig unabhängig
- Keine Namenskonflikte oder Überschneidungen
- Verschiedene Technologie-Stacks (Node.js vs PHP/Laravel)

### 2. Gemeinsame Verwaltung
Alle MCP-Server können zentral verwaltet werden:

```bash
# Gesamtstatus anzeigen
php artisan mcp:status

# Detaillierter Status mit Metriken
php artisan mcp:status --detailed

# Live-Monitoring aller Server
php artisan mcp:monitor

# Externe Server verwalten
php artisan mcp:external start
php artisan mcp:external stop
php artisan mcp:external status
```

### 3. Service Provider Integration
Der `MCPServiceProvider` registriert jetzt auch den `ExternalMCPManager`:

```php
// app/Providers/MCPServiceProvider.php
$this->app->singleton(ExternalMCPManager::class);
```

### 4. Monitoring Integration
Der `mcp:monitor` Befehl zeigt jetzt beide Server-Typen:
- System Health (interne Server)
- External MCP Servers (externe Server)
- Performance Metriken
- Fehlerüberwachung

## Verwendung für Claude

### 1. Claude Desktop Konfiguration
Füge zu deiner `config.json`:

```json
{
  "mcpServers": {
    // Externe Server (Node.js)
    "sequential-thinking": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-sequential-thinking"]
    },
    
    // Interne Server (Laravel)
    "askproai-webhook": {
      "command": "php",
      "args": ["/var/www/api-gateway/artisan", "mcp:webhook"]
    },
    "askproai-database": {
      "command": "php",
      "args": ["/var/www/api-gateway/artisan", "mcp:database"]
    }
  }
}
```

### 2. Workflow-Beispiele

#### Beispiel 1: Komplexe Analyse mit Sequential Thinking
```
1. Claude nutzt sequential-thinking für schrittweise Analyse
2. Greift via askproai-database auf Daten zu
3. Verarbeitet Webhooks über askproai-webhook
```

#### Beispiel 2: Dokumentationsgenerierung
```
1. effect-docs (wenn verfügbar) für Doku-Generierung
2. askproai-database für Schema-Informationen
3. Kombinierte Ausgabe mit vollständiger Dokumentation
```

## Best Practices

### 1. Server-Start
```bash
# Beim System-Start alle Server starten
php artisan mcp:external start
php artisan horizon  # Für interne Queue-Verarbeitung
```

### 2. Monitoring
```bash
# Regelmäßige Health-Checks
php artisan mcp:monitor --interval=60

# Status-Check vor wichtigen Operationen
php artisan mcp:status
```

### 3. Troubleshooting
```bash
# Logs prüfen
tail -f storage/logs/laravel.log | grep MCP

# Einzelnen Server neu starten
php artisan mcp:external restart sequential_thinking

# Connection Pool Status
php artisan mcp:status --detailed
```

## Sicherheit

### 1. Isolation
- Externe Server haben keinen direkten Zugriff auf Laravel-Code
- Interne Server sind durch Laravel's Security-Layer geschützt
- Tenant-Isolation bleibt erhalten

### 2. Rate Limiting
- Gilt für beide Server-Typen
- Konfigurierbar pro Service
- Automatische Throttling bei Überlastung

### 3. Monitoring
- Alle Aktionen werden geloggt
- Metriken für Performance-Analyse
- Fehler-Tracking über Sentry

## Nächste Schritte

1. **Installation externer Server**:
   ```bash
   npm install -g @modelcontextprotocol/server-sequential-thinking
   ```

2. **Server starten**:
   ```bash
   php artisan mcp:external start
   ```

3. **Claude Desktop neu starten** nach config.json Änderung

4. **Testen** mit:
   ```bash
   php artisan mcp:status
   ```

Die Integration ist vollständig harmonisch und bereit für den produktiven Einsatz!