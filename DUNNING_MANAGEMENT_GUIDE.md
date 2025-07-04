# Dunning Management Guide

Dieses Dokument beschreibt das Dunning Management System für automatisches Payment Retry bei fehlgeschlagenen Zahlungen.

## Übersicht

Das Dunning Management System automatisiert den Prozess der Zahlungswiederholung bei fehlgeschlagenen Abbuchungen. Es umfasst:
- Automatische Retry-Versuche mit konfigurierbaren Intervallen
- E-Mail-Benachrichtigungen an Kunden
- Service-Pausierung bei anhaltenden Zahlungsfehlern
- Manuelle Review-Optionen für Edge Cases

## Architektur

### Datenbank-Struktur

1. **dunning_configurations** - Konfiguration pro Company
   - Retry-Intervalle und Anzahl
   - E-Mail-Einstellungen
   - Service-Pausierungs-Regeln

2. **dunning_processes** - Aktive Dunning-Prozesse
   - Status-Tracking (active, resolved, failed, paused)
   - Retry-Zähler und Zeitpläne
   - Zahlungsbeträge

3. **dunning_activities** - Audit-Log aller Aktivitäten
   - Retry-Versuche
   - E-Mail-Versand
   - Service-Pausierungen

### Service Layer

**DunningService** (`app/Services/Billing/DunningService.php`)
- Hauptlogik für Dunning-Prozesse
- Integration mit Stripe für Payment Retries
- E-Mail-Benachrichtigungen
- Service-Pausierung

## Konfiguration

### Standard-Einstellungen

```php
// Retry-Intervalle (Tage)
$retryDelays = [
    1 => 3,  // Erster Retry nach 3 Tagen
    2 => 5,  // Zweiter Retry nach 5 Tagen
    3 => 7   // Dritter Retry nach 7 Tagen
];

// Weitere Einstellungen
$config = [
    'max_retry_attempts' => 3,
    'grace_period_days' => 3,
    'pause_service_on_failure' => true,
    'pause_after_days' => 14
];
```

### Company-spezifische Konfiguration

Jede Company kann eigene Dunning-Einstellungen haben:

```php
$config = DunningConfiguration::forCompany($company);
$config->update([
    'retry_delays' => ['1' => 2, '2' => 4, '3' => 7],
    'send_payment_failed_email' => true,
    'enable_manual_review' => true
]);
```

## Workflow

### 1. Payment Failure Detection

Wenn Stripe einen `invoice.payment_failed` Webhook sendet:

```php
// In StripeWebhookHandler
protected function handleInvoicePaymentFailed($webhookEvent)
{
    // ... Invoice update ...
    
    // Initiate dunning
    $dunningProcess = $this->dunningService->handleFailedPayment($webhookEvent);
}
```

### 2. Dunning Process Creation

Ein neuer Dunning-Prozess wird erstellt:
- Status: `active`
- Erster Retry wird geplant
- Payment Failed E-Mail wird gesendet

### 3. Automatic Retries

Der Cronjob läuft alle 4 Stunden und täglich um 10:00 Uhr:

```bash
php artisan dunning:process-retries
```

Für jeden fälligen Retry:
1. Zahlung wird über Stripe API wiederholt
2. Bei Erfolg: Process als `resolved` markiert
3. Bei Fehler: Nächster Retry geplant oder Process beendet

### 4. Service Suspension

Nach konfigurierbarer Zeit (Standard: 14 Tage):
- Service wird pausiert
- Company `billing_status` → `suspended`
- Service Paused E-Mail wird gesendet

### 5. Manual Review

Nach konfigurierten Retry-Versuchen:
- Process Status → `paused`
- Admin-Benachrichtigung für manuelle Prüfung

## Commands

### Process Retries

```bash
# Normale Ausführung
php artisan dunning:process-retries

# Dry-Run Modus
php artisan dunning:process-retries --dry-run

# Nur für bestimmte Company
php artisan dunning:process-retries --company=1
```

### Status Monitoring

```bash
# Übersicht aller Dunning-Prozesse
php artisan dunning:status

# Gefiltert nach Status
php artisan dunning:status --status=active

# Mit Activity-Log
php artisan dunning:status --show-activities

# Für bestimmte Company
php artisan dunning:status --company=1
```

## E-Mail Templates

### Payment Failed
- **Template**: `resources/views/emails/billing/payment-failed.blade.php`
- **Zeitpunkt**: Sofort nach Zahlungsfehler
- **Inhalt**: Fehlergrund, nächster Retry-Termin

### Retry Warning
- **Template**: `resources/views/emails/billing/payment-retry-warning.blade.php`
- **Zeitpunkt**: Nach jedem fehlgeschlagenen Retry
- **Inhalt**: Anzahl Versuche, Warnung vor Service-Pausierung

### Service Paused
- **Template**: `resources/views/emails/billing/service-paused.blade.php`
- **Zeitpunkt**: Bei Service-Pausierung
- **Inhalt**: Pausierungs-Info, Reaktivierungs-Anleitung

### Payment Recovered
- **Template**: `resources/views/emails/billing/payment-recovered.blade.php`
- **Zeitpunkt**: Nach erfolgreicher Zahlung
- **Inhalt**: Bestätigung, Service-Reaktivierung

## API Integration

### Manuelle Aktionen

```php
// Dunning manuell auflösen
$dunningService->manuallyResolve($process, 'Manuell bezahlt', 'admin@example.com');

// Dunning abbrechen
$dunningService->cancelDunning($process, 'Kunde hat gekündigt', 'admin@example.com');

// Service manuell pausieren
$process->pauseService();

// Service wieder aktivieren
$process->resumeService();
```

### Statistiken abrufen

```php
$stats = $dunningService->getStatistics();
// Liefert: total_processes, active_processes, recovery_rate, etc.

// Für spezifische Company
$stats = $dunningService->getStatistics($company);
```

## Testing

### Stripe CLI Testing

```bash
# Webhook forwarding starten
stripe listen --forward-to localhost:8000/api/webhooks/stripe

# Payment Failure triggern
stripe trigger invoice.payment_failed

# Mit spezifischer Invoice ID
stripe invoices update in_xxx --payment_behavior=default_incomplete
```

### Manuelle Tests

```php
// Test-Dunning-Process erstellen
$webhookEvent = WebhookEvent::create([
    'provider' => 'stripe',
    'event_type' => 'invoice.payment_failed',
    'payload' => [
        'data' => [
            'object' => [
                'id' => 'in_test123',
                'amount_due' => 9900,
                'currency' => 'eur',
                'customer' => 'cus_test123'
            ]
        ]
    ]
]);

$process = $dunningService->handleFailedPayment($webhookEvent);
```

## Monitoring & Alerts

### Logs

```bash
# Dunning Retry Logs
tail -f storage/logs/dunning-retries.log

# Allgemeine Logs mit Dunning-Einträgen
tail -f storage/logs/laravel.log | grep -i dunning
```

### Metriken

Wichtige KPIs:
- **Recovery Rate**: Prozentsatz erfolgreich wiederhergestellter Zahlungen
- **Average Retry Count**: Durchschnittliche Anzahl Versuche bis zur Zahlung
- **Service Suspension Rate**: Anzahl pausierter Services
- **Outstanding Amount**: Gesamtbetrag offener Forderungen

### Alerts

Empfohlene Alerts:
- Recovery Rate < 70%
- Mehr als 10% der Companies mit `billing_status = suspended`
- Dunning-Prozesse älter als 30 Tage ohne Resolution

## Best Practices

1. **Retry Timing**
   - Beste Zeiten: 10-11 Uhr und 14-15 Uhr
   - Vermeide: Wochenenden, Feiertage, Monatsanfang

2. **Communication**
   - Klare, freundliche Sprache in E-Mails
   - Zahlungsmethoden-Update prominent platzieren
   - Support-Kontakt immer angeben

3. **Grace Periods**
   - Mindestens 14 Tage vor Service-Pausierung
   - Bei Stammkunden längere Grace Periods

4. **Manual Review**
   - High-Value Customers immer manuell prüfen
   - Bei wiederholten Fehlern Support kontaktieren

## Troubleshooting

### Problem: Retries werden nicht ausgeführt

1. Cronjob prüfen:
   ```bash
   crontab -l | grep dunning
   ```

2. Queue Worker läuft:
   ```bash
   php artisan horizon:status
   ```

3. Dunning-Konfiguration aktiv:
   ```sql
   SELECT * FROM dunning_configurations WHERE company_id = ?;
   ```

### Problem: E-Mails werden nicht gesendet

1. Mail-Queue prüfen:
   ```bash
   php artisan queue:work --queue=emails
   ```

2. Mail-Konfiguration testen:
   ```bash
   php artisan tinker
   >>> Mail::raw('Test', fn($m) => $m->to('test@example.com'));
   ```

### Problem: Service wird nicht pausiert

1. Konfiguration prüfen:
   ```php
   $config = DunningConfiguration::forCompany($company);
   dd($config->pause_service_on_failure, $config->pause_after_days);
   ```

2. Dunning-Process Status:
   ```sql
   SELECT * FROM dunning_processes WHERE company_id = ? AND status = 'active';
   ```

## Security Considerations

1. **Payment Retry Authorization**
   - Nur Off-Session Payments mit expliziter Kundenerlaubnis
   - PCI-Compliance beachten

2. **Data Protection**
   - Zahlungsfehler-Details nicht in Klartext loggen
   - Sensible Daten in E-Mails minimieren

3. **Rate Limiting**
   - Max. 1 Retry pro Tag pro Invoice
   - Circuit Breaker für Stripe API

## Future Enhancements

1. **Smart Retry Timing**
   - ML-basierte optimale Retry-Zeiten
   - Kundenspezifische Patterns erkennen

2. **Alternative Payment Methods**
   - Fallback auf andere Zahlungsmethoden
   - SEPA-Lastschrift als Alternative

3. **Customer Self-Service**
   - Portal für Zahlungsmethoden-Update
   - Payment Plan Optionen

4. **Advanced Analytics**
   - Churn Prediction basierend auf Dunning-Verhalten
   - Revenue Recovery Dashboard