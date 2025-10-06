# 📅 Cal.com Test-Buchung Anleitung

## 🔴 Live-Monitor läuft!
Webhook-Events werden in Echtzeit überwacht.

## Service-Details für Test-Buchung:
- **Service**: Geheimer Termin
- **Event Type ID**: 2026300
- **URL**: https://cal.com/[ihr-username]/geheimer-termin

## Schritt-für-Schritt Test-Buchung:

### Option 1: Über Cal.com Dashboard
1. Gehen Sie zu **Cal.com Dashboard** → **Bookings**
2. Klicken Sie auf **"+ New Booking"**
3. Wählen Sie **"Geheimer Termin"**
4. Füllen Sie aus:
   - Name: Test Kunde
   - Email: test@example.com
   - Datum: Beliebig
   - Uhrzeit: Beliebig
5. Klicken Sie **"Confirm Booking"**

### Option 2: Über öffentliche Buchungsseite
1. Öffnen Sie: https://cal.com/[ihr-username]/geheimer-termin
2. Wählen Sie Datum und Zeit
3. Geben Sie ein:
   - Name: Test Webhook
   - Email: webhook-test@example.com
   - Telefon: +491234567890
4. Buchen Sie den Termin

## 🔍 Erwartete Webhook-Events:

### Bei Buchung:
```json
{
  "triggerEvent": "BOOKING_CREATED",
  "payload": {
    "bookingId": ...,
    "eventTypeId": 2026300,
    "title": "Geheimer Termin",
    "startTime": "...",
    "endTime": "...",
    "attendees": [...],
    "organizer": {...}
  }
}
```

### Bei Stornierung:
```json
{
  "triggerEvent": "BOOKING_CANCELLED",
  "payload": {
    "bookingId": ...,
    "reason": "..."
  }
}
```

### Bei Verschiebung:
```json
{
  "triggerEvent": "BOOKING_RESCHEDULED",
  "payload": {
    "bookingId": ...,
    "oldStartTime": "...",
    "newStartTime": "..."
  }
}
```

## 📊 Test-Aktionen nach Buchung:

1. **Buchung erstellen** → Warten auf BOOKING_CREATED
2. **Buchung verschieben** → Warten auf BOOKING_RESCHEDULED
3. **Buchung stornieren** → Warten auf BOOKING_CANCELLED

## 🎯 Überprüfung:

Die Events erscheinen in:
- **Live-Monitor** (läuft bereits)
- **Log-Datei**: `/storage/logs/calcom-webhooks.log`
- **Datenbank**: `webhook_events` Tabelle

## ⚡ Quick-Test via API:

```bash
curl -X POST https://cal.com/api/bookings \
  -H "Authorization: Bearer [CAL_API_KEY]" \
  -H "Content-Type: application/json" \
  -d '{
    "eventTypeId": 2026300,
    "start": "2025-10-01T14:00:00Z",
    "end": "2025-10-01T15:00:00Z",
    "name": "API Test",
    "email": "api-test@example.com",
    "timeZone": "Europe/Berlin"
  }'
```