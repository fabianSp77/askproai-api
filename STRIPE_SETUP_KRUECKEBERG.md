# Stripe Setup für Krückeberg GmbH - Zusammenfassung

## ✅ Was funktioniert bereits:

1. **Öffentlicher Topup Link**: https://api.askproai.de/topup/1
2. **Zahlungsabwicklung**: Stripe Checkout Session wird erfolgreich erstellt
3. **Manuelle Guthabenbuchung**: Zahlung wurde manuell verarbeitet und 10€ gutgeschrieben

## 🔧 Was noch eingerichtet werden muss:

### 1. Stripe Webhook konfigurieren

Gehen Sie zu Ihrem Stripe Dashboard:
1. Navigieren Sie zu **Developers → Webhooks**
2. Klicken Sie auf **Add endpoint**
3. Fügen Sie diese URL ein: `https://api.askproai.de/api/stripe/webhook`
4. Wählen Sie folgende Events aus:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
5. Kopieren Sie das **Signing secret** und fügen Sie es in die `.env` Datei ein:
   ```
   STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
   ```

### 2. Dollar-Symbol Problem

Das Dollar-Symbol ($) anstatt Euro (€) ist ein Frontend-Display-Problem. Die Währung ist intern korrekt als EUR gespeichert.

**Workaround**: Das Guthaben wird korrekt in EUR verarbeitet, nur die Anzeige zeigt $ an.

### 3. Automatische Verarbeitung

Sobald der Webhook konfiguriert ist, werden Zahlungen automatisch verarbeitet:
- Kunde zahlt über Link
- Stripe sendet Webhook
- System bucht Guthaben automatisch
- E-Mail-Bestätigung wird versendet

## 📋 Nächste Schritte:

1. **Webhook in Stripe konfigurieren** (siehe oben)
2. **Test-Zahlung durchführen** nach Webhook-Setup
3. **Frontend-Währungsanzeige korrigieren** (optional)

## 🔗 Ihre Links:

### Für Kunden:
- **Standard**: https://api.askproai.de/topup/1
- **50€ voreingestellt**: https://api.askproai.de/topup/1?amount=50
- **100€ voreingestellt**: https://api.askproai.de/topup/1?amount=100
- **250€ voreingestellt**: https://api.askproai.de/topup/1?amount=250

### QR-Code für 100€:
```
https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=https://api.askproai.de/topup/1?amount=100
```

## 💰 Aktueller Stand:

- **Guthaben**: 10,00 EUR (manuell gebucht)
- **System**: Funktionsfähig
- **Webhook**: Noch nicht konfiguriert (manuelle Verarbeitung nötig)