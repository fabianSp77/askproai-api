# Retell.ai Webhook Secret finden - Anleitung

## Schritt-für-Schritt Anleitung

### 1. Bei Retell.ai einloggen
- Gehen Sie zu: https://app.retellai.com
- Loggen Sie sich mit Ihren Zugangsdaten ein

### 2. Webhook Secret finden

Es gibt mehrere mögliche Orte, je nach Retell.ai Version:

#### Option A: Im Dashboard / Settings
1. Klicken Sie auf **"Settings"** oder **"Einstellungen"** (Zahnrad-Icon)
2. Suchen Sie nach einem Bereich wie:
   - "Webhooks"
   - "API Settings"
   - "Security"
   - "Developer Settings"
3. Dort sollten Sie finden:
   - **Webhook Secret** (sieht aus wie: `whsec_...` oder `secret_...`)
   - **Signing Secret**
   - **Webhook Signing Key**

#### Option B: Bei der Webhook-Konfiguration
1. Gehen Sie zu **"Agents"** oder **"Phone Numbers"**
2. Bearbeiten Sie einen Agent oder eine Telefonnummer
3. Im Webhook-Bereich könnte ein Link sein wie:
   - "View webhook secret"
   - "Show signing secret"
   - "Webhook authentication"

#### Option C: Im Developer/API Bereich
1. Suchen Sie nach **"Developers"** oder **"API"** im Hauptmenü
2. Dort finden Sie möglicherweise:
   - API Keys
   - Webhook Secrets
   - Authentication Settings

### 3. Was Sie suchen
Der Webhook Secret:
- Ist NICHT derselbe wie Ihr API Key
- Beginnt oft mit einem Prefix wie `whsec_`, `secret_`, oder `sig_`
- Ist eine lange, zufällige Zeichenkette
- Wird speziell für die Webhook-Signatur-Verifizierung verwendet

### 4. Wenn Sie keinen Webhook Secret finden

Es ist möglich, dass Retell.ai die Webhook-Verifizierung optional macht. In diesem Fall:

1. **Prüfen Sie die Dokumentation**: https://docs.retellai.com
2. **Kontaktieren Sie den Support**: Fragen Sie nach dem "Webhook Signing Secret"
3. **Alternative**: Wenn kein Secret verfügbar ist, können wir die Verifizierung deaktivieren

## Temporäre Lösung (NUR für Tests!)

Wenn Sie den Secret nicht finden, können wir temporär die Verifizierung deaktivieren:

```bash
# In der .env Datei:
RETELL_WEBHOOK_SECRET=SKIP_VERIFICATION
```

**WICHTIG**: Dies ist unsicher und sollte nur temporär verwendet werden!

## Nach dem Finden des Secrets

1. Öffnen Sie `/var/www/api-gateway/.env`
2. Aktualisieren Sie:
   ```
   RETELL_WEBHOOK_SECRET=ihr_gefundener_webhook_secret
   ```
3. Cache leeren:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## Hilfe

Wenn Sie den Webhook Secret nicht finden können:
1. Screenshot vom Retell.ai Dashboard machen
2. Support kontaktieren mit der Frage: "Where can I find the webhook signing secret?"
3. In der Dokumentation nach "webhook", "signature", oder "authentication" suchen