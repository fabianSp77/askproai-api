# Retell.ai Integration - Vollständige System-Dokumentation
**Erstellt**: 2025-06-29  
**Status**: ✅ VOLLSTÄNDIG FUNKTIONSFÄHIG UND DOKUMENTIERT

---

## 🚀 Quick Recovery Commands
Falls das System nicht funktioniert, führe diese Befehle aus:
```bash
# 1. Retell Health Check & Auto-Fix
php retell-health-check.php

# 2. Agent-Konfiguration synchronisieren
php sync-retell-agent.php

# 3. Anrufe importieren
php fetch-retell-calls.php

# 4. Webhook testen
curl -X POST https://api.askproai.de/api/retell/webhook \
  -H "Content-Type: application/json" \
  -d '{"event_type":"test","call_id":"test-123"}'
```

---

## 📋 Inhaltsverzeichnis
1. [System-Überblick](#1-system-überblick)
2. [Kritische Konfiguration](#2-kritische-konfiguration)
3. [Phone Number Resolution](#3-phone-number-resolution)
4. [Webhook-Verarbeitung](#4-webhook-verarbeitung)
5. [API-Endpunkte](#5-api-endpunkte)
6. [Datenbank-Schema](#6-datenbank-schema)
7. [Troubleshooting](#7-troubleshooting)
8. [Backup & Recovery](#8-backup--recovery)

---

## 1. System-Überblick

### Komponenten-Architektur
```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Kunde     │────▶│  Retell.ai   │────▶│  AskProAI   │
│  (Anrufer)  │     │  (AI Agent)  │     │  (Backend)  │
└─────────────┘     └──────────────┘     └─────────────┘
                            │                     │
                            ▼                     ▼
                    ┌──────────────┐     ┌─────────────┐
                    │   Webhook    │     │  Database   │
                    │  Processing  │     │   (MySQL)   │
                    └──────────────┘     └─────────────┘
```

### Datenfluss
1. **Eingehender Anruf** → +493083793369
2. **Retell.ai** empfängt und verarbeitet mit AI Agent
3. **Webhook** sendet Events an AskProAI
4. **PhoneNumberResolver** identifiziert Company/Branch
5. **Datenbank** speichert Call-Daten
6. **Admin-Panel** zeigt Anrufe in Echtzeit

---

## 2. Kritische Konfiguration

### 2.1 Retell Agent Details
```yaml
Agent ID: agent_9a8202a740cd3120d96fcfda1e
Name: "Online: Assistent für Fabian Spitzer Rechtliches/V33"
LLM ID: llm_f3209286ed1caf6a75906d2645b9
Voice ID: custom_voice_191b11197fd8c3e92dab972a5a
Language: de-DE
```

### 2.2 Custom Functions (9 Stück)
```javascript
1. end_call() - Beendet den Anruf
2. transfer_call() - Weiterleitung an +491604366218
3. current_time_berlin() - Aktuelle Zeit in Berlin
4. collect_appointment_data(details) - Sammelt Termindaten
5. check_customer(phone_number) - Prüft Bestandskunde
6. check_availability(date, time, duration) - Verfügbarkeit prüfen
7. book_appointment(customer_data, appointment_data) - Termin buchen
8. cancel_appointment(phone_number, appointment_info) - Stornierung
9. reschedule_appointment(phone_number, old_appointment, new_data) - Umbuchung
```

### 2.3 Environment Variables (.env)
```bash
# Retell.ai Configuration
RETELL_TOKEN=key_e973c8962e09d6a34b3b1cf386
RETELL_WEBHOOK_SECRET=Hqj8iGCaWxGXdoKCqQQFaHsUjFKHFjUO
RETELL_BASE=https://api.retellai.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=lkZ57Dju9EDjrMxn

# Application
APP_URL=https://api.askproai.de
```

### 2.4 Webhook Configuration
```yaml
URL: https://api.askproai.de/api/retell/webhook
Aktivierte Events:
  - call_started
  - call_ended
  - call_analyzed
Signature Verification: ✅ Aktiviert
```

---

## 3. Phone Number Resolution

### 3.1 Phone Numbers Tabelle
```sql
-- Aktueller Eintrag
SELECT * FROM phone_numbers;

┌─────────────────────┬────────────┬──────────────────────────────────────┬──────────────────────────────┐
│ number              │ company_id │ branch_id                            │ retell_agent_id              │
├─────────────────────┼────────────┼──────────────────────────────────────┼──────────────────────────────┤
│ +493083793369       │ 1          │ 35a66176-5376-11f0-b773-0ad77e7a9793│ agent_9a8202a740cd3120d96... │
└─────────────────────┴────────────┴──────────────────────────────────────┴──────────────────────────────┘
```

### 3.2 Resolution Flow
```php
// app/Services/PhoneNumberResolver.php
1. Webhook empfängt: to_number = "+493083793369"
2. Normalisierung: formatPhoneNumber($number)
3. Lookup: PhoneNumber::where('number', $normalized)->first()
4. Return: ['company_id' => 1, 'branch_id' => 'xxx', 'confidence' => 0.9]
```

### 3.3 Fallback-Strategien
```
Priorität 1: Metadata (askproai_branch_id)
Priorität 2: phone_numbers Tabelle
Priorität 3: branches.phone_number
Priorität 4: Agent ID Mapping
Priorität 5: Caller History
Priorität 6: Default Company
```

---

## 4. Webhook-Verarbeitung

### 4.1 Event-Typen
```yaml
Primäre Events (aktivieren in Retell Dashboard):
  - call_started: Anruf beginnt
  - call_ended: Anruf endet (mit disconnection_reason)
  - call_analyzed: Post-Call Analysis fertig

Echtzeit-Events:
  - call_inbound: Eingehender Anruf

System-Events (automatisch):
  - phone_number_updated
  - agent_updated
  - agent_deleted
```

### 4.2 Webhook Flow
```
POST /api/retell/webhook
    ↓
VerifyRetellSignature Middleware
    ↓
RetellWebhookMCPController
    ↓
MCPGateway → RetellMCPServer
    ↓
RetellWebhookHandler/Strategy
    ↓
ProcessRetellCallEndedJob (Queue)
    ↓
Database Update
```

### 4.3 Signature Verification
```php
// app/Http/Middleware/VerifyRetellSignature.php
- Header: x-retell-signature
- Secret: env('RETELL_WEBHOOK_SECRET')
- Algorithmus: HMAC-SHA256
```

---

## 5. API-Endpunkte

### 5.1 Kritischer Fix - V2 Endpoint
```php
// app/Services/RetellV2Service.php - Zeile 252
// WICHTIG: Muss /v2/list-calls sein, nicht /list-calls!
->post($this->url . '/v2/list-calls', [
    'limit' => $limit,
    'sort_order' => 'descending'
]);
```

### 5.2 Wichtige Endpoints
```
GET  /api/retell/test-connection    - API-Verbindung testen
POST /api/retell/webhook            - Webhook-Empfang
GET  /api/retell/sync-agent         - Agent synchronisieren
GET  /api/retell/import-calls       - Anrufe importieren
```

---

## 6. Datenbank-Schema

### 6.1 Wichtige Tabellen
```sql
-- phone_numbers
CREATE TABLE phone_numbers (
    id UUID PRIMARY KEY,
    number VARCHAR(255) UNIQUE NOT NULL,
    company_id INT NOT NULL,
    branch_id UUID,
    retell_agent_id VARCHAR(255),
    retell_phone_number_id VARCHAR(255),
    retell_version VARCHAR(10) DEFAULT 'v2',
    is_active BOOLEAN DEFAULT true,
    type ENUM('direct', 'hotline') DEFAULT 'direct',
    name VARCHAR(255),
    description TEXT,
    capabilities_sms BOOLEAN DEFAULT false,
    capabilities_whatsapp BOOLEAN DEFAULT false,
    routing_config JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- calls
CREATE TABLE calls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    call_id VARCHAR(255) UNIQUE,
    company_id INT NOT NULL,
    branch_id UUID,
    customer_id INT,
    from_number VARCHAR(255),
    to_number VARCHAR(255),
    direction ENUM('inbound', 'outbound'),
    call_status VARCHAR(50),
    start_timestamp TIMESTAMP,
    end_timestamp TIMESTAMP,
    duration_seconds INT,
    recording_url TEXT,
    transcript JSON,
    metadata JSON,
    agent_id VARCHAR(255),
    call_type VARCHAR(50),
    end_reason VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_company_start (company_id, start_timestamp),
    INDEX idx_call_id (call_id),
    INDEX idx_from_number (from_number)
);

-- retell_agents
CREATE TABLE retell_agents (
    id UUID PRIMARY KEY,
    company_id INT NOT NULL,
    retell_agent_id VARCHAR(255) UNIQUE,
    name VARCHAR(255),
    configuration JSON,
    prompt TEXT,
    voice_id VARCHAR(255),
    language VARCHAR(10) DEFAULT 'de-DE',
    llm_id VARCHAR(255),
    version VARCHAR(10) DEFAULT 'v2',
    is_active BOOLEAN DEFAULT true,
    last_synced_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 7. Troubleshooting

### 7.1 Häufige Probleme & Lösungen

#### Problem: "Keine neuen Anrufe werden importiert"
```bash
# Lösung 1: Horizon prüfen
php artisan horizon:status
php artisan horizon  # Falls nicht läuft

# Lösung 2: Manueller Import
php fetch-retell-calls.php

# Lösung 3: Webhook testen
php test-retell-webhook.php
```

#### Problem: "500 Error im Admin Panel"
```bash
# Cache leeren
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# Logs prüfen
tail -f storage/logs/laravel.log
```

#### Problem: "Phone Number nicht gefunden"
```sql
-- Prüfe Telefonnummer Format
SELECT * FROM phone_numbers WHERE number LIKE '%3083793369%';

-- Füge fehlende Nummer hinzu
INSERT INTO phone_numbers (id, number, company_id, branch_id, retell_agent_id, is_active)
VALUES (UUID(), '+493083793369', 1, 'BRANCH-UUID', 'agent_xxx', 1);
```

### 7.2 Debug-Befehle
```bash
# Retell-Konfiguration prüfen
php check-retell-configuration.php

# API-Verbindung testen
php test-retell-api-connection.php

# Agent-Details anzeigen
php check-retell-agent-details.php

# Webhook-Logs anzeigen
grep "retell" storage/logs/laravel.log | tail -50
```

---

## 8. Backup & Recovery

### 8.1 Wichtige Backup-Dateien
```bash
# Konfigurationsdateien sichern
cp .env .env.backup.$(date +%Y%m%d)
cp retell-agent-current-*.json backup/
cp retell-llm-config-*.json backup/

# Datenbank-Backup
mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  calls phone_numbers retell_agents webhook_events \
  > backup/retell-tables-$(date +%Y%m%d).sql
```

### 8.2 Recovery-Prozess
```bash
# 1. Environment wiederherstellen
cp .env.backup .env

# 2. Datenbank wiederherstellen
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db < backup/retell-tables.sql

# 3. Agent synchronisieren
php sync-retell-agent.php

# 4. Anrufe neu importieren
php fetch-retell-calls.php

# 5. System testen
php retell-health-check.php
```

### 8.3 Monitoring-Skripte
```bash
# Erstelle Cron-Job für regelmäßige Checks
crontab -e

# Füge hinzu:
*/5 * * * * /usr/bin/php /var/www/api-gateway/retell-health-check.php >> /var/log/retell-health.log 2>&1
0 */6 * * * /usr/bin/php /var/www/api-gateway/sync-retell-agent.php >> /var/log/retell-sync.log 2>&1
```

---

## 📌 Wichtige Notizen

1. **API Version**: Immer V2 endpoints verwenden (`/v2/list-calls` statt `/list-calls`)
2. **Tenant Scope**: Wird bei Webhook-Verarbeitung temporär deaktiviert
3. **Phone Format**: Immer E.164 Format (+49...) verwenden
4. **Agent Sync**: Mindestens täglich synchronisieren
5. **Horizon**: Muss für Queue-Verarbeitung laufen

---

## 🔗 Verwandte Dokumentationen

- [RETELL_INTEGRATION_COMPLETE_2025-06-29.md](./RETELL_INTEGRATION_COMPLETE_2025-06-29.md)
- [RETELL_WEBHOOK_CONFIGURATION.md](./RETELL_WEBHOOK_CONFIGURATION.md)
- [CLAUDE.md](./CLAUDE.md) - Hauptdokumentation

---

**Status**: ✅ System vollständig dokumentiert und einsatzbereit
**Letzte Aktualisierung**: 2025-06-29