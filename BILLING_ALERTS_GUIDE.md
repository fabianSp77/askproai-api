# Billing Alerts & Notifications Guide

Dieses Dokument beschreibt das Billing Alert System für proaktive Benachrichtigungen bei wichtigen Abrechnungsereignissen.

## Übersicht

Das Billing Alert System bietet automatische Benachrichtigungen für:
- Usage-Limit-Überschreitungen
- Zahlungserinnerungen
- Subscription-Erneuerungen
- Zahlungsfehler
- Budget-Überschreitungen
- Overage-Warnungen

## Alert-Typen

### 1. Usage Limit Alerts
**Zweck**: Warnung bei Erreichen bestimmter Verbrauchsschwellen

**Konfiguration**:
- Schwellenwerte: 80%, 90%, 100% (konfigurierbar)
- Wird einmal pro Schwellenwert pro Periode ausgelöst
- Severity steigt mit Schwellenwert (info → warning → critical)

**Beispiel-Alert**:
```
Subject: Usage Alert: 90% of included minutes used
Message: You have used 450 of your 500 included minutes (90%).
```

### 2. Payment Reminders
**Zweck**: Erinnerung vor Fälligkeit von Rechnungen

**Konfiguration**:
- Standard: 3 Tage vor Fälligkeit
- Konfigurierbar: 1-30 Tage
- Nur für offene Rechnungen

**Beispiel-Alert**:
```
Subject: Payment Reminder: Invoice due in 3 days
Message: Invoice INV-2025-001 for €99.00 is due on June 30, 2025.
```

### 3. Subscription Renewal
**Zweck**: Information über anstehende Subscription-Verlängerung

**Konfiguration**:
- Standard: 7 Tage vor Verlängerung
- Konfigurierbar: 1-30 Tage

### 4. Payment Failed
**Zweck**: Sofortige Benachrichtigung bei Zahlungsfehlern

**Eigenschaften**:
- Wird sofort ausgelöst
- Severity: critical
- Enthält nächsten Retry-Versuch

### 5. Budget Exceeded
**Zweck**: Warnung bei Budget-Überschreitung

**Konfiguration**:
- Schwellenwerte: 75%, 90%, 100%
- Benötigt gesetztes Monatsbudget

### 6. Overage Warning
**Zweck**: Warnung bei Überschreitung inkludierter Leistungen

## Datenmodell

### BillingAlertConfig
```php
- company_id: UUID
- alert_type: enum
- is_enabled: boolean
- thresholds: array (z.B. [80, 90, 100])
- notification_channels: array (z.B. ['email'])
- advance_days: integer (für zeitbasierte Alerts)
- recipients: array (zusätzliche Empfänger)
- notify_primary_contact: boolean
- notify_billing_contact: boolean
- preferred_time: time
- quiet_hours: array
```

### BillingAlert
```php
- company_id: UUID
- config_id: foreign key
- alert_type: enum
- severity: enum (info, warning, critical)
- title: string
- message: text
- data: json (Kontext-Daten)
- threshold_value: decimal
- current_value: decimal
- status: enum (pending, sent, failed, acknowledged)
- sent_at: timestamp
- acknowledged_at: timestamp
- channels_used: array
```

### BillingAlertSuppression
```php
- company_id: UUID
- alert_type: enum (oder 'all')
- starts_at: timestamp
- ends_at: timestamp (nullable für unbegrenzt)
- reason: string
- created_by: UUID
```

## Konfiguration

### Filament Admin Interface
**BillingAlertsManagement** Page unter `/admin/billing-alerts`:
- Globaler Alert-Schalter
- Konfiguration pro Alert-Typ
- Test-Funktion für jeden Alert-Typ
- Alert-Historie mit Filtern
- Suppression-Management

### API Configuration
```php
// Standard-Konfigurationen erstellen
BillingAlertConfig::createDefaultsForCompany($company);

// Einzelne Konfiguration updaten
$config = BillingAlertConfig::where('company_id', $company->id)
    ->where('alert_type', 'usage_limit')
    ->first();

$config->update([
    'thresholds' => [70, 85, 95, 100],
    'notification_channels' => ['email', 'sms'],
]);
```

## Notification Channels

### Email (Implementiert)
- Verwendet Laravel Mail
- Template: `resources/views/emails/billing/alert.blade.php`
- Responsive Design
- Call-to-Action Buttons

### SMS (Geplant)
- Integration mit Twilio/Vonage
- Kurze, prägnante Nachrichten
- Opt-in erforderlich

### Webhook (Geplant)
- POST an konfigurierte URL
- JSON Payload mit Alert-Details
- Retry-Mechanismus

## Alert Service

### Automatische Prüfung
```php
// Stündlich via Scheduler
$schedule->command('billing:check-alerts')->hourly();

// Während Geschäftszeiten alle 30 Minuten
$schedule->command('billing:check-alerts')
    ->everyThirtyMinutes()
    ->between('08:00', '18:00')
    ->weekdays();
```

### Manuelle Prüfung
```bash
# Alle Companies prüfen
php artisan billing:check-alerts

# Spezifische Company
php artisan billing:check-alerts --company=UUID

# Dry-Run ohne Versand
php artisan billing:check-alerts --dry-run
```

### Immediate Alerts
```php
// Bei kritischen Events (z.B. Payment Failed)
$alertService->createImmediateAlert($company, 'payment_failed', [
    'severity' => 'critical',
    'title' => 'Payment Failed',
    'message' => 'Your payment could not be processed.',
    'data' => ['invoice_id' => $invoice->id],
]);
```

## Alert Management

### CLI Commands
```bash
# Alerts auflisten
php artisan billing:manage-alerts list
php artisan billing:manage-alerts list --company=UUID --type=usage_limit

# Alert bestätigen
php artisan billing:manage-alerts acknowledge --alert=123

# Alerts unterdrücken
php artisan billing:manage-alerts suppress --company=UUID --type=all --days=7 --reason="Wartung"

# Alerts aktivieren/deaktivieren
php artisan billing:manage-alerts enable --company=UUID --type=payment_reminder
php artisan billing:manage-alerts disable --company=UUID
```

### Suppression Rules
- Temporäre Unterdrückung möglich
- Pro Alert-Typ oder global
- Mit Begründung dokumentiert
- Automatisches Auslaufen

## Best Practices

### 1. Alert Fatigue vermeiden
- Sinnvolle Schwellenwerte setzen
- Nicht zu viele Alerts aktivieren
- Gruppierung ähnlicher Alerts

### 2. Timing
- Quiet Hours respektieren (22:00 - 08:00)
- Zeitzone des Kunden beachten
- Business Hours für nicht-kritische Alerts

### 3. Content
- Klare, actionable Nachrichten
- Kontext bereitstellen
- Call-to-Action einbauen

### 4. Testing
```php
// Test-Alert senden
$this->testAlert('usage_limit');

// Prüft Konfiguration und Versand
// Markiert Alert als Test
```

## Monitoring

### Alert-Metriken
- Versandte Alerts pro Typ
- Erfolgsrate der Zustellung
- Acknowledgment-Rate
- Durchschnittliche Response-Zeit

### Dashboard-Widgets
```php
// Unbestätigte kritische Alerts
BillingAlert::unacknowledged()
    ->critical()
    ->where('company_id', $company->id)
    ->count();

// Alert-Trend letzte 30 Tage
BillingAlert::where('company_id', $company->id)
    ->where('created_at', '>=', now()->subDays(30))
    ->groupBy('alert_type')
    ->selectRaw('alert_type, COUNT(*) as count')
    ->get();
```

## Troubleshooting

### Problem: Alerts werden nicht versendet

1. Prüfe globale Einstellung:
```sql
SELECT alerts_enabled FROM companies WHERE id = ?;
```

2. Prüfe Alert-Konfiguration:
```sql
SELECT * FROM billing_alert_configs 
WHERE company_id = ? AND alert_type = ?;
```

3. Prüfe Suppressions:
```sql
SELECT * FROM billing_alert_suppressions 
WHERE company_id = ? 
AND starts_at <= NOW() 
AND (ends_at IS NULL OR ends_at > NOW());
```

### Problem: Doppelte Alerts

1. Prüfe Scheduler-Überlappung:
```bash
php artisan schedule:list
```

2. Prüfe Alert-History:
```sql
SELECT * FROM billing_alerts 
WHERE company_id = ? 
AND alert_type = ? 
AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);
```

### Problem: Email-Versand schlägt fehl

1. Prüfe Mail-Konfiguration:
```bash
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

2. Prüfe Queue-Processing:
```bash
php artisan queue:work --queue=notifications
```

## Zukünftige Erweiterungen

1. **Machine Learning**
   - Vorhersage von Usage-Patterns
   - Intelligente Alert-Schwellenwerte
   - Anomalie-Erkennung

2. **Multi-Channel**
   - WhatsApp Integration
   - Push Notifications
   - In-App Notifications

3. **Advanced Rules**
   - Komplexe Bedingungen
   - Alert-Workflows
   - Eskalationsketten

4. **Analytics**
   - Alert-Effectiveness-Tracking
   - A/B Testing für Messages
   - ROI-Messung