# Stripe Setup Guide für AskProAI Prepaid-System

## 📋 Dein Geschäftsmodell
- **Prepaid-System**: Kunden laden Guthaben auf
- **Abrechnung Option 1**: Minutenpreis (0,15€/Min) mit sekundengenauer Abrechnung
- **Abrechnung Option 2**: Preis pro erfolgreich gebuchtem Termin

## 🔧 Stripe Dashboard Einstellungen

### 1. Basis-Konfiguration

#### a) Payment Methods aktivieren
1. Gehe zu: https://dashboard.stripe.com/settings/payment_methods
2. Aktiviere folgende Zahlungsmethoden:
   - ✅ **Cards** (Kreditkarten) - MUSS aktiviert sein
   - ✅ **SEPA Direct Debit** (Lastschrift) - Optional aber empfohlen für DE
   - ✅ **Giropay** - Optional für deutsche Kunden
   - ✅ **Sofort** - Optional für DACH-Region

#### b) Währungen
1. Gehe zu: https://dashboard.stripe.com/settings/payments
2. Stelle sicher, dass **EUR** als Währung aktiviert ist

### 2. Webhook Konfiguration

#### a) Webhook erstellen
1. Gehe zu: https://dashboard.stripe.com/webhooks
2. Klicke auf "Add endpoint"
3. Endpoint URL: `https://api.askproai.de/api/stripe/webhook`
4. Events to send - wähle folgende aus:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.succeeded`
   - `charge.failed`

#### b) Webhook Secret kopieren
1. Nach dem Erstellen klicke auf den Webhook
2. Kopiere das "Signing secret" (beginnt mit `whsec_`)
3. Füge es in deine `.env` ein:
   ```
   STRIPE_WEBHOOK_SECRET=whsec_dein_webhook_secret_hier
   ```

### 3. Test-Modus Setup

#### a) Test API Keys
1. Aktiviere den Test-Modus (Toggle oben rechts im Dashboard)
2. Gehe zu: https://dashboard.stripe.com/test/apikeys
3. Kopiere die Test-Keys:
   ```
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   ```

#### b) Test-Webhook
1. Erstelle einen separaten Test-Webhook:
   - URL: `https://api.askproai.de/api/stripe/webhook`
   - Gleiche Events wie oben
2. Kopiere das Test Webhook Secret

### 4. Checkout Settings

#### a) Checkout Anpassungen
1. Gehe zu: https://dashboard.stripe.com/settings/checkout
2. Konfiguriere:
   - **Allowed countries**: Germany, Austria, Switzerland
   - **Default country**: Germany
   - **Collect billing address**: Yes
   - **Collect phone number**: Optional

#### b) Branding
1. Gehe zu: https://dashboard.stripe.com/settings/branding
2. Lade dein Logo hoch
3. Setze deine Markenfarben

### 5. Business Settings

#### a) Geschäftsinformationen
1. Gehe zu: https://dashboard.stripe.com/settings/business
2. Stelle sicher, dass alle Infos korrekt sind:
   - Firmenname
   - Adresse
   - Steuernummer
   - Support-Kontakt

#### b) Customer Emails
1. Gehe zu: https://dashboard.stripe.com/settings/emails
2. Aktiviere:
   - ✅ Successful payments
   - ✅ Failed payments

## 🧪 Test-Transaktionen

### Test-Kreditkarten für Sandbox:
```
✅ Erfolgreiche Zahlung:     4242 4242 4242 4242
❌ Zahlung abgelehnt:        4000 0000 0000 9995
🔄 3D Secure erforderlich:   4000 0025 0000 3155
💳 SEPA Test IBAN:           DE89 3704 0044 0532 0130 00
```

### Test-Daten:
- Ablaufdatum: Beliebiges zukünftiges Datum (z.B. 12/34)
- CVC: Beliebige 3 Ziffern (z.B. 123)
- PLZ: Beliebige 5 Ziffern (z.B. 12345)

## 🔍 Verifizierung im Code

### 1. Aktiviere Test-Modus:
```bash
./test-stripe-billing.sh start
```

### 2. Prüfe Konfiguration:
```bash
php public/test-stripe-config.php
```

### 3. Teste Topup-Link:
```bash
php test-public-topup.php
```

## 📊 Reporting & Reconciliation

Für dein Prepaid-Modell brauchst du:

1. **Balance Tracking**:
   - Jede Aufladung wird in `balance_topups` gespeichert
   - Verbrauch wird in `call_charges` getrackt
   - Aktuelles Guthaben in `prepaid_balances`

2. **Reporting Webhooks** (optional):
   - `radar.early_fraud_warning.created`
   - `charge.dispute.created`

## ⚙️ Erweiterte Features (Optional)

### 1. Subscription für Auto-Topup
Wenn du automatische Aufladungen anbietest:
1. Aktiviere "Subscriptions" in Stripe
2. Erstelle ein Produkt für Auto-Topup

### 2. Invoice Settings
1. Gehe zu: https://dashboard.stripe.com/settings/billing/invoice
2. Konfiguriere:
   - Invoice prefix: "ASKPRO-"
   - Footer text: Deine Geschäftsbedingungen

## 🚀 Nächste Schritte

1. **Vervollständige `.env`**:
   ```env
   STRIPE_KEY=pk_test_dein_key
   STRIPE_SECRET=sk_test_dein_secret
   STRIPE_WEBHOOK_SECRET=whsec_dein_webhook_secret
   ```

2. **Teste im Test-Modus**:
   ```bash
   ./test-stripe-billing.sh start
   ```

3. **Öffne Test-Link**:
   https://api.askproai.de/topup/1

4. **Nach erfolgreichem Test**:
   - Wechsle zu Live-Keys
   - Erstelle Live-Webhook
   - Teste mit kleinem Betrag (1€)

## ❓ Troubleshooting

### "Payment method not available"
→ Aktiviere die Zahlungsmethode im Dashboard

### "Invalid API Key"
→ Prüfe ob Test/Live-Modus übereinstimmt

### "Webhook signature verification failed"
→ Webhook Secret in .env prüfen

### "Currency not supported"
→ EUR in Dashboard aktivieren