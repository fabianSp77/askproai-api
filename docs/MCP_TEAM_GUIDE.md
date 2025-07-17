# MCP (Model Context Protocol) Team Guide

## 🚀 Schnellstart für Entwickler

Diese Anleitung hilft dir, MCP-Server im AskProAI-Projekt effektiv zu nutzen.

### Was ist MCP?

Model Context Protocol (MCP) ist unsere standardisierte Schnittstelle für AI-Assistenten und Entwickler-Tools. Es ermöglicht:
- Strukturierte Kommunikation zwischen Services
- Wiederverwendbare Tool-Definitionen
- Automatische Dokumentation
- Konsistente Error-Handling

## 📋 Inhaltsverzeichnis

1. [Verfügbare MCP-Server](#verfügbare-mcp-server)
2. [MCP Dashboard](#mcp-dashboard)
3. [Schnellzugriff mit Shortcuts](#schnellzugriff-mit-shortcuts)
4. [Developer Assistant](#developer-assistant)
5. [Best Practices](#best-practices)
6. [Troubleshooting](#troubleshooting)

## 🔧 Verfügbare MCP-Server

### Interne MCP-Server

| Server | Beschreibung | Hauptfunktionen |
|--------|--------------|-----------------|
| **calcom** | Cal.com Integration | Termine verwalten, Kalender synchronisieren |
| **retell** | Retell.ai Integration | Anrufe verarbeiten, Transkripte abrufen |
| **database** | Datenbankoperationen | Sichere Queries, Datenanalyse |
| **appointment** | Terminverwaltung | Buchungen, Stornierungen, Verfügbarkeit |
| **customer** | Kundenverwaltung | Suche, Historie, Duplikaterkennung |
| **stripe** | Zahlungsverarbeitung | Rechnungen, Abonnements, Zahlungen |
| **queue** | Queue-Management | Jobs überwachen, Fehler analysieren |
| **webhook** | Webhook-Verarbeitung | Events empfangen und verarbeiten |
| **github** | GitHub Integration | Issues, PRs, Releases verwalten |
| **notion** | Notion Integration | Seiten erstellen, Datenbanken verwalten |
| **memory_bank** | Persistente Speicherung | Kontext speichern, Sessions verwalten |

### Externe MCP-Server

| Server | Beschreibung | Status |
|--------|--------------|--------|
| **sequential_thinking** | Schrittweise Problemlösung | Aktiv |
| **postgres** | Datenbankzugriff (MySQL mapping) | Aktiv |
| **github** | Erweiterte GitHub-Features | Optional |
| **notion** | Erweiterte Notion-Features | Optional |

## 💻 MCP Dashboard

### Zugriff
```
URL: https://api.askproai.de/admin/mcp-servers
Berechtigung: Admin oder Developer Role
```

### Features
- **Übersicht**: Alle MCP-Server auf einen Blick
- **Health Monitoring**: Echtzeit-Status aller Server
- **Quick Actions**: Test, Restart, Sync direkt aus dem Dashboard
- **Metriken**: Performance-Daten und Fehlerstatistiken
- **Integration Status**: GitHub-Notion, Cal.com, Retell.ai

### Dashboard-Bereiche
1. **Quick Stats**: Gesamtanzahl Server, aktive Server, Capabilities
2. **Internal Servers**: Alle internen MCP-Server mit Status
3. **External Servers**: NPM-basierte externe Server
4. **Integrations**: Status der Hauptintegrationen
5. **Recent Activities**: Letzte MCP-Aktivitäten aus Memory Bank

## ⚡ Schnellzugriff mit Shortcuts

### Basis-Shortcuts

```bash
# Termin buchen
php artisan mcp book
php artisan mcp b  # Alias

# Anrufe importieren
php artisan mcp calls
php artisan mcp i  # Alias

# Kunde suchen
php artisan mcp customer
php artisan mcp f  # Alias

# Memory Bank
php artisan mcp remember  # Speichern
php artisan mcp recall    # Suchen

# Synchronisierung
php artisan mcp sync
```

### Erweiterte Shortcuts

```bash
# Tagesbericht generieren
php artisan mcp daily-report

# Alle Integrationen prüfen
php artisan mcp check-integrations
php artisan mcp h  # Alias

# GitHub-Notion synchronisieren
php artisan mcp gh-notion

# Task Discovery (AI findet besten Server)
php artisan mcp discover
```

### Konfigurierte Shortcuts

Alle Shortcuts sind in `config/mcp-shortcuts.php` definiert:

```php
// Beispiel: Neuen Shortcut hinzufügen
'my-shortcut' => [
    'server' => 'appointment',
    'tool' => 'check_availability',
    'description' => 'Verfügbarkeit prüfen',
    'prompts' => [
        'date' => 'Datum (YYYY-MM-DD)',
        'branch_id' => 'Filial-ID',
    ],
],
```

## 🤖 Developer Assistant

### Code Generation

```bash
# Interaktive Code-Generierung
php artisan dev generate

# Beispiel-Prompts:
# "Create a service for handling email notifications"
# "Generate a repository for managing invoices"
```

### Boilerplate Generation

```bash
# Service mit Interface
php artisan dev bp --type=service --name=EmailNotification

# MCP Server
php artisan dev bp --type=mcp-server --name=CustomIntegration

# Filament Resource
php artisan dev bp --type=filament-resource --model=Invoice

# API Endpoint
php artisan dev bp --type=api-endpoint --resource=invoices
```

### Code-Analyse

```bash
# Datei analysieren
php artisan dev analyze --file=app/Services/BookingService.php

# Ähnlichen Code finden
php artisan dev similar --file=app/Services/ExampleService.php

# Code erklären
php artisan dev explain --file=app/Models/Appointment.php
```

### Entwicklungs-Vorschläge

```bash
# Allgemeine Vorschläge
php artisan dev suggest

# Mit Kontext
php artisan dev suggest
# > Any specific context? performance optimization
```

## 📝 MCP Server erstellen

### 1. Boilerplate generieren

```bash
php artisan dev bp --type=mcp-server --name=MyIntegration
```

### 2. Server implementieren

```php
<?php

namespace App\Services\MCP;

class MyIntegrationMCPServer extends BaseMCPServer
{
    protected string $name = 'my_integration';
    protected string $version = '1.0.0';
    
    public function getTools(): array
    {
        return [
            [
                'name' => 'fetch_data',
                'description' => 'Fetch data from integration',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search query'
                        ],
                    ],
                    'required' => ['query']
                ],
            ],
        ];
    }
    
    public function executeTool(string $tool, array $params = []): array
    {
        return match($tool) {
            'fetch_data' => $this->fetchData($params),
            default => ['error' => 'Unknown tool: ' . $tool]
        };
    }
    
    protected function fetchData(array $params): array
    {
        // Implementierung
        return [
            'success' => true,
            'data' => []
        ];
    }
}
```

### 3. Server registrieren

In `app/Providers/MCPServiceProvider.php`:
```php
$this->app->singleton(MyIntegrationMCPServer::class);
```

In `config/mcp-servers.php`:
```php
'my_integration' => [
    'enabled' => true,
    'class' => \App\Services\MCP\MyIntegrationMCPServer::class,
    'description' => 'My custom integration',
],
```

### 4. Server testen

```bash
# Health Check
php artisan mcp:health

# Tool ausführen
php artisan mcp exec --server=my_integration --tool=fetch_data --params='{"query":"test"}'
```

## 🎯 Best Practices

### 1. Tool-Design

✅ **DO:**
- Klare, beschreibende Tool-Namen
- Vollständige Parameter-Beschreibungen
- Konsistente Response-Struktur
- Error Handling implementieren

❌ **DON'T:**
- Zu viele Parameter pro Tool
- Side-Effects in Query-Tools
- Synchrone lange Operationen

### 2. Error Handling

```php
public function executeTool(string $tool, array $params = []): array
{
    try {
        // Validation
        if (!isset($params['required_field'])) {
            return ['error' => 'Missing required field: required_field'];
        }
        
        // Operation
        $result = $this->performOperation($params);
        
        return [
            'success' => true,
            'data' => $result
        ];
        
    } catch (\Exception $e) {
        Log::error('MCP tool failed', [
            'server' => $this->name,
            'tool' => $tool,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
```

### 3. Memory Bank Integration

```php
// Context speichern
$this->memory->remember(
    'operation_context',
    [
        'tool' => $tool,
        'params' => $params,
        'result' => $result,
        'timestamp' => now()
    ],
    'mcp_operations',
    ['mcp', $this->name, $tool]
);

// Context abrufen
$history = $this->memory->search($tool, 'mcp_operations');
```

### 4. Performance

- Cache häufige Queries
- Nutze Queue für lange Operationen
- Implementiere Pagination für große Datensätze
- Verwende Eager Loading bei Eloquent

## 🔍 Troubleshooting

### Häufige Probleme

#### Server nicht gefunden
```bash
# Konfiguration prüfen
php artisan config:clear
php artisan cache:clear

# Server-Status prüfen
php artisan mcp:health
```

#### Tool-Ausführung fehlgeschlagen
```bash
# Debug-Modus aktivieren
MCP_DEBUG=true php artisan mcp exec --server=X --tool=Y

# Logs prüfen
tail -f storage/logs/laravel.log | grep MCP
```

#### Memory Bank Probleme
```bash
# Memory Bank Status
php artisan memory:status

# Cache leeren
php artisan memory:clear --type=mcp_operations
```

### Debug-Workflow

1. **MCP Dashboard** öffnen
2. **Server Status** prüfen
3. **Recent Activities** analysieren
4. **Test Tool** ausführen
5. **Logs** überprüfen

## 📊 Monitoring

### Metriken im Dashboard
- Request Count
- Error Rate  
- Average Response Time
- Tool Usage Statistics

### Alerts konfigurieren
```php
// In AppServiceProvider
if (Cache::get('mcp_recent_errors_count', 0) > 10) {
    // Send alert
}
```

## 🚀 Quick Reference Card

```bash
# === SHORTCUTS ===
mcp b              # Book appointment
mcp i              # Import calls  
mcp f              # Find customer
mcp s              # Sync Cal.com
mcp h              # Health check

# === DEVELOPER ===
dev bp             # Generate boilerplate
dev analyze        # Analyze code
dev suggest        # Get suggestions

# === DISCOVERY ===
mcp discover       # Find best server
mcp list --all     # Show all shortcuts

# === HEALTH ===
mcp:health         # Check all servers
mcp:health --json  # JSON output
```

## 🤝 Team Guidelines

1. **Neue Features**: Immer als MCP Server implementieren
2. **Dokumentation**: Tools im Code dokumentieren
3. **Testing**: Unit Tests für jeden Tool schreiben
4. **Review**: MCP Dashboard vor Deployment prüfen
5. **Monitoring**: Fehlerrate im Auge behalten

## 📚 Weiterführende Ressourcen

- [MCP Architecture Documentation](./MCP_ARCHITECTURE.md)
- [API Integration Guide](./API_INTEGRATION_GUIDE.md)
- [Performance Optimization](./PERFORMANCE_GUIDE.md)
- [Security Best Practices](./SECURITY_GUIDE.md)

---

**Fragen?** Wende dich an das Dev-Team oder nutze `php artisan dev suggest` für AI-gestützte Hilfe.