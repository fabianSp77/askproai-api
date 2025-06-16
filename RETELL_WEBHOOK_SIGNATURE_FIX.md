# Retell Webhook Signatur Problem

## Problem
Die Retell Webhooks werden mit "signature mismatch" abgelehnt.

## Debugging Steps

1. **Konfiguration geprüft**: ✅
   - API Key ist konfiguriert
   - Secret ist konfiguriert (gleicher Wert wie API Key)

2. **Erweiterte Debug-Logs hinzugefügt**: ✅
   - Zeigt erste 10 Zeichen der Signaturen
   - Zeigt ob Timestamp vorhanden ist
   - Zeigt erste 100 Zeichen des Body

## Mögliche Ursachen

1. **Falscher API Key**
   - Retell nutzt einen anderen Key für Webhooks
   - Prüfen Sie im Retell Dashboard die Webhook-Konfiguration

2. **Signatur-Format**
   - Retell sendet möglicherweise die Signatur in einem anderen Format
   - Eventuell Base64 encoded oder mit Prefix

3. **Timestamp-Problem**
   - Wenn kein Timestamp gesendet wird, aber erwartet wird

## Temporäre Lösung (NUR FÜR TESTS!)

Falls nötig, können wir temporär die Signatur-Verifizierung für bekannte Retell IPs deaktivieren:

```php
// In VerifyRetellSignature.php
$retellIps = ['100.20.5.228', '100.20.5.229']; // Retell IP Range
if (in_array($request->ip(), $retellIps)) {
    Log::warning('Skipping signature check for Retell IP', [
        'ip' => $request->ip()
    ]);
    return $next($request);
}
```

## Nächster Schritt

1. Machen Sie einen neuen Test-Anruf
2. Prüfen Sie die erweiterten Logs:
```bash
tail -f /var/www/api-gateway/storage/logs/laravel-*.log | grep "signature mismatch" -A 10
```

3. Die Debug-Informationen zeigen:
   - Wie die Signaturen aussehen
   - Ob ein Timestamp vorhanden ist
   - Den Anfang des Request Body

Mit diesen Informationen können wir das genaue Problem identifizieren.