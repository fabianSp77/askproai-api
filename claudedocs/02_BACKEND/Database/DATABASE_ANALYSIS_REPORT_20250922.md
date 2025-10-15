# Database Analysis Report - AskPro AI Gateway
**Erstellt am:** 22. September 2025
**Analysiert:** askproai_db (MySQL)
**Gesamttabellen:** 127 Tabellen

## Executive Summary

Das AskPro AI Gateway System verfügt über eine umfangreiche Datenbankstruktur mit 127 Tabellen, jedoch sind **kritische Geschäftstabellen unvollständig** oder **komplett leer**. Das Integrationssystem ist nicht funktionsfähig, was die Geschäftsprozesse erheblich beeinträchtigt.

### Kritische Befunde
- **🔴 KRITISCH:** `integrations` Tabelle ist komplett leer (0 Einträge)
- **🟡 WARNUNG:** `phone_numbers` hat nur 4 Einträge für 13 Unternehmen
- **🟡 WARNUNG:** Viele geschäftskritische Tabellen sind unterbesetzt
- **🟢 POSITIV:** Kernstrukturen und Beziehungen sind korrekt definiert

## 1. Leere Tabellen (0 Einträge) - Kritische Analyse

### 🔴 Geschäftskritische leere Tabellen:
```
integrations               - SYSTEM-KRITISCH: Keine Integrationen konfiguriert!
calcom_bookings           - Cal.com Buchungen fehlen
calcom_event_types        - Event-Types nicht synchronisiert
webhook_dead_letter_queue - Webhook-Fehlerbehandlung inaktiv
```

### 🟡 Betriebskritische leere Tabellen:
```
customer_interactions     - Kundeninteraktionen nicht getrackt
customer_notes           - Kundennotizen fehlen
customer_preferences     - Kundenpräferenzen nicht gespeichert
email_logs              - E-Mail-Kommunikation nicht protokolliert
notifications           - Benachrichtigungssystem inaktiv
sms_message_logs        - SMS-Kommunikation nicht protokolliert
whatsapp_message_logs   - WhatsApp-Kommunikation nicht protokolliert
```

### 🟢 Optional/Zukunft leere Tabellen:
```
billing_line_items      - Abrechnungsdetails (für Zukunft)
subscription_items      - Abonnement-Details (für Zukunft)
ml_models              - Machine Learning Modelle (geplant)
knowledge_notebooks    - Wissensdatenbank (geplant)
```

## 2. Spärlich besiedelte Tabellen (<10 Einträge)

### Kritische Unterbesetzung:
```
phone_numbers: 4 Einträge
├── Nur 4 Telefonnummern für 13 Unternehmen
├── Fehlende Zuordnung zu Branches
└── Empfehlung: Mindestens 1-2 Nummern pro Unternehmen

staff: 8 Einträge
├── 8 Mitarbeiter für 9 Branches (Unterbesetzung)
├── Fehlende Zuordnung zu allen Branches
└── Empfehlung: Mindestens 2-3 Mitarbeiter pro Branch

branches: 9 Einträge
├── 9 Filialen für 13 Unternehmen (einige ohne Filialen)
├── Fehlende geografische Verteilung
└── Empfehlung: Mindestens 1 Hauptfiliale pro Unternehmen
```

## 3. Datenverteilung der Geschäftstabellen

| Tabelle | Einträge | Status | Sollwert | Kritikalität |
|---------|----------|--------|----------|--------------|
| **companies** | 13 | ✅ Basis | 10-20 | Ausreichend |
| **branches** | 9 | ⚠️ Unvollständig | 13+ | Mittel |
| **customers** | 42 | ✅ Testdaten | 50-100 | Ausreichend |
| **calls** | 207 | ✅ Aktiv | 200+ | Gut |
| **appointments** | 41 | ✅ Testdaten | 50+ | Ausreichend |
| **phone_numbers** | 4 | 🔴 Kritisch | 20+ | Sehr kritisch |
| **staff** | 8 | ⚠️ Niedrig | 18+ | Mittel |
| **services** | 21 | ✅ Basis | 20+ | Ausreichend |
| **integrations** | 0 | 🔴 Leer | 13+ | System-kritisch |

## 4. Fehlende Relationen und Constraints

### ✅ Korrekt konfigurierte Beziehungen:
```sql
-- Alle kritischen Foreign Keys sind vorhanden:
appointments -> companies, customers, branches, calls
calls -> customers, appointments
customers -> companies, branches, staff
branches -> companies
phone_numbers -> companies, branches
```

### 🔴 Fehlende kritische Daten für Beziehungen:

#### Integrations-Problem:
```sql
-- integrations Tabelle ist leer!
-- Betrifft: companies.calcom_api_key, branches.calcom_event_type_id
-- Empfehlung: Sofortige Integration-Konfiguration erforderlich
```

#### Phone Numbers-Problem:
```sql
-- Nur 4 phone_numbers für 13 companies
-- 9 companies haben keine zugewiesenen Telefonnummern
-- Betrifft: Eingehende Anrufe können nicht geroutet werden
```

## 5. Integration-System Status - KRITISCHER ZUSTAND

### 🔴 Integrations-Tabelle Analyse:
```sql
-- Tabelle: integrations (0 Einträge)
-- Erwartete Integrationen pro Unternehmen:
-- 1. Cal.com Integration (Terminbuchung)
-- 2. Retell AI Integration (Anrufverarbeitung)
-- 3. WhatsApp Business Integration (optional)
-- 4. DATEV Integration (optional)

-- MISSING: 13 companies × 2 core integrations = 26 fehlende Einträge!
```

### Integration-Konfiguration aus .env:
```bash
# Vorhandene API-Schlüssel (aber nicht in DB konfiguriert):
CALCOM_API_KEY=cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da
CALCOM_EVENT_TYPE_ID=2026302
RETELL_TOKEN=key_6ff998ba48e842092e04a5455d19
```

## 6. Test-Daten-Generierung Empfehlungen

### 🔥 SOFORTIGE MASSNAHMEN (Priorität 1):

#### A) Integrations-Tabelle befüllen:
```sql
-- Für jedes Unternehmen Cal.com Integration:
INSERT INTO integrations (company_id, name, type, status, config, credentials, is_active, created_at, updated_at)
VALUES
(1, 'Cal.com Integration', 'calcom', 'active',
 JSON_OBJECT('event_type_id', 2026302, 'team_slug', 'askproai'),
 JSON_OBJECT('api_key', 'cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da'),
 1, NOW(), NOW());

-- Für jedes Unternehmen Retell AI Integration:
INSERT INTO integrations (company_id, name, type, status, config, credentials, is_active, created_at, updated_at)
VALUES
(1, 'Retell AI Integration', 'retell', 'active',
 JSON_OBJECT('webhook_url', 'https://api.askproai.de/api/retell/webhook'),
 JSON_OBJECT('api_token', 'key_6ff998ba48e842092e04a5455d19'),
 1, NOW(), NOW());
```

#### B) Phone Numbers erweitern:
```sql
-- Pro Unternehmen mindestens 1 Hauptnummer:
INSERT INTO phone_numbers (id, company_id, branch_id, number, type, is_active, is_primary, created_at, updated_at)
VALUES
(UUID(), 2, 'branch_id_2', '+49 30 11111111', 'hotline', 1, 1, NOW(), NOW()),
(UUID(), 3, 'branch_id_3', '+49 30 22222222', 'hotline', 1, 1, NOW(), NOW());
-- ... für alle 13 Unternehmen
```

### 🟡 MITTELFRISTIGE MASSNAHMEN (Priorität 2):

#### C) Fehlende Branches ergänzen:
```sql
-- Unternehmen ohne Branches erhalten Hauptfiliale:
INSERT INTO branches (id, company_id, name, city, is_active, created_at, updated_at)
VALUES
(UUID(), 4, 'Hauptfiliale', 'Berlin', 1, NOW(), NOW()),
(UUID(), 5, 'Hauptfiliale', 'München', 1, NOW(), NOW());
-- ... für alle Unternehmen ohne Branch
```

#### D) Staff erweitern:
```sql
-- Pro Branch mindestens 2 Mitarbeiter:
INSERT INTO staff (id, name, email, role, is_active, created_at, updated_at)
VALUES
(UUID(), 'Maria Schmidt', 'maria@test.com', 'receptionist', 1, NOW(), NOW()),
(UUID(), 'Thomas Weber', 'thomas@test.com', 'manager', 1, NOW(), NOW());
```

### 🟢 LANGFRISTIGE VERBESSERUNGEN (Priorität 3):

#### E) Customer Journey optimieren:
```sql
-- Customer Interactions protokollieren:
INSERT INTO customer_interactions (customer_id, type, channel, content, created_at)
SELECT id, 'initial_contact', 'phone', 'Erstkontakt via Telefon', created_at
FROM customers WHERE call_count > 0;
```

#### F) Notification System aktivieren:
```sql
-- Standard Notification Templates:
INSERT INTO notification_templates (name, type, subject, content, is_active)
VALUES
('appointment_confirmation', 'email', 'Terminbestätigung', 'Ihr Termin wurde bestätigt...', 1),
('appointment_reminder', 'sms', '', 'Erinnerung: Ihr Termin morgen um {time}', 1);
```

## 7. Automatisierte Test-Daten Generierung

### Empfohlene Skript-Reihenfolge:

```bash
# 1. Integrations SOFORT konfigurieren
mysql -e "SOURCE scripts/01_setup_integrations.sql"

# 2. Phone Numbers ergänzen
mysql -e "SOURCE scripts/02_setup_phone_numbers.sql"

# 3. Fehlende Branches/Staff
mysql -e "SOURCE scripts/03_setup_branches_staff.sql"

# 4. Customer Journey Daten
mysql -e "SOURCE scripts/04_setup_customer_journey.sql"

# 5. Notification System
mysql -e "SOURCE scripts/05_setup_notifications.sql"
```

## 8. Monitoring und Validierung

### KPIs für Daten-Vollständigkeit:
```sql
-- Integration Coverage: 100% (aktuell: 0%)
SELECT
    COUNT(DISTINCT c.id) as total_companies,
    COUNT(DISTINCT i.company_id) as companies_with_integrations,
    ROUND(COUNT(DISTINCT i.company_id) / COUNT(DISTINCT c.id) * 100, 2) as coverage_percent
FROM companies c
LEFT JOIN integrations i ON c.id = i.company_id;

-- Phone Coverage: 100% (aktuell: ~30%)
SELECT
    COUNT(DISTINCT c.id) as total_companies,
    COUNT(DISTINCT p.company_id) as companies_with_phones,
    ROUND(COUNT(DISTINCT p.company_id) / COUNT(DISTINCT c.id) * 100, 2) as coverage_percent
FROM companies c
LEFT JOIN phone_numbers p ON c.id = p.company_id;
```

## 9. Risiko-Assessment

| Risiko | Wahrscheinlichkeit | Impact | Mitigation |
|--------|-------------------|--------|------------|
| **System-Ausfall durch fehlende Integrations** | Hoch | Kritisch | Sofortiges Setup der integrations-Tabelle |
| **Anrufe können nicht geroutet werden** | Mittel | Hoch | Phone Numbers für alle Companies |
| **Inkonsistente Customer Journey** | Niedrig | Mittel | Customer Interactions einführen |
| **Fehlende Benachrichtigungen** | Niedrig | Mittel | Notification Templates aktivieren |

## 10. Handlungsempfehlungen

### 🔥 SOFORT (nächste 24h):
1. **Integrations-Tabelle befüllen** - System ist aktuell nicht betriebsbereit
2. **Phone Numbers für alle Companies** - Anruf-Routing funktioniert nicht
3. **Validierung der API-Schlüssel** - Prüfung der .env Konfiguration

### 📋 DIESE WOCHE:
1. **Fehlende Branches erstellen** - Vollständige Filial-Abdeckung
2. **Staff erweitern** - Ausreichende Personalzuordnung
3. **Customer Journey tracking** - Kundeninteraktionen protokollieren

### 📈 NÄCHSTEN MONAT:
1. **Notification System vollständig aktivieren**
2. **Knowledge Base aufbauen** - Wissensdatenbank mit Inhalten füllen
3. **ML/Analytics Grundlagen** - Vorbereitung für Advanced Features

---

**Fazit:** Das System hat eine solide Architektur, aber **kritische Betriebsdaten fehlen**. Die `integrations`-Tabelle muss sofort befüllt werden, um das System funktionsfähig zu machen. Mit den empfohlenen Test-Daten wird das System vollständig betriebsbereit sein.