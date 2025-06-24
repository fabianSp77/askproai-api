# Quick MCP Setup Guide

## Sofort-Installation der gewünschten MCP Server

### 1. Sequential-Thinking installieren (empfohlen)
```bash
# Direkt ausführen
npx -y @modelcontextprotocol/server-sequential-thinking
```

### 2. Claude Desktop Konfiguration

Füge folgendes zu deiner Claude Desktop `config.json` hinzu:
- **macOS**: `~/Library/Application Support/Claude/config.json`
- **Windows**: `%APPDATA%\Claude\config.json`
- **Linux**: `~/.config/claude/config.json`

```json
{
  "mcpServers": {
    "sequential-thinking": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-sequential-thinking"]
    },
    "askproai-database": {
      "command": "php",
      "args": ["/var/www/api-gateway/artisan", "mcp:database"]
    },
    "askproai-webhook": {
      "command": "php",
      "args": ["/var/www/api-gateway/artisan", "mcp:webhook"]
    },
    "askproai-calcom": {
      "command": "php",
      "args": ["/var/www/api-gateway/artisan", "mcp:calcom"]
    }
  }
}
```

### 3. Server Status prüfen
```bash
cd /var/www/api-gateway
php artisan mcp:external status
```

### 4. Alle Server starten
```bash
php artisan mcp:external start
```

## Wichtige Hinweise

### PostgreSQL MCP für MySQL/MariaDB
Da AskProAI MySQL/MariaDB verwendet, nicht PostgreSQL, könnte der PostgreSQL MCP Server Kompatibilitätsprobleme haben. Alternativen:
1. Nutze den bereits vorhandenen `askproai-database` MCP Server
2. Suche nach einem MySQL-spezifischen MCP Server

### Verfügbare MCP Server Status
- ✅ **sequential-thinking**: Verfügbar und empfohlen
- ⚠️ **postgres**: Verfügbar, aber für PostgreSQL (nicht MySQL)
- ❓ **effect-docs**: Möglicherweise nicht im npm Registry
- ❓ **taskmaster-ai**: Möglicherweise nicht im npm Registry

### Bereits integrierte AskProAI MCP Server
Das System hat bereits diese MCP Server integriert:
- `mcp:webhook` - Webhook-Verarbeitung
- `mcp:calcom` - Cal.com Integration
- `mcp:database` - Datenbank-Zugriff (MySQL/MariaDB)
- `mcp:queue` - Queue-Management
- `mcp:retell` - Retell.ai Integration
- `mcp:stripe` - Payment Processing

## Sofort loslegen

1. **Sequential-Thinking testen:**
   ```bash
   npx -y @modelcontextprotocol/server-sequential-thinking
   ```

2. **In Claude Desktop:**
   - Starte Claude Desktop neu nach config.json Änderung
   - Die MCP Server erscheinen im Tools-Menü

3. **AskProAI MCP Server nutzen:**
   ```bash
   # Datenbank-Abfragen
   php artisan mcp:database
   
   # Webhook-Status
   php artisan mcp:webhook
   ```

## Troubleshooting

**MCP Server nicht sichtbar in Claude:**
1. Claude Desktop neu starten
2. Prüfe config.json Syntax (JSON Validator nutzen)
3. Prüfe Pfade sind absolut, nicht relativ

**Server startet nicht:**
1. Node.js Version prüfen: `node --version` (min. v18)
2. NPM Cache leeren: `npm cache clean --force`
3. Logs prüfen: `tail -f storage/logs/laravel.log`