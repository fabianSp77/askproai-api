# Webhook Signature Verification Configuration

## 1. Retell Webhook Signature Setup

### Environment Variables (.env)
```bash
# Retell Configuration
RETELL_TOKEN=key_37da113d063ce12a93a9daf9eb97
RETELL_WEBHOOK_SECRET=your_webhook_secret_here  # Muss von Retell Dashboard kopiert werden
RETELL_BASE=https://api.retellai.com
```

### Retell Dashboard Configuration
1. Login bei https://retell.ai/
2. Navigiere zu Settings → Webhooks
3. Setze Webhook URL: `https://api.askproai.de/api/mcp/retell/webhook`
4. Kopiere den Webhook Secret
5. Füge den Secret in .env als `RETELL_WEBHOOK_SECRET` ein

### Aktivierung in Production

#### Option 1: Mit Signature Verification (Empfohlen)
```php
// routes/api-mcp.php
Route::prefix('retell')->middleware([
    'throttle:webhook',
    VerifyRetellSignature::class  // ✅ Signature Verification aktiv
])->group(function () {
    Route::post('/webhook', [RetellWebhookMCPController::class, 'processWebhook']);
});
```

#### Option 2: Mit IP Whitelist (Alternative)
```php
// config/services.php
'retell' => [
    'verify_ip' => true,  // Aktiviert IP Whitelist
    'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
];
```

Bekannte Retell IPs:
- 100.20.5.228
- 34.226.180.161
- 34.198.47.77
- 52.203.159.213
- 52.53.229.199
- 54.241.134.41
- 54.183.150.123
- 152.53.228.178

## 2. Test der Signature Verification

### Test Script
```bash
php test-webhook-signature.php
```

### Manueller Test mit curl
```bash
# Generiere Test Signature
WEBHOOK_SECRET="your_secret"
TIMESTAMP=$(date +%s)
BODY='{"event":"call_ended","call":{"call_id":"test123"}}'
SIGNATURE=$(echo -n "${TIMESTAMP}.${BODY}" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET" -binary | base64)

# Sende Request
curl -X POST https://api.askproai.de/api/mcp/retell/webhook \
  -H "Content-Type: application/json" \
  -H "X-Retell-Signature: $SIGNATURE" \
  -H "X-Retell-Timestamp: $TIMESTAMP" \
  -d "$BODY"
```

## 3. Monitoring & Debugging

### Log Einträge prüfen
```bash
tail -f storage/logs/laravel.log | grep "Retell Webhook"
```

### Typische Log Messages
```
[Retell Webhook] Signature verification started
[Retell Webhook] Signature valid
[Retell Webhook] Invalid signature
[Retell Webhook] Request from unknown IP
```

## 4. Fallback für Development/Testing

### Temporäre Test Route (NUR für Development!)
```php
// routes/api.php
Route::post('/test/mcp-webhook', function (Request $request) {
    // OHNE Signature Verification für Tests
    $controller = app(RetellWebhookMCPController::class);
    return $controller->processWebhook($request);
});
```

## 5. Production Checklist

- [ ] RETELL_WEBHOOK_SECRET in .env konfiguriert
- [ ] Webhook URL in Retell Dashboard eingetragen
- [ ] Signature Verification Middleware aktiv
- [ ] Test mit echtem Retell Webhook durchgeführt
- [ ] Monitoring für fehlgeschlagene Verifications
- [ ] Fallback/Test Routes entfernt

## 6. Troubleshooting

### "Missing X-Retell-Signature header"
- Webhook URL in Retell Dashboard prüfen
- Webhook Secret korrekt kopiert?

### "Invalid signature"
- Webhook Secret in .env und Retell Dashboard identisch?
- Keine zusätzlichen Leerzeichen im Secret?

### "Request from unknown IP"
- Neue Retell IP? → In Middleware hinzufügen
- Proxy/Load Balancer? → TrustProxies konfigurieren