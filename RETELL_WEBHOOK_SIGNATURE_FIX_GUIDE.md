# Retell.ai Webhook Signature Verification Fix Guide

## 🚨 Problem

Die Webhook Signature Verification für Retell.ai schlägt fehl, obwohl die Konfiguration korrekt erscheint.

## 🔍 Diagnose

### 1. Aktuelle Konfiguration
```env
RETELL_TOKEN=key_e973c8962e09d6a34b3b1cf386
RETELL_WEBHOOK_SECRET=Hqj8iGCaWxGXdoKCqQQFaHsUjFKHFjUO
```

### 2. Retell.ai Signatur-Format
Retell verwendet das Format: `X-Retell-Signature: v=<timestamp>,d=<signature>`

- `v` = Timestamp in Millisekunden
- `d` = HMAC-SHA256 Signatur

### 3. Signatur-Berechnung
```
message = timestamp + body  // Konkatenation ohne Separator
signature = HMAC-SHA256(message, api_key)
```

**Wichtig**: Retell verwendet den **API Key** für die Signatur, NICHT ein separates Webhook Secret!

## 🛠️ Lösung

### Option 1: Quick Fix (Empfohlen)
Verwende das Debug-Skript, um die korrekte Signatur-Methode zu identifizieren:

```bash
php debug-retell-webhook-signature.php
```

Dann analysiere fehlerhafte Webhooks:

```bash
php analyze-retell-webhook-failures.php
```

### Option 2: Middleware Update
Aktualisiere die Middleware in `app/Http/Kernel.php`:

```php
// ALT:
\App\Http\Middleware\VerifyRetellSignature::class,

// NEU:
\App\Http\Middleware\VerifyRetellSignatureFixed::class,
```

### Option 3: Temporärer Bypass (NUR für Debugging!)
```php
// In VerifyRetellSignature.php, nach dem Logging:
if (config('app.debug') && config('services.retell.bypass_signature')) {
    Log::warning('[Retell Webhook] Signature verification bypassed (DEBUG MODE)');
    return $next($request);
}
```

Dann in `.env`:
```env
RETELL_BYPASS_SIGNATURE=true  # NUR für Debugging!
```

## 📋 Implementierungs-Checkliste

1. **Verifiziere API Key vs Webhook Secret**
   - [ ] Prüfe in Retell.ai Dashboard, ob ein separates Webhook Secret existiert
   - [ ] Oder ob der API Key für Signaturen verwendet wird

2. **Test mit echtem Webhook**
   - [ ] Löse einen Test-Call aus
   - [ ] Prüfe Logs: `tail -f storage/logs/laravel.log | grep Retell`
   - [ ] Notiere die exakte Signatur-Format

3. **Update Konfiguration**
   ```env
   # Falls Retell den API Key verwendet:
   RETELL_WEBHOOK_SECRET= # Leer lassen oder entfernen
   
   # Falls separates Secret:
   RETELL_WEBHOOK_SECRET=<secret-from-retell-dashboard>
   ```

4. **Deploy Fix**
   ```bash
   # 1. Test lokal
   php artisan test --filter RetellWebhook
   
   # 2. Deploy
   git add -A
   git commit -m "fix: Retell webhook signature verification"
   git push
   
   # 3. Auf Server
   php artisan config:cache
   php artisan queue:restart
   ```

## 🧪 Test-Befehle

### Manueller Webhook-Test
```bash
# Simuliere einen Retell Webhook
curl -X POST https://api.askproai.de/api/retell/webhook \
  -H "Content-Type: application/json" \
  -H "X-Retell-Signature: v=1234567890000,d=test_signature" \
  -d '{
    "event_type": "call_ended",
    "call_id": "test_123",
    "call": {
      "call_id": "test_123",
      "status": "ended"
    }
  }'
```

### Signature Generator
```php
// Test-Signatur generieren
$timestamp = time() * 1000;
$body = '{"event_type":"call_ended"}';
$apiKey = 'key_e973c8962e09d6a34b3b1cf386';

$signature = hash_hmac('sha256', $timestamp . $body, $apiKey);
echo "X-Retell-Signature: v=$timestamp,d=$signature\n";
```

## 🚀 Monitoring

Nach dem Fix:
```bash
# Überwache erfolgreiche Webhooks
watch -n 5 "grep 'Retell.*verified successfully' storage/logs/laravel.log | tail -n 10"

# Prüfe Call-Imports
php artisan tinker
>>> \App\Models\Call::where('created_at', '>=', now()->subHour())->count()
```

## ⚠️ Wichtige Hinweise

1. **Niemals Signature-Verification in Production deaktivieren** (außer temporär für Debugging)
2. **API Keys nicht in Logs ausgeben**
3. **Webhook-Endpunkt mit Rate Limiting schützen**
4. **Circuit Breaker für fehlerhafte Webhooks implementieren**

## 📊 Erwartete Ergebnisse

Nach erfolgreicher Implementierung:
- ✅ Webhook Signature Verification erfolgreich
- ✅ Calls werden automatisch importiert
- ✅ Dynamic Variables enthalten Termindaten
- ✅ Keine "Invalid signature" Fehler in Logs

---

**Status**: 🚧 In Bearbeitung
**Priority**: 🔴 Kritisch (blockiert Call-Import)