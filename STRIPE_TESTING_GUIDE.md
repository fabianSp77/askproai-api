# Stripe Testing Guide für AskProAI

## 🚨 WARNUNG: Live-Keys sind aktiv!
Das System nutzt aktuell **LIVE Stripe Keys**. Jede Zahlung ist ECHT und wird belastet!

## Aktuelle Konfiguration

```env
# In .env - LIVE KEYS!
STRIPE_KEY=pk_live_51QjozIEypZR52surHzCXaOHdCy0Y6vddflKOD4qZy0xH4sEb9vcfOSHJnQfaAT6ixRFiJkA7Xp6V1VidJfR1NrFw00FQ0i4xWa
STRIPE_SECRET=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
STRIPE_WEBHOOK_SECRET=
```

## Sicheres Testing einrichten

### Option 1: Test-Environment Setup (Empfohlen)

1. **Test-Keys von Stripe Dashboard holen**:
   - Login: https://dashboard.stripe.com
   - Test-Mode aktivieren (Toggle oben rechts)
   - Developers → API keys
   - Test-Keys kopieren (beginnen mit `pk_test_` und `sk_test_`)

2. **Temporäre Test-Konfiguration erstellen**:
   ```bash
   # Backup der aktuellen Konfiguration
   cp /var/www/api-gateway/.env /var/www/api-gateway/.env.backup
   
   # Test-Environment erstellen
   cp /var/www/api-gateway/.env /var/www/api-gateway/.env.testing
   ```

3. **Test-Keys in .env.testing eintragen**:
   ```env
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_test_...
   ```

4. **Test-Script verwenden**:
   ```bash
   # Test-Modus aktivieren
   ./test-stripe-billing.sh start
   
   # Nach dem Test zurück zu Live
   ./test-stripe-billing.sh stop
   ```

### Option 2: Stripe CLI für lokales Testing

1. **Stripe CLI installieren**:
   ```bash
   # macOS
   brew install stripe/stripe-cli/stripe
   
   # Linux
   curl -s https://packages.stripe.dev/api/security/keypair/stripe-cli-gpg/public | gpg --dearmor | sudo tee /usr/share/keyrings/stripe.gpg
   echo "deb [signed-by=/usr/share/keyrings/stripe.gpg] https://packages.stripe.dev/stripe-cli-debian-local stable main" | sudo tee -a /etc/apt/sources.list.d/stripe.list
   sudo apt update
   sudo apt install stripe
   ```

2. **Login und Webhook-Forwarding**:
   ```bash
   stripe login
   stripe listen --forward-to https://api.askproai.de/api/stripe/webhook
   ```

3. **Test-Events senden**:
   ```bash
   stripe trigger checkout.session.completed
   stripe trigger payment_intent.succeeded
   ```

## Test-Kreditkarten

### Erfolgreiche Zahlungen:
- `4242 4242 4242 4242` - Visa (Standard-Testkarte)
- `5555 5555 5555 4444` - Mastercard
- `4000 0025 0000 3155` - Requires authentication

### Fehlgeschlagene Zahlungen:
- `4000 0000 0000 9995` - Zahlung abgelehnt
- `4000 0000 0000 0002` - Karte abgelehnt
- `4000 0000 0000 9987` - Zahlung fehlgeschlagen

### Test-Daten:
- **Ablaufdatum**: Beliebiges Datum in der Zukunft
- **CVV**: Beliebige 3 Ziffern
- **Postleitzahl**: Beliebige 5 Ziffern

## Test-Workflow im Business Portal

1. **Login**: https://api.askproai.de/business
2. **Navigation**: Billing → Guthaben aufladen
3. **Betrag wählen**: 
   - Empfohlene Beträge oder
   - Eigener Betrag (10-10.000 EUR)
4. **Zahlung**:
   - Klick "Zur Zahlung"
   - Weiterleitung zu Stripe Checkout
   - Test-Karte eingeben
5. **Erfolg/Fehler**:
   - Success: Redirect zu Billing-Übersicht
   - Cancel: Redirect mit Fehlermeldung

## Monitoring während des Tests

### Logs überwachen:
```bash
# Laravel Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i stripe

# Queue Jobs
php artisan horizon
```

### Datenbank prüfen:
```sql
-- Letzte Topups
SELECT * FROM balance_topups 
ORDER BY created_at DESC 
LIMIT 10;

-- Transaktionen
SELECT * FROM balance_transactions 
WHERE type = 'topup' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Stripe Dashboard:
- Test-Mode: https://dashboard.stripe.com/test/payments
- Live-Mode: https://dashboard.stripe.com/payments

## Webhook Testing

### Webhook-Events prüfen:
```bash
# In Stripe Dashboard
# Developers → Webhooks → [Webhook auswählen] → Sending webhook

# Oder mit CLI
stripe events list --limit 10
```

### Webhook-Signatur verifizieren:
```php
// Temporär in StripeWebhookController für Debug
Log::info('Stripe Webhook received', [
    'signature' => $request->header('Stripe-Signature'),
    'payload' => $request->getContent()
]);
```

## Sicherheits-Checkliste

- [ ] Test-Keys nur in isolierter Umgebung verwenden
- [ ] Nach Tests sofort Live-Keys wiederherstellen
- [ ] Keine Test-Transaktionen in Produktion
- [ ] Webhook-Secret konfigurieren für Produktion
- [ ] Test-Daten nach Abschluss löschen

## Troubleshooting

### "Payment failed"
1. Prüfe ob Test-Keys verwendet werden
2. Verifiziere Webhook-Konfiguration
3. Check Queue-Worker läuft

### "Invalid API Key"
1. Prüfe Key-Format (pk_test_ vs pk_live_)
2. Cache leeren: `php artisan config:clear`
3. PHP-FPM restart: `sudo systemctl restart php8.3-fpm`

### "Webhook signature verification failed"
1. Webhook-Secret in .env prüfen
2. Endpoint-URL verifizieren
3. Stripe CLI nutzen für lokale Tests

## Automatisiertes Testing

### PHPUnit Tests:
```bash
# Stripe-Mocking in Tests
php artisan test --filter StripeTest
```

### Feature Tests:
```php
// Mock Stripe in Tests
Stripe::shouldReceive('checkout.sessions.create')
    ->once()
    ->andReturn(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/test']);
```

## Wichtige Dateien

- `/app/Services/StripeTopupService.php` - Hauptlogik
- `/app/Http/Controllers/Portal/BillingController.php` - Portal-Endpoints
- `/app/Http/Controllers/Api/StripeWebhookController.php` - Webhook-Handler
- `/resources/js/components/billing/` - React-Komponenten

## Support

Bei Problemen:
1. Logs prüfen: `/storage/logs/laravel.log`
2. Stripe Dashboard: https://dashboard.stripe.com
3. Stripe Support: https://support.stripe.com

---

**Erstellt am**: 2025-07-06  
**Version**: 1.0