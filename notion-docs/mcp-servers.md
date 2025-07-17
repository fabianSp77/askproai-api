## Verfügbare MCP Server

### 🏢 Interne Server

#### CalcomMCPServer
- **Zweck**: Cal.com Kalender-Integration
- **Tools**: 
  - `sync_event_types` - Event-Typen synchronisieren
  - `check_availability` - Verfügbarkeit prüfen
  - `create_booking` - Buchung erstellen
  - `get_bookings` - Buchungen abrufen
- **Beispiel**: `php artisan mcp:execute calcom check_availability --date=2025-01-20`

#### RetellMCPServer
- **Zweck**: Retell.ai Telefonie-Integration
- **Tools**:
  - `fetch_calls` - Anrufe importieren
  - `get_call` - Einzelnen Anruf abrufen
  - `get_agents` - AI-Agenten verwalten
  - `update_agent` - Agent konfigurieren
- **Health Check**: Automatisch alle 5 Minuten

#### DatabaseMCPServer
- **Zweck**: Sichere Datenbankabfragen
- **Tools**:
  - `execute_query` - Read-only Queries
  - `analyze_table` - Tabellenstruktur
  - `get_statistics` - DB-Statistiken
- **Sicherheit**: Nur SELECT-Queries erlaubt

#### AppointmentMCPServer
- **Zweck**: Terminverwaltung
- **Tools**:
  - `create_appointment` - Neuer Termin
  - `cancel_appointment` - Termin stornieren
  - `reschedule_appointment` - Verschieben
  - `get_appointments` - Termine abrufen
- **Integration**: Cal.com & Retell.ai

#### CustomerMCPServer
- **Zweck**: Kundenverwaltung
- **Tools**:
  - `search_customers` - Kunden suchen
  - `get_customer` - Kundendetails
  - `merge_customers` - Duplikate zusammenführen
  - `get_customer_history` - Historie anzeigen

#### StripeMCPServer
- **Zweck**: Zahlungsverarbeitung
- **Tools**:
  - `create_invoice` - Rechnung erstellen
  - `process_payment` - Zahlung verarbeiten
  - `manage_subscription` - Abos verwalten
  - `get_balance` - Kontostand abrufen

#### QueueMCPServer
- **Zweck**: Job-Queue Management
- **Tools**:
  - `get_overview` - Queue-Übersicht
  - `retry_failed_jobs` - Fehlerhafte Jobs wiederholen
  - `monitor_job` - Job überwachen
  - `clear_queue` - Queue leeren

#### WebhookMCPServer
- **Zweck**: Webhook-Verarbeitung
- **Tools**:
  - `list_webhooks` - Registrierte Webhooks
  - `verify_signature` - Signatur prüfen
  - `replay_webhook` - Webhook wiederholen
  - `get_webhook_logs` - Logs anzeigen

#### CompanyMCPServer
- **Zweck**: Multi-Tenant Management
- **Tools**:
  - `get_company` - Firmendetails
  - `update_settings` - Einstellungen ändern
  - `manage_branches` - Filialen verwalten
  - `get_statistics` - Firmenstatistiken

#### BranchMCPServer
- **Zweck**: Filialverwaltung
- **Tools**:
  - `list_branches` - Alle Filialen
  - `get_branch` - Filialdetails
  - `update_hours` - Öffnungszeiten
  - `manage_staff` - Mitarbeiter zuweisen

#### KnowledgeMCPServer
- **Zweck**: Knowledge Base Management
- **Tools**:
  - `search_articles` - Artikel suchen
  - `create_article` - Neuer Artikel
  - `update_article` - Artikel bearbeiten
  - `get_categories` - Kategorien verwalten

#### SentryMCPServer
- **Zweck**: Error Tracking
- **Tools**:
  - `get_issues` - Aktuelle Fehler
  - `resolve_issue` - Fehler als behoben markieren
  - `get_metrics` - Error-Metriken
  - `create_alert` - Alert erstellen

### 🌐 Externe Server

#### SequentialThinkingMCPServer
- **Zweck**: Schrittweise Problemlösung
- **Status**: Standardmäßig aktiv
- **Verwendung**: Automatisch bei komplexen Aufgaben
- **Tools**:
  - `think_step_by_step` - Strukturierte Analyse
  - `solve_problem` - Problemlösung
  - `plan_solution` - Lösungsplanung

#### PostgresMCPServer
- **Zweck**: Datenbankzugriff (mapped auf MySQL)
- **Status**: Aktiv
- **Hinweis**: Nutzt MySQL/MariaDB trotz Namen
- **Tools**:
  - `query` - SQL-Abfragen
  - `describe` - Schema-Information
  - `analyze` - Query-Analyse

#### NotionMCPServer
- **Zweck**: Notion-Integration
- **Tools**: 
  - `search_pages` - Seiten suchen
  - `create_page` - Neue Seite
  - `update_page` - Seite bearbeiten
  - `query_database` - Datenbank abfragen
- **OAuth**: Erforderlich

#### GitHubMCPServer
- **Zweck**: GitHub-Integration
- **Tools**: 
  - `list_issues` - Issues anzeigen
  - `create_pr` - Pull Request erstellen
  - `manage_releases` - Releases verwalten
  - `get_workflows` - Actions Status
- **Token**: Erforderlich

#### MemoryBankMCPServer
- **Zweck**: Persistente Speicherung
- **Tools**: 
  - `save_memory` - Daten speichern
  - `recall_memory` - Daten abrufen
  - `search_memories` - Suchen
  - `manage_sessions` - Sessions verwalten
- **Speicher**: Redis-basiert

#### FigmaMCPServer
- **Zweck**: Design-Integration
- **Status**: Optional
- **Tools**:
  - `get_files` - Dateien abrufen
  - `export_assets` - Assets exportieren
  - `get_comments` - Kommentare lesen

#### ApidogMCPServer
- **Zweck**: API-Dokumentation
- **Status**: Optional
- **Tools**:
  - `generate_docs` - Docs generieren
  - `sync_spec` - API Spec sync
  - `generate_client` - Client Code

#### Context7MCPServer
- **Zweck**: Library-Dokumentation
- **Status**: Aktiv
- **Tools**:
  - `resolve_library_id` - Library ID finden
  - `get_library_docs` - Docs abrufen
  - `search_docs` - Dokumentation durchsuchen

### Server-Status Dashboard

**Zugriff**: https://api.askproai.de/admin/mcp-servers

**Features**:
- Echtzeit-Status aller Server
- Performance-Metriken
- Error-Logs
- Quick Actions (Test, Restart)

### Server-Konfiguration

#### Aktivierung/Deaktivierung
```env
# In .env
MCP_SEQUENTIAL_THINKING_ENABLED=true
MCP_NOTION_ENABLED=true
MCP_GITHUB_ENABLED=false
```

#### Rate Limits
```php
// In config/mcp.php
'services' => [
    'calcom' => [
        'rate_limit' => [
            'per_minute' => 60,
            'per_hour' => 1000,
        ],
    ],
]
```

#### Health Check Einstellungen
```php
// Automatische Health Checks
'health_check_interval' => 60, // Sekunden
'restart_on_failure' => true,
'max_restart_attempts' => 3,
```