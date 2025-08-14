# Cal.com Integration

## Einrichtung
- API-Schlüssel: `CALCOM_API_KEY` (aus .env)
- Benutzer-ID: `CALCOM_USER_ID` (aus .env) 
- Event-Typen:
  - Herren: `CALCOM_EVENT_TYPE_HERREN` (aus .env)
  - Damen: `CALCOM_EVENT_TYPE_DAMEN` (aus .env)

## Endpunkte
- Verfügbarkeit prüfen: `GET /availability`
- Termin buchen: `POST /bookings`
- Event-Typen abrufen: `GET /event-types`

## Bekannte Probleme
- Serverfehler bei Cal.com (Support kontaktiert)
- Zeitzonenprobleme (Europe/Berlin verwenden)
