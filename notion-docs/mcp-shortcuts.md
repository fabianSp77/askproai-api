## MCP Shortcuts & Befehle

### ⚡ Quick Commands

#### Basis-Shortcuts
```bash
# Termin buchen
php artisan mcp book
php artisan mcp b  # Alias

# Kunde finden
php artisan mcp find-customer "+49 123 456789"
php artisan mcp f  # Alias

# Anrufe importieren
php artisan mcp import-calls
php artisan mcp i  # Alias

# Tagesreport
php artisan mcp daily-report

# Health Check
php artisan mcp check-integrations
php artisan mcp h  # Alias
```

#### Erweiterte Shortcuts
```bash
# Memory Bank
php artisan mcp remember-task "Deploy new feature"
php artisan mcp search-memory "deploy"

# Synchronisierung
php artisan mcp sync-calcom
php artisan mcp sync-github

# Batch-Operationen
php artisan mcp:batch book,cancel,sync
```

### 🎯 Developer Assistant Commands

```bash
# MCP Discovery - Besten Server für Aufgabe finden
php artisan mcp:discover "create appointment for tomorrow"
php artisan mcp:discover "analyze call data" --execute

# Impact Analysis
php artisan analyze:impact --component=AppointmentService
php artisan analyze:impact --git

# Documentation Health
php artisan docs:check-updates
php artisan docs:health

# Code Quality
composer quality       # Alle Checks
composer pint         # Code formatieren
composer stan         # Static Analysis
```

### 📋 Shortcut-Konfiguration

Datei: `config/mcp-shortcuts.php`

```php
'shortcuts' => [
    'book' => [
        'server' => 'appointment',
        'tool' => 'create_appointment',
        'description' => 'Schnell einen Termin buchen',
        'prompts' => [
            'customer_phone' => 'Telefonnummer des Kunden',
            'service' => 'Service/Dienstleistung',
            'date' => 'Datum (YYYY-MM-DD)',
            'time' => 'Uhrzeit (HH:MM)',
        ],
    ],
]
```

### 🔧 Eigene Shortcuts erstellen

1. Shortcut in `config/mcp-shortcuts.php` definieren
2. Optional: Alias hinzufügen
3. Gruppe zuweisen für Dashboard-Widget

Beispiel:
```php
'my-report' => [
    'server' => 'database',
    'tool' => 'execute_query',
    'query' => 'SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURDATE()',
    'description' => 'Mein eigener Report',
],

'aliases' => [
    'mr' => 'my-report',
],
```

### 🚀 Dashboard Widget

Im Admin-Panel verfügbar:
- Gruppierte Shortcuts
- One-Click Ausführung
- Letzte Ausführungen
- Quick Stats

### 📊 Vordefinierte Reports

#### Daily Report
```bash
php artisan mcp daily-report
```
Zeigt:
- Anzahl Anrufe heute
- Gebuchte Termine
- Neue Kunden
- Umsatz (wenn Stripe aktiv)

#### Weekly Summary
```bash
php artisan mcp weekly-summary
```
Zeigt:
- Wochenvergleich
- Top Services
- Auslastung
- Trends

### 🔍 Interaktive Modi

#### Appointment Wizard
```bash
php artisan mcp:wizard appointment
```
Führt durch:
1. Kundensuche/-erstellung
2. Service-Auswahl
3. Verfügbare Zeiten
4. Bestätigung

#### Customer Explorer
```bash
php artisan mcp:explore customer
```
Features:
- Suche nach verschiedenen Kriterien
- Historie anzeigen
- Notizen hinzufügen
- Termine verwalten

### ⚙️ Erweiterte Optionen

#### Verbose Mode
```bash
php artisan mcp book -v
# Zeigt detaillierte Ausgabe

php artisan mcp book -vvv
# Maximum Debug-Info
```

#### Dry Run
```bash
php artisan mcp book --dry-run
# Simuliert ohne Ausführung
```

#### Format Output
```bash
php artisan mcp daily-report --format=json
php artisan mcp daily-report --format=table
php artisan mcp daily-report --format=csv
```

### 🎨 Shortcut Gruppen

#### appointments
- book - Termin buchen
- cancel - Stornieren
- reschedule - Verschieben
- check-availability - Verfügbarkeit

#### customers
- find-customer - Suchen
- customer-history - Historie
- merge-duplicates - Duplikate
- export-customers - Export

#### operations
- import-calls - Anrufe importieren
- sync-calcom - Kalender sync
- check-integrations - Health Check
- clear-cache - Cache leeren

#### memory
- remember-task - Aufgabe speichern
- search-memory - Durchsuchen
- list-tasks - Aufgaben anzeigen
- clear-completed - Erledigte löschen

### 🛠️ Troubleshooting Shortcuts

```bash
# Debug Mode aktivieren
export MCP_DEBUG=true
php artisan mcp book

# Specific Server testen
php artisan mcp:test calcom
php artisan mcp:test retell

# Logs anzeigen
php artisan mcp:logs
php artisan mcp:logs --tail=50
php artisan mcp:logs --filter=error
```

### 📱 API Shortcuts

Für mobile Apps und externe Integrationen:

```bash
# API Token generieren
php artisan mcp:token generate

# Shortcut via API
curl -X POST https://api.askproai.de/api/v2/mcp/shortcut \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"shortcut": "book", "args": {...}}'
```