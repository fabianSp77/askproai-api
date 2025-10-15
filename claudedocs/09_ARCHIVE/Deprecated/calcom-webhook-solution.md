# Cal.com Webhook Integration - Lösung

## Problem
Cal.com Webhook Ping-Test schlug fehl mit:
1. Zuerst 404 Not Found
2. Dann 302 Redirect
3. Laravel Middleware/Routing-Probleme

## Lösung
Direkter PHP-Endpoint ohne Laravel-Routing: `/calcom.php`

## Neue Webhook-URL für Cal.com

**URL zum Eintragen in Cal.com:**
```
https://api.askproai.de/calcom.php
```

## Implementierung

### Datei: `/public/calcom.php`
- Direkter PHP-Handler ohne Laravel-Dependencies für GET (Ping)
- GET-Request: Gibt `{"ping": "ok"}` zurück
- POST-Request: Validiert Signatur und loggt Events
- Umgeht alle Laravel-Middleware und Routing-Probleme

### Features:
1. **Ping-Test (GET)**: Sofortige Antwort ohne Laravel
2. **Webhook (POST)**:
   - Signatur-Validierung mit HMAC-SHA256
   - Logging in `/storage/logs/calcom-webhooks.log`
   - Gibt immer 200 OK zurück (verhindert Cal.com Retry-Loops)

### Sicherheit:
- Webhook-Secret: `6846aed4d55f6f3df70c40781e02d964aae34147f72763e1ccedd726e66dfff7`
- Signatur-Validierung bei POST-Requests
- Keine sensiblen Daten im Response

## Test-Befehle

### Ping-Test:
```bash
curl -X GET https://api.askproai.de/calcom.php
# Erwartete Antwort: {"ping":"ok"}
```

### Webhook-Test:
```bash
# Signatur berechnen
PAYLOAD='{"triggerEvent": "BOOKING_CREATED", "payload": {"id": 123}}'
SECRET='6846aed4d55f6f3df70c40781e02d964aae34147f72763e1ccedd726e66dfff7'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

# Webhook senden
curl -X POST https://api.askproai.de/calcom.php \
  -H "Content-Type: application/json" \
  -H "X-Cal-Signature-256: $SIGNATURE" \
  -d "$PAYLOAD"
```

## Logs überprüfen

```bash
tail -f /var/www/api-gateway/storage/logs/calcom-webhooks.log
```

## Unterstützte Events

Alle Cal.com Events werden akzeptiert:
- BOOKING_CREATED (Termin erstellt)
- BOOKING_CANCELLED (Termin abgesagt)
- BOOKING_REJECTED (Termin abgelehnt)
- BOOKING_REQUESTED (Buchung angefragt)
- BOOKING_PAYMENT_INITIATED (Zahlung eingeleitet)
- BOOKING_RESCHEDULED (Termin verlegt)
- BOOKING_PAID (Buchung bezahlt)
- BOOKING_NO_SHOW_UPDATED (Als Nichterscheinen aktualisiert)
- MEETING_ENDED (Meeting beendet)
- MEETING_STARTED (Meeting gestartet)
- RECORDING_READY (Aufnahme verfügbar)
- OOO_CREATED (Abwesenheit erstellt)
- TRANSCRIPTION_READY (Transkript erstellt)
- FORM_SUBMITTED (Formular gesendet)
- INSTANT_MEETING (Sofortmeeting)

## Nächste Schritte

1. ✅ URL in Cal.com ändern auf: `https://api.askproai.de/calcom.php`
2. ✅ Ping-Test in Cal.com durchführen
3. ✅ Test-Buchung erstellen und Webhook-Log prüfen
4. ⏳ Laravel-Integration für Webhook-Verarbeitung implementieren (aktuell nur Logging)

## Vorteile dieser Lösung

- **Keine Middleware-Konflikte**: Direkter PHP-Zugriff ohne Laravel-Stack
- **Schnelle Response**: Minimale Verarbeitungszeit für Ping-Tests
- **Zuverlässig**: Keine Redirects oder Session-Probleme
- **Debug-freundlich**: Einfaches Logging und Fehlerbehandlung
- **Cal.com-kompatibel**: Erfüllt alle Anforderungen (200 OK, JSON-Response)